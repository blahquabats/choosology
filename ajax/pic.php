<?php
/**
 * Serve a library image by pics.id (public URL for embedded experiment HTML).
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
ob_end_clean();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$thumb = !empty($_GET['thumb']) && ($_GET['thumb'] === '1' || $_GET['thumb'] === 'true');

if ($id < 1) {
	http_response_code(400);
	header('Content-Type: text/plain; charset=utf-8');
	header('X-Content-Type-Options: nosniff');
	echo 'Bad id';
	exit;
}

global $db;
$res = mysqli_query($db, 'SELECT * FROM pics WHERE id = ' . $id . ' LIMIT 1');
if (!$res || mysqli_num_rows($res) === 0) {
	http_response_code(404);
	header('X-Content-Type-Options: nosniff');
	exit;
}
$row = mysqli_fetch_assoc($res);
if (!$row) {
	http_response_code(404);
	header('X-Content-Type-Options: nosniff');
	exit;
}

$path = choosology_pic_filesystem_path($row, $thumb);
if ($path === null || !is_readable($path)) {
	http_response_code(404);
	header('X-Content-Type-Options: nosniff');
	exit;
}

$mime = 'application/octet-stream';
if (function_exists('mime_content_type')) {
	$m = @mime_content_type($path);
	if (is_string($m) && $m !== '') {
		$mime = $m;
	}
} elseif (class_exists('finfo')) {
	$fi = new finfo(FILEINFO_MIME_TYPE);
	$m = @$fi->file($path);
	if (is_string($m) && $m !== '') {
		$mime = $m;
	}
}

if (!preg_match('#^image/#i', $mime)) {
	$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
	$map = array(
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
		'webp' => 'image/webp',
		'svg' => 'image/svg+xml',
		'avif' => 'image/avif',
		'bmp' => 'image/bmp',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
	);
	if (isset($map[$ext])) {
		$mime = $map[$ext];
	}
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
