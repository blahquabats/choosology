<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DateFormatHelpersTest extends TestCase
{
	protected function setUp(): void
	{
		require_once CHOOSOLOGY_ROOT . '/lib/date-format-helpers.php';
		$_COOKIE = array();
		unset($_SESSION['user']);
	}

	public function testNormalize(): void
	{
		$this->assertSame('mdy', choosology_date_format_normalize('mdy'));
		$this->assertSame('mdy', choosology_date_format_normalize(''));
		$this->assertSame('dmy', choosology_date_format_normalize('dmy'));
		$this->assertSame('dmy', choosology_date_format_normalize('dd/mm/yyyy'));
	}

	public function testFormatPatternsAndLabels(): void
	{
		$this->assertSame('m/d/Y', choosology_date_format_pattern('date', 'mdy'));
		$this->assertSame('d/m/Y', choosology_date_format_pattern('date', 'dmy'));
		$this->assertSame('mm/dd/yyyy', choosology_date_format_label('mdy'));
		$this->assertSame('dd/mm/yyyy', choosology_date_format_label('dmy'));
	}

	public function testFormatUserDateRespectsPreference(): void
	{
		$stamp = '2020-06-03 15:30:00';
		$this->assertSame('06/03/2020', choosology_format_user_date($stamp, 'date', 'mdy'));
		$this->assertSame('03/06/2020', choosology_format_user_date($stamp, 'date', 'dmy'));
		$this->assertSame('3:30pm on 06/03/2020', choosology_format_user_date($stamp, 'datetime', 'mdy'));
		$this->assertSame('3:30pm on 03/06/2020', choosology_format_user_date($stamp, 'datetime', 'dmy'));
	}

	public function testNicedatetimeUsesCookiePreference(): void
	{
		$stamp = '2020-06-03 15:30:00';
		$_COOKIE[CHOOSOLOGY_DATE_FMT_COOKIE] = 'mdy';
		$this->assertSame('06/03/2020', nicedatetime($stamp, 'date'));
		$_COOKIE[CHOOSOLOGY_DATE_FMT_COOKIE] = 'dmy';
		$this->assertSame('03/06/2020', nicedatetime($stamp, 'date'));
	}

	public function testCookieSaveRoundTrip(): void
	{
		choosology_date_format_cookie_set('dmy');
		$this->assertSame('dmy', choosology_date_format_cookie_get());
		$this->assertSame('dmy', choosology_date_format_preferred(null));
	}
}
