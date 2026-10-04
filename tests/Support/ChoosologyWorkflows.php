<?php
/**
 * Multi-step Classic / Lite workflow definitions + runner.
 *
 * Each workflow returns experiential metrics (clicks, elapsed_ms) and assertions.
 */
declare(strict_types=1);

require_once __DIR__ . '/WorkflowHttpClient.php';

final class ChoosologyWorkflows
{
	public static function serverAvailable(string $base = 'http://127.0.0.1:8000'): bool
	{
		$host = parse_url($base, PHP_URL_HOST) ?: '127.0.0.1';
		$port = (int) (parse_url($base, PHP_URL_PORT) ?: 8000);
		$fp = @fsockopen($host, $port, $errno, $errstr, 0.5);
		if (!$fp) {
			return false;
		}
		fclose($fp);
		return true;
	}

	/**
	 * Resolve a public branching adventure for play workflows.
	 *
	 * @return array{id:int,begin:int,middle:int,ending:int,title:string}|null
	 */
	public static function resolvePlayFixture(mysqli $db): ?array
	{
		$r = mysqli_query(
			$db,
			"SELECT id, title, `begin` FROM advs
			 WHERE avail='public' AND title='Midnight Beaker'
			 ORDER BY id DESC LIMIT 1"
		);
		$row = $r ? mysqli_fetch_assoc($r) : null;
		if (!$row) {
			$r = mysqli_query(
				$db,
				"SELECT id, title, `begin` FROM advs WHERE avail='public' ORDER BY id DESC LIMIT 1"
			);
			$row = $r ? mysqli_fetch_assoc($r) : null;
		}
		if (!$row) {
			return null;
		}
		$advid = (int) $row['id'];
		$begin = (int) $row['begin'];
		$delim = function_exists('choosology_choice_delimiter') ? choosology_choice_delimiter() : '|Q-D-|';
		$sr = mysqli_query($db, "SELECT id, name, choice1, choice2 FROM advscreens WHERE advused='$advid' AND IFNULL(deleted,0) NOT IN (1,'1')");
		$middle = 0;
		$ending = 0;
		$screens = array();
		while ($sr && ($s = mysqli_fetch_assoc($sr))) {
			$screens[(int) $s['id']] = $s;
		}
		if (isset($screens[$begin]['choice1'])) {
			$parts = explode($delim, (string) $screens[$begin]['choice1']);
			if (!empty($parts[1])) {
				$middle = (int) $parts[1];
			}
		}
		if ($middle > 0 && isset($screens[$middle]['choice1'])) {
			$parts = explode($delim, (string) $screens[$middle]['choice1']);
			if (!empty($parts[1])) {
				$ending = (int) $parts[1];
			}
		}
		if ($ending < 1) {
			foreach ($screens as $id => $s) {
				if ($id === $begin || $id === $middle) {
					continue;
				}
				$c1 = trim((string) ($s['choice1'] ?? ''));
				if ($c1 === '') {
					$ending = $id;
					break;
				}
			}
		}
		if ($begin < 1 || $middle < 1 || $ending < 1) {
			return null;
		}
		return array(
			'id' => $advid,
			'begin' => $begin,
			'middle' => $middle,
			'ending' => $ending,
			'title' => (string) ($row['title'] ?? ('#' . $advid)),
		);
	}

	/**
	 * @return list<string>
	 */
	public static function catalog(): array
	{
		return array(
			'lite_login_browse_play_ending',
			'classic_login_play_ending',
			'lite_login_ledger',
			'classic_login_ledger',
			'mode_switch_roundtrip',
			'lite_structure_inspect',
		);
	}

