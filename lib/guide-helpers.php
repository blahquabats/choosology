<?php
/**
 * Lab guide — feature-agnostic first-visit help.
 *
 * Pages emit a feature key. Interactions (data) decide whether a mascot speaks.
 * Mascot packs own portraits, theme tokens, and dialog. The engine does not.
 *
 * Phase 1 has no admin UI. Seed rows are inserted by choosology_guide_seed_defaults().
 */

const CHOOSOLOGY_GUIDE_COOKIE = 'choosology_guide';

/**
 * Events the app can emit. Admins (later) attach interactions to these keys.
 *
 * @return array<string, array{label:string}>
 */
function choosology_guide_event_registry(): array
{
	return array(
		'feature_first_seen' => array(
			'label' => 'First time a feature surface opens',
		),
	);
}

/**
 * Feature keys pages may signal. Label is interpolated into mascot lines as {feature}.
 *
 * @return array<string, array{label:string, surfaces:list<string>}>
 */
function choosology_guide_feature_registry(): array
{
	return array(
		'graph_editor' => array(
			'label' => 'the graph editor',
			'surfaces' => array('classic'),
		),
		'ledger' => array(
			'label' => 'the Ledger',
			'surfaces' => array('classic', 'lite'),
		),
		'clipboard' => array(
			'label' => 'the clipboard',
			'surfaces' => array('classic'),
		),
		'view' => array(
			'label' => 'playing an experiment',
			'surfaces' => array('classic', 'lite'),
		),
	);
}

function choosology_guide_feature_label(string $feature): string
{
	$reg = choosology_guide_feature_registry();
	if (isset($reg[$feature]['label'])) {
		return (string) $reg[$feature]['label'];
	}
	return $feature;
}

function choosology_guide_feature_known(string $feature): bool
{
	$feature = strtolower(trim($feature));
	return $feature !== '' && isset(choosology_guide_feature_registry()[$feature]);
}

function choosology_guide_key_ok(string $key): bool
{
	return (bool) preg_match('/^[a-z0-9_]{1,64}$/', $key);
}

/**
 * @return list<array<string, mixed>>
 */
function choosology_guide_mascot_seed(): array
{
	return array(
		array(
			'key' => 'lab_guide',
			'display_name' => 'Lab Guide',
			'enabled' => 1,
			'is_default' => 1,
			'sort_order' => 0,
			'theme' => array(
				'accent' => '#c4a35a',
				'paper' => '#f4ecd8',
				'ink' => '#1c1a16',
			),
			'portraits' => array(
				'idle' => 'images/mascots/lab_guide/idle.png',
				'offer' => 'images/mascots/lab_guide/offer.png',
				'explain' => 'images/mascots/lab_guide/explain.png',
				'done' => 'images/mascots/lab_guide/done.png',
			),
			'lines' => array(
				'offer_help' => array('Looks like this is your first time with {feature}. Want a hand?'),
				'explain' => array('{body}'),
				'done' => array('That is the lot. You can keep going from here.'),
			),
			'choices' => array(
				'accept' => 'Show me',
				'decline' => "I'll manage",
				'later' => 'Later',
				'done' => 'Got it',
				'next' => 'Next',
			),
		),
	);
}

/**
 * @return list<array<string, mixed>>
 */
function choosology_guide_interaction_seed(): array
{
	return array(
		array(
			'key' => 'first_graph_editor',
			'title' => 'First visit to the graph editor',
			'event_key' => 'feature_first_seen',
			'feature_key' => 'graph_editor',
			'surface' => 'classic',
			'audience' => 'both',
			'mascot_key' => 'lab_guide',
			'frequency' => 'once',
			'priority' => 100,
			'enabled' => 1,
			'beats' => array(
				array(
					'id' => 'offer',
					'role' => 'offer_help',
					'pose' => 'offer',
					'body' => '',
					'choices' => array('accept', 'later', 'decline'),
				),
				array(
					'id' => 'explain',
					'role' => 'explain',
					'pose' => 'explain',
					'body' => 'Each box is a screen. Draw a choice from one screen to the next, then open a screen to write what the reader sees.',
					'choices' => array('done'),
				),
			),
		),
	);
}

