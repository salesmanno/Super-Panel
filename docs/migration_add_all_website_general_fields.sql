-- ================================================
-- MIGRATION: Add all missing fields to tenant_website_general
-- Run this on your MySQL production database
-- ================================================
-- Date: 2025-11-02
-- Description: Adds all missing columns to tenant_website_general table
--              including logo_url, favicon_url, homepage_module, hero_image_url,
--              hero_button_url, social_media_links, footer_text, footer_links,
--              custom_css, custom_js, google_analytics_id, maintenance_message, updated_by

USE salesmanno04;

-- Add missing columns to tenant_website_general
ALTER TABLE tenant_website_general
ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) DEFAULT NULL COMMENT 'Logo image URL',
ADD COLUMN IF NOT EXISTS favicon_url VARCHAR(500) DEFAULT NULL COMMENT 'Favicon image URL',
ADD COLUMN IF NOT EXISTS homepage_module VARCHAR(100) DEFAULT NULL COMMENT 'Default homepage module (products, events, etc)',
ADD COLUMN IF NOT EXISTS hero_image_url VARCHAR(500) DEFAULT NULL COMMENT 'Hero section background image URL',
ADD COLUMN IF NOT EXISTS hero_button_url VARCHAR(255) DEFAULT NULL COMMENT 'Hero section button URL',
ADD COLUMN IF NOT EXISTS social_media_links TEXT DEFAULT NULL COMMENT 'Social media links in JSON format',
ADD COLUMN IF NOT EXISTS footer_text TEXT DEFAULT NULL COMMENT 'Footer copyright text',
ADD COLUMN IF NOT EXISTS footer_links TEXT DEFAULT NULL COMMENT 'Footer navigation links in JSON format',
ADD COLUMN IF NOT EXISTS custom_css TEXT DEFAULT NULL COMMENT 'Custom CSS code',
ADD COLUMN IF NOT EXISTS custom_js TEXT DEFAULT NULL COMMENT 'Custom JavaScript code',
ADD COLUMN IF NOT EXISTS google_analytics_id VARCHAR(100) DEFAULT NULL COMMENT 'Google Analytics tracking ID',
ADD COLUMN IF NOT EXISTS maintenance_message TEXT DEFAULT NULL COMMENT 'Message shown during maintenance mode',
ADD COLUMN IF NOT EXISTS updated_by INT DEFAULT NULL COMMENT 'ID of admin who last updated this record';

-- ================================================
-- VERIFICATION
-- ================================================
-- Run this query to verify all columns were added:
-- DESCRIBE tenant_website_general;

-- Expected columns (in addition to existing ones):
-- logo_url, favicon_url, homepage_module, hero_image_url, hero_button_url,
-- social_media_links, footer_text, footer_links, custom_css, custom_js,
-- google_analytics_id, maintenance_message, updated_by

-- ================================================
-- MIGRATION COMPLETE
-- ================================================
