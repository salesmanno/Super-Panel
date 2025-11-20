-- ============================================================
-- ORPHAN DATA CLEANUP FOR OUTPUT_PRODUCTS
-- ============================================================
-- Denne SQL-sekvensen rydder opp i orphan data i output_products-tabellen
-- Kjør denne mot MYSQL PRODUKSJONSDATABASEN
-- 
-- VIKTIG: Kjør STEG 1 først for å se hva som vil bli slettet!
-- ============================================================

-- STEG 1: IDENTIFISER ORPHAN DATA
-- ============================================================

-- Finn output_products uten tilhørende events
SELECT 
    'ORPHAN EVENTS' as type,
    op.id,
    op.name as product_name,
    op.module_id,
    op.created_at
FROM output_products op
WHERE op.module_id = 'mod_events'
  AND NOT EXISTS (
    SELECT 1 FROM events_eventdata e 
    WHERE e.output_product_id = op.id
  );

-- Finn output_products uten tilhørende rental objects
SELECT 
    'ORPHAN RENTAL' as type,
    op.id,
    op.name as product_name,
    op.module_id,
    op.created_at
FROM output_products op
WHERE op.module_id = 'mod_rental'
  AND NOT EXISTS (
    SELECT 1 FROM rental_objects ro 
    WHERE ro.output_product_id = op.id
  );

-- Finn output_products uten tilhørende reservation services
SELECT 
    'ORPHAN RESERVATION' as type,
    op.id,
    op.name as product_name,
    op.module_id,
    op.created_at
FROM output_products op
WHERE op.module_id = 'mod_reservation'
  AND NOT EXISTS (
    SELECT 1 FROM reservation_services rs 
    WHERE rs.output_product_id = op.id
  );

-- Finn output_products uten tilhørende sales products
SELECT 
    'ORPHAN SALES' as type,
    op.id,
    op.name as product_name,
    op.module_id,
    op.created_at
FROM output_products op
WHERE op.module_id = 'mod_sales'
  AND NOT EXISTS (
    SELECT 1 FROM sales_products sp 
    WHERE sp.output_product_id = op.id
  );

-- Finn output_products uten tilhørende membership plans
SELECT 
    'ORPHAN MEMBERSHIP' as type,
    op.id,
    op.name as product_name,
    op.module_id,
    op.created_at
FROM output_products op
WHERE op.module_id = 'mod_membership'
  AND NOT EXISTS (
    SELECT 1 FROM membership_plans mp 
    WHERE mp.output_product_id = op.id
  );


-- STEG 2: SLETT ORPHAN DATA (KJØR ETTER Å HA VERIFISERT STEG 1)
-- ============================================================
-- ADVARSEL: Denne sletter permanent. Sjekk resultatene fra STEG 1 først!

-- Slett orphan events
DELETE op FROM output_products op
WHERE op.module_id = 'mod_events'
  AND NOT EXISTS (
    SELECT 1 FROM events_eventdata e 
    WHERE e.output_product_id = op.id
  );

-- Slett orphan rental
DELETE op FROM output_products op
WHERE op.module_id = 'mod_rental'
  AND NOT EXISTS (
    SELECT 1 FROM rental_objects ro 
    WHERE ro.output_product_id = op.id
  );

-- Slett orphan reservation
DELETE op FROM output_products op
WHERE op.module_id = 'mod_reservation'
  AND NOT EXISTS (
    SELECT 1 FROM reservation_services rs 
    WHERE rs.output_product_id = op.id
  );

-- Slett orphan sales
DELETE op FROM output_products op
WHERE op.module_id = 'mod_sales'
  AND NOT EXISTS (
    SELECT 1 FROM sales_products sp 
    WHERE sp.output_product_id = op.id
  );

-- Slett orphan membership
DELETE op FROM output_products op
WHERE op.module_id = 'mod_membership'
  AND NOT EXISTS (
    SELECT 1 FROM membership_plans mp 
    WHERE mp.output_product_id = op.id
  );


