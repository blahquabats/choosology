<?php
require_once ("connect.php");
require_once ("authent.php");
require_once ("comments.php");
require_once ("messagesfunc.php");
require_once ("Browser.php");
$fgcs=array("0","#FFABAD","#A6FF79","#88EDE8","#C6CAF1","#F8B6F9","#CDCDCD","#FFFFFF","#FFFB91");
$fgbcs=array("0","#EA3D30","#548F2F","#2F8F8A","#747BCF","#C355BF","#818181","#000000","#F3EA00");
$wrapperexists = 1;
if ($alluserinfo['view_restricted'])
	$qand = "(avail=\"public\" or avail=\"restricted\")";
else
	$qand = "avail=\"public\"";
	
	
	function resizeProp($image,$max=25)
	{
		list($w, $h) = getimagesize($image);
	$width = $w;
	$height = $h;
	
	if ($width > $max)
	{
		$perc = $max / $width;
		$height *= $perc;
		$width = $max;
	}
	if ($height > $max)
	{
		$perc = $max / $height;
		$width *= $perc;
		$height = $max;
	}
	$sizes=array("w"=>$width,"h"=>$height);
	return $sizes;	
	}
	
   function showMiniAdvs($wheres)
   {
    global $db, $name, $screensrc, $uniscreensrc;
    include("fetchminiadvs.php");
   }

function nl2br_limit($string, $num)
{
    $dirty = preg_replace('/\\\r/', '', $string);
    $clean = preg_replace('/\\\n{4,}/', str_repeat('<br />', $num), preg_replace('/\\\r/', '', $dirty));
    $clean = preg_replace('/\\\n/', '<br />', $clean);
    return nl2br($clean);
}

function checkOwnScreen($id)
{
	global $db, $name;
	$equery = "select * from advscreens where advscreens.id='$id' and advscreens.user='$name'";
	//echo $equery;
	$eresult = mysqli_query($db, $equery);
	$eresult = mysqli_fetch_array($eresult);
	if (!$eresult)
		return false;
	return $eresult;
}

function checkRating($name, $id, $screen)
{
	global $db;
	$q = "select * from ratings where who=\"$name\" and adv=$id";
	$r = mysqli_query($db, $q);
	if ($r && mysqli_fetch_array($r))
		return false;
	else
		return true;
}

function assembleRating($which, $readonly=true)
{
	global $db, $name;
	$q = "select * from advs where id=$which";
	$r = mysqli_query($db, $q);
	$res=mysqli_fetch_array($r);
	if($res['user']==$name) $readonly=true;
	$q = "select * from ratings where adv=$which";
	$r = mysqli_query($db, $q);
	if ($r && $res = mysqli_fetch_array($r))
	{
	  if($res['who']==$res['user'])
	  {
      $readonly=true;
    }
		$count = 0;
		$sum = 0;
		do
		{
			$sum += $res['rating'];
			if($res['who']==$name)
			{
         $myrating=$res['rating'];
      }
			$count++;
		} while ($res = mysqli_fetch_array($r));
		$rat = round($sum / $count, 1);
		$perc = intval(($rat / 5) * 100);
    //$noratingsoutput="<div class='ratingholder'><img class='understar' src='icons/normal/stars.png' alt='Not enough ratings'></div><div style='margin-left:auto;margin-right:auto;width:auto;text-align:center;'><small>Not enough ratings</small></div>";
		if ($count < 1)
		{
			//return $noratingsoutput;
			$avg="Not enough ratings";
			$rat=0;
		}
		else 
		{
    $q = "update advs set rating='$rat' where id=$which";
		mysqli_query($db, $q);                                                     
		//return "<div class='ratingholder'><img class='understar' src='icons/normal/stars.png' alt='Average: $rat stars'><div class='overstar' style='width:{$perc}%'><img src='icons/over/stars.png' alt='Average: $rat stars'></div></div><div style='text-align:center'><small>Average: $rat stars</small></div>";
		$avg="Average rating: <b>$rat</b> stars";
		}
	}
	if(!$rat) $rat=0;
	if(!$myrating) 
  {
    $myrating=0;
    if(!$readonly) $ratdesc="You haven't rated this adventure yet!";
  }
  else {
  $ratdesc="Your rating: <b>$myrating</b> stars";
  }
	//return $noratingsoutput;
	if(!$avg) $avg="Not enough ratings";
	$id=$which;
	$output= "<div id='rateresponse$id' style='margin-left:auto;margin-right:auto;width:auto;text-align:center;white-space:nowrap;font-size:8pt;'>$avg</div>";
	$output.= "<div class='starsholder' id='starsholder$id'>
	<input type='hidden' value='$rat' id='starsrating$id'>
		<div class='loading' id='starsloading$id'>&nbsp;</div>
		";
			for($x=1; $x<=5; $x++)
			{
			$output.="<div class='star'><div class='avgstar' id=\"stavg{$x}-$id\"><img src=\"icons/over/greenstar.png\" id=\"stavgs$x\""; 
      if(!$readonly) $output.="onmouseover=\"highlightStars($x, $id)\"";
      $output.="></div>
      <img src=\"icons/normal/star.png\" id=\"st{$x}-$id\" "; 
      if(!$readonly) $output.= " class=\"link\" onmouseout=\"showAvgStars($id)\" onclick=\"sendRating($x,{$id},'{$_GET['screen']}');\" onmouseover=\"highlightStars($x, $id)\"  alt=\"Click to rate!\" title=\"Click to rate!\"";
      $output.= "></div>";        
      			}
			$output.= "</div>";
			$output.= "<div id='rateyours$id' style='margin-left:auto;margin-right:auto;width:auto;text-align:center;white-space:nowrap;font-size:8pt;'>$ratdesc</div>
      <script>
          showAvgStars($id);
      </script>
      ";
			return $output;


}

