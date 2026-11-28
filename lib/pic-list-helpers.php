<?php
/**
 * Picture list / picker filter helpers (safe without connect.php).
 */

/**
 * @return list<string>
 */
function choosology_listuserpics_tags_from_cat(string $cat): array
{
	$cat = trim($cat);
	if ($cat === '') {
		return array();
	}
	if (strpos($cat, ',') !== false) {
		$parts = preg_split('/\s*,\s*/', $cat, -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($parts)) {
			return array();
		}
		$out = array();
		foreach ($parts as $p) {
			$t = trim((string) $p);
			if ($t !== '') {
				$out[] = $t;
			}
		}
		return array_values(array_unique($out));
	}
	return array($cat);
}

function choosology_listuserpics_row_matches_q(array $pic, string $qNorm): bool
{
	if ($qNorm === '') {
		return true;
	}
	$blob = strtolower(
		(string) ($pic['imagename'] ?? '') . "\n" .
		(string) ($pic['filename'] ?? '') . "\n" .
		(string) ($pic['cat'] ?? '')
	);
	return strpos($blob, $qNorm) !== false;
}

function choosology_listuserpics_row_matches_tag(array $pic, string $tagPick): bool
{
	if ($tagPick === '') {
		return true;
	}
	$want = strtolower($tagPick);
	$rowTags = choosology_listuserpics_tags_from_cat((string) ($pic['cat'] ?? ''));
	foreach ($rowTags as $t) {
		if (strtolower($t) === $want) {
			return true;
		}
	}
	return false;
}

/**
 * @return list<string>
 */
/**
 * Resolve a catalog: filename to a file under the pics root or images/about.
 * Returns null for anything that would leave those directories.
 */
function choosology_catalog_resolve_file(string $filename, string $picsRoot, string $aboutDir): ?string
{
	if (strncmp($filename, 'catalog:', 8) !== 0) {
		return null;
	}
	$rel = str_replace('\\', '/', substr($filename, 8));
	if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, "\0") !== false) {
		return null;
	}
	if (strncmp($rel, 'pics/', 5) === 0) {
		$base = realpath($picsRoot);
		$tail = substr($rel, 5);
	} elseif (strncmp($rel, 'about/', 6) === 0) {
		$base = realpath($aboutDir);
		$tail = substr($rel, 6);
	} else {
		return null;
	}
	if ($base === false || $tail === '' || strpos($tail, '..') !== false) {
		return null;
	}
	$full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tail);
	$real = realpath($full);
	if ($real === false || !is_file($real)) {
		return null;
	}
	$baseNorm = rtrim(str_replace('\\', '/', $base), '/');
	$realNorm = str_replace('\\', '/', $real);
	if ($realNorm !== $baseNorm && strpos($realNorm, $baseNorm . '/') !== 0) {
		return null;
	}
	return $real;
}

function choosology_account_pic_tags(string $cat): array
{
	$cat = trim($cat);
	if ($cat === '') {
		return array();
	}
	$parts = preg_split('/\s*,\s*/', $cat, -1, PREG_SPLIT_NO_EMPTY);
	if (!is_array($parts) || count($parts) === 0) {
		return array($cat);
	}
	return array_values(array_unique(array_map('trim', $parts)));
}
