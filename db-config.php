<?php
/**
 * Database connection settings for Choosology.
 *
 * Resolution order (each step overrides the previous):
 * 1. Defaults (production / DreamHost remote host)
 * 2. Optional connect.local.php in this directory (overrides host / user / password only;
 *    default database name still comes from connect.php vs connect1.php unless
 *    CHOOSOLOGY_DB_DATABASE is set)
 * 3. Environment variables (CHOOSOLOGY_DB_*), e.g. from Apache SetEnv or the system
 *
 * Set CHOOSOLOGY_DB_HOST=127.0.0.1 (or localhost) for local MySQL without editing PHP files.
 *
 * CHOOSOLOGY_JSON_ERRORS=1 forces JSON (instead of HTML) for database bootstrap failures in connect.php.
 */

function choosology_db_settings(string $defaultDatabase): array
{
	$cfg = [
		'host' => 'mysql.cyocyoa.com',
		'user' => 'luser',
		'password' => 'l_jU65TaA3',
		'database' => $defaultDatabase,
	];

	$localPath = __DIR__ . DIRECTORY_SEPARATOR . 'connect.local.php';
	if (is_readable($localPath)) {
		$local = include $localPath;
		if (is_array($local)) {
			foreach (['host', 'user', 'password'] as $k) {
				if (array_key_exists($k, $local)) {
					$cfg[$k] = $local[$k];
				}
			}
		}
	}

	$envKeys = [
		'host' => 'CHOOSOLOGY_DB_HOST',
		'user' => 'CHOOSOLOGY_DB_USER',
		'password' => 'CHOOSOLOGY_DB_PASSWORD',
		'database' => 'CHOOSOLOGY_DB_DATABASE',
	];
	foreach ($envKeys as $key => $envName) {
		$v = getenv($envName);
		if ($v !== false && $v !== '') {
			$cfg[$key] = $v;
		}
	}

	return $cfg;
}

/**
 * True when the request is served over HTTPS (including common reverse-proxy headers).
 */
function choosology_request_is_https(): bool
{
	if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
		return true;
	}
	if (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
		return true;
	}
	$fp = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
	return $fp === 'https';
}

/**
 * Prefer JSON for fatal DB bootstrap errors (e.g. ajax/ or Accept: application/json).
 */
function choosology_wants_json_error_response(): bool
{
	if (getenv('CHOOSOLOGY_JSON_ERRORS') === '1') {
		return true;
	}
	if (!empty($_GET['format']) && (string) $_GET['format'] === 'json') {
		return true;
	}
	$accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
	if (stripos($accept, 'application/json') !== false) {
		return true;
	}
	$scr = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
	if ($scr !== '' && stripos($scr, '/ajax/') !== false) {
		return true;
	}
	return false;
}

/**
 * Emit a JSON error body and exit (used when DB is unavailable).
 */
function choosology_exit_json_error(int $httpCode, string $errorCode, string $message): void
{
	http_response_code($httpCode);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(
		array(
			'ok' => false,
			'error' => $errorCode,
			'code' => $httpCode,
			'message' => $message,
		),
		JSON_UNESCAPED_SLASHES
	);
	exit;
}
