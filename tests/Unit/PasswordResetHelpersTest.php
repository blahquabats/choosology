<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PasswordResetHelpersTest extends TestCase
{
	protected function setUp(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/password-reset-helpers.php';
		require_once CHOOSOLOGY_ROOT . '/paths-config.php';
		$_SESSION = array();
	}

	public function testHashTokenIsSha256Hex(): void
	{
		$token = '0123456789abcdef0123456789abcdef';
		$hash = choosology_password_reset_hash_token($token);
		$this->assertSame(64, strlen($hash));
		$this->assertSame(hash('sha256', $token), $hash);
	}

	public function testRequestRejectsEmptyIdentity(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test unavailable');
		}
		ChoosologyTestDb::resetFixtures();
		$db = ChoosologyTestDb::mysqli();
		$result = choosology_password_reset_request($db, '   ');
		$this->assertFalse($result['ok']);
		$this->assertStringContainsString('lab handle', (string) ($result['error'] ?? ''));
	}

	public function testRequestIsGenericForUnknownAccount(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test unavailable');
		}
		ChoosologyTestDb::resetFixtures();
		$db = ChoosologyTestDb::mysqli();
		$result = choosology_password_reset_request($db, 'definitely-not-a-user-' . bin2hex(random_bytes(4)));
		$this->assertTrue($result['ok']);
		$this->assertSame(CHOOSOLOGY_RESET_GENERIC_OK, $result['message'] ?? null);
		$this->assertFalse($result['mailed'] ?? true);
	}

	public function testConfirmRoundTripUpdatesPassword(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test unavailable');
		}
		ChoosologyTestDb::resetFixtures();
		$db = ChoosologyTestDb::mysqli();
		choosology_password_reset_ensure_schema($db);

		$token = bin2hex(random_bytes(16));
		$hash = choosology_password_reset_hash_token($token);
		$esc = mysqli_real_escape_string($db, $hash);
		mysqli_query(
			$db,
			"UPDATE users
			 SET reset_token='$esc',
			     reset_expires=DATE_ADD(NOW(), INTERVAL 1 HOUR)
			 WHERE name='testuser'
			 LIMIT 1"
		);

		$bad = choosology_password_reset_confirm($db, $token, 'abcdef', 'abcdeg');
		$this->assertFalse($bad['ok']);
		$this->assertStringContainsString('match', (string) ($bad['error'] ?? ''));

		$short = choosology_password_reset_confirm($db, $token, 'abc', 'abc');
		$this->assertFalse($short['ok']);

		$ok = choosology_password_reset_confirm($db, $token, 'newlabpass', 'newlabpass');
		$this->assertTrue($ok['ok']);
		$this->assertSame('testuser', $ok['name'] ?? null);

		$expected = choosology_legacy_password_hash('newlabpass');
		$r = mysqli_query($db, "SELECT pass, reset_token, reset_expires FROM users WHERE name='testuser' LIMIT 1");
		$row = $r ? mysqli_fetch_assoc($r) : null;
		$this->assertNotNull($row);
		$this->assertSame($expected, $row['pass']);
		$this->assertTrue($row['reset_token'] === null || $row['reset_token'] === '');
		$this->assertTrue($row['reset_expires'] === null || $row['reset_expires'] === '');

		/* Token cannot be reused. */
		$reuse = choosology_password_reset_validate_token($db, $token);
		$this->assertFalse($reuse['ok']);
	}

	public function testInvalidTokenRejected(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test unavailable');
		}
		ChoosologyTestDb::resetFixtures();
		$db = ChoosologyTestDb::mysqli();
		$bad = choosology_password_reset_validate_token($db, 'not-a-token');
		$this->assertFalse($bad['ok']);
		$missing = choosology_password_reset_validate_token($db, str_repeat('a', 32));
		$this->assertFalse($missing['ok']);
	}
}
