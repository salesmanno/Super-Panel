<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT id,
               TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) AS name,
               email,
               customer_code
        FROM tenant_customers
        WHERE company_id = ? AND status = 'active'
        ORDER BY first_name, last_name
    ");
    $stmt->execute([$companyId]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'customers' => $customers]);

} catch (Exception $e) {
    error_log("get_customers_simple error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente kunder']);
}
