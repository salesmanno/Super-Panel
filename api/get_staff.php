<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Fix collation mismatch
    $db->exec("SET collation_connection = 'utf8mb4_general_ci'");
    
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT 
            id,
            first_name,
            last_name,
            email,
            job_title,
            is_active
        FROM tenant_staff
        WHERE company_id = ? AND is_active = ?
        ORDER BY first_name, last_name
    ");
    
    $stmt->execute([$companyId, 1]);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'staff' => $staff
    ]);
    
} catch (Exception $e) {
    error_log("Get staff error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Serverfeil']);
}
