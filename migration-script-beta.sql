RENAME TABLE `skivsamlingen_s`.`beta_colls`  TO `skivsamlingen_s`.`beta_records` ;
INSERT INTO beta_artists (name) SELECT DISTINCT artist FROM beta_records;
ALTER TABLE `beta_records` ADD INDEX ( `artist` ) ;
ALTER TABLE `beta_artists` ADD INDEX ( `name` ) ;
UPDATE beta_records, beta_artists SET beta_records.artist_id = beta_artists.id WHERE beta_records.artist = beta_artists.name;
CREATE TABLE beta_records_tmp (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) SELECT DISTINCT artist_id, title, year, format FROM beta_records;
ALTER TABLE `beta_records` ADD INDEX ( `artist_id` ) ;
ALTER TABLE `beta_records` ADD INDEX ( `title` ) ;
ALTER TABLE `beta_records` ADD INDEX ( `year` ) ;
ALTER TABLE `beta_records` ADD INDEX ( `format` ) ;
ALTER TABLE `beta_records_tmp` ADD INDEX ( `artist_id` ) ;
ALTER TABLE `beta_records_tmp` ADD INDEX ( `title` ) ;
ALTER TABLE `beta_records_tmp` ADD INDEX ( `year` ) ;
ALTER TABLE `beta_records_tmp` ADD INDEX ( `format` ) ;
CREATE TABLE beta_records_users (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) SELECT beta_records.uid AS user_id, beta_records_tmp.id AS record_id, beta_records.comment AS comment FROM beta_records RIGHT JOIN beta_records_tmp ON beta_records.artist_id = beta_records_tmp.artist_id AND beta_records.title = beta_records_tmp.title AND (beta_records.year = beta_records_tmp.year OR (beta_records.year IS NULL AND beta_records_tmp.year IS NULL)) AND (beta_records.format = beta_records_tmp.format OR (beta_records.format IS NULL AND beta_records_tmp.format IS NULL));
ALTER TABLE `beta_records_users` ADD INDEX ( `record_id` ) ;
ALTER TABLE `beta_records_users` ADD INDEX ( `user_id` );
UPDATE beta_records_users SET comment = NULL WHERE comment = '';
# CREATE TABLE comments (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, record_id INT NOT NULL, `text` TEXT NOT NULL, INDEX (record_id));
# INSERT INTO comments SELECT 0 AS id, records.uid AS user_id, records_tmp.id AS record_id, comment AS text FROM records RIGHT JOIN records_tmp ON records.artist_id = records_tmp.artist_id AND records.title = records_tmp.title AND (records.year = records_tmp.year OR (records.year IS NULL AND records_tmp.year IS NULL)) AND (records.format = records_tmp.format OR (records.format IS NULL AND records_tmp.format IS NULL)) AND comment IS NOT NULL AND comment != '' WHERE uid IS NOT NULL;
DROP TABLE `beta_records`;
RENAME TABLE `skivsamlingen_s`.`beta_records_tmp`  TO `skivsamlingen_s`.`beta_records` ;
ALTER TABLE `beta_users` CHANGE `usr` `username` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL;
ALTER TABLE `beta_users` CHANGE `psw` `password` CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL;
ALTER TABLE `beta_users` DROP `pm_accept`;
ALTER TABLE `beta_users` DROP `warned`;
ALTER TABLE `beta_users` ADD `public_email` TINYINT( 1 ) NOT NULL AFTER `email`;
# ALTER TABLE `beta_records` DROP INDEX `year` ;
# ALTER TABLE `beta_records` DROP INDEX `format`;
# DROP TABLE `friends` ;
ALTER TABLE `beta_records` CHANGE `format` `format` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `beta_records` CHANGE `title` `title` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL ;
ALTER TABLE `beta_artists` CHANGE `name` `name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL ;
ALTER TABLE `beta_users` CHANGE `email` `email` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NULL DEFAULT NULL ;
ALTER TABLE `beta_users` DROP INDEX `password` ;
UPDATE beta_users SET per_page = 100 WHERE per_page > 100 OR per_page = 0;
ALTER TABLE `beta_records` CHANGE `id` `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE `beta_artists` CHANGE `id` `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE `beta_records` CHANGE `artist_id` `artist_id` MEDIUMINT UNSIGNED NOT NULL ;
ALTER TABLE `beta_users` CHANGE `id` `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE `beta_records_users` CHANGE `user_id` `user_id` SMALLINT UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `beta_records_users` CHANGE `record_id` `record_id` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0';
DROP TABLE `beta_friends`;

-- Create message system tables
DROP TABLE beta_messages;
CREATE TABLE `skivsamlingen_s`.`beta_messages` (
`id` SMALLINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`message` TINYTEXT NOT NULL ,
`created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE `beta_messages_users` (
  `user_id` smallint(6) NOT NULL,
  `message_id` smallint(6) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`message_id`)
);

-- Table for storing persistent logins
CREATE TABLE `beta_persistent_logins` (
  `user_id` smallint(6) NOT NULL,
  `series` char(40) COLLATE utf8_swedish_ci NOT NULL,
  `token` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`series`,`token`)
);

-- Session table
CREATE TABLE IF NOT EXISTS  `beta_ci_sessions` (
session_id varchar(40) DEFAULT '0' NOT NULL,
ip_address varchar(16) DEFAULT '0' NOT NULL,
user_agent varchar(50) NOT NULL,
last_activity int(10) unsigned DEFAULT 0 NOT NULL,
user_data text NOT NULL,
PRIMARY KEY (session_id)
);