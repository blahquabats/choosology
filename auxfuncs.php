<?php
require_once("authent.php");
require_once("comments.php");
require_once("buildadvflag.php");
require_once("icondefs.php");
function runquery($query, $database=null)
{
    global $db;
    
    //$query=mysqli_real_escape_string($db, $query);
    //preg_replace( "/\r|\n/", "", $query );
    if($database == null) $database = $db;
    if(!$database) return "<div class='error'>No DB found!</div>";
    return mysqli_query($database, $query);
}

function runquery_assoc($query, $database=null)
{
    global $db;
    
    //$query=mysqli_real_escape_string($db, $query);
    //preg_replace( "/\r|\n/", "", $query );
    if($database == null) $database = $db;
    if(!$database) return "<div class='error'>No DB found!</div>";
    $res = mysqli_query($database, $query);
    if(!$res) return mysqli_error($db);
    while($result = mysqli_fetch_assoc($res))
    {
        $results[] = $result;
    }

    return $results;
}

function insert($table,$fields)
{
    global $db;
    $q = "insert into `$table` (";
    $vals = "VALUES(";
    $first = 1;
    foreach($fields as $k=>$v)
    {
        if(!$first) 
        {
            $q .= ",";
            $vals .= ",";
        }
        else $first = 0;
        $q .= "`$k`";
        $vals .= "\"$v\"";
        
    }
    $vals .= ")";
    $q .= ") $vals";
    //echo $q;
    if(runquery($q))
    {
        return mysqli_insert_id($db);
    }
    return false;
    
}

function echoPre($var)
{
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}

function getAdv($advid)
{
    global $db;
    if(!is_numeric($advid)) return false;
    $q = "select * from advs where id = '$advid'";
    $advres = runquery_assoc($q);
    if(!$advres) return false;
    $sq = "select * from advscreens where advused = '$advid'";
    $screenres = runquery_assoc($sq);
    $orderedscreens = array();
    if(!$screenres) return array($advres[0], null);
    foreach($screenres as $sr)
    {
        $orderedscreens[$sr['id']] = array_map(html_entity_decode, array_map(html_entity_decode, $sr));
    }
    return array($advres[0], $orderedscreens);
}

function getNewMessages()
{
    $checkuser = $_SESSION['user'];
    $q = "select count(*) from messages where to_user = '$checkuser' and seen=0";
    $res = runquery($q);
    $r = mysqli_fetch_array($res);
    $number = $r[0];
    if (!$number) $number = "0";
    return $number;
}

function playerDir($who = "")
{
    global $name;
    if($who=="&everyone") return "/home/abombmcgee/choosology.com/pics/universal";
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

    $finalname = preg_replace("/[^A-Za-z0-9_\-]/", "_", $imgname) . "." . $parts[1];
    $dname = substr($who, 0, 1) . substr(md5($who . "cYo"), 0, 15);
    $dir = "/home/abombmcgee/choosology.com/pics/$dir/$dname";
    if (!is_dir($dir))
    {
        if (!mkdir($dir))
            die("<div class='error'>Something went wrong making the icon directory $dir. Contact admin.</div>");
    }
    return $dir;
}

function getPic($id)
{
    if(!$id || $id == 0) return 0;
    $squery = "select * from pics where id='$id'";
    $sres = runquery($squery);
    $result = mysqli_fetch_array($sres);
    $img = $result['filename'];
    $dir = playerDir($result['user']);
    $imagepath = "$dir/thumbs/$img";
    return $imagepath;
}

function decode($str, $strip = 0)
{
    $str = html_entity_decode($str);
    if ($strip) $str = strip_tags($str);
    return html_entity_decode($str);
}

