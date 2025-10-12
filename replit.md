# Salesman Superportal System

## Overview
The Salesman Superportal System is a comprehensive super_user admin portal for managing company operations. It provides a centralized platform for company administrators to manage modules and settings based on their privileges. The system aims to streamline business processes across various domains, including sales, webmail, reservations, economy, membership, rental, events, and marketplace management, with the vision of offering a robust platform for efficient oversight of diverse company operations.

## User Preferences
- Language: Norwegian
- Clean, compact dashboard design (not oversized)
- Simple navigation header
- Focus on functionality over visual complexity
- Modules should be displayed as tabs (faner) instead of cards
- Only show modules where user has privileges (can_access = true)
- Each module tab should have sub-tabs with the same name showing current content

## System Architecture
The system is built on a PHP 8.2 backend with a MySQL database.
- **UI/UX Decisions**: Prioritizes a clean, compact dashboard with a simple navigation header. Modules are presented as Bootstrap 5 tabs (faner) for efficient navigation, with sub-tabs reflecting current content. Color schemes include a blue hero banner with company logos.
- **Technical Implementations**:
    - **Authentication**: Session-based system with failsafe messages.
    - **Module & Privilege Management**: Granular access control using `module_privileges` and `module_visibility` tables; modules only appear if `can_access` is true. Super_users can update their own module privileges dynamically with backend validation.
    - **Webmail Client**: Integrated client with inbox, compose, and message viewing, connected to live database data, supporting read-only configuration.
    - **Events Module**: Comprehensive event management with CRUD operations via JavaScript and dedicated API endpoints. Supports event images via URL or file upload, and member pricing (displayed only when membership module is active).
    - **User Profiles**: `super_user_profiles` table stores personal information for features like automatic email signatures and a profile editing page.
    - **Homepage Template System**: An isolated system allowing companies to choose from 5 pre-defined homepage templates. Includes template management, settings flow, public rendering, live preview, and publishing options (SEO, custom domains).
    - **Rental System**: Manages individual rental objects with serial number tracking and condition notes. Integrates with a reservation system for booking periods, overlap checks, and price calculation. Includes contract management for document uploads (PDF, DOC, DOCX, TXT, RTF).
    - **Global Reservation System**: Manages reservations across modules (rental, sales, events, economy), assigning unique reservation numbers with prefixes (RENT, SALE, EVNT, ECON). Includes API endpoint for retrieving all reservations based on user privileges and settings, advanced filtering, and statistics.
    - **Homepage Template Placeholders**: Templates use `{{company_name}}` and `{{company_logo}}` placeholders for flexibility, with previews showing a generic logo and actual usage displaying the company's specific logo and name.
    - **Homepage Template Storage**: Homepage templates are stored as complete HTML content in the `homepage_templates` table, and `company_homepage_settings` tracks template choices per company.
    - **Sales Module**: Includes functionality to add sales items via a dedicated sub-pane, with a form for data entry, JavaScript for submission, and an API endpoint (`create_sales_item.php`) for storage in a `sales_items` table, including validation and duplicate SKU checks.
    - **Reservation Settings**: A `reservation_settings` table controls which modules (arrangements, economy, sales, membership, webmail, rental) are included in the global reservation system, respecting both these settings and user privileges.
- **System Design Choices**:
    - **Modularity**: Core system features are organized into distinct modules.
    - **Data Flow (Homepage System)**: Involves template selection, settings configuration, public rendering, and live preview, all interacting with the database via API.
    - **Database Structure**: Key tables include `webmail_accounts`, `webmail_messages`, `x_events` (with `image_url` and `member_price` columns), `companies`, `super_users`, `modules`, `module_privileges`, `module_visibility`, `homepage_templates`, `company_homepage_settings`, `super_user_profiles`, `rental_objects`, `rental_reservations`, `x_rental`, `contract_documents`, `sales_items`, and `reservation_settings`.

## External Dependencies
- **Database**: MySQL database hosted on Domeneshop (`salesmanno04.mysql.domeneshop.no/salesmanno04`).
  - Connection credentials stored in `config/database.php`
  - Uses PDO for secure database operations
- **UI Framework**: Bootstrap 5.
- **Email Protocols**: SMTP (`smtp.domeneshop.no:587` with TLS) and IMAP (`imap.domeneshop.no:993` with SSL) for the integrated webmail client.
- **Storage**: Object storage for contract document uploads.

## Recent Updates
- 2025-10-12: **Database-struktur optimalisering** - Restrukturert tenant_admins og tenant_staff tabeller
  * Koblet tenant_admins til tenant_staff via tenant_staff_id foreign key
  * Reversert relasjon: tenant_admins.tenant_staff_id → tenant_staff.id
  * Fjernet salary og hire_date kolonner fra tenant_staff (ikke nødvendig)
  * Oppdatert database-skjema dokumentasjon med ny struktur
  * Beholdt alle persondata (navn, kontaktinfo, adresse, jobbinformasjon)
