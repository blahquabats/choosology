<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LiteViewOwnerTest extends TestCase
{
	public function testLiteViewDefinesOwnerFromAdventureUserBeforeByline(): void
	{
		$src = file_get_contents(CHOOSOLOGY_ROOT . '/lite/view.php');
		$this->assertIsString($src);
		$assign = strpos($src, "\$owner = (string) (\$adv['user'] ?? '');");
		$byline = strpos($src, 'htmlspecialchars($owner, ENT_QUOTES, \'UTF-8\')');
		$this->assertNotFalse($assign);
		$this->assertNotFalse($byline);
		$this->assertLessThan($byline, $assign);
	}

	public function testScreenAjaxCastsNullScreenTextBeforeDecode(): void
	{
		$src = file_get_contents(CHOOSOLOGY_ROOT . '/ajax/screenajax.php');
		$this->assertIsString($src);
		$this->assertStringContainsString(
			"htmlspecialchars_decode(htmlspecialchars_decode((string) (\$screen['text'] ?? '')))",
			$src
		);
	}
}
