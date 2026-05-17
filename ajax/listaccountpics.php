<?php
/**
 * JSON list of images available for the current user's profile picture.
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

if (empty($_SESSION['user'])) {
	echo json_encode(array('ok' => 0, 'error' => 'Not signed in.'), $jsonFlags);
	exit;
}

function choosology_account_pic_tags(string $cat): array
{
	$cat = trim($cat);
	if ($cat === '') {
		return array();
	}
	$parts = preg_split('/\s*,\s*/', $cat, -1, PREG_SPLIT_NO_EMPTY);
	if (!is_array($parts) || count($parts) === 0) {
		return array($cat);
	}
	return array_values(array_unique(array_map('trim', $parts)));
}

$pics = getUserPics((string) $_SESSION['user']);
$tagMap = array();
$items = array();
foreach ($pics as $pic) {
	$pid = (int) ($pic['id'] ?? 0);
	if ($pid < 1) {
		continue;
	}
	$cat = trim((string) ($pic['cat'] ?? ''));
	$tags = choosology_account_pic_tags($cat);
	foreach ($tags as $tag) {
		if ($tag !== '') {
			$tagMap[$tag] = true;
		}
	}
	$items[] = array(
		'id' => $pid,
		'title' => (string) ($pic['imagename'] ?? $pic['filename'] ?? ('#' . $pid)),
		'filename' => (string) ($pic['filename'] ?? ''),
		'cat' => $cat,
		'tags' => $tags,
		'thumbUrl' => choosology_site_url('ajax/pic.php?id=' . $pid . '&thumb=1'),
		'imageUrl' => choosology_site_url('ajax/pic.php?id=' . $pid),
	);
}

$tagOptions = array_keys($tagMap);
natcasesort($tagOptions);
$tagOptions = array_values($tagOptions);

echo json_encode(array(
	'ok' => 1,
	'items' => $items,
	'tagOptions' => $tagOptions,
), $jsonFlags);
exit;
