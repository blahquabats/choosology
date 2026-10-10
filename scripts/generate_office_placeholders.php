<?php
/**
 * Generate simple placeholder PNGs for My Office décor / trinkets / trophy bay.
 * Requires PHP GD. Safe to re-run (overwrites).
 */
$root = dirname(__DIR__);
$outDir = $root . '/images/office';
if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {
	fwrite(STDERR, "Cannot create $outDir\n");
	exit(1);
}
if (!function_exists('imagecreatetruecolor')) {
	fwrite(STDERR, "PHP GD is required.\n");
	exit(1);
}

function office_palette(string $slot): array
{
	$map = array(
		'wallpaper' => array(0x5a, 0x6b, 0x5e),
		'flooring' => array(0x8a, 0x84, 0x72),
		'computer' => array(0x3d, 0x4a, 0x52),
		'desk' => array(0xc4, 0xb4, 0x96),
		'bookshelf' => array(0x6e, 0x5a, 0x48),
		'corner' => array(0x7a, 0x78, 0x6a),
		'lighting' => array(0xe8, 0xd5, 0xa0),
		'window' => array(0x6a, 0x78, 0x88),
		'trinket' => array(0xb4, 0x8a, 0x3d),
		'trophy' => array(0x5c, 0x68, 0x5e),
	);
	return $map[$slot] ?? array(0x70, 0x70, 0x70);
}

function office_draw_label($im, string $text, int $w, int $h): void
{
	$white = imagecolorallocatealpha($im, 255, 255, 255, 40);
	$dark = imagecolorallocate($im, 30, 34, 38);
	imagestring($im, 2, 8, 8, $text, $dark);
	imagestring($im, 2, 9, 9, $text, $white);
}

function office_save_layer(string $path, string $slot, string $variant, int $w, int $h, callable $draw): void
{
	$im = imagecreatetruecolor($w, $h);
	imagesavealpha($im, true);
	$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
	imagefill($im, 0, 0, $transparent);
	$draw($im, $w, $h);
	office_draw_label($im, $slot . '/' . $variant, $w, $h);
	imagepng($im, $path);
	imagedestroy($im);
}

$defs = array(
	'wallpaper__plain' => 'wallpaper',
	'wallpaper__blueprint' => 'wallpaper',
	'wallpaper__cork' => 'wallpaper',
	'flooring__linoleum' => 'flooring',
	'flooring__tile' => 'flooring',
	'flooring__wood' => 'flooring',
	'computer__crt' => 'computer',
	'computer__beige' => 'computer',
	'computer__slate' => 'computer',
	'desk__beige' => 'desk',
	'desk__metal' => 'desk',
	'desk__oak' => 'desk',
	'bookshelf__empty' => 'bookshelf',
	'bookshelf__filled' => 'bookshelf',
	'bookshelf__glass' => 'bookshelf',
	'corner__sparse' => 'corner',
	'corner__plant' => 'corner',
	'corner__crates' => 'corner',
	'lighting__fluorescent' => 'lighting',
	'lighting__desk_lamp' => 'lighting',
	'lighting__spot' => 'lighting',
	'window__courtyard' => 'window',
	'window__dusk' => 'window',
	'window__rain' => 'window',
);

