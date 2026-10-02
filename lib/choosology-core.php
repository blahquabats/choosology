<?php
/**
 * Pure / mostly-pure Choosology helpers (safe to load without connect.php).
 */

/**
 * Legacy password digest: md5("cYo" . password).
 */
function choosology_legacy_password_hash(string $password): string
{
	return md5('cYo' . $password);
}

/**
 * Basic email validation for signup / legacy registerCheck.
 */
function checkEmail($email): bool
{
	$email = trim((string) $email);
	if ($email === '' || strlen($email) > 45) {
		return false;
	}
	return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function nicedatetime($date, $mode = 'datetime')
{
	$phptime = strtotime((string) $date);
	if ($phptime === false) {
		return '';
	}
	switch ($mode) {
		case 'date':
			return date('m/d/Y', $phptime);
		case 'time':
			return date('g:ia', $phptime);
		case 'datetime':
		default:
			return date('g:ia \o\n m/d/Y', $phptime);
	}
}

function decode($str, $strip = 0)
{
	$str = (string) ($str ?? '');
	$str = html_entity_decode($str);
	if ($strip) {
		$str = strip_tags($str);
	}
	return html_entity_decode($str);
}

/** Choice payload delimiter used in advscreens.choice1..8 ("label|Q-D-|targetId"). */
function choosology_choice_delimiter(): string
{
	return '|Q-D-|';
}

/**
 * True when a screen has no valid outgoing choices (terminal / ending node).
 * Loops that only point back to the adventure begin are ignored (same as player).
 *
 * @param array<string,mixed> $screen
 */
function choosology_screen_is_ending(array $screen, $beginId): bool
{
	$begin = (string) $beginId;
	$delim = choosology_choice_delimiter();
	for ($i = 1; $i <= 8; $i++) {
		$raw = $screen['choice' . $i] ?? '';
		if ($raw === '' || $raw === null) {
			continue;
		}
		$parts = explode($delim, (string) $raw);
		if (empty($parts[0]) || empty($parts[1])) {
			continue;
		}
		if ((string) $parts[1] === $begin) {
			continue;
		}
		return false;
	}
	return true;
}

/**
 * Undo connect.php's htmlspecialchars(mysqli_real_escape_string(...)) mutation of POST/GET strings.
 */
function choosology_undo_connect_string_mutation(string $value): string
{
	return stripslashes(htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML5));
}

/**
 * Whether an <img src> is allowed in saved screen HTML.
 */
function choosology_screen_html_img_src_allowed(string $src): bool
{
	$src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	if ($src === '') {
		return false;
	}
	if (stripos($src, 'data:') === 0 || stripos($src, 'javascript:') === 0) {
		return false;
	}
	$parts = parse_url($src);
	$path = isset($parts['path']) ? str_replace('\\', '/', (string) $parts['path']) : '';
	$query = isset($parts['query']) ? (string) $parts['query'] : '';

	if (preg_match('#(?:.*/)?ajax/pic\.php$#i', $path)) {
		parse_str($query, $qp);
		if (!empty($qp['id']) && is_numeric($qp['id']) && (int) $qp['id'] > 0) {
			return true;
		}
		return false;
	}

	$pathForExt = $path;
	if ($pathForExt === '' && (preg_match('#^https?://#i', $src) || strncmp($src, '//', 2) === 0)) {
		$abs = strncmp($src, '//', 2) === 0 ? 'https:' . $src : $src;
		$pathForExt = (string) (parse_url($abs, PHP_URL_PATH) ?? '');
	}
	if ($pathForExt === '' && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $src)) {
		$pathForExt = preg_replace('/[?#].*$/', '', str_replace('\\', '/', $src));
	}
	if ($pathForExt === '') {
		return false;
	}
	return (bool) preg_match('/\.(jpe?g|png|gif|webp|svg|avif|bmp|tiff?)(?:$|[?#])/i', $pathForExt);
}

/** Remove <img> tags whose src fails {@see choosology_screen_html_img_src_allowed}. */
function choosology_sanitize_screen_html_images(string $html): string
{
	return (string) preg_replace_callback('/<img\b[^>]*>/i', function (array $m): string {
		$tag = $m[0];
		if (preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $tag, $sm)) {
			$src = $sm[1];
		} elseif (preg_match("/\bsrc\s*=\s*'([^']*)'/i", $tag, $sm)) {
			$src = $sm[1];
		} else {
			return '';
		}
		return choosology_screen_html_img_src_allowed($src) ? $tag : '';
	}, $html);
}

function makeStars($rating, $tiny = 0)
{
	if ($rating == 0) {
		$ratetext = 'No rating yet';
	} else {
		$ratetext = "$rating stars";
	}
	$output = " <div class='tinystarsholder' title='$ratetext'>";
	if ($rating == 0) {
		return $output . '<span class="tinystarsholder-norating">not rated</span></div>';
	}
	for ($x = 1.0; $x <= 5.0; $x = $x + 1.0) {
		if ($rating >= $x) {
			$w = 100;
		} else {
			$y = $rating - $x + 1;
			$w = $y * 100;
		}
		$output .= "<div class='star'><div class='avgstar' style='width: $w%'><img src=\"images/icons/ratings/greenstar-o-sm.png\"></div>
      <img src=\"images/icons/ratings/star-n-sm.png\" ></div>";
	}
	$output .= '</div>';
	return $output;
}
