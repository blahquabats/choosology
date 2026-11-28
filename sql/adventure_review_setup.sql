-- Adventure review queue + probation / hold flags.
-- Publishing stays frictionless; new public (and restricted) releases enter the
-- Admin review queue. Hold keeps them out of browse until a moderator acts.

ALTER TABLE `advs`
  ADD COLUMN IF NOT EXISTS `review_status` varchar(16) NOT NULL DEFAULT 'none'
    COMMENT 'none|pending|cleared|restricted|rejected' AFTER `avail`,
  ADD COLUMN IF NOT EXISTS `review_hold` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = hidden from public discovery until reviewed' AFTER `review_status`,
  ADD COLUMN IF NOT EXISTS `content_flags` varchar(255) NOT NULL DEFAULT ''
    COMMENT 'comma-separated flags that can force a hold (e.g. mature)' AFTER `review_hold`,
  ADD COLUMN IF NOT EXISTS `featured` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = eligible for the home Newly Featured carousel' AFTER `content_flags`,
  ADD COLUMN IF NOT EXISTS `featured_at` datetime NULL DEFAULT NULL
    COMMENT 'when an admin last marked this adventure featured' AFTER `featured`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `probation` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = new public content is held pending review' AFTER `usertype`;

-- Existing listed adventures are already live; mark them cleared so the queue
-- only covers new publish events after this migration.
UPDATE `advs`
SET `review_status` = 'cleared', `review_hold` = 0
WHERE `avail` IN ('public', 'restricted')
  AND `review_status` = 'none';
