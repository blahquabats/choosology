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

$advidTouch = (int) preg_replace('/\D/', '', (string) ($_POST['advid'] ?? ''));
if ($advidTouch < 1) {
	exit;
}
$escUser = mysqli_real_escape_string($db, (string) $user);
$own = runquery_assoc("SELECT id FROM advs WHERE id = '$advidTouch' AND user = '$escUser' LIMIT 1");
if (!$own || empty($own[0]['id'])) {
	exit;
}

function addNew($name, $title)
{
    global $boxgroups, $user, $advidTouch;
    $vals = array("name"=>$title, "user"=>$user, "advused"=>$advidTouch);
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
}



$keys = array_keys($_POST);

rsort($keys);


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
mysqli_autocommit($db, FALSE);
foreach ($boxgroups as $k => $p)
{
    if(substr($k,0,3) != "box") 
    {
        continue;
    }
    else $id = substr($k,4);
    $id = preg_replace('/\D/', '', (string) $id);
    if ($id === '') {
        continue;
    }
    
    $q = "update advscreens set xpos = '{$p['x']}', ypos = '{$p['y']}'";
    if($p['deleted']) $q .= ", deleted = '{$p['deleted']}' ";
    for ($ck = 1; $ck <= 8; $ck++)
        {
           if($p['connections'][$ck]) $q .= ", choice$ck = \"{$p['connections'][$ck]}\"";
           else $q .= ", choice$ck= ''";
        }
    $q .= " where id = '$id' AND user = '$escUser' AND advused = '$advidTouch'";
    
    echo $q."
    
    ";
    $res[] = mysqli_query($db, $q);
}
mysqli_commit($db);
mysqli_autocommit($db, TRUE);
if (!empty($user) && function_exists('choosology_adv_touch_edited')) {
	choosology_adv_touch_edited($db, $advidTouch, (string) $user);
}
?>
