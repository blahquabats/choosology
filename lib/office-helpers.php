<?php
/**
 * My Office — décor catalog, inventory, loadout, trinket pedestals, shop.
 */

require_once __DIR__ . '/datascrip-helpers.php';

/** Décor slot keys (exactly one equipped each). */
function choosology_office_decor_slots(): array
{
	return array('wallpaper', 'flooring', 'computer', 'desk', 'bookshelf', 'corner', 'lighting', 'window');
}

/** Number of office display pedestals. */
function choosology_office_pedestal_count(): int
{
	return 3;
}

/**
 * Canonical catalog (seeded into office_catalog).
 *
 * @return list<array{item_key:string,kind:string,slot:?string,label:string,blurb:string,price:int,asset_path:string,sort:int,unlock_rule:string}>
 */
function choosology_office_catalog_defs(): array
{
	$defs = array();
	$sort = 0;

	$decor = array(
		'wallpaper' => array(
			array('plain', 'Plain Lab Green', 'Institutional sage wash.', 0, 'starter'),
			array('blueprint', 'Blueprint Grid', 'Faint graph-paper wall.', 25, 'shop'),
			array('cork', 'Cork Pinboard', 'Warm cork with pin marks.', 40, 'shop'),
		),
		'flooring' => array(
			array('linoleum', 'Lab Linoleum', 'Speckled institutional floor.', 0, 'starter'),
			array('tile', 'Checker Tile', 'Muted two-tone tile.', 30, 'shop'),
			array('wood', 'Oak Strip', 'Worn office hardwood.', 55, 'shop'),
		),
		'computer' => array(
			array('crt', 'Basic CRT', 'Amber terminal glow.', 0, 'starter'),
			array('beige', 'Beige Tower Set', 'Late-90s lab workstation.', 35, 'shop'),
			array('slate', 'Slate Terminal', 'Cool slate CRT stack.', 60, 'shop'),
		),
		'desk' => array(
			array('beige', 'Beige Desk', 'Simple laminate desk.', 0, 'starter'),
			array('metal', 'Metal Lab Bench', 'Steel-edged work surface.', 45, 'shop'),
			array('oak', 'Oak Writing Desk', 'Warm wood with drawer pulls.', 70, 'shop'),
		),
		'bookshelf' => array(
			array('empty', 'Sparse Shelf', 'Mostly empty metal shelf.', 0, 'starter'),
			array('filled', 'Filled Stack', 'Binders and field manuals.', 40, 'shop'),
			array('glass', 'Glass Case Shelf', 'Display-friendly cabinet.', 75, 'shop'),
		),
		'corner' => array(
			array('sparse', 'Sparse Corner', 'A lonely box and cable.', 0, 'starter'),
			array('plant', 'Corner Plant', 'A hardy lab fern.', 20, 'shop'),
			array('crates', 'Archive Crates', 'Stacked specimen crates.', 50, 'shop'),
		),
		'lighting' => array(
			array('fluorescent', 'Overhead Fluorescents', 'Cool institutional tubes.', 0, 'starter'),
			array('desk_lamp', 'Desk Lamp', 'Warm cone of desk light.', 28, 'shop'),
			array('spot', 'Gallery Spots', 'Soft amber accent spots.', 65, 'shop'),
		),
		'window' => array(
			array('courtyard', 'Gray Courtyard', 'Muted concrete courtyard.', 0, 'starter'),
			array('dusk', 'Dusk Skyline', 'Soft evening lab campus.', 45, 'shop'),
			array('rain', 'Rain Pane', 'Streaked glass, gray day.', 55, 'shop'),
		),
	);

	foreach ($decor as $slot => $variants) {
		foreach ($variants as $v) {
			$defs[] = array(
				'item_key' => $slot . '__' . $v[0],
				'kind' => 'decor',
				'slot' => $slot,
				'label' => $v[1],
				'blurb' => $v[2],
				'price' => (int) $v[3],
				'asset_path' => 'images/office/' . $slot . '__' . $v[0] . '.png',
				'sort' => $sort++,
				'unlock_rule' => $v[4],
			);
		}
	}

	$trinkets = array(
		array('trinket__lab_initiate', 'Lab Initiate Plaque', 'First draft credential.', 'degree:lab_initiate'),
		array('trinket__screenwright', 'Screenwright Quill', 'A tiny saved-screen token.', 'degree:screenwright'),
		array('trinket__field_release', 'Field Release Pin', 'Public release commemorative.', 'degree:field_release'),
		array('trinket__clipboard_clerk', 'Clipboard Clip', 'Desk clipboard miniature.', 'degree:clipboard_clerk'),
		array('trinket__scribbler', 'Scribbler Pad', 'Pocket note pad trophy.', 'degree:scribbler'),
		array('trinket__taskmaster', 'Taskmaster Check', 'A finished checklist badge.', 'degree:taskmaster'),
		array('trinket__results_analyst', 'Results Chart', 'Tiny metrics wall chart.', 'degree:results_analyst'),
		array('trinket__terminal_specimen', 'Terminal Specimen', 'First catalogued end screen.', 'ending_find'),
	);
	foreach ($trinkets as $t) {
		$defs[] = array(
			'item_key' => $t[0],
			'kind' => 'trinket',
			'slot' => null,
			'label' => $t[1],
			'blurb' => $t[2],
			'price' => 0,
			'asset_path' => 'images/office/' . $t[0] . '.png',
			'sort' => $sort++,
			'unlock_rule' => $t[3],
		);
	}

	return $defs;
}

