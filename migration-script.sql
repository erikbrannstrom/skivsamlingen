RENAME TABLE `skivsamlingen_s`.`colls`  TO `skivsamlingen_s`.`records` ;
INSERT INTO artists (name) SELECT DISTINCT artist FROM records;
ALTER TABLE `records` ADD INDEX ( `artist` ) ;
ALTER TABLE `artists` ADD INDEX ( `name` ) ;
UPDATE records, artists SET records.artist_id = artists.id WHERE records.artist = artists.name;
CREATE TABLE records_tmp (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) SELECT DISTINCT artist_id, title, year, format FROM records;
ALTER TABLE `records` ADD INDEX ( `artist_id` ) ;
ALTER TABLE `records` ADD INDEX ( `title` ) ;
ALTER TABLE `records` ADD INDEX ( `year` ) ;
ALTER TABLE `records` ADD INDEX ( `format` ) ;
ALTER TABLE `records_tmp` ADD INDEX ( `artist_id` ) ;
ALTER TABLE `records_tmp` ADD INDEX ( `title` ) ;
ALTER TABLE `records_tmp` ADD INDEX ( `year` ) ;
ALTER TABLE `records_tmp` ADD INDEX ( `format` ) ;
CREATE TABLE records_users (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) SELECT records.uid AS user_id, records_tmp.id AS record_id, records.comment AS comment FROM records RIGHT JOIN records_tmp ON records.artist_id = records_tmp.artist_id AND records.title = records_tmp.title AND (records.year = records_tmp.year OR (records.year IS NULL AND records_tmp.year IS NULL)) AND (records.format = records_tmp.format OR (records.format IS NULL AND records_tmp.format IS NULL));
ALTER TABLE `records_users` ADD INDEX ( `record_id` ) ;
ALTER TABLE `records_users` ADD INDEX ( `user_id` );
UPDATE records_users SET comment = NULL WHERE comment = '';
# CREATE TABLE comments (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, record_id INT NOT NULL, `text` TEXT NOT NULL, INDEX (record_id));
# INSERT INTO comments SELECT 0 AS id, records.uid AS user_id, records_tmp.id AS record_id, comment AS text FROM records RIGHT JOIN records_tmp ON records.artist_id = records_tmp.artist_id AND records.title = records_tmp.title AND (records.year = records_tmp.year OR (records.year IS NULL AND records_tmp.year IS NULL)) AND (records.format = records_tmp.format OR (records.format IS NULL AND records_tmp.format IS NULL)) AND comment IS NOT NULL AND comment != '' WHERE uid IS NOT NULL;
DROP TABLE `records`;
RENAME TABLE `skivsamlingen_s`.`records_tmp`  TO `skivsamlingen_s`.`records` ;
ALTER TABLE `users` CHANGE `usr` `username` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL;
ALTER TABLE `users` CHANGE `psw` `password` CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL;
ALTER TABLE `users` DROP `pm_accept`;
ALTER TABLE `users` DROP `warned`;
ALTER TABLE `users` ADD `public_email` TINYINT( 1 ) NOT NULL AFTER `email`;
# ALTER TABLE `records` DROP INDEX `year` ;
# ALTER TABLE `records` DROP INDEX `format`;
# DROP TABLE `friends` ;
ALTER TABLE `records` CHANGE `format` `format` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `records` CHANGE `title` `title` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL ;
ALTER TABLE `artists` CHANGE `name` `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL ;
ALTER TABLE `users` CHANGE `email` `email` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NULL DEFAULT NULL ;
ALTER TABLE `users` DROP INDEX `password` ;
UPDATE users SET per_page = 100 WHERE per_page > 100 OR per_page = 0;
ALTER TABLE `records` CHANGE `id` `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE `artists` CHANGE `id` `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE `records` CHANGE `artist_id` `artist_id` MEDIUMINT UNSIGNED NOT NULL ;
ALTER TABLE `users` CHANGE `id` `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE `records_users` CHANGE `user_id` `user_id` SMALLINT UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `records_users` CHANGE `record_id` `record_id` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0';
DROP TABLE `friends`;

-- Create message system tables
DROP TABLE messages;
CREATE TABLE `skivsamlingen_s`.`messages` (
`id` SMALLINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`message` TINYTEXT NOT NULL ,
`created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE `messages_users` (
  `user_id` smallint(6) NOT NULL,
  `message_id` smallint(6) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`message_id`)
);

-- Table for storing persistent logins
CREATE TABLE `persistent_logins` (
  `user_id` smallint(6) NOT NULL,
  `series` char(40) COLLATE utf8_swedish_ci NOT NULL,
  `token` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`series`,`token`)
);

-- Session table
CREATE TABLE IF NOT EXISTS  `ci_sessions` (
session_id varchar(40) DEFAULT '0' NOT NULL,
ip_address varchar(16) DEFAULT '0' NOT NULL,
user_agent varchar(50) NOT NULL,
last_activity int(10) unsigned DEFAULT 0 NOT NULL,
user_data text DEFAULT '' NOT NULL,
PRIMARY KEY (session_id)
);