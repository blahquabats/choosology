<?php
/* view.php needs to generate the innards of the viewwindow for the given adventure id. 
try to be self-sufficient? (migrate to mobile easily)
- take a screen id if given, else start with beginning
- create css
-- background img/color
-- foreground color/border/rounded
-- general font/text style
-- ???
- adventure information
-- probs just title and icon and byline top left
-- "save progress for later"
-- persistent hide?
- Screen data
-- nice scroll
-- deal with pictures/videos well
- path choices
-- Back (remember path)
-- generate choices after fully scrolled, on right? or generate on mouseover something to hide potential spoilers
--- maybe also a simple version...
-- jquery to change screens as well as synchronous method
*/
require_once("connect.php");
require_once("auxfuncs.php");

function getAdvInfo($id)
{
    $q = "select * from advs where id = '$id'";
    $res = runquery_assoc($q);
    return $res[0];
}
function getScreenInfo($id)
{
    $q = "select * from advscreens where id = '$id'";
    $res = runquery_assoc($q);
    return $res[0];
}

$id = $_GET['id'] ?? '';
if ($id === '' || !is_numeric($id)) {
	die("No adventure found!");
}
$adv = getAdvInfo($id);

/** "Map" opens the graph editor; only useful for the owner when there is more than one active screen. */
$showExpMap = false;
if (!empty($_SESSION['user']) && (string) $_SESSION['user'] === (string) ($adv['user'] ?? '')) {
	$escAdvid = mysqli_real_escape_string($db, (string) $id);
	$cnt = runquery_assoc(
		"SELECT COUNT(*) AS c FROM advscreens WHERE advused = '$escAdvid' AND IFNULL(deleted,0) NOT IN (1, '1')"
	);
	if ($cnt && (int) ($cnt[0]['c'] ?? 0) > 1) {
		$showExpMap = true;
	}
}

if (!empty($_GET['screen'])) {
	$sid = $_GET['screen'];
} else {
	$sid = $adv['begin'];
}
if (!$sid) die ("Can't find page information!");
$screen = getScreenInfo($sid);

//16345 for lots of choices
if ($adv['pic'] && choosology_adv_pic_usable_for_display($adv['pic'])) {
	$pu = getPicUrl($adv['pic'], true);
	$image = $pu !== '' ? "<img class='advpic' alt='' src=\"" . htmlspecialchars($pu, ENT_QUOTES, 'UTF-8') . "\" />" : "";
} else {
	$image = "";
}

// foreground/background should be screen pic->screencolor->advpic->advcolor
if ($screen['screenbgcolor']) $bg = "background-color: ".$screen['screenbgcolor'];
else if ($adv['bgpic'] && choosology_adv_pic_usable_for_display($adv['bgpic'])) {
	$bgu = getPicUrl($adv['bgpic'], false);
	$bg = $bgu !== '' ? "background-image: url(\"" . htmlspecialchars($bgu, ENT_QUOTES, 'UTF-8') . "\")" : "background-color: ".$adv['bg'];
} else $bg = "background-color: ".$adv['bg'];

