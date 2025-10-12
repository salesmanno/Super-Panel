-- Database: salesmanno04
-- Server: salesmanno04.mysql.domeneshop.no
-- This is the complete database schema for the Salesman SaaS platform

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS marketplace_favorites;
DROP TABLE IF EXISTS marketplace_inquiries;
DROP TABLE IF EXISTS marketplace_categories;
DROP TABLE IF EXISTS market_marketplace;
DROP TABLE IF EXISTS reservation_service_bookings;
DROP TABLE IF EXISTS reservation_services;
DROP TABLE IF EXISTS reservations_confirmed;
DROP TABLE IF EXISTS reservations_reservations;
DROP TABLE IF EXISTS reservations_resources;
DROP TABLE IF EXISTS activity_summaries;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS economy_invoices;
DROP TABLE IF EXISTS sales_orders_items;
DROP TABLE IF EXISTS sales_orders;
DROP TABLE IF EXISTS sales_products_stock;
DROP TABLE IF EXISTS sales_products;
DROP TABLE IF EXISTS sales_categories;
DROP TABLE IF EXISTS membership_members;
DROP TABLE IF EXISTS membership_plans;
DROP TABLE IF EXISTS tenant_customers;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS email_templates;
DROP TABLE IF EXISTS email_configs;
DROP TABLE IF EXISTS email_drafts;
DROP TABLE IF EXISTS email_attachments;
DROP TABLE IF EXISTS email_messages;
DROP TABLE IF EXISTS email_folders;
DROP TABLE IF EXISTS email_accounts;
DROP TABLE IF EXISTS economy_tax_rates;
DROP TABLE IF EXISTS economy_daily_summary;
DROP TABLE IF EXISTS economy_categories;
DROP TABLE IF EXISTS economy_transactions;
DROP TABLE IF EXISTS economy_payment_gateways;
DROP TABLE IF EXISTS economy_bank_accounts;
DROP TABLE IF EXISTS saas_invoices;
DROP TABLE IF EXISTS tenant_staff_modules;
DROP TABLE IF EXISTS tenant_staff;
DROP TABLE IF EXISTS tenant_admin_modules;
DROP TABLE IF EXISTS tenant_admins;
DROP TABLE IF EXISTS tenant_storage;
DROP TABLE IF EXISTS tenant_website_config;
DROP TABLE IF EXISTS tenant_settings;
DROP TABLE IF EXISTS tenant_company_modules;
DROP TABLE IF EXISTS tenant_subscriptions;
DROP TABLE IF EXISTS tenant_companies;
DROP TABLE IF EXISTS saas_subscription_plans;
DROP TABLE IF EXISTS system_modules;
DROP TABLE IF EXISTS self_company;
DROP TABLE IF EXISTS core_sys_updates;
DROP TABLE IF EXISTS core_sys_news;
DROP TABLE IF EXISTS saas_admins;
DROP TABLE IF EXISTS saas_staff;

SET FOREIGN_KEY_CHECKS = 1;


