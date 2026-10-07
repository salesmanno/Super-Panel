<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';
require_once __DIR__ . '/../includes/active_payment_methods.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['member_id']) && empty($data['customer_email'])) {
        echo json_encode(['success' => false, 'message' => 'member_id eller customer_email er påkrevd']);
        exit;
    }
    
    if (empty($data['amount']) || floatval($data['amount']) <= 0) {
        echo json_encode(['success' => false, 'message' => 'Beløp må være større enn 0']);
        exit;
    }
    
    $db = getDBConnection();
    $db->beginTransaction();
    activePaymentMethodsLock($db,(string)$company_id);
    $paymentMethod = requireActivePaymentMethod($db,(string)$company_id,$data['payment_method']??null);
    
    $memberInfo = null;
    $customerEmail = $data['customer_email'] ?? null;
    $customerName = $data['customer_name'] ?? null;
    $planName = $data['plan_name'] ?? 'Medlemskap';
    $memberId = $data['member_id'] ?? null;
    
    if ($memberId) {
        $stmt = $db->prepare("
            SELECT 
                mm.id as member_id,
                c.first_name,
                c.last_name,
                c.email,
                mp.name as plan_name,
                mp.price as plan_price
            FROM membership_members mm
            LEFT JOIN tenant_customers c ON mm.customer_id = c.id
            LEFT JOIN membership_plans mp ON mm.membership_plan_id = mp.id
            WHERE mm.id = ? AND mm.company_id = ?
        ");
        $stmt->execute([$memberId, $company_id]);
        $memberInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$memberInfo) throw new InvalidArgumentException('Medlem ikke funnet i ditt firma.');
        
        if ($memberInfo) {
            $customerEmail = $memberInfo['email'];
            $customerName = trim(($memberInfo['first_name'] ?? '') . ' ' . ($memberInfo['last_name'] ?? ''));
            $planName = $memberInfo['plan_name'] ?? 'Medlemskap';
        }
    }
    
    $amount = floatval($data['amount']);
    $transactionDate = $data['transaction_date'] ?? date('Y-m-d H:i:s');
    $transactionReference = $data['transaction_reference'] ?? null;
    $itemType = $data['item_type'] ?? 'membership_payment';
    
    $orderId = 'MEM-PAY-' . date('Ymd-His') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    if ($transactionReference) {
        $orderId = $transactionReference;
    }
    
    // Get customer_id from membership_members if available
    $customerId = null;
    if ($memberId) {
        $custStmt = $db->prepare("SELECT customer_id FROM membership_members WHERE id = ? AND company_id = ?");
        $custStmt->execute([$memberId, $company_id]);
        $custResult = $custStmt->fetch(PDO::FETCH_ASSOC);
        if ($custResult) {
            $customerId = $custResult['customer_id'];
        }
    }
    
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    $result = $ledgerService->logTransaction([
        'order_id' => $orderId,
        'module_source' => 'membership',
        'item_type' => $itemType,
        'source_id' => $memberId,
        'item_name' => $planName,
        'quantity' => 1,
        'selling_price' => $amount,
        'cost_price' => null,
        'customer_id' => $customerId,
        'payment_method' => $paymentMethod,
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_date' => $transactionDate,
        'metadata' => [
            'member_id' => $memberId,
            'transaction_reference' => $transactionReference,
            'source' => 'external_payment',
            'customer_name' => $customerName,
            'customer_email' => $customerEmail
        ]
    ]);
    if (!$result['success']) throw new RuntimeException('Kunne ikke registrere betalingen.');
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Betaling registrert',
        'order_id' => $orderId,
        'amount' => $amount,
        'customer_email' => $customerEmail
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    error_log("Error logging membership payment: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved registrering av betaling: ' . $e->getMessage()
    ]);
}
