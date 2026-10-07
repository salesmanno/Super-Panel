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
require_once __DIR__ . '/../includes/active_payment_methods.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    $customerId = $data['customer_id'] ?? null;
    $registrationId = $data['registration_id'] ?? null;
    
    // If registration_id provided, load registration data
    $registrationData = null;
    if (!empty($registrationId)) {
        $regStmt = $db->prepare("
            SELECT er.customer_id, er.number_of_attendees
            FROM event_registrations er
            WHERE er.id = ? AND er.event_id = ? AND er.company_id = ?
        ");
        $regStmt->execute([$registrationId, $data['event_id'], $_SESSION['company_id']]);
        $registrationData = $regStmt->fetch(PDO::FETCH_ASSOC);
        if ($registrationData && !$customerId) {
            $customerId = $registrationData['customer_id'];
        }
    }
    
    // Load customer data if available
    $customerData = null;
    if (!empty($customerId)) {
        $custStmt = $db->prepare("
            SELECT first_name, last_name, email, phone FROM tenant_customers
            WHERE id = ? AND company_id = ?
        ");
        $custStmt->execute([$customerId, $_SESSION['company_id']]);
        $customerData = $custStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Validate required fields - use customer data if available
    $firstName = $data['attendee_first_name'] ?? $customerData['first_name'] ?? '';
    $lastName = $data['attendee_last_name'] ?? $customerData['last_name'] ?? '';
    $attendeeName = trim($firstName . ' ' . $lastName);
    
    if (empty($attendeeName)) {
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
    $paymentMethod = $data['payment_method'] ?? null;
    
    // Determine ticket price based on membership plan
    $ticketPrice = $event['ticket_price'];
    $membershipPlanId = null;
    $ticketType = 'Ingen'; // Default for non-members without specific membership price
    
    if (!empty($data['membership_plan_id'])) {
        // Check for membership-specific pricing
        $pricingStmt = $db->prepare("
            SELECT emp.membership_plan_id, emp.ticket_price, mp.name as plan_name
            FROM event_membership_plans emp
            JOIN membership_plans mp ON emp.membership_plan_id = mp.id
            WHERE emp.event_id = ? AND emp.membership_plan_id = ? AND mp.company_id = ?
        ");
        $pricingStmt->execute([$data['event_id'], $data['membership_plan_id'], $_SESSION['company_id']]);
        $pricing = $pricingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pricing) {
            $ticketPrice = $pricing['ticket_price'];
            $ticketType = $pricing['plan_name']; // Use membership plan name as ticket type
            $membershipPlanId = $pricing['membership_plan_id']; // Store FK reference to membership_plans.id
        }
    }
    
    $db->beginTransaction();
    activePaymentMethodsLock($db,(string)$_SESSION['company_id']);
    $noPayment=($data['is_paying']??null)===false || in_array($paymentMethod,[null,'','free','on_arrival'],true);
    if (($data['is_paying']??null)!==false && (!$noPayment || ($data['is_paying']??false)===true || ($data['paid']??false)===true)) {
        $paymentMethod=requireActiveEventPayment($db,(string)$_SESSION['company_id'],(string)$data['event_id'],$paymentMethod,(float)$ticketPrice);
    } else {
        // A check-in is not proof of receiving payment. Keep a genuine pending
        // path without inventing an activated method or a revenue entry.
        $paymentMethod=null;
    }
    // Create check-in record
    $checkinId = bin2hex(random_bytes(16));
    
    // Generate barcode
    require_once '../includes/BarcodeService.php';
    $barcodeService = new BarcodeService();
    $barcode = $barcodeService->generateEAN13($checkinId, $data['event_id']);
    
    // Note: ticket_type is derived from membership_plan_id FK via JOIN, not stored as column
    $stmt = $db->prepare("
        INSERT INTO events_checkin (
            id,
            event_id,
            customer_id,
            membership_plan_id,
            ticket_price,
            payment_status,
            payment_method,
            checked_in,
            check_in_time,
            checked_in_by,
            barcode,
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
            1,
            NOW(),
            ?,
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
    $paymentStatus=(float)$ticketPrice<=0 ? 'paid' :
        (($paymentMethod==='cash' && ($data['paid']??false)===true) ? 'paid' : 'pending');
    
    $stmt->execute([
        $checkinId,
        $data['event_id'],
        $data['customer_id'] ?? null,
        $membershipPlanId,
        $ticketPrice,
        $paymentStatus,
        $paymentMethod,
        $_SESSION['tenant_admin_id'],
        $barcode,
        $data['notes'] ?? null
    ]);
    
    // Log to unified_ledger when payment is completed
    if ($paymentStatus === 'paid' && (float)$ticketPrice>0) {
        $ledgerService = new UnifiedLedgerService($db, $_SESSION['company_id']);
        
        // Event cost_price was removed; expenses are separate ledger entries.
        $costPrice = null;
        
        // Generate order ID for this event checkin
        $orderId = 'EVT-' . date('Ymd') . '-' . strtoupper(substr($checkinId, 0, 6));
        
        $ledgerResult = $ledgerService->logTransaction([
            'order_id' => $orderId,
            'module_source' => 'events',
            'item_type' => 'event',
            'source_id' => $checkinId,
            'item_name' => $event['title'],
            'quantity' => 1,
            'selling_price' => $ticketPrice,
            'cost_price' => $costPrice,
            'customer_id' => $data['customer_id'] ?? null,
            'payment_method' => $paymentMethod,
            'payment_status' => 'completed',
            'status' => 'active',
            'transaction_date' => date('Y-m-d H:i:s'),
            'transaction_type' => 'income',
            'category' => 'Arrangement',
            'metadata' => [
                'event_id' => $data['event_id'],
                'ticket_type' => $ticketType,
                'checkin_id' => $checkinId,
                'customer_name' => $attendeeName,
                'customer_email' => $data['attendee_email'] ?? null
            ]
        ]);
        if (!$ledgerResult['success']) throw new RuntimeException('Kunne ikke lagre innsjekkingsbetalingen.');

    }
    
    // Send confirmation email if attendee email is provided
    $emailSent = false;
    if (!empty($data['attendee_email'])) {
        try {
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
    
    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Check-in registrert',
        'checkin_id' => $checkinId,
        'ticket_price' => $ticketPrice,
        'payment_status' => $paymentStatus,
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Error checking in: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved check-in: ' . $e->getMessage()
    ]);
}
