# Salesman Superportal System

## Overview
The Salesman Superportal System is a comprehensive administration portal designed to centralize and streamline various company operations. It provides a unified platform for managing modules and settings based on user privileges, aiming to enhance efficiency across sales, webmail, reservations, economy, membership, rental, and events. The system's vision is to offer efficient oversight of diverse company functions through a single interface.

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
The design prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs (faner). Official modules (Dashboard, Ditt Nettsted) are displayed in a dedicated tab row, while other modules (Webmail, Reservasjoner, Arrangement, etc.) are shown in a separate row below. Each module displays its specific subtabs in a dedicated row beneath the main module tabs. Subtabs use namespaced IDs (e.g., events-my, membership-plans) and JavaScript scopes content switching to the active module container. The design includes a blue hero banner and consistent use of company logos. The Dashboard header displays the company subdomain (subdomain.salesman.no) with an active/inactive status badge and company logo.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation, failsafe messages, and complete session tracking (login/logout, IP, user agents, activity, auto-cleanup). Single session enforcement logs out existing sessions when an admin logs in.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component with `shown.bs.tab` event listeners for proper content display without stacking.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables, ensuring modules are visible only based on user privileges. "Ditt Nettsted" module is automatically granted to all admins as an official system module for website template selection.
- **Email Module**: Integrated email client (inbox, compose, view) with SMTP configuration validation from `email_configs` table.
- **Cross-Module Email Integration (Oct 2025)**: Implemented `EmailService.php` as the unified email layer for automated notifications across all modules. Uses PHP's mail() function with template rendering from `email_templates` table and activity logging in `email_logs`. Email is the ONLY cross-module integration point to maintain modularity. Supports sale receipts (checkout), event confirmations (checkin), and future membership/rental confirmations.
- **Events Module**: Comprehensive CRUD operations for event management (`events_eventdata`) with an integrated `output_products` system. Features include event details, attendee limits, age restrictions, ticket pricing (with optional member pricing), images, and a member-exclusive toggle. Includes a check-in system (`events_checkin`) for attendee tracking and payment processing.
- **User Profiles**: Stores personal information for automatic email signatures and profile editing.
- **Ditt Nettsted Module (Website Builder)**: Official module accessible to all admins for viewing website configuration. Displays current website settings from `tenant_website_config` table including publication status, SEO settings, and display information. Features lock mechanism based on `granted_at` timestamp - once configured, settings are locked and require admin password (`tenant_companies.admin_password_hash`) for 30-minute temporary editing access. Password unlock is session-based and expires automatically.
- **Rental System**: Manages individual rental objects with serial number tracking, condition notes, and integrates with the reservation system for booking, overlap checks, and pricing. Supports contract document uploads.
- **Reservation Module (Oct 2025)**: Complete reservation management system with dual-view display and five subtabs:
    - **Reservasjonsoversikt**: Primary view with toggle between agenda and calendar modes. Agenda shows chronological list with date grouping. Calendar shows monthly grid with color-coded reservations by staff (10 colors). Features include date range filter (agenda: 7-90 days), staff filter, status filter, statistics cards (total, pending, confirmed, value), and month navigation (calendar). Timezone-safe date handling prevents UTC conversion issues.
    - **Reservasjonsmottak**: Incoming/pending reservations awaiting confirmation.
    - **Bekreftede Reservasjoner**: Confirmed reservations with date range search.
    - **Opprett tjenester** (Oct 2025): Create new reservable services with comprehensive settings (service name, type, description, duration, pricing, capacity, availability schedule, active days). Similar to product creation workflow.
    - **Aktive tjenester** (Oct 2025): Table view of all active reservable services with edit/delete actions. Shows service details (type, duration, price, capacity, availability, status).
    - Uses `reservations_reservations` table with staff assignments, date/time tracking, guest counts, and status management (pending, confirmed, cancelled, completed).
    - API supports both days-from-now and explicit date range parameters for flexible data fetching.
