<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'paths-config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'font-options.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'messagesfunc.php';
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
    $results = [];
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
        $orderedscreens[$sr['id']] = array_map(function ($v) {
            $s = $v === null ? '' : (string) $v;
            return html_entity_decode(html_entity_decode($s));
        }, $sr);
    }
    return array($advres[0], $orderedscreens);
}

function getNewMessages()
{
	if (empty($_SESSION['user'])) {
		return '0';
	}
	if (function_exists('choosology_unread_message_count')) {
		return (string) choosology_unread_message_count((string) $_SESSION['user']);
	}
	$checkuser = mysqli_real_escape_string($GLOBALS['db'], (string) $_SESSION['user']);
	$q = "select count(*) from messages where to_user = '$checkuser' and seen=0 and IFNULL(to_deleted,0)=0";
	$res = runquery($q);
	if (!$res) {
		return '0';
	}
	$r = mysqli_fetch_array($res);
	$number = $r[0];
	if (!$number) {
		$number = '0';
	}
	return $number;
}

function playerDir($who = "")
{
    global $name;
    if ($who == "&everyone") {
        return choosology_pics_universal_dir();
    }
    $who = ($who != "") ? $who : $name;

    $number = ord(strtolower(substr($who, 0, 1)));
    switch ($number)
    {
        case ($number > 96 && $number < 101):
            $bucket = "ad";
            break;
        case ($number > 96 && $number < 106):
            $bucket = "ei";
            break;
        case ($number > 96 && $number < 111):
            $bucket = "jn";
            break;
        case ($number > 96 && $number < 116):
            $bucket = "os";
            break;
        case ($number > 96 && $number < 123):
            $bucket = "tz";
            break;
        default:
            $bucket = "else";

    }

    $dname = substr($who, 0, 1) . substr(md5($who . "cYo"), 0, 15);
    $root = choosology_pics_root();
    $dir = $root . DIRECTORY_SEPARATOR . $bucket . DIRECTORY_SEPARATOR . $dname;
    if (!is_dir($dir))
    {
        if (!mkdir($dir, 0775, true)) {
            die("<div class='error'>Something went wrong making the icon directory $dir. Contact admin.</div>");
        }
    }
    return $dir;
}

function getPic($id)
{
    if(!$id || $id == 0) return 0;
    $squery = "select * from pics where id='$id'";
    $sres = runquery($squery);
    $result = mysqli_fetch_array($sres);
    if (!$result) {
        return 0;
    }
    $img = $result['filename'];
    $dir = playerDir($result['user']);
    $imagepath = "$dir/thumbs/$img";
    return $imagepath;
}

/**
 * Browser URL for a row in pics (thumbnails and player-facing images). Uses ajax/pic.php.
 * For server-side file access, use {@see getPic()} which returns a filesystem path under pics_root.
 */
function getPicUrl($id, bool $useThumb = true): string
{
	if ($id === null || $id === '' || $id === 0 || $id === '0') {
		return '';
	}
	$pid = (int) $id;
	if ($pid < 1) {
		return '';
	}
	if ($useThumb) {
		return choosology_site_url('ajax/pic.php?id=' . $pid . '&thumb=1');
	}
	return choosology_site_url('ajax/pic.php?id=' . $pid);
}

/**
 * Pictures available to a user: their uploads plus universal (&everyone) assets.
 *
 * @return list<array<string, mixed>>
 */
function getUserPics(string $username): array
{
	global $db;
	$username = mysqli_real_escape_string($db, $username);
	$q = "SELECT * FROM pics WHERE user = '$username' OR user = '&everyone' ORDER BY (user = '&everyone') ASC, id DESC";
	$res = runquery_assoc($q);
	if (!is_array($res)) {
		return [];
	}
	return $res;
}

/**
 * Whether PHP GD is available for thumbnail generation.
 */
function choosology_gd_available(): bool
{
	return function_exists('imagecreatetruecolor')
		&& (function_exists('imagecreatefromjpeg') || function_exists('imagecreatefrompng'));
}

