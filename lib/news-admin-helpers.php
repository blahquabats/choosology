<?php
/**
 * Admin writes for `news` posts and one-line `updates` patch notes.
 *
 * Callers enforce the admin gate. Classic RESTRICTED and Lite share these
 * rules so the two desks cannot drift.
 */

require_once __DIR__ . '/news-helpers.php';
require_once __DIR__ . '/feed-helpers.php';

const CHOOSOLOGY_NEWS_HEADLINE_MAX = 255;
const CHOOSOLOGY_NEWS_UPDATE_MAX = 225;
const CHOOSOLOGY_NEWS_BYLINE_MAX = 45;

function choosology_news_admin_clip(string $text, int $max): string
{
	if (function_exists('mb_substr')) {
		return mb_substr($text, 0, $max, 'UTF-8');
	}
	return substr($text, 0, $max);
}

/**
 * Parse a desk selector such as "news:12" or "update:0" (0 = new item).
 *
 * @return array{kind:string,id:int}|null
 */
function choosology_news_admin_parse_key(string $key): ?array
{
	if (!preg_match('/^(news|update):(\d{1,9})$/', trim($key), $m)) {
		return null;
	}
	return array('kind' => $m[1], 'id' => (int) $m[2]);
}

/**
 * Validate one desk submission without touching the database.
 *
 * @param array<string,mixed> $data
 * @return array{ok:bool,error?:string,kind?:string,id?:int,headline?:string,body?:string,by?:string}
 */
function choosology_news_admin_normalize(array $data, string $defaultByline): array
{
	$kind = trim((string) ($data['kind'] ?? 'news'));
	if ($kind !== 'news' && $kind !== 'update') {
		return array('ok' => false, 'error' => 'Unknown item type.');
	}
	$id = (int) ($data['id'] ?? 0);
	if ($id < 0) {
		return array('ok' => false, 'error' => 'Invalid item id.');
	}
	$headline = trim((string) ($data['headline'] ?? ''));
	if ($headline === '') {
		return array(
			'ok' => false,
			'error' => $kind === 'update' ? 'Update text is required.' : 'Headline is required.',
		);
	}
	if ($kind === 'update') {
		return array(
			'ok' => true,
			'kind' => 'update',
			'id' => $id,
			'headline' => choosology_news_admin_clip($headline, CHOOSOLOGY_NEWS_UPDATE_MAX),
			'body' => '',
			'by' => '',
		);
	}
	$body = trim((string) ($data['body'] ?? ''));
	if ($body === '') {
		return array('ok' => false, 'error' => 'Body is required.');
	}
	$by = trim((string) ($data['by'] ?? ''));
	if ($by === '') {
		$by = trim($defaultByline);
	}
	return array(
		'ok' => true,
		'kind' => 'news',
		'id' => $id,
		'headline' => choosology_news_admin_clip($headline, CHOOSOLOGY_NEWS_HEADLINE_MAX),
		'body' => $body,
		'by' => choosology_news_admin_clip($by, CHOOSOLOGY_NEWS_BYLINE_MAX),
	);
}

function choosology_news_admin_updates_ready(mysqli $db): bool
{
	$chk = @mysqli_query($db, "SHOW TABLES LIKE 'updates'");
	return $chk && mysqli_num_rows($chk) > 0;
}

/**
 * Full news posts for the desk, newest first.
 *
 * @return list<array{id:int,headline:string,by:string,body:string,whenposted:string}>
 */
function choosology_news_admin_posts(mysqli $db, int $limit = 200): array
{
	$out = array();
	$ready = choosology_news_table_ready($db);
	if (!$ready[0]) {
		return $out;
	}
	$schema = choosology_news_schema($db);
	$limit = max(1, min(500, $limit));
	$order = !empty($schema['has_whenposted'])
		? "COALESCE(whenposted, '1970-01-01 00:00:00') DESC, id DESC"
		: 'id DESC';
	$r = mysqli_query(
		$db,
		'SELECT ' . choosology_news_detail_select($schema) . " FROM news ORDER BY $order LIMIT $limit"
	);
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$out[] = array(
			'id' => (int) ($row['id'] ?? 0),
			'headline' => (string) ($row['headline'] ?? ''),
			'by' => (string) ($row['by'] ?? ''),
			'body' => choosology_news_row_body_raw($row),
			'whenposted' => choosology_news_stamp_raw($row),
		);
	}
	return $out;
}

/**
 * Minor updates for the desk, newest first.
 *
 * @return list<array{id:int,text:string,whenposted:string}>
 */
function choosology_news_admin_updates(mysqli $db, int $limit = 200): array
{
	$out = array();
	if (!choosology_news_admin_updates_ready($db)) {
		return $out;
	}
	$limit = max(1, min(500, $limit));
	$r = mysqli_query(
		$db,
		"SELECT id, `text`, whenposted FROM updates
		 ORDER BY COALESCE(whenposted, '1970-01-01 00:00:00') DESC, id DESC
		 LIMIT $limit"
	);
	if (!$r) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($r)) {
		$out[] = array(
			'id' => (int) ($row['id'] ?? 0),
			'text' => (string) ($row['text'] ?? ''),
			'whenposted' => choosology_news_stamp_raw($row),
		);
	}
	return $out;
}

/**
 * Everything a desk needs to list and edit items, with dates in the viewer's format.
 *
 * @return array{posts:list<array<string,mixed>>,updates:list<array<string,mixed>>}
 */
function choosology_news_admin_desk(mysqli $db): array
{
	$posts = choosology_news_admin_posts($db);
	foreach ($posts as $i => $post) {
		$posts[$i]['when_label'] = choosology_feed_date_label((string) $post['whenposted']);
	}
	$updates = choosology_news_admin_updates($db);
	foreach ($updates as $i => $update) {
		$updates[$i]['when_label'] = choosology_feed_date_label((string) $update['whenposted']);
	}
	return array('posts' => $posts, 'updates' => $updates);
}

