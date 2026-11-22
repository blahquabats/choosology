<?php
/**
 * Password reset request (JSON). Always returns a generic success message
 * when the identity looks valid, so account existence is not leaked.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/password-reset-helpers.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

function choosology_password_reset_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (!empty($_SESSION['user'])) {
	choosology_password_reset_json(array('ok' => 0, 'error' => 'Already signed in.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw === false ? '' : $raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
if (!is_array($data)) {
	choosology_password_reset_json(array('ok' => 0, 'error' => 'Invalid request.'));
}

/* Honeypot */
if (trim((string) ($data['lab_fax'] ?? '')) !== '') {
	choosology_password_reset_json(array(
		'ok' => 1,
		'message' => CHOOSOLOGY_RESET_GENERIC_OK,
		'mailed' => false,
	));
}

$identity = trim((string) ($data['identity'] ?? $data['email'] ?? $data['name'] ?? ''));
/* connect.php may HTML-escape POST; undo for lookup. */
if (function_exists('choosology_undo_connect_string_mutation')) {
	$identity = choosology_undo_connect_string_mutation($identity);
}

$result = choosology_password_reset_request($db, $identity);
if (empty($result['ok']) && !empty($result['error'])) {
	choosology_password_reset_json(array('ok' => 0, 'error' => $result['error']));
}
choosology_password_reset_json(array(
	'ok' => 1,
	'message' => $result['message'] ?? CHOOSOLOGY_RESET_GENERIC_OK,
	'mailed' => !empty($result['mailed']),
	'debug_url' => $result['debug_url'] ?? null,
));
