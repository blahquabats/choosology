#!/usr/bin/env php
<?php
/**
 * Run Classic/Lite multi-step workflows and print experiential metrics JSON.
 *
 * Usage:
 *   php tests/bin/run-workflows.php
 *   php tests/bin/run-workflows.php --only=lite_login_browse_play_ending,classic_login_play_ending
 *   php tests/bin/run-workflows.php --out=/opt/cursor/artifacts/workflow-metrics.json
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/db-config.php';
require_once $root . '/paths-config.php';
require_once $root . '/lib/choosology-core.php';
require_once $root . '/tests/Support/ChoosologyWorkflows.php';

$base = 'http://127.0.0.1:8000';
$only = null;
$out = null;
foreach (array_slice($argv, 1) as $arg) {
	if (str_starts_with($arg, '--base=')) {
		$base = substr($arg, 7);
	} elseif (str_starts_with($arg, '--only=')) {
		$only = array_filter(array_map('trim', explode(',', substr($arg, 7))));
	} elseif (str_starts_with($arg, '--out=')) {
		$out = substr($arg, 6);
	}
}

if (!ChoosologyWorkflows::serverAvailable($base)) {
	fwrite(STDERR, "Server not available at {$base}\n");
	exit(2);
}

$cfg = choosology_db_settings('choosology');
$db = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
if (!$db) {
	fwrite(STDERR, "Cannot connect to app DB\n");
	exit(2);
}
mysqli_set_charset($db, 'utf8mb4');
$fixture = ChoosologyWorkflows::resolvePlayFixture($db);
if ($fixture === null) {
	fwrite(STDERR, "No public branching adventure fixture found (seed Midnight Beaker)\n");
	exit(2);
}

$names = $only ?: ChoosologyWorkflows::catalog();
$report = array(
	'generated_at' => gmdate('c'),
	'base' => $base,
	'fixture' => $fixture,
	'workflows' => array(),
	'summary' => array(),
);
$failed = 0;
foreach ($names as $name) {
	$result = ChoosologyWorkflows::run($name, array(
		'base' => $base,
		'fixture' => $fixture,
		'user' => 'labtester',
		'pass' => 'labpass',
	));
	$report['workflows'][$name] = $result;
	if (empty($result['ok'])) {
		$failed++;
	}
}

$byMode = array('lite' => array(), 'classic' => array(), 'both' => array());
foreach ($report['workflows'] as $name => $result) {
	$mode = (string) ($result['mode'] ?? 'both');
	if (!isset($byMode[$mode])) {
		$byMode[$mode] = array();
	}
	$byMode[$mode][$name] = array(
		'ok' => !empty($result['ok']),
		'clicks' => (int) ($result['clicks'] ?? 0),
		'elapsed_ms' => (int) ($result['elapsed_ms'] ?? 0),
		'steps' => count($result['steps'] ?? array()),
	);
}
$report['summary'] = array(
	'failed' => $failed,
	'total' => count($names),
	'by_mode' => $byMode,
);

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
if ($out) {
	$dir = dirname($out);
	if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
		mkdir($dir, 0775, true);
	}
	file_put_contents($out, $json);
	fwrite(STDERR, "Wrote {$out}\n");
}
echo $json;
exit($failed > 0 ? 1 : 0);
