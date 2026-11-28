<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Adventure review queue against choosology_test.
 */
final class AdventureReviewIntegrationTest extends TestCase
{
	private mysqli $db;
	private int $advId = 0;
	/** @var list<int> */
	private array $extraIds = array();

	protected function setUp(): void
	{
		$this->db = ChoosologyTestDb::mysqli();
		choosology_adventure_review_ensure_schema($this->db);
		$_SESSION = array('user' => 'adminuser', 'usertype' => 1);
		@mysqli_query($this->db, "UPDATE users SET probation=0 WHERE name='testuser'");
	}

	protected function tearDown(): void
	{
		if ($this->advId > 0) {
			choosology_adventure_review_delete($this->db, $this->advId);
			$this->advId = 0;
		}
		foreach ($this->extraIds as $id) {
			choosology_adventure_review_delete($this->db, (int) $id);
		}
		$this->extraIds = array();
		@mysqli_query($this->db, "UPDATE users SET probation=0 WHERE name='testuser'");
		$_SESSION = array();
	}

	private function makeAdventure(string $title = 'Review Fixture'): int
	{
		$titleEsc = mysqli_real_escape_string($this->db, $title);
		mysqli_query(
			$this->db,
			"INSERT INTO advs (user, created, title, status, edited, avail, pass, font, titlefont, bg, box, borderwidth, usesimplestyling, totalwordcount, titlestyle, textstyle, linkstyle)
			 VALUES ('testuser', NOW(), '$titleEsc', '0', NOW(), 'none', '', 'Trebuchet MS', 'Arial', '#ffffff', '#ccddff', 2, 1, 0, '', '', '')"
		);
		$id = (int) mysqli_insert_id($this->db);
		mysqli_query(
			$this->db,
			"INSERT INTO advscreens (
				user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, screenborderwidth,
				use_defaults, created, advused, xpos, ypos, deleted, wordcount
			) VALUES (
				'testuser', '', 'Start', 'Start here.', '#ffffff', '#ccddff', '#9999cc', 2,
				1, NOW(), '$id', 0, 0, 0, 2
			)"
		);
		$screenId = (int) mysqli_insert_id($this->db);
		mysqli_query($this->db, "UPDATE advs SET `begin`='$screenId' WHERE id=$id LIMIT 1");
		$this->advId = $id;
		return $id;
	}

	public function testPublishEnqueuesWithoutHoldByDefault(): void
	{
		$id = $this->makeAdventure();
		$result = choosology_adventure_review_on_avail_change($this->db, $id, 'testuser', 'public', 'none', '');
		$this->assertTrue($result['ok']);
		$this->assertSame('pending', $result['review_status']);
		$this->assertSame(0, (int) $result['review_hold']);

		$pending = choosology_adventure_review_pending($this->db);
		$ids = array_column($pending, 'id');
		$this->assertContains($id, $ids);
	}

	public function testProbationAndFlagsForceHold(): void
	{
		choosology_adventure_review_set_probation($this->db, 'testuser', true);
		$id = $this->makeAdventure('Held Fixture');
		$result = choosology_adventure_review_on_avail_change($this->db, $id, 'testuser', 'public', 'none', 'mature');
		$this->assertTrue($result['ok']);
		$this->assertSame(1, (int) $result['review_hold']);

		$discovery = choosology_adventure_discovery_sql('a', false);
		$r = mysqli_query(
			$this->db,
			"SELECT id FROM advs a WHERE a.id=$id AND $discovery LIMIT 1"
		);
		$this->assertSame(0, $r ? mysqli_num_rows($r) : -1);
	}

	public function testAcceptRestrictedAndRejectNotify(): void
	{
		$id = $this->makeAdventure('Decision Fixture');
		choosology_adventure_review_on_avail_change($this->db, $id, 'testuser', 'public', 'none', '');

		$accepted = choosology_adventure_review_moderate($this->db, 'adminuser', 'accept_restricted', $id);
		$this->assertTrue($accepted['ok']);
		$row = mysqli_fetch_assoc(mysqli_query($this->db, "SELECT avail, review_status, review_hold FROM advs WHERE id=$id"));
		$this->assertSame('restricted', $row['avail']);
		$this->assertSame('restricted', $row['review_status']);
		$this->assertSame('0', (string) $row['review_hold']);

		/* Re-queue a fresh adventure for reject. */
		$id2 = $this->makeAdventure('Reject Fixture');
		choosology_adventure_review_on_avail_change($this->db, $id2, 'testuser', 'public', 'none', '');
		$before = (int) (mysqli_fetch_assoc(mysqli_query(
			$this->db,
			"SELECT COUNT(*) AS c FROM messages WHERE to_user='testuser'"
		))['c'] ?? 0);
		$rejected = choosology_adventure_review_moderate(
			$this->db,
			'adminuser',
			'reject',
			$id2,
			'Please revise the opening scene.'
		);
		$this->assertTrue($rejected['ok']);
		$this->advId = $id; /* id2 already deleted */
		$gone = mysqli_fetch_assoc(mysqli_query($this->db, "SELECT id FROM advs WHERE id=$id2"));
		$this->assertNull($gone);
		$after = (int) (mysqli_fetch_assoc(mysqli_query(
			$this->db,
			"SELECT COUNT(*) AS c FROM messages WHERE to_user='testuser'"
		))['c'] ?? 0);
		$this->assertGreaterThan($before, $after);
	}

	public function testNewlyFeaturedUsesOnlyTheTenMostRecent(): void
	{
		mysqli_query($this->db, 'UPDATE advs SET featured=0, featured_at=NULL');
		$ids = array();
		$unfeatured = $this->makeAdventure('Unfeatured Public');
		choosology_adventure_review_on_avail_change($this->db, $unfeatured, 'testuser', 'public', 'none', '');
		$ids[] = $unfeatured;
		for ($i = 0; $i < 11; $i++) {
			$id = $this->makeAdventure('Featured Pool ' . $i);
			choosology_adventure_review_on_avail_change($this->db, $id, 'testuser', 'public', 'none', '');
			$marked = choosology_adventure_review_set_featured($this->db, $id, true);
			$this->assertTrue($marked['ok']);
			$stamp = sprintf('2020-01-%02d 12:00:00', $i + 1);
			mysqli_query($this->db, "UPDATE advs SET featured_at='$stamp' WHERE id=$id");
			$ids[] = $id;
		}
		$this->extraIds = $ids;
		$oldest = $ids[1];
		$newest = $ids[11];
		$rows = choosology_newly_featured_adventures($this->db, 10);
		$got = array_map('intval', array_column($rows, 'aid'));
		$this->assertCount(10, $got);
		$this->assertNotContains($unfeatured, $got);
		$this->assertNotContains($oldest, $got);
		$this->assertContains($newest, $got);

		$_SESSION['usertype'] = 0;
		$denied = choosology_adventure_review_set_featured($this->db, $unfeatured, true);
		$this->assertFalse($denied['ok']);
		$_SESSION['usertype'] = 1;

		$this->extraIds = $ids;
		$this->advId = 0;
	}
}