	/**
	 * @param array{user?:string,pass?:string,base?:string,fixture?:array} $opts
	 * @return array<string,mixed>
	 */
	public static function run(string $name, array $opts = array()): array
	{
		$base = (string) ($opts['base'] ?? 'http://127.0.0.1:8000');
		$user = (string) ($opts['user'] ?? 'labtester');
		$pass = (string) ($opts['pass'] ?? 'labpass');
		$client = new WorkflowHttpClient($base);
		$client->resetMetrics();

		$result = array(
			'workflow' => $name,
			'ok' => false,
			'error' => null,
			'mode' => null,
			'assertions' => array(),
		);

		try {
			switch ($name) {
				case 'lite_login_browse_play_ending':
					$result = array_merge($result, self::liteLoginBrowsePlayEnding($client, $user, $pass, $opts));
					break;
				case 'classic_login_play_ending':
					$result = array_merge($result, self::classicLoginPlayEnding($client, $user, $pass, $opts));
					break;
				case 'lite_login_ledger':
					$result = array_merge($result, self::liteLoginLedger($client, $user, $pass));
					break;
				case 'classic_login_ledger':
					$result = array_merge($result, self::classicLoginLedger($client, $user, $pass));
					break;
				case 'mode_switch_roundtrip':
					$result = array_merge($result, self::modeSwitchRoundtrip($client, $user, $pass));
					break;
				case 'lite_structure_inspect':
					$result = array_merge($result, self::liteStructureInspect($client, $user, $pass, $opts));
					break;
				default:
					$result['error'] = 'Unknown workflow: ' . $name;
					break;
			}
		} catch (Throwable $e) {
			$result['ok'] = false;
			$result['error'] = $e->getMessage();
		}

		$metrics = $client->metrics();
		$result['clicks'] = $metrics['clicks'];
		$result['elapsed_ms'] = $metrics['elapsed_ms'];
		$result['steps'] = $metrics['steps'];
		$result['workflow'] = $name;
		return $result;
	}

	/**
	 * @param array<string,mixed> $opts
	 * @return array<string,mixed>
	 */
	private static function liteLoginBrowsePlayEnding(WorkflowHttpClient $client, string $user, string $pass, array $opts): array
	{
		$fixture = $opts['fixture'] ?? null;
		if (!is_array($fixture)) {
			throw new RuntimeException('Play fixture required');
		}
		$advid = (int) $fixture['id'];
		$middle = (int) $fixture['middle'];
		$ending = (int) $fixture['ending'];
		$assertions = array();

		$login = $client->postForm(
			'/lite/login.php',
			array('action' => 'login', 'logname' => $user, 'logpass' => $pass, 'next' => '/lite/index.php'),
			1,
			'lite_login_submit',
			'Submit Lite login form'
		);
		$home = $client->followRedirect($login, 0, 'lite_login_landing', 'Land on Lite home after login');
		$assertions['login_redirect'] = in_array($login['code'], array(302, 303), true) || $home['code'] === 200;
		$assertions['home_brand'] = str_contains($home['body'], 'CHOOSOLOGY LITE') || str_contains($home['body'], 'Signed in as');

		$browse = $client->request('GET', '/lite/browse.php', null, array(), 1, 'lite_browse', 'Open Browse');
		$assertions['browse_ok'] = $browse['code'] === 200 && str_contains($browse['body'], 'Browse experiments');

		$view = $client->request('GET', '/lite/view.php?id=' . $advid, null, array(), 1, 'lite_open_experiment', 'Open experiment begin');
		$assertions['view_begin'] = $view['code'] === 200 && (str_contains($view['body'], 'Continue') || str_contains($view['body'], 'choice') || str_contains($view['body'], 'lite-btn'));

		$mid = $client->request(
			'GET',
			'/lite/view.php?id=' . $advid . '&screen=' . $middle . '&from=' . (int) $fixture['begin'],
			null,
			array(),
			1,
			'lite_choice_continue',
			'Choose Continue → middle'
		);
		$assertions['view_middle'] = $mid['code'] === 200 && (str_contains($mid['body'], 'glowing') || str_contains($mid['body'], 'dark') || str_contains($mid['body'], 'Take the'));

		$end = $client->request(
			'GET',
			'/lite/view.php?id=' . $advid . '&screen=' . $ending . '&from=' . $middle,
			null,
			array(),
			1,
			'lite_choice_ending',
			'Choose path → ending'
		);
		$assertions['view_ending'] = $end['code'] === 200 && (
			str_contains($end['body'], 'reached an end')
			|| str_contains($end['body'], 'Terminal outcome')
			|| str_contains($end['body'], 'lite-ending')
		);

		return array(
			'mode' => 'lite',
			'ok' => !in_array(false, $assertions, true),
			'assertions' => $assertions,
			'fixture_id' => $advid,
		);
	}

