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

if (empty($_SESSION['user'])) {
	echo json_encode(array('ok' => 0, 'error' => 'Not signed in.'), $jsonFlags);
	exit;
}

$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$rows = runquery_assoc("SELECT id, filename, imagename, cat, size, uploaded, type FROM pics WHERE user = '$escUser' ORDER BY uploaded DESC, id DESC");
if (!is_array($rows)) {
	$rows = array();
}

$items = array();
foreach ($rows as $row) {
	$id = (int) ($row['id'] ?? 0);
	if ($id < 1) {
		continue;
	}
	$uploadedRaw = (string) ($row['uploaded'] ?? '');
	$uploaded = $uploadedRaw;
	$uploadedDate = '';
	$ts = strtotime($uploadedRaw);
	if ($ts > 0) {
		$uploaded = date('M j, Y g:ia', $ts);
		$uploadedDate = date('M j, Y', $ts);
	}
	$items[] = array(
		'id' => $id,
		'filename' => (string) ($row['filename'] ?? ''),
		'imagename' => (string) ($row['imagename'] ?? ''),
		'cat' => (string) ($row['cat'] ?? ''),
		'size' => (string) ($row['size'] ?? ''),
		'uploaded' => $uploaded,
		'uploadedDate' => $uploadedDate,
		'type' => (string) ($row['type'] ?? ''),
		'thumbUrl' => choosology_site_url('ajax/pic.php?id=' . $id . '&thumb=1'),
		'imageUrl' => choosology_site_url('ajax/pic.php?id=' . $id),
	);
}

echo json_encode(array('ok' => 1, 'items' => $items), $jsonFlags);
exit;