function choosology_office_ensure_schema(mysqli $db): void
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	try {
		mysqli_query(
			$db,
			'CREATE TABLE IF NOT EXISTS office_catalog (
				item_key varchar(64) NOT NULL,
				kind varchar(16) NOT NULL,
				slot varchar(32) DEFAULT NULL,
				label varchar(128) NOT NULL,
				blurb varchar(255) NOT NULL DEFAULT \'\',
				price int NOT NULL DEFAULT 0,
				asset_path varchar(255) NOT NULL DEFAULT \'\',
				sort int NOT NULL DEFAULT 0,
				unlock_rule varchar(64) NOT NULL DEFAULT \'shop\',
				PRIMARY KEY (item_key),
				KEY office_catalog_kind_slot (kind, slot),
				KEY office_catalog_sort (sort)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		mysqli_query(
			$db,
			'CREATE TABLE IF NOT EXISTS office_inventory (
				uname varchar(45) NOT NULL,
				item_key varchar(64) NOT NULL,
				acquired_at datetime NOT NULL,
				source varchar(64) NOT NULL DEFAULT \'\',
				PRIMARY KEY (uname, item_key),
				KEY office_inventory_item (item_key),
				KEY office_inventory_acquired (uname, acquired_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		mysqli_query(
			$db,
			'CREATE TABLE IF NOT EXISTS office_loadout (
				uname varchar(45) NOT NULL,
				slot varchar(32) NOT NULL,
				item_key varchar(64) NOT NULL,
				PRIMARY KEY (uname, slot),
				KEY office_loadout_item (item_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		mysqli_query(
			$db,
			'CREATE TABLE IF NOT EXISTS office_trinket_slots (
				uname varchar(45) NOT NULL,
				pedestal_index tinyint unsigned NOT NULL,
				item_key varchar(64) DEFAULT NULL,
				PRIMARY KEY (uname, pedestal_index),
				KEY office_trinket_slots_item (item_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		choosology_office_seed_catalog($db);
	} catch (Throwable $e) {
		$done = false;
	}
}

function choosology_office_seed_catalog(mysqli $db): void
{
	foreach (choosology_office_catalog_defs() as $row) {
		$k = mysqli_real_escape_string($db, $row['item_key']);
		$kind = mysqli_real_escape_string($db, $row['kind']);
		$slot = $row['slot'] === null ? 'NULL' : ("'" . mysqli_real_escape_string($db, $row['slot']) . "'");
		$label = mysqli_real_escape_string($db, $row['label']);
		$blurb = mysqli_real_escape_string($db, $row['blurb']);
		$price = (int) $row['price'];
		$asset = mysqli_real_escape_string($db, $row['asset_path']);
		$sort = (int) $row['sort'];
		$rule = mysqli_real_escape_string($db, $row['unlock_rule']);
		mysqli_query(
			$db,
			"INSERT INTO office_catalog (item_key, kind, slot, label, blurb, price, asset_path, sort, unlock_rule)
			 VALUES ('$k', '$kind', $slot, '$label', '$blurb', $price, '$asset', $sort, '$rule')
			 ON DUPLICATE KEY UPDATE
				kind = VALUES(kind),
				slot = VALUES(slot),
				label = VALUES(label),
				blurb = VALUES(blurb),
				price = VALUES(price),
				asset_path = VALUES(asset_path),
				sort = VALUES(sort),
				unlock_rule = VALUES(unlock_rule)"
		);
	}
}

/**
 * @return array<string, array{item_key:string,kind:string,slot:?string,label:string,blurb:string,price:int,asset_path:string,sort:int,unlock_rule:string}>
 */
function choosology_office_catalog_map(mysqli $db): array
{
	choosology_office_ensure_schema($db);
	$map = array();
	$r = mysqli_query($db, 'SELECT item_key, kind, slot, label, blurb, price, asset_path, sort, unlock_rule FROM office_catalog ORDER BY sort ASC, item_key ASC');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$key = (string) ($row['item_key'] ?? '');
			if ($key === '') {
				continue;
			}
			$map[$key] = array(
				'item_key' => $key,
				'kind' => (string) ($row['kind'] ?? ''),
				'slot' => isset($row['slot']) && $row['slot'] !== null && $row['slot'] !== '' ? (string) $row['slot'] : null,
				'label' => (string) ($row['label'] ?? ''),
				'blurb' => (string) ($row['blurb'] ?? ''),
				'price' => (int) ($row['price'] ?? 0),
				'asset_path' => (string) ($row['asset_path'] ?? ''),
				'sort' => (int) ($row['sort'] ?? 0),
				'unlock_rule' => (string) ($row['unlock_rule'] ?? ''),
			);
		}
	}
	if (count($map) === 0) {
		foreach (choosology_office_catalog_defs() as $row) {
			$map[$row['item_key']] = $row;
		}
	}
	return $map;
}

function choosology_office_asset_url(string $assetPath): string
{
	$assetPath = ltrim(str_replace('\\', '/', $assetPath), '/');
	if (function_exists('choosology_site_url')) {
		return choosology_site_url($assetPath);
	}
	return '/' . $assetPath;
}

/**
 * @return list<string>
 */
function choosology_office_owned_keys(mysqli $db, string $uname): array
{
	$uname = trim($uname);
	if ($uname === '') {
		return array();
	}
	choosology_office_ensure_schema($db);
	$esc = mysqli_real_escape_string($db, $uname);
	$out = array();
	$r = mysqli_query($db, "SELECT item_key FROM office_inventory WHERE uname = '$esc'");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$k = (string) ($row['item_key'] ?? '');
			if ($k !== '') {
				$out[] = $k;
			}
		}
	}
	return $out;
}

function choosology_office_grant_item(mysqli $db, string $uname, string $itemKey, string $source = 'grant'): bool
{
	$uname = trim($uname);
	$itemKey = trim($itemKey);
	if ($uname === '' || $itemKey === '') {
		return false;
	}
	choosology_office_ensure_schema($db);
	$catalog = choosology_office_catalog_map($db);
	if (!isset($catalog[$itemKey])) {
		return false;
	}
	$escU = mysqli_real_escape_string($db, $uname);
	$escK = mysqli_real_escape_string($db, $itemKey);
	$escS = mysqli_real_escape_string($db, substr($source, 0, 64));
	$ok = mysqli_query(
		$db,
		"INSERT IGNORE INTO office_inventory (uname, item_key, acquired_at, source)
		 VALUES ('$escU', '$escK', NOW(), '$escS')"
	);
	return (bool) $ok && mysqli_affected_rows($db) > 0;
}

/**
 * Grant free starter décor and equip if loadout empty.
 */
function choosology_office_ensure_starter(mysqli $db, string $uname): void
{
	$uname = trim($uname);
	if ($uname === '') {
		return;
	}
	choosology_office_ensure_schema($db);
	$catalog = choosology_office_catalog_map($db);
	foreach ($catalog as $item) {
		if (($item['kind'] ?? '') === 'decor' && ($item['unlock_rule'] ?? '') === 'starter') {
			choosology_office_grant_item($db, $uname, $item['item_key'], 'starter');
		}
	}

	$escU = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM office_loadout WHERE uname = '$escU'");
	$count = 0;
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		$count = (int) ($row['c'] ?? 0);
	}
	if ($count === 0) {
		foreach (choosology_office_decor_slots() as $slot) {
			$starterKey = null;
			foreach ($catalog as $item) {
				if (($item['kind'] ?? '') === 'decor'
					&& ($item['slot'] ?? '') === $slot
					&& ($item['unlock_rule'] ?? '') === 'starter') {
					$starterKey = $item['item_key'];
					break;
				}
			}
			if ($starterKey === null) {
				continue;
			}
			$escS = mysqli_real_escape_string($db, $slot);
			$escK = mysqli_real_escape_string($db, $starterKey);
			mysqli_query(
				$db,
				"INSERT INTO office_loadout (uname, slot, item_key) VALUES ('$escU', '$escS', '$escK')
				 ON DUPLICATE KEY UPDATE item_key = VALUES(item_key)"
			);
		}
	}

	$n = choosology_office_pedestal_count();
	for ($i = 0; $i < $n; $i++) {
		mysqli_query(
			$db,
			"INSERT IGNORE INTO office_trinket_slots (uname, pedestal_index, item_key)
			 VALUES ('$escU', $i, NULL)"
		);
	}
}

/**
 * Grant degree / ending trinkets the user has earned but does not own yet.
 */
function choosology_office_sync_milestone_trinkets(mysqli $db, string $uname): void
{
	$uname = trim($uname);
	if ($uname === '') {
		return;
	}
	choosology_office_ensure_schema($db);
	$catalog = choosology_office_catalog_map($db);

	if (!function_exists('choosology_clipboard_ensure_schema')) {
		require_once __DIR__ . '/clipboard-helpers.php';
	}
	choosology_clipboard_ensure_schema($db);
	$escU = mysqli_real_escape_string($db, $uname);
	$earned = array();
	$r = mysqli_query($db, "SELECT achievement_key FROM user_achievements WHERE uname = '$escU'");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$earned[(string) $row['achievement_key']] = true;
		}
	}

	foreach ($catalog as $item) {
		if (($item['kind'] ?? '') !== 'trinket') {
			continue;
		}
		$rule = (string) ($item['unlock_rule'] ?? '');
		if (strpos($rule, 'degree:') === 0) {
			$deg = substr($rule, strlen('degree:'));
			if ($deg !== '' && isset($earned[$deg])) {
				choosology_office_grant_item($db, $uname, $item['item_key'], 'degree:' . $deg);
			}
		}
	}

	/* Ending-find specimen: any row in ending_finds */
	if (!function_exists('choosology_ensure_ending_finds_table')) {
		require_once __DIR__ . '/ending-helpers.php';
	}
	choosology_ensure_ending_finds_table($db);
	$ef = mysqli_query($db, "SELECT 1 FROM ending_finds WHERE uname = '$escU' LIMIT 1");
	if ($ef && mysqli_fetch_row($ef)) {
		choosology_office_grant_item($db, $uname, 'trinket__terminal_specimen', 'ending_find');
	}
}

