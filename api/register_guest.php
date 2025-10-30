<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['event_id']) || !isset($input['fornavn']) || !isset($input['etternavn'])) {
    echo json_encode(['success' => false, 'message' => 'Manglende påkrevde felt']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    $admin_id = $_SESSION['tenant_admin_id'];
    
    $event_id = $input['event_id'];
    $fornavn = trim($input['fornavn']);
    $etternavn = trim($input['etternavn']);
    $ticket_price = isset($input['ticket_price']) ? floatval($input['ticket_price']) : 0;
    $payment_method = isset($input['payment_method']) ? $input['payment_method'] : 'cash';
    $payment_status = isset($input['payment_status']) ? $input['payment_status'] : 'paid';
    
    // Verify event belongs to company
    $stmt = $db->prepare("SELECT id FROM events_eventdata WHERE id = ? AND company_id = ?");
    $stmt->execute([$event_id, $company_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Generate UUID
    $dbType = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($dbType === 'pgsql') {
        $stmt = $db->query("SELECT gen_random_uuid()");
        $checkin_id = $stmt->fetchColumn();
    } else {
        $checkin_id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    // Combine fornavn and etternavn
    $attendee_name = $fornavn . ' ' . $etternavn;
    
    // Insert guest into events_checkin
    if ($dbType === 'pgsql') {
        $stmt = $db->prepare("
            INSERT INTO events_checkin 
            (id, event_id, attendee_name, ticket_type, ticket_price, 
             payment_status, payment_method, checked_in, checked_in_by, created_at, updated_at) 
            VALUES (?, ?, ?, 'walk-in', ?, ?, ?, TRUE, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $checkin_id,
            $event_id,
            $attendee_name,
            $ticket_price,
            $payment_status,
            $payment_method,
            $admin_id
        ]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO events_checkin 
            (id, event_id, attendee_name, ticket_type, ticket_price, 
             payment_status, payment_method, checked_in, checked_in_by, created_at, updated_at) 
            VALUES (?, ?, ?, 'walk-in', ?, ?, ?, 1, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $checkin_id,
            $event_id,
            $attendee_name,
            $ticket_price,
            $payment_status,
            $payment_method,
            $admin_id
        ]);
    }
    
    // If payment is completed (paid), create unified_ledger entry
    if ($payment_status === 'paid' && $ticket_price > 0) {
        require_once '../includes/UnifiedLedgerService.php';
        
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
            'payment_method' => $payment_method,
            'payment_status' => 'completed',
            'status' => 'active',
            'metadata' => [
                'event_id' => $event_id,
                'checkin_id' => $checkin_id,
                'guest_type' => 'walk-in'
            ]
        ]);
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
