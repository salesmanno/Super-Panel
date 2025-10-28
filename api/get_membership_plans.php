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
    
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            description,
            price,
            currency,
            billing_cycle,
            benefits,
            is_active,
            created_at
        FROM membership_plans
        WHERE company_id = ? AND is_active = TRUE
        ORDER BY created_at DESC
    ");
    
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
        'error' => 'Database utilgjengelig',
        'plans' => []
    ]);
}
