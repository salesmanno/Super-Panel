<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_results.php';

$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();

    $summaryStmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE
                WHEN payment_status = 'paid' THEN COALESCE(total_amount, 0)
                ELSE 0
            END), 0) AS total_revenue,
            SUM(CASE
                WHEN status IS NULL OR status NOT IN ('cancelled', 'draft') THEN 1
                ELSE 0
            END) AS total_rentals,
            COALESCE(SUM(CASE
                WHEN payment_status = 'paid'
                 AND paid_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                 AND paid_at < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)
                THEN COALESCE(total_amount, 0)
                ELSE 0
            END), 0) AS monthly_revenue,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_contract_count
        FROM rental_contracts
        WHERE company_id = ?
    ");
    $summaryStmt->execute([$companyId]);
    $aggregates = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    if (!$aggregates) {
        throw new RuntimeException('Aggregat for rental_contracts kunne ikke leses');
    }

    $statusStmt = $db->prepare("
        SELECT status, COUNT(*) AS contract_count
        FROM rental_contracts
        WHERE company_id = ?
        GROUP BY status
        ORDER BY status
    ");
    $statusStmt->execute([$companyId]);
    $statusDistribution = [];
    foreach ($statusStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $statusDistribution[(string) ($row['status'] ?? 'unknown')] = (int) $row['contract_count'];
    }

    $recentStmt = $db->prepare("
        SELECT rc.id, rc.rental_object_id, ro.object_name,
               rc.customer_id, rc.customer_name,
               rc.start_date, rc.end_date, rc.rental_days, rc.total_amount,
               rc.payment_status, rc.paid_at, rc.status, rc.created_at, rc.updated_at
        FROM rental_contracts rc
        INNER JOIN rental_objects ro
          ON ro.id = rc.rental_object_id AND ro.company_id = rc.company_id
        WHERE rc.company_id = ? AND rc.payment_status = 'paid'
        ORDER BY paid_at DESC, id DESC
        LIMIT 10
    ");
    $recentStmt->execute([$companyId]);
    $recentPaidContracts = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    $results = calculateRentalResults(
        $aggregates,
        $statusDistribution,
        $recentPaidContracts
    );

    echo json_encode([
        'success' => true,
        'summary' => $results,
        'currency' => 'NOK',
        'monthly_revenue_basis' => 'Betalte kontrakter med paid_at i denne kalendermåneden',
    ]);
} catch (\Throwable $e) {
    error_log('get_rental_results error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente utleieresultater fra rental_contracts.',
    ]);
}