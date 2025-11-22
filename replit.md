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
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields (first/last name always required, email and/or phone selectable with at least one required for membership verification), scanner-based check-in, membership-based pricing via `event_membership_plans` table, dynamic payment methods per event with backend-enforced "oppmøtebetaling" (cash) always included as first method, and income/expense tracking.
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
- **2025-11-22 (Latest)**: DEBUG-FIX - INNSJEKK DROPDOWN IKKE VISER ARRANGEMENTER - Feilsøkt og fikset multiple MySQL-kompatibilitetsproblemer: (1) **JavaScript-feilhåndtering**: `loadCheckinEventSelector()` hadde dårlig exception-håndtering som gjorde at dropdown forble tom ved API-feil; nå med detaljert [Check-in Loader]-logging og visuell feilmelding i dropdown. (2) **SQL TRUE/FALSE konvertering**: Erstattet alle `= TRUE` → `= 1` og `= FALSE` → `= 0` i 9 API-filer for MySQL-kompatibilitet: `get_events.php`, `delete_event.php`, `get_company_website.php`, `get_staff_modules.php`, `save_website_template.php`, `update_permissions.php`, `update_staff_modules.php`, `register_guest.php`. (3) **VALUES-klausuler**: Byttet VALUES-innhold `TRUE, ?, NOW(), NOW()` → `1, ?, NOW(), NOW()` for nullable columns. PostgreSQL-fallback fjernet fullstendig; MySQL 100% enforced.
- **2025-11-19**: EVENTS MODULE - KOMPRIMERT LAYOUT & FORENKLET UI - Fjernet fargede bokser (medlemspriser, betaling, påmeldingsfelt) og erstattet med enkle `<hr>` separatorer for mer kompakt og ryddig utseende. Beholdt kun registreringstype som farget card. Implementert innhold direkte i formen med `<h6>` overskrifter. Optimalisert kolonneutnyttelse: Aldersgrense/Stedsnavn/By/Postnummer på samme rad (col-md-3), påmeldingsfelt som col-md-3 (4 kolonner). Wrappet påmeldingsfelt i `<div id="registration-fields-section">` for show/hide funksjonalitet. JavaScript viser/skjuler påmeldingsfelt basert på registreringstype uten å flytte betalingsseksjon (nå bare en `<hr>` tag). Tidligere samme dag: KONSOLIDERT BETALINGSSEKSJON med tillatte betalingsmetoder og dynamisk status for betalingstidspunkt. REGISTRERINGSTYPE OG OPPGJØR-implementering. Database-kompatibilitet: `last_logout` → `last_login` fallback.
- **2025-11-14**: EVENTS MODULE - BETALINGSMETODER ENFORCEMENT - Implementert backend-håndhevelse av "oppmøtebetaling" (cash) som alltid påkrevd betalingsmetode. Backend: `create_event.php` og `update_event.php` normaliserer alle betalingsmetode-input ved å automatisk injisere 'cash' som første element hvis manglende, eller flytte den til første posisjon hvis til stede men feil rekkefølge. Frontend: Betalingsmetode-checkboxer i både opprettelse og redigering viser cash som disabled og alltid checked, med info-alert som forklarer kravet. Dette sikrer at arrangementer alltid kan publiseres uten webbetaling. Både `payment_methods_json` og `default_payment_method` oppdateres samtidig for konsistens. Tidligere samme dag: PÅMELDINGSFELT KONFIGURASJON med fleksibel kontaktinformasjon (e-post og/eller telefon). MEDLEMSPRISER & BETALINGSMETODER med medlemsplan-baserte priser. RENTAL MODULE med UX-forbedringer og helgepriser.

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.