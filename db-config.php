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
