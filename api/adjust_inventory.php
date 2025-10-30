<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$productId = $data['product_id'] ?? null;
$adjustmentType = $data['adjustment_type'] ?? 'add';
$quantity = $data['quantity'] ?? 0;
$comment = $data['comment'] ?? '';

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Validation
    if (!$productId || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig produkt eller antall']);
        exit;
    }
    
    if (!in_array($adjustmentType, ['add', 'remove'])) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig justeringstype']);
        exit;
    }
    
    $db->beginTransaction();
    
    // Get current product and stock (with schema compatibility check)
    $product = null;
    $hasStockTracking = true;
    try {
        $stmt = $db->prepare("SELECT name, stock_quantity FROM sales_products WHERE id = ? AND company_id = ?");
        $stmt->execute([$productId, $company_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
            $hasStockTracking = false;
            $stmt = $db->prepare("SELECT name FROM sales_products WHERE id = ? AND company_id = ?");
            $stmt->execute([$productId, $company_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw $e;
        }
    }
    
    if (!$product) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    // Check if stock tracking is available
    if (!$hasStockTracking) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lagerstyring er ikke aktivert for dette produktet']);
        exit;
    }
    
    $currentStock = $product['stock_quantity'] ?? 0;
    
    // Calculate new stock
    if ($adjustmentType === 'add') {
        $newStock = $currentStock + $quantity;
    } else {
        $newStock = $currentStock - $quantity;
        
        // Prevent negative stock
        if ($newStock < 0) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ikke nok varer på lager']);
            exit;
        }
    }
    
    // Update stock (with schema compatibility)
    try {
        $updateStmt = $db->prepare("UPDATE sales_products SET stock_quantity = ? WHERE id = ?");
        $updateStmt->execute([$newStock, $productId]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') === false && strpos($e->getMessage(), 'Column not found') === false) {
            throw $e;
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Lager justert',
        'new_stock' => $newStock,
        'adjustment' => $adjustmentType === 'add' ? "+$quantity" : "-$quantity"
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Inventory adjustment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved justering av lager']);
}
