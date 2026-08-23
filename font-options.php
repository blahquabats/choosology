<?php
/**
 * Shared adventure / editor font catalog.
 * Merges Choosology site GUI faces with TinyMCE's default font list so nothing
 * disappears when font_family_formats is set explicitly.
 */
declare(strict_types=1);

/**
 * Choosology site / lab UI faces (webfonts + shell stacks from choosology.css).
 *
 * @return array<string, array{label: string, stack: string, group: string}>
 */
function choosology_font_site_catalog(): array
{
	return array(
		'trebuchet' => array(
			'label' => 'Trebuchet MS',
			'stack' => '"Trebuchet MS", Verdana, Arial, sans-serif',
			'group' => 'site',
		),
		'consolas' => array(
			'label' => 'Consolas',
			'stack' => 'Consolas, "Courier New", Courier, monospace',
			'group' => 'site',
		),
		'segoe_print' => array(
			'label' => 'Segoe Print',
			'stack' => '"Segoe Print", "Comic Sans MS", cursive, sans-serif',
			'group' => 'site',
		),
		'eraser' => array(
			'label' => 'Eraser',
			'stack' => '"Eraser", "Trebuchet MS", sans-serif',
			'group' => 'site',
		),
		'zx_spectrum' => array(
			'label' => 'ZX Spectrum',
			'stack' => '"ZX Spectrum", monospace',
			'group' => 'site',
		),
		'bradley_hand' => array(
			'label' => 'Bradley Hand ITC',
			'stack' => '"Bradley Hand ITC", "Segoe Print", cursive, sans-serif',
			'group' => 'site',
		),
	);
}

/**
 * TinyMCE 7 default font_family_formats entries (standard + symbol faces).
 *
 * @return array<string, array{label: string, stack: string, group: string}>
 */
function choosology_font_tinymce_builtin_catalog(): array
{
	return array(
		'andale_mono' => array(
			'label' => 'Andale Mono',
			'stack' => '"Andale Mono", Times, monospace',
			'group' => 'standard',
		),
		'arial' => array(
			'label' => 'Arial',
			'stack' => 'Arial, Helvetica, sans-serif',
			'group' => 'standard',
		),
		'arial_black' => array(
			'label' => 'Arial Black',
			'stack' => '"Arial Black", "Avant Garde", sans-serif',
			'group' => 'standard',
		),
		'book_antiqua' => array(
			'label' => 'Book Antiqua',
			'stack' => '"Book Antiqua", Palatino, serif',
			'group' => 'standard',
		),
		'comic' => array(
			'label' => 'Comic Sans MS',
			'stack' => '"Comic Sans MS", sans-serif',
			'group' => 'standard',
		),
		'courier' => array(
			'label' => 'Courier New',
			'stack' => '"Courier New", Courier, monospace',
			'group' => 'standard',
		),
		'georgia' => array(
			'label' => 'Georgia',
			'stack' => 'Georgia, Palatino, serif',
			'group' => 'standard',
		),
		'helvetica' => array(
			'label' => 'Helvetica',
			'stack' => 'Helvetica, Arial, sans-serif',
			'group' => 'standard',
		),
		'impact' => array(
			'label' => 'Impact',
			'stack' => 'Impact, Chicago, sans-serif',
			'group' => 'standard',
		),
		'tahoma' => array(
			'label' => 'Tahoma',
			'stack' => 'Tahoma, Arial, Helvetica, sans-serif',
			'group' => 'standard',
		),
		'terminal' => array(
			'label' => 'Terminal',
			'stack' => 'Terminal, Monaco, monospace',
			'group' => 'standard',
		),
		'times' => array(
			'label' => 'Times New Roman',
			'stack' => '"Times New Roman", Times, serif',
			'group' => 'standard',
		),
		'verdana' => array(
			'label' => 'Verdana',
			'stack' => 'Verdana, Geneva, sans-serif',
			'group' => 'standard',
		),
		'symbol' => array(
			'label' => 'Symbol',
			'stack' => 'Symbol',
			'group' => 'symbol',
		),
		'webdings' => array(
			'label' => 'Webdings',
			'stack' => 'Webdings',
			'group' => 'symbol',
		),
		'wingdings' => array(
			'label' => 'Wingdings',
			'stack' => 'Wingdings, "Zapf Dingbats"',
			'group' => 'symbol',
		),
	);
}

/**
 * Full merged catalog: site faces first, then TinyMCE defaults not already listed.
 *
 * @return array<string, array{label: string, stack: string, group: string}>
 */
function choosology_font_catalog(): array
{
	static $merged = null;
	if (is_array($merged)) {
		return $merged;
	}
	$merged = choosology_font_site_catalog();
	$seenLabels = array();
	foreach ($merged as $meta) {
		$seenLabels[strtolower($meta['label'])] = true;
	}
	foreach (choosology_font_tinymce_builtin_catalog() as $key => $meta) {
		$labelKey = strtolower($meta['label']);
		if (isset($seenLabels[$labelKey])) {
			continue;
		}
		$merged[$key] = $meta;
		$seenLabels[$labelKey] = true;
	}
	return $merged;
}

/**
 * Catalog grouped for HTML &lt;optgroup&gt; selects.
 *
 * @return array<string, array<string, array{label: string, stack: string, group: string}>>
 */
function choosology_font_catalog_grouped(): array
{
	$groups = array(
		'site' => array(),
		'standard' => array(),
		'symbol' => array(),
	);
	$labels = array(
		'site' => 'Choosology & site UI',
		'standard' => 'Standard web fonts',
		'symbol' => 'Symbol & decorative',
	);
	$out = array();
	foreach ($labels as $gid => $title) {
		$out[$title] = array();
	}
	foreach (choosology_font_catalog() as $key => $meta) {
		$gid = $meta['group'] ?? 'standard';
		$title = $labels[$gid] ?? $labels['standard'];
		$out[$title][$key] = $meta;
	}
	return $out;
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
	$needles = array(
		'andale' => 'andale_mono',
		'arial black' => 'arial_black',
		'book antiqua' => 'book_antiqua',
		'bradley' => 'bradley_hand',
		'comic sans' => 'comic',
		'consolas' => 'consolas',
		'courier' => 'courier',
		'eraser' => 'eraser',
		'georgia' => 'georgia',
		'helvetica' => 'helvetica',
		'impact' => 'impact',
		'segoe print' => 'segoe_print',
		'symbol' => 'symbol',
		'tahoma' => 'tahoma',
		'terminal' => 'terminal',
		'times' => 'times',
		'trebuchet' => 'trebuchet',
		'verdana' => 'verdana',
		'webdings' => 'webdings',
		'wingdings' => 'wingdings',
		'zx spectrum' => 'zx_spectrum',
		'arial' => 'arial',
	);
	foreach ($needles as $frag => $key) {
		if (stripos($lower, $frag) !== false && isset($catalog[$key])) {
			return $key;
		}
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