function errorOut($string, $die = 0)
{
	echo "<div class='error'>" . $string . "</div>";
	if ($die)
	{
        echo "</div>";
	  require_once("footer.php");
		die();
	}
}

function successOut($string)
{
	echo "<div class='success'>" . $string . "</div>";
}

function getParents($screen, $adv)
{
	global $db;
	$query = "select * from advscreens where choice1 like \"%|Q-D-|$screen\" or choice2 like \"%|Q-D-|$screen\" or choice3 like \"%|Q-D-|$screen\" or choice4 like \"%|Q-D-|$screen\" or choice5 like \"%|Q-D-|$screen\" or choice6 like \"%|Q-D-|$screen\" or choice7 like \"%|Q-D-|$screen\" or choice8 like \"%|Q-D-|$screen\"";
	//	echo $query;
	$answer = mysqli_query($db, $query);
	$parents = array();
	if ($result = mysqli_fetch_array($answer))
	{
		do
		{
			$parents[] = "<a href='members.php?loc=adv&id=$adv&screen=" . $result['id'] .
				"'>" . $result['name'] . "</a>";
		} while ($result = mysqli_fetch_array($answer));
	}
	return $parents;
}

function makeFakeButton($id, $onclick, $href, $icon, $text, $color="gray", $style=false, $check=false, $rollover=false)
{
  $button="";
  if($href) $button.="<a href='$href' class='fakebuttona'>";
  $button.= "<div "; 
  
  if($rollover) 
  {
    $button.=" onmouseover=\"{$id}roll=setTimeout('document.getElementById(\'{$id}roll\').style.visibility=\'visible\'', 500)\" onmouseout=\"clearTimeout({$id}roll); document.getElementById('{$id}roll').style.visibility='hidden'\"";
    $onclick="clearTimeout({$id}roll); $onclick";
  }
  if($onclick) $button.=" onclick=\"$onclick\" id='$id'";
  $button.=" class='f$color"; 
  if($check && ($_SERVER['PHP_SELF']==$check)) $button.=" fdisable";
  else if ($href && ($href==$_SERVER['REQUEST_URI'] || "/cyo".$href==$_SERVER['REQUEST_URI'])) $button .=" fdisable";
  $button.= " fakebutton' ";
   if($style) $button.=" style='$style'"; 
  
   $button.=">";
  if($icon) $button.=icon($icon, false, false, "$text");
        $button.=" $text ";     
    if($rollover) $button.=" <div class='triangle-border top awaiting' id='{$id}roll'>
    $rollover
    </div>";    
        $button.="</div>";
  if($href) $button.="</a>";
          
   return $button;
}


