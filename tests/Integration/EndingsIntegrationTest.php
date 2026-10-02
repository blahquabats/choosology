<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EndingsIntegrationTest extends TestCase
{
	private int $advid = 0;
	private int $beginId = 0;
	private int $endId = 0;

	protected function setUp(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test database unavailable');
		}
		ChoosologyTestDb::resetFixtures();
		$db = ChoosologyTestDb::mysqli();
		if (session_status() === PHP_SESSION_NONE) {
			@session_start();
		}
		$_SESSION = array();

		mysqli_query(
			$db,
			"INSERT INTO advs (user, created, title, status, edited, avail, pass, totalwordcount)
			 VALUES ('testuser', NOW(), 'Ending Fixture', '1', NOW(), 'all', '', 0)"
		);
		$this->advid = (int) mysqli_insert_id($db);
		mysqli_query(
			$db,
			"INSERT INTO advscreens
			 (user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, created, advused, wordcount)
			 VALUES ('testuser', 'Start', 'Start', 'Begin', '', '', '', NOW(), '{$this->advid}', 0)"
		);
		$this->beginId = (int) mysqli_insert_id($db);
		$delim = choosology_choice_delimiter();
		mysqli_query(
			$db,
			"INSERT INTO advscreens
			 (user, title, name, text, screenbgcolor, screenboxcolor, screenbordercolor, created, advused, wordcount, choice1)
			 VALUES ('testuser', 'End', 'End', 'The end', '', '', '', NOW(), '{$this->advid}', 0, '')"
		);
		$this->endId = (int) mysqli_insert_id($db);
		mysqli_query(
			$db,
			"UPDATE advscreens SET choice1 = 'Finish{$delim}{$this->endId}' WHERE id = {$this->beginId}"
		);
		mysqli_query($db, "UPDATE advs SET begin = '{$this->beginId}' WHERE id = {$this->advid}");
	}

	public function testAdventureEndingIds(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$ids = choosology_adventure_ending_ids($db, $this->advid);
		$this->assertSame(array($this->endId), $ids);
	}

	public function testRecordEndingFindAnonymousThenLoggedIn(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$anon = choosology_record_ending_find($db, $this->advid, $this->endId);
		$this->assertSame(1, $anon['found']);
		$this->assertFalse($anon['more']);
		$this->assertSame(1, $anon['total']);

		$_SESSION['user'] = 'testuser';
		$again = choosology_record_ending_find($db, $this->advid, $this->endId);
		$this->assertSame(1, $again['found']);

		$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM ending_finds WHERE uname='testuser' AND adv={$this->advid}");
		$row = mysqli_fetch_assoc($r);
		$this->assertSame(1, (int) $row['c']);
	}
}
