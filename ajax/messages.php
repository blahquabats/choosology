<?php
/**
 * Messages AJAX API.
 * POST JSON or form: action = list|get|send|report|unread|mark_seen|recent
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../messagesfunc.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

function choosology_messages_json($payload, int $flags): void
{
	echo json_encode($payload, $flags);
	exit;
}

if (empty($_SESSION['user'])) {
	choosology_messages_json(array('ok' => 0, 'error' => 'Not signed in.'), $jsonFlags);
}

$user = (string) $_SESSION['user'];
$userEsc = mysqli_real_escape_string($db, $user);

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
	$data = $_POST;
}
// Undo connect.php mutation on form POST scalars
foreach ($data as $k => $v) {
	if (is_string($v) && function_exists('choosology_undo_connect_string_mutation')) {
		$data[$k] = choosology_undo_connect_string_mutation($v);
	} elseif (is_string($v)) {
		$data[$k] = stripslashes(htmlspecialchars_decode($v, ENT_QUOTES | ENT_HTML5));
	}
}

$action = isset($data['action']) ? trim((string) $data['action']) : '';
if ($action === '' && isset($_GET['action'])) {
	$action = trim((string) $_GET['action']);
}

choosology_ensure_digest_columns();
// Opportunistic digest check for this user (cheap when windows not due)
if ($action === 'unread' || $action === 'recent' || $action === 'list') {
	@choosology_run_adventure_digests($user);
}

if ($action === 'unread') {
	choosology_messages_json(array(
		'ok' => 1,
		'unread' => choosology_unread_message_count($user),
	), $jsonFlags);
}

if ($action === 'recent') {
	$limit = isset($data['limit']) ? (int) $data['limit'] : 8;
	if ($limit < 1) {
		$limit = 8;
	}
	if ($limit > 20) {
		$limit = 20;
	}
	$res = mysqli_query(
		$db,
		"SELECT id, from_user, title, body, sent_date, seen, message_type
		 FROM messages
		 WHERE to_user = '$userEsc' AND IFNULL(to_deleted,0) = 0
		 ORDER BY sent_date DESC, id DESC
		 LIMIT $limit"
	);
	$items = array();
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$items[] = array(
				'id' => (int) $row['id'],
				'from' => (string) $row['from_user'],
				'title' => (string) $row['title'],
				'preview' => substr(trim(strip_tags(html_entity_decode((string) $row['body']))), 0, 120),
				'sent' => (string) $row['sent_date'],
				'seen' => (int) $row['seen'] === 1,
				'type' => (string) $row['message_type'],
			);
		}
	}
	choosology_messages_json(array(
		'ok' => 1,
		'unread' => choosology_unread_message_count($user),
		'items' => $items,
	), $jsonFlags);
}

if ($action === 'list') {
	$q = isset($data['q']) ? trim((string) $data['q']) : '';
	$folder = isset($data['folder']) ? trim((string) $data['folder']) : 'inbox';
	$page = isset($data['page']) ? max(1, (int) $data['page']) : 1;
	$per = 25;
	$offset = ($page - 1) * $per;

	if ($folder === 'sent') {
		$where = "from_user = '$userEsc' AND IFNULL(from_deleted,0) = 0";
	} else {
		$where = "to_user = '$userEsc' AND IFNULL(to_deleted,0) = 0";
		$folder = 'inbox';
	}
	if ($q !== '') {
		$like = mysqli_real_escape_string($db, '%' . $q . '%');
		$where .= " AND (title LIKE '$like' OR body LIKE '$like' OR from_user LIKE '$like' OR to_user LIKE '$like')";
	}
	$countRes = mysqli_query($db, "SELECT COUNT(*) AS c FROM messages WHERE $where");
	$total = 0;
	if ($countRes) {
		$cr = mysqli_fetch_assoc($countRes);
		$total = (int) ($cr['c'] ?? 0);
	}
	$res = mysqli_query(
		$db,
		"SELECT id, to_user, from_user, title, body, sent_date, seen, message_type
		 FROM messages WHERE $where
		 ORDER BY sent_date DESC, id DESC
		 LIMIT $per OFFSET $offset"
	);
	$items = array();
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$items[] = array(
				'id' => (int) $row['id'],
				'to' => (string) $row['to_user'],
				'from' => (string) $row['from_user'],
				'title' => (string) $row['title'],
				'preview' => substr(trim(strip_tags(html_entity_decode((string) $row['body']))), 0, 140),
				'sent' => (string) $row['sent_date'],
				'seen' => (int) $row['seen'] === 1,
				'type' => (string) $row['message_type'],
			);
		}
	}
	choosology_messages_json(array(
		'ok' => 1,
		'folder' => $folder,
		'page' => $page,
		'total' => $total,
		'unread' => choosology_unread_message_count($user),
		'items' => $items,
	), $jsonFlags);
}

if ($action === 'get') {
	$id = isset($data['id']) ? (int) $data['id'] : 0;
	if ($id < 1) {
		choosology_messages_json(array('ok' => 0, 'error' => 'Invalid id.'), $jsonFlags);
	}
	$res = mysqli_query(
		$db,
		"SELECT * FROM messages WHERE id = $id
		 AND ((to_user = '$userEsc' AND IFNULL(to_deleted,0) = 0)
		   OR (from_user = '$userEsc' AND IFNULL(from_deleted,0) = 0))
		 LIMIT 1"
	);
	if (!$res || mysqli_num_rows($res) < 1) {
		choosology_messages_json(array('ok' => 0, 'error' => 'Message not found.'), $jsonFlags);
	}
	$row = mysqli_fetch_assoc($res);
	if ((string) $row['to_user'] === $user && (int) $row['seen'] === 0) {
		mysqli_query($db, "UPDATE messages SET seen = 1 WHERE id = $id AND to_user = '$userEsc' LIMIT 1");
		$row['seen'] = 1;
	}
	choosology_messages_json(array(
		'ok' => 1,
		'unread' => choosology_unread_message_count($user),
		'message' => array(
			'id' => (int) $row['id'],
			'to' => (string) $row['to_user'],
			'from' => (string) $row['from_user'],
			'title' => (string) $row['title'],
			'body' => (string) $row['body'],
			'sent' => (string) $row['sent_date'],
			'seen' => (int) $row['seen'] === 1,
			'type' => (string) $row['message_type'],
			'can_reply' => ((string) $row['to_user'] === $user && !in_array((string) $row['message_type'], array('system', 'digest', 'report'), true)),
			'can_report' => ((string) $row['to_user'] === $user && (string) $row['from_user'] !== $user),
		),
	), $jsonFlags);
}

if ($action === 'mark_seen') {
	$id = isset($data['id']) ? (int) $data['id'] : 0;
	if ($id > 0) {
		mysqli_query($db, "UPDATE messages SET seen = 1 WHERE id = $id AND to_user = '$userEsc' LIMIT 1");
	} else {
		mysqli_query($db, "UPDATE messages SET seen = 1 WHERE to_user = '$userEsc' AND seen = 0");
	}
	choosology_messages_json(array('ok' => 1, 'unread' => choosology_unread_message_count($user)), $jsonFlags);
}

if ($action === 'send') {
	$to = isset($data['to']) ? trim((string) $data['to']) : '';
	$title = isset($data['title']) ? trim((string) $data['title']) : '';
	$body = isset($data['body']) ? trim((string) $data['body']) : '';
	$replyTo = isset($data['reply_to']) ? (int) $data['reply_to'] : 0;
	if ($to === '' || $body === '') {
		choosology_messages_json(array('ok' => 0, 'error' => 'Recipient and body are required.'), $jsonFlags);
	}
	if ($title === '') {
		$title = '(no subject)';
	}
	// Strip tags from user-composed body for safety; allow simple newlines
	$bodySafe = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
	$titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
	if ($replyTo > 0) {
		$chk = mysqli_query(
			$db,
			"SELECT id, from_user, to_user, title FROM messages WHERE id = $replyTo
			 AND to_user = '$userEsc' LIMIT 1"
		);
		if ($chk && mysqli_num_rows($chk) === 1) {
			$orig = mysqli_fetch_assoc($chk);
			$to = (string) $orig['from_user'];
			$ot = (string) ($orig['title'] ?? '');
			if ($title === '(no subject)' || $title === '') {
				$titleSafe = (stripos($ot, 'Re:') === 0) ? $ot : ('Re: ' . $ot);
				if (strlen($titleSafe) > 55) {
					$titleSafe = substr($titleSafe, 0, 55);
				}
			}
		}
	}
	$id = choosology_send_message($to, $user, $titleSafe, $bodySafe, 'normal');
	if ($id < 1) {
		choosology_messages_json(array('ok' => 0, 'error' => 'Could not send (unknown recipient?).'), $jsonFlags);
	}
	choosology_messages_json(array('ok' => 1, 'id' => $id, 'unread' => choosology_unread_message_count($user)), $jsonFlags);
}

if ($action === 'report') {
	$id = isset($data['id']) ? (int) $data['id'] : 0;
	$note = isset($data['note']) ? trim((string) $data['note']) : '';
	if ($id < 1) {
		choosology_messages_json(array('ok' => 0, 'error' => 'Invalid id.'), $jsonFlags);
	}
	$chk = mysqli_query(
		$db,
		"SELECT id FROM messages WHERE id = $id AND to_user = '$userEsc' LIMIT 1"
	);
	if (!$chk || mysqli_num_rows($chk) < 1) {
		choosology_messages_json(array('ok' => 0, 'error' => 'Message not found.'), $jsonFlags);
	}
	$n = choosology_report_message($id, $user, $note);
	if ($n < 1) {
		choosology_messages_json(array('ok' => 0, 'error' => 'No admin recipients available.'), $jsonFlags);
	}
	choosology_messages_json(array('ok' => 1, 'reported_to' => $n), $jsonFlags);
}

choosology_messages_json(array('ok' => 0, 'error' => 'Unknown action.'), $jsonFlags);
