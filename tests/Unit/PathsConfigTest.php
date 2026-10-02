<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PathsConfigTest extends TestCase
{
	protected function tearDown(): void
	{
		putenv('CHOOSOLOGY_PICS_ROOT');
		putenv('CHOOSOLOGY_PICS_UNIVERSAL');
		putenv('CHOOSOLOGY_WEB_BASE');
		unset($_SERVER['SCRIPT_NAME']);
		choosology_web_base_reset();
	}

	public function testPicsRootEnvOverride(): void
	{
		putenv('CHOOSOLOGY_PICS_ROOT=/tmp/choosology-pics');
		$this->assertSame('/tmp/choosology-pics', choosology_pics_root());
	}

	public function testPicsUniversalFallsBackUnderRoot(): void
	{
		putenv('CHOOSOLOGY_PICS_ROOT=/tmp/choosology-pics');
		putenv('CHOOSOLOGY_PICS_UNIVERSAL');
		$uni = choosology_pics_universal_dir();
		$this->assertStringEndsWith(DIRECTORY_SEPARATOR . 'universal', $uni);
	}

	/** @runInSeparateProcess */
	public function testWebBaseFromEnv(): void
	{
		putenv('CHOOSOLOGY_WEB_BASE=/choosology');
		choosology_web_base_reset();
		$this->assertSame('/choosology', choosology_web_base());
		$this->assertSame('/choosology/ajax/pic.php', choosology_site_url('ajax/pic.php'));
	}

	/** @runInSeparateProcess */
	public function testSiteUrlAtDomainRoot(): void
	{
		putenv('CHOOSOLOGY_WEB_BASE=');
		// Force empty via SCRIPT_NAME inference when env is empty string — set explicit empty local? env empty means skip.
		// Use reset + SCRIPT_NAME at root ajax path.
		$_SERVER['SCRIPT_NAME'] = '/ajax/foo.php';
		choosology_web_base_reset();
		$this->assertSame('', choosology_web_base());
		$this->assertSame('/home.php', choosology_site_url('home.php'));
	}
}
