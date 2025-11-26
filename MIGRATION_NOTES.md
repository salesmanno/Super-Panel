# Database Migration - Nov 26, 2025

## Fix: Event Membership Check-in Foreign Key

### Issue
Error #1005 - Foreign key constraint is incorrectly formed

### Root Cause
`event_membership_plans.id` is **CHAR(36)** (UUID), not INT

### Schema Discovery
```sql
event_membership_plans (
  `id` char(36) NOT NULL DEFAULT uuid(),
  PRIMARY KEY (`id`)
)
```

### Solution
Run this SQL in Domeneshop MySQL console:

```sql
ALTER TABLE events_checkin 
ADD COLUMN membership_plan_id CHAR(36) AFTER ticket_type, 
ADD CONSTRAINT fk_checkin_membership FOREIGN KEY (membership_plan_id) REFERENCES event_membership_plans(id) ON DELETE SET NULL;
```

### Why This Works
- Uses `CHAR(36)` to match `event_membership_plans.id` type
- References existing PRIMARY KEY
- Enables audit trail for membership-based pricing
- Allows NULL when event_membership_plans record is deleted

### Code Changes Made
- Updated `api/checkin_event.php` to store UUID string (not cast to INT)
- Removed incorrect INT UNSIGNED type assumption
