-- ================================================================
-- SALESMAN SUPERPORTAL - FINAL CLEAN DATABASE MIGRATION
-- ================================================================
-- Dato: Oktober 2025
-- Versjon: 2.0 Clean Architecture
-- Database: salesmanno04 (MySQL)
-- Server: salesmanno04.mysql.domeneshop.no
-- 
-- Kjør denne migreringen for å:
-- 1. Opprette nye tabeller (email_templates, email_logs, service_bookings, unified_ledger)
-- 2. Legge til cost_price i events_eventdata
-- 3. Legge til name/price i output_products
-- 4. Migrere data fra x_* tabeller
-- 5. Slette duplikat-tabeller
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ================================================================
-- FASE 1: OPPRETTE NYE TABELLER
-- ================================================================

-- 1.1 Email Templates (for automatiske bekreftelser)
-- ================================================================
CREATE TABLE IF NOT EXISTS email_templates (
    id CHAR(36) NOT NULL PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    
    template_key VARCHAR(100) NOT NULL COMMENT 'sale_receipt, event_confirmation, etc.',
    name VARCHAR(255) NOT NULL,
    subject_template VARCHAR(500) NOT NULL COMMENT 'Subject with {{placeholders}}',
    body_template TEXT NOT NULL COMMENT 'HTML body with {{placeholders}}',
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_template_per_company (company_id, template_key),
    INDEX idx_template_company (company_id),
    INDEX idx_template_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 Email Logs (sporing av sendte e-poster)
-- ================================================================
CREATE TABLE IF NOT EXISTS email_logs (
    id CHAR(36) NOT NULL PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    template_id CHAR(36) NULL,
    
    module_source VARCHAR(50) NOT NULL COMMENT 'sales, events, membership, rental, service',
    source_id CHAR(36) NOT NULL COMMENT 'ID fra original modul',
    
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    
    sent_at DATETIME NULL,
    status VARCHAR(50) DEFAULT 'pending' COMMENT 'sent, failed, pending',
    error_message TEXT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    
    INDEX idx_email_logs_company (company_id),
    INDEX idx_email_logs_module (module_source, source_id),
    INDEX idx_email_logs_status (status),
    INDEX idx_email_logs_recipient (recipient_email),
    INDEX idx_email_logs_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3 Service Bookings (for servicenæring: frisør, massasje, etc.)
-- ================================================================
CREATE TABLE IF NOT EXISTS service_bookings (
    id CHAR(36) NOT NULL PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    admin_id CHAR(36) NOT NULL,
    
    -- Service details
    service_name VARCHAR(255) NOT NULL,
    service_description TEXT,
    selling_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Optional cost for profit tracking',
    profit_margin DECIMAL(10,2) GENERATED ALWAYS AS (selling_price - IFNULL(cost_price, 0)) STORED,
    
    -- Time slot
    duration INT NOT NULL COMMENT 'Duration in minutes',
    date_from DATE NOT NULL,
    time_from TIME NOT NULL,
    date_to DATE NOT NULL,
    time_to TIME NOT NULL,
    
    -- Assignment
    assigned_staff_id CHAR(36) DEFAULT NULL,
    
    -- Booking details  
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    
    -- Status
    status VARCHAR(50) DEFAULT 'available' COMMENT 'available, booked, completed, blocked, cancelled',
    
    -- Metadata
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES tenant_admins(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_staff_id) REFERENCES tenant_staff(id) ON DELETE SET NULL,
    
    INDEX idx_service_bookings_company (company_id),
    INDEX idx_service_bookings_date (date_from, date_to),
    INDEX idx_service_bookings_status (status),
    INDEX idx_service_bookings_staff (assigned_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 Unified Ledger (samlet økonomi på tvers av moduler)
-- ================================================================
CREATE TABLE IF NOT EXISTS unified_ledger (
    id CHAR(36) NOT NULL PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL,
    company_id CHAR(36) NOT NULL,
    
    -- Source tracking
    module_source VARCHAR(50) NOT NULL COMMENT 'sales, events, membership, rental, service',
    item_type VARCHAR(50) NOT NULL COMMENT 'product, event, membership, rental, service',
    source_id CHAR(36) NOT NULL COMMENT 'ID from original module table',
    
    -- Item details
    item_name VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    
    -- Financial
    selling_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    profit DECIMAL(10,2) GENERATED ALWAYS AS (selling_price - IFNULL(cost_price, 0)) STORED,
    
    -- Customer info
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    
    -- Payment
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'completed',
    
    -- Status
    status VARCHAR(50) DEFAULT 'active',
    
    -- Timestamps
    transaction_date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Metadata (JSON for module-specific data)
    metadata JSON,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    
    INDEX idx_unified_ledger_company (company_id),
    INDEX idx_unified_ledger_module (module_source, item_type),
    INDEX idx_unified_ledger_order (order_id),
    INDEX idx_unified_ledger_date (transaction_date),
    INDEX idx_unified_ledger_customer (customer_email),
    INDEX idx_unified_ledger_status (status),
    UNIQUE KEY unique_transaction (module_source, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- FASE 2: LEGGE TIL KOLONNER I EKSISTERENDE TABELLER
-- ================================================================

-- 2.1 Legge til cost_price tracking i events_eventdata
-- ================================================================
ALTER TABLE events_eventdata 
ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) DEFAULT 0 COMMENT 'Total cost for event';

ALTER TABLE events_eventdata 
ADD COLUMN IF NOT EXISTS profit_per_ticket DECIMAL(10,2) GENERATED ALWAYS AS (
    CASE 
        WHEN max_attendees > 0 THEN ticket_price - (cost_price / max_attendees)
        ELSE ticket_price - cost_price
    END
) STORED COMMENT 'Calculated profit per ticket';

-- 2.2 Legge til denormaliserte felt i output_products for ytelse
-- ================================================================
ALTER TABLE output_products 
ADD COLUMN IF NOT EXISTS name VARCHAR(255) COMMENT 'Denormalized product name';

ALTER TABLE output_products 
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) COMMENT 'Denormalized price';

-- 2.3 Legge til profit_margin i sales_products (hvis mangler)
-- ================================================================
ALTER TABLE sales_products 
ADD COLUMN IF NOT EXISTS profit_margin DECIMAL(10,2) GENERATED ALWAYS AS (selling_price - IFNULL(cost_price, 0)) STORED;

-- ================================================================
-- FASE 3: MIGRERE DATA FRA REDUNDANTE TABELLER
-- ================================================================

-- 3.1 Migrer x_membership_plan → membership_plans (hvis data finnes)
-- ================================================================
INSERT IGNORE INTO membership_plans (id, company_id, name, description, price, duration_months, benefits, is_active, created_at, updated_at)
SELECT 
    UUID() as id,
    1 as company_id, -- Standard company_id (oppdater om nødvendig)
    name,
    description,
    price,
    duration_months,
    benefits,
    is_active,
    created_at,
    updated_at
FROM x_membership_plan
WHERE EXISTS (SELECT 1 FROM x_membership_plan LIMIT 1);

-- 3.2 Migrer x_webmailconfiguration → email_configs (hvis data finnes)
-- ================================================================
INSERT IGNORE INTO email_configs (id, company_id, smtp_host, smtp_port, smtp_user, smtp_password, from_email, from_name, created_at, updated_at)
SELECT 
    UUID() as id,
    1 as company_id, -- Standard company_id (oppdater om nødvendig)
    smtp_host,
    smtp_port,
    smtp_user,
    smtp_password,
    from_email,
    from_name,
    created_at,
    updated_at
FROM x_webmailconfiguration
WHERE EXISTS (SELECT 1 FROM x_webmailconfiguration LIMIT 1);

-- 3.3 Migrer sales_items → sales_products (hvis data finnes og ikke duplikat)
-- ================================================================
-- MERK: Denne migreringen KAN feile hvis sales_products allerede har data
-- Fjern kommentaren hvis du vil kjøre den:

-- INSERT IGNORE INTO sales_products (id, company_id, name, sku, description, cost_price, selling_price, tax_rate, is_active, created_at, updated_at)
-- SELECT 
--     id,
--     company_id,
--     item_name as name,
--     sku,
--     description,
--     cost_price,
--     price as selling_price,
--     tax_rate,
--     is_active,
--     created_at,
--     updated_at
-- FROM sales_items
-- WHERE EXISTS (SELECT 1 FROM sales_items LIMIT 1);

-- 3.4 Migrer sales_orders → sales_transactions (hvis data finnes)
-- ================================================================
-- MERK: Dette krever mapping av order_id og items
-- Fjern kommentaren hvis du vil kjøre den:

-- INSERT IGNORE INTO sales_transactions (id, company_id, admin_id, order_id, customer_name, customer_email, total_amount, payment_method, payment_status, transaction_date, created_at)
-- SELECT 
--     UUID() as id,
--     company_id,
--     admin_id,
--     order_number as order_id,
--     customer_name,
--     customer_email,
--     total as total_amount,
--     payment_method,
--     status as payment_status,
--     created_at as transaction_date,
--     created_at
-- FROM sales_orders
-- WHERE EXISTS (SELECT 1 FROM sales_orders LIMIT 1);

-- ================================================================
-- FASE 4: OPPDATERE OUTPUT_PRODUCTS MED DENORMALISERTE DATA
-- ================================================================

-- 4.1 Oppdater name og price fra sales_products
UPDATE output_products op
INNER JOIN sales_products sp ON op.product_id = sp.id AND op.product_type = 'product'
SET 
    op.name = sp.name,
    op.price = sp.selling_price
WHERE op.product_type = 'product';

-- 4.2 Oppdater name og price fra events_eventdata
UPDATE output_products op
INNER JOIN events_eventdata ed ON op.product_id = ed.id AND op.product_type = 'event'
SET 
    op.name = ed.title,
    op.price = ed.ticket_price
WHERE op.product_type = 'event';

-- 4.3 Oppdater name og price fra membership_plans
UPDATE output_products op
INNER JOIN membership_plans mp ON op.product_id = mp.id AND op.product_type = 'membership'
SET 
    op.name = mp.name,
    op.price = mp.price
WHERE op.product_type = 'membership';

-- 4.4 Oppdater name og price fra rental_objects
UPDATE output_products op
INNER JOIN rental_objects ro ON op.product_id = ro.id AND op.product_type = 'rental'
SET 
    op.name = ro.name,
    op.price = ro.rental_price_daily
WHERE op.product_type = 'rental';

-- ================================================================
-- FASE 5: SETTE INN STANDARD EMAIL TEMPLATES
-- ================================================================

-- 5.1 Standard templates for company_id = 1 (oppdater om nødvendig)
INSERT INTO email_templates (id, company_id, template_key, name, subject_template, body_template, is_active) VALUES
(UUID(), 1, 'sale_receipt', 'Salgskvittering', 'Kvittering for ordre {{order_id}}', 
'<h1>Kvittering for salg</h1>
<p>Hei {{customer_name}},</p>
<p>Takk for ditt kjøp!</p>
<p><strong>Ordre ID:</strong> {{order_id}}</p>
<p><strong>Antall varer:</strong> {{items_sold}}</p>
<p><strong>Totalbeløp:</strong> {{total_amount}} NOK</p>
<p><strong>Betalingsmetode:</strong> {{payment_method}}</p>
<p>Med vennlig hilsen,<br>{{company_name}}</p>', TRUE),

(UUID(), 1, 'event_confirmation', 'Arrangement-bekreftelse', 'Bekreftelse: {{event_title}}',
'<h1>Bekreftelse på arrangement</h1>
<p>Hei {{customer_name}},</p>
<p>Du er nå påmeldt følgende arrangement:</p>
<p><strong>Arrangement:</strong> {{event_title}}</p>
<p><strong>Dato:</strong> {{event_date}}</p>
<p><strong>Tid:</strong> {{event_time}}</p>
<p><strong>Sted:</strong> {{event_location}}</p>
<p><strong>Billetttype:</strong> {{ticket_type}}</p>
<p>Vi gleder oss til å se deg!</p>
<p>Med vennlig hilsen,<br>{{company_name}}</p>', TRUE),

(UUID(), 1, 'membership_confirmation', 'Medlemskap-bekreftelse', 'Velkommen som medlem!',
'<h1>Medlemskap aktivert</h1>
<p>Hei {{customer_name}},</p>
<p>Velkommen som medlem!</p>
<p><strong>Medlemskapsplan:</strong> {{plan_name}}</p>
<p><strong>Varighet:</strong> {{duration_months}} måneder</p>
<p><strong>Fordeler:</strong></p>
<p>{{benefits}}</p>
<p><strong>Gyldig fra:</strong> {{start_date}}</p>
<p><strong>Gyldig til:</strong> {{end_date}}</p>
<p>Med vennlig hilsen,<br>{{company_name}}</p>', TRUE),

(UUID(), 1, 'rental_confirmation', 'Utleie-bekreftelse', 'Bekreftelse på utleie: {{object_name}}',
'<h1>Bekreftelse på utleie</h1>
<p>Hei {{customer_name}},</p>
<p>Din reservasjon er bekreftet:</p>
<p><strong>Objekt:</strong> {{object_name}}</p>
<p><strong>Fra:</strong> {{date_from}}</p>
<p><strong>Til:</strong> {{date_to}}</p>
<p><strong>Pris:</strong> {{total_price}} NOK</p>
<p><strong>Betalingsmetode:</strong> {{payment_method}}</p>
<p>Med vennlig hilsen,<br>{{company_name}}</p>', TRUE),

(UUID(), 1, 'service_booking_confirmation', 'Service-booking bekreftelse', 'Bekreftelse på timebestilling: {{service_name}}',
'<h1>Timebestilling bekreftet</h1>
<p>Hei {{customer_name}},</p>
<p>Din time er bekreftet:</p>
<p><strong>Tjeneste:</strong> {{service_name}}</p>
<p><strong>Dato:</strong> {{booking_date}}</p>
<p><strong>Tid:</strong> {{booking_time}}</p>
<p><strong>Varighet:</strong> {{duration}} minutter</p>
<p><strong>Behandler:</strong> {{staff_name}}</p>
<p><strong>Pris:</strong> {{price}} NOK</p>
<p>Vi gleder oss til å se deg!</p>
<p>Med vennlig hilsen,<br>{{company_name}}</p>', TRUE);

-- ================================================================
-- FASE 6: SLETTE DUPLIKAT-TABELLER OG REDUNDANTE TABELLER
-- ================================================================
-- ADVARSEL: Denne fasen sletter tabeller permanent!
-- Fjern kommentarer kun hvis du er SIKKER på at data er migrert

-- DROP TABLE IF EXISTS sales_items;
-- DROP TABLE IF EXISTS sales_orders_items;
-- DROP TABLE IF EXISTS sales_orders;
-- DROP TABLE IF EXISTS x_events;
-- DROP TABLE IF EXISTS x_reservations;
-- DROP TABLE IF EXISTS x_membership_plan;
-- DROP TABLE IF EXISTS x_webmailconfiguration;

-- ================================================================
-- FASE 7: VERIFISERING
-- ================================================================

-- Sjekk nye tabeller
SELECT 'email_templates' as tabell, COUNT(*) as antall FROM email_templates
UNION ALL
SELECT 'email_logs', COUNT(*) FROM email_logs
UNION ALL
SELECT 'service_bookings', COUNT(*) FROM service_bookings
UNION ALL
SELECT 'unified_ledger', COUNT(*) FROM unified_ledger;

-- Sjekk nye kolonner
DESCRIBE events_eventdata;
DESCRIBE output_products;
DESCRIBE sales_products;

-- Sjekk duplikater (skal være 0 etter sletting)
-- SELECT COUNT(*) as sales_items_count FROM sales_items;
-- SELECT COUNT(*) as sales_orders_count FROM sales_orders;
-- SELECT COUNT(*) as x_events_count FROM x_events;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- MIGRATION COMPLETE
-- ================================================================
-- 
-- Neste steg:
-- 1. ✅ Oppdater PHP-kode til å bruke sales_products (ikke sales_items)
-- 2. ✅ Oppdater PHP-kode til å bruke sales_transactions (ikke sales_orders)
-- 3. ✅ Implementer EmailService.php for automatisk utsending
-- 4. ✅ Legg til email-triggers i alle moduler
-- 5. ✅ Implementer synkronisering til unified_ledger
-- 
-- ================================================================
