<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/play-surface.php';

use PHPUnit\Framework\TestCase;

final class PlaySurfaceTest extends TestCase
{
	public function testStartOverIsHiddenOnTheOpeningScreen(): void
	{
		$this->assertFalse(choosology_play_show_start_over('10', '10'));
		$this->assertFalse(choosology_play_show_start_over(10, '10'));
		$this->assertFalse(choosology_play_show_start_over('', '10'));
		$this->assertTrue(choosology_play_show_start_over('11', '10'));
	}

	public function testAuthorHrefIsAPublicRoute(): void
	{
		$this->assertSame('#/author/Jack%20fern', choosology_author_profile_href('Jack fern', 'classic'));
		$lite = choosology_author_profile_href('Jack fern', 'lite');
		$this->assertStringContainsString('author.php?user=Jack%20fern', $lite);
		$this->assertStringNotContainsString('account.php', $lite);
	}

	public function testGuestBylineIsNotALink(): void
	{
		$this->assertSame('guest', choosology_author_byline_html('guest', 'classic'));
		$this->assertStringContainsString('href="#/author/Ada"', choosology_author_byline_html('Ada', 'classic'));
	}
}
