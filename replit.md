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
- **Authentication**: Session-based system with robust backend validation, complete session tracking, and single-session enforcement.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables.
- **Email Module**: Integrated email client with SMTP configuration and `EmailService.php` for automated notifications.
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields, scanner-based check-in, membership-based pricing, dynamic payment methods, and income/expense tracking. Includes a two-column check-in system with EAN13 barcode generation and a results module with key metrics.
- **Ditt Nettsted Module**: Manages website configuration from `tenant_website_config` with an admin password-protected lock.
- **Reservation Module**: Complete reservation management with agenda/calendar views, filtering, statistics, and visual calendar-based booking.
- **Rental Module**: Professional multi-type rental management using table-per-type architecture for 12 object types, featuring dynamic form generation, metadata schemas, and image support.
- **Sales Module with Shopping Cart**: Supports dual purchase flows, barcode scanner integration, customer data management, event linking, automatic stock tracking, and weighted average cost calculation.
- **Deres Firma Module**: Manages company profile, staff, and website settings.
- **Unified Economic System**: Centralized financial tracking via `unified_ledger` table, aggregating all transactions with profit tracking, managed by `UnifiedLedgerService.php`. Supports income/expense tracking across various modules. Economy module features live data display with overview, income, expense tabs; manual transaction entry with 16 expense categories; payment gateway management with toggle functionality.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregating all sellable items/services, auto-generated and synchronized by `ProductCatalogService.php`.
- **Polling Tracker System**: Real-time update notifications via `tenant_polling_tracker` table. External systems write to the table, dashboard polls every 30 seconds via `api/check_refresh_flag.php`, shows toast notification and auto-refreshes when updates are detected.

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture**: Modular database design eliminating duplicates, standardizing tables, adding cost price tracking, and introducing `service_bookings` and `unified_ledger` tables.
- **Database UUID Compliance**: MySQL production uses `char(36)` UUID format for `system_modules.id` and `output_products.module_id`.
- **Event Check-in Data Integrity**: `events_checkin` stores `membership_plan_id` and `ticket_price` for historical accuracy, with `barcode` and `qrcode` columns for EAN13 and QR code storage.
- **Payment Gateway Configuration**: All payment method availability is controlled by the Economy module via `economy_payment_gateways` table, requiring specific credentials for each gateway (Vipps, Stripe, PayPal). Cash payment availability is controlled by `tenant_companies.cash_payment`.

## External Dependencies
- **Database**: Dual database support with PDO (Production: MySQL, Development/Testing: PostgreSQL).
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.
- **Barcode Generation**: BarcodeService with EAN13 support (pure PHP).