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

    $limit  = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM email_logs WHERE company_id = ?");
    $countStmt->execute([$companyId]);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT id, from_email, from_name, to_email, to_name, subject, status, error_message, sent_at, template_used
        FROM email_logs
        WHERE company_id = ?
        ORDER BY sent_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$companyId, $limit, $offset]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'logs'    => $rows,
        'total'   => $total,
        'limit'   => $limit,
        'offset'  => $offset
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
