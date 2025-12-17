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
            s.name,
            s.description,
            s.price,
            s.duration_minutes,
            s.required_duration_minutes,
            s.buffer_before_minutes,
            s.buffer_after_minutes,
            s.max_daily_bookings,
            s.category,
            s.is_active,
            s.payment_methods_json,
            s.default_payment_method,
            s.created_at,
            (SELECT COUNT(*) FROM staff_services ss WHERE ss.service_id = s.id AND ss.is_active = 1) as staff_count
        FROM reservation_services s
        WHERE s.company_id = ?
        ORDER BY s.is_active DESC, s.name ASC
    ");
    
    $stmt->execute([$companyId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($services as &$service) {
        $service['payment_methods'] = $service['payment_methods_json'] 
            ? json_decode($service['payment_methods_json'], true) 
            : [];
        unset($service['payment_methods_json']);
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
