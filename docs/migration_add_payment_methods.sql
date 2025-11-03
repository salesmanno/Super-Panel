-- ================================================
-- MIGRATION: Create payment_methods table
-- Run this on your MySQL production database (super.salesman.no)
-- ================================================
-- Date: 2025-11-03
-- Description: Creates table for storing payment methods (Vipps, Kontant, etc) with sandbox/demo support

USE salesmanno04;

CREATE TABLE IF NOT EXISTS payment_methods (
    id CHAR(36) PRIMARY KEY,
    company_id INT NOT NULL,
    method_name VARCHAR(100) NOT NULL,
    method_type VARCHAR(50) NOT NULL COMMENT 'vipps, cash, card, invoice, etc',
    is_demo TINYINT(1) DEFAULT 0 COMMENT 'Sandbox/demo mode flag',
    is_active TINYINT(1) DEFAULT 1,
    api_credentials TEXT COMMENT 'JSON with API keys/credentials',
    config_data TEXT COMMENT 'JSON with additional configuration',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_pm_company_id (company_id),
    INDEX idx_pm_active (is_active),
    INDEX idx_pm_type (method_type),
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- VERIFICATION
-- ================================================
-- Run this query to verify table was created:
-- DESCRIBE payment_methods;
-- 
-- Expected columns:
-- - id: CHAR(36) PRIMARY KEY
-- - company_id: INT
-- - method_name: VARCHAR(100)
-- - method_type: VARCHAR(50)
-- - is_demo: TINYINT(1)
-- - is_active: TINYINT(1)
-- - api_credentials: TEXT
-- - config_data: TEXT
-- - created_at, updated_at: TIMESTAMP

-- ================================================
-- MIGRATION COMPLETE
-- ================================================
