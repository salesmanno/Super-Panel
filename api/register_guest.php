<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['event_id']) || !isset($input['attendee_first_name']) || !isset($input['attendee_last_name'])) {
    echo json_encode(['success' => false, 'message' => 'Fornavn og etternavn er påkrevd']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    $admin_id = $_SESSION['tenant_admin_id'];
    
    $event_id = $input['event_id'];
    $first_name = trim($input['attendee_first_name']);
    $last_name = trim($input['attendee_last_name']);
    $email = isset($input['attendee_email']) ? trim($input['attendee_email']) : null;
    $membership_plan_id = isset($input['membership_plan_id']) ? $input['membership_plan_id'] : null;
    $payment_method = isset($input['payment_method']) ? $input['payment_method'] : 'cash';
    $payment_status = isset($input['payment_status']) ? $input['payment_status'] : 'paid';
    
    // Verify event belongs to company and get pricing info
    $stmt = $db->prepare("SELECT ticket_price, default_payment_method FROM events_eventdata WHERE id = ? AND company_id = ?");
    $stmt->execute([$event_id, $company_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Determine ticket price based on membership plan
    $ticket_type = 'walk-in';
    $ticket_price = $event['ticket_price'];
    
    if ($membership_plan_id) {
        $pricingStmt = $db->prepare("
            SELECT emp.ticket_price, mp.name as plan_name
            FROM event_membership_plans emp
            JOIN membership_plans mp ON emp.membership_plan_id = mp.id
            WHERE emp.event_id = ? AND emp.membership_plan_id = ? AND mp.company_id = ?
        ");
        $pricingStmt->execute([$event_id, $membership_plan_id, $company_id]);
        $pricing = $pricingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pricing) {
            $ticket_price = $pricing['ticket_price'];
            $ticket_type = $pricing['plan_name'];
        }
    }
    
    // Use default payment method
    if (!$payment_method) {
        $payment_method = $event['default_payment_method'] ?? 'cash';
    }
    
    // Generate UUID
    $checkin_id = bin2hex(random_bytes(16));
    
    // Combine first and last name for storage (consistent with checkin_event.php)
    $attendee_name = trim($first_name . ' ' . $last_name);
    
    // Insert guest into events_checkin
    $stmt = $db->prepare("
        INSERT INTO events_checkin 
        (id, company_id, event_id, attendee_name, attendee_email,
         ticket_type, ticket_price, payment_status, payment_method, 
         checked_in, check_in_time, checked_in_by, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, NOW(), NOW())
    ");
    $stmt->execute([
        $checkin_id,
        $company_id,
        $event_id,
        $attendee_name,
        $email,
        $ticket_type,
        $ticket_price,
        $payment_status,
        $payment_method,
        $admin_id
    ]);
    
    // If payment is completed (paid), create unified_ledger entry and update events_results
    if ($payment_status === 'paid' && $ticket_price > 0) {
        require_once '../includes/UnifiedLedgerService.php';
        require_once '../includes/EventResultsService.php';
        
        $ledgerService = new UnifiedLedgerService($db, $company_id);
        $ledgerService->logTransaction([
            'order_id' => $checkin_id,
            'module_source' => 'events',
            'item_type' => 'event',
            'source_id' => $event_id,
            'item_name' => 'Walk-in Registration',
            'quantity' => 1,
            'selling_price' => $ticket_price,
            'cost_price' => 0,
            'customer_name' => $attendee_name,
            'customer_email' => $email,
            'payment_method' => $payment_method,
            'payment_status' => 'completed',
            'status' => 'active',
            'metadata' => [
                'event_id' => $event_id,
                'checkin_id' => $checkin_id,
                'guest_type' => 'walk-in',
                'membership_plan_id' => $membership_plan_id
            ]
        ]);
        
        // Update events_results with ticket revenue by payment method
        $resultsService = new EventResultsService($db, $company_id);
        $resultsService->addTicketRevenue($event_id, $payment_method, $ticket_price, $ticket_type);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Gjest registrert',
        'checkin_id' => $checkin_id
    ]);
    
} catch (Exception $e) {
    error_log("Error registering guest: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke registrere gjest: ' . $e->getMessage()
    ]);
}
