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
Prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs (faner) for efficient navigation. Official modules (Dashboard, Ditt Nettsted) are displayed in a dedicated tab row, while other modules (Webmail, Reservasjoner, Arrangement, etc.) are shown in a separate row below. Each module displays its specific subtabs in a dedicated row beneath the main module tabs, allowing efficient navigation within modules. Subtabs use namespaced IDs (e.g., events-my, membership-plans) to avoid collisions, and JavaScript scopes content switching to the active module container. The design includes a blue hero banner and consistent use of company logos.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation and failsafe messages. Includes a complete session tracking system with login/logout logging, IP addresses, user agents, activity timestamps, and auto-cleanup of inactive sessions.
- **Tab Navigation System**: Uses Bootstrap 5's native tab component with `shown.bs.tab` event listeners to ensure proper show/hide behavior. Prevents content stacking by letting Bootstrap handle all `.active` class management instead of manual manipulation.
- **Module & Privilege Management**: Granular access control using `tenant_admin_modules` and `tenant_company_modules` tables ensures modules are only visible based on user privileges. Tenant admins can dynamically update their own module privileges.
- **Webmail Client**: Integrated client supporting inbox, compose, and message viewing. Includes SMTP configuration validation from `x_webmailconfiguration` table - displays detailed warnings when SMTP server data (host, port, username, password) is missing. Email client interface expanded to 600px height for better usability with additional folders (Stjernemerket, Arkiv).
- **Events Module**: Comprehensive CRUD operations for event management, including image support (URL or file upload) and conditional display of member pricing.
- **User Profiles**: Stores personal information for features like automatic email signatures and profile editing.
- **Homepage Template System**: An isolated system offering 5 pre-defined templates, including management, settings flow, public rendering, live preview, and publishing options (SEO, custom domains). Templates use placeholders like `{{company_name}}` and `{{company_logo}}`.
- **Rental System**: Manages individual rental objects with serial number tracking, condition notes, and integrates with the reservation system for booking, overlap checks, and pricing. Supports contract document uploads (PDF, DOC, DOCX, TXT, RTF).
- **Global Reservation System**: Manages reservations across multiple modules (rental, sales, events, economy) with unique reservation numbering (e.g., RENT, SALE, EVNT). Includes API endpoints for retrieval, advanced filtering, and statistics, respecting user privileges and `reservation_settings`.
- **Sales Module**: Functionality to add sales items via a dedicated sub-pane, with form data entry, JavaScript submission, and API endpoints for storage, including validation and duplicate SKU checks.
- **Dynamic Database Status**: Live database connection status indicator in the navigation, with automatic checks and visual feedback (pulsating dot) and graceful degradation (gray background, disabled login) upon disconnection.
- **Public Module Visibility Dashboard**: Dashboard includes toggles for controlling which modules are displayed publicly on the company website. Supports 5 modules: Arrangement, Medlemskap, Reservasjoner, Salg, and Utleie. Settings stored in `tenant_company_modules.is_public` column and managed via `api/toggle_public_module.php`.
- **Website Configuration (Ditt Nettsted)**: Module-based feature with one-time configuration logic. Tenant admins can select website templates once per privilege grant. If they lose and regain the module privilege, they can reconfigure. Template selections are stored in the `webconfiguration` table (with custom_css and custom_colors fields) and can be retrieved via public API (`api/get_company_website.php`) by external domains using company_id or subdomain. Includes 5 pre-defined templates: Moderne Bedrift, E-handel Basic, Kreativ Portefølje, Restaurant & Café, and Minimalistisk Landing. Lock mechanism compares `tenant_admin_modules.granted_at` with `webconfiguration.updated_at` to determine if reconfiguration is allowed.
- **Module Subtabs System**: Each module has dedicated subtabs displayed in a separate row beneath the main module navigation:
  - **Arrangement**: "Dine arrangement", "Opprett arrangement", "Arrangement resultat"
  - **Medlemskap**: "Dine medlemskapsplaner", "Medlemmer" (with detailed fields: medlemsnr, fornavn, etternavn, e-post, telefon, adresse, medlemstype, status, medlem siden, gyldig til), "Medlemsforespørsler", "Medlemsinntekter"
  - **Reservasjoner**: "Reservasjonsmottak", "Bekreftede Reservasjoner"
  - **Utleie**: "Se og opprett utleieobjekt", "Kontrakt & betingelser", "Kommende utleie", "Deres utleie resultat"
  - **Webmail**: "E-post klient" (600px height, includes SMTP validation check), "E-post historikk", "AutoMailer" (AutoMailer subtab only visible when both webmail_accounts AND x_webmailconfiguration SMTP data are configured; allows enable/disable of automatic emails for modules: utleie, arrangement, medlemskap, reservasjoner, salg)
  - **Økonomi**: "Deres resultat", "Se inntekt", "Se utgift", "Betalingsløsninger" (shows individual payment providers: Stripe, Vipps, Klarna, PayPal with enable buttons; displays warning when no payment_integrations configured)
  - **Salg**: "Produkter", "Ordrer", "Salgsstatistikk"
  - Subtab IDs are namespaced per module (e.g., events-my, membership-plans) to prevent collisions. JavaScript uses Bootstrap's `shown.bs.tab` event to handle tab switching, ensuring only one module and subtab is visible at a time.

### System Design Choices
- **Modularity**: Core system features are organized into distinct, manageable modules.
- **Data Flow**: Emphasizes clear data flow, especially in the homepage system (template selection, settings, preview, publishing).
- **Database Structure**: Key tables include `tenant_admin_sessions`, `tenant_admins`, `tenant_admin_modules`, `tenant_company_modules`, `tenant_companies`, `tenant_staff`, `webmail_accounts`, `x_events`, `companies`, `system_modules`, `homepage_templates`, `company_homepage_settings`, `rental_objects`, `rental_reservations`, `contract_documents`, `sales_items`, `reservation_settings`, `webconfiguration`, `website_templates`, and `saas_admins`. The `tenant_admins` table includes a `username` field for authentication and is linked to `tenant_staff`. The `tenant_admin_modules` table includes a `granted_by` column linking to `saas_admins` for permission tracking.

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