/**
 * Write a max-240px thumbnail of $src to $dest.
 * Requires the PHP GD extension for real resizing; without GD it copies the full file
 * (which makes UI thumbs enormous — install php-gd / php8.3-gd).
 *
 * @return bool true when a resized thumb was written; false when it fell back to a full copy / failed
 */
function choosology_make_image_thumb(string $src, string $dest, string $mime = '', int $max = 240): bool
{
	if ($mime === '') {
		$info0 = @getimagesize($src);
		$mime = is_array($info0) && !empty($info0['mime']) ? (string) $info0['mime'] : '';
	}
	if (!choosology_gd_available()) {
		@copy($src, $dest);
		return false;
	}
	$info = @getimagesize($src);
	if (!$info || empty($info[0]) || empty($info[1])) {
		@copy($src, $dest);
		return false;
	}
	$w = (int) $info[0];
	$h = (int) $info[1];
	$max = max(16, $max);
	$scale = min(1, $max / max($w, $h));
	$tw = max(1, (int) round($w * $scale));
	$th = max(1, (int) round($h * $scale));

	if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
		$im = @imagecreatefromjpeg($src);
	} elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
		$im = @imagecreatefrompng($src);
	} elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
		$im = @imagecreatefromgif($src);
	} elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
		$im = @imagecreatefromwebp($src);
	} else {
		$im = false;
	}
	if (!$im) {
		@copy($src, $dest);
		return false;
	}

	$thumb = imagecreatetruecolor($tw, $th);
	if ($mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp') {
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
		$transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
		imagefilledrectangle($thumb, 0, 0, $tw, $th, $transparent);
	}
	imagecopyresampled($thumb, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
	$ok = false;
	if ($mime === 'image/png' && function_exists('imagepng')) {
		$ok = imagepng($thumb, $dest);
	} elseif ($mime === 'image/gif' && function_exists('imagegif')) {
		$ok = imagegif($thumb, $dest);
	} elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
		$ok = imagewebp($thumb, $dest, 82);
	} elseif (function_exists('imagejpeg')) {
		$ok = imagejpeg($thumb, $dest, 84);
	}
	imagedestroy($im);
	imagedestroy($thumb);
	if (!$ok) {
		@copy($src, $dest);
		return false;
	}
	return true;
}

/**
 * Filesystem path for a pics row (full image or thumb); prefers requested variant, falls back if missing.
 */
function choosology_pic_filesystem_path(array $row, bool $wantThumb): ?string
{
	$fn = (string) ($row['filename'] ?? '');
	if ($fn === '') {
		return null;
	}
	$user = (string) ($row['user'] ?? '');
	$base = playerDir($user !== '' ? $user : '&everyone');
	$thumbPath = $base . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $fn;
	$fullPath = $base . DIRECTORY_SEPARATOR . $fn;
	if ($wantThumb) {
		if (is_file($thumbPath)) {
			return $thumbPath;
		}
		if (is_file($fullPath)) {
			return $fullPath;
		}
		return null;
	}
	if (is_file($fullPath)) {
		return $fullPath;
	}
	if (is_file($thumbPath)) {
		return $thumbPath;
	}
	return null;
}

/**
 * True if advs.pic points to a pics row whose image (thumb preferred) exists on disk.
 * Used so miniflags / flags do not show a broken icon when the row or file is missing.
 */
function choosology_adv_pic_usable_for_display($picId): bool
{
	static $cache = array();
	if ($picId === null || $picId === '') {
		return false;
	}
	$key = (string) $picId;
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}
	$pid = (int) $picId;
	if ($pid < 1) {
		$cache[$key] = false;
		return false;
	}
	global $db;
	$res = mysqli_query($db, 'SELECT id, user, filename FROM pics WHERE id = ' . $pid . ' LIMIT 1');
	if (!$res || mysqli_num_rows($res) < 1) {
		$cache[$key] = false;
		return false;
	}
	$row = mysqli_fetch_assoc($res);
	if (!is_array($row)) {
		$cache[$key] = false;
		return false;
	}
	$path = choosology_pic_filesystem_path($row, true);
	$ok = $path !== null && is_readable($path);
	$cache[$key] = $ok;
	return $ok;
}

