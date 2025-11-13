-- ========================================
-- MEDLEMSIMPORT FOR BERGSØY DANSEKLUBB
-- ========================================
-- Generert: 2025-11-13 22:46:35
-- Antall medlemmer: 135
-- 
-- INSTRUKSJONER:
-- 1. Logg inn på phpMyAdmin (Domeneshop)
-- 2. Velg salesmanno01 database
-- 3. Gå til SQL-tab
-- 4. Lim inn HELE dette skriptet
-- 5. Klikk 'Utfør'
-- ========================================

-- ========================================
-- VALIDER FORUTSETNINGER
-- ========================================

-- Finn company_id og plan_id automatisk
SET @company_id = (SELECT id FROM tenant_companies WHERE subdomain = 'bergsoy' LIMIT 1);
SET @plan_id = (SELECT id FROM membership_plans WHERE company_id = @company_id AND name LIKE '%Bergsøy%' LIMIT 1);

-- KRITISK: Stopp importen hvis company eller plan ikke finnes
SELECT 
    CASE 
        WHEN @company_id IS NULL THEN 'FEIL: Bergsøy company ikke funnet! Stopp importen!'
        WHEN @plan_id IS NULL THEN 'FEIL: Bergsøy Medlem plan ikke funnet! Opprett planen først!'
        ELSE CONCAT('OK: Company ID = ', @company_id, ', Plan ID = ', @plan_id)
    END AS validation_status;

-- Hvis du ser FEIL ovenfor, IKKE kjør resten av skriptet!
-- Du må opprette company eller medlemskapsplan først.

-- ========================================
-- START IMPORT (135 medlemmer)
-- ========================================

-- #1: Egil Rasmineson Frantsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Egil', 'Rasmineson Frantsen', 'e-fran@online.no', '90689856', NULL, 'Gamlemyklebustveg 9', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'e-fran@online.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #2: Britt Inger Gusdal Frantsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Britt', 'Inger Gusdal Frantsen', 'brittingerbig@gmail.com', '93226717', NULL, 'GamleMyklebustveg 9', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'brittingerbig@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #3: Atle Kvalsvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Atle', 'Kvalsvik', 'atlekarl@tussa.com', '95918925', '1956-04-08', 'Mulevikvegen,42', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'atlekarl@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #4: Bente Kvalsvik Notøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Bente', 'Kvalsvik Notøy', 'bente02@live.no', '48158704', NULL, 'Breidanesvegen 8,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'bente02@live.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #5: Marion Nilsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marion', 'Nilsen', 'marion.nilsen@outlook.com', '97988677', '1955-07-17', 'Mulevikvegen,42', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'marion.nilsen@outlook.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #6: Kristin Leine
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kristin', 'Leine', 'kristinleine@hotmail.no', '99649880', NULL, 'Jordmor Helgas veg  14,', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'kristinleine@hotmail.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #7: Marita Higraf Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marita', 'Higraf Remøy', 'maritahr2@gmail.com', '90981991', '1966-04-14', 'Nedre plassgjerde', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'maritahr2@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-21', '2026-07-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-21';