-- STEG 3: FORBEDRE FOREIGN KEY CONSTRAINTS (VALGFRITT)
-- ============================================================
-- Sikrer at orphan data ikke oppstår igjen i fremtiden
-- ADVARSEL: Kun kjør dette hvis du forstår konsekvensene

-- Oppdater foreign keys til å bruke ON DELETE CASCADE
-- Dette gjør at når en output_product slettes, blir tilhørende records også slettet

-- ALTERNATIV A: ON DELETE CASCADE (sletter begge veier)
-- Hvis output_product slettes -> slett tilhørende event/rental/etc
-- Hvis event/rental/etc slettes -> slett tilhørende output_product (via trigger/app logic)

-- ALTERNATIV B: ON DELETE SET NULL (anbefalt - tryggere)
-- Hvis output_product slettes -> sett output_product_id til NULL i event/rental/etc
-- Hvis event/rental/etc slettes -> behold output_product (ryddes opp manuelt)

-- Eksempel (kommenter ut hvis du vil bruke):
-- ALTER TABLE events_eventdata 
--   DROP FOREIGN KEY events_eventdata_ibfk_2;
-- ALTER TABLE events_eventdata 
--   ADD CONSTRAINT events_eventdata_ibfk_2 
--   FOREIGN KEY (output_product_id) 
--   REFERENCES output_products(id) 
--   ON DELETE SET NULL ON UPDATE CASCADE;


-- STEG 4: VERIFISERING
-- ============================================================
-- Sjekk at ingen orphan data er igjen

SELECT 
    'VERIFICATION' as step,
    COUNT(*) as orphan_count
FROM output_products op
WHERE 
  (op.module_id = 'mod_events' AND NOT EXISTS (SELECT 1 FROM events_eventdata e WHERE e.output_product_id = op.id))
  OR (op.module_id = 'mod_rental' AND NOT EXISTS (SELECT 1 FROM rental_objects ro WHERE ro.output_product_id = op.id))
  OR (op.module_id = 'mod_reservation' AND NOT EXISTS (SELECT 1 FROM reservation_services rs WHERE rs.output_product_id = op.id))
  OR (op.module_id = 'mod_sales' AND NOT EXISTS (SELECT 1 FROM sales_products sp WHERE sp.output_product_id = op.id))
  OR (op.module_id = 'mod_membership' AND NOT EXISTS (SELECT 1 FROM membership_plans mp WHERE mp.output_product_id = op.id));

-- RESULTAT: orphan_count skal være 0 etter cleanup


-- STEG 5: OPPRETT SCHEDULED CLEANUP JOB (VALGFRITT)
-- ============================================================
-- Automatisk cleanup av orphan data hver natt

DELIMITER $$

CREATE EVENT IF NOT EXISTS cleanup_output_products_orphans
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO
BEGIN
    -- Slett orphan events
    DELETE op FROM output_products op
    WHERE op.module_id = 'mod_events'
      AND NOT EXISTS (SELECT 1 FROM events_eventdata e WHERE e.output_product_id = op.id);
    
    -- Slett orphan rental
    DELETE op FROM output_products op
    WHERE op.module_id = 'mod_rental'
      AND NOT EXISTS (SELECT 1 FROM rental_objects ro WHERE ro.output_product_id = op.id);
    
    -- Slett orphan reservation
    DELETE op FROM output_products op
    WHERE op.module_id = 'mod_reservation'
      AND NOT EXISTS (SELECT 1 FROM reservation_services rs WHERE rs.output_product_id = op.id);
    
    -- Slett orphan sales
    DELETE op FROM output_products op
    WHERE op.module_id = 'mod_sales'
      AND NOT EXISTS (SELECT 1 FROM sales_products sp WHERE sp.output_product_id = op.id);
    
    -- Slett orphan membership
    DELETE op FROM output_products op
    WHERE op.module_id = 'mod_membership'
      AND NOT EXISTS (SELECT 1 FROM membership_plans mp WHERE mp.output_product_id = op.id);
END$$

DELIMITER ;

-- Aktiver event scheduler (hvis ikke allerede aktivert)
-- SET GLOBAL event_scheduler = ON;
