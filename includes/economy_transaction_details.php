<?php
declare(strict_types=1);

/** Only public accounting details, scoped by the caller to its company. */
function economy_transaction_columns(): string {
    $email = tenantCustomerContactSql('tc', 'email');
    $phone = tenantCustomerContactSql('tc', 'phone');
    return "ul.id AS ledger_id, ul.order_id, ul.customer_id, ul.item_name, ul.item_type,
        ul.module_source, ul.category, ul.transaction_type, ul.quantity,
        ul.selling_price, ul.cost_price, ul.payment_method, ul.payment_status,
        ul.status AS ledger_status, ul.transaction_date, ul.metadata,
        NULLIF(TRIM(CONCAT(COALESCE(tc.first_name,''),' ',COALESCE(tc.last_name,''))), '') AS customer_name,
        {$email} AS customer_email, {$phone} AS customer_phone, tc.customer_code";
}

function economy_transaction_details(array $row, array $fees): array {
    $metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : json_decode($row['metadata'] ?? '{}', true);
    $metadata = is_array($metadata) ? $metadata : [];
    foreach (['customer_name', 'customer_email', 'customer_phone'] as $key) {
        $row[$key] = $row[$key] ?: ($metadata[$key] ?? null);
    }
    $amounts = ledger_reporting_amounts($row + ['status' => $row['ledger_status'] ?? null]);
    $row = array_merge($row, $amounts);
    $method = strtolower((string)($row['payment_method'] ?? ''));
    $fee = $fees[$method] ?? ['fee_fixed' => 0, 'fee_percent' => 0];
    $income = $amounts['income_amount'];
    $row['fee_amount'] = $income > 0 ? round($fee['fee_fixed'] + $income * $fee['fee_percent'] / 100, 2) : 0.0;
    $row['gross_amount'] = $income;
    $row['adjusted_amount'] = round($income - $row['fee_amount'], 2);
    $row['amount'] = $amounts['expense_amount'];
    // A purchase's frozen cost_price is not a payment fee.
    $row['result_amount'] = $amounts['counts_as_paid']
        ? ($amounts['expense_amount'] > 0 ? -$amounts['expense_amount'] : $row['adjusted_amount']) : null;
    $row['vipps_adjusted'] = $method === 'vipps' && $row['fee_amount'] > 0;
    unset($row['metadata']);
    return $row;
}

function economy_payment_fees(PDO $db, string $company): array {
    try {
        $stmt = $db->prepare('SELECT gateway_type,fee_fixed,fee_percent FROM economy_configuration_data WHERE company_id=?');
        $stmt->execute([$company]);
        $fees = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $type = strtolower((string)$row['gateway_type']);
            // Match the legacy fee policy: first configuration per method.
            if (!isset($fees[$type])) $fees[$type] = [
                'fee_fixed' => max(0.0, (float)$row['fee_fixed']),
                'fee_percent' => max(0.0, (float)$row['fee_percent']),
            ];
        }
        return $fees;
    } catch (PDOException $e) {
        // Older installations may not yet have the optional fee columns.
        if (($e->errorInfo[1] ?? null) !== 1054) throw $e;
        return [];
    }
}

function economy_fee_sql(PDO $db, array $fees, string $incomeSql, string $alias = ''): string {
    $p = ledger_reporting_prefix($alias);
    $cases = [];
    foreach ($fees as $method => $fee) {
        $fixed = number_format($fee['fee_fixed'], 6, '.', '');
        $percent = number_format($fee['fee_percent'], 6, '.', '');
        $cases[] = 'WHEN ' . $db->quote($method) . " THEN ROUND({$fixed} + ({$incomeSql}) * {$percent} / 100, 2)";
    }
    return $cases ? "CASE WHEN ({$incomeSql}) > 0 THEN CASE LOWER({$p}payment_method) "
        . implode(' ', $cases) . ' ELSE 0 END ELSE 0 END' : '0';
}
