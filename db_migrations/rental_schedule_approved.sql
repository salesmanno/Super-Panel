-- Migration: Add approved + pickup_time columns to rental_schedule
-- Run on: salesmanno04.mysql.domeneshop.no
-- Safe to run multiple times (IF NOT EXISTS guards)

ALTER TABLE `rental_schedule`
  ADD COLUMN IF NOT EXISTS `approved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved',
  ADD COLUMN IF NOT EXISTS `pickup_time` TIME NULL COMMENT 'Confirmed pickup time set by admin';

-- Normalize legacy NULL values to 0 (pending) so the pending filter works correctly
UPDATE `rental_schedule` SET `approved` = 0 WHERE `approved` IS NULL;

-- Index to speed up the pending/approved queries
ALTER TABLE `rental_schedule`
  ADD INDEX IF NOT EXISTS `idx_approved` (`approved`),
  ADD INDEX IF NOT EXISTS `idx_company_approved` (`company_id`, `approved`);
