<?php
/**
 * Returns experiment + screens as JSON. Must never emit HTML/notices before the payload.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

$advid = null;
if (!empty($_GET['advid'])) {
	$advid = $_GET['advid'];
} elseif (!empty($_POST['advid'])) {
	$advid = $_POST['advid'];
}

if ($advid === null || $advid === '') {
	echo json_encode(array('0', 'Missing experiment id.'), $jsonFlags);
	exit;
}

if (!is_numeric($advid)) {
	echo json_encode(array('0', 'Invalid experiment id.'), $jsonFlags);
	exit;
}

$infos = getAdv($advid);
if ($infos === false) {
	echo json_encode(array('0', 'Experiment not found.'), $jsonFlags);
	exit;
}

$adv = $infos[0];
$screens = $infos[1];

$payload = json_encode(array($adv, $screens), $jsonFlags);
if ($payload === false) {
	echo json_encode(array('0', 'Could not encode experiment data: ' . json_last_error_msg()), $jsonFlags);
	exit;
}

echo $payload;
