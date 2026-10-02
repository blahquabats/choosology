<?php
/**
 * JSON list of the current user's Choosology library images (+ universal) for pickers (e.g. TinyMCE, adv settings).
 *
 * Query params:
 *   advid (required) — experiment id (must be owned by session user).
 *   q (optional) — case-insensitive substring match on title, filename, or category string.
 *   tag (optional) — filter to images whose category field contains this tag token (comma-separated cat is split).
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

$user = (string) $_SESSION['user'];
$advid = isset($_GET['advid']) ? trim((string) $_GET['advid']) : '';

if ($advid === '' || !ctype_digit($advid)) {
	echo json_encode(array('ok' => 0, 'error' => 'Missing or invalid advid.'), $jsonFlags);
	exit;
}

$advidInt = (int) $advid;
$escUser = mysqli_real_escape_string($db, $user);
$own = runquery_assoc("SELECT id FROM advs WHERE id = '$advidInt' AND user = '$escUser' LIMIT 1");
if (!is_array($own) || count($own) === 0) {
	echo json_encode(array('ok' => 0, 'error' => 'Experiment not found or not yours.'), $jsonFlags);
	exit;
}

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$qNorm = $q === '' ? '' : strtolower($q);
$tagPick = isset($_GET['tag']) ? trim((string) $_GET['tag']) : '';

require_once __DIR__ . '/../lib/pic-list-helpers.php';

$pics = getUserPics($user);

$tagOptionsMap = array();
foreach ($pics as $pic) {
	foreach (choosology_listuserpics_tags_from_cat((string) ($pic['cat'] ?? '')) as $t) {
		if ($t !== '') {
			$tagOptionsMap[$t] = true;
		}
	}
}
$tagList = array_keys($tagOptionsMap);
natcasesort($tagList);
$tagList = array_values($tagList);

$items = array();
foreach ($pics as $pic) {
	$pid = (int) ($pic['id'] ?? 0);
	if ($pid < 1) {
		continue;
	}
	if (!choosology_listuserpics_row_matches_tag($pic, $tagPick)) {
		continue;
	}
	if (!choosology_listuserpics_row_matches_q($pic, $qNorm)) {
		continue;
	}
	$title = (string) ($pic['imagename'] ?? $pic['filename'] ?? ('#' . $pid));
	$cat = trim((string) ($pic['cat'] ?? ''));
	$tags = choosology_listuserpics_tags_from_cat($cat);
	$width = 0;
	$height = 0;
	$path = choosology_pic_filesystem_path($pic, true);
	if ($path !== '' && is_file($path)) {
		$sz = @getimagesize($path);
		if (is_array($sz) && isset($sz[0], $sz[1])) {
			$width = (int) $sz[0];
			$height = (int) $sz[1];
		}
	}
	$items[] = array(
		'id' => $pid,
		'title' => $title,
		'filename' => (string) ($pic['filename'] ?? ''),
		'cat' => $cat,
		'tags' => $tags,
		'width' => $width,
		'height' => $height,
		'thumbUrl' => choosology_site_url('ajax/pic.php?id=' . $pid . '&thumb=1'),
		'imageUrl' => choosology_site_url('ajax/pic.php?id=' . $pid),
	);
}

echo json_encode(array(
	'ok' => 1,
	'items' => $items,
	'tagOptions' => $tagList,
), $jsonFlags);
exit;
