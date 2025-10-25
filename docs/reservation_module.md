# Reservation Module Documentation

## Overview
The Reservation Module provides comprehensive reservation management with staff assignment, date filtering, and status tracking.

## Module Structure

### Subtabs
1. **Reservasjonsoversikt** (Primary view)
   - First subtab, shows upcoming reservations
   - Configurable date range filter (7, 14, 30, 60, 90 days)
   - Staff member filter
   - Status filter (pending, confirmed, cancelled, completed)
   - Real-time statistics cards
   - Action buttons for view, confirm, cancel

2. **Reservasjonsmottak**
   - Pending reservations awaiting confirmation
   - Quick overview of incoming requests

3. **Bekreftede Reservasjoner**
   - Confirmed reservations with date range search
   - Historical view of completed bookings

## Database Tables

### `reservations_reservations`
Main reservation table with columns:
- `id` (UUID) - Primary key
- `company_id` (UUID) - Company reference
- `resource_id` (UUID) - Resource being reserved
- `staff_id` (UUID) - Assigned staff member
- `customer_id` (UUID) - Customer reference (nullable)
- `title` (VARCHAR) - Reservation title
- `description` (TEXT) - Additional details
- `start_datetime` (DATETIME) - Reservation start
- `end_datetime` (DATETIME) - Reservation end
- `total_amount` (DECIMAL) - Total price
- `deposit_amount` (DECIMAL) - Deposit required
- `currency` (VARCHAR) - Currency code (default: NOK)
- `status` (VARCHAR) - Status: pending, confirmed, cancelled, completed
- `guest_count` (INT) - Number of guests
- `is_recurring` (BOOLEAN) - Recurring reservation flag
- `recurrence_pattern` (VARCHAR) - Pattern for recurring reservations
- `recurrence_end_date` (DATE) - End date for recurring pattern
- `created_by` (UUID) - Creator reference
- `created_at` (DATETIME) - Creation timestamp
- `updated_at` (DATETIME) - Last update timestamp

### `reservations_resources`
Resources available for reservation:
- `id` (UUID) - Primary key
- `company_id` (UUID) - Company reference
- `resource_name` (VARCHAR) - Resource name
- `resource_type` (VARCHAR) - Type: room, facility, equipment, etc.
- `description` (TEXT) - Resource description
- `capacity` (INT) - Maximum capacity
- `location` (VARCHAR) - Physical location
- `hourly_rate` (DECIMAL) - Hourly pricing
- `daily_rate` (DECIMAL) - Daily pricing
- `is_available` (BOOLEAN) - Availability flag
- `features` (TEXT) - Resource features/amenities
- `image_url` (VARCHAR) - Resource image
- `created_by` (UUID) - Creator reference
- `created_at` (DATETIME) - Creation timestamp
- `updated_at` (DATETIME) - Last update timestamp

### `tenant_staff`
Staff assignments reference this table for assigned personnel.

## API Endpoints

### `/api/get_reservations_overview.php`
**Method**: GET  
**Purpose**: Fetch reservations with filtering

**Query Parameters**:
- `days` (int) - Number of days forward to show (default: 14)
- `staff_id` (UUID) - Filter by staff member (optional)
- `status` (string) - Filter by status (optional)

**Response**:
```json
{
  "success": true,
  "reservations": [
    {
      "id": "uuid",
      "title": "Møte med kunde",
      "start_datetime": "2025-10-27 13:50:00",
      "end_datetime": "2025-10-27 15:50:00",
      "staff_name": "Atle Karl Kvalsvik",
      "customer_name": "Kunde",
      "guest_count": 4,
      "total_amount": "1000.00",
      "currency": "NOK",
      "status": "confirmed"
    }
  ],
  "stats": {
    "total": 4,
    "pending": 2,
    "confirmed": 2,
    "cancelled": 0,
    "completed": 0,
    "total_value": 9100.00
  }
}
```

### `/api/get_staff.php`
**Method**: GET  
**Purpose**: Fetch active staff for filter dropdown

**Response**:
```json
{
  "success": true,
  "staff": [
    {
      "id": "uuid",
      "first_name": "Atle Karl",
      "last_name": "Kvalsvik",
      "email": "atle@example.com",
      "job_title": "Manager",
      "is_active": 1
    }
  ]
}
```

## Features

### Statistics Dashboard
- **Total Reservations**: Count of all reservations in date range
- **Pending**: Reservations awaiting confirmation
- **Confirmed**: Approved reservations
- **Total Value**: Sum of all reservation amounts

### Filtering Capabilities
1. **Date Range**: Choose how many days forward to display
2. **Staff Assignment**: Filter by specific staff member
3. **Status**: Filter by reservation status
4. **Real-time Updates**: Click "Oppdater" to refresh data

### Action Buttons
- **View** (👁️): View full reservation details
- **Confirm** (✓): Approve pending reservation
- **Cancel** (✕): Cancel/reject reservation

## User Interface

### Overview Table Columns
1. **Dato & Tid**: Date and time range with day name
2. **Kunde**: Customer name
3. **Ansatt**: Assigned staff member
4. **Tittel**: Reservation title
5. **Antall gjester**: Number of guests
6. **Beløp**: Total amount with currency
7. **Status**: Color-coded status badge
8. **Handling**: Action button group

### Status Badges
- 🟡 **Ventende** (Pending) - Yellow badge
- 🟢 **Bekreftet** (Confirmed) - Green badge
- 🔴 **Kansellert** (Cancelled) - Red badge
- 🔵 **Fullført** (Completed) - Blue badge

## Implementation Details

### Collation Fix
All database queries include `SET collation_connection = 'utf8mb4_general_ci'` to prevent collation mismatch errors between MariaDB 11.4.7 default (utf8mb4_0900_ai_ci) and table definitions (utf8mb4_general_ci).

### JavaScript Functions
- `loadStaffFilter()`: Populates staff dropdown on page load
- `loadReservationsOverview()`: Fetches and displays reservations
- `updateStatistics()`: Updates stat cards with current data
- `displayReservations()`: Renders reservation table rows
- `getStatusBadge()`: Returns HTML for status badges
- `viewReservation(id)`: View reservation details (placeholder)
- `confirmReservation(id)`: Confirm pending reservation (placeholder)
- `cancelReservation(id)`: Cancel reservation (placeholder)

## Test Data
4 sample reservations created for demonstration:
1. "Møte med kunde" - 2 days forward, confirmed, 4 guests, 1000 NOK
2. "Treningsøkt" - 5 days forward, pending, 2 guests, 600 NOK
3. "Workshop" - 7 days forward, pending, 8 guests, 2500 NOK
4. "Bedriftsarrangement" - 10 days forward, confirmed, 15 guests, 5000 NOK

## Files Modified/Created

### New Files
- `api/get_reservations_overview.php` - API for fetching filtered reservations
- `api/get_staff.php` - API for fetching active staff
- `docs/reservation_module.md` - This documentation

### Modified Files
- `pages/company_dashboard.php` - Updated subtabs (added Reservasjonsoversikt)
- `modules/reservations_content.php` - Complete rewrite with overview tab
- `replit.md` - Updated with reservation module info

## Future Enhancements
- Implement detail view modal
- Add reservation creation form
- Implement confirm/cancel API endpoints
- Add calendar view option
- Email notifications for confirmations
- Recurring reservation management
- Resource availability checking
- Customer management integration
