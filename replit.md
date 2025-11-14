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
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields, scanner-based check-in, and income/expense tracking.
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

## Recent Changes
- **2025-11-14 (Latest)**: RENTAL MODULE - TABLE-PER-TYPE ARCHITECTURE FULLFØRT - Komplett omskriving av Rental Module til profesjonell table-per-type arkitektur. Backend: Opprettet 12 type-spesifikke tabeller (rental_residences, rental_cabins, rental_venues, rental_containers, rental_tools, rental_vehicles, rental_offices, rental_sports_equipment, rental_parking, rental_rvs, rental_event_items, rental_trailers) med 1:1 foreign keys til rental_objects base-tabell. Alle relasjoner bruker ON DELETE CASCADE for automatisk opprydding. Type-spesifikke tabeller inneholder felt som: residence (sqm, bedrooms, bathrooms, furnished, energy_rating), cabin (beds, season_type, cleaning_fee, facilities), vehicle (make, model, fuel_type, driver_license_class, license_years_required), etc. Opprettet `includes/rental_schemas.php` som definerer alle feltskjemaer for 12 objekttyper med norske labels, datatyper og valideringsregler. Full CRUD API: `api/create_rental_object.php`, `api/get_rental_objects.php`, `api/update_rental_object.php`, `api/delete_rental_object.php`. Frontend: Komplett omskriving av `modules/rental_content.php` med dynamisk form-generator basert på objekttype, card-basert visning med bilder og badges, kategorifilter, edit/delete-funksjonalitet med type_data populering, og detaljvisning-modal. Validering: Schema-level "required" flags enforces frontend-side, backend validerer kun object_name og category.

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.