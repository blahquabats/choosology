<?php
/**
 * DataScrip — in-lab currency (denoted with the UAE Dirham sign د.إ).
 */

require_once __DIR__ . '/choosology-core.php';

/** UAE Dirham sign used as the DataScrip glyph. */
function choosology_datascrip_sign(): string
{
	return 'د.إ';
}

function choosology_datascrip_format(int $amount, bool $withSign = true): string
{
	$n = number_format($amount, 0, '.', ',');
	if (!$withSign) {
		return $n;
	}
	return choosology_datascrip_sign() . ' ' . $n;
}

/**
 * Reward amounts for achievements and recurring activities.
 *
 * @return array{achievements: array<string,int>, activities: array<string,int>}
 */
function choosology_datascrip_reward_catalog(): array
{
	return array(
		'achievements' => array(
			'lab_initiate' => 25,
			'screenwright' => 15,
			'field_release' => 40,
			'clipboard_clerk' => 10,
			'scribbler' => 10,
			'taskmaster' => 10,
			'results_analyst' => 15,
		),
		'activities' => array(
			'daily_login' => 5,
			'daily_play' => 3,
			'ending_find' => 2,
		),
	);
}

function choosology_datascrip_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	try {
		mysqli_query(
			$db,
			'CREATE TABLE IF NOT EXISTS datascrip_balances (
				uname varchar(45) NOT NULL,
				balance int NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY (uname)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		mysqli_query(
			$db,
			'CREATE TABLE IF NOT EXISTS datascrip_ledger (
				id int unsigned NOT NULL AUTO_INCREMENT,
				uname varchar(45) NOT NULL,
				amount int NOT NULL,
				balance_after int NOT NULL,
				reason_key varchar(64) NOT NULL,
				memo varchar(255) NOT NULL DEFAULT \'\',
				actor varchar(45) DEFAULT NULL,
				idempotency_key varchar(128) NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY datascrip_ledger_user_idem (uname, idempotency_key),
				KEY datascrip_ledger_user_created (uname, created_at),
				KEY datascrip_ledger_created (created_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
	} catch (Throwable $e) {
		$done = false;
	}
}

function choosology_datascrip_balance(mysqli $db, string $uname): int
{
	$uname = trim($uname);
	if ($uname === '') {
		return 0;
	}
	choosology_datascrip_ensure_schema($db);
	$esc = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query($db, "SELECT balance FROM datascrip_balances WHERE uname = '$esc' LIMIT 1");
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		return (int) ($row['balance'] ?? 0);
	}
	return 0;
}

/**
 * Credit (positive) or debit (negative) DataScrip. Idempotent on (uname, idempotency_key).
 *
 * @return array{ok:bool,applied:bool,balance:int,amount:int,id?:int,error?:string}
 */
function choosology_datascrip_apply(
	mysqli $db,
	string $uname,
	int $amount,
	string $reasonKey,
	string $memo = '',
	?string $actor = null,
	?string $idempotencyKey = null
): array {
	$uname = trim($uname);
	$reasonKey = trim($reasonKey);
	$memo = trim($memo);
	if ($uname === '' || $reasonKey === '' || $amount === 0) {
		return array('ok' => false, 'applied' => false, 'balance' => 0, 'amount' => 0, 'error' => 'Invalid grant.');
	}
	if (strlen($memo) > 255) {
		$memo = substr($memo, 0, 255);
	}
	if (strlen($reasonKey) > 64) {
		$reasonKey = substr($reasonKey, 0, 64);
	}
	choosology_datascrip_ensure_schema($db);

	if ($idempotencyKey === null || $idempotencyKey === '') {
		$idempotencyKey = $reasonKey . ':' . bin2hex(random_bytes(8));
	}
	if (strlen($idempotencyKey) > 128) {
		$idempotencyKey = substr($idempotencyKey, 0, 128);
	}

	$escU = mysqli_real_escape_string($db, $uname);
	$escReason = mysqli_real_escape_string($db, $reasonKey);
	$escMemo = mysqli_real_escape_string($db, $memo);
	$escIdem = mysqli_real_escape_string($db, $idempotencyKey);
	$escActor = $actor !== null && $actor !== ''
		? ("'" . mysqli_real_escape_string($db, $actor) . "'")
		: 'NULL';

	$dup = mysqli_query(
		$db,
		"SELECT id, balance_after FROM datascrip_ledger
		 WHERE uname = '$escU' AND idempotency_key = '$escIdem' LIMIT 1"
	);
	if ($dup && ($drow = mysqli_fetch_assoc($dup))) {
		return array(
			'ok' => true,
			'applied' => false,
			'balance' => (int) ($drow['balance_after'] ?? choosology_datascrip_balance($db, $uname)),
			'amount' => $amount,
			'id' => (int) ($drow['id'] ?? 0),
		);
	}

	mysqli_begin_transaction($db);
	try {
		$br = mysqli_query($db, "SELECT balance FROM datascrip_balances WHERE uname = '$escU' LIMIT 1 FOR UPDATE");
		$balance = 0;
		$hasRow = false;
		if ($br && ($brow = mysqli_fetch_assoc($br))) {
			$hasRow = true;
			$balance = (int) ($brow['balance'] ?? 0);
		}
		$newBal = $balance + $amount;
		if ($newBal < 0) {
			mysqli_rollback($db);
			return array(
				'ok' => false,
				'applied' => false,
				'balance' => $balance,
				'amount' => $amount,
				'error' => 'Insufficient DataScrip.',
			);
		}
		if ($hasRow) {
			mysqli_query($db, "UPDATE datascrip_balances SET balance = $newBal, updated_at = NOW() WHERE uname = '$escU'");
		} else {
			mysqli_query(
				$db,
				"INSERT INTO datascrip_balances (uname, balance, updated_at) VALUES ('$escU', $newBal, NOW())"
			);
		}
		$amt = (int) $amount;
		$okIns = mysqli_query(
			$db,
			"INSERT INTO datascrip_ledger
				(uname, amount, balance_after, reason_key, memo, actor, idempotency_key, created_at)
			 VALUES
				('$escU', $amt, $newBal, '$escReason', '$escMemo', $escActor, '$escIdem', NOW())"
		);
		if (!$okIns) {
			mysqli_rollback($db);
			/* Unique race: treat as already applied */
			$dup2 = mysqli_query(
				$db,
				"SELECT id, balance_after FROM datascrip_ledger
				 WHERE uname = '$escU' AND idempotency_key = '$escIdem' LIMIT 1"
			);
			if ($dup2 && ($d2 = mysqli_fetch_assoc($dup2))) {
				return array(
					'ok' => true,
					'applied' => false,
					'balance' => (int) ($d2['balance_after'] ?? 0),
					'amount' => $amount,
					'id' => (int) ($d2['id'] ?? 0),
				);
			}
			return array('ok' => false, 'applied' => false, 'balance' => $balance, 'amount' => $amount, 'error' => 'Ledger write failed.');
		}
		$id = (int) mysqli_insert_id($db);
		mysqli_commit($db);
		return array('ok' => true, 'applied' => true, 'balance' => $newBal, 'amount' => $amount, 'id' => $id);
	} catch (Throwable $e) {
		mysqli_rollback($db);
		return array('ok' => false, 'applied' => false, 'balance' => choosology_datascrip_balance($db, $uname), 'amount' => $amount, 'error' => 'Transaction failed.');
	}
}

/**
 * @return list<array{id:int,amount:int,balance_after:int,reason_key:string,memo:string,actor:?string,created_at:string,label:string}>
 */
function choosology_datascrip_ledger_entries(mysqli $db, string $uname, int $limit = 20, int $offset = 0): array
{
	$uname = trim($uname);
	if ($uname === '') {
		return array();
	}
	choosology_datascrip_ensure_schema($db);
	$limit = max(1, min(100, $limit));
	$offset = max(0, $offset);
	$esc = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query(
		$db,
		"SELECT id, amount, balance_after, reason_key, memo, actor, created_at
		 FROM datascrip_ledger
		 WHERE uname = '$esc'
		 ORDER BY created_at DESC, id DESC
		 LIMIT $limit OFFSET $offset"
	);
	$out = array();
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$out[] = array(
			'id' => (int) ($row['id'] ?? 0),
			'amount' => (int) ($row['amount'] ?? 0),
			'balance_after' => (int) ($row['balance_after'] ?? 0),
			'reason_key' => (string) ($row['reason_key'] ?? ''),
			'memo' => (string) ($row['memo'] ?? ''),
			'actor' => isset($row['actor']) && $row['actor'] !== null && $row['actor'] !== '' ? (string) $row['actor'] : null,
			'created_at' => (string) ($row['created_at'] ?? ''),
			'label' => choosology_datascrip_reason_label((string) ($row['reason_key'] ?? ''), (string) ($row['memo'] ?? '')),
		);
	}
	return $out;
}

