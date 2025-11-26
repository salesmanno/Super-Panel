<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Validate required fields
    if (empty($data['attendee_first_name']) || empty($data['attendee_last_name'])) {
        echo json_encode(['success' => false, 'message' => 'Fornavn og etternavn er påkrevd']);
        exit;
    }
    
    // Get event details and pricing
    $stmt = $db->prepare("
        SELECT title, start_date, start_time, venue_name, event_city, ticket_price, is_member_exclusive, default_payment_method
        FROM events_eventdata
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event ikke funnet']);
        exit;
    }
    
    // Use default payment method
    $paymentMethod = $data['payment_method'] ?? $event['default_payment_method'] ?? 'cash';
    
    // Determine ticket price based on membership plan
    $ticketType = 'regular';
    $ticketPrice = $event['ticket_price'];
    $membershipPlanId = null;
    
    if (!empty($data['membership_plan_id'])) {
        // Check for membership-specific pricing
        $pricingStmt = $db->prepare("
            SELECT id, ticket_price, mp.name as plan_name
            FROM event_membership_plans emp
            JOIN membership_plans mp ON emp.membership_plan_id = mp.id
            WHERE emp.event_id = ? AND emp.membership_plan_id = ? AND mp.company_id = ?
        ");
        $pricingStmt->execute([$data['event_id'], $data['membership_plan_id'], $_SESSION['company_id']]);
        $pricing = $pricingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pricing) {
            $ticketPrice = $pricing['ticket_price'];
            $ticketType = $pricing['plan_name'];
            $membershipPlanId = $pricing['id']; // Store FK reference
        }
    }
    
    // Create check-in record
    $checkinId = bin2hex(random_bytes(16));
    
    $stmt = $db->prepare("
        INSERT INTO events_checkin (
            id,
            event_id,
            customer_id,
            attendee_first_name,
            attendee_last_name,
            attendee_email,
            attendee_phone,
            attendee_street,
            attendee_zip,
            attendee_city,
            attendee_postal,
            ticket_type,
            membership_plan_id,
            ticket_price,
            payment_status,
            payment_method,
            checked_in,
            check_in_time,
            checked_in_by,
            notes,
            created_at,
            updated_at
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
            ?,
            ?,
            ?,
            ?,
            ?,
            1,
            NOW(),
            ?,
            ?,
            NOW(),
            NOW()
        )
    ");
    
    // Payment status logic for accurate financial tracking:
    // - on_arrival: Always pending (payment not received yet)
    // - cash: Paid only if explicitly confirmed
    // - other methods: Pending until confirmed
    if ($paymentMethod === 'on_arrival') {
        $paymentStatus = 'pending';  // Never mark as paid - money not received
    } else {
        $paymentStatus = ($paymentMethod === 'cash' && isset($data['paid']) && $data['paid']) ? 'paid' : 'pending';
    }
    
    // Normalize dynamic fields (blank-to-null for consistency)
    $street = $data['field_street'] ?? null;
    $zip = $data['field_zip'] ?? null;
    $city = $data['field_city'] ?? null;
    $postal = $data['field_postal'] ?? null;
    
    $stmt->execute([
        $checkinId,
        $data['event_id'],
        $data['customer_id'] ?? null,
        $data['attendee_first_name'],
        $data['attendee_last_name'],
        $data['attendee_email'] ?? null,
        $data['attendee_phone'] ?? null,
        $street,
        $zip,
        $city,
        $postal,
        $ticketType,
        $membershipPlanId,
        $ticketPrice,
        $paymentStatus,
        $paymentMethod,
        $_SESSION['tenant_admin_id'],
        $data['notes'] ?? null
    ]);
    
    // Log to unified_ledger when payment is completed
    if ($paymentStatus === 'paid') {
        $ledgerService = new UnifiedLedgerService($db, $_SESSION['company_id']);
        
        // Get event cost_price for profit tracking
        $costStmt = $db->prepare("SELECT cost_price FROM events_eventdata WHERE id = ?");
        $costStmt->execute([$data['event_id']]);
        $costResult = $costStmt->fetch(PDO::FETCH_ASSOC);
        $costPrice = ($costResult && $costResult['cost_price']) ? $costResult['cost_price'] : null;
        
        // Generate order ID for this event checkin
        $orderId = 'EVT-' . date('Ymd') . '-' . strtoupper(substr($checkinId, 0, 6));
        
        $attendeeName = trim($data['attendee_first_name'] . ' ' . $data['attendee_last_name']);
        $ledgerService->logTransaction([
            'order_id' => $orderId,
            'module_source' => 'events',
            'item_type' => 'event',
            'source_id' => $checkinId,
            'item_name' => $event['title'],
            'quantity' => 1,
            'selling_price' => $ticketPrice,
            'cost_price' => $costPrice,
            'customer_name' => $attendeeName,
            'customer_email' => $data['attendee_email'] ?? null,
            'payment_method' => $paymentMethod,
            'payment_status' => 'completed',
            'status' => 'active',
            'transaction_date' => date('Y-m-d H:i:s'),
            'metadata' => [
                'event_id' => $data['event_id'],
                'ticket_type' => $ticketType,
                'checkin_id' => $checkinId
            ]
        ]);
    }
    
    // Send confirmation email if attendee email is provided
    $emailSent = false;
    if (!empty($data['attendee_email'])) {
        try {
            $attendeeName = trim($data['attendee_first_name'] . ' ' . $data['attendee_last_name']);
            $emailService = new EmailService($db, $_SESSION['company_id']);
            $emailResult = $emailService->send([
                'template' => 'event_confirmation',
                'to' => $data['attendee_email'],
                'data' => [
                    'customer_name' => $attendeeName,
                    'event_title' => $event['title'],
                    'event_date' => $event['start_date'],
                    'event_time' => $event['start_time'],
                    'event_location' => $event['venue_name'] ?? $event['event_city'] ?? 'Se arrangementdetaljer',
                    'ticket_type' => $ticketType
                ],
                'module_source' => 'events',
                'source_id' => $checkinId
            ]);
            $emailSent = $emailResult['success'];
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Check-in registrert',
        'checkin_id' => $checkinId,
        'ticket_price' => $ticketPrice,
        'payment_status' => $paymentStatus,
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    error_log("Error checking in: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved check-in: ' . $e->getMessage()
    ]);
}