-- #8: Oddveig Arnesen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddveig', 'Arnesen', NULL, '45235239', NULL, 'Nordgardsvegen 4', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #9: Asbjørn Bendal
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Asbjørn', 'Bendal', 'abendal@online.no', '95766662', NULL, 'Kviehaugen 8 B', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'abendal@online.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #10: Jan Bugge
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Jan', 'Bugge', NULL, '95125587', NULL, 'Nedre Hjelmeseth 20', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #11: Jan Bø
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Jan', 'Bø', NULL, '48051150', NULL, 'Sandevegen 6', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #12: Reidun Baade
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Reidun', 'Baade', 'reidun23@hotmail.no', '97077208', '1958-03-23', 'Ospenesvegen 7', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'reidun23@hotmail.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #13: Annlaug Eikrem
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Annlaug', 'Eikrem', 'annlaugeikrem@hotmail.com', '46430527', NULL, 'Voldsundvegen 48,', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'annlaugeikrem@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #14: Myrthel Emblem
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Myrthel', 'Emblem', 'myrtelemblem@gmail.com', '91849589', NULL, 'Postboks 25', 'Fosnavåg', '6099', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'myrtelemblem@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #15: Arild Flusund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Arild', 'Flusund', 'arild.flusund@gmail.com', '91637514', '1958-10-12', 'Sandevegen 8', 'Bølandet', '9095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'arild.flusund@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #16: Arnhild Flusund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Arnhild', 'Flusund', NULL, '41235051', '1962-04-29', 'Sandevegen 8', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #17: Idar Frantzen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Idar', 'Frantzen', 'frantsenidar@gmail.com', '40613610', NULL, 'Ospenesveien 21', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'frantsenidar@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #18: Tone Foss Frantzen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Tone', 'Foss Frantzen', 'tone.frantzen@hotmail.com', '95338070', NULL, 'Holmsildgata 16', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'tone.frantzen@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #19: Mathias Haugen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Mathias', 'Haugen', 'stottemur@hotmail.com', '45500147', NULL, 'Myklebustvegen 115', 'Myklebust', '6488', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'stottemur@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #20: Robert Bjarne Helde
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Robert', 'Bjarne Helde', 'robert.heldeb@gmail.com', '97624748', NULL, 'Demingane 27', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'robert.heldeb@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #21: Fritjof Husøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Fritjof', 'Husøy', NULL, '95223457', NULL, 'Torvik', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #22: Erik Iversen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Erik', 'Iversen', 'erik.iversen@kcadeutag.com', '90838258', NULL, 'Kopperstadvegen 140', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'erik.iversen@kcadeutag.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #23: Margunn Kalland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Margunn', 'Kalland', NULL, '91329484', NULL, 'Massavegen 14,', 'Runde', '6096', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #24: Marit Knutsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marit', 'Knutsen', NULL, '91894371', NULL, 'Borgarhaugvegen 4', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #25: Perly Christin Vikebak Kopperstad
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Perly', 'Christin Vikebak Kopperstad', 'perly.kopperstad@outlook.com', '90117868', NULL, '6099 Fosnavåg', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'perly.kopperstad@outlook.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #26: Idar Ove Kragset
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Idar', 'Ove Kragset', 'idarovekr@hotmail.com', '90112149', NULL, 'Breidanesvegen 8,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'idarovekr@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #27: Myrthel Kvalsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Myrthel', 'Kvalsund', NULL, '90588968', NULL, 'Teigelia 38', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #28: Per Ove Kvalsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Per', 'Ove Kvalsund', NULL, '90688968', NULL, 'Teigelia 38', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #29: Geir Vangen Kvalsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Geir', 'Vangen Kvalsund', 'geirkv@gmail.com', '48248499', NULL, 'Øvre Plassegjerde 5', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'geirkv@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #30: aksel.kvalsvik@tussa.com
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'aksel.kvalsvik@tussa.com', '', 'aksel.kvalsvik@tussa.com', '97553520', NULL, 'Øvre Plassgjerdet 61', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'aksel.kvalsvik@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #31: Evy Helen Ryssevik Kvalsvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Evy', 'Helen Ryssevik Kvalsvik', 'evy.kvals@gmail.com', '95995513', NULL, 'Bendalsvegen 8C', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'evy.kvals@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #32: Åse Kvalsvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Åse', 'Kvalsvik', NULL, '92451835', NULL, 'Øvre Plassegjerde 5', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #33: Mariann Leinebø
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Mariann', 'Leinebø', NULL, '48294481', NULL, 'Støylavegne 13', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #34: Gunnar Leinebø
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Gunnar', 'Leinebø', NULL, '91561632', NULL, 'Støylavegen 13', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #35: Marit Jordan Lyster
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marit', 'Jordan Lyster', NULL, '95156176', NULL, 'Leikongvegen 422', 'Gurskøy', '6080', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #36: Mariann Løvold
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Mariann', 'Løvold', 'mariann.lovold@hotmail.com', '98482604', NULL, 'Verket 14', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'mariann.lovold@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #37: Tore Løvøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Tore', 'Løvøy', 'tore.lovoy@gmail.com', '41307657', NULL, 'Sandvikvegen 71', 'Tjørvåg', '6070', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'tore.lovoy@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #38: Irene Petra Madsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Irene', 'Petra Madsen', NULL, '91737567', NULL, 'Geila 5,', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #39: Johan Moltu
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Johan', 'Moltu', 'johan.moltu@gmail.com', '91568576', NULL, 'Røyravegen 10', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'johan.moltu@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #40: Sølvi Moltu
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Sølvi', 'Moltu', 'solvi.moltu@tussa.com', '93641550', NULL, 'Leinøy', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'solvi.moltu@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #41: Anne-Jorunn Moltumyr
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Anne-Jorunn', 'Moltumyr', NULL, '90940208', NULL, 'Remøy', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #42: Henning Moltumyr
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Henning', 'Moltumyr', 'henningmoltumyr@tussa.com', '94143636', NULL, 'Remøy', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'henningmoltumyr@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #43: Oddvar Myklebust
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddvar', 'Myklebust', NULL, '93600832', NULL, 'Eggesbøvegen 6 A', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #44: Jan Arild Myklebusthaug
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Jan', 'Arild Myklebusthaug', 'jamyklebusthaug@gmail.com', '47830680', NULL, 'Brunholmgata 22', 'Ålesund', '6004', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'jamyklebusthaug@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #45: Geir Per Nerland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Geir', 'Per Nerland', 'geir.per.nerland@gmail.com', '99308210', NULL, 'Landsvegen 12', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'geir.per.nerland@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #46: Jeanette Nerland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Jeanette', 'Nerland', 'jeanette@hotmail.com', '97191203', NULL, 'Landsvegen 12', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'jeanette@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #47: Oddbjørn Nerland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddbjørn', 'Nerland', 'oddbjorn@nerland.io', '47238763', NULL, 'Svingen 8', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'oddbjorn@nerland.io' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #48: Alf Nerland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Alf', 'Nerland', 'alfnerland@tussa.com', '41660225', NULL, 'Holmsildgata 16', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'alfnerland@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #49: Marit Nerlid
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marit', 'Nerlid', NULL, '97643583', NULL, 'Kalvegjerde 2', 'Moltustranda', '6076', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #50: Anita Nilsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Anita', 'Nilsen', 'anita-nilsen@live.no', '97646387', NULL, '6092 Fosnavåg', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'anita-nilsen@live.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #51: Gerd Jorunn Nilsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Gerd', 'Jorunn Nilsen', NULL, '41302631', NULL, 'Kyrkjevegen 93', 'Sykkulven', '6230', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #52: Gunn Nilsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Gunn', 'Nilsen', 'gunn-n@hotmail.no', '93412336', NULL, 'Bendalsvegen 15B', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'gunn-n@hotmail.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #53: Bård Nordnes
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Bård', 'Nordnes', NULL, '41734804', NULL, 'Voldsundvegen 32', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #54: Ingunn Nordnes
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Ingunn', 'Nordnes', 'ingnordnes@hotmail.com', '95752952', NULL, 'Voldsundvegen 32', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'ingnordnes@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #55: Alis Notøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Alis', 'Notøy', NULL, '90857103', NULL, 'Stornesvegen 8', 'Tjørvåg', '6070', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #56: Mai-Brit Nygarden
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Mai-Brit', 'Nygarden', NULL, '95000339', NULL, 'K.L.Vegen 9,', 'Hareid', '6060', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #57: Sigmund Nymark
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Sigmund', 'Nymark', 'sigmund.nymark@gmail.com', '41932875', NULL, 'Leione 3A', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'sigmund.nymark@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #58: Eldar Hoan Ose
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Eldar', 'Hoan Ose', NULL, '41506612', NULL, 'Hoslekervegen 120', 'Ulsteinvik', '6065', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #59: Endre Johan Ose
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Endre', 'Johan Ose', 'endrejohan@hotmail.com', '91101955', NULL, 'Holkestadvegen 6', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'endrejohan@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #60: Eva Lena Ose
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Eva', 'Lena Ose', 'trolle-40@hotmail.com', '48172229', NULL, 'Hoslekervegen 120', 'Ulsteinvik', '6065', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'trolle-40@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #61: Bjørn Prytz
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Bjørn', 'Prytz', 'bjornprytz@gmail.com', '47393108', NULL, 'Buråsa 1', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'bjornprytz@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #62: Kristin Rabben
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kristin', 'Rabben', 'kri-rabb@online.no', '90069947', NULL, 'Brunholmgata 22,', 'Ålesund', '6004', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'kri-rabb@online.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #63: Kirsten Reite
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kirsten', 'Reite', 'k.reite@hotmail.com', '95791864', NULL, 'Kvianesvegen 49,', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'k.reite@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #64: Ronald Reite
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Ronald', 'Reite', 'rona-re@tussa.com', '41438157', NULL, 'Kvianesvegen 49,', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'rona-re@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #65: Aud Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Aud', 'Remøy', 'audremoy@gmail.com', '98684879', '1961-06-04', 'Myrane 20', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'audremoy@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #66: Kurt Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kurt', 'Remøy', 'kurt.remoy@tussa.com', '95839656', '1957-11-15', 'Myrane 20', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'kurt.remoy@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #67: Paul Gustav Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Paul', 'Gustav Remøy', 'gustav.jarle@example.com', '87654321', NULL, 'Remøya 22', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'gustav.jarle@example.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #68: Sveinung Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Sveinung', 'Remøy', NULL, '48195217', NULL, 'Nedre Plassgjerde 7', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #69: Ellinor Rinde
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Ellinor', 'Rinde', 'hallgeir66@hotmail.com', '41257965', NULL, 'Ædane 9', 'Gurskøy', '6080', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'hallgeir66@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #70: Liv Runde
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Liv', 'Runde', NULL, '92260396', NULL, 'Rundevegen 10', 'Runde', '6096', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #71: Frode Ryssevik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Frode', 'Ryssevik', NULL, '97521996', NULL, 'Bendalsvegen 6C', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #72: Palmar Rønnestad
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Palmar', 'Rønnestad', 'palmar.ronnestad@gmail.com', '99011940', '1954-01-18', 'Nedre Røyra 1', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'palmar.ronnestad@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #73: Sølvi Røren
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Sølvi', 'Røren', NULL, '97966560', NULL, 'Vågsgata 45,', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #74: Hans Sande
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Hans', 'Sande', 'hanssande@tussa.com', '92459392', NULL, 'Sandevegen  47', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'hanssande@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #75: Petra Sandvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Petra', 'Sandvik', NULL, '45035591', NULL, 'Tjørvågvegen 2', 'Tjørvåg', '6070', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #76: Roar Sandvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Roar', 'Sandvik', 'solvi.moltu@tussa.com', '90116870', NULL, 'Nautøya 90', 'Tjørvåg', '6070', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'solvi.moltu@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #77: Oddvar Skogen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddvar', 'Skogen', NULL, '47857099', NULL, 'Vågsgata 45,', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #78: Roar Skoglund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Roar', 'Skoglund', 'rokriskog@gmail.com', '91369350', NULL, 'Svingen 8', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'rokriskog@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #79: Arild Slettestøl
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Arild', 'Slettestøl', 'arild_j@live.com', '91688928', NULL, 'Knotten 130', 'Gursken', '6082', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'arild_j@live.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #80: Aslak Solbjørg
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Aslak', 'Solbjørg', 'asolborg@aolnorge.no', '46891090', NULL, 'Nautøya 125', 'Tjørvåg', '6070', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'asolborg@aolnorge.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #81: Oddveig Stanghelle
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddveig', 'Stanghelle', 'oddveig_sta@hotmail.com', '99649879', NULL, 'Leinevegen 272', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'oddveig_sta@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #82: Liv Bessie Stave
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Liv', 'Bessie Stave', 'livstave@online.no', '95768617', NULL, 'Osebergvegen 5', 'Ulsteinvik', '6065', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'livstave@online.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #83: Per Egil Sørheller
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Per', 'Egil Sørheller', 'peregilsorheller@gmail.com', '45906393', NULL, 'Brauta 3', 'Eidsnes', '6037', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'peregilsorheller@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #84: Kirsten Teige
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kirsten', 'Teige', NULL, '40086106', NULL, 'Teigeveien 47,', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #85: Oluf Teige
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oluf', 'Teige', 'oluf.teige@tussa.com', '97525423', '2025-09-15', 'Teigeveien 47,', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'oluf.teige@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #86: Anne-Johanne Tellefsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Anne-Johanne', 'Tellefsen', 'anne.johanne.tellefsen@gmail.com', '90846683', NULL, 'Teigelia 6', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'anne.johanne.tellefsen@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #87: Solfrid Thorset
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Solfrid', 'Thorset', NULL, '95766727', NULL, 'Leikongbakken 4,', 'Gurskøy', '6080', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #88: Kjellmar Vike
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kjellmar', 'Vike', 'kjellmar50@gmail.com', '90863096', '2025-08-25', 'Grønhaugvegen 34,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'kjellmar50@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #89: Laila Vike
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Laila', 'Vike', 'vennen53@hotmail.com', '99162331', '1963-06-15', 'Grønhaugvegen 34,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'vennen53@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #90: Idar Voldsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Idar', 'Voldsund', 'idarvoldsund@tussa.com', '99308221', NULL, 'Arhaugvegen 14,', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'idarvoldsund@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #91: Anne-Marie Westbø
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Anne-Marie', 'Westbø', 'annemawest4268@gmail.com', '91804691', NULL, 'Torvikvegen 43', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'annemawest4268@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #92: Hallgeir Åsebø
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Hallgeir', 'Åsebø', 'hallgeir66@hotmail.com', '95753664', NULL, 'Ædane 9', 'Gurskøy', '6080', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'hallgeir66@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-23', '2026-07-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-23';

