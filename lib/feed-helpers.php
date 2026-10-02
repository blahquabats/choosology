<?php
/**
 * Pure lab-feed date formatting helpers (safe without connect.php).
 */

/**
 * Format a DB datetime for feed display (e.g. "Jun 3, 2020").
 */
function choosology_feed_date_label(string $stampRaw): string
{
	$t = trim($stampRaw);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($t);
	if ($ts <= 0) {
		return '';
	}
	return date('M j, Y', $ts);
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
