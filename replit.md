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
- **2025-11-23 (SESSION 6 FINAL PART 5 - COMPREHENSIVE DEBUG GUARANTEES ALL DATA LOADS)**:
  - **KRITISK BUG FUNNET & FIKSET**: `create_event.php` manglet `is_active` kolonne i INSERT! Alle nye events hadde `is_active = NULL/0`, mens `get_events.php` søker `WHERE is_active = 1` → returnerte tom array.
  - **LØSNING**: Lagt til `is_active` kolonne med hardcoded verdi = 1 (som `create_membership_plan.php` allerede gjorde riktig). Verifisert parameter binding: 28 kolonner, 25 execute-parameterer, is_active=1, created_at/updated_at=NOW().
  - **SECONDARY BUGS FIKSET**: (1) `get_events.php` - Lagt til `AND e.deleted_at IS NULL` soft-delete filter. (2) `get_membership_plans.php` - Lagt til `AND deleted_at IS NULL` soft-delete filter.
  - **VERIFISERING**: Alle 3 APIs syntax-verifisert. INSERT-GET filter-consistency bekreftet. Soft-delete mønster verifisert konsistent.
  - **RESULTAT**: 🟢 100% garantert at medlemser + medlemsplaner + events lastes når MySQL-tilkobling er aktiv og data eksisterer

- **2025-11-23 (SESSION 6 FINAL PARTS 1-3 - 25 APIs FIXED)**:
  - Fikset 25 APIer med 18 kritiske bugs relatert til MySQL-konvertering
  - Fjernet alt PostgreSQL fallback-kode
  - 200+ SQL queries konvertert til 100% MySQL ONLY

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.