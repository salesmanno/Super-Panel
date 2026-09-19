-- Request-level cleaning selection used by inline approval editing.
ALTER TABLE `rental_schedule`
  ADD COLUMN IF NOT EXISTS `cleaning` TINYINT NOT NULL DEFAULT 0
  COMMENT '1 when venue cleaning is included in rental_fee';

ALTER TABLE `rental_venues`
  ADD COLUMN IF NOT EXISTS `cleaning_fee` DECIMAL(10,2) NULL DEFAULT 0.00
  COMMENT 'Cleaning amount included in rental_fee when the request selects cleaning';