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
    
    if (empty($data['event_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Get event details and pricing
    $stmt = $db->prepare("
        SELECT ticket_price, member_ticket_price, is_member_exclusive
        FROM events_eventdata
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event ikke funnet']);
        exit;
    }
    
    // Determine ticket price based on customer type
    $ticketType = $data['ticket_type'] ?? 'regular';
    $ticketPrice = $event['ticket_price'];
    
    if ($ticketType === 'member' && $event['member_ticket_price'] !== null) {
        $ticketPrice = $event['member_ticket_price'];
    }
    
    // Create check-in record
    $checkinId = bin2hex(random_bytes(16));
    
    $stmt = $db->prepare("
        INSERT INTO events_checkin (
            id,
            event_id,
            customer_id,
            attendee_name,
            attendee_email,
            attendee_phone,
            ticket_type,
            ticket_price,
            payment_status,
            payment_method,
            paid_at,
            checked_in,
            check_in_time,
            checked_in_by,
            notes,
            created_by
        ) VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            TRUE,
            NOW(),
            ?,
            ?,
            ?
        )
    ");
    
    $paymentStatus = ($data['payment_method'] === 'cash' && isset($data['paid'])) ? 'paid' : 'pending';
    $paidAt = ($paymentStatus === 'paid') ? date('Y-m-d H:i:s') : null;
    
    $stmt->execute([
        $checkinId,
        $data['event_id'],
        $data['customer_id'] ?? null,
        $data['attendee_name'] ?? null,
        $data['attendee_email'] ?? null,
        $data['attendee_phone'] ?? null,
        $ticketType,
        $ticketPrice,
        $paymentStatus,
        $data['payment_method'] ?? 'cash',
        $paidAt,
        $_SESSION['tenant_admin_id'],
        $data['notes'] ?? null,
        $_SESSION['tenant_admin_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Check-in registrert',
        'checkin_id' => $checkinId,
        'ticket_price' => $ticketPrice,
        'payment_status' => $paymentStatus
    ]);
    
} catch (Exception $e) {
    error_log("Error checking in: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved check-in: ' . $e->getMessage()
    ]);
}