/**
 * Grant a degree-linked trinket when a Degree is newly earned.
 */
function choosology_office_on_achievement(mysqli $db, string $uname, string $achievementKey): void
{
	$uname = trim($uname);
	$achievementKey = trim($achievementKey);
	if ($uname === '' || $achievementKey === '') {
		return;
	}
	choosology_office_ensure_schema($db);
	$itemKey = 'trinket__' . $achievementKey;
	$catalog = choosology_office_catalog_map($db);
	if (isset($catalog[$itemKey])) {
		choosology_office_grant_item($db, $uname, $itemKey, 'degree:' . $achievementKey);
	}
}

/**
 * Grant terminal specimen on first ending find.
 */
function choosology_office_on_ending_find(mysqli $db, string $uname): void
{
	$uname = trim($uname);
	if ($uname === '') {
		return;
	}
	choosology_office_ensure_schema($db);
	choosology_office_grant_item($db, $uname, 'trinket__terminal_specimen', 'ending_find');
}

/**
 * @return array<string, string> slot => item_key
 */
function choosology_office_loadout(mysqli $db, string $uname): array
{
	$uname = trim($uname);
	$out = array();
	if ($uname === '') {
		return $out;
	}
	choosology_office_ensure_schema($db);
	$esc = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query($db, "SELECT slot, item_key FROM office_loadout WHERE uname = '$esc'");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$slot = (string) ($row['slot'] ?? '');
			$key = (string) ($row['item_key'] ?? '');
			if ($slot !== '' && $key !== '') {
				$out[$slot] = $key;
			}
		}
	}
	return $out;
}

