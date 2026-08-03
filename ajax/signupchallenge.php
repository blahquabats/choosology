<?php
/**
 * Issue a short-lived signup anti-bot challenge (time trap + simple arithmetic).
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

if (!empty($_SESSION['user'])) {
	echo json_encode(array('ok' => 0, 'error' => 'Already signed in.'), $jsonFlags);
	exit;
}

$a = random_int(2, 9);
$b = random_int(1, 8);
$_SESSION['signup_challenge'] = array(
	'opened' => time(),
	'answer' => $a + $b,
	'nonce' => bin2hex(random_bytes(8)),
);

echo json_encode(array(
	'ok' => 1,
	'prompt' => "What is {$a} + {$b}?",
	'nonce' => $_SESSION['signup_challenge']['nonce'],
), $jsonFlags);
exit;
