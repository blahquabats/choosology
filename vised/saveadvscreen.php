<?php
include ('../connect.php');
include ('../auxfuncs.php');
if (!isset($_SESSION['user']))
{
	session_start();
}
if (isset($_SESSION['user']))
{

	$user = $_SESSION['user'];
}
else
{
	exit;
}
/*
function addNew($name, $title)
{
    global $boxgroups, $user;
    $vals = array("name"=>$title, "user"=>$user, "advused"=>":".addslashes($_POST['advid']).":");
    $newid = insert("advscreens", $vals);
    $newname = $newid;
    foreach($boxgroups as $k=>&$v) // go through everything and update the id
    {
        if(!count($v['connections'])) continue;
        foreach ($v['connections'] as $kk => &$c)
        {
            if(strpos($c, substr($name, 4))) 
            {
                $c = str_replace(substr($name, 4), $newname, $c);
                break;
            }
        }
    }
    return $newid;
}*/

$sid = isset($_POST['sid']) ? preg_replace('/\D/', '', (string) $_POST['sid']) : '';
if ($sid === '') {
	exit;
}
$content = choosology_sanitize_screen_html_images((string) ($_POST['content'] ?? ''));
$content = mysqli_real_escape_string($db, $content);
$screenlabel = trim(preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, str_replace("\\n", "", (string) ($_POST['screenlabel'] ?? ''))));
$screenlabel = mysqli_real_escape_string($db, $screenlabel);

$screeninfo = runquery_assoc("select * from advscreens where id = '$sid'");
if(!$screeninfo || $screeninfo[0]['id'] != $sid)
{
    die("Error: screen not found");
}
$screeninfo = $screeninfo[0];

$q = "update advscreens set text = \"$content\", name = \"$screenlabel\" ";
for ($c = 1; $c <= 8; $c++)
{
	if (empty($_POST['choice'.$c])) {
		continue;
	}
	$choicetext = trim(preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, str_replace("\\n", "", (string) $_POST["choice$c"])));
	$choicetext = choosology_sanitize_screen_html_images($choicetext);
	$cpid = mysqli_real_escape_string($db, (string) ($_POST['choice'.$c.'id'] ?? ''));
	$choice = mysqli_real_escape_string($db, html_entity_decode($choicetext . '|Q-D-|' . $cpid));
	$q .= ", choice$c = \"$choice\"";
}
$q .= " where id = '$sid' and user = '$user'";
if(runquery($q))
{
    echo $q;    
}
else echo "Error: problem updating database";

//echo $screeninfo['advused'];
/*
$keys = array_keys($_POST);

rsort($keys);

mysqli_autocommit($db, FALSE);
*/
/*

foreach($keys as $k2)
{
  //  echo $k;
//    echoPre($_POST[$k]);
    $boxgroups[$k2] = $_POST[$k2];
}

$res = array();

foreach ($boxgroups as $k => $p)
{
    if(substr($k,0,3) != "box") 
    {
        if(substr($k,0,3) != "new") continue;
        $id = addNew($k, $p['title']);
        $boxgroups["box_".$id] = $p;
        unset($boxgroups[$k]);
        
    }
}
foreach ($boxgroups as $k => $p)
{
    if(substr($k,0,3) != "box") 
    {
        continue;
    }
    else $id = substr($k,4);
    
    $q = "update advscreens set xpos = '{$p['x']}', ypos = '{$p['y']}'";
    if($p['deleted']) $q .= ", deleted = '{$p['deleted']}' ";
    for ($ck = 1; $ck <= 8; $ck++)
        {
           if($p['connections'][$ck]) $q .= ", choice$ck = \"{$p['connections'][$ck]}\"";
           else $q .= ", choice$ck= ''";
        }
    $q .= " where id = '$id'";
    
    echo $q."
    
    ";
    $res[] = mysqli_query($db, $q);
}
mysqli_commit($db);
mysqli_autocommit($db, TRUE);
*/
?>