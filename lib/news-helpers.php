<?php
/**
 * Pure news list/detail helpers (safe without connect.php).
 * Schema probes need mysqli when called.
 */

/**
 * @return array{0:bool,1?:string} [ ok, error message ]
 */
function choosology_news_table_ready(mysqli $db): array
{
	$chk = @mysqli_query($db, "SHOW TABLES LIKE 'news'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array(false, 'The <code>news</code> table was not found. Run <code>choosology-schema.sql</code> or <code>sql/news_setup.sql</code> on your database.');
	}
	return array(true);
}

function choosology_news_schema(mysqli $db): array
{
	static $cache = null;
	if (is_array($cache)) {
		return $cache;
	}
	$cache = array(
		'has_body' => false,
		'has_text' => false,
		'has_whenposted' => false,
		'has_by' => false,
	);
	$r = mysqli_query($db, 'SHOW COLUMNS FROM news');
	if (!$r) {
		return $cache;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$f = isset($row['Field']) ? (string) $row['Field'] : '';
		if ($f === 'body') {
			$cache['has_body'] = true;
		}
		if ($f === 'text') {
			$cache['has_text'] = true;
		}
		if ($f === 'whenposted') {
			$cache['has_whenposted'] = true;
		}
		if ($f === 'by') {
			$cache['has_by'] = true;
		}
	}
	return $cache;
}

/** Allowed HTML tags for news article bodies. */
function choosology_news_body_allowed_tags(): string
{
	return '<p><br><strong><em><b><i><a><ul><ol><li><h2><h3><blockquote><code><pre>';
}

function choosology_news_body_safe(string $bodyRaw): string
{
	if ($bodyRaw === '') {
		return '<p><em>No body text for this item yet.</em></p>';
	}
	/* Drop common executable/embed blocks before strip_tags (which keeps inner text). */
	$clean = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $bodyRaw);
	if (!is_string($clean)) {
		$clean = $bodyRaw;
	}
	$clean = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*/?>#is', '', $clean);
	if (!is_string($clean)) {
		$clean = $bodyRaw;
	}
	return strip_tags($clean, choosology_news_body_allowed_tags());
}

function choosology_news_row_body_raw(array $row): string
{
	if (!isset($row['body'])) {
		return isset($row['text']) ? (string) $row['text'] : '';
	}
	return (string) $row['body'];
}

/** Datetime for stamps / sort: `whenposted` only. */
function choosology_news_stamp_raw(array $row): string
{
	if (!isset($row['whenposted'])) {
		return '';
	}
	$t = trim((string) $row['whenposted']);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	return $t;
}

function choosology_news_list_query(array $schema): string
{
	$cols = 'id, headline';
	if (!empty($schema['has_whenposted'])) {
		$cols .= ', whenposted';
	}
	if (!empty($schema['has_body'])) {
		$cols .= ', body';
	} elseif (!empty($schema['has_text'])) {
		$cols .= ', `text`';
	}
	$order = 'id DESC';
	if (!empty($schema['has_whenposted'])) {
		$order = 'COALESCE(whenposted, \'1970-01-01 00:00:00\') DESC, id DESC';
	}
	return "SELECT {$cols} FROM news ORDER BY {$order}";
}

function choosology_news_detail_select(array $schema): string
{
	$parts = array('id', 'headline');
	if (!empty($schema['has_whenposted'])) {
		$parts[] = 'whenposted';
	}
	if (!empty($schema['has_by'])) {
		$parts[] = '`by`';
	}
	if (!empty($schema['has_body'])) {
		$parts[] = 'body';
	} elseif (!empty($schema['has_text'])) {
		$parts[] = '`text`';
	}
	return implode(', ', $parts);
}

function choosology_news_excerpt(string $html, int $maxLen = 90): string
{
	$plain = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
	$lenFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
	if (function_exists('mb_substr')) {
		$cut = mb_substr($plain, 0, $maxLen);
	} else {
		$cut = substr($plain, 0, $maxLen);
	}
	if ($lenFn($plain) > $maxLen) {
		$cut .= '…';
	}
	return $cut;
}
