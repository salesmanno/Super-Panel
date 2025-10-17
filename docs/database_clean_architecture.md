# Clean Modulær Database-Arkitektur
**Salesman Superportal System**  
**Versjon:** 2.0 (Clean Architecture)  
**Dato:** Oktober 2025

---

## 🎯 Arkitekturprinsipper

### 1. **Modulær Eierskap**
Hver modul eier sine egne tabeller og er ansvarlig for sin egen datalogikk.

### 2. **Loose Coupling**
Moduler kommuniserer via:
- **output_products** (samlet katalog)
- **unified_ledger** (samlet økonomi)
- **email_logs** (automatiske meldinger)

### 3. **Email som Integrasjonslag**
Email-modulen er det eneste som har direkte samspill med andre moduler:
- Sender automatiske bekreftelser
- Logger all kommunikasjon
- Bruker templates for konsistens

---

## 📦 MODULSTRUKTUR

### 🏢 CORE SYSTEM (Fundament)

**Ansvar:** Brukeradministrasjon, tilganger, sesjonshåndtering

**Tabeller:**
```
✅ tenant_companies          - Selskapsinformasjon
✅ tenant_staff              - Ansatte (beskrivende)
✅ tenant_admins             - Admin-kontoer (innlogging)
✅ tenant_admin_sessions     - Sesjonssporing
✅ system_modules            - Tilgjengelige moduler
✅ tenant_company_modules    - Selskap → modul tilgang
✅ tenant_admin_modules      - Admin → modul tilgang
✅ tenant_customers          - Kundedata (delt på tvers)
```

**Relasjoner:**
- Alle moduler har `company_id` FK til `tenant_companies`
- Alle actions har `admin_id` eller `created_by` FK til `tenant_admins`

---

### 💰 SALES MODULE

**Ansvar:** Produktsalg, handlekurv, transaksjoner

**Tabeller (CLEAN):**
```
✅ sales_products            - Produktkatalog (MED cost_price)
✅ sales_cart                - Handlekurv
✅ sales_transactions        - Gjennomførte salg
✅ sales_categories          - Kategorier
```

**FJERNES:**
```
❌ sales_items               - Duplikat av sales_products
❌ sales_orders              - Duplikat ordresystem
❌ sales_orders_items        - Duplikat ordrelinje
❌ sales_products_stock      - (hvis ikke i bruk)
```

**Data Flow:**
1. Produkt opprettes → `sales_products`
2. Auto-synk → `output_products` (marketplace)
3. Kjøp → `sales_transactions`
4. Auto-synk → `unified_ledger` (økonomi)
5. Auto-send → Email via `email_logs`

**Profittsporing:**
```sql
sales_products:
  cost_price DECIMAL(10,2)
  selling_price DECIMAL(10,2)
  profit_margin GENERATED AS (selling_price - cost_price) STORED
```

---

### 🎉 EVENTS MODULE

**Ansvar:** Arrangementer, billetter, innsjekking

**Tabeller:**
```
✅ events_eventdata          - Arrangement (MED cost_price)
✅ events_checkin            - Deltakere/innsjekking
```

**Nye kolonner:**
```sql
events_eventdata:
  cost_price DECIMAL(10,2) DEFAULT 0
  profit_per_ticket GENERATED AS (
    CASE 
      WHEN max_attendees > 0 THEN ticket_price - (cost_price / max_attendees)
      ELSE ticket_price - cost_price
    END
  ) STORED
```

**Data Flow:**
1. Arrangement opprettes → `events_eventdata`
2. Auto-synk → `output_products`
3. Billettkjøp → `events_checkin`
4. Auto-synk → `unified_ledger`
5. Auto-send → Email bekreftelse

---

### 👥 MEMBERSHIP MODULE

**Ansvar:** Medlemskap, abonnementer

**Tabeller:**
```
✅ membership_plans          - Medlemskapsplaner
✅ membership_transactions   - Kjøp/registrering
✅ membership_members        - Aktive medlemmer
```