-- #93: Unni Nerland Nerland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Unni', 'Nerland Nerland', 'unni@nerland.io', '45474106', NULL, 'Svingen 8', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'unni@nerland.io' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-07-28', '2026-07-28', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-07-28';

-- #94: Laila Nedreberg Remøy Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Laila', 'Nedreberg Remøy Remøy', 'laila@remoy.org', '40873948', '1962-08-21', 'Nedre plassgjerde 7', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'laila@remoy.org' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-08-22', '2026-08-22', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-08-22';

-- #95: Halvard Leine
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Halvard', 'Leine', 'halvard.leine@tussa.com', '95247887', '1956-11-01', 'Leine 41', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'halvard.leine@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-08-22', '2026-08-22', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-08-22';

-- #96: Svein Arne Leine
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Svein', 'Arne Leine', 'tull@one.no', '97069105', '1953-07-25', 'bergslia 3', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'tull@one.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-08-22', '2026-08-22', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-08-22';

-- #97: Kristine Bratli Leine
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kristine', 'Bratli Leine', 'tull@one.no', '97500786', '1960-09-30', 'Leine 41', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'tull@one.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-08-22', '2026-08-22', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-08-22';

-- #98: Hildegunn Øye
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Hildegunn', 'Øye', 'hildegunnoddrun@gmail.com', '48030489', '1955-03-14', 'Øvre', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'hildegunnoddrun@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-08-22', '2026-08-22', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-08-22';

