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
    
    if (empty($data['staff_id']) || empty($data['date'])) {
        echo json_encode(['success' => false, 'message' => 'Ansatt-ID og dato er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $checkStmt = $db->prepare("
        SELECT id FROM staff_availability 
        WHERE company_id = ? AND staff_id = ? AND date = ?
    ");
    $checkStmt->execute([$companyId, $data['staff_id'], $data['date']]);
    $existing = $checkStmt->fetch();
    
    $dayOfWeek = date('w', strtotime($data['date']));
    
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
            $data['start_time'] ?? '09:00:00',
            $data['end_time'] ?? '17:00:00',
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
            $data['date'],
            $dayOfWeek,
            $data['start_time'] ?? '09:00:00',
            $data['end_time'] ?? '17:00:00',
            isset($data['is_working_day']) ? ($data['is_working_day'] ? 1 : 0) : 1,
            $data['availability_type'] ?? 'standard',
            $data['notes'] ?? null
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tilgjengelighet oppdatert'
    ]);
    
} catch (Exception $e) {
    error_log("Error setting availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