/**
 * @return array<int, ?string> pedestal index => item_key|null
 */
function choosology_office_trinket_placement(mysqli $db, string $uname): array
{
	$uname = trim($uname);
	$n = choosology_office_pedestal_count();
	$out = array();
	for ($i = 0; $i < $n; $i++) {
		$out[$i] = null;
	}
	if ($uname === '') {
		return $out;
	}
	choosology_office_ensure_schema($db);
	$esc = mysqli_real_escape_string($db, $uname);
	$r = mysqli_query($db, "SELECT pedestal_index, item_key FROM office_trinket_slots WHERE uname = '$esc'");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$idx = (int) ($row['pedestal_index'] ?? -1);
			if ($idx < 0 || $idx >= $n) {
				continue;
			}
			$k = isset($row['item_key']) && $row['item_key'] !== null && $row['item_key'] !== ''
				? (string) $row['item_key']
				: null;
			$out[$idx] = $k;
		}
	}
	return $out;
}

/**
 * Public JSON-friendly state for the office UI.
 *
 * @return array<string, mixed>
 */
function choosology_office_state(mysqli $db, string $uname): array
{
	$uname = trim($uname);
	choosology_office_ensure_schema($db);
	choosology_office_ensure_starter($db, $uname);
	choosology_office_sync_milestone_trinkets($db, $uname);

	$catalog = choosology_office_catalog_map($db);
	$owned = array_fill_keys(choosology_office_owned_keys($db, $uname), true);
	$loadout = choosology_office_loadout($db, $uname);
	$pedestals = choosology_office_trinket_placement($db, $uname);
	$balance = choosology_datascrip_balance($db, $uname);

	$catalogOut = array();
	foreach ($catalog as $item) {
		$key = $item['item_key'];
		$isOwned = isset($owned[$key]);
		$catalogOut[] = array(
			'item_key' => $key,
			'kind' => $item['kind'],
			'slot' => $item['slot'],
			'label' => $item['label'],
			'blurb' => $item['blurb'],
			'price' => (int) $item['price'],
			'asset_url' => choosology_office_asset_url($item['asset_path']),
			'unlock_rule' => $item['unlock_rule'],
			'owned' => $isOwned,
			'buyable' => !$isOwned && $item['kind'] === 'decor' && $item['unlock_rule'] === 'shop' && (int) $item['price'] > 0,
		);
	}

	$layers = array();
	foreach (choosology_office_decor_slots() as $slot) {
		$key = $loadout[$slot] ?? null;
		$layers[$slot] = null;
		if ($key !== null && isset($catalog[$key])) {
			$layers[$slot] = array(
				'item_key' => $key,
				'label' => $catalog[$key]['label'],
				'asset_url' => choosology_office_asset_url($catalog[$key]['asset_path']),
			);
		}
	}

	$pedOut = array();
	foreach ($pedestals as $idx => $key) {
		$entry = array('index' => (int) $idx, 'item_key' => null, 'label' => null, 'asset_url' => null);
		if ($key !== null && isset($catalog[$key])) {
			$entry['item_key'] = $key;
			$entry['label'] = $catalog[$key]['label'];
			$entry['asset_url'] = choosology_office_asset_url($catalog[$key]['asset_path']);
		}
		$pedOut[] = $entry;
	}

	$trinkets = array();
	foreach ($catalogOut as $item) {
		if ($item['kind'] === 'trinket' && !empty($item['owned'])) {
			$trinkets[] = $item;
		}
	}

	return array(
		'ok' => 1,
		'balance' => $balance,
		'balance_html' => choosology_datascrip_format_html($balance),
		'slots' => choosology_office_decor_slots(),
		'pedestal_count' => choosology_office_pedestal_count(),
		'layers' => $layers,
		'pedestals' => $pedOut,
		'catalog' => $catalogOut,
		'trinkets' => $trinkets,
		'trophy_bay_url' => choosology_office_asset_url('images/office/trophy_bay__tile.png'),
	);
}

