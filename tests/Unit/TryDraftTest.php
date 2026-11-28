<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CHOOSOLOGY_ROOT . '/lib/try-draft.php';
require_once CHOOSOLOGY_ROOT . '/lib/about-content.php';
require_once CHOOSOLOGY_ROOT . '/lib/date-format-helpers.php';

final class TryDraftTest extends TestCase
{
	protected function tearDown(): void
	{
		unset($_SESSION['user'], $_SESSION['choosology_try_draft']);
	}

	public function testCapMatchesEightOutgoingChoices(): void
	{
		$this->assertSame(8, choosology_try_screen_cap());
		$src = file_get_contents(CHOOSOLOGY_ROOT . '/vised/vised.js');
		$this->assertIsString($src);
		$this->assertStringContainsString('eight outgoing connections', $src);
		$this->assertStringContainsString('eight children of one screen', $src);
	}

	public function testApplyAddsAScreenWithoutTouchingTheDatabase(): void
	{
		$lib = file_get_contents(CHOOSOLOGY_ROOT . '/lib/try-draft.php');
		$ajax = file_get_contents(CHOOSOLOGY_ROOT . '/ajax/trydraft.php');
		$this->assertIsString($lib);
		$this->assertIsString($ajax);
		$this->assertStringNotContainsString('INSERT', $lib);
		$this->assertStringNotContainsString('INSERT', $ajax);
		$this->assertStringNotContainsString('advscreens', $lib);

		$_SESSION['user'] = '';
		$draft = choosology_try_draft_blank();
		$result = choosology_try_apply_boxes($draft, array(
			'box_1' => array(
				'title' => 'Start',
				'x' => 10,
				'y' => 20,
				'connections' => array(1 => 'Second beat|Q-D-|_box_1'),
			),
			'new0_box_1' => array(
				'title' => 'Second beat',
				'x' => 200,
				'y' => 20,
				'connections' => array(),
			),
		));
		$this->assertSame(1, $result['ok']);
		$this->assertSame(0, $result['account']);
		$this->assertSame(2, choosology_try_active_count($result['draft']));
		$this->assertSame('Second beat|Q-D-|2', $result['draft']['screens']['1']['choice1']);
		$this->assertArrayNotHasKey('advs', $result);
	}

	public function testNinthScreenAsksForAnAccountAndLeavesTheDraft(): void
	{
		$draft = choosology_try_draft_blank();
		for ($n = 2; $n <= 8; $n++) {
			$draft['screens'][(string) $n] = choosology_try_blank_screen((string) $n, 'Screen ' . $n, 10, $n * 40);
		}
		$draft['next_id'] = 9;
		$before = $draft;
		$result = choosology_try_apply_boxes($draft, array(
			'new0_box_1' => array('title' => 'Too many', 'x' => 1, 'y' => 1, 'connections' => array()),
		));
		$this->assertSame(0, $result['ok']);
		$this->assertSame(1, $result['account']);
		$this->assertStringContainsString('Apply for lab access', $result['error']);
		$this->assertSame(8, choosology_try_active_count($result['draft']));
		$this->assertSame($before['screens'], $result['draft']['screens']);
	}

	public function testLoggedInVisitorCannotStoreADraft(): void
	{
		$_SESSION['user'] = 'someone';
		$_SESSION['choosology_try_draft'] = null;
		unset($_SESSION['choosology_try_draft']);
		$draft = choosology_try_draft_get(true);
		$this->assertArrayNotHasKey('choosology_try_draft', $_SESSION);
		choosology_try_draft_store($draft);
		$this->assertArrayNotHasKey('choosology_try_draft', $_SESSION);
	}

