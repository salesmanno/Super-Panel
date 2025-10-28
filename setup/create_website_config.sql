-- Opprette tenant_website_config tabellen hvis den ikke finnes
CREATE TABLE IF NOT EXISTS tenant_website_config (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    is_published BOOLEAN DEFAULT FALSE,
    is_enabled BOOLEAN DEFAULT TRUE,
    display_name VARCHAR(255),
    seo_title VARCHAR(255),
    seo_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_config (company_id)
);

-- Sett inn eksempeldata for nettstedinnstillinger
-- Dette vil opprette innstillinger for eksisterende bedrifter i systemet
INSERT INTO tenant_website_config (company_id, is_published, is_enabled, display_name, seo_title, seo_description, updated_at)
SELECT 
    id as company_id,
    TRUE as is_published,
    TRUE as is_enabled,
    name as display_name,
    CONCAT(name, ' - Offisiell Nettside') as seo_title,
    CONCAT('Velkommen til ', name, '. Vi tilbyr profesjonelle tjenester og produkter.') as seo_description,
    NOW() as updated_at
FROM tenant_companies
WHERE NOT EXISTS (
    SELECT 1 FROM tenant_website_config WHERE tenant_website_config.company_id = tenant_companies.id
)
LIMIT 10;
