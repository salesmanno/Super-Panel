<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['member_id'])) {
        echo json_encode(['success' => false, 'message' => 'Medlem-ID mangler']);
        exit;
    }

    $db = getDBConnection();
    $dbType = getDBType();
    $db->beginTransaction();
    
    // Get member details
    if ($dbType === 'mysql') {
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
            LEFT JOIN membership_plans p ON mm.membership_plan_id = p.id
            WHERE mm.id = ? AND mm.company_id = ?
        ");
    } else {
        $stmt = $db->prepare("
            SELECT 
                mm.*,
                c.first_name,
                c.last_name,
                c.email,
                p.duration_months,
                p.price,
                p.plan_name
            FROM membership_members mm
            LEFT JOIN tenant_customers c ON mm.customer_id = c.id
            LEFT JOIN x_membership_plan p ON mm.membership_plan_id = p.plan_id
            WHERE mm.id = ? AND mm.company_id = ?
        ");
    }
    $stmt->execute([$data['member_id'], $_SESSION['company_id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Medlem ikke funnet']);
        exit;
    }
    
    // Calculate new expiry date based on renewal date and membership plan
    $renewalDate = $data['renewal_date'] ?? date('Y-m-d');
    
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
    
    // Update member expiry date and status
    $expiryColumn = $dbType === 'mysql' ? 'expiration_date' : 'expiry_date';
    $stmt = $db->prepare("
        UPDATE membership_members 
        SET $expiryColumn = ?, 
            status = 'active',
            join_date = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$newExpiryDate, $renewalDate, $data['member_id'], $_SESSION['company_id']]);
    
    // Log renewal transaction to unified_ledger
    $ledgerService = new UnifiedLedgerService($db, $_SESSION['company_id']);
    $ledgerService->logTransaction([
        'order_id' => 'MEM-RENEW-' . date('Ymd') . '-' . substr($data['member_id'], 0, 6),
        'module_source' => 'membership',
        'item_type' => 'membership_renewal',
        'source_id' => $data['member_id'],
        'item_name' => ($member['plan_name'] ?? 'Medlemskap') . ' - Fornyelse',
        'quantity' => 1,
        'selling_price' => $member['price'] ?? 0,
        'cost_price' => null,
        'customer_name' => trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')),
        'customer_email' => $member['email'] ?? null,
        'payment_method' => 'renewal',
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_date' => $renewalDate,
        'metadata' => [
            'member_id' => $data['member_id'],
            'renewal_date' => $renewalDate,
            'new_expiry_date' => $newExpiryDate,
            'duration_months' => $durationMonths
        ]
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Medlemskap fornyet',
        'new_expiry_date' => $newExpiryDate,
        'renewal_date' => $renewalDate
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error renewing membership: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved fornyelse: ' . $e->getMessage()
    ]);
}
