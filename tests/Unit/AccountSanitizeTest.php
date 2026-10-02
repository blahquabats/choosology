<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AccountSanitizeTest extends TestCase
{
	public function testPlainLenStripsTags(): void
	{
		$this->assertSame(5, choosology_account_plain_len('<p>Hello</p>'));
	}

	public function testSanitizeRemovesScriptAndEvents(): void
	{
		$html = '<p onclick="x">Hi</p><script>evil()</script><a href="javascript:alert(1)">x</a>';
		$out = choosology_account_sanitize_about($html);
		$this->assertStringNotContainsString('<script', $out);
		$this->assertStringNotContainsString('onclick', $out);
		$this->assertStringNotContainsString('javascript:', $out);
		$this->assertStringContainsString('<p', $out);
	}

	public function testSanitizeKeepsSafeColorStyle(): void
	{
		$html = '<span style="color: #ff0000; position: absolute">x</span>';
		$out = choosology_account_sanitize_about($html);
		$this->assertStringContainsString('color', $out);
		$this->assertStringNotContainsString('position', $out);
	}
}
