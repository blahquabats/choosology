-- Optional: per-user date display format (also auto-added by lib/date-format-helpers.php)
-- Values: mdy (mm/dd/yyyy, default) | dmy (dd/mm/yyyy)
ALTER TABLE `users`
  ADD COLUMN `date_format` varchar(8) NOT NULL DEFAULT 'mdy';
