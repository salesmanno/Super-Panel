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

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$company_id = $_SESSION['company_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $product_name = $data['product_name'] ?? null;
    $category = $data['category'] ?? null;
    $supplier = $data['supplier'] ?? null;
    $quantity = intval($data['quantity'] ?? 0);
    $total_cost = floatval($data['total_cost'] ?? 0);
    
    if (!$product_name || $quantity <= 0 || $total_cost <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    // SECURITY: Calculate unit cost server-side, never trust client input
    $unit_cost = round($total_cost / $quantity, 2);
    
    // Default selling price set to 0 for non-registered items (not intended for resale)
    $selling_price = 0;
    
    $db = getDB();
    $db->beginTransaction();
    
    // Generate product ID
    $product_id = generateUUID();
    
    // Generate SKU automatically for non-registered items
    $sku = 'NR-' . strtoupper(substr(md5($product_name . time()), 0, 8));
    
    // Create new product (no barcode field)
    $stmt = $db->prepare("
        INSERT INTO sales_products 
        (id, company_id, sku, name, category_id, cost_price, selling_price, stock_quantity, is_active, unit)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'stk')
    ");
    $stmt->execute([
        $product_id,
        $company_id,
        $sku,
        $product_name,
        $category,
        $unit_cost,
        $selling_price,
        $quantity
    ]);
    
    // Insert into purchase history
    $purchase_id = generateUUID();
    $stmt = $db->prepare("
        INSERT INTO sales_purchase_history 
        (id, company_id, product_id, product_name, supplier, quantity, unit_cost, total_cost, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $purchase_id,
        $company_id,
        $product_id,
        $product_name,
        $supplier,
        $quantity,
        $unit_cost,
        $total_cost,
        $admin_id
    ]);
    
    // Log to unified ledger as expense
    $ledger = new UnifiedLedgerService($db);
    $ledger->logTransaction([
        'company_id' => $company_id,
        'transaction_type' => 'purchase',
        'module_source' => 'sales',
        'amount' => $total_cost,
        'payment_status' => 'completed',
        'payment_method' => 'cash',
        'metadata' => json_encode([
            'purchase_id' => $purchase_id,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'supplier' => $supplier,
            'quantity' => $quantity,
            'unit_cost' => $unit_cost,
            'sku' => $sku,
            'new_product' => true,
            'non_registered_item' => true
        ])
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Produkt opprettet og innkjøp registrert',
        'product_id' => $product_id,
        'product_name' => $product_name,
        'sku' => $sku,
        'stock_quantity' => $quantity,
        'cost_price' => number_format($unit_cost, 2)
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Purchase new product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil ved opprettelse av produkt']);
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
