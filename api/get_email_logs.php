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

    $limit            = min((int)($_GET['limit'] ?? 50), 200);
    $offset           = (int)($_GET['offset'] ?? 0);
    $excludeAutomailer = isset($_GET['exclude_automailer']) && $_GET['exclude_automailer'] == '1';
    $recipientEmail   = trim($_GET['recipient_email'] ?? '');

    $where  = 'WHERE company_id = ?';
    $params = [$companyId];

    if ($excludeAutomailer) {
        $where .= " AND (module_source IS NULL OR module_source != 'membership')";
    }

    if ($recipientEmail !== '') {
        $where .= ' AND to_email = ?';
        $params[] = $recipientEmail;
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM email_logs {$where}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $queryParams = array_merge($params, [$limit, $offset]);
    $stmt = $db->prepare("
        SELECT id, module_source, from_email, from_name, to_email, to_name,
               subject, body_html, status, error_message, sent_at, template_used
        FROM email_logs
        {$where}
        ORDER BY sent_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($queryParams);
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
