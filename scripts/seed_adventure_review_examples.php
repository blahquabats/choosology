<?php
/**
 * Seed example adventures into the Classic Admin Adventure Review queue,
 * each with a generated cover image (and matching thumbnail).
 *
 * Usage:
 *   php scripts/seed_adventure_review_examples.php
 *   php scripts/seed_adventure_review_examples.php --user=labtester
 */
require_once dirname(__DIR__) . '/connect.php';
require_once dirname(__DIR__) . '/auxfuncs.php';
require_once dirname(__DIR__) . '/lib/adventure-review-helpers.php';

$owner = 'labtester';
foreach ($argv as $arg) {
	if (strpos($arg, '--user=') === 0) {
		$owner = substr($arg, 7);
	}
}

choosology_adventure_review_ensure_schema($db);

$pass = choosology_legacy_password_hash('testpass');
$escOwner = mysqli_real_escape_string($db, $owner);
$exists = mysqli_query($db, "SELECT id FROM users WHERE name='$escOwner' LIMIT 1");
if (!$exists || mysqli_num_rows($exists) < 1) {
	$email = mysqli_real_escape_string($db, $owner . '@example.com');
	mysqli_query(
		$db,
		"INSERT INTO users (name, pass, email, authent, usertype, joined, view_restricted, fbshow, probation)
		 VALUES ('$escOwner', '$pass', '$email', '', 0, NOW(), 0, 1, 0)"
	);
	echo "Created user {$owner}\n";
}

/**
 * Draw a simple cover PNG and register it in pics for $owner.
 *
 * @param array{0:int,1:int,2:int} $bg
 * @param array{0:int,1:int,2:int} $accent
 * @return int pic id
 */
function choosology_seed_review_cover(
	mysqli $db,
	string $owner,
	string $label,
	string $subtitle,
	array $bg,
	array $accent
): int {
	if (!choosology_gd_available()) {
		fwrite(STDERR, "PHP GD required to seed cover art.\n");
		exit(1);
	}

	$dir = playerDir($owner);
	$thumbDir = $dir . DIRECTORY_SEPARATOR . 'thumbs';
	if (!is_dir($thumbDir) && !mkdir($thumbDir, 0775, true) && !is_dir($thumbDir)) {
		fwrite(STDERR, "Could not create thumbs dir: {$thumbDir}\n");
		exit(1);
	}

	$w = 640;
	$h = 400;
	$im = imagecreatetruecolor($w, $h);
	$bgColor = imagecolorallocate($im, $bg[0], $bg[1], $bg[2]);
	$accentColor = imagecolorallocate($im, $accent[0], $accent[1], $accent[2]);
	$ink = imagecolorallocate($im, 28, 26, 22);
	$paper = imagecolorallocate($im, 244, 240, 229);
	imagefilledrectangle($im, 0, 0, $w, $h, $bgColor);
	imagefilledrectangle($im, 24, 24, $w - 24, $h - 24, $paper);
	imagefilledrectangle($im, 24, 24, 48, $h - 24, $accentColor);
	imagefilledellipse($im, $w - 110, 110, 120, 120, $accentColor);

	$lines = preg_split('/\s+/', $label) ?: array($label);
	$y = 130;
	foreach ($lines as $i => $word) {
		imagestring($im, 5, 70, $y + ($i * 22), strtoupper(substr($word, 0, 28)), $ink);
	}
	imagestring($im, 3, 70, $h - 70, substr($subtitle, 0, 60), $ink);
	imagestring($im, 2, 70, $h - 48, 'Choosology review specimen', $accentColor);

	$filename = 'review_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.png';
	$dest = $dir . DIRECTORY_SEPARATOR . $filename;
	$thumbDest = $thumbDir . DIRECTORY_SEPARATOR . $filename;
	if (!imagepng($im, $dest)) {
		imagedestroy($im);
		fwrite(STDERR, "Failed writing {$dest}\n");
		exit(1);
	}
	imagedestroy($im);
	choosology_make_image_thumb($dest, $thumbDest, 'image/png', 240);

	$size = filesize($dest) ?: 0;
	$escFile = mysqli_real_escape_string($db, $filename);
	$escName = mysqli_real_escape_string($db, $label);
	$escUser = mysqli_real_escape_string($db, $owner);
	$escSize = mysqli_real_escape_string($db, (string) $size);
	mysqli_query(
		$db,
		"INSERT INTO pics (filename, imagename, cat, size, uploaded, user, type)
		 VALUES ('$escFile', '$escName', 'review-seed', '$escSize', NOW(), '$escUser', 'image/png')"
	);
	return (int) mysqli_insert_id($db);
}

