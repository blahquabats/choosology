<?php
/**
 * Public author profile (Classic fragment). Not the My Information editor.
 */
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auxfuncs.php';
require_once __DIR__ . '/lib/play-surface.php';

$name = choosology_undo_connect_string_mutation((string) ($_GET['user'] ?? ''));
$profile = choosology_author_public_profile($db, $name);
if ($profile === null) {
	echo '<div class="author-page"><article class="home-copy-card"><h2 class="home-copy-heading">Profile</h2><p>No public profile for that name.</p></article></div>';
	return;
}

$picUrl = '';
if ($profile['pic_id'] > 0 && function_exists('getPicUrl')) {
	$picUrl = getPicUrl($profile['pic_id'], true);
}
$safeName = htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8');
?>
<div class="author-page">
	<article class="home-copy-card author-card">
		<div class="author-head">
			<?php if ($picUrl !== '') { ?>
				<img class="author-icon" src="<?php echo htmlspecialchars($picUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
			<?php } else { ?>
				<div class="author-icon author-icon--empty" aria-hidden="true">No icon</div>
			<?php } ?>
			<div>
				<h2 class="home-copy-heading author-name"><?php echo $safeName; ?></h2>
				<?php if ($profile['joined'] !== '') { ?>
					<p class="author-joined">Joined <?php echo htmlspecialchars($profile['joined'], ENT_QUOTES, 'UTF-8'); ?></p>
				<?php } ?>
			</div>
		</div>
		<div class="author-bio">
			<?php if ($profile['about_html'] !== '') {
				echo $profile['about_html'];
			} else { ?>
				<p>This researcher has not written a bio.</p>
			<?php } ?>
		</div>
		<p class="author-stats"><?php echo htmlspecialchars(choosology_author_stats_line($profile), ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($profile['experiments']) { ?>
			<ul class="author-experiments">
				<?php foreach ($profile['experiments'] as $exp) {
					$title = $exp['title'] !== '' ? $exp['title'] : ('Experiment #' . $exp['id']);
					$href = '#/view/' . (int) $exp['id'];
					?>
					<li>
						<a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
					</li>
				<?php } ?>
			</ul>
		<?php } ?>
	</article>
</div>
