<?php
require_once("../connect.php");
require_once("../auxfuncs.php");

$response = array();
$response["success"] = 0;
$_POST=$_GET;
if (isset($_POST['screen']))
{
    $screenid = $_POST['screen'];
    $q = "select * from advscreens, advs where advscreens.id = '$screenid' and advscreens.advused = advs.id";
    $res = runquery_assoc($q);
    $screen = $res[0];
    if(!$screen) die(json_encode($response)); // just fail
    $advused = $screen['advused'];
    $text = htmlspecialchars_decode(htmlspecialchars_decode($screen['text']));
    $response['text'] = nl2br($text)."<br/><div class='spacebox'>&emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; 
    &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; &emsp; --</div>";
    
    $choices = "";
    for ($i=1; $i <= 8; $i++)
    {
        if($screen['choice'.$i])
        {
            $c = explode("|Q-D-|", $screen['choice'.$i]);
            if(!$c[1] || !$c[0]) continue;
            if($c[1] == $screen['begin']) continue;
            $choicetext = htmlspecialchars_decode(htmlspecialchars_decode($c[0]));
            $choices .= "<div class='choice' onclick = \"goToScreen('".$c[1]."',$screenid,0)\">$choicetext</div>";
        }
    }
    if($choices == "") 
    {
        $choices = "<br />You have reached an end to the experiment!<br /><br />";
        if($name) $choices .= "Please rate your experience:<br />";
        else $choices .= "Log in to rate this experiment!<br/>";
        $choices .= assembleRating($advused, false);
        $choices .= "<br /><hr />
        <div class ='commentsdiv hidecomments commentsdiv-$screenid'>
        ";
        $comments=new commentArea("adv".$advused, true, false);
        $choices .= $comments->display(true);
        $choices .= "</div>
        <input type='hidden' name='commentsexist' id='commentsexist' value='1'>";
    }
    $choices .= "<input type='hidden' name='advid' id='advid' value='$advused'>";
    $choices .= "<input type='hidden' name='screenid' id='screenid' value='$screenid'>";
    $response['choices'] = $choices;
    
    // foreground/background should be screen pic->screencolor->advpic->advcolor
    if ($screen['screenbgcolor']) $bg = "background-color: ".$screen['screenbgcolor'];
    else if ($screen['bgpic']) $bg = "background-image: url(\"".getPic($screen['bgpic'])."\")";
    else $bg = "background-color: ".$screen['bg'];
    
    if($screen['screenboxcolor']) $box = "background-color: ".$screen['screenboxcolor'];
    else if($screen['box']) $box = "background-color: ".$screen['box'];
    else $box = "background-color: #ddd";
    $borderwidth = ($screen['screenborderwidth']) ?: $screen['borderwidth']; 
    $bordercolor = ($screen['screenbordercolor']) ?: $screen['border']; 
    $border = "border: ".$borderwidth."px solid ".$bordercolor;
    $hex = $bordercolor;
    list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
    $choiceborder= "    border: 2px solid rgb($r, $g, $b);border: 2px solid rgba($r, $g, $b, .5)";
    $offset = "top: -".$borderwidth."px; left: -".$borderwidth."px";

    $response['bg'] = $bg;
    $response['box'] = $box;
    $response['border'] = $border;
    $response['choiceborder'] = $choiceborder;
    $response['offset'] = $offset;
    
    $response['success'] = 1;
    echo json_encode($response);
}
else 
{
    $response['success'] = -1;
    echo json_encode($response);
}
?>