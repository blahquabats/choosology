<?php
/**
 * Resource upload helpers (safe without connect.php).
 */

function choosology_resource_format_size(int $bytes): string
{
	if ($bytes >= 1048576) {
		return round($bytes / 1048576, 1) . ' MB';
	}
	if ($bytes >= 1024) {
		return round($bytes / 1024, 1) . ' KB';
	}
	return $bytes . ' B';
}

function choosology_resource_ext_for_mime(string $mime): string
{
	$map = array(
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/webp' => 'webp',
	);
	return $map[$mime] ?? '';
}
