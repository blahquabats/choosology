<?php
/**
 * Integration bootstrap helpers — connect to MariaDB without connect.php request mutation.
 */
declare(strict_types=1);

final class ChoosologyTestDb
{
	private static ?mysqli $db = null;

	public static function available(): bool
	{
		try {
			self::mysqli();
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	public static function mysqli(): mysqli
	{
		if (self::$db instanceof mysqli) {
			return self::$db;
		}
		putenv('CHOOSOLOGY_DB_DATABASE=choosology_test');
		$_ENV['CHOOSOLOGY_DB_DATABASE'] = 'choosology_test';
		$cfg = choosology_db_settings('choosology_test');
		$db = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
		if (!$db) {
			throw new RuntimeException('Cannot connect to choosology_test: ' . mysqli_connect_error());
		}
		mysqli_set_charset($db, 'utf8mb4');
		self::$db = $db;
		$GLOBALS['db'] = $db;
		$GLOBALS['sel'] = 'cYo';
		return $db;
	}

	public static function ensureSchema(): void
	{
		$db = self::mysqli();
		$r = mysqli_query($db, "SHOW TABLES LIKE 'users'");
		if ($r && mysqli_num_rows($r) > 0) {
			return;
		}
		throw new RuntimeException('choosology_test schema missing — run tests/bin/prepare-test-db.sh');
	}

	public static function resetFixtures(): void
	{
		$db = self::mysqli();
		self::ensureSchema();
		@mysqli_query($db, 'SET FOREIGN_KEY_CHECKS=0');
		foreach (array(
			'datascrip_ledger',
			'datascrip_balances',
			'user_achievements',
			'clipboard_checklist',
			'clipboard_todos',
			'clipboard_pad',
			'ending_finds',
			'messages',
			'advscreens',
			'advs',
			'users',
		) as $t) {
			try {
				mysqli_query($db, "DELETE FROM `$t`");
			} catch (Throwable $e) {
				/* table may not exist yet in a fresh test DB */
			}
		}
		@mysqli_query($db, 'SET FOREIGN_KEY_CHECKS=1');

		$pass = choosology_legacy_password_hash('testpass');
		mysqli_query(
			$db,
			"INSERT INTO users (name, pass, email, authent, usertype, joined, view_restricted, fbshow)
			 VALUES
			 ('testuser', '$pass', 'test@example.com', '', 0, NOW(), 0, 1),
			 ('adminuser', '$pass', 'admin@example.com', '', 1, NOW(), 0, 1)"
		);
	}
}