- **Sales Module with Shopping Cart (Oct 2025)**: Supports dual purchase flows:
    - **Shopping Cart System**: For products (`product_type='product'`) with simplified card display showing product image, name, and price. Cart badge displays total quantity count (sum of all items). Cart persists per admin session.
    - **Service Sales**: Events, memberships, and rentals are sold individually via a dedicated purchase modal requiring customer information (first name, last name, email, phone). Customer data is automatically saved/updated in `tenant_customers` table with proper name splitting.
    - **Product Management**: Products support image URLs (`img_product`) and MVA-taxable flag (`item_taxable`) for tax compliance.
- **Dynamic Database Status**: Live database connection status indicator in the navigation with visual feedback and graceful degradation upon disconnection.
- **Payment Gateway System**: Simplified payment system using `tenant_companies.cash_payment` boolean. The Economy module displays a cash payment toggle with validation preventing deactivation if it's the only active method. Placeholders for other gateways (Stripe, Vipps, Klarna, PayPal).
- **Public Module Visibility Dashboard**: Toggles control public visibility of modules (Arrangement, Medlemskap, Reservasjoner, Salg, Utleie) on the company website, managed via `tenant_company_modules.is_public`.
- **Deres Firma Module**: Company profile and staff management with subtabs for "Firmadata" (company info), "Om oss" (company description), "Stillinger" (staff overview), and "Webside" (website settings). The Webside tab provides editable settings for both `tenant_website_config` (publication status, SEO) and `tenant_website_general` (design colors, hero section, contact info, analytics) with comprehensive input validation and normalization via `api/update_webside_data.php`.
- **Dashboard Activity Log**: Displays admin activity (login/logout, IP, last activity, status) from `tenant_admin_sessions`.
- **Website Configuration (Legacy)**: Module-based feature for selecting website templates, with lock mechanisms based on `granted_at` and `updated_at`. Extended with `tenant_website_config` for per-module settings and `tenant_website_general` for global site settings.
- **Module Subtabs System**: Each main module has dedicated subtabs, such as "Dine arrangement", "Opprett arrangement", "Innsjekking", "Arrangement resultat" for the "Arrangement" module. Subtab IDs are namespaced per module (e.g., events-my).

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Data Flow**: Clear data flow, particularly in the homepage system.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture (Oct 2025)**: Implemented modular database design eliminating duplicates and redundancy:
    - **Standardized Tables**: Removed duplicates (`sales_items`→`sales_products`, `sales_orders`→`sales_transactions`) for single source of truth
    - **Cost Price Tracking**: Added `cost_price` and `profit_margin` columns across all product types (sales_products, events_eventdata, membership_plans, rental_objects) for unified profit analysis
    - **Service Bookings**: New `service_bookings` table for non-product transactions (events, memberships, rentals) with polymorphic relationships
    - **Unified Ledger**: New `unified_ledger` table aggregates all financial transactions across modules for centralized reporting
    - **Email System Tables**: `email_templates` and `email_logs` support cross-module email automation
    - **Migration File**: Complete migration strategy documented in `docs/migration_final_clean.sql` (pending manual execution on production MySQL)
- **Database Structure**: Key tables include `tenant_admin_sessions`, `tenant_admins`, `tenant_admin_modules`, `tenant_company_modules`, `tenant_companies` (with columns: name, legal_name, entity_type, subdomain, domain, contact_phone, contact_email, about_us, industry, address_street, address_city, address_zip, address_country, tax_id, org_number, logo_url), `tenant_staff`, `tenant_customers` (stores customer data with first_name, last_name, email, phone, auto-created from service purchases), `events_eventdata`, `output_products`, `sales_cart`, `sales_products` (with img_product URL and item_taxable boolean), `sales_transactions`, `service_bookings`, `unified_ledger`, `email_templates`, `email_logs`. The `tenant_admins` table includes a `username` field and links to `tenant_staff`. `tenant_admin_modules` includes a `granted_by` column linking to `saas_admins`.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregates all sellable items/services from different modules, enabling cross-module marketplace functionality and unified ordering. Entries are auto-generated by `ProductCatalogService.php` during item creation.

## External Dependencies
- **Database**: Dual database support with cross-compatibility via PDO and database-agnostic helpers:
    - **Production**: MySQL hosted on Domeneshop.
    - **Development/Testing**: PostgreSQL (auto-detected via `DATABASE_URL`).
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for the integrated webmail client.
- **Storage**: Object storage for contract document uploads.