-- #99: Kari Iren Sandvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Kari', 'Iren Sandvik', 'kariharisan@hotmail.com', '47863533', '1953-04-15', 'Eggesbøgrenda 6 A', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'kariharisan@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-02', '2026-09-02', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-02';

-- #100: Alf Inge Leine
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Alf', 'Inge Leine', 'alf.inge.leine@gmail.com', '90636950', NULL, 'Nedre Plassegjerdet 15', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'alf.inge.leine@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-07', '2026-09-07', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-07';

-- #101: Gry Myrseth Myrseth
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Gry', 'Myrseth Myrseth', 'gry.myrseth@gmail.com', '95793923', '1961-12-11', 'Hulldalsbana 4 A', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'gry.myrseth@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-10', '2026-09-10', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-10';

-- #102: Lise Hauge Kvalsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Lise', 'Hauge Kvalsund', 'lise.kvalsund@hotmail.com', '95058708', '1959-08-29', 'Bakkevn 9', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'lise.kvalsund@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-10', '2026-09-10', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-10';

-- #103: Oddgeir Madsen Madsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddgeir', 'Madsen Madsen', 'oddgeir.madsen@tussa.com', '91681611', '1955-05-21', 'Teigelia 4', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'oddgeir.madsen@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-11', '2026-09-11', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-11';

