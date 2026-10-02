-- Clipboard pad, todos, checklist dismissals, achievements, and adventure play metrics.
-- Safe to re-run (CREATE IF NOT EXISTS / conditional ALTERs handled in PHP too).

CREATE TABLE IF NOT EXISTS clipboard_pad (
	uname varchar(45) NOT NULL,
	body mediumtext NOT NULL,
	updated_at datetime NOT NULL,
	PRIMARY KEY (uname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clipboard_todos (
	id int unsigned NOT NULL AUTO_INCREMENT,
	uname varchar(45) NOT NULL,
	body varchar(500) NOT NULL,
	done tinyint(1) NOT NULL DEFAULT 0,
	sort_order int NOT NULL DEFAULT 0,
	created_at datetime NOT NULL,
	done_at datetime DEFAULT NULL,
	PRIMARY KEY (id),
	KEY clipboard_todos_user_done (uname, done),
	KEY clipboard_todos_user_sort (uname, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clipboard_checklist (
	uname varchar(45) NOT NULL,
	item_key varchar(64) NOT NULL,
	dismissed tinyint(1) NOT NULL DEFAULT 0,
	completed tinyint(1) NOT NULL DEFAULT 0,
	updated_at datetime NOT NULL,
	PRIMARY KEY (uname, item_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_achievements (
	id int unsigned NOT NULL AUTO_INCREMENT,
	uname varchar(45) NOT NULL,
	achievement_key varchar(64) NOT NULL,
	earned_at datetime NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY user_achievements_user_key (uname, achievement_key),
	KEY user_achievements_user (uname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
