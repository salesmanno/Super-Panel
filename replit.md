# Salesman Superportal System

## Overview
The Salesman Superportal System is a comprehensive administration portal designed to centralize and streamline various company operations. It provides a unified platform for managing modules and settings based on user privileges, aiming to enhance efficiency across sales, webmail, reservations, economy, membership, rental, and events. The system's vision is to offer efficient oversight of diverse company functions through a single interface, delivering a single source of truth for company data.

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
The system is built on a PHP 8.2 backend with dual database support (MySQL production, PostgreSQL development/backup), emphasizing modularity and granular access control.

### UI/UX Decisions
The design prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs. The dashboard header displays the company subdomain with an active/inactive status badge and company logo.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation, failsafe messages, complete session tracking, and single-session enforcement.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component for content display.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables, with a dedicated interface for staff module access management.
- **Email System**: Complete email infrastructure with `EmailService.php` using PHPMailer for SMTP sending, `email_configs` table for SMTP/IMAP configuration, `email_accounts` for individual mailboxes, `email_templates` for reusable templates, `email_logs` for audit trail, and `automailer_settings` for per-module automated email triggers. Includes AutoMailer system with configurable email automation for Rental, Events, Membership, Reservations, and Sales modules. PostgreSQL boolean handling uses `pgBoolToPHP()` helper to properly convert 't'/'f' strings.
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, including configurable registration fields via `event_registration_fields` table (standard fields: first_name, last_name, email; optional: phone, street, zip, city, postal, age), scanner-based check-in, and income/expense tracking.
- **Ditt Nettsted Module**: Manages website configuration from `tenant_website_config` with an admin password-protected lock mechanism.
- **Reservation Module**: Complete reservation management with agenda/calendar views, filtering, statistics, and subtabs for service types. Includes visual calendar-based booking with staff availability.
- **Sales Module with Shopping Cart**: Supports dual purchase flows (shopping cart for products, individual service sales), barcode scanner integration, customer data management, and event linking. Includes hardcoded payment methods (Kontant, Vipps, Oppmøte) and a fullscreen mode. Features purchasing for registered and non-registered items with automatic stock tracking and weighted average cost calculation.
- **Deres Firma Module**: Manages company profile, staff, and website settings.
- **Unified Economic System**: Centralized financial tracking via `unified_ledger` table, aggregating all transactions with profit tracking, managed by `UnifiedLedgerService.php`. Includes comprehensive membership income overview.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregating all sellable items/services from different modules, auto-generated and synchronized by `ProductCatalogService.php`. Implements cascade-and-filter patterns for product deletion and activation status.
- **Database UUID Compliance**: MySQL production uses `char(36)` UUID format for `system_modules.id` and `output_products.module_id`.

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture**: Modular database design eliminating duplicates and redundancy, standardizing tables, adding cost price tracking, and introducing `service_bookings` and `unified_ledger` tables.

## Recent Changes
- **2025-11-04**: Implemented complete email system infrastructure with PHPMailer integration. Created `email_configs` (SMTP/IMAP), `email_accounts`, `email_folders`, `email_messages`, `email_drafts`, `email_attachments`, `automailer_settings`, and `email_templates` tables. Migrated from `x_webmailconfiguration` to `email_configs`. Built AutoMailer system with per-module toggles and per-event settings for automated emails. Created standard email templates (sale_receipt, event_registration, membership_welcome, rental_confirmation, service_booking). Fixed critical PostgreSQL boolean handling bug with `pgBoolToPHP()` helper in `api/get_automailer_settings.php`. MySQL migration: `docs/migration_email_system.sql`. PHPMailer installed via Composer (v7.0).
- **2025-11-03**: Implemented payment methods system with demo/sandbox support. Created `payment_methods` table for storing payment configurations. Added "Aktiver Demo" button in Economy module for Vipps sandbox mode with demo credentials. New APIs: `api/setup_demo_vipps.php` (activates/deactivates demo Vipps), `api/get_payment_methods.php` (returns available payment methods for frontend use). MySQL migration: `docs/migration_add_payment_methods.sql`. Economy module now displays payment methods with demo/sandbox badge and toggle functionality.
- **2025-11-03**: Fixed purchase_new_product.php - Ikke-registrerte varer (taklamper, etc) registreres nå KUN som utgift i `sales_purchase_history` og `unified_ledger`, IKKE som produkt i `sales_products`. Registrerte kioskvarer bruker fortsatt `purchase_existing_product.php` for varebeholdning.
- **2025-11-02**: Fixed `sales_purchase_history` table migration - changed `id` from INT to CHAR(36) for UUID support. MySQL migration: `docs/migration_add_sales_purchase_history.sql`
- **2025-11-02**: FIXED ALL CRITICAL BUGS - 1) Created `sales_purchase_history` table, 2) Fixed purchase APIs authentication (`tenant_admin_id` instead of `admin_id`), 3) Fixed database function calls (`getDBConnection()` instead of `getDB()`), 4) Fixed domain display to show only first part before hyphen (bergsoy.salesman.no instead of bergsoy-danseklubb.salesman.no). Files fixed: `api/purchase_existing_product.php`, `api/purchase_new_product.php`, `api/get_purchase_history.php`, `modules/deres_firma_content.php`
- **2025-11-02**: Implemented full support for ALL 44 columns in `tenant_website_general` including logo_url, favicon_url, homepage_module, hero_image_url, hero_button_url, social_media_links, footer_text, footer_links, custom_css, custom_js, google_analytics_id, maintenance_message, updated_by. Complete backend API with URL validation, frontend UI with organized sections (General, Design, Layout, Hero, Contact, Footer, Advanced), and MySQL migration available in `docs/migration_add_all_website_general_fields.sql`
- **2025-11-02**: Fixed innkjøp (purchase) subtab in Sales module - now correctly loads products from `sales_products` table via `get_sales_products.php` instead of all items. Created `sales_products` table in PostgreSQL with stock_quantity and low_stock_threshold columns. Added foreign key constraint `sales_products.output_product_id → output_products.id` (ON DELETE SET NULL). Fixed IFNULL → COALESCE for PostgreSQL compatibility
- **2025-11-01**: Added `event_registration_fields` table for configurable event registration fields. Migration: Run `api/migrate_event_registration_fields.php` or use `docs/migration_add_event_registration_fields.sql`
- **2025-11-01**: Fixed event creation to properly handle registration field boolean values for SMALLINT columns
- **2025-11-01**: Added `image_url` column to `x_tenant_announcements` for news/announcement image support

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL (Domeneshop)
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: PHPMailer (v7.0) for SMTP sending, IMAP for email retrieval.
- **Storage**: Object storage for contract document uploads.