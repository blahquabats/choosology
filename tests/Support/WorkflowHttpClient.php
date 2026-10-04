<?php
/**
 * Cookie-aware HTTP client for multi-step workflow tests.
 * Counts experiential metrics: clicks + wall-clock time per step.
 */
declare(strict_types=1);

final class WorkflowHttpClient
{
	private string $base;
	/** @var array<string,string> */
	private array $cookies = array();
	/** @var list<array<string,mixed>> */
	private array $steps = array();
	private int $clicks = 0;
	private float $startedAt;

	public function __construct(string $base = 'http://127.0.0.1:8000')
	{
		$this->base = rtrim($base, '/');
		$this->startedAt = microtime(true);
	}

	public function resetMetrics(): void
	{
		$this->steps = array();
		$this->clicks = 0;
		$this->startedAt = microtime(true);
	}

	public function clearCookies(): void
	{
		$this->cookies = array();
	}

	/**
	 * @param array<string,string> $headers
	 * @return array{code:int,body:string,headers:array<string,string>,url:string,elapsed_ms:int}
	 */
	public function request(
		string $method,
		string $path,
		?string $body = null,
		array $headers = array(),
		int $clickCost = 1,
		string $stepId = '',
		string $label = ''
	): array {
		$t0 = microtime(true);
		$url = $this->base . (str_starts_with($path, '/') ? $path : '/' . $path);
		$headerLines = array();
		foreach ($headers as $k => $v) {
			$headerLines[] = $k . ': ' . $v;
		}
		if ($this->cookies) {
			$pairs = array();
			foreach ($this->cookies as $name => $val) {
				$pairs[] = $name . '=' . $val;
			}
			$headerLines[] = 'Cookie: ' . implode('; ', $pairs);
		}
		$opts = array(
			'http' => array(
				'method' => strtoupper($method),
				'header' => implode("\r\n", $headerLines) . ($headerLines ? "\r\n" : ''),
				'ignore_errors' => true,
				'timeout' => 20,
				'follow_location' => 0,
			),
		);
		if ($body !== null) {
			$opts['http']['content'] = $body;
		}
		$ctx = stream_context_create($opts);
		$raw = @file_get_contents($url, false, $ctx);
		$bodyOut = $raw === false ? '' : (string) $raw;
		$code = 0;
		$respHeaders = array();
		if (!empty($http_response_header) && is_array($http_response_header)) {
			foreach ($http_response_header as $line) {
				if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $m)) {
					$code = (int) $m[1];
					continue;
				}
				if (stripos($line, 'Set-Cookie:') === 0) {
					$cookie = trim(substr($line, strlen('Set-Cookie:')));
					$part = explode(';', $cookie, 2)[0];
					if (strpos($part, '=') !== false) {
						[$cn, $cv] = explode('=', $part, 2);
						$this->cookies[trim($cn)] = trim($cv);
					}
					continue;
				}
				if (strpos($line, ':') !== false) {
					[$hk, $hv] = explode(':', $line, 2);
					$respHeaders[strtolower(trim($hk))] = trim($hv);
				}
			}
		}
		$elapsed = (int) round((microtime(true) - $t0) * 1000);
		$this->clicks += max(0, $clickCost);
		$step = array(
			'id' => $stepId !== '' ? $stepId : (strtolower($method) . ':' . $path),
			'label' => $label !== '' ? $label : ($method . ' ' . $path),
			'method' => strtoupper($method),
			'path' => $path,
			'code' => $code,
			'clicks' => max(0, $clickCost),
			'elapsed_ms' => $elapsed,
			'ok' => $code >= 200 && $code < 400,
		);
		$this->steps[] = $step;
		return array(
			'code' => $code,
			'body' => $bodyOut,
			'headers' => $respHeaders,
			'url' => $url,
			'elapsed_ms' => $elapsed,
		);
	}

	/**
	 * @param array<string,scalar|null> $fields
	 * @return array{code:int,body:string,headers:array<string,string>,url:string,elapsed_ms:int}
	 */
	public function postForm(string $path, array $fields, int $clickCost = 1, string $stepId = '', string $label = ''): array
	{
		return $this->request(
			'POST',
			$path,
			http_build_query($fields),
			array('Content-Type' => 'application/x-www-form-urlencoded'),
			$clickCost,
			$stepId,
			$label
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{code:int,body:string,headers:array<string,string>,url:string,elapsed_ms:int}
	 */
	public function postJson(string $path, array $payload, int $clickCost = 1, string $stepId = '', string $label = ''): array
	{
		return $this->request(
			'POST',
			$path,
			json_encode($payload, JSON_UNESCAPED_UNICODE),
			array('Content-Type' => 'application/json; charset=utf-8'),
			$clickCost,
			$stepId,
			$label
		);
	}

	/**
	 * Follow one redirect if Location present (counts as 0 extra clicks by default).
	 *
	 * @param array{code:int,body:string,headers:array<string,string>,url:string,elapsed_ms:int} $res
	 * @return array{code:int,body:string,headers:array<string,string>,url:string,elapsed_ms:int}
	 */
	public function followRedirect(array $res, int $clickCost = 0, string $stepId = 'redirect', string $label = 'Follow redirect'): array
	{
		if ($res['code'] < 300 || $res['code'] >= 400) {
			return $res;
		}
		$loc = $res['headers']['location'] ?? '';
		if ($loc === '') {
			return $res;
		}
		if (str_starts_with($loc, 'http://') || str_starts_with($loc, 'https://')) {
			$path = parse_url($loc, PHP_URL_PATH) ?: '/';
			$query = parse_url($loc, PHP_URL_QUERY);
			if ($query) {
				$path .= '?' . $query;
			}
		} else {
			$path = $loc;
		}
		return $this->request('GET', $path, null, array(), $clickCost, $stepId, $label);
	}

	/**
	 * @return array{clicks:int,elapsed_ms:int,steps:list<array<string,mixed>>}
	 */
	public function metrics(): array
	{
		return array(
			'clicks' => $this->clicks,
			'elapsed_ms' => (int) round((microtime(true) - $this->startedAt) * 1000),
			'steps' => $this->steps,
		);
	}
}
