<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DataScripIntegrationTest extends TestCase
{
	protected function setUp(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test DB unavailable');
		}
		require_once CHOOSOLOGY_ROOT . '/lib/datascrip-helpers.php';
		require_once CHOOSOLOGY_ROOT . '/lib/clipboard-helpers.php';
		if (is_readable(CHOOSOLOGY_ROOT . '/messagesfunc.php')) {
			require_once CHOOSOLOGY_ROOT . '/messagesfunc.php';
		}
		$db = ChoosologyTestDb::mysqli();
		choosology_datascrip_ensure_schema($db);
		choosology_clipboard_ensure_schema($db);
		ChoosologyTestDb::resetFixtures();
	}

	public function testDailyLoginIsIdempotentPerDay(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$a = choosology_datascrip_try_daily_login($db, 'testuser');
		$this->assertTrue($a['ok']);
		$this->assertTrue($a['applied']);
		$this->assertSame(5, $a['amount']);
		$b = choosology_datascrip_try_daily_login($db, 'testuser');
		$this->assertTrue($b['ok']);
		$this->assertFalse($b['applied']);
		$this->assertSame(5, choosology_datascrip_balance($db, 'testuser'));
	}

	public function testAchievementAwardsScripOnce(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$this->assertTrue(choosology_award_achievement($db, 'testuser', 'lab_initiate'));
		$this->assertSame(25, choosology_datascrip_balance($db, 'testuser'));
		$this->assertFalse(choosology_award_achievement($db, 'testuser', 'lab_initiate'));
		$this->assertSame(25, choosology_datascrip_balance($db, 'testuser'));
	}

	public function testAdminGrantToOneUser(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$res = choosology_datascrip_admin_grant($db, 'adminuser', 'testuser', 12, 'Test grant', false);
		$this->assertTrue($res['ok']);
		$this->assertSame(1, $res['granted']);
		$this->assertSame(12, choosology_datascrip_balance($db, 'testuser'));
		$entries = choosology_datascrip_ledger_entries($db, 'testuser', 5);
		$this->assertNotEmpty($entries);
		$this->assertSame('adminuser', $entries[0]['actor']);
	}
}