function checkOwnAdv($id)
{
	global $db, $name;
	$equery = "select * from advs where id='$id' and user='$name'";
	$eresult = mysqli_query($db, $equery);
	$eresult = mysqli_fetch_array($eresult);
	if (!$eresult)
		return false;
	return $eresult;

}
function icon($which, $link = false, $onclick = "", $alt = "", $id = "", $gifpng =	"gif")
{
	if($alt) $title=$alt;
		else $title = ucfirst($which);
	$dir = ($link) ? "normal" : "over";
	$icon = "<img src='icons/$dir/$which.$gifpng'";
	if ($link)
		$icon .= " class='link' onmouseover=\"this.src='icons/over/$which.$gifpng'\" onmouseout=\"this.src='icons/normal/$which.$gifpng'\"";
	if ($onclick)
		$icon .= " onclick=\"$onclick\"";
	if ($id)
		$icon .= " id=\"$id\"";
	$icon .= " alt=\"$alt\" title=\"$title\">";
	return $icon;
}
function playerDir($who = "")
{
	global $name;
        if($who=="&everyone") return "pics/universal";
	$who = ($who != "") ? $who : $name;
       
	$number = ord(strtolower(substr($who, 0, 1)));
	switch ($number)
	{
		case ($number > 96 && $number < 101):
			$dir = "ad";
			break;
		case ($number > 96 && $number < 106):
			$dir = "ei";
			break;
		case ($number > 96 && $number < 111):
			$dir = "jn";
			break;
		case ($number > 96 && $number < 116):
			$dir = "os";
			break;
		case ($number > 96 && $number < 123):
			$dir = "tz";
			break;
		default:
			$dir = "else";

	}

	//die($finalname." is finalname");
	$finalname = preg_replace("/[^A-Za-z0-9_\-]/", "_", $imgname) . "." . $parts[1];
	$dname = substr($who, 0, 1) . substr(md5($who . "cYo"), 0, 15);
	$dir = "pics/$dir/$dname";
	if (!is_dir($dir))
	{
		if (!mkdir($dir))
			die("<div class='error'>Something went wrong making the icon directory. Contact admin.</div>");
	}
	return $dir;
}

function deleteAll($directory, $empty = false) {
    if(substr($directory,-1) == "/") {
        $directory = substr($directory,0,-1);
    }

    if(!file_exists($directory) || !is_dir($directory)) {
        return false;
    } elseif(!is_readable($directory) || $directory == "" || $directory == "/") {
        return false;
    } else {
        $directoryHandle = opendir($directory);
       
        while ($contents = readdir($directoryHandle)) {
            if($contents != '.' && $contents != '..') {
                $path = $directory . "/" . $contents;
               
                if(is_dir($path)) {
                    deleteAll($path);
                } else {
                    unlink($path);
                }
            }
        }
       
        closedir($directoryHandle);

        if($empty == false) {
            if(!rmdir($directory)) {
                return false;
            }
        }
       
        return true;
    }
} 


//$bg = rand(1, 6);
//$box = rand(1, 6);
$bg = "#77EE77";
$bgpic=207;
$box = "#FaFabb";
$boxpic = "0";
//$border = "#888811";
$border = "#385700";
$borderwidth =2;
//$textcolor ="#000000";
//$linkcolor ="#000066";
//|| basename($_SERVER['PHP_SELF']) == "members.php")
 if($_GET['id'] && is_numeric($_GET['id']))
 {
  	$viewquery = "select * from advs where id='{$_GET['id']}' and (avail !='none' or (user='$name'))";
  	if ($_SESSION['usertype'] == "1")
		$viewquery = "select * from advs where id='{$_GET['id']}'";
	  $viewres = mysqli_query($db, $viewquery);
	  if($viewresult = @mysqli_fetch_array($viewres))
	  {
	    echo "<script>
         var advavail='{$viewresult['avail']}';
      </script>";
    }
	  else $viewresult=false;
	}
	
	if ($viewresult && (basename($_SERVER['PHP_SELF']) ==
	"view.php" ))
 {

		$bg = ($viewresult['bg']) ? $viewresult['bg'] : $bg;
		$box = ($viewresult['box']) ? $viewresult['box'] : $box;
	//	$textcolor = ($viewresult['textcolor']) ? $viewresult['textcolor'] : $textcolor;
	//	$linkcolor = ($viewresult['linkcolor']) ? $viewresult['linkcolor'] : $linkcolor;
		$border = ($viewresult['border']) ? $viewresult['border'] : $border;
		$borderwidth = ($viewresult['borderwidth']) ? $viewresult['borderwidth'] : $borderwidth;
		if ($viewresult['pic'] > 0)
		{
            $advimagepath = getPic($viewresult['pic']);
		}
	
 }

