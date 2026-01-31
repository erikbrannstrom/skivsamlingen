-- MySQL 8.0 initialization for Skivsamlingen
-- This file runs on first container startup

-- Ensure the user uses mysql_native_password for PHP 5.6 compatibility
ALTER USER 'skivsamlingen'@'%' IDENTIFIED WITH mysql_native_password BY 'skivsamlingen';

-- Create test database for Laravel tests
CREATE DATABASE IF NOT EXISTS skivsamlingen_test;
GRANT ALL PRIVILEGES ON skivsamlingen_test.* TO 'skivsamlingen'@'%';

FLUSH PRIVILEGES;
