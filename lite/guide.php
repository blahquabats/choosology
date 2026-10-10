<?php
/**
 * Lite lab-guide choices. Form post, then redirect back to the page.
 */
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db;
require_once dirname(__DIR__) . '/lib/guide-helpers.php';

$next = choosology_guide_safe_return((string) ($_POST['next'] ?? 'index.php'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	choosology_guide_respond(
		$db,
		(string) ($_POST['interaction_key'] ?? ''),
		(string) ($_POST['choice'] ?? '')
	);
}

header('Location: ' . choosology_lite_url($next));
exit;
