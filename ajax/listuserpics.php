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

/**
 * @return list<string>
 */
function choosology_listuserpics_tags_from_cat(string $cat): array
{
	$cat = trim($cat);
	if ($cat === '') {
		return array();
	}
	if (strpos($cat, ',') !== false) {
		$parts = preg_split('/\s*,\s*/', $cat, -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($parts)) {
			return array();
		}
		$out = array();
		foreach ($parts as $p) {
			$t = trim((string) $p);
			if ($t !== '') {
				$out[] = $t;
			}
		}
		return array_values(array_unique($out));
	}
	return array($cat);
}

function choosology_listuserpics_row_matches_q(array $pic, string $qNorm): bool
{
	if ($qNorm === '') {
		return true;
	}
	$blob = strtolower(
		(string) ($pic['imagename'] ?? '') . "\n" .
		(string) ($pic['filename'] ?? '') . "\n" .
		(string) ($pic['cat'] ?? '')
	);
	return strpos($blob, $qNorm) !== false;
}

function choosology_listuserpics_row_matches_tag(array $pic, string $tagPick): bool
{
	if ($tagPick === '') {
		return true;
	}
	$want = strtolower($tagPick);
	$rowTags = choosology_listuserpics_tags_from_cat((string) ($pic['cat'] ?? ''));
	foreach ($rowTags as $t) {
		if (strtolower($t) === $want) {
			return true;
		}
	}
	return false;
}

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
	$items[] = array(
		'id' => $pid,
		'title' => $title,
		'filename' => (string) ($pic['filename'] ?? ''),
		'cat' => $cat,
		'tags' => $tags,
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
