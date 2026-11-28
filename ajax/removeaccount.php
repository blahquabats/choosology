<?php
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/account-removal.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

if (empty($_SESSION['user'])) {
	echo json_encode(array('ok' => 0, 'error' => 'Not signed in.'), $jsonFlags);
	exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw === false ? '' : $raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
$password = (string) ($data['password'] ?? '');
$confirmed = !empty($data['confirm']);
if (!$confirmed) {
	echo json_encode(array('ok' => 0, 'error' => 'Confirm that this cannot be undone.'), $jsonFlags);
	exit;
}

$result = choosology_account_remove($db, (string) $_SESSION['user'], $password);
if (!empty($result['ok'])) {
	session_destroy();
}
echo json_encode($result, $jsonFlags);
exit;
