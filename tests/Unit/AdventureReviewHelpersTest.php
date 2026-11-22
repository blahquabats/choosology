<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdventureReviewHelpersTest extends TestCase
{
	public function testNormalizeFlagsDedupesAndStripsJunk(): void
	{
		$this->assertSame(
			array('mature', 'sensitive', 'badflag'),
			choosology_adventure_review_normalize_flags(' Mature, sensitive, MATURE, BadFlag! ')
		);
		$this->assertSame(
			'mature,force_hold',
			choosology_adventure_review_flags_csv(array('mature', 'force_hold', 'mature'))
		);
	}

	public function testHoldFlagsDetectConfiguredKeys(): void
	{
		$this->assertTrue(choosology_adventure_review_flags_force_hold('mature'));
		$this->assertTrue(choosology_adventure_review_flags_force_hold(array('sensitive')));
		$this->assertFalse(choosology_adventure_review_flags_force_hold('comedy'));
		$this->assertFalse(choosology_adventure_review_flags_force_hold(''));
	}

	public function testDiscoverySqlExcludesHeldAndOptionallyIncludesRestricted(): void
	{
		$_SESSION = array();
		$sql = choosology_adventure_discovery_sql('a', false);
		$this->assertStringContainsString("a.avail='public'", $sql);
		$this->assertStringContainsString('review_hold', $sql);
		$this->assertStringNotContainsString("avail='restricted'", $sql);

		$sqlRestricted = choosology_adventure_discovery_sql('a', true);
		$this->assertStringContainsString("a.avail='restricted'", $sqlRestricted);
	}

	public function testCanViewHonorsOwnerHoldAndRestrictedPref(): void
	{
		$adv = array(
			'user' => 'author',
			'avail' => 'public',
			'review_hold' => 1,
		);
		$_SESSION = array();
		$this->assertFalse(choosology_adventure_can_view($adv, 'stranger'));
		$this->assertTrue(choosology_adventure_can_view($adv, 'author'));

		$adv['review_hold'] = 0;
		$this->assertTrue(choosology_adventure_can_view($adv, 'stranger'));

		$adv['avail'] = 'restricted';
		$_SESSION['view_restricted'] = 0;
		$this->assertFalse(choosology_adventure_can_view($adv, 'stranger'));
		$_SESSION['view_restricted'] = 1;
		$this->assertTrue(choosology_adventure_can_view($adv, 'stranger'));
	}
}
