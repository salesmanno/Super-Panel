-- Track the first transition of a rental contract to payment_status='paid'.
-- Historical paid rows cannot be reconstructed exactly because older schema
-- versions had no payment timestamp. Backfill uses updated_at first (the best
-- available estimate of the payment update) and falls back to created_at.
ALTER TABLE `rental_contracts`
  ADD COLUMN IF NOT EXISTS `paid_at` DATETIME NULL AFTER `payment_status`;

UPDATE `rental_contracts`
SET `paid_at` = COALESCE(`updated_at`, `created_at`)
WHERE `payment_status` = 'paid' AND `paid_at` IS NULL;

ALTER TABLE `rental_contracts`
  ADD INDEX IF NOT EXISTS `idx_company_paid_at` (`company_id`, `paid_at`);