<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PasswordResetHttpTest extends TestCase
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

	public function testIndexExposesForgotCredentials(): void
	{
		[$code, $body] = $this->httpGet('/index.php');
		$this->assertSame(200, $code);
		$this->assertStringContainsString('Forgot credentials?', $body);
		$this->assertStringContainsString("id='openforgot'", $body);
		$this->assertStringContainsString('id="forgotmodal"', $body);
		$this->assertStringContainsString("id='loginbox-inner'", $body);
	}

	public function testLiteLoginLinksForgot(): void
	{
		[$code, $body] = $this->httpGet('/lite/login.php');
		$this->assertSame(200, $code);
		$this->assertStringContainsString('Forgot credentials?', $body);
		$this->assertStringContainsString('forgot.php', $body);
	}

	public function testPasswordResetAjaxAcceptsJson(): void
	{
		[$code, $body] = $this->httpPostJson('/ajax/passwordreset.php', array(
			'identity' => 'nobody-at-all-' . bin2hex(random_bytes(3)),
			'lab_fax' => '',
		));
		$this->assertSame(200, $code);
		$data = json_decode($body, true);
		$this->assertIsArray($data);
		$this->assertSame(1, (int) ($data['ok'] ?? 0));
		$this->assertNotSame('', $data['message'] ?? '');
	}

	public function testResetPassRejectsMissingToken(): void
	{
		[$code, $body] = $this->httpGet('/resetpass.php');
		$this->assertSame(200, $code);
		$this->assertStringContainsString('invalid', strtolower($body));
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
	 * @param array<string,mixed> $payload
	 * @return array{0:int,1:string}
	 */
	private function httpPostJson(string $path, array $payload): array
	{
		$ctx = stream_context_create(array('http' => array(
			'method' => 'POST',
			'header' => "Content-Type: application/json; charset=utf-8\r\n",
			'content' => json_encode($payload),
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
