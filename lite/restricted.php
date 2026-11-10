<?php
/**
 * Lite RESTRICTED hub.
 *
 * The full visual guide editor is Classic-only; Lite keeps the news desk,
 * credentials, treasury, and comment moderation usable with plain forms.
 */
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/messagesfunc.php';
require_once dirname(__DIR__) . '/lib/restricted-helpers.php';
require_once dirname(__DIR__) . '/lib/news-admin-helpers.php';

global $db;

if (!choosology_restricted_is_admin()) {
	http_response_code(403);
	choosology_lite_header(array('title' => 'RESTRICTED', 'active' => 'restricted'));
	echo '<fieldset class="lite-panel"><legend>Access denied</legend><p>Lab admin authorization is required.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$rawBody = file_get_contents('php://input');
	$form = choosology_raw_form_fields($rawBody === false ? '' : $rawBody);
	$action = (string) ($form['action'] ?? '');
	$result = array('ok' => false, 'error' => 'Unknown action.');
	$query = array();
	if ($action === 'save_news') {
		$item = array(
			'kind' => (string) ($form['kind'] ?? ''),
			'id' => (int) ($form['id'] ?? 0),
			'headline' => (string) ($form['headline'] ?? ''),
			'by' => (string) ($form['by'] ?? ''),
			'body' => (string) ($form['body'] ?? ''),
		);
		$result = choosology_news_admin_save($db, $item, (string) $_SESSION['user']);
		if (!empty($result['ok'])) {
			$query['news'] = $result['kind'] . ':' . $result['id'];
		} else {
			/* Keep what was typed so a rejected save does not cost the admin an article. */
			$_SESSION['restricted_news_draft'] = $item;
			$query['news'] = ($item['kind'] === 'update' ? 'update' : 'news') . ':' . max(0, $item['id']);
		}
	} elseif ($action === 'delete_news') {
		$result = choosology_news_admin_delete(
			$db,
			(string) ($form['kind'] ?? ''),
			(int) ($form['id'] ?? 0)
		);
	} elseif ($action === 'award_degree') {
		if (trim((string) ($form['record'] ?? '')) !== '') {
			$query['record'] = trim((string) $form['record']);
		}
		$result = choosology_restricted_grant_degree(
			$db,
			(string) $_SESSION['user'],
			(string) ($form['to'] ?? ''),
			(string) ($form['degree'] ?? '')
		);
	} elseif ($action === 'award_scrip') {
		$result = choosology_datascrip_admin_grant(
			$db,
			(string) $_SESSION['user'],
			(string) ($form['to'] ?? ''),
			(int) ($form['amount'] ?? 0),
			(string) ($form['memo'] ?? ''),
			!empty($form['all_users'])
		);
	} elseif ($action === 'approve' || $action === 'delete' || $action === 'approve_all') {
		$result = choosology_restricted_moderate_comment(
			$db,
			$action,
			(int) ($form['comment_id'] ?? 0)
		);
	}
	$_SESSION['restricted_flash'] = !empty($result['ok'])
		? 'Action completed.'
		: (string) ($result['error'] ?? 'Action failed.');
	header('Location: ' . choosology_lite_url('restricted.php' . ($query ? '?' . http_build_query($query) : '')));
	exit;
}

$flash = isset($_SESSION['restricted_flash']) ? (string) $_SESSION['restricted_flash'] : '';
unset($_SESSION['restricted_flash']);
$users = choosology_restricted_usernames($db);
$degrees = choosology_achievement_catalog();
$degreeCounts = choosology_restricted_degree_counts($db);
$comments = choosology_restricted_pending_comments($db);
$self = htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8');