function choosology_datascrip_ledger_count(mysqli $db, string $uname): int
{
	$uname = trim($uname);
	if ($uname === '') {
		return 0;
	}
	choosology_datascrip_ensure_schema($db);
	$esc = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM datascrip_ledger WHERE uname = '$esc'");
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		return (int) ($row['c'] ?? 0);
	}
	return 0;
}

function choosology_datascrip_reason_label(string $reasonKey, string $memo = ''): string
{
	if ($memo !== '') {
		return $memo;
	}
	if (strpos($reasonKey, 'achievement:') === 0) {
		$key = substr($reasonKey, strlen('achievement:'));
		if (function_exists('choosology_achievement_catalog')) {
			$cat = choosology_achievement_catalog();
			if (isset($cat[$key]['label'])) {
				return 'Degree: ' . $cat[$key]['label'];
			}
		}
		return 'Degree award';
	}
	switch ($reasonKey) {
		case 'daily_login':
			return 'Daily lab check-in';
		case 'daily_play':
			return 'Played an experiment today';
		case 'ending_find':
			return 'Catalogued an end screen';
		case 'admin_grant':
			return 'Admin DataScrip award';
		case 'admin_grant_all':
			return 'Lab-wide DataScrip award';
		default:
			return $reasonKey !== '' ? $reasonKey : 'DataScrip movement';
	}
}

