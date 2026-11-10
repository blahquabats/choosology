-- Admin tools (My Office → RESTRICTED, Lite RESTRICTED, inline comment delete,
-- News Desk) are gated on users.usertype >= 1. News management and
-- delete-any-comment used to be hardcoded to the account named 'The Grasssmith'
-- instead; this keeps that account's access if it was never given the role.
-- Safe to re-run. Promote other admins the same way.

UPDATE `users` SET `usertype` = 1 WHERE `name` = 'The Grasssmith' AND `usertype` < 1;
