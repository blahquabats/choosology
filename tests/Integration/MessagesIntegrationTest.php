<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MessagesIntegrationTest extends TestCase
{
	protected function setUp(): void
	{
		if (!ChoosologyTestDb::available()) {
			$this->markTestSkipped('choosology_test database unavailable');
		}
		ChoosologyTestDb::resetFixtures();
		if (session_status() === PHP_SESSION_NONE) {
			@session_start();
		}
		$_SESSION['user'] = 'testuser';
	}

	public function testResolveUsername(): void
	{
		$this->assertSame('testuser', choosology_resolve_username('testuser'));
		$this->assertNull(choosology_resolve_username('missing-user-xyz'));
	}

	public function testSendAndUnreadCount(): void
	{
		$id = choosology_send_message('testuser', 'adminuser', 'Hello', 'Body text', 'normal');
		$this->assertGreaterThan(0, $id);
		$this->assertSame(1, choosology_unread_message_count('testuser'));
		$this->assertSame(0, choosology_unread_message_count('adminuser'));
	}

	public function testReportMessageToAdmins(): void
	{
		$id = choosology_send_message('testuser', 'adminuser', 'To report', 'Secret', 'normal');
		$sent = choosology_report_message($id, 'testuser', 'Looks spammy');
		$this->assertGreaterThanOrEqual(1, $sent);
		$this->assertGreaterThanOrEqual(1, choosology_unread_message_count('adminuser'));
	}

	public function testAdminUsernames(): void
	{
		$admins = choosology_admin_usernames();
		$this->assertContains('adminuser', $admins);
		$this->assertNotContains('testuser', $admins);
	}

	public function testMarkSingleAndAllMessagesSeenFiltersUnreadDropdown(): void
	{
		$first = choosology_send_message('testuser', 'Choosology', 'Degree awarded', 'Lab Initiate', 'system');
		$second = choosology_send_message('testuser', 'adminuser', 'Hello', 'First note', 'normal');
		$third = choosology_send_message('testuser', 'Choosology', 'DataScrip award', 'Received 10', 'system');
		$otherUserMsg = choosology_send_message('adminuser', 'testuser', 'Ping', 'Admin copy', 'normal');

		$this->assertSame(3, choosology_unread_message_count('testuser'));
		$this->assertSame(1, choosology_unread_message_count('adminuser'));

		$unreadList = choosology_recent_messages('testuser', 20, true);
		$this->assertCount(3, $unreadList);

		// Another user cannot mark testuser's message as read.
		$this->assertSame(0, choosology_mark_message_seen('adminuser', $first));
		$this->assertSame(3, choosology_unread_message_count('testuser'));

		// Clicking one event marks only that message seen and removes it from the unread list.
		$this->assertSame(1, choosology_mark_message_seen('testuser', $second));
		$this->assertSame(0, choosology_mark_message_seen('testuser', $second));
		$this->assertSame(2, choosology_unread_message_count('testuser'));

		$afterSingle = choosology_recent_messages('testuser', 20, true);
		$remainingIds = array_map(static fn (array $row): int => $row['id'], $afterSingle);
		$this->assertSame(array($third, $first), $remainingIds);

		// Mixed recent list still orders unread items ahead of already-seen items.
		$mixed = choosology_recent_messages('testuser', 20, false);
		$this->assertCount(3, $mixed);
		$this->assertFalse($mixed[0]['seen']);
		$this->assertFalse($mixed[1]['seen']);
		$this->assertTrue($mixed[2]['seen']);
		$this->assertSame($second, $mixed[2]['id']);

		// Mark all as read clears testuser's unread list without touching adminuser's inbox.
		$this->assertSame(2, choosology_mark_message_seen('testuser', 0));
		$this->assertSame(0, choosology_unread_message_count('testuser'));
		$this->assertSame(array(), choosology_recent_messages('testuser', 20, true));
		$this->assertSame(1, choosology_unread_message_count('adminuser'));
		$this->assertGreaterThan(0, $otherUserMsg);
	}
}
