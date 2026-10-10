<?php
/**
 * Shared Lite (Choosology 3.1) layout helpers.
 * Call choosology_lite_bootstrap() first from each page.
 */

function choosology_lite_bootstrap(): void
{
	/*
	 * connect.php assigns $db in the including scope. When required from this
	 * function we must promote it to $GLOBALS so the rest of the request sees it.
	 */
	if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof mysqli)) {
		require_once dirname(__DIR__) . '/connect.php';
		if (isset($db) && $db instanceof mysqli) {
			$GLOBALS['db'] = $db;
		}
	}
	require_once dirname(__DIR__) . '/auxfuncs.php';
	require_once dirname(__DIR__) . '/lib/lite-helpers.php';
	require_once dirname(__DIR__) . '/lib/date-format-helpers.php';
	if (is_readable(dirname(__DIR__) . '/lib/ending-helpers.php')) {
		require_once dirname(__DIR__) . '/lib/ending-helpers.php';
	}
	global $db;
	if (!($db instanceof mysqli)) {
		http_response_code(503);
		die('Database unavailable.');
	}
	choosology_ui_mode_ensure_schema($db);
	choosology_date_format_ensure_schema($db);
}

/**
 * @param array{title?:string,active?:string} $opts
 */
function choosology_lite_header(array $opts = array()): void
{
	$title = (string) ($opts['title'] ?? 'Choosology 3.1');
	$active = (string) ($opts['active'] ?? '');
	$pageTitle = $title === 'Choosology 3.1' ? $title : ($title . ' — Choosology 3.1');
	$css = htmlspecialchars(choosology_site_url('lite/style.css'), ENT_QUOTES, 'UTF-8');
	$home = htmlspecialchars(choosology_lite_url('index.php'), ENT_QUOTES, 'UTF-8');
	$browse = htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8');
	$news = htmlspecialchars(choosology_lite_url('news.php'), ENT_QUOTES, 'UTF-8');
	$ledger = htmlspecialchars(choosology_lite_url('ledger.php'), ENT_QUOTES, 'UTF-8');
	$login = htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8');
	$switchClassic = htmlspecialchars(choosology_lite_url('switch.php?to=classic'), ENT_QUOTES, 'UTF-8');
	$user = !empty($_SESSION['user']) ? (string) $_SESSION['user'] : '';
	$scripFmt = '';
	if ($user !== '') {
		if (!function_exists('choosology_datascrip_format_html')) {
			require_once dirname(__DIR__) . '/lib/datascrip-helpers.php';
		}
		global $db;
		if ($db instanceof mysqli) {
			$scripFmt = choosology_datascrip_format_html(choosology_datascrip_balance($db, $user));
		}
	}

	$nav = static function (string $key, string $href, string $label) use ($active): string {
		$cur = ($active === $key) ? ' aria-current="page"' : '';
		return '<a href="' . $href . '"' . $cur . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
	};

	echo '<!DOCTYPE html>' . "\n";
	echo '<html lang="en">' . "\n";
	echo '<head>' . "\n";
	echo '<meta charset="utf-8">' . "\n";
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
	echo '<title>' . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
	echo '<link rel="stylesheet" href="' . $css . '">' . "\n";
	echo '</head>' . "\n";
	echo '<body>' . "\n";
	echo '<div class="lite-wrap">' . "\n";
	echo '<div class="lite-win">' . "\n";
	echo '<div class="lite-titlebar"><span>Choosology 3.1 — Lab Terminal</span><span class="lite-titlebar-sys" aria-hidden="true">_ □ ×</span></div>' . "\n";
	echo '<div class="lite-inner">' . "\n";
	echo '<div class="lite-brand-row">' . "\n";
	echo '<div><p class="lite-brand">CHOOSOLOGY 3.1</p>';
	echo '<p class="lite-tag">Proprietary narrative application © 1986</p></div>' . "\n";
	echo '<p class="lite-mode-link"><a href="' . $switchClassic . '" title="Full Classic Choosology interface">Classic view</a></p>' . "\n";
	echo '</div>' . "\n";
	echo '<nav class="lite-menubar" aria-label="Choosology 3.1">' . "\n";
	echo $nav('home', $home, 'Home') . "\n";
	echo $nav('news', $news, 'News') . "\n";
	echo $nav('browse', $browse, 'Browse') . "\n";
	if ($user !== '') {
		echo $nav('ledger', $ledger, 'Ledger') . "\n";
		echo $nav('login', $login, 'Sign out') . "\n";
	} else {
		echo $nav('login', $login, 'Sign in') . "\n";
	}
	echo '</nav>' . "\n";
	if ($user !== '') {
		echo '<p class="lite-userbox">Signed in as <strong>' . htmlspecialchars($user, ENT_QUOTES, 'UTF-8') . '</strong>';
		if ($scripFmt !== '') {
			echo ' <span class="lite-scrip" title="Balance">' . $scripFmt . '</span>';
		}
		echo '<br>My Office extras remain in <a href="' . htmlspecialchars(choosology_classic_url('mystuff'), ENT_QUOTES, 'UTF-8') . '">Classic</a>';
		echo '</p>' . "\n";
	}
}

function choosology_lite_footer(): void
{
	echo '</div></div></div>' . "\n";
	echo '</body></html>' . "\n";
}

/**
 * Compact date-format preference form for Lite pages.
 */
function choosology_lite_date_format_form(string $nextPath = 'ledger.php'): void
{
	global $db;
	$current = choosology_date_format_preferred($db instanceof mysqli ? $db : null);
	$action = htmlspecialchars(choosology_lite_url('prefs.php'), ENT_QUOTES, 'UTF-8');
	$next = htmlspecialchars($nextPath, ENT_QUOTES, 'UTF-8');
	$mdy = ($current === CHOOSOLOGY_DATE_FMT_MDY) ? ' checked' : '';
	$dmy = ($current === CHOOSOLOGY_DATE_FMT_DMY) ? ' checked' : '';
	echo '<fieldset class="lite-panel lite-prefs">' . "\n";
	echo '<legend>Date format</legend>' . "\n";
	echo '<form method="post" action="' . $action . '" class="lite-date-form">' . "\n";
	echo '<input type="hidden" name="next" value="' . $next . '">' . "\n";
	echo '<label class="lite-radio"><input type="radio" name="date_format" value="mdy"' . $mdy . '> mm/dd/yyyy</label> ';
	echo '<label class="lite-radio"><input type="radio" name="date_format" value="dmy"' . $dmy . '> dd/mm/yyyy</label> ';
	echo '<button type="submit" class="lite-btn">Save</button>' . "\n";
	echo '</form>' . "\n";
	echo '</fieldset>' . "\n";
}
