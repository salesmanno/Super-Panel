-- Rental check-in details and idempotent email delivery tracking.
SET @db_name = DATABASE();

SET @had_rental_confirm = EXISTS(
  SELECT 1 FROM information_schema.columns
  WHERE table_schema=@db_name AND table_name='email_configs' AND column_name='rental_confirm'
);
SET @sql = IF(
  @had_rental_confirm,
  'SELECT 1',
  'ALTER TABLE email_configs ADD COLUMN rental_confirm TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  @had_rental_confirm = 0
    AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='email_configs' AND column_name='automail_rental_confirm'),
  'UPDATE email_configs SET rental_confirm = COALESCE(automail_rental_confirm, 0) WHERE rental_confirm = 0',
  'SELECT 1'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_objects' AND column_name='rental_text'),
  'SELECT 1',
  'ALTER TABLE rental_objects ADD COLUMN rental_text VARCHAR(1000) DEFAULT NULL AFTER description'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_venues' AND column_name='access_code'),
  'SELECT 1',
  'ALTER TABLE rental_venues ADD COLUMN access_code CHAR(6) NOT NULL DEFAULT ''0000'''
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_schedule' AND column_name='key_box_code'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN key_box_code CHAR(6) DEFAULT NULL AFTER rental_object_id'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

UPDATE rental_schedule rs
INNER JOIN rental_objects ro
  ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
LEFT JOIN rental_venues rv ON rv.object_id = ro.id
SET rs.key_box_code = NULLIF(rv.access_code, '')
WHERE rs.approved = 1
  AND rs.status = 'approved'
  AND (rs.key_box_code IS NULL OR rs.key_box_code = '')
  AND NULLIF(rv.access_code, '') IS NOT NULL;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_schedule' AND column_name='checkin_email_queued_at'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN checkin_email_queued_at DATETIME DEFAULT NULL'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_schedule' AND column_name='checkin_email_sent_at'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN checkin_email_sent_at DATETIME DEFAULT NULL'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='email_queue' AND column_name='idempotency_key'),
  'SELECT 1',
  'ALTER TABLE email_queue ADD COLUMN idempotency_key VARCHAR(191) DEFAULT NULL'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db_name AND table_name='email_queue' AND index_name='uq_email_queue_idempotency'),
  'SELECT 1',
  'ALTER TABLE email_queue ADD UNIQUE KEY uq_email_queue_idempotency (idempotency_key)'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;