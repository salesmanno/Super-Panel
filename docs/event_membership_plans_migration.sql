-- Migration: Events Enhancement - Payment Timing & Membership Plans
-- Purpose: 
--   1. Add payment_timing to control when payment occurs (on_arrival, immediate, both)
--   2. Add event_membership_plans junction table for membership plan integration
-- Date: 2025-11-13

-- Step 1: Add payment_timing column to events_eventdata
ALTER TABLE `events_eventdata` 
ADD COLUMN IF NOT EXISTS `payment_timing` VARCHAR(20) DEFAULT 'both' 
COMMENT 'Payment timing: on_arrival (betales ved oppmøte), immediate (betales nå), both (deltaker velger)';

-- Step 2: Create event_membership_plans junction table
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

-- Implementation Notes:
-- 
-- PAYMENT_TIMING LOGIC:
-- - 'on_arrival': Payment must be made at the event (cash, etc.)
-- - 'immediate': Payment must be completed online during registration
-- - 'both': Participant chooses to pay now or on arrival
-- - Works independently of payment_methods (which controls HOW to pay)
--
-- MEMBERSHIP PLANS LOGIC:
-- - Empty table for an event = all active members qualify for member pricing
-- - Specific plans linked = only those plans qualify for member pricing
-- - Works with is_member_exclusive flag for access control
