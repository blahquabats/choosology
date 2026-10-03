-- Optional: Lite / Classic UI preference (also auto-added by lib/lite-helpers.php)
ALTER TABLE `users`
  ADD COLUMN `ui_mode` varchar(16) NOT NULL DEFAULT 'classic';
