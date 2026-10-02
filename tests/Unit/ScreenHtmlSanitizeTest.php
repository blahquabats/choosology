<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ScreenHtmlSanitizeTest extends TestCase
{
	public function testAllowsAjaxPicAndImageUrls(): void
	{
		$this->assertTrue(choosology_screen_html_img_src_allowed('/ajax/pic.php?id=12'));
		$this->assertTrue(choosology_screen_html_img_src_allowed('https://cdn.example.com/a.png'));
		$this->assertTrue(choosology_screen_html_img_src_allowed('images/foo.jpg'));
	}

	public function testBlocksDangerousSources(): void
	{
		$this->assertFalse(choosology_screen_html_img_src_allowed('javascript:alert(1)'));
		$this->assertFalse(choosology_screen_html_img_src_allowed('data:image/png;base64,xxx'));
		$this->assertFalse(choosology_screen_html_img_src_allowed('/ajax/pic.php'));
		$this->assertFalse(choosology_screen_html_img_src_allowed('https://evil.example/page'));
	}

	public function testSanitizeRemovesBadImages(): void
	{
		$html = '<p>Hi</p><img src="javascript:x"><img src="/ajax/pic.php?id=3">';
		$out = choosology_sanitize_screen_html_images($html);
		$this->assertStringNotContainsString('javascript', $out);
		$this->assertStringContainsString('ajax/pic.php?id=3', $out);
	}

	public function testUndoConnectMutation(): void
	{
		$mutated = htmlspecialchars("O'Reilly", ENT_QUOTES | ENT_HTML5);
		$this->assertSame("O'Reilly", choosology_undo_connect_string_mutation($mutated));
	}

	public function testDecode(): void
	{
		$this->assertSame('A & B', decode('A &amp; B'));
		$this->assertSame('plain', decode('<b>plain</b>', 1));
	}
}
