<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NewsAdminHelpersTest extends TestCase
{
	public function testNewsPostKeepsBodyAndFallsBackToAdminByline(): void
	{
		$item = choosology_news_admin_normalize(
			array('kind' => 'news', 'headline' => '  Lab reopens  ', 'body' => " <p>Doors open.</p>\n", 'by' => '  '),
			'adminuser'
		);
		$this->assertTrue($item['ok']);
		$this->assertSame('news', $item['kind']);
		$this->assertSame(0, $item['id']);
		$this->assertSame('Lab reopens', $item['headline']);
		$this->assertSame('<p>Doors open.</p>', $item['body']);
		$this->assertSame('adminuser', $item['by']);
	}

	public function testExplicitBylineWinsAndIsClipped(): void
	{
		$item = choosology_news_admin_normalize(
			array('kind' => 'news', 'id' => '7', 'headline' => 'H', 'body' => 'B', 'by' => str_repeat('x', 60)),
			'adminuser'
		);
		$this->assertTrue($item['ok']);
		$this->assertSame(7, $item['id']);
		$this->assertSame(CHOOSOLOGY_NEWS_BYLINE_MAX, strlen($item['by']));
	}

	public function testMinorUpdateNeedsOnlyTextAndIsClipped(): void
	{
		$item = choosology_news_admin_normalize(
			array('kind' => 'update', 'headline' => str_repeat('é', 300), 'body' => 'ignored', 'by' => 'ignored'),
			'adminuser'
		);
		$this->assertTrue($item['ok']);
		$this->assertSame('update', $item['kind']);
		$this->assertSame(CHOOSOLOGY_NEWS_UPDATE_MAX, mb_strlen($item['headline'], 'UTF-8'));
		$this->assertSame('', $item['body']);
		$this->assertSame('', $item['by']);
	}

	public function testRejectsIncompleteOrUnknownSubmissions(): void
	{
		$this->assertSame(
			'Headline is required.',
			choosology_news_admin_normalize(array('kind' => 'news', 'body' => 'B'), 'a')['error']
		);
		$this->assertSame(
			'Body is required.',
			choosology_news_admin_normalize(array('kind' => 'news', 'headline' => 'H', 'body' => '  '), 'a')['error']
		);
		$this->assertSame(
			'Update text is required.',
			choosology_news_admin_normalize(array('kind' => 'update', 'headline' => ' '), 'a')['error']
		);
		$this->assertSame(
			'Unknown item type.',
			choosology_news_admin_normalize(array('kind' => 'memo', 'headline' => 'H'), 'a')['error']
		);
		$this->assertSame(
			'Invalid item id.',
			choosology_news_admin_normalize(array('kind' => 'update', 'id' => -3, 'headline' => 'H'), 'a')['error']
		);
	}

	public function testParseKey(): void
	{
		$this->assertSame(array('kind' => 'news', 'id' => 12), choosology_news_admin_parse_key('news:12'));
		$this->assertSame(array('kind' => 'update', 'id' => 0), choosology_news_admin_parse_key(' update:0 '));
		$this->assertNull(choosology_news_admin_parse_key(''));
		$this->assertNull(choosology_news_admin_parse_key('news'));
		$this->assertNull(choosology_news_admin_parse_key('memo:4'));
		$this->assertNull(choosology_news_admin_parse_key('news:-1'));
		$this->assertNull(choosology_news_admin_parse_key('news:1 OR 1=1'));
	}

	public function testRawFormFieldsKeepMarkupAndLineBreaks(): void
	{
		$body = "<p>It's \"one\" & two.</p>\r\n<p>Three</p>";
		$fields = choosology_raw_form_fields(http_build_query(array(
			'action' => 'save_news',
			'body' => $body,
			'tags' => array('dropped'),
		)));
		$this->assertSame('save_news', $fields['action']);
		$this->assertSame($body, $fields['body']);
		$this->assertArrayNotHasKey('tags', $fields);
		$this->assertSame(array(), choosology_raw_form_fields(''));
		$this->assertSame(array('news' => 'update:3'), choosology_raw_form_fields('news=update%3A3'));
	}

	public function testAdminGateIsRoleBasedNotNameBased(): void
	{
		$saved = $_SESSION ?? array();
		try {
			$_SESSION = array('user' => 'The Grasssmith', 'usertype' => 0);
			$this->assertFalse(choosology_restricted_is_admin());
			$_SESSION = array('user' => 'someone_else', 'usertype' => 1);
			$this->assertTrue(choosology_restricted_is_admin());
			$_SESSION = array('usertype' => 1);
			$this->assertFalse(choosology_restricted_is_admin());
		} finally {
			$_SESSION = $saved;
		}
	}
}
