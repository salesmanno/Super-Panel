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

## System Architecture
The system is built on a PHP 8.2 backend with dual database support (MySQL production, PostgreSQL development/backup), emphasizing modularity and granular access control.

### UI/UX Decisions
The design prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs (faner). Official modules (Dashboard, Deres firma) are displayed in a dedicated tab row, while other modules (Webmail, Reservasjoner, Arrangement, etc.) are shown in a separate row below. Each module displays its specific subtabs in a dedicated row beneath the main module tabs. Subtabs use namespaced IDs (e.g., events-my, membership-plans) and JavaScript scopes content switching to the active module container. The design includes a blue hero banner and consistent use of company logos. The Dashboard header displays the company subdomain (subdomain.salesman.no) with an active/inactive status badge and company logo.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation, failsafe messages, and complete session tracking (login/logout, IP, user agents, activity, auto-cleanup). Single session enforcement logs out existing sessions when an admin logs in.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component with `shown.bs.tab` event listeners for proper content display without stacking.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables, ensuring modules are visible only based on user privileges. Tenant admins can dynamically update their own module privileges.
- **Email Module**: Integrated email client (inbox, compose, view) with SMTP configuration validation from `email_configs` table.
- **Cross-Module Email Integration (Oct 2025)**: Implemented `EmailService.php` as the unified email layer for automated notifications across all modules. Uses PHP's mail() function with template rendering from `email_templates` table and activity logging in `email_logs`. Email is the ONLY cross-module integration point to maintain modularity. Supports sale receipts (checkout), event confirmations (checkin), and future membership/rental confirmations.
- **Events Module**: Comprehensive CRUD operations for event management (`events_eventdata`) with an integrated `output_products` system. Features include event details, attendee limits, age restrictions, ticket pricing (with optional member pricing), images, and a member-exclusive toggle. Includes a check-in system (`events_checkin`) for attendee tracking and payment processing.
- **User Profiles**: Stores personal information for automatic email signatures and profile editing.
- **Homepage Template System**: Isolated system offering 5 pre-defined templates with management, settings, public rendering, live preview, and publishing options (SEO, custom domains).
- **Rental System**: Manages individual rental objects with serial number tracking, condition notes, and integrates with the reservation system for booking, overlap checks, and pricing. Supports contract document uploads.
- **Global Reservation System**: Manages reservations across multiple modules (rental, sales, events, economy) with unique numbering and API endpoints for retrieval, filtering, and statistics.
- **Sales Module with Shopping Cart**: Supports dual purchase flows:
    - **Shopping Cart System**: For products (`product_type='product'`) allowing multiple items, quantity adjustments, and checkout. Cart persists per admin session.
    - **Service Sales**: Events, memberships, and rentals are sold individually via a dedicated purchase modal requiring customer information.
- **Dynamic Database Status**: Live database connection status indicator in the navigation with visual feedback and graceful degradation upon disconnection.
- **Payment Gateway System**: Simplified payment system using `tenant_companies.cash_payment` boolean. The Economy module displays a cash payment toggle with validation preventing deactivation if it's the only active method. Placeholders for other gateways (Stripe, Vipps, Klarna, PayPal).
- **Public Module Visibility Dashboard**: Toggles control public visibility of modules (Arrangement, Medlemskap, Reservasjoner, Salg, Utleie) on the company website, managed via `tenant_company_modules.is_public`.
- **Deres Firma Module**: Company profile and staff management with subtabs for "Firmadata" (company info), "Om oss" (company description), and "Stillinger" (staff overview).
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
- **Database Structure**: Key tables include `tenant_admin_sessions`, `tenant_admins`, `tenant_admin_modules`, `tenant_company_modules`, `tenant_companies`, `tenant_staff`, `events_eventdata`, `output_products`, `sales_cart`, `sales_products`, `sales_transactions`, `service_bookings`, `unified_ledger`, `email_templates`, `email_logs`. The `tenant_admins` table includes a `username` field and links to `tenant_staff`. `tenant_admin_modules` includes a `granted_by` column linking to `saas_admins`.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregates all sellable items/services from different modules, enabling cross-module marketplace functionality and unified ordering. Entries are auto-generated by `ProductCatalogService.php` during item creation.

## External Dependencies
- **Database**: Dual database support with cross-compatibility via PDO and database-agnostic helpers:
    - **Production**: MySQL hosted on Domeneshop.
    - **Development/Testing**: PostgreSQL (auto-detected via `DATABASE_URL`).
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for the integrated webmail client.
- **Storage**: Object storage for contract document uploads.