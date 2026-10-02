<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbConfigTest extends TestCase
{
	protected function tearDown(): void
	{
		putenv('CHOOSOLOGY_DB_HOST');
		putenv('CHOOSOLOGY_DB_USER');
		putenv('CHOOSOLOGY_DB_PASSWORD');
		putenv('CHOOSOLOGY_DB_DATABASE');
		putenv('CHOOSOLOGY_JSON_ERRORS');
		unset($_SERVER['HTTPS'], $_SERVER['SERVER_PORT'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
		unset($_SERVER['SCRIPT_NAME'], $_SERVER['PHP_SELF'], $_GET['format']);
		$_SERVER['HTTP_ACCEPT'] = '';
	}

	public function testDbSettingsEnvOverrides(): void
	{
		putenv('CHOOSOLOGY_DB_HOST=127.0.0.1');
		putenv('CHOOSOLOGY_DB_USER=chooser');
		putenv('CHOOSOLOGY_DB_PASSWORD=secret');
		putenv('CHOOSOLOGY_DB_DATABASE=choosology_test');
		$cfg = choosology_db_settings('ignored');
		$this->assertSame('127.0.0.1', $cfg['host']);
		$this->assertSame('chooser', $cfg['user']);
		$this->assertSame('secret', $cfg['password']);
		$this->assertSame('choosology_test', $cfg['database']);
	}

	public function testHttpsDetection(): void
	{
		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue(choosology_request_is_https());
		unset($_SERVER['HTTPS']);
		$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
		$this->assertTrue(choosology_request_is_https());
		unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
		$_SERVER['SERVER_PORT'] = '80';
		$this->assertFalse(choosology_request_is_https());
	}

	public function testWantsJsonErrorResponse(): void
	{
		putenv('CHOOSOLOGY_JSON_ERRORS=1');
		$this->assertTrue(choosology_wants_json_error_response());
		putenv('CHOOSOLOGY_JSON_ERRORS');
		$_GET['format'] = 'json';
		$this->assertTrue(choosology_wants_json_error_response());
		unset($_GET['format']);
		$_SERVER['SCRIPT_NAME'] = '/ajax/signup.php';
		$this->assertTrue(choosology_wants_json_error_response());
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['HTTP_ACCEPT'] = 'text/html';
		$this->assertFalse(choosology_wants_json_error_response());
	}
}
