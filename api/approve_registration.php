<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/BarcodeService.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['registration_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påmeldings-ID']);
        exit;
    }
    
    $db = getDBConnection();
    $registrationId = $data['registration_id'];
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    
    // Fetch registration data
    $regStmt = $db->prepare("
        SELECT er.*, ee.ticket_price, ee.default_payment_method, emp.ticket_price as member_ticket_price, mp.name as membership_plan_name
        FROM event_registrations er
        JOIN events_eventdata ee ON er.event_id = ee.id
        LEFT JOIN event_membership_plans emp ON ee.id = emp.event_id AND er.membership_plan_id = emp.membership_plan_id
        LEFT JOIN membership_plans mp ON er.membership_plan_id = mp.id
        WHERE er.id = ? AND er.company_id = ? AND er.approval_status = 'pending'
    ");
    $regStmt->execute([$registrationId, $companyId]);
    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        echo json_encode(['success' => false, 'message' => 'Fant ikke påmelding eller allerede behandlet']);
        exit;
    }
    
    // Update registration approval status
    $updateStmt = $db->prepare("
        UPDATE event_registrations
        SET 
            approval_status = 'approved',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$adminId, $registrationId]);
    
    // Create events_checkin entry
    $checkinId = bin2hex(random_bytes(16));
    $barcodeService = new BarcodeService();
    $barcode = $barcodeService->generateEAN13($checkinId, $registration['event_id']);
    
    // Determine ticket type and price - use membership plan name if available
    $ticketType = $registration['membership_plan_name'] ?? 'Ordinær'; // Membership plan name as ticket type
    $ticketPrice = $registration['member_ticket_price'] ?? $registration['ticket_price'] ?? 0;
    $paymentStatus = $registration['payment_status'] ?? 'pending';
    $paymentMethod = $registration['payment_method'] ?? $registration['default_payment_method'] ?? 'cash';
    
    // Combine first and last name into attendee_name
    $attendeeName = trim(($registration['first_name'] ?? '') . ' ' . ($registration['last_name'] ?? ''));
    
    $checkinStmt = $db->prepare("
        INSERT INTO events_checkin 
        (id, event_id, ticket_type, ticket_price, payment_status, payment_method, 
         checked_in, checked_in_by, barcode, membership_plan_id, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())
    ");
    $checkinStmt->execute([
        $checkinId,
        $registration['event_id'],
        $ticketType,
        $ticketPrice,
        $paymentStatus,
        $paymentMethod,
        $adminId,
        $barcode,
        $registration['membership_plan_id'] ?? null
    ]);
    
    // Log transaction if payment is completed
    if ($paymentStatus === 'completed' && $ticketPrice > 0) {
        $ledgerService = new UnifiedLedgerService($db, $companyId);
        $attendeeName = trim($registration['first_name'] . ' ' . $registration['last_name']);
        $ledgerService->logTransaction([
            'order_id' => $checkinId,
            'module_source' => 'events',
            'item_type' => 'event',
            'source_id' => $registration['event_id'],
            'item_name' => 'Event Registration - ' . $ticketType,
            'quantity' => 1,
            'selling_price' => $ticketPrice,
            'cost_price' => 0,
            'customer_name' => $attendeeName,
            'customer_email' => $registration['email'],
            'payment_method' => $paymentMethod,
            'payment_status' => 'completed',
            'status' => 'active',
            'metadata' => [
                'event_id' => $registration['event_id'],
                'checkin_id' => $checkinId,
                'registration_id' => $registrationId,
                'membership_plan_id' => $registration['membership_plan_id']
            ]
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Påmelding godkjent og innsjekket',
        'checkin_id' => $checkinId
    ]);
    
} catch (Exception $e) {
    error_log("Error approving registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved godkjenning: ' . $e->getMessage()
    ]);
}
