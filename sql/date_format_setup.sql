-- Optional: per-user date display format (also auto-added by lib/date-format-helpers.php)
-- Values: mdy (mm/dd/yyyy, default) | dmy (dd/mm/yyyy)
--
-- MySQL 8 may reject ALTER on `users` while sql_mode includes NO_ZERO_DATE because
-- legacy `lastlogin` defaults to '0000-00-00 00:00:00'. Relax session mode first:

SET @__choosology_old_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(REPLACE(REPLACE(@@SESSION.sql_mode,
  'NO_ZERO_DATE', ''),
  'NO_ZERO_IN_DATE', ''),
  'STRICT_TRANS_TABLES', ''),
  'STRICT_ALL_TABLES', '');

ALTER TABLE `users`
  ADD COLUMN `date_format` varchar(8) NOT NULL DEFAULT 'mdy';

SET SESSION sql_mode = @__choosology_old_sql_mode;
