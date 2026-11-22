<?php
/**
 * Email password-reset helpers (request token + confirm new password).
 */

require_once __DIR__ . '/choosology-core.php';
if (!function_exists('choosology_site_url')) {
	require_once dirname(__DIR__) . '/paths-config.php';
}

const CHOOSOLOGY_RESET_TTL_SECONDS = 3600;
const CHOOSOLOGY_RESET_GENERIC_OK = 'If that account exists, we sent reset instructions to its contact address.';

function choosology_password_reset_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	$cols = array();
	$r = @mysqli_query($db, 'SHOW COLUMNS FROM users');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$cols[(string) ($row['Field'] ?? '')] = true;
		}
	}
	if (empty($cols['reset_token'])) {
		@mysqli_query(
			$db,
			'ALTER TABLE users ADD COLUMN reset_token varchar(64) DEFAULT NULL AFTER chpass'
		);
	}
	if (empty($cols['reset_expires'])) {
		@mysqli_query(
			$db,
			'ALTER TABLE users ADD COLUMN reset_expires datetime DEFAULT NULL AFTER reset_token'
		);
	}
}

function choosology_password_reset_hash_token(string $token): string
{
	return hash('sha256', $token);
}

/**
 * Find an account by lab handle or email (case-insensitive email).
 *
 * @return array{name:string,email:string}|null
 */
function choosology_password_reset_find_account(mysqli $db, string $identity): ?array
{
	$identity = trim($identity);
	if ($identity === '' || strlen($identity) > 100) {
		return null;
	}
	$esc = mysqli_real_escape_string($db, $identity);
	$r = mysqli_query(
		$db,
		"SELECT name, email FROM users
		 WHERE name='$esc' OR LOWER(email)=LOWER('$esc')
		 LIMIT 1"
	);
	$row = $r ? mysqli_fetch_assoc($r) : null;
	if (!$row) {
		return null;
	}
	$name = trim((string) ($row['name'] ?? ''));
	$email = trim((string) ($row['email'] ?? ''));
	if ($name === '' || $email === '' || !checkEmail($email)) {
		return null;
	}
	return array('name' => $name, 'email' => $email);
}

