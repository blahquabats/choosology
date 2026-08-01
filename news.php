<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auxfuncs.php';
require_once __DIR__ . '/labfeed.php';

/* Paginated updates fragment — exit before news queries. */
if (!empty($_GET['fragment']) && $_GET['fragment'] === 'updates') {
	header('Content-Type: text/html; charset=UTF-8');
	$page = isset($_GET['updates_page']) ? (int) $_GET['updates_page'] : 1;
	if ($page < 1) {
		$page = 1;
	}
	choosology_echo_updates_feed($db, $page);
	exit;
}

/**
 * @return array{0:bool,1?:string} [ ok, error message ]
 */
function choosology_news_table_ready(mysqli $db): array
{
	$chk = @mysqli_query($db, "SHOW TABLES LIKE 'news'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array(false, 'The <code>news</code> table was not found. Run <code>choosology-schema.sql</code> or <code>sql/news_setup.sql</code> on your database.');
	}
	return array(true);
}

function choosology_news_schema(mysqli $db): array
{
	static $cache = null;
	if (is_array($cache)) {
		return $cache;
	}
	$cache = array(
		'has_body' => false,
		'has_text' => false,
		'has_whenposted' => false,
		'has_by' => false,
	);
	$r = mysqli_query($db, 'SHOW COLUMNS FROM news');
	if (!$r) {
		return $cache;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$f = isset($row['Field']) ? (string) $row['Field'] : '';
		if ($f === 'body') {
			$cache['has_body'] = true;
		}
		if ($f === 'text') {
			$cache['has_text'] = true;
		}
		if ($f === 'whenposted') {
			$cache['has_whenposted'] = true;
		}
		if ($f === 'by') {
			$cache['has_by'] = true;
		}
	}
	return $cache;
}

function choosology_news_row_body_raw(array $row): string
{
	if (!isset($row['body'])) {
		return isset($row['text']) ? (string) $row['text'] : '';
	}
	return (string) $row['body'];
}

/** Datetime for stamps / sort: `whenposted` only. */
function choosology_news_stamp_raw(array $row): string
{
	if (!isset($row['whenposted'])) {
		return '';
	}
	$t = trim((string) $row['whenposted']);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	return $t;
}

function choosology_news_list_query(array $schema): string
{
	$cols = 'id, headline';
	if ($schema['has_whenposted']) {
		$cols .= ', whenposted';
	}
	if ($schema['has_body']) {
		$cols .= ', body';
	} elseif (!empty($schema['has_text'])) {
		$cols .= ', `text`';
	}
	$order = 'id DESC';
	if ($schema['has_whenposted']) {
		$order = 'COALESCE(whenposted, \'1970-01-01 00:00:00\') DESC, id DESC';
	}
	return "SELECT {$cols} FROM news ORDER BY {$order}";
}

function choosology_news_detail_select(array $schema): string
{
	$parts = array('id', 'headline');
	if ($schema['has_whenposted']) {
		$parts[] = 'whenposted';
	}
	if ($schema['has_by']) {
		$parts[] = '`by`';
	}
	if ($schema['has_body']) {
		$parts[] = 'body';
	} elseif (!empty($schema['has_text'])) {
		$parts[] = '`text`';
	}
	return implode(', ', $parts);
}

function choosology_news_excerpt(string $html, int $maxLen = 90): string
{
	$plain = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
	$lenFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
	if (function_exists('mb_substr')) {
		$cut = mb_substr($plain, 0, $maxLen);
	} else {
		$cut = substr($plain, 0, $maxLen);
	}
	if ($lenFn($plain) > $maxLen) {
		$cut .= '…';
	}
	return $cut;
}

/**
 * Main-column HTML only (for full page inside #news-article-mount, or AJAX fragment=article).
 */
