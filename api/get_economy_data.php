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

$view = $_GET['view'] ?? 'overview';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

try {
    $response = ['success' => true];
    
    switch ($view) {
        case 'overview':
            // Get total income (positive selling_price only)
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(selling_price), 0) as total_income,
                    COUNT(*) as transaction_count
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price > 0
            ");
            $stmt->execute([$company_id]);
            $income = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total expenses (absolute value of negative selling_price)
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(ABS(selling_price)), 0) as total_expenses
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price < 0
            ");
            $stmt->execute([$company_id]);
            $expenses = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get this month's income and expenses (MYSQL ONLY)
            $monthQuery = "
                SELECT 
                    COALESCE(SUM(CASE WHEN selling_price > 0 THEN selling_price ELSE 0 END), 0) as month_income,
                    COALESCE(SUM(CASE WHEN selling_price < 0 THEN ABS(selling_price) ELSE 0 END), 0) as month_expenses
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
                  AND MONTH(transaction_date) = MONTH(CURRENT_DATE)
            ";
            $stmt = $db->prepare($monthQuery);
            $stmt->execute([$company_id]);
            $monthData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get this year's income and expenses (MYSQL ONLY)
            $yearQuery = "
                SELECT 
                    COALESCE(SUM(CASE WHEN selling_price > 0 THEN selling_price ELSE 0 END), 0) as year_income,
                    COALESCE(SUM(CASE WHEN selling_price < 0 THEN ABS(selling_price) ELSE 0 END), 0) as year_expenses
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
            ";
            $stmt = $db->prepare($yearQuery);
            $stmt->execute([$company_id]);
            $yearData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get recent transactions
            $stmt = $db->prepare("
                SELECT 
                    transaction_date,
                    item_name,
                    module_source,
                    selling_price,
                    payment_method
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                ORDER BY transaction_date DESC
                LIMIT 10
            ");
            $stmt->execute([$company_id]);
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['data'] = [
                'total_income' => floatval($income['total_income']),
                'total_expenses' => floatval($expenses['total_expenses']),
                'total_profit' => floatval($income['total_income']) - floatval($expenses['total_expenses']),
                'month_income' => floatval($monthData['month_income']),
                'month_expenses' => floatval($monthData['month_expenses']),
                'year_income' => floatval($yearData['year_income']),
                'year_expenses' => floatval($yearData['year_expenses']),
                'transaction_count' => intval($income['transaction_count']),
                'recent_transactions' => $recentTransactions
            ];
            break;
            
        case 'income':
            $dateFilter = '';
            $params = [$company_id];
            
            if ($start_date && $end_date) {
                $dateFilter = " AND transaction_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date . ' 23:59:59';
            }
            
            $stmt = $db->prepare("
                SELECT 
                    id,
                    transaction_date,
                    item_name,
                    module_source as category,
                    item_type,
                    source_id,
                    selling_price,
                    payment_method,
                    metadata
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price > 0
                  {$dateFilter}
                ORDER BY transaction_date DESC
            ");
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $grouped_tickets = [];
            $other_transactions = [];
            $eventTitleCache = [];
            
            foreach ($transactions as $t) {
                if ($t['item_type'] === 'event_ticket' || $t['item_type'] === 'event') {
                    $meta = json_decode($t['metadata'] ?? '{}', true);
                    $eventId = $meta['event_id'] ?? null;
                    
                    if (!$eventId) {
                        $checkinId = $t['source_id'] ?? null;
                        if ($checkinId) {
                            $evLookup = $db->prepare("SELECT event_id FROM events_checkin WHERE id = ? LIMIT 1");
                            $evLookup->execute([$checkinId]);
                            $evRow = $evLookup->fetch(PDO::FETCH_ASSOC);
                            $eventId = $evRow ? $evRow['event_id'] : $checkinId;
                        } else {
                            $eventId = 'unknown';
                        }
                    }
                    
                    if (!isset($eventTitleCache[$eventId])) {
                        $itemName = $t['item_name'] ?? '';
                        $isGenericName = empty($itemName) || strpos($itemName, 'Walk-in') !== false || strpos($itemName, 'Event Registration') !== false;
                        
                        if ($isGenericName) {
                            $titleLookup = $db->prepare("SELECT title FROM events_eventdata WHERE id = ? LIMIT 1");
                            $titleLookup->execute([$eventId]);
                            $titleRow = $titleLookup->fetch(PDO::FETCH_ASSOC);
                            $eventTitleCache[$eventId] = $titleRow ? $titleRow['title'] : ($meta['event_title'] ?? $itemName ?: 'Ukjent arrangement');
                        } else {
                            $eventTitleCache[$eventId] = $itemName;
                        }
                    }
                    
                    $eventTitle = $eventTitleCache[$eventId];
                    $checkinId = $t['source_id'] ?? ($meta['checkin_id'] ?? null);
                    $memberType = 'Ikke-medlem';
                    if ($checkinId) {
                        $memberLookup = $db->prepare("SELECT membership_plan_id FROM events_checkin WHERE id = ? LIMIT 1");
                        $memberLookup->execute([$checkinId]);
                        $memberRow = $memberLookup->fetch(PDO::FETCH_ASSOC);
                        if ($memberRow && $memberRow['membership_plan_id']) {
                            $memberType = 'Medlem';
                        }
                    }
                    $price = floatval($t['selling_price']);
                    $key = $eventId . '|' . $memberType . '|' . number_format($price, 2);
                    
                    if (!isset($grouped_tickets[$key])) {
                        $grouped_tickets[$key] = [
                            'event_title' => $eventTitle,
                            'event_id' => $eventId,
                            'ticket_type' => $memberType,
                            'unit_price' => $price,
                            'count' => 0,
                            'total' => 0,
                            'ids' => []
                        ];
                    }
                    $grouped_tickets[$key]['count']++;
                    $grouped_tickets[$key]['total'] += $price;
                    $grouped_tickets[$key]['ids'][] = $t['id'];
                } else {
                    $other_transactions[] = $t;
                }
            }
            
            $stats_params = [$company_id];
            $statsDateFilter = '';
            if ($start_date && $end_date) {
                $statsDateFilter = " AND transaction_date BETWEEN ? AND ?";
                $stats_params[] = $start_date;
                $stats_params[] = $end_date . ' 23:59:59';
            }
            
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(selling_price), 0) as total,
                    COALESCE(AVG(selling_price), 0) as average
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price > 0
                  {$statsDateFilter}
            ");
            $stmt->execute($stats_params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['data'] = [
                'transactions' => $other_transactions,
                'grouped_tickets' => array_values($grouped_tickets),
                'total' => floatval($stats['total']),
                'average' => floatval($stats['average']),
                'count' => count($transactions)
            ];
            break;
            
        case 'expense':
            $dateFilter = '';
            $params = [$company_id];
            
            if ($start_date && $end_date) {
                $dateFilter = " AND transaction_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date . ' 23:59:59';
            }
            
            $stmt = $db->prepare("
                SELECT 
                    id,
                    transaction_date,
                    item_name,
                    module_source as category,
                    ABS(selling_price) as amount,
                    payment_method
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price < 0
                  {$dateFilter}
                ORDER BY transaction_date DESC
            ");
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get summary stats
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(ABS(selling_price)), 0) as total,
                    COALESCE(AVG(ABS(selling_price)), 0) as average
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price < 0
                  {$dateFilter}
            ");
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['data'] = [
                'transactions' => $transactions,
                'total' => floatval($stats['total']),
                'average' => floatval($stats['average']),
                'count' => count($transactions)
            ];
            break;
            
        case 'by_module':
            // Get income and expense breakdown by module
            $stmt = $db->prepare("
                SELECT 
                    module_source,
                    COALESCE(SUM(CASE WHEN selling_price > 0 THEN selling_price ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN selling_price < 0 THEN ABS(selling_price) ELSE 0 END), 0) as total_expenses,
                    COALESCE(SUM(selling_price), 0) as total_profit,
                    COUNT(*) as transaction_count
                FROM unified_ledger 
                WHERE company_id = ? 
                  AND status = 'active'
                  AND payment_status = 'completed'
                GROUP BY module_source
                ORDER BY total_income DESC
            ");
            $stmt->execute([$company_id]);
            $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['data'] = [
                'breakdown' => $breakdown
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Ugyldig visningstype'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Economy data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av økonomisk data: ' . $e->getMessage()
    ]);
}
