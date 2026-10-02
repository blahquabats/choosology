<?php
/**
 * Pure news list/detail helpers (safe without connect.php).
 */

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
