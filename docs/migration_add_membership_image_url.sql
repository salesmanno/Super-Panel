-- Migration: Add image_url column to membership_plans table
-- Date: 2025-11-07
-- Purpose: Support image upload for membership plans

-- For MySQL (production database)
ALTER TABLE membership_plans ADD COLUMN image_url TEXT;

-- For PostgreSQL (development/fallback database)
-- Already applied via execute_sql_tool:
-- ALTER TABLE x_membership_plan ADD COLUMN image_url TEXT;
