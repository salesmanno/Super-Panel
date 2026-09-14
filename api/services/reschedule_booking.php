<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ugyldig metode']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/request_date_time.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !isset($data['new_date']) || !isset($data['new_time'])) {
    echo json_encode(['success' => false, 'message' => 'Mangler booking_id, new_date eller new_time']);
    exit;
}

try {
    $newDate = normalizeRequestDate($data['new_date'], 'Ny dato', false);
    $newTime = normalizeRequestTime($data['new_time'], 'Ny tid', false);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $bookingId = $data['booking_id'];
    $newStaffId = $data['new_staff_id'] ?? null;
    
    $checkStmt = $db->prepare("
        SELECT b.id, b.staff_id, b.service_id, rs.duration_minutes 
        FROM service_bookings b
        LEFT JOIN reservation_services rs ON b.service_id = rs.id
        WHERE b.id = ? AND b.company_id = ?
    ");
    $checkStmt->execute([$bookingId, $companyId]);
    $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking ikke funnet']);
        exit;
    }
    
    $staffId = $newStaffId ?? $booking['staff_id'];
    $duration = $booking['duration_minutes'] ?? 60;
    
    $dayOfWeek = strtolower(date('l', strtotime($newDate)));
    $workingDayStmt = $db->prepare("
        SELECT working_day, start_time, end_time 
        FROM staff_working_hours 
        WHERE staff_id = ? AND day_of_week = ?
    ");
    $workingDayStmt->execute([$staffId, $dayOfWeek]);
    $workingHours = $workingDayStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$workingHours || !$workingHours['working_day']) {
        echo json_encode(['success' => false, 'message' => 'Behandler jobber ikke denne dagen']);
        exit;
    }
    
    $newTimeSeconds = strtotime($newTime);
    $startTimeSeconds = strtotime($workingHours['start_time']);
    $endTimeSeconds = strtotime($workingHours['end_time']);
    
    if ($newTimeSeconds < $startTimeSeconds) {
        echo json_encode(['success' => false, 'message' => 'Tidspunkt er før arbeidstid starter']);
        exit;
    }
    
    $endTime = date('H:i:s', $newTimeSeconds + ($duration * 60));
    $endTimeBookingSeconds = strtotime($endTime);
    
    if ($endTimeBookingSeconds > $endTimeSeconds) {
        echo json_encode(['success' => false, 'message' => 'Timen vil overstige arbeidstiden (slutter ' . substr($workingHours['end_time'], 0, 5) . ')']);
        exit;
    }
    
    $conflictStmt = $db->prepare("
        SELECT id FROM service_bookings 
        WHERE staff_id = ? 
        AND booking_date = ? 
        AND id != ?
        AND status NOT IN ('cancelled', 'completed')
        AND (
            (start_time <= ? AND end_time > ?)
            OR (start_time < ? AND end_time >= ?)
            OR (start_time >= ? AND end_time <= ?)
        )
    ");
    $conflictStmt->execute([
        $staffId, $newDate, $bookingId,
        $newTime, $newTime,
        $endTime, $endTime,
        $newTime, $endTime
    ]);
    
    if ($conflictStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Behandler har allerede en booking på dette tidspunktet']);
        exit;
    }
    
    $updateStmt = $db->prepare("
        UPDATE service_bookings 
        SET booking_date = ?, start_time = ?, end_time = ?, staff_id = ?, updated_at = NOW() 
        WHERE id = ? AND company_id = ?
    ");
    $updateStmt->execute([$newDate, $newTime, $endTime, $staffId, $bookingId, $companyId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking flyttet til ' . date('d.m.Y', strtotime($newDate)) . ' kl ' . substr($newTime, 0, 5)
    ]);
    
} catch (Exception $e) {
    error_log("Error rescheduling booking: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved flytting: ' . $e->getMessage()
    ]);
}
