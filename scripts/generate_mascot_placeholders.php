<?php
/**
 * Placeholder portraits for the Lab Guide mascot.
 * Requires PHP GD. Safe to re-run (overwrites).
 */
$root = dirname(__DIR__);
$outDir = $root . '/images/mascots/lab_guide';
if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {
	fwrite(STDERR, "Cannot create $outDir\n");
	exit(1);
}
if (!function_exists('imagecreatetruecolor')) {
	fwrite(STDERR, "PHP GD is required.\n");
	exit(1);
}

/**
 * @param 'idle'|'offer'|'explain'|'done' $pose
 */
function guide_draw_portrait(string $path, string $pose): void
{
	$w = 200;
	$h = 240;
	$im = imagecreatetruecolor($w, $h);
	imagesavealpha($im, true);
	imagealphablending($im, false);
	$clear = imagecolorallocatealpha($im, 0, 0, 0, 127);
	imagefilledrectangle($im, 0, 0, $w, $h, $clear);
	imagealphablending($im, true);

	$ink = imagecolorallocate($im, 28, 26, 22);
	$skin = imagecolorallocate($im, 232, 196, 154);
	$hair = imagecolorallocate($im, 61, 52, 40);
	$coat = imagecolorallocate($im, 244, 236, 216);
	$coatShade = imagecolorallocate($im, 196, 176, 140);
	$accent = imagecolorallocate($im, 196, 163, 90);
	$eye = imagecolorallocate($im, 36, 44, 52);
	$paper = imagecolorallocate($im, 255, 252, 245);
	$sleeve = imagecolorallocate($im, 232, 220, 196);

	imagefilledellipse($im, 100, 214, 120, 28, $coatShade);
	imagefilledrectangle($im, 62, 124, 138, 214, $coat);
	imagefilledrectangle($im, 62, 124, 78, 214, $coatShade);
	imagefilledpolygon($im, array(86, 122, 114, 122, 100, 150), $accent);

	$arm = static function (int $x1, int $y1, int $x2, int $y2) use ($im, $sleeve, $skin, $ink): void {
		imagesetthickness($im, 8);
		imageline($im, $x1, $y1, $x2, $y2, $sleeve);
		imagesetthickness($im, 1);
		imagefilledellipse($im, $x2, $y2, 18, 18, $skin);
		imageellipse($im, $x2, $y2, 18, 18, $ink);
	};

	if ($pose === 'offer') {
		$arm(70, 140, 36, 78);
		$arm(130, 150, 150, 190);
	} elseif ($pose === 'explain') {
		$arm(70, 150, 48, 188);
		$arm(128, 142, 176, 128);
	} elseif ($pose === 'done') {
		$arm(78, 146, 92, 176);
		$arm(122, 146, 108, 176);
		imagefilledrectangle($im, 96, 168, 112, 186, $accent);
	} else {
		$arm(72, 148, 48, 196);
		$arm(128, 148, 152, 196);
	}

	imagefilledellipse($im, 100, 78, 86, 92, $skin);
	imagefilledarc($im, 100, 64, 88, 72, 180, 360, $hair, IMG_ARC_PIE);
	imagefilledrectangle($im, 70, 74, 94, 94, $paper);
	imagefilledrectangle($im, 106, 74, 130, 94, $paper);
	imagerectangle($im, 70, 74, 94, 94, $ink);
	imagerectangle($im, 106, 74, 130, 94, $ink);
	imageline($im, 94, 84, 106, 84, $ink);
	imagefilledellipse($im, 82, 84, 8, 10, $eye);
	imagefilledellipse($im, 118, 84, 8, 10, $eye);

	if ($pose === 'done' || $pose === 'offer') {
		imagearc($im, 100, 96, 22, 14, 20, 160, $ink);
	} elseif ($pose === 'explain') {
		imageline($im, 92, 100, 108, 100, $ink);
	} else {
		imagearc($im, 100, 98, 16, 10, 20, 160, $ink);
	}

	if ($pose === 'explain') {
		imagefilledrectangle($im, 168, 112, 188, 132, $paper);
		imagerectangle($im, 168, 112, 188, 132, $ink);
		imageline($im, 172, 118, 184, 118, $accent);
		imageline($im, 172, 124, 182, 124, $accent);
	}

	imagepng($im, $path);
	imagedestroy($im);
}

foreach (array('idle', 'offer', 'explain', 'done') as $pose) {
	guide_draw_portrait($outDir . '/' . $pose . '.png', $pose);
	echo "wrote $pose.png\n";
}
