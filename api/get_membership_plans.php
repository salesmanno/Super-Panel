<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    // Dual database support: MySQL uses membership_plans, PostgreSQL uses membership_plans
    $stmt = $db->prepare("
            SELECT 
                id as plan_id,
                name,
                description,
                price,
                billing_cycle,
                benefits,
                is_active,
                is_internal
            FROM membership_plans
            WHERE company_id = ? AND is_active = 1 AND is_internal = 0
            ORDER BY created_at DESC
        ");
    } else {
        $stmt = $db->prepare("
            SELECT 
                plan_id,
                plan_name as name,
                description,
                price,
                duration_months,
                features as benefits,
                is_active
            FROM membership_plans
            WHERE company_id = ? AND is_active = true
            ORDER BY created_at DESC
        ");
    }
    
    $stmt->execute([$companyId]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plans' => $plans
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching membership plans: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente medlemskapsplaner'
    ]);
}
