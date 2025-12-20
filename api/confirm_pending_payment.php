<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

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
$admin_id = $_SESSION['tenant_admin_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ledgerId = $data['ledger_id'] ?? null;
    $paymentMethod = $data['payment_method'] ?? 'cash';
    
    if (empty($ledgerId)) {
        echo json_encode(['success' => false, 'message' => 'Transaksjons-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT id, order_id, item_name, selling_price, payment_status, module_source, source_id
        FROM unified_ledger 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$ledgerId, $company_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaksjon ikke funnet']);
        exit;
    }
    
    if ($transaction['payment_status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Betaling er allerede bekreftet']);
        exit;
    }
    
    $stmt = $db->prepare("
        UPDATE unified_ledger 
        SET payment_status = 'completed',
            payment_method = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$paymentMethod, $ledgerId, $company_id]);
    
    if ($transaction['module_source'] === 'events' && $transaction['source_id']) {
        $updateCheckin = $db->prepare("
            UPDATE events_checkin 
            SET payment_status = 'paid',
                payment_method = ?
            WHERE id = ? AND company_id = ?
        ");
        $updateCheckin->execute([$paymentMethod, $transaction['source_id'], $company_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Betaling bekreftet',
        'transaction' => [
            'id' => $ledgerId,
            'item_name' => $transaction['item_name'],
            'amount' => $transaction['selling_price']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Confirm payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved bekreftelse: ' . $e->getMessage()
    ]);
}
