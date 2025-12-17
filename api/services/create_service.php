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
    
    $paymentMethodsJson = isset($data['payment_methods']) && is_array($data['payment_methods'])
        ? json_encode($data['payment_methods'])
        : null;
    
    $stmt = $db->prepare("
        INSERT INTO reservation_services (
            company_id, name, description, price, duration_minutes,
            required_duration_minutes, buffer_before_minutes, buffer_after_minutes,
            max_daily_bookings, category, is_active, payment_methods_json,
            default_payment_method, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $companyId,
        $data['name'],
        $data['description'] ?? null,
        $data['price'] ?? 0,
        $data['duration_minutes'] ?? 60,
        $data['required_duration_minutes'] ?? null,
        $data['buffer_before_minutes'] ?? 0,
        $data['buffer_after_minutes'] ?? 0,
        $data['max_daily_bookings'] ?? null,
        $data['category'] ?? null,
        isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
        $paymentMethodsJson,
        $data['default_payment_method'] ?? null,
        $_SESSION['tenant_admin_id']
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
