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
    
    if (empty($data['staff_id']) || empty($data['dates'])) {
        echo json_encode(['success' => false, 'message' => 'Ansatt-ID og datoer er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $db->beginTransaction();
    
    $deleteStmt = $db->prepare("
        DELETE FROM staff_availability 
        WHERE company_id = ? AND staff_id = ? AND date = ?
    ");
    
    $insertStmt = $db->prepare("
        INSERT INTO staff_availability (
            company_id, staff_id, date, day_of_week,
            start_time, end_time, is_working_day, availability_type, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($data['dates'] as $dateEntry) {
        $date = $dateEntry['date'];
        $dayOfWeek = date('w', strtotime($date));
        
        $deleteStmt->execute([$companyId, $data['staff_id'], $date]);
        
        $insertStmt->execute([
            $companyId,
            $data['staff_id'],
            $date,
            $dayOfWeek,
            $dateEntry['start_time'] ?? $data['default_start_time'] ?? '09:00:00',
            $dateEntry['end_time'] ?? $data['default_end_time'] ?? '17:00:00',
            isset($dateEntry['is_working_day']) ? ($dateEntry['is_working_day'] ? 1 : 0) : 1,
            $dateEntry['availability_type'] ?? $data['availability_type'] ?? 'standard',
            $dateEntry['notes'] ?? $data['notes'] ?? null
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => count($data['dates']) . ' dager oppdatert'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error bulk availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
