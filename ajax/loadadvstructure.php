<?php
require_once("../connect.php");
require_once("../auxfuncs.php");
//require_once("../authent.php");

header('Content-Type: application/json; charset=utf-8');

$advid = null;
if (!empty($_GET['advid'])) {
	$advid = $_GET['advid'];
} elseif (!empty($_POST['advid'])) {
	$advid = $_POST['advid'];
}

if ($advid === null || $advid === '') {
	echo json_encode(array('0', 'Missing experiment id.'));
	exit;
}

if (!is_numeric($advid)) {
	echo json_encode(array('0', 'Invalid experiment id.'));
	exit;
}

//if (!$alluserinfo) die(json_encode(array("0","Not logged in.")));

$infos = getAdv($advid);
if ($infos === false) {
	echo json_encode(array('0', 'Experiment not found.'));
	exit;
}

$adv = $infos[0];
$screens = $infos[1];
//echoPre($alluserinfo);
//if ($adv['user'] != $alluserinfo['name']) die(json_encode(array("0",$alluserinfo['name'].", this is not your experiment!")));
echo json_encode(array($adv, $screens));
