-- Convert tenant News priority from legacy labels to a 1..10 numeric scale.
-- Safe to run more than once: the UPDATE only targets legacy/non-canonical values
-- and MODIFY preserves already-converted numeric values.
UPDATE `tenant_announcements`
SET `priority` = CASE LOWER(TRIM(COALESCE(`priority`, '')))
    WHEN 'urgent' THEN 1
    WHEN 'high' THEN 3
    WHEN 'normal' THEN 5
    WHEN 'low' THEN 8
    ELSE CASE
        WHEN TRIM(`priority`) REGEXP '^(1|2|3|4|5|6|7|8|9|10)$'
        THEN CAST(`priority` AS UNSIGNED)
        ELSE 5
    END
END
WHERE `priority` IS NULL
   OR LOWER(TRIM(`priority`)) IN ('urgent', 'high', 'normal', 'low')
   OR TRIM(`priority`) NOT REGEXP '^(1|2|3|4|5|6|7|8|9|10)$';

ALTER TABLE `tenant_announcements`
    MODIFY COLUMN `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5;

SET @priority_check_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND CONSTRAINT_NAME = 'chk_tenant_announcements_priority'
      AND CONSTRAINT_TYPE = 'CHECK'
);
SET @priority_check_sql = IF(
    @priority_check_exists = 0,
    'ALTER TABLE `tenant_announcements` ADD CONSTRAINT `chk_tenant_announcements_priority` CHECK (`priority` BETWEEN 1 AND 10)',
    'SELECT 1'
);
PREPARE priority_check_statement FROM @priority_check_sql;
EXECUTE priority_check_statement;
DEALLOCATE PREPARE priority_check_statement;
