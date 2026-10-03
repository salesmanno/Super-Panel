<?php
/**
 * Perform Check-in API
 * Handles checking in attendees with payment processing and no-show marking
 * Logs all actions to audit_logs
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';
require_once __DIR__ . '/../includes/tenant_audit.php';

/**
 * Get human-readable payment method name
 */
function getPaymentMethodName($method) {
    $names = [
        'cash' => 'kontant',
        'card' => 'kort',
        'vipps' => 'Vipps',
        'invoice' => 'faktura',
        'bank_transfer' => 'bankoverføring',
        'on_arrival' => 'ved oppmøte',
        'free' => 'gratis'
    ];
    return $names[$method] ?? $method;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['checkin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler checkin_id']);
        exit;
    }
    
    $db = getDBConnection();
    $checkinId = $data['checkin_id'];
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    $isNoShow = isset($data['no_show']) && $data['no_show'] === true;
    $paymentMethod = $data['payment_method'] ?? 'cash';
    $skipPayment = isset($data['skip_payment']) && $data['skip_payment'] === true;
    $preservePaymentMethod = isset($data['preserve_payment_method']) && $data['preserve_payment_method'] === true;
    
    $auditActor = tenantAuditActor($db, (string) $companyId);
    $adminName = $auditActor['name'];
    $db->beginTransaction();
    
    // Get checkin record with customer and event info
    // Use membership_plan_id FK instead of ticket_type column
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.event_id,
            c.customer_id,
            c.membership_plan_id,
            c.ticket_price,
            c.payment_status,
            c.payment_method,
            c.checked_in,
            c.barcode,
            tc.id as tenant_customer_id,
            tc.first_name,
            tc.last_name,
            tc.email as customer_email,
            ee.title as event_title,
            ee.company_id,
            mp.name as membership_plan_name
        FROM events_checkin c
        LEFT JOIN tenant_customers tc ON c.customer_id = tc.id
        JOIN events_eventdata ee ON c.event_id = ee.id
        LEFT JOIN membership_plans mp ON c.membership_plan_id = mp.id
        WHERE c.id = ? AND ee.company_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$checkinId, $companyId]);
    $checkin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$checkin) {
        $db->rollBack();
        tenantAuditRecord($db, $companyId, 'events', 'EVENT_CHECKIN', 'events_checkin',
            (string) $checkinId, 'Innsjekking ikke tilgjengelig', [], [], 'denied', [], $auditActor);
        echo json_encode(['success' => false, 'message' => 'Innsjekking ikke funnet']);
        exit;
    }
    
    if ($checkin['checked_in'] == 1 && !$isNoShow) {
        $db->rollBack();
        tenantAuditRecord($db, $companyId, 'events', 'EVENT_CHECKIN', 'events_checkin',
            (string) $checkinId, (string) $checkin['event_title'], [], [], 'noop', [], $auditActor);
        echo json_encode(['success' => false, 'message' => 'Allerede sjekket inn']);
        exit;
    }
    
    $customerName = trim(($checkin['first_name'] ?? '') . ' ' . ($checkin['last_name'] ?? ''));
    if (empty($customerName)) {
        $customerName = 'Ukjent kunde';
    }
    
    $ticketPrice = floatval($checkin['ticket_price']);
    
    try {
        if ($isNoShow) {
            // Mark as no-show: checked_in=1, payment_status='no_show', no payment recorded
            $updateStmt = $db->prepare("
                UPDATE events_checkin 
                SET 
                    checked_in = 1, 
                    check_in_time = NOW(),
                    checked_in_by = ?,
                    payment_status = 'no_show',
                    notes = CONCAT(IFNULL(notes, ''), '\nNo-show markert av ', ?, ' den ', NOW()),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$adminId, $adminName, $checkinId]);
            
            // Audit log for no-show
            $auditAction = "NO_SHOW_MARKED";
            $auditMessage = "{$adminName} markerte {$customerName} som no-show for arrangement '{$checkin['event_title']}'.";
            
        } else {
            // Regular check-in with payment
            // If preservePaymentMethod is set and already paid, keep existing payment info
            if ($preservePaymentMethod && $checkin['payment_status'] === 'paid') {
                $newPaymentStatus = 'paid';
                $newPaymentMethod = $checkin['payment_method'];
            } else {
                $newPaymentStatus = $skipPayment ? 'pending' : 'paid';
                $newPaymentMethod = $paymentMethod;
            }
            
            $updateStmt = $db->prepare("
                UPDATE events_checkin 
                SET 
                    checked_in = 1, 
                    check_in_time = NOW(),
                    checked_in_by = ?,
                    payment_status = ?,
                    payment_method = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$adminId, $newPaymentStatus, $newPaymentMethod, $checkinId]);
            
            // Log to unified ledger only when attendee was previously unpaid/pending
            // (forhåndsbetalte har allerede en ledger-oppføring og skal ikke dobbelregistreres)
            if ($checkin['payment_status'] !== 'paid' && $newPaymentStatus === 'paid' && $ticketPrice > 0) {
                $ledgerService = new UnifiedLedgerService($db, $companyId);
                
                $orderId = 'EVT-' . date('Ymd') . '-' . strtoupper(substr($checkinId, 0, 6));
                
                // Derive ticket_type from membership_plan_name or default to 'Ordinær'
                $ticketType = $checkin['membership_plan_name'] ?? 'Ordinær';
                
                $ledgerService->logTransaction([
                    'order_id' => $orderId,
                    'module_source' => 'events',
                    'item_type' => 'event',
                    'source_id' => $checkinId,
                    'item_name' => $checkin['event_title'],
                    'quantity' => 1,
                    'selling_price' => $ticketPrice,
                    'cost_price' => 0,
                    'customer_id' => $checkin['customer_id'],
                    'payment_method' => $newPaymentMethod,
                    'payment_status' => 'completed',
                    'status' => 'active',
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'transaction_type' => 'income',
                    'category' => 'Arrangement',
                    'metadata' => [
                        'event_id' => $checkin['event_id'],
                        'ticket_type' => $ticketType,
                        'checkin_id' => $checkinId,
                        'customer_name' => $customerName,
                        'customer_email' => $checkin['customer_email']
                    ]
                ]);
            }
            
            // Audit log for check-in
            $auditAction = "EVENT_CHECKIN";
            $paymentMethodName = getPaymentMethodName($newPaymentMethod);
            
            if ($newPaymentStatus === 'paid' && $ticketPrice > 0) {
                $auditMessage = "{$adminName} sjekket inn {$customerName} mot betaling på {$ticketPrice} kr gjennom {$paymentMethodName}.";
            } else if ($skipPayment) {
                $auditMessage = "{$adminName} sjekket inn {$customerName} uten betaling (venter betaling på {$ticketPrice} kr).";
            } else {
                $auditMessage = "{$adminName} sjekket inn {$customerName} (gratis billett).";
            }
        }
        
        $auditAfter = $checkin;
        $auditAfter['checked_in'] = 1;
        $auditAfter['payment_status'] = $isNoShow ? 'no_show' : $newPaymentStatus;
        $auditAfter['payment_method'] = $isNoShow ? $checkin['payment_method'] : $newPaymentMethod;
        tenantAuditChange($db, $companyId, 'events', $auditAction, 'events_checkin',
            (string) $checkinId, (string) $checkin['event_title'], $checkin, $auditAfter, [], $auditActor);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $isNoShow ? 'Markert som no-show' : 'Innsjekket',
            'checkin_id' => $checkinId,
            'customer_name' => $customerName,
            'ticket_price' => $ticketPrice,
            'payment_status' => $isNoShow ? 'no_show' : ($skipPayment ? 'pending' : 'paid'),
            'is_no_show' => $isNoShow,
            'audit_message' => $auditMessage
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    if (isset($db, $auditActor, $companyId)) {
        tenantAuditFailure($db, $companyId, 'events', 'EVENT_CHECKIN', 'events_checkin',
            (string) ($checkinId ?? ''), 'Innsjekking', 'failed', $auditActor);
    }
    error_log("Error performing check-in: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved innsjekking: ' . $e->getMessage()
    ]);
}
