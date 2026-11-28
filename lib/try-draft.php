<?php
/**
 * Anonymous "Try it yourself" draft.
 * Lives in the PHP session only. Never inserts or updates advs / advscreens.
 *
 * The screen ceiling matches the visual editor: eight outgoing choices is the
 * most one screen can have (choice1..choice8, and createChild refuses an
 * eighth child). The draft allows that many screens in total, not more.
 */

function choosology_try_screen_cap(): int
{
	return 8;
}

function choosology_try_account_message(): string
{
	return 'This draft stays in this browser session and is not saved to the lab. Create an account (Apply for lab access) to keep an experiment.';
}

function choosology_try_cap_message(): string
{
	return 'This draft holds eight screens, the same ceiling as one screen\'s outgoing choices. ' . choosology_try_account_message();
}

function choosology_try_visitor_is_logged_in(): bool
{
	return !empty($_SESSION['user']);
}

/**
 * @return array{next_id:int,adv:array<string,string>,screens:array<string,array<string,string>>}
 */
function choosology_try_draft_blank(): array
{
	return array(
		'next_id' => 2,
		'adv' => array(
			'id' => 'try',
			'user' => 'guest',
			'title' => 'Untitled draft',
			'begin' => '1',
			'bg' => '#FFFFFF',
			'box' => '#ccddff',
			'border' => '#9999cc',
			'borderwidth' => '2',
			'font' => '',
			'textstyle' => '',
			'linkstyle' => '',
			'titlestyle' => '',
			'linkcolor' => '',
			'textcolor' => '',
			'description' => '',
			'pic' => '',
			'bgpic' => '',
		),
		'screens' => array(
			'1' => choosology_try_blank_screen('1', 'Start', 140, 120, 'This is the first screen of your draft.'),
		),
	);
}

/**
 * @return array<string,string>
 */
function choosology_try_blank_screen(string $id, string $name, int $x, int $y, string $text = ''): array
{
	$screen = array(
		'id' => $id,
		'name' => $name !== '' ? $name : 'New Screen',
		'title' => '',
		'text' => $text,
		'xpos' => (string) $x,
		'ypos' => (string) $y,
		'deleted' => '0',
		'advused' => 'try',
		'user' => '',
		'screenbgcolor' => '',
		'screenboxcolor' => '',
		'screenbordercolor' => '',
		'screenborderwidth' => '',
		'edited' => '',
	);
	for ($i = 1; $i <= 8; $i++) {
		$screen['choice' . $i] = '';
	}
	return $screen;
}

/**
 * @return array{next_id:int,adv:array<string,string>,screens:array<string,array<string,string>>}
 */
function choosology_try_draft_get(bool $create = true): array
{
	if (!isset($_SESSION['choosology_try_draft']) || !is_array($_SESSION['choosology_try_draft'])) {
		$blank = choosology_try_draft_blank();
		if ($create && !choosology_try_visitor_is_logged_in()) {
			$_SESSION['choosology_try_draft'] = $blank;
		}
		return $blank;
	}
	return $_SESSION['choosology_try_draft'];
}

/**
 * @param array{next_id:int,adv:array<string,string>,screens:array<string,array<string,string>>} $draft
 */
function choosology_try_draft_store(array $draft): void
{
	if (choosology_try_visitor_is_logged_in()) {
		return;
	}
	$_SESSION['choosology_try_draft'] = $draft;
}

function choosology_try_screen_is_active(array $screen): bool
{
	return (string) ($screen['deleted'] ?? '0') !== '1';
}

function choosology_try_active_count(array $draft): int
{
	$n = 0;
	foreach ($draft['screens'] as $screen) {
		if (is_array($screen) && choosology_try_screen_is_active($screen)) {
			$n++;
		}
	}
	return $n;
}

/**
 * Apply one editor save payload (the same box map saveAdventure posts).
 * New boxes use the editor's temporary names (new0_box_1). Their connection
 * targets use substr(name, 4), matching vised/saveadv.php, and are rewritten
 * to session ids here. Nothing is written to the database.
 *
 * @param array<string,mixed> $boxes
 * @return array{ok:int,account:int,error:string,draft:array}
 */
