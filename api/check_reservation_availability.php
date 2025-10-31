<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['date']) || empty($data['time']) || empty($data['staff_id']) || empty($data['duration'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felter']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Calculate end time based on duration
    $startDateTime = new DateTime($data['date'] . ' ' . $data['time']);
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('PT' . $data['duration'] . 'M'));
    
    $startTime = $startDateTime->format('H:i:s');
    $endTime = $endDateTime->format('H:i:s');
    $date = $data['date'];
    
    // Check for overlapping bookings for this staff member
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM service_bookings
        WHERE company_id = ?
        AND assigned_staff_id = ?
        AND date_from = ?
        AND status NOT IN ('cancelled', 'completed')
        AND (
            (time_from < ? AND time_to > ?) OR
            (time_from >= ? AND time_from < ?) OR
            (time_to > ? AND time_to <= ?)
        )
    ");
    
    $stmt->execute([
        $_SESSION['company_id'],
        $data['staff_id'],
        $date,
        $endTime, $startTime,
        $startTime, $endTime,
        $startTime, $endTime
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAvailable = ($result['count'] == 0);
    
    echo json_encode([
        'success' => true,
        'available' => $isAvailable,
        'message' => $isAvailable ? 'Tidspunktet er ledig' : 'Tidspunktet er opptatt'
    ]);
    
} catch (Exception $e) {
    error_log("Error checking availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved tilgjengelighetssjekk: ' . $e->getMessage()
    ]);
}
