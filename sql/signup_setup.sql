-- Signup preferences on users (idempotent-ish: run once per environment).
-- newsletter: periodic Lab bulletins (1–3 / month), opt-in
-- welcome_pending: send a single confirmation / first-steps email when mail is configured

ALTER TABLE `users`
  ADD COLUMN `newsletter` tinyint(1) NOT NULL DEFAULT 0 AFTER `fbshow`,
  ADD COLUMN `welcome_pending` tinyint(1) NOT NULL DEFAULT 0 AFTER `newsletter`;
