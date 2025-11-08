<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

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

$admin_id = $_SESSION['tenant_admin_id'];
$company_id = $_SESSION['company_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $product_name = $data['product_name'] ?? null;
    $supplier = $data['supplier'] ?? null;
    $quantity = intval($data['quantity'] ?? 0);
    $total_cost = floatval($data['total_cost'] ?? 0);
    
    if (!$product_name || $quantity <= 0 || $total_cost <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $unit_cost = round($total_cost / $quantity, 2);
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Insert into purchase history (NO product creation!)
    $purchase_id = generateUUID();
    $stmt = $db->prepare("
        INSERT INTO sales_purchase_history 
        (id, company_id, product_id, product_name, supplier, quantity, unit_cost, total_cost, created_by)
        VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $purchase_id,
        $company_id,
        $product_name,
        $supplier,
        $quantity,
        $unit_cost,
        $total_cost,
        $admin_id
    ]);
    
    // Log to unified_ledger as expense (negative amount)
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    $order_id = 'PUR-' . date('Ymd') . '-' . strtoupper(substr($purchase_id, 0, 6));
    
    $ledgerService->logTransaction([
        'order_id' => $order_id,
        'module_source' => 'sales',
        'item_type' => 'purchase',
        'source_id' => $purchase_id,
        'item_name' => $product_name,
        'quantity' => $quantity,
        'selling_price' => -$total_cost,
        'cost_price' => $unit_cost,
        'customer_name' => $supplier,
        'customer_email' => null,
        'payment_method' => 'purchase',
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_date' => date('Y-m-d H:i:s'),
        'metadata' => [
            'supplier' => $supplier,
            'unit_cost' => $unit_cost,
            'non_inventory_item' => true
        ]
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Innkjøp registrert som utgift',
        'purchase_id' => $purchase_id,
        'product_name' => $product_name,
        'total_cost' => number_format($total_cost, 2)
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Purchase new product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
