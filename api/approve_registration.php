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
    $approvalReason = $data['approval_reason'] ?? 'manual approval';
    
    // Fetch registration data with customer info (base data)
    $regStmt = $db->prepare("
        SELECT er.*, ee.ticket_price AS event_ticket_price, ee.default_payment_method,
               c.first_name, c.last_name, c.email, c.phone, c.is_member, c.membership_plan_id AS customer_membership_plan_id
        FROM event_registrations er
        JOIN events_eventdata ee ON er.event_id = ee.id
        JOIN tenant_customers c ON er.customer_id = c.id
        WHERE er.id = ? AND er.company_id = ? AND er.approval_status = 'pending'
    ");
    $regStmt->execute([$registrationId, $companyId]);
    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        echo json_encode(['success' => false, 'message' => 'Fant ikke påmelding eller allerede behandlet']);
        exit;
    }
    
    // Determine membership pricing - only if customer is a member AND has a valid membership plan
    $membershipPriceInfo = null;
    if ($registration['is_member'] && $registration['customer_membership_plan_id']) {
        // Check if this event has a special price for the customer's membership plan
        $mpStmt = $db->prepare("
            SELECT emp.ticket_price, emp.membership_plan_id, mp.plan_name
            FROM event_membership_plans emp
            JOIN membership_plans mp ON emp.membership_plan_id = mp.id
            WHERE emp.event_id = ? AND emp.membership_plan_id = ?
        ");
        $mpStmt->execute([$registration['event_id'], $registration['customer_membership_plan_id']]);
        $membershipPriceInfo = $mpStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $db->beginTransaction();
    
    try {
        // Update registration approval status
        $updateStmt = $db->prepare("
            UPDATE event_registrations
            SET 
                approval_status = 'approved',
                approval_reason = ?,
                approved_by = ?,
                approved_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$approvalReason, $adminId, $registrationId]);
        
        // Create events_checkin entry
        $checkinId = bin2hex(random_bytes(16));
        $barcodeService = new BarcodeService();
        $barcode = $barcodeService->generateEAN13($checkinId, $registration['event_id']);
        
        // Determine ticket type and price based on membership
        // Only use membership price if customer is a member AND event has pricing for their specific plan
        $isMember = $registration['is_member'] && $membershipPriceInfo !== null;
        $ticketType = $isMember ? ($membershipPriceInfo['plan_name'] ?? 'member') : 'regular';
        $ticketPrice = $isMember 
            ? floatval($membershipPriceInfo['ticket_price'])
            : floatval($registration['event_ticket_price'] ?? 0);
        
        $paymentStatus = $registration['payment_status'] ?? 'pending';
        $paymentMethod = $registration['payment_method'] ?? $registration['default_payment_method'] ?? 'cash';
        $membershipPlanId = $isMember ? $membershipPriceInfo['membership_plan_id'] : null;
        
        // Insert into events_checkin with checked_in=0 (will be checked in later)
        $checkinStmt = $db->prepare("
            INSERT INTO events_checkin 
            (id, event_id, customer_id, registration_id, ticket_type, ticket_price, 
             payment_status, payment_method, checked_in, checked_in_by, check_in_time,
             barcode, membership_plan_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NULL, NULL, ?, ?, NOW(), NOW())
        ");
        $checkinStmt->execute([
            $checkinId,
            $registration['event_id'],
            $registration['customer_id'],
            $registrationId,
            $ticketType,
            $ticketPrice,
            $paymentStatus,
            $paymentMethod,
            $barcode,
            $membershipPlanId
        ]);
        
        $db->commit();
        
        // Log transaction if payment is completed (outside transaction for safety)
        if ($paymentStatus === 'completed' && $ticketPrice > 0) {
            try {
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
                        'registration_id' => $registrationId
                    ]
                ]);
            } catch (Exception $ledgerError) {
                error_log("Warning: Ledger logging failed: " . $ledgerError->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding godkjent - innsjekking opprettet',
            'checkin_id' => $checkinId,
            'barcode' => $barcode,
            'ticket_type' => $ticketType,
            'ticket_price' => $ticketPrice
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error approving registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved godkjenning: ' . $e->getMessage()
    ]);
}
