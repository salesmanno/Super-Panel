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
    $attendee_name = trim($first_name . ' ' . $last_name);
    $email = isset($input['attendee_email']) ? trim($input['attendee_email']) : null;
    $membership_plan_id = isset($input['membership_plan_id']) ? $input['membership_plan_id'] : null;
    $payment_method = isset($input['payment_method']) ? $input['payment_method'] : 'cash';
    $payment_status = isset($input['payment_status']) ? $input['payment_status'] : 'paid';
    $number_of_people = isset($input['number_of_people']) ? (int)$input['number_of_people'] : 1;
    
    // Validate number_of_people
    if ($number_of_people < 1 || $number_of_people > 10) {
        echo json_encode(['success' => false, 'message' => 'Antall personer må være mellom 1 og 10']);
        exit;
    }
    
    // Verify event belongs to company and get pricing info
    $stmt = $db->prepare("SELECT ticket_price, default_payment_method FROM events_eventdata WHERE id = ? AND company_id = ?");
    $stmt->execute([$event_id, $company_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Check for duplicate registration by email (if email provided and only one person)
    if ($email && $number_of_people === 1) {
        $dupStmt = $db->prepare("
            SELECT ec.id, ec.checked_in, ec.payment_status
            FROM events_checkin ec
            LEFT JOIN tenant_customers tc ON ec.customer_id = tc.id
            WHERE ec.event_id = ? AND (tc.email = ? OR ec.notes LIKE ?)
        ");
        $dupStmt->execute([$event_id, $email, "%{$email}%"]);
        $existingCheckin = $dupStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingCheckin) {
            $statusText = $existingCheckin['checked_in'] == 1 ? 'innsjekket' :
                          ($existingCheckin['payment_status'] === 'paid' ? 'godkjent' :
                          ($existingCheckin['payment_status'] === 'pending' ? 'ventende' : 'registrert'));
            echo json_encode([
                'success' => false,
                'is_duplicate' => true,
                'existing_status' => $existingCheckin['payment_status'],
                'existing_status_text' => $statusText,
                'message' => "Deltaker med denne e-posten er allerede registrert (status: {$statusText})"
            ]);
            exit;
        }
    }
    
    // Determine ticket price based on membership plan
    $ticket_type = 'regular'; // Default for walk-in/non-members without specific membership price
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
            $ticket_type = $pricing['plan_name']; // Use membership plan name as ticket type
        }
    }
    
    // Use default payment method
    if (!$payment_method) {
        $payment_method = $event['default_payment_method'] ?? 'cash';
    }
    
    // Generate barcode
    require_once '../includes/BarcodeService.php';
    $barcodeService = new BarcodeService();
    
    require_once '../includes/UnifiedLedgerService.php';
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    
    // Loop through number_of_people and create separate registrations
    $createdIds = [];
    for ($i = 0; $i < $number_of_people; $i++) {
        // Generate UUID for each person
        $checkin_id = bin2hex(random_bytes(16));
        $barcode = $barcodeService->generateEAN13($checkin_id, $event_id);
        
        // Insert guest into events_checkin with membership_plan_id
        // Note: ticket_type is derived from membership_plan_id FK via JOIN, not stored as column
        $stmt = $db->prepare("
            INSERT INTO events_checkin 
            (id, event_id, ticket_price, payment_status, payment_method, 
             checked_in, checked_in_by, barcode, membership_plan_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $checkin_id,
            $event_id,
            $ticket_price,
            $payment_status,
            $payment_method,
            $admin_id,
            $barcode,
            $membership_plan_id
        ]);
        
        $createdIds[] = $checkin_id;
        
        // If payment is completed (paid), create unified_ledger entry
        // Note: Walk-in guests may not have customer_id, store name/email in metadata
        if ($payment_status === 'paid' && $ticket_price > 0) {
            $attendee_name = trim($first_name . ' ' . $last_name);
            $ledgerService->logTransaction([
                'order_id' => $checkin_id,
                'module_source' => 'events',
                'item_type' => 'event',
                'source_id' => $event_id,
                'item_name' => 'Walk-in Registration',
                'quantity' => 1,
                'selling_price' => $ticket_price,
                'cost_price' => 0,
                'customer_id' => null,
                'payment_method' => $payment_method,
                'payment_status' => 'completed',
                'status' => 'active',
                'metadata' => [
                    'event_id' => $event_id,
                    'checkin_id' => $checkin_id,
                    'guest_type' => 'walk-in',
                    'membership_plan_id' => $membership_plan_id,
                    'group_booking' => $number_of_people > 1 ? 'yes' : 'no',
                    'customer_name' => $attendee_name,
                    'customer_email' => $email
                ]
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $number_of_people > 1 ? "{$number_of_people} gjester registrert" : 'Gjest registrert',
        'checkin_ids' => $createdIds
    ]);
    
} catch (Exception $e) {
    error_log("Error registering guest: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke registrere gjest: ' . $e->getMessage()
    ]);
}
