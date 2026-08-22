<?php
/**
 * Create a new lab account (signup), then establish a session (instant login).
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

function choosology_signup_json(array $payload): void
{
	global $jsonFlags;
	echo json_encode($payload, $jsonFlags);
	exit;
}

if (!empty($_SESSION['user'])) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Already signed in.'));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Invalid application payload.'));
}

/* ---- Anti-spam: honeypot ---- */
$honeypot = trim((string) ($data['lab_fax'] ?? $data['website'] ?? ''));
if ($honeypot !== '') {
	choosology_signup_json(array('ok' => 0, 'error' => 'Application rejected.'));
}

/* ---- Anti-spam: time trap + math challenge ---- */
$challenge = $_SESSION['signup_challenge'] ?? null;
if (!is_array($challenge) || empty($challenge['opened']) || !isset($challenge['answer'])) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Application form expired. Close and reopen the intake window.'));
}
$elapsed = time() - (int) $challenge['opened'];
if ($elapsed < 2) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Application filed too quickly. Pause a moment and try again.'));
}
if ($elapsed > 7200) {
	unset($_SESSION['signup_challenge']);
	choosology_signup_json(array('ok' => 0, 'error' => 'Application form expired. Close and reopen the intake window.'));
}
$nonce = (string) ($data['nonce'] ?? '');
if ($nonce === '' || !hash_equals((string) ($challenge['nonce'] ?? ''), $nonce)) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Application form expired. Close and reopen the intake window.'));
}
$humanAnswer = trim((string) ($data['human_check'] ?? ''));
if ($humanAnswer === '' || (int) $humanAnswer !== (int) $challenge['answer']) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Human verification failed. Recheck the arithmetic and try again.'));
}

$name = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$pass1 = (string) ($data['pass1'] ?? '');
$pass2 = (string) ($data['pass2'] ?? '');
$wantWelcome = !empty($data['welcome_email']);
$wantNewsletter = !empty($data['newsletter']);

if ($name === '' || preg_match('/[^a-z0-9 ._-]/i', $name)) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Lab handle must use letters, numbers, spaces, dots, underscores, or hyphens.'));
}
if (strlen($name) > 45) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Lab handle is too long (45 characters max).'));
}
if (!checkEmail($email)) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Enter a valid contact address.'));
}
if ($pass1 !== $pass2) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Passwords do not match.'));
}
if (strlen($pass1) < 5) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Password must be at least 5 characters.'));
}
if (strlen($pass1) > 72) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Password is too long.'));
}

choosology_users_ensure_signup_columns($db);

$escName = mysqli_real_escape_string($db, $name);
$escEmail = mysqli_real_escape_string($db, $email);
$dup = mysqli_query($db, "SELECT name, email FROM users WHERE name = '$escName' OR email = '$escEmail' LIMIT 1");
if ($dup && ($match = mysqli_fetch_assoc($dup))) {
	if (strcasecmp((string) $match['email'], $email) === 0) {
		choosology_signup_json(array('ok' => 0, 'error' => 'That contact address is already on file.'));
	}
	choosology_signup_json(array('ok' => 0, 'error' => 'That lab handle is already taken.'));
}

global $sel;
if (!isset($sel) || $sel === '') {
	$sel = 'cYo';
}
$hash = md5($sel . $pass1);
$authent = md5((string) random_int(10000, 99999999) . 'cyo');
$newsletter = $wantNewsletter ? 1 : 0;
$welcomePending = $wantWelcome ? 1 : 0;

/* Confirm columns after ensure (older DBs). */
$colOk = false;
$colCheck = @mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'newsletter'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
	$colOk = true;
}

if ($colOk) {
	$stmt = mysqli_prepare(
		$db,
		'INSERT INTO users (name, pass, email, authent, joined, hint, newsletter, welcome_pending, lastlogin, usertype, view_restricted, pic, fbshow)
		 VALUES (?, ?, ?, ?, NOW(), \'\', ?, ?, NOW(), 0, 0, 0, 0)'
	);
	if (!$stmt) {
		choosology_signup_json(array('ok' => 0, 'error' => 'Could not file application (database).'));
	}
	mysqli_stmt_bind_param($stmt, 'ssssii', $name, $hash, $email, $authent, $newsletter, $welcomePending);
} else {
	$stmt = mysqli_prepare(
		$db,
		'INSERT INTO users (name, pass, email, authent, joined, hint, lastlogin, usertype, view_restricted, pic, fbshow)
		 VALUES (?, ?, ?, ?, NOW(), \'\', NOW(), 0, 0, 0, 0)'
	);
	if (!$stmt) {
		choosology_signup_json(array('ok' => 0, 'error' => 'Could not file application (database).'));
	}
	mysqli_stmt_bind_param($stmt, 'ssss', $name, $hash, $email, $authent);
}
if (!mysqli_stmt_execute($stmt)) {
	choosology_signup_json(array('ok' => 0, 'error' => 'Could not file application. Try a different handle or address.'));
}
$userId = (int) mysqli_insert_id($db);
mysqli_stmt_close($stmt);

$welcomeSent = false;
if ($wantWelcome) {
	$welcomeSent = choosology_send_welcome_email($name, $email);
	if ($welcomeSent) {
		@mysqli_query($db, "UPDATE users SET welcome_pending = 0 WHERE id = $userId LIMIT 1");
	}
}

/* Instant login */
@session_regenerate_id(true);
$_SESSION['user'] = $name;
$_SESSION['usertype'] = 0;
unset($_SESSION['signup_challenge']);

choosology_signup_json(array(
	'ok' => 1,
	'name' => $name,
	'id' => $userId,
	'newsletter' => $newsletter,
	'welcome_requested' => $wantWelcome ? 1 : 0,
	'welcome_sent' => $welcomeSent ? 1 : 0,
));
