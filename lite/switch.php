<?php
/**
 * Switch Classic ↔ Lite preference and redirect.
 * GET: to=lite|classic, optional next= (path or full relative URL on this host)
 */
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db;

$to = choosology_ui_normalize((string) ($_GET['to'] ?? 'lite'));
choosology_ui_save_preference($db, $to);

$next = (string) ($_GET['next'] ?? '');
if ($next !== '' && strpos($next, '://') === false && strpos($next, '//') !== 0) {
	if ($next[0] !== '/') {
		$next = '/' . $next;
	}
} else {
	$next = '';
}

if ($to === 'lite') {
	if ($next === '' || strpos($next, '/lite') === false) {
		$next = choosology_lite_url('index.php');
	}
} else {
	if ($next === '' || strpos($next, '/lite') !== false) {
		$next = choosology_site_url('index.php') . '?stay=1#/home';
	} elseif (strpos($next, 'stay=') === false && strpos($next, 'index.php') !== false) {
		$next .= (strpos($next, '?') !== false ? '&' : '?') . 'stay=1';
	}
}

header('Location: ' . $next);
exit;
