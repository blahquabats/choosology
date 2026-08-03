<?php
/**
 * Add a news post or minor update. Temporarily limited to the site owner account.
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

function choosology_news_add_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user']) || (string) $_SESSION['user'] !== 'The Grasssmith') {
	choosology_news_add_json(array('ok' => 0, 'error' => 'Not authorized.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_news_add_json(array('ok' => 0, 'error' => 'Invalid JSON.'));
}

$kind = isset($data['kind']) ? trim((string) $data['kind']) : 'news';
if ($kind !== 'update' && $kind !== 'news') {
	$kind = 'news';
}

$headline = isset($data['headline']) ? trim((string) $data['headline']) : '';
if ($headline === '') {
	choosology_news_add_json(array(
		'ok' => 0,
		'error' => $kind === 'update' ? 'Update text is required.' : 'Headline is required.',
	));
}

/* ---------- Minor update ---------- */
if ($kind === 'update') {
	if (strlen($headline) > 225) {
		$headline = substr($headline, 0, 225);
	}
	$chk = @mysqli_query($db, "SHOW TABLES LIKE 'updates'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		choosology_news_add_json(array('ok' => 0, 'error' => 'The updates table was not found.'));
	}
	$esc = mysqli_real_escape_string($db, $headline);
	$sql = "INSERT INTO updates (`text`, whenposted) VALUES ('$esc', NOW())";
	if (!mysqli_query($db, $sql)) {
		choosology_news_add_json(array('ok' => 0, 'error' => 'Could not add update.'));
	}
	$id = (int) mysqli_insert_id($db);
	choosology_news_add_json(array(
		'ok' => 1,
		'kind' => 'update',
		'id' => $id,
		'text' => $headline,
		'date' => date('M j, Y'),
	));
}

/* ---------- News post ---------- */
$body = isset($data['body']) ? trim((string) $data['body']) : '';
$by = isset($data['by']) ? trim((string) $data['by']) : 'The Grasssmith';

if ($body === '') {
	choosology_news_add_json(array('ok' => 0, 'error' => 'Body is required.'));
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

$chk = @mysqli_query($db, "SHOW TABLES LIKE 'news'");
if (!$chk || mysqli_num_rows($chk) === 0) {
	choosology_news_add_json(array('ok' => 0, 'error' => 'The news table was not found.'));
}

$hasBody = false;
$hasText = false;
$hasBy = false;
$hasWhenposted = false;
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
		if ($field === 'whenposted') {
			$hasWhenposted = true;
		}
	}
}

if (!$hasBody && !$hasText) {
	choosology_news_add_json(array('ok' => 0, 'error' => 'The news table has no body/text column.'));
}

$columns = array('headline');
$values = array("'" . mysqli_real_escape_string($db, $headline) . "'");

$bodyValue = "'" . mysqli_real_escape_string($db, $body) . "'";
if ($hasBody) {
	$columns[] = 'body';
	$values[] = $bodyValue;
}
if ($hasText) {
	$columns[] = '`text`';
	$values[] = $bodyValue;
}

if ($hasBy) {
	$columns[] = '`by`';
	$values[] = "'" . mysqli_real_escape_string($db, $by) . "'";
}
if ($hasWhenposted) {
	$columns[] = 'whenposted';
	$values[] = 'NOW()';
}

$sql = 'INSERT INTO news (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
if (!mysqli_query($db, $sql)) {
	choosology_news_add_json(array('ok' => 0, 'error' => 'Could not add news item.'));
}

$id = (int) mysqli_insert_id($db);
$plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
$excerpt = $plain;
if (function_exists('mb_substr')) {
	$excerpt = mb_substr($plain, 0, 90, 'UTF-8');
} else {
	$excerpt = substr($plain, 0, 90);
}
if (strlen($plain) > strlen($excerpt)) {
	$excerpt .= '...';
}

choosology_news_add_json(array(
	'ok' => 1,
	'kind' => 'news',
	'id' => $id,
	'headline' => $headline,
	'excerpt' => $excerpt,
	'date' => date('M j, Y'),
));
