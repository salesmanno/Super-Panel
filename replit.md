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
Prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs (faner) for efficient navigation, with sub-tabs reflecting current content. The design includes a blue hero banner and consistent use of company logos.

### Technical Implementations
- **Authentication**: Session-based system with robust backend validation and failsafe messages. Includes a complete session tracking system with login/logout logging, IP addresses, user agents, activity timestamps, and auto-cleanup of inactive sessions.
- **Module & Privilege Management**: Granular access control using `module_privileges` and `module_visibility` tables ensures modules are only visible if `can_access` is true. Super_users can dynamically update their own module privileges.
- **Webmail Client**: Integrated client supporting inbox, compose, and message viewing, connected to live database data with read-only configuration.
- **Events Module**: Comprehensive CRUD operations for event management, including image support (URL or file upload) and conditional display of member pricing.
- **User Profiles**: Stores personal information for features like automatic email signatures and profile editing.
- **Homepage Template System**: An isolated system offering 5 pre-defined templates, including management, settings flow, public rendering, live preview, and publishing options (SEO, custom domains). Templates use placeholders like `{{company_name}}` and `{{company_logo}}`.
- **Rental System**: Manages individual rental objects with serial number tracking, condition notes, and integrates with the reservation system for booking, overlap checks, and pricing. Supports contract document uploads (PDF, DOC, DOCX, TXT, RTF).
- **Global Reservation System**: Manages reservations across multiple modules (rental, sales, events, economy) with unique reservation numbering (e.g., RENT, SALE, EVNT). Includes API endpoints for retrieval, advanced filtering, and statistics, respecting user privileges and `reservation_settings`.
- **Sales Module**: Functionality to add sales items via a dedicated sub-pane, with form data entry, JavaScript submission, and API endpoints for storage, including validation and duplicate SKU checks.
- **Dynamic Database Status**: Live database connection status indicator in the navigation, with automatic checks and visual feedback (pulsating dot) and graceful degradation (gray background, disabled login) upon disconnection.
- **Website Configuration (Ditt Nettsted)**: Module-based feature with one-time configuration logic. Tenant admins can select website templates once per privilege grant. If they lose and regain the module privilege, they can reconfigure. Template selections are stored in the `webconfiguration` table (with custom_css and custom_colors fields) and can be retrieved via public API (`api/get_company_website.php`) by external domains using company_id or subdomain. Includes 5 pre-defined templates: Moderne Bedrift, E-handel Basic, Kreativ Portefølje, Restaurant & Café, and Minimalistisk Landing. Lock mechanism compares `tenant_admin_modules.granted_at` with `webconfiguration.updated_at` to determine if reconfiguration is allowed.

### System Design Choices
- **Modularity**: Core system features are organized into distinct, manageable modules.
- **Data Flow**: Emphasizes clear data flow, especially in the homepage system (template selection, settings, preview, publishing).
- **Database Structure**: Key tables include `tenant_admin_sessions`, `webmail_accounts`, `x_events`, `companies`, `super_users`, `modules`, `module_privileges`, `homepage_templates`, `company_homepage_settings`, `super_user_profiles`, `rental_objects`, `rental_reservations`, `contract_documents`, `sales_items`, `reservation_settings`, `webconfiguration`, `website_templates`, and `saas_admins`. The `tenant_admins` table now includes a `username` field for authentication and is linked to `tenant_staff`. The `tenant_admin_modules` table includes a `granted_by` column linking to `saas_admins` for permission tracking.

## External Dependencies
- **Database**: Dual database support:
  - **Production**: MySQL hosted on Domeneshop (`salesmanno04.mysql.domeneshop.no/salesmanno04`)
  - **Development/Testing**: PostgreSQL (auto-detected via DATABASE_URL environment variable)
  - Connection managed via `config/database.php` using PDO with automatic database type detection
  - All boolean comparisons use TRUE/FALSE (PostgreSQL compatible)
  - Timestamp columns use `login_at`, `logout_at` naming convention
  - Interval syntax uses PostgreSQL format: `NOW() - INTERVAL '1 MINUTE' * ?`
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP (`smtp.domeneshop.no:587` with TLS) and IMAP (`imap.domeneshop.no:993` with SSL) for the integrated webmail client.
- **Storage**: Object storage for contract document uploads.