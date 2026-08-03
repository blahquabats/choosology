<?php
/**
 * Shared helpers for lab notes: `news` articles and one-line `updates` patch notes.
 */

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auxfuncs.php';

/** Home mixed feed length (news + updates). */
const CHOOSOLOGY_HOME_FEED_LIMIT = 8;

/** News-tab updates page size. */
const CHOOSOLOGY_UPDATES_PAGE_SIZE = 15;

/**
 * @return array{0:bool,1?:string}
 */
function choosology_updates_table_ready(mysqli $db): array
{
	$chk = @mysqli_query($db, "SHOW TABLES LIKE 'updates'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array(false, 'The <code>updates</code> table was not found. Run <code>choosology-schema.sql</code> or <code>sql/updates_setup.sql</code>.');
	}
	return array(true);
}

function choosology_count_updates(mysqli $db): int
{
	$r = mysqli_query($db, 'SELECT COUNT(*) AS c FROM updates');
	if (!$r) {
		return 0;
	}
	$row = mysqli_fetch_assoc($r);
	return isset($row['c']) ? (int) $row['c'] : 0;
}

/**
 * @return list<array{id:int,text:string,whenposted:string}>
 */
function choosology_fetch_updates(mysqli $db, int $limit, int $offset = 0): array
{
	$limit = max(1, min(100, $limit));
	$offset = max(0, $offset);
	$sql = "SELECT id, `text`, whenposted
		FROM updates
		ORDER BY COALESCE(whenposted, '1970-01-01 00:00:00') DESC, id DESC
		LIMIT {$limit} OFFSET {$offset}";
	$rows = runquery_assoc($sql);
	if (!is_array($rows)) {
		return array();
	}
	$out = array();
	foreach ($rows as $row) {
		$out[] = array(
			'id' => (int) ($row['id'] ?? 0),
			'text' => (string) ($row['text'] ?? ''),
			'whenposted' => (string) ($row['whenposted'] ?? ''),
		);
	}
	return $out;
}

/**
 * Format a DB datetime for feed display (e.g. "Jun 3, 2020").
 */
function choosology_feed_date_label(string $stampRaw): string
{
	$t = trim($stampRaw);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($t);
	if ($ts <= 0) {
		return '';
	}
	return date('M j, Y', $ts);
}

/**
 * ISO-8601 for <time datetime>, or empty.
 */