/**
 * @return array{ok:bool,error?:string,balance?:int,state?:array}
 */
function choosology_office_buy(mysqli $db, string $uname, string $itemKey): array
{
	$uname = trim($uname);
	$itemKey = trim($itemKey);
	if ($uname === '' || $itemKey === '') {
		return array('ok' => false, 'error' => 'Invalid request.');
	}
	choosology_office_ensure_schema($db);
	choosology_office_ensure_starter($db, $uname);
	$catalog = choosology_office_catalog_map($db);
	if (!isset($catalog[$itemKey])) {
		return array('ok' => false, 'error' => 'Unknown item.');
	}
	$item = $catalog[$itemKey];
	if (($item['kind'] ?? '') !== 'decor' || ($item['unlock_rule'] ?? '') !== 'shop') {
		return array('ok' => false, 'error' => 'Item is not for sale.');
	}
	$price = (int) ($item['price'] ?? 0);
	if ($price <= 0) {
		return array('ok' => false, 'error' => 'Item is not for sale.');
	}
	$owned = array_fill_keys(choosology_office_owned_keys($db, $uname), true);
	if (isset($owned[$itemKey])) {
		return array('ok' => false, 'error' => 'Already owned.', 'balance' => choosology_datascrip_balance($db, $uname));
	}

	$idem = 'office_shop:' . $uname . ':' . $itemKey;
	$result = choosology_datascrip_apply(
		$db,
		$uname,
		-$price,
		'office_shop',
		'Office shop: ' . $item['label'],
		null,
		$idem
	);
	if (empty($result['ok'])) {
		return array(
			'ok' => false,
			'error' => (string) ($result['error'] ?? 'Purchase failed.'),
			'balance' => (int) ($result['balance'] ?? choosology_datascrip_balance($db, $uname)),
		);
	}
	choosology_office_grant_item($db, $uname, $itemKey, 'shop');
	/* Auto-equip purchased décor into its slot. */
	if (!empty($item['slot'])) {
		choosology_office_equip_decor($db, $uname, (string) $item['slot'], $itemKey);
	}
	$state = choosology_office_state($db, $uname);
	return array('ok' => true, 'balance' => (int) $state['balance'], 'state' => $state);
}

