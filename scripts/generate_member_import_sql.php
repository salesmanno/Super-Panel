<?php
/**
 * GENERERER SQL IMPORT SCRIPT FOR BERGSØY MEDLEMMER
 * Kjør: php scripts/generate_member_import_sql.php > docs/bergsoy_members_import.sql
 */

$sqlFile = __DIR__ . '/../attached_assets/salesmanno01_1763072172671.sql';
$sqlContent = file_get_contents($sqlFile);

// Parser INSERT-setninger
preg_match_all("/\((\d+),\s*'([^']+)',\s*(?:'([^']*)'|NULL),\s*(?:'([^']*)'|NULL),\s*(?:'([^']*)'|NULL),\s*(?:'([^']*)'|NULL),\s*'([^']+)',\s*(\d+),\s*'([^']+)'/", $sqlContent, $matches, PREG_SET_ORDER);

echo "-- ========================================\n";
echo "-- MEDLEMSIMPORT FOR BERGSØY DANSEKLUBB\n";
echo "-- ========================================\n";
echo "-- Generert: " . date('Y-m-d H:i:s') . "\n";
echo "-- Antall medlemmer: " . count($matches) . "\n";
echo "-- \n";
echo "-- INSTRUKSJONER:\n";
echo "-- 1. Logg inn på phpMyAdmin (Domeneshop)\n";
echo "-- 2. Velg salesmanno01 database\n";
echo "-- 3. Gå til SQL-tab\n";
echo "-- 4. Lim inn HELE dette skriptet\n";
echo "-- 5. Klikk 'Utfør'\n";
echo "-- ========================================\n\n";

echo "-- Finn company_id og plan_id automatisk\n";
echo "SET @company_id = (SELECT id FROM tenant_companies WHERE subdomain = 'bergsoy' LIMIT 1);\n";
echo "SET @plan_id = (SELECT id FROM membership_plans WHERE company_id = @company_id AND name LIKE '%Bergsøy%' LIMIT 1);\n\n";

echo "-- Verifiser at verdiene er funnet\n";
echo "SELECT \n";
echo "    COALESCE(@company_id, 'ERROR: Company ikke funnet!') AS company_status,\n";
echo "    COALESCE(@plan_id, 'ERROR: Plan ikke funnet!') AS plan_status;\n\n";

echo "-- ========================================\n";
echo "-- START IMPORT (" . count($matches) . " medlemmer)\n";
echo "-- ========================================\n\n";

foreach ($matches as $index => $match) {
    $fullName = $match[2];
    $email = $match[3] ?: 'NULL';
    $phone = $match[4] ?: 'NULL';
    $address = $match[5] ?: 'NULL';
    $birthDate = $match[6] ?: 'NULL';
    $joinDate = $match[7];
    $expiresAt = $match[9];
    
    // Split navn
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // SQL-escape
    $firstName = addslashes($firstName);
    $lastName = addslashes($lastName);
    $email = $email === 'NULL' ? 'NULL' : "'" . addslashes($email) . "'";
    $phone = $phone === 'NULL' ? 'NULL' : "'" . addslashes($phone) . "'";
    $address = $address === 'NULL' ? 'NULL' : "'" . addslashes($address) . "'";
    $birthDate = $birthDate === 'NULL' ? 'NULL' : "'" . addslashes($birthDate) . "'";
    
    echo "-- #" . ($index + 1) . ": $fullName\n";
    echo "SET @customer_id = REPLACE(UUID(), '-', '');\n";
    echo "SET @member_id = REPLACE(UUID(), '-', '');\n";
    
    // Bruk INSERT IGNORE for å hoppe over duplikater
    echo "INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)\n";
    echo "VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), '$firstName', '$lastName', $email, $phone, $birthDate, $address, 1, 'active');\n";
    
    // Sjekk om kunde ble opprettet, ellers finn eksisterende kunde
    echo "SET @customer_id = COALESCE(\n";
    echo "    (SELECT id FROM tenant_customers WHERE company_id = @company_id AND customer_code = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) LIMIT 1),\n";
    if ($email !== 'NULL') {
        echo "    (SELECT id FROM tenant_customers WHERE company_id = @company_id AND email = $email LIMIT 1),\n";
    }
    echo "    @customer_id\n";
    echo ");\n";
    
    echo "INSERT IGNORE INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)\n";
    echo "VALUES (@member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '$joinDate', '$expiresAt', 1);\n\n";
}

echo "-- ========================================\n";
echo "-- OPPSUMMERING\n";
echo "-- ========================================\n\n";

echo "SELECT \n";
echo "    (SELECT COUNT(*) FROM tenant_customers WHERE company_id = @company_id) as antall_kunder,\n";
echo "    (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id) as antall_medlemmer,\n";
echo "    (SELECT name FROM membership_plans WHERE id = @plan_id) as medlemskapsplan;\n\n";

echo "-- Import fullført!\n";
