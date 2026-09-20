-- Idempotent rental pricing adjustments and schedule-based availability.
SET @db_name = DATABASE();

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_schedule' AND column_name='price_adjustment_type'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN price_adjustment_type VARCHAR(10) NULL'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_schedule' AND column_name='price_adjustment_cents'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN price_adjustment_cents INT NOT NULL DEFAULT 0'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='rental_schedule' AND column_name='price_adjustment_explanation'),
  'SELECT 1',
  'ALTER TABLE rental_schedule ADD COLUMN price_adjustment_explanation VARCHAR(1000) NULL'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db_name AND table_name='rental_schedule' AND index_name='idx_rental_schedule_adjustment'),
  'SELECT 1',
  'CREATE INDEX idx_rental_schedule_adjustment ON rental_schedule (company_id, price_adjustment_type)'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db_name AND table_name='rental_schedule' AND index_name='idx_rental_schedule_availability'),
  'SELECT 1',
  'CREATE INDEX idx_rental_schedule_availability ON rental_schedule (company_id, rental_object_id, status, start_date, end_date)'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

-- Preserve periods from the superseded table before removing it. An ID collision
-- receives a new UUID; an already equivalent unavailable row is not duplicated.
SET @blackout_exists = (
  SELECT COUNT(*) FROM information_schema.tables
  WHERE table_schema=@db_name AND table_name='rental_blackouts'
);
SET @blackout_count = 0;
SET @sql = IF(
  @blackout_exists = 1,
  'SELECT COUNT(*) INTO @blackout_count
   FROM rental_blackouts rb
   WHERE NOT EXISTS (
     SELECT 1 FROM rental_schedule equivalent
     WHERE equivalent.company_id = rb.company_id
       AND equivalent.rental_object_id = rb.rental_object_id
       AND equivalent.status = ''unavailable''
       AND equivalent.start_date = rb.start_date
       AND equivalent.end_date = rb.end_date
   )',
  'SELECT 1'
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

SET @sql = IF(
  @blackout_exists = 1,
  'INSERT INTO rental_schedule
   (id, company_id, rental_object_id, customer_id, customer_name, customer_email,
    customer_phone, start_date, start_time, end_date, end_time, rental_fee,
    status, notes, created_by, approved, pickup_time, created_at, updated_at)
   SELECT IF(collision.id IS NULL, rb.id, UUID()),
          rb.company_id, rb.rental_object_id, NULL, NULL, NULL, NULL,
          rb.start_date, ''00:00:00'', rb.end_date, ''23:59:59'', 0.00,
          ''unavailable'', NULL, COALESCE(rb.created_by, ro.created_by), 0, ''08:00:00'',
          rb.created_at, rb.updated_at
   FROM rental_blackouts rb
   INNER JOIN rental_objects ro
     ON ro.id = rb.rental_object_id AND ro.company_id = rb.company_id
   LEFT JOIN rental_schedule collision ON collision.id = rb.id
   WHERE NOT EXISTS (
     SELECT 1 FROM rental_schedule equivalent
     WHERE equivalent.company_id = rb.company_id
       AND equivalent.rental_object_id = rb.rental_object_id
       AND equivalent.status = ''unavailable''
       AND equivalent.start_date = rb.start_date
       AND equivalent.end_date = rb.end_date
   )',
  'SELECT 1'
);
PREPARE rental_stmt FROM @sql;
EXECUTE rental_stmt;
SET @migrated_count = IF(@blackout_exists = 1, ROW_COUNT(), 0);
DEALLOCATE PREPARE rental_stmt;
SET @sql = IF(
  @migrated_count = @blackout_count,
  'DROP TABLE IF EXISTS rental_blackouts',
  'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''Kunne ikke bevare alle utilgjengelige perioder; rental_blackouts er ikke slettet'''
);
PREPARE rental_stmt FROM @sql; EXECUTE rental_stmt; DEALLOCATE PREPARE rental_stmt;

UPDATE rental_schedule SET pickup_time = '08:00:00'
WHERE (approved = 0 OR approved IS NULL)
  AND status = 'pending'
  AND (pickup_time IS NULL OR pickup_time <> '08:00:00');

UPDATE rental_schedule rs
INNER JOIN rental_objects ro ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
LEFT JOIN rental_venues rv ON rv.object_id = ro.id
SET rs.key_box_code = NULLIF(rv.access_code, '')
WHERE rs.approved = 1 AND rs.status = 'approved'
  AND (rs.key_box_code IS NULL OR rs.key_box_code = '')
  AND NULLIF(rv.access_code, '') IS NOT NULL;