<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT 
            op.id,
            op.product_type,
            op.product_id,
            COALESCE(
                CASE 
                    WHEN op.product_type = 'event' THEN e.title
                    WHEN op.product_type = 'product' THEN sp.name
                    WHEN op.product_type = 'membership' THEN mp.name
                    ELSE NULL
                END,
                op.name
            ) as item_name,
            COALESCE(
                CASE 
                    WHEN op.product_type = 'event' THEN e.ticket_price
                    WHEN op.product_type = 'product' THEN sp.selling_price
                    WHEN op.product_type = 'membership' THEN mp.price
                    ELSE NULL
                END,
                op.price
            ) as price
        FROM output_products op
        LEFT JOIN events_eventdata e ON op.product_type = 'event' AND op.product_id = e.id
        LEFT JOIN sales_products sp ON op.product_type = 'product' AND op.product_id = sp.id
        LEFT JOIN membership_plans mp ON op.product_type = 'membership' AND op.product_id = mp.id
        WHERE op.company_id = ? AND op.is_visible = 1
        ORDER BY item_name ASC
    ");
    
    $stmt->execute([$company_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching sales items: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkter'
    ]);
}
