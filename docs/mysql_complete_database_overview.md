# MySQL Produksjonsdatabase - Komplett Oversikt
**Database:** salesmanno04  
**Server:** salesmanno04.mysql.domeneshop.no  
**Sist oppdatert:** Oktober 2025

---

## 📊 TABELLOVERSIKT (Gruppert etter modul)

### 🏢 CORE SYSTEM (Fundament)

#### **tenant_companies**
Multi-tenant selskapsinformasjon
```
id (CHAR(36) PK)
name, legal_name, entity_type
subdomain (UNIQUE), domain (UNIQUE)
industry, company_size
contact_email, contact_phone, website, logo_url
address_street, address_city, address_state, address_zip, address_country
tax_id, tax_registered
cash_payment (BOOLEAN) - Payment gateway toggle
about_us (TEXT) - Company description
is_active, trial_ends_at
created_at, updated_at
```

#### **tenant_staff**
Ansattinformasjon (beskrivende data)
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
employee_code, first_name, last_name
email, phone, date_of_birth
job_title, department, employment_type
address_street, address_city, address_state, address_zip, address_country
emergency_contact_name, emergency_contact_phone
is_active, last_login
created_at, updated_at
```

#### **tenant_admins**
Admin-kontoer med innloggingsrettigheter
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
tenant_staff_id (FK -> tenant_staff) NULL
username, email (UNIQUE per company)
password_hash
name, role
is_active, last_login
mfa_enabled, mfa_secret
created_at, updated_at
UNIQUE(company_id, username)
UNIQUE(company_id, email)
```

#### **tenant_admin_sessions**
Sesjonssporing for admins
```
id (CHAR(36) PK)
tenant_admin_id (FK -> tenant_admins)
session_id, ip_address, user_agent
login_at, last_activity, logout_at
is_active
created_at, updated_at
INDEX: tenant_admin_id, session_id, is_active, login_at
```

#### **system_modules**
Tilgjengelige moduler i systemet
```
id (CHAR(36) PK)
module_key (VARCHAR(50) UNIQUE) - 'sales', 'events', etc.
name, description, category
icon, version
is_active
created_at, updated_at
```

#### **tenant_company_modules**
Hvilke moduler selskapet har tilgang til
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
module_id (FK -> system_modules)
is_active, is_public (BOOLEAN) - Public visibility toggle
config (TEXT)
activated_at, deactivated_at
assigned_by (FK -> saas_admins)
created_at
UNIQUE(company_id, module_id)
```

#### **tenant_admin_modules**
Individuelle admin-tilganger til moduler
```
id (CHAR(36) PK)
tenant_admin_id (FK -> tenant_admins)
module_id (FK -> system_modules)
granted_by (FK -> tenant_admins/saas_admins)
granted_at
UNIQUE(tenant_admin_id, module_id)
```

---

### 💰 SALES MODULE

#### **sales_products** ⭐ (PRIMÆR)
Produktkatalog (ny struktur)
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
output_product_id (FK -> output_products)
name, sku, description
cost_price (DECIMAL(10,2)) - For profittberegning
selling_price (DECIMAL(10,2))
profit_margin (GENERATED: selling_price - cost_price)
member_price (DECIMAL(10,2))
category_id
tax_rate, unit
reorder_level
is_active
created_at, updated_at
```

#### **sales_items** ⚠️ (DUPLIKAT - OLD)
Gammel produktstruktur (skal fases ut)
```
id, company_id
item_name, sku, description
price, cost_price
category
stock_quantity, low_stock_threshold
is_active, tax_rate
created_at, updated_at
```

#### **sales_cart**
Handlekurv for admin-brukere
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
admin_id (FK -> tenant_admins)
output_product_id (FK -> output_products)
quantity
created_at, updated_at
```

#### **sales_transactions** ⭐ (PRIMÆR)
Gjennomførte salg (ny struktur)
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
admin_id (FK -> tenant_admins)
order_id (VARCHAR(100))
customer_name, customer_email
items_sold (INT)
total_amount (DECIMAL(10,2))
payment_method, payment_status
receipt_sent (BOOLEAN)
notes (TEXT)
transaction_date
created_at
```

#### **sales_orders** + **sales_orders_items** ⚠️ (DUPLIKAT - OLD)
Gammelt ordresystem (skal fases ut)
```
sales_orders: id, order_number, company_id, customer_name, total, status...
sales_orders_items: order_id, product_id, quantity, price...
```

#### **sales_categories**
Produktkategorier
```
id, company_id
name, description
is_active
created_at, updated_at
```

#### **sales_products_stock**
Lagerstyring (hvis i bruk)
```
id, product_id
quantity, reorder_level
created_at, updated_at
```

---

### 🎉 EVENTS MODULE

