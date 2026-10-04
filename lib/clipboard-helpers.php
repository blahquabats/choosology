<?php
/**
 * Clipboard + achievement catalog (schema + helpers).
 * Safe to require after connect.php / mysqli is available.
 */

require_once __DIR__ . '/choosology-core.php';

/**
 * Ensure clipboard / achievement tables (and optional adv metrics columns) exist.
 */
function choosology_clipboard_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS clipboard_pad (
			uname varchar(45) NOT NULL,
			body mediumtext NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (uname)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);

	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS clipboard_todos (
			id int unsigned NOT NULL AUTO_INCREMENT,
			uname varchar(45) NOT NULL,
			body varchar(500) NOT NULL,
			done tinyint(1) NOT NULL DEFAULT 0,
			sort_order int NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			done_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY clipboard_todos_user_done (uname, done),
			KEY clipboard_todos_user_sort (uname, sort_order, id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);

	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS clipboard_checklist (
			uname varchar(45) NOT NULL,
			item_key varchar(64) NOT NULL,
			dismissed tinyint(1) NOT NULL DEFAULT 0,
			completed tinyint(1) NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY (uname, item_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);

	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS user_achievements (
			id int unsigned NOT NULL AUTO_INCREMENT,
			uname varchar(45) NOT NULL,
			achievement_key varchar(64) NOT NULL,
			earned_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_achievements_user_key (uname, achievement_key),
			KEY user_achievements_user (uname)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);

	$cols = array();
	$r = @mysqli_query($db, 'SHOW COLUMNS FROM advs');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$cols[(string) ($row['Field'] ?? '')] = true;
		}
	}
	if (empty($cols['play_starts'])) {
		@mysqli_query($db, 'ALTER TABLE advs ADD COLUMN play_starts int unsigned NOT NULL DEFAULT 0');
	}
	if (empty($cols['last_played'])) {
		@mysqli_query($db, 'ALTER TABLE advs ADD COLUMN last_played datetime DEFAULT NULL');
	}
}

/**
 * Achievement catalog (degrees).
 *
 * @return array<string, array{label:string,blurb:string,group:string}>
 */
function choosology_achievement_catalog(): array
{
	return array(
		'lab_initiate' => array(
			'label' => 'Lab Initiate',
			'blurb' => 'Created your first experiment draft.',
			'group' => 'authoring',
		),
		'screenwright' => array(
			'label' => 'Screenwright',
			'blurb' => 'Saved content on an experiment screen.',
			'group' => 'authoring',
		),
		'field_release' => array(
			'label' => 'Field Release',
			'blurb' => 'Published an experiment for others to play.',
			'group' => 'authoring',
		),
		'clipboard_clerk' => array(
			'label' => 'Clipboard Clerk',
			'blurb' => 'Opened your office clipboard.',
			'group' => 'office',
		),
		'scribbler' => array(
			'label' => 'Scribbler',
			'blurb' => 'Saved a free-form clipboard note.',
			'group' => 'office',
		),
		'taskmaster' => array(
			'label' => 'Taskmaster',
			'blurb' => 'Added a personal to-do on the clipboard.',
			'group' => 'office',
		),
		'results_analyst' => array(
			'label' => 'Results Analyst',
			'blurb' => 'Reviewed experiment usage metrics in My Office.',
			'group' => 'office',
		),
	);
}

/**
 * New-user clipboard checklist definitions.
 * auto: condition key checked server-side; completing awards achievement_key.
 *
 * @return list<array{key:string,label:string,hint:string,achievement:string,auto:string}>
 */
function choosology_clipboard_checklist_defs(): array
{
	return array(
		array(
			'key' => 'create_experiment',
			'label' => 'Draft your first experiment',
			'hint' => 'My Stuff → Experiments → New experiment',
			'achievement' => 'lab_initiate',
			'auto' => 'has_adventure',
		),
		array(
			'key' => 'edit_screen',
			'label' => 'Edit a screen',
			'hint' => 'Open the graph editor and save screen text',
			'achievement' => 'screenwright',
			'auto' => 'has_screenwright',
		),
		array(
			'key' => 'publish_experiment',
			'label' => 'Publish an experiment',
			'hint' => 'Set availability to Public in Experiment Settings',
			'achievement' => 'field_release',
			'auto' => 'has_public',
		),
		array(
					'key' => 'open_clipboard',
			'label' => 'Visit your clipboard',
			'hint' => 'Open Clip beside your login name',
			'achievement' => 'clipboard_clerk',
			'auto' => 'visited_clipboard',
		),
		array(
			'key' => 'write_note',
			'label' => 'Save a personal note',
			'hint' => 'Use the free-form pad on the clipboard',
			'achievement' => 'scribbler',
			'auto' => 'has_note',
		),
		array(
			'key' => 'add_todo',
			'label' => 'Add a to-do item',
			'hint' => 'Capture one lab task on the clipboard',
			'achievement' => 'taskmaster',
			'auto' => 'has_todo',
		),
		array(
			'key' => 'review_results',
			'label' => 'Review experiment results',
			'hint' => 'Open My Office for full metrics (highlights also appear on the clipboard)',
			'achievement' => 'results_analyst',
			'auto' => 'visited_results',
		),
	);
}

