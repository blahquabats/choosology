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