-- #104: Karen Myrseth Moltumyr
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Karen', 'Myrseth Moltumyr', 'ksmoltumyr@gmail.com', '95966318', NULL, 'Markveien 9', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'ksmoltumyr@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-17', '2026-09-17', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-17';

-- #105: Reidun Frantzen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Reidun', 'Frantzen', 'reidun.frantzen@hotmail.com', '47044251', '1965-11-18', 'Toftestøvegen 24', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'reidun.frantzen@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-17', '2026-09-17', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-17';

-- #106: Vilfred Pettersen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Vilfred', 'Pettersen', NULL, NULL, NULL, 'INNHENT', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-17', '2026-09-17', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-17';

-- #107: Karl -Sverre Moltumyr
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Karl', '-Sverre Moltumyr', 'ksmolrumyr@gmail.com', '48281188', NULL, 'Merkveien 9', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'ksmolrumyr@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-19', '2026-09-19', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-19';

-- #108: Paul Sandanger
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Paul', 'Sandanger', 'paul.sandanger@tussa.com', '908 95 585', '1950-05-04', 'Gjerdevegen 10', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'paul.sandanger@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-21', '2026-09-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-21';

-- #109: Jorunn Berge
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Jorunn', 'Berge', 'lola58@live.no', '91894452', '1958-04-14', 'Bergsvegen 39', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'lola58@live.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-21', '2026-09-21', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-21';

