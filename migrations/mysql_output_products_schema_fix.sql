-- Migration: Add missing columns to output_products table for MySQL production
-- Run this when MySQL database is available
-- Date: 2025-10-30

USE salesmanno04;

-- Add missing columns to output_products
ALTER TABLE output_products 
ADD COLUMN IF NOT EXISTS name VARCHAR(255),
ADD COLUMN IF NOT EXISTS description TEXT,
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0;

-- Verify changes
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'output_products' 
  AND COLUMN_NAME IN ('name', 'description', 'price');
