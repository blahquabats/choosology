<?php
/**
 * Public author profile (Lite). Not the My Information editor.
 */
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/play-surface.php';

global $db;

$name = choosology_undo_connect_string_mutation((string) ($_GET['user'] ?? ''));
$profile = choosology_author_public_profile($db, $name);

if ($profile === null) {
	http_response_code(404);
	choosology_lite_header(array('title' => 'Profile', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Profile</legend><p>No public profile for that name.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

$picUrl = '';
if ($profile['pic_id'] > 0 && function_exists('getPicUrl')) {
	$picUrl = getPicUrl($profile['pic_id'], true);
}
$safeName = htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8');

choosology_lite_header(array('title' => $profile['name'], 'active' => 'browse'));
?>
<fieldset class="lite-panel author-card">
	<legend><?php echo $safeName; ?></legend>
	<div class="author-head">
		<?php if ($picUrl !== '') { ?>
			<img class="author-icon" src="<?php echo htmlspecialchars($picUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
		<?php } else { ?>
			<div class="author-icon author-icon--empty">No icon</div>
		<?php } ?>
		<div>
			<?php if ($profile['joined'] !== '') { ?>
				<p class="lite-muted">Joined <?php echo htmlspecialchars($profile['joined'], ENT_QUOTES, 'UTF-8'); ?></p>
			<?php } ?>
		</div>
	</div>
	<div class="author-bio">
		<?php if ($profile['about_html'] !== '') {
			echo $profile['about_html'];
		} else { ?>
			<p class="lite-muted">This researcher has not written a bio.</p>
		<?php } ?>
	</div>
	<p class="author-stats"><?php echo htmlspecialchars(choosology_author_stats_line($profile), ENT_QUOTES, 'UTF-8'); ?></p>
	<?php if ($profile['experiments']) { ?>
		<ul class="lite-list author-experiments">
			<?php foreach ($profile['experiments'] as $exp) {
				$title = $exp['title'] !== '' ? $exp['title'] : ('Experiment #' . $exp['id']);
				$href = choosology_lite_url('view.php?id=' . (int) $exp['id']);
				?>
				<li><a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a></li>
			<?php } ?>
		</ul>
	<?php } ?>
</fieldset>
<?php
choosology_lite_footer();