function assembleRating($which, $readonly=true, $smallrat = 0)
{
    global $db, $name;
    if(!$which) return false;
    if(!$name) $readonly = true;
    $q = "select * from advs where id='$which'";
    $r = mysqli_query($db, $q);
    $res=mysqli_fetch_array($r);
    if($res['user']==$name) $readonly=true;
    $q = "select * from ratings where adv='$which'";
    $r = mysqli_query($db, $q);
    if ($r && $res = mysqli_fetch_array($r))
    {
        if($res['who']==$res['user'])
        {
            $readonly=true;
        }
        $count = 0;
        $sum = 0;
        do {
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
    if($smallrat) return makeStars($rat, 1);
    if(!$myrating)
    {
        $myrating=0;
        if(!$readonly) $ratdesc="You haven't rated this experiment yet!";
    }
    else {
        $ratdesc="Your rating: <b>$myrating</b> stars";
    }
    //return $noratingsoutput;
    if(!$avg) $avg="Not enough ratings";
    $id=$which;
    $output= "<div class='rateresponse$id' style='margin-left:auto;margin-right:auto;width:auto;text-align:center;white-space:nowrap;font-size:8pt;'>$avg</div>";
    $output.= "<div class='starsholder' id='starsholder$id'>
	<input type='hidden' value='$rat' class='starsrating$id'>
		<div class='loading starsloading$id'>&nbsp;</div>
		";
    for($x=1; $x<=5; $x++)
    {
        $output.="<div class='star'><div class='avgstar avgstar$x' id=\"stavg{$x}-$id\"><img src=\"images/icons/ratings/greenstar-o.png\" id=\"stavgs$x\"";
        if(!$readonly) $output.=" onmouseover=\"highlightStars($x, $id)\"";
        $output.="></div>
      <img src=\"images/icons/ratings/star-n.png\" id=\"st{$x}-$id\" ";
        if(!$readonly) $output.= " class=\"link ratingstar$x\" onmouseout=\"showAvgStars($id)\" onclick=\"sendRating($x,{$id},{$_GET['screen']});\" onmouseover=\"highlightStars($x, $id)\"  alt=\"Click to rate!\" title=\"Click to rate!\"";
        $output.= "></div>";
    }
    $output.= "</div>";
    $output.= "<div class='rateyours$id' style='margin-left:auto;margin-right:auto;width:auto;text-align:center;white-space:nowrap;font-size:8pt;'>$ratdesc</div>
      <script>
          showAvgStars($id);
      </script>
      ";
    return $output;
}

function makeStars($rating, $tiny = 0)
{
    if($rating == 0) $ratetext = "No rating yet";
    else $ratetext = "$rating stars";
    $output = " <div class='tinystarsholder' title='$ratetext'>";
    if ($rating == 0)  return $output."not rated</div>";
    for($x=1.0; $x<=5.0; $x = $x + 1.0)
    {
        if($rating >= $x) $w = 100;
        else
        {
            $y = $rating - $x + 1;
            $w = $y * 100;
        }
        $output.="<div class='star'><div class='avgstar' style='width: $w%'><img src=\"images/icons/ratings/greenstar-o-sm.png\"></div>
      <img src=\"images/icons/ratings/star-n-sm.png\" ></div>";
    }
    $output.= "</div>";
    return $output;
}

function buildColumn($title="", $where = "", $orderby="published desc", $which, $limit = "4", $inhead="more", $page = 1)
{
    global $name;
    if(strpos($limit, ","))
    {
        $limits = explode(",",$limit);
        $limitinterval = $limits[1];
    }
    else
    {
        $limits = array(0,$limit);   
        $limitinterval = $limit;
    }
    
    $where=stripslashes(html_entity_decode($where));
    if ($where) $where = "and ".$where;
    $query = "select *,
a.id as aid
from advs a, advscreens s
where avail='public' $where and s.id = a.begin order by $orderby limit $limit";
    $countquery = "select count(a.id) as c
from advs a, advscreens s
where avail='public' $where and s.id = a.begin";
    $res = runquery_assoc($query);
    $cres = runquery_assoc($countquery);
    $titleid = strtolower(preg_replace("/\s/","", $title));
    $out = "";
    if($title) $out .= <<<EOD
<div class='columntitle'>$title
EOD;
    else $out .= "<div class='columntitle'>&nbsp;";
    $advstart = $limits[0]+1;
    $advcount = $limits[1]*3;
    $fullcount = $cres[0]["c"];
    if($fullcount < ($advstart+$advcount-1)) $advend = $fullcount;
    else $advend = $advcount + $limits[0];
    
    switch ($inhead)
    {
    	case "more":
    		    $out .= <<<EOD
    <a href='#' class='seemore' id = '$titleid' onclick = 'triggerSearch("$which", $limitinterval, 1)'>see more...&nbsp;</a></div>
EOD;
        break;
        case "count":
        if($advstart > $advend) $advstart = $advend;
        if($fullcount ==0) $out.=" (No results $countquery)</div>";
    	else $out .= <<<EOD
     ($advstart-$advend of $fullcount results)</div>
EOD;
        break;
        case "prev":
        $lastpage= $page-1;
        if($advstart == $limitinterval+1) $out.="</div>";
    	else $out .= <<<EOD
     <a href='#' class='seemore' id = '$titleid' onclick = 'triggerSearch("$which", $limitinterval, 1, $lastpage)'>&larr; previous</a></div>
EOD;
        break;
        case "next":
        $nextpage= $page+1;
        $advend = $advstart+$limitinterval; // cause we're in the third column, all this stuff gets screwed up... this is a dumb way to solve this problem
        if($advend >= $fullcount) $out.="</div>";
    	else $out .= <<<EOD
     <a href='#' class='seemore' id = '$titleid' onclick = 'triggerSearch("$which", $limitinterval, 1, $nextpage)'>next &rarr;</a> </div>
EOD;
        break;
    	
    }
    

    if(!$res) return $out;
    foreach ($res as $r)
    {
        $out.=buildMiniFlag($r['aid'], $name);
    }

    return $out;
}

function makeFakeButton($id, $onclick, $href, $icon, $text, $color="gray", $style=false, $check=false, $rollover=false)
{
  $button="";
  if($href) $button.="<a href='$href' class='fakebuttona' alt='$text'>";
  $button.= "<div "; 
  
  if($rollover) 
  {
    $button.=" onmouseover=\"{$id}roll=setTimeout('document.getElementById(\'{$id}roll\').style.visibility=\'visible\'', 500)\" onmouseout=\"clearTimeout({$id}roll); document.getElementById('{$id}roll').style.visibility='hidden'\"";
    $onclick="clearTimeout({$id}roll); $onclick";
  }
  if($onclick) $button.=" onclick=\"$onclick\" id='$id'";
  $button.=" class='f$color"; 
  if($check && ($_SERVER['PHP_SELF']==$check)) $button.=" fdisable";
  else if ($href && ($href==$_SERVER['REQUEST_URI'] || "/choosology".$href==$_SERVER['REQUEST_URI'])) $button .=" fdisable";
  $button.= " fakebutton' ";
   if($style) $button.=" style='$style'"; 
  
   $button.=">";
  if($icon) $button.=icon($icon, "small");
        $button.=" $text ";     
    if($rollover) $button.=" <div class='triangle-border top awaiting' id='{$id}roll'>
    $rollover
    </div>";    
        $button.="</div>";
  if($href) $button.="</a>";
          
   return $button;
}

function nicedatetime($date, $mode="datetime")
{
    $phptime = strtotime($date);
    switch ($mode)
    {
        case "date":
            return date('m/d/Y', $phptime);
            break;
        case "time":
            return date('g:ia', $phptime);
            break;
        case "datetime":
        default:
            return date('g:ia \o\n m/d/Y', $phptime);
    }
}
?>