function choosology_award_achievement(mysqli $db, string $uname, string $key): bool
{
	$uname = trim($uname);
	$key = trim($key);
	$catalog = choosology_achievement_catalog();
	if ($uname === '' || $key === '' || !isset($catalog[$key])) {
		return false;
	}
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$escK = mysqli_real_escape_string($db, $key);
	$ok = @mysqli_query(
		$db,
		"INSERT IGNORE INTO user_achievements (uname, achievement_key, earned_at)
		 VALUES ('$escU', '$escK', NOW())"
	);
	$awarded = (bool) $ok && mysqli_affected_rows($db) > 0;
	if ($awarded) {
		if (!function_exists('choosology_datascrip_on_achievement')) {
			require_once __DIR__ . '/datascrip-helpers.php';
		}
		choosology_datascrip_on_achievement($db, $uname, $key);
	}
	return $awarded;
}

/**
 * @return list<array{key:string,label:string,blurb:string,group:string,earned_at:?string}>
 */
function choosology_user_achievements(mysqli $db, string $uname): array
{
	choosology_clipboard_ensure_schema($db);
	$catalog = choosology_achievement_catalog();
	$escU = mysqli_real_escape_string($db, $uname);
	$earned = array();
	$r = mysqli_query($db, "SELECT achievement_key, earned_at FROM user_achievements WHERE uname = '$escU'");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$earned[(string) $row['achievement_key']] = (string) ($row['earned_at'] ?? '');
		}
	}
	$out = array();
	foreach ($catalog as $key => $meta) {
		$out[] = array(
			'key' => $key,
			'label' => $meta['label'],
			'blurb' => $meta['blurb'],
			'group' => $meta['group'],
			'earned_at' => isset($earned[$key]) ? $earned[$key] : null,
		);
	}
	return $out;
}

function choosology_clipboard_set_checklist_flag(mysqli $db, string $uname, string $itemKey, string $field, int $value): void
{
	if (!in_array($field, array('dismissed', 'completed'), true)) {
		return;
	}
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$escK = mysqli_real_escape_string($db, $itemKey);
	$val = $value ? 1 : 0;
	@mysqli_query(
		$db,
		"INSERT INTO clipboard_checklist (uname, item_key, dismissed, completed, updated_at)
		 VALUES ('$escU', '$escK', " . ($field === 'dismissed' ? $val : 0) . ', ' . ($field === 'completed' ? $val : 0) . ", NOW())
		 ON DUPLICATE KEY UPDATE `$field` = $val, updated_at = NOW()"
	);
}

/**
 * Evaluate auto conditions and complete checklist items + award achievements.
 *
 * @param array<string,bool> $sessionFlags e.g. visited_clipboard, visited_results
 * @return list<string> newly completed item keys
 */
function choosology_clipboard_sync_checklist(mysqli $db, string $uname, array $sessionFlags = array()): array
{
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$completedNow = array();

	$hasAdv = false;
	$hasPublic = false;
	$r = mysqli_query($db, "SELECT avail FROM advs WHERE user = '$escU' LIMIT 50");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$hasAdv = true;
			if ((string) ($row['avail'] ?? '') === 'public') {
				$hasPublic = true;
			}
		}
	}

	$hasNote = false;
	$nr = mysqli_query($db, "SELECT LENGTH(TRIM(body)) AS L FROM clipboard_pad WHERE uname = '$escU' LIMIT 1");
	if ($nr && ($nrow = mysqli_fetch_assoc($nr)) && (int) ($nrow['L'] ?? 0) > 0) {
		$hasNote = true;
	}

	$hasTodo = false;
	$tr = mysqli_query($db, "SELECT id FROM clipboard_todos WHERE uname = '$escU' LIMIT 1");
	if ($tr && mysqli_num_rows($tr) > 0) {
		$hasTodo = true;
	}

	$hasScreenwright = false;
	$ar = mysqli_query($db, "SELECT achievement_key FROM user_achievements WHERE uname = '$escU' AND achievement_key = 'screenwright' LIMIT 1");
	if ($ar && mysqli_num_rows($ar) > 0) {
		$hasScreenwright = true;
	}

	$autoMap = array(
		'has_adventure' => $hasAdv,
		'has_public' => $hasPublic,
		'has_note' => $hasNote,
		'has_todo' => $hasTodo,
		'has_screenwright' => $hasScreenwright,
		'visited_clipboard' => !empty($sessionFlags['visited_clipboard']),
		'visited_results' => !empty($sessionFlags['visited_results']),
	);

	$state = array();
	$sr = mysqli_query($db, "SELECT item_key, dismissed, completed FROM clipboard_checklist WHERE uname = '$escU'");
	if ($sr) {
		while ($row = mysqli_fetch_assoc($sr)) {
			$state[(string) $row['item_key']] = array(
				'dismissed' => (int) ($row['dismissed'] ?? 0) === 1,
				'completed' => (int) ($row['completed'] ?? 0) === 1,
			);
		}
	}

	foreach (choosology_clipboard_checklist_defs() as $def) {
		$key = $def['key'];
		$st = $state[$key] ?? array('dismissed' => false, 'completed' => false);
		if ($st['completed'] || $st['dismissed']) {
			continue;
		}
		$auto = $def['auto'];
		if (!empty($autoMap[$auto])) {
			choosology_clipboard_set_checklist_flag($db, $uname, $key, 'completed', 1);
			choosology_award_achievement($db, $uname, $def['achievement']);
			$completedNow[] = $key;
		}
	}

	return $completedNow;
}

