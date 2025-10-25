-- Migration: Add img_product and item_taxable to sales_products
-- Date: 2025-10-25
-- Description: Replace member_price with img_product and add item_taxable boolean

-- Add new columns to sales_products table
ALTER TABLE sales_products 
ADD COLUMN img_product VARCHAR(500) NULL AFTER selling_price,
ADD COLUMN item_taxable BOOLEAN DEFAULT TRUE AFTER is_active;

-- Optional: Remove member_price column if no longer needed
-- ALTER TABLE sales_products DROP COLUMN member_price;

-- Update existing products to have item_taxable = true (default)
UPDATE sales_products SET item_taxable = TRUE WHERE item_taxable IS NULL;

-- Verification query to confirm changes
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'sales_products' 
    AND COLUMN_NAME IN ('img_product', 'item_taxable', 'member_price')
ORDER BY ORDINAL_POSITION;
