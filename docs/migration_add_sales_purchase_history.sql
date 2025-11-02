-- ================================================
-- MIGRATION: Create sales_purchase_history table
-- Run this on your MySQL production database (super.salesman.no)
-- ================================================
-- Date: 2025-11-02
-- Description: Creates table for tracking purchase history in Sales module

USE salesmanno04;

CREATE TABLE IF NOT EXISTS sales_purchase_history (
    id CHAR(36) PRIMARY KEY,
    company_id INT NOT NULL,
    product_id CHAR(36),
    product_name VARCHAR(255) NOT NULL,
    supplier VARCHAR(255),
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    created_by INT,
    purchase_date DATE DEFAULT (CURRENT_DATE),
    payment_method VARCHAR(50),
    invoice_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sph_company_id (company_id),
    INDEX idx_sph_product_id (product_id),
    INDEX idx_sph_purchase_date (purchase_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- VERIFICATION
-- ================================================
-- Run this query to verify table was created:
-- DESCRIBE sales_purchase_history;
-- 
-- Expected output should show:
-- - id: CHAR(36) PRIMARY KEY
-- - product_id: CHAR(36)
-- - created_by: INT
-- - All other fields as defined above

-- ================================================
-- MIGRATION COMPLETE
-- ================================================
