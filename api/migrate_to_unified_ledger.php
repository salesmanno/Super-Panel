<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];

try {
    $db->beginTransaction();
    
    $migrated = [
        'sales' => 0,
        'events' => 0,
        'membership' => 0,
        'rental' => 0
    ];
    
    // 1. Migrate sales_transactions to unified_ledger
    $stmt = $db->prepare("
        SELECT 
            st.id,
            st.product_id,
            st.quantity,
            st.unit_price,
            st.total_amount,
            st.customer_name,
            st.payment_method,
            st.created_at,
            sp.name as product_name,
            sp.cost_price
        FROM sales_transactions st
        LEFT JOIN sales_products sp ON st.product_id = sp.id
        WHERE st.company_id = ?
          AND NOT EXISTS (
              SELECT 1 FROM unified_ledger ul 
              WHERE ul.module_source = 'sales' 
                AND ul.source_id = st.id
          )
    ");
    $stmt->execute([$company_id]);
    $salesTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($salesTransactions as $tx) {
        $ledgerId = bin2hex(random_bytes(16));
        $orderId = 'MIGR-SALES-' . date('Ymd') . '-' . substr($tx['id'], 0, 6);
        $costPrice = $tx['cost_price'] ? ($tx['cost_price'] * $tx['quantity']) : null;
        
        $insertStmt = $db->prepare("
            INSERT INTO unified_ledger (
                id, order_id, company_id,
                module_source, item_type, source_id,
                item_name, quantity,
                selling_price, cost_price,
                customer_name, customer_email,
                payment_method, payment_status,
                status, transaction_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            $ledgerId,
            $orderId,
            $company_id,
            'sales',
            'product',
            $tx['id'],
            $tx['product_name'] ?? 'Ukjent produkt',
            $tx['quantity'],
            $tx['total_amount'],
            $costPrice,
            $tx['customer_name'],
            null,
            $tx['payment_method'],
            'completed',
            'active',
            $tx['created_at']
        ]);
        
        $migrated['sales']++;
    }
    
    // 2. Migrate events_checkin to unified_ledger (only paid)
    $stmt = $db->prepare("
        SELECT 
            ec.id,
            ec.event_id,
            ec.attendee_name,
            ec.attendee_email,
            ec.ticket_price,
            ec.payment_method,
            ec.created_at,
            ed.title as event_title,
            ed.cost_price
        FROM events_checkin ec
        LEFT JOIN events_eventdata ed ON ec.event_id = ed.id
        WHERE ec.payment_status = 'paid'
          AND ed.company_id = ?
          AND NOT EXISTS (
              SELECT 1 FROM unified_ledger ul 
              WHERE ul.module_source = 'events' 
                AND ul.source_id = ec.id
          )
    ");
    $stmt->execute([$company_id]);
    $eventCheckins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($eventCheckins as $tx) {
        $ledgerId = bin2hex(random_bytes(16));
        $orderId = 'MIGR-EVT-' . date('Ymd') . '-' . substr($tx['id'], 0, 6);
        
        $insertStmt = $db->prepare("
            INSERT INTO unified_ledger (
                id, order_id, company_id,
                module_source, item_type, source_id,
                item_name, quantity,
                selling_price, cost_price,
                customer_name, customer_email,
                payment_method, payment_status,
                status, transaction_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            $ledgerId,
            $orderId,
            $company_id,
            'events',
            'event',
            $tx['id'],
            $tx['event_title'] ?? 'Ukjent arrangement',
            1,
            $tx['ticket_price'],
            $tx['cost_price'],
            $tx['attendee_name'],
            $tx['attendee_email'],
            $tx['payment_method'],
            'completed',
            'active',
            $tx['created_at']
        ]);
        
        $migrated['events']++;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Migrering fullført',
        'migrated' => $migrated,
        'total' => array_sum($migrated)
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Migration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved migrering: ' . $e->getMessage()
    ]);
}