foreach ($defs as $name => $slot) {
	$parts = explode('__', $name, 2);
	$variant = $parts[1] ?? 'x';
	$rgb = office_palette($slot);
	$path = $outDir . '/' . $name . '.png';
	office_save_layer($path, $slot, $variant, 320, 200, function ($im, $w, $h) use ($rgb, $slot, $variant) {
		$base = imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], 30);
		$solid = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
		$accent = imagecolorallocate($im, min(255, $rgb[0] + 40), min(255, $rgb[1] + 30), min(255, $rgb[2] + 20));
		$ink = imagecolorallocate($im, 40, 44, 48);

		if ($slot === 'wallpaper') {
			imagefilledrectangle($im, 0, 0, $w, $h, $solid);
			if ($variant === 'blueprint') {
				$line = imagecolorallocatealpha($im, 200, 220, 230, 90);
				for ($x = 0; $x < $w; $x += 16) {
					imageline($im, $x, 0, $x, $h, $line);
				}
				for ($y = 0; $y < $h; $y += 16) {
					imageline($im, 0, $y, $w, $y, $line);
				}
			} elseif ($variant === 'cork') {
				for ($i = 0; $i < 80; $i++) {
					$dot = imagecolorallocate($im, $rgb[0] + ($i % 20), $rgb[1] + ($i % 15), $rgb[2]);
					imagefilledellipse($im, rand(0, $w), rand(0, $h), 3, 3, $dot);
				}
			}
		} elseif ($slot === 'flooring') {
			imagefilledrectangle($im, 0, (int) ($h * 0.55), $w, $h, $solid);
			if ($variant === 'tile') {
				$alt = imagecolorallocate($im, $rgb[0] - 20, $rgb[1] - 15, $rgb[2] - 10);
				for ($x = 0; $x < $w; $x += 24) {
					for ($y = (int) ($h * 0.55); $y < $h; $y += 24) {
						if ((($x + $y) / 24) % 2 === 0) {
							imagefilledrectangle($im, $x, $y, $x + 23, $y + 23, $alt);
						}
					}
				}
			} elseif ($variant === 'wood') {
				for ($y = (int) ($h * 0.55); $y < $h; $y += 10) {
					imageline($im, 0, $y, $w, $y, $ink);
				}
			}
		} elseif ($slot === 'window') {
			imagefilledrectangle($im, (int) ($w * 0.55), (int) ($h * 0.12), (int) ($w * 0.92), (int) ($h * 0.55), $solid);
			imagerectangle($im, (int) ($w * 0.55), (int) ($h * 0.12), (int) ($w * 0.92), (int) ($h * 0.55), $ink);
			$sky = imagecolorallocate($im, 90, 110, 130);
			if ($variant === 'dusk') {
				$sky = imagecolorallocate($im, 120, 90, 100);
			} elseif ($variant === 'rain') {
				$sky = imagecolorallocate($im, 80, 90, 100);
			}
			imagefilledrectangle($im, (int) ($w * 0.57), (int) ($h * 0.14), (int) ($w * 0.90), (int) ($h * 0.53), $sky);
		} elseif ($slot === 'desk') {
			imagefilledrectangle($im, (int) ($w * 0.22), (int) ($h * 0.55), (int) ($w * 0.78), (int) ($h * 0.72), $solid);
			imagefilledrectangle($im, (int) ($w * 0.26), (int) ($h * 0.72), (int) ($w * 0.32), (int) ($h * 0.90), $accent);
			imagefilledrectangle($im, (int) ($w * 0.68), (int) ($h * 0.72), (int) ($w * 0.74), (int) ($h * 0.90), $accent);
		} elseif ($slot === 'computer') {
			imagefilledrectangle($im, (int) ($w * 0.40), (int) ($h * 0.30), (int) ($w * 0.60), (int) ($h * 0.52), $solid);
			$screen = imagecolorallocate($im, 180, 140, 60);
			if ($variant === 'slate') {
				$screen = imagecolorallocate($im, 100, 160, 170);
			}
			imagefilledrectangle($im, (int) ($w * 0.43), (int) ($h * 0.33), (int) ($w * 0.57), (int) ($h * 0.48), $screen);
			imagefilledrectangle($im, (int) ($w * 0.46), (int) ($h * 0.52), (int) ($w * 0.54), (int) ($h * 0.56), $accent);
		} elseif ($slot === 'bookshelf') {
			imagefilledrectangle($im, (int) ($w * 0.08), (int) ($h * 0.20), (int) ($w * 0.28), (int) ($h * 0.80), $solid);
			imagerectangle($im, (int) ($w * 0.08), (int) ($h * 0.20), (int) ($w * 0.28), (int) ($h * 0.80), $ink);
			if ($variant !== 'empty') {
				for ($y = (int) ($h * 0.28); $y < (int) ($h * 0.75); $y += 18) {
					imagefilledrectangle($im, (int) ($w * 0.10), $y, (int) ($w * 0.26), $y + 12, $accent);
				}
			}
		} elseif ($slot === 'corner') {
			imagefilledrectangle($im, (int) ($w * 0.78), (int) ($h * 0.58), (int) ($w * 0.95), (int) ($h * 0.85), $solid);
			if ($variant === 'plant') {
				$leaf = imagecolorallocate($im, 70, 120, 80);
				imagefilledellipse($im, (int) ($w * 0.86), (int) ($h * 0.52), 36, 28, $leaf);
			} elseif ($variant === 'crates') {
				imagefilledrectangle($im, (int) ($w * 0.80), (int) ($h * 0.48), (int) ($w * 0.93), (int) ($h * 0.60), $accent);
			}
		} elseif ($slot === 'lighting') {
			if ($variant === 'fluorescent') {
				imagefilledrectangle($im, (int) ($w * 0.25), (int) ($h * 0.06), (int) ($w * 0.75), (int) ($h * 0.12), $accent);
			} elseif ($variant === 'desk_lamp') {
				imagefilledellipse($im, (int) ($w * 0.62), (int) ($h * 0.38), 40, 24, $accent);
			} else {
				imagefilledellipse($im, (int) ($w * 0.30), (int) ($h * 0.10), 18, 10, $accent);
				imagefilledellipse($im, (int) ($w * 0.50), (int) ($h * 0.08), 18, 10, $accent);
				imagefilledellipse($im, (int) ($w * 0.70), (int) ($h * 0.10), 18, 10, $accent);
			}
		} else {
			imagefilledrectangle($im, 10, 10, $w - 10, $h - 10, $base);
		}
	});
	echo "Wrote $path\n";
}

