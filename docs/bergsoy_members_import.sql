-- ========================================
-- MEDLEMSIMPORT FOR BERGSØY DANSEKLUBB
-- ========================================
-- Generert: 2025-11-13 22:32:32
-- Antall medlemmer: 135
-- 
-- INSTRUKSJONER:
-- 1. Logg inn på phpMyAdmin (Domeneshop)
-- 2. Velg salesmanno01 database
-- 3. Gå til SQL-tab
-- 4. Lim inn HELE dette skriptet
-- 5. Klikk 'Utfør'
-- ========================================

-- Finn company_id og plan_id automatisk
SET @company_id = (SELECT id FROM tenant_companies WHERE subdomain = 'bergsoy' LIMIT 1);
SET @plan_id = (SELECT id FROM membership_plans WHERE company_id = @company_id AND name LIKE '%Bergsøy%' LIMIT 1);

-- Verifiser at verdiene er funnet
SELECT 
    COALESCE(@company_id, 'ERROR: Company ikke funnet!') AS company_status,
    COALESCE(@plan_id, 'ERROR: Plan ikke funnet!') AS plan_status;

-- ========================================
-- START IMPORT (135 medlemmer)
-- ========================================

-- #1: Egil Rasmineson Frantsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Egil', 'Rasmineson Frantsen', 'e-fran@online.no', '90689856', NULL, 'Gamlemyklebustveg 9 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #2: Britt Inger Gusdal Frantsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Britt', 'Inger Gusdal Frantsen', 'brittingerbig@gmail.com', '93226717', NULL, 'GamleMyklebustveg 9 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #3: Atle Kvalsvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Atle', 'Kvalsvik', 'atlekarl@tussa.com', '95918925', '1956-04-08', 'Mulevikvegen,42 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #4: Bente Kvalsvik Notøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Bente', 'Kvalsvik Notøy', 'bente02@live.no', '48158704', NULL, 'Breidanesvegen 8, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #5: Marion Nilsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marion', 'Nilsen', 'marion.nilsen@outlook.com', '97988677', '1955-07-17', 'Mulevikvegen,42 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #6: Kristin Leine
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kristin', 'Leine', 'kristinleine@hotmail.no', '99649880', NULL, 'Jordmor Helgas veg  14, 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #7: Marita Higraf Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marita', 'Higraf Remøy', 'maritahr2@gmail.com', '90981991', '1966-04-14', 'Nedre plassgjerde 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-21', '2026-07-21', 1);

