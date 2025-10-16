<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$admin_id = $_SESSION['admin_id'];
$company_id = $_SESSION['company_id'];

$data = json_decode(file_get_contents('php://input'), true);
$output_product_id = $data['output_product_id'] ?? null;
$quantity = intval($data['quantity'] ?? 1);

if (!$output_product_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Ugyldig data']);
    exit;
}

try {
    // Verify product exists and is a sales product
    $stmt = $db->prepare("
        SELECT id, product_type 
        FROM output_products 
        WHERE id = ? AND company_id = ? AND product_type = 'product'
    ");
    $stmt->execute([$output_product_id, $company_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet eller er ikke et salgsprodukt']);
        exit;
    }
    
    // Check if item already in cart
    $stmt = $db->prepare("
        SELECT id, quantity 
        FROM sales_cart 
        WHERE admin_id = ? AND company_id = ? AND output_product_id = ?
    ");
    $stmt->execute([$admin_id, $company_id, $output_product_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update quantity
        $newQuantity = $existing['quantity'] + $quantity;
        $stmt = $db->prepare("UPDATE sales_cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $existing['id']]);
    } else {
        // Add new item
        $stmt = $db->prepare("
            INSERT INTO sales_cart (company_id, admin_id, output_product_id, quantity) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$company_id, $admin_id, $output_product_id, $quantity]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Produkt lagt til i handlekurv']);
    
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved tillegg til handlekurv']);
}
