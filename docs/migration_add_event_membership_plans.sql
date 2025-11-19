-- Migration: Add event_membership_plans table for MySQL
-- This table links events to membership plans with custom pricing

CREATE TABLE IF NOT EXISTS event_membership_plans (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    membership_plan_id CHAR(36) NOT NULL,
    ticket_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_event_membership (event_id, membership_plan_id),
    KEY idx_event_id (event_id),
    KEY idx_membership_plan_id (membership_plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