-- #8: Oddveig Arnesen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddveig', 'Arnesen', NULL, '45235239', NULL, 'Nordgardsvegen 4 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #9: Asbjørn Bendal
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Asbjørn', 'Bendal', 'abendal@online.no', '95766662', NULL, 'Kviehaugen 8 B 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #10: Jan Bugge
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Jan', 'Bugge', NULL, '95125587', NULL, 'Nedre Hjelmeseth 20 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #11: Jan Bø
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Jan', 'Bø', NULL, '48051150', NULL, 'Sandevegen 6 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #12: Reidun Baade
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Reidun', 'Baade', 'reidun23@hotmail.no', '97077208', '1958-03-23', 'Ospenesvegen 7 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #13: Annlaug Eikrem
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Annlaug', 'Eikrem', 'annlaugeikrem@hotmail.com', '46430527', NULL, 'Voldsundvegen 48, 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #14: Myrthel Emblem
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Myrthel', 'Emblem', 'myrtelemblem@gmail.com', '91849589', NULL, 'Postboks 25 6099 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #15: Arild Flusund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Arild', 'Flusund', 'arild.flusund@gmail.com', '91637514', '1958-10-12', 'Sandevegen 8 9095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #16: Arnhild Flusund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Arnhild', 'Flusund', NULL, '41235051', '1962-04-29', 'Sandevegen 8 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #17: Idar Frantzen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Idar', 'Frantzen', 'frantsenidar@gmail.com', '40613610', NULL, 'Ospenesveien 21 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #18: Tone Foss Frantzen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Tone', 'Foss Frantzen', 'tone.frantzen@hotmail.com', '95338070', NULL, 'Holmsildgata 16 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #19: Mathias Haugen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Mathias', 'Haugen', 'stottemur@hotmail.com', '45500147', NULL, 'Myklebustvegen 115 6488 Myklebust', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #20: Robert Bjarne Helde
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Robert', 'Bjarne Helde', 'robert.heldeb@gmail.com', '97624748', NULL, 'Demingane 27 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #21: Fritjof Husøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Fritjof', 'Husøy', NULL, '95223457', NULL, 'Torvik 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #22: Erik Iversen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Erik', 'Iversen', 'erik.iversen@kcadeutag.com', '90838258', NULL, 'Kopperstadvegen 140 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #23: Margunn Kalland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Margunn', 'Kalland', NULL, '91329484', NULL, 'Massavegen 14, 6096 Runde', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #24: Marit Knutsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marit', 'Knutsen', NULL, '91894371', NULL, 'Borgarhaugvegen 4 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #25: Perly Christin Vikebak Kopperstad
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Perly', 'Christin Vikebak Kopperstad', 'perly.kopperstad@outlook.com', '90117868', NULL, '6099 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #26: Idar Ove Kragset
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Idar', 'Ove Kragset', 'idarovekr@hotmail.com', '90112149', NULL, 'Breidanesvegen 8, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #27: Myrthel Kvalsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Myrthel', 'Kvalsund', NULL, '90588968', NULL, 'Teigelia 38 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #28: Per Ove Kvalsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Per', 'Ove Kvalsund', NULL, '90688968', NULL, 'Teigelia 38 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #29: Geir Vangen Kvalsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Geir', 'Vangen Kvalsund', 'geirkv@gmail.com', '48248499', NULL, 'Øvre Plassegjerde 5 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #30: aksel.kvalsvik@tussa.com
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'aksel.kvalsvik@tussa.com', '', 'aksel.kvalsvik@tussa.com', '97553520', NULL, 'Øvre Plassgjerdet 61 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #31: Evy Helen Ryssevik Kvalsvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Evy', 'Helen Ryssevik Kvalsvik', 'evy.kvals@gmail.com', '95995513', NULL, 'Bendalsvegen 8C 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #32: Åse Kvalsvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Åse', 'Kvalsvik', NULL, '92451835', NULL, 'Øvre Plassegjerde 5 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #33: Mariann Leinebø
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Mariann', 'Leinebø', NULL, '48294481', NULL, 'Støylavegne 13 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #34: Gunnar Leinebø
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Gunnar', 'Leinebø', NULL, '91561632', NULL, 'Støylavegen 13 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #35: Marit Jordan Lyster
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marit', 'Jordan Lyster', NULL, '95156176', NULL, 'Leikongvegen 422 6080 Gurskøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #36: Mariann Løvold
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Mariann', 'Løvold', 'mariann.lovold@hotmail.com', '98482604', NULL, 'Verket 14 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #37: Tore Løvøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Tore', 'Løvøy', 'tore.lovoy@gmail.com', '41307657', NULL, 'Sandvikvegen 71 6070 Tjørvåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #38: Irene Petra Madsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Irene', 'Petra Madsen', NULL, '91737567', NULL, 'Geila 5, 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #39: Johan Moltu
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Johan', 'Moltu', 'johan.moltu@gmail.com', '91568576', NULL, 'Røyravegen 10 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #40: Sølvi Moltu
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Sølvi', 'Moltu', 'solvi.moltu@tussa.com', '93641550', NULL, 'Leinøy 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #41: Anne-Jorunn Moltumyr
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Anne-Jorunn', 'Moltumyr', NULL, '90940208', NULL, 'Remøy 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #42: Henning Moltumyr
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Henning', 'Moltumyr', 'henningmoltumyr@tussa.com', '94143636', NULL, 'Remøy 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #43: Oddvar Myklebust
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddvar', 'Myklebust', NULL, '93600832', NULL, 'Eggesbøvegen 6 A 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #44: Jan Arild Myklebusthaug
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Jan', 'Arild Myklebusthaug', 'jamyklebusthaug@gmail.com', '47830680', NULL, 'Brunholmgata 22 6004 Ålesund', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #45: Geir Per Nerland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Geir', 'Per Nerland', 'geir.per.nerland@gmail.com', '99308210', NULL, 'Landsvegen 12 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #46: Jeanette Nerland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Jeanette', 'Nerland', 'jeanette@hotmail.com', '97191203', NULL, 'Landsvegen 12 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #47: Oddbjørn Nerland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddbjørn', 'Nerland', 'oddbjorn@nerland.io', '47238763', NULL, 'Svingen 8 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #48: Alf Nerland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Alf', 'Nerland', 'alfnerland@tussa.com', '41660225', NULL, 'Holmsildgata 16 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #49: Marit Nerlid
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marit', 'Nerlid', NULL, '97643583', NULL, 'Kalvegjerde 2 6076 Moltustranda', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #50: Anita Nilsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Anita', 'Nilsen', 'anita-nilsen@live.no', '97646387', NULL, '6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #51: Gerd Jorunn Nilsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Gerd', 'Jorunn Nilsen', NULL, '41302631', NULL, 'Kyrkjevegen 93 6230 Sykkulven', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #52: Gunn Nilsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Gunn', 'Nilsen', 'gunn-n@hotmail.no', '93412336', NULL, 'Bendalsvegen 15B 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #53: Bård Nordnes
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Bård', 'Nordnes', NULL, '41734804', NULL, 'Voldsundvegen 32 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #54: Ingunn Nordnes
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Ingunn', 'Nordnes', 'ingnordnes@hotmail.com', '95752952', NULL, 'Voldsundvegen 32 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #55: Alis Notøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Alis', 'Notøy', NULL, '90857103', NULL, 'Stornesvegen 8 6070 Tjørvåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #56: Mai-Brit Nygarden
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Mai-Brit', 'Nygarden', NULL, '95000339', NULL, 'K.L.Vegen 9, 6060 Hareid', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #57: Sigmund Nymark
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Sigmund', 'Nymark', 'sigmund.nymark@gmail.com', '41932875', NULL, 'Leione 3A 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #58: Eldar Hoan Ose
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Eldar', 'Hoan Ose', NULL, '41506612', NULL, 'Hoslekervegen 120 6065 Ulsteinvik', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #59: Endre Johan Ose
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Endre', 'Johan Ose', 'endrejohan@hotmail.com', '91101955', NULL, 'Holkestadvegen 6 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #60: Eva Lena Ose
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Eva', 'Lena Ose', 'trolle-40@hotmail.com', '48172229', NULL, 'Hoslekervegen 120 6065 Ulsteinvik', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #61: Bjørn Prytz
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Bjørn', 'Prytz', 'bjornprytz@gmail.com', '47393108', NULL, 'Buråsa 1 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #62: Kristin Rabben
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kristin', 'Rabben', 'kri-rabb@online.no', '90069947', NULL, 'Brunholmgata 22, 6004 Ålesund', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #63: Kirsten Reite
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kirsten', 'Reite', 'k.reite@hotmail.com', '95791864', NULL, 'Kvianesvegen 49, 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #64: Ronald Reite
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Ronald', 'Reite', 'rona-re@tussa.com', '41438157', NULL, 'Kvianesvegen 49, 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #65: Aud Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Aud', 'Remøy', 'audremoy@gmail.com', '98684879', '1961-06-04', 'Myrane 20 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #66: Kurt Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kurt', 'Remøy', 'kurt.remoy@tussa.com', '95839656', '1957-11-15', 'Myrane 20 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #67: Paul Gustav Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Paul', 'Gustav Remøy', 'gustav.jarle@example.com', '87654321', NULL, 'Remøya 22 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #68: Sveinung Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Sveinung', 'Remøy', NULL, '48195217', NULL, 'Nedre Plassgjerde 7 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #69: Ellinor Rinde
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Ellinor', 'Rinde', 'hallgeir66@hotmail.com', '41257965', NULL, 'Ædane 9 6080 Gurskøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #70: Liv Runde
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Liv', 'Runde', NULL, '92260396', NULL, 'Rundevegen 10 6096 Runde', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #71: Frode Ryssevik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Frode', 'Ryssevik', NULL, '97521996', NULL, 'Bendalsvegen 6C 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #72: Palmar Rønnestad
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Palmar', 'Rønnestad', 'palmar.ronnestad@gmail.com', '99011940', '1954-01-18', 'Nedre Røyra 1', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #73: Sølvi Røren
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Sølvi', 'Røren', NULL, '97966560', NULL, 'Vågsgata 45, 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #74: Hans Sande
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Hans', 'Sande', 'hanssande@tussa.com', '92459392', NULL, 'Sandevegen  47 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #75: Petra Sandvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Petra', 'Sandvik', NULL, '45035591', NULL, 'Tjørvågvegen 2 6070 Tjørvåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #76: Roar Sandvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Roar', 'Sandvik', 'solvi.moltu@tussa.com', '90116870', NULL, 'Nautøya 90 6070 Tjørvåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #77: Oddvar Skogen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddvar', 'Skogen', NULL, '47857099', NULL, 'Vågsgata 45, 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #78: Roar Skoglund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Roar', 'Skoglund', 'rokriskog@gmail.com', '91369350', NULL, 'Svingen 8 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #79: Arild Slettestøl
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Arild', 'Slettestøl', 'arild_j@live.com', '91688928', NULL, 'Knotten 130 6082 Gursken', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #80: Aslak Solbjørg
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Aslak', 'Solbjørg', 'asolborg@aolnorge.no', '46891090', NULL, 'Nautøya 125 6070 Tjørvåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #81: Oddveig Stanghelle
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddveig', 'Stanghelle', 'oddveig_sta@hotmail.com', '99649879', NULL, 'Leinevegen 272 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #82: Liv Bessie Stave
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Liv', 'Bessie Stave', 'livstave@online.no', '95768617', NULL, 'Osebergvegen 5 6065 Ulsteinvik', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #83: Per Egil Sørheller
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Per', 'Egil Sørheller', 'peregilsorheller@gmail.com', '45906393', NULL, 'Brauta 3 6037 Eidsnes', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #84: Kirsten Teige
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kirsten', 'Teige', NULL, '40086106', NULL, 'Teigeveien 47, 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #85: Oluf Teige
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oluf', 'Teige', 'oluf.teige@tussa.com', '97525423', '2025-09-15', 'Teigeveien 47, 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #86: Anne-Johanne Tellefsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Anne-Johanne', 'Tellefsen', 'anne.johanne.tellefsen@gmail.com', '90846683', NULL, 'Teigelia 6 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #87: Solfrid Thorset
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Solfrid', 'Thorset', NULL, '95766727', NULL, 'Leikongbakken 4, 6080 Gurskøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #88: Kjellmar Vike
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kjellmar', 'Vike', 'kjellmar50@gmail.com', '90863096', '2025-08-25', 'Grønhaugvegen 34, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #89: Laila Vike
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Laila', 'Vike', 'vennen53@hotmail.com', '99162331', '1963-06-15', 'Grønhaugvegen 34, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #90: Idar Voldsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Idar', 'Voldsund', 'idarvoldsund@tussa.com', '99308221', NULL, 'Arhaugvegen 14, 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #91: Anne-Marie Westbø
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Anne-Marie', 'Westbø', 'annemawest4268@gmail.com', '91804691', NULL, 'Torvikvegen 43 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #92: Hallgeir Åsebø
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Hallgeir', 'Åsebø', 'hallgeir66@hotmail.com', '95753664', NULL, 'Ædane 9 6080 Gurskøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-23', '2026-07-23', 1);

