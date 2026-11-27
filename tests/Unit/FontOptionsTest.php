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

	public function testReadingPresentationUsesAuthorAndScreenColors(): void
	{
		$adv = array(
			'bg' => '#112233',
			'box' => '#fff8dc',
			'border' => '#d6a84f',
			'borderwidth' => 3,
			'font' => 'arial',
			'textstyle' => 'color:#111111;',
			'linkstyle' => 'color:#0000ff;',
			'titlestyle' => 'font-weight:bold;color:#ffccff;',
			'title' => '&lt;font color=&quot;#ffccff&quot;&gt;Cave mouth&lt;/font&gt;',
		);
		$screen = array('screenbgcolor' => '#abcdef', 'screenboxcolor' => '');
		$read = choosology_adv_reading_presentation($adv, $screen);
		$this->assertStringContainsString('background-color:#abcdef', $read['background']);
		$this->assertStringContainsString('background-color:#fff8dc', $read['panel']);
		$this->assertStringContainsString('3px solid #d6a84f', $read['panel']);
		$this->assertStringContainsString('font-family', $read['text']);
		$this->assertStringContainsString('color:#111111', $read['text']);
		$this->assertStringContainsString('color:#0000ff', $read['choice']);
		$this->assertStringContainsString('color:#ffccff', $read['title']);
		$this->assertStringContainsString('<font color="#ffccff">Cave mouth</font>', $read['title_html']);
	}

	public function testTinymceFormatsNonEmpty(): void
	{
		$fmt = choosology_font_tinymce_formats();
		$this->assertStringContainsString('=', $fmt);
	}
}
