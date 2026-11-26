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

	public function testClipboardTitleUsesStoredFontHtml(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
		$stored = '&lt;font color=&quot;#ffccff&quot;&gt;blah blah adventure&lt;/font&gt;';
		$this->assertSame(
			'<font color="#ffccff">blah blah adventure</font>',
			choosology_clipboard_title_html($stored)
		);
		$this->assertSame(
			decode($stored),
			choosology_clipboard_title_html($stored)
		);
		$this->assertSame('Untitled', choosology_clipboard_title_html('   '));
	}

	public function testEvalAutoExposesChecklistAutoKeys(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
		$this->assertTrue(function_exists('choosology_clipboard_eval_auto'));
		$expected = array(
			'has_adventure',
			'has_public',
			'has_note',
			'has_todo',
			'has_screenwright',
			'visited_clipboard',
			'visited_results',
		);
		foreach (choosology_clipboard_checklist_defs() as $def) {
			$this->assertContains($def['auto'], $expected, $def['key']);
		}
	}
}