/**
 * Remove &lt;img&gt; tags that point at ajax/pic.php ids whose files are missing on disk.
 * Used when rendering play/view HTML so missing library files leave no broken-image chrome or alt text.
 * Non-library images are left alone (the play UI hides those on load error).
 */
function choosology_omit_unreachable_pic_images(string $html): string
{
	if ($html === '' || stripos($html, '<img') === false) {
		return $html;
	}
	return (string) preg_replace_callback('/<img\b[^>]*>/i', function (array $m): string {
		$tag = $m[0];
		if (preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $tag, $sm)) {
			$src = html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		} elseif (preg_match("/\bsrc\s*=\s*'([^']*)'/i", $tag, $sm)) {
			$src = html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		} else {
			return '';
		}
		$src = trim($src);
		$parts = parse_url($src);
		$path = isset($parts['path']) ? str_replace('\\', '/', (string) $parts['path']) : '';
		$query = isset($parts['query']) ? (string) $parts['query'] : '';
		if (preg_match('#(?:.*/)?ajax/pic\.php$#i', $path)) {
			parse_str($query, $qp);
			$id = isset($qp['id']) ? (int) $qp['id'] : 0;
			if ($id < 1 || !choosology_adv_pic_usable_for_display($id)) {
				return '';
			}
		}
		return $tag;
	}, $html);
}

/**
 * Undo connect.php's htmlspecialchars(mysqli_real_escape_string(...)) mutation of POST/GET strings.
 */
function choosology_undo_connect_string_mutation(string $value): string
{
	return stripslashes(htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML5));
}

/**
 * Whether an <img src> is allowed in saved screen HTML: Choosology library URLs or http(s)// URLs whose path ends in a common raster/vector image extension.
 */
function choosology_screen_html_img_src_allowed(string $src): bool
{
	$src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	if ($src === '') {
		return false;
	}
	if (stripos($src, 'data:') === 0 || stripos($src, 'javascript:') === 0) {
		return false;
	}
	$parts = parse_url($src);
	$path = isset($parts['path']) ? str_replace('\\', '/', (string) $parts['path']) : '';
	$query = isset($parts['query']) ? (string) $parts['query'] : '';

	if (preg_match('#(?:.*/)?ajax/pic\.php$#i', $path)) {
		parse_str($query, $qp);
		if (!empty($qp['id']) && is_numeric($qp['id']) && (int) $qp['id'] > 0) {
			return true;
		}
		return false;
	}

	$pathForExt = $path;
	if ($pathForExt === '' && (preg_match('#^https?://#i', $src) || strncmp($src, '//', 2) === 0)) {
		$abs = strncmp($src, '//', 2) === 0 ? 'https:' . $src : $src;
		$pathForExt = (string) (parse_url($abs, PHP_URL_PATH) ?? '');
	}
	if ($pathForExt === '' && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $src)) {
		$pathForExt = preg_replace('/[?#].*$/', '', str_replace('\\', '/', $src));
	}
	if ($pathForExt === '') {
		return false;
	}
	return (bool) preg_match('/\.(jpe?g|png|gif|webp|svg|avif|bmp|tiff?)(?:$|[?#])/i', $pathForExt);
}

/** Remove <img> tags whose src fails {@see choosology_screen_html_img_src_allowed}. */
function choosology_sanitize_screen_html_images(string $html): string
{
	return (string) preg_replace_callback('/<img\b[^>]*>/i', function (array $m): string {
		$tag = $m[0];
		if (preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $tag, $sm)) {
			$src = $sm[1];
		} elseif (preg_match("/\bsrc\s*=\s*'([^']*)'/i", $tag, $sm)) {
			$src = $sm[1];
		} else {
			return '';
		}
		return choosology_screen_html_img_src_allowed($src) ? $tag : '';
	}, $html);
}

