<?php
/**
 * Seed demo experiment metrics for local Results / clipboard previews.
 *
 * Usage:
 *   php scripts/seed_demo_metrics.php
 *   php scripts/seed_demo_metrics.php --user=labtester
 *
 * Creates (if missing) sample public + draft adventures owned by the user,
 * visitor accounts, ratings, comments, ending finds, and play_starts.
 */
require_once dirname(__DIR__) . '/connect.php';
require_once dirname(__DIR__) . '/auxfuncs.php';
require_once dirname(__DIR__) . '/lib/clipboard-helpers.php';
require_once dirname(__DIR__) . '/lib/ending-helpers.php';

$owner = 'labtester';
foreach ($argv as $arg) {
	if (strpos($arg, '--user=') === 0) {
		$owner = substr($arg, 7);
	}
}

choosology_clipboard_ensure_schema($db);
choosology_ensure_ending_finds_table($db);

$pass = choosology_legacy_password_hash('labpass');
$visitors = array('alice', 'bob', 'cara', 'devon');
foreach (array_merge(array($owner), $visitors) as $name) {
	$esc = mysqli_real_escape_string($db, $name);
	$exists = mysqli_query($db, "SELECT id FROM users WHERE name='$esc' LIMIT 1");
	if ($exists && mysqli_num_rows($exists) > 0) {
		continue;
	}
	$email = mysqli_real_escape_string($db, $name . '@example.com');
	mysqli_query(
		$db,
		"INSERT INTO users (name, pass, email, authent, usertype, joined, view_restricted, fbshow)
		 VALUES ('$esc', '$pass', '$email', '', 0, NOW(), 0, 1)"
	);
	echo "Created user {$name}\n";
}

$delim = choosology_choice_delimiter();

function choosology_seed_make_experiment(mysqli $db, string $owner, string $title, string $avail, array $opts): int
{
	global $delim;
	$escOwner = mysqli_real_escape_string($db, $owner);
	$escTitle = mysqli_real_escape_string($db, $title);
	$escAvail = mysqli_real_escape_string($db, $avail);
	$desc = mysqli_real_escape_string($db, $opts['description'] ?? '');
	$words = (int) ($opts['words'] ?? 120);
	$plays = (int) ($opts['plays'] ?? 0);
	$ratingAvg = mysqli_real_escape_string($db, (string) ($opts['rating'] ?? 'NA'));
	$editedDays = (int) ($opts['edited_days_ago'] ?? 1);
	$edited = date('Y-m-d H:i:s', time() - $editedDays * 86400);
	$created = date('Y-m-d H:i:s', time() - ($editedDays + 5) * 86400);
	$published = ($avail === 'public') ? ("'" . mysqli_real_escape_string($db, $edited) . "'") : 'NULL';
	$lastPlayedSql = 'NULL';
	if (isset($opts['last_played_days_ago'])) {
		$lp = date('Y-m-d H:i:s', time() - ((int) $opts['last_played_days_ago']) * 86400);
		$lastPlayedSql = "'" . mysqli_real_escape_string($db, $lp) . "'";
	}

	mysqli_query(
		$db,
		"INSERT INTO advs (user, created, edited, title, description, status, avail, pass, totalwordcount, rating, published, play_starts, last_played)
		 VALUES ('$escOwner', '$created', '$edited', '$escTitle', '$desc', '1', '$escAvail', '', $words, '$ratingAvg', $published, $plays, $lastPlayedSql)"
	);
	$advid = (int) mysqli_insert_id($db);

	mysqli_query(
		$db,
		"INSERT INTO advscreens (user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, created, advused, wordcount, xpos, ypos)
		 VALUES ('$escOwner', 'Start', 'Start', 'You enter the lab.', '#ffffff', '#ccddff', '#9999cc', '$created', '$advid', 20, 80, 80)"
	);
	$begin = (int) mysqli_insert_id($db);
	mysqli_query(
		$db,
		"INSERT INTO advscreens (user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, created, advused, wordcount, xpos, ypos)
		 VALUES ('$escOwner', 'Middle', 'Middle', 'A choice presents itself.', '#ffffff', '#ccddff', '#9999cc', '$created', '$advid', 30, 280, 80)"
	);
	$mid = (int) mysqli_insert_id($db);
	mysqli_query(
		$db,
		"INSERT INTO advscreens (user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, created, advused, wordcount, xpos, ypos)
		 VALUES ('$escOwner', 'Ending A', 'Ending A', 'The beaker glows. End.', '#ffffff', '#ccddff', '#9999cc', '$created', '$advid', 15, 480, 40)"
	);
	$endA = (int) mysqli_insert_id($db);
	mysqli_query(
		$db,
		"INSERT INTO advscreens (user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, created, advused, wordcount, xpos, ypos)
		 VALUES ('$escOwner', 'Ending B', 'Ending B', 'The lights go out. End.', '#ffffff', '#ccddff', '#9999cc', '$created', '$advid', 15, 480, 160)"
	);
	$endB = (int) mysqli_insert_id($db);

	$c1 = mysqli_real_escape_string($db, 'Continue' . $delim . $mid);
	$c2a = mysqli_real_escape_string($db, 'Take the glowing path' . $delim . $endA);
	$c2b = mysqli_real_escape_string($db, 'Take the dark hall' . $delim . $endB);
	mysqli_query($db, "UPDATE advscreens SET choice1='$c1' WHERE id=$begin");
	mysqli_query($db, "UPDATE advscreens SET choice1='$c2a', choice2='$c2b' WHERE id=$mid");
	mysqli_query($db, "UPDATE advs SET begin='$begin' WHERE id=$advid");
	return $advid;
}

