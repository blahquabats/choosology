<?php
/**
 * Adventure review queue: enqueue on publish, hold under probation/flags,
 * and graduate / restrict / reject from the Classic Admin hub.
 */

require_once __DIR__ . '/choosology-core.php';

/** Content flags that force a discovery hold until review. */
const CHOOSOLOGY_REVIEW_HOLD_FLAGS = array('mature', 'sensitive', 'force_hold');

/**
 * Ensure adventure-review columns exist (idempotent).
 */
function choosology_adventure_review_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	$advsCols = array();
	$r = @mysqli_query($db, 'SHOW COLUMNS FROM advs');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$advsCols[(string) ($row['Field'] ?? '')] = true;
		}
	}
	if (empty($advsCols['review_status'])) {
		@mysqli_query(
			$db,
			"ALTER TABLE advs ADD COLUMN review_status varchar(16) NOT NULL DEFAULT 'none' AFTER avail"
		);
	}
	if (empty($advsCols['review_hold'])) {
		@mysqli_query(
			$db,
			"ALTER TABLE advs ADD COLUMN review_hold tinyint(1) NOT NULL DEFAULT 0 AFTER review_status"
		);
	}
	if (empty($advsCols['content_flags'])) {
		@mysqli_query(
			$db,
			"ALTER TABLE advs ADD COLUMN content_flags varchar(255) NOT NULL DEFAULT '' AFTER review_hold"
		);
	}

	$userCols = array();
	$ur = @mysqli_query($db, 'SHOW COLUMNS FROM users');
	if ($ur) {
		while ($row = mysqli_fetch_assoc($ur)) {
			$userCols[(string) ($row['Field'] ?? '')] = true;
		}
	}
	if (empty($userCols['probation'])) {
		@mysqli_query(
			$db,
			"ALTER TABLE users ADD COLUMN probation tinyint(1) NOT NULL DEFAULT 0 AFTER usertype"
		);
	}

	/* One-shot: treat already-listed adventures as cleared so the queue stays empty of legacy rows. */
	@mysqli_query(
		$db,
		"UPDATE advs
		 SET review_status='cleared', review_hold=0
		 WHERE avail IN ('public','restricted') AND review_status='none'"
	);
}

/**
 * @param list<string>|string $flags
 * @return list<string>
 */
function choosology_adventure_review_normalize_flags($flags): array
{
	if (is_string($flags)) {
		$flags = preg_split('/[\s,]+/', strtolower($flags)) ?: array();
	}
	if (!is_array($flags)) {
		return array();
	}
	$out = array();
	foreach ($flags as $flag) {
		$flag = strtolower(trim((string) $flag));
		$flag = preg_replace('/[^a-z0-9_]/', '', $flag) ?: '';
		if ($flag === '' || in_array($flag, $out, true)) {
			continue;
		}
		$out[] = $flag;
	}
	return $out;
}

function choosology_adventure_review_flags_csv($flags): string
{
	return implode(',', choosology_adventure_review_normalize_flags($flags));
}

function choosology_adventure_review_flags_force_hold($flags): bool
{
	$normalized = choosology_adventure_review_normalize_flags($flags);
	foreach (CHOOSOLOGY_REVIEW_HOLD_FLAGS as $holdFlag) {
		if (in_array($holdFlag, $normalized, true)) {
			return true;
		}
	}
	return false;
}

function choosology_adventure_review_user_on_probation(mysqli $db, string $username): bool
{
	choosology_adventure_review_ensure_schema($db);
	$username = trim($username);
	if ($username === '') {
		return false;
	}
	$esc = mysqli_real_escape_string($db, $username);
	$r = mysqli_query($db, "SELECT probation FROM users WHERE name='$esc' LIMIT 1");
	$row = $r ? mysqli_fetch_assoc($r) : null;
	return $row ? ((int) ($row['probation'] ?? 0) === 1) : false;
}

/**
 * @return array{ok:bool,error?:string,user?:string,probation?:bool}
 */
