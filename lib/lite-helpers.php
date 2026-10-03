<?php
/**
 * Choosology Lite: preference plumbing, URLs, ASCII structure, light ending panel.
 */

require_once __DIR__ . '/choosology-core.php';

const CHOOSOLOGY_UI_COOKIE = 'choosology_ui';
const CHOOSOLOGY_UI_SUGGEST_DISMISS = 'choosology_ui_suggest_dismiss';

function choosology_ui_mode_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	try {
		$chk = mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'ui_mode'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			return;
		}
		mysqli_query($db, "ALTER TABLE users ADD COLUMN ui_mode varchar(16) NOT NULL DEFAULT 'classic'");
	} catch (Throwable $e) {
		$done = false;
	}
}

function choosology_ui_normalize(?string $mode): string
{
	$mode = strtolower(trim((string) $mode));
	return ($mode === 'lite') ? 'lite' : 'classic';
}

function choosology_is_lite_host(): bool
{
	$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
	$host = preg_replace('/:\d+$/', '', $host);
	if ($host === '') {
		return false;
	}
	return (bool) preg_match('/^lite(\.|$)/', $host);
}

function choosology_is_lite_path(): bool
{
	$sn = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
	return strpos($sn, '/lite/') !== false || preg_match('#/lite$#', rtrim($sn, '/')) === 1;
}

/** True when this request should render Lite chrome. */
function choosology_is_lite_request(): bool
{
	return choosology_is_lite_host() || choosology_is_lite_path();
}

function choosology_ui_cookie_get(): string
{
	if (!isset($_COOKIE[CHOOSOLOGY_UI_COOKIE])) {
		return '';
	}
	return choosology_ui_normalize((string) $_COOKIE[CHOOSOLOGY_UI_COOKIE]);
}

function choosology_ui_cookie_set(string $mode): void
{
	$mode = choosology_ui_normalize($mode);
	$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	setcookie(CHOOSOLOGY_UI_COOKIE, $mode, array(
		'expires' => time() + 60 * 60 * 24 * 365,
		'path' => '/',
		'secure' => $secure,
		'httponly' => false,
		'samesite' => 'Lax',
	));
	$_COOKIE[CHOOSOLOGY_UI_COOKIE] = $mode;
}

/**
 * Effective preferred mode: account setting > cookie > classic.
 * Defaults always classic for new users / no preference.
 */
function choosology_ui_preferred_mode(mysqli $db): string
{
	choosology_ui_mode_ensure_schema($db);
	if (!empty($_SESSION['user'])) {
		$esc = mysqli_real_escape_string($db, (string) $_SESSION['user']);
		$r = @mysqli_query($db, "SELECT ui_mode FROM users WHERE name='$esc' LIMIT 1");
		if ($r && ($row = mysqli_fetch_assoc($r)) && isset($row['ui_mode']) && (string) $row['ui_mode'] !== '') {
			return choosology_ui_normalize((string) $row['ui_mode']);
		}
	}
	$cookie = choosology_ui_cookie_get();
	return $cookie !== '' ? $cookie : 'classic';
}

function choosology_ui_save_preference(mysqli $db, string $mode): void
{
	$mode = choosology_ui_normalize($mode);
	choosology_ui_mode_ensure_schema($db);
	choosology_ui_cookie_set($mode);
	if (!empty($_SESSION['user'])) {
		$esc = mysqli_real_escape_string($db, (string) $_SESSION['user']);
		@mysqli_query($db, "UPDATE users SET ui_mode='$mode' WHERE name='$esc' LIMIT 1");
	}
}

/**
 * Absolute path URL into Lite.
 * Always under /lite/ so lite.choosology.com can share the same app root as Classic.
 */
function choosology_lite_url(string $path = ''): string
{
	$path = ltrim(str_replace('\\', '/', $path), '/');
	if ($path === '' || $path === 'index.php') {
		return choosology_site_url('lite/index.php');
	}
	if (strpos($path, 'lite/') === 0) {
		return choosology_site_url($path);
	}
	return choosology_site_url('lite/' . $path);
}

/** Classic SPA entry, optionally with hash route. */
function choosology_classic_url(string $hash = ''): string
{
	$base = choosology_site_url('index.php');
	$hash = ltrim($hash, '#');
	if ($hash === '') {
		return $base;
	}
	return $base . '#/' . ltrim($hash, '/');
}

/**
 * Rewrite img tags in adventure HTML for Lite: force max dimensions, prefer lazy loading.
 */
