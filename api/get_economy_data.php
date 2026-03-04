<?php
session_start();
session_write_close();
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

function getVippsFees($db, $company_id): array {
    try {
        $stmt = $db->prepare("
            SELECT fee_fixed, fee_percent
            FROM economy_configuration_data
            WHERE company_id = ? AND LOWER(gateway_type) = 'vipps'
            LIMIT 1
        ");
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'fee_fixed'   => floatval($row['fee_fixed']   ?? 0),
            'fee_percent' => floatval($row['fee_percent'] ?? 0),
        ];
    } catch (Exception $e) {
        return ['fee_fixed' => 0, 'fee_percent' => 0];
    }
}

function vippsNetAmount(float $gross, float $feeFixed, float $feePct): float {
    return $gross - $feeFixed - ($gross * $feePct / 100);
}

try {
    $response = ['success' => true];
    $vipps = getVippsFees($db, $company_id);
    $vippsFixed = $vipps['fee_fixed'];
    $vippsPct   = $vipps['fee_percent'];

    switch ($view) {
        case 'overview':
            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(
                        CASE
                            WHEN LOWER(payment_method) = 'vipps'
                            THEN selling_price - ? - (selling_price * ? / 100)
                            ELSE selling_price
                        END
                    ), 0) as total_income,
                    COUNT(*) as transaction_count
                FROM unified_ledger
                WHERE company_id = ?
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price > 0
            ");
            $stmt->execute([$vippsFixed, $vippsPct, $company_id]);
            $income = $stmt->fetch(PDO::FETCH_ASSOC);

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

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN selling_price > 0 THEN
                        CASE WHEN LOWER(payment_method) = 'vipps'
                            THEN selling_price - ? - (selling_price * ? / 100)
                            ELSE selling_price
                        END
                    ELSE 0 END), 0) as month_income,
                    COALESCE(SUM(CASE WHEN selling_price < 0 THEN ABS(selling_price) ELSE 0 END), 0) as month_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
                  AND MONTH(transaction_date) = MONTH(CURRENT_DATE)
            ");
            $stmt->execute([$vippsFixed, $vippsPct, $company_id]);
            $monthData = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN selling_price > 0 THEN
                        CASE WHEN LOWER(payment_method) = 'vipps'
                            THEN selling_price - ? - (selling_price * ? / 100)
                            ELSE selling_price
                        END
                    ELSE 0 END), 0) as year_income,
                    COALESCE(SUM(CASE WHEN selling_price < 0 THEN ABS(selling_price) ELSE 0 END), 0) as year_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
            ");
            $stmt->execute([$vippsFixed, $vippsPct, $company_id]);
            $yearData = $stmt->fetch(PDO::FETCH_ASSOC);

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

            $totalIncome = floatval($income['total_income']);
            $totalExp    = floatval($expenses['total_expenses']);
            $response['data'] = [
                'total_income'        => $totalIncome,
                'total_expenses'      => $totalExp,
                'total_profit'        => $totalIncome - $totalExp,
                'month_income'        => floatval($monthData['month_income']),
                'month_expenses'      => floatval($monthData['month_expenses']),
                'year_income'         => floatval($yearData['year_income']),
                'year_expenses'       => floatval($yearData['year_expenses']),
                'transaction_count'   => intval($income['transaction_count']),
                'recent_transactions' => $recentTransactions,
                'vipps_fee_fixed'     => $vippsFixed,
                'vipps_fee_percent'   => $vippsPct,
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
                    transaction_date,
                    item_name,
                    module_source as category,
                    selling_price,
                    payment_method
                FROM unified_ledger
                WHERE company_id = ?
                  AND status = 'active'
                  AND payment_status = 'completed'
                  AND selling_price > 0
                  {$dateFilter}
                ORDER BY transaction_date DESC
            ");
            $stmt->execute($params);
            $rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $netTotal = 0;
            $transactions = [];
            foreach ($rawTransactions as $t) {
                $gross = floatval($t['selling_price']);
                $isVipps = strtolower($t['payment_method'] ?? '') === 'vipps';
                if ($isVipps && ($vippsFixed > 0 || $vippsPct > 0)) {
                    $fee = $vippsFixed + ($gross * $vippsPct / 100);
                    $net = $gross - $fee;
                    $t['adjusted_amount']   = round($net, 2);
                    $t['fee_amount']        = round($fee, 2);
                    $t['gross_amount']      = $gross;
                    $t['vipps_adjusted']    = true;
                    $netTotal += $net;
                } else {
                    $t['adjusted_amount']   = $gross;
                    $t['fee_amount']        = 0;
                    $t['gross_amount']      = $gross;
                    $t['vipps_adjusted']    = false;
                    $netTotal += $gross;
                }
                $transactions[] = $t;
            }

            $response['data'] = [
                'transactions'    => $transactions,
                'total'           => round($netTotal, 2),
                'count'           => count($transactions),
                'vipps_fee_fixed'   => $vippsFixed,
                'vipps_fee_percent' => $vippsPct,
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
                'total'        => floatval($stats['total']),
                'average'      => floatval($stats['average']),
                'count'        => count($transactions)
            ];
            break;

        case 'by_module':
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

            $response['data'] = ['breakdown' => $breakdown];
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
