<?php
// IMPORT SCRIPT FOR BERGSØY DANSEKLUBB MEDLEMMER
// Kjør dette skriptet én gang: php import_members.php

require_once __DIR__ . '/config/database.php';

echo "=== MEDLEMSIMPORT FOR BERGSØY DANSEKLUBB ===\n\n";

// Hent company_id og membership_plan_id
$db = getDBConnection();

// Finn Bergsøy company
$stmt = $db->prepare("SELECT id FROM tenant_companies WHERE subdomain = ?");
$stmt->execute(['bergsoy']);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die("FEIL: Finner ikke Bergsøy i databasen!\n");
}
$companyId = $company['id'];
echo "✓ Fant company_id: $companyId\n";

// Finn Bergsøy Medlem plan
$stmt = $db->prepare("SELECT id FROM membership_plans WHERE company_id = ? AND name LIKE '%Bergsøy%' LIMIT 1");
$stmt->execute([$companyId]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    die("FEIL: Finner ikke medlemskapsplan 'Bergsøy Medlem'!\n");
}
$planId = $plan['id'];
echo "✓ Fant plan_id: $planId\n\n";

// Les medlemsdata fra SQL-filen
$sqlFile = __DIR__ . '/attached_assets/salesmanno01_1763072172671.sql';
$sqlContent = file_get_contents($sqlFile);

// Parser INSERT-setninger
preg_match_all("/\((\d+),\s*'([^']+)',\s*(?:'([^']*)'|NULL),\s*(?:'([^']*)'|NULL),\s*(?:'([^']*)'|NULL),\s*(?:'([^']*)'|NULL),\s*'([^']+)',\s*(\d+),\s*'([^']+)'/", $sqlContent, $matches, PREG_SET_ORDER);

$imported = 0;
$skipped = 0;

$db->beginTransaction();

try {
    foreach ($matches as $match) {
        $oldId = $match[1];
        $fullName = $match[2];
        $email = $match[3] ?: null;
        $phone = $match[4] ?: null;
        $address = $match[5] ?: null;
        $birthDate = $match[6] ?: null;
        $joinDate = $match[7];
        $expiresAt = $match[9];
        
        // Split navn i fornavn/etternavn
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Generer customer_code
        $customerCode = 'CUST-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $customerId = bin2hex(random_bytes(16));
        
        // Opprett kunde
        $stmt = $db->prepare("
            INSERT INTO tenant_customers (
                id, company_id, customer_code, first_name, last_name, 
                email, phone, date_of_birth, address_street, 
                is_member, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'active')
        ");
        
        $stmt->execute([
            $customerId,
            $companyId,
            $customerCode,
            $firstName,
            $lastName,
            $email,
            $phone,
            $birthDate,
            $address
        ]);
        
        // Opprett medlemskap
        $memberCode = 'MBR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $memberId = bin2hex(random_bytes(16));
        
        $stmt = $db->prepare("
            INSERT INTO membership_members (
                id, company_id, customer_id, membership_plan_id,
                member_code, status, join_date, expiration_date, auto_renew
            ) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, 1)
        ");
        
        $stmt->execute([
            $memberId,
            $companyId,
            $customerId,
            $planId,
            $memberCode,
            $joinDate,
            $expiresAt
        ]);
        
        $imported++;
        echo "✓ Importert: $fullName (Medlem: $memberCode)\n";
    }
    
    $db->commit();
    
    echo "\n=== IMPORT FULLFØRT ===\n";
    echo "Importert: $imported medlemmer\n";
    echo "Hoppet over: $skipped\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ FEIL: " . $e->getMessage() . "\n";
    exit(1);
}
