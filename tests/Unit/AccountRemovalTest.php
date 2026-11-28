<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AccountRemovalTest extends TestCase
{
	public function testPasswordMatchUsesLegacyDigest(): void
	{
		require_once dirname(__DIR__, 2) . '/lib/choosology-core.php';
		require_once dirname(__DIR__, 2) . '/lib/account-removal.php';
		$hash = choosology_legacy_password_hash('removeme');
		$this->assertTrue(choosology_account_password_matches($hash, 'removeme'));
		$this->assertFalse(choosology_account_password_matches($hash, 'wrong'));
		$this->assertFalse(choosology_account_password_matches('', 'removeme'));
	}
}
