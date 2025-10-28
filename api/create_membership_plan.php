<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler plan navn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // Generer ID-er
        $planId = bin2hex(random_bytes(16));
        $outputProductId = bin2hex(random_bytes(16));
        $planCode = 'PLAN-' . strtoupper(substr($planId, 0, 8));
        
        // 1. Opprett medlemskapsplan (match MySQL structure from salesmanno04)
        $stmt = $db->prepare("
            INSERT INTO membership_plans (
                id,
                company_id,
                plan_code,
                name,
                description,
                price,
                billing_cycle,
                benefits,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $billingCycle = $data['billing_cycle'] ?? 'monthly';
        
        $stmt->execute([
            $planId,
            $_SESSION['company_id'],
            $planCode,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $billingCycle,
            $data['benefits'] ?? null
        ]);
        
        // 2. Auto-generer output_products entry (module_id=5 for membership)
        $stmt = $db->prepare("
            INSERT INTO output_products (
                id,
                company_id,
                module_id,
                product_type,
                product_id,
                name,
                description,
                price,
                is_visible,
                is_public,
                is_marketplace_featured,
                created_at,
                created_by
            ) VALUES (?, ?, 5, 'membership', ?, ?, ?, ?, 1, 1, 0, NOW(), ?)
        ");
        
        $stmt->execute([
            $outputProductId,
            $_SESSION['company_id'],
            $planId,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $_SESSION['tenant_admin_id']
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Medlemskapsplan opprettet',
            'plan_id' => $planId,
            'output_product_id' => $outputProductId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating membership plan: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
