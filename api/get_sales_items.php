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
    
    $dbType = getDBType();
    
    $stmt = $db->prepare("
        SELECT 
            op.id,
            op.product_type,
            op.product_id,
            COALESCE(
                CASE 
                    WHEN op.product_type = 'events' THEN e.title
                    WHEN op.product_type = 'product' THEN sp.name
                    WHEN op.product_type = 'membership' THEN " . (mp.name) . "
                    WHEN op.product_type = 'service' THEN rs.service_name
                    ELSE NULL
                END,
                op.name
            ) as item_name,
            COALESCE(
                CASE 
                    WHEN op.product_type = 'events' THEN e.ticket_price
                    WHEN op.product_type = 'product' THEN sp.selling_price
                    WHEN op.product_type = 'membership' THEN mp.price
                    WHEN op.product_type = 'service' THEN rs.price
                    ELSE NULL
                END,
                op.price
            ) as price,
            CASE 
                WHEN op.product_type = 'events' THEN e.ticket_price
                ELSE NULL
            END as member_price,
            CASE 
                WHEN op.product_type = 'membership' AND " . ($dbType === 'mysql' ? "mp.billing_cycle IS NOT NULL" : "mp.duration_months IS NOT NULL") . " THEN 
                    " . ($dbType === 'mysql' ? 
                        "CASE mp.billing_cycle 
                            WHEN 'monthly' THEN 1 
                            WHEN 'quarterly' THEN 3 
                            WHEN 'yearly' THEN 12 
                            ELSE 12 
                        END" : 
                        "mp.duration_months") . "
                ELSE NULL
            END as duration_months,
            " . ($dbType === 'mysql' ? "mp.billing_cycle" : "NULL") . " as billing_cycle,
            sp.img_product,
            sp.item_taxable,
            sp.sku,
            sp.stock_quantity,
            rs.duration_minutes as service_duration,
            rs.assigned_staff_ids as service_staff_ids
        FROM output_products op
        LEFT JOIN events_eventdata e ON op.product_type = 'events' AND op.product_id = e.id
        LEFT JOIN sales_products sp ON op.product_type = 'product' AND op.product_id = sp.id
        LEFT JOIN " . ($dbType === 'mysql' ? "membership_plans" : "membership_plans") . " mp ON op.product_type = 'membership' AND op.product_id = " . ($dbType === 'mysql' ? "mp.id" : "mp.id") . "
        LEFT JOIN reservation_services rs ON op.product_type = 'service' AND op.product_id = rs.id
        WHERE op.company_id = ? AND op.is_visible = ?
        ORDER BY item_name ASC
    ");
    
    $stmt->execute([$company_id, 1]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Duplicate events with member_price to show both regular and member pricing
    $finalItems = [];
    foreach ($items as $item) {
        // Add regular item
        $finalItems[] = $item;
        
        // If it's an event with member_price, add a second entry for member pricing
        if ($item['product_type'] === 'events' && !empty($item['member_price']) && $item['member_price'] > 0) {
            $memberItem = $item;
            $memberItem['id'] = $item['id'] . '_member'; // Unique ID for member version
            $memberItem['item_name'] = $item['item_name'] . ' (Medlemspris)';
            $memberItem['price'] = $item['member_price'];
            $memberItem['is_member_price'] = true;
            $finalItems[] = $memberItem;
        }
    }
    
    echo json_encode([
        'success' => true,
        'items' => $finalItems
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching sales items: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkter'
    ]);
}