function choosology_guide_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}

	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS guide_mascots (
			id int unsigned NOT NULL AUTO_INCREMENT,
			mascot_key varchar(64) NOT NULL,
			display_name varchar(80) NOT NULL,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			is_default tinyint(1) NOT NULL DEFAULT 0,
			theme_json text NOT NULL,
			lines_json mediumtext NOT NULL,
			portraits_json text NOT NULL,
			sort_order int NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY guide_mascots_key (mascot_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);
	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS guide_interactions (
			id int unsigned NOT NULL AUTO_INCREMENT,
			interaction_key varchar(64) NOT NULL,
			title varchar(120) NOT NULL,
			event_key varchar(64) NOT NULL,
			feature_key varchar(64) NOT NULL DEFAULT \'\',
			surface varchar(16) NOT NULL DEFAULT \'both\',
			audience varchar(16) NOT NULL DEFAULT \'both\',
			mascot_key varchar(64) NOT NULL DEFAULT \'\',
			frequency varchar(24) NOT NULL DEFAULT \'once\',
			priority int NOT NULL DEFAULT 100,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			beats_json mediumtext NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY guide_interactions_key (interaction_key),
			KEY guide_interactions_event (event_key, enabled)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);
	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS guide_seen (
			id int unsigned NOT NULL AUTO_INCREMENT,
			subject_key varchar(128) NOT NULL,
			feature_key varchar(64) NOT NULL,
			surface varchar(16) NOT NULL,
			first_seen_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY guide_seen_once (subject_key, feature_key, surface)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);
	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS guide_progress (
			id int unsigned NOT NULL AUTO_INCREMENT,
			subject_key varchar(128) NOT NULL,
			interaction_key varchar(64) NOT NULL,
			status varchar(24) NOT NULL,
			beat_id varchar(64) NOT NULL DEFAULT \'\',
			snooze_until datetime DEFAULT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY guide_progress_once (subject_key, interaction_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);

	try {
		$chk = mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'guide_enabled'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($db, 'ALTER TABLE users ADD COLUMN guide_enabled tinyint(1) NOT NULL DEFAULT 1');
		}
	} catch (Throwable $e) {
		/* users table may be absent in a partial install */
	}

	choosology_guide_seed_defaults($db);
	$done = true;
}