function choosology_news_echo_main_article_html(
	bool $tableOk,
	string $listError,
	array $schema,
	int $newsId,
	$displayRow,
	bool $canManageNews = false
): void {
	if (!$tableOk) {
		echo '<p class="news-empty news-empty--main">' . $listError . '</p>';
		return;
	}
	if ($listError !== '') {
		echo '<section class="browse-section browse-section--intro" aria-labelledby="news-setup-heading">';
		echo '<h3 class="browse-section-heading" id="news-setup-heading"><span class="browse-section-num" aria-hidden="true">!</span> Setup</h3>';
		echo '<div class="browse-intro"><p>' . $listError . '</p></div></section>';
		return;
	}
	if ($displayRow) {
		$h = htmlspecialchars((string) $displayRow['headline'], ENT_QUOTES, 'UTF-8');
		$bodyRaw = choosology_news_row_body_raw($displayRow);
		$bodySafe = $bodyRaw !== '' ? strip_tags($bodyRaw, '<p><br><strong><em><b><i><a><ul><ol><li><h2><h3><blockquote><code><pre>') : '<p><em>No body text for this item yet.</em></p>';
		$stampRaw = choosology_news_stamp_raw($displayRow);
		$pub = '';
		if ($stampRaw !== '') {
			$ts = strtotime($stampRaw);
			if ($ts > 0) {
				$iso = date('c', $ts);
				$label = htmlspecialchars(date('M j, Y', $ts), ENT_QUOTES, 'UTF-8');
				$pub = '<p class="news-meta"><time datetime="' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . '">' . $label . '</time></p>';
			}
		}
		$bylineHtml = '';
		if ($newsId > 0 && !empty($schema['has_by']) && isset($displayRow['by'])) {
			$by = trim((string) $displayRow['by']);
			if ($by !== '') {
				$byEsc = htmlspecialchars($by, ENT_QUOTES, 'UTF-8');
				$bylineHtml = '<p class="news-byline">By ' . $byEsc . '</p>';
			}
		}
		echo '<article class="news-article" aria-labelledby="news-article-title">';
		echo '<h3 class="news-article-title" id="news-article-title">' . $h . '</h3>';
		echo $bylineHtml;
		echo $pub;
		echo '<div class="news-article-body">' . $bodySafe . '</div>';
		$id = (int) ($displayRow['id'] ?? $newsId);
		if ($canManageNews && $id > 0) {
			echo '<div class="news-article-admin">';
			echo '<button type="button" class="news-admin-edit-current" data-id="' . $id . '">Edit item</button>';
			echo '<button type="button" class="news-admin-delete-current" data-id="' . $id . '">Delete item</button>';
			echo '</div>';
		}
		echo '</article>';
		return;
	}
	if ($newsId > 0) {
		echo '<section class="browse-section browse-section--intro" aria-labelledby="news-missing-heading">';
		echo '<h3 class="browse-section-heading" id="news-missing-heading"><span class="browse-section-num" aria-hidden="true">?</span> Not found</h3>';
		echo '<div class="browse-intro"><p>That news item does not exist or was removed.</p>';
		echo '<p><a class="link" href="#/news">&larr; Back to all notes</a></p></div></section>';
		return;
	}
	echo '<p class="news-empty news-empty--main">No items yet. Add rows to the <code>news</code> table or re-run <code>sql/news_setup.sql</code>.</p>';
}

$schemaEmpty = array(
	'has_body' => false,
	'has_text' => false,
	'has_whenposted' => false,
	'has_by' => false,
);

$tableOk = choosology_news_table_ready($db);
$schema = $tableOk[0] ? choosology_news_schema($db) : $schemaEmpty;
$newsId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$rows = array();
$single = null;
$listError = $tableOk[0] ? '' : (string) ($tableOk[1] ?? '');
$canManageNews = !empty($_SESSION['user']) && (string) $_SESSION['user'] === 'The Grasssmith';

if ($tableOk[0]) {
	$listSql = choosology_news_list_query($schema);
	$rows = runquery_assoc($listSql);
	if (!is_array($rows)) {
		$rows = array();
	}
	if ($newsId > 0) {
		$escId = (int) $newsId;
		$detailCols = choosology_news_detail_select($schema);
		$detailSql = "SELECT {$detailCols} FROM news WHERE id = {$escId} LIMIT 1";
		$one = runquery_assoc($detailSql);
		if (is_array($one) && isset($one[0])) {
			$single = $one[0];
		}
	}
}

/** Row shown in main column: explicit id match, else latest when browsing home. */
$displayRow = null;
if ($tableOk[0]) {
	if ($newsId > 0) {
		if ($single) {
			$displayRow = $single;
		}
	} elseif (count($rows) > 0) {
		$displayRow = $rows[0];
	}
}

$latestId = ($tableOk[0] && count($rows) > 0) ? (int) ($rows[0]['id'] ?? 0) : 0;
$updatesPage = isset($_GET['updates_page']) ? (int) $_GET['updates_page'] : 1;
if ($updatesPage < 1) {
	$updatesPage = 1;
}

if (!empty($_GET['fragment']) && $_GET['fragment'] === 'article') {
	header('Content-Type: text/html; charset=UTF-8');
	choosology_news_echo_main_article_html(
		(bool) $tableOk[0],
		$listError,
		$schema,
		$newsId,
		$displayRow,
		$canManageNews
	);
	exit;
}

