<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DataScripHelpersTest extends TestCase
{
	public function testSignAndFormat(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/datascrip-helpers.php';
		require_once CHOOSOLOGY_ROOT . '/paths-config.php';
		$this->assertSame("\u{20C3}", choosology_datascrip_sign());
		$this->assertSame("\u{20C3} 1,234", choosology_datascrip_format(1234));
		$this->assertSame('42', choosology_datascrip_format(42, false));
		$this->assertStringContainsString('images/datascrip.png', choosology_datascrip_sign_url());
		$html = choosology_datascrip_format_html(1234);
		$this->assertStringContainsString('datascrip.png', $html);
		$this->assertStringContainsString('1,234', $html);
		$this->assertStringContainsString('<img', $html);
	}

	public function testRewardCatalogCoversAchievementsAndActivities(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/datascrip-helpers.php';
		require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
		$rewards = choosology_datascrip_reward_catalog();
		$this->assertArrayHasKey('daily_login', $rewards['activities']);
		$this->assertGreaterThan(0, $rewards['activities']['daily_login']);
		foreach (array_keys(choosology_achievement_catalog()) as $key) {
			$this->assertArrayHasKey($key, $rewards['achievements'], $key);
			$this->assertGreaterThan(0, $rewards['achievements'][$key]);
		}
	}

	public function testReasonLabels(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/datascrip-helpers.php';
		$this->assertSame('Daily lab check-in', choosology_datascrip_reason_label('daily_login'));
		$this->assertSame('Custom memo', choosology_datascrip_reason_label('daily_login', 'Custom memo'));
	}
}