function choosology_guide_seed_defaults(mysqli $db): void
{
	foreach (choosology_guide_mascot_seed() as $mascot) {
		$key = (string) $mascot['key'];
		if (!choosology_guide_key_ok($key)) {
			continue;
		}
		$esc = mysqli_real_escape_string($db, $key);
		$r = mysqli_query($db, "SELECT id FROM guide_mascots WHERE mascot_key='$esc' LIMIT 1");
		if ($r && mysqli_fetch_assoc($r)) {
			continue;
		}
		$dialog = json_encode(
			array(
				'lines' => $mascot['lines'],
				'choices' => $mascot['choices'],
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		$theme = json_encode($mascot['theme'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$portraits = json_encode($mascot['portraits'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($dialog === false || $theme === false || $portraits === false) {
			continue;
		}
		$name = mysqli_real_escape_string($db, (string) $mascot['display_name']);
		$enabled = !empty($mascot['enabled']) ? 1 : 0;
		$isDefault = !empty($mascot['is_default']) ? 1 : 0;
		$sort = (int) ($mascot['sort_order'] ?? 0);
		@mysqli_query(
			$db,
			"INSERT INTO guide_mascots
				(mascot_key, display_name, enabled, is_default, theme_json, lines_json, portraits_json, sort_order)
			 VALUES
				('$esc', '$name', $enabled, $isDefault, '" . mysqli_real_escape_string($db, $theme) . "',
				 '" . mysqli_real_escape_string($db, $dialog) . "',
				 '" . mysqli_real_escape_string($db, $portraits) . "', $sort)"
		);
	}

	foreach (choosology_guide_interaction_seed() as $interaction) {
		$key = (string) $interaction['key'];
		if (!choosology_guide_key_ok($key)) {
			continue;
		}
		$esc = mysqli_real_escape_string($db, $key);
		$r = mysqli_query($db, "SELECT id FROM guide_interactions WHERE interaction_key='$esc' LIMIT 1");
		if ($r && mysqli_fetch_assoc($r)) {
			continue;
		}
		$beats = json_encode($interaction['beats'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($beats === false) {
			continue;
		}
		$title = mysqli_real_escape_string($db, (string) $interaction['title']);
		$event = mysqli_real_escape_string($db, (string) $interaction['event_key']);
		$feature = mysqli_real_escape_string($db, (string) ($interaction['feature_key'] ?? ''));
		$surface = mysqli_real_escape_string($db, choosology_guide_surface_normalize((string) $interaction['surface']));
		$audience = mysqli_real_escape_string($db, choosology_guide_audience_normalize((string) $interaction['audience']));
		$mascotKey = mysqli_real_escape_string($db, (string) ($interaction['mascot_key'] ?? ''));
		$frequency = mysqli_real_escape_string($db, (string) ($interaction['frequency'] ?? 'once'));
		$priority = (int) ($interaction['priority'] ?? 100);
		$enabled = !empty($interaction['enabled']) ? 1 : 0;
		@mysqli_query(
			$db,
			"INSERT INTO guide_interactions
				(interaction_key, title, event_key, feature_key, surface, audience, mascot_key, frequency, priority, enabled, beats_json)
			 VALUES
				('$esc', '$title', '$event', '$feature', '$surface', '$audience', '$mascotKey', '$frequency', $priority, $enabled,
				 '" . mysqli_real_escape_string($db, $beats) . "')"
		);
	}
}

function choosology_guide_surface_normalize(string $surface): string
{
	$surface = strtolower(trim($surface));
	if ($surface === 'lite' || $surface === 'both') {
		return $surface;
	}
	return 'classic';
}

function choosology_guide_audience_normalize(string $audience): string
{
	$audience = strtolower(trim($audience));
	if ($audience === 'logged_in' || $audience === 'anonymous') {
		return $audience;
	}
	return 'both';
}

function choosology_guide_flag_on($value): bool
{
	if (is_bool($value)) {
		return $value;
	}
	if (is_int($value) || is_float($value)) {
		return ((int) $value) !== 0;
	}
	$s = strtolower(trim((string) $value));
	if ($s === '' || $s === '1' || $s === 'on' || $s === 'true' || $s === 'yes') {
		return true;
	}
	return false;
}

function choosology_guide_cookie_enabled(): bool
{
	if (!isset($_COOKIE[CHOOSOLOGY_GUIDE_COOKIE])) {
		return true;
	}
	return choosology_guide_flag_on($_COOKIE[CHOOSOLOGY_GUIDE_COOKIE]);
}

function choosology_guide_cookie_set(bool $on): void
{
	$value = $on ? 'on' : 'off';
	$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	setcookie(CHOOSOLOGY_GUIDE_COOKIE, $value, array(
		'expires' => time() + 60 * 60 * 24 * 365,
		'path' => '/',
		'secure' => $secure,
		'httponly' => false,
		'samesite' => 'Lax',
	));
	$_COOKIE[CHOOSOLOGY_GUIDE_COOKIE] = $value;
}

function choosology_guide_enabled_for_user(?mysqli $db, string $uname): bool
{
	$uname = trim($uname);
	if ($db instanceof mysqli && $uname !== '') {
		choosology_guide_ensure_schema($db);
		$esc = mysqli_real_escape_string($db, $uname);
		$r = @mysqli_query($db, "SELECT guide_enabled FROM users WHERE name='$esc' LIMIT 1");
		if ($r && ($row = mysqli_fetch_assoc($r)) && array_key_exists('guide_enabled', $row)) {
			return choosology_guide_flag_on($row['guide_enabled']);
		}
	}
	return choosology_guide_cookie_enabled();
}

function choosology_guide_enabled(?mysqli $db = null): bool
{
	if (!($db instanceof mysqli) && isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
		$db = $GLOBALS['db'];
	}
	if ($db instanceof mysqli && !empty($_SESSION['user'])) {
		return choosology_guide_enabled_for_user($db, (string) $_SESSION['user']);
	}
	return choosology_guide_cookie_enabled();
}

function choosology_guide_set_enabled(?mysqli $db, bool $on): void
{
	choosology_guide_cookie_set($on);
	if (!($db instanceof mysqli)) {
		if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
			$db = $GLOBALS['db'];
		} else {
			return;
		}
	}
	choosology_guide_ensure_schema($db);
	if (!empty($_SESSION['user'])) {
		$esc = mysqli_real_escape_string($db, (string) $_SESSION['user']);
		$val = $on ? 1 : 0;
		@mysqli_query($db, "UPDATE users SET guide_enabled=$val WHERE name='$esc' LIMIT 1");
	}
}

function choosology_guide_subject_key(): string
{
	if (!empty($_SESSION['user'])) {
		$name = trim((string) $_SESSION['user']);
		if ($name !== '') {
			return 'u:' . $name;
		}
	}
	if (session_status() === PHP_SESSION_ACTIVE) {
		$sid = session_id();
		if (is_string($sid) && $sid !== '') {
			return 's:' . $sid;
		}
	}
	return 's:none';
}

function choosology_guide_snooze_seconds(): int
{
	return 86400;
}

/**
 * Keep {feature}, {body}, {name}, and {user}. Strip tags so dialog stays plain text.
 *
 * @param array<string, string> $vars
 */
function choosology_guide_interpolate(string $template, array $vars): string
{
	$repl = array();
	foreach (array('feature', 'body', 'name', 'user') as $key) {
		$repl['{' . $key . '}'] = (string) ($vars[$key] ?? '');
	}
	$out = strtr($template, $repl);
	$out = trim(strip_tags($out));
	return $out;
}

/**
 * @param array<string, mixed> $theme
 * @return array{accent:string, paper:string, ink:string}
 */
function choosology_guide_theme_tokens(array $theme): array
{
	$defaults = array(
		'accent' => '#c4a35a',
		'paper' => '#f4ecd8',
		'ink' => '#1c1a16',
	);
	$out = array();
	foreach ($defaults as $key => $fallback) {
		$raw = isset($theme[$key]) ? strtolower(trim((string) $theme[$key])) : '';
		if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $raw)) {
			$raw = $fallback;
		}
		$out[$key] = $raw;
	}
	return $out;
}

/**
 * @param array<string, mixed> $mascot
 * @param array<string, string> $vars
 */
function choosology_guide_resolve_line(array $mascot, string $role, string $body, array $vars): string
{
	$template = '';
	$lines = $mascot['lines'][$role] ?? null;
	if (is_array($lines)) {
		foreach ($lines as $line) {
			$line = trim((string) $line);
			if ($line !== '') {
				$template = $line;
				break;
			}
		}
	}
	if ($template === '' && trim($body) !== '') {
		$template = $body;
	}
	if ($template === '') {
		$template = 'Want a hand with {feature}?';
	}
	$vars['body'] = $body;
	return choosology_guide_interpolate($template, $vars);
}

/**
 * @param array<string, mixed> $mascot
 */
function choosology_guide_choice_label(array $mascot, string $choiceId): string
{
	$choices = $mascot['choices'] ?? array();
	if (is_array($choices) && isset($choices[$choiceId]) && trim((string) $choices[$choiceId]) !== '') {
		return trim((string) $choices[$choiceId]);
	}
	$fallback = array(
		'accept' => 'Show me',
		'decline' => 'No thanks',
		'later' => 'Later',
		'done' => 'Got it',
		'next' => 'Next',
	);
	return $fallback[$choiceId] ?? $choiceId;
}

function choosology_guide_choice_effect(string $choiceId): string
{
	switch ($choiceId) {
		case 'accept':
		case 'next':
		case 'done':
			return 'advance';
		case 'later':
			return 'snooze';
		case 'decline':
			return 'dismiss';
		default:
			return '';
	}
}

/**
 * @param list<array<string, mixed>> $beats
 * @return array<string, mixed>|null
 */
function choosology_guide_beat_by_id(array $beats, string $id): ?array
{
	if ($id !== '') {
		foreach ($beats as $beat) {
			if ((string) ($beat['id'] ?? '') === $id) {
				return $beat;
			}
		}
	}
	return isset($beats[0]) && is_array($beats[0]) ? $beats[0] : null;
}

/**
 * @param list<array<string, mixed>> $beats
 * @return array<string, mixed>|null
 */
function choosology_guide_next_beat(array $beats, string $id): ?array
{
	$index = null;
	foreach ($beats as $i => $beat) {
		if ((string) ($beat['id'] ?? '') === $id) {
			$index = $i;
			break;
		}
	}
	if ($index === null) {
		return isset($beats[0]) ? $beats[0] : null;
	}
	$next = $index + 1;
	return isset($beats[$next]) && is_array($beats[$next]) ? $beats[$next] : null;
}

/**
 * Pick the interaction that should speak, if any.
 *
 * Progress statuses: offered, in_progress, snoozed, completed, dismissed.
 * A first-seen event with no progress is skipped when the feature was already seen.
 * Open progress (offered / in_progress) still resumes. Expired snoozes resume too.
 *
 * @param list<array<string, mixed>> $interactions
 * @param array{
 *   event:string,
 *   feature:string,
 *   surface:string,
 *   audience:string,
 *   now:int,
 *   already_seen:bool,
 *   progress:array<string, array{status:string, beat_id?:string, snooze_until?:?string}>
 * } $ctx
 * @return array<string, mixed>|null
 */
function choosology_guide_select_interaction(array $interactions, array $ctx): ?array
{
	$matches = array();
	$event = (string) ($ctx['event'] ?? '');
	$feature = (string) ($ctx['feature'] ?? '');
	$surface = choosology_guide_surface_normalize((string) ($ctx['surface'] ?? 'classic'));
	$audience = choosology_guide_audience_normalize((string) ($ctx['audience'] ?? 'both'));
	if ($audience === 'both') {
		$audience = 'logged_in';
	}
	$now = (int) ($ctx['now'] ?? time());
	$already = !empty($ctx['already_seen']);
	$progress = is_array($ctx['progress'] ?? null) ? $ctx['progress'] : array();

	foreach ($interactions as $interaction) {
		if (empty($interaction['enabled'])) {
			continue;
		}
		if ((string) ($interaction['event_key'] ?? '') !== $event) {
			continue;
		}
		$wantFeature = (string) ($interaction['feature_key'] ?? '');
		if ($wantFeature !== '' && $wantFeature !== $feature) {
			continue;
		}
		$wantSurface = choosology_guide_surface_normalize((string) ($interaction['surface'] ?? 'both'));
		if ($wantSurface !== 'both' && $wantSurface !== $surface) {
			continue;
		}
		$wantAudience = (string) ($interaction['audience'] ?? 'both');
		if ($wantAudience !== 'both' && $wantAudience !== $audience) {
			continue;
		}
		$key = (string) ($interaction['key'] ?? '');
		$prog = isset($progress[$key]) && is_array($progress[$key]) ? $progress[$key] : null;
		if ($prog) {
			$status = (string) ($prog['status'] ?? '');
			if ($status === 'completed' || $status === 'dismissed') {
				continue;
			}
			if ($status === 'snoozed') {
				$until = isset($prog['snooze_until']) ? strtotime((string) $prog['snooze_until']) : false;
				if ($until !== false && $until > $now) {
					continue;
				}
			}
		} elseif ($event === 'feature_first_seen' && $already) {
			continue;
		}
		$matches[] = $interaction;
	}

	if (!$matches) {
		return null;
	}
	usort($matches, static function (array $a, array $b): int {
		$pa = (int) ($a['priority'] ?? 100);
		$pb = (int) ($b['priority'] ?? 100);
		if ($pa === $pb) {
			return strcmp((string) ($a['key'] ?? ''), (string) ($b['key'] ?? ''));
		}
		return $pa <=> $pb;
	});
	return $matches[0];
}

/**
 * @param list<array<string, mixed>> $mascots
 * @return array<string, mixed>|null
 */
function choosology_guide_pick_mascot(array $mascots, string $preferredKey): ?array
{
	$enabled = array();
	foreach ($mascots as $mascot) {
		if (!empty($mascot['enabled'])) {
			$enabled[] = $mascot;
		}
	}
	if ($preferredKey !== '') {
		foreach ($enabled as $mascot) {
			if ((string) ($mascot['key'] ?? '') === $preferredKey) {
				return $mascot;
			}
		}
	}
	foreach ($enabled as $mascot) {
		if (!empty($mascot['is_default'])) {
			return $mascot;
		}
	}
	return $enabled[0] ?? null;
}

/**
 * @param array<string, mixed> $interaction
 * @param array<string, mixed> $mascot
 * @param array{status?:string, beat_id?:string}|null $progress
 * @param array<string, string> $vars
 * @return array<string, mixed>|null
 */
function choosology_guide_present(array $interaction, array $mascot, ?array $progress, array $vars): ?array
{
	$beats = $interaction['beats'] ?? array();
	if (!is_array($beats) || !$beats) {
		return null;
	}
	$beatId = is_array($progress) ? (string) ($progress['beat_id'] ?? '') : '';
	$beat = choosology_guide_beat_by_id($beats, $beatId);
	if (!$beat) {
		return null;
	}
	$role = (string) ($beat['role'] ?? 'offer_help');
	$body = (string) ($beat['body'] ?? '');
	$vars['name'] = (string) ($mascot['display_name'] ?? '');
	$vars['body'] = $body;
	$text = choosology_guide_resolve_line($mascot, $role, $body, $vars);
	$choiceIds = $beat['choices'] ?? array();
	if (!is_array($choiceIds)) {
		$choiceIds = array();
	}
	$choices = array();
	foreach ($choiceIds as $choiceId) {
		$choiceId = (string) $choiceId;
		if (choosology_guide_choice_effect($choiceId) === '') {
			continue;
		}
		$choices[] = array(
			'id' => $choiceId,
			'label' => choosology_guide_choice_label($mascot, $choiceId),
		);
	}
	if (!$choices) {
		return null;
	}
	$pose = (string) ($beat['pose'] ?? 'idle');
	$portraits = is_array($mascot['portraits'] ?? null) ? $mascot['portraits'] : array();
	$path = (string) ($portraits[$pose] ?? $portraits['idle'] ?? '');
	$url = '';
	if ($path !== '' && strpos($path, '..') === false && !preg_match('#^(https?:)?//#i', $path)) {
		$url = function_exists('choosology_site_url')
			? choosology_site_url($path)
			: ('/' . ltrim($path, '/'));
	}
	$feature = (string) ($vars['feature_key'] ?? ($interaction['feature_key'] ?? ''));
	return array(
		'interaction_key' => (string) ($interaction['key'] ?? ''),
		'event' => (string) ($interaction['event_key'] ?? ''),
		'feature' => $feature,
		'feature_label' => (string) ($vars['feature'] ?? choosology_guide_feature_label($feature)),
		'mascot' => array(
			'key' => (string) ($mascot['key'] ?? ''),
			'name' => (string) ($mascot['display_name'] ?? ''),
			'pose' => $pose,
			'portrait_url' => $url,
			'theme' => choosology_guide_theme_tokens(is_array($mascot['theme'] ?? null) ? $mascot['theme'] : array()),
		),
		'beat' => array(
			'id' => (string) ($beat['id'] ?? ''),
			'text' => $text,
			'choices' => $choices,
		),
	);
}

/**
 * @param array<string, mixed> $interaction
 * @param array{status?:string, beat_id?:string, snooze_until?:?string} $progress
 * @return array{ok:bool, error?:string, status?:string, beat_id?:string, snooze_until?:?string, finished?:bool}
 */
function choosology_guide_apply_choice(array $interaction, array $progress, string $choiceId, int $now): array
{
	$beats = $interaction['beats'] ?? array();
	if (!is_array($beats)) {
		$beats = array();
	}
	$beat = choosology_guide_beat_by_id($beats, (string) ($progress['beat_id'] ?? ''));
	if (!$beat) {
		return array('ok' => false, 'error' => 'That step is no longer available.');
	}
	$allowed = array();
	foreach (($beat['choices'] ?? array()) as $id) {
		$allowed[] = (string) $id;
	}
	if (!in_array($choiceId, $allowed, true)) {
		return array('ok' => false, 'error' => 'That choice is not available.');
	}
	$effect = choosology_guide_choice_effect($choiceId);
	$beatId = (string) ($beat['id'] ?? '');
	if ($effect === 'snooze') {
		return array(
			'ok' => true,
			'status' => 'snoozed',
			'beat_id' => $beatId,
			'snooze_until' => date('Y-m-d H:i:s', $now + choosology_guide_snooze_seconds()),
			'finished' => false,
		);
	}
	if ($effect === 'dismiss') {
		return array(
			'ok' => true,
			'status' => 'dismissed',
			'beat_id' => $beatId,
			'snooze_until' => null,
			'finished' => true,
		);
	}
	if ($effect !== 'advance') {
		return array('ok' => false, 'error' => 'That choice is not available.');
	}
	$next = choosology_guide_next_beat($beats, $beatId);
	if (!$next) {
		return array(
			'ok' => true,
			'status' => 'completed',
			'beat_id' => $beatId,
			'snooze_until' => null,
			'finished' => true,
		);
	}
	return array(
		'ok' => true,
		'status' => 'in_progress',
		'beat_id' => (string) ($next['id'] ?? ''),
		'snooze_until' => null,
		'finished' => false,
	);
}

/**
 * Relative return path for Lite form posts.
 */
function choosology_guide_safe_return(string $next): string
{
	$next = str_replace('\\', '/', trim($next));
	if (strpos($next, 'lite/') === 0) {
		$next = substr($next, 5);
	}
	if ($next === '' || strpos($next, '..') !== false || preg_match('#^(https?:)?//#i', $next)) {
		return 'index.php';
	}
	if (!preg_match('/^[a-z0-9][a-z0-9_-]*\.php(\?[a-z0-9_=&%.+-]*)?$/i', $next)) {
		return 'index.php';
	}
	return $next;
}

/**
 * @param array<string, mixed> $offer
 * @param array{action?:string, next?:string} $opts
 */
function choosology_guide_render_panel(array $offer, string $mode = 'classic', array $opts = array()): string
{
	$mode = $mode === 'lite' ? 'lite' : 'classic';
	$theme = choosology_guide_theme_tokens(is_array($offer['mascot']['theme'] ?? null) ? $offer['mascot']['theme'] : array());
	$style = '--guide-accent:' . $theme['accent'] . ';--guide-paper:' . $theme['paper'] . ';--guide-ink:' . $theme['ink'] . ';';
	$name = (string) ($offer['mascot']['name'] ?? 'Guide');
	$text = (string) ($offer['beat']['text'] ?? '');
	$portrait = (string) ($offer['mascot']['portrait_url'] ?? '');
	$feature = (string) ($offer['feature'] ?? '');
	$key = (string) ($offer['interaction_key'] ?? '');
	$esc = static function (string $value): string {
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	};
	$choicesHtml = '';
	$choices = $offer['beat']['choices'] ?? array();
	if (is_array($choices)) {
		foreach ($choices as $choice) {
			if (!is_array($choice)) {
				continue;
			}
			$id = (string) ($choice['id'] ?? '');
			$label = (string) ($choice['label'] ?? $id);
			if ($mode === 'lite') {
				$choicesHtml .= '<button type="submit" class="guide-choice" name="choice" value="' . $esc($id) . '">' . $esc($label) . '</button>';
			} else {
				$choicesHtml .= '<button type="button" class="guide-choice" data-guide-choice="' . $esc($id) . '">' . $esc($label) . '</button>';
			}
		}
	}
	$img = $portrait !== ''
		? '<img class="guide-portrait" src="' . $esc($portrait) . '" alt="" width="88" height="106">'
		: '<span class="guide-portrait guide-portrait--empty" aria-hidden="true"></span>';
	$inner = $img
		. '<div class="guide-bubble">'
		. '<p class="guide-name">' . $esc($name) . '</p>'
		. '<p class="guide-text">' . $esc($text) . '</p>'
		. '<div class="guide-choices">' . $choicesHtml . '</div>'
		. '</div>';
	$attrs = ' id="guide_panel" class="guide-panel guide-panel--' . $mode . '" style="' . $esc($style) . '"'
		. ' data-feature="' . $esc($feature) . '" data-interaction="' . $esc($key) . '"'
		. ' role="region" aria-label="' . $esc($name) . '"';
	if ($mode === 'lite') {
		$action = $esc((string) ($opts['action'] ?? ''));
		$next = $esc((string) ($opts['next'] ?? ''));
		return '<form method="post" action="' . $action . '"' . $attrs . '>'
			. '<input type="hidden" name="interaction_key" value="' . $esc($key) . '">'
			. '<input type="hidden" name="next" value="' . $next . '">'
			. $inner
			. '</form>';
	}
	return '<aside' . $attrs . '>' . $inner . '</aside>';
}

/**
 * @return list<array<string, mixed>>
 */
function choosology_guide_load_mascots(mysqli $db): array
{
	choosology_guide_ensure_schema($db);
	$out = array();
	$r = mysqli_query($db, 'SELECT * FROM guide_mascots ORDER BY sort_order ASC, id ASC');
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$dialog = json_decode((string) ($row['lines_json'] ?? ''), true);
		$theme = json_decode((string) ($row['theme_json'] ?? ''), true);
		$portraits = json_decode((string) ($row['portraits_json'] ?? ''), true);
		if (!is_array($dialog)) {
			$dialog = array();
		}
		$out[] = array(
			'key' => (string) ($row['mascot_key'] ?? ''),
			'display_name' => (string) ($row['display_name'] ?? ''),
			'enabled' => (int) ($row['enabled'] ?? 0) === 1,
			'is_default' => (int) ($row['is_default'] ?? 0) === 1,
			'theme' => is_array($theme) ? $theme : array(),
			'lines' => is_array($dialog['lines'] ?? null) ? $dialog['lines'] : array(),
			'choices' => is_array($dialog['choices'] ?? null) ? $dialog['choices'] : array(),
			'portraits' => is_array($portraits) ? $portraits : array(),
		);
	}
	return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function choosology_guide_load_interactions(mysqli $db): array
{
	choosology_guide_ensure_schema($db);
	$out = array();
	$r = mysqli_query($db, 'SELECT * FROM guide_interactions ORDER BY priority ASC, id ASC');
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$beats = json_decode((string) ($row['beats_json'] ?? ''), true);
		if (!is_array($beats)) {
			continue;
		}
		$out[] = array(
			'key' => (string) ($row['interaction_key'] ?? ''),
			'title' => (string) ($row['title'] ?? ''),
			'event_key' => (string) ($row['event_key'] ?? ''),
			'feature_key' => (string) ($row['feature_key'] ?? ''),
			'surface' => (string) ($row['surface'] ?? 'both'),
			'audience' => (string) ($row['audience'] ?? 'both'),
			'mascot_key' => (string) ($row['mascot_key'] ?? ''),
			'frequency' => (string) ($row['frequency'] ?? 'once'),
			'priority' => (int) ($row['priority'] ?? 100),
			'enabled' => (int) ($row['enabled'] ?? 0) === 1,
			'beats' => $beats,
		);
	}
	return $out;
}

/**
 * @return array<string, array{status:string, beat_id:string, snooze_until:?string}>
 */
function choosology_guide_load_progress(mysqli $db, string $subject): array
{
	$esc = mysqli_real_escape_string($db, $subject);
	$out = array();
	$r = mysqli_query(
		$db,
		"SELECT interaction_key, status, beat_id, snooze_until FROM guide_progress WHERE subject_key='$esc'"
	);
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$key = (string) ($row['interaction_key'] ?? '');
		$out[$key] = array(
			'status' => (string) ($row['status'] ?? ''),
			'beat_id' => (string) ($row['beat_id'] ?? ''),
			'snooze_until' => isset($row['snooze_until']) && $row['snooze_until'] !== null && $row['snooze_until'] !== ''
				? (string) $row['snooze_until']
				: null,
		);
	}
	return $out;
}

function choosology_guide_has_seen(mysqli $db, string $subject, string $feature, string $surface): bool
{
	$escS = mysqli_real_escape_string($db, $subject);
	$escF = mysqli_real_escape_string($db, $feature);
	$escU = mysqli_real_escape_string($db, $surface);
	$r = mysqli_query(
		$db,
		"SELECT id FROM guide_seen WHERE subject_key='$escS' AND feature_key='$escF' AND surface='$escU' LIMIT 1"
	);
	return (bool) ($r && mysqli_fetch_assoc($r));
}

function choosology_guide_mark_seen(mysqli $db, string $subject, string $feature, string $surface): void
{
	$escS = mysqli_real_escape_string($db, $subject);
	$escF = mysqli_real_escape_string($db, $feature);
	$escU = mysqli_real_escape_string($db, $surface);
	$now = date('Y-m-d H:i:s');
	@mysqli_query(
		$db,
		"INSERT IGNORE INTO guide_seen (subject_key, feature_key, surface, first_seen_at)
		 VALUES ('$escS', '$escF', '$escU', '$now')"
	);
}

function choosology_guide_save_progress(
	mysqli $db,
	string $subject,
	string $interactionKey,
	string $status,
	string $beatId,
	?string $snoozeUntil
): void {
	$escS = mysqli_real_escape_string($db, $subject);
	$escK = mysqli_real_escape_string($db, $interactionKey);
	$escStatus = mysqli_real_escape_string($db, $status);
	$escBeat = mysqli_real_escape_string($db, $beatId);
	$snoozeSql = $snoozeUntil === null || $snoozeUntil === ''
		? 'NULL'
		: ("'" . mysqli_real_escape_string($db, $snoozeUntil) . "'");
	$now = date('Y-m-d H:i:s');
	@mysqli_query(
		$db,
		"INSERT INTO guide_progress (subject_key, interaction_key, status, beat_id, snooze_until, updated_at)
		 VALUES ('$escS', '$escK', '$escStatus', '$escBeat', $snoozeSql, '$now')
		 ON DUPLICATE KEY UPDATE
			status = '$escStatus',
			beat_id = '$escBeat',
			snooze_until = $snoozeSql,
			updated_at = '$now'"
	);
}

/**
 * @param list<array<string, mixed>> $interactions
 */
function choosology_guide_has_candidate(array $interactions, string $event, string $feature, string $surface, string $audience): bool
{
	$surface = choosology_guide_surface_normalize($surface);
	foreach ($interactions as $interaction) {
		if (empty($interaction['enabled'])) {
			continue;
		}
		if ((string) ($interaction['event_key'] ?? '') !== $event) {
			continue;
		}
		$wantFeature = (string) ($interaction['feature_key'] ?? '');
		if ($wantFeature !== '' && $wantFeature !== $feature) {
			continue;
		}
		$wantSurface = choosology_guide_surface_normalize((string) ($interaction['surface'] ?? 'both'));
		if ($wantSurface !== 'both' && $wantSurface !== $surface) {
			continue;
		}
		$wantAudience = (string) ($interaction['audience'] ?? 'both');
		if ($wantAudience !== 'both' && $wantAudience !== $audience) {
			continue;
		}
		return true;
	}
	return false;
}

/**
 * @param array<string, mixed> $interaction
 * @param array<string, mixed>|null $progress
 * @return array<string, mixed>|null
 */
function choosology_guide_offer_from(array $interaction, array $mascots, ?array $progress, string $userName): ?array
{
	$mascot = choosology_guide_pick_mascot($mascots, (string) ($interaction['mascot_key'] ?? ''));
	if (!$mascot) {
		return null;
	}
	$feature = (string) ($interaction['feature_key'] ?? '');
	$vars = array(
		'feature' => choosology_guide_feature_label($feature),
		'feature_key' => $feature,
		'user' => $userName,
		'name' => (string) ($mascot['display_name'] ?? ''),
		'body' => '',
	);
	return choosology_guide_present($interaction, $mascot, $progress, $vars);
}

/**
 * Record a feature open and return the current offer, or null.
 *
 * Seen is recorded only when an interaction already targets this feature, so
 * signaling Ledger or the clipboard before a tour exists does not burn the
 * first-visit event.
 *
 * @return array<string, mixed>|null
 */
function choosology_guide_signal(mysqli $db, string $feature, string $surface): ?array
{
	$feature = strtolower(trim($feature));
	if (!choosology_guide_feature_known($feature)) {
		return null;
	}
	if (!choosology_guide_enabled($db)) {
		return null;
	}
	$surface = choosology_guide_surface_normalize($surface);
	choosology_guide_ensure_schema($db);
	$audience = !empty($_SESSION['user']) ? 'logged_in' : 'anonymous';
	$event = 'feature_first_seen';
	$interactions = choosology_guide_load_interactions($db);
	if (!choosology_guide_has_candidate($interactions, $event, $feature, $surface, $audience)) {
		return null;
	}
	$subject = choosology_guide_subject_key();
	$already = choosology_guide_has_seen($db, $subject, $feature, $surface);
	$progress = choosology_guide_load_progress($db, $subject);
	$userName = !empty($_SESSION['user']) ? trim((string) $_SESSION['user']) : '';
	$picked = choosology_guide_select_interaction($interactions, array(
		'event' => $event,
		'feature' => $feature,
		'surface' => $surface,
		'audience' => $audience,
		'now' => time(),
		'already_seen' => $already,
		'progress' => $progress,
	));
	if (!$picked) {
		return null;
	}
	$mascots = choosology_guide_load_mascots($db);
	$key = (string) $picked['key'];
	$prog = $progress[$key] ?? null;
	$offer = choosology_guide_offer_from($picked, $mascots, is_array($prog) ? $prog : null, $userName);
	if (!$offer) {
		return null;
	}
	if (!$already) {
		choosology_guide_mark_seen($db, $subject, $feature, $surface);
	}
	if (!is_array($prog) || (string) ($prog['status'] ?? '') === 'snoozed') {
		choosology_guide_save_progress($db, $subject, $key, 'offered', (string) $offer['beat']['id'], null);
	}
	return $offer;
}

/**
 * @return array{ok:bool, error?:string, offer:?array, feature:string}
 */
function choosology_guide_respond(mysqli $db, string $interactionKey, string $choiceId): array
{
	$empty = array('ok' => false, 'error' => 'That choice is not available.', 'offer' => null, 'feature' => '');
	if (!choosology_guide_key_ok($interactionKey) || choosology_guide_choice_effect($choiceId) === '') {
		return $empty;
	}
	if (!choosology_guide_enabled($db)) {
		return array('ok' => true, 'offer' => null, 'feature' => '');
	}
	choosology_guide_ensure_schema($db);
	$interactions = choosology_guide_load_interactions($db);
	$interaction = null;
	foreach ($interactions as $row) {
		if ((string) ($row['key'] ?? '') === $interactionKey && !empty($row['enabled'])) {
			$interaction = $row;
			break;
		}
	}
	if (!$interaction) {
		return $empty;
	}
	$subject = choosology_guide_subject_key();
	$progress = choosology_guide_load_progress($db, $subject);
	$prog = $progress[$interactionKey] ?? null;
	if (!is_array($prog)) {
		return $empty;
	}
	$status = (string) ($prog['status'] ?? '');
	if ($status === 'completed' || $status === 'dismissed') {
		return array(
			'ok' => true,
			'offer' => null,
			'feature' => (string) ($interaction['feature_key'] ?? ''),
		);
	}
	$applied = choosology_guide_apply_choice($interaction, $prog, $choiceId, time());
	if (empty($applied['ok'])) {
		return array(
			'ok' => false,
			'error' => (string) ($applied['error'] ?? 'That choice is not available.'),
			'offer' => null,
			'feature' => (string) ($interaction['feature_key'] ?? ''),
		);
	}
	choosology_guide_save_progress(
		$db,
		$subject,
		$interactionKey,
		(string) $applied['status'],
		(string) $applied['beat_id'],
		$applied['snooze_until'] ?? null
	);
	$feature = (string) ($interaction['feature_key'] ?? '');
	if (!empty($applied['finished']) || (string) ($applied['status'] ?? '') === 'snoozed') {
		return array('ok' => true, 'offer' => null, 'feature' => $feature);
	}
	$mascots = choosology_guide_load_mascots($db);
	$userName = !empty($_SESSION['user']) ? trim((string) $_SESSION['user']) : '';
	$offer = choosology_guide_offer_from($interaction, $mascots, array(
		'status' => (string) $applied['status'],
		'beat_id' => (string) $applied['beat_id'],
	), $userName);
	return array(
		'ok' => true,
		'offer' => $offer,
		'feature' => $feature,
	);
}
