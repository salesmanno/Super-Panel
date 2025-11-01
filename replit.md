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
The design prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs. Official modules are in a dedicated tab row, others below. Each module displays its specific subtabs. The design includes a blue hero banner and consistent use of company logos. The Dashboard header displays the company subdomain with an active/inactive status badge and company logo.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation, failsafe messages, complete session tracking, and single-session enforcement.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component for proper content display.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables.
- **Email Module**: Integrated email client with SMTP configuration validation and `EmailService.php` for cross-module automated notifications, using PHP's `mail()` and `email_templates`.
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration. Features include:
  - **Standard Registration Fields**: All events automatically include fornavn, etternavn, and e-postadresse as required fields
  - **Optional Registration Fields**: Phone, street address, zip code, city, postal box, and age can be added per event
  - **Min Participants Deadline**: When min_attendees is set, a deadline date (before event start) must be specified
  - **Registration Fields Storage**: Configurable fields stored in `event_registration_fields`
  - **Scanner-based Check-in**: Uses `search_attendee.php` API for fast attendee lookup and check-in
  - **Event Results Tracking**: Click event cards to expand dropdown showing income/expense tracking with live summary calculations (total income, total expense, net result)
- **Ditt Nettsted Module**: Manages website configuration from `tenant_website_config`, with a lock mechanism requiring admin password for temporary editing access.
- **Reservation Module**: Complete reservation management with agenda/calendar views, filtering, statistics, and subtabs for incoming, confirmed, and active services. New reservable services can be created with comprehensive settings.
- **Sales Module with Shopping Cart**: Supports dual purchase flows: a shopping cart for products and individual service sales (events, memberships, rentals) via a dedicated modal. Includes a full-width barcode scanner header for product lookup and addition to cart. Customer data is saved/updated in `tenant_customers`. Membership sales include customizable join_date, expiry_date, and auto_renew settings with extended customer information (address, birth_date). **Event Linking**: Sales can be linked to events via dropdown in checkout, with event_id stored in unified_ledger metadata for cross-module reporting. Auto-refresh after transactions ensures all data is up-to-date. **Payment Methods**: Hardcoded to Kontant, Vipps (ukonfigurert), and Oppmøte with cash return calculator. **Fullscreen Mode**: Dedicated fullscreen view with toggle button, exact same functionality as normal view, ESC key to close. **Purchases Subtab**: Redesigned innkjøpssystem with 3 alternativer: (1) Se innkjøpshistorikk from `sales_purchase_history`, (2) Gjør innkjøp på eksisterende produkt med vektet gjennomsnittspris-beregning, (3) Gjør innkjøp av ikke-registrerte varer (oppretter nytt produkt). All purchases logged to unified_ledger as expenses. **Automatic Stock Tracking**: `sales_products.stock_quantity` automatically decreases on sales (with validation to prevent overselling), increases on purchases, and displays with color-coded badges (green=sufficient, yellow=low, red=empty) based on `low_stock_threshold`.
- **Deres Firma Module**: Manages company profile, staff, and website settings (`tenant_website_config` and `tenant_website_general`).
- **Unified Economic System**: Centralized financial tracking via `unified_ledger` table, aggregating all transactions with profit tracking. `UnifiedLedgerService.php` automatically logs transactions from all modules.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregates all sellable items/services from different modules, auto-generated by `ProductCatalogService.php`. Includes automatic synchronization:
  - **Auto-sync on Create**: `create_membership_plan.php` automatically creates output_products entries and syncs data
  - **Auto-sync on Update**: `update_membership_plan.php` automatically syncs changes to output_products
  - **Manual Sync**: `api/sync_product_catalog.php` endpoint available for manual synchronization
  - **Sync Method**: `ProductCatalogService::syncProductData()` updates name and price from source tables
- **Database UUID Compliance**: MySQL production uses `char(36)` UUID format for `system_modules.id` and `output_products.module_id`. All APIs must query `system_modules` for correct UUIDs.

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture**: Modular database design eliminating duplicates and redundancy, standardizing tables, adding cost price tracking, and introducing `service_bookings` and `unified_ledger` tables.
- **Database Structure**: Key tables include `tenant_admin_sessions`, `tenant_admins`, `tenant_companies`, `tenant_staff`, `tenant_customers`, `membership_members`, `events_eventdata`, `sales_products`, `sales_transactions`, `unified_ledger`, `email_templates`, `email_logs`, and `output_products`.

## Recent Changes (November 1, 2025)
- **Fix for slettede produkter i varesalg**: Produktkataloget filtrerer nå ut slettede arrangementer:
  - **Filtrering**: getAllProducts() hopper over produkter der kildedata er null (slettet fra kildetabell)
  - **Cascade update**: Når arrangementer slettes, settes is_visible=FALSE i output_products
  - **Aktiv-sjekk**: getProductDetails() henter kun aktive arrangementer (is_active=TRUE)
  - **Logging**: Orphaned output_products entries logges for debugging
- **Single-Session Enforcement**: Streng håndhevelse av én sesjon per bruker:
  - **Blokkerer ny innlogging**: Hvis brukeren allerede er innlogget, blokkeres nye innloggingsforsøk
  - **Klar feilmelding**: "Brukeren er allerede innlogget. Uautorisert bruk? Ta kontakt med 404 69 364."
  - **Ingen automatisk utlogging**: Eksisterende sesjon må logges ut manuelt før ny innlogging
  - **Sikkerhet**: Forhindrer uautorisert tilgang ved å kreve eksplisitt utlogging
