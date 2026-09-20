-- Payment/cancellation fields and a database-backed idempotency identity for
-- approved rental schedules. Every step is safe to rerun.

SET @db_name = DATABASE();

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @db_name AND table_name = 'rental_schedule' AND column_name = 'payment_status'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN payment_status ENUM(''unpaid'',''paid'') NOT NULL DEFAULT ''unpaid'' AFTER deposit_paid'
);
PREPARE migration_stmt FROM @sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @db_name AND table_name = 'rental_schedule' AND column_name = 'payment_method'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER payment_status'
);
PREPARE migration_stmt FROM @sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @db_name AND table_name = 'rental_schedule' AND column_name = 'paid_at'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER payment_method'
);
PREPARE migration_stmt FROM @sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @db_name AND table_name = 'rental_schedule' AND column_name = 'cancelled_at'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN cancelled_at DATETIME DEFAULT NULL AFTER paid_at'
);
PREPARE migration_stmt FROM @sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @db_name AND table_name = 'unified_ledger' AND column_name = 'rental_schedule_id'),
  'SELECT 1',
  'ALTER TABLE unified_ledger ADD COLUMN rental_schedule_id CHAR(36) DEFAULT NULL AFTER source_id'
);
PREPARE migration_stmt FROM @sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = @db_name AND table_name = 'unified_ledger' AND index_name = 'uq_ledger_rental_schedule'),
  'SELECT 1',
  'ALTER TABLE unified_ledger ADD UNIQUE KEY uq_ledger_rental_schedule (company_id, rental_schedule_id)'
);
PREPARE migration_stmt FROM @sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;