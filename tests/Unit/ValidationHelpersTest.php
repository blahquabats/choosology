<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidationHelpersTest extends TestCase
{
	public function testCheckEmail(): void
	{
		$this->assertTrue(checkEmail('a@b.co'));
		$this->assertFalse(checkEmail(''));
		$this->assertFalse(checkEmail('not-an-email'));
		$this->assertFalse(checkEmail(str_repeat('a', 40) . '@b.com')); // >45
	}

	public function testLegacyPasswordHash(): void
	{
		$this->assertSame(md5('cYosecret'), choosology_legacy_password_hash('secret'));
	}

	public function testNicedatetimeModes(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/date-format-helpers.php';
		$_COOKIE = array();
		$stamp = '2020-06-03 15:30:00';
		$this->assertSame('06/03/2020', nicedatetime($stamp, 'date'));
		$this->assertStringContainsString('m', nicedatetime($stamp, 'time'));
		$this->assertStringContainsString('on', nicedatetime($stamp, 'datetime'));
		$this->assertStringContainsString('06/03/2020', nicedatetime($stamp, 'datetime'));
	}

	public function testMakeStars(): void
	{
		$empty = makeStars(0);
		$this->assertStringContainsString('not rated', $empty);
		$rated = makeStars(3.5);
		$this->assertStringContainsString('tinystarsholder', $rated);
		$this->assertStringContainsString('avgstar', $rated);
	}

	public function testIconKnownName(): void
	{
		$html = icon('beaker');
		$this->assertStringContainsString('&#xe025;', $html);
	}

	public function testResourceHelpers(): void
	{
		$this->assertSame('512 B', choosology_resource_format_size(512));
		$this->assertSame('1.5 KB', choosology_resource_format_size(1536));
		$this->assertSame('jpg', choosology_resource_ext_for_mime('image/jpeg'));
		$this->assertSame('', choosology_resource_ext_for_mime('text/plain'));
	}
}