/**
 * @param array{kind:string,id:int,headline:string} $item
 * @return array{ok:bool,error?:string,kind?:string,id?:int,created?:bool}
 */
function choosology_news_admin_save_update(mysqli $db, array $item): array
{
	if (!choosology_news_admin_updates_ready($db)) {
		return array('ok' => false, 'error' => 'The updates table was not found.');
	}
	$id = (int) $item['id'];
	$text = mysqli_real_escape_string($db, (string) $item['headline']);
	if ($id > 0) {
		$exists = mysqli_query($db, "SELECT id FROM updates WHERE id = $id LIMIT 1");
		if (!$exists || mysqli_num_rows($exists) === 0) {
			return array('ok' => false, 'error' => 'Update not found.');
		}
		if (!mysqli_query($db, "UPDATE updates SET `text` = '$text' WHERE id = $id LIMIT 1")) {
			return array('ok' => false, 'error' => 'Could not save the update.');
		}
		return array('ok' => true, 'kind' => 'update', 'id' => $id, 'created' => false);
	}
	if (!mysqli_query($db, "INSERT INTO updates (`text`, whenposted) VALUES ('$text', NOW())")) {
		return array('ok' => false, 'error' => 'Could not add the update.');
	}
	return array('ok' => true, 'kind' => 'update', 'id' => (int) mysqli_insert_id($db), 'created' => true);
}

/**
 * @param array{kind:string,id:int,headline:string,body:string,by:string} $item
 * @return array{ok:bool,error?:string,kind?:string,id?:int,created?:bool}
 */
function choosology_news_admin_save_post(mysqli $db, array $item): array
{
	$ready = choosology_news_table_ready($db);
	if (!$ready[0]) {
		return array('ok' => false, 'error' => 'The news table was not found.');
	}
	$schema = choosology_news_schema($db);
	if (empty($schema['has_body']) && empty($schema['has_text'])) {
		return array('ok' => false, 'error' => 'The news table has no body/text column.');
	}
	$id = (int) $item['id'];
	$values = array('headline' => (string) $item['headline']);
	if (!empty($schema['has_body'])) {
		$values['body'] = (string) $item['body'];
	}
	if (!empty($schema['has_text'])) {
		$values['text'] = (string) $item['body'];
	}
	if (!empty($schema['has_by'])) {
		$values['by'] = (string) $item['by'];
	}
	$quoted = array();
	foreach ($values as $column => $value) {
		$quoted['`' . $column . '`'] = "'" . mysqli_real_escape_string($db, $value) . "'";
	}

	if ($id > 0) {
		$exists = mysqli_query($db, "SELECT id FROM news WHERE id = $id LIMIT 1");
		if (!$exists || mysqli_num_rows($exists) === 0) {
			return array('ok' => false, 'error' => 'News item not found.');
		}
		$sets = array();
		foreach ($quoted as $column => $value) {
			$sets[] = "$column = $value";
		}
		if (!mysqli_query($db, 'UPDATE news SET ' . implode(', ', $sets) . " WHERE id = $id LIMIT 1")) {
			return array('ok' => false, 'error' => 'Could not save the news item.');
		}
		return array('ok' => true, 'kind' => 'news', 'id' => $id, 'created' => false);
	}

	if (!empty($schema['has_whenposted'])) {
		$quoted['`whenposted`'] = 'NOW()';
	}
	$sql = 'INSERT INTO news (' . implode(', ', array_keys($quoted)) . ') VALUES (' . implode(', ', $quoted) . ')';
	if (!mysqli_query($db, $sql)) {
		return array('ok' => false, 'error' => 'Could not add the news item.');
	}
	return array('ok' => true, 'kind' => 'news', 'id' => (int) mysqli_insert_id($db), 'created' => true);
}

/**
 * Create (id 0) or update a news post or minor update.
 *
 * @param array<string,mixed> $data kind, id, headline, body, by
 * @return array{ok:bool,error?:string,kind?:string,id?:int,created?:bool}
 */
function choosology_news_admin_save(mysqli $db, array $data, string $defaultByline): array
{
	$item = choosology_news_admin_normalize($data, $defaultByline);
	if (empty($item['ok'])) {
		return $item;
	}
	return $item['kind'] === 'update'
		? choosology_news_admin_save_update($db, $item)
		: choosology_news_admin_save_post($db, $item);
}

/**
 * @return array{ok:bool,error?:string,kind?:string,id?:int}
 */
function choosology_news_admin_delete(mysqli $db, string $kind, int $id): array
{
	if ($kind !== 'news' && $kind !== 'update') {
		return array('ok' => false, 'error' => 'Unknown item type.');
	}
	if ($id < 1) {
		return array('ok' => false, 'error' => 'Invalid item id.');
	}
	if ($kind === 'update') {
		if (!choosology_news_admin_updates_ready($db)) {
			return array('ok' => false, 'error' => 'The updates table was not found.');
		}
		$table = 'updates';
		$missing = 'Update not found.';
	} else {
		$ready = choosology_news_table_ready($db);
		if (!$ready[0]) {
			return array('ok' => false, 'error' => 'The news table was not found.');
		}
		$table = 'news';
		$missing = 'News item not found.';
	}
	$ok = mysqli_query($db, "DELETE FROM `$table` WHERE id = $id LIMIT 1");
	if (!$ok || mysqli_affected_rows($db) < 1) {
		return array('ok' => false, 'error' => $missing);
	}
	return array('ok' => true, 'kind' => $kind, 'id' => $id);
}
