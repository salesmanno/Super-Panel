<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/request_date_time.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['staff_id']) || empty($data['date'])) {
        echo json_encode(['success' => false, 'message' => 'Ansatt-ID og dato er påkrevd']);
        exit;
    }

    $normalizedDate = normalizeRequestDate($data['date'], 'Dato', false);
    $normalizedStartTime = normalizeRequestTime($data['start_time'] ?? '09:00:00', 'Starttid', false);
    $normalizedEndTime = normalizeRequestTime($data['end_time'] ?? '17:00:00', 'Sluttid', false);
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $verifyStaff = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
    $verifyStaff->execute([$data['staff_id'], $companyId]);
    if (!$verifyStaff->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ansatt ikke funnet']);
        exit;
    }
    
    $checkStmt = $db->prepare("
        SELECT id FROM staff_availability 
        WHERE company_id = ? AND staff_id = ? AND date = ?
    ");
    $checkStmt->execute([$companyId, $data['staff_id'], $normalizedDate]);
    $existing = $checkStmt->fetch();
    
    $dayOfWeek = date('w', strtotime($normalizedDate));
    
    if ($existing) {
        $stmt = $db->prepare("
            UPDATE staff_availability SET
                start_time = ?,
                end_time = ?,
                is_working_day = ?,
                availability_type = ?,
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $normalizedStartTime,
            $normalizedEndTime,
            isset($data['is_working_day']) ? ($data['is_working_day'] ? 1 : 0) : 1,
            $data['availability_type'] ?? 'standard',
            $data['notes'] ?? null,
            $existing['id']
        ]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO staff_availability (
                company_id, staff_id, date, day_of_week,
                start_time, end_time, is_working_day, availability_type, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $companyId,
            $data['staff_id'],
            $normalizedDate,
            $dayOfWeek,
            $normalizedStartTime,
            $normalizedEndTime,
            isset($data['is_working_day']) ? ($data['is_working_day'] ? 1 : 0) : 1,
            $data['availability_type'] ?? 'standard',
            $data['notes'] ?? null
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tilgjengelighet oppdatert'
    ]);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error setting availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
