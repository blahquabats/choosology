-- My Office: décor catalog, inventory, loadout, trinket pedestals
-- (also auto-created by lib/office-helpers.php)

CREATE TABLE IF NOT EXISTS `office_catalog` (
  `item_key` varchar(64) NOT NULL,
  `kind` varchar(16) NOT NULL,
  `slot` varchar(32) DEFAULT NULL,
  `label` varchar(128) NOT NULL,
  `blurb` varchar(255) NOT NULL DEFAULT '',
  `price` int NOT NULL DEFAULT 0,
  `asset_path` varchar(255) NOT NULL DEFAULT '',
  `sort` int NOT NULL DEFAULT 0,
  `unlock_rule` varchar(64) NOT NULL DEFAULT 'shop',
  PRIMARY KEY (`item_key`),
  KEY `office_catalog_kind_slot` (`kind`, `slot`),
  KEY `office_catalog_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `office_inventory` (
  `uname` varchar(45) NOT NULL,
  `item_key` varchar(64) NOT NULL,
  `acquired_at` datetime NOT NULL,
  `source` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`uname`, `item_key`),
  KEY `office_inventory_item` (`item_key`),
  KEY `office_inventory_acquired` (`uname`, `acquired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `office_loadout` (
  `uname` varchar(45) NOT NULL,
  `slot` varchar(32) NOT NULL,
  `item_key` varchar(64) NOT NULL,
  PRIMARY KEY (`uname`, `slot`),
  KEY `office_loadout_item` (`item_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `office_trinket_slots` (
  `uname` varchar(45) NOT NULL,
  `pedestal_index` tinyint unsigned NOT NULL,
  `item_key` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`uname`, `pedestal_index`),
  KEY `office_trinket_slots_item` (`item_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
