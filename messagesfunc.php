<?php
/**
 * Messaging helpers: send, notify, report, digests.
 * Prefer choosology_send_message() for new code (returns bool, no HTML echo).
 * Expects connect.php / $db already available.
 */

/**
 * Resolve a username from id or name. Returns null if not found / ambiguous.
 */
function choosology_resolve_username($userRef): ?string
{
	global $db;
	$ref = trim((string) $userRef);
	if ($ref === '') {
		return null;
	}
	$esc = mysqli_real_escape_string($db, $ref);
	if (ctype_digit($ref)) {
		$res = mysqli_query($db, "SELECT name FROM users WHERE id = '$esc' LIMIT 1");
	} else {
		$res = mysqli_query($db, "SELECT name FROM users WHERE name = '$esc' LIMIT 1");
	}
	if (!$res || mysqli_num_rows($res) !== 1) {
		return null;
	}
	$row = mysqli_fetch_assoc($res);
	$name = trim((string) ($row['name'] ?? ''));
	return $name !== '' ? $name : null;
}

/**
 * Insert a message. Returns new id or 0 on failure.
 */
function choosology_send_message(
	string $toUser,
	string $fromUser,
	string $title,
	string $body,
	string $type = 'normal'
): int {
	global $db;
	$to = choosology_resolve_username($toUser);
	if ($to === null) {
		return 0;
	}
	$from = trim($fromUser);
	if ($from === '') {
		return 0;
	}
	$type = preg_replace('/[^a-z0-9_-]/i', '', $type) ?: 'normal';
	if (strlen($title) > 55) {
		$title = substr($title, 0, 55);
	}
	$tEsc = mysqli_real_escape_string($db, $title);
	$bEsc = mysqli_real_escape_string($db, $body);
	$toEsc = mysqli_real_escape_string($db, $to);
	$fromEsc = mysqli_real_escape_string($db, $from);
	$typeEsc = mysqli_real_escape_string($db, $type);
	$ok = mysqli_query(
		$db,
		"INSERT INTO messages (to_user, from_user, sent_date, body, seen, message_type, title, to_deleted, from_deleted)
		 VALUES ('$toEsc', '$fromEsc', NOW(), '$bEsc', 0, '$typeEsc', '$tEsc', 0, 0)"
	);
	if (!$ok) {
		return 0;
	}
	return (int) mysqli_insert_id($db);
}

/** @deprecated Prefer choosology_send_message(); kept for legacy callers. */
function sendMessage($touser, $fromuser, $messagetitle, $messagebody, $type = 'normal')
{
	$id = choosology_send_message((string) $touser, (string) $fromuser, (string) $messagetitle, (string) $messagebody, (string) $type);
	if ($id > 0) {
		echo "<div class='success'>Message sent!</div>";
		return true;
	}
	echo "<div class='error'>Couldn't figure out which user to send to!</div>";
	return false;
}

function choosology_unread_message_count(?string $user = null): int
{
	global $db;
	$user = $user !== null ? $user : (string) ($_SESSION['user'] ?? '');
	if ($user === '') {
		return 0;
	}
	$esc = mysqli_real_escape_string($db, $user);
	$res = mysqli_query(
		$db,
		"SELECT COUNT(*) AS c FROM messages
		 WHERE to_user = '$esc' AND seen = 0 AND IFNULL(to_deleted,0) = 0"
	);
	if (!$res) {
		return 0;
	}
	$row = mysqli_fetch_assoc($res);
	return (int) ($row['c'] ?? 0);
}

/** Admin usernames (usertype >= 1). */
function choosology_admin_usernames(): array
{
	global $db;
	$out = array();
	$res = mysqli_query($db, 'SELECT name FROM users WHERE usertype >= 1 ORDER BY id ASC');
	if (!$res) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($res)) {
		$n = trim((string) ($row['name'] ?? ''));
		if ($n !== '') {
			$out[] = $n;
		}
	}
	return $out;
}

/**
 * Report a message to all admins. Returns number of admin messages created.
 */
