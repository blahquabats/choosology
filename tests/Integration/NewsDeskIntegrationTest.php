<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * RESTRICTED News Desk and Degree records against choosology_test.
 */
final class NewsDeskIntegrationTest extends TestCase
{
	private const MARK = 'DESKTEST';

	private mysqli $db;

	protected function setUp(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test DB unavailable');
		}
		$this->db = ChoosologyTestDb::mysqli();
		choosology_clipboard_ensure_schema($this->db);
		choosology_datascrip_ensure_schema($this->db);
		ChoosologyTestDb::resetFixtures();
		$this->purge();
	}

	protected function tearDown(): void
	{
		if (isset($this->db)) {
			$this->purge();
		}
	}

	public function testNewsPostCanBePublishedEditedAndDeleted(): void
	{
		$created = choosology_news_admin_save($this->db, array(
			'kind' => 'news',
			'headline' => self::MARK . ' Lab reopens',
			'body' => '<p>Doors open at nine.</p>',
			'by' => '',
		), 'adminuser');
		$this->assertTrue($created['ok']);
		$this->assertTrue($created['created']);
		$id = $created['id'];
		$this->assertGreaterThan(0, $id);

		$post = $this->findPost($id);
		$this->assertNotNull($post);
		$this->assertSame(self::MARK . ' Lab reopens', $post['headline']);
		$this->assertSame('<p>Doors open at nine.</p>', $post['body']);
		$this->assertSame('adminuser', $post['by']);
		$this->assertNotSame('', $post['whenposted']);

		$edited = choosology_news_admin_save($this->db, array(
			'kind' => 'news',
			'id' => $id,
			'headline' => self::MARK . ' Lab reopens early',
			'body' => '<p>Doors open at eight.</p>',
			'by' => 'Front Desk',
		), 'adminuser');
		$this->assertTrue($edited['ok']);
		$this->assertFalse($edited['created']);
		$this->assertSame($id, $edited['id']);

		$post = $this->findPost($id);
		$this->assertSame(self::MARK . ' Lab reopens early', $post['headline']);
		$this->assertSame('<p>Doors open at eight.</p>', $post['body']);
		$this->assertSame('Front Desk', $post['by']);

		$this->assertTrue(choosology_news_admin_delete($this->db, 'news', $id)['ok']);
		$this->assertNull($this->findPost($id));

		$again = choosology_news_admin_delete($this->db, 'news', $id);
		$this->assertFalse($again['ok']);
		$this->assertSame('News item not found.', $again['error']);
	}

	public function testMinorUpdateCanBePublishedEditedAndDeleted(): void
	{
		$created = choosology_news_admin_save($this->db, array(
			'kind' => 'update',
			'headline' => self::MARK . ' Fixed the centrifuge',
		), 'adminuser');
		$this->assertTrue($created['ok']);
		$id = $created['id'];
		$this->assertSame(self::MARK . ' Fixed the centrifuge', $this->findUpdate($id)['text']);

		$edited = choosology_news_admin_save($this->db, array(
			'kind' => 'update',
			'id' => $id,
			'headline' => self::MARK . ' Fixed the centrifuge twice',
		), 'adminuser');
		$this->assertTrue($edited['ok']);
		$this->assertSame(self::MARK . ' Fixed the centrifuge twice', $this->findUpdate($id)['text']);

		$this->assertTrue(choosology_news_admin_delete($this->db, 'update', $id)['ok']);
		$this->assertNull($this->findUpdate($id));
	}

	public function testRejectedSavesLeaveNothingBehind(): void
	{
		$before = count(choosology_news_admin_posts($this->db)) + count(choosology_news_admin_updates($this->db));

		$this->assertFalse(choosology_news_admin_save($this->db, array(
			'kind' => 'news',
			'headline' => self::MARK . ' No body',
			'body' => '',
		), 'adminuser')['ok']);

		$missing = choosology_news_admin_save($this->db, array(
			'kind' => 'update',
			'id' => 99999999,
			'headline' => self::MARK . ' Ghost',
		), 'adminuser');
		$this->assertFalse($missing['ok']);
		$this->assertSame('Update not found.', $missing['error']);

		$this->assertFalse(choosology_news_admin_delete($this->db, 'memo', 1)['ok']);
		$this->assertFalse(choosology_news_admin_delete($this->db, 'news', 0)['ok']);

		$after = count(choosology_news_admin_posts($this->db)) + count(choosology_news_admin_updates($this->db));
		$this->assertSame($before, $after);
	}

	public function testDeskListsItemsWithDateLabels(): void
	{
		$id = choosology_news_admin_save($this->db, array(
			'kind' => 'update',
			'headline' => self::MARK . ' Labelled',
		), 'adminuser')['id'];

		$desk = choosology_news_admin_desk($this->db);
		$this->assertArrayHasKey('posts', $desk);
		$this->assertSame($id, $desk['updates'][0]['id']);
		$this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4}$#', $desk['updates'][0]['when_label']);
	}

	public function testDegreeCountsAndResearcherRecord(): void
	{
		$counts = choosology_restricted_degree_counts($this->db);
		$this->assertSame(array_keys(choosology_achievement_catalog()), array_keys($counts));
		$this->assertSame(0, array_sum($counts));

		$this->assertTrue(choosology_restricted_grant_degree($this->db, 'adminuser', 'testuser', 'lab_initiate')['ok']);
		$this->assertTrue(choosology_restricted_grant_degree($this->db, 'adminuser', 'adminuser', 'lab_initiate')['ok']);
		$this->assertTrue(choosology_restricted_grant_degree($this->db, 'adminuser', 'testuser', 'scribbler')['ok']);

		$counts = choosology_restricted_degree_counts($this->db);
		$this->assertSame(2, $counts['lab_initiate']);
		$this->assertSame(1, $counts['scribbler']);
		$this->assertSame(0, $counts['taskmaster']);

		$record = choosology_restricted_degree_record($this->db, '  TESTUSER ');
		$this->assertTrue($record['ok']);
		$this->assertSame('testuser', $record['user']);
		$this->assertCount(count(choosology_achievement_catalog()), $record['degrees']);
		$held = array();
		foreach ($record['degrees'] as $degree) {
			if ($degree['earned_at'] !== null) {
				$held[] = $degree['key'];
				$this->assertNotSame('', $degree['earned_label']);
			} else {
				$this->assertSame('', $degree['earned_label']);
			}
		}
		sort($held);
		$this->assertSame(array('lab_initiate', 'scribbler'), $held);

		$this->assertSame('Unknown researcher.', choosology_restricted_degree_record($this->db, 'nobody_here')['error']);
		$this->assertSame('Researcher username required.', choosology_restricted_degree_record($this->db, '  ')['error']);
	}

	/** @return array<string,mixed>|null */
	private function findPost(int $id): ?array
	{
		foreach (choosology_news_admin_posts($this->db) as $post) {
			if ($post['id'] === $id) {
				return $post;
			}
		}
		return null;
	}

	/** @return array<string,mixed>|null */
	private function findUpdate(int $id): ?array
	{
		foreach (choosology_news_admin_updates($this->db) as $update) {
			if ($update['id'] === $id) {
				return $update;
			}
		}
		return null;
	}

	private function purge(): void
	{
		$like = mysqli_real_escape_string($this->db, self::MARK) . '%';
		mysqli_query($this->db, "DELETE FROM news WHERE headline LIKE '$like'");
		mysqli_query($this->db, "DELETE FROM updates WHERE `text` LIKE '$like'");
	}
}