$trinkets = array(
	'trinket__lab_initiate',
	'trinket__screenwright',
	'trinket__field_release',
	'trinket__clipboard_clerk',
	'trinket__scribbler',
	'trinket__taskmaster',
	'trinket__results_analyst',
	'trinket__terminal_specimen',
);
foreach ($trinkets as $name) {
	$path = $outDir . '/' . $name . '.png';
	$rgb = office_palette('trinket');
	office_save_layer($path, 'trinket', str_replace('trinket__', '', $name), 96, 96, function ($im, $w, $h) use ($rgb) {
		$solid = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
		$dark = imagecolorallocate($im, 50, 40, 20);
		$shine = imagecolorallocate($im, 230, 200, 120);
		imagefilledellipse($im, (int) ($w / 2), (int) ($h / 2), 70, 70, $solid);
		imageellipse($im, (int) ($w / 2), (int) ($h / 2), 70, 70, $dark);
		imagefilledrectangle($im, 30, 28, 66, 68, $shine);
	});
	echo "Wrote $path\n";
}

/* Tileable trophy bay background */
$bayPath = $outDir . '/trophy_bay__tile.png';
$im = imagecreatetruecolor(280, 220);
$wall = imagecolorallocate($im, 92, 104, 94);
$floor = imagecolorallocate($im, 120, 110, 90);
$trim = imagecolorallocate($im, 180, 150, 80);
$ink = imagecolorallocate($im, 40, 44, 48);
imagefilledrectangle($im, 0, 0, 279, 150, $wall);
imagefilledrectangle($im, 0, 150, 279, 219, $floor);
imageline($im, 0, 150, 279, 150, $ink);
imagefilledrectangle($im, 20, 40, 80, 100, $trim);
imagefilledrectangle($im, 100, 50, 170, 140, imagecolorallocate($im, 70, 78, 72));
imagefilledrectangle($im, 190, 55, 250, 130, $trim);
imagerectangle($im, 0, 0, 279, 219, $ink);
imagestring($im, 2, 8, 8, 'trophy bay', $ink);
imagepng($im, $bayPath);
imagedestroy($im);
echo "Wrote $bayPath\n";

echo "Done.\n";