/**
 * @param array{title:string,description:string,tags:string,flags:string,hold:bool,probation_author?:bool,cover_label:string,subtitle:string,bg:array{0:int,1:int,2:int},accent:array{0:int,1:int,2:int},screen_html:string} $spec
 */
function choosology_seed_review_adventure(mysqli $db, string $owner, array $spec): int
{
	$picId = choosology_seed_review_cover(
		$db,
		$owner,
		$spec['cover_label'],
		$spec['subtitle'],
		$spec['bg'],
		$spec['accent']
	);

	$escOwner = mysqli_real_escape_string($db, $owner);
	$escTitle = mysqli_real_escape_string($db, $spec['title']);
	$escDesc = mysqli_real_escape_string($db, $spec['description']);
	$escTags = mysqli_real_escape_string($db, $spec['tags']);
	$escFlags = mysqli_real_escape_string($db, $spec['flags']);
	$hold = !empty($spec['hold']) ? 1 : 0;
	$pic = (int) $picId;

	mysqli_query(
		$db,
		"INSERT INTO advs (
			user, created, title, status, description, edited, published, avail, pass,
			font, titlefont, bg, box, border, borderwidth, usesimplestyling, totalwordcount,
			titlestyle, textstyle, linkstyle, tags, pic, bgpic,
			review_status, review_hold, content_flags
		) VALUES (
			'$escOwner', NOW(), '$escTitle', '0', '$escDesc', NOW(), NOW(), 'public', '',
			'Trebuchet MS', 'Arial', '#f4f0e5', '#e8dcc8', '#7c2d34', 2, 1, 40,
			'font-weight:bold;text-align:center;color:#1c1a16;',
			'font-weight:normal;text-align:left;color:#1c1a16;',
			'font-weight:normal;text-align:center;color:#3333ff;',
			'$escTags', '$pic', '$pic',
			'pending', $hold, '$escFlags'
		)"
	);
	$advid = (int) mysqli_insert_id($db);
	if ($advid < 1) {
		fwrite(STDERR, "Failed inserting adventure {$spec['title']}\n");
		exit(1);
	}

	/* Embed the cover in the opening screen so reviewers see art in-play too. */
	$imgUrl = choosology_site_url('ajax/pic.php?id=' . $pic);
	$screenBody = '<p><img src="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') .
		'" alt="" style="max-width:100%;height:auto;border:2px solid #7c2d34;"></p>' .
		(string) $spec['screen_html'];
	$escBody = mysqli_real_escape_string($db, $screenBody);
	$escAdv = mysqli_real_escape_string($db, (string) $advid);

	mysqli_query(
		$db,
		"INSERT INTO advscreens (
			user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, screenborderwidth,
			use_defaults, created, advused, xpos, ypos, deleted, wordcount, pic
		) VALUES (
			'$escOwner', '', 'Opening', '$escBody', '#f4f0e5', '#ffffff', '#7c2d34', 2,
			1, NOW(), '$escAdv', 40, 40, 0, 48, '$pic'
		)"
	);
	$sid = (int) mysqli_insert_id($db);
	mysqli_query($db, "UPDATE advs SET `begin`='$sid' WHERE id=$advid LIMIT 1");

	echo "Queued #{$advid} «{$spec['title']}» pic={$pic} hold={$hold} flags={$spec['flags']}\n";
	return $advid;
}

