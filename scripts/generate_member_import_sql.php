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

echo "-- ========================================\n";
echo "-- VALIDER FORUTSETNINGER\n";
echo "-- ========================================\n\n";

echo "-- Finn company_id og plan_id automatisk\n";
echo "SET @company_id = (SELECT id FROM tenant_companies WHERE subdomain = 'bergsoy' LIMIT 1);\n";
echo "SET @plan_id = (SELECT id FROM membership_plans WHERE company_id = @company_id AND name LIKE '%Bergsøy%' LIMIT 1);\n\n";

echo "-- KRITISK: Stopp importen hvis company eller plan ikke finnes\n";
echo "SELECT \n";
echo "    CASE \n";
echo "        WHEN @company_id IS NULL THEN 'FEIL: Bergsøy company ikke funnet! Stopp importen!'\n";
echo "        WHEN @plan_id IS NULL THEN 'FEIL: Bergsøy Medlem plan ikke funnet! Opprett planen først!'\n";
echo "        ELSE CONCAT('OK: Company ID = ', @company_id, ', Plan ID = ', @plan_id)\n";
echo "    END AS validation_status;\n\n";

echo "-- Hvis du ser FEIL ovenfor, IKKE kjør resten av skriptet!\n";
echo "-- Du må opprette company eller medlemskapsplan først.\n\n";

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
    
    // Prøv å opprette kunde
    echo "INSERT IGNORE INTO tenant_customers (id, company_id, customer_code, first_name, last_name, email, phone, date_of_birth, address_street, is_member, status)\n";
    echo "VALUES (@customer_id, @company_id, CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))), '$firstName', '$lastName', $email, $phone, $birthDate, $address, 1, 'active');\n";
    
    // Finn kunde-ID (enten nyopprettet eller eksisterende)
    echo "SET @customer_id = (\n";
    echo "    SELECT id FROM tenant_customers \n";
    echo "    WHERE company_id = @company_id \n";
    if ($email !== 'NULL') {
        echo "    AND (customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci\n";
        echo "         OR email COLLATE utf8mb4_unicode_ci = $email COLLATE utf8mb4_unicode_ci)\n";
    } else {
        echo "    AND customer_code COLLATE utf8mb4_unicode_ci = CONCAT('CUST-', UPPER(SUBSTRING(@customer_id, 1, 8))) COLLATE utf8mb4_unicode_ci\n";
    }
    echo "    LIMIT 1\n";
    echo ");\n";
    
    // Opprett medlemskap kun hvis kunde eksisterer
    echo "INSERT INTO membership_members (id, company_id, customer_id, membership_plan_id, member_code, status, join_date, expiration_date, auto_renew)\n";
    echo "SELECT @member_id, @company_id, @customer_id, @plan_id, CONCAT('MBR-', UPPER(SUBSTRING(@member_id, 1, 8))), 'active', '$joinDate', '$expiresAt', 1\n";
    echo "WHERE @customer_id IS NOT NULL AND @plan_id IS NOT NULL\n";
    echo "ON DUPLICATE KEY UPDATE expiration_date = '$expiresAt';\n\n";
}

echo "-- ========================================\n";
echo "-- OPPSUMMERING OG VERIFISERING\n";
echo "-- ========================================\n\n";

$expected = count($matches);

echo "-- Forventet: $expected medlemmer\n";
echo "-- La oss sjekke hva som faktisk ble importert:\n\n";

echo "SELECT \n";
echo "    (SELECT COUNT(*) FROM tenant_customers WHERE company_id = @company_id AND is_member = 1) as totalt_kunder_med_medlemskap,\n";
echo "    (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id AND membership_plan_id = @plan_id) as totalt_medlemmer_i_plan,\n";
echo "    (SELECT name FROM membership_plans WHERE id = @plan_id) as medlemskapsplan_navn,\n";
echo "    CASE \n";
echo "        WHEN (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id AND membership_plan_id = @plan_id) >= $expected THEN '✓ SUKSESS'\n";
echo "        ELSE CONCAT('⚠ ADVARSEL: Kun ', (SELECT COUNT(*) FROM membership_members WHERE company_id = @company_id AND membership_plan_id = @plan_id), ' av $expected importert')\n";
echo "    END as import_status;\n\n";

echo "-- VIKTIG: Hvis import_status viser advarsel, kan det være duplikate e-poster.\n";
echo "-- Dette er normalt og betyr at eksisterende kunder fikk fornyet medlemskap.\n\n";

echo "-- Import fullført!\n";
