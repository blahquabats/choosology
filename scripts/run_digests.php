#!/usr/bin/env php
<?php
/**
 * CLI: send due adventure activity digests.
 * Usage: php scripts/run_digests.php [username]
 */
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';

$only = isset($argv[1]) ? (string) $argv[1] : null;
$n = choosology_run_adventure_digests($only);
fwrite(STDOUT, "Digests sent: $n\n");
exit(0);
