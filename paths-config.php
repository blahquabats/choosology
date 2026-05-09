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
