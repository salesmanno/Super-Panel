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
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields, scanner-based check-in, membership-based pricing, dynamic payment methods per event (with "oppmøtebetaling" always included), and income/expense tracking.
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

## Recent Changes
- **2025-11-24 (SESSION 8+ - EVENT REGISTRATION FIELDS CRITICAL BUG FIXED)**:
  - **ISSUE**: `event_registration_fields` table was completely empty - no fields saved when events created
  - **ROOT CAUSE**: Checkboxes in create/edit event forms lacked `value="1"` attributes
    - Checkboxes without `value` attributes submit "on" when checked
    - Form used: `formData.get('field_email') ? 1 : 0` to convert truthy/falsy to 1/0
    - However, this approach was unreliable with missing value attributes
    - Result: Registration field preferences not consistently sent to API
  - **SOLUTION**:
    - Added explicit `value="1"` to ALL registration field checkboxes in create form (lines 378, 386, 394, 402, 412, 420)
    - Added explicit `value="1"` to ALL registration field checkboxes in edit form (lines 831, 839, 847, 855, 865, 873)
    - Now FormData explicitly sends "1" when checked, null when unchecked
    - JavaScript correctly converts: "1" (truthy) → 1, null (falsy) → 0
  - **Impact**: Going forward, all events will properly save their registration field configuration
  - **Database**: Existing event (Julefest) needs to be recreated with registration fields now that form is fixed

- **2025-11-24 (SESSION 8 - EVENT CHECK-IN CONSISTENCY FIXED)**:
  - **BUG FOUND**: `api/register_guest.php` lagret i `attendee_first_name`/`attendee_last_name`, mens `api/checkin_event.php` lagret i `attendee_name` 
  - **PROBLEM**: Data-inkonsistens - guest registrations og check-ins i ulike formater
  - **Root Cause**: `register_guest.php` brukte feil INSERT kolonner
  - **Solution Applied**:
    - Fikset `api/register_guest.php` til å kombinere first+last name til `attendee_name`
    - Lagt til `company_id` i INSERT (var manglende)
    - Lagt til `check_in_time` kolonne (NOW())
    - Nå konsistent med `api/checkin_event.php`
  - **Database**: Schema har redundante kolonner (attendee_first_name, attendee_last_name, etc) som ikke lenger brukes - kan ryddes senere
  - **Result**: Check-ins og guest registrations nå i samme format

- **2025-11-24 (SESSION 8 - EVENT FORM FIELD ORGANIZATION FIXED)**:
  - **FIXED**: Reordered location fields in both create and edit event forms
  - **Previous order**: Stedsnavn, By, Postnummer, Adresse → **New order**: Stedsnavn, Gateadresse, Postnummer, Poststed
  - **Create form**: Added missing `field_zip` and `field_city` checkboxes in registration fields section
  - **Edit form**: Removed unnecessary `field_postal` checkbox (only zip and city needed)
  - **Location fields**: Gateadresse now full-width, Postnummer + Poststed on same row
  - **Contact fields**: E-post, Telefon, Gateadresse, Postnummer (row 1) → Poststed, Alder (row 2)
  - **Database alignment**: All form fields now properly match `events_eventdata` columns (street, postal, city)
  - **Verified**: PHP syntax checked, workflow restarted successfully

- **2025-11-24 (SESSION 8 - PRODUCTS INVENTORY MODAL INTEGRATED)**:
  - **NEW FEATURE**: Products/services overview in header with clickable link
  - **Location**: Under "Du har tilgang til X moduler" in dashboard header
  - **Header Display**: Shows "...og har 3 oppføringer (2 aktive)" as clickable link
  - **Modal Features**:
    - Full inventory view when clicked
    - Reaktivating inactive products (toggle is_visible)
    - Hard delete with confirmation dialog
    - Real-time updates in modal
    - Active/inactive status badges
    - Module names for each product
  - **Files Created**:
    - `api/get_output_products_inventory.php` - Fetch all products with stats
    - `api/reactivate_product.php` - Reactivate/deactivate products
    - `api/delete_product.php` - Permanently delete products
  - **Dashboard Changes**:
    - Updated `loadProductsSummary()` to use inventory API
    - Enhanced `showProductsModal()` with inline actions
    - Added `toggleInventoryProduct()` for status changes
    - Added `deleteInventoryProduct()` with proper cleanup
    - Header text now clickable with underline hover effect
  - **User Experience**:
    - Header is compact (single line)
    - Click the product count to see full overview modal
    - Toast notifications for all actions
    - Removed separate "Oppføringer" tab (integrated into header instead)

- **2025-11-24 (SESSION 8 - ANNOUNCEMENTS ERROR HANDLING FIXED)**:
  - **BUG FOUND**: Only nyheter/announcements produced error messages on login (events worked fine)
  - **ROOT CAUSE #1**: `loadAnnouncements()` frontend lacked HTTP status check (`if (!response.ok)`), so API errors (HTTP 500) were silently caught and not displayed
  - **ROOT CAUSE #2**: `get_announcements.php` never returned `author_name` field, but frontend tried to display it (showed "Ukjent" as fallback)
  - **Solution**:
    - Added HTTP status validation to frontend `loadAnnouncements()`
    - Added error toast notification showing actual error message
    - Added error display in announcements container with warning icon
    - Updated API to JOIN with `tenant_admins` and return `author_name` field
  - **Why this helps**: Now if API fails (database down, etc), user sees clear error message instead of silent failure
  - **Comparison**: All other modules (events, membership, etc) already had proper error handling - announcements was the outlier

- **2025-11-24 (SESSION 8 - CHECK-IN COLUMN MISMATCH FIXED)**:
  - **BUG FIXED**: `checkin_event.php` was trying to INSERT into non-existent columns
  - **Problem**: Used `attendee_first_name`, `attendee_last_name`, `attendee_street`, `attendee_zip`, `attendee_city`, `attendee_postal` - but actual schema only has `attendee_name`
  - **Solution**: 
    - Removed non-existent columns from INSERT statement
    - Combined first/last name into single `attendee_name` field
    - Added `company_id` column (was missing)
    - Removed street/zip/city/postal fields (not part of check-in schema)
  - **Frontend**: Enhanced `loadCheckins()` with HTTP status checking and error display
  - **Result**: Check-ins now save correctly to database

## External Dependencies
- **Database**: Dual database support with PDO, primarily MySQL, with PostgreSQL for development/testing.
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.