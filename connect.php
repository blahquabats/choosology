<?php
require_once __DIR__ . '/db-config.php';

$s = choosology_db_settings('choosology');
try {
	$db = mysqli_connect($s['host'], $s['user'], $s['password'], $s['database']);
} catch (mysqli_sql_exception $e) {
	if (choosology_wants_json_error_response()) {
		choosology_exit_json_error(503, 'database_connection', 'Could not connect to the database.');
	}
	http_response_code(503);
	$detail = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
	$host = htmlspecialchars((string) $s['host'], ENT_QUOTES, 'UTF-8');
	die(
		'<p><strong>Could not connect to the database.</strong></p>'
		. '<p><code>' . $detail . '</code></p>'
		. '<p>Configured host: <code>' . $host . '</code> (see <code>db-config.php</code>, optional <code>connect.local.php</code>, or <code>CHOOSOLOGY_DB_*</code> environment variables).</p>'
		. '<p>If you see <strong>actively refused</strong>, nothing is accepting TCP on that host/port: start MySQL/MariaDB locally, or point <code>host</code> at a reachable server. For local dev, add <code>connect.local.php</code> in this folder returning an array with <code>host</code>, <code>user</code>, and <code>password</code>.</p>'
	);
}
if (!$db) {
	if (choosology_wants_json_error_response()) {
		choosology_exit_json_error(503, 'database_connection', 'Could not connect to the database.');
	}
	http_response_code(503);
	die('no db');
}

mysqli_set_charset($db, 'utf8mb4');
mysqli_query($db, "SET time_zone='-07:00'");

foreach ($_POST as & $post)
{
	if(!is_array($post)) $post = htmlspecialchars(mysqli_real_escape_string($db, $post));
}
foreach ($_COOKIE as & $cookie)
{
	//$cookie = htmlspecialchars(mysqli_real_escape_string($db, $cookie));
}
foreach ($_GET as & $get)
{
	$get = htmlspecialchars(mysqli_real_escape_string($db, $get));
}

$sel = "cYo";
if (session_status() === PHP_SESSION_NONE) {
	ini_set('session.cookie_httponly', '1');
	ini_set('session.use_strict_mode', '1');
	if (PHP_VERSION_ID >= 70300) {
		ini_set('session.cookie_samesite', 'Lax');
	}
	if (choosology_request_is_https()) {
		ini_set('session.cookie_secure', '1');
	}
	session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user'] == "")
{
    if (($_GET['project_lazarus'] ?? null) != "go") 
    {
        //echo "Not yet! :)<META http-equiv='refresh' content='0;URL=http://www.cyocyoa.com'>";
        //die();
    }
}
?>