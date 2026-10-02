-- Convert legacy News priority labels to the supported 1–9 numeric range.
-- This file is intentionally safe to re-run after the cascade migration.
-- MANUAL OPERATION ONLY: do not run against the external production database
-- without a new, explicit approval for that target. No startup auto-migration.
-- Before execution: confirm the database/environment, inspect current priorities
-- and column/constraint definitions, verify MySQL compatibility (8.0.16+ for
-- enforced CHECK constraints and DROP CHECK), and take a restorable backup.
-- Pause News writers during BOTH files: MySQL DDL is not transactionally atomic.
-- Run this file first, then tenant_announcement_priority_numeric_cascade.sql.
-- Do not run the superseded unrestricted/integer-expansion migration.
UPDATE `tenant_announcements`
SET `priority` = CASE LOWER(TRIM(COALESCE(`priority`, '')))
    WHEN 'urgent' THEN 1
    WHEN 'high' THEN 3
    WHEN 'normal' THEN 5
    WHEN 'low' THEN 8
    ELSE CASE
        WHEN TRIM(`priority`) REGEXP '^[0-9]+$'
          AND CAST(TRIM(`priority`) AS DECIMAL(20,0)) >= 1
        THEN LEAST(CAST(TRIM(`priority`) AS DECIMAL(20,0)), 9)
        ELSE 5
    END
END;

SET @priority_column_type = (
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND COLUMN_NAME = 'priority'
);
SET @priority_type_needs_modify = IF(
    @priority_column_type LIKE 'tinyint%unsigned%',
    0,
    1
);
SET @priority_guard_index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND INDEX_NAME = 'uq_tenant_announcements_priority_one'
);
SET @priority_guard_index_sql = IF(
    @priority_type_needs_modify = 1 AND @priority_guard_index_exists > 0,
    'ALTER TABLE `tenant_announcements` DROP INDEX `uq_tenant_announcements_priority_one`',
    'SELECT 1'
);
PREPARE priority_guard_index_statement FROM @priority_guard_index_sql;
EXECUTE priority_guard_index_statement;
DEALLOCATE PREPARE priority_guard_index_statement;

SET @priority_guard_column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND COLUMN_NAME = 'priority_one_company_id'
);
SET @priority_guard_column_sql = IF(
    @priority_type_needs_modify = 1 AND @priority_guard_column_exists > 0,
    'ALTER TABLE `tenant_announcements` DROP COLUMN `priority_one_company_id`',
    'SELECT 1'
);
PREPARE priority_guard_column_statement FROM @priority_guard_column_sql;
EXECUTE priority_guard_column_statement;
DEALLOCATE PREPARE priority_guard_column_statement;

SET @priority_modify_sql = IF(
    @priority_type_needs_modify = 0,
    'SELECT 1',
    'ALTER TABLE `tenant_announcements` MODIFY COLUMN `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5'
);
PREPARE priority_modify_statement FROM @priority_modify_sql;
EXECUTE priority_modify_statement;
DEALLOCATE PREPARE priority_modify_statement;

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
    CHECK (`priority` BETWEEN 1 AND 9);
