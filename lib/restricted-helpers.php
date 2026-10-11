<?php
/**
 * Shared admin-only actions for the RESTRICTED hub.
 *
 * Callers must still enforce the session gate. These helpers validate targets
 * so Classic, Lite, and JSON endpoints use the same rules.
 */

require_once __DIR__ . '/clipboard-helpers.php';
require_once __DIR__ . '/datascrip-helpers.php';

function choosology_restricted_is_admin(): bool
{
	return !empty($_SESSION['user'])
		&& !empty($_SESSION['usertype'])
		&& (int) $_SESSION['usertype'] >= 1;
}

/**
 * @return list<string>
 */
function choosology_restricted_usernames(mysqli $db): array
{
	$names = array();
	$r = mysqli_query($db, 'SELECT name FROM users ORDER BY name ASC');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$name = trim((string) ($row['name'] ?? ''));
			if ($name !== '') {
				$names[] = $name;
			}
		}
	}
	return $names;
}

/**
 * @return array{ok:bool,error?:string,already?:bool,user?:string,degree?:string,label?:string}
 */
function choosology_restricted_grant_degree(
	mysqli $db,
	string $admin,
	string $target,
	string $degreeKey
): array {
	$admin = trim($admin);
	$target = trim($target);
	$degreeKey = trim($degreeKey);
	$catalog = choosology_achievement_catalog();
	if ($admin === '' || $target === '') {
		return array('ok' => false, 'error' => 'Admin and recipient are required.');
	}
	if (!isset($catalog[$degreeKey])) {
		return array('ok' => false, 'error' => 'Unknown Degree.');
	}
	choosology_clipboard_ensure_schema($db);
	$escTarget = mysqli_real_escape_string($db, $target);
	$ur = mysqli_query($db, "SELECT name FROM users WHERE name='$escTarget' LIMIT 1");
	if (!$ur || !($userRow = mysqli_fetch_assoc($ur))) {
		return array('ok' => false, 'error' => 'Unknown recipient.');
	}
	$target = (string) $userRow['name'];
	$escDegree = mysqli_real_escape_string($db, $degreeKey);
	$earned = mysqli_query(
		$db,
		"SELECT id FROM user_achievements
		 WHERE uname='$escTarget' AND achievement_key='$escDegree' LIMIT 1"
	);
	if ($earned && mysqli_fetch_assoc($earned)) {
		return array(
			'ok' => false,
			'already' => true,
			'error' => $target . ' already has this Degree.',
		);
	}
	if (!choosology_award_achievement($db, $target, $degreeKey)) {
		return array('ok' => false, 'error' => 'The Degree could not be granted.');
	}

	if (!function_exists('choosology_send_message')) {
		$messages = dirname(__DIR__) . '/messagesfunc.php';
		if (is_readable($messages)) {
			require_once $messages;
		}
	}
	$label = (string) $catalog[$degreeKey]['label'];
	if (function_exists('choosology_send_message')) {
		$body = 'You received the <strong>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
			. '</strong> Degree from lab admin '
			. htmlspecialchars($admin, ENT_QUOTES, 'UTF-8')
			. '.';
		choosology_send_message($target, 'Choosology', 'Degree awarded', $body, 'system');
	}

	return array(
		'ok' => true,
		'user' => $target,
		'degree' => $degreeKey,
		'label' => $label,
	);
}

/**
 * @return list<array{id:int,author:string,date:string,text:string,board:string,screen:int}>
 */
function choosology_restricted_pending_comments(mysqli $db, int $limit = 100): array
{
	$limit = max(1, min(250, $limit));
	$out = array();
	$r = mysqli_query(
		$db,
		"SELECT id, author, date, text, whichboard, whichscreen
		 FROM comments
		 WHERE reviewed=0
		 ORDER BY date ASC, id ASC
		 LIMIT $limit"
	);
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$out[] = array(
			'id' => (int) ($row['id'] ?? 0),
			'author' => (string) ($row['author'] ?? ''),
			'date' => (string) ($row['date'] ?? ''),
			'text' => (string) ($row['text'] ?? ''),
			'board' => (string) ($row['whichboard'] ?? ''),
			'screen' => (int) ($row['whichscreen'] ?? 0),
		);
	}
	return $out;
}

/**
 * @return array{ok:bool,error?:string,affected?:int}
 */
function choosology_restricted_moderate_comment(
	mysqli $db,
	string $action,
	int $commentId = 0
): array {
	if ($action === 'approve_all') {
		$ok = mysqli_query($db, 'UPDATE comments SET reviewed=1 WHERE reviewed=0');
		return $ok
			? array('ok' => true, 'affected' => mysqli_affected_rows($db))
			: array('ok' => false, 'error' => 'Comments could not be approved.');
	}
	if ($commentId < 1 || !in_array($action, array('approve', 'delete'), true)) {
		return array('ok' => false, 'error' => 'Invalid comment action.');
	}
	$verb = $action === 'approve'
		? 'UPDATE comments SET reviewed=1'
		: 'DELETE FROM comments';
	$ok = mysqli_query($db, "$verb WHERE id=$commentId LIMIT 1");
	if (!$ok || mysqli_affected_rows($db) < 1) {
		return array('ok' => false, 'error' => 'Comment not found.');
	}
	return array('ok' => true, 'affected' => 1);
}
