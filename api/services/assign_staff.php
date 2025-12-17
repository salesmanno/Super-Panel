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
    
    if (empty($data['service_id']) || empty($data['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tjeneste-ID og ansatt-ID er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $verifyStaff = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
    $verifyStaff->execute([$data['staff_id'], $companyId]);
    if (!$verifyStaff->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ansatt ikke funnet']);
        exit;
    }
    
    $verifyService = $db->prepare("SELECT id FROM reservation_services WHERE id = ? AND company_id = ?");
    $verifyService->execute([$data['service_id'], $companyId]);
    if (!$verifyService->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Tjeneste ikke funnet']);
        exit;
    }
    
    $checkStmt = $db->prepare("
        SELECT id FROM staff_services 
        WHERE company_id = ? AND service_id = ? AND staff_id = ?
    ");
    $checkStmt->execute([$companyId, $data['service_id'], $data['staff_id']]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        $stmt = $db->prepare("
            UPDATE staff_services 
            SET is_active = 1, 
                hourly_rate = ?,
                max_daily_bookings = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['hourly_rate'] ?? null,
            $data['max_daily_bookings'] ?? 5,
            $existing['id']
        ]);
        $message = 'Ansatt-tilknytning oppdatert';
    } else {
        $stmt = $db->prepare("
            INSERT INTO staff_services (company_id, staff_id, service_id, is_active, hourly_rate, max_daily_bookings)
            VALUES (?, ?, ?, 1, ?, ?)
        ");
        $stmt->execute([
            $companyId,
            $data['staff_id'],
            $data['service_id'],
            $data['hourly_rate'] ?? null,
            $data['max_daily_bookings'] ?? 5
        ]);
        $message = 'Ansatt tilknyttet tjeneste';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log("Error assigning staff: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved tilknytning: ' . $e->getMessage()
    ]);
}
