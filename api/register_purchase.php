<?php
session_start();
require_once '../config/database.php';
require_once '../includes/UnifiedLedgerService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$supplier = $data['supplier'] ?? '';
$date = $data['date'] ?? date('Y-m-d');
$productId = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? 0;
$unitCost = $data['unit_cost'] ?? 0;
$paymentMethod = $data['payment_method'] ?? 'invoice';
$invoiceNumber = $data['invoice_number'] ?? '';
$notes = $data['notes'] ?? '';

try {
    $db = getDBConnection();
    $admin_id = $_SESSION['tenant_admin_id'];
    $company_id = $_SESSION['company_id'];
    
    // Validation
    if (empty($supplier)) {
        echo json_encode(['success' => false, 'message' => 'Leverandør må oppgis']);
        exit;
    }
    
    if (!$productId || $quantity <= 0 || $unitCost < 0) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig produkt, antall eller kostpris']);
        exit;
    }
    
    $db->beginTransaction();
    
    // Get product details
    $stmt = $db->prepare("SELECT name, stock_quantity FROM sales_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$productId, $company_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    $totalCost = $quantity * $unitCost;
    $transactionId = bin2hex(random_bytes(16));
    $orderId = 'PUR-' . date('Ymd') . '-' . strtoupper(substr($transactionId, 0, 6));
    
    // Increase stock quantity (initialize to 0 if NULL)
    $updateStockStmt = $db->prepare("
        UPDATE sales_products 
        SET stock_quantity = COALESCE(stock_quantity, 0) + ? 
        WHERE id = ?
    ");
    $updateStockStmt->execute([$quantity, $productId]);
    
    // Fetch updated stock
    $newStockStmt = $db->prepare("SELECT stock_quantity FROM sales_products WHERE id = ?");
    $newStockStmt->execute([$productId]);
    $updatedProduct = $newStockStmt->fetch(PDO::FETCH_ASSOC);
    $newStock = $updatedProduct['stock_quantity'] ?? null;
    
    // Log to unified_ledger as expense
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    
    $metadata = [
        'supplier' => $supplier,
        'invoice_number' => $invoiceNumber,
        'unit_cost' => $unitCost,
        'notes' => $notes,
        'purchase_date' => $date
    ];
    
    $ledgerService->logTransaction([
        'order_id' => $orderId,
        'module_source' => 'sales',
        'item_type' => 'purchase',
        'source_id' => $productId,
        'item_name' => $product['name'],
        'quantity' => $quantity,
        'selling_price' => -$totalCost, // Negative for expense
        'cost_price' => null,
        'customer_name' => $supplier,
        'customer_email' => null,
        'payment_method' => $paymentMethod,
        'payment_status' => ($paymentMethod === 'invoice') ? 'pending' : 'completed',
        'status' => 'active',
        'transaction_date' => $date . ' 00:00:00',
        'metadata' => $metadata
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Innkjøp registrert',
        'order_id' => $orderId,
        'new_stock' => $newStock
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Purchase registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved registrering av innkjøp']);
}