-- #93: Unni Nerland Nerland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Unni', 'Nerland Nerland', 'unni@nerland.io', '45474106', NULL, 'Svingen 8', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-07-28', '2026-07-28', 1);

-- #94: Laila Nedreberg Remøy Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Laila', 'Nedreberg Remøy Remøy', 'laila@remoy.org', '40873948', '1962-08-21', 'Nedre plassgjerde 7', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-08-22', '2026-08-22', 1);

-- #95: Halvard Leine
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Halvard', 'Leine', 'halvard.leine@tussa.com', '95247887', '1956-11-01', 'Leine 41', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-08-22', '2026-08-22', 1);

-- #96: Svein Arne Leine
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Svein', 'Arne Leine', 'tull@one.no', '97069105', '1953-07-25', 'bergslia 3', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-08-22', '2026-08-22', 1);

-- #97: Kristine Bratli Leine
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kristine', 'Bratli Leine', 'tull@one.no', '97500786', '1960-09-30', 'Leine 41', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-08-22', '2026-08-22', 1);

-- #98: Hildegunn Øye
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Hildegunn', 'Øye', 'hildegunnoddrun@gmail.com', '48030489', '1955-03-14', 'Øvre', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-08-22', '2026-08-22', 1);

-- #99: Kari Iren Sandvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Kari', 'Iren Sandvik', 'kariharisan@hotmail.com', '47863533', '1953-04-15', 'Eggesbøgrenda 6 A', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-02', '2026-09-02', 1);

