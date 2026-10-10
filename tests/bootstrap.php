<?php
/**
 * PHPUnit bootstrap — loads pure libs without connect.php.
 */
declare(strict_types=1);

define('CHOOSOLOGY_ROOT', dirname(__DIR__));

require_once CHOOSOLOGY_ROOT . '/vendor/autoload.php';
require_once CHOOSOLOGY_ROOT . '/db-config.php';
require_once CHOOSOLOGY_ROOT . '/paths-config.php';
require_once CHOOSOLOGY_ROOT . '/font-options.php';
require_once CHOOSOLOGY_ROOT . '/lib/choosology-core.php';
require_once CHOOSOLOGY_ROOT . '/lib/ending-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/account-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/resource-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/pic-list-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/news-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/feed-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/date-format-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/guide-helpers.php';
require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
require_once CHOOSOLOGY_ROOT . '/icondefs.php';
require_once CHOOSOLOGY_ROOT . '/tests/Support/ChoosologyTestDb.php';
require_once CHOOSOLOGY_ROOT . '/tests/Support/WorkflowHttpClient.php';
require_once CHOOSOLOGY_ROOT . '/tests/Support/ChoosologyWorkflows.php';
require_once CHOOSOLOGY_ROOT . '/messagesfunc.php';