function decode($str, $strip = 0)
{
    $str = (string) ($str ?? '');
    $str = html_entity_decode($str);
    if ($strip) {
        $str = strip_tags($str);
    }
    return html_entity_decode($str);
}

function assembleRating($which, $readonly=true, $smallrat = 0)
{
    global $db, $name;
    $rat = 0;
    $myrating = null;
    $avg = null;
    if(!$which) return false;
    if(!$name) $readonly = true;
    $q = "select * from advs where id='$which'";
    $r = mysqli_query($db, $q);
    $advRow = mysqli_fetch_array($r);
    if (!$advRow) {
        return false;
    }
    if (($advRow['user'] ?? '') === $name) {
        $readonly = true;
    }
    $q = "select * from ratings where adv='$which'";
    $r = mysqli_query($db, $q);
    if ($r && $res = mysqli_fetch_array($r))
    {
        // ratings table uses owner (experiment owner), not user
        if (($res['who'] ?? '') === ($res['owner'] ?? ''))
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
    $ratdesc = '';
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
    if ($rating == 0)  return $output."<span class=\"tinystarsholder-norating\">not rated</span></div>";
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

function buildColumn($which, $title = "", $where = "", $orderby = "COALESCE(a.published, a.created) desc", $limit = "4", $inhead = "more", $page = 1)
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
where avail='public' $where and s.id = a.`begin` order by $orderby limit $limit";
    $countquery = "select count(a.id) as c
from advs a, advscreens s
where avail='public' $where and s.id = a.`begin`";
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
        $out.=buildMiniFlag($r['aid'], $name, true);
    }

    return $out;
}

/**
 * Miniflag HTML only (no column header) — used for unified browse layout.
 */
function buildColumnFlagsOnly($where, $orderby, $limit)
{
    global $name;
    if (strpos($limit, ","))
    {
        $limits = explode(",", $limit);
        $limit = $limits[0] . "," . $limits[1];
    }
    else
    {
        $limit = "0," . $limit;
    }
    $where = stripslashes(html_entity_decode($where));
    if ($where) {
        $where = "and " . $where;
    }
    $query = "select *,
a.id as aid
from advs a, advscreens s
where avail='public' $where and s.id = a.`begin` order by $orderby limit $limit";
    $res = runquery_assoc($query);
    $out = "";
    if (!is_array($res) || count($res) === 0) {
        return $out;
    }
    foreach ($res as $r)
    {
        $out .= buildMiniFlag($r['aid'], $name, true);
    }
    return $out;
}

/**
 * Single full-width toolbar + three flag-only columns for Browse (matches one logical result set).
 */
