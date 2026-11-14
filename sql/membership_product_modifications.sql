-- Referanseskjema. Brukeren har allerede opprettet tabellen; ikke kjør på nytt.
-- Ingen eksisterende priser eller medlemskap flyttes.
CREATE TABLE `salesmanno04`.`membership_product_modifications` (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    company_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    output_product_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    membership_plan_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    benefit_type ENUM('access','fixed_price','percent_discount','amount_discount','included')
        NOT NULL DEFAULT 'access',
    benefit_value DECIMAL(12,2) DEFAULT NULL,
    price_basis ENUM('unit','booking_total') NOT NULL DEFAULT 'unit',
    public_display TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_company_product_plan (company_id,output_product_id,membership_plan_id),
    KEY idx_company_plan_active (company_id,membership_plan_id,is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
