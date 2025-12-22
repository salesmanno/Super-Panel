<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("SELECT refresh_polling FROM tenant_companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $shouldRefresh = ($result && $result['refresh_polling'] == 1);
    
    if ($shouldRefresh) {
        $updateStmt = $db->prepare("UPDATE tenant_companies SET refresh_polling = 0 WHERE id = ?");
        $updateStmt->execute([$companyId]);
    }
    
    echo json_encode([
        'success' => true,
        'refresh' => $shouldRefresh
    ]);
    
} catch (Exception $e) {
    error_log("Error checking refresh flag: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sjekk av refresh-flagg'
    ]);
}