function buildBrowseUnifiedToolbarHtml($which, $title, $where, $orderby, $first, $second, $third, $page)
{
    $whereSql = stripslashes(html_entity_decode($where));
    if ($whereSql) {
        $whereSql = "and " . $whereSql;
    }
    $countquery = "select count(a.id) as c
from advs a, advscreens s
where avail='public' $whereSql and s.id = a.`begin`";
    $cres = runquery_assoc($countquery);
    $fullcount = isset($cres[0]["c"]) ? (int) $cres[0]["c"] : 0;

    $parseL = static function ($lim) {
        if (strpos($lim, ",") !== false)
        {
            $a = explode(",", $lim);
            return array((int) $a[0], (int) $a[1]);
        }
        return array(0, (int) $lim);
    };
    $L1 = $parseL($first);
    $L2 = $parseL($second);
    $L3 = $parseL($third);
    $limits = $L1;
    $limitinterval = $limits[1];

    $advstart = $limits[0] + 1;
    $advcount = $limits[1] * 3;
    if ($fullcount < ($advstart + $advcount - 1)) {
        $advend = $fullcount;
    } else {
        $advend = $advcount + $limits[0];
    }
    if ($advstart > $advend) {
        $advstart = $advend;
    }

    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $whichAttr = htmlspecialchars((string) $which, ENT_QUOTES, 'UTF-8');
    $limAttr = (int) $limitinterval;

    if ($fullcount === 0) {
        $rangeHtml = '<span class="columntitle-range">(No results)</span>';
    } else {
        $rangeHtml = '<span class="columntitle-range">(' . (int) $advstart . "–" . (int) $advend . ' of ' . $fullcount . ")</span>";
    }

    $limits2 = $L2;
    $advstart2 = $limits2[0] + 1;
    $prevLink = "";
    if ($advstart2 != $limitinterval + 1)
    {
        $prevPage = max(1, (int) $page - 1);
        $prevLink = '<a href="#" class="seemore browse-nav-prev" data-which="' . $whichAttr . '" data-limit="' . $limAttr . '" data-page="' . $prevPage . '">&larr; previous</a>';
    }

    $limits3 = $L3;
    $advstart3 = $limits3[0] + 1;
    $advend3 = $advstart3 + $limitinterval;
    $nextLink = "";
    if ($advend3 < $fullcount)
    {
        $nextPage = (int) $page + 1;
        $nextLink = '<a href="#" class="seemore browse-nav-next" data-which="' . $whichAttr . '" data-limit="' . $limAttr . '" data-page="' . $nextPage . '">next &rarr;</a>';
    }

    return "<div class=\"columntitle columntitle--unified\" role=\"toolbar\" aria-label=\"Result navigation\"><span class=\"columntitle-cluster\"><span class=\"columntitle-label\">"
        . $titleEsc . "</span>" . $rangeHtml . "</span><span class=\"columntitle-navcluster\">" . $prevLink . $nextLink . "</span></div>";
}

function buildBrowseUnifiedFourPack($which, $title, $where, $orderby, $first, $second, $third, $page)
{
    $toolbar = buildBrowseUnifiedToolbarHtml($which, $title, $where, $orderby, $first, $second, $third, $page);
    $b1 = buildColumnFlagsOnly($where, $orderby, $first);
    $b2 = buildColumnFlagsOnly($where, $orderby, $second);
    $b3 = buildColumnFlagsOnly($where, $orderby, $third);
    return $toolbar . "!@!@!" . $b1 . "!@!@!" . $b2 . "!@!@!" . $b3;
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

/**
 * Basic email validation for signup / legacy registerCheck.
 */
function checkEmail($email): bool
{
	$email = trim((string) $email);
	if ($email === '' || strlen($email) > 45) {
		return false;
	}
	return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Ensure signup preference columns exist (newsletter, welcome_pending).
 */
function choosology_users_ensure_signup_columns(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	$have = array();
	$r = @mysqli_query($db, 'SHOW COLUMNS FROM users');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$have[(string) ($row['Field'] ?? '')] = true;
		}
	}
	if (empty($have['newsletter'])) {
		@mysqli_query($db, 'ALTER TABLE users ADD COLUMN newsletter tinyint(1) NOT NULL DEFAULT 0');
	}
	if (empty($have['welcome_pending'])) {
		@mysqli_query($db, 'ALTER TABLE users ADD COLUMN welcome_pending tinyint(1) NOT NULL DEFAULT 0');
	}
}

/**
 * Attempt a plain-text welcome / confirmation email. Returns true if mail() accepted it.
 */
