<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';
require_once __DIR__ . '/../includes/request_date_time.php';

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
    
    $type = $data['type'] ?? 'income';
    $itemName = trim($data['item_name'] ?? '');
    $amount = floatval($data['amount'] ?? 0);
    $category = $data['category'] ?? 'general';
    $customerName = trim($data['customer_name'] ?? '');
    $paymentMethod = $data['payment_method'] ?? 'cash';
    $transactionDate = $data['transaction_date'] ?? date('Y-m-d');
    $notes = trim($data['notes'] ?? '');

    $transactionDate = normalizeRequestDate($transactionDate, 'Transaksjonsdato', false);
    
    if (empty($itemName)) {
        echo json_encode(['success' => false, 'message' => 'Beskrivelse er påkrevd']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Beløp må være større enn 0']);
        exit;
    }
    
    $db = getDBConnection();
    
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    
    $orderId = 'MANUAL-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
    
    $sellingPrice = $type === 'expense' ? -abs($amount) : abs($amount);
    
    $categoryLabels = [
        'general' => 'Generelt',
        'sales' => 'Salg',
        'events' => 'Arrangementer',
        'membership' => 'Medlemskap',
        'rental' => 'Utleie',
        'reservations' => 'Reservasjoner',
        'services' => 'Tjenester',
        'supplies' => 'Forbruksvarer',
        'equipment' => 'Utstyr',
        'marketing' => 'Markedsføring',
        'rent' => 'Husleie',
        'utilities' => 'Strøm/vann',
        'salary' => 'Lønn',
        'insurance' => 'Forsikring',
        'maintenance' => 'Vedlikehold',
        'other' => 'Annet'
    ];
    
    $result = $ledgerService->logTransaction([
        'order_id' => $orderId,
        'module_source' => 'manual',
        'item_type' => $type === 'expense' ? 'expense' : 'income',
        'source_id' => $orderId,
        'item_name' => $itemName,
        'quantity' => 1,
        'selling_price' => $sellingPrice,
        'cost_price' => $type === 'expense' ? abs($amount) : null,
        'customer_id' => null,
        'payment_method' => $paymentMethod,
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_type' => $type,
        'category' => $categoryLabels[$category] ?? $category,
        'transaction_date' => $transactionDate . ' ' . date('H:i:s'),
        'metadata' => [
            'manual_entry' => true,
            'added_by' => $admin_id,
            'category_key' => $category,
            'notes' => $notes,
            'customer_name' => $customerName ?: null
        ]
    ]);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $type === 'income' ? 'Inntekt registrert' : 'Utgift registrert',
            'ledger_id' => $result['ledger_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Add manual transaction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved lagring: ' . $e->getMessage()
    ]);
}