function choosology_seed_screens_by_name(mysqli $db, int $advid): array
{
	$screens = array();
	$sr = mysqli_query($db, "SELECT id, name FROM advscreens WHERE advused='$advid' AND IFNULL(deleted,0) NOT IN (1,'1')");
	while ($s = mysqli_fetch_assoc($sr)) {
		$screens[(string) $s['name']] = (int) $s['id'];
	}
	return $screens;
}

function choosology_seed_engagement(mysqli $db, int $advid, string $owner, array $cfg): void
{
	mysqli_query($db, "DELETE FROM ratings WHERE adv=$advid");
	mysqli_query($db, "DELETE FROM comments WHERE whichboard='adv$advid'");
	mysqli_query($db, "DELETE FROM ending_finds WHERE adv=$advid");
	$screens = choosology_seed_screens_by_name($db, $advid);
	$mid = $screens['Middle'] ?? 0;

	foreach ($cfg['ratings'] ?? array() as $who => $stars) {
		$escWho = mysqli_real_escape_string($db, $who);
		$escOwner = mysqli_real_escape_string($db, $owner);
		mysqli_query(
			$db,
			"INSERT INTO ratings (adv, owner, who, rating, screen)
			 VALUES ($advid, '$escOwner', '$escWho', " . (int) $stars . ", $mid)"
		);
	}
	foreach ($cfg['comments'] ?? array() as $c) {
		$escA = mysqli_real_escape_string($db, $c['author']);
		$escT = mysqli_real_escape_string($db, $c['text']);
		$days = (int) ($c['days_ago'] ?? 1);
		$when = date('Y-m-d H:i:s', time() - $days * 86400 - random_int(0, 20000));
		$sid = (int) ($c['screen'] ?? $mid);
		mysqli_query(
			$db,
			"INSERT INTO comments (author, date, text, whichboard, reviewed, whichscreen)
			 VALUES ('$escA', '$when', '$escT', 'adv$advid', 0, $sid)"
		);
	}
	foreach ($cfg['ending_finds'] ?? array() as $f) {
		$sid = (int) ($f['screen'] ?? 0);
		if ($sid < 1) {
			continue;
		}
		$escU = mysqli_real_escape_string($db, $f['user']);
		$when = date('Y-m-d H:i:s', time() - ((int) ($f['days_ago'] ?? 1)) * 86400);
		mysqli_query(
			$db,
			"INSERT IGNORE INTO ending_finds (uname, adv, screen, found_at)
			 VALUES ('$escU', $advid, $sid, '$when')"
		);
	}
	$avgR = mysqli_query($db, "SELECT AVG(rating) AS a, COUNT(*) AS c FROM ratings WHERE adv=$advid");
	if ($avgR && ($ar = mysqli_fetch_assoc($avgR)) && (int) $ar['c'] > 0) {
		$avg = round((float) $ar['a'], 1);
		mysqli_query($db, "UPDATE advs SET rating='$avg' WHERE id=$advid");
	}
}

$escOwner = mysqli_real_escape_string($db, $owner);
$ids = array();
$r = mysqli_query($db, "SELECT id, title FROM advs WHERE user='$escOwner'");
while ($row = mysqli_fetch_assoc($r)) {
	$ids[(string) $row['title']] = (int) $row['id'];
}