/**
 * @return array{ok:bool,error?:string,state?:array}
 */
function choosology_office_equip_decor(mysqli $db, string $uname, string $slot, string $itemKey): array
{
	$uname = trim($uname);
	$slot = trim($slot);
	$itemKey = trim($itemKey);
	if ($uname === '' || $slot === '' || $itemKey === '') {
		return array('ok' => false, 'error' => 'Invalid request.');
	}
	if (!in_array($slot, choosology_office_decor_slots(), true)) {
		return array('ok' => false, 'error' => 'Invalid slot.');
	}
	choosology_office_ensure_schema($db);
	$catalog = choosology_office_catalog_map($db);
	if (!isset($catalog[$itemKey]) || ($catalog[$itemKey]['kind'] ?? '') !== 'decor' || ($catalog[$itemKey]['slot'] ?? '') !== $slot) {
		return array('ok' => false, 'error' => 'Item does not fit that slot.');
	}
	$owned = array_fill_keys(choosology_office_owned_keys($db, $uname), true);
	if (!isset($owned[$itemKey])) {
		return array('ok' => false, 'error' => 'Item not in inventory.');
	}
	$escU = mysqli_real_escape_string($db, $uname);
	$escS = mysqli_real_escape_string($db, $slot);
	$escK = mysqli_real_escape_string($db, $itemKey);
	mysqli_query(
		$db,
		"INSERT INTO office_loadout (uname, slot, item_key) VALUES ('$escU', '$escS', '$escK')
		 ON DUPLICATE KEY UPDATE item_key = VALUES(item_key)"
	);
	return array('ok' => true, 'state' => choosology_office_state($db, $uname));
}