-- #100: Alf Inge Leine
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Alf', 'Inge Leine', 'alf.inge.leine@gmail.com', '90636950', NULL, 'Nedre Plassegjerdet 15', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-07', '2026-09-07', 1);

-- #101: Gry Myrseth Myrseth
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Gry', 'Myrseth Myrseth', 'gry.myrseth@gmail.com', '95793923', '1961-12-11', 'Hulldalsbana 4 A', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-10', '2026-09-10', 1);

-- #102: Lise Hauge Kvalsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Lise', 'Hauge Kvalsund', 'lise.kvalsund@hotmail.com', '95058708', '1959-08-29', 'Bakkevn 9', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-10', '2026-09-10', 1);

-- #103: Oddgeir Madsen Madsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddgeir', 'Madsen Madsen', 'oddgeir.madsen@tussa.com', '91681611', '1955-05-21', 'Teigelia 4', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-11', '2026-09-11', 1);

-- #104: Karen Myrseth Moltumyr
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Karen', 'Myrseth Moltumyr', 'ksmoltumyr@gmail.com', '95966318', NULL, 'Markveien 9', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-17', '2026-09-17', 1);

-- #105: Reidun Frantzen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Reidun', 'Frantzen', 'reidun.frantzen@hotmail.com', '47044251', '1965-11-18', 'Toftestøvegen 24', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-17', '2026-09-17', 1);

