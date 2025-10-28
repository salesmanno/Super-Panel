<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? null;
$eventId = $input['event_id'] ?? null;

if (!$bookingId || !$eventId) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get booking details and verify it's for this event
    $stmt = $db->prepare("
        SELECT sb.*, tc.first_name, tc.last_name, tc.email, tc.phone
        FROM service_bookings sb
        LEFT JOIN tenant_customers tc ON sb.customer_id = tc.id
        WHERE sb.id = ? 
        AND sb.company_id = ?
        AND sb.service_type = 'event'
        AND sb.service_id = ?
    ");
    $stmt->execute([$bookingId, $_SESSION['company_id'], $eventId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Påmelding ikke funnet eller matcher ikke arrangement']);
        exit;
    }
    
    // Check if already checked in
    $stmt = $db->prepare("SELECT id FROM events_checkin WHERE booking_id = ? AND event_id = ?");
    $stmt->execute([$bookingId, $eventId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Denne påmeldingen er allerede sjekket inn']);
        exit;
    }
    
    // Get event details for ticket pricing
    $stmt = $db->prepare("SELECT ticket_price, member_ticket_price FROM events_eventdata WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Create check-in record
    $customerName = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''));
    $ticketPrice = $booking['unit_price'] ?? $event['ticket_price'];
    
    $sql = "INSERT INTO events_checkin (
        event_id,
        booking_id,
        attendee_name,
        attendee_email,
        attendee_phone,
        ticket_type,
        ticket_price,
        payment_method,
        payment_status,
        amount_paid,
        notes,
        check_in_time,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $eventId,
        $bookingId,
        $customerName,
        $booking['email'] ?? '',
        $booking['phone'] ?? '',
        'regular', // Default to regular ticket
        $ticketPrice,
        $booking['payment_method'] ?? 'online',
        $booking['payment_status'] ?? 'pending',
        $ticketPrice * ($booking['quantity'] ?? 1),
        'Sjekket inn fra påmelding'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Deltaker sjekket inn',
        'checkin_id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Quick check-in error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil: ' . $e->getMessage()]);
}
