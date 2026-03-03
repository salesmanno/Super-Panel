<?php
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server-feil: ' . $error['message']]);
        exit;
    }
});

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $status = $_GET['status'] ?? 'all';
    $limit  = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);

    $where = "company_id = ?";
    $params = [$companyId];

    if ($status !== 'all') {
        $where .= " AND status = ?";
        $params[] = $status;
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM email_queue WHERE {$where}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare("
        SELECT id, recipient_email, from_email, subject, status, attempts, max_attempts,
               sent_at, error_message, created_at, scheduled_at, automail
        FROM email_queue
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'queue'   => $rows,
        'total'   => $total,
        'limit'   => $limit,
        'offset'  => $offset
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
