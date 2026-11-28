<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/labfeed.php';
require_once dirname(__DIR__) . '/lib/news-helpers.php';

global $db;

$recentFeed = choosology_build_recent_feed($db, CHOOSOLOGY_HOME_FEED_LIMIT);

require_once dirname(__DIR__) . '/lib/adventure-review-helpers.php';
$featured = choosology_newly_featured_adventures($db, 10);

choosology_lite_header(array('title' => 'Home', 'active' => 'home'));
?>
<fieldset class="lite-panel">
	<legend>Welcome</legend>
	<p>Here at Choosology Labs, Choosologists discover new elements of fiction through branching experiments.</p>
	<p class="lite-meta">You are on <strong>Choosology 3.1</strong> — the lean terminal UI. My Office management stays in Classic for now.</p>
	<p>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8'); ?>">Browse experiments</a>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('news.php'), ENT_QUOTES, 'UTF-8'); ?>">Lab notes</a>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('about.php'), ENT_QUOTES, 'UTF-8'); ?>">About</a>
		<?php if (empty($_SESSION['user'])) { ?>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('try.php'), ENT_QUOTES, 'UTF-8'); ?>">Try it yourself</a>
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
