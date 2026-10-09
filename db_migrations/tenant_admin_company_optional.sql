-- Apply only after explicit operator approval. No account data is changed.
-- Preserve the existing column type and collation (older exports may differ).
SET @company_column = (
  SELECT CONCAT('ALTER TABLE tenant_admins MODIFY company_id ', COLUMN_TYPE,
    IF(CHARACTER_SET_NAME IS NULL, '', CONCAT(' CHARACTER SET ', CHARACTER_SET_NAME)),
    IF(COLLATION_NAME IS NULL, '', CONCAT(' COLLATE ', COLLATION_NAME)), ' NULL DEFAULT NULL')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenant_admins' AND COLUMN_NAME='company_id'
);
PREPARE company_column_stmt FROM @company_column;
EXECUTE company_column_stmt;
DEALLOCATE PREPARE company_column_stmt;
