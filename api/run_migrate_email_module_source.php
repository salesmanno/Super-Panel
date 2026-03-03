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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$results = [];
$errors  = [];

try {
    $db = getDBConnection();

    $statements = [
        'email_logs.module_source'  => "ALTER TABLE email_logs ADD COLUMN module_source VARCHAR(100) NULL DEFAULT NULL",
        'email_queue.module_source' => "ALTER TABLE email_queue ADD COLUMN module_source VARCHAR(100) NULL DEFAULT NULL",
    ];

    foreach ($statements as $label => $sql) {
        try {
            $db->exec($sql);
            $results[] = "OK: $label lagt til";
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate column') !== false || stripos($msg, 'already exists') !== false) {
                $results[] = "Allerede til stede: $label";
            } else {
                $errors[] = "$label: $msg";
            }
        }
    }

    ob_end_clean();
    echo json_encode([
        'success' => empty($errors),
        'altered' => $results,
        'errors'  => $errors,
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
