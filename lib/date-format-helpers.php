<?php
/**
 * User-configurable date display: mm/dd/yyyy (default) or dd/mm/yyyy.
 * Preference: account column > cookie > mdy.
 */

const CHOOSOLOGY_DATE_FMT_COOKIE = 'choosology_date_fmt';
const CHOOSOLOGY_DATE_FMT_MDY = 'mdy';
const CHOOSOLOGY_DATE_FMT_DMY = 'dmy';

function choosology_date_format_normalize(?string $fmt): string
{
	$fmt = strtolower(trim((string) $fmt));
	if ($fmt === 'dmy' || $fmt === 'dd/mm/yyyy' || $fmt === 'd/m/y') {
		return CHOOSOLOGY_DATE_FMT_DMY;
	}
	return CHOOSOLOGY_DATE_FMT_MDY;
}

function choosology_date_format_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	try {
		$chk = mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'date_format'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			return;
		}
		mysqli_query(
			$db,
			"ALTER TABLE users ADD COLUMN date_format varchar(8) NOT NULL DEFAULT 'mdy'"
		);
	} catch (Throwable $e) {
		$done = false;
	}
}

function choosology_date_format_cookie_get(): string
{
	if (!isset($_COOKIE[CHOOSOLOGY_DATE_FMT_COOKIE])) {
		return '';
	}
	return choosology_date_format_normalize((string) $_COOKIE[CHOOSOLOGY_DATE_FMT_COOKIE]);
}

function choosology_date_format_cookie_set(string $fmt): void
{
	$fmt = choosology_date_format_normalize($fmt);
	$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	setcookie(CHOOSOLOGY_DATE_FMT_COOKIE, $fmt, array(
		'expires' => time() + 60 * 60 * 24 * 365,
		'path' => '/',
		'secure' => $secure,
		'httponly' => false,
		'samesite' => 'Lax',
	));
	$_COOKIE[CHOOSOLOGY_DATE_FMT_COOKIE] = $fmt;
}

/**
 * Cookie / default only (no DB). Used to avoid recursion from account lookups.
 */
function choosology_date_format_anonymous(): string
{
	$cookie = choosology_date_format_cookie_get();
	return $cookie !== '' ? $cookie : CHOOSOLOGY_DATE_FMT_MDY;
}

/**
 * Date format for a named account (falls back to cookie/default — never re-enters preferred).
 */
function choosology_date_format_for_user(?mysqli $db, string $uname): string
{
	$uname = trim($uname);
	if ($db instanceof mysqli && $uname !== '') {
		choosology_date_format_ensure_schema($db);
		$esc = mysqli_real_escape_string($db, $uname);
		$r = @mysqli_query($db, "SELECT date_format FROM users WHERE name='$esc' LIMIT 1");
		if ($r && ($row = mysqli_fetch_assoc($r)) && isset($row['date_format']) && (string) $row['date_format'] !== '') {
			return choosology_date_format_normalize((string) $row['date_format']);
		}
	}
	return choosology_date_format_anonymous();
}

/**
 * Effective date format code: mdy | dmy.
 */
function choosology_date_format_preferred(?mysqli $db = null): string
{
	if (!($db instanceof mysqli) && isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
		$db = $GLOBALS['db'];
	}
	if ($db instanceof mysqli && !empty($_SESSION['user'])) {
		return choosology_date_format_for_user($db, (string) $_SESSION['user']);
	}
	return choosology_date_format_anonymous();
}

function choosology_date_format_save(?mysqli $db, string $fmt): void
{
	$fmt = choosology_date_format_normalize($fmt);
	choosology_date_format_cookie_set($fmt);
	if (!($db instanceof mysqli)) {
		if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
			$db = $GLOBALS['db'];
		} else {
			return;
		}
	}
	choosology_date_format_ensure_schema($db);
	if (!empty($_SESSION['user'])) {
		$esc = mysqli_real_escape_string($db, (string) $_SESSION['user']);
		@mysqli_query($db, "UPDATE users SET date_format='$fmt' WHERE name='$esc' LIMIT 1");
	}
}

/**
 * PHP date() pattern for the preferred format.
 * @param 'date'|'datetime'|'time' $kind
 */
function choosology_date_format_pattern(string $kind = 'date', ?string $fmt = null): string
{
	$code = $fmt !== null ? choosology_date_format_normalize($fmt) : choosology_date_format_preferred();
	$datePat = ($code === CHOOSOLOGY_DATE_FMT_DMY) ? 'd/m/Y' : 'm/d/Y';
	switch ($kind) {
		case 'time':
			return 'g:ia';
		case 'datetime':
			return 'g:ia \o\n ' . $datePat;
		case 'date':
		default:
			return $datePat;
	}
}

/**
 * Human label for UI (mm/dd/yyyy or dd/mm/yyyy).
 */
function choosology_date_format_label(?string $fmt = null): string
{
	$code = $fmt !== null ? choosology_date_format_normalize($fmt) : choosology_date_format_preferred();
	return ($code === CHOOSOLOGY_DATE_FMT_DMY) ? 'dd/mm/yyyy' : 'mm/dd/yyyy';
}

/**
 * Format a DB datetime / unix timestamp using the user preference.
 * @param 'date'|'datetime'|'time' $kind
 */
function choosology_format_user_date($date, string $kind = 'date', ?string $fmt = null): string
{
	if (is_int($date) || (is_string($date) && ctype_digit($date))) {
		$phptime = (int) $date;
	} else {
		$phptime = strtotime((string) $date);
	}
	if ($phptime === false || $phptime <= 0) {
		return '';
	}
	return date(choosology_date_format_pattern($kind, $fmt), $phptime);
}
