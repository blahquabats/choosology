<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PicListHelpersTest extends TestCase
{
	public function testTagsFromCat(): void
	{
		$this->assertSame(array('lab', 'icon'), choosology_listuserpics_tags_from_cat('lab, icon'));
		$this->assertSame(array('solo'), choosology_listuserpics_tags_from_cat('solo'));
		$this->assertSame(array(), choosology_listuserpics_tags_from_cat(''));
	}

	public function testRowMatchesQAndTag(): void
	{
		$pic = array('imagename' => 'Beaker', 'filename' => 'beaker.png', 'cat' => 'lab,glass');
		$this->assertTrue(choosology_listuserpics_row_matches_q($pic, 'beak'));
		$this->assertFalse(choosology_listuserpics_row_matches_q($pic, 'zzz'));
		$this->assertTrue(choosology_listuserpics_row_matches_tag($pic, 'lab'));
		$this->assertFalse(choosology_listuserpics_row_matches_tag($pic, 'missing'));
	}

	public function testAccountPicTags(): void
	{
		$this->assertSame(array('a', 'b'), choosology_account_pic_tags('a, b'));
	}
}
