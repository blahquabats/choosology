<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
	http_response_code(404);
	choosology_lite_header(array('title' => 'Not found', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Error</legend><p>No experiment specified.</p>';
	echo '<p><a href="' . htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8') . '">Back to Browse</a></p></fieldset>';
	choosology_lite_footer();
	exit;
}

$advRows = runquery_assoc("SELECT * FROM advs WHERE id = '$id' LIMIT 1");
$adv = is_array($advRows) && $advRows ? $advRows[0] : null;
if (!$adv) {
	http_response_code(404);
	choosology_lite_header(array('title' => 'Not found', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Error</legend><p>Experiment not found.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

$avail = (string) ($adv['avail'] ?? '');
$owner = (string) ($adv['user'] ?? '');
$sessionUser = (string) ($_SESSION['user'] ?? '');
$canView = ($avail === 'public') || ($sessionUser !== '' && $sessionUser === $owner);
if (!$canView) {
	http_response_code(403);
	choosology_lite_header(array('title' => 'Private', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Private experiment</legend>';
	echo '<p>This experiment is not public. Sign in as the owner, or open it in Classic if you have access.</p>';
	echo '</fieldset>';
	choosology_lite_footer();
	exit;
}

$begin = (int) ($adv['begin'] ?? 0);
$sid = isset($_GET['screen']) ? (int) $_GET['screen'] : $begin;
if ($sid < 1) {
	$sid = $begin;
}
$from = isset($_GET['from']) ? (int) $_GET['from'] : 0;

$screenRows = runquery_assoc("SELECT * FROM advscreens WHERE id = '$sid' LIMIT 1");
$screen = is_array($screenRows) && $screenRows ? $screenRows[0] : null;
if (!$screen || (string) ($screen['advused'] ?? '') !== (string) $id) {
	http_response_code(404);
	choosology_lite_header(array('title' => 'Screen missing', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Error</legend><p>Screen not found in this experiment.</p>';
	echo '<p><a href="' . htmlspecialchars(choosology_lite_url('view.php?id=' . $id), ENT_QUOTES, 'UTF-8') . '">Start over</a></p></fieldset>';
	choosology_lite_footer();
	exit;
}

/* Play start once per adv per session when on begin. */
$isBeginLoad = !isset($_GET['screen']) || (string) ($_GET['screen'] ?? '') === (string) $begin;
if ($isBeginLoad) {
	if (!isset($_SESSION['play_counted']) || !is_array($_SESSION['play_counted'])) {
		$_SESSION['play_counted'] = array();
	}
	if (empty($_SESSION['play_counted'][$id])) {
		$_SESSION['play_counted'][$id] = 1;
		require_once dirname(__DIR__) . '/lib/clipboard-helpers.php';
		if (function_exists('choosology_adv_record_play_start')) {
			choosology_adv_record_play_start($db, $id);
		}
	}
}

$title = trim(decode((string) ($adv['title'] ?? ''), 1));
if ($title === '') {
	$title = 'Experiment #' . $id;
}

$text = '';
if (function_exists('choosology_omit_unreachable_pic_images')) {
	$text = choosology_omit_unreachable_pic_images(
		htmlspecialchars_decode(htmlspecialchars_decode((string) ($screen['text'] ?? '')))
	);
} else {
	$text = htmlspecialchars_decode(htmlspecialchars_decode((string) ($screen['text'] ?? '')));
}
$text = choosology_lite_constrain_html_images($text);

$delim = choosology_choice_delimiter();
$choices = array();
for ($i = 1; $i <= 8; $i++) {
	$raw = (string) ($screen['choice' . $i] ?? '');
	if ($raw === '') {
		continue;
	}
	$parts = explode($delim, $raw);
	if (empty($parts[0]) || empty($parts[1])) {
		continue;
	}
	if ((string) $parts[1] === (string) $begin) {
		continue;
	}
	$choices[] = array(
		'label' => htmlspecialchars_decode(htmlspecialchars_decode((string) $parts[0])),
		'target' => (int) $parts[1],
	);
}

$isEnding = $choices === array() || choosology_screen_is_ending($screen, $begin);

$iconHtml = '';
if (!empty($adv['pic']) && function_exists('choosology_adv_pic_usable_for_display') && choosology_adv_pic_usable_for_display($adv['pic'])) {
	$pu = getPicUrl($adv['pic'], true);
	if ($pu !== '') {
		$iconHtml = '<img src="' . htmlspecialchars($pu, ENT_QUOTES, 'UTF-8') . '" alt="" width="64" height="64" loading="lazy" style="max-width:64px;height:auto;vertical-align:middle;margin-right:0.5rem;">';
	}
}

$structureUrl = choosology_lite_url('structure.php?id=' . $id);
$classicEdit = choosology_classic_url('edit/' . $id);
$startUrl = choosology_lite_url('view.php?id=' . $id);
$returnUrl = $from > 0
	? choosology_lite_url('view.php?id=' . $id . '&screen=' . $from)
	: '';

choosology_lite_header(array('title' => $title, 'active' => 'browse'));
?>
<fieldset class="lite-panel">
	<legend><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></legend>
	<p class="lite-meta">
		<?php echo $iconHtml; ?>
		Exp #<?php echo (int) $id; ?> · by <?php echo htmlspecialchars($owner, ENT_QUOTES, 'UTF-8'); ?>
		· <a href="<?php echo htmlspecialchars($structureUrl, ENT_QUOTES, 'UTF-8'); ?>">Structure (ASCII)</a>
		<?php if ($sessionUser !== '' && $sessionUser === $owner) { ?>
			· <a href="<?php echo htmlspecialchars($classicEdit, ENT_QUOTES, 'UTF-8'); ?>">Edit in Classic</a>
		<?php } ?>
	</p>
	<div class="lite-screen adv-play-text">
		<?php echo $text; ?>
	</div>

	<?php if ($isEnding && $choices === array()) {
		echo choosology_lite_ending_panel_html($db, $id, $sid);
	} else { ?>
		<div class="lite-btn-row" role="navigation" aria-label="Choices">
			<?php foreach ($choices as $idx => $c) {
				$href = choosology_lite_url('view.php?id=' . $id . '&screen=' . (int) $c['target'] . '&from=' . $sid);
				$label = strip_tags((string) $c['label']);
				?>
				<a class="lite-btn" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
					<?php echo ($idx + 1) . '. ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
				</a>
			<?php } ?>
		</div>
	<?php } ?>

	<p class="lite-btn-row" style="margin-top:0.75rem;">
		<a class="lite-btn" href="<?php echo htmlspecialchars($startUrl, ENT_QUOTES, 'UTF-8'); ?>">← Start over</a>
		<?php if ($returnUrl !== '') { ?>
			<a class="lite-btn" href="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">← Return to previous screen</a>
		<?php } ?>
		<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8'); ?>">Back to Browse</a>
	</p>
</fieldset>
<?php
choosology_lite_footer();
