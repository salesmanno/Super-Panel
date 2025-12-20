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
            service_type, available_from, available_to, is_active, capacity, img_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $data['capacity'] ?? 1,
        $data['img_url'] ?? null
    ]);
    
    $serviceId = $db->lastInsertId();
    
    if (!empty($data['staff_ids']) && is_array($data['staff_ids'])) {
        $primaryStaffId = $data['primary_staff_id'] ?? null;
        $verifyStmt = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
        $staffStmt = $db->prepare("
            INSERT INTO staff_services (company_id, staff_id, service_id, is_active, worker_primary)
            VALUES (?, ?, ?, 1, ?)
        ");
        foreach ($data['staff_ids'] as $staffId) {
            $verifyStmt->execute([$staffId, $companyId]);
            if ($verifyStmt->fetch()) {
                $isPrimary = ($staffId == $primaryStaffId) ? 1 : 0;
                $staffStmt->execute([$companyId, $staffId, $serviceId, $isPrimary]);
            }
        }
    }
    
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