	/**
	 * @param array<string,mixed> $opts
	 * @return array<string,mixed>
	 */
	private static function classicLoginPlayEnding(WorkflowHttpClient $client, string $user, string $pass, array $opts): array
	{
		$fixture = $opts['fixture'] ?? null;
		if (!is_array($fixture)) {
			throw new RuntimeException('Play fixture required');
		}
		$advid = (int) $fixture['id'];
		$begin = (int) $fixture['begin'];
		$middle = (int) $fixture['middle'];
		$ending = (int) $fixture['ending'];
		$assertions = array();

		$login = $client->postForm(
			'/ajax/authentajax.php',
			array('loginsubmit' => '1', 'logname' => $user, 'logpass' => $pass),
			1,
			'classic_login_submit',
			'Submit Classic AJAX login'
		);
		$assertions['login_ok'] = $login['code'] === 200 && trim($login['body']) === $user;

		$shell = $client->request('GET', '/index.php?stay=1', null, array(), 1, 'classic_shell', 'Open Classic shell');
		$assertions['shell_ok'] = $shell['code'] === 200 && str_contains($shell['body'], 'Logged in as');

		$view = $client->request('GET', '/view.php?id=' . $advid, null, array(), 1, 'classic_open_experiment', 'Open Classic view begin');
		$assertions['view_begin'] = $view['code'] === 200 && str_contains($view['body'], (string) $advid);

		$mid = $client->request(
			'GET',
			'/ajax/screenajax.php?project_lazarus=go&screen=' . $middle . '&from=' . $begin,
			null,
			array(),
			1,
			'classic_choice_continue',
			'Classic AJAX navigate to middle'
		);
		$midJson = json_decode($mid['body'], true);
		$assertions['ajax_middle'] = $mid['code'] === 200 && is_array($midJson) && !empty($midJson['choices']);

		$end = $client->request(
			'GET',
			'/ajax/screenajax.php?project_lazarus=go&screen=' . $ending . '&from=' . $middle,
			null,
			array(),
			1,
			'classic_choice_ending',
			'Classic AJAX navigate to ending'
		);
		$endJson = json_decode($end['body'], true);
		$choicesHtml = is_array($endJson) ? (string) ($endJson['choices'] ?? '') : '';
		$assertions['ajax_ending'] = $end['code'] === 200 && (
			str_contains($choicesHtml, 'ending-panel')
			|| str_contains($choicesHtml, 'reached an end')
			|| str_contains($choicesHtml, 'Terminal outcome')
		);

		return array(
			'mode' => 'classic',
			'ok' => !in_array(false, $assertions, true),
			'assertions' => $assertions,
			'fixture_id' => $advid,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function liteLoginLedger(WorkflowHttpClient $client, string $user, string $pass): array
	{
		$assertions = array();
		$login = $client->postForm(
			'/lite/login.php',
			array('action' => 'login', 'logname' => $user, 'logpass' => $pass, 'next' => '/lite/ledger.php'),
			1,
			'lite_login_for_ledger',
			'Lite login targeting Ledger'
		);
		$ledger = $client->followRedirect($login, 0, 'lite_ledger_landing', 'Land on Lite Ledger');
		if ($ledger['code'] !== 200 || !str_contains($ledger['body'], 'DataScrip')) {
			$ledger = $client->request('GET', '/lite/ledger.php', null, array(), 1, 'lite_ledger_open', 'Open Lite Ledger');
		}
		$assertions['ledger_ok'] = $ledger['code'] === 200 && str_contains($ledger['body'], 'DataScrip');
		$assertions['glyph_img'] = str_contains($ledger['body'], 'datascrip.png');
		$assertions['signed_in'] = str_contains($ledger['body'], $user);

		return array(
			'mode' => 'lite',
			'ok' => !in_array(false, $assertions, true),
			'assertions' => $assertions,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function classicLoginLedger(WorkflowHttpClient $client, string $user, string $pass): array
	{
		$assertions = array();
		$login = $client->postForm(
			'/ajax/authentajax.php',
			array('loginsubmit' => '1', 'logname' => $user, 'logpass' => $pass),
			1,
			'classic_login_for_ledger',
			'Classic login for Ledger'
		);
		$assertions['login_ok'] = $login['code'] === 200 && trim($login['body']) === $user;

		$ledger = $client->request('GET', '/mystuff/ledger.php', null, array(), 1, 'classic_ledger_open', 'Open My Stuff Ledger fragment');
		$assertions['ledger_ok'] = $ledger['code'] === 200 && str_contains($ledger['body'], 'DataScrip');
		$assertions['glyph_img'] = str_contains($ledger['body'], 'datascrip.png');

		return array(
			'mode' => 'classic',
			'ok' => !in_array(false, $assertions, true),
			'assertions' => $assertions,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function modeSwitchRoundtrip(WorkflowHttpClient $client, string $user, string $pass): array
	{
		$assertions = array();
		$login = $client->postForm(
			'/lite/login.php',
			array('action' => 'login', 'logname' => $user, 'logpass' => $pass, 'next' => '/lite/index.php'),
			1,
			'roundtrip_lite_login',
			'Login in Lite'
		);
		$liteHome = $client->followRedirect($login, 0, 'roundtrip_lite_home', 'Lite home');
		$assertions['lite_start'] = $liteHome['code'] === 200 && str_contains($liteHome['body'], 'CHOOSOLOGY LITE');

		$toClassic = $client->request('GET', '/lite/switch.php?to=classic', null, array(), 1, 'switch_to_classic', 'Switch preference to Classic');
		$classic = $client->followRedirect($toClassic, 0, 'classic_after_switch', 'Open Classic after switch');
		$assertions['classic_land'] = (
			($toClassic['code'] >= 300 && $toClassic['code'] < 400)
			|| $classic['code'] === 200
		) && (
			str_contains($classic['body'], 'Logged in as')
			|| str_contains($classic['body'], 'Choosology')
			|| str_contains($toClassic['headers']['location'] ?? '', 'stay=1')
		);

		$toLite = $client->request('GET', '/lite/switch.php?to=lite', null, array(), 1, 'switch_to_lite', 'Switch preference back to Lite');
		$liteAgain = $client->followRedirect($toLite, 0, 'lite_after_switch', 'Return to Lite');
		$assertions['lite_return'] = $liteAgain['code'] === 200 && (
			str_contains($liteAgain['body'], 'CHOOSOLOGY LITE')
			|| str_contains($liteAgain['body'], 'Signed in as')
		);

		return array(
			'mode' => 'both',
			'ok' => !in_array(false, $assertions, true),
			'assertions' => $assertions,
		);
	}

	/**
	 * @param array<string,mixed> $opts
	 * @return array<string,mixed>
	 */
	private static function liteStructureInspect(WorkflowHttpClient $client, string $user, string $pass, array $opts): array
	{
		$fixture = $opts['fixture'] ?? null;
		if (!is_array($fixture)) {
			throw new RuntimeException('Play fixture required');
		}
		$advid = (int) $fixture['id'];
		$assertions = array();

		$login = $client->postForm(
			'/lite/login.php',
			array('action' => 'login', 'logname' => $user, 'logpass' => $pass, 'next' => '/lite/view.php?id=' . $advid),
			1,
			'struct_login',
			'Lite login into experiment'
		);
		$view = $client->followRedirect($login, 0, 'struct_view', 'Experiment view');
		if ($view['code'] !== 200) {
			$view = $client->request('GET', '/lite/view.php?id=' . $advid, null, array(), 1, 'struct_view_direct', 'Open experiment');
		}
		$assertions['view_ok'] = $view['code'] === 200;

		$struct = $client->request('GET', '/lite/structure.php?id=' . $advid, null, array(), 1, 'struct_open', 'Open ASCII structure');
		$assertions['structure_ok'] = $struct['code'] === 200 && str_contains($struct['body'], 'lite-ascii');
		$assertions['structure_has_begin'] = str_contains($struct['body'], '*') || str_contains($struct['body'], 'begin') || str_contains($struct['body'], 'Start');

		$back = $client->request('GET', '/lite/view.php?id=' . $advid, null, array(), 1, 'struct_back_play', 'Back to play');
		$assertions['back_ok'] = $back['code'] === 200;

		return array(
			'mode' => 'lite',
			'ok' => !in_array(false, $assertions, true),
			'assertions' => $assertions,
			'fixture_id' => $advid,
		);
	}
}
