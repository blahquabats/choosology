<?php
/**
 * Lab guide API for Classic.
 * action=signal  { feature, surface }
 * action=respond { interaction_key, choice }
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/guide-helpers.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

/**
 * @param array<string, mixed> $payload
 */
function choosology_guide_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw === false ? '' : $raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
if (!is_array($data)) {
	choosology_guide_json(array('ok' => 0, 'error' => 'Invalid request.'));
}

$action = (string) ($data['action'] ?? '');
if ($action === 'signal') {
	$feature = (string) ($data['feature'] ?? '');
	$surface = (string) ($data['surface'] ?? 'classic');
	$offer = choosology_guide_signal($db, $feature, $surface);
	if (is_array($offer)) {
		$offer['html'] = choosology_guide_render_panel($offer, 'classic');
	}
	choosology_guide_json(array(
		'ok' => 1,
		'feature' => strtolower(trim($feature)),
		'offer' => $offer,
	));
}

if ($action === 'respond') {
	$result = choosology_guide_respond(
		$db,
		(string) ($data['interaction_key'] ?? ''),
		(string) ($data['choice'] ?? '')
	);
	if (empty($result['ok'])) {
		choosology_guide_json(array(
			'ok' => 0,
			'error' => (string) ($result['error'] ?? 'That choice is not available.'),
			'feature' => (string) ($result['feature'] ?? ''),
			'offer' => null,
		));
	}
	$offer = $result['offer'] ?? null;
	if (is_array($offer)) {
		$offer['html'] = choosology_guide_render_panel($offer, 'classic');
	}
	choosology_guide_json(array(
		'ok' => 1,
		'feature' => (string) ($result['feature'] ?? ''),
		'offer' => $offer,
	));
}

choosology_guide_json(array('ok' => 0, 'error' => 'Unknown action.'));
