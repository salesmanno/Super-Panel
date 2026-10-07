<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';
require_once __DIR__ . '/../includes/request_date_time.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['member_id'])) {
        echo json_encode(['success' => false, 'message' => 'Medlem-ID mangler']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    require_once __DIR__.'/../includes/active_payment_methods.php';
    activePaymentMethodsLock($db,(string)$_SESSION['company_id']);
    
    // Get member details (MYSQL ONLY)
    $stmt = $db->prepare("
        SELECT 
            mm.*,
            c.first_name,
            c.last_name,
            c.email,
            p.billing_cycle,
            p.price,
            p.name as plan_name
        FROM membership_members mm
        LEFT JOIN tenant_customers c ON mm.customer_id = c.id
        LEFT JOIN membership_plans p ON mm.membership_plan_id = p.id AND p.company_id=mm.company_id
        WHERE mm.id = ? AND mm.company_id = ?
    ");
    $stmt->execute([$data['member_id'], $_SESSION['company_id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Medlem ikke funnet']);
        exit;
    }
    
    // Calculate new expiry date based on renewal date and membership plan
    $renewalDate = $data['renewal_date'] ?? date('Y-m-d');
    $renewalDate = normalizeRequestDate($renewalDate, 'Fornyelsesdato', false);
    
    $durationMonths = 12; // Default to 12 months
    if (isset($member['duration_months'])) {
        $durationMonths = $member['duration_months'];
    } elseif (isset($member['billing_cycle'])) {
        $durationMonths = match($member['billing_cycle']) {
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default => 12
        };
    }
    
    $newExpiryDate = date('Y-m-d', strtotime($renewalDate . " +{$durationMonths} months"));
    
    if (!isset($member['price']) || !is_numeric($member['price'])) throw new InvalidArgumentException('Finner ikke medlemskapets pris.');
    $price=(float)$member['price'];
    $paymentMethod=$price>0 ? requireActivePaymentMethod($db,(string)$_SESSION['company_id'],$data['payment_method']??null) : null;
    // Update member expiry date and status (MYSQL ONLY)
    $stmt = $db->prepare("
        UPDATE membership_members 
        SET expiry_date = ?, 
            status = 'active',
            join_date = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$newExpiryDate, $renewalDate, $data['member_id'], $_SESSION['company_id']]);
    
    // Log renewal transaction to unified_ledger (uses customer_id FK, name/email in metadata)
    if ($price>0) {
    $paymentUpdate=$db->prepare("UPDATE membership_members SET payment_status='paid',paymentMethod=? WHERE id=? AND company_id=?");
    $paymentUpdate->execute([$paymentMethod,$data['member_id'],$_SESSION['company_id']]);
    $ledgerService = new UnifiedLedgerService($db, $_SESSION['company_id']);
    $ledgerResult=$ledgerService->logTransaction([
        'order_id' => 'MEM-RENEW-' . date('Ymd') . '-' . substr($data['member_id'], 0, 6),
        'module_source' => 'membership',
        'item_type' => 'membership_renewal',
        'source_id' => $data['member_id'],
        'item_name' => ($member['plan_name'] ?? 'Medlemskap') . ' - Fornyelse',
        'quantity' => 1,
        'selling_price' => $member['price'] ?? 0,
        'cost_price' => null,
        'customer_id' => $member['customer_id'] ?? null,
        'payment_method' => $paymentMethod,
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_date' => $renewalDate,
        'metadata' => [
            'member_id' => $data['member_id'],
            'renewal_date' => $renewalDate,
            'new_expiry_date' => $newExpiryDate,
            'duration_months' => $durationMonths,
            'customer_name' => trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')),
            'customer_email' => $member['email'] ?? null
        ]
    ]);
    
    if (!$ledgerResult['success']) throw new RuntimeException('Kunne ikke registrere fornyelsesbetalingen.');
    }
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Medlemskap fornyet',
        'new_expiry_date' => $newExpiryDate,
        'renewal_date' => $renewalDate
    ]);
    
} catch (InvalidArgumentException $ex) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
} catch (Exception $ex) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error renewing membership: " . $ex->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved fornyelse: ' . $ex->getMessage()
    ]);
}