#### **events_eventdata**
Arrangementsdata
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
output_product_id (FK -> output_products)
created_by (FK -> tenant_admins)
title, description
date_from, date_to, time_from, time_to
location, location_address
max_attendees, min_age, max_age
ticket_price (DECIMAL(10,2))
cost_price (DECIMAL(10,2)) - NY: For profittberegning
profit_per_ticket (GENERATED) - NY
member_price_enabled, member_price
members_only (BOOLEAN)
image_url
is_active, deleted_at
created_at, updated_at
```

#### **events_checkin**
Innsjekking av deltakere
```
id (CHAR(36) PK)
event_id (FK -> events_eventdata)
company_id (FK -> tenant_companies)
customer_id (FK -> tenant_customers)
customer_name, customer_email, customer_phone
ticket_type, payment_method, payment_status
checked_in_at, checked_in_by
notes
created_at
```

#### **tenant_customers**
Kundedata (delt på tvers av moduler)
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
name, email, phone
address, city, zip
notes
created_at, updated_at
```

---

### 👥 MEMBERSHIP MODULE

#### **membership_plans**
Medlemskapsplaner
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
output_product_id (FK -> output_products)
name, description
price (DECIMAL(10,2))
duration_months
benefits (TEXT)
is_active
created_at, updated_at
```

#### **membership_transactions**
Medlemskapsregistreringer/kjøp
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
plan_id (FK -> membership_plans)
customer_name, customer_email, customer_phone
payment_method, payment_status
start_date, end_date
created_at
```

#### **membership_members**
Aktive medlemmer
```
id, company_id
plan_id, customer_id
status, start_date, end_date
created_at, updated_at
```

---

### 🏠 RENTAL MODULE

#### **rental_objects**
Utleieobjekter
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
output_product_id (FK -> output_products)
name, description
serial_number, condition_notes
rental_price_daily (DECIMAL(10,2))
is_available
contract_document_url
created_at, updated_at
```

#### **rental_reservations**
Utleiereservasjoner
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
rental_object_id (FK -> rental_objects)
customer_name, customer_email, customer_phone
date_from, date_to
total_price, payment_method, payment_status
status ('pending', 'confirmed', 'completed', 'cancelled')
created_at, updated_at
```

---

### 📧 EMAIL MODULE (Integrasjon med alle moduler)

#### **email_accounts**
E-postkontoer (IMAP/SMTP)
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
admin_id (FK -> tenant_admins)
email, password_encrypted
imap_host, imap_port, smtp_host, smtp_port
is_default (BOOLEAN)
created_at, updated_at
```

#### **email_messages**
Innkommende/utgående e-post
```
id (CHAR(36) PK)
account_id (FK -> email_accounts)
folder_id (FK -> email_folders)
message_id (external), subject
from_email, to_email, cc, bcc
body_html, body_text
is_read, is_starred
received_at, sent_at
created_at
```

#### **email_folders**
E-postmapper (Inbox, Sent, Drafts, etc.)
```
id (CHAR(36) PK)
account_id (FK -> email_accounts)
name, type ('inbox', 'sent', 'drafts', 'trash')
created_at
```

#### **email_drafts**
Utkast
```
id (CHAR(36) PK)
account_id (FK -> email_accounts)
to_email, subject, body
created_at, updated_at
```

#### **email_attachments**
Vedlegg
```
id (CHAR(36) PK)
message_id (FK -> email_messages)
filename, file_path, file_size
created_at
```

#### **email_templates** ⭐ (NY - For automatisering)
Maler for automatiske e-poster
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
template_key ('sale_receipt', 'event_confirmation', 'membership_confirmation', 'reservation_confirmation')
name, subject_template
body_template (HTML med {{placeholders}})
is_active
created_at, updated_at
```

#### **email_logs** ⭐ (NY - Sporing)
Logger over sendte automatiske e-poster
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
template_id (FK -> email_templates)
module_source ('sales', 'events', 'membership', 'rental')
source_id (CHAR(36)) - ID fra modul
recipient_email, subject
sent_at, status ('sent', 'failed', 'pending')
error_message (TEXT)
created_at
```

#### **email_configs**
SMTP-konfigurasjon per selskap
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
smtp_host, smtp_port, smtp_user, smtp_password
from_email, from_name
created_at, updated_at
```

---

### 💼 ECONOMY MODULE

#### **economy_transactions**
Økonomiske transaksjoner
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
transaction_type ('income', 'expense')
amount, category
description, date
created_at, updated_at
```

#### **economy_invoices**
Fakturaer
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
invoice_number, customer_name
amount, due_date, status
created_at, updated_at
```

#### **economy_bank_accounts**
Bankkontoer
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
bank_name, account_number
balance
created_at, updated_at
```

#### **economy_payment_gateways**
Betalingsgateway-konfigurasjon
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
gateway_type ('stripe', 'vipps', 'klarna', 'paypal')
api_key, is_active
created_at, updated_at
```

#### **economy_categories**, **economy_daily_summary**, **economy_tax_rates**
Støttetabeller for økonomi

---

### 🛒 MARKETPLACE (Samlet katalog)