/**
 * @return array{ok:bool,error?:string,state?:array}
 */
function choosology_office_place_trinket(mysqli $db, string $uname, int $pedestalIndex, ?string $itemKey): array
{
	$uname = trim($uname);
	$n = choosology_office_pedestal_count();
	if ($uname === '' || $pedestalIndex < 0 || $pedestalIndex >= $n) {
		return array('ok' => false, 'error' => 'Invalid pedestal.');
	}
	choosology_office_ensure_schema($db);
	choosology_office_ensure_starter($db, $uname);

	$escU = mysqli_real_escape_string($db, $uname);
	if ($itemKey === null || $itemKey === '') {
		mysqli_query(
			$db,
			"INSERT INTO office_trinket_slots (uname, pedestal_index, item_key)
			 VALUES ('$escU', $pedestalIndex, NULL)
			 ON DUPLICATE KEY UPDATE item_key = NULL"
		);
		return array('ok' => true, 'state' => choosology_office_state($db, $uname));
	}

	$itemKey = trim($itemKey);
	$catalog = choosology_office_catalog_map($db);
	if (!isset($catalog[$itemKey]) || ($catalog[$itemKey]['kind'] ?? '') !== 'trinket') {
		return array('ok' => false, 'error' => 'Not a trinket.');
	}
	$owned = array_fill_keys(choosology_office_owned_keys($db, $uname), true);
	if (!isset($owned[$itemKey])) {
		return array('ok' => false, 'error' => 'Trinket not owned.');
	}

	/* Clear from any other pedestal first. */
	$escK = mysqli_real_escape_string($db, $itemKey);
	mysqli_query(
		$db,
		"UPDATE office_trinket_slots SET item_key = NULL
		 WHERE uname = '$escU' AND item_key = '$escK'"
	);
	mysqli_query(
		$db,
		"INSERT INTO office_trinket_slots (uname, pedestal_index, item_key)
		 VALUES ('$escU', $pedestalIndex, '$escK')
		 ON DUPLICATE KEY UPDATE item_key = VALUES(item_key)"
	);
	return array('ok' => true, 'state' => choosology_office_state($db, $uname));
}
