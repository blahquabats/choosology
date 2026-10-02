<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FontOptionsTest extends TestCase
{
	public function testSiteCatalogHasTrebuchet(): void
	{
		$cat = choosology_font_site_catalog();
		$this->assertArrayHasKey('trebuchet', $cat);
		$this->assertSame('Trebuchet MS', $cat['trebuchet']['label']);
	}

	public function testNormalizeKeyAcceptsLabelAndLegacy(): void
	{
		$this->assertSame('arial', choosology_font_normalize_key('Arial'));
		$this->assertSame('trebuchet', choosology_font_normalize_key(null));
		$this->assertSame('trebuchet', choosology_font_normalize_key('nope'));
	}

	public function testStackAndLabelForKey(): void
	{
		$stack = choosology_font_stack_for_key('consolas');
		$this->assertStringContainsString('Consolas', $stack);
		$this->assertSame('Consolas', choosology_font_label_for_key('consolas'));
	}

	public function testStyleExtractAndSetFontFamily(): void
	{
		$style = 'font-weight:bold; font-family:"Arial"; color:#000';
		$this->assertStringContainsString('Arial', choosology_style_extract_font_family($style));
		$updated = choosology_style_set_font_family($style, 'Georgia, serif');
		$this->assertStringContainsString('Georgia', $updated);
	}

	public function testAdvPlayTypographyCss(): void
	{
		$adv = array(
			'font' => 'arial',
			'textstyle' => 'font-weight:normal; color:#111;',
			'linkstyle' => 'color:#00f;',
		);
		$text = choosology_adv_play_typography_css($adv, 'text');
		$this->assertNotSame('', $text);
		$choice = choosology_adv_play_typography_css($adv, 'choice');
		$this->assertNotSame('', $choice);
	}

	public function testTinymceFormatsNonEmpty(): void
	{
		$fmt = choosology_font_tinymce_formats();
		$this->assertStringContainsString('=', $fmt);
	}
}