if($screen['screenboxcolor']) $box = "background-color: ".$screen['screenboxcolor'];
else if($adv['box']) $box = "background-color: ".$adv['box'];
else $box = "background-color: #ddd";
$border = "border: ".$adv['borderwidth']."px solid ".$adv['border'];
$hex = $adv['border'];
$textstyling = htmlspecialchars_decode($adv['textstyle']);
$choicestyling = htmlspecialchars_decode($adv['linkstyle']);
$titlestyling = htmlspecialchars_decode($adv['titlestyle']);
$playTextCss = choosology_adv_play_typography_css($adv, 'text');
$playChoiceCss = choosology_adv_play_typography_css($adv, 'choice');
$playTextCssSafe = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F<>]/', '', $playTextCss);
$playChoiceCssSafe = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F<>]/', '', $playChoiceCss);
list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
$choiceborder= "border: 2px solid rgb($r, $g, $b);border: 2px solid rgba($r, $g, $b, .5)";
$choices = "";
for ($i=1; $i <= 8; $i++)
{
    if($screen['choice'.$i])
    {
        $c = explode("|Q-D-|", $screen['choice'.$i]);
        if(!$c[1] || !$c[0]) continue;
        if ($c[1] == $adv['begin']) continue;
        $choicetext = htmlspecialchars_decode(htmlspecialchars_decode($c[0]));
        $choices .= "<div class='choice' onclick = \"goToScreen('".$c[1]."', '$sid', 0)\">$choicetext</div>";
    }
}
if ($choices === '') {
	$choices = choosology_build_ending_panel_html($db, (int) $id, (int) $sid);
	$choices .= "<input type='hidden' name='advid' id='advid' value='" . (int) $id . "'>";
	$choices .= "<input type='hidden' name='screenid' id='screenid' value='" . (int) $sid . "'>";
}

$offset = "top: -".$adv['borderwidth']."px; left: -".$adv['borderwidth']."px";
//$choices = "hey look choices";
$text = choosology_omit_unreachable_pic_images(
	htmlspecialchars_decode(htmlspecialchars_decode($screen['text']))
);
/*$text = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.";
*/
//
$out = "

    <meta charset='utf-8' />

        <link rel='stylesheet' href='style/view.css' /><link rel='stylesheet' href='style/choosology.css' />
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/jquery-ui@1.10.4/themes/base/jquery-ui.css' />
      <style>
        .choice
        {
            $choiceborder;
        }
        .adv-play-text {
            $playTextCssSafe;
        }
        .adv-play-choices .choice {
            $playChoiceCssSafe;
        }
        </style>
<div class='advcanvas' style='display:block;
$bg;'>
<div class='viewcol1 view-sidebar' style='$box;$border;' >
<div class='view-sidebar-meta'>
<p class='view-sidebar-eyebrow'>Experiment <span class='view-sidebar-eyebrow-tag'>lab node</span></p>
".($image !== '' ? "<div class='view-sidebar-icon'>".$image."</div>" : "")."
<h2 class='view-sidebar-title'".($titlestyling !== '' ? " style='".$titlestyling."'" : "").">".
htmlspecialchars_decode($adv['title']).
"</h2>
<p class='view-sidebar-byline'>by <span class='view-sidebar-author'>".htmlspecialchars((string) $adv['user'], ENT_QUOTES, 'UTF-8')."</span></p>
</div>
<div class='view-sidebar-nav' role='navigation' aria-label='Experiment navigation'>
<div class='choice view-sidebar-action' id='firstscreen' onclick=\"goToScreen('".$adv['begin']."', $sid,1)\">
    &larr; Start over
</div>
".($showExpMap ? "<div class='choice view-sidebar-action' id='mapbutton' data-advid='".htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8')."'>
    Map
</div>" : "")."
<div class='choice view-sidebar-action' id='lastscreen'>
    &larr; Return to previous screen
</div>
</div>
</div>
<div class='viewcol2'>
<br/>
<div class='text'><span id='innards'> 
<div class='realinnards adv-play-text' style='$box;$border;'>".
$text.
"<br/>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;</div></span>

    <div class='choicecontainer'>
        <div class='choices adv-play-choices'  style='$box;$border;'>
            <div class='choicecover' style='$box;$border;$offset'><br>
            <br>
            Hover to reveal choices!
            </div>
        <div id = 'choicemeat'>
        $choices
        </div>
        </div>

</div>

</div>



</div>


    <script src='scripts/view.js'></script>
";
//<div style='$choicestyling;$box;$border' class='commentsholder'>See comments...</div>
//    <script src='jquery.js'></script>
    //<script src='jquery-ui-1.10.3/ui/jquery-ui.js'></script>
echo $out;
?>