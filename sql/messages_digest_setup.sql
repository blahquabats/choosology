-- Adventure activity digest preferences (idempotent for MySQL 8+ / MariaDB 10.3+)
ALTER TABLE advs ADD COLUMN IF NOT EXISTS digest_notify VARCHAR(16) NOT NULL DEFAULT 'off';
ALTER TABLE advs ADD COLUMN IF NOT EXISTS digest_last_sent DATETIME NULL DEFAULT NULL;
