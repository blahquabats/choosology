-- Logged-in ending discovery history (session covers anonymous / current visit).
CREATE TABLE IF NOT EXISTS ending_finds (
  id int unsigned NOT NULL AUTO_INCREMENT,
  uname varchar(45) NOT NULL,
  adv int unsigned NOT NULL,
  screen int unsigned NOT NULL,
  found_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ending_finds_user_adv_screen (uname, adv, screen),
  KEY ending_finds_adv_user (adv, uname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
