# Salesman Superportal System

## Overview
The Salesman Superportal System is a comprehensive super_user administration portal designed to centralize and streamline various company operations. It offers a robust platform for managing modules and settings based on user privileges, aiming to enhance efficiency across sales, webmail, reservations, economy, membership, rental, events, and marketplace management. The system's vision is to provide efficient oversight of diverse company functions through a unified interface.

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
Prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs (faner) for efficient navigation. Official modules (Dashboard, Deres firma) are displayed in a dedicated tab row, while other modules (Webmail, Reservasjoner, Arrangement, etc.) are shown in a separate row below. Each module displays its specific subtabs in a dedicated row beneath the main module tabs, allowing efficient navigation within modules. Subtabs use namespaced IDs (e.g., events-my, membership-plans) to avoid collisions, and JavaScript scopes content switching to the active module container. The design includes a blue hero banner and consistent use of company logos from tenant_companies.logo_url. Dashboard header displays company subdomain (subdomain.salesman.no) with active/inactive status badge on the left side under the welcome message, with company logo positioned on the right.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation and failsafe messages. Includes a complete session tracking system with login/logout logging, IP addresses, user agents, activity timestamps, and auto-cleanup of inactive sessions.
- **Tab Navigation System**: Uses Bootstrap 5's native tab component with `shown.bs.tab` event listeners to ensure proper show/hide behavior. Prevents content stacking by letting Bootstrap handle all `.active` class management instead of manual manipulation.
- **Module & Privilege Management**: Granular access control using `tenant_admin_modules` and `tenant_company_modules` tables ensures modules are only visible based on user privileges. Tenant admins can dynamically update their own module privileges.
- **Email Module**: Integrated email client supporting inbox, compose, and message viewing. Includes SMTP configuration validation from `email_configs` table - displays detailed warnings when SMTP server data (host, port, username, password) is missing. Email client interface expanded to 600px height for better usability with additional folders (Stjernemerket, Arkiv).
- **Events Module**: Comprehensive CRUD operations for event management stored in `events_eventdata` table with integrated output_products system. Features include: title, description, start/end date/time, venue details (name, address, city, postal), attendee limits (min/max), age restrictions, ticket pricing with optional member pricing (only visible when membership module is accessible), event images, and member-exclusive toggle. Professional card-based UI displays events with gradient backgrounds, hover effects, and clear visual hierarchy showing date, location, capacity, and pricing. Includes check-in system (`events_checkin`) for attendee tracking, payment processing (cash/card/online), and revenue management. Full API support for create, read, soft-delete, check-in, and attendee listing.
- **User Profiles**: Stores personal information for features like automatic email signatures and profile editing.
- **Homepage Template System**: An isolated system offering 5 pre-defined templates, including management, settings flow, public rendering, live preview, and publishing options (SEO, custom domains). Templates use placeholders like `{{company_name}}` and `{{company_logo}}`.
- **Rental System**: Manages individual rental objects with serial number tracking, condition notes, and integrates with the reservation system for booking, overlap checks, and pricing. Supports contract document uploads (PDF, DOC, DOCX, TXT, RTF).
- **Global Reservation System**: Manages reservations across multiple modules (rental, sales, events, economy) with unique reservation numbering (e.g., RENT, SALE, EVNT). Includes API endpoints for retrieval, advanced filtering, and statistics, respecting user privileges and `reservation_settings`.
- **Sales Module**: Functionality to add sales items via a dedicated sub-pane, with form data entry, JavaScript submission, and API endpoints for storage, including validation and duplicate SKU checks.
- **Dynamic Database Status**: Live database connection status indicator in the navigation, with automatic checks and visual feedback (pulsating dot) and graceful degradation (gray background, disabled login) upon disconnection.
- **Payment Gateway System**: Simplified payment system using `tenant_companies.cash_payment` boolean (default TRUE). Economy module displays cash payment toggle with validation preventing deactivation when no other payment methods are active. Other gateways (Stripe, Vipps, Klarna, PayPal) shown as "Ikke konfigurert" placeholders for future implementation. API endpoint `toggle_cash_payment.php` enforces business rule requiring at least one active payment method.
- **Public Module Visibility Dashboard**: Dashboard includes toggles for controlling which modules are displayed publicly on the company website. Supports 5 modules: Arrangement, Medlemskap, Reservasjoner, Salg, and Utleie. Settings stored in `tenant_company_modules.is_public` column and managed via `api/toggle_public_module.php`.
- **Deres Firma Module**: Company profile and staff management module with three subtabs:
  - **Firmadata**: Displays company information from tenant_companies (name, org number, address, postal code, city, phone, email, website) - read-only with admin-only edit access
  - **Om oss**: Company description and history with creation date and active status from tenant_companies.description field
  - **Stillinger**: Staff overview from tenant_staff table showing all employees (name, job title, email, phone, department, active status) with total count