/** Calendar day in DB session timezone (matches connect.php time_zone). */
function choosology_datascrip_db_day(mysqli $db): string
{
	$r = @mysqli_query($db, 'SELECT CURDATE() AS d');
	if ($r && ($row = mysqli_fetch_assoc($r)) && !empty($row['d'])) {
		return (string) $row['d'];
	}
	return date('Y-m-d');
}

/**
 * Daily login grant (once per calendar day, DB timezone).
 *
 * @return array{ok:bool,applied:bool,balance:int,amount:int}
 */
function choosology_datascrip_try_daily_login(mysqli $db, string $uname): array
{
	$catalog = choosology_datascrip_reward_catalog();
	$amount = (int) ($catalog['activities']['daily_login'] ?? 5);
	$day = choosology_datascrip_db_day($db);
	return choosology_datascrip_apply(
		$db,
		$uname,
		$amount,
		'daily_login',
		'Daily lab check-in (' . $day . ')',
		null,
		'daily_login:' . $day
	);
}

/**
 * Award DataScrip when a new achievement is earned.
 *
 * @return array{ok:bool,applied:bool,balance:int,amount:int}
 */
function choosology_datascrip_on_achievement(mysqli $db, string $uname, string $achievementKey): array
{
	$catalog = choosology_datascrip_reward_catalog();
	$amount = (int) ($catalog['achievements'][$achievementKey] ?? 0);
	if ($amount < 1) {
		return array('ok' => true, 'applied' => false, 'balance' => choosology_datascrip_balance($db, $uname), 'amount' => 0);
	}
	$label = $achievementKey;
	if (function_exists('choosology_achievement_catalog')) {
		$ach = choosology_achievement_catalog();
		if (isset($ach[$achievementKey]['label'])) {
			$label = (string) $ach[$achievementKey]['label'];
		}
	}
	return choosology_datascrip_apply(
		$db,
		$uname,
		$amount,
		'achievement:' . $achievementKey,
		'Degree: ' . $label,
		null,
		'achievement:' . $achievementKey
	);
}

function choosology_datascrip_try_daily_play(mysqli $db, string $uname): array
{
	$uname = trim($uname);
	if ($uname === '') {
		return array('ok' => true, 'applied' => false, 'balance' => 0, 'amount' => 0);
	}
	$catalog = choosology_datascrip_reward_catalog();
	$amount = (int) ($catalog['activities']['daily_play'] ?? 3);
	$day = choosology_datascrip_db_day($db);
	return choosology_datascrip_apply(
		$db,
		$uname,
		$amount,
		'daily_play',
		'Played an experiment (' . $day . ')',
		null,
		'daily_play:' . $day
	);
}

function choosology_datascrip_try_ending_find(mysqli $db, string $uname, int $advid, int $screenid): array
{
	$uname = trim($uname);
	if ($uname === '' || $advid < 1 || $screenid < 1) {
		return array('ok' => true, 'applied' => false, 'balance' => 0, 'amount' => 0);
	}
	$catalog = choosology_datascrip_reward_catalog();
	$amount = (int) ($catalog['activities']['ending_find'] ?? 2);
	return choosology_datascrip_apply(
		$db,
		$uname,
		$amount,
		'ending_find',
		'Catalogued end screen #' . $screenid . ' in experiment #' . $advid,
		null,
		'ending_find:' . $advid . ':' . $screenid
	);
}

