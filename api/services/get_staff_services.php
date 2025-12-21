<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $serviceId = $_GET['service_id'] ?? null;
    $staffId = $_GET['staff_id'] ?? null;
    
    $sql = "
        SELECT 
            ss.id,
            ss.staff_id,
            ss.service_id,
            ss.is_active,
            ss.max_daily_bookings,
            ss.worker_primary,
            ts.first_name,
            ts.last_name,
            ts.email,
            ts.phone,
            rs.service_name,
            rs.price as service_price
        FROM staff_services ss
        INNER JOIN tenant_staff ts ON ss.staff_id = ts.id
        INNER JOIN reservation_services rs ON ss.service_id = rs.id
        WHERE ss.company_id = ?
    ";
    
    $params = [$companyId];
    
    if ($serviceId) {
        $sql .= " AND ss.service_id = ?";
        $params[] = $serviceId;
    }
    
    if ($staffId) {
        $sql .= " AND ss.staff_id = ?";
        $params[] = $staffId;
    }
    
    $sql .= " ORDER BY ts.first_name, ts.last_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $staffServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'staff_services' => $staffServices
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching staff services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av ansatt-tjenester'
    ]);
}
