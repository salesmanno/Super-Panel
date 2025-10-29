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
        // Beregn duration_months fra billing_cycle
        $billingCycle = $data['billing_cycle'] ?? 'yearly';
        $durationMonths = match($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default => 12
        };
        
        // 1. Opprett medlemskapsplan i x_membership_plan tabell
        $stmt = $db->prepare("
            INSERT INTO x_membership_plan (
                company_id,
                plan_name,
                description,
                price,
                duration_months,
                features,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, true)
            RETURNING plan_id
        ");
        
        $stmt->execute([
            $_SESSION['company_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $durationMonths,
            $data['benefits'] ?? null
        ]);
        
        $planId = $stmt->fetchColumn();
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Medlemskapsplan opprettet',
            'plan_id' => $planId
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
