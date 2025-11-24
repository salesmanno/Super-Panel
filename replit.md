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
- **2025-11-24 (SESSION 11 - BUGFIXES: CHECKIN SELECTOR & CREATOR NAME)**:
  - **Innsjekk-Selector Fixed**:
    - `get_event_checkins.php` nå returnerer LIST av arrangementer når event_id mangler
    - Viser Påmeldte, Medlemmer, Betalt, Inntekt per arrangement
    - modules/events_content.php loadCheckinEventSelector() fungerer nå
  - **Creator Attribution Fixed**:
    - `get_event.php` nå JOIN med tenant_admins for created_by_name
    - Viser "Opprettet av [Navn]" eller "Opprettet av Ukjent bruker" hvis admin slettet
    - Forhindrer "opprettet av ukjent bruker" feilen

- **2025-11-24 (SESSION 10 - EVENTS_CHECKIN AS AUTHORITATIVE SOURCE)**:
  - **EventResultsService Refactored**:
    - `getSummary()` nå beregner all innsjekk- og deltakelsesdata dynamisk fra `events_checkin`
    - `getAttendanceDetails()` ny metode - henter raw checkin data direkte
    - All attendance metrics (total_registered, checked_in, no_shows, member_count, attendance_rate) beregnes fra events_checkin ved hver henting
  - **API Oppdatert**:
    - `get_event_checkins.php` - beregner stats fra events_checkin: checked_in, no_shows, member_count, attendance_rate
    - `get_event_results_summary.php` - returnerer enriked data med attendance fra events_checkin
  - **Data Architecture**:
    - events_results: Lagrer inntekter/utgifter (revenue_tickets_*, expense_*)
    - events_checkin: Autorativ kilde for alle innsjekk- og deltakelsesdata
    - getSummary() merger begge kilder på henting (events_results + events_checkin live stats)
  - **Fordeler**:
    - Real-time attendance stats - alltid oppdatert fra events_checkin
    - Enkelt å endre check-in status - stats blir automatisk recalculated
    - Konsolidert datakilde - ingen duplikater av attendance info

- **2025-11-24 (SESSION 10 - EVENTS_RESULTS IMPLEMENTATION WITH STATS & ECONOMICS)**:
  - **EventResultsService** created (`includes/EventResultsService.php`):
    - `addTicketRevenue()` - Track revenue by payment method (cash, card, vipps, invoice)
    - `addOtherRevenue()` - Track merchandise and donations
    - `addExpense()` - Register expenses (staff, venue, equipment, materials, other)
    - `updateAttendanceStats()` - Auto-calculate no_shows, attendance_rate, average_age
    - `updateStatus()` - Update event status and notes
    - `finalize()` - Mark event completed with finalized_at timestamp
    - `getSummary()` - Retrieve complete event summary
  - **Integration Points**:
    - `api/register_guest.php` - Auto-update revenue_tickets_* when guest registered with payment
    - `api/checkin_event.php` - Auto-update revenue_tickets_* when check-in paid
    - `api/add_event_expense.php` - Register expenses by type with profit/loss calculation
  - **New APIs**:
    - `api/finalize_event_results.php` - Mark event completed, finalize stats
    - `api/get_event_results_summary.php` - Get formatted event summary with all metrics
  - **Key Features**:
    - Revenue separated by payment method (not lumped together)
    - Expenses categorized by type for detailed tracking
    - Automatic profit/loss calculation (total_revenue - total_costs)
    - Attendance metrics auto-calculated from check-ins
    - Ready for MySQL production deployment

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