<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EndingLogicTest extends TestCase
{
	public function testChoiceDelimiter(): void
	{
		$this->assertSame('|Q-D-|', choosology_choice_delimiter());
	}

	public function testEmptyChoicesAreEnding(): void
	{
		$screen = array();
		$this->assertTrue(choosology_screen_is_ending($screen, '100'));
	}

	public function testOutgoingChoiceIsNotEnding(): void
	{
		$d = choosology_choice_delimiter();
		$screen = array('choice1' => 'Go on' . $d . '200');
		$this->assertFalse(choosology_screen_is_ending($screen, '100'));
	}

	public function testLoopToBeginIsEnding(): void
	{
		$d = choosology_choice_delimiter();
		$screen = array('choice1' => 'Restart' . $d . '100');
		$this->assertTrue(choosology_screen_is_ending($screen, '100'));
	}

	public function testIncompleteChoiceIgnored(): void
	{
		$d = choosology_choice_delimiter();
		$screen = array(
			'choice1' => 'No target' . $d,
			'choice2' => $d . '200',
		);
		$this->assertTrue(choosology_screen_is_ending($screen, '1'));
	}
}
