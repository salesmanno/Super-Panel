# Salesman Superportal System

## Overview
The Salesman Superportal System is an administration portal designed to centralize and streamline company operations. It provides a unified platform for managing modules and settings based on user privileges, enhancing efficiency across sales, webmail, reservations, economy, membership, rental, and events. The system aims to offer efficient oversight of diverse company functions through a single interface, delivering a single source of truth for company data and providing a competitive advantage in the market.

## User Preferences
- Language: Norwegian
- Clean, compact dashboard design (not oversized)
- Simple navigation header
- Focus on functionality over visual complexity
- Modules should be displayed as tabs (faner) instead of cards
- Only show modules where user has privileges (can_access = true)
- Each module tab should have sub-tabs with the same name showing current content
- Unlock feedback must be clear and visible (green button, countdown timer, success message)

## System Architecture
The system is built on a PHP 8.2 backend with dual database support, emphasizing modularity and granular access control.

### UI/UX Decisions
The design prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs. The dashboard header displays the company subdomain with an active/inactive status badge and company logo.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation, failsafe messages, complete session tracking, and single-session enforcement.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component for content display.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables, with an interface for staff module access management.
- **Email Module**: Integrated email client with SMTP configuration validation and `EmailService.php` for cross-module automated notifications.
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields, scanner-based check-in, membership-based pricing, dynamic payment methods per event, and income/expense tracking.
  - **Check-in System**: Two-column todelt layout (innsjekket/ikke innsjekket) with admin tracking, EAN13 barcode generation via `BarcodeService.php`, and membership plan references in `events_checkin`.
  - **Results Module**: Horizontal stats display with 7 metrics including most profitable/expensive/attended events.
- **Ditt Nettsted Module**: Manages website configuration from `tenant_website_config` with an admin password-protected lock mechanism.
- **Reservation Module**: Complete reservation management with agenda/calendar views, filtering, statistics, and subtabs for service types, including visual calendar-based booking with staff availability.
- **Rental Module**: Professional multi-type rental management system using table-per-type architecture for 12 different rental object types. Features include dynamic form generation, comprehensive metadata schemas, CASCADE deletion, driver's license/certificate requirements, image support, and summary badges.
- **Sales Module with Shopping Cart**: Supports dual purchase flows, barcode scanner integration, customer data management, and event linking. Includes hardcoded payment methods, fullscreen mode, automatic stock tracking, and weighted average cost calculation.
- **Deres Firma Module**: Manages company profile, staff, and website settings.
- **Unified Economic System**: Centralized financial tracking via `unified_ledger` table, aggregating all transactions with profit tracking, managed by `UnifiedLedgerService.php`. Includes comprehensive membership income overview.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregating all sellable items/services from different modules, auto-generated and synchronized by `ProductCatalogService.php`. Implements cascade-and-filter patterns for product deletion and activation status.

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture**: Modular database design eliminating duplicates and redundancy, standardizing tables, adding cost price tracking, and introducing `service_bookings` and `unified_ledger` tables.
- **Database UUID Compliance**: MySQL production uses `char(36)` UUID format for `system_modules.id` and `output_products.module_id`.
- **Event Check-in Data Integrity**: `events_checkin` stores both `membership_plan_id` (FK reference to `event_membership_plans` for auditability) and `ticket_price` (snapshot of price at time of check-in for historical accuracy). Added `barcode` (VARCHAR(13)) and `qrcode` (LONGBLOB) columns for EAN13 and QR code storage.

## Recent Changes (Nov 27, 2025 - COMPLETE SESSION)
**Barcode & Financial System - FULLY IMPLEMENTED:**
1. **Automatic EAN13 Barcode Generation** - Implemented `BarcodeService::generateEAN13()` integration in all 3 check-in creation points (`checkin_event.php`, `register_guest.php`, `process_service_sale.php`). Barcodes now auto-assign on record creation with format: MD5-hash-based 12 digits + Luhn check digit.
2. **Removed profit column from UnifiedLedgerService** - Fixed "Unknown column 'profit'" error by removing non-existent profit column from INSERT. Profit now calculated as `selling_price - cost_price` in application layer.
3. **Fixed event creator display ("Ukjent bruker")** - Added LEFT JOIN to `tenant_staff` in `get_events.php` to fetch creator's `first_name` and `last_name`.
4. **Prevented duplicate events_results records** - Implemented atomic `ON DUPLICATE KEY UPDATE` in three locations to eliminate race conditions.
5. **Fixed expenses logging bug** - Removed `UNIQUE KEY unique_transaction` constraint from `unified_ledger` table that blocked multiple transactions per event. Now supports both income and expense entries per arrangement.
6. **Fixed event title HTML rendering** - Changed `.textContent` to `.innerHTML` in event detail modal so Bootstrap icons render correctly (e.g., `📅 Julenissefest`).
7. **Fixed event bookings ("eventbestillinger") loading** - Corrected all relative API paths from `../api/` to `/api/` (8 occurrences) in `modules/events_content.php`. Affected functions: `loadPendingRegistrations()`, `approveRegistration()`, `rejectRegistration()`, and all event CRUD operations.
8. **Added barcode columns to check-in display** - Both "Innsjekket" and "Ikke innsjekket" tables now show barcode column with monospace font.

## Earlier Changes (Nov 26, 2025)
- Todelt Check-in Layout, Horisontale Event Stats, EAN13 Barcode Service, Barcode Assignment API, Check-in API Enhancement, JavaScript Check-in Rendering, Database Schema Updates

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.
- **Barcode Generation**: BarcodeService with EAN13 support (no external dependencies, pure PHP)
