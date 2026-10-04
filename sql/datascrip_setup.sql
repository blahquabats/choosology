-- DataScrip balances + ledger (also auto-created by lib/datascrip-helpers.php)
CREATE TABLE IF NOT EXISTS `datascrip_balances` (
  `uname` varchar(45) NOT NULL,
  `balance` int NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`uname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `datascrip_ledger` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uname` varchar(45) NOT NULL,
  `amount` int NOT NULL,
  `balance_after` int NOT NULL,
  `reason_key` varchar(64) NOT NULL,
  `memo` varchar(255) NOT NULL DEFAULT '',
  `actor` varchar(45) DEFAULT NULL,
  `idempotency_key` varchar(128) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `datascrip_ledger_user_idem` (`uname`, `idempotency_key`),
  KEY `datascrip_ledger_user_created` (`uname`, `created_at`),
  KEY `datascrip_ledger_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
