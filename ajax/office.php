<?php
/**
 * My Office JSON API (state, shop, equip, trinket pedestals).
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/office-helpers.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

function choosology_office_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_office_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

$user = (string) $_SESSION['user'];
choosology_office_ensure_schema($db);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
if (!is_array($data)) {
	$data = array();
}
$action = (string) ($data['action'] ?? $_GET['action'] ?? 'state');

if ($action === 'state') {
	choosology_office_json(choosology_office_state($db, $user));
}

if ($action === 'buy') {
	$itemKey = (string) ($data['item_key'] ?? '');
	$result = choosology_office_buy($db, $user, $itemKey);
	choosology_office_json(array(
		'ok' => !empty($result['ok']) ? 1 : 0,
		'error' => $result['error'] ?? null,
		'balance' => $result['balance'] ?? null,
		'state' => $result['state'] ?? null,
	));
}

if ($action === 'equip_decor') {
	$slot = (string) ($data['slot'] ?? '');
	$itemKey = (string) ($data['item_key'] ?? '');
	$result = choosology_office_equip_decor($db, $user, $slot, $itemKey);
	choosology_office_json(array(
		'ok' => !empty($result['ok']) ? 1 : 0,
		'error' => $result['error'] ?? null,
		'state' => $result['state'] ?? null,
	));
}

if ($action === 'place_trinket') {
	$idx = (int) ($data['pedestal_index'] ?? -1);
	$rawKey = $data['item_key'] ?? null;
	$itemKey = ($rawKey === null || $rawKey === '' || $rawKey === false) ? null : (string) $rawKey;
	$result = choosology_office_place_trinket($db, $user, $idx, $itemKey);
	choosology_office_json(array(
		'ok' => !empty($result['ok']) ? 1 : 0,
		'error' => $result['error'] ?? null,
		'state' => $result['state'] ?? null,
	));
}

if ($action === 'clear_trinket') {
	$idx = (int) ($data['pedestal_index'] ?? -1);
	$result = choosology_office_place_trinket($db, $user, $idx, null);
	choosology_office_json(array(
		'ok' => !empty($result['ok']) ? 1 : 0,
		'error' => $result['error'] ?? null,
		'state' => $result['state'] ?? null,
	));
}

choosology_office_json(array('ok' => 0, 'error' => 'Unknown action.'));