if (empty($ids['Midnight Beaker'])) {
	$ids['Midnight Beaker'] = choosology_seed_make_experiment($db, $owner, 'Midnight Beaker', 'public', array(
		'description' => 'A late-night lab puzzle with two endings.',
		'words' => 840,
		'plays' => 47,
		'rating' => '4.2',
		'edited_days_ago' => 1,
		'last_played_days_ago' => 0,
	));
	echo "Created Midnight Beaker (#{$ids['Midnight Beaker']})\n";
}
if (empty($ids['Corridor of Choices'])) {
	$ids['Corridor of Choices'] = choosology_seed_make_experiment($db, $owner, 'Corridor of Choices', 'public', array(
		'description' => 'Branching hallway experiment for playtesting metrics.',
		'words' => 1260,
		'plays' => 18,
		'rating' => '3.7',
		'edited_days_ago' => 3,
		'last_played_days_ago' => 1,
	));
	echo "Created Corridor of Choices (#{$ids['Corridor of Choices']})\n";
}
if (empty($ids['Draft: Untitled Protocol'])) {
	$ids['Draft: Untitled Protocol'] = choosology_seed_make_experiment($db, $owner, 'Draft: Untitled Protocol', 'none', array(
		'description' => 'Private draft — little traffic yet.',
		'words' => 95,
		'plays' => 2,
		'rating' => 'NA',
		'edited_days_ago' => 0,
		'last_played_days_ago' => 6,
	));
	echo "Created Draft: Untitled Protocol (#{$ids['Draft: Untitled Protocol']})\n";
}

$id = $ids['Midnight Beaker'];
$screens = choosology_seed_screens_by_name($db, $id);
choosology_seed_engagement($db, $id, $owner, array(
	'ratings' => array('alice' => 5, 'bob' => 4, 'cara' => 4, 'devon' => 4),
	'comments' => array(
		array('author' => 'alice', 'text' => 'Loved the glowing ending — felt like a real lab night.', 'days_ago' => 0),
		array('author' => 'bob', 'text' => 'Dark hall ending is spooky. More branches please!', 'days_ago' => 1),
		array('author' => 'cara', 'text' => 'Smooth pacing on the middle node.', 'days_ago' => 2),
	),
	'ending_finds' => array(
		array('user' => 'alice', 'screen' => $screens['Ending A'] ?? 0, 'days_ago' => 0),
		array('user' => 'bob', 'screen' => $screens['Ending B'] ?? 0, 'days_ago' => 1),
		array('user' => 'cara', 'screen' => $screens['Ending A'] ?? 0, 'days_ago' => 2),
		array('user' => 'devon', 'screen' => $screens['Ending A'] ?? 0, 'days_ago' => 3),
		array('user' => 'devon', 'screen' => $screens['Ending B'] ?? 0, 'days_ago' => 3),
	),
));
mysqli_query($db, "UPDATE advs SET play_starts=47, last_played=NOW() WHERE id=$id");

$id = $ids['Corridor of Choices'];
$screens = choosology_seed_screens_by_name($db, $id);
choosology_seed_engagement($db, $id, $owner, array(
	'ratings' => array('alice' => 3, 'bob' => 4, 'cara' => 4),
	'comments' => array(
		array('author' => 'devon', 'text' => 'Nice structure for a first public corridor.', 'days_ago' => 1),
		array('author' => 'alice', 'text' => 'I missed Ending B somehow — will retry.', 'days_ago' => 2),
	),
	'ending_finds' => array(
		array('user' => 'alice', 'screen' => $screens['Ending A'] ?? 0, 'days_ago' => 2),
		array('user' => 'bob', 'screen' => $screens['Ending A'] ?? 0, 'days_ago' => 1),
		array('user' => 'cara', 'screen' => $screens['Ending B'] ?? 0, 'days_ago' => 1),
	),
));
mysqli_query($db, "UPDATE advs SET play_starts=18, last_played=DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id=$id");

$id = $ids['Draft: Untitled Protocol'];
mysqli_query($db, "UPDATE advs SET play_starts=2, last_played=DATE_SUB(NOW(), INTERVAL 6 DAY), rating='NA' WHERE id=$id");
mysqli_query($db, "DELETE FROM ratings WHERE adv=$id");
mysqli_query($db, "DELETE FROM comments WHERE whichboard='adv$id'");
mysqli_query($db, "DELETE FROM ending_finds WHERE adv=$id");

echo "Demo metrics ready for {$owner}. Open My Stuff → My Office.\n";
foreach (choosology_clipboard_experiment_metrics($db, $owner) as $row) {
	echo sprintf(
		"  #%d %s | %s | plays=%d comments=%d ratings=%d avg=%s endings=%d\n",
		$row['id'],
		$row['title'],
		$row['avail'],
		$row['play_starts'],
		$row['comments'],
		$row['rating_count'],
		$row['rating'],
		$row['ending_finds']
	);
}