$desk = choosology_news_admin_desk($db);
$queryFields = choosology_raw_form_fields((string) ($_SERVER['QUERY_STRING'] ?? ''));
$newsKey = choosology_news_admin_parse_key((string) ($queryFields['news'] ?? ''));
$newsItem = null;
$newsMissing = false;
if ($newsKey !== null) {
	$newsItem = array('kind' => $newsKey['kind'], 'id' => 0, 'headline' => '', 'by' => (string) $_SESSION['user'], 'body' => '');
	if ($newsKey['id'] > 0) {
		$newsMissing = true;
		foreach ($newsKey['kind'] === 'update' ? $desk['updates'] : $desk['posts'] as $row) {
			if ((int) $row['id'] === $newsKey['id']) {
				$newsItem = array(
					'kind' => $newsKey['kind'],
					'id' => (int) $row['id'],
					'headline' => (string) ($newsKey['kind'] === 'update' ? $row['text'] : $row['headline']),
					'by' => (string) ($row['by'] ?? ''),
					'body' => (string) ($row['body'] ?? ''),
				);
				$newsMissing = false;
				break;
			}
		}
		if ($newsMissing) {
			$newsItem = null;
		}
	}
}
$draft = isset($_SESSION['restricted_news_draft']) && is_array($_SESSION['restricted_news_draft'])
	? $_SESSION['restricted_news_draft']
	: null;
unset($_SESSION['restricted_news_draft']);
if ($draft !== null && $newsItem !== null
	&& (string) ($draft['kind'] ?? '') === $newsItem['kind']
	&& (int) ($draft['id'] ?? 0) === $newsItem['id']) {
	$newsItem['headline'] = (string) ($draft['headline'] ?? '');
	$newsItem['by'] = (string) ($draft['by'] ?? '');
	$newsItem['body'] = (string) ($draft['body'] ?? '');
}

$recordQuery = trim((string) ($queryFields['record'] ?? ''));
$record = $recordQuery !== '' ? choosology_restricted_degree_record($db, $recordQuery) : null;
$classicGuide = choosology_classic_url('mystuff/restricted');

choosology_lite_header(array('title' => 'RESTRICTED', 'active' => 'restricted'));
?>
<fieldset class="lite-panel lite-restricted">
	<legend>RESTRICTED — Admin terminal</legend>
	<p>Authorized lab administration.</p>
	<?php if ($flash !== '') { ?>
	<p class="lite-alert"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
	<?php } ?>
	<p><a class="lite-btn" href="<?php echo htmlspecialchars($classicGuide, ENT_QUOTES, 'UTF-8'); ?>">Open mascot and interaction editor in Classic</a></p>
</fieldset>

<datalist id="restricted-users">
	<?php foreach ($users as $name) { ?>
	<option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
	<?php } ?>
</datalist>