$examples = array(
	array(
		'title' => 'Greenhouse Protocol',
		'description' => 'A calm botanical mystery among the lab terrariums. Safe for general discovery — still queued for routine review.',
		'tags' => 'botany,mystery,lab',
		'flags' => '',
		'hold' => false,
		'cover_label' => 'Greenhouse Protocol',
		'subtitle' => 'Routine review · no hold',
		'bg' => array(210, 228, 198),
		'accent' => array(74, 122, 68),
		'screen_html' => '<p>Morning mist hangs over the specimen benches. A clipboard notes three wilting orchids and one unlocked door.</p><p>Where do you begin?</p>',
	),
	array(
		'title' => 'After-Hours Containment',
		'description' => 'Mature themes: late-shift containment breach, stressed staff, and ethically gray choices. Held until cleared.',
		'tags' => 'thriller,night,containment',
		'flags' => 'mature',
		'hold' => true,
		'cover_label' => 'After-Hours Containment',
		'subtitle' => 'Held · mature flag',
		'bg' => array(48, 42, 58),
		'accent' => array(176, 64, 72),
		'screen_html' => '<p>The corridor lights stutter. Sirens are on a delay loop. Someone left a keycard in the airlock.</p><p>Do you seal the wing or follow the signal?</p>',
	),
	array(
		'title' => 'Letters from Quarantine',
		'description' => 'Sensitive content about isolation and loss. Held pending moderator review.',
		'tags' => 'epistolary,sensitive,quarantine',
		'flags' => 'sensitive',
		'hold' => true,
		'cover_label' => 'Letters from Quarantine',
		'subtitle' => 'Held · sensitive flag',
		'bg' => array(232, 220, 204),
		'accent' => array(120, 92, 64),
		'screen_html' => '<p>Three sealed envelopes sit on the desk. The return addresses are redacted. The fourth letter is unfinished.</p>',
	),
	array(
		'title' => 'Probationary Field Notes',
		'description' => 'Published while the author is on probation, so discovery is held even without content flags.',
		'tags' => 'fieldwork,notes,probation',
		'flags' => '',
		'hold' => true,
		'cover_label' => 'Probationary Field Notes',
		'subtitle' => 'Held · author probation',
		'bg' => array(198, 210, 228),
		'accent' => array(56, 88, 140),
		'screen_html' => '<p>Day 12. The sample cooler still hums. Your badge says TEMPORARY ACCESS. The trail camera caught something walking upright.</p>',
	),
);

$ids = array();
foreach ($examples as $spec) {
	/* Probationary specimen needs the author flag on; others seed with it off so hold is flag-driven. */
	$wantProbation = (strpos($spec['title'], 'Probationary') !== false) ? 1 : 0;
	mysqli_query($db, "UPDATE users SET probation=$wantProbation WHERE name='$escOwner' LIMIT 1");
	$ids[] = choosology_seed_review_adventure($db, $owner, $spec);
}
/* Leave the demo author on probation so Admin can clear it from the queue UI. */
mysqli_query($db, "UPDATE users SET probation=1 WHERE name='$escOwner' LIMIT 1");

$pending = choosology_adventure_review_pending($db);
echo "\nAdventure Review queue now has " . count($pending) . " item(s).\n";
foreach ($pending as $row) {
	$bits = array();
	if (!empty($row['review_hold'])) {
		$bits[] = 'HELD';
	}
	foreach ($row['hold_reasons'] as $reason) {
		$bits[] = $reason;
	}
	echo "  #{$row['id']} {$row['title']} [" . implode(', ', $bits) . "]\n";
}

echo "\nOpen Classic Admin → Adventure Review to moderate.\n";
