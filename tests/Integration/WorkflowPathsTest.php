<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Multi-step Classic/Lite workflow integration tests with experiential metrics.
 *
 * Requires: PHP built-in server on :8000 and seeded public adventure (e.g. Midnight Beaker).
 * Metrics (clicks / elapsed_ms / steps) are attached to each result and printed on failure.
 */
final class WorkflowPathsTest extends TestCase
{
	private string $base = 'http://127.0.0.1:8000';
	private ?mysqli $db = null;
	/** @var array{id:int,begin:int,middle:int,ending:int,title:string}|null */
	private ?array $fixture = null;

	protected function setUp(): void
	{
		require_once CHOOSOLOGY_ROOT . '/tests/Support/ChoosologyWorkflows.php';
		if (!ChoosologyWorkflows::serverAvailable($this->base)) {
			$this->markTestSkipped('PHP dev server not listening on :8000');
		}
		$this->db = $this->openAppDb();
		if (!$this->db) {
			$this->markTestSkipped('Cannot connect to app database for workflow fixtures');
		}
		$this->fixture = ChoosologyWorkflows::resolvePlayFixture($this->db);
		if ($this->fixture === null) {
			$this->markTestSkipped('No public branching adventure available (seed Midnight Beaker)');
		}
		$this->ensureWorkflowUser($this->db, 'labtester', 'labpass');
	}

	public function testLiteLoginBrowsePlayEnding(): void
	{
		$result = ChoosologyWorkflows::run('lite_login_browse_play_ending', array(
			'base' => $this->base,
			'fixture' => $this->fixture,
		));
		$this->assertWorkflowOk($result);
		$this->assertSame('lite', $result['mode']);
		$this->assertGreaterThanOrEqual(5, (int) $result['clicks']);
	}

	public function testClassicLoginPlayEnding(): void
	{
		$result = ChoosologyWorkflows::run('classic_login_play_ending', array(
			'base' => $this->base,
			'fixture' => $this->fixture,
		));
		$this->assertWorkflowOk($result);
		$this->assertSame('classic', $result['mode']);
		$this->assertGreaterThanOrEqual(5, (int) $result['clicks']);
	}

	public function testLiteLoginLedger(): void
	{
		$result = ChoosologyWorkflows::run('lite_login_ledger', array('base' => $this->base));
		$this->assertWorkflowOk($result);
		$this->assertTrue($result['assertions']['glyph_img']);
	}

	public function testClassicLoginLedger(): void
	{
		$result = ChoosologyWorkflows::run('classic_login_ledger', array('base' => $this->base));
		$this->assertWorkflowOk($result);
		$this->assertTrue($result['assertions']['glyph_img']);
	}

	public function testModeSwitchRoundtrip(): void
	{
		$result = ChoosologyWorkflows::run('mode_switch_roundtrip', array('base' => $this->base));
		$this->assertWorkflowOk($result);
		$this->assertSame('both', $result['mode']);
	}

	public function testLiteStructureInspect(): void
	{
		$result = ChoosologyWorkflows::run('lite_structure_inspect', array(
			'base' => $this->base,
			'fixture' => $this->fixture,
		));
		$this->assertWorkflowOk($result);
	}

	public function testPlayWorkflowsExposeComparableMetrics(): void
	{
		$lite = ChoosologyWorkflows::run('lite_login_browse_play_ending', array(
			'base' => $this->base,
			'fixture' => $this->fixture,
		));
		$classic = ChoosologyWorkflows::run('classic_login_play_ending', array(
			'base' => $this->base,
			'fixture' => $this->fixture,
		));
		$this->assertWorkflowOk($lite);
		$this->assertWorkflowOk($classic);
		$this->assertArrayHasKey('elapsed_ms', $lite);
		$this->assertArrayHasKey('elapsed_ms', $classic);
		$this->assertNotEmpty($lite['steps']);
		$this->assertNotEmpty($classic['steps']);
		// Experiential comparison snapshot (not pass/fail on which is faster).
		$comparison = array(
			'lite_clicks' => $lite['clicks'],
			'classic_clicks' => $classic['clicks'],
			'lite_elapsed_ms' => $lite['elapsed_ms'],
			'classic_elapsed_ms' => $classic['elapsed_ms'],
		);
		$this->assertGreaterThan(0, $comparison['lite_clicks']);
		$this->assertGreaterThan(0, $comparison['classic_clicks']);
	}

	/**
	 * @param array<string,mixed> $result
	 */
	private function assertWorkflowOk(array $result): void
	{
		if (empty($result['ok'])) {
			$msg = 'Workflow failed: ' . ($result['workflow'] ?? '?')
				. ' error=' . ($result['error'] ?? 'n/a')
				. ' assertions=' . json_encode($result['assertions'] ?? array())
				. ' metrics=' . json_encode(array(
					'clicks' => $result['clicks'] ?? null,
					'elapsed_ms' => $result['elapsed_ms'] ?? null,
					'steps' => $result['steps'] ?? array(),
				));
			$this->fail($msg);
		}
		$this->assertTrue(true);
	}

	private function openAppDb(): ?mysqli
	{
		try {
			$cfg = choosology_db_settings('choosology');
			$db = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
			if (!$db) {
				return null;
			}
			mysqli_set_charset($db, 'utf8mb4');
			return $db;
		} catch (Throwable $e) {
			return null;
		}
	}

	private function ensureWorkflowUser(mysqli $db, string $name, string $pass): void
	{
		$hash = choosology_legacy_password_hash($pass);
		$esc = mysqli_real_escape_string($db, $name);
		$escHash = mysqli_real_escape_string($db, $hash);
		$r = mysqli_query($db, "SELECT id FROM users WHERE name='$esc' LIMIT 1");
		if ($r && mysqli_num_rows($r) > 0) {
			mysqli_query($db, "UPDATE users SET pass='$escHash' WHERE name='$esc' LIMIT 1");
			return;
		}
		mysqli_query(
			$db,
			"INSERT INTO users (name, pass, email, authent, usertype, joined, view_restricted, fbshow)
			 VALUES ('$esc', '$escHash', '{$esc}@example.com', '', 0, NOW(), 0, 1)"
		);
	}
}
