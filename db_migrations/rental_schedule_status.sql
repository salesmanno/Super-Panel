-- Keep approval status values consistent with the rental API.
-- Temporarily retain the legacy value so this also works when status is an
-- older ENUM (not only when it is VARCHAR).
ALTER TABLE `rental_schedule`
  MODIFY COLUMN `status`
    ENUM('pending','approved','declined','rejected','completed','cancelled')
    NOT NULL DEFAULT 'pending';

UPDATE `rental_schedule`
SET `status` = 'rejected'
WHERE `status` = 'declined';

ALTER TABLE `rental_schedule`
  MODIFY COLUMN `status`
    ENUM('pending','approved','rejected','completed','cancelled')
    NOT NULL DEFAULT 'pending';