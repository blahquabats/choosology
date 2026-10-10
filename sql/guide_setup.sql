-- Lab guide (mascot tutorials). Also created by lib/guide-helpers.php.
-- Seed rows are inserted by choosology_guide_seed_defaults() the first time
-- the helper runs, and only when that key is missing. Do not re-import this
-- file over a database whose mascot copy has been edited.

ALTER TABLE `users`
  ADD COLUMN `guide_enabled` tinyint(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS guide_mascots (
  id int unsigned NOT NULL AUTO_INCREMENT,
  mascot_key varchar(64) NOT NULL,
  display_name varchar(80) NOT NULL,
  enabled tinyint(1) NOT NULL DEFAULT 1,
  is_default tinyint(1) NOT NULL DEFAULT 0,
  theme_json text NOT NULL,
  lines_json mediumtext NOT NULL,
  portraits_json text NOT NULL,
  sort_order int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY guide_mascots_key (mascot_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guide_interactions (
  id int unsigned NOT NULL AUTO_INCREMENT,
  interaction_key varchar(64) NOT NULL,
  title varchar(120) NOT NULL,
  event_key varchar(64) NOT NULL,
  feature_key varchar(64) NOT NULL DEFAULT '',
  surface varchar(16) NOT NULL DEFAULT 'both',
  audience varchar(16) NOT NULL DEFAULT 'both',
  mascot_key varchar(64) NOT NULL DEFAULT '',
  frequency varchar(24) NOT NULL DEFAULT 'once',
  priority int NOT NULL DEFAULT 100,
  enabled tinyint(1) NOT NULL DEFAULT 1,
  beats_json mediumtext NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY guide_interactions_key (interaction_key),
  KEY guide_interactions_event (event_key, enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guide_seen (
  id int unsigned NOT NULL AUTO_INCREMENT,
  subject_key varchar(128) NOT NULL,
  feature_key varchar(64) NOT NULL,
  surface varchar(16) NOT NULL,
  first_seen_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY guide_seen_once (subject_key, feature_key, surface)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guide_progress (
  id int unsigned NOT NULL AUTO_INCREMENT,
  subject_key varchar(128) NOT NULL,
  interaction_key varchar(64) NOT NULL,
  status varchar(24) NOT NULL,
  beat_id varchar(64) NOT NULL DEFAULT '',
  snooze_until datetime DEFAULT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY guide_progress_once (subject_key, interaction_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