function getPic($id)
{
    global $db;
    $squery = "select * from pics where id=$id";
    $sres = mysqli_query($db, $squery);
    $result = mysqli_fetch_array($sres);
    $img = $result['filename'];
    $dir = playerDir($result['user']);
    $imagepath = "$dir/thumbs/$img";
    return $imagepath;
}

	function cssBodyChangeAdv()
{
  global $viewresult;
  return "cssBodyChange('".$viewresult['bg']."','{$viewresult['box']}','{$viewresult['border']}','{$viewresult['borderwidth']}','{$viewresult['textcolor']}','".$viewresult['linkcolor']."','{$viewresult['bgpic']}');";
}
$cleantitle=strip_tags(htmlspecialchars_decode($viewresult['title']));
// some screen titling stuff for fun
if ($checkstuff && $_GET['screen'] && $cleantitle && $edit)
{
	$ftitle = $title . " ({$viewresult['title']})";
}
else
	if ($checkstuff && $title == "Viewing")
	{
		$title = "$cleantitle";
	}
	else
		if ($checkstuff && is_numeric($_GET['screen']) && $viewresult['title'] && !$_POST['screensub'])
		{
			if ($sstuff = checkOwnScreen($_GET['screen']))
				$title = "Editing \"{$sstuff['name']}\"";
			$ftitle = $title . " ({$cleantitle})";
		}
		else
			if ($checkstuff && $_GET['id'] and !$_POST['advsub'])
				$title = "Editing \"".$cleantitle."\"";

if ($_GET['screen'] == "new" && !isset($_POST['screensub']))
	$title = "Editing New Screen";
if ($_GET['id'] == "new")
	$title = "Editing New Adventure";

if (!$title)
	$ftitle = "Create Your Own Choose-Your-Own-Adventures!";
if (!$ftitle)
	$ftitle = $title;
$ftitle = "CYOCYOA - " . $ftitle;

?>
<head>
<meta name="description" content="CYOCYOA! - A community and tool for building and sharing your own Choose Your Own Adventure-type stories." /> 
<meta name="keywords" content="cyoa, choose your own adventure,cyocyoa, adventure, interactive fiction, gamebook, fiction, browser game, browser rpg, choose your own" />

<title><?php

echo $ftitle

?></title>

<script type='text/javascript' src="nicedit/nicEdit.js"></script>
    <script type='text/javascript' src="jquery.js"></script>
    <script type='text/javascript' src="kinetic.js"></script>
<link rel="stylesheet" type="text/css" href="cyo.css?lastup=070813" />
<link rel="stylesheet" type="text/css" href="bubbles.css?lastup=60111" /> 
<script type='text/javascript' src="cyo.js?lastup=070813"></script>
<script type='text/javascript' src="encoder.js"></script>


<script type='text/javascript' src="jscolor/jscolor.js"></script>
<?php
require_once("imgpicker.php");
?>
</head>

<body id='thebody'>
<script type='text/javascript' src="divup.js"></script>
<?php

if (strstr($_SERVER['PHP_SELF'], "view.php"))
	echo "<span id='collapseholder' onclick='togTop()'>Show banner</span>
<div class='body top' style='display:none' id='topbanner'>";
else
	echo "<div class='body top' id='topbanner'>"
