<?php
/**
 * Validated CRUD for mascot packs and guide interactions.
 */

require_once __DIR__ . '/guide-helpers.php';

function choosology_guide_admin_plain($value, int $max): string
{
	$text = trim(strip_tags((string) $value));
	if (function_exists('mb_substr')) {
		return mb_substr($text, 0, $max, 'UTF-8');
	}
	return substr($text, 0, $max);
}

function choosology_guide_admin_portrait_path_ok(string $path): bool
{
	$path = trim(str_replace('\\', '/', $path));
	if ($path === '') {
		return true;
	}
	return strlen($path) <= 255
		&& strpos($path, '..') === false
		&& !preg_match('#^(https?:)?//#i', $path)
		&& (bool) preg_match('#^images/mascots/[a-z0-9_/-]+\.(png|gif|jpe?g|webp)$#i', $path);
}

/**
 * @return array{ok:bool,errors:list<string>,value?:array<string,mixed>}
 */
function choosology_guide_admin_validate_mascot(array $input): array
{
	$errors = array();
	$key = strtolower(trim((string) ($input['key'] ?? '')));
	$name = choosology_guide_admin_plain($input['display_name'] ?? '', 80);
	if (!choosology_guide_key_ok($key)) {
		$errors[] = 'Mascot key must use lowercase letters, numbers, and underscores.';
	}
	if ($name === '') {
		$errors[] = 'Mascot name is required.';
	}

	$themeInput = is_array($input['theme'] ?? null) ? $input['theme'] : array();
	foreach (array('accent', 'paper', 'ink') as $token) {
		$raw = strtolower(trim((string) ($themeInput[$token] ?? '')));
		if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $raw)) {
			$errors[] = ucfirst($token) . ' must be a hex color.';
		}
	}
	$theme = choosology_guide_theme_tokens($themeInput);

	$portraits = array();
	$portraitInput = is_array($input['portraits'] ?? null) ? $input['portraits'] : array();
	foreach (array('idle', 'offer', 'explain', 'done') as $pose) {
		$path = trim(str_replace('\\', '/', (string) ($portraitInput[$pose] ?? '')));
		if (!choosology_guide_admin_portrait_path_ok($path)) {
			$errors[] = ucfirst($pose) . ' portrait must be under images/mascots/.';
		}
		$portraits[$pose] = $path;
	}
	if ($portraits['idle'] === '') {
		$errors[] = 'An idle portrait is required.';
	}

	$lines = array();
	$lineInput = is_array($input['lines'] ?? null) ? $input['lines'] : array();
	foreach (array('offer_help', 'explain', 'done') as $role) {
		$source = $lineInput[$role] ?? array();
		if (!is_array($source)) {
			$source = preg_split('/\r\n|\r|\n/', (string) $source);
		}
		$clean = array();
		foreach ($source as $line) {
			$line = choosology_guide_admin_plain($line, 2000);
			if ($line !== '') {
				$clean[] = $line;
			}
		}
		if (!$clean) {
			$errors[] = 'At least one ' . str_replace('_', ' ', $role) . ' line is required.';
		}
		$lines[$role] = $clean;
	}

	$choiceInput = is_array($input['choices'] ?? null) ? $input['choices'] : array();
	$choices = array();
	foreach (array('accept', 'decline', 'later', 'done', 'next') as $choice) {
		$label = choosology_guide_admin_plain($choiceInput[$choice] ?? '', 80);
		if ($label === '') {
			$errors[] = ucfirst($choice) . ' choice label is required.';
		}
		$choices[$choice] = $label;
	}

	if ($errors) {
		return array('ok' => false, 'errors' => $errors);
	}
	return array(
		'ok' => true,
		'errors' => array(),
		'value' => array(
			'key' => $key,
			'display_name' => $name,
			'enabled' => !empty($input['enabled']),
			'is_default' => !empty($input['is_default']),
			'sort_order' => max(-9999, min(9999, (int) ($input['sort_order'] ?? 0))),
			'theme' => $theme,
			'portraits' => $portraits,
			'lines' => $lines,
			'choices' => $choices,
		),
	);
}

/**
 * @param list<string> $mascotKeys
 * @return array{ok:bool,errors:list<string>,value?:array<string,mixed>}
 */
