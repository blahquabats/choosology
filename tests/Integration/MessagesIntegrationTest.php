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
}
