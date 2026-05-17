<?php
require_once("connect.php");
require_once("auxfuncs.php");

if (isset($_GET['id']) && $_GET['id'] !== '' && is_numeric($_GET['id'])) {
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
	<span class='fakebutton vised-settings-button' id='vised_opensettings'>Experiment settings…</span>
</div>
<div id='visedcontainer' class='intabs'>
    
    
</div>

<div id="advsettingswindow" class="advsettings-hidden" aria-hidden="true">
	<div id="advsettingscontents"></div>
</div>

<div id="dummyholder" style="display:none"></div>
<script>
var advid = "<?php echo htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>";
</script>

    <script src='vised/config.js'></script>
    <script src='vised/setup.js'></script>
    <script src='vised/vised.js'></script>

