<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/labfeed.php';
require_once dirname(__DIR__) . '/lib/news-helpers.php';

global $db;

$recentFeed = choosology_build_recent_feed($db, CHOOSOLOGY_HOME_FEED_LIMIT);

$query = "
	SELECT DISTINCT a.id AS aid, a.title AS adv_title, a.description AS adv_description, a.user AS adv_user
	FROM advs a
	INNER JOIN advscreens s ON s.id = a.`begin`
	WHERE a.avail = 'public'
	  AND CHAR_LENGTH(TRIM(COALESCE(a.description, ''))) > 0
	  AND (
			NOT EXISTS (SELECT 1 FROM ratings r WHERE r.adv = a.id)
		 OR (SELECT AVG(r.rating) FROM ratings r WHERE r.adv = a.id) > 2.5
	  )
	ORDER BY a.id DESC
	LIMIT 40";
$res = runquery_assoc($query);
$featured = array();
if (is_array($res) && count($res) > 0) {
	$res = array_values(array_filter($res, static function ($row) {
		$text = trim(decode($row['adv_description'] ?? '', 1));
		return $text !== '';
	}));
	if (count($res) > 0) {
		shuffle($res);
		$featured = array_slice($res, 0, 8);
	}
}

choosology_lite_header(array('title' => 'Home', 'active' => 'home'));
?>
<fieldset class="lite-panel">
	<legend>Welcome</legend>
	<p>Here at Choosology Labs, Choosologists discover new elements of fiction through branching experiments.</p>
	<p class="lite-meta">You are on <strong>Choosology 3.1</strong> — the lean terminal UI. My Office management stays in Classic for now.</p>
	<p>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8'); ?>">Browse experiments</a>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('news.php'), ENT_QUOTES, 'UTF-8'); ?>">Lab notes</a>
		<?php if (empty($_SESSION['user'])) { ?>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Sign in</a>
		<?php } ?>
	</p>
</fieldset>

<fieldset class="lite-panel">
	<legend>Recent lab notes</legend>
	<?php if (count($recentFeed) === 0) { ?>
		<p class="lite-muted">No recent notes yet.</p>
	<?php } else { ?>
		<?php foreach ($recentFeed as $item) {
			$type = ($item['type'] ?? '') === 'news' ? 'News' : 'Update';
			$text = htmlspecialchars((string) ($item['text'] ?? ''), ENT_QUOTES, 'UTF-8');
			$stamp = htmlspecialchars(choosology_feed_date_label((string) ($item['whenposted'] ?? '')), ENT_QUOTES, 'UTF-8');
			$nid = (int) ($item['id'] ?? 0);
			$isNews = ($item['type'] ?? '') === 'news' && $nid > 0;
			$href = $isNews ? htmlspecialchars(choosology_lite_url('news.php?id=' . $nid), ENT_QUOTES, 'UTF-8') : '';
			?>
			<div class="lite-feed-item">
				<span class="lite-feed-badge"><?php echo $type; ?></span>
				<?php if ($stamp !== '') { ?><span class="lite-muted"><?php echo $stamp; ?> — </span><?php } ?>
				<?php if ($href !== '') { ?>
					<a href="<?php echo $href; ?>"><?php echo $text; ?></a>
				<?php } else { ?>
					<span><?php echo $text; ?></span>
				<?php } ?>
			</div>
		<?php } ?>
		<p class="lite-meta"><a href="<?php echo htmlspecialchars(choosology_lite_url('news.php'), ENT_QUOTES, 'UTF-8'); ?>">All lab notes →</a></p>
	<?php } ?>
</fieldset>

<fieldset class="lite-panel">
	<legend>Newly featured</legend>
	<?php if (!$featured) { ?>
		<p class="lite-muted">No featured public experiments yet.</p>
	<?php } else { ?>
		<ul class="lite-list">
			<?php foreach ($featured as $row) {
				$id = (int) ($row['aid'] ?? 0);
				$title = trim(decode((string) ($row['adv_title'] ?? ''), 1));
				if ($title === '') {
					$title = 'Experiment #' . $id;
				}
				$by = htmlspecialchars((string) ($row['adv_user'] ?? ''), ENT_QUOTES, 'UTF-8');
				$desc = trim(decode((string) ($row['adv_description'] ?? ''), 1));
				if (strlen($desc) > 140) {
					$desc = substr($desc, 0, 139) . '…';
				}
				$href = htmlspecialchars(choosology_lite_url('view.php?id=' . $id), ENT_QUOTES, 'UTF-8');
				?>
				<li>
					<a href="<?php echo $href; ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
					<span class="lite-muted"> by <?php echo $by; ?></span>
					<?php if ($desc !== '') { ?><div class="lite-muted"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
				</li>
			<?php } ?>
		</ul>
	<?php } ?>
</fieldset>
<?php
choosology_lite_guide_form('index.php');
choosology_lite_footer();