-- ==================== SAAS MANAGEMENT MODULE ====================
-- SaaS Platform Staff (Salesman company employees - descriptive information)
CREATE TABLE saas_staff (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    job_title VARCHAR(100),
    department VARCHAR(100),
    employment_type VARCHAR(50) DEFAULT 'full_time',
    hire_date DATE,
    address_street VARCHAR(255),
    address_city VARCHAR(255),
    address_state VARCHAR(255),
    address_zip VARCHAR(50),
    address_country VARCHAR(100),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- System Administrators (Login credentials tied to staff members)
CREATE TABLE saas_admins (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    saas_staff_id CHAR(36) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    permissions TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (saas_staff_id) REFERENCES saas_staff(id) ON DELETE CASCADE,
    UNIQUE(saas_staff_id)
);

-- ==================== SYSTEM NEWS & UPDATES ====================
CREATE TABLE core_sys_news (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    news_type VARCHAR(50) DEFAULT 'info',
    severity VARCHAR(20) DEFAULT 'low',
    is_published BOOLEAN DEFAULT FALSE,
    publish_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expire_at DATETIME NULL,
    target_audience VARCHAR(50) DEFAULT 'all',
    created_by CHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES saas_admins(id)
);

CREATE TABLE core_sys_updates (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    version VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    update_type VARCHAR(50) DEFAULT 'feature',
    release_date DATE NOT NULL,
    is_published BOOLEAN DEFAULT FALSE,
    documentation_url VARCHAR(500),
    created_by CHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES saas_admins(id),
    UNIQUE(version)
);

-- ==================== SALESMAN SELF COMPANY DETAILS ====================
CREATE TABLE self_company (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    organization_no VARCHAR(100),
    tax_id VARCHAR(100),
    company_number VARCHAR(100),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    website VARCHAR(255),
    logo_url VARCHAR(500),
    
    -- Legal Address
    legal_address_street VARCHAR(255),
    legal_address_city VARCHAR(255),
    legal_address_state VARCHAR(255),
    legal_address_zip VARCHAR(50),
    legal_address_country VARCHAR(100) DEFAULT 'Norway',
    
    -- Banking Information
    bank_name VARCHAR(255),
    bank_account_number VARCHAR(100),
    bank_swift_code VARCHAR(100),
    
    -- Invoice Settings
    invoice_prefix VARCHAR(50) DEFAULT 'INV',
    invoice_start_number INT DEFAULT 1000,
    invoice_terms TEXT,
    invoice_due_days INT DEFAULT 30,
    
    -- Tax Settings for Salesman (your company)
    default_tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_enabled BOOLEAN DEFAULT FALSE,
    
    -- Currency
    default_currency VARCHAR(3) DEFAULT 'NOK',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    setup_completed BOOLEAN DEFAULT FALSE,
    
    created_by CHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES saas_admins(id)
);

-- ==================== MODULE MANAGEMENT ====================
CREATE TABLE system_modules (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    module_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'business',
    icon VARCHAR(100),
    version VARCHAR(20) DEFAULT '1.0.0',
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==================== SAAS SUBSCRIPTION & BILLING ====================
CREATE TABLE saas_subscription_plans (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2),
    price_annual DECIMAL(10,2),
    max_users INTEGER,
    max_storage_mb INTEGER,
    features TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by CHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES saas_admins(id)
);

-- ==================== TENANT MODULE ====================
CREATE TABLE tenant_companies (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    entity_type VARCHAR(20) DEFAULT 'company',
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(255) UNIQUE,
    industry VARCHAR(100),
    company_size VARCHAR(50),
    
    -- Contact Information
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    website VARCHAR(255),
    logo_url VARCHAR(500),
    
    -- Address Information
    address_street VARCHAR(255),
    address_city VARCHAR(255),
    address_state VARCHAR(255),
    address_zip VARCHAR(50),
    address_country VARCHAR(100) DEFAULT 'Norway',
    
    -- Tax Information (specifically for Norway)
    tax_id VARCHAR(100),
    tax_registered BOOLEAN DEFAULT FALSE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    trial_ends_at DATETIME NULL,
    onboarded_by CHAR(36),
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (onboarded_by) REFERENCES saas_admins(id)
);

CREATE TABLE tenant_subscriptions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    self_company_id CHAR(36) NOT NULL,
    plan_id CHAR(36),
    status VARCHAR(20) DEFAULT 'active',
    billing_cycle VARCHAR(20) DEFAULT 'monthly',
    current_period_start DATETIME,
    current_period_end DATETIME,
    stripe_subscription_id VARCHAR(255),
    stripe_customer_id VARCHAR(255),
    canceled_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (self_company_id) REFERENCES self_company(id),
    FOREIGN KEY (plan_id) REFERENCES saas_subscription_plans(id)
);

-- ==================== TENANT USER MANAGEMENT ====================
-- Tenant Staff (Employee information for tenant companies)
CREATE TABLE tenant_staff (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    employee_code VARCHAR(100),
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    date_of_birth DATE,
    job_title VARCHAR(100),
    department VARCHAR(100),
    employment_type VARCHAR(50),
    address_street VARCHAR(255),
    address_city VARCHAR(255),
    address_state VARCHAR(255),
    address_zip VARCHAR(50),
    address_country VARCHAR(100),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE
);

-- THIS IS THE KEY TABLE FOR COMPANY ADMIN LOGIN
CREATE TABLE tenant_admins (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    tenant_staff_id CHAR(36) NULL,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_staff_id) REFERENCES tenant_staff(id) ON DELETE SET NULL,
    UNIQUE(company_id, username),
    UNIQUE(company_id, email)
);

-- SaaS admin controls which modules are available to each tenant
CREATE TABLE tenant_company_modules (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    module_id CHAR(36) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    config TEXT,
    activated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deactivated_at DATETIME NULL,
    assigned_by CHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES saas_admins(id),
    UNIQUE(company_id, module_id)
);

-- Tenant admin access to individual modules
CREATE TABLE tenant_admin_modules (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    tenant_admin_id CHAR(36) NOT NULL,
    module_id CHAR(36) NOT NULL,
    granted_by CHAR(36),
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_admin_id) REFERENCES tenant_admins(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES tenant_admins(id),
    UNIQUE(tenant_admin_id, module_id)
);

-- Additional tables follow...
-- (See attached file for complete structure with all modules: sales, rental, members, email, reservations, events, marketplace, economy, etc.)
