<?php
/**
 * Permanently delete an experiment owned by the signed-in user.
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

function choosology_delete_adventure_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_delete_adventure_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_delete_adventure_json(array('ok' => 0, 'error' => 'Invalid JSON.'));
}

$advid = isset($data['advid']) ? (int) $data['advid'] : 0;
if ($advid < 1) {
	choosology_delete_adventure_json(array('ok' => 0, 'error' => 'Invalid experiment id.'));
}

$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$escAdvid = mysqli_real_escape_string($db, (string) $advid);

$own = runquery_assoc("SELECT id, title FROM advs WHERE id = '$escAdvid' AND user = '$escUser' LIMIT 1");
$legacyEscAdvid = mysqli_real_escape_string($db, ':' . (string) $advid . ':');

if (!is_array($own) || count($own) === 0) {
	choosology_delete_adventure_json(array('ok' => 0, 'error' => 'Experiment not found or not yours.'));
}

mysqli_begin_transaction($db);
try {
	if (!mysqli_query($db, "DELETE FROM ratings WHERE adv = '$escAdvid'")) {
		throw new RuntimeException(mysqli_error($db));
	}
	if (!mysqli_query($db, "DELETE FROM comments WHERE whichboard = 'adv$escAdvid'")) {
		throw new RuntimeException(mysqli_error($db));
	}
	if (!mysqli_query($db, "DELETE FROM paths WHERE adv = '$escAdvid'")) {
		throw new RuntimeException(mysqli_error($db));
	}
	if (!mysqli_query($db, "DELETE FROM advscreens WHERE advused IN ('$escAdvid', '$legacyEscAdvid') AND user = '$escUser'")) {
		throw new RuntimeException(mysqli_error($db));
	}
	if (!mysqli_query($db, "DELETE FROM advs WHERE id = '$escAdvid' AND user = '$escUser' LIMIT 1")) {
		throw new RuntimeException(mysqli_error($db));
	}
	if (mysqli_affected_rows($db) < 1) {
		throw new RuntimeException('No experiment row deleted.');
	}

	mysqli_commit($db);
	choosology_delete_adventure_json(array('ok' => 1, 'id' => $advid));
} catch (Throwable $e) {
	mysqli_rollback($db);
	$error = 'Could not delete experiment.';
	if (getenv('CHOOSOLOGY_DEBUG') === '1') {
		$error .= ' ' . $e->getMessage();
	}
	choosology_delete_adventure_json(array('ok' => 0, 'error' => $error));
}