function choosology_password_reset_send_email(string $name, string $email, string $resetUrl): bool
{
	if (!function_exists('mail')) {
		return false;
	}
	$name = trim($name);
	$email = trim($email);
	if ($name === '' || $email === '' || !checkEmail($email) || $resetUrl === '') {
		return false;
	}
	$subject = 'Choosology Lab — password reset';
	$body = "Hello {$name},\n\n"
		. "We received a request to reset the password for your Choosology Lab account.\n\n"
		. "Open this link within one hour to choose a new password:\n"
		. "{$resetUrl}\n\n"
		. "If you did not request this, you can ignore this message. Your current password stays unchanged.\n\n"
		. "— The Choosology Lab\n";
	$from = 'Choosology Lab <noreply@choosology.com>';
	$headers = 'MIME-Version: 1.0' . "\r\n"
		. 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
		. 'From: ' . $from . "\r\n"
		. 'Reply-To: ' . $from . "\r\n"
		. 'X-Mailer: Choosology';
	return (bool) @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

/**
 * Issue a reset token for an identity. Always returns a generic ok payload
 * (does not reveal whether the account exists), except on hard validation errors.
 *
 * @return array{ok:bool,error?:string,message?:string,mailed?:bool,debug_url?:string}
 */
function choosology_password_reset_request(mysqli $db, string $identity): array
{
	choosology_password_reset_ensure_schema($db);
	$identity = trim($identity);
	if ($identity === '') {
		return array('ok' => false, 'error' => 'Enter your lab handle or contact address.');
	}

	/* Session throttle: at most one attempt every 20s. */
	$now = time();
	$last = (int) ($_SESSION['password_reset_last'] ?? 0);
	if ($last > 0 && ($now - $last) < 20) {
		return array('ok' => true, 'message' => CHOOSOLOGY_RESET_GENERIC_OK, 'mailed' => false);
	}
	$_SESSION['password_reset_last'] = $now;

	$account = choosology_password_reset_find_account($db, $identity);
	if ($account === null) {
		return array('ok' => true, 'message' => CHOOSOLOGY_RESET_GENERIC_OK, 'mailed' => false);
	}

	$token = bin2hex(random_bytes(16));
	$tokenHash = choosology_password_reset_hash_token($token);
	$escName = mysqli_real_escape_string($db, $account['name']);
	$escHash = mysqli_real_escape_string($db, $tokenHash);
	$ttl = (int) CHOOSOLOGY_RESET_TTL_SECONDS;
	$ok = mysqli_query(
		$db,
		"UPDATE users
		 SET reset_token='$escHash',
		     reset_expires=DATE_ADD(NOW(), INTERVAL $ttl SECOND)
		 WHERE name='$escName'
		 LIMIT 1"
	);
	if (!$ok) {
		return array('ok' => false, 'error' => 'Could not start a password reset. Try again shortly.');
	}

	$resetUrl = choosology_site_url('resetpass.php?token=' . rawurlencode($token));
	$mailed = choosology_password_reset_send_email($account['name'], $account['email'], $resetUrl);
	$payload = array(
		'ok' => true,
		'message' => CHOOSOLOGY_RESET_GENERIC_OK,
		'mailed' => $mailed,
	);
	/* Local/dev convenience when mail() is unavailable. */
	if (!$mailed && getenv('CHOOSOLOGY_DEBUG') === '1') {
		$payload['debug_url'] = $resetUrl;
	}
	return $payload;
}

/**
 * @return array{ok:bool,error?:string,name?:string}
 */
function choosology_password_reset_validate_token(mysqli $db, string $token): array
{
	choosology_password_reset_ensure_schema($db);
	$token = trim($token);
	if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
		return array('ok' => false, 'error' => 'This reset link is invalid or incomplete.');
	}
	$hash = choosology_password_reset_hash_token($token);
	$esc = mysqli_real_escape_string($db, $hash);
	$r = mysqli_query(
		$db,
		"SELECT name FROM users
		 WHERE reset_token='$esc'
		   AND reset_expires IS NOT NULL
		   AND reset_expires > NOW()
		 LIMIT 1"
	);
	$row = $r ? mysqli_fetch_assoc($r) : null;
	if (!$row) {
		return array('ok' => false, 'error' => 'This reset link is invalid or has expired.');
	}
	return array('ok' => true, 'name' => (string) $row['name']);
}

/**
 * @return array{ok:bool,error?:string,name?:string}
 */
function choosology_password_reset_confirm(
	mysqli $db,
	string $token,
	string $pass1,
	string $pass2
): array {
	$check = choosology_password_reset_validate_token($db, $token);
	if (empty($check['ok'])) {
		return $check;
	}
	if ($pass1 !== $pass2) {
		return array('ok' => false, 'error' => 'Passwords do not match.');
	}
	if (strlen($pass1) < 6) {
		return array('ok' => false, 'error' => 'Choose a password of at least 6 characters.');
	}
	if (strlen($pass1) > 72) {
		return array('ok' => false, 'error' => 'Password is too long (72 characters max).');
	}
	$name = (string) $check['name'];
	$hash = choosology_legacy_password_hash($pass1);
	$escName = mysqli_real_escape_string($db, $name);
	$escPass = mysqli_real_escape_string($db, $hash);
	$ok = mysqli_query(
		$db,
		"UPDATE users
		 SET pass='$escPass',
		     reset_token=NULL,
		     reset_expires=NULL,
		     chpass=NULL
		 WHERE name='$escName'
		 LIMIT 1"
	);
	if (!$ok || mysqli_affected_rows($db) < 1) {
		return array('ok' => false, 'error' => 'Could not update the password. Try a fresh reset link.');
	}
	return array('ok' => true, 'name' => $name);
}