function choosology_feed_date_iso(string $stampRaw): string
{
	$t = trim($stampRaw);
	if ($t === '' || $t === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($t);
	if ($ts <= 0) {
		return '';
	}
	return date('c', $ts);
}

/**
 * Mixed recent feed for Home: news headlines + one-line updates, newest first.
 *
 * @return list<array{type:string,id:int,text:string,whenposted:string,href:?string}>
 */
function choosology_build_recent_feed(mysqli $db, int $limit = CHOOSOLOGY_HOME_FEED_LIMIT): array
{
	$limit = max(1, min(40, $limit));
	$pool = array();

	$newsChk = @mysqli_query($db, "SHOW TABLES LIKE 'news'");
	if ($newsChk && mysqli_num_rows($newsChk) > 0) {
		$newsRows = runquery_assoc(
			"SELECT id, headline, whenposted
			 FROM news
			 ORDER BY COALESCE(whenposted, '1970-01-01 00:00:00') DESC, id DESC
			 LIMIT {$limit}"
		);
		if (is_array($newsRows)) {
			foreach ($newsRows as $row) {
				$pool[] = array(
					'type' => 'news',
					'id' => (int) ($row['id'] ?? 0),
					'text' => (string) ($row['headline'] ?? ''),
					'whenposted' => (string) ($row['whenposted'] ?? ''),
					'href' => '#/news/' . (int) ($row['id'] ?? 0),
				);
			}
		}
	}

	$updReady = choosology_updates_table_ready($db);
	if ($updReady[0]) {
		foreach (choosology_fetch_updates($db, $limit, 0) as $row) {
			$pool[] = array(
				'type' => 'update',
				'id' => (int) $row['id'],
				'text' => (string) $row['text'],
				'whenposted' => (string) $row['whenposted'],
				'href' => null,
			);
		}
	}

	usort($pool, static function (array $a, array $b): int {
		$ta = strtotime($a['whenposted'] ?? '') ?: 0;
		$tb = strtotime($b['whenposted'] ?? '') ?: 0;
		if ($ta === $tb) {
			return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
		}
		return $tb <=> $ta;
	});

	return array_slice($pool, 0, $limit);
}

/**
 * Render one mixed-feed / updates-list row.
 *
 * @param array{type:string,id?:int,text:string,whenposted:string,href?:?string} $item
 */
function choosology_echo_feed_item(array $item, string $extraClass = '', bool $canManage = false): void
{
	$type = ($item['type'] ?? '') === 'news' ? 'news' : 'update';
	$text = htmlspecialchars((string) ($item['text'] ?? ''), ENT_QUOTES, 'UTF-8');
	$stamp = (string) ($item['whenposted'] ?? '');
	$label = htmlspecialchars(choosology_feed_date_label($stamp), ENT_QUOTES, 'UTF-8');
	$iso = choosology_feed_date_iso($stamp);
	$badge = $type === 'news' ? 'News' : 'Update';
	$cls = 'lab-feed-item lab-feed-item--' . $type;
	if ($extraClass !== '') {
		$cls .= ' ' . $extraClass;
	}
	$href = isset($item['href']) ? $item['href'] : null;
	$tag = ($href !== null && $href !== '') ? 'a' : 'div';
	$hrefAttr = ($tag === 'a') ? ' href="' . htmlspecialchars((string) $href, ENT_QUOTES, 'UTF-8') . '"' : '';
	$id = (int) ($item['id'] ?? 0);
	$showAdmin = $canManage && $type === 'update' && $id > 0;

	if ($showAdmin) {
		echo '<div class="lab-feed-item-row">';
	}

	echo '<' . $tag . ' class="' . $cls . '"' . $hrefAttr . '>';
	echo '<span class="lab-feed-badge lab-feed-badge--' . $type . '">' . $badge . '</span>';
	if ($label !== '') {
		$dt = $iso !== '' ? ' datetime="' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . '"' : '';
		echo '<time class="lab-feed-date"' . $dt . '>' . $label . '</time>';
	}
	echo '<span class="lab-feed-text">' . $text . '</span>';
	echo '</' . $tag . '>';

	if ($showAdmin) {
		echo '<div class="lab-feed-item-admin">';
		echo '<button type="button" class="news-admin-edit-update" data-id="' . $id . '" data-text="' . $text . '">Edit</button>';
		echo '<button type="button" class="news-admin-delete-update" data-id="' . $id . '">Delete</button>';
		echo '</div></div>';
	}
}

/**
 * Echo the News-tab paginated updates list (or fragment).
 */
function choosology_echo_updates_feed(mysqli $db, int $page = 1, bool $canManage = false): void
{
	$ready = choosology_updates_table_ready($db);
	if (!$ready[0]) {
		echo '<p class="news-empty">' . ($ready[1] ?? 'Updates unavailable.') . '</p>';
		return;
	}

	$pageSize = CHOOSOLOGY_UPDATES_PAGE_SIZE;
	$total = choosology_count_updates($db);
	$totalPages = max(1, (int) ceil($total / $pageSize));
	$page = max(1, min($totalPages, $page));
	$offset = ($page - 1) * $pageSize;
	$rows = choosology_fetch_updates($db, $pageSize, $offset);

	echo '<div class="updates-feed" data-page="' . $page . '" data-pages="' . $totalPages . '">';
	if ($total === 0) {
		echo '<p class="news-empty">No patch notes yet. Add rows to the <code>updates</code> table or run <code>sql/updates_setup.sql</code>.</p>';
	} else {
		echo '<div class="lab-feed-list lab-feed-list--updates">';
		foreach ($rows as $row) {
			choosology_echo_feed_item(array(
				'type' => 'update',
				'id' => $row['id'],
				'text' => $row['text'],
				'whenposted' => $row['whenposted'],
				'href' => null,
			), '', $canManage);
		}
		echo '</div>';
		if ($totalPages > 1) {
			echo '<nav class="updates-pager" aria-label="Updates pages">';
			if ($page > 1) {
				echo '<button type="button" class="updates-pager-btn" data-updates-page="' . ($page - 1) . '">Prev</button>';
			} else {
				echo '<span class="updates-pager-btn updates-pager-btn--disabled">Prev</span>';
			}
			echo '<span class="updates-pager-status">Page ' . $page . ' of ' . $totalPages . '</span>';
			if ($page < $totalPages) {
				echo '<button type="button" class="updates-pager-btn" data-updates-page="' . ($page + 1) . '">Next</button>';
			} else {
				echo '<span class="updates-pager-btn updates-pager-btn--disabled">Next</span>';
			}
			echo '</nav>';
		}
	}
	echo '</div>';
}
