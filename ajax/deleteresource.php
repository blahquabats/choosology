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

function choosology_resource_delete_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_resource_delete_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_resource_delete_json(array('ok' => 0, 'error' => 'Invalid JSON.'));
}

$id = isset($data['id']) ? (int) $data['id'] : 0;
if ($id < 1) {
	choosology_resource_delete_json(array('ok' => 0, 'error' => 'Invalid picture id.'));
}

$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$rows = runquery_assoc("SELECT * FROM pics WHERE id = '$id' AND user = '$escUser' LIMIT 1");
if (!is_array($rows) || !isset($rows[0])) {
	choosology_resource_delete_json(array('ok' => 0, 'error' => 'Picture not found or not yours.'));
}
$row = $rows[0];

$full = choosology_pic_filesystem_path($row, false);
$thumb = choosology_pic_filesystem_path($row, true);

if (!mysqli_query($db, "DELETE FROM pics WHERE id = '$id' AND user = '$escUser' LIMIT 1")) {
	choosology_resource_delete_json(array('ok' => 0, 'error' => 'Could not delete picture.'));
}

foreach (array($full, $thumb) as $path) {
	if (is_string($path) && $path !== '' && is_file($path)) {
		@unlink($path);
	}
}
mysqli_query($db, "UPDATE users SET pic = 0 WHERE pic = '$id' AND name = '$escUser'");
mysqli_query($db, "UPDATE advs SET pic = NULL WHERE pic = '$id' AND user = '$escUser'");
mysqli_query($db, "UPDATE advs SET bgpic = 0 WHERE bgpic = '$id' AND user = '$escUser'");

choosology_resource_delete_json(array('ok' => 1));
