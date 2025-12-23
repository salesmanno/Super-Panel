<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$serviceId = $_GET['service_id'] ?? null;

if (!$serviceId) {
    echo json_encode(['success' => false, 'message' => 'Service ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT 
            ts.id,
            CONCAT(ts.first_name, ' ', ts.last_name) as name,
            ss.is_primary
        FROM staff_services ss
        JOIN tenant_staff ts ON ss.staff_id = ts.id
        WHERE ss.service_id = ? AND ts.company_id = ?
        ORDER BY ss.is_primary DESC, ts.first_name ASC
    ");
    $stmt->execute([$serviceId, $companyId]);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'staff' => $staff
    ]);
    
} catch (Exception $e) {
    error_log("Error getting staff for service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av ansatte: ' . $e->getMessage()
    ]);
}