function choosology_adventure_review_set_probation(
	mysqli $db,
	string $target,
	bool $onProbation
): array {
	choosology_adventure_review_ensure_schema($db);
	require_once __DIR__ . '/restricted-helpers.php';
	$name = choosology_restricted_find_user($db, $target);
	if ($name === null) {
		return array('ok' => false, 'error' => 'Unknown researcher.');
	}
	$bit = $onProbation ? 1 : 0;
	$esc = mysqli_real_escape_string($db, $name);
	$ok = mysqli_query($db, "UPDATE users SET probation=$bit WHERE name='$esc' LIMIT 1");
	if (!$ok) {
		return array('ok' => false, 'error' => 'Could not update probation.');
	}
	return array('ok' => true, 'user' => $name, 'probation' => $onProbation);
}

/**
 * Apply review-queue state after an availability change.
 *
 * @param list<string>|string $contentFlags
 * @return array{ok:bool,error?:string,review_status?:string,review_hold?:int,content_flags?:string}
 */
function choosology_adventure_review_on_avail_change(
	mysqli $db,
	int $advid,
	string $owner,
	string $newAvail,
	string $oldAvail,
	$contentFlags = ''
): array {
	choosology_adventure_review_ensure_schema($db);
	if ($advid < 1) {
		return array('ok' => false, 'error' => 'Invalid experiment id.');
	}
	$flagsCsv = choosology_adventure_review_flags_csv($contentFlags);
	$flagsEsc = mysqli_real_escape_string($db, $flagsCsv);
	$id = (int) $advid;

	$listed = ($newAvail === 'public' || $newAvail === 'restricted');
	$wasListed = ($oldAvail === 'public' || $oldAvail === 'restricted');
	$hold = (
		choosology_adventure_review_user_on_probation($db, $owner)
		|| choosology_adventure_review_flags_force_hold($flagsCsv)
	) ? 1 : 0;

	if ($listed && !$wasListed) {
		/* Fresh public/restricted listing enters the review queue. */
		$ok = mysqli_query(
			$db,
			"UPDATE advs
			 SET review_status='pending',
			     review_hold=$hold,
			     content_flags='$flagsEsc'
			 WHERE id=$id LIMIT 1"
		);
		if (!$ok) {
			return array('ok' => false, 'error' => 'Could not enqueue adventure for review.');
		}
		return array(
			'ok' => true,
			'review_status' => 'pending',
			'review_hold' => $hold,
			'content_flags' => $flagsCsv,
		);
	}

	if ($listed && $wasListed) {
		/* Already listed: keep graduation state; only refresh flags / pending hold. */
		$cur = mysqli_query($db, "SELECT review_status FROM advs WHERE id=$id LIMIT 1");
		$curRow = $cur ? mysqli_fetch_assoc($cur) : null;
		$status = (string) ($curRow['review_status'] ?? 'pending');
		if ($status === 'none' || $status === 'rejected') {
			$status = 'pending';
		}
		$holdSql = ($status === 'pending') ? $hold : 0;
		$ok = mysqli_query(
			$db,
			"UPDATE advs
			 SET review_status='$status',
			     review_hold=$holdSql,
			     content_flags='$flagsEsc'
			 WHERE id=$id LIMIT 1"
		);
		if (!$ok) {
			return array('ok' => false, 'error' => 'Could not update review state.');
		}
		return array(
			'ok' => true,
			'review_status' => $status,
			'review_hold' => $holdSql,
			'content_flags' => $flagsCsv,
		);
	}

	/* Leaving the public/restricted shelf clears queue membership. */
	$ok = mysqli_query(
		$db,
		"UPDATE advs
		 SET review_status='none',
		     review_hold=0,
		     content_flags='$flagsEsc'
		 WHERE id=$id LIMIT 1"
	);
	if (!$ok) {
		return array('ok' => false, 'error' => 'Could not update review state.');
	}
	return array(
		'ok' => true,
		'review_status' => 'none',
		'review_hold' => 0,
		'content_flags' => $flagsCsv,
	);
}

/**
 * SQL fragment for browse/home/search discovery (alias-aware).
 *
 * Listed = public (or restricted when the viewer opted in), never held.
 */
function choosology_adventure_discovery_sql(string $alias = 'a', ?bool $includeRestricted = null): string
{
	$alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias) ?: 'a';
	if ($includeRestricted === null) {
		$includeRestricted = (!empty($_SESSION['view_restricted']) && (int) $_SESSION['view_restricted'] === 1)
			|| (
				!empty($_SESSION['usertype'])
				&& (int) $_SESSION['usertype'] >= 1
			);
	}
	$hold = "IFNULL({$alias}.review_hold,0)=0";
	$parts = array("({$alias}.avail='public' AND $hold)");
	if ($includeRestricted) {
		$parts[] = "({$alias}.avail='restricted' AND $hold)";
	}
	return '(' . implode(' OR ', $parts) . ')';
}