function choosology_send_welcome_email(string $name, string $email): bool
{
	if (!function_exists('mail')) {
		return false;
	}
	$name = trim($name);
	$email = trim($email);
	if ($name === '' || $email === '' || !checkEmail($email)) {
		return false;
	}
	$subject = 'Choosology Lab — application received';
	$body = "Hello {$name},\n\n"
		. "Your application to the Choosology Lab has been filed. You are cleared for access.\n\n"
		. "Next steps (more tutorial material will land here later):\n"
		. "  1. Sign in with your lab handle.\n"
		. "  2. Open My Stuff → Experiments to begin your first experiment.\n"
		. "  3. Browse the catalog when you want inspiration.\n\n"
		. "We only email what you asked for on your application.\n\n"
		. "— The Choosology Lab\n";
	$from = 'Choosology Lab <noreply@choosology.com>';
	$headers = 'MIME-Version: 1.0' . "\r\n"
		. 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
		. 'From: ' . $from . "\r\n"
		. 'Reply-To: ' . $from . "\r\n"
		. 'X-Mailer: Choosology';
	return (bool) @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

/** Choice payload delimiter used in advscreens.choice1..8 ("label|Q-D-|targetId"). */
function choosology_choice_delimiter(): string
{
	return '|Q-D-|';
}

/**
 * True when a screen has no valid outgoing choices (terminal / ending node).
 * Loops that only point back to the adventure begin are ignored (same as player).
 *
 * @param array<string,mixed> $screen
 */
function choosology_screen_is_ending(array $screen, $beginId): bool
{
	$begin = (string) $beginId;
	$delim = choosology_choice_delimiter();
	for ($i = 1; $i <= 8; $i++) {
		$raw = $screen['choice' . $i] ?? '';
		if ($raw === '' || $raw === null) {
			continue;
		}
		$parts = explode($delim, (string) $raw);
		if (empty($parts[0]) || empty($parts[1])) {
			continue;
		}
		if ((string) $parts[1] === $begin) {
			continue;
		}
		return false;
	}
	return true;
}

function choosology_ensure_ending_finds_table(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS ending_finds (
			id int unsigned NOT NULL AUTO_INCREMENT,
			uname varchar(45) NOT NULL,
			adv int unsigned NOT NULL,
			screen int unsigned NOT NULL,
			found_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY ending_finds_user_adv_screen (uname, adv, screen),
			KEY ending_finds_adv_user (adv, uname)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);
}

/**
 * @return list<int>
 */
function choosology_adventure_ending_ids(mysqli $db, int $advid): array
{
	$advid = max(0, $advid);
	if ($advid < 1) {
		return array();
	}
	$begin = '';
	$br = mysqli_query($db, 'SELECT begin FROM advs WHERE id = ' . $advid . ' LIMIT 1');
	if ($br && ($row = mysqli_fetch_assoc($br))) {
		$begin = (string) ($row['begin'] ?? '');
	}
	$ids = array();
	$sr = mysqli_query(
		$db,
		'SELECT * FROM advscreens WHERE advused = ' . $advid . ' AND IFNULL(deleted, 0) NOT IN (1, \'1\')'
	);
	if (!$sr) {
		return array();
	}
	while ($screen = mysqli_fetch_assoc($sr)) {
		if (choosology_screen_is_ending($screen, $begin)) {
			$ids[] = (int) ($screen['id'] ?? 0);
		}
	}
	return array_values(array_filter($ids, static function ($id) {
		return $id > 0;
	}));
}

/**
 * Record that the current visitor found an ending; return progress for the UI.
 *
 * @return array{found:int,more:bool,total:int}
 */
function choosology_record_ending_find(mysqli $db, int $advid, int $screenid): array
{
	$advid = max(0, $advid);
	$screenid = max(0, $screenid);
	$endingIds = choosology_adventure_ending_ids($db, $advid);
	$total = count($endingIds);
	$endingSet = array_fill_keys($endingIds, true);

	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (!isset($_SESSION['ending_finds']) || !is_array($_SESSION['ending_finds'])) {
		$_SESSION['ending_finds'] = array();
	}
	if (!isset($_SESSION['ending_finds'][$advid]) || !is_array($_SESSION['ending_finds'][$advid])) {
		$_SESSION['ending_finds'][$advid] = array();
	}

	$user = '';
	if (!empty($_SESSION['user'])) {
		$user = (string) $_SESSION['user'];
	}

	if ($user !== '') {
		choosology_ensure_ending_finds_table($db);
		$escUser = mysqli_real_escape_string($db, $user);
		$existing = mysqli_query(
			$db,
			"SELECT screen FROM ending_finds WHERE uname = '$escUser' AND adv = $advid"
		);
		if ($existing) {
			while ($row = mysqli_fetch_assoc($existing)) {
				$sid = (int) ($row['screen'] ?? 0);
				if ($sid > 0 && isset($endingSet[$sid])) {
					$_SESSION['ending_finds'][$advid][$sid] = 1;
				}
			}
		}
	}

	if ($screenid > 0 && isset($endingSet[$screenid])) {
		$_SESSION['ending_finds'][$advid][$screenid] = 1;
		if ($user !== '') {
			$escUser = mysqli_real_escape_string($db, $user);
			@mysqli_query(
				$db,
				"INSERT IGNORE INTO ending_finds (uname, adv, screen, found_at)
				 VALUES ('$escUser', $advid, $screenid, NOW())"
			);
		}
	}

	$found = 0;
	foreach ($_SESSION['ending_finds'][$advid] as $sid => $_) {
		$sid = (int) $sid;
		if (isset($endingSet[$sid])) {
			$found++;
		}
	}

	return array(
		'found' => $found,
		'more' => ($total > 0 && $found < $total),
		'total' => $total,
	);
}

/**
 * Lab-styled end-of-experiment panel: outcome notice, ending progress, rating, comments.
 */
function choosology_build_ending_panel_html(mysqli $db, int $advid, int $screenid): string
{
	global $name;

	$progress = choosology_record_ending_find($db, $advid, $screenid);
	$found = (int) $progress['found'];
	$more = !empty($progress['more']);
	$total = (int) $progress['total'];

	$foundLabel = $found === 1
		? 'You have catalogued <strong>1</strong> end screen in this experiment.'
		: 'You have catalogued <strong>' . $found . '</strong> end screens in this experiment.';

	if ($total <= 0) {
		$moreHtml = '';
	} elseif ($more) {
		$moreHtml = '<p class="ending-panel-progress-more">Lab note: additional terminal outcomes remain unclassified.</p>';
	} else {
		$moreHtml = '<p class="ending-panel-progress-done">Lab note: every terminal outcome in this experiment has been logged.</p>';
	}

	$loggedIn = !empty($name) || !empty($_SESSION['user']);
	$ratePrompt = $loggedIn
		? 'Rate this experiment'
		: 'Log in to rate this experiment';

	$html = '<div class="ending-panel" role="status" aria-label="End of experiment">';
	$html .= '<p class="ending-panel-eyebrow">Terminal outcome <span class="ending-panel-eyebrow-tag">end node</span></p>';
	$html .= '<h3 class="ending-panel-title">You have reached an end</h3>';
	$html .= '<p class="ending-panel-lede">This path through the experiment terminates here. Other routes may end differently.</p>';
	$html .= '<div class="ending-panel-progress">';
	$html .= '<p class="ending-panel-progress-count">' . $foundLabel . '</p>';
	$html .= $moreHtml;
	$html .= '</div>';
	$html .= '<div class="ending-panel-rate">';
	$html .= '<p class="ending-panel-rate-label">' . htmlspecialchars($ratePrompt, ENT_QUOTES, 'UTF-8') . '</p>';
	$html .= assembleRating($advid, false);
	$html .= '</div>';
	$html .= '<hr class="ending-panel-rule" />';
	$html .= '<div class="commentsdiv hidecomments commentsdiv-' . (int) $screenid . '">';
	$comments = new commentArea('adv' . $advid, true, false, $screenid);
	$html .= $comments->display(true);
	$html .= '</div>';
	$html .= '<input type="hidden" name="commentsexist" id="commentsexist" value="1">';
	$html .= '</div>';

	return $html;
}
?>