- **Dashboard Activity Log**: Dashboard includes admin activity console showing tenant_admin sessions (login/logout times, IP addresses, last activity, active status) filtered by current company from tenant_admin_sessions table
- **Website Configuration (Legacy)**: Module-based feature with one-time configuration logic. Tenant admins can select website templates once per privilege grant. If they lose and regain the module privilege, they can reconfigure. Template selections are stored in the `webconfiguration` table (with custom_css and custom_colors fields) and can be retrieved via public API (`api/get_company_website.php`) by external domains using company_id or subdomain. Includes 5 pre-defined templates: Moderne Bedrift, E-handel Basic, Kreativ Portefølje, Restaurant & Café, and Minimalistisk Landing. Lock mechanism compares `tenant_admin_modules.granted_at` with `webconfiguration.updated_at` to determine if reconfiguration is allowed. Extended with `tenant_website_config` for per-module website settings (visibility, navigation, SEO, styling) and `tenant_website_general` for global site settings (branding, layout, hero section, contact info, custom CSS/JS).
- **Module Subtabs System**: Each module has dedicated subtabs displayed in a separate row beneath the main module navigation:
  - **Arrangement**: "Dine arrangement", "Opprett arrangement", "Innsjekking" (velg event, check-in skjema, deltaker-liste med betalingsstatus og statistikk), "Arrangement resultat"
  - **Medlemskap**: "Dine medlemskapsplaner", "Medlemmer" (with detailed fields: medlemsnr, fornavn, etternavn, e-post, telefon, adresse, medlemstype, status, medlem siden, gyldig til), "Medlemsforespørsler", "Medlemsinntekter"
  - **Reservasjoner**: "Reservasjonsmottak", "Bekreftede Reservasjoner"
  - **Utleie**: "Se og opprett utleieobjekt", "Kontrakt & betingelser", "Kommende utleie", "Deres utleie resultat"
  - **Email**: "E-post klient" (600px height, includes SMTP validation check), "E-post historikk", "AutoMailer" (AutoMailer subtab only visible when both email_accounts AND email_configs SMTP data are configured; allows enable/disable of automatic emails for modules: utleie, arrangement, medlemskap, reservasjoner, salg)
  - **Økonomi**: "Deres resultat", "Se inntekt", "Se utgift", "Betalingsløsninger" (shows individual payment providers with cash_payment toggle and disabled Stripe, Vipps, Klarna, PayPal options; includes validation preventing deactivation of cash payment when it's the only active payment method)
  - **Salg**: "Produkter", "Ordrer", "Salgsstatistikk"
  - **Deres firma**: "Firmadata", "Om oss", "Stillinger" (displays company info, description, and staff from tenant_companies and tenant_staff tables)
  - Subtab IDs are namespaced per module (e.g., events-my, membership-plans, firma-firmadata) to prevent collisions. JavaScript uses Bootstrap's `shown.bs.tab` event to handle tab switching, ensuring only one module and subtab is visible at a time.

### System Design Choices
- **Modularity**: Core system features are organized into distinct, manageable modules.
- **Data Flow**: Emphasizes clear data flow, especially in the homepage system (template selection, settings, preview, publishing).
- **Staff-First Architecture**: `tenant_staff` (employees) are created FIRST independently, then `tenant_admins` (login accounts) optionally link to staff via `tenant_admins.tenant_staff_id`. This allows staff records without login access.
- **Database Structure**: Key tables include `tenant_admin_sessions`, `tenant_admins`, `tenant_admin_modules`, `tenant_company_modules`, `tenant_companies`, `tenant_staff`, `email_accounts`, `email_configs`, `events_eventdata`, `events_checkin`, `output_products`, `sales_items`, `companies`, `system_modules`, `homepage_templates`, `company_homepage_settings`, `rental_objects`, `rental_reservations`, `contract_documents`, `reservation_settings`, `webconfiguration`, `website_templates`, `tenant_website_config`, `tenant_website_general`, and `saas_admins`. The `tenant_admins` table includes a `username` field for authentication and links to `tenant_staff` via `tenant_staff_id` (optional). The `tenant_admin_modules` table includes a `granted_by` column linking to `saas_admins` for permission tracking. Website configuration uses tenant_admins as owners (created_by/updated_by) to maintain Staff-First Architecture. The `tenant_companies` table includes `cash_payment` boolean column for payment gateway management and `logo_url` for company logo display (fallback to `companies.logo_index` if not available).
- **Output Products System**: Unified product catalog (`output_products`) that aggregates all sellable items/services from different modules (events, sales, rental, membership) into one system using pure polymorphic approach. Each product has visibility controls (is_visible, is_public, is_marketplace_featured), marketplace tracking (referred_by, marketplace_priority), and references source data via (product_type, product_id) unique pair. This enables cross-module marketplace functionality, unified ordering (customers → output_products), and hide/show without deletion. Auto-generation handled by ProductCatalogService.php during item creation - no output_product_id needed in source tables. API endpoints: get_all_products.php (unified catalog view), update_product_visibility.php (marketplace controls). Creation APIs auto-generate entries: create_event.php, create_sales_item.php, create_rental.php, create_membership_plan.php.

## External Dependencies
- **Database**: Dual database support with cross-compatibility:
  - **Production**: MySQL hosted on Domeneshop (`salesmanno04.mysql.domeneshop.no/salesmanno04`)
  - **Development/Testing**: PostgreSQL (auto-detected via DATABASE_URL environment variable)
  - Connection managed via `config/database.php` using PDO with automatic database type detection
  - **Database-agnostic helpers** for cross-compatibility:
    - `getDBType()`: Returns current database driver (pgsql/mysql)
    - `dbBool($value)`: Returns TRUE/FALSE for PostgreSQL, 1/0 for MySQL
    - `dbInterval($amount, $unit)`: Returns proper INTERVAL syntax for each database
  - Timestamp columns use `login_at`, `logout_at` naming convention
  - All queries use database-agnostic helpers to ensure smooth MySQL/PostgreSQL transition
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP (`smtp.domeneshop.no:587` with TLS) and IMAP (`imap.domeneshop.no:993` with SSL) for the integrated webmail client.
- **Storage**: Object storage for contract document uploads.