<fieldset class="lite-panel">
	<legend>News desk</legend>
	<p class="lite-muted">News posts get a headline, byline, and full article. Minor updates are one-line patch notes in the Home and News feeds.</p>
	<p>
		<a class="lite-btn" href="<?php echo $self; ?>?news=news:0">New news post</a>
		<a class="lite-btn" href="<?php echo $self; ?>?news=update:0">New minor update</a>
	</p>
	<form method="get" action="<?php echo $self; ?>" class="lite-form">
		<label>Edit an existing item
			<select name="news">
				<optgroup label="News posts">
					<?php foreach ($desk['posts'] as $row) {
						$key = 'news:' . (int) $row['id'];
						$sel = ($newsItem !== null && $newsItem['kind'] === 'news' && $newsItem['id'] === (int) $row['id']) ? ' selected' : '';
						?>
					<option value="<?php echo $key; ?>"<?php echo $sel; ?>><?php
						echo htmlspecialchars(trim($row['when_label'] . ' · ' . $row['headline'], ' ·'), ENT_QUOTES, 'UTF-8');
					?></option>
					<?php } ?>
				</optgroup>
				<optgroup label="Minor updates">
					<?php foreach ($desk['updates'] as $row) {
						$key = 'update:' . (int) $row['id'];
						$sel = ($newsItem !== null && $newsItem['kind'] === 'update' && $newsItem['id'] === (int) $row['id']) ? ' selected' : '';
						?>
					<option value="<?php echo $key; ?>"<?php echo $sel; ?>><?php
						echo htmlspecialchars(trim($row['when_label'] . ' · ' . $row['text'], ' ·'), ENT_QUOTES, 'UTF-8');
					?></option>
					<?php } ?>
				</optgroup>
			</select>
		</label>
		<button type="submit" class="lite-btn">Open</button>
	</form>
	<?php if ($newsMissing) { ?>
	<p class="lite-alert">That item no longer exists.</p>
	<?php } ?>
	<?php if ($newsItem !== null) {
		$isUpdate = $newsItem['kind'] === 'update';
		$noun = $isUpdate ? 'minor update' : 'news post';
		?>
	<hr>
	<h3><?php echo ($newsItem['id'] > 0 ? 'Editing ' : 'New ') . $noun; ?></h3>
	<form method="post" action="<?php echo $self; ?>" class="lite-form">
		<input type="hidden" name="kind" value="<?php echo $newsItem['kind']; ?>">
		<input type="hidden" name="id" value="<?php echo (int) $newsItem['id']; ?>">
		<label><?php echo $isUpdate ? 'Update text' : 'Headline'; ?>
			<input type="text" name="headline" maxlength="<?php echo $isUpdate ? CHOOSOLOGY_NEWS_UPDATE_MAX : CHOOSOLOGY_NEWS_HEADLINE_MAX; ?>" value="<?php echo htmlspecialchars($newsItem['headline'], ENT_QUOTES, 'UTF-8'); ?>" required>
		</label>
		<?php if (!$isUpdate) { ?>
		<label>Byline
			<input type="text" name="by" maxlength="<?php echo CHOOSOLOGY_NEWS_BYLINE_MAX; ?>" value="<?php echo htmlspecialchars($newsItem['by'], ENT_QUOTES, 'UTF-8'); ?>">
		</label>
		<label>Body (basic HTML: paragraphs, links, lists, emphasis)
			<textarea name="body" rows="12" class="lite-desk-body" required><?php echo htmlspecialchars($newsItem['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
		</label>
		<?php } ?>
		<p>
			<button type="submit" class="lite-btn" name="action" value="save_news"><?php echo $newsItem['id'] > 0 ? 'Save changes' : 'Publish'; ?></button>
			<?php if ($newsItem['id'] > 0) { ?>
			<button type="submit" class="lite-btn" name="action" value="delete_news" formnovalidate>Delete</button>
			<?php } ?>
			<a class="lite-btn" href="<?php echo $self; ?>">Cancel</a>
		</p>
	</form>
	<?php } ?>
</fieldset>

<fieldset class="lite-panel">
	<legend>Degrees</legend>
	<table class="lite-ledger-table">
		<thead><tr><th scope="col">Degree</th><th scope="col">Requirement</th><th scope="col">Holders</th></tr></thead>
		<tbody>
			<?php foreach ($degrees as $key => $meta) { ?>
			<tr>
				<th scope="row"><?php echo htmlspecialchars((string) $meta['label'], ENT_QUOTES, 'UTF-8'); ?></th>
				<td><?php echo htmlspecialchars((string) $meta['blurb'], ENT_QUOTES, 'UTF-8'); ?></td>
				<td><?php echo (int) ($degreeCounts[$key] ?? 0); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<form method="get" action="<?php echo $self; ?>" class="lite-form">
		<label>Researcher record
			<input type="text" name="record" list="restricted-users" maxlength="45" value="<?php echo htmlspecialchars($recordQuery, ENT_QUOTES, 'UTF-8'); ?>" required>
		</label>
		<button type="submit" class="lite-btn">Look up</button>
	</form>
	<?php if ($record !== null && empty($record['ok'])) { ?>
	<p class="lite-alert"><?php echo htmlspecialchars((string) ($record['error'] ?? 'Lookup failed.'), ENT_QUOTES, 'UTF-8'); ?></p>
	<?php } elseif ($record !== null) { ?>
	<h3><?php echo htmlspecialchars($record['user'], ENT_QUOTES, 'UTF-8'); ?></h3>
	<ul class="lite-list">
		<?php foreach ($record['degrees'] as $degree) { ?>
		<li>
			<strong><?php echo htmlspecialchars((string) $degree['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
			<?php if (!empty($degree['earned_at'])) { ?>
			<span class="lite-muted"> · Earned <?php echo htmlspecialchars((string) $degree['earned_label'], ENT_QUOTES, 'UTF-8'); ?></span>
			<?php } else { ?>
			<span class="lite-muted"> · Not held</span>
			<form method="post" action="<?php echo $self; ?>" class="lite-inline-form">
				<input type="hidden" name="action" value="award_degree">
				<input type="hidden" name="to" value="<?php echo htmlspecialchars($record['user'], ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" name="record" value="<?php echo htmlspecialchars($record['user'], ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" name="degree" value="<?php echo htmlspecialchars((string) $degree['key'], ENT_QUOTES, 'UTF-8'); ?>">
				<button type="submit" class="lite-btn">Grant</button>
			</form>
			<?php } ?>
		</li>
		<?php } ?>
	</ul>
	<?php } ?>
</fieldset>

<fieldset class="lite-panel">
	<legend>Grant Degree</legend>
	<p class="lite-muted">Includes the normal DataScrip reward, trophy trinket, and CLIC notice.</p>
	<form method="post" action="<?php echo $self; ?>" class="lite-form">
		<input type="hidden" name="action" value="award_degree">
		<label>Recipient <input name="to" list="restricted-users" maxlength="45" required></label>
		<label>Degree
			<select name="degree" required>
				<?php foreach ($degrees as $key => $meta) { ?>
				<option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"><?php
					echo htmlspecialchars((string) $meta['label'], ENT_QUOTES, 'UTF-8');
				?></option>
				<?php } ?>
			</select>
		</label>
		<button type="submit" class="lite-btn">Grant Degree</button>
	</form>
</fieldset>

<fieldset class="lite-panel">
	<legend>Award DataScrip</legend>
	<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>" class="lite-form">
		<input type="hidden" name="action" value="award_scrip">
		<label>Recipient <input name="to" list="restricted-users" maxlength="45"></label>
		<label class="lite-radio"><input type="checkbox" name="all_users" value="1"> Award every account</label>
		<label>Amount <input type="number" name="amount" min="1" max="100000" value="10" required></label>
		<label>Memo <input name="memo" maxlength="255"></label>
		<button type="submit" class="lite-btn">Award DataScrip</button>
	</form>
</fieldset>

<fieldset class="lite-panel">
	<legend>Comment review (<?php echo count($comments); ?>)</legend>
	<?php if (!$comments) { ?>
	<p class="lite-muted">Review queue clear.</p>
	<?php } else { ?>
	<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="action" value="approve_all">
		<button type="submit" class="lite-btn">Approve all</button>
	</form>
	<ol class="lite-list">
		<?php foreach ($comments as $comment) { ?>
		<li>
			<strong><?php echo htmlspecialchars($comment['author'], ENT_QUOTES, 'UTF-8'); ?></strong>
			<span class="lite-muted"> · <?php echo htmlspecialchars($comment['date'], ENT_QUOTES, 'UTF-8'); ?>
				· <?php echo htmlspecialchars($comment['board'], ENT_QUOTES, 'UTF-8'); ?></span>
			<p><?php echo nl2br(htmlspecialchars($comment['text'], ENT_QUOTES, 'UTF-8')); ?></p>
			<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>" class="lite-inline-form">
				<input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
				<button type="submit" class="lite-btn" name="action" value="approve">Approve</button>
				<button type="submit" class="lite-btn" name="action" value="delete">Delete</button>
			</form>
		</li>
		<?php } ?>
	</ol>
	<?php } ?>
</fieldset>
<?php
choosology_lite_footer();