- 2025-10-12: **Dashboard for innloggede brukere** - Komplett dashboard med statistikk og sanntidsoppdateringer
  * Fire statistikk-kort: tilgjengelige moduler, kontostatus, brukerrolle, og siste innlogging
  * Dynamisk beregning av innloggingstid (Nå, X min siden, X t siden, I går, eller faktisk dato/tid)
  * Modul-oversikt med ikoner og beskrivelser i responsive kort
  * SSE-drevet nyhetswidget med sanntidsoppdateringer
  * Fullstendig responsiv layout for desktop, tablet og mobil
  * Moderne gradient-design med purple (#667eea til #764ba2)
  * Interaktive hover-effekter og smooth transitions
  * Automatisk utloggingsknapp med session-håndtering
- 2025-10-12: **SSE (Server-Sent Events) og responsiv design** - Implementert sanntids nyhetsoppdateringer og fullstendig responsivitet
  * Server-Sent Events for real-time nyhetsstreaming
  * SSE endpoint med automatisk oppdatering hver 30. sekund
  * Heartbeat (5 sek) for å forhindre timeout
  * Automatisk reconnect ved tilkoblingstap (5 sek forsinkelse)
  * Fullstendig responsiv design: desktop, tablet (≤992px), og mobil (≤576px)
  * Nyhetsseksjon nå synlig på alle enheter med tilpasset layout
  * EventSource API med proper cleanup og feilhåndtering
  * Sikker streaming med output buffer-håndtering
- 2025-10-12: **Nyhetsvisning med alle felt** - Koblet alle core_sys_news felt til nyhetsrammen på innloggingssiden
  * Viser alle databasefelt: title, content, news_type, severity
  * news_type vises som badge med ikon og norsk label (System, Ny funksjon, Vedlikehold, etc.)
  * severity vises som fargekodet badge for alle nivåer (kritisk=rød, høy=orange, middels=blå, lav=grønn)
  * Hver nyhet viser: tittel, innhold, type, alvorlighetsgrad og publiseringsdato
  * Konsistent styling med lilla gradient-tema
  * Sikker visning med HTML-escaping
- 2025-10-12: **Database Migration to salesmanno04** - Migrated to new MySQL database structure
  * Disconnected from salesmanno03 and connected to salesmanno04.mysql.domeneshop.no
  * Implemented tenant-based architecture with tenant_companies and tenant_admins tables
  * Login system now uses username (not email) for tenant_admins authentication
  * Created refined company login page with sharper design (purple gradient: #667eea to #764ba2)
  * Branding updated to "Salesman.no Bedriftsportal"
  * Integrated news system from core_sys_news table
  * Module-based access control with tenant_admin_modules and tenant_company_modules
  * Created comprehensive database schema documentation in docs/database_schema.sql
  * Key tables: tenant_companies, tenant_admins (with username field), system_modules, core_sys_news
  * Authentication uses PDO prepared statements for security
  * API endpoint /api/get_latest_news.php for system news display
  * Session regeneration after login for security
  * Fixed PDO LIMIT binding issue for news queries
- 2025-10-10: **Arrangementsbilde og medlemspris** - Utvidet events-modulen med bildefunksjonalitet og medlemsprising
  * Lagt til `image_url` og `member_price` kolonner i `x_events` tabellen
  * Bildeadresse kan legges inn direkte som URL eller lastes opp som fil
  * Medlemspris vises kun når medlemsmodulen er aktiv (use_membership privilege)
  * Arrangementsbilde vises i oversikten med thumbnail (60x60px) eller fallback-ikon
  * API-endepunkter oppdatert: `get_events.php` og `create_event.php` støtter nye felter
  * Frontend viser medlemspris i grønn tekst under ordinær pris
- 2025-10-01: **Database-tilkoblingsindikator** - Lagt til live database-status i navigasjonen
  * Visuell indikator med grønn/rød pulserende prikk i navbar
  * Hover-funksjonalitet viser database-server og -navn (MySQL: salesmanno04.mysql.domeneshop.no/salesmanno04)
  * API-endpoint `/api/check_database_status.php` returnerer sanntids database-status
  * Automatisk oppdatering hver 30. sekund for live overvåking
  * Animert pulsering som indikerer tilkoblingsstatus
- 2025-08-22: **Reservasjonsoversikt** - Implementert detaljert oversikt med både dagsoversikt og timesoversikt for reservasjoner
  * Opprettet tabs-struktur i reservasjonsmodulen: "Reservasjonsoversikt" og "Reservasjonsmottak"
  * Dagsoversikt grupperer reservasjoner etter dato med kortvisning og antallsindikatorer
  * Timesoversikt viser detaljert timeplan med reservasjoner organisert etter dato og klokkeslett
  * Radioknapper for å bytte mellom dag- og timevisning med JavaScript-funksjonalitet
  * Bruker eksisterende get_all_reservations.php API som respekterer reservation_settings og bruger-privilegier
- 2025-08-22: **Hjemmeside-forhåndsvisning fikset** - "Se hjemmeside" viser nå valgt mal med bedriftens logo
  * Oppdatert get_homepage_preview.php til å bruke companies.logo_url som primær logokilde
  * Implementert automatisk erstatning av hardkodede Salesman-logoer med bedriftens logo
  * Prioriteringskjede: innstillinger-logo > bedrift-logo-url > fallback-logo
  * Fjernet alle Salesman-logo-variasjoner og erstattet med bedriftens faktiske logo