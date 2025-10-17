-- ================================================
-- DATABASE RESTRUCTURING MIGRATION
-- Cost Tracking, Service Bookings & Unified Ledger
-- ================================================
-- Run this SQL on your production MySQL database

-- 1. Add profit_margin to sales_products (cost_price already exists)
ALTER TABLE sales_products 
ADD COLUMN profit_margin DECIMAL(10,2) GENERATED ALWAYS AS (selling_price - IFNULL(cost_price, 0)) STORED;

-- 2. Add cost tracking to events_eventdata
ALTER TABLE events_eventdata 
ADD COLUMN cost_price DECIMAL(10,2) DEFAULT 0;

ALTER TABLE events_eventdata 
ADD COLUMN profit_per_ticket DECIMAL(10,2) GENERATED ALWAYS AS (
    CASE 
        WHEN max_attendees > 0 THEN ticket_price - (cost_price / max_attendees)
        ELSE ticket_price - cost_price
    END
) STORED;

-- 3. Create service_bookings table for service industry (hair salons, massage, etc)
CREATE TABLE service_bookings (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    tenant_admin_id CHAR(36) NOT NULL,
    
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
    FOREIGN KEY (tenant_admin_id) REFERENCES tenant_admins(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_staff_id) REFERENCES tenant_staff(id) ON DELETE SET NULL,
    
    INDEX idx_service_bookings_company (company_id),
    INDEX idx_service_bookings_date (date_from, date_to),
    INDEX idx_service_bookings_status (status),
    INDEX idx_service_bookings_staff (assigned_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create unified_ledger for aggregated economy reporting
CREATE TABLE unified_ledger (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    order_id VARCHAR(100) NOT NULL,
    company_id CHAR(36) NOT NULL,
    
    -- Source tracking
    module_source VARCHAR(50) NOT NULL COMMENT 'sales, events, membership, reservations',
    item_type VARCHAR(50) NOT NULL COMMENT 'product, event, membership, service, rental',
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

-- 5. Ensure output_products has name and price columns (may already exist)
ALTER TABLE output_products 
ADD COLUMN IF NOT EXISTS name VARCHAR(255),
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2);

-- ================================================
-- VERIFICATION QUERIES
-- ================================================
-- Run these to verify the migration was successful:

-- Check sales_products has profit_margin
-- DESCRIBE sales_products;

-- Check events_eventdata has cost tracking
-- DESCRIBE events_eventdata;

-- Check service_bookings table exists
-- SHOW TABLES LIKE 'service_bookings';

-- Check unified_ledger table exists
-- SHOW TABLES LIKE 'unified_ledger';

-- ================================================
-- MIGRATION COMPLETE
-- ================================================
