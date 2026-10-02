<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NewsPureHelpersTest extends TestCase
{
	public function testBodyAndStampRaw(): void
	{
		$this->assertSame('Hello', choosology_news_row_body_raw(array('body' => 'Hello')));
		$this->assertSame('Legacy', choosology_news_row_body_raw(array('text' => 'Legacy')));
		$this->assertSame('', choosology_news_stamp_raw(array()));
		$this->assertSame('', choosology_news_stamp_raw(array('whenposted' => '0000-00-00 00:00:00')));
		$this->assertSame('2020-01-02 03:04:05', choosology_news_stamp_raw(array('whenposted' => '2020-01-02 03:04:05')));
	}

	public function testListAndDetailQueries(): void
	{
		$schema = array('has_whenposted' => true, 'has_body' => true, 'has_text' => false, 'has_by' => true);
		$list = choosology_news_list_query($schema);
		$this->assertStringContainsString('whenposted', $list);
		$this->assertStringContainsString('body', $list);
		$detail = choosology_news_detail_select($schema);
		$this->assertStringContainsString('`by`', $detail);
	}

	public function testExcerpt(): void
	{
		$long = str_repeat('word ', 40);
		$ex = choosology_news_excerpt('<p>' . $long . '</p>', 20);
		$this->assertLessThanOrEqual(25, mb_strlen($ex));
		$this->assertStringContainsString('…', $ex);
	}

	public function testFeedDates(): void
	{
		$this->assertSame('', choosology_feed_date_label(''));
		$label = choosology_feed_date_label('2020-06-03 12:00:00');
		$this->assertStringContainsString('2020', $label);
		$iso = choosology_feed_date_iso('2020-06-03 12:00:00');
		$this->assertStringContainsString('2020', $iso);
		$this->assertStringContainsString('T', $iso);
	}
}
