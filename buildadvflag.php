<?php

function buildAdvFlag($id, $user, $mini = 0)
{
    /* Explicit columns only: SELECT * from a join makes duplicate names (e.g. description) overwrite in mysqli_fetch_assoc */
    $query = "SELECT
        a.id AS aid,
        a.pic AS advpic,
        a.title AS advtitle,
        a.user AS advuser,
        a.avail AS avail,
        a.rating AS rating,
        a.description,
        a.published,
        a.bg,
        a.bgpic,
        a.tags,
        a.textstyle,
        a.titlestyle,
        a.box,
        a.borderwidth,
        a.border
    FROM advs a
    INNER JOIN advscreens s ON s.id = a.`begin`
    WHERE a.id = '$id'
    AND (a.avail = 'public' OR a.user = '$user')";
    $r = runquery_assoc($query);
    $r = $r[0];
    $pic = getPicUrl($r['advpic'], true);
    $bg = $r['bg'];
    $bgpicUrl = getPicUrl($r['bgpic'], false);
    if ($bgpicUrl !== '')
    {
        $bgpic = "background-image: url('" . htmlspecialchars($bgpicUrl, ENT_QUOTES, 'UTF-8') . "');";
    }
    else $bgpic = "";
    $avail = decode($r['avail']);
    $desc = decode($r['description']);
    $savedrating = $r['rating'];
    $published = strtotime($r['published']);
    $published = date("M d, Y", $published);
    $mine = false;
    if ($user == $r['advuser']) $mine = true;
    $tags = "";
    if($mini)
    {
        $rating = assembleRating($r['aid'], 1, 1);
        
        $title = decode($r['advtitle'], 1);
        if($avail !=='public')
        {
            if ($avail == "private") $title .= " (private)";
            if ($avail == "none") $title .= " (<b>unpublished</b>)";
            if ($avail == "public") $title .= " (public)";
        }
            
        if($r['tags'])
        {
            $taglist = explode(",", $r['tags']);
            foreach ($taglist as $tag)
            {
                $tag = trim($tag);
                $tags .= "<div class='smalltag' title='{$r['tags']}'>$tag</div>";
            }
        }
    }
    else
    {
        $rating = assembleRating($r['aid'], 1);
        $title = decode($r['advtitle']);
        if($r['tags'])
        {
            $taglist = explode(",", $r['tags']);
            foreach ($taglist as $tag)
            {
                $tag = trim($tag);
                $tags .= "<div class='onetag' title='{$r['tags']}'>$tag</div>";
            }
        }
    }

    if($r['textstyle']) $textstyle = $r['textstyle'];
    else $textstyle = "";
    if($r['titlestyle']) $titlestyle = $r['titlestyle'];
    else $titlestyle = "";
    if($r['box']) $pagestyle = "background-color: ".$r['box']."; border: ".$r['borderwidth']."px solid ".$r['border'].";";
    else $pagestyle = "";
    if($mini)
    {
        if($rating === 0) $rating = "No Ratings";
        if ($avail == "none" && $savedrating == "NA") $rating = ""; // if there's a rating, always show it; if not, only show the "not rated" for published exps
        $out = "<div class='miniflag' data-viewid = \"$id\">
    $title<hr />";
        if($pic) $out .= "<img src=\"" . htmlspecialchars($pic, ENT_QUOTES, 'UTF-8') . "\" alt=\"\" />";
        $out .= "<div class='tinyrating'>$rating</div><br />Tagged: $tags";
        if($mine) 
        {
            $out .= "<div class='deleteadvbutton' style='float:right;' data-advid='$id' id='delete-$id'>X</div>
            <div class='editadvbutton' style='float:right;' data-advid='$id' id='edit-$id'>Edit</div>";
        }
        $out .= "</div>";
        return $out;
    }
    $out = "
    <div class='slidefolder' style = \"background-color: $bg; $bgpic\">

    <div class='slideinfo' style='$pagestyle; $titlestyle'>";
    if($pic) $out .= "<img class='advflagicon' src=\"" . htmlspecialchars($pic, ENT_QUOTES, 'UTF-8') . "\" alt=\"\" />";
    $out .= "<div class='slideinfo-inner'>
    <div class='slideinfo-toprow'>
    <div class='slideinfo-titlewrap'><a class='link' onclick=\"location.href='#/view/$id'\">$title</a></div>
    <div class='advflagrating'>$rating</div>
    </div>
    <div class='slideinfo-meta'>
    <small>by <a class='link' onclick=\"location.href='#/mystuff/$id'\">{$r['advuser']}</a></small>
    <br />
    <small>Published $published</small><br />
    $tags
    </div>
    </div>
    </div>
    <div class='oneslide oneslide-teaser' style = '$pagestyle'>
    <div class='slidetitle' style='$textstyle'>
    <span class='expnotes'><b>Experiment&nbsp;notes:</b></span>
    <br />
   $desc
    </div>
    </div>
    </div>

    ";

    return $out;
}

function buildMiniFlag($id, $user)
{
    return buildAdvFlag($id, $user, 1);
}
?>