<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * HTTP smoke tests against the local PHP built-in server when available.
 */
final class AjaxHttpSmokeTest extends TestCase
{
	private string $base = 'http://127.0.0.1:8000';

	protected function setUp(): void
	{
		$fp = @fsockopen('127.0.0.1', 8000, $errno, $errstr, 0.5);
		if (!$fp) {
			$this->markTestSkipped('PHP dev server not listening on :8000');
		}
		fclose($fp);
	}

	public function testIndexLoads(): void
	{
		[$code, $body] = $this->httpGet('/index.php');
		$this->assertSame(200, $code);
		$this->assertStringContainsString('Choosology', $body);
		$this->assertStringContainsString('Apply for lab access', $body);
	}

	public function testSignupChallengeJson(): void
	{
		[$code, $body] = $this->httpGet('/ajax/signupchallenge.php');
		$this->assertSame(200, $code);
		$data = json_decode($body, true);
		$this->assertIsArray($data);
		$this->assertTrue(!empty($data['ok']));
		$this->assertNotSame('', $data['nonce'] ?? '');
		$this->assertNotSame('', $data['prompt'] ?? '');
	}

	public function testAuthRejectsBadLogin(): void
	{
		[$code, $body] = $this->httpPostForm('/ajax/authentajax.php', array(
			'loginsubmit' => '1',
			'logname' => 'nobody',
			'logpass' => 'wrong',
		));
		$this->assertSame(200, $code);
		$this->assertSame('2', trim($body));
	}

	/**
	 * @return array{0:int,1:string}
	 */
	private function httpGet(string $path): array
	{
		$ctx = stream_context_create(array('http' => array(
			'method' => 'GET',
			'ignore_errors' => true,
			'timeout' => 5,
		)));
		$body = (string) file_get_contents($this->base . $path, false, $ctx);
		$code = 0;
		if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
			$code = (int) $m[1];
		}
		return array($code, $body);
	}

	/**
	 * @param array<string,string> $fields
	 * @return array{0:int,1:string}
	 */
	private function httpPostForm(string $path, array $fields): array
	{
		$ctx = stream_context_create(array('http' => array(
			'method' => 'POST',
			'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
			'content' => http_build_query($fields),
			'ignore_errors' => true,
			'timeout' => 5,
		)));
		$body = (string) file_get_contents($this->base . $path, false, $ctx);
		$code = 0;
		if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
			$code = (int) $m[1];
		}
		return array($code, $body);
	}
}
