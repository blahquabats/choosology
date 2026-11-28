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

	public function testCatalogResolveStaysInsideAllowedDirs(): void
	{
		$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'choosology-pic-test-' . getmypid();
		$pics = $root . DIRECTORY_SEPARATOR . 'pics';
		$about = $root . DIRECTORY_SEPARATOR . 'about';
		mkdir($pics . DIRECTORY_SEPARATOR . 'tz', 0775, true);
		mkdir($about, 0775, true);
		file_put_contents($pics . DIRECTORY_SEPARATOR . 'tz' . DIRECTORY_SEPARATOR . 'lantern.jpg', 'jpeg');
		file_put_contents($about . DIRECTORY_SEPARATOR . 'story-bg.jpg', 'jpeg');
		$hit = choosology_catalog_resolve_file('catalog:pics/tz/lantern.jpg', $pics, $about);
		$story = choosology_catalog_resolve_file('catalog:about/story-bg.jpg', $pics, $about);
		$this->assertNotNull($hit);
		$this->assertNotNull($story);
		$this->assertNull(choosology_catalog_resolve_file('catalog:pics/../../secret.jpg', $pics, $about));
		$this->assertNull(choosology_catalog_resolve_file('catalog:elsewhere/a.jpg', $pics, $about));
		@unlink($pics . DIRECTORY_SEPARATOR . 'tz' . DIRECTORY_SEPARATOR . 'lantern.jpg');
		@unlink($about . DIRECTORY_SEPARATOR . 'story-bg.jpg');
		@rmdir($pics . DIRECTORY_SEPARATOR . 'tz');
		@rmdir($pics);
		@rmdir($about);
		@rmdir($root);
	}

	public function testAccountPicTags(): void
	{
		$this->assertSame(array('a', 'b'), choosology_account_pic_tags('a, b'));
	}
}