-- #110: Marina Hoff Myking
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marina', 'Hoff Myking', 'marinamyking@hotmail.com', '91586793', NULL, 'Støylavegen 11A,', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'marinamyking@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-23', '2026-09-23', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-23';

-- #111: Susann Nerland
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Susann', 'Nerland', 'susannno@yahoo.no', '99393147', '2025-04-15', 'Landsvegen 18,', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'susannno@yahoo.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-24', '2026-09-24', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-24';

-- #112: Toril Zahl Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Toril', 'Zahl Remøy', 'toril.zahlr@hotmail.no', '41646461', '1965-08-22', 'Myrane 6,', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'toril.zahlr@hotmail.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-24', '2026-09-24', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-24';

-- #113: Norunn Kragseth
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Norunn', 'Kragseth', 'magne.kragseth@tussa.com', '95433899', NULL, 'Kopperstadvegen 47', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'magne.kragseth@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-25', '2026-09-25', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-25';

-- #114: Bård Remøy Remøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Bård', 'Remøy Remøy', 'baard_remoy@hotmail.com', '95724601', NULL, 'Kvanesveien 45,', 'Leinøy', '6094', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'baard_remoy@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-25', '2026-09-25', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-25';

-- #115: Audhild Kvalsvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Audhild', 'Kvalsvik', 'audhild.kvalsvik@outlook.com', '99570874', '1957-03-12', 'Valvegen 1D,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'audhild.kvalsvik@outlook.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-26', '2026-09-26', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-26';

-- #116: Ole Jonny Konningen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Ole', 'Jonny Konningen', 'konningen@icloud.com', '90996351', '1959-02-09', 'Nedre Plassegjerdet 11,', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'konningen@icloud.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-26', '2026-09-26', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-26';

-- #117: Marit Kvalsund Konningen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Marit', 'Kvalsund Konningen', 'marit.konningen@hotmail.com', '92461475', '1960-02-07', 'Nedre Plassegjerdet 11,', 'Vatne', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'marit.konningen@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-26', '2026-09-26', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-26';

-- #118: Eivind Longva
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Eivind', 'Longva', 'elongva@gmail.com', '93027251', '1959-10-23', 'Vågsholmvegen 19,', 'FosnavÅg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'elongva@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-26', '2026-09-26', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-26';

-- #119: Mona Zahl Longva
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Mona', 'Zahl Longva', 'monazl@hotmail.com', '995 01 291', NULL, 'Vågsholmvegen 19,', 'Fosnavåg', '6090', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'monazl@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-26', '2026-09-26', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-26';