**FJERNES:**
```
❌ x_membership_plan         - Gammel PostgreSQL tabell (migrer data)
```

**Data Flow:**
1. Plan opprettes → `membership_plans`
2. Auto-synk → `output_products`
3. Kjøp → `membership_transactions`
4. Aktivering → `membership_members`
5. Auto-synk → `unified_ledger`
6. Auto-send → Email bekreftelse

---

### 🏠 RENTAL MODULE

**Ansvar:** Utleie av objekter, reservasjoner

**Tabeller:**
```
✅ rental_objects            - Utleieobjekter
✅ rental_reservations       - Reservasjoner/booking
```

**Data Flow:**
1. Objekt opprettes → `rental_objects`
2. Auto-synk → `output_products`
3. Booking → `rental_reservations`
4. Auto-synk → `unified_ledger`
5. Auto-send → Email bekreftelse

---

### 💼 SERVICE BOOKINGS MODULE (NY)

**Ansvar:** Tidsbaserte tjenester (frisør, massasje, etc.)

**Tabell (NY):**
```sql
✅ service_bookings
  id CHAR(36) PK
  company_id FK
  admin_id FK
  
  -- Service
  service_name VARCHAR(255)
  service_description TEXT
  selling_price DECIMAL(10,2)
  cost_price DECIMAL(10,2) NULL
  profit_margin GENERATED AS (selling_price - IFNULL(cost_price, 0)) STORED
  
  -- Tidsslot
  duration INT (minutter)
  date_from DATE
  time_from TIME
  date_to DATE
  time_to TIME
  
  -- Tildeling
  assigned_staff_id FK (tenant_staff)
  
  -- Kunde
  customer_name, customer_email, customer_phone
  payment_method, payment_status
  
  -- Status
  status VARCHAR(50) -- 'available', 'booked', 'completed', 'blocked', 'cancelled'
  
  notes TEXT
  created_at, updated_at
```

**Data Flow:**
1. Tidsslot opprettes → `service_bookings` (status='available')
2. Auto-synk → `output_products` (product_type='service')
3. Kunde booker → status='booked'
4. Fullført → status='completed'
5. Auto-synk → `unified_ledger`
6. Auto-send → Email bekreftelse

---

### 📧 EMAIL MODULE (Integrasjonslag)

**Ansvar:** E-postklient + automatiske meldinger til andre moduler

**Tabeller:**
```
✅ email_accounts            - IMAP/SMTP kontoer
✅ email_messages            - Innkommende/utgående
✅ email_folders             - Inbox, Sent, Drafts
✅ email_drafts              - Utkast
✅ email_attachments         - Vedlegg
✅ email_configs             - SMTP-konfigurasjon
✅ email_templates (NY)      - Automatiseringsmaler
✅ email_logs (NY)           - Sendingslogg
```

**FJERNES:**
```
❌ x_webmailconfiguration    - Gammel PostgreSQL (migrer til email_configs)
```

#### **NY: email_templates**
```sql
CREATE TABLE email_templates (
  id CHAR(36) PK DEFAULT (UUID()),
  company_id CHAR(36) FK,
  
  template_key VARCHAR(100) UNIQUE -- 'sale_receipt', 'event_confirmation', etc.
  name VARCHAR(255)
  subject_template VARCHAR(500)    -- "Kvittering for {{order_id}}"
  body_template TEXT                -- HTML med {{placeholders}}
  
  is_active BOOLEAN DEFAULT TRUE
  created_at, updated_at
);
```

**Standard templates:**
- `sale_receipt` - Salgskvittering
- `event_confirmation` - Arrangement-bekreftelse
- `membership_confirmation` - Medlemskap-bekreftelse
- `rental_confirmation` - Utleie-bekreftelse
- `service_booking_confirmation` - Service-booking bekreftelse