function choosology_try_apply_boxes(array $draft, array $boxes): array
{
	$screens = $draft['screens'];
	$next = (int) ($draft['next_id'] ?? 2);
	$newKeys = array();
	foreach ($boxes as $key => $box) {
		if (!is_array($box)) {
			continue;
		}
		$key = (string) $key;
		if (strncmp($key, 'new', 3) === 0) {
			$newKeys[] = $key;
		}
	}

	$adding = 0;
	foreach ($newKeys as $key) {
		if (empty($boxes[$key]['deleted'])) {
			$adding++;
		}
	}
	if (choosology_try_active_count($draft) + $adding > choosology_try_screen_cap()) {
		return array(
			'ok' => 0,
			'account' => 1,
			'error' => choosology_try_cap_message(),
			'draft' => $draft,
		);
	}

	$idMap = array();
	foreach ($newKeys as $key) {
		$newId = (string) $next;
		$next++;
		$temp = substr($key, 4);
		$idMap[$temp] = $newId;
		$box = $boxes[$key];
		$screens[$newId] = choosology_try_blank_screen(
			$newId,
			trim((string) ($box['title'] ?? 'New Screen')),
			(int) ($box['x'] ?? 100),
			(int) ($box['y'] ?? 100)
		);
		if (!empty($box['deleted'])) {
			$screens[$newId]['deleted'] = '1';
		}
	}

	foreach ($boxes as $key => $box) {
		if (!is_array($box)) {
			continue;
		}
		$key = (string) $key;
		if (strncmp($key, 'box_', 4) === 0) {
			$id = substr($key, 4);
		} elseif (isset($idMap[substr($key, 4)])) {
			$id = $idMap[substr($key, 4)];
		} else {
			continue;
		}
		if (!isset($screens[$id]) || !is_array($screens[$id])) {
			continue;
		}
		$screens[$id]['xpos'] = (string) (int) ($box['x'] ?? $screens[$id]['xpos'] ?? 0);
		$screens[$id]['ypos'] = (string) (int) ($box['y'] ?? $screens[$id]['ypos'] ?? 0);
		$title = trim((string) ($box['title'] ?? ''));
		if ($title !== '') {
			$screens[$id]['name'] = $title;
		}
		$screens[$id]['deleted'] = !empty($box['deleted']) ? '1' : '0';
		for ($i = 1; $i <= 8; $i++) {
			$screens[$id]['choice' . $i] = '';
		}
		$conns = $box['connections'] ?? array();
		if (!is_array($conns)) {
			$conns = array();
		}
		$n = 1;
		foreach ($conns as $raw) {
			if ($n > 8) {
				break;
			}
			$raw = (string) $raw;
			foreach ($idMap as $temp => $real) {
				if ($temp !== '') {
					$raw = str_replace($temp, $real, $raw);
				}
			}
			$screens[$id]['choice' . $n] = $raw;
			$n++;
		}
	}

	$draft['screens'] = $screens;
	$draft['next_id'] = $next;
	return array(
		'ok' => 1,
		'account' => 0,
		'error' => '',
		'draft' => $draft,
	);
}

/**
 * @param array<string,string> $choiceLabels choice1 => new label, target kept
 * @return array{ok:int,error:string,draft:array}
 */
function choosology_try_save_screen(array $draft, string $id, string $name, string $text, array $choiceLabels = array()): array
{
	if (!isset($draft['screens'][$id]) || !is_array($draft['screens'][$id])) {
		return array('ok' => 0, 'error' => 'Screen could not be found.', 'draft' => $draft);
	}
	$name = trim($name);
	if ($name !== '') {
		$draft['screens'][$id]['name'] = $name;
	}
	$draft['screens'][$id]['text'] = $text;
	$draft['screens'][$id]['edited'] = date('Y-m-d H:i:s');
	foreach ($choiceLabels as $slot => $label) {
		$slot = (string) $slot;
		if (!preg_match('/^choice[1-8]$/', $slot)) {
			continue;
		}
		$existing = (string) ($draft['screens'][$id][$slot] ?? '');
		$parts = explode('|Q-D-|', $existing, 2);
		$target = $parts[1] ?? '';
		if ($target === '') {
			continue;
		}
		$draft['screens'][$id][$slot] = trim((string) $label) . '|Q-D-|' . $target;
	}
	return array('ok' => 1, 'error' => '', 'draft' => $draft);
}

/**
 * Draft end screen only. Uses the published END NODE pill, without rating or comments.
 */
