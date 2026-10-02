<?php
/**
 * Ending-find helpers (need mysqli; safe without authent.php).
 * Requires choosology_screen_is_ending() from lib/choosology-core.php.
 */

function choosology_ensure_ending_finds_table(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	@mysqli_query(
		$db,
		'CREATE TABLE IF NOT EXISTS ending_finds (
			id int unsigned NOT NULL AUTO_INCREMENT,
			uname varchar(45) NOT NULL,
			adv int unsigned NOT NULL,
			screen int unsigned NOT NULL,
			found_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY ending_finds_user_adv_screen (uname, adv, screen),
			KEY ending_finds_adv_user (adv, uname)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	);
}

/**
 * @return list<int>
 */
function choosology_adventure_ending_ids(mysqli $db, int $advid): array
{
	$advid = max(0, $advid);
	if ($advid < 1) {
		return array();
	}
	$begin = '';
	$br = mysqli_query($db, 'SELECT begin FROM advs WHERE id = ' . $advid . ' LIMIT 1');
	if ($br && ($row = mysqli_fetch_assoc($br))) {
		$begin = (string) ($row['begin'] ?? '');
	}
	$ids = array();
	$sr = mysqli_query(
		$db,
		'SELECT * FROM advscreens WHERE advused = ' . $advid . ' AND IFNULL(deleted, 0) NOT IN (1, \'1\')'
	);
	if (!$sr) {
		return array();
	}
	while ($screen = mysqli_fetch_assoc($sr)) {
		if (choosology_screen_is_ending($screen, $begin)) {
			$ids[] = (int) ($screen['id'] ?? 0);
		}
	}
	return array_values(array_filter($ids, static function ($id) {
		return $id > 0;
	}));
}

/**
 * Record that the current visitor found an ending; return progress for the UI.
 *
 * @return array{found:int,more:bool,total:int}
 */
function choosology_record_ending_find(mysqli $db, int $advid, int $screenid): array
{
	$advid = max(0, $advid);
	$screenid = max(0, $screenid);
	$endingIds = choosology_adventure_ending_ids($db, $advid);
	$total = count($endingIds);
	$endingSet = array_fill_keys($endingIds, true);

	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (!isset($_SESSION['ending_finds']) || !is_array($_SESSION['ending_finds'])) {
		$_SESSION['ending_finds'] = array();
	}
	if (!isset($_SESSION['ending_finds'][$advid]) || !is_array($_SESSION['ending_finds'][$advid])) {
		$_SESSION['ending_finds'][$advid] = array();
	}

	$user = '';
	if (!empty($_SESSION['user'])) {
		$user = (string) $_SESSION['user'];
	}

	if ($user !== '') {
		choosology_ensure_ending_finds_table($db);
		$escUser = mysqli_real_escape_string($db, $user);
		$existing = mysqli_query(
			$db,
			"SELECT screen FROM ending_finds WHERE uname = '$escUser' AND adv = $advid"
		);
		if ($existing) {
			while ($row = mysqli_fetch_assoc($existing)) {
				$sid = (int) ($row['screen'] ?? 0);
				if ($sid > 0 && isset($endingSet[$sid])) {
					$_SESSION['ending_finds'][$advid][$sid] = 1;
				}
			}
		}
	}

	if ($screenid > 0 && isset($endingSet[$screenid])) {
		$_SESSION['ending_finds'][$advid][$screenid] = 1;
		if ($user !== '') {
			$escUser = mysqli_real_escape_string($db, $user);
			@mysqli_query(
				$db,
				"INSERT IGNORE INTO ending_finds (uname, adv, screen, found_at)
				 VALUES ('$escUser', $advid, $screenid, NOW())"
			);
		}
	}

	$found = 0;
	foreach ($_SESSION['ending_finds'][$advid] as $sid => $_) {
		$sid = (int) $sid;
		if (isset($endingSet[$sid])) {
			$found++;
		}
	}

	return array(
		'found' => $found,
		'more' => ($total > 0 && $found < $total),
		'total' => $total,
	);
}