#### **NY: email_logs**
```sql
CREATE TABLE email_logs (
  id CHAR(36) PK DEFAULT (UUID()),
  company_id CHAR(36) FK,
  template_id CHAR(36) FK (email_templates),
  
  module_source VARCHAR(50)  -- 'sales', 'events', 'membership', 'rental', 'service'
  source_id CHAR(36)         -- ID fra modul (transaction_id, event_id, etc.)
  
  recipient_email VARCHAR(255)
  subject VARCHAR(500)
  
  sent_at DATETIME
  status VARCHAR(50)         -- 'sent', 'failed', 'pending'
  error_message TEXT
  
  created_at
);
```

**Integrasjonspunkter:**

| Modul | Trigger | Template | Data |
|-------|---------|----------|------|
| Sales | `sales_transactions` INSERT | `sale_receipt` | order_id, items, total, customer |
| Events | `events_checkin` INSERT | `event_confirmation` | event_title, date, ticket_type |
| Membership | `membership_transactions` INSERT | `membership_confirmation` | plan_name, duration, benefits |
| Rental | `rental_reservations` INSERT | `rental_confirmation` | object_name, date_from, date_to |
| Service | `service_bookings` UPDATE (booked) | `service_booking_confirmation` | service_name, datetime, staff |

---

### 💼 ECONOMY MODULE

**Ansvar:** Økonomistyring, fakturaer, bankkonto

**Tabeller:**
```
✅ economy_transactions      - Transaksjoner
✅ economy_invoices          - Fakturaer
✅ economy_bank_accounts     - Bankkontoer
✅ economy_payment_gateways  - Stripe, Vipps, Klarna
✅ economy_categories        - Kategorier
✅ economy_daily_summary     - Daglig oppsummering
✅ economy_tax_rates         - MVA-satser
```

**NY: unified_ledger (samlet økonomi på tvers)**
```sql
CREATE TABLE unified_ledger (
  id CHAR(36) PK DEFAULT (UUID()),
  order_id VARCHAR(100)
  company_id CHAR(36) FK,
  
  -- Kilde
  module_source VARCHAR(50)     -- 'sales', 'events', 'membership', 'rental', 'service'
  item_type VARCHAR(50)         -- 'product', 'event', 'membership', 'rental', 'service'
  source_id CHAR(36)            -- ID fra original modul
  
  -- Vare
  item_name VARCHAR(255)
  quantity INT DEFAULT 1
  
  -- Økonomi
  selling_price DECIMAL(10,2)
  cost_price DECIMAL(10,2) NULL
  profit GENERATED AS (selling_price - IFNULL(cost_price, 0)) STORED
  
  -- Kunde
  customer_name, customer_email
  
  -- Betaling
  payment_method, payment_status
  
  -- Status
  status VARCHAR(50) DEFAULT 'active'
  
  -- Tid
  transaction_date DATETIME
  created_at
  
  -- Metadata (JSON for modulspesifikk data)
  metadata JSON
  
  INDEX: company_id, module_source, order_id, transaction_date
  UNIQUE: (module_source, source_id)
);
```

**Data synk til unified_ledger:**
```
Sales:     sales_transactions → unified_ledger
Events:    events_checkin → unified_ledger
Membership: membership_transactions → unified_ledger
Rental:    rental_reservations → unified_ledger
Service:   service_bookings → unified_ledger
```

---

### 🛒 MARKETPLACE (Output Products)

**Ansvar:** Samlet produktkatalog på tvers av moduler

**Tabell:**
```
✅ output_products           - READ-ONLY aggregert katalog
```

**Oppdatert struktur:**
```sql
output_products:
  id CHAR(36) PK
  company_id FK
  product_type VARCHAR(50)   -- 'product', 'event', 'membership', 'rental', 'service'
  product_id CHAR(36)        -- FK til kildetabell
  name VARCHAR(255)          -- ⭐ NY: Denormalisert for ytelse
  price DECIMAL(10,2)        -- ⭐ NY: Denormalisert for ytelse
  is_public BOOLEAN          -- Synlighet på nettside
  created_at, updated_at
```

**Auto-synk fra:**
- `sales_products` → output_products (product_type='product')
- `events_eventdata` → output_products (product_type='event')
- `membership_plans` → output_products (product_type='membership')
- `rental_objects` → output_products (product_type='rental')
- `service_bookings` → output_products (product_type='service')

