<?php
require_once("connect.php");
require_once("auxfuncs.php");
if (isset($_POST['searchval']))
{
    $val = $_POST['searchval'];
    $limit = $_POST['limit'];
    $searchq = "select  * from advs where title like \"%$val%\" and avail='public' limit 11";
    $val = htmlspecialchars($val);
    $pres=runquery_assoc($searchq);
    $c = 0;
    if($pres)
    {
    foreach ($pres as $p)
    {
        if ($c >= 10)
        {
            echo "<div class='ajaxsearchresult' style='font-style:italic' onclick='triggerSearch(\"a.title;;$val\", $limit, 1, 1)'>See more...</div>";
        }

        else echo "<div class='ajaxsearchresult' onclick='triggerSearch(\"a.title;;".html_entity_decode(strip_tags($p['title']))."\", $limit, 1, 1)'>".html_entity_decode($p['title'])."</div>";
        $c++;
    }
    }
    if($c == 0) echo "<div class='ajaxsearchresult'>No adventures found!</div>";
}
if(isset($_POST['which']))
{
    $which = $_POST['which'];
    //$title = $_POST['title'];
    //$orderby = $_POST['orderby'];
    switch ($which)
    {
        case "tr":
            $title = "Top Rated";
            $where = 'rating!=\"NA\" and rating !=\"\"';
            $orderby = "rating desc";
            break;
        case "fa":
            $title = "From The Archives";
            $where = 'rating!=\"NA\" and rating !=\"\"';
            $orderby = "rand()";
            break;
        case "re":
            $title = "Recently Edited";
            $where = "";
            $orderby = "a.edited desc";
            break;
        case "as":
        	$title = "Advanced Search";
        	$where = "a.title LIKE '%".$_POST['params']['title']."%'";
        	$orderby = "a.edited desc";
        	break;
        case "rp":
        default:
            if(strpos($which, ";;"))
            {
                $title = "Search by title";
                $pars = explode(";;",$which);
                $where = $pars[0].' LIKE \"%'.$pars[1].'%\"';
                $orderby = "a.edited desc";
            }
            else 
            {
                $title = "Recently Published";
                $where = "";
                $orderby = "COALESCE(a.published, a.created) desc";
            }
            break;
    }
    $limit = $_POST['limit'];
    $allthree = $_POST['allthree'];
    $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
    if($which == "gs") // special case for general survey
    {
        $out = buildColumn("rp", "Recently Published", "", "COALESCE(a.published, a.created) desc", $limit, "more")."!@!@!";
        $out .= buildColumn("tr", "Top Rated", 'rating!=\"NA\" and rating !=\"\"', "rating desc", $limit, "more")."!@!@!";
        $out .= buildColumn("fa", "From The Archives", 'rating=\"NA\"', "rand()", $limit,"more");
        echo $out;
        exit;
    }
    	if(!empty($_POST['page']))
    	{
    		$p = max(1, (int) $_POST['page']) - 1;
    		$begin = $limit*3*$p;
    		$first = $begin.",".$limit;
    		$second = $begin+$limit.",".$limit;
    		$third = $begin+($limit*2).",".$limit;
    	}
    	else
    	{
    		$first = $limit;
    		$second = $limit.",".$limit;
    		$third = ($limit*2).",".$limit;
    	}
    if ($allthree)
    {
        echo buildBrowseUnifiedFourPack($which, $title, $where, $orderby, $first, $second, $third, $page);
        exit;
    }
    echo buildColumn($which, $title, $where, $orderby, $first, "count", $page) . "!@!@!";

}

?>

