-- ==================================================
-- EMAIL SYSTEM MIGRATION FOR MYSQL PRODUCTION
-- ==================================================
-- Database: salesmanno04 (MySQL)
-- Server: salesmanno04.mysql.domeneshop.no
-- Dato: November 2025
--
-- Dette scriptet oppretter:
-- 1. email_configs (SMTP/IMAP konfigurasjon)
-- 2. email_accounts (individuelle e-postkontoer)
-- 3. email_folders (innboks, sendt, etc.)
-- 4. email_messages (e-postmeldinger)
-- 5. email_drafts (utkast)
-- 6. email_attachments (vedlegg)
-- 7. automailer_settings (automatisk utsending)
-- 8. Migrerer data fra x_webmailconfiguration
-- ==================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ==================================================
-- 1. EMAIL_CONFIGS (SMTP/IMAP konfigurasjon per selskap)
-- ==================================================
CREATE TABLE IF NOT EXISTS email_configs (
    id CHAR(36) NOT NULL PRIMARY KEY,
    company_id INT NOT NULL,
    
    -- SMTP Settings
    smtp_host VARCHAR(255),
    smtp_port INT DEFAULT 587,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    smtp_encryption VARCHAR(20),
    smtp_ssl BOOLEAN DEFAULT FALSE,
    
    -- IMAP Settings
    imap_host VARCHAR(255),
    imap_port INT DEFAULT 993,
    imap_username VARCHAR(255),
    imap_password VARCHAR(255),
    imap_ssl BOOLEAN DEFAULT TRUE,
    
    -- From Settings
    from_email VARCHAR(255),
    from_name VARCHAR(255),
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    INDEX idx_email_configs_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 2. EMAIL_ACCOUNTS (individuelle e-postkontoer)
-- ==================================================
CREATE TABLE IF NOT EXISTS email_accounts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    company_id INT NOT NULL,
    admin_id CHAR(36),
    email_address VARCHAR(255) NOT NULL,
    
    -- IMAP Settings
    imap_host VARCHAR(255),
    imap_port INT DEFAULT 993,
    imap_username VARCHAR(255),
    imap_password VARCHAR(255),
    
    -- SMTP Settings
    smtp_host VARCHAR(255),
    smtp_port INT DEFAULT 587,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    INDEX idx_email_accounts_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 3. EMAIL_FOLDERS
-- ==================================================
CREATE TABLE IF NOT EXISTS email_folders (
    id CHAR(36) NOT NULL PRIMARY KEY,
    account_id CHAR(36),
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES email_accounts(id) ON DELETE CASCADE,
    INDEX idx_email_folders_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 4. EMAIL_MESSAGES
-- ==================================================
CREATE TABLE IF NOT EXISTS email_messages (
    id CHAR(36) NOT NULL PRIMARY KEY,
    account_id CHAR(36),
    folder_id CHAR(36),
    message_id VARCHAR(500),
    subject VARCHAR(500),
    from_email VARCHAR(255),
    to_email TEXT,
    cc TEXT,
    bcc TEXT,
    body_html TEXT,
    body_text TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    is_starred BOOLEAN DEFAULT FALSE,
    received_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES email_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES email_folders(id) ON DELETE SET NULL,
    INDEX idx_email_messages_account (account_id),
    INDEX idx_email_messages_folder (folder_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 5. EMAIL_DRAFTS
-- ==================================================
CREATE TABLE IF NOT EXISTS email_drafts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    account_id CHAR(36),
    to_email VARCHAR(255),
    subject VARCHAR(500),
    body TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES email_accounts(id) ON DELETE CASCADE,
    INDEX idx_email_drafts_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 6. EMAIL_ATTACHMENTS
-- ==================================================
CREATE TABLE IF NOT EXISTS email_attachments (
    id CHAR(36) NOT NULL PRIMARY KEY,
    message_id CHAR(36),
    filename VARCHAR(500),
    content_type VARCHAR(100),
    size_bytes INT,
    file_path VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES email_messages(id) ON DELETE CASCADE,
    INDEX idx_email_attachments_message (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 7. AUTOMAILER_SETTINGS
-- ==================================================
CREATE TABLE IF NOT EXISTS automailer_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    module_name VARCHAR(50) NOT NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    setting_key VARCHAR(100) NOT NULL,
    setting_value BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_automailer_setting (company_id, module_name, setting_key),
    INDEX idx_automailer_company (company_id),
    INDEX idx_automailer_module (module_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==================================================
-- 8. MIGRER DATA FRA X_WEBMAILCONFIGURATION
-- ==================================================
INSERT INTO email_configs (
    id, company_id, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_ssl,
    imap_host, imap_port, imap_username, imap_password, imap_ssl, from_email, created_at, updated_at
)
SELECT 
    UUID(),
    company_id,
    smtp_host,
    smtp_port,
    smtp_username,
    smtp_password,
    smtp_encryption,
    smtp_ssl,
    imap_host,
    imap_port,
    imap_username,
    imap_password,
    imap_ssl,
    smtp_username,
    created_at,
    updated_at
FROM x_webmailconfiguration
ON DUPLICATE KEY UPDATE id=id;

-- ==================================================
-- 9. INIT STANDARD EMAIL TEMPLATES (optional)
-- ==================================================
-- Disse malene kan opprettes via API /api/init_email_templates.php

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================
-- VERIFISERING
-- ==================================================
SELECT 'email_configs' as tabell, COUNT(*) as antall FROM email_configs
UNION ALL SELECT 'email_accounts', COUNT(*) FROM email_accounts
UNION ALL SELECT 'email_templates', COUNT(*) FROM email_templates
UNION ALL SELECT 'email_logs', COUNT(*) FROM email_logs
UNION ALL SELECT 'automailer_settings', COUNT(*) FROM automailer_settings;

SELECT '✅ Email System Migration Fullført' as status;
