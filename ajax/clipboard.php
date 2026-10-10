<?php
/**
 * Clipboard JSON API (pad, todos, checklist, results snapshot).
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/clipboard-helpers.php';
require_once __DIR__ . '/../lib/datascrip-helpers.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

function choosology_clipboard_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_clipboard_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

$user = (string) $_SESSION['user'];
choosology_clipboard_ensure_schema($db);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
if (!is_array($data)) {
	$data = array();
}
$action = (string) ($data['action'] ?? $_GET['action'] ?? 'summary');

$sessionFlags = array(
	'visited_clipboard' => !empty($_SESSION['clipboard_visited']),
	'visited_results' => !empty($_SESSION['clipboard_results_visited']),
);

if ($action === 'visit_clipboard') {
	$_SESSION['clipboard_visited'] = 1;
	$sessionFlags['visited_clipboard'] = true;
	choosology_clipboard_sync_checklist($db, $user, $sessionFlags);
	choosology_award_achievement($db, $user, 'clipboard_clerk');
	choosology_clipboard_set_checklist_flag($db, $user, 'open_clipboard', 'completed', 1);
	choosology_clipboard_json(array('ok' => 1));
}

if ($action === 'visit_results') {
	$_SESSION['clipboard_results_visited'] = 1;
	$sessionFlags['visited_results'] = true;
	choosology_clipboard_sync_checklist($db, $user, $sessionFlags);
	choosology_award_achievement($db, $user, 'results_analyst');
	choosology_clipboard_set_checklist_flag($db, $user, 'review_results', 'completed', 1);
	choosology_clipboard_json(array('ok' => 1, 'metrics' => choosology_clipboard_experiment_metrics($db, $user)));
}

if ($action === 'save_pad') {
	$body = (string) ($data['body'] ?? '');
	$ok = choosology_clipboard_save_pad($db, $user, $body);
	choosology_clipboard_sync_checklist($db, $user, $sessionFlags);
	choosology_clipboard_json(array('ok' => $ok ? 1 : 0, 'pad' => choosology_clipboard_get_pad($db, $user)));
}

if ($action === 'add_todo') {
	$id = choosology_clipboard_add_todo($db, $user, (string) ($data['body'] ?? ''));
	choosology_clipboard_sync_checklist($db, $user, $sessionFlags);
	choosology_clipboard_json(array(
		'ok' => $id > 0 ? 1 : 0,
		'id' => $id,
		'todos' => choosology_clipboard_list_todos($db, $user),
		'error' => $id > 0 ? null : 'Enter a to-do item.',
	));
}

if ($action === 'toggle_todo') {
	$id = (int) ($data['id'] ?? 0);
	$done = !empty($data['done']);
	$ok = choosology_clipboard_set_todo_done($db, $user, $id, $done);
	choosology_clipboard_json(array(
		'ok' => $ok ? 1 : 0,
		'todos' => choosology_clipboard_list_todos($db, $user),
	));
}

if ($action === 'delete_todo') {
	$id = (int) ($data['id'] ?? 0);
	$ok = choosology_clipboard_delete_todo($db, $user, $id);
	choosology_clipboard_json(array(
		'ok' => $ok ? 1 : 0,
		'todos' => choosology_clipboard_list_todos($db, $user),
	));
}

if ($action === 'dismiss_checklist') {
	$key = trim((string) ($data['item_key'] ?? ''));
	$valid = false;
	foreach (choosology_clipboard_checklist_defs() as $def) {
		if ($def['key'] === $key) {
			$valid = true;
			break;
		}
	}
	if (!$valid) {
		choosology_clipboard_json(array('ok' => 0, 'error' => 'Unknown checklist item.'));
	}
	choosology_clipboard_set_checklist_flag($db, $user, $key, 'dismissed', 1);
	choosology_clipboard_json(array('ok' => 1));
}

if ($action === 'complete_checklist') {
	$key = trim((string) ($data['item_key'] ?? ''));
	$found = null;
	foreach (choosology_clipboard_checklist_defs() as $def) {
		if ($def['key'] === $key) {
			$found = $def;
			break;
		}
	}
	if ($found === null) {
		choosology_clipboard_json(array('ok' => 0, 'error' => 'Unknown checklist item.'));
	}
	/* Require the auto-condition so Done cannot grant Degrees/DataScrip without the task. */
	$autoMap = choosology_clipboard_eval_auto($db, $user, $sessionFlags);
	$autoKey = (string) ($found['auto'] ?? '');
	if ($autoKey === '' || empty($autoMap[$autoKey])) {
		choosology_clipboard_json(array(
			'ok' => 0,
			'error' => 'Finish this task before marking it done.',
		));
	}
	choosology_clipboard_set_checklist_flag($db, $user, $key, 'completed', 1);
	choosology_award_achievement($db, $user, (string) $found['achievement']);
	choosology_clipboard_json(array('ok' => 1));
}

/* Default: summary for office UI */
choosology_clipboard_sync_checklist($db, $user, $sessionFlags);

$escU = mysqli_real_escape_string($db, $user);
$checkState = array();
$cr = mysqli_query($db, "SELECT item_key, dismissed, completed FROM clipboard_checklist WHERE uname = '$escU'");
if ($cr) {
	while ($row = mysqli_fetch_assoc($cr)) {
		$checkState[(string) $row['item_key']] = array(
			'dismissed' => (int) ($row['dismissed'] ?? 0) === 1,
			'completed' => (int) ($row['completed'] ?? 0) === 1,
		);
	}
}
$checklist = array();
foreach (choosology_clipboard_checklist_defs() as $def) {
	$st = $checkState[$def['key']] ?? array('dismissed' => false, 'completed' => false);
	$checklist[] = array(
		'key' => $def['key'],
		'label' => $def['label'],
		'hint' => $def['hint'],
		'achievement' => $def['achievement'],
		'dismissed' => $st['dismissed'],
		'completed' => $st['completed'],
	);
}

$scrip = choosology_datascrip_summary_for_user($db, $user, 5);

choosology_clipboard_json(array(
	'ok' => 1,
	'pad' => choosology_clipboard_get_pad($db, $user),
	'todos' => choosology_clipboard_list_todos($db, $user),
	'checklist' => $checklist,
	'last_edited' => choosology_clipboard_last_edited_adv($db, $user),
	'metrics' => choosology_clipboard_experiment_metrics($db, $user),
	'achievements' => choosology_user_achievements($db, $user),
	'datascrip' => $scrip,
));
