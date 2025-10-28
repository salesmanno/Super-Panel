<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$eventId = $_GET['event_id'] ?? null;

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get registrations (service bookings) that haven't been checked in yet
    // Note: service_bookings stores customer data directly (no customer_id foreign key)
    $sql = "SELECT 
                sb.id as booking_id,
                sb.customer_name,
                sb.customer_email,
                sb.customer_phone,
                sb.selling_price as unit_price,
                1 as quantity,
                sb.payment_method,
                sb.payment_status,
                sb.created_at
            FROM service_bookings sb
            WHERE sb.service_name LIKE CONCAT('%', (SELECT title FROM events_eventdata WHERE id = ?), '%')
            AND sb.company_id = ?
            AND sb.id NOT IN (
                SELECT booking_id 
                FROM events_checkin 
                WHERE event_id = ? AND booking_id IS NOT NULL
            )
            ORDER BY sb.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$eventId, $_SESSION['company_id'], $eventId]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'registrations' => $registrations
    ]);
    
} catch (Exception $e) {
    error_log("Get pending registrations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil']);
}