function choosology_lite_constrain_html_images(string $html): string
{
	if ($html === '' || stripos($html, '<img') === false) {
		return $html;
	}
	return preg_replace_callback(
		'/<img\b([^>]*)>/i',
		static function (array $m): string {
			$attrs = $m[1];
			$attrs = preg_replace('/\s(width|height)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);
			if (!preg_match('/\bstyle\s*=/i', $attrs)) {
				$attrs .= ' style="max-width:280px;max-height:200px;height:auto;"';
			} else {
				$attrs = preg_replace(
					'/\sstyle\s*=\s*("|\')(.*?)\1/i',
					' style=$1$2;max-width:280px;max-height:200px;height:auto;$1',
					$attrs,
					1
				);
			}
			if (!preg_match('/\bloading\s*=/i', $attrs)) {
				$attrs .= ' loading="lazy"';
			}
			return '<img' . $attrs . '>';
		},
		$html
	) ?? $html;
}

/**
 * Build a read-only ASCII map of adventure structure (active screens + choices).
 *
 * @return string plain text (escape on output)
 */
function choosology_lite_ascii_structure(mysqli $db, int $advid): string
{
	if ($advid < 1) {
		return '(no experiment)';
	}
	$esc = (int) $advid;
	$advR = mysqli_query($db, "SELECT `begin`, title FROM advs WHERE id=$esc LIMIT 1");
	$adv = $advR ? mysqli_fetch_assoc($advR) : null;
	if (!$adv) {
		return '(experiment not found)';
	}
	$begin = (int) ($adv['begin'] ?? 0);
	$sr = mysqli_query(
		$db,
		"SELECT id, name, title, choice1, choice2, choice3, choice4, choice5, choice6, choice7, choice8
		 FROM advscreens
		 WHERE advused='$esc' AND IFNULL(deleted,0) NOT IN (1,'1')
		 ORDER BY id ASC"
	);
	if (!$sr) {
		return '(unable to load screens)';
	}
	$screens = array();
	while ($row = mysqli_fetch_assoc($sr)) {
		$screens[(int) $row['id']] = $row;
	}
	if (!$screens) {
		return '(no screens)';
	}

	$labelOf = static function (array $s): string {
		$name = trim((string) ($s['name'] ?? ''));
		if ($name === '') {
			$name = trim((string) ($s['title'] ?? ''));
		}
		if ($name === '') {
			$name = 'Screen ' . (int) $s['id'];
		}
		if (strlen($name) > 28) {
			$name = substr($name, 0, 27) . '…';
		}
		return $name;
	};

	$delim = choosology_choice_delimiter();
	$lines = array();
	$lines[] = 'Experiment #' . $advid;
	$lines[] = '';

	foreach ($screens as $id => $s) {
		$mark = ($id === $begin) ? ' *' : '';
		$ending = choosology_screen_is_ending($s, $begin) ? ' [end]' : '';
		$lines[] = '[' . $labelOf($s) . ']' . $mark . $ending;
		$any = false;
		for ($i = 1; $i <= 8; $i++) {
			$raw = (string) ($s['choice' . $i] ?? '');
			if ($raw === '') {
				continue;
			}
			$parts = explode($delim, $raw);
			if (empty($parts[0]) || empty($parts[1])) {
				continue;
			}
			$tid = (int) $parts[1];
			if ($tid === $begin) {
				continue;
			}
			$any = true;
			$clabel = trim(html_entity_decode(strip_tags($parts[0])));
			if (strlen($clabel) > 24) {
				$clabel = substr($clabel, 0, 23) . '…';
			}
			$target = isset($screens[$tid]) ? $labelOf($screens[$tid]) : ('#' . $tid);
			$lines[] = '  +-- ' . $clabel . ' --> [' . $target . ']';
		}
		if (!$any && !choosology_screen_is_ending($s, $begin)) {
			$lines[] = '  (no outgoing choices)';
		}
		$lines[] = '';
	}
	$lines[] = '* = begin';
	return implode("\n", $lines);
}

/**
 * Lite-friendly terminal outcome (no Classic rating/comment JS widgets).
 */
function choosology_lite_ending_panel_html(mysqli $db, int $advid, int $screenid): string
{
	if (function_exists('choosology_record_ending_find')) {
		$progress = choosology_record_ending_find($db, $advid, $screenid);
		$found = (int) ($progress['found'] ?? 0);
		$more = !empty($progress['more']);
	} else {
		$found = 1;
		$more = false;
	}
	$foundLabel = $found === 1
		? 'You have catalogued <strong>1</strong> end screen in this experiment.'
		: 'You have catalogued <strong>' . $found . '</strong> end screens in this experiment.';
	$moreHtml = $more
		? '<p>Lab note: additional terminal outcomes remain unclassified.</p>'
		: '<p>Lab note: every known terminal outcome in this experiment has been logged (or none remain).</p>';

	$html = '<div class="lite-ending" role="status">';
	$html .= '<p class="lite-ending-eyebrow">Terminal outcome</p>';
	$html .= '<h3>You have reached an end</h3>';
	$html .= '<p>This path through the experiment terminates here.</p>';
	$html .= '<p>' . $foundLabel . '</p>';
	$html .= $moreHtml;
	$html .= '<p class="lite-muted">Rating and comments are available in <a href="' .
		htmlspecialchars(choosology_classic_url('view/' . $advid), ENT_QUOTES, 'UTF-8') .
		'">Classic view</a>.</p>';
	$html .= '</div>';
	return $html;
}
