# Nettstedinnstillinger Opprettet

## Dato: 28. oktober 2025

### Oversikt
Opprettet komplette nettstedinnstillinger for "Ditt Nettsted"-modulen for alle aktive bedrifter i systemet.

### Tabeller Fylt Med Data

#### 1. tenant_website_config
Modul-spesifikke innstillinger for hver bedrift. Disse innstillingene er låst bak admin-passord når de først er konfigurert.

**Felt som ble satt:**
- `company_id` - Bedriftens ID
- `module_id` - Modul ID for "Ditt Nettsted" (f9d42479-b182-11f0-b9ad-b02628506e5c)
- `is_enabled` - Aktivert (1)
- `is_public` - Offentlig tilgjengelig (1)
- `show_in_navigation` - Vis i navigasjon (1)
- `display_name` - Bedriftsnavn
- `display_order` - Rekkefølge (1)
- `navigation_icon` - Ikon (bi-globe)
- `color_scheme` - Fargevalg (default)
- `seo_title` - SEO tittel (f.eks. "Bergsøy Danseklubb - Offisiell Nettside")
- `seo_description` - SEO beskrivelse
- `seo_keywords` - Søkeord
- `is_published` - Publisert (1)
- `published_at` - Publiseringsdato

#### 2. tenant_website_general
Globale nettstedinnstillinger for hver bedrift.

**Felt som ble satt:**
- `site_title` - Nettstedstittel
- `site_description` - Nettstedbeskrivelse
- `logo_url` - Logo URL (fra tenant_companies eller default)
- `primary_color` - Primærfarge (#667eea)
- `secondary_color` - Sekundærfarge (#764ba2)
- `header_layout` - Header layout (standard)
- `footer_layout` - Footer layout (standard)
- `navigation_style` - Navigasjonsstil (top)
- `hero_enabled` - Hero-seksjon aktivert (1)
- `hero_title` - Hero-tittel (f.eks. "Velkommen til Bergsøy Danseklubb")
- `hero_description` - Hero-beskrivelse
- `hero_button_text` - Knappetekst ("Kontakt oss")
- `hero_button_url` - Knappelenke (/kontakt)
- `contact_email` - Kontakt e-post
- `contact_phone` - Kontakttelefon
- `contact_address` - Kontaktadresse
- `footer_text` - Footer-tekst med copyright
- `is_active` - Aktiv (1)
- `maintenance_mode` - Vedlikeholdsmodus (0)

### Bedrifter Med Komplette Innstillinger

1. **Bergsøy Danseklubb** (bergsoy)
   - SEO: "Bergsøy Danseklubb - Offisiell Nettside"
   - Status: Publisert og aktivert
   
2. **Nordic Fitness AS** (nordicfitness)
   - SEO: "Nordic Fitness AS - Offisiell Nettside"
   - Status: Publisert og aktivert
   - *Hadde allerede generelle innstillinger*
   
3. **Oslo Tech Solutions AS** (oslotech)
   - SEO: "Oslo Tech Solutions AS - Offisiell Nettside"
   - Status: Publisert og aktivert
   
4. **Fjord Restaurant AS** (fjordrestaurant)
   - SEO: "Fjord Restaurant AS - Offisiell Nettside"
   - Status: Publisert og aktivert
   
5. **Nordic Outdoor AS** (nordicoutdoor)
   - SEO: "Nordic Outdoor AS - Offisiell Nettside"
   - Status: Publisert og aktivert

### Tilgang til Innstillinger

For å se/redigere nettstedinnstillingene:

1. Logg inn på dashboardet
2. Klikk på bedriftsnavnet i header
3. Siden vil vise nettstedinnstillingene
4. Hvis siden er låst, oppgi admin-passord for å låse opp i 30 minutter
5. Etter opplåsing vises en nedtellingstimer som viser gjenværende redigeringstid

### Passordlås-funksjon

Innstillingene er låst bak admin-passord når:
- `tenant_admin_modules.granted_at` <= `tenant_website_config.updated_at`
- Og det ikke finnes en aktiv opplåsing i sesjonen

Når låst opp:
- Opplåsing varer i 30 minutter
- Nedtellingstimer vises på siden
- Automatisk utlogging når tiden går ut

### SQL-filer Opprettet

1. `setup/create_website_config.sql` - Opprinnelig script (erstattet)
2. `setup/insert_website_config.sql` - Script for å sette inn modul-spesifikke innstillinger
3. `setup/insert_website_general.sql` - Script for å sette inn generelle innstillinger

### Tekniske Detaljer

- Modul ID: `f9d42479-b182-11f0-b9ad-b02628506e5c` (Ditt Nettsted)
- Admin ID (created_by): `4541881c-b0e4-11f0-a1bb-b02628506e5c`
- Alle innstillinger opprettet: 28.10.2025 07:38-07:46
- Database: MySQL (salesmanno04.mysql.domeneshop.no)
