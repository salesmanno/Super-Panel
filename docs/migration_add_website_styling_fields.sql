-- MySQL Migration: Add styling and layout fields to tenant_website_general
-- Run this on MySQL production database before deploying the updated code
-- Date: 2025-11-02

ALTER TABLE tenant_website_general 
ADD COLUMN IF NOT EXISTS header_bg_color VARCHAR(7) DEFAULT '#ffffff',
ADD COLUMN IF NOT EXISTS body_bg_color VARCHAR(7) DEFAULT '#f8f9fa',
ADD COLUMN IF NOT EXISTS logo_height VARCHAR(10) DEFAULT '50px',
ADD COLUMN IF NOT EXISTS header_height VARCHAR(10) DEFAULT '80px',
ADD COLUMN IF NOT EXISTS logo_position VARCHAR(20) DEFAULT 'left',
ADD COLUMN IF NOT EXISTS nav_position VARCHAR(20) DEFAULT 'center',
ADD COLUMN IF NOT EXISTS font_family VARCHAR(100) DEFAULT 'Inter, sans-serif',
ADD COLUMN IF NOT EXISTS button_style VARCHAR(20) DEFAULT 'rounded',
ADD COLUMN IF NOT EXISTS border_radius VARCHAR(10) DEFAULT '16px',
ADD COLUMN IF NOT EXISTS spacing_unit VARCHAR(10) DEFAULT '1rem';

-- Verify columns were added
DESCRIBE tenant_website_general;
