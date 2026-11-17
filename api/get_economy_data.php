<?php
session_start();
session_write_close();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/ledger_reporting.php';
require_once __DIR__ . '/../includes/tenant_customer_contacts.php';
require_once __DIR__ . '/../includes/economy_transaction_details.php';

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
    $fees = economy_payment_fees($db, (string)$company_id);
    $feeSql = economy_fee_sql($db, $fees, $incomeSql);
    try {
        $moduleFilter = ledger_reporting_module_filter($_GET['modules'] ?? null);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        throw $e;
    }

    switch ($view) {
        case 'overview':
            // The transaction browser is deliberately paged independently of the
            // aggregate queries below. All use the same source selection, but
            // dashboard totals must not change when a user changes page.
            $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
            $perPage = filter_var($_GET['per_page'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['default' => 10]]);
            $page = max(1, min(100000, (int) $page));
            $perPage = max(1, min(100, (int) $perPage));
            $paymentFilter = $_GET['payment_filter'] ?? 'all';
            if (!is_string($paymentFilter) || !in_array($paymentFilter, ['completed', 'attempts', 'all'], true)) {
                http_response_code(400);
                throw new InvalidArgumentException('Ugyldig betalingsfilter');
            }
            try {
                $moduleFilter = ledger_reporting_module_filter($_GET['modules'] ?? null);
                $listModuleFilter = ledger_reporting_module_filter($_GET['modules'] ?? null, 'ul');
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                throw $e;
            }
            $overviewParams = array_merge([$company_id], $moduleFilter['params']);
            $paidSql = "({$paidSql}) AND {$moduleFilter['sql']}";
            $listFilter = ledger_reporting_filter_sql($paymentFilter, 'ul') . ' AND ' . $listModuleFilter['sql'];
            $countStmt = $db->prepare("SELECT COUNT(*) FROM unified_ledger ul WHERE ul.company_id = ? AND {$listFilter}");
            $countStmt->execute($overviewParams);
            $transactionTotal = (int) $countStmt->fetchColumn();
            $totalPages = max(1, (int) ceil($transactionTotal / $perPage));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $perPage;

            $customerEmailSql = tenantCustomerContactSql('tc', 'email');
            $customerPhoneSql = tenantCustomerContactSql('tc', 'phone');
            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(({$incomeSql}) - ({$feeSql})), 0) as total_income,
                    COUNT(*) as transaction_count
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND ({$incomeSql}) > 0
            ");
            $stmt->execute($overviewParams);
            $income = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT COALESCE(SUM({$expenseSql}), 0) as total_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
            ");
            $stmt->execute($overviewParams);
            $expenses = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(({$incomeSql}) - ({$feeSql})), 0) as month_income,
                    COALESCE(SUM({$expenseSql}), 0) as month_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
                  AND MONTH(transaction_date) = MONTH(CURRENT_DATE)
            ");
            $stmt->execute($overviewParams);
            $monthData = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(({$incomeSql}) - ({$feeSql})), 0) as year_income,
                    COALESCE(SUM({$expenseSql}), 0) as year_expenses
                FROM unified_ledger
                WHERE company_id = ?
                  AND {$paidSql}
                  AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
            ");
            $stmt->execute($overviewParams);
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
                    tc.company_name AS customer_company,
                    {$customerEmailSql} AS customer_email,
                    {$customerPhoneSql} AS customer_phone, tc.customer_code
                FROM unified_ledger ul
                LEFT JOIN tenant_customers tc
                    ON tc.id = ul.customer_id AND tc.company_id = ul.company_id
                WHERE ul.company_id = ? AND {$listFilter}
                ORDER BY ul.transaction_date DESC, ul.id DESC
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute($overviewParams);
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
            $recentTransactions = array_map(fn($row) => economy_transaction_details($row, $fees), $recentTransactions);

            $totalIncome = floatval($income['total_income']);
            $totalExp    = floatval($expenses['total_expenses']);
            $paidCount = $db->prepare("SELECT COUNT(*) FROM unified_ledger WHERE company_id = ? AND {$paidSql}");
            $paidCount->execute($overviewParams);
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
        case 'expense':
            $dateFilter = '';
            $params = array_merge([$company_id], $moduleFilter['params']);
            $qualifiedModules = ledger_reporting_module_filter($_GET['modules'] ?? null, 'ul');
            $qualifiedPaid = ledger_reporting_paid_sql('ul');
            $qualifiedIncome = ledger_reporting_income_sql('ul');
            $qualifiedExpense = ledger_reporting_expense_sql('ul');
            $amountSql = $view === 'income' ? $qualifiedIncome : $qualifiedExpense;
            $columns = economy_transaction_columns();

            if ($start_date && $end_date) {
                $dateFilter = " AND ul.transaction_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date . ' 23:59:59';
            }

            $stmt = $db->prepare("
                SELECT {$columns}
                FROM unified_ledger ul
                LEFT JOIN tenant_customers tc ON tc.id=ul.customer_id AND tc.company_id=ul.company_id
                WHERE ul.company_id = ?
                  AND {$qualifiedModules['sql']}
                  AND {$qualifiedPaid}
                  AND ({$amountSql}) > 0
                  {$dateFilter}
                ORDER BY ul.transaction_date DESC, ul.id DESC
            ");
            $stmt->execute($params);
            $rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $transactions = array_map(fn($row) => economy_transaction_details($row, $fees), $rawTransactions);
            $total = array_sum(array_column($transactions, $view === 'income' ? 'adjusted_amount' : 'expense_amount'));

            $response['data'] = [
                'transactions'    => $transactions,
                'total'           => round($total, 2),
                'average'         => $transactions ? round($total / count($transactions), 2) : 0,
                'count'           => count($transactions),
                'vipps_fee_fixed'   => $vippsFixed,
                'vipps_fee_percent' => $vippsPct,
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
