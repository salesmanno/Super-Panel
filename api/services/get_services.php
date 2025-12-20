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
    
    $stmt = $db->prepare("
        SELECT 
            s.id,
            s.service_name as name,
            s.description,
            s.price,
            s.duration_minutes,
            s.service_type as category,
            s.available_from,
            s.available_to,
            s.is_active,
            s.capacity,
            s.img_url,
            s.assigned_staff_mode,
            s.assigned_staff_ids,
            s.created_at
        FROM reservation_services s
        WHERE s.company_id = ?
        ORDER BY s.is_active DESC, s.service_name ASC
    ");
    
    $stmt->execute([$companyId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $staffStmt = $db->prepare("
        SELECT 
            ss.service_id,
            ss.staff_id,
            ss.worker_primary,
            ts.first_name,
            ts.last_name
        FROM staff_services ss
        INNER JOIN tenant_staff ts ON ss.staff_id = ts.id
        WHERE ss.company_id = ? AND ss.is_active = 1
    ");
    $staffStmt->execute([$companyId]);
    $allStaffServices = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $staffByService = [];
    foreach ($allStaffServices as $ss) {
        if (!isset($staffByService[$ss['service_id']])) {
            $staffByService[$ss['service_id']] = [];
        }
        $staffByService[$ss['service_id']][] = [
            'id' => $ss['staff_id'],
            'name' => $ss['first_name'] . ' ' . $ss['last_name'],
            'is_primary' => (bool)$ss['worker_primary']
        ];
    }
    
    foreach ($services as &$service) {
        $service['assigned_staff'] = $staffByService[$service['id']] ?? [];
        $service['staff_count'] = count($service['assigned_staff']);
    }
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av tjenester'
    ]);
}
