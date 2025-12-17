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
    
    $fields = [
        'name', 'description', 'price', 'duration_minutes',
        'required_duration_minutes', 'buffer_before_minutes', 'buffer_after_minutes',
        'max_daily_bookings', 'category', 'default_payment_method'
    ];
    
    foreach ($fields as $field) {
        if (array_key_exists($field, $data)) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (array_key_exists('is_active', $data)) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'] ? 1 : 0;
    }
    
    if (array_key_exists('payment_methods', $data)) {
        $updates[] = "payment_methods_json = ?";
        $params[] = is_array($data['payment_methods']) ? json_encode($data['payment_methods']) : null;
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
