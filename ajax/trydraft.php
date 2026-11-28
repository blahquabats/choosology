<?php
/**
 * Session draft for Try it yourself. JSON in, JSON out. No advs / advscreens writes.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/try-draft.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw === false ? '' : $raw, true);
if (!is_array($data)) {
	echo json_encode(array('0', 'Invalid request.'), $jsonFlags);
	exit;
}

$op = (string) ($data['op'] ?? '');

if (choosology_try_visitor_is_logged_in()) {
	if ($op === 'load') {
		echo json_encode(array('0', 'Try it yourself is only for visitors who are not signed in.'), $jsonFlags);
	} else {
		echo json_encode(array(
			'ok' => 0,
			'logged_in' => 1,
			'account' => 0,
			'error' => 'Try it yourself is only for visitors who are not signed in.',
		), $jsonFlags);
	}
	exit;
}

if ($op === 'load') {
	$draft = choosology_try_draft_get(true);
	echo json_encode(array($draft['adv'], $draft['screens']), $jsonFlags);
	exit;
}

if ($op === 'sync') {
	$boxes = $data['boxes'] ?? array();
	if (!is_array($boxes)) {
		echo json_encode(array('ok' => 0, 'account' => 0, 'error' => 'Invalid draft.'), $jsonFlags);
		exit;
	}
	unset($boxes['advid']);
	$draft = choosology_try_draft_get(true);
	$result = choosology_try_apply_boxes($draft, $boxes);
	if (!empty($result['ok'])) {
		choosology_try_draft_store($result['draft']);
	}
	echo json_encode(array(
		'ok' => (int) $result['ok'],
		'account' => (int) $result['account'],
		'error' => (string) $result['error'],
	), $jsonFlags);
	exit;
}

if ($op === 'savetext') {
	$id = (string) ($data['id'] ?? '');
	$draft = choosology_try_draft_get(true);
	$labels = array();
	if (isset($data['choices']) && is_array($data['choices'])) {
		$labels = $data['choices'];
	}
	$result = choosology_try_save_screen(
		$draft,
		$id,
		(string) ($data['name'] ?? ''),
		(string) ($data['text'] ?? ''),
		$labels
	);
	if (!empty($result['ok'])) {
		choosology_try_draft_store($result['draft']);
	}
	echo json_encode(array(
		'ok' => (int) $result['ok'],
		'account' => 0,
		'error' => (string) $result['error'],
	), $jsonFlags);
	exit;
}

echo json_encode(array('0', 'Unknown request.'), $jsonFlags);