- **Servicesalg med kalendervisning**: Redesignet reservasjonsbooking med visuell kalender:
  - **Visuell kalender**: Erstatter enkle dato/tid-inputs med ukeskalender som viser alle tilgjengelige timeslots
  - **Per-ansatt visning**: Hver timeslot viser knapper for alle tilgjengelige ansatte (grønn ✓ = ledig, grå ✗ = opptatt)
  - **Ukesnavigering**: Bla gjennom uker med Forrige/Neste-knapper
  - **Batch-API**: `get_week_availability.php` laster all tilgjengelighet for en uke i ett kall (reduserer fra 400+ til 1 API-kall)
  - **Fleksible åpningstider**: Konfigurerbare timer (standard 06:00-22:00, inkluderer helger)
  - **Visuell bekreftelse**: Valgt tidspunkt vises tydelig med dag, dato, tid og ansatt
- **Antall-felt fjernet for medlemskap og events**: 
  - Medlemskap og arrangementbilletter selges alltid med antall = 1
  - Antall-input skjules automatisk for disse produkttypene
  - Forenkler salgsprosessen og forhindrer feil

## Recent Changes (November 1, 2025 - Previous)
- **Non-Registered Items Purchase Flow**: Redesigned purchase form for ikke-registrerte varer (items not for resale):
  - **Removed Fields**: Strekkode (barcode) and Salgspris (selling price) fields removed from UI
  - **Auto-Generated SKU**: System automatically generates SKU with "NR-" prefix (e.g., NR-A3F2D1E8)
  - **Fixed Selling Price**: Backend sets selling_price = 0 automatically (not intended for resale)
  - **Product ID Display**: Success message shows product_id and SKU for tracking
  - **Metadata Flag**: Unified ledger includes 'non_registered_item' = true for reporting
  - **Use Case**: For office supplies, consumables, and other expense items that won't be sold
- **Membership Income Overview**: Complete membership revenue tracking and reporting system:
  - **Statistics Dashboard**: Real-time display of total omsetning, aktive medlemmer, månedlig inntekt, årlig inntekt
  - **Transaction History**: Detailed table showing date, customer, membership type, sale type (nytt/fornyelse), amount, and payment status
  - **Income by Plan**: Visual progress bars showing revenue breakdown per membership plan with sales count
  - **API**: `get_membership_income.php` aggregates data from unified_ledger where module_source='membership'
  - **Auto-load**: Data refreshes automatically when "Inntekter" subtab is clicked
- **Module Administration for Staff**: Complete system for managing employee module access:
  - **New Tab in Deres Firma**: "Moduler" tab between "Stillinger" and "Webside" for centralized management
  - **Staff Selection**: List all employees, click to select and manage their module access
  - **Module Checkboxes**: Visual interface showing all available modules with current access status
  - **API**: `get_staff_modules.php` retrieves staff and module data, `update_staff_modules.php` updates permissions
  - **Database**: Uses existing `tenant_admin_modules` table with granted_by and granted_at tracking
  - **Security**: All operations scoped to company_id, validates admin ownership before updates

## Recent Changes (October 31, 2025)
- **Reservation Sales Integration**: Complete integration of reservable services into sales module with intelligent booking system:
  - **Booking Modal**: servicePurchaseModal extended with date/time picker, staff selector, and real-time availability checking
  - **Smart Availability**: `check_reservation_availability.php` API validates time slots against existing bookings with overlap detection
  - **First Available**: `find_first_available.php` searches 14 days ahead (30-min slots, 09:00-17:00, excludes weekends) for next open appointment
  - **Secure Booking**: process_service_sale.php enforces server-side validation - uses authoritative service data (duration, price, assigned staff) from database to prevent tampering
  - **Unified Ledger**: All reservation bookings logged to unified_ledger with complete metadata (booking date/time, staff, duration)
  - **Service Bookings Table**: Creates entries in service_bookings with status management (pending/confirmed/completed/cancelled)
- **Member Management Enhancements**:
  - **Inline Editing**: Click-to-edit functionality for member data (first name, last name, email, phone) with auto-save
  - **Status Tracking**: Expired memberships show "Manglende betaling" status with red badge
  - **Compact IDs**: Member IDs displayed as first 8 characters in `<code>` format
  - **Renewal System**: Green renewal button with date prompt, auto-calculates new expiry based on plan, logs to unified_ledger
  - **API Support**: update_member.php and renew_membership.php with dual-database compatibility
- **News Module**: Renamed from "Kunngjøringer" to "News" with full image support (image_url field in forms, display with object-fit cover, max 300px height)

## Recent Changes (October 30, 2025)
- **Database Fallback**: Automatic PostgreSQL fallback when MySQL unavailable in `config/database.php`
- **Schema Fixes**: Added `name`, `description`, `price` columns to `output_products` in PostgreSQL
- **Type Compatibility**: Fixed module_id type mismatch between `system_modules.id` (INTEGER in PostgreSQL, UUID in MySQL) and `output_products.module_id` (VARCHAR) using string casting
- **Payment Status Handling**: "Oppmøte" (on_arrival) payment method now correctly sets `payment_status='pending'` across all transaction types (products, events, memberships, rentals) in unified_ledger
- **Auto-Checkout on Cash Payment**: Cash denomination buttons (50-1000 NOK) now automatically complete checkout when sufficient amount is received, calculating change and showing success message in both normal and fullscreen modes

## External Dependencies
- **Database**: Dual database support with PDO:
    - Production: MySQL (Domeneshop)
    - Development/Testing: PostgreSQL (auto-fallback when MySQL unavailable)
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.