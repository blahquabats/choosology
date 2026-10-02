<?php
/**
 * Account / profile HTML helpers (safe without connect.php).
 */

function choosology_account_plain_len(string $html): int
{
	$plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
	if (function_exists('mb_strlen')) {
		return mb_strlen($plain, 'UTF-8');
	}
	return strlen($plain);
}

function choosology_account_sanitize_about(string $html): string
{
	$html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html);
	$html = strip_tags((string) $html, '<p><br><strong><em><b><i><a><ul><ol><li><blockquote><span>');
	$html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
	$html = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $html);
	$html = preg_replace_callback('/\sstyle\s*=\s*([\'"])(.*?)\1/is', static function (array $m): string {
		$safe = array();
		$decls = explode(';', (string) $m[2]);
		foreach ($decls as $decl) {
			$parts = explode(':', $decl, 2);
			if (count($parts) !== 2) {
				continue;
			}
			$prop = strtolower(trim($parts[0]));
			$value = trim($parts[1]);
			if (!in_array($prop, array('color', 'background-color'), true)) {
				continue;
			}
			if (preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\(\s*[0-9.\s,%]+\)|[a-zA-Z]+)$/', $value)) {
				$safe[] = $prop . ': ' . $value;
			}
		}
		return count($safe) > 0 ? ' style="' . htmlspecialchars(implode('; ', $safe), ENT_QUOTES, 'UTF-8') . '"' : '';
	}, $html);
	return (string) $html;
}
