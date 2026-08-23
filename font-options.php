<?php
/**
 * Shared adventure / editor font catalog (site GUI faces + common reader fonts).
 */
declare(strict_types=1);

/**
 * @return array<string, array{label: string, stack: string}>
 */
function choosology_font_catalog(): array
{
	return array(
		'trebuchet' => array(
			'label' => 'Trebuchet MS',
			'stack' => '"Trebuchet MS", Verdana, Arial, sans-serif',
		),
		'verdana' => array(
			'label' => 'Verdana',
			'stack' => 'Verdana, Geneva, sans-serif',
		),
		'arial' => array(
			'label' => 'Arial',
			'stack' => 'Arial, Helvetica, sans-serif',
		),
		'consolas' => array(
			'label' => 'Consolas',
			'stack' => 'Consolas, "Courier New", Courier, monospace',
		),
		'georgia' => array(
			'label' => 'Georgia',
			'stack' => 'Georgia, "Times New Roman", Times, serif',
		),
		'times' => array(
			'label' => 'Times New Roman',
			'stack' => '"Times New Roman", Times, serif',
		),
		'courier' => array(
			'label' => 'Courier New',
			'stack' => '"Courier New", Courier, monospace',
		),
		'comic' => array(
			'label' => 'Comic Sans MS',
			'stack' => '"Comic Sans MS", "Segoe Print", cursive, sans-serif',
		),
		'segoe_print' => array(
			'label' => 'Segoe Print',
			'stack' => '"Segoe Print", "Comic Sans MS", cursive, sans-serif',
		),
		'eraser' => array(
			'label' => 'Eraser',
			'stack' => '"Eraser", "Trebuchet MS", sans-serif',
		),
		'zx_spectrum' => array(
			'label' => 'ZX Spectrum',
			'stack' => '"ZX Spectrum", monospace',
		),
	);
}

/**
 * Resolve a catalog key from a stored label, stack fragment, or legacy value.
 */
function choosology_font_normalize_key(?string $raw): string
{
	$raw = trim((string) $raw);
	if ($raw === '') {
		return 'trebuchet';
	}
	$catalog = choosology_font_catalog();
	if (isset($catalog[$raw])) {
		return $raw;
	}
	$lower = strtolower($raw);
	foreach ($catalog as $key => $meta) {
		if (strtolower($meta['label']) === $lower) {
			return $key;
		}
	}
	if (stripos($raw, 'trebuchet') !== false) {
		return 'trebuchet';
	}
	if (stripos($raw, 'verdana') !== false) {
		return 'verdana';
	}
	if (stripos($raw, 'consolas') !== false) {
		return 'consolas';
	}
	if (stripos($raw, 'comic sans') !== false) {
		return 'comic';
	}
	if (stripos($raw, 'segoe print') !== false) {
		return 'segoe_print';
	}
	if (stripos($raw, 'eraser') !== false) {
		return 'eraser';
	}
	if (stripos($raw, 'zx spectrum') !== false) {
		return 'zx_spectrum';
	}
	if (stripos($raw, 'georgia') !== false) {
		return 'georgia';
	}
	if (stripos($raw, 'times') !== false) {
		return 'times';
	}
	if (stripos($raw, 'courier') !== false) {
		return 'courier';
	}
	if (stripos($raw, 'arial') !== false) {
		return 'arial';
	}
	return 'trebuchet';
}

function choosology_font_stack_for_key(string $key): string
{
	$catalog = choosology_font_catalog();
	$key = choosology_font_normalize_key($key);
	return $catalog[$key]['stack'];
}

function choosology_font_label_for_key(string $key): string
{
	$catalog = choosology_font_catalog();
	$key = choosology_font_normalize_key($key);
	return $catalog[$key]['label'];
}

/**
 * TinyMCE font_family_formats string (Label=stack pairs).
 */
function choosology_font_tinymce_formats(): string
{
	$parts = array();
	foreach (choosology_font_catalog() as $meta) {
		$parts[] = $meta['label'] . '=' . $meta['stack'];
	}
	return implode('; ', $parts);
}

/**
 * Extract the first font-family value from a legacy inline style string.
 */
function choosology_style_extract_font_family(string $style): string
{
	if ($style === '') {
		return '';
	}
	if (preg_match('/font-family\s*:\s*([^;]+)/i', $style, $m)) {
		return trim($m[1], " \t\n\r\0\x0B\"'");
	}
	return '';
}

/**
 * Replace or append font-family in a legacy inline style string.
 */
function choosology_style_set_font_family(string $style, string $fontKey): string
{
	$stack = choosology_font_stack_for_key($fontKey);
	$decl = 'font-family:' . $stack;
	if ($style === '') {
		return $decl;
	}
	if (preg_match('/font-family\s*:/i', $style)) {
		return (string) preg_replace('/font-family\s*:\s*[^;]*/i', $decl, $style);
	}
	return rtrim($style, ';') . ';' . $decl;
}

/**
 * Effective default body font key for an advs row.
 */
function choosology_adv_default_font_key(array $adv): string
{
	$fontCol = trim((string) ($adv['font'] ?? ''));
	if ($fontCol !== '') {
		return choosology_font_normalize_key($fontCol);
	}
	$fromStyle = choosology_style_extract_font_family(htmlspecialchars_decode((string) ($adv['textstyle'] ?? '')));
	if ($fromStyle !== '') {
		return choosology_font_normalize_key($fromStyle);
	}
	return 'trebuchet';
}

/**
 * Merge color override into a legacy style string when a simple color column is set.
 */
function choosology_style_apply_color_override(string $style, ?string $color): string
{
	$color = trim((string) $color);
	if ($color === '') {
		return $style;
	}
	if (preg_match('/color\s*:/i', $style)) {
		return (string) preg_replace('/color\s*:\s*[^;]*/i', 'color:' . $color, $style);
	}
	if ($style === '') {
		return 'color:' . $color;
	}
	return rtrim($style, ';') . ';color:' . $color;
}

/**
 * CSS declarations for adventure play typography (body or choice labels).
 *
 * @param 'text'|'choice' $role
 */
function choosology_adv_play_typography_css(array $adv, string $role): string
{
	$fontKey = choosology_adv_default_font_key($adv);
	if ($role === 'choice') {
		$style = htmlspecialchars_decode((string) ($adv['linkstyle'] ?? ''));
		$style = choosology_style_apply_color_override($style, trim((string) ($adv['linkcolor'] ?? '')));
	} else {
		$style = htmlspecialchars_decode((string) ($adv['textstyle'] ?? ''));
		$style = choosology_style_apply_color_override($style, trim((string) ($adv['textcolor'] ?? '')));
	}
	$style = choosology_style_set_font_family($style, $fontKey);
	$style = trim(str_replace(array("\r", "\n"), '', $style), '; ');
	return $style;
}
