<?php
session_start();
session_write_close();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/ledger_reporting.php';

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
    $paidSql = ledger_reporting_paid_sql();
    $incomeSql = ledger_reporting_income_sql();
    $expenseSql = ledger_reporting_expense_sql();

    switch ($view) {
        case 'overview':
            // The transaction browser is deliberately paged independently of the
            // aggregate queries below. Keep the latter untouched: dashboard totals
            // must not change when a user changes page.
            $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
            $perPage = filter_var($_GET['per_page'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['default' => 10]]);
            $page = max(1, min(100000, (int) $page));
            $perPage = max(1, min(100, (int) $perPage));
            $paymentFilter = $_GET['payment_filter'] ?? 'completed';
            if (!is_string($paymentFilter) || !in_array($paymentFilter, ['completed', 'attempts', 'all'], true)) {
                http_response_code(400);
                throw new InvalidArgumentException('Ugyldig betalingsfilter');
            }
            $listFilter = ledger_reporting_filter_sql($paymentFilter, 'ul');
            $countStmt = $db->prepare("SELECT COUNT(*) FROM unified_ledger ul WHERE ul.company_id = ? AND {$listFilter}");
            $countStmt->execute([$company_id]);
            $transactionTotal = (int) $countStmt->fetchColumn();
            $totalPages = max(1, (int) ceil($transactionTotal / $perPage));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $perPage;

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(
                        CASE
                            WHEN LOWER(payment_method) = 'vipps'
                            THEN ({$incomeSql}) - ? - (({$incomeSql}) * ? / 100)
                            ELSE ({$incomeSql})
                        END
                    ), 0) as total_income,
                    COUNT(*) as transaction_count
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND ({$incomeSql}) > 0
            ");
            $stmt->execute([$vippsFixed, $vippsPct, $company_id]);
            $income = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT COALESCE(SUM({$expenseSql}), 0) as total_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
            ");
            $stmt->execute([$company_id]);
            $expenses = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN ({$incomeSql}) > 0 THEN
                        CASE WHEN LOWER(payment_method) = 'vipps'
                            THEN ({$incomeSql}) - ? - (({$incomeSql}) * ? / 100)
                            ELSE ({$incomeSql})
                        END
                    ELSE 0 END), 0) as month_income,
                    COALESCE(SUM({$expenseSql}), 0) as month_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
                  AND MONTH(transaction_date) = MONTH(CURRENT_DATE)
            ");
            $stmt->execute([$vippsFixed, $vippsPct, $company_id]);
            $monthData = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN ({$incomeSql}) > 0 THEN
                        CASE WHEN LOWER(payment_method) = 'vipps'
                            THEN ({$incomeSql}) - ? - (({$incomeSql}) * ? / 100)
                            ELSE ({$incomeSql})
                        END
                    ELSE 0 END), 0) as year_income,
                    COALESCE(SUM({$expenseSql}), 0) as year_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
            ");
            $stmt->execute([$vippsFixed, $vippsPct, $company_id]);
            $yearData = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    ul.id AS ledger_id, ul.order_id, ul.source_id, ul.customer_id,
                    ul.item_name, ul.item_type, ul.quantity,
                    ul.module_source, ul.category, ul.transaction_type,
                    ul.selling_price, ul.cost_price,
                    CASE WHEN ul.cost_price IS NULL THEN NULL
                         ELSE ul.selling_price - ul.cost_price END AS profit,
                    ul.payment_method, ul.payment_status, ul.status AS ledger_status,
                    ul.transaction_date, ul.metadata,
                    NULLIF(TRIM(CONCAT(COALESCE(tc.first_name, ''), ' ', COALESCE(tc.last_name, ''))), '') AS customer_name,
                    tc.company_name AS customer_company, tc.email AS customer_email,
                    tc.phone AS customer_phone, tc.customer_code
                FROM unified_ledger ul
                LEFT JOIN tenant_customers tc
                    ON tc.id = ul.customer_id AND tc.company_id = ul.company_id
                WHERE ul.company_id = ? AND {$listFilter}
                ORDER BY ul.transaction_date DESC, ul.id DESC
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute([$company_id]);
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recentTransactions as &$transaction) {
                $metadata = [];
                if (!empty($transaction['metadata'])) {
                    $decoded = json_decode($transaction['metadata'], true);
                    if (is_array($decoded)) $metadata = $decoded;
                }
                $transaction['metadata'] = $metadata;
                $amounts = ledger_reporting_amounts($transaction + ['status' => $transaction['ledger_status']]);
                $transaction += $amounts;
                if (!$amounts['counts_as_paid']) $transaction['profit'] = null;
                if (!$transaction['customer_name']) {
                    $transaction['customer_name'] = $metadata['customer_name'] ?? null;
                }
                if (!$transaction['customer_email']) {
                    $transaction['customer_email'] = $metadata['customer_email'] ?? null;
                }
            }
            unset($transaction);

            $totalIncome = floatval($income['total_income']);
            $totalExp    = floatval($expenses['total_expenses']);
            $paidCount = $db->prepare("SELECT COUNT(*) FROM unified_ledger WHERE company_id = ? AND {$paidSql}");
            $paidCount->execute([$company_id]);
            $response['data'] = [
                'total_income'        => $totalIncome,
                'total_expenses'      => $totalExp,
                'total_profit'        => $totalIncome - $totalExp,
                'month_income'        => floatval($monthData['month_income']),
                'month_expenses'      => floatval($monthData['month_expenses']),
                'year_income'         => floatval($yearData['year_income']),
                'year_expenses'       => floatval($yearData['year_expenses']),
                'transaction_count'   => (int)$paidCount->fetchColumn(),
                'payment_filter'      => $paymentFilter,
                'recent_transactions' => $recentTransactions,
                'transactions'        => $recentTransactions,
                'page'                => $page,
                'per_page'            => $perPage,
                'total'               => $transactionTotal,
                'total_pages'         => $totalPages,
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
                  AND {$paidSql}
                  AND ({$incomeSql}) > 0
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
                    ({$expenseSql}) as amount,
                    payment_method
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND ({$expenseSql}) > 0
                  {$dateFilter}
                ORDER BY transaction_date DESC
            ");
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM({$expenseSql}), 0) as total,
                    COALESCE(AVG({$expenseSql}), 0) as average
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND ({$expenseSql}) > 0
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
                    COALESCE(SUM({$incomeSql}), 0) as total_income,
                    COALESCE(SUM({$expenseSql}), 0) as total_expenses,
                    COALESCE(SUM(({$incomeSql}) - ({$expenseSql})), 0) as total_profit,
                    COUNT(*) as transaction_count
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
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
