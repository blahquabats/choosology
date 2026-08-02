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

function choosology_resource_upload_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

function choosology_resource_format_size(int $bytes): string
{
	if ($bytes >= 1048576) {
		return round($bytes / 1048576, 1) . ' MB';
	}
	if ($bytes >= 1024) {
		return round($bytes / 1024, 1) . ' KB';
	}
	return $bytes . ' B';
}

function choosology_resource_ext_for_mime(string $mime): string
{
	$map = array(
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/webp' => 'webp',
	);
	return $map[$mime] ?? '';
}

if (empty($_SESSION['user'])) {
	choosology_resource_upload_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

if (!choosology_gd_available()) {
	choosology_resource_upload_json(array(
		'ok' => 0,
		'error' => 'Image uploads require the PHP GD extension (install php-gd / php8.3-gd). Without it, thumbnails become full-size and break the UI.',
	));
}

$user = (string) $_SESSION['user'];
$dir = playerDir($user);
$thumbDir = $dir . DIRECTORY_SEPARATOR . 'thumbs';
if (!is_dir($thumbDir) && !mkdir($thumbDir, 0775, true)) {
	choosology_resource_upload_json(array('ok' => 0, 'error' => 'Could not create thumbnail directory.'));
}

$files = array();
if (!empty($_FILES['images']) && is_array($_FILES['images']) && is_array($_FILES['images']['name'])) {
	$count = count($_FILES['images']['name']);
	for ($i = 0; $i < $count; $i++) {
		$files[] = array(
			'name' => $_FILES['images']['name'][$i] ?? '',
			'type' => $_FILES['images']['type'][$i] ?? '',
			'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
			'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
			'size' => $_FILES['images']['size'][$i] ?? 0,
		);
	}
} elseif (!empty($_FILES['image']) && is_array($_FILES['image'])) {
	$files[] = $_FILES['image'];
}

if (count($files) === 0) {
	choosology_resource_upload_json(array('ok' => 0, 'error' => 'No image uploaded.'));
}

$names = isset($_POST['imagename']) && is_array($_POST['imagename']) ? $_POST['imagename'] : array($_POST['imagename'] ?? '');
$cats = isset($_POST['cat']) && is_array($_POST['cat']) ? $_POST['cat'] : array($_POST['cat'] ?? '');
$ids = array();
$escUser = mysqli_real_escape_string($db, $user);

foreach ($files as $idx => $file) {
	if (!empty($file['error'])) {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Upload failed for one of the images.'));
	}
	$size = (int) ($file['size'] ?? 0);
	if ($size < 1) {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'One uploaded file is empty.'));
	}
	if ($size > 1024 * 1024) {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Each image must be 1 MB or smaller.'));
	}
	$tmp = (string) ($file['tmp_name'] ?? '');
	if ($tmp === '' || !is_uploaded_file($tmp)) {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Upload was not accepted.'));
	}

	$mime = '';
	if (function_exists('finfo_open')) {
		$fi = finfo_open(FILEINFO_MIME_TYPE);
		if ($fi) {
			$mime = (string) finfo_file($fi, $tmp);
		}
	}
	if ($mime === '' && function_exists('mime_content_type')) {
		$mime = (string) @mime_content_type($tmp);
	}
	$ext = choosology_resource_ext_for_mime($mime);
	if ($ext === '') {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Only JPG, PNG, GIF, and WebP images are supported.'));
	}
	if (!@getimagesize($tmp)) {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Uploaded file is not a valid image.'));
	}

	$filename = substr('pic_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $ext, 0, 75);
	$dest = $dir . DIRECTORY_SEPARATOR . $filename;
	$thumbDest = $thumbDir . DIRECTORY_SEPARATOR . $filename;
	if (!move_uploaded_file($tmp, $dest)) {
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Could not save uploaded image.'));
	}
	if (!choosology_make_image_thumb($dest, $thumbDest, $mime)) {
		@unlink($dest);
		@unlink($thumbDest);
		choosology_resource_upload_json(array(
			'ok' => 0,
			'error' => 'Could not create image thumbnail. Ensure PHP GD supports this image type.',
		));
	}

	$name = isset($names[$idx]) ? trim((string) $names[$idx]) : '';
	if ($name === '') {
		$name = pathinfo((string) ($file['name'] ?? $filename), PATHINFO_FILENAME);
	}
	$cat = isset($cats[$idx]) ? trim((string) $cats[$idx]) : '';
	if (strlen($name) > 75) {
		$name = substr($name, 0, 75);
	}
	if (strlen($cat) > 75) {
		$cat = substr($cat, 0, 75);
	}

	$escFilename = mysqli_real_escape_string($db, $filename);
	$escName = mysqli_real_escape_string($db, $name);
	$escCat = mysqli_real_escape_string($db, $cat);
	$escSize = mysqli_real_escape_string($db, choosology_resource_format_size($size));
	$escType = mysqli_real_escape_string($db, $mime);
	$q = "INSERT INTO pics (filename, imagename, cat, size, uploaded, user, type)
		VALUES ('$escFilename', '$escName', '$escCat', '$escSize', NOW(), '$escUser', '$escType')";
	if (!mysqli_query($db, $q)) {
		@unlink($dest);
		@unlink($thumbDest);
		choosology_resource_upload_json(array('ok' => 0, 'error' => 'Could not save image record.'));
	}
	$ids[] = (int) mysqli_insert_id($db);
}

choosology_resource_upload_json(array('ok' => 1, 'id' => $ids[0] ?? 0, 'ids' => $ids));
