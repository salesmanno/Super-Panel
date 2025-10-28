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
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT sb.*, tc.first_name, tc.last_name, tc.email, tc.phone
        FROM service_bookings sb
        LEFT JOIN tenant_customers tc ON sb.customer_id = tc.id
        WHERE sb.id = ? AND sb.company_id = ?
    ");
    $stmt->execute([$bookingId, $_SESSION['company_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Påmelding ikke funnet']);
        exit;
    }
    
    // Get event details for ticket pricing
    $stmt = $db->prepare("SELECT title, ticket_price, member_ticket_price, cost_price FROM events_eventdata WHERE id = ?");
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
    
    $checkinId = $db->lastInsertId();
    
    // Create transaction record for economy module
    try {
        $transactionId = bin2hex(random_bytes(16));
        $totalAmount = $ticketPrice * ($booking['quantity'] ?? 1);
        
        // Insert into sales_transactions
        $stmt = $db->prepare("
            INSERT INTO sales_transactions (
                id,
                company_id,
                transaction_type,
                service_type,
                service_id,
                customer_name,
                customer_email,
                customer_phone,
                total_amount,
                payment_method,
                payment_status,
                created_by,
                created_at
            ) VALUES (?, ?, 'service', 'event', ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $transactionId,
            $_SESSION['company_id'],
            $eventId,
            $customerName,
            $booking['email'] ?? null,
            $booking['phone'] ?? null,
            $totalAmount,
            $booking['payment_method'] ?? 'online',
            $booking['payment_status'] ?? 'pending',
            $_SESSION['tenant_admin_id']
        ]);
        
        // Insert into unified_ledger for centralized financial tracking
        $profitMargin = $totalAmount - (($event['cost_price'] ?? 0) * ($booking['quantity'] ?? 1));
        
        $stmt = $db->prepare("
            INSERT INTO unified_ledger (
                id,
                company_id,
                transaction_type,
                source_module,
                source_id,
                description,
                amount,
                cost,
                profit,
                payment_method,
                payment_status,
                transaction_date,
                created_at
            ) VALUES (?, ?, 'sale', 'events', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $_SESSION['company_id'],
            $checkinId,
            $event['title'] . ' - Quick check-in',
            $totalAmount,
            ($event['cost_price'] ?? 0) * ($booking['quantity'] ?? 1),
            $profitMargin,
            $booking['payment_method'] ?? 'online',
            $booking['payment_status'] ?? 'pending'
        ]);
    } catch (Exception $e) {
        error_log("Transaction creation error: " . $e->getMessage());
        // Don't fail the check-in if transaction fails
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Deltaker sjekket inn',
        'checkin_id' => $checkinId
    ]);
    
} catch (Exception $e) {
    error_log("Quick check-in error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil: ' . $e->getMessage()]);
}
