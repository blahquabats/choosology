<?php
/**
 * Update experiment-level fields (advs) for the owner. JSON body to avoid connect.php mutating POST strings.
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

$advid = isset($data['advid']) ? (int) $data['advid'] : 0;
if ($advid < 1) {
	echo json_encode(array('ok' => 0, 'error' => 'Invalid adventure id.'), $jsonFlags);
	exit;
}

$escUser = mysqli_real_escape_string($db, $user);
$escAdvid = mysqli_real_escape_string($db, (string) $advid);
$own = runquery_assoc("SELECT id, avail, published FROM advs WHERE id = '$escAdvid' AND user = '$escUser' LIMIT 1");
if (!is_array($own) || count($own) === 0) {
	echo json_encode(array('ok' => 0, 'error' => 'Adventure not found or not yours.'), $jsonFlags);
	exit;
}
$oldAvail = (string) ($own[0]['avail'] ?? '');
$oldPublished = $own[0]['published'] ?? null;

$title = isset($data['title']) ? trim((string) $data['title']) : '';
if ($title === '') {
	echo json_encode(array('ok' => 0, 'error' => 'Title is required.'), $jsonFlags);
	exit;
}
if (strlen($title) > 255) {
	$title = substr($title, 0, 255);
}

$description = isset($data['description']) ? trim((string) $data['description']) : '';
if (strlen($description) > 275) {
	$description = substr($description, 0, 275);
}

$tags = isset($data['tags']) ? trim((string) $data['tags']) : '';
$tagParts = array_values(array_filter(array_map('trim', explode(',', $tags)), static function ($x) {
	return $x !== '';
}));
$tagParts = array_slice($tagParts, 0, 10);
$tagParts = array_map(static function ($x) {
	return substr($x, 0, 50);
}, $tagParts);
$tags = implode(',', $tagParts);
if (strlen($tags) > 1024) {
	$tags = substr($tags, 0, 1024);
}

$avail = isset($data['avail']) ? trim((string) $data['avail']) : 'none';
if (!in_array($avail, array('public', 'private', 'none'), true)) {
	$avail = 'none';
}

$beginRaw = isset($data['begin']) ? trim((string) $data['begin']) : '';
$beginSql = 'NULL';
if ($beginRaw !== '') {
	if (!ctype_digit($beginRaw)) {
		echo json_encode(array('ok' => 0, 'error' => 'Starting screen id is invalid.'), $jsonFlags);
		exit;
	}
	$beginId = (int) $beginRaw;
	$escAdvid = mysqli_real_escape_string($db, (string) $advid);
	$chk = runquery_assoc(
		"SELECT id FROM advscreens WHERE id = '$beginId' AND advused = '$escAdvid' AND IFNULL(deleted,0) NOT IN (1, '1') LIMIT 1"
	);
	if (!is_array($chk) || count($chk) === 0) {
		echo json_encode(array('ok' => 0, 'error' => 'Starting screen is not part of this adventure.'), $jsonFlags);
		exit;
	}
	$beginSql = "'" . $beginId . "'";
}

$picRaw = isset($data['pic']) ? trim((string) $data['pic']) : '';
$picSql = 'NULL';
if ($picRaw !== '' && ctype_digit($picRaw)) {
	$pid = (int) $picRaw;
	if ($pid > 0) {
		$prow = runquery_assoc("SELECT id, user FROM pics WHERE id = '$pid' LIMIT 1");
		if (!is_array($prow) || count($prow) === 0) {
			echo json_encode(array('ok' => 0, 'error' => 'Icon image id not found.'), $jsonFlags);
			exit;
		}
		$puser = (string) ($prow[0]['user'] ?? '');
		if ($puser !== $user && $puser !== '&everyone') {
			echo json_encode(array('ok' => 0, 'error' => 'You may not use that image as the experiment icon.'), $jsonFlags);
			exit;
		}
		$picSql = "'" . mysqli_real_escape_string($db, (string) $pid) . "'";
	}
}

$bgpic = isset($data['bgpic']) ? (int) $data['bgpic'] : 0;
if ($bgpic > 0) {
	$prow = runquery_assoc("SELECT id, user FROM pics WHERE id = '$bgpic' LIMIT 1");
	if (!is_array($prow) || count($prow) === 0) {
		echo json_encode(array('ok' => 0, 'error' => 'Background image id not found.'), $jsonFlags);
		exit;
	}
	$puser = (string) ($prow[0]['user'] ?? '');
	if ($puser !== $user && $puser !== '&everyone') {
		echo json_encode(array('ok' => 0, 'error' => 'You may not use that image as the background.'), $jsonFlags);
		exit;
	}
}

$bg = isset($data['bg']) ? trim((string) $data['bg']) : '#ffffff';
if (strlen($bg) > 20) {
	$bg = substr($bg, 0, 20);
}
$box = isset($data['box']) ? trim((string) $data['box']) : '#ccddff';
if (strlen($box) > 20) {
	$box = substr($box, 0, 20);
}
$border = isset($data['border']) ? trim((string) $data['border']) : '#9999cc';
if (strlen($border) > 10) {
	$border = substr($border, 0, 10);
}

$borderwidth = isset($data['borderwidth']) ? (int) $data['borderwidth'] : 2;
if ($borderwidth < 0) {
	$borderwidth = 0;
}
if ($borderwidth > 20) {
	$borderwidth = 20;
}

$textcolor = isset($data['textcolor']) ? trim((string) $data['textcolor']) : '';
if ($textcolor !== '' && strlen($textcolor) > 10) {
	$textcolor = substr($textcolor, 0, 10);
}
$textcolorSql = ($textcolor === '') ? 'NULL' : "'" . mysqli_real_escape_string($db, $textcolor) . "'";

$linkcolor = isset($data['linkcolor']) ? trim((string) $data['linkcolor']) : '';
if ($linkcolor !== '' && strlen($linkcolor) > 10) {
	$linkcolor = substr($linkcolor, 0, 10);
}
$linkcolorSql = ($linkcolor === '') ? 'NULL' : "'" . mysqli_real_escape_string($db, $linkcolor) . "'";

$tEsc = mysqli_real_escape_string($db, $title);
$dEsc = mysqli_real_escape_string($db, $description);
$tagsEsc = mysqli_real_escape_string($db, $tags);
$availEsc = mysqli_real_escape_string($db, $avail);
$bgEsc = mysqli_real_escape_string($db, $bg);
$boxEsc = mysqli_real_escape_string($db, $box);
$borderEsc = mysqli_real_escape_string($db, $border);

$publishedSet = '';
if ($avail === 'public' && $oldAvail !== 'public') {
	$pubEmpty = ($oldPublished === null || $oldPublished === '' || $oldPublished === '0000-00-00 00:00:00');
	if ($pubEmpty) {
		$publishedSet = ", published = NOW()";
	}
}

$q = "UPDATE advs SET
	title = '$tEsc',
	description = '$dEsc',
	tags = '$tagsEsc',
	avail = '$availEsc',
	`begin` = $beginSql,
	pic = $picSql,
	bgpic = '$bgpic',
	bg = '$bgEsc',
	box = '$boxEsc',
	border = '$borderEsc',
	borderwidth = '$borderwidth',
	textcolor = $textcolorSql,
	linkcolor = $linkcolorSql,
	edited = NOW()
	$publishedSet
	WHERE id = '$escAdvid' AND user = '$escUser' LIMIT 1";

if (!runquery($q)) {
	echo json_encode(array('ok' => 0, 'error' => 'Database update failed.'), $jsonFlags);
	exit;
}

echo json_encode(array('ok' => 1), $jsonFlags);
exit;
