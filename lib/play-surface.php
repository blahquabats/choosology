<?php
/**
 * Shared play-view rules: when Start over is useful, and the public author profile.
 */

require_once __DIR__ . '/choosology-core.php';
require_once __DIR__ . '/account-helpers.php';

/**
 * Start over only helps after the reader has left the opening screen.
 */
function choosology_play_show_start_over($screenId, $beginId): bool
{
	$screenId = trim((string) $screenId);
	$beginId = trim((string) $beginId);
	if ($screenId === '' || $beginId === '') {
		return false;
	}
	return $screenId !== $beginId;
}

/**
 * Public profile URL. Classic is a hash route; Lite is its own page.
 */
function choosology_author_profile_href(string $name, string $surface): string
{
	$encoded = rawurlencode(trim($name));
	if ($surface === 'lite') {
		if (!function_exists('choosology_lite_url')) {
			require_once __DIR__ . '/lite-helpers.php';
		}
		return choosology_lite_url('author.php?user=' . $encoded);
	}
	return '#/author/' . $encoded;
}

/**
 * Author name as a link, or plain text when there is no account to show (draft guest).
 */
function choosology_author_byline_html(string $name, string $surface): string
{
	$name = trim($name);
	$safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
	if ($name === '' || strcasecmp($name, 'guest') === 0) {
		return $safe !== '' ? $safe : 'guest';
	}
	$href = htmlspecialchars(choosology_author_profile_href($name, $surface), ENT_QUOTES, 'UTF-8');
	$class = $surface === 'classic' ? ' class="view-sidebar-author"' : '';
	return '<a' . $class . ' href="' . $href . '">' . $safe . '</a>';
}

/**
 * Public profile for a lab account. Null when that name has no account.
 * Counts only experiments a logged-out reader can find (public, not held).
 *
 * @return array{
 *   name:string,
 *   about_html:string,
 *   pic_id:int,
 *   joined:string,
 *   published_count:int,
 *   play_starts:int,
 *   rating_avg:?float,
 *   rating_count:int,
 *   experiments:list<array{id:int,title:string,plays:int,rating:string}>
 * }|null
 */
function choosology_author_public_profile(mysqli $db, string $name): ?array
{
	$name = trim($name);
	if ($name === '' || strlen($name) > 45) {
		return null;
	}
	$esc = mysqli_real_escape_string($db, $name);
	$userRes = mysqli_query(
		$db,
		"SELECT name, about, pic, joined FROM users WHERE name='$esc' LIMIT 1"
	);
	$user = $userRes ? mysqli_fetch_assoc($userRes) : null;
	if (!$user) {
		return null;
	}
	$storedName = (string) ($user['name'] ?? $name);

	if (!function_exists('choosology_adventure_discovery_sql')) {
		require_once __DIR__ . '/adventure-review-helpers.php';
	}
	if (!function_exists('choosology_clipboard_ensure_schema')) {
		require_once __DIR__ . '/clipboard-helpers.php';
	}
	choosology_clipboard_ensure_schema($db);
	$discovery = choosology_adventure_discovery_sql('a', false);
	$escStored = mysqli_real_escape_string($db, $storedName);

	$stats = array('n' => 0, 'plays' => 0);
	$statsRes = mysqli_query(
		$db,
		"SELECT COUNT(*) AS n, COALESCE(SUM(a.play_starts),0) AS plays
		 FROM advs a
		 WHERE a.user='$escStored' AND $discovery"
	);
	if ($statsRes && ($row = mysqli_fetch_assoc($statsRes))) {
		$stats['n'] = (int) ($row['n'] ?? 0);
		$stats['plays'] = (int) ($row['plays'] ?? 0);
	}

	$ratingAvg = null;
	$ratingCount = 0;
	$rateRes = mysqli_query(
		$db,
		"SELECT COUNT(r.rating) AS c, AVG(r.rating) AS a
		 FROM ratings r
		 INNER JOIN advs a ON a.id = r.adv
		 WHERE a.user='$escStored' AND $discovery"
	);
	if ($rateRes && ($row = mysqli_fetch_assoc($rateRes))) {
		$ratingCount = (int) ($row['c'] ?? 0);
		if ($ratingCount > 0 && $row['a'] !== null) {
			$ratingAvg = (float) $row['a'];
		}
	}

	$experiments = array();
	$listRes = mysqli_query(
		$db,
		"SELECT a.id, a.title, a.play_starts, a.rating
		 FROM advs a
		 WHERE a.user='$escStored' AND $discovery
		 ORDER BY COALESCE(a.published, a.edited) DESC, a.id DESC
		 LIMIT 40"
	);
	if ($listRes) {
		while ($row = mysqli_fetch_assoc($listRes)) {
			$title = trim(decode((string) ($row['title'] ?? ''), 1));
			$experiments[] = array(
				'id' => (int) ($row['id'] ?? 0),
				'title' => $title,
				'plays' => (int) ($row['play_starts'] ?? 0),
				'rating' => (string) ($row['rating'] ?? 'NA'),
			);
		}
	}

	$about = choosology_account_sanitize_about((string) ($user['about'] ?? ''));
	if ($about !== '' && !preg_match('/<(p|br|ul|ol|li|blockquote)\b/i', $about)) {
		$about = nl2br($about, false);
	}

	$joined = (string) ($user['joined'] ?? '');
	$joinedTs = ($joined !== '' && $joined !== '0000-00-00 00:00:00') ? strtotime($joined) : false;
	$joinedLabel = $joinedTs ? date('M j, Y', $joinedTs) : '';

	return array(
		'name' => $storedName,
		'about_html' => $about,
		'pic_id' => (int) ($user['pic'] ?? 0),
		'joined' => $joinedLabel,
		'published_count' => $stats['n'],
		'play_starts' => $stats['plays'],
		'rating_avg' => $ratingAvg,
		'rating_count' => $ratingCount,
		'experiments' => $experiments,
	);
}

function choosology_author_stats_line(array $profile): string
{
	$n = (int) ($profile['published_count'] ?? 0);
	$plays = (int) ($profile['play_starts'] ?? 0);
	$line = $n . ' published experiment' . ($n === 1 ? '' : 's');
	$line .= ' · ' . $plays . ' play' . ($plays === 1 ? '' : 's');
	$count = (int) ($profile['rating_count'] ?? 0);
	if ($count > 0 && isset($profile['rating_avg']) && $profile['rating_avg'] !== null) {
		$line .= ' · average rating ' . number_format((float) $profile['rating_avg'], 1);
		$line .= ' from ' . $count . ' rating' . ($count === 1 ? '' : 's');
	} else {
		$line .= ' · no ratings yet';
	}
	return $line;
}