function choosology_guide_admin_validate_interaction(array $input, array $mascotKeys): array
{
	$errors = array();
	$key = strtolower(trim((string) ($input['key'] ?? '')));
	$title = choosology_guide_admin_plain($input['title'] ?? '', 120);
	$event = trim((string) ($input['event_key'] ?? ''));
	$feature = trim((string) ($input['feature_key'] ?? ''));
	$surface = trim((string) ($input['surface'] ?? ''));
	$audience = trim((string) ($input['audience'] ?? ''));
	$mascot = trim((string) ($input['mascot_key'] ?? ''));
	if (!choosology_guide_key_ok($key)) {
		$errors[] = 'Interaction key must use lowercase letters, numbers, and underscores.';
	}
	if ($title === '') {
		$errors[] = 'Interaction title is required.';
	}
	if (!isset(choosology_guide_event_registry()[$event])) {
		$errors[] = 'Choose a registered event.';
	}
	if ($feature !== '' && !isset(choosology_guide_feature_registry()[$feature])) {
		$errors[] = 'Choose a registered feature.';
	}
	if (!in_array($surface, array('classic', 'lite', 'both'), true)) {
		$errors[] = 'Choose a valid surface.';
	}
	if (!in_array($audience, array('both', 'logged_in', 'anonymous'), true)) {
		$errors[] = 'Choose a valid audience.';
	}
	if ($mascot === '' || !in_array($mascot, $mascotKeys, true)) {
		$errors[] = 'Choose an existing mascot.';
	}

	$beats = array();
	$beatIds = array();
	$beatInput = is_array($input['beats'] ?? null) ? $input['beats'] : array();
	if (!$beatInput || count($beatInput) > 20) {
		$errors[] = 'Interactions need between 1 and 20 dialog beats.';
	}
	foreach ($beatInput as $rawBeat) {
		if (!is_array($rawBeat)) {
			$errors[] = 'Each beat must be an object.';
			continue;
		}
		$id = strtolower(trim((string) ($rawBeat['id'] ?? '')));
		$role = strtolower(trim((string) ($rawBeat['role'] ?? '')));
		$pose = strtolower(trim((string) ($rawBeat['pose'] ?? '')));
		if (!choosology_guide_key_ok($id) || isset($beatIds[$id])) {
			$errors[] = 'Beat IDs must be unique lowercase keys.';
		} else {
			$beatIds[$id] = true;
		}
		if (!in_array($role, array('offer_help', 'explain', 'done'), true)) {
			$errors[] = 'Beat role must be offer_help, explain, or done.';
		}
		if (!choosology_guide_key_ok($pose)) {
			$errors[] = 'Beat pose must be a lowercase key.';
		}
		$choices = array();
		$choiceInput = is_array($rawBeat['choices'] ?? null) ? $rawBeat['choices'] : array();
		foreach ($choiceInput as $choice) {
			$choice = trim((string) $choice);
			if (choosology_guide_choice_effect($choice) === '') {
				$errors[] = 'Unknown beat choice: ' . $choice;
				continue;
			}
			if (!in_array($choice, $choices, true)) {
				$choices[] = $choice;
			}
		}
		if (!$choices) {
			$errors[] = 'Every beat needs at least one choice.';
		}
		$beats[] = array(
			'id' => $id,
			'role' => $role,
			'pose' => $pose,
			'body' => choosology_guide_admin_plain($rawBeat['body'] ?? '', 2000),
			'choices' => $choices,
		);
	}

	if ($errors) {
		return array('ok' => false, 'errors' => array_values(array_unique($errors)));
	}
	return array(
		'ok' => true,
		'errors' => array(),
		'value' => array(
			'key' => $key,
			'title' => $title,
			'event_key' => $event,
			'feature_key' => $feature,
			'surface' => $surface,
			'audience' => $audience,
			'mascot_key' => $mascot,
			'frequency' => 'once',
			'priority' => max(-9999, min(9999, (int) ($input['priority'] ?? 100))),
			'enabled' => !empty($input['enabled']),
			'beats' => $beats,
		),
	);
}

/**
 * @return array{ok:bool,error?:string,mascot?:array<string,mixed>}
 */