/**
 * Admin award to one user or every user.
 *
 * @return array{ok:bool,error?:string,granted:int,amount:int,targets:int}
 */
function choosology_datascrip_admin_grant(
	mysqli $db,
	string $adminUname,
	string $target,
	int $amount,
	string $memo = '',
	bool $allUsers = false
): array {
	$adminUname = trim($adminUname);
	$target = trim($target);
	$memo = trim($memo);
	if ($adminUname === '' || $amount < 1) {
		return array('ok' => false, 'error' => 'Admin and a positive amount are required.', 'granted' => 0, 'amount' => 0, 'targets' => 0);
	}
	if ($amount > 100000) {
		return array('ok' => false, 'error' => 'Amount too large (max 100000).', 'granted' => 0, 'amount' => 0, 'targets' => 0);
	}
	choosology_datascrip_ensure_schema($db);

	$names = array();
	if ($allUsers) {
		$r = mysqli_query($db, 'SELECT name FROM users ORDER BY id ASC');
		if ($r) {
			while ($row = mysqli_fetch_assoc($r)) {
				$n = trim((string) ($row['name'] ?? ''));
				if ($n !== '') {
					$names[] = $n;
				}
			}
		}
	} else {
		if ($target === '') {
			return array('ok' => false, 'error' => 'Recipient username required.', 'granted' => 0, 'amount' => 0, 'targets' => 0);
		}
		$escT = mysqli_real_escape_string($db, $target);
		$chk = mysqli_query($db, "SELECT name FROM users WHERE name = '$escT' LIMIT 1");
		if (!$chk || mysqli_num_rows($chk) < 1) {
			return array('ok' => false, 'error' => 'Unknown recipient.', 'granted' => 0, 'amount' => 0, 'targets' => 0);
		}
		$row = mysqli_fetch_assoc($chk);
		$names[] = (string) $row['name'];
	}

	if (!$names) {
		return array('ok' => false, 'error' => 'No recipients found.', 'granted' => 0, 'amount' => 0, 'targets' => 0);
	}

	$reason = $allUsers ? 'admin_grant_all' : 'admin_grant';
	if ($memo === '') {
		$memo = $allUsers
			? ('Lab-wide award from ' . $adminUname)
			: ('Award from ' . $adminUname);
	}
	$batch = date('YmdHis');
	$granted = 0;
	foreach ($names as $name) {
		$idem = $allUsers
			? ('admin_all:' . $adminUname . ':' . $batch . ':' . $name)
			: ('admin:' . $adminUname . ':' . $batch . ':' . $name);
		$res = choosology_datascrip_apply($db, $name, $amount, $reason, $memo, $adminUname, $idem);
		if (!empty($res['ok']) && !empty($res['applied'])) {
			$granted++;
			if (function_exists('choosology_send_message')) {
				$body = 'You received <strong>' . htmlspecialchars(choosology_datascrip_format($amount), ENT_QUOTES, 'UTF-8')
					. '</strong> DataScrip.';
				if ($memo !== '') {
					$body .= '<br>' . htmlspecialchars($memo, ENT_QUOTES, 'UTF-8');
				}
				$body .= '<br><span class="lite-muted">Awarded by lab admin '
					. htmlspecialchars($adminUname, ENT_QUOTES, 'UTF-8') . '.</span>';
				choosology_send_message($name, 'Choosology', 'DataScrip award', $body, 'system');
			}
		}
	}

	return array(
		'ok' => $granted > 0,
		'granted' => $granted,
		'amount' => $amount,
		'targets' => count($names),
		'error' => $granted > 0 ? null : 'No grants applied.',
	);
}

/**
 * Compact payload for header / clipboard.
 *
 * @return array{balance:int,formatted:string,sign:string,recent:list}
 */
function choosology_datascrip_summary_for_user(mysqli $db, string $uname, int $recentLimit = 5): array
{
	$balance = choosology_datascrip_balance($db, $uname);
	return array(
		'balance' => $balance,
		'formatted' => choosology_datascrip_format($balance),
		'sign' => choosology_datascrip_sign(),
		'recent' => choosology_datascrip_ledger_entries($db, $uname, $recentLimit, 0),
	);
}
