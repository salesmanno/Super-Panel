# Salesman Superportal System

## Overview
The Salesman Superportal System is an administration portal designed to centralize and streamline company operations. It provides a unified platform for managing modules and settings based on user privileges, enhancing efficiency across sales, webmail, reservations, economy, membership, rental, and events. The system aims to offer efficient oversight of diverse company functions through a single interface, delivering a single source of truth for company data and providing a competitive advantage in market.

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
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields via `event_registration_fields` table (standard fields: first_name, last_name; optional: email, phone, street, zip, city, postal, age), scanner-based check-in, income/expense tracking, and membership plan integration. Events can specify which membership plans qualify for member pricing via `event_membership_plans` junction table - if no plans are specified, all active members qualify. Payment methods are dynamically loaded from the economy module's `payment_methods` table, with cash payment always available based on company settings. The `payment_timing` field controls when payment occurs (on_arrival, immediate, both) independently of payment method selection, allowing flexible payment workflows.
- **Ditt Nettsted Module**: Manages website configuration from `tenant_website_config` with an admin password-protected lock mechanism.
- **Reservation Module**: Complete reservation management with agenda/calendar views, filtering, statistics, and subtabs for service types, including visual calendar-based booking with staff availability.
- **Rental Module**: Professional multi-type rental management system using table-per-type architecture for 12 different rental object types. Features include dynamic form generation, comprehensive metadata schemas, CASCADE deletion, driver's license/certificate requirements, image support, and summary badges.
- **Sales Module with Shopping Cart**: Supports dual purchase flows (shopping cart for products, individual service sales), barcode scanner integration, customer data management, and event linking. Includes hardcoded payment methods and a fullscreen mode, with automatic stock tracking and weighted average cost calculation.
- **Deres Firma Module**: Manages company profile, staff, and website settings.
- **Unified Economic System**: Centralized financial tracking via `unified_ledger` table, aggregating all transactions with profit tracking, managed by `UnifiedLedgerService.php`. Includes comprehensive membership income overview.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregating all sellable items/services from different modules, auto-generated and synchronized by `ProductCatalogService.php`. Implements cascade-and-filter patterns for product deletion and activation status.

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture**: Modular database design eliminating duplicates and redundancy, standardizing tables, adding cost price tracking, and introducing `service_bookings` and `unified_ledger` tables.
- **Database UUID Compliance**: MySQL production uses `char(36)` UUID format for `system_modules.id` and `output_products.module_id`.

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.