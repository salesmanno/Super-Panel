-- Migration: Add event_membership_plans table
-- Purpose: Link events to specific membership plans that qualify for member pricing
-- Date: 2025-11-13

-- Create event_membership_plans table
CREATE TABLE IF NOT EXISTS `event_membership_plans` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `event_id` char(36) NOT NULL,
  `membership_plan_id` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_plan` (`event_id`, `membership_plan_id`),
  KEY `idx_event_membership_event` (`event_id`),
  KEY `idx_event_membership_plan` (`membership_plan_id`),
  CONSTRAINT `event_membership_plans_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_membership_plans_ibfk_2` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Purpose:
-- This table allows you to specify which membership plans qualify for member pricing on each event.
-- If no plans are linked to an event, all active members get member pricing.
-- If specific plans are linked, only members with those plans get member pricing.
