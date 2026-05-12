<?php
/**
 * Create a new adventure (advs + first advscreen + begin) for the signed-in user.
 * JSON body: { "title": "..." } — avoids connect.php mutating POST strings.
 */
ob_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

if (empty($_SESSION['user'])) {
	echo json_encode(array('ok' => 0, 'error' => 'Not signed in.'), $jsonFlags);
	exit;
}

$user = (string) $_SESSION['user'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
	echo json_encode(array('ok' => 0, 'error' => 'Invalid JSON.'), $jsonFlags);
	exit;
}

$title = isset($data['title']) ? trim((string) $data['title']) : '';
if ($title === '') {
	echo json_encode(array('ok' => 0, 'error' => 'Please enter a name for the experiment.'), $jsonFlags);
	exit;
}
if (strlen($title) > 255) {
	$title = substr($title, 0, 255);
}

$escUser = mysqli_real_escape_string($db, $user);
$escTitle = mysqli_real_escape_string($db, $title);
$now = date('Y-m-d H:i:s');

mysqli_begin_transaction($db);
try {
	$q1 = "INSERT INTO advs (`user`, `created`, `edited`, `title`, `totalwordcount`, `avail`)
		VALUES ('$escUser', '$now', '$now', '$escTitle', 0, 'none')";
	if (!mysqli_query($db, $q1)) {
		throw new RuntimeException(mysqli_error($db));
	}
	$newAdvId = (int) mysqli_insert_id($db);
	if ($newAdvId < 1) {
		throw new RuntimeException('No adventure id returned.');
	}

	$escAdv = mysqli_real_escape_string($db, (string) $newAdvId);
	$screenName = function_exists('mb_substr')
		? mb_substr($title, 0, 60, 'UTF-8')
		: substr($title, 0, 60);
	if ($screenName === '') {
		$screenName = 'Start';
	}
	$escScreenName = mysqli_real_escape_string($db, $screenName);

	$q2 = "INSERT INTO advscreens (
			`user`, `title`, `name`, `text`,
			`screenbgcolor`, `screenboxcolor`, `screenbordercolor`, `screenborderwidth`,
			`use_defaults`, `created`, `advused`, `xpos`, `ypos`, `deleted`, `wordcount`
		) VALUES (
			'$escUser',
			'',
			'$escScreenName',
			'',
			'#ffffff',
			'#ccddff',
			'#9999cc',
			2,
			1,
			'$now',
			'$escAdv',
			100,
			100,
			0,
			0
		)";
	if (!mysqli_query($db, $q2)) {
		throw new RuntimeException(mysqli_error($db));
	}
	$newScreenId = (int) mysqli_insert_id($db);
	if ($newScreenId < 1) {
		throw new RuntimeException('No screen id returned.');
	}

	$q3 = "UPDATE advs SET `begin` = '" . $newScreenId . "' WHERE id = '$newAdvId' AND user = '$escUser' LIMIT 1";
	if (!mysqli_query($db, $q3)) {
		throw new RuntimeException(mysqli_error($db));
	}

	mysqli_commit($db);
	echo json_encode(array('ok' => 1, 'id' => $newAdvId), $jsonFlags);
} catch (Throwable $e) {
	mysqli_rollback($db);
	$err = 'Could not create experiment.';
	if (getenv('CHOOSOLOGY_DEBUG') === '1') {
		$err .= ' ' . $e->getMessage();
	}
	echo json_encode(array('ok' => 0, 'error' => $err), $jsonFlags);
}
exit;
