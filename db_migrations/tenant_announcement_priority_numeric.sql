-- Convert legacy News priority labels to positive integers.
-- This file is intentionally safe to re-run after the cascade migration: values
-- above 10 remain intact and the column is never narrowed back to TINYINT.
UPDATE `tenant_announcements`
SET `priority` = CASE LOWER(TRIM(COALESCE(`priority`, '')))
    WHEN 'urgent' THEN 1
    WHEN 'high' THEN 3
    WHEN 'normal' THEN 5
    WHEN 'low' THEN 8
    ELSE CASE
        WHEN TRIM(`priority`) REGEXP '^[0-9]+$'
          AND CAST(TRIM(`priority`) AS DECIMAL(20,0)) BETWEEN 1 AND 9007199254740991
        THEN CAST(TRIM(`priority`) AS UNSIGNED)
        ELSE 5
    END
END;

ALTER TABLE `tenant_announcements`
    MODIFY COLUMN `priority` BIGINT UNSIGNED NOT NULL DEFAULT 5;

SET @priority_check_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND CONSTRAINT_NAME = 'chk_tenant_announcements_priority'
      AND CONSTRAINT_TYPE = 'CHECK'
);
SET @priority_check_sql = IF(
    @priority_check_exists > 0,
    'ALTER TABLE `tenant_announcements` DROP CHECK `chk_tenant_announcements_priority`',
    'SELECT 1'
);
PREPARE priority_check_statement FROM @priority_check_sql;
EXECUTE priority_check_statement;
DEALLOCATE PREPARE priority_check_statement;

ALTER TABLE `tenant_announcements`
    ADD CONSTRAINT `chk_tenant_announcements_priority`
    CHECK (`priority` BETWEEN 1 AND 9007199254740991);
