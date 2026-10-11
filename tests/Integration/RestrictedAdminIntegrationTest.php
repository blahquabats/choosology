<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RestrictedAdminIntegrationTest extends TestCase
{
	private mysqli $db;

	protected function setUp(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test DB unavailable');
		}
		$this->db = ChoosologyTestDb::mysqli();
		choosology_guide_ensure_schema($this->db);
		choosology_clipboard_ensure_schema($this->db);
		choosology_datascrip_ensure_schema($this->db);
		ChoosologyTestDb::resetFixtures();
		$_SESSION = array('user' => 'adminuser', 'usertype' => 1);
		@mysqli_query($this->db, "DELETE FROM guide_interactions WHERE interaction_key='admin_test_interaction'");
		@mysqli_query($this->db, "DELETE FROM guide_mascots WHERE mascot_key='admin_test_guide'");
	}

	protected function tearDown(): void
	{
		if (isset($this->db)) {
			@mysqli_query($this->db, "DELETE FROM guide_interactions WHERE interaction_key='admin_test_interaction'");
			@mysqli_query($this->db, "DELETE FROM guide_mascots WHERE mascot_key='admin_test_guide'");
		}
	}

	public function testAdminCanCreatePreviewAndDeleteGuideContent(): void
	{
		$mascot = $this->mascot();
		$savedMascot = choosology_guide_admin_save_mascot($this->db, $mascot);
		$this->assertTrue($savedMascot['ok']);

		$interaction = $this->interaction();
		$savedInteraction = choosology_guide_admin_save_interaction($this->db, $interaction);
		$this->assertTrue($savedInteraction['ok']);

		$before = $this->countRows('guide_progress') + $this->countRows('guide_seen');
		$preview = choosology_guide_admin_preview($this->db, $interaction);
		$this->assertTrue($preview['ok']);
		$this->assertStringContainsString('Admin preview for the graph editor.', $preview['offer']['beat']['text']);
		$this->assertStringContainsString('guide-panel', $preview['html']);
		$this->assertSame($before, $this->countRows('guide_progress') + $this->countRows('guide_seen'));

		$blocked = choosology_guide_admin_delete_mascot($this->db, 'admin_test_guide');
		$this->assertFalse($blocked['ok']);
		$this->assertStringContainsString('used by an interaction', $blocked['error']);

		$this->assertTrue(choosology_guide_admin_delete_interaction($this->db, 'admin_test_interaction')['ok']);
		$this->assertTrue(choosology_guide_admin_delete_mascot($this->db, 'admin_test_guide')['ok']);
	}

	public function testGrantDegreeUsesNormalRewardsAndRejectsDuplicate(): void
	{
		$result = choosology_restricted_grant_degree(
			$this->db,
			'adminuser',
			'testuser',
			'lab_initiate'
		);
		$this->assertTrue($result['ok']);
		$this->assertSame(25, choosology_datascrip_balance($this->db, 'testuser'));

		$esc = mysqli_real_escape_string($this->db, 'testuser');
		$r = mysqli_query(
			$this->db,
			"SELECT COUNT(*) AS c FROM user_achievements
			 WHERE uname='$esc' AND achievement_key='lab_initiate'"
		);
		$row = $r ? mysqli_fetch_assoc($r) : array();
		$this->assertSame('1', (string) ($row['c'] ?? '0'));

		$duplicate = choosology_restricted_grant_degree(
			$this->db,
			'adminuser',
			'testuser',
			'lab_initiate'
		);
		$this->assertFalse($duplicate['ok']);
		$this->assertTrue($duplicate['already']);
		$this->assertSame(25, choosology_datascrip_balance($this->db, 'testuser'));
	}

	public function testCommentQueueCanApproveAndDelete(): void
	{
		mysqli_query(
			$this->db,
			"INSERT INTO comments (author, date, text, whichboard, reviewed, whichscreen)
			 VALUES
			 ('testuser', NOW(), 'Approve this', 'adv1', 0, 0),
			 ('testuser', NOW(), 'Delete this', 'adv1', 0, 0)"
		);
		$comments = choosology_restricted_pending_comments($this->db);
		$this->assertCount(2, $comments);

		$approved = choosology_restricted_moderate_comment(
			$this->db,
			'approve',
			(int) $comments[0]['id']
		);
		$this->assertTrue($approved['ok']);
		$deleted = choosology_restricted_moderate_comment(
			$this->db,
			'delete',
			(int) $comments[1]['id']
		);
		$this->assertTrue($deleted['ok']);
		$this->assertCount(0, choosology_restricted_pending_comments($this->db));
	}

	private function countRows(string $table): int
	{
		$r = mysqli_query($this->db, "SELECT COUNT(*) AS c FROM `$table`");
		$row = $r ? mysqli_fetch_assoc($r) : array();
		return (int) ($row['c'] ?? 0);
	}

	/** @return array<string,mixed> */
	private function mascot(): array
	{
		return array(
			'key' => 'admin_test_guide',
			'display_name' => 'Admin Test Guide',
			'enabled' => true,
			'is_default' => false,
			'sort_order' => 50,
			'theme' => array('accent' => '#123456', 'paper' => '#ffffff', 'ink' => '#111111'),
			'portraits' => array(
				'idle' => 'images/mascots/admin_test/idle.png',
				'offer' => 'images/mascots/admin_test/offer.png',
				'explain' => 'images/mascots/admin_test/explain.png',
				'done' => 'images/mascots/admin_test/done.png',
			),
			'lines' => array(
				'offer_help' => array('Admin preview for {feature}.'),
				'explain' => array('{body}'),
				'done' => array('Done.'),
			),
			'choices' => array(
				'accept' => 'Open',
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
			'key' => 'admin_test_interaction',
			'title' => 'Admin test interaction',
			'event_key' => 'feature_first_seen',
			'feature_key' => 'graph_editor',
			'surface' => 'classic',
			'audience' => 'both',
			'mascot_key' => 'admin_test_guide',
			'priority' => 10,
			'enabled' => true,
			'beats' => array(
				array(
					'id' => 'offer',
					'role' => 'offer_help',
					'pose' => 'offer',
					'body' => '',
					'choices' => array('accept', 'decline'),
				),
			),
		);
	}
}
