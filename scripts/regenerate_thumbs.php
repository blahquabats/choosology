#!/usr/bin/env php
<?php
/**
 * Rebuild pics thumbs/ directories from full-size originals (max 240px).
 *
 * Usage (from repo root):
 *   php scripts/regenerate_thumbs.php
 *   php scripts/regenerate_thumbs.php --user=Cursor
 *
 * Requires the PHP GD extension (php-gd / php8.3-gd).
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

require_once dirname(__DIR__) . '/connect.php';
require_once dirname(__DIR__) . '/auxfuncs.php';

if (!choosology_gd_available()) {
	fwrite(STDERR, "PHP GD is not available. Install php-gd / php8.3-gd and retry.\n");
	exit(1);
}

$userFilter = null;
foreach ($argv as $arg) {
	if (strpos($arg, '--user=') === 0) {
		$userFilter = substr($arg, 7);
	}
}

$sql = 'SELECT id, filename, user, type FROM pics ORDER BY id ASC';
if ($userFilter !== null && $userFilter !== '') {
	$esc = mysqli_real_escape_string($db, $userFilter);
	$sql = "SELECT id, filename, user, type FROM pics WHERE user = '$esc' ORDER BY id ASC";
}

$rows = runquery_assoc($sql);
if (!is_array($rows)) {
	fwrite(STDERR, "Query failed: $rows\n");
	exit(1);
}

$ok = 0;
$skip = 0;
$fail = 0;
foreach ($rows as $row) {
	$id = (int) ($row['id'] ?? 0);
	$fn = (string) ($row['filename'] ?? '');
	$user = (string) ($row['user'] ?? '');
	$mime = (string) ($row['type'] ?? '');
	if ($id < 1 || $fn === '' || $user === '') {
		$skip++;
		continue;
	}
	$dir = playerDir($user);
	$src = $dir . DIRECTORY_SEPARATOR . $fn;
	$thumbDir = $dir . DIRECTORY_SEPARATOR . 'thumbs';
	$dest = $thumbDir . DIRECTORY_SEPARATOR . $fn;
	if (!is_file($src)) {
		fwrite(STDERR, "skip #$id missing full image: $src\n");
		$skip++;
		continue;
	}
	if (!is_dir($thumbDir) && !mkdir($thumbDir, 0775, true)) {
		fwrite(STDERR, "fail #$id could not create $thumbDir\n");
		$fail++;
		continue;
	}
	if ($mime === '' || strpos($mime, 'image/') !== 0) {
		$info = @getimagesize($src);
		$mime = is_array($info) && !empty($info['mime']) ? (string) $info['mime'] : 'image/jpeg';
	}
	clearstatcache(true, $dest);
	$before = is_file($dest) ? (int) filesize($dest) : 0;
	if (!choosology_make_image_thumb($src, $dest, $mime)) {
		fwrite(STDERR, "fail #$id could not resize $fn ($mime)\n");
		$fail++;
		continue;
	}
	clearstatcache(true, $dest);
	$after = is_file($dest) ? (int) filesize($dest) : 0;
	$dim = @getimagesize($dest);
	$wh = $dim ? ($dim[0] . 'x' . $dim[1]) : '?';
	echo "ok #$id $fn -> $wh (" . $before . 'B -> ' . $after . "B)\n";
	$ok++;
}

echo "Done. resized=$ok skipped=$skip failed=$fail\n";
exit($fail > 0 ? 2 : 0);
