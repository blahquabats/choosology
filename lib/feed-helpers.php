<?php
/**
 * Pure lab-feed date formatting helpers (safe without connect.php).
 */

/**
 * Format a DB datetime for feed display (user date preference).
 */
function choosology_feed_date_label(string $stampRaw): string
{
	$t = trim($stampRaw);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	if (!function_exists('choosology_format_user_date')) {
		require_once __DIR__ . '/date-format-helpers.php';
	}
	return choosology_format_user_date($t, 'date');
}

/**
 * ISO-8601 for <time datetime>, or empty.
 */
function choosology_feed_date_iso(string $stampRaw): string
{
	$t = trim($stampRaw);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($t);
	if ($ts <= 0) {
		return '';
	}
	return date('c', $ts);
}