function choosology_try_ending_html(string $surface): string
{
	$href = function_exists('choosology_site_url')
		? choosology_site_url('index.php?stay=1') . '#/home'
		: 'index.php?stay=1#/home';
	$note = 'Other users may rate or comment on published experiments in end nodes like this. '
		. '<a class="login-signup-link" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" onclick="var el=document.getElementById(\'opensignup\'); if(el){el.click(); return false;}">Create an account</a>'
		. ' to save and publish your work so far!';
	if ($surface === 'lite') {
		return '<fieldset class="lite-ending" role="status">'
			. '<legend class="lite-ending-legend"><span class="lite-ending-pill">END NODE</span></legend>'
			. '<p>This path ends here.</p>'
			. '<p>' . $note . '</p>'
			. '</fieldset>';
	}
	return '<div class="ending-panel" role="status" aria-label="End of experiment">'
		. '<fieldset class="ending-panel-notice">'
		. '<legend class="ending-panel-legend"><span class="ending-panel-pill">END NODE</span></legend>'
		. '<p class="ending-panel-lede">This path ends here.</p>'
		. '</fieldset>'
		. '<p class="ending-panel-lede">' . $note . '</p>'
		. '</div>';
}

function choosology_try_choice_markup(array $screen, string $fromId): string
{
	$choices = '';
	for ($i = 1; $i <= 8; $i++) {
		$raw = (string) ($screen['choice' . $i] ?? '');
		if ($raw === '') {
			continue;
		}
		$parts = explode('|Q-D-|', $raw, 2);
		$label = $parts[0] ?? '';
		$target = $parts[1] ?? '';
		if ($label === '' || $target === '') {
			continue;
		}
		/* Same as a saved adventure: decode stored HTML and emit it so a
		   paragraph shows as words, not as a visible <p> tag. */
		$labelHtml = htmlspecialchars_decode(htmlspecialchars_decode($label));
		$choices .= "<div class='choice' onclick=\"goToScreen('"
			. htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "','"
			. htmlspecialchars($fromId, ENT_QUOTES, 'UTF-8') . "',0,this)\">"
			. $labelHtml . "</div>";
	}
	if ($choices === '') {
		$choices = choosology_try_ending_html('classic');
	}
	$choices .= "<input type='hidden' id='try-draft' value='1'>";
	$choices .= "<input type='hidden' name='advid' id='advid' value='try'>";
	$choices .= "<input type='hidden' name='screenid' id='screenid' value='"
		. htmlspecialchars((string) ($screen['id'] ?? ''), ENT_QUOTES, 'UTF-8') . "'>";
	return $choices;
}

/**
 * Player JSON for one draft screen. Does not read the database.
 */
function choosology_try_screen_payload(array $draft, string $screenId): array
{
	$adv = $draft['adv'];
	if (!isset($draft['screens'][$screenId]) || !choosology_try_screen_is_active($draft['screens'][$screenId])) {
		$screenId = (string) ($adv['begin'] ?? '1');
	}
	$screen = $draft['screens'][$screenId] ?? choosology_try_blank_screen('1', 'Start', 140, 120, '');
	$text = (string) ($screen['text'] ?? '');
	$body = nl2br($text) . "<br/><div class='spacebox'>&emsp;</div>";
	$bgColor = (string) ($screen['screenbgcolor'] ?? '');
	if ($bgColor === '') {
		$bgColor = (string) ($adv['bg'] ?? '#FFFFFF');
	}
	$boxColor = (string) ($screen['screenboxcolor'] ?? '');
	if ($boxColor === '') {
		$boxColor = (string) ($adv['box'] ?? '#ccddff');
	}
	$borderWidth = (string) ($screen['screenborderwidth'] ?? '');
	if ($borderWidth === '') {
		$borderWidth = (string) ($adv['borderwidth'] ?? '2');
	}
	$borderColor = (string) ($screen['screenbordercolor'] ?? '');
	if ($borderColor === '') {
		$borderColor = (string) ($adv['border'] ?? '#9999cc');
	}
	$rgb = sscanf($borderColor, '#%02x%02x%02x');
	if (!is_array($rgb) || count($rgb) < 3) {
		$rgb = array(153, 153, 204);
	}
	return array(
		'success' => 1,
		'text' => $body,
		'choices' => choosology_try_choice_markup($screen, (string) ($screen['id'] ?? '')),
		'bg' => 'background-color: ' . $bgColor,
		'box' => 'background-color: ' . $boxColor,
		'border' => 'border: ' . $borderWidth . 'px solid ' . $borderColor,
		'choiceborder' => 'border: 2px solid rgb(' . (int) $rgb[0] . ', ' . (int) $rgb[1] . ', ' . (int) $rgb[2] . ')',
		'offset' => 'top: -' . $borderWidth . 'px; left: -' . $borderWidth . 'px',
	);
}
