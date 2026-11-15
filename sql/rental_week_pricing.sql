-- Run manually in phpMyAdmin. Existing tariff values and bookings are untouched.
-- Run the ALTER once. Do not repeat it if these columns already exist.
ALTER TABLE `salesmanno04`.`rental_objects`
    ADD COLUMN `pricing_model` ENUM('legacy','weekly') NOT NULL DEFAULT 'legacy',
    ADD COLUMN `daily_prices` JSON DEFAULT NULL;

-- No generated columns, triggers or foreign-key collation dependencies.
-- The application validates slots 1/2, weekdays 1..7, non-overlap and all prices.
CREATE TABLE `salesmanno04`.`rental_price_groups` (
    `id` CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `company_id` VARCHAR(36) NOT NULL,
    `rental_object_id` VARCHAR(36) NOT NULL,
    `slot` TINYINT UNSIGNED NOT NULL,
    `start_day` TINYINT UNSIGNED NOT NULL,
    `end_day` TINYINT UNSIGNED NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rental_group_slot` (`company_id`,`rental_object_id`,`slot`),
    KEY `idx_rental_group_object` (`rental_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
