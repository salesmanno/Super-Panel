-- Idempotent rental pricing snapshots and tenant-scoped internal blackouts.
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

CREATE TABLE IF NOT EXISTS rental_blackouts (
  id CHAR(36) NOT NULL,
  company_id CHAR(36) NOT NULL,
  rental_object_id CHAR(36) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  created_by CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_rental_blackouts_company_dates (company_id, start_date, end_date),
  INDEX idx_rental_blackouts_object_dates (rental_object_id, start_date, end_date),
  CONSTRAINT fk_rental_blackouts_company FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_rental_blackouts_object FOREIGN KEY (rental_object_id) REFERENCES rental_objects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE rental_schedule SET pickup_time = '08:00:00'
WHERE (approved = 0 OR approved IS NULL)
  AND status = 'pending'
  AND (pickup_time IS NULL OR pickup_time <> '08:00:00');

UPDATE rental_schedule rs
INNER JOIN rental_objects ro ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
LEFT JOIN rental_venues rv ON rv.object_id = ro.id
SET rs.key_box_code = COALESCE(NULLIF(rv.access_code, ''), '0000')
WHERE rs.approved = 1 AND rs.status = 'approved'
  AND (rs.key_box_code IS NULL OR rs.key_box_code = '');