<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GuideAdminHelpersTest extends TestCase
{
	public function testValidMascotIsNormalized(): void
	{
		$result = choosology_guide_admin_validate_mascot($this->mascot());
		$this->assertTrue($result['ok']);
		$this->assertSame('test_guide', $result['value']['key']);
		$this->assertSame('#abcdef', $result['value']['theme']['accent']);
		$this->assertSame(
			'Looks like {feature}.',
			$result['value']['lines']['offer_help'][0]
		);
	}

	public function testMascotRejectsCssAndExternalPortraits(): void
	{
		$mascot = $this->mascot();
		$mascot['theme']['accent'] = 'red;background:url(evil)';
		$mascot['portraits']['idle'] = 'https://evil.example/a.png';
		$result = choosology_guide_admin_validate_mascot($mascot);
		$this->assertFalse($result['ok']);
		$this->assertStringContainsString('hex color', implode(' ', $result['errors']));
		$this->assertStringContainsString('images/mascots', implode(' ', $result['errors']));
	}

	public function testMascotDialogIsPlainText(): void
	{
		$mascot = $this->mascot();
		$mascot['lines']['offer_help'] = array('<b>Hello</b> {feature}.');
		$result = choosology_guide_admin_validate_mascot($mascot);
		$this->assertTrue($result['ok']);
		$this->assertSame('Hello {feature}.', $result['value']['lines']['offer_help'][0]);
	}

	public function testValidInteractionUsesRegisteredKeys(): void
	{
		$result = choosology_guide_admin_validate_interaction(
			$this->interaction(),
			array('test_guide')
		);
		$this->assertTrue($result['ok']);
		$this->assertSame('feature_first_seen', $result['value']['event_key']);
		$this->assertSame(array('accept', 'later', 'decline'), $result['value']['beats'][0]['choices']);
	}

	public function testInteractionRejectsUnknownFeatureMascotAndChoice(): void
	{
		$interaction = $this->interaction();
		$interaction['feature_key'] = 'made_up';
		$interaction['mascot_key'] = 'missing';
		$interaction['beats'][0]['choices'][] = 'teleport';
		$result = choosology_guide_admin_validate_interaction($interaction, array('test_guide'));
		$this->assertFalse($result['ok']);
		$errors = implode(' ', $result['errors']);
		$this->assertStringContainsString('registered feature', $errors);
		$this->assertStringContainsString('existing mascot', $errors);
		$this->assertStringContainsString('Unknown beat choice', $errors);
	}

	public function testInteractionRejectsDuplicateBeatIds(): void
	{
		$interaction = $this->interaction();
		$interaction['beats'][] = array(
			'id' => 'offer',
			'role' => 'done',
			'pose' => 'done',
			'body' => 'Finished.',
			'choices' => array('done'),
		);
		$result = choosology_guide_admin_validate_interaction($interaction, array('test_guide'));
		$this->assertFalse($result['ok']);
		$this->assertStringContainsString('unique lowercase keys', implode(' ', $result['errors']));
	}

	public function testAdminSessionGate(): void
	{
		$_SESSION = array('user' => 'admin', 'usertype' => 1);
		$this->assertTrue(choosology_restricted_is_admin());
		$_SESSION['usertype'] = 0;
		$this->assertFalse(choosology_restricted_is_admin());
		unset($_SESSION['user']);
		$this->assertFalse(choosology_restricted_is_admin());
	}

	/** @return array<string,mixed> */
	private function mascot(): array
	{
		return array(
			'key' => 'test_guide',
			'display_name' => 'Test Guide',
			'enabled' => true,
			'is_default' => false,
			'sort_order' => 5,
			'theme' => array('accent' => '#ABCDEF', 'paper' => '#fff', 'ink' => '#123456'),
			'portraits' => array(
				'idle' => 'images/mascots/test/idle.png',
				'offer' => 'images/mascots/test/offer.png',
				'explain' => 'images/mascots/test/explain.png',
				'done' => 'images/mascots/test/done.png',
			),
			'lines' => array(
				'offer_help' => array('Looks like {feature}.'),
				'explain' => array('{body}'),
				'done' => array('Done.'),
			),
			'choices' => array(
				'accept' => 'Yes',
				'decline' => 'No',
				'later' => 'Later',
				'done' => 'Done',
				'next' => 'Next',
			),
		);
	}

	/** @return array<string,mixed> */
	private function interaction(): array
	{
		return array(
			'key' => 'test_interaction',
			'title' => 'Test interaction',
			'event_key' => 'feature_first_seen',
			'feature_key' => 'graph_editor',
			'surface' => 'classic',
			'audience' => 'both',
			'mascot_key' => 'test_guide',
			'priority' => 10,
			'enabled' => true,
			'beats' => array(
				array(
					'id' => 'offer',
					'role' => 'offer_help',
					'pose' => 'offer',
					'body' => '',
					'choices' => array('accept', 'later', 'decline'),
				),
			),
		);
	}
}