-- #120: Per Jarle Skar
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Per', 'Jarle Skar', NULL, '95813437', NULL, 'Demingane 14,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #121: Venche Lannerholm
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Venche', 'Lannerholm', 'wenchelann@hotmail.com', '95498886', NULL, 'Knotten 130,', 'Gursken', '6082', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'wenchelann@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #122: Karin Bø
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Karin', 'Bø', 'karin.bo15@gmail.com', '41224947', NULL, 'Sandevegen 6,', 'Bølandet', '6095', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'karin.bo15@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #123: Sigurd Notøy
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Sigurd', 'Notøy', 'sigmarnotoy@gmail.com', '90645690', NULL, 'Stornesvegen 8,', 'Tjørvåg', '6070', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'sigmarnotoy@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #124: Bergljot Myrseth
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Bergljot', 'Myrseth', NULL, '90025016', NULL, 'Mikkelhaugen 9,,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #125: Jostein Mo
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Jostein', 'Mo', 'jo-i-mo@hotmail.com', '90683835', NULL, 'Mosgeila,', 'Ørsta', '6154', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'jo-i-mo@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #126: Anne Kristensen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Anne', 'Kristensen', NULL, '94891113', NULL, 'Storbakken 2,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #127: Oddveig GARVIK
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Oddveig', 'GARVIK', 'Oddveig.petrin.garvik@outlook.com', '99611182', NULL, 'Mikkelhaugen 6,', 'Fosnavåg', '6092', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'Oddveig.petrin.garvik@outlook.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #128: Laila Berge
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Laila', 'Berge', 'berge_laila35@hotmail.com', '41498722', '1959-11-16', 'Elsebøveien 17,', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'berge_laila35@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #129: Guttorm Berge
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Guttorm', 'Berge', 'gube53@hotmail.com', '97777840', '1953-05-11', 'Flusundvegen 53,', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'gube53@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #130: Line Nygard
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Line', 'Nygard', 'linenyga@hotmail.com', '957 25 121', '1969-02-18', 'Flusundvegen 53,', 'Fosnavåg', '6091', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'linenyga@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-29', '2026-09-29', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-29';

-- #131: Anita H Kvalsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Anita', 'H Kvalsund', 'anitah.kvalsund@hotmail.com', '91107083', '1959-07-17', 'Koperstadvegen 43,', 'NERLANDSØY', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'anitah.kvalsund@hotmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-30', '2026-09-30', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-30';

-- #132: Runar Kvalsund
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Runar', 'Kvalsund', 'runar.kvalsund@tussa.com', '47015195', '1958-02-21', 'Koperstadvegen 43,', 'Nerlandsøy', '6098', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'runar.kvalsund@tussa.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-30', '2026-09-30', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-30';

-- #133: Perny Hilde Kvalsvik
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Perny', 'Hilde Kvalsvik', 'pernyh58@gmail.com', '92842285', '1958-09-19', 'Nesevegen 5,', 'Ulstein', '6065', 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'pernyh58@gmail.com' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-09-30', '2026-09-30', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-09-30';

-- #134: Trond Tellefsen
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Trond', 'Tellefsen', 'trond.tellefsen@oneco.no', '91729150', NULL, NULL, NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'trond.tellefsen@oneco.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-10-17', '2026-10-17', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-10-17';

-- #135: Leidulf Helde
SET @customer_id = REPLACE(UUID(), '-', '');
SET @member_id = REPLACE(UUID(), '-', '');
INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, address_city, address_zip, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), 'Leidulf', 'Helde', 'tull@one.no', '41646426', NULL, 'x', NULL, NULL, 1, 'active');
SET @customer_id = (
    SELECT id FROM tenant_customers 
    WHERE company_id = @company_id 
    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci
         OR email COLLATE utf8mb4_unicode_ci = 'tull@one.no' COLLATE utf8mb4_unicode_ci)
    LIMIT 1
);
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '2025-10-17', '2026-10-17', 1
WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL
ON DUPLICATE KEY UPDATE expiration_date = '2026-10-17';

-- ========================================
-- OPPSUMMERING OG VERIFISERING
-- ========================================

-- Forventet: 135 medlemmer
-- La oss sjekke hva som faktisk ble importert:

SELECT 
    (SELECT COUNT(*) FROM tenant_customers WHERE company_id = @company_id AND is_member = 1) as totalt_kunder_med_medlemskap,
    (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id AND membership_plan_id = @plan_id) as totalt_medlemmer_i_plan,
    (SELECT name FROM membership_plans WHERE id = @plan_id) as medlemskapsplan_navn,
    CASE 
        WHEN (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id AND membership_plan_id = @plan_id) >= 135 THEN '✓ SUKSESS'
        ELSE CONCAT('⚠ ADVARSEL: Kun ', (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id AND membership_plan_id = @plan_id), ' av 135 importert')
    END as import_status;

-- VIKTIG: Hvis import_status viser advarsel, kan det være duplikate e-poster.
-- Dette er normalt og betyr at eksisterende kunder fikk fornyet medlemskap.

-- Import fullført!