function choosology_guide_admin_save_mascot(mysqli $db, array $input): array
{
	$validated = choosology_guide_admin_validate_mascot($input);
	if (empty($validated['ok'])) {
		return array('ok' => false, 'error' => implode(' ', $validated['errors']));
	}
	$m = $validated['value'];
	$key = mysqli_real_escape_string($db, $m['key']);
	$name = mysqli_real_escape_string($db, $m['display_name']);
	$theme = mysqli_real_escape_string($db, (string) json_encode($m['theme'], JSON_UNESCAPED_SLASHES));
	$portraits = mysqli_real_escape_string($db, (string) json_encode($m['portraits'], JSON_UNESCAPED_SLASHES));
	$dialog = mysqli_real_escape_string($db, (string) json_encode(array(
		'lines' => $m['lines'],
		'choices' => $m['choices'],
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	$enabled = $m['enabled'] ? 1 : 0;
	$isDefault = $m['is_default'] ? 1 : 0;
	$sort = (int) $m['sort_order'];
	choosology_guide_ensure_schema($db);
	if ($isDefault) {
		mysqli_query($db, 'UPDATE guide_mascots SET is_default=0');
	}
	$ok = mysqli_query(
		$db,
		"INSERT INTO guide_mascots
			(mascot_key, display_name, enabled, is_default, theme_json, lines_json, portraits_json, sort_order)
		 VALUES ('$key', '$name', $enabled, $isDefault, '$theme', '$dialog', '$portraits', $sort)
		 ON DUPLICATE KEY UPDATE
			display_name='$name', enabled=$enabled, is_default=$isDefault,
			theme_json='$theme', lines_json='$dialog', portraits_json='$portraits', sort_order=$sort"
	);
	return $ok
		? array('ok' => true, 'mascot' => $m)
		: array('ok' => false, 'error' => 'Mascot could not be saved.');
}

/**
 * @return array{ok:bool,error?:string,interaction?:array<string,mixed>}
 */
function choosology_guide_admin_save_interaction(mysqli $db, array $input): array
{
	$mascotKeys = array_map(static function (array $m): string {
		return (string) $m['key'];
	}, choosology_guide_load_mascots($db));
	$validated = choosology_guide_admin_validate_interaction($input, $mascotKeys);
	if (empty($validated['ok'])) {
		return array('ok' => false, 'error' => implode(' ', $validated['errors']));
	}
	$i = $validated['value'];
	$key = mysqli_real_escape_string($db, $i['key']);
	$title = mysqli_real_escape_string($db, $i['title']);
	$event = mysqli_real_escape_string($db, $i['event_key']);
	$feature = mysqli_real_escape_string($db, $i['feature_key']);
	$surface = mysqli_real_escape_string($db, $i['surface']);
	$audience = mysqli_real_escape_string($db, $i['audience']);
	$mascot = mysqli_real_escape_string($db, $i['mascot_key']);
	$beats = mysqli_real_escape_string($db, (string) json_encode($i['beats'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	$priority = (int) $i['priority'];
	$enabled = $i['enabled'] ? 1 : 0;
	$ok = mysqli_query(
		$db,
		"INSERT INTO guide_interactions
			(interaction_key, title, event_key, feature_key, surface, audience, mascot_key, frequency, priority, enabled, beats_json)
		 VALUES ('$key', '$title', '$event', '$feature', '$surface', '$audience', '$mascot', 'once', $priority, $enabled, '$beats')
		 ON DUPLICATE KEY UPDATE
			title='$title', event_key='$event', feature_key='$feature', surface='$surface',
			audience='$audience', mascot_key='$mascot', frequency='once',
			priority=$priority, enabled=$enabled, beats_json='$beats'"
	);
	return $ok
		? array('ok' => true, 'interaction' => $i)
		: array('ok' => false, 'error' => 'Interaction could not be saved.');
}

/**
 * @return array{ok:bool,error?:string}
 */
function choosology_guide_admin_delete_mascot(mysqli $db, string $key): array
{
	$key = strtolower(trim($key));
	foreach (choosology_guide_mascot_seed() as $seed) {
		if ((string) $seed['key'] === $key) {
			return array('ok' => false, 'error' => 'Built-in mascots can be reset, not deleted.');
		}
	}
	if (!choosology_guide_key_ok($key)) {
		return array('ok' => false, 'error' => 'Invalid mascot key.');
	}
	$esc = mysqli_real_escape_string($db, $key);
	$refs = mysqli_query($db, "SELECT id FROM guide_interactions WHERE mascot_key='$esc' LIMIT 1");
	if ($refs && mysqli_fetch_assoc($refs)) {
		return array('ok' => false, 'error' => 'This mascot is used by an interaction.');
	}
	$ok = mysqli_query($db, "DELETE FROM guide_mascots WHERE mascot_key='$esc' LIMIT 1");
	return $ok && mysqli_affected_rows($db) > 0
		? array('ok' => true)
		: array('ok' => false, 'error' => 'Mascot not found.');
}

/**
 * @return array{ok:bool,error?:string}
 */
function choosology_guide_admin_delete_interaction(mysqli $db, string $key): array
{
	$key = strtolower(trim($key));
	foreach (choosology_guide_interaction_seed() as $seed) {
		if ((string) $seed['key'] === $key) {
			return array('ok' => false, 'error' => 'Built-in interactions can be reset, not deleted.');
		}
	}
	if (!choosology_guide_key_ok($key)) {
		return array('ok' => false, 'error' => 'Invalid interaction key.');
	}
	$esc = mysqli_real_escape_string($db, $key);
	$ok = mysqli_query($db, "DELETE FROM guide_interactions WHERE interaction_key='$esc' LIMIT 1");
	if (!$ok || mysqli_affected_rows($db) < 1) {
		return array('ok' => false, 'error' => 'Interaction not found.');
	}
	mysqli_query($db, "DELETE FROM guide_progress WHERE interaction_key='$esc'");
	return array('ok' => true);
}

/**
 * @return array{ok:bool,error?:string}
 */
function choosology_guide_admin_reset_mascot(mysqli $db, string $key): array
{
	foreach (choosology_guide_mascot_seed() as $seed) {
		if ((string) $seed['key'] === $key) {
			return choosology_guide_admin_save_mascot($db, $seed);
		}
	}
	return array('ok' => false, 'error' => 'No built-in mascot uses that key.');
}

/**
 * @return array{ok:bool,error?:string}
 */
function choosology_guide_admin_reset_interaction(mysqli $db, string $key): array
{
	foreach (choosology_guide_interaction_seed() as $seed) {
		if ((string) $seed['key'] === $key) {
			return choosology_guide_admin_save_interaction($db, $seed);
		}
	}
	return array('ok' => false, 'error' => 'No built-in interaction uses that key.');
}

/**
 * Side-effect-free preview of an interaction draft.
 *
 * @return array{ok:bool,error?:string,offer?:array<string,mixed>,html?:string}
 */
function choosology_guide_admin_preview(
	mysqli $db,
	array $interactionInput,
	string $beatId = ''
): array {
	$mascots = choosology_guide_load_mascots($db);
	$keys = array_map(static function (array $m): string {
		return (string) $m['key'];
	}, $mascots);
	$validated = choosology_guide_admin_validate_interaction($interactionInput, $keys);
	if (empty($validated['ok'])) {
		return array('ok' => false, 'error' => implode(' ', $validated['errors']));
	}
	$interaction = $validated['value'];
	$mascot = choosology_guide_pick_mascot($mascots, (string) $interaction['mascot_key']);
	if (!$mascot) {
		return array('ok' => false, 'error' => 'Mascot not found.');
	}
	$feature = (string) $interaction['feature_key'];
	$offer = choosology_guide_present(
		$interaction,
		$mascot,
		array('beat_id' => $beatId),
		array(
			'feature' => choosology_guide_feature_label($feature),
			'feature_key' => $feature,
			'user' => (string) ($_SESSION['user'] ?? 'Preview User'),
			'name' => (string) $mascot['display_name'],
			'body' => '',
		)
	);
	if (!$offer) {
		return array('ok' => false, 'error' => 'Preview could not be rendered.');
	}
	return array(
		'ok' => true,
		'offer' => $offer,
		'html' => choosology_guide_render_panel($offer, 'classic'),
	);
}