-- #106: Vilfred Pettersen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Vilfred', 'Pettersen', NULL, NULL, NULL, 'INNHENT', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-17', '2026-09-17', 1);

-- #107: Karl -Sverre Moltumyr
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Karl', '-Sverre Moltumyr', 'ksmolrumyr@gmail.com', '48281188', NULL, 'Merkveien 9', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-19', '2026-09-19', 1);

-- #108: Paul Sandanger
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Paul', 'Sandanger', 'paul.sandanger@tussa.com', '908 95 585', '1950-05-04', 'Gjerdevegen 10', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-21', '2026-09-21', 1);

-- #109: Jorunn Berge
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Jorunn', 'Berge', 'lola58@live.no', '91894452', '1958-04-14', 'Bergsvegen 39', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-21', '2026-09-21', 1);

-- #110: Marina Hoff Myking
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marina', 'Hoff Myking', 'marinamyking@hotmail.com', '91586793', NULL, 'Støylavegen 11A, 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-23', '2026-09-23', 1);

-- #111: Susann Nerland
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Susann', 'Nerland', 'susannno@yahoo.no', '99393147', '2025-04-15', 'Landsvegen 18, 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-24', '2026-09-24', 1);

-- #112: Toril Zahl Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Toril', 'Zahl Remøy', 'toril.zahlr@hotmail.no', '41646461', '1965-08-22', 'Myrane 6, 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-24', '2026-09-24', 1);

-- #113: Norunn Kragseth
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Norunn', 'Kragseth', 'magne.kragseth@tussa.com', '95433899', NULL, 'Kopperstadvegen 47', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-25', '2026-09-25', 1);

-- #114: Bård Remøy Remøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Bård', 'Remøy Remøy', 'baard_remoy@hotmail.com', '95724601', NULL, 'Kvanesveien 45, 6094 Leinøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-25', '2026-09-25', 1);

-- #115: Audhild Kvalsvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Audhild', 'Kvalsvik', 'audhild.kvalsvik@outlook.com', '99570874', '1957-03-12', 'Valvegen 1D, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-26', '2026-09-26', 1);

-- #116: Ole Jonny Konningen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Ole', 'Jonny Konningen', 'konningen@icloud.com', '90996351', '1959-02-09', 'Nedre Plassegjerdet 11, 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-26', '2026-09-26', 1);

