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
- **2025-11-23 (CRITICAL FIX - Syntax Errors & MySQL Parameterisering)**:
  - **FIKSET 2 SYNTAX ERRORS**: `get_membership_plans.php` og `get_members.php` hadde uventet `else` uten tilhørende `if` + duplicate SQL queries. Nå helt riktig.
  - **FJERNET PostgreSQL FALLBACK**: Kode bruker nå MYSQL ONLY per brukerens krav. 
  - **FIKSET 14+ APIer**: Alle hardkoderte `= 1` og `= 0` i SQL-queries erstattet med parameterisert SQL (`?`). Sikrer MySQL-kompatibilitet og forhindrer SQL-feil.

- **2025-11-23 (Previous - MySQL Parameterisering)**:
  - **FJERNET PostgreSQL FALLBACK**: Kode bruker nå MYSQL ONLY per brukerens krav. Ingen PostgreSQL-fallback.
  - **FIKSET 12+ APIer for MySQL-kompatibilitet**: Alle hardkoderte `= 1` og `= 0` i SQL-queries erstattet med parameterisert SQL (`?`). Påvirker filer: `config/database.php`, `auth/company_auth.php`, `api/get_membership_plans.php`, `api/get_events.php`, `api/get_announcements.php`, `api/get_company_website.php`, `api/get_rental_objects.php`, `api/get_sales_items.php`, `api/get_staff_modules.php`, `api/get_staff.php`, `api/get_terms_conditions.php`, `api/save_website_template.php`, `api/update_permissions.php`, `api/update_staff_modules.php`, `includes/functions.php`. Dette sikrer MySQL-kompatibilitet og forhindrer SQL-feil ved parameterisert binding.
- **2025-11-22 (Latest Session - PART 3)**:
  - **INTERN MEDLEMSKAPSPLAN FEATURE**: Lagt til checkbox "Intern medlemskapsplan" i opprett-form for medlemskapsplaner. Når avmerket: (1) `is_internal = 1` i database, (2) INGEN output_products entry lages, (3) Planen vises IKKE i offentlige tjenester/shopping cart. Filter: `get_membership_plans.php` viser kun `WHERE is_internal = 0`. Bruk: Interne planer for intern administrasjon, eksterne for salgskanaler.
- **2025-11-22 (Latest Session - PART 2)**:
  - **OUTPUT_PRODUCTS VISIBILITY & MANAGEMENT**: Lagt til feature på dashboarden som viser hvor mange oppføringer bruker har i moduler med output_products. Under "Du har tilgang til X moduler" vises nå "...og har Y(antall) oppføringer i Z(moduler) av disse!" med "Se og slett"-lenke. Klikk åpner modal med tabell over alle oppføringer (navn, modul, pris, status) med mulighet for å slette hver enkelt. APIs: `get_output_products.php` (teller og henter alle), `delete_output_product.php` (sletter). Dashboard JavaScript oppdateres automatisk etter sletting.
  - **ARRANGEMENT RESULTAT - FIKSET**: Fixed asymmetric SQL query i `get_event_finances.php` - expenses-query manglte OR-klausul for source_id matching. Nå både income og expenses søker etter både metadata JSON OG module_source+source_id kombinasjon.
- **2025-11-22 (Latest Session)**: 
  - **ACTIVITY FEED FIX**: Dashboarden viser nå "Laster aktivitetssammendrag..." som aldri ble fullført. **Fikset**: (1) `get_activity_summary.php` brukte feil tabellnavn (`tenant_events` → `events_eventdata`, `tenant_event_registrations` → `event_registrations`). (2) Lagt til nye aktiviteter som nå vises: nye bestillinger fra salg (`orders` tabell), event check-ins (`events_checkin`), påmeldinger, og reservasjoner - alle med tidsbasert tracking fra sist innlogging. Dashboard oppdateres automatisk og viser "Det er X nye hendelser siden sist: Y i Arrangementer, Z i Salg, osv."
  - **INNSJEKK DROPDOWN + MySQL FIXES (10 BUGS)**: Feilsøkt og fikset multiple MySQL-kompatibilitetsproblemer: (1) **JavaScript-feilhåndtering**: `loadCheckinEventSelector()` hadde dårlig exception-håndtering som gjorde at dropdown forble tom ved API-feil; nå med detaljert `[Check-in Loader]`-logging og visuell feilmelding i dropdown. (2) **SQL TRUE/FALSE konvertering**: Erstattet alle `= TRUE` → `= 1` og `= FALSE` → `= 0` i 10 API-filer for MySQL-kompatibilitet: `get_events.php`, `delete_event.php`, `get_company_website.php`, `get_staff_modules.php`, `save_website_template.php`, `update_permissions.php`, `update_staff_modules.php`, `register_guest.php`. (3) **VALUES-klausuler**: Byttet VALUES-innhold `TRUE, ?, NOW(), NOW()` → `1, ?, NOW(), NOW()` for nullable columns. PostgreSQL-fallback fjernet fullstendig; MySQL 100% enforced.
- **2025-11-19**: EVENTS MODULE - KOMPRIMERT LAYOUT & FORENKLET UI - Fjernet fargede bokser (medlemspriser, betaling, påmeldingsfelt) og erstattet med enkle `<hr>` separatorer for mer kompakt og ryddig utseende. Beholdt kun registreringstype som farget card. Implementert innhold direkte i formen med `<h6>` overskrifter. Optimalisert kolonneutnyttelse: Aldersgrense/Stedsnavn/By/Postnummer på samme rad (col-md-3), påmeldingsfelt som col-md-3 (4 kolonner). Wrappet påmeldingsfelt i `<div id="registration-fields-section">` for show/hide funksjonalitet. JavaScript viser/skjuler påmeldingsfelt basert på registreringstype uten å flytte betalingsseksjon (nå bare en `<hr>` tag). Tidligere samme dag: KONSOLIDERT BETALINGSSEKSJON med tillatte betalingsmetoder og dynamisk status for betalingstidspunkt. REGISTRERINGSTYPE OG OPPGJØR-implementering. Database-kompatibilitet: `last_logout` → `last_login` fallback.
- **2025-11-14**: EVENTS MODULE - BETALINGSMETODER ENFORCEMENT - Implementert backend-håndhevelse av "oppmøtebetaling" (cash) som alltid påkrevd betalingsmetode. Backend: `create_event.php` og `update_event.php` normaliserer alle betalingsmetode-input ved å automatisk injisere 'cash' som første element hvis manglende, eller flytte den til første posisjon hvis til stede men feil rekkefølge. Frontend: Betalingsmetode-checkboxer i både opprettelse og redigering viser cash som disabled og alltid checked, med info-alert som forklarer kravet. Dette sikrer at arrangementer alltid kan publiseres uten webbetaling. Både `payment_methods_json` og `default_payment_method` oppdateres samtidig for konsistens. Tidligere samme dag: PÅMELDINGSFELT KONFIGURASJON med fleksibel kontaktinformasjon (e-post og/eller telefon). MEDLEMSPRISER & BETALINGSMETODER med medlemsplan-baserte priser. RENTAL MODULE med UX-forbedringer og helgepriser.

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.