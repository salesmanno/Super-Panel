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
- **Authentication**: Session-based system with robust backend validation and single-session enforcement.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component for content display.
- **Module & Privilege Management**: Granular access control via dedicated tables, with an interface for staff module access management.
- **Email Module**: Integrated email client with SMTP configuration and `EmailService.php` for automated notifications.
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields, scanner-based check-in, membership-based pricing, dynamic payment methods, and income/expense tracking.
- **Ditt Nettsted Module**: Manages website configuration with an admin password-protected lock mechanism.
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
- **2025-11-24 (SESSION 10 - EVENTS_RESULTS ENHANCED COLUMNS)**:
  - Expanded `events_results` table with comprehensive event summary columns
  - **Revenue Tracking** (by payment method):
    - `revenue_tickets_cash`, `revenue_tickets_card`, `revenue_tickets_vipps`, `revenue_tickets_invoice`
    - `revenue_merchandise`, `revenue_donations`
  - **Expense Tracking**:
    - `expense_staff`, `expense_venue`, `expense_equipment`, `expense_materials`, `expense_other`
  - **Attendance & Status**:
    - `no_shows`, `attendance_rate`, `average_age`, `status`
    - `notes`, `organizer_notes`, `finalized_at`
  - Migration available: `api/migrate_events_results_columns.php` or SQL at `docs/migrate_events_results_columns.sql`
  - Auto-create `events_results` when event created (linje 215-233 i `api/create_event.php`)

- **2025-11-24 (SESSION 10 - AUTO-CREATE EVENTS_RESULTS ON EVENT CREATION)**:
  - Når arrangement opprettes → Auto-create `events_results` record
  - `events_results` brukes for å registrere inn- og utgifter til arrangement
  - Implementert i `api/create_event.php` (linje 215-233)
  - Setter ticket price og dato fra arrangement automatisk
  - Åpner for å spore event-spesifikk ekonomi separate fra unified_ledger
  - Feasibility-vurdering: events_results eksisterte allerede, nå brukes den konsekvent

- **2025-11-24 (SESSION 9 - INNSJEKKING REDESIGNED KOMPLETT)**:
  - **Event Selection → Cards Layout**:
    - Arrangement selector endret fra dropdown til cards
    - Hver card viser stats: Påmeldte, Medlemmer, Betalt, Inntekt
    - Clickable cards for å velge arrangement
  - **Check-in View → Individual Cards**:
    - Deltakere vises som individuelle cards (ikke tabeller)
    - Fargekoding: ⚠️ Gul border (nye), ✅ Grønn border (sjekket inn)
    - Nyeste registreringer øverst
    - Quick "Sjekk inn" button på hver card
  - **Modal: Alle Reservasjoner**:
    - Klikk på arrangement → Modal med alle reservasjoner
    - Viser: Navn, Kontakt, Type, Pris, Betalingsstatus, Registreringsdato
    - Table layout for full oversikt
  - **Functions Added**:
    - `loadCheckinEventSelector()` - Load events as cards with stats
    - `selectCheckinEvent()` - Handle event card selection
    - `showAllRegistrations()` - Open modal with all registrations
    - `quickCheckin()` - Check-in with event.stopPropagation()
  - **Stats Updated**: Added "Medlemmer" count to check-in stats

## External Dependencies
- **Database**: Dual database support with PDO, primarily MySQL, with PostgreSQL for development/testing.
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.