/**
 * Whether a viewer may open an adventure outside browse (direct link / play).
 *
 * @param array<string,mixed> $adv
 */
function choosology_adventure_can_view(array $adv, string $viewer = ''): bool
{
	$owner = (string) ($adv['user'] ?? '');
	$avail = (string) ($adv['avail'] ?? 'none');
	$hold = (int) ($adv['review_hold'] ?? 0) === 1;
	$viewer = $viewer !== '' ? $viewer : (string) ($_SESSION['user'] ?? '');
	$isOwner = ($viewer !== '' && $viewer === $owner);
	$isAdmin = !empty($_SESSION['usertype']) && (int) $_SESSION['usertype'] >= 1;

	if ($isOwner || $isAdmin) {
		return true;
	}
	if ($hold) {
		return false;
	}
	if ($avail === 'public') {
		return true;
	}
	if ($avail === 'restricted') {
		return !empty($_SESSION['view_restricted']) && (int) $_SESSION['view_restricted'] === 1;
	}
	return false;
}

/**
 * @return list<array<string,mixed>>
 */
function choosology_adventure_review_pending(mysqli $db, int $limit = 100): array
{
	choosology_adventure_review_ensure_schema($db);
	$limit = max(1, min(250, $limit));
	$out = array();
	$r = mysqli_query(
		$db,
		"SELECT a.id, a.title, a.user, a.avail, a.description, a.tags, a.content_flags,
		        a.review_status, a.review_hold, a.published, a.edited,
		        IFNULL(u.probation,0) AS author_probation
		 FROM advs a
		 LEFT JOIN users u ON u.name = a.user
		 WHERE a.review_status='pending'
		 ORDER BY COALESCE(a.published, a.edited) ASC, a.id ASC
		 LIMIT $limit"
	);
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$flags = choosology_adventure_review_normalize_flags((string) ($row['content_flags'] ?? ''));
		$holdReasons = array();
		if ((int) ($row['author_probation'] ?? 0) === 1) {
			$holdReasons[] = 'probation';
		}
		foreach (CHOOSOLOGY_REVIEW_HOLD_FLAGS as $flag) {
			if (in_array($flag, $flags, true)) {
				$holdReasons[] = $flag;
			}
		}
		$out[] = array(
			'id' => (int) ($row['id'] ?? 0),
			'title' => (string) ($row['title'] ?? ''),
			'user' => (string) ($row['user'] ?? ''),
			'avail' => (string) ($row['avail'] ?? ''),
			'description' => (string) ($row['description'] ?? ''),
			'tags' => (string) ($row['tags'] ?? ''),
			'content_flags' => $flags,
			'review_status' => (string) ($row['review_status'] ?? ''),
			'review_hold' => (int) ($row['review_hold'] ?? 0) === 1,
			'author_probation' => (int) ($row['author_probation'] ?? 0) === 1,
			'hold_reasons' => $holdReasons,
			'published' => (string) ($row['published'] ?? ''),
			'edited' => (string) ($row['edited'] ?? ''),
		);
	}
	return $out;
}

/**
 * Permanently remove an adventure (admin moderation path).
 *
 * @return array{ok:bool,error?:string,title?:string,owner?:string}
 */
