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
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Tjeneste-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $updates = [];
    $params = [];
    
    $fieldMap = [
        'name' => 'service_name',
        'description' => 'description',
        'price' => 'price',
        'duration_minutes' => 'duration_minutes',
        'category' => 'service_type',
        'available_from' => 'available_from',
        'available_to' => 'available_to',
        'capacity' => 'capacity',
        'img_url' => 'img_url'
    ];
    
    foreach ($fieldMap as $input => $column) {
        if (array_key_exists($input, $data)) {
            $updates[] = "$column = ?";
            $params[] = $data[$input];
        }
    }
    
    if (array_key_exists('is_active', $data)) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'] ? 1 : 0;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Ingen felt å oppdatere']);
        exit;
    }
    
    $params[] = $data['id'];
    $params[] = $companyId;
    
    $stmt = $db->prepare("
        UPDATE reservation_services 
        SET " . implode(', ', $updates) . "
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tjeneste oppdatert'
    ]);
    
} catch (Exception $e) {
    error_log("Error updating service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
