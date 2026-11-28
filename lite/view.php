<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/play-surface.php';

global $db;

$tryMode = isset($_GET['try']) && (string) $_GET['try'] === '1';
if ($tryMode) {
	require_once dirname(__DIR__) . '/lib/try-draft.php';
	if (choosology_try_visitor_is_logged_in()) {
		header('Location: ' . choosology_lite_url('index.php'));
		exit;
	}
	$draft = choosology_try_draft_get(true);
	$adv = $draft['adv'];
	$id = 0;
	$begin = (string) ($adv['begin'] ?? '1');
	$sid = isset($_GET['screen']) ? (string) $_GET['screen'] : $begin;
	if (!isset($draft['screens'][$sid])) {
		$sid = $begin;
	}
	$screen = $draft['screens'][$sid];
	$from = isset($_GET['from']) ? (string) $_GET['from'] : '';
	$sessionUser = '';
} else {
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

require_once dirname(__DIR__) . '/lib/adventure-review-helpers.php';
$sessionUser = (string) ($_SESSION['user'] ?? '');
$canView = choosology_adventure_can_view($adv, $sessionUser);
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
}

$title = trim(decode((string) ($adv['title'] ?? ''), 1));
if ($title === '') {
	$title = 'Experiment #' . $id;
}
$owner = (string) ($adv['user'] ?? '');
$read = choosology_adv_reading_presentation($adv, $screen);
if (trim(strip_tags($read['title_html'])) === '') {
	$read['title_html'] = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
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

if (!empty($tryMode)) {
	$structureUrl = '';
	$classicEdit = '';
	$startUrl = choosology_lite_url('view.php?try=1');
	$returnUrl = ($from !== '' && $from !== '0')
		? choosology_lite_url('view.php?try=1&screen=' . rawurlencode((string) $from))
		: '';
} else {
$structureUrl = choosology_lite_url('structure.php?id=' . $id);
$classicEdit = choosology_classic_url('edit/' . $id);
$startUrl = choosology_lite_url('view.php?id=' . $id);
$returnUrl = $from > 0
	? choosology_lite_url('view.php?id=' . $id . '&screen=' . $from)
	: '';
}

$guideNext = 'view.php?id=' . $id;
if (isset($_GET['screen'])) {
	$guideNext .= '&screen=' . $sid;
}
if ($from > 0) {
	$guideNext .= '&from=' . $from;
}

choosology_lite_header(array('title' => $title, 'active' => 'browse'));
choosology_lite_guide_panel('view', $guideNext);
?>
<div class="lite-read" style="<?php echo htmlspecialchars($read['background'], ENT_QUOTES, 'UTF-8'); ?>">
	<article class="lite-read-sheet" style="<?php echo htmlspecialchars($read['panel'], ENT_QUOTES, 'UTF-8'); ?>">
		<header class="lite-read-head">
			<?php echo $iconHtml; ?>
			<h1 class="lite-read-title"<?php echo $read['title'] !== '' ? ' style="' . htmlspecialchars($read['title'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?php echo $read['title_html']; ?></h1>
			<p class="lite-read-meta">
				<?php if (!empty($tryMode)) { ?>
				Draft · not saved · by <?php echo choosology_author_byline_html($owner, 'lite'); ?>
				<?php } else { ?>
				Exp #<?php echo (int) $id; ?> · by <?php echo choosology_author_byline_html($owner, 'lite'); ?>
				· <a href="<?php echo htmlspecialchars($structureUrl, ENT_QUOTES, 'UTF-8'); ?>">Structure (ASCII)</a>
				<?php if ($sessionUser !== '' && $sessionUser === $owner) { ?>
					· <a href="<?php echo htmlspecialchars($classicEdit, ENT_QUOTES, 'UTF-8'); ?>">Edit in Classic</a>
				<?php } ?>
				<?php } ?>
			</p>
		</header>
		<div class="lite-read-text adv-play-text"<?php echo $read['text'] !== '' ? ' style="' . htmlspecialchars($read['text'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
			<?php echo $text; ?>
		</div>

		<?php $isTerminal = ($isEnding && $choices === array()); ?>
		<details class="lite-choices-reveal lite-read-choices<?php echo $isTerminal ? ' lite-choices-reveal--ending' : ''; ?>">
			<summary class="lite-choices-summary lite-read-summary">
				<span class="lite-choices-summary-label">Click to reveal choices</span>
				<span class="lite-choices-summary-cue" aria-hidden="true">&#9662;</span>
			</summary>
			<div class="lite-choices-body">
				<?php if ($isTerminal) {
					if (!empty($tryMode)) {
						echo choosology_try_ending_html('lite');
					} else {
						echo choosology_lite_ending_panel_html($db, $id, $sid);
					}
				} else { ?>
					<div class="lite-read-choice-list" role="navigation" aria-label="Choices">
						<?php foreach ($choices as $idx => $c) {
							if (!empty($tryMode)) {
								$href = choosology_lite_url('view.php?try=1&screen=' . (int) $c['target'] . '&from=' . rawurlencode((string) $sid));
							} else {
								$href = choosology_lite_url('view.php?id=' . $id . '&screen=' . (int) $c['target'] . '&from=' . $sid);
							}
							?>
							<a class="lite-read-choice" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $read['choice'] !== '' ? ' style="' . htmlspecialchars($read['choice'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
								<?php echo ($idx + 1) . '. ' . $c['label']; ?>
							</a>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</details>

		<p class="lite-btn-row lite-read-nav">
			<?php if (choosology_play_show_start_over($sid, $begin)) { ?>
			<a class="lite-btn" href="<?php echo htmlspecialchars($startUrl, ENT_QUOTES, 'UTF-8'); ?>">← Start over</a>
			<?php } ?>
			<?php if ($returnUrl !== '') { ?>
				<a class="lite-btn" href="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">← Return to previous screen</a>
			<?php } ?>
			<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8'); ?>">Back to Browse</a>
		</p>
	</article>
</div>
<script src="<?php echo htmlspecialchars(choosology_site_url('lite/play.js?v=choiceload1'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php
choosology_lite_guide_form($guideNext);
choosology_lite_footer();
