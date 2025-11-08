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
    
    $product_id = $data['product_id'] ?? null;
    $supplier = $data['supplier'] ?? null;
    $quantity = intval($data['quantity'] ?? 0);
    $total_cost = floatval($data['total_cost'] ?? 0);
    
    if (!$product_id || $quantity <= 0 || $total_cost <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    // SECURITY: Calculate unit cost server-side, never trust client input
    $unit_cost = round($total_cost / $quantity, 2);
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Get current product data
    $stmt = $db->prepare("
        SELECT stock_quantity, cost_price, name 
        FROM sales_products 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$product_id, $company_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    $old_stock = intval($product['stock_quantity'] ?? 0);
    $old_cost_price = floatval($product['cost_price'] ?? 0);
    $product_name = $product['name'];
    
    // Calculate weighted average cost price
    // Formula: (old_stock * old_cost_price + new_quantity * new_unit_cost) / (old_stock + new_quantity)
    $new_stock = $old_stock + $quantity;
    
    if ($new_stock > 0) {
        $new_cost_price = (($old_stock * $old_cost_price) + ($quantity * $unit_cost)) / $new_stock;
    } else {
        $new_cost_price = $unit_cost;
    }
    
    // Update product stock and cost price
    $stmt = $db->prepare("
        UPDATE sales_products 
        SET stock_quantity = ?, 
            cost_price = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$new_stock, $new_cost_price, $product_id, $company_id]);
    
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
    
    // Log to unified_ledger as expense (negative amount)
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    $order_id = 'PUR-' . date('Ymd') . '-' . strtoupper(substr($purchase_id, 0, 6));
    
    $ledgerService->logTransaction([
        'order_id' => $order_id,
        'module_source' => 'sales',
        'item_type' => 'purchase',
        'source_id' => $product_id,
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
            'old_stock' => $old_stock,
            'new_stock' => $new_stock,
            'old_cost_price' => $old_cost_price,
            'new_cost_price' => $new_cost_price
        ]
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Innkjøp registrert',
        'new_stock' => $new_stock,
        'new_cost_price' => number_format($new_cost_price, 2),
        'old_cost_price' => number_format($old_cost_price, 2)
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Purchase existing product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil ved registrering av innkjøp']);
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
