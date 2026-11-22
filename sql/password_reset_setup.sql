-- Password reset tokens for the Forgot credentials? email flow.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `reset_token` varchar(64) DEFAULT NULL AFTER `chpass`,
  ADD COLUMN IF NOT EXISTS `reset_expires` datetime DEFAULT NULL AFTER `reset_token`;
