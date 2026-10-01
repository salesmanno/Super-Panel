-- Run after tenant_announcement_priority_numeric.sql.
-- Keep the lexicographically smallest ID at priority 1. Move the contiguous
-- occupied chain beginning at 2 down one slot before placing the other legacy
-- priority-1 rows at 2; gaps and unrelated rows remain untouched.

DROP TEMPORARY TABLE IF EXISTS `tmp_tenant_announcement_priority_one`;
DROP TEMPORARY TABLE IF EXISTS `tmp_tenant_announcement_priority_occupied`;
DROP TEMPORARY TABLE IF EXISTS `tmp_tenant_announcement_priority_chain_slots`;
DROP TEMPORARY TABLE IF EXISTS `tmp_tenant_announcement_priority_chain_rows`;
CREATE TEMPORARY TABLE `tmp_tenant_announcement_priority_one` AS
SELECT
    `company_id`,
    MIN(`id`) AS `keeper_id`
FROM `tenant_announcements`
WHERE `priority` = 1
GROUP BY `company_id`
HAVING COUNT(*) > 1;

CREATE TEMPORARY TABLE `tmp_tenant_announcement_priority_occupied` AS
SELECT DISTINCT announcement.`company_id`, announcement.`priority`
FROM `tenant_announcements` AS announcement
JOIN `tmp_tenant_announcement_priority_one` AS duplicate_groups
    ON duplicate_groups.`company_id` = announcement.`company_id`
WHERE announcement.`priority` >= 2;

CREATE TEMPORARY TABLE `tmp_tenant_announcement_priority_chain_slots` AS
WITH RECURSIVE `occupied_chain` (`company_id`, `priority`) AS (
    SELECT `company_id`, 2
    FROM `tmp_tenant_announcement_priority_one`
    UNION ALL
    SELECT chain.`company_id`, chain.`priority` + 1
    FROM `occupied_chain` AS chain
    JOIN `tmp_tenant_announcement_priority_occupied` AS occupied
        ON occupied.`company_id` = chain.`company_id`
       AND occupied.`priority` = chain.`priority`
)
SELECT `company_id`, `priority`
FROM `occupied_chain`;

CREATE TEMPORARY TABLE `tmp_tenant_announcement_priority_chain_rows` AS
SELECT announcement.`company_id`, announcement.`id`, announcement.`priority`
FROM `tmp_tenant_announcement_priority_chain_slots` AS chain
JOIN `tenant_announcements` AS announcement
    ON announcement.`company_id` = chain.`company_id`
   AND announcement.`priority` = chain.`priority`;

UPDATE `tenant_announcements` AS announcement
JOIN `tmp_tenant_announcement_priority_chain_rows` AS chain
    ON chain.`company_id` = announcement.`company_id`
   AND chain.`id` = announcement.`id`
SET announcement.`priority` = chain.`priority` + 1,
    announcement.`updated_at` = NOW();

UPDATE `tenant_announcements` AS loser
JOIN `tmp_tenant_announcement_priority_one` AS duplicate_groups
    ON duplicate_groups.`company_id` = loser.`company_id`
SET loser.`priority` = 2,
    loser.`updated_at` = NOW()
WHERE loser.`priority` = 1
  AND loser.`id` <> duplicate_groups.`keeper_id`;

DROP TEMPORARY TABLE `tmp_tenant_announcement_priority_chain_rows`;
DROP TEMPORARY TABLE `tmp_tenant_announcement_priority_chain_slots`;
DROP TEMPORARY TABLE `tmp_tenant_announcement_priority_occupied`;
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