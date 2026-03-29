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
- **Authentication**: Session-based system with robust backend validation, complete session tracking, and single-session enforcement.
- **Tab Navigation System**: Utilizes Bootstrap 5's native tab component.
- **Module & Privilege Management**: Granular access control via `tenant_admin_modules` and `tenant_company_modules` tables.
- **Email Module**: Full email client with queue-based sending via `SMTPClient.php` (socket-based, SSL port 465 / STARTTLS port 587). `EmailTemplate.php` wraps all outgoing emails in dual-branded HTML template. Webmail client uses 3-column layout (70vh, no position:fixed), compose writes to `email_queue`, mass-sending with mottakerpicker (kunder/aktive medlemmer), AutoMailer accordion in sidebar. API: `queue_email.php`, `get_email_queue.php`, `get_email_logs.php`, `get_recipients.php`, `send_email.php`, `process_email_queue.php` (MySQL INTERVAL 1 HOUR syntax, no mail()). Webmail JS uses AbortController (`wmCurrentController`) to cancel in-flight requests on tab switch — prevents race conditions and "nothing loads" bug.
- **Events Module**: Comprehensive CRUD for event management with `output_products` integration, configurable registration fields, scanner-based check-in, membership-based pricing, dynamic payment methods, and income/expense tracking. Includes a two-column check-in system with EAN13 barcode generation and a results module with key metrics.
- **Ditt Nettsted Module**: Manages website configuration from `tenant_website_config` with an admin password-protected lock.
- **Reservation Module**: Complete reservation management with agenda/calendar views, filtering, statistics, and visual calendar-based booking.
- **Rental Module**: Professional multi-type rental management using table-per-type architecture for 12 object types, featuring dynamic form generation, metadata schemas, and image support. **Rental Contracts**: Full contract lifecycle via `rental_contracts` table (id, company_id, rental_object_id, customer_id, customer_name, start_date, end_date, rental_days, total_amount, deposit_amount, deposit_paid, payment_status enum(unpaid/partial/paid), payment_method, status enum(draft/active/completed/cancelled), notes). Flow: create object → click "Opprett kontrakt" inline per object card → form with live customer search, date picker, auto-calculated total → on save with status=active, object.is_available=0 automatically. Contract status transitions: draft→active sets object unavailable, completed/cancelled restores availability. Payment marking (paid) logs to unified_ledger (module_source='rental', category='Utleie'). APIs: `api/get_rental_contracts.php` (GET, ?object_id=, ?status=, ?upcoming=1), `api/create_rental_contract.php`, `api/update_rental_contract.php`, `api/delete_rental_contract.php`. Migration: `db_migrations/rental_contracts.sql`. Contracts tab shows filterable list (Alle/Aktive/Avsluttede/Kansellerte/Utkast). Upcoming tab shows active contracts with end_date >= today, sorted by days remaining.
- **Sales Module with Shopping Cart**: Supports dual purchase flows, barcode scanner integration, customer data management, event linking, automatic stock tracking, and weighted average cost calculation.
- **Deres Firma Module**: Manages company profile, staff, and website settings.
- **Unified Economic System**: Centralized financial tracking via `unified_ledger` table, aggregating all transactions with profit tracking, managed by `UnifiedLedgerService.php`. Supports income/expense tracking across various modules. Economy module features live data display with overview, income, expense tabs; manual transaction entry with 16 expense categories; payment gateway management with toggle functionality.
- **Output Products System**: A unified polymorphic product catalog (`output_products`) aggregating all sellable items/services, auto-generated and synchronized by `ProductCatalogService.php`.
- **Polling Tracker System**: Real-time update notifications via `tenant_polling_tracker` table. External systems write to the table, dashboard polls every 30 seconds via `api/check_refresh_flag.php`, shows toast notification and auto-refreshes when updates are detected.

### Performance & Reliability
- **Session Lock Fix**: All `api/get_*.php` files call `session_write_close()` immediately after `session_start()`. This releases PHP's exclusive session write-lock so concurrent API requests don't block each other (critical for IMAP + simultaneous tab loads).
- **Login Preload System**: `login_transition.php` preloads 12 API endpoints concurrently during the animation. Progress bar is JS-driven (real %, not CSS animation). Hard 6-second max timeout. On `company_dashboard.php`, a transparent `window.fetch` interceptor serves preloaded data to ALL module JS on first call — no changes needed in individual modules. Cache is per-key, used once, then falls through to real network.

### System Design Choices
- **Modularity**: Core system features are organized into distinct modules.
- **Staff-First Architecture**: `tenant_staff` (employees) are created independently, with `tenant_admins` (login accounts) optionally linked.
- **Clean Database Architecture**: Modular database design eliminating duplicates, standardizing tables, adding cost price tracking, and introducing `service_bookings` and `unified_ledger` tables.
- **Database UUID Compliance**: MySQL production uses `char(36)` UUID format for `system_modules.id` and `output_products.module_id`.
- **Event Check-in Data Integrity**: `events_checkin` stores `membership_plan_id` and `ticket_price` for historical accuracy, with `barcode` and `qrcode` columns for EAN13 and QR code storage.
- **Payment Gateway Configuration**: All payment method availability is controlled by the Economy module via `economy_payment_gateways` table, requiring specific credentials for each gateway (Vipps, Stripe, PayPal). Cash payment availability is controlled by `tenant_companies.cash_payment`.
- **Membership Cancellation**: `membership_members` has `cancellation_active TINYINT` and `cancellation_reasoning VARCHAR(256)`. Cancel via `api/cancel_member.php` (POST), reactive with `{reactivate: true}`. Dashboard has "Kansellerte" subtab. Active members view auto-excludes cancelled. Dates formatted dd.mm.yyyy via `fmtDate()` helper.
- **Invoice (Faktura) Module**: Economy module has a "Fakturaer" subtab backed by `economy_invoices` table. APIs: `api/get_invoices.php` (lists with customer JOIN + computed_overdue flag + invoice_config from tenant_companies), `api/create_invoice.php` (auto-numbering: prefix + sequential number from MAX existing), `api/mark_invoice_paid.php` (sets status='paid', paid_at=NOW()), `api/get_customers_simple.php` (active customers for dropdown), `api/get_members_simple.php` (active members for renewal picker). UI: inline collapsible create form (customer select from tenant_customers, plan select from membership_plans with live price preview), filter buttons (Alle/Sendt/Forfalt/Betalt), inline mark-paid without page reload. Bank transfer is manual — no webhook callback available without bank agreement. Three invoice types: **general** (no membership action), **new_member** (creates and activates membership_members record on paid + logs to unified_ledger), **renewal** (extends expiration_date from old expiry or today + billing_cycle months, inserts into membership_members_renew, logs to unified_ledger). Marking paid shows Bootstrap toast with member_code or new expiry date. `economy_invoices` has `invoice_type`, `membership_plan_id`, `membership_member_id` columns.

## External Dependencies
- **Database**: Dual database support with PDO (Production: MySQL, Development/Testing: PostgreSQL).
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP and IMAP for webmail.
- **Storage**: Object storage for contract document uploads.
- **Barcode Generation**: BarcodeService with EAN13 support (pure PHP).