?>
<div id="wholepage" class="browse-program browse-program--news">
	<div class="browse-program-head">
		<div class="browse-program-head-inner">
			<p class="browse-program-eyebrow">Lab communications <span class="browse-program-eyebrow-tag">announcements</span></p>
			<h2 class="browse-program-title">News &amp; notes</h2>
		</div>
	</div>

	<div class="browse-program-body">
		<?php if ($listError !== '') { ?>
			<section class="browse-section browse-section--intro" aria-labelledby="news-setup-heading">
				<h3 class="browse-section-heading" id="news-setup-heading"><span class="browse-section-num" aria-hidden="true">!</span> Setup</h3>
				<div class="browse-intro">
					<p><?php echo $listError; ?></p>
				</div>
			</section>
		<?php } else { ?>
		<div class="browse-program-layout news-archive-layout">
			<main class="browse-program-main">
				<div id="news-article-mount" class="news-article-mount">
					<?php
					choosology_news_echo_main_article_html(
						(bool) $tableOk[0],
						$listError,
						$schema,
						$newsId,
						$displayRow,
						$canManageNews
					);
					?>
				</div>
			</main>

			<aside class="browse-program-aside news-archive-aside" aria-labelledby="news-list-heading">
				<section class="browse-section browse-section--intro" aria-labelledby="news-list-heading">
					<h3 class="browse-section-heading" id="news-list-heading"><span class="browse-section-num" aria-hidden="true">01</span> Recent items</h3>
					<div class="news-archive-results">
						<?php
						if (count($rows) === 0) {
							echo '<p class="news-empty">No items yet. Add rows to the <code>news</code> table or re-run <code>sql/news_setup.sql</code>.</p>';
						} else {
							foreach ($rows as $row) {
								$rid = (int) ($row['id'] ?? 0);
								$title = htmlspecialchars((string) ($row['headline'] ?? ''), ENT_QUOTES, 'UTF-8');
								$bodySrc = choosology_news_row_body_raw($row);
								$excerpt = '';
								if ($bodySrc !== '') {
									$excerpt = htmlspecialchars(choosology_news_excerpt($bodySrc), ENT_QUOTES, 'UTF-8');
								}
								$stampRaw = choosology_news_stamp_raw($row);
								$dateStr = '';
								if ($stampRaw !== '') {
									$ts = strtotime($stampRaw);
									if ($ts > 0) {
										$dateStr = htmlspecialchars(date('M j, Y', $ts), ENT_QUOTES, 'UTF-8');
									}
								}
								$active = (($newsId > 0 && $rid === $newsId) || ($newsId === 0 && $latestId > 0 && $rid === $latestId)) ? ' news-card--active' : '';
								echo '<a class="news-card' . $active . '" href="#/news/' . $rid . '">';
								echo '<span class="news-card-title">' . $title . '</span>';
								if ($dateStr !== '') {
									echo '<span class="news-card-date">' . $dateStr . '</span>';
								}
								if ($excerpt !== '') {
									echo '<span class="news-card-excerpt">' . $excerpt . '</span>';
								}
								echo '</a>';
							}
						}
						?>
					</div>
				</section>
				<section class="browse-section browse-section--intro news-updates-section" aria-labelledby="news-updates-heading">
					<h3 class="browse-section-heading" id="news-updates-heading"><span class="browse-section-num" aria-hidden="true">02</span> Recent updates</h3>
					<div id="news-updates-mount" class="news-updates-mount">
						<?php choosology_echo_updates_feed($db, $updatesPage); ?>
					</div>
				</section>
				<?php if ($canManageNews && $tableOk[0]) { ?>
				<section class="browse-section browse-section--intro news-admin-section" aria-labelledby="news-admin-heading">
					<h3 class="browse-section-heading" id="news-admin-heading"><span class="browse-section-num" aria-hidden="true">03</span> Admin</h3>
					<form id="news-add-form" class="news-admin-form" action="ajax/addnews.php" method="post">
						<input type="hidden" id="news-edit-id" name="id" value="">

						<label class="news-admin-label" for="news-add-headline">Headline</label>
						<input type="text" id="news-add-headline" name="headline" maxlength="255" required>

						<label class="news-admin-label" for="news-add-by">Byline</label>
						<input type="text" id="news-add-by" name="by" maxlength="45" value="The Grasssmith">

						<label class="news-admin-label" for="news-add-body">Body</label>
						<textarea id="news-add-body" name="body" rows="7" required></textarea>

						<div class="news-admin-actions">
							<button type="submit" id="news-add-submit">Add news item</button>
							<button type="button" id="news-edit-cancel" style="display:none;">Cancel edit</button>
							<span id="news-add-status" class="news-admin-status" aria-live="polite"></span>
						</div>
					</form>
				</section>
				<?php } ?>
			</aside>
		</div>
		<?php } ?>
	</div>
</div>
