<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ClipboardCatalogTest extends TestCase
{
	public function testAchievementCatalogKeys(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
		$cat = choosology_achievement_catalog();
		$this->assertArrayHasKey('lab_initiate', $cat);
		$this->assertArrayHasKey('clipboard_clerk', $cat);
		$this->assertArrayHasKey('results_analyst', $cat);
		$this->assertNotSame('', $cat['lab_initiate']['label']);
	}

	public function testChecklistDefsLinkToAchievements(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
		$cat = choosology_achievement_catalog();
		foreach (choosology_clipboard_checklist_defs() as $def) {
			$this->assertArrayHasKey('key', $def);
			$this->assertArrayHasKey($def['achievement'], $cat, $def['key']);
			$this->assertNotSame('', $def['auto']);
		}
	}
}
