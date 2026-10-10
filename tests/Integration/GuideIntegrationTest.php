<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GuideIntegrationTest extends TestCase
{
	protected function setUp(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test DB unavailable');
		}
		$db = ChoosologyTestDb::mysqli();
		choosology_guide_ensure_schema($db);
		ChoosologyTestDb::resetFixtures();
		$_SESSION['user'] = 'testuser';
		unset($_COOKIE[CHOOSOLOGY_GUIDE_COOKIE]);
	}

	public function testFirstGraphEditorOfferThenRepeatIsSilent(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$offer = choosology_guide_signal($db, 'graph_editor', 'classic');
		$this->assertIsArray($offer);
		$this->assertSame('lab_guide', $offer['mascot']['key']);
		$this->assertStringContainsString('graph editor', $offer['beat']['text']);
		$this->assertSame('#c4a35a', $offer['mascot']['theme']['accent']);
		$this->assertNotSame('', $offer['mascot']['portrait_url']);

		$advanced = choosology_guide_respond($db, 'first_graph_editor', 'accept');
		$this->assertTrue($advanced['ok']);
		$this->assertIsArray($advanced['offer']);
		$this->assertStringContainsString('Each box is a screen', $advanced['offer']['beat']['text']);

		$done = choosology_guide_respond($db, 'first_graph_editor', 'done');
		$this->assertTrue($done['ok']);
		$this->assertNull($done['offer']);

		$this->assertNull(choosology_guide_signal($db, 'graph_editor', 'classic'));
	}

	public function testSnoozeHidesUntilItExpires(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$this->assertIsArray(choosology_guide_signal($db, 'graph_editor', 'classic'));
		$snoozed = choosology_guide_respond($db, 'first_graph_editor', 'later');
		$this->assertTrue($snoozed['ok']);
		$this->assertNull($snoozed['offer']);
		$this->assertNull(choosology_guide_signal($db, 'graph_editor', 'classic'));

		$subject = mysqli_real_escape_string($db, choosology_guide_subject_key());
		mysqli_query(
			$db,
			"UPDATE guide_progress SET snooze_until = '2000-01-01 00:00:00' WHERE subject_key = '$subject'"
		);
		$again = choosology_guide_signal($db, 'graph_editor', 'classic');
		$this->assertIsArray($again);
		$this->assertStringContainsString('graph editor', $again['beat']['text']);
	}

	public function testOtherFeaturesDoNotBurnFirstSeenOrShowTheGraphTour(): void
	{
		$db = ChoosologyTestDb::mysqli();
		$this->assertNull(choosology_guide_signal($db, 'ledger', 'classic'));
		$this->assertNull(choosology_guide_signal($db, 'clipboard', 'classic'));
		$this->assertNull(choosology_guide_signal($db, 'view', 'lite'));
		$this->assertNull(choosology_guide_signal($db, 'graph_editor', 'lite'));

		$subject = mysqli_real_escape_string($db, choosology_guide_subject_key());
		$r = mysqli_query($db, "SELECT COUNT(*) AS n FROM guide_seen WHERE subject_key = '$subject'");
		$row = $r ? mysqli_fetch_assoc($r) : null;
		$this->assertSame('0', (string) ($row['n'] ?? '0'));

		$this->assertIsArray(choosology_guide_signal($db, 'graph_editor', 'classic'));
	}

	public function testOptOutSuppressesTheOffer(): void
	{
		$db = ChoosologyTestDb::mysqli();
		choosology_guide_set_enabled($db, false);
		$this->assertNull(choosology_guide_signal($db, 'graph_editor', 'classic'));
		$subject = mysqli_real_escape_string($db, choosology_guide_subject_key());
		$r = mysqli_query($db, "SELECT COUNT(*) AS n FROM guide_seen WHERE subject_key = '$subject'");
		$row = $r ? mysqli_fetch_assoc($r) : null;
		$this->assertSame('0', (string) ($row['n'] ?? '0'));

		choosology_guide_set_enabled($db, true);
		$this->assertIsArray(choosology_guide_signal($db, 'graph_editor', 'classic'));
	}

	public function testDismissedChoiceCannotBeReplayed(): void
	{
		$db = ChoosologyTestDb::mysqli();
		choosology_guide_signal($db, 'graph_editor', 'classic');
		$dismissed = choosology_guide_respond($db, 'first_graph_editor', 'decline');
		$this->assertTrue($dismissed['ok']);
		$this->assertNull($dismissed['offer']);
		$again = choosology_guide_respond($db, 'first_graph_editor', 'accept');
		$this->assertTrue($again['ok']);
		$this->assertNull($again['offer']);
		$this->assertNull(choosology_guide_signal($db, 'graph_editor', 'classic'));
	}
}