/**
 * @return array{body:string,updated_at:?string}
 */
function choosology_clipboard_get_pad(mysqli $db, string $uname): array
{
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query($db, "SELECT body, updated_at FROM clipboard_pad WHERE uname = '$escU' LIMIT 1");
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		return array(
			'body' => (string) ($row['body'] ?? ''),
			'updated_at' => (string) ($row['updated_at'] ?? ''),
		);
	}
	return array('body' => '', 'updated_at' => null);
}

function choosology_clipboard_save_pad(mysqli $db, string $uname, string $body): bool
{
	choosology_clipboard_ensure_schema($db);
	if (strlen($body) > 50000) {
		$body = substr($body, 0, 50000);
	}
	$escU = mysqli_real_escape_string($db, $uname);
	$escB = mysqli_real_escape_string($db, $body);
	$ok = mysqli_query(
		$db,
		"INSERT INTO clipboard_pad (uname, body, updated_at) VALUES ('$escU', '$escB', NOW())
		 ON DUPLICATE KEY UPDATE body = VALUES(body), updated_at = NOW()"
	);
	if ($ok && trim($body) !== '') {
		choosology_award_achievement($db, $uname, 'scribbler');
		choosology_clipboard_set_checklist_flag($db, $uname, 'write_note', 'completed', 1);
	}
	return (bool) $ok;
}

/**
 * @return list<array{id:int,body:string,done:bool,created_at:string,done_at:?string}>
 */
function choosology_clipboard_list_todos(mysqli $db, string $uname): array
{
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$out = array();
	$r = mysqli_query(
		$db,
		"SELECT id, body, done, created_at, done_at FROM clipboard_todos
		 WHERE uname = '$escU' ORDER BY done ASC, sort_order ASC, id ASC LIMIT 100"
	);
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$out[] = array(
				'id' => (int) ($row['id'] ?? 0),
				'body' => (string) ($row['body'] ?? ''),
				'done' => (int) ($row['done'] ?? 0) === 1,
				'created_at' => (string) ($row['created_at'] ?? ''),
				'done_at' => isset($row['done_at']) && $row['done_at'] !== null && $row['done_at'] !== ''
					? (string) $row['done_at'] : null,
			);
		}
	}
	return $out;
}

function choosology_clipboard_add_todo(mysqli $db, string $uname, string $body): int
{
	$body = trim($body);
	if ($body === '') {
		return 0;
	}
	if (strlen($body) > 500) {
		$body = substr($body, 0, 500);
	}
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$escB = mysqli_real_escape_string($db, $body);
	$ok = mysqli_query(
		$db,
		"INSERT INTO clipboard_todos (uname, body, done, sort_order, created_at)
		 VALUES ('$escU', '$escB', 0, 0, NOW())"
	);
	if (!$ok) {
		return 0;
	}
	$id = (int) mysqli_insert_id($db);
	choosology_award_achievement($db, $uname, 'taskmaster');
	choosology_clipboard_set_checklist_flag($db, $uname, 'add_todo', 'completed', 1);
	return $id;
}

function choosology_clipboard_set_todo_done(mysqli $db, string $uname, int $id, bool $done): bool
{
	if ($id < 1) {
		return false;
	}
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$doneSql = $done ? '1, done_at = NOW()' : '0, done_at = NULL';
	return (bool) mysqli_query(
		$db,
		"UPDATE clipboard_todos SET done = $doneSql WHERE id = $id AND uname = '$escU' LIMIT 1"
	);
}

