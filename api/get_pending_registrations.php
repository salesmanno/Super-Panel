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
    $sql = "SELECT 
                sb.id as booking_id,
                sb.customer_id,
                sb.quantity,
                sb.unit_price,
                sb.payment_method,
                sb.payment_status,
                sb.created_at,
                tc.first_name as customer_first_name,
                tc.last_name as customer_last_name,
                tc.email as customer_email,
                tc.phone as customer_phone
            FROM service_bookings sb
            LEFT JOIN tenant_customers tc ON sb.customer_id = tc.id
            WHERE sb.service_type = 'event'
            AND sb.service_id = ?
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
