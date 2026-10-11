<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GuideHelpersTest extends TestCase
{
	protected function setUp(): void
	{
		$_COOKIE = array();
		unset($_SESSION['user']);
	}

	public function testRegistriesCoverPhase1Signals(): void
	{
		$events = choosology_guide_event_registry();
		$this->assertArrayHasKey('feature_first_seen', $events);
		$features = choosology_guide_feature_registry();
		foreach (array('graph_editor', 'ledger', 'clipboard', 'view') as $key) {
			$this->assertArrayHasKey($key, $features);
			$this->assertNotSame('', $features[$key]['label']);
		}
	}

	public function testInterpolateReplacesKnownTokensAndStripsTags(): void
	{
		$text = choosology_guide_interpolate(
			'Hello {user}, this is {feature}. <b>{body}</b>',
			array('user' => 'Ada', 'feature' => 'the graph editor', 'body' => 'Screens are nodes.')
		);
		$this->assertSame('Hello Ada, this is the graph editor. Screens are nodes.', $text);
	}

	public function testThemeRejectsUnsafeColors(): void
	{
		$tokens = choosology_guide_theme_tokens(array(
			'accent' => 'red; background:url(http://evil)',
			'paper' => '#ABCDEF',
			'ink' => '#123',
		));
		$this->assertSame('#c4a35a', $tokens['accent']);
		$this->assertSame('#abcdef', $tokens['paper']);
		$this->assertSame('#123', $tokens['ink']);
	}

	public function testSelectOffersOnFirstSeen(): void
	{
		$picked = choosology_guide_select_interaction(
			array($this->graphInteraction()),
			$this->ctx(array('already_seen' => false))
		);
		$this->assertNotNull($picked);
		$this->assertSame('first_graph_editor', $picked['key']);
	}

	public function testSelectSkipsRepeatWhenAlreadySeenWithoutProgress(): void
	{
		$picked = choosology_guide_select_interaction(
			array($this->graphInteraction()),
			$this->ctx(array('already_seen' => true))
		);
		$this->assertNull($picked);
	}

	public function testSelectResumesOpenProgressAfterSeen(): void
	{
		$picked = choosology_guide_select_interaction(
			array($this->graphInteraction()),
			$this->ctx(array(
				'already_seen' => true,
				'progress' => array(
					'first_graph_editor' => array('status' => 'in_progress', 'beat_id' => 'explain'),
				),
			))
		);
		$this->assertNotNull($picked);
		$this->assertSame('first_graph_editor', $picked['key']);
	}

	public function testSelectSkipsActiveSnoozeAndReturnsAfterItExpires(): void
	{
		$now = strtotime('2026-10-10 12:00:00');
		$active = choosology_guide_select_interaction(
			array($this->graphInteraction()),
			$this->ctx(array(
				'now' => $now,
				'already_seen' => true,
				'progress' => array(
					'first_graph_editor' => array(
						'status' => 'snoozed',
						'beat_id' => 'offer',
						'snooze_until' => '2026-10-11 12:00:00',
					),
				),
			))
		);
		$this->assertNull($active);

		$expired = choosology_guide_select_interaction(
			array($this->graphInteraction()),
			$this->ctx(array(
				'now' => $now,
				'already_seen' => true,
				'progress' => array(
					'first_graph_editor' => array(
						'status' => 'snoozed',
						'beat_id' => 'offer',
						'snooze_until' => '2026-10-09 12:00:00',
					),
				),
			))
		);
		$this->assertNotNull($expired);
	}

	public function testSelectFiltersSurfaceAudienceAndPriority(): void
	{
		$liteOnly = $this->graphInteraction();
		$liteOnly['key'] = 'lite_only';
		$liteOnly['surface'] = 'lite';
		$liteOnly['priority'] = 1;

		$loggedIn = $this->graphInteraction();
		$loggedIn['key'] = 'members';
		$loggedIn['audience'] = 'logged_in';
		$loggedIn['priority'] = 50;

		$general = $this->graphInteraction();
		$general['key'] = 'general';
		$general['priority'] = 80;

		$surface = choosology_guide_select_interaction(
			array($liteOnly),
			$this->ctx(array('surface' => 'classic', 'already_seen' => false))
		);
		$this->assertNull($surface);

		$anon = choosology_guide_select_interaction(
			array($loggedIn),
			$this->ctx(array('audience' => 'anonymous', 'already_seen' => false))
		);
		$this->assertNull($anon);

		$picked = choosology_guide_select_interaction(
			array($general, $loggedIn),
			$this->ctx(array('audience' => 'logged_in', 'already_seen' => false))
		);
		$this->assertNotNull($picked);
		$this->assertSame('members', $picked['key']);
	}

	public function testMascotSwapChangesLineAndTheme(): void
	{
		$interaction = $this->graphInteraction();
		$ada = $this->mascot('ada', 'Ada here. First time in {feature}?');
		$ada['theme'] = array('accent' => '#112233', 'paper' => '#abcdef', 'ink' => '#010101');
		$bo = $this->mascot('bo', 'Bo speaking about {feature}.');
		$bo['theme'] = array('accent' => '#445566', 'paper' => '#ffffff', 'ink' => '#000000');

		$offerA = choosology_guide_present($interaction, $ada, null, array(
			'feature' => 'the graph editor',
			'feature_key' => 'graph_editor',
			'user' => 'testuser',
		));
		$offerB = choosology_guide_present($interaction, $bo, null, array(
			'feature' => 'the graph editor',
			'feature_key' => 'graph_editor',
			'user' => 'testuser',
		));
		$this->assertNotNull($offerA);
		$this->assertNotNull($offerB);
		$this->assertSame('Ada here. First time in the graph editor?', $offerA['beat']['text']);
		$this->assertSame('Bo speaking about the graph editor.', $offerB['beat']['text']);
		$this->assertSame('#112233', $offerA['mascot']['theme']['accent']);
		$this->assertSame('#445566', $offerB['mascot']['theme']['accent']);
		$this->assertSame('Show me, Ada', $offerA['beat']['choices'][0]['label']);
		$this->assertSame('Show me, Bo', $offerB['beat']['choices'][0]['label']);
	}

	public function testMissingLineFallsBackToBodyThenNeutral(): void
	{
		$mascot = $this->mascot('quiet', '');
		$mascot['lines'] = array();
		$body = choosology_guide_resolve_line($mascot, 'explain', 'Fallback copy', array(
			'feature' => 'the Ledger',
		));
		$this->assertSame('Fallback copy', $body);

		$neutral = choosology_guide_resolve_line($mascot, 'offer_help', '', array(
			'feature' => 'the Ledger',
		));
		$this->assertSame('Want a hand with the Ledger?', $neutral);
	}

	public function testApplyChoiceAdvancesSnoozesAndDismisses(): void
	{
		$interaction = $this->graphInteraction();
		$now = strtotime('2026-10-10 12:00:00');
		$progress = array('status' => 'offered', 'beat_id' => 'offer');

		$next = choosology_guide_apply_choice($interaction, $progress, 'accept', $now);
		$this->assertTrue($next['ok']);
		$this->assertSame('in_progress', $next['status']);
		$this->assertSame('explain', $next['beat_id']);
		$this->assertFalse($next['finished']);

		$done = choosology_guide_apply_choice($interaction, array(
			'status' => 'in_progress',
			'beat_id' => 'explain',
		), 'done', $now);
		$this->assertSame('completed', $done['status']);
		$this->assertTrue($done['finished']);

		$snooze = choosology_guide_apply_choice($interaction, $progress, 'later', $now);
		$this->assertSame('snoozed', $snooze['status']);
		$this->assertSame('2026-10-11 12:00:00', $snooze['snooze_until']);

		$dismiss = choosology_guide_apply_choice($interaction, $progress, 'decline', $now);
		$this->assertSame('dismissed', $dismiss['status']);
		$this->assertTrue($dismiss['finished']);

		$bad = choosology_guide_apply_choice($interaction, $progress, 'done', $now);
		$this->assertFalse($bad['ok']);
	}

	public function testRenderPanelEscapesTextAndUsesTheme(): void
	{
		$offer = array(
			'interaction_key' => 'first_graph_editor',
			'feature' => 'graph_editor',
			'mascot' => array(
				'name' => 'Lab Guide',
				'portrait_url' => '/images/mascots/lab_guide/offer.png',
				'theme' => array('accent' => '#112233', 'paper' => '#abcdef', 'ink' => '#010101'),
			),
			'beat' => array(
				'id' => 'offer',
				'text' => '<script>alert(1)</script>',
				'choices' => array(
					array('id' => 'accept', 'label' => 'Show me'),
				),
			),
		);
		$html = choosology_guide_render_panel($offer, 'classic');
		$this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
		$this->assertStringContainsString('--guide-accent:#112233', $html);
		$this->assertStringContainsString('data-guide-choice="accept"', $html);
		$this->assertStringNotContainsString('<script>alert', $html);

		$lite = choosology_guide_render_panel($offer, 'lite', array(
			'action' => '/lite/guide.php',
			'next' => 'view.php?id=4',
		));
		$this->assertStringContainsString('<form method="post"', $lite);
		$this->assertStringContainsString('name="choice" value="accept"', $lite);
		$this->assertStringContainsString('value="view.php?id=4"', $lite);
	}

	public function testSafeReturnRejectsOffsitePaths(): void
	{
		$this->assertSame('view.php?id=4&screen=2', choosology_guide_safe_return('view.php?id=4&screen=2'));
		$this->assertSame('index.php', choosology_guide_safe_return('https://evil.example/'));
		$this->assertSame('index.php', choosology_guide_safe_return('../connect.php'));
		$this->assertSame('ledger.php', choosology_guide_safe_return('lite/ledger.php'));
	}

	public function testGuideFlagAndCookie(): void
	{
		$this->assertTrue(choosology_guide_flag_on('on'));
		$this->assertTrue(choosology_guide_flag_on(1));
		$this->assertFalse(choosology_guide_flag_on('off'));
		$this->assertFalse(choosology_guide_flag_on('0'));
		$this->assertTrue(choosology_guide_cookie_enabled());
		choosology_guide_cookie_set(false);
		$this->assertFalse(choosology_guide_cookie_enabled());
		$this->assertFalse(choosology_guide_enabled(null));
	}

	/**
	 * @param array<string, mixed> $overrides
	 * @return array<string, mixed>
	 */
	private function ctx(array $overrides): array
	{
		return array_merge(array(
			'event' => 'feature_first_seen',
			'feature' => 'graph_editor',
			'surface' => 'classic',
			'audience' => 'logged_in',
			'now' => strtotime('2026-10-10 12:00:00'),
			'already_seen' => false,
			'progress' => array(),
		), $overrides);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function graphInteraction(): array
	{
		$rows = choosology_guide_interaction_seed();
		$row = $rows[0];
		$row['enabled'] = true;
		return $row;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mascot(string $key, string $offerLine): array
	{
		return array(
			'key' => $key,
			'display_name' => ucfirst($key),
			'enabled' => true,
			'is_default' => false,
			'theme' => array('accent' => '#c4a35a', 'paper' => '#f4ecd8', 'ink' => '#1c1a16'),
			'lines' => array(
				'offer_help' => array($offerLine),
				'explain' => array('{body}'),
			),
			'choices' => array(
				'accept' => 'Show me, ' . ucfirst($key),
				'decline' => 'No',
				'later' => 'Later',
				'done' => 'Got it',
			),
			'portraits' => array(
				'offer' => 'images/mascots/lab_guide/offer.png',
				'explain' => 'images/mascots/lab_guide/explain.png',
			),
		);
	}
}
