<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/news-helpers.php';
require_once dirname(__DIR__) . '/lib/date-format-helpers.php';
require_once dirname(__DIR__) . '/lib/feed-helpers.php';

global $db;

$tableOk = choosology_news_table_ready($db);
$schemaEmpty = array(
	'has_body' => false,
	'has_text' => false,
	'has_whenposted' => false,
	'has_by' => false,
);
$schema = $tableOk[0] ? choosology_news_schema($db) : $schemaEmpty;
$newsId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$listError = '';
$rows = array();
$displayRow = null;

if (!$tableOk[0]) {
	$listError = (string) ($tableOk[1] ?? 'News unavailable.');
} else {
	$listSql = choosology_news_list_query($schema);
	$listRes = mysqli_query($db, $listSql);
	if ($listRes) {
		while ($row = mysqli_fetch_assoc($listRes)) {
			$rows[] = $row;
		}
	} else {
		$listError = 'Could not load news items.';
	}
	if ($newsId > 0) {
		$detailCols = choosology_news_detail_select($schema);
		$detailSql = 'SELECT ' . $detailCols . ' FROM news WHERE id = ' . $newsId . ' LIMIT 1';
		$dr = mysqli_query($db, $detailSql);
		$displayRow = $dr ? mysqli_fetch_assoc($dr) : null;
		if (!$displayRow) {
			$newsId = 0;
		}
	}
	if ($newsId === 0 && count($rows) > 0) {
		$latestId = (int) ($rows[0]['id'] ?? 0);
		if ($latestId > 0) {
			$detailCols = choosology_news_detail_select($schema);
			$detailSql = 'SELECT ' . $detailCols . ' FROM news WHERE id = ' . $latestId . ' LIMIT 1';
			$dr = mysqli_query($db, $detailSql);
			$displayRow = $dr ? mysqli_fetch_assoc($dr) : $rows[0];
			$newsId = $latestId;
		}
	}
}

choosology_lite_header(array('title' => 'News', 'active' => 'news'));
?>
<fieldset class="lite-panel">
	<legend>Lab notes</legend>
	<?php if ($listError !== '') { ?>
		<p class="lite-muted"><?php echo $listError; ?></p>
	<?php } elseif (!$displayRow) { ?>
		<p class="lite-muted">No news items yet.</p>
	<?php } else {
		$h = htmlspecialchars((string) ($displayRow['headline'] ?? ''), ENT_QUOTES, 'UTF-8');
		$bodySafe = choosology_news_body_safe(choosology_news_row_body_raw($displayRow));
		$stampRaw = choosology_news_stamp_raw($displayRow);
		$stampLabel = $stampRaw !== '' ? choosology_format_user_date($stampRaw, 'date') : '';
		$by = '';
		if (!empty($schema['has_by']) && isset($displayRow['by'])) {
			$by = trim((string) $displayRow['by']);
		}
		?>
		<article class="lite-news-article">
			<h2 class="lite-news-title"><?php echo $h; ?></h2>
			<?php if ($by !== '') { ?>
				<p class="lite-meta">By <?php echo htmlspecialchars($by, ENT_QUOTES, 'UTF-8'); ?></p>
			<?php } ?>
			<?php if ($stampLabel !== '') { ?>
				<p class="lite-meta"><time datetime="<?php echo htmlspecialchars(choosology_feed_date_iso($stampRaw), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($stampLabel, ENT_QUOTES, 'UTF-8'); ?></time></p>
			<?php } ?>
			<div class="lite-news-body"><?php echo $bodySafe; ?></div>
		</article>
	<?php } ?>
</fieldset>

<fieldset class="lite-panel">
	<legend>All items</legend>
	<?php if (count($rows) === 0) { ?>
		<p class="lite-muted">No items in the archive.</p>
	<?php } else { ?>
		<nav class="lite-news-nav" aria-label="News items">
			<ul class="lite-list">
				<?php foreach ($rows as $row) {
					$rid = (int) ($row['id'] ?? 0);
					$title = trim((string) ($row['headline'] ?? ''));
					if ($title === '') {
						$title = 'Item #' . $rid;
					}
					$stampRaw = choosology_news_stamp_raw($row);
					$stampLabel = $stampRaw !== '' ? choosology_format_user_date($stampRaw, 'date') : '';
					$excerpt = '';
					$bodySrc = choosology_news_row_body_raw($row);
					if ($bodySrc !== '') {
						$excerpt = choosology_news_excerpt($bodySrc, 80);
					}
					$href = htmlspecialchars(choosology_lite_url('news.php?id=' . $rid), ENT_QUOTES, 'UTF-8');
					$cur = ($rid === $newsId) ? ' aria-current="page"' : '';
					?>
					<li<?php echo $rid === $newsId ? ' class="lite-news-nav-current"' : ''; ?>>
						<a href="<?php echo $href; ?>"<?php echo $cur; ?>><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
						<?php if ($stampLabel !== '') { ?>
							<span class="lite-muted"> · <?php echo htmlspecialchars($stampLabel, ENT_QUOTES, 'UTF-8'); ?></span>
						<?php } ?>
						<?php if ($excerpt !== '') { ?>
							<div class="lite-muted"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></div>
						<?php } ?>
					</li>
				<?php } ?>
			</ul>
		</nav>
	<?php } ?>
</fieldset>
<?php
choosology_lite_date_format_form('news.php' . ($newsId > 0 ? ('?id=' . $newsId) : ''));
choosology_lite_footer();
