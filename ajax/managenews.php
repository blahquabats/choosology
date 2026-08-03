<?php
/**
 * Edit or delete a news post or minor update. Temporarily limited to the site owner account.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

function choosology_news_manage_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user']) || (string) $_SESSION['user'] !== 'The Grasssmith') {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Not authorized.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Invalid JSON.'));
}

$action = isset($data['action']) ? trim((string) $data['action']) : '';
$id = isset($data['id']) ? (int) $data['id'] : 0;
$kind = isset($data['kind']) ? trim((string) $data['kind']) : 'news';
if ($kind !== 'update' && $kind !== 'news') {
	$kind = 'news';
}
if ($id < 1) {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Invalid item id.'));
}

/* ---------- Minor updates ---------- */
if ($kind === 'update') {
	$chk = @mysqli_query($db, "SHOW TABLES LIKE 'updates'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		choosology_news_manage_json(array('ok' => 0, 'error' => 'The updates table was not found.'));
	}
	$escId = mysqli_real_escape_string($db, (string) $id);
	$exists = mysqli_query($db, "SELECT id FROM updates WHERE id = '$escId' LIMIT 1");
	if (!$exists || mysqli_num_rows($exists) === 0) {
		choosology_news_manage_json(array('ok' => 0, 'error' => 'Update not found.'));
	}

	if ($action === 'delete') {
		if (!mysqli_query($db, "DELETE FROM updates WHERE id = '$escId' LIMIT 1")) {
			choosology_news_manage_json(array('ok' => 0, 'error' => 'Could not delete update.'));
		}
		choosology_news_manage_json(array('ok' => 1, 'kind' => 'update', 'id' => $id));
	}

	if ($action !== 'update') {
		choosology_news_manage_json(array('ok' => 0, 'error' => 'Unknown action.'));
	}

	$text = isset($data['headline']) ? trim((string) $data['headline']) : '';
	if ($text === '' && isset($data['text'])) {
		$text = trim((string) $data['text']);
	}
	if ($text === '') {
		choosology_news_manage_json(array('ok' => 0, 'error' => 'Update text is required.'));
	}
	if (strlen($text) > 225) {
		$text = substr($text, 0, 225);
	}
	$esc = mysqli_real_escape_string($db, $text);
	$sql = "UPDATE updates SET `text` = '$esc' WHERE id = '$escId' LIMIT 1";
	if (!mysqli_query($db, $sql)) {
		choosology_news_manage_json(array('ok' => 0, 'error' => 'Could not update item.'));
	}
	choosology_news_manage_json(array(
		'ok' => 1,
		'kind' => 'update',
		'id' => $id,
		'text' => $text,
	));
}

/* ---------- News posts ---------- */
$chk = @mysqli_query($db, "SHOW TABLES LIKE 'news'");
if (!$chk || mysqli_num_rows($chk) === 0) {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'The news table was not found.'));
}

$hasBody = false;
$hasText = false;
$hasBy = false;
$colsResult = mysqli_query($db, 'SHOW COLUMNS FROM news');
if ($colsResult) {
	while ($row = mysqli_fetch_assoc($colsResult)) {
		$field = isset($row['Field']) ? (string) $row['Field'] : '';
		if ($field === 'body') {
			$hasBody = true;
		}
		if ($field === 'text') {
			$hasText = true;
		}
		if ($field === 'by') {
			$hasBy = true;
		}
	}
}
if (!$hasBody && !$hasText) {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'The news table has no body/text column.'));
}

$escId = mysqli_real_escape_string($db, (string) $id);
$exists = mysqli_query($db, "SELECT id FROM news WHERE id = '$escId' LIMIT 1");
if (!$exists || mysqli_num_rows($exists) === 0) {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'News item not found.'));
}

if ($action === 'delete') {
	if (!mysqli_query($db, "DELETE FROM news WHERE id = '$escId' LIMIT 1")) {
		choosology_news_manage_json(array('ok' => 0, 'error' => 'Could not delete news item.'));
	}
	choosology_news_manage_json(array('ok' => 1, 'kind' => 'news', 'id' => $id));
}

if ($action !== 'update') {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Unknown action.'));
}

$headline = isset($data['headline']) ? trim((string) $data['headline']) : '';
$body = isset($data['body']) ? trim((string) $data['body']) : '';
$by = isset($data['by']) ? trim((string) $data['by']) : 'The Grasssmith';

if ($headline === '') {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Headline is required.'));
}
if ($body === '') {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Body is required.'));
}
if (strlen($headline) > 255) {
	$headline = substr($headline, 0, 255);
}
if (strlen($by) > 45) {
	$by = substr($by, 0, 45);
}
if ($by === '') {
	$by = 'The Grasssmith';
}

$sets = array(
	"headline = '" . mysqli_real_escape_string($db, $headline) . "'",
);
$bodySql = "'" . mysqli_real_escape_string($db, $body) . "'";
if ($hasBody) {
	$sets[] = "body = $bodySql";
}
if ($hasText) {
	$sets[] = "`text` = $bodySql";
}
if ($hasBy) {
	$sets[] = "`by` = '" . mysqli_real_escape_string($db, $by) . "'";
}

$sql = 'UPDATE news SET ' . implode(', ', $sets) . " WHERE id = '$escId' LIMIT 1";
if (!mysqli_query($db, $sql)) {
	choosology_news_manage_json(array('ok' => 0, 'error' => 'Could not update news item.'));
}

$plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
if (function_exists('mb_substr')) {
	$excerpt = mb_substr($plain, 0, 90, 'UTF-8');
} else {
	$excerpt = substr($plain, 0, 90);
}
if (strlen($plain) > strlen($excerpt)) {
	$excerpt .= '...';
}

choosology_news_manage_json(array(
	'ok' => 1,
	'kind' => 'news',
	'id' => $id,
	'headline' => $headline,
	'excerpt' => $excerpt,
));