#### **output_products** ⭐ (READ-ONLY VIEW)
Samlet produktkatalog på tvers av moduler
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
product_type ('product', 'event', 'membership', 'rental', 'service')
product_id (CHAR(36)) - FK til kildetabell
name, price - NY: Denormalisert for ytelse
is_public (BOOLEAN) - Synlighet på nettside
created_at, updated_at
```

---

### 🌐 WEBSITE MODULE

#### **website_templates**
Forhåndsdefinerte nettsidedesign
```
template_id (VARCHAR PK)
name, description
html_content, css_content
preview_image_url
is_active
created_at
```

#### **webconfiguration**
Valgt nettside-design per selskap
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
template_id (FK -> website_templates)
granted_at, updated_at
```

#### **tenant_website_config**
Modulspesifikke nettside-innstillinger
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
module_id (FK -> system_modules)
settings (JSON)
created_at, updated_at
```

#### **tenant_settings**
Generelle innstillinger
```
id, company_id
setting_key, setting_value
created_at, updated_at
```

#### **tenant_storage**
Fillagring
```
id, company_id
file_path, file_size
created_at
```

---

### 🔧 SAAS ADMIN (Salesman-ansatte)

#### **saas_staff**
Salesman-ansatte (beskrivende)
```
id (CHAR(36) PK)
first_name, last_name, email, phone
job_title, department, employment_type
hire_date
address_street, address_city, address_state, address_zip, address_country
emergency_contact_name, emergency_contact_phone
is_active
created_at, updated_at
```

#### **saas_admins**
Salesman admin-kontoer
```
id (CHAR(36) PK)
saas_staff_id (FK -> saas_staff)
email, password_hash
role, permissions
is_active, last_login
mfa_enabled, mfa_secret
created_at, updated_at
```

#### **saas_subscription_plans**
Abonnementsplaner
```
id (CHAR(36) PK)
name, description
price_monthly, price_annual
max_users, max_storage_mb
features (TEXT)
is_active
created_at, updated_at
```

#### **tenant_subscriptions**
Selskap-abonnementer
```
id (CHAR(36) PK)
company_id (FK -> tenant_companies)
self_company_id (FK -> self_company)
plan_id (FK -> saas_subscription_plans)
status, billing_cycle
current_period_start, current_period_end
stripe_subscription_id, stripe_customer_id
canceled_at
created_at, updated_at
```

#### **self_company**
Salesman selskapsinfo (Salesman.no)
```
id (CHAR(36) PK)
company_name, legal_name, organization_no, tax_id
contact_email, contact_phone, website, logo_url
legal_address_street, legal_address_city, legal_address_state, legal_address_zip, legal_address_country
bank_name, bank_account_number, bank_swift_code
invoice_prefix, invoice_start_number, invoice_terms, invoice_due_days
default_tax_rate, tax_enabled
default_currency
is_active, setup_completed
created_at, updated_at
```

#### **saas_invoices**
Fakturaer til kunder (Salesman -> tenant)

---

### 📰 CORE SYSTEM NEWS

#### **core_sys_news**
Systemnyheter
```
id (CHAR(36) PK)
title, content
news_type, severity
is_published, publish_at, expire_at
target_audience
created_by (FK -> saas_admins)
created_at, updated_at
```

#### **core_sys_updates**
Systemoppdateringer
```
id (CHAR(36) PK)
version, title, description
update_type, release_date
is_published, documentation_url
created_by (FK -> saas_admins)
created_at, updated_at
```

---

## 🚨 IDENTIFISERTE PROBLEMER

### DUPLIKATER (VELG ÉN):
1. **Sales:**
   - ✅ `sales_products` (BEHOLDES - ny struktur med cost_price)
   - ❌ `sales_items` (SLETTES - gammel struktur)
   - ✅ `sales_transactions` (BEHOLDES - enkel struktur)
   - ❌ `sales_orders` + `sales_orders_items` (SLETTES - kompleks)

### REDUNDANTE (PostgreSQL dev):
- ❌ `x_events` (0 rader - SLETT)
- ❌ `x_reservations` (0 rader - SLETT)
- ⚠️ `x_membership_plan` (3 rader - migrer til `membership_plans`)
- ⚠️ `x_webmailconfiguration` (1 rad - migrer til `email_configs`)

### MANGLENDE (Må opprettes):
- ⭐ `email_templates` (for automatisering)
- ⭐ `email_logs` (for sporing)
- ⭐ `service_bookings` (for servicenæring)
- ⭐ `unified_ledger` (samlet økonomi)

---

## 📝 NESTE STEG

1. ✅ Opprette `email_templates` og `email_logs`
2. ✅ Opprette `service_bookings` for servicenæring
3. ✅ Opprette `unified_ledger` for samlet økonomi
4. ✅ Legge til `cost_price` i `events_eventdata`
5. ✅ Slette duplikat-tabeller (`sales_items`, `sales_orders`, `sales_orders_items`)
6. ✅ Migrere data fra `x_*` tabeller
7. ✅ Oppdatere PHP-kode til å bruke standard tabeller
8. ✅ Implementere email-automatisering

---

**Oppdatert:** Oktober 2025  
**Autor:** Database-analyse basert på faktisk kode og dokumentasjon
