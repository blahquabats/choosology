<?php
/**
 * JSON actions for My Office → RESTRICTED.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../messagesfunc.php';
require_once __DIR__ . '/../lib/restricted-helpers.php';
require_once __DIR__ . '/../lib/guide-admin-helpers.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

/**
 * @param array<string,mixed> $payload
 */
function choosology_restricted_json(array $payload, int $status = 200): void
{
	global $jsonFlags;
	http_response_code($status);
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (!choosology_restricted_is_admin()) {
	choosology_restricted_json(array('ok' => 0, 'error' => 'Admin access required.'), 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw === false ? '' : $raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
if (!is_array($data)) {
	choosology_restricted_json(array('ok' => 0, 'error' => 'Invalid request.'), 400);
}

choosology_guide_ensure_schema($db);
choosology_clipboard_ensure_schema($db);
$action = trim((string) ($data['action'] ?? 'state'));
$admin = (string) $_SESSION['user'];

if ($action === 'state') {
	choosology_restricted_json(array(
		'ok' => 1,
		'users' => choosology_restricted_usernames($db),
		'degrees' => choosology_achievement_catalog(),
		'comments' => choosology_restricted_pending_comments($db),
		'events' => choosology_guide_event_registry(),
		'features' => choosology_guide_feature_registry(),
		'mascots' => choosology_guide_load_mascots($db),
		'interactions' => choosology_guide_load_interactions($db),
	));
}

if ($action === 'award_degree') {
	choosology_restricted_json(choosology_restricted_grant_degree(
		$db,
		$admin,
		(string) ($data['to'] ?? ''),
		(string) ($data['degree'] ?? '')
	));
}

if ($action === 'award_scrip') {
	$scope = (string) ($data['scope'] ?? 'one');
	$result = choosology_datascrip_admin_grant(
		$db,
		$admin,
		(string) ($data['to'] ?? ''),
		(int) ($data['amount'] ?? 0),
		(string) ($data['memo'] ?? ''),
		$scope === 'all'
	);
	if (!empty($result['ok'])) {
		$result['formatted'] = choosology_datascrip_format((int) $result['amount']);
	}
	choosology_restricted_json($result);
}

if ($action === 'moderate_comment') {
	choosology_restricted_json(choosology_restricted_moderate_comment(
		$db,
		(string) ($data['mode'] ?? ''),
		(int) ($data['comment_id'] ?? 0)
	));
}

if ($action === 'save_mascot') {
	choosology_restricted_json(choosology_guide_admin_save_mascot(
		$db,
		is_array($data['mascot'] ?? null) ? $data['mascot'] : array()
	));
}
if ($action === 'save_interaction') {
	choosology_restricted_json(choosology_guide_admin_save_interaction(
		$db,
		is_array($data['interaction'] ?? null) ? $data['interaction'] : array()
	));
}
if ($action === 'delete_mascot') {
	choosology_restricted_json(choosology_guide_admin_delete_mascot(
		$db,
		(string) ($data['key'] ?? '')
	));
}
if ($action === 'delete_interaction') {
	choosology_restricted_json(choosology_guide_admin_delete_interaction(
		$db,
		(string) ($data['key'] ?? '')
	));
}
if ($action === 'reset_mascot') {
	choosology_restricted_json(choosology_guide_admin_reset_mascot(
		$db,
		(string) ($data['key'] ?? '')
	));
}
if ($action === 'reset_interaction') {
	choosology_restricted_json(choosology_guide_admin_reset_interaction(
		$db,
		(string) ($data['key'] ?? '')
	));
}
if ($action === 'preview_interaction') {
	choosology_restricted_json(choosology_guide_admin_preview(
		$db,
		is_array($data['interaction'] ?? null) ? $data['interaction'] : array(),
		(string) ($data['beat_id'] ?? '')
	));
}
if ($action === 'reset_my_guide_progress') {
	$subject = mysqli_real_escape_string($db, choosology_guide_subject_key());
	mysqli_query($db, "DELETE FROM guide_progress WHERE subject_key='$subject'");
	mysqli_query($db, "DELETE FROM guide_seen WHERE subject_key='$subject'");
	choosology_restricted_json(array('ok' => 1));
}

choosology_restricted_json(array('ok' => 0, 'error' => 'Unknown action.'), 400);
