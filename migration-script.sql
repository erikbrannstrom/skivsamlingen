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
CREATE TABLE records_users (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) SELECT records.uid AS user_id, records_tmp.id AS record_id FROM records RIGHT JOIN records_tmp ON records.artist_id = records_tmp.artist_id AND records.title = records_tmp.title AND (records.year = records_tmp.year OR (records.year IS NULL AND records_tmp.year IS NULL)) AND (records.format = records_tmp.format OR (records.format IS NULL AND records_tmp.format IS NULL));
CREATE TABLE comments (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, record_id INT NOT NULL, `text` TEXT NOT NULL, INDEX (record_id));
INSERT INTO comments SELECT 0 AS id, records.uid AS user_id, records_tmp.id AS record_id, comment AS text FROM records RIGHT JOIN records_tmp ON records.artist_id = records_tmp.artist_id AND records.title = records_tmp.title AND (records.year = records_tmp.year OR (records.year IS NULL AND records_tmp.year IS NULL)) AND (records.format = records_tmp.format OR (records.format IS NULL AND records_tmp.format IS NULL)) AND comment IS NOT NULL AND comment != '' WHERE uid IS NOT NULL;
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