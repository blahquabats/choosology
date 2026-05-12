<?php
/**
 * Filesystem paths for uploaded pictures / icons (production vs local dev).
 *
 * Override for local Windows/Mac/Linux:
 * - Set CHOOSOLOGY_PICS_ROOT (and optionally CHOOSOLOGY_PICS_UNIVERSAL), or
 * - Add to connect.local.php: 'pics_root' => 'C:/path/to/pics', optionally 'pics_universal'.
 *
 * Defaults match legacy DreamHost/Linux layout.
 */

function choosology_pics_root(): string
{
	$default = '/home/abombmcgee/choosology.com/pics';

	$v = getenv('CHOOSOLOGY_PICS_ROOT');
	if ($v !== false && $v !== '') {
		return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $v), DIRECTORY_SEPARATOR);
	}

	$localPath = __DIR__ . DIRECTORY_SEPARATOR . 'connect.local.php';
	if (is_readable($localPath)) {
		$local = include $localPath;
		if (is_array($local) && !empty($local['pics_root'])) {
			return rtrim(str_replace('/', DIRECTORY_SEPARATOR, (string) $local['pics_root']), DIRECTORY_SEPARATOR);
		}
	}

	return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $default), DIRECTORY_SEPARATOR);
}

function choosology_pics_universal_dir(): string
{
	$v = getenv('CHOOSOLOGY_PICS_UNIVERSAL');
	if ($v !== false && $v !== '') {
		return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $v), DIRECTORY_SEPARATOR);
	}

	$localPath = __DIR__ . DIRECTORY_SEPARATOR . 'connect.local.php';
	if (is_readable($localPath)) {
		$local = include $localPath;
		if (is_array($local) && !empty($local['pics_universal'])) {
			return rtrim(str_replace('/', DIRECTORY_SEPARATOR, (string) $local['pics_universal']), DIRECTORY_SEPARATOR);
		}
	}

	return choosology_pics_root() . DIRECTORY_SEPARATOR . 'universal';
}

/**
 * Web path prefix for the app (e.g. "" at domain root, "/choosology" in a subfolder).
 * Used to build absolute-style paths for JSON and Location headers.
 * Override: env CHOOSOLOGY_WEB_BASE, connect.local.php key web_base, or infer from SCRIPT_NAME.
 */
function choosology_web_base(): string
{
	static $cached = null;
	if ($cached !== null) {
		return $cached;
	}
	$localPath = __DIR__ . DIRECTORY_SEPARATOR . 'connect.local.php';
	if (is_readable($localPath)) {
		$local = include $localPath;
		if (is_array($local) && array_key_exists('web_base', $local)) {
			$cached = rtrim(str_replace('\\', '/', (string) $local['web_base']), '/');
			return $cached;
		}
	}
	$env = getenv('CHOOSOLOGY_WEB_BASE');
	if ($env !== false && $env !== '') {
		$cached = rtrim(str_replace('\\', '/', (string) $env), '/');
		return $cached;
	}
	if (!empty($_SERVER['SCRIPT_NAME'])) {
		$sn = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
		$trim = trim($sn, '/');
		if ($trim !== '') {
			$parts = explode('/', $trim);
			foreach (array('ajax', 'vised', 'mystuff') as $appDir) {
				$i = array_search($appDir, $parts, true);
				if ($i !== false) {
					if ($i === 0) {
						$cached = '';
						return $cached;
					}
					$cached = '/' . implode('/', array_slice($parts, 0, $i));
					return $cached;
				}
			}
		}
		$d = rtrim(dirname($sn), '/');
		$cached = ($d === '' || $d === '.' || $d === '/') ? '' : $d;
		return $cached;
	}
	$cached = '';
	return $cached;
}

/** Site-root URL path (leading slash, no trailing slash on base). */
function choosology_site_url(string $path): string
{
	$path = ltrim(str_replace('\\', '/', $path), '/');
	$b = choosology_web_base();
	if ($b === '' || $b === '/') {
		return '/' . $path;
	}
	return $b . '/' . $path;
}