function choosology_clipboard_delete_todo(mysqli $db, string $uname, int $id): bool
{
	if ($id < 1) {
		return false;
	}
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	return (bool) mysqli_query($db, "DELETE FROM clipboard_todos WHERE id = $id AND uname = '$escU' LIMIT 1");
}

/**
 * @return array{id:int,title:string,edited:?string}|null
 */
function choosology_clipboard_last_edited_adv(mysqli $db, string $uname): ?array
{
	$escU = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query(
		$db,
		"SELECT id, title, edited FROM advs WHERE user = '$escU' ORDER BY edited DESC, id DESC LIMIT 1"
	);
	if (!$r || !($row = mysqli_fetch_assoc($r))) {
		return null;
	}
	return array(
		'id' => (int) ($row['id'] ?? 0),
		'title' => (string) ($row['title'] ?? 'Untitled'),
		'edited' => (string) ($row['edited'] ?? ''),
	);
}

/**
 * @return list<array<string,mixed>>
 */
function choosology_clipboard_experiment_metrics(mysqli $db, string $uname): array
{
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$rows = array();
	$r = mysqli_query(
		$db,
		"SELECT id, title, avail, rating, edited, published, play_starts, last_played, totalwordcount
		 FROM advs WHERE user = '$escU' ORDER BY edited DESC, id DESC LIMIT 100"
	);
	if (!$r) {
		return $rows;
	}
	while ($adv = mysqli_fetch_assoc($r)) {
		$advid = (int) ($adv['id'] ?? 0);
		$screens = 0;
		$sr = mysqli_query(
			$db,
			"SELECT COUNT(*) AS c FROM advscreens WHERE advused = '$advid' AND IFNULL(deleted,0) NOT IN (1,'1')"
		);
		if ($sr && ($srow = mysqli_fetch_assoc($sr))) {
			$screens = (int) ($srow['c'] ?? 0);
		}
		$comments = 0;
		$cr = mysqli_query(
			$db,
			"SELECT COUNT(*) AS c FROM comments WHERE whichboard = 'adv$advid'"
		);
		if ($cr && ($crow = mysqli_fetch_assoc($cr))) {
			$comments = (int) ($crow['c'] ?? 0);
		}
		$endingFinds = 0;
		$er = @mysqli_query(
			$db,
			"SELECT COUNT(*) AS c FROM ending_finds WHERE adv = $advid"
		);
		if ($er && ($erow = mysqli_fetch_assoc($er))) {
			$endingFinds = (int) ($erow['c'] ?? 0);
		}
		$ratings = 0;
		$rr = @mysqli_query($db, "SELECT COUNT(*) AS c FROM ratings WHERE adv = $advid");
		if ($rr && ($rrow = mysqli_fetch_assoc($rr))) {
			$ratings = (int) ($rrow['c'] ?? 0);
		}
		$rows[] = array(
			'id' => $advid,
			'title' => (string) ($adv['title'] ?? 'Untitled'),
			'avail' => (string) ($adv['avail'] ?? 'none'),
			'rating' => (string) ($adv['rating'] ?? 'NA'),
			'rating_count' => $ratings,
			'edited' => (string) ($adv['edited'] ?? ''),
			'published' => (string) ($adv['published'] ?? ''),
			'play_starts' => (int) ($adv['play_starts'] ?? 0),
			'last_played' => (string) ($adv['last_played'] ?? ''),
			'wordcount' => (int) ($adv['totalwordcount'] ?? 0),
			'screens' => $screens,
			'comments' => $comments,
			'ending_finds' => $endingFinds,
		);
	}
	return $rows;
}

function choosology_adv_record_play_start(mysqli $db, int $advid): void
{
	if ($advid < 1) {
		return;
	}
	choosology_clipboard_ensure_schema($db);
	@mysqli_query(
		$db,
		"UPDATE advs SET play_starts = play_starts + 1, last_played = NOW() WHERE id = $advid LIMIT 1"
	);
	/* Daily play DataScrip for signed-in researchers. */
	if (!empty($_SESSION['user'])) {
		if (!function_exists('choosology_datascrip_try_daily_play')) {
			require_once __DIR__ . '/datascrip-helpers.php';
		}
		choosology_datascrip_try_daily_play($db, (string) $_SESSION['user']);
	}
}

function choosology_adv_touch_edited(mysqli $db, int $advid, string $owner): void
{
	if ($advid < 1 || $owner === '') {
		return;
	}
	$escU = mysqli_real_escape_string($db, $owner);
	@mysqli_query($db, "UPDATE advs SET edited = NOW() WHERE id = $advid AND user = '$escU' LIMIT 1");
}
