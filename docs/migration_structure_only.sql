-- ================================================================
-- SALESMAN SUPERPORTAL - DATABASE STRUCTURE MIGRATION
-- ================================================================
-- Versjon: 2.0 Clean Architecture
-- Database: salesmanno04 (MySQL/MariaDB)
-- Server: salesmanno04.mysql.domeneshop.no
-- 
-- Dette scriptet oppretter KUN nye tabeller for clean arkitektur.
-- Data settes inn etterhvert i PHP-koden.
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ================================================================
-- 1. EMAIL TEMPLATES
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- 2. EMAIL LOGS
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- 3. SERVICE BOOKINGS
-- ================================================================
CREATE TABLE IF NOT EXISTS service_bookings (
    id CHAR(36) NOT NULL PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    admin_id CHAR(36) NOT NULL,
    
    service_name VARCHAR(255) NOT NULL,
    service_description TEXT,
    selling_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Optional cost for profit tracking',
    profit_margin DECIMAL(10,2) GENERATED ALWAYS AS (selling_price - IFNULL(cost_price, 0)) STORED,
    
    duration INT NOT NULL COMMENT 'Duration in minutes',
    date_from DATE NOT NULL,
    time_from TIME NOT NULL,
    date_to DATE NOT NULL,
    time_to TIME NOT NULL,
    
    assigned_staff_id CHAR(36) DEFAULT NULL,
    
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    
    status VARCHAR(50) DEFAULT 'available' COMMENT 'available, booked, completed, blocked, cancelled',
    
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- 4. UNIFIED LEDGER
-- ================================================================
CREATE TABLE IF NOT EXISTS unified_ledger (
    id CHAR(36) NOT NULL PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL,
    company_id CHAR(36) NOT NULL,
    
    module_source VARCHAR(50) NOT NULL COMMENT 'sales, events, membership, rental, service',
    item_type VARCHAR(50) NOT NULL COMMENT 'product, event, membership, rental, service',
    source_id CHAR(36) NOT NULL COMMENT 'ID from original module table',
    
    item_name VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    
    selling_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    profit DECIMAL(10,2) GENERATED ALWAYS AS (selling_price - IFNULL(cost_price, 0)) STORED,
    
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'completed',
    
    status VARCHAR(50) DEFAULT 'active',
    
    transaction_date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    metadata JSON,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    
    INDEX idx_unified_ledger_company (company_id),
    INDEX idx_unified_ledger_module (module_source, item_type),
    INDEX idx_unified_ledger_order (order_id),
    INDEX idx_unified_ledger_date (transaction_date),
    INDEX idx_unified_ledger_customer (customer_email),
    INDEX idx_unified_ledger_status (status),
    UNIQUE KEY unique_transaction (module_source, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- MIGRATION COMPLETE
-- ================================================================
-- 4 nye tabeller opprettet:
-- ✓ email_templates
-- ✓ email_logs  
-- ✓ service_bookings
-- ✓ unified_ledger
-- ================================================================
