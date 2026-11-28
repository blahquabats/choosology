<?php
require_once("connect.php");
require_once("auxfuncs.php");
require_once __DIR__ . "/lib/try-draft.php";

$tryMode = isset($_GET['try']) && (string) $_GET['try'] === '1';
if ($tryMode) {
	if (choosology_try_visitor_is_logged_in()) {
		echo "<p class='home-copy-card'>Try it yourself is only for visitors who are not signed in.</p>";
		echo "<script>location.hash='#/home';</script>";
		exit;
	}
	$id = 'try';
} elseif (isset($_GET['id']) && $_GET['id'] !== '' && is_numeric($_GET['id'])) {
	$id = $_GET['id'];
} else {
	die("No id found.");
}
//echoPre($alluserinfo);
//<canvas class='boxcanvas' width= "1000" height="900" id='canvas'></canvas>
//<script src="jquery-ui-1.10.3/ui/jquery-ui.js"></script>

?>
<div class='ajaxloader' id = 'visedloader'></div>
<div class='vised-toolbar'>
<?php if ($tryMode) { ?>
	<span class='fakebutton vised-settings-button' id='vised_try_save'>Save experiment</span>
	<a class='fakebutton vised-settings-button' id='vised_try_preview' href='#/try/play'>Preview</a>
	<p class='try-draft-note'>Session draft only. Eight screens maximum. Nothing is written to an account.</p>
<?php } else { ?>
	<span class='fakebutton vised-settings-button' id='vised_opensettings'>Experiment settings…</span>
<?php } ?>
</div>
<div id='visedcontainer' class='intabs'>
    
    
</div>

<div id="advsettingswindow" class="advsettings-hidden" aria-hidden="true">
	<div id="advsettingscontents"></div>
</div>

<div id="dummyholder" style="display:none"></div>
<script>
var advid = "<?php echo htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>";
window.choosologyTryDraft = <?php echo $tryMode ? 'true' : 'false'; ?>;
</script>

    <script src='vised/config.js?v=choicelabel1'></script>
    <script src='vised/setup.js?v=choicelabel1'></script>
    <script src='vised/vised.js?v=choicelabel1'></script>

