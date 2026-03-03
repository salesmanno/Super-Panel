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

function runLogsQuery(PDO $db, string $select, string $where, array $params, int $limit, int $offset): array {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM email_logs {$where}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT {$select}
        FROM email_logs
        {$where}
        ORDER BY sent_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
}

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $limit          = min((int)($_GET['limit'] ?? 50), 200);
    $offset         = (int)($_GET['offset'] ?? 0);
    $sourceType     = $_GET['source_type']     ?? '';
    $recipientEmail = trim($_GET['recipient_email'] ?? '');

    $hasModuleSource = (bool) $db->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'email_logs'
         AND COLUMN_NAME = 'module_source'"
    )->fetchColumn();

    $baseWhere  = 'WHERE company_id = ?';
    $baseParams = [$companyId];

    $sourceWhere = '';
    if ($hasModuleSource) {
        if ($sourceType === 'manual') {
            $sourceWhere = " AND (module_source = 'webmail' OR module_source IS NULL)";
        } elseif ($sourceType === 'auto') {
            $sourceWhere = " AND module_source IS NOT NULL AND module_source != 'webmail'";
        }
    }

    $recipientWhere  = '';
    $recipientParams = [];
    if ($recipientEmail !== '') {
        $recipientWhere    = ' AND to_email = ?';
        $recipientParams[] = $recipientEmail;
    }

    $where  = $baseWhere . $sourceWhere . $recipientWhere;
    $params = array_merge($baseParams, $recipientParams);

    if ($hasModuleSource) {
        $select = "id, module_source, from_email, from_name, to_email, to_name, subject, body_html, status, error_message, sent_at, template_used";
    } else {
        $select = "id, from_email, from_name, to_email, to_name, subject, body_html, status, error_message, sent_at, template_used";
    }

    $result = runLogsQuery($db, $select, $where, $params, $limit, $offset);

    if (!$hasModuleSource) {
        foreach ($result['rows'] as &$row) {
            $row['module_source'] = null;
        }
        unset($row);
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'logs'    => $result['rows'],
        'total'   => $result['total'],
        'limit'   => $limit,
        'offset'  => $offset,
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
