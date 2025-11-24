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
- **2025-11-24 (SESSION 8 - PRODUCTS INVENTORY FEATURE ADDED)**:
  - **NEW FEATURE**: Added "Oppføringer" (Products Inventory) tab in dashboard
  - **Overview**: Shows ALL output_products with active/inactive status and counts
  - **Functionality**:
    - View all products from all modules in one place
    - Filter by status (active/inactive/all)
    - Search by product name
    - Reactivate inactive products (set is_visible = 1)
    - Hard delete products with confirmation
    - Real-time statistics (total, active, inactive, modules count)
  - **Files Created**:
    - `api/get_output_products_inventory.php` - Fetch all products with stats
    - `api/reactivate_product.php` - Reactivate (un-hide) products
    - `api/delete_product.php` - Permanently delete products
    - `modules/products_inventory_content.php` - Frontend UI with Bootstrap table
  - **Dashboard Changes**:
    - Added "Oppføringer" tab (📋 icon) between modules and News
    - Always visible for all admins (like News tab)
    - Tab content loads automatically on click
  - **User Experience**:
    - Stats cards show: Total, Active, Inactive, Module Count
    - Responsive table with inline action buttons
    - Toast notifications for success/error feedback
    - Empty state message when no products found

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