//<h4 style='position:relative;left:15px;'>Create Your Own Choose-Your-Own-Adventures!</h4>
?>
<div style="display:inline-block;width:auto; padding-left:25px;padding-bottom:10px;">
<a href='index.php'>
<img src='pics/bg/logo.png' alt='CYOCYOA logo'/>
</a>
    <p style="position:relative; left:0px;">Create Your Own Choose-Your-Own Adventures!</p>


</div>
<div class="bannernav">
<?php
    //<p style='position:relative;left:15px;'>Create Your Own Choose-Your-Own-Adventures!</p>
echo makeFakeButton("", false, "index.php","home", "Home", "trans",false, "/index.php", "News and things!")."&nbsp;"
.makeFakeButton("browse", false, "browse.php","category", "Browse", "trans",false, "/browse.php", "Find an adventure to read through!")."&nbsp;"
.makeFakeButton("about", false, "about.php","question", "About", "trans", false, false, "Learn about the site and its creator!")."&nbsp;"
.makeFakeButton("forum", false, "forum/","person", "Forum", "trans", false, false, "Join the dedicated CYOCYOA forum for conversation and help with the site!")."&nbsp;"
.makeFakeButton("help", false, "view.php?id=2","help", "Help", "trans", false, false, "Read through the site tutorial or learn about specific features!")."&nbsp;";
/*
<a href="index.php">Home</a> 
<a href='browse.php'>Browse Adventures</a> 
<a href="about.php">About</a> <br /> <a href="forum/">Forum</a> | <a href="view.php?id=2">Help</a> 
  */
	if (!$loggedin)
	{
		echo makeFakeButton("register", false, "/register.php","write", "Register", "trans", false, false, "Join the site to make your own Adventures!");
	}
	else 
	{
     echo  
     makeFakeButton("acct", false, "members.php?loc=user","optionspurple", "Account", "trans", false, false, "Edit account options")."&nbsp;"
.makeFakeButton("myadvs", false, "members.php?loc=adv","listred", "My Adventures", "trans", "clear:left;", false, "Create, view, and edit your adventures!")."&nbsp;"
.makeFakeButton("addpics", false, "members.php?loc=addpics","add", "Add Pictures", "trans", false, false, "Upload pictures to use in your Adventures")."&nbsp;"
.makeFakeButton("mngpics", false, "members.php?loc=managepics","lookblue", "Manage Pictures", "trans", false, false, "View, organize, or delete your uploaded pictures");
  }
echo "

</div> <div class=\"topriderr\">";

if (!$loggedin)
{
	$tomembers = array("/resetpass.php");
	if (in_array($_SERVER['PHP_SELF'], $tomembers))
		$target = "/members.php";
	else
		$target = $_SERVER['REQUEST_URI'];
	echo $error . '<form action="' . $target .
		'" method="post" ><span style=\"\"> Username/Email: <input type="text" name="logname" /><br />Password: <input type="password" name="logpass" /><br /><small> <a href="resetpass.php">forgot password?</a> <input type="checkbox" name="rememberlogin" id="rememberlogin"> <label for="rememberlogin">Remember me</label>&emsp;</small><input type="submit" value="Submit" name="loginsubmit"/> </span></form>';
}
else
{
	$thing = "?";
	if ($_SERVER['REQUEST_URI'] != $_SERVER['PHP_SELF'])
		$thing = "&";
	if ($_SESSION['usertype'] == 1)
		echo "<a href='adminnypoo.php'>Admin</a> | ";

    $messnum = $alluserinfo['newmessages'];
    if ($messnum == 1) $messagesnumber = "new message";
    else $messagesnumber = "new messages";
	echo "Logged in as <a href=\"profile.php?user=$name\">" . $name .
		"</a><br /><a href='messages.php' style='font-weight:bold'>$messnum $messagesnumber</a> </div>";
    
    echo '<div class="bottomriderr">'
    
    .makeFakeButton("", false, "index.php?action=logout","x", "Log Out", "red");
   // <a href="members.php">Member Area</a> | <a href="index.php?action=logout">Log Out</a>
}

?>
</div>
</div>