---

### 🌐 WEBSITE MODULE

**Ansvar:** Nettside-templates, konfigurasjon

**Tabeller:**
```
✅ website_templates         - Forhåndsdefinerte design
✅ webconfiguration          - Valgt template
✅ tenant_website_config     - Modulspesifikk config
✅ tenant_settings           - Generelle innstillinger
✅ tenant_storage            - Fillagring
```

---

### 🔧 SAAS ADMIN (Salesman-ansatte)

**Ansvar:** Salesman.no administrasjon

**Tabeller:**
```
✅ saas_staff                - Salesman-ansatte
✅ saas_admins               - Salesman admin-kontoer
✅ saas_subscription_plans   - Abonnementsplaner
✅ tenant_subscriptions      - Kundeabonnementer
✅ self_company              - Salesman.no info
✅ saas_invoices             - Fakturaer til kunder
✅ core_sys_news             - Systemnyheter
✅ core_sys_updates          - Systemoppdateringer
```

---

## 🔄 DATA FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────┐
│                        CORE SYSTEM                          │
│  tenant_companies, tenant_admins, tenant_staff, modules     │
└─────────────────────────────────────────────────────────────┘
                              │
                ┌─────────────┴─────────────┐
                │                           │
┌───────────────▼──────────┐   ┌────────────▼──────────────┐
│  MODULE TABELLER         │   │  INTEGRASJONSLAG          │
│  (Eier egen data)        │   │  (Delt på tvers)          │
│                          │   │                           │
│  • sales_products        │   │  • output_products        │
│  • events_eventdata      │───┤  • unified_ledger         │
│  • membership_plans      │   │  • email_templates        │
│  • rental_objects        │   │  • email_logs             │
│  • service_bookings      │   │  • tenant_customers       │
└──────────────────────────┘   └───────────────────────────┘
                              │
                ┌─────────────┴─────────────┐
                │                           │
