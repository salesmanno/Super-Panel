-- MySQL/MariaDB-compatible idempotent additions for inline request editing.
SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_schedule' AND COLUMN_NAME = 'cleaning') = 0,
  'ALTER TABLE `rental_schedule` ADD COLUMN `cleaning` TINYINT NOT NULL DEFAULT 0 COMMENT ''1 when venue cleaning is included in rental_fee''',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_schedule' AND COLUMN_NAME = 'customer_name') = 0,
  'ALTER TABLE `rental_schedule` ADD COLUMN `customer_name` VARCHAR(255) NULL',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_schedule' AND COLUMN_NAME = 'customer_email') = 0,
  'ALTER TABLE `rental_schedule` ADD COLUMN `customer_email` VARCHAR(255) NULL',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_schedule' AND COLUMN_NAME = 'customer_phone') = 0,
  'ALTER TABLE `rental_schedule` ADD COLUMN `customer_phone` VARCHAR(50) NULL',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_venues' AND COLUMN_NAME = 'cleaning_fee') = 0,
  'ALTER TABLE `rental_venues` ADD COLUMN `cleaning_fee` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT ''Cleaning amount included in rental_fee when the request selects cleaning''',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;