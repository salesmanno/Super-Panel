-- Migration: Add event_registration_fields table
-- Date: 2025-11-01
-- Purpose: Create table for storing configurable event registration fields
-- Supports both MySQL and PostgreSQL

-- PostgreSQL version
CREATE TABLE IF NOT EXISTS event_registration_fields (
    id VARCHAR(255) PRIMARY KEY,
    event_id VARCHAR(255) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    field_type VARCHAR(50) NOT NULL DEFAULT 'text',
    is_required SMALLINT NOT NULL DEFAULT 0,
    is_visible SMALLINT NOT NULL DEFAULT 1,
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events_eventdata(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_event_registration_fields_event_id ON event_registration_fields(event_id);

-- MySQL version (same structure, slightly different syntax)
-- CREATE TABLE IF NOT EXISTS event_registration_fields (
--     id VARCHAR(255) PRIMARY KEY,
--     event_id VARCHAR(255) NOT NULL,
--     field_name VARCHAR(100) NOT NULL,
--     field_label VARCHAR(255) NOT NULL,
--     field_type VARCHAR(50) NOT NULL DEFAULT 'text',
--     is_required TINYINT(1) NOT NULL DEFAULT 0,
--     is_visible TINYINT(1) NOT NULL DEFAULT 1,
--     display_order INT NOT NULL DEFAULT 0,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (event_id) REFERENCES events_eventdata(id) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 
-- CREATE INDEX idx_event_registration_fields_event_id ON event_registration_fields(event_id);

-- Notes:
-- - Standard fields (first_name, last_name, email) are always created for each event
-- - Optional fields (phone, street, zip, city, postal, age) can be added per event
-- - display_order controls the order fields appear in registration forms
-- - is_required controls whether the field must be filled
-- - is_visible controls whether the field is shown (future feature)
