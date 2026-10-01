-- Run after tenant_announcement_priority_numeric.sql.
-- For each tenant with duplicate priority-1 rows, keep the smallest ID at 1,
-- move the remaining former top rows to 2, and shift every existing rank >= 2
-- once per former duplicate (capped at 9). This is deterministic and replay-safe:
-- after this migration no tenant has duplicate priority-1 rows.

DROP TEMPORARY TABLE IF EXISTS `tmp_tenant_announcement_priority_one`;
CREATE TEMPORARY TABLE `tmp_tenant_announcement_priority_one` AS
SELECT
    `company_id`,
    MIN(`id`) AS `keeper_id`,
    COUNT(*) - 1 AS `duplicate_count`
FROM `tenant_announcements`
WHERE `priority` = 1
GROUP BY `company_id`
HAVING COUNT(*) > 1;

UPDATE `tenant_announcements` AS announcement
JOIN `tmp_tenant_announcement_priority_one` AS duplicate_groups
    ON duplicate_groups.`company_id` = announcement.`company_id`
SET announcement.`priority` = LEAST(
        9,
        announcement.`priority` + duplicate_groups.`duplicate_count`
    ),
    announcement.`updated_at` = NOW()
WHERE announcement.`priority` >= 2;

UPDATE `tenant_announcements` AS loser
JOIN `tmp_tenant_announcement_priority_one` AS duplicate_groups
    ON duplicate_groups.`company_id` = loser.`company_id`
SET loser.`priority` = 2,
    loser.`updated_at` = NOW()
WHERE loser.`priority` = 1
  AND loser.`id` <> duplicate_groups.`keeper_id`;

DROP TEMPORARY TABLE `tmp_tenant_announcement_priority_one`;

SET @priority_guard_column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND COLUMN_NAME = 'priority_one_company_id'
);
SET @priority_guard_column_sql = IF(
    @priority_guard_column_exists = 0,
    'ALTER TABLE `tenant_announcements` ADD COLUMN `priority_one_company_id` CHAR(36) GENERATED ALWAYS AS (CASE WHEN `priority` = 1 THEN `company_id` ELSE NULL END) STORED',
    'SELECT 1'
);
PREPARE priority_guard_column_statement FROM @priority_guard_column_sql;
EXECUTE priority_guard_column_statement;
DEALLOCATE PREPARE priority_guard_column_statement;

SET @priority_guard_index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tenant_announcements'
      AND INDEX_NAME = 'uq_tenant_announcements_priority_one'
);
SET @priority_guard_index_sql = IF(
    @priority_guard_index_exists = 0,
    'ALTER TABLE `tenant_announcements` ADD UNIQUE KEY `uq_tenant_announcements_priority_one` (`priority_one_company_id`)',
    'SELECT 1'
);
PREPARE priority_guard_index_statement FROM @priority_guard_index_sql;
EXECUTE priority_guard_index_statement;
DEALLOCATE PREPARE priority_guard_index_statement;