function choosology_report_message(int $messageId, string $reporter, string $note = ''): int
{
	global $db;
	if ($messageId < 1 || $reporter === '') {
		return 0;
	}
	$res = mysqli_query($db, 'SELECT * FROM messages WHERE id = ' . $messageId . ' LIMIT 1');
	if (!$res || mysqli_num_rows($res) < 1) {
		return 0;
	}
	$msg = mysqli_fetch_assoc($res);
	$admins = choosology_admin_usernames();
	if ($admins === array()) {
		return 0;
	}
	$title = 'Report: message #' . $messageId;
	if (strlen($title) > 55) {
		$title = substr($title, 0, 55);
	}
	$origTitle = htmlspecialchars((string) ($msg['title'] ?? ''), ENT_QUOTES, 'UTF-8');
	$origFrom = htmlspecialchars((string) ($msg['from_user'] ?? ''), ENT_QUOTES, 'UTF-8');
	$origTo = htmlspecialchars((string) ($msg['to_user'] ?? ''), ENT_QUOTES, 'UTF-8');
	$origBody = htmlspecialchars((string) ($msg['body'] ?? ''), ENT_QUOTES, 'UTF-8');
	$noteEsc = htmlspecialchars(trim($note), ENT_QUOTES, 'UTF-8');
	$reporterEsc = htmlspecialchars($reporter, ENT_QUOTES, 'UTF-8');
	$body = '<p><strong>' . $reporterEsc . '</strong> reported a message.</p>';
	if ($noteEsc !== '') {
		$body .= '<p>Note: ' . $noteEsc . '</p>';
	}
	$body .= '<p>From: <strong>' . $origFrom . '</strong> → To: <strong>' . $origTo . '</strong><br>';
	$body .= 'Sent: ' . htmlspecialchars((string) ($msg['sent_date'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
	$body .= 'Title: ' . $origTitle . '</p>';
	$body .= '<blockquote style="border-left:3px solid #999;padding-left:8px;margin:8px 0;color:#333">' . nl2br($origBody) . '</blockquote>';
	$body .= '<p><a href="#/mystuff/messages?id=' . $messageId . '">Open in Messages</a></p>';

	$sent = 0;
	foreach ($admins as $admin) {
		if (strcasecmp($admin, $reporter) === 0) {
			continue;
		}
		if (choosology_send_message($admin, $reporter, $title, $body, 'report') > 0) {
			$sent++;
		}
	}
	return $sent;
}

/**
 * Notify adventure/profile owner of a new comment (immediate system message).
 * Adventure comments only fire for published (public) experiments.
 */
function checkSendMessage($user, $board, $screen = 0)
{
	global $db;
	$user = (string) $user;
	$board = (string) $board;
	if (substr($board, 0, 4) === 'user') {
		$userid = substr($board, 4);
		$q = "SELECT name FROM users WHERE id='" . mysqli_real_escape_string($db, $userid) . "' LIMIT 1";
		$result = mysqli_query($db, $q);
		if (!$result || mysqli_num_rows($result) !== 1) {
			return false;
		}
		$res = mysqli_fetch_array($result);
		if ((string) $res[0] === $user) {
			return false;
		}
		$title = "$user commented on your profile";
		$body = 'See the new comment <a href="#/mystuff">in My Stuff</a> or on your profile.';
		choosology_send_message((string) $res[0], 'Choosology', $title, $body, 'system');
		return true;
	}
	if (substr($board, 0, 3) === 'adv') {
		$advid = (int) substr($board, 3);
		if ($advid < 1) {
			return false;
		}
		$q = "SELECT id, user, title, avail FROM advs WHERE id = '$advid' LIMIT 1";
		$result = mysqli_query($db, $q);
		if (!$result || mysqli_num_rows($result) !== 1) {
			return false;
		}
		$adv = mysqli_fetch_assoc($result);
		$owner = (string) ($adv['user'] ?? '');
		if ($owner === '' || $owner === $user) {
			return false;
		}
		if ((string) ($adv['avail'] ?? '') !== 'public') {
			return false;
		}
		$screenQ = ((int) $screen > 0) ? ('&screen=' . (int) $screen) : '';
		$advTitle = htmlspecialchars(strip_tags(html_entity_decode((string) ($adv['title'] ?? 'Experiment'))), ENT_QUOTES, 'UTF-8');
		$title = "$user commented on your experiment";
		if (strlen($title) > 55) {
			$title = substr($title, 0, 55);
		}
		$body = '<p><strong>' . htmlspecialchars($user, ENT_QUOTES, 'UTF-8') . '</strong> left a comment on ';
		$body .= '<em>' . $advTitle . '</em>.</p>';
		$body .= '<p><a href="#/view/' . $advid . '">Open experiment</a>';
		if ($screenQ !== '') {
			$body .= ' · <a href="view.php?id=' . $advid . $screenQ . '">Open screen</a>';
		}
		$body .= '</p>';
		choosology_send_message($owner, 'Choosology', $title, $body, 'system');
		return true;
	}
	return false;
}

function convertBoardName($name)
{
	global $db;
	switch (substr($name, 0, 3)) {
		case 'use':
			$user = substr($name, 4);
			$q = "select name from users where id = '$user'";
			$after = "'s profile";
			$href = "profile.php?user=$user";
			break;
		case 'new':
			$newsitem = substr($name, 4);
			$q = "select headline from news where id = '$newsitem'";
			$after = ' (News)';
			$href = "index.php?newsitem=$newsitem";
			break;
		case 'adv':
			$adv = substr($name, 3);
			$q = "select title from advs where id = '$adv'";
			$after = '';
			$href = "#/view/$adv";
			break;
		default:
			return false;
	}
	$result = mysqli_query($db, $q);
	if (!$result) {
		return false;
	}
	$res = mysqli_fetch_array($result);
	$text = $res[0] ?? '';
	if (!$text) {
		return false;
	}
	$text = strip_tags(html_entity_decode($text));
	if ($href) {
		$text = '&lt;a class="link" onclick="location.href=\'' . $href . '\'" &gt;' . $text . '&lt;/a&gt;';
	}
	if ($after) {
		$text = $text . $after;
	}
	return $text;
}

/**
 * Ensure digest columns exist (idempotent for local/dev VMs).
 */
function choosology_ensure_digest_columns(): void
{
	global $db;
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	$cols = array();
	$res = mysqli_query($db, 'SHOW COLUMNS FROM advs');
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$cols[strtolower((string) $row['Field'])] = true;
		}
	}
	if (!isset($cols['digest_notify'])) {
		@mysqli_query($db, "ALTER TABLE advs ADD COLUMN digest_notify VARCHAR(16) NOT NULL DEFAULT 'off' AFTER tags");
	}
	if (!isset($cols['digest_last_sent'])) {
		@mysqli_query($db, 'ALTER TABLE advs ADD COLUMN digest_last_sent DATETIME NULL DEFAULT NULL AFTER digest_notify');
	}
}

/**
 * Build and send activity digests for adventures whose window has elapsed.
 * Returns number of digests sent.
 */
function choosology_run_adventure_digests(?string $onlyOwner = null): int
{
	global $db;
	choosology_ensure_digest_columns();
	$ownerClause = '';
	if ($onlyOwner !== null && $onlyOwner !== '') {
		$ownerClause = " AND user = '" . mysqli_real_escape_string($db, $onlyOwner) . "'";
	}
	$q = "SELECT id, user, title, digest_notify, digest_last_sent, published
		FROM advs
		WHERE digest_notify IN ('daily','weekly')
		  AND avail = 'public'
		  $ownerClause";
	$res = mysqli_query($db, $q);
	if (!$res) {
		return 0;
	}
	$sent = 0;
	$now = time();
	while ($adv = mysqli_fetch_assoc($res)) {
		$mode = (string) ($adv['digest_notify'] ?? 'off');
		$hours = ($mode === 'weekly') ? (24 * 7) : 24;
		$last = $adv['digest_last_sent'] ?? null;
		$lastTs = ($last && $last !== '0000-00-00 00:00:00') ? strtotime((string) $last) : 0;
		if ($lastTs && ($now - $lastTs) < ($hours * 3600 - 60)) {
			continue;
		}
		$sinceSql = $lastTs
			? ("'" . mysqli_real_escape_string($db, date('Y-m-d H:i:s', $lastTs)) . "'")
			: ("DATE_SUB(NOW(), INTERVAL $hours HOUR)");
		$advid = (int) $adv['id'];
		$board = 'adv' . $advid;

		$cRes = mysqli_query(
			$db,
			"SELECT COUNT(*) AS c FROM comments
			 WHERE whichboard = '" . mysqli_real_escape_string($db, $board) . "'
			   AND `date` > $sinceSql"
		);
		$cRow = $cRes ? mysqli_fetch_assoc($cRes) : null;
		$commentCount = (int) ($cRow['c'] ?? 0);

		$rRes = mysqli_query(
			$db,
			"SELECT COUNT(*) AS c, ROUND(AVG(rating),2) AS avg_r FROM ratings
			 WHERE adv = $advid"
		);
		// Ratings table has no timestamp — include current aggregate + count of ratings by who
		// Use ending_finds / paths if available for "activity"; keep ratings summary always
		$rRow = $rRes ? mysqli_fetch_assoc($rRes) : null;
		$ratingCount = (int) ($rRow['c'] ?? 0);
		$ratingAvg = $rRow['avg_r'] ?? null;

		// New ending finds in window (proxy for playthrough activity)
		$eRes = mysqli_query(
			$db,
			"SELECT COUNT(*) AS c FROM ending_finds
			 WHERE adv = $advid AND found_at > $sinceSql"
		);
		$endingCount = 0;
		if ($eRes) {
			$eRow = mysqli_fetch_assoc($eRes);
			$endingCount = (int) ($eRow['c'] ?? 0);
		}

		if ($commentCount < 1 && $endingCount < 1 && $ratingCount < 1) {
			// Still advance the window so we do not spam empty checks forever without progress
			mysqli_query($db, "UPDATE advs SET digest_last_sent = NOW() WHERE id = $advid LIMIT 1");
			continue;
		}
		// Skip empty digests when nothing meaningful happened
		if ($commentCount < 1 && $endingCount < 1) {
			mysqli_query($db, "UPDATE advs SET digest_last_sent = NOW() WHERE id = $advid LIMIT 1");
			continue;
		}

		$periodLabel = ($mode === 'weekly') ? 'weekly' : 'daily';
		$advTitle = htmlspecialchars(strip_tags(html_entity_decode((string) ($adv['title'] ?? 'Experiment'))), ENT_QUOTES, 'UTF-8');
		$title = ucfirst($periodLabel) . ' digest: ' . substr(strip_tags(html_entity_decode((string) ($adv['title'] ?? 'Experiment'))), 0, 40);
		if (strlen($title) > 55) {
			$title = substr($title, 0, 55);
		}
		$body = '<p>Activity digest for <em>' . $advTitle . '</em> (' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . ').</p><ul>';
		$body .= '<li>New comments: <strong>' . $commentCount . '</strong></li>';
		if ($endingCount > 0) {
			$body .= '<li>Ending discoveries: <strong>' . $endingCount . '</strong></li>';
		}
		if ($ratingCount > 0) {
			$body .= '<li>Ratings on file: <strong>' . $ratingCount . '</strong>';
			if ($ratingAvg !== null) {
				$body .= ' (avg ' . htmlspecialchars((string) $ratingAvg, ENT_QUOTES, 'UTF-8') . '★)';
			}
			$body .= '</li>';
		}
		$body .= '</ul><p><a href="#/view/' . $advid . '">Open experiment</a> · <a href="#/mystuff/messages">Inbox</a></p>';

		$owner = (string) ($adv['user'] ?? '');
		if ($owner !== '' && choosology_send_message($owner, 'Choosology', $title, $body, 'digest') > 0) {
			$sent++;
			mysqli_query($db, "UPDATE advs SET digest_last_sent = NOW() WHERE id = $advid LIMIT 1");
		}
	}
	return $sent;
}
