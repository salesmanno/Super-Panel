<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Tjenestenavn er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        INSERT INTO reservation_services (
            company_id, service_name, description, price, duration_minutes,
            service_type, available_from, available_to, is_active, capacity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $companyId,
        $data['name'],
        $data['description'] ?? null,
        $data['price'] ?? 0,
        $data['duration_minutes'] ?? 60,
        $data['category'] ?? 'other',
        $data['available_from'] ?? '09:00:00',
        $data['available_to'] ?? '17:00:00',
        isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
        $data['capacity'] ?? 1
    ]);
    
    $serviceId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tjeneste opprettet',
        'service_id' => $serviceId
    ]);
    
} catch (Exception $e) {
    error_log("Error creating service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av tjeneste: ' . $e->getMessage()
    ]);
}
