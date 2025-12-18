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