function choosology_adventure_review_delete(mysqli $db, int $advid): array
{
	if ($advid < 1) {
		return array('ok' => false, 'error' => 'Invalid experiment id.');
	}
	$idEsc = mysqli_real_escape_string($db, (string) $advid);
	$legacyEsc = mysqli_real_escape_string($db, ':' . (string) $advid . ':');
	$r = mysqli_query($db, "SELECT id, title, user FROM advs WHERE id='$idEsc' LIMIT 1");
	$row = $r ? mysqli_fetch_assoc($r) : null;
	if (!$row) {
		return array('ok' => false, 'error' => 'Experiment not found.');
	}
	$title = (string) ($row['title'] ?? '');
	$owner = (string) ($row['user'] ?? '');
	$ownerEsc = mysqli_real_escape_string($db, $owner);

	mysqli_begin_transaction($db);
	try {
		if (!mysqli_query($db, "DELETE FROM ratings WHERE adv='$idEsc'")) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (!mysqli_query($db, "DELETE FROM comments WHERE whichboard='adv$idEsc'")) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (!mysqli_query($db, "DELETE FROM paths WHERE adv='$idEsc'")) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (!mysqli_query(
			$db,
			"DELETE FROM advscreens WHERE advused IN ('$idEsc', '$legacyEsc') AND user='$ownerEsc'"
		)) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (!mysqli_query($db, "DELETE FROM advs WHERE id='$idEsc' LIMIT 1")) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (mysqli_affected_rows($db) < 1) {
			throw new RuntimeException('No experiment row deleted.');
		}
		mysqli_commit($db);
		return array('ok' => true, 'title' => $title, 'owner' => $owner);
	} catch (Throwable $e) {
		mysqli_rollback($db);
		return array('ok' => false, 'error' => 'Could not remove experiment.');
	}
}

/**
 * @return array{ok:bool,error?:string,action?:string,id?:int,message_id?:int}
 */
function choosology_adventure_review_moderate(
	mysqli $db,
	string $admin,
	string $action,
	int $advid,
	string $note = ''
): array {
	choosology_adventure_review_ensure_schema($db);
	$admin = trim($admin);
	$action = trim($action);
	$note = trim($note);
	if ($admin === '' || $advid < 1) {
		return array('ok' => false, 'error' => 'Admin and experiment id are required.');
	}
	if (!in_array($action, array('accept', 'accept_restricted', 'reject'), true)) {
		return array('ok' => false, 'error' => 'Invalid adventure review action.');
	}

	$idEsc = mysqli_real_escape_string($db, (string) $advid);
	$r = mysqli_query(
		$db,
		"SELECT id, title, user, avail, review_status
		 FROM advs WHERE id='$idEsc' LIMIT 1"
	);
	$row = $r ? mysqli_fetch_assoc($r) : null;
	if (!$row) {
		return array('ok' => false, 'error' => 'Experiment not found.');
	}
	if ((string) ($row['review_status'] ?? '') !== 'pending') {
		return array('ok' => false, 'error' => 'Experiment is not awaiting review.');
	}

	$title = (string) ($row['title'] ?? 'Untitled');
	$owner = (string) ($row['user'] ?? '');

	if ($action === 'accept') {
		$ok = mysqli_query(
			$db,
			"UPDATE advs
			 SET review_status='cleared', review_hold=0, avail='public'
			 WHERE id='$idEsc' LIMIT 1"
		);
		if (!$ok) {
			return array('ok' => false, 'error' => 'Could not accept experiment.');
		}
		return array('ok' => true, 'action' => 'accept', 'id' => $advid);
	}

	if ($action === 'accept_restricted') {
		$ok = mysqli_query(
			$db,
			"UPDATE advs
			 SET review_status='restricted', review_hold=0, avail='restricted'
			 WHERE id='$idEsc' LIMIT 1"
		);
		if (!$ok) {
			return array('ok' => false, 'error' => 'Could not mark experiment Restricted.');
		}
		return array('ok' => true, 'action' => 'accept_restricted', 'id' => $advid);
	}

	/* reject */
	if ($note === '') {
		return array('ok' => false, 'error' => 'A brief note for the creator is required when rejecting.');
	}
	if (strlen($note) > 500) {
		$note = substr($note, 0, 500);
	}
	$deleted = choosology_adventure_review_delete($db, $advid);
	if (empty($deleted['ok'])) {
		return array('ok' => false, 'error' => $deleted['error'] ?? 'Could not remove experiment.');
	}

	$messageId = 0;
	if ($owner !== '' && function_exists('choosology_send_message')) {
		$body = 'Your experiment “' . $title . '” was not accepted for public listing and has been removed.'
			. "\n\nModerator note:\n" . $note;
		$messageId = (int) choosology_send_message(
			$owner,
			$admin,
			'Experiment not accepted',
			$body,
			'system'
		);
	}

	return array(
		'ok' => true,
		'action' => 'reject',
		'id' => $advid,
		'message_id' => $messageId,
	);
}
