<?php
/**
 * Save date-format preference (cookie + account when signed in), then redirect.
 */
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['date_format'])) {
		$fmt = choosology_date_format_normalize((string) $_POST['date_format']);
		choosology_date_format_save($db, $fmt);
	}
	if (isset($_POST['guide_enabled'])) {
		choosology_guide_set_enabled($db, (string) $_POST['guide_enabled'] !== '0');
	}
}

$next = (string) ($_POST['next'] ?? $_GET['next'] ?? 'index.php');
$next = ltrim(str_replace('\\', '/', $next), '/');
if ($next === '' || strpos($next, '..') !== false || preg_match('#^(https?:)?//#i', $next)) {
	$next = 'index.php';
}
if (strpos($next, 'lite/') === 0) {
	$next = substr($next, 5);
}
header('Location: ' . choosology_lite_url($next));
exit;
