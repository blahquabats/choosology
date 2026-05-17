<?php
/**
 * Save current user's profile/account settings.
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

function choosology_account_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

function choosology_account_plain_len(string $html): int
{
	$plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
	if (function_exists('mb_strlen')) {
		return mb_strlen($plain, 'UTF-8');
	}
	return strlen($plain);
}

function choosology_account_sanitize_about(string $html): string
{
	$html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html);
	$html = strip_tags((string) $html, '<p><br><strong><em><b><i><a><ul><ol><li><blockquote><span>');
	$html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
	$html = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $html);
	$html = preg_replace_callback('/\sstyle\s*=\s*([\'"])(.*?)\1/is', static function (array $m): string {
		$safe = array();
		$decls = explode(';', (string) $m[2]);
		foreach ($decls as $decl) {
			$parts = explode(':', $decl, 2);
			if (count($parts) !== 2) {
				continue;
			}
			$prop = strtolower(trim($parts[0]));
			$value = trim($parts[1]);
			if (!in_array($prop, array('color', 'background-color'), true)) {
				continue;
			}
			if (preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\(\s*[0-9.\s,%]+\)|[a-zA-Z]+)$/', $value)) {
				$safe[] = $prop . ': ' . $value;
			}
		}
		return count($safe) > 0 ? ' style="' . htmlspecialchars(implode('; ', $safe), ENT_QUOTES, 'UTF-8') . '"' : '';
	}, $html);
	return (string) $html;
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
