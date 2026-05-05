<?php
require_once("../connect.php");
require_once("../auxfuncs.php");
//require_once("../authent.php");

if($_GET['advid']) $advid = $_GET['advid'];
else $advid = $_POST['advid'];

//if (!$alluserinfo) die(json_encode(array("0","Not logged in.")));

$infos = getAdv($advid);
$adv = $infos[0];
$screens = $infos[1];
//echoPre($alluserinfo);
//if ($adv['user'] != $alluserinfo['name']) die(json_encode(array("0",$alluserinfo['name'].", this is not your experiment!")));
echo json_encode(array($adv, $screens));

?>