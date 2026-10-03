<?php
/**
 * Shared Lite layout helpers.
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
	if (is_readable(dirname(__DIR__) . '/lib/ending-helpers.php')) {
		require_once dirname(__DIR__) . '/lib/ending-helpers.php';
	}
	global $db;
	if (!($db instanceof mysqli)) {
		http_response_code(503);
		die('Database unavailable.');
	}
	choosology_ui_mode_ensure_schema($db);
}

/**
 * @param array{title?:string,active?:string} $opts
 */
function choosology_lite_header(array $opts = array()): void
{
	$title = (string) ($opts['title'] ?? 'Choosology Lite');
	$active = (string) ($opts['active'] ?? '');
	$pageTitle = $title === 'Choosology Lite' ? $title : ($title . ' — Choosology Lite');
	$css = htmlspecialchars(choosology_site_url('lite/style.css'), ENT_QUOTES, 'UTF-8');
	$home = htmlspecialchars(choosology_lite_url('index.php'), ENT_QUOTES, 'UTF-8');
	$browse = htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8');
	$login = htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8');
	$switchClassic = htmlspecialchars(choosology_lite_url('switch.php?to=classic'), ENT_QUOTES, 'UTF-8');
	$user = !empty($_SESSION['user']) ? (string) $_SESSION['user'] : '';

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
	echo '<div class="lite-titlebar"><span>Choosology Lite — Lab Terminal</span><span class="lite-titlebar-sys" aria-hidden="true">_ □ ×</span></div>' . "\n";
	echo '<div class="lite-inner">' . "\n";
	echo '<div class="lite-brand-row">' . "\n";
	echo '<div><p class="lite-brand">CHOOSOLOGY LITE</p>';
	echo '<p class="lite-tag" title="Representative of the authentic experience of the original cohort of Choosologists from 1986.">';
	echo 'Authentic lab terminal · cohort of 1986</p></div>' . "\n";
	echo '<p class="lite-mode-link"><a href="' . $switchClassic . '" title="Full Classic Choosology interface">Classic view</a></p>' . "\n";
	echo '</div>' . "\n";
	echo '<nav class="lite-menubar" aria-label="Lite">' . "\n";
	echo $nav('home', $home, 'Home') . "\n";
	echo $nav('browse', $browse, 'Browse') . "\n";
	if ($user !== '') {
		echo $nav('login', $login, 'Sign out') . "\n";
	} else {
		echo $nav('login', $login, 'Sign in') . "\n";
	}
	echo '</nav>' . "\n";
	if ($user !== '') {
		echo '<p class="lite-userbox">Signed in as <strong>' . htmlspecialchars($user, ENT_QUOTES, 'UTF-8') . '</strong>';
		echo ' · My Stuff is available in <a href="' . htmlspecialchars(choosology_classic_url('mystuff'), ENT_QUOTES, 'UTF-8') . '">Classic</a>';
		echo '</p>' . "\n";
	}
}

function choosology_lite_footer(): void
{
	echo '<p class="lite-foot">Lite keeps pages plain and images small. Prefer Classic chrome? Use Classic view above.</p>' . "\n";
	echo '</div></div></div>' . "\n";
	echo '</body></html>' . "\n";
}
