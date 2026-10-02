<?php
/**
 * Save current user's profile/account settings.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/account-helpers.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

function choosology_account_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_account_json(array('ok' => 0, 'error' => 'Not signed in.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_account_json(array('ok' => 0, 'error' => 'Invalid JSON.'));
}

$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$rows = runquery_assoc("SELECT name, pass FROM users WHERE name = '$escUser' LIMIT 1");
if (!is_array($rows) || !isset($rows[0])) {
	choosology_account_json(array('ok' => 0, 'error' => 'Account not found.'));
}
$account = $rows[0];

$aboutRaw = isset($data['about']) ? (string) $data['about'] : '';
if (choosology_account_plain_len($aboutRaw) > 400) {
	choosology_account_json(array('ok' => 0, 'error' => 'Bio must be 400 characters or fewer, not counting formatting.'));
}
$about = choosology_account_sanitize_about($aboutRaw);

$viewRestricted = !empty($data['view_restricted']) ? 1 : 0;
$picRaw = isset($data['pic']) ? trim((string) $data['pic']) : '';
$picSql = '0';
if ($picRaw !== '') {
	if (!ctype_digit($picRaw)) {
		choosology_account_json(array('ok' => 0, 'error' => 'Invalid profile image.'));
	}
	$picId = (int) $picRaw;
	if ($picId > 0) {
		$picRows = runquery_assoc("SELECT id, user FROM pics WHERE id = '$picId' LIMIT 1");
		if (!is_array($picRows) || !isset($picRows[0])) {
			choosology_account_json(array('ok' => 0, 'error' => 'Profile image not found.'));
		}
		$picOwner = (string) ($picRows[0]['user'] ?? '');
		if ($picOwner !== $user && $picOwner !== '&everyone') {
			choosology_account_json(array('ok' => 0, 'error' => 'You may not use that profile image.'));
		}
		$picSql = (string) $picId;
	}
}

$passwordChanged = false;
$passwordSetSql = '';
$currentPassword = isset($data['current_password']) ? (string) $data['current_password'] : '';
$newPassword = isset($data['new_password']) ? (string) $data['new_password'] : '';
$confirmPassword = isset($data['confirm_password']) ? (string) $data['confirm_password'] : '';
if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
	if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
		choosology_account_json(array('ok' => 0, 'error' => 'Fill out all password fields to change your password.'));
	}
	if ($newPassword !== $confirmPassword) {
		choosology_account_json(array('ok' => 0, 'error' => 'New password confirmation does not match.'));
	}
	if (strlen($newPassword) < 5) {
		choosology_account_json(array('ok' => 0, 'error' => 'New password must be at least 5 characters.'));
	}
	$currentHash = md5($sel . $currentPassword);
	if (!hash_equals((string) ($account['pass'] ?? ''), $currentHash)) {
		choosology_account_json(array('ok' => 0, 'error' => 'Current password is incorrect.'));
	}
	$newHash = md5($sel . $newPassword);
	$passwordSetSql = ", pass = '" . mysqli_real_escape_string($db, $newHash) . "'";
	$passwordChanged = true;
}

$aboutEsc = mysqli_real_escape_string($db, $about);
$q = "UPDATE users SET
	about = '$aboutEsc',
	pic = '$picSql',
	view_restricted = '$viewRestricted'
	$passwordSetSql
	WHERE name = '$escUser'
	LIMIT 1";

if (!mysqli_query($db, $q)) {
	choosology_account_json(array('ok' => 0, 'error' => 'Could not save account settings.'));
}

choosology_account_json(array('ok' => 1, 'passwordChanged' => $passwordChanged));
