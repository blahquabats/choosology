<?php

function buildAdvFlag($id, $user, $mini = 0, $applyBrowseAdvTone = false)
{
    /* Explicit columns only: SELECT * from a join makes duplicate names (e.g. description) overwrite in mysqli_fetch_assoc */
    $query = "SELECT
        a.id AS aid,
        a.pic AS advpic,
        a.title AS advtitle,
        a.user AS advuser,
        a.avail AS avail,
        a.review_hold AS review_hold,
        a.rating AS rating,
        a.description,
        a.created,
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
    AND (a.avail IN ('public', 'restricted') OR a.user = '$user')";
    $r = runquery_assoc($query);
    if (!is_array($r) || !isset($r[0])) {
        return '';
    }
    $r = $r[0];
    if (!function_exists('choosology_adventure_can_view')) {
        require_once __DIR__ . '/lib/adventure-review-helpers.php';
    }
    if (!choosology_adventure_can_view($r, (string) $user)) {
        return '';
    }
    $pic = getPicUrl($r['advpic'], true);
    if ($pic !== '' && !choosology_adv_pic_usable_for_display($r['advpic'] ?? null)) {
        $pic = '';
    }
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
    $pubRaw = $r['published'] ?? null;
    $pubTs = ($pubRaw !== null && $pubRaw !== '' && $pubRaw !== '0000-00-00 00:00:00') ? strtotime((string) $pubRaw) : false;
    $creTs = strtotime((string) ($r['created'] ?? ''));
    if ($avail === 'public' && $pubTs > 0) {
        $dateline = 'Published ' . date('M d, Y', $pubTs);
    } elseif ($creTs > 0) {
        $dateline = 'Created ' . date('M d, Y', $creTs);
    } else {
        $dateline = '';
    }
    $mine = false;
    if ($user == $r['advuser']) $mine = true;
    $tags = "";
    if($mini)
    {
        $rating = assembleRating($r['aid'], 1, 1);
        
        $title = decode($r['advtitle'], 1);
        $descText = trim(decode($r['description'] ?? '', 1));
        $descHtml = $descText !== '' ? htmlspecialchars($descText, ENT_QUOTES, 'UTF-8') : '';
        if($avail !=='public')
        {
            if ($avail == "private") $title .= " (private)";
            if ($avail == "none") $title .= " (<b>unpublished</b>)";
            if ($avail == "restricted") $title .= " (restricted)";
            if ($avail == "public") $title .= " (public)";
        }
        if (!empty($r['review_hold'])) {
            $title .= " (held for review)";
        }
            
        if($r['tags'])
        {
            $taglist = explode(",", $r['tags']);
            foreach ($taglist as $tag)
            {
                $tag = trim($tag);
                if ($tag === '') {
                    continue;
                }
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
        $advStyleAttr = "";
        $advStyleClass = "";
        if ($applyBrowseAdvTone)
        {
            $advStyleClass = " miniflag--advcol";
            $escColor = static function ($c, $fallbackHex) {
                $c = trim((string) $c);
                if ($c === '') {
                    $c = $fallbackHex;
                }
                return htmlspecialchars($c, ENT_QUOTES, 'UTF-8');
            };
            $bw = (int) ($r['borderwidth'] ?? 2);
            if ($bw < 0) {
                $bw = 0;
            }
            if ($bw > 8) {
                $bw = 8;
            }
            $pageBg = $escColor($bg, '#e8ecf1');
            $panelBg = $escColor($r['box'] ?? '', '#dce4ee');
            $ruleCol = $escColor($r['border'] ?? '', '#2874a6');
            $advStyleAttr = " style=\"--adv-page-bg: {$pageBg}; --adv-panel: {$panelBg}; --adv-rule: {$ruleCol}; --adv-rule-w: {$bw}px;\"";
        }
        $out = "<div class='miniflag{$advStyleClass}' data-viewid = \"$id\"{$advStyleAttr}>\n";
        $out .= "<div class='miniflag-media" . ($pic ? "" : " miniflag-media--empty") . "'>";
        if ($pic) {
            $out .= "<img src=\"" . htmlspecialchars($pic, ENT_QUOTES, 'UTF-8') . "\" alt=\"\" />";
        }
        $out .= "</div>\n<div class='miniflag-main'>\n";
        $out .= "<div class='miniflag-titlebar'>";
        $out .= "<div class='miniflag-title'>$title</div>";
        if($mine)
        {
            $out .= "<div class='miniflag-actions'><div class='deleteadvbutton' data-advid='$id' id='delete-$id'>X</div>\n            <div class='editadvbutton' data-advid='$id' id='edit-$id'>Edit</div></div>\n";
        }
        $out .= "</div>\n";
        $out .= "<hr class='miniflag-rule' />\n";
        $out .= "<div class='tinyrating'>$rating</div>\n";
        if ($descHtml !== '') {
            $out .= "<div class='miniflag-description'>$descHtml</div>\n";
        }
        if ($tags !== '') {
            $out .= "<div class='miniflag-tagsrow'><span class='miniflag-tags-label'>Tagged</span>: $tags</div>\n";
        }
        $out .= "</div>\n</div>\n";
        return $out;
    }
    $slideInfoClass = $pic ? 'slideinfo' : 'slideinfo slideinfo--no-icon';
    $out = "
    <div class='slidefolder' style = \"background-color: $bg; $bgpic\">

    <div class='$slideInfoClass' style='$pagestyle; $titlestyle'>";
    if($pic) $out .= "<img class='advflagicon' src=\"" . htmlspecialchars($pic, ENT_QUOTES, 'UTF-8') . "\" alt=\"\" />";
    $out .= "<div class='slideinfo-inner'>
    <div class='slideinfo-toprow'>
    <div class='slideinfo-titlewrap'><a class='link' onclick=\"location.href='#/view/$id'\">$title</a></div>
    <div class='advflagrating'>$rating</div>
    </div>
    <div class='slideinfo-meta'>
    <div class='slideinfo-byline-row'>
    <small class='slideinfo-byline'>by <a class='link' onclick=\"location.href='#/mystuff/$id'\">{$r['advuser']}</a></small>";
    if ($dateline !== '') {
        $out .= "<small class='slideinfo-date'>" . htmlspecialchars($dateline, ENT_QUOTES, 'UTF-8') . "</small>";
    }
    $out .= "
    </div>";
    if ($tags !== '') {
        $out .= "<div class='slideinfo-tags'>$tags</div>";
    }
    $out .= "
    </div>
    </div>
    </div>
    <div class='oneslide oneslide-teaser' style = '$pagestyle'>
    <div class='slidetitle' style='$textstyle'>
    <div class='expnotes-header'><span class='expnotes'>Experiment notes</span></div>
    <div class='expnotes-body'>$desc</div>
    </div>
    </div>
    </div>

    ";

    return $out;
}

function buildMiniFlag($id, $user, $applyBrowseAdvTone = false)
{
    return buildAdvFlag($id, $user, 1, $applyBrowseAdvTone);
}
?>