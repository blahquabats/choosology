<?php
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

function choosology_resource_save_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_resource_save_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_resource_save_json(array('ok' => 0, 'error' => 'Invalid JSON.'));
}

$id = isset($data['id']) ? (int) $data['id'] : 0;
if ($id < 1) {
	choosology_resource_save_json(array('ok' => 0, 'error' => 'Invalid picture id.'));
}

$name = isset($data['imagename']) ? trim((string) $data['imagename']) : '';
$cat = isset($data['cat']) ? trim((string) $data['cat']) : '';
if ($name === '') {
	choosology_resource_save_json(array('ok' => 0, 'error' => 'Name is required.'));
}
if (strlen($name) > 75) {
	$name = substr($name, 0, 75);
}
if (strlen($cat) > 75) {
	$cat = substr($cat, 0, 75);
}

$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$escName = mysqli_real_escape_string($db, $name);
$escCat = mysqli_real_escape_string($db, $cat);

$q = "UPDATE pics SET imagename = '$escName', cat = '$escCat' WHERE id = '$id' AND user = '$escUser' LIMIT 1";
if (!mysqli_query($db, $q) || mysqli_affected_rows($db) < 0) {
	choosology_resource_save_json(array('ok' => 0, 'error' => 'Could not save picture info.'));
}

choosology_resource_save_json(array('ok' => 1));