┌───────────────▼──────────┐   ┌────────────▼──────────────┐
│  TRANSAKSJONSSYSTEMER    │   │  EMAIL AUTOMATISERING     │
│                          │   │                           │
│  • sales_transactions    │   │  1. Transaksjonsdata      │
│  • events_checkin        │───┤  2. Hent template         │
│  • membership_trans..    │   │  3. Generer e-post        │
│  • rental_reservations   │   │  4. Send + logg           │
│  • service_bookings      │   │                           │
└──────────────────────────┘   └───────────────────────────┘
```

---

## 🗑️ MIGRERINGSSTRATEGI

### Fase 1: Opprette nye tabeller
```sql
1. ✅ CREATE TABLE email_templates
2. ✅ CREATE TABLE email_logs
3. ✅ CREATE TABLE service_bookings
4. ✅ CREATE TABLE unified_ledger
```

### Fase 2: Legge til kolonner
```sql
1. ✅ ALTER TABLE events_eventdata ADD COLUMN cost_price
2. ✅ ALTER TABLE events_eventdata ADD COLUMN profit_per_ticket (GENERATED)
3. ✅ ALTER TABLE output_products ADD COLUMN name, price
```

### Fase 3: Migrere data
```sql
1. ✅ Migrer x_membership_plan → membership_plans
2. ✅ Migrer x_webmailconfiguration → email_configs
3. ✅ Migrer sales_items → sales_products (hvis data finnes)
4. ✅ Migrer sales_orders → sales_transactions (hvis data finnes)
```

### Fase 4: Slette duplikater
```sql
1. ❌ DROP TABLE sales_items
2. ❌ DROP TABLE sales_orders
3. ❌ DROP TABLE sales_orders_items
4. ❌ DROP TABLE x_events
5. ❌ DROP TABLE x_reservations
6. ❌ DROP TABLE x_membership_plan (etter migrering)
7. ❌ DROP TABLE x_webmailconfiguration (etter migrering)
```

### Fase 5: Oppdatere PHP-kode
```php
1. ✅ Alle referanser til sales_items → sales_products
2. ✅ Alle referanser til sales_orders → sales_transactions
3. ✅ Implementere email-sending i alle moduler
4. ✅ Implementere unified_ledger sync
```

---

## 📧 EMAIL INTEGRASJON - IMPLEMENTERING

### Template-struktur

**sale_receipt.html:**
```html
<h1>Kvittering for salg</h1>
<p>Ordre ID: {{order_id}}</p>
<p>Kunde: {{customer_name}}</p>
<p>Antall varer: {{items_sold}}</p>
<p>Totalbeløp: {{total_amount}} NOK</p>
<p>Betalingsmetode: {{payment_method}}</p>
```

**event_confirmation.html:**
```html
<h1>Bekreftelse på arrangement</h1>
<p>Arrangement: {{event_title}}</p>
<p>Dato: {{event_date}}</p>
<p>Tid: {{event_time}}</p>
<p>Sted: {{event_location}}</p>
<p>Billetttype: {{ticket_type}}</p>
```

### Triggere i kode

**sales/checkout.php:**
```php
// Etter successful checkout
$email_service->send([
    'template' => 'sale_receipt',
    'to' => $customer_email,
    'data' => [
        'order_id' => $order_id,
        'customer_name' => $customer_name,
        'items_sold' => $items_sold,
        'total_amount' => $total_amount,
        'payment_method' => $payment_method
    ],
    'module_source' => 'sales',
    'source_id' => $transaction_id
]);
```

**events/checkin.php:**
```php
// Etter successful checkin
$email_service->send([
    'template' => 'event_confirmation',
    'to' => $customer_email,
    'data' => [
        'event_title' => $event['title'],
        'event_date' => $event['date_from'],
        'event_time' => $event['time_from'],
        'event_location' => $event['location'],
        'ticket_type' => $ticket_type
    ],
    'module_source' => 'events',
    'source_id' => $checkin_id
]);
```

---

## ✅ VALIDERING

### Database-integritet
```sql
-- Sjekk duplikater
SELECT COUNT(*) FROM sales_items;        -- Skal være 0 (slettet)
SELECT COUNT(*) FROM sales_orders;       -- Skal være 0 (slettet)
SELECT COUNT(*) FROM x_events;           -- Skal være 0 (slettet)

-- Sjekk nye tabeller
SELECT COUNT(*) FROM email_templates;    -- >= 5 (standard templates)
SELECT COUNT(*) FROM service_bookings;   -- OK
SELECT COUNT(*) FROM unified_ledger;     -- OK
```

### Modul-uavhengighet
```
✅ Sales kan fungere uten Events
✅ Events kan fungere uten Membership
✅ Rental kan fungere uten Sales
✅ Email er eneste tverrgående modul (sender bekreftelser)
```

### Email-integrasjon
```
✅ Salg sender kvittering automatisk
✅ Arrangement sender bekreftelse automatisk
✅ Medlemskap sender bekreftelse automatisk
✅ Utleie sender bekreftelse automatisk
✅ Service booking sender bekreftelse automatisk
✅ Alle sendte e-poster logges i email_logs
```

---

## 🎯 RESULTAT

**Før (Kaos):**
- Duplikat-tabeller (`sales_items` + `sales_products`)
- Duplikat ordresystemer (`sales_orders` + `sales_transactions`)
- Redundante x_* tabeller
- Ingen kostpris-tracking
- Ingen automatiske e-poster
- Ingen samlet økonomi

**Etter (Clean):**
- ✅ Én tabell per funksjon
- ✅ Moduler eier egen data
- ✅ Email-modul integrerer automatisk
- ✅ Kostpris-tracking i alle moduler
- ✅ Unified_ledger for samlet økonomi
- ✅ Service_bookings for servicenæring
- ✅ Automatiske e-poster med templates

---

**Versjon:** 2.0 Clean Architecture  
**Oppdatert:** Oktober 2025  
**Status:** Klar for implementering
