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
    
    // Hent alle produkter fra output_products med detaljer fra kildetabeller
    $stmt = $db->prepare("
        SELECT 
            op.id,
            op.product_type,
            op.product_id,
            CASE 
                WHEN op.product_type = 'events' THEN e.title
                WHEN op.product_type = 'sales' THEN si.item_name
                WHEN op.product_type = 'rental' THEN r.item_name
                WHEN op.product_type = 'membership' THEN mp.plan_name
            END as item_name,
            CASE 
                WHEN op.product_type = 'events' THEN e.ticket_price
                WHEN op.product_type = 'sales' THEN si.price
                WHEN op.product_type = 'rental' THEN r.daily_price
                WHEN op.product_type = 'membership' THEN mp.price
            END as price,
            CASE 
                WHEN op.product_type = 'sales' THEN si.stock_quantity
                ELSE NULL
            END as stock_quantity,
            op.product_type as category
        FROM output_products op
        LEFT JOIN events_eventdata e ON op.product_type = 'events' AND op.product_id = e.id
        LEFT JOIN sales_items si ON op.product_type = 'sales' AND op.product_id = si.id
        LEFT JOIN rental_objects r ON op.product_type = 'rental' AND op.product_id = r.id
        LEFT JOIN membership_plans mp ON op.product_type = 'membership' AND op.product_id = mp.id
        WHERE op.company_id = ? AND op.is_visible = " . dbBool(true) . "
        ORDER BY item_name ASC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
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