-- #117: Marit Kvalsund Konningen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Marit', 'Kvalsund Konningen', 'marit.konningen@hotmail.com', '92461475', '1960-02-07', 'Nedre Plassegjerdet 11, 6098 Vatne', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-26', '2026-09-26', 1);

-- #118: Eivind Longva
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Eivind', 'Longva', 'elongva@gmail.com', '93027251', '1959-10-23', 'Vågsholmvegen 19, 6090 FosnavÅg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-26', '2026-09-26', 1);

-- #119: Mona Zahl Longva
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Mona', 'Zahl Longva', 'monazl@hotmail.com', '995 01 291', NULL, 'Vågsholmvegen 19, 6090 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-26', '2026-09-26', 1);

-- #120: Per Jarle Skar
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Per', 'Jarle Skar', NULL, '95813437', NULL, 'Demingane 14, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #121: Venche Lannerholm
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Venche', 'Lannerholm', 'wenchelann@hotmail.com', '95498886', NULL, 'Knotten 130, 6082 Gursken', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #122: Karin Bø
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Karin', 'Bø', 'karin.bo15@gmail.com', '41224947', NULL, 'Sandevegen 6, 6095 Bølandet', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #123: Sigurd Notøy
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Sigurd', 'Notøy', 'sigmarnotoy@gmail.com', '90645690', NULL, 'Stornesvegen 8, 6070 Tjørvåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #124: Bergljot Myrseth
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Bergljot', 'Myrseth', NULL, '90025016', NULL, 'Mikkelhaugen 9,, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #125: Jostein Mo
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Jostein', 'Mo', 'jo-i-mo@hotmail.com', '90683835', NULL, 'Mosgeila, 6154 Ørsta', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #126: Anne Kristensen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Anne', 'Kristensen', NULL, '94891113', NULL, 'Storbakken 2, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #127: Oddveig GARVIK
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Oddveig', 'GARVIK', 'Oddveig.petrin.garvik@outlook.com', '99611182', NULL, 'Mikkelhaugen 6, 6092 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #128: Laila Berge
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Laila', 'Berge', 'berge_laila35@hotmail.com', '41498722', '1959-11-16', 'Elsebøveien 17, 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #129: Guttorm Berge
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Guttorm', 'Berge', 'gube53@hotmail.com', '97777840', '1953-05-11', 'Flusundvegen 53, 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #130: Line Nygard
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Line', 'Nygard', 'linenyga@hotmail.com', '957 25 121', '1969-02-18', 'Flusundvegen 53, 6091 Fosnavåg', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-29', '2026-09-29', 1);

-- #131: Anita H Kvalsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Anita', 'H Kvalsund', 'anitah.kvalsund@hotmail.com', '91107083', '1959-07-17', 'Koperstadvegen 43, 6098 NERLANDSØY', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-30', '2026-09-30', 1);

-- #132: Runar Kvalsund
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Runar', 'Kvalsund', 'runar.kvalsund@tussa.com', '47015195', '1958-02-21', 'Koperstadvegen 43, 6098 Nerlandsøy', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-30', '2026-09-30', 1);

-- #133: Perny Hilde Kvalsvik
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Perny', 'Hilde Kvalsvik', 'pernyh58@gmail.com', '92842285', '1958-09-19', 'Nesevegen 5, 6065 Ulstein', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-09-30', '2026-09-30', 1);

-- #134: Trond Tellefsen
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Trond', 'Tellefsen', 'trond.tellefsen@oneco.no', '91729150', NULL, NULL, 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-10-17', '2026-10-17', 1);

-- #135: Leidulf Helde
SET @customer_id = UUID();
SET @member_id = UUID();
INSERT INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)
VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(REPLACE(@customer_id, '-', ''), 1, 8))), 'Leidulf', 'Helde', 'tull@one.no', '41646426', NULL, 'x', 1, 'active');
INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)
VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(REPLACE(@member_id, '-', ''), 1, 8))), 'active', '2025-10-17', '2026-10-17', 1);

-- ========================================
-- OPPSUMMERING
-- ========================================

SELECT 
    (SELECT COUNT(*) FROM tenant_customers WHERE company_id = @company_id) as antall_kunder,
    (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id) as antall_medlemmer,
    (SELECT name FROM membership_plans WHERE id = @plan_id) as medlemskapsplan;

-- Import fullført!