	public function testPreviewPayloadFollowsTheLinkedScreen(): void
	{
		$draft = choosology_try_draft_blank();
		$applied = choosology_try_apply_boxes($draft, array(
			'box_1' => array(
				'title' => 'Start',
				'x' => 10,
				'y' => 20,
				'connections' => array(1 => 'Second beat|Q-D-|_box_1'),
			),
			'new0_box_1' => array(
				'title' => 'Second beat',
				'x' => 200,
				'y' => 20,
				'connections' => array(),
			),
		));
		$applied['draft'] = choosology_try_save_screen($applied['draft'], '2', 'Second beat', 'You reached the next screen.')['draft'];
		$start = choosology_try_screen_payload($applied['draft'], '1');
		$next = choosology_try_screen_payload($applied['draft'], '2');
		$this->assertStringContainsString("goToScreen('2'", $start['choices']);
		$withMarkup = $applied['draft'];
		$withMarkup['screens']['1']['choice1'] = '<p>okay then</p>|Q-D-|2';
		$marked = choosology_try_screen_payload($withMarkup, '1');
		$this->assertStringContainsString('<p>okay then</p>', $marked['choices']);
		$this->assertStringNotContainsString('&lt;p&gt;', $marked['choices']);
		$fresh = choosology_try_blank_screen('9', 'New', 1, 1);
		$this->assertSame('', $fresh['edited']);
		$this->assertSame('Not saved yet', choosology_last_saved_label($fresh['edited']));
		$saved = choosology_try_save_screen($applied['draft'], '2', 'Second beat', 'You reached the next screen.');
		$this->assertNotSame('', $saved['draft']['screens']['2']['edited']);
		$this->assertStringNotContainsString('1970', $saved['draft']['screens']['2']['edited']);
		$this->assertStringStartsWith('Last Saved:', choosology_last_saved_label($saved['draft']['screens']['2']['edited']));
		$end = choosology_try_ending_html('classic');
		$this->assertStringContainsString('END NODE', $end);
		$this->assertStringContainsString('This path ends here.', $end);
		$this->assertStringContainsString('<a class="login-signup-link"', $end);
		$this->assertStringContainsString('opensignup', $end);
		$this->assertStringNotContainsString('<button', $end);
		$this->assertStringContainsString('Other users may rate or comment', $end);
		$this->assertStringNotContainsString('commentsexist', $end);
		$this->assertStringNotContainsString('ending-panel-rate', $end);
		$liteEnd = choosology_try_ending_html('lite');
		$this->assertStringContainsString('lite-ending-pill', $liteEnd);
		$this->assertStringContainsString('<a class="login-signup-link"', $liteEnd);
		$this->assertStringNotContainsString('<button', $liteEnd);
		$this->assertStringContainsString('This path ends here.', $liteEnd);
		$this->assertStringNotContainsString('commentsexist', $liteEnd);
		$this->assertStringContainsString('You reached the next screen.', $next['text']);
		$this->assertStringNotContainsString('first screen of your draft', $next['text']);
		$ajax = file_get_contents(CHOOSOLOGY_ROOT . '/ajax/screenajax.php');
		$this->assertIsString($ajax);
		$this->assertStringNotContainsString('application/json', $ajax);
		$this->assertStringContainsString('choosology_try_screen_payload', $ajax);
	}

	public function testAboutFaqListsTheQuestions(): void
	{
		$this->assertSame('FAQ (Frequently Assumed Questions)', choosology_about_faq_heading());
		$faq = choosology_about_faq();
		$this->assertSame(array(
			'After building the experiment, make it available privately or publicly.',
			'You can choose to be notified when readers interact with your experiment and view usage data and feedback.',
			'Invite other users to collaborate on adventures and broaden your offerings.',
		), choosology_about_running());
		$this->assertSame('What is Choosology?', $faq[0]['q']);
		$this->assertStringContainsString('Choose Your Own Adventure', $faq[0]['a']);
		$this->assertStringContainsString('Choosology is not affiliated with ChooseCo.', $faq[0]['a']);
		$this->assertStringContainsString('href="https://www.cyoa.com/"', choosology_about_faq_answer_html($faq[0]['a']));
		$this->assertSame('Nothing. Choosology is now and will always be completely free.', $faq[1]['a']);
		$this->assertSame('I don\'t. There are no ads, no selling data, no industry collabs, no premium subscriptions.', $faq[2]['a']);
		$this->assertSame('Can I write adult material?', $faq[3]['q']);
		$this->assertStringContainsString('content warnings are required', $faq[3]['a']);
		$this->assertCount(6, $faq);
		$this->assertSame('What is your policy on generative AI?', $faq[4]['q']);
		$this->assertStringContainsString('human-generated--this', $faq[4]['a']);
		$this->assertSame('What is your policy on using the intellectual property (IP) of others?', $faq[5]['q']);
		$this->assertStringContainsString('See our Terms and Conditions for more details.', $faq[5]['a']);
		$this->assertStringContainsString('href="#/terms"', choosology_about_faq_answer_html($faq[5]['a']));
		$questions = array_column($faq, 'q');
		$this->assertNotContains('What happens to a Try it yourself draft if I leave?', $questions);
		$this->assertNotContains('How much can I build before I need an account?', $questions);
		$this->assertNotContains('Do readers see the graph?', $questions);
		$classic = file_get_contents(CHOOSOLOGY_ROOT . '/about.php');
		$lite = file_get_contents(CHOOSOLOGY_ROOT . '/lite/about.php');
		$this->assertIsString($classic);
		$this->assertIsString($lite);
		$this->assertStringContainsString('choosology_about_faq()', $classic);
		$this->assertStringContainsString('choosology_about_faq()', $lite);
		$this->assertStringContainsString('news-card-title', $classic);
		$this->assertStringContainsString('news-card-excerpt', $lite);
		$this->assertStringNotContainsString('Questions are coming.', $classic);
		$this->assertStringNotContainsString('Questions are coming.', $lite);
		$this->assertSame(array(
			'Browsing users can find recent or popular experiments to enjoy.',
			'Readers can rate and comment on experiments.',
			'Activity unlocks Degrees and builds up DataScrip, which lets users customize their Office.',
		), choosology_about_experience());
	}
}
