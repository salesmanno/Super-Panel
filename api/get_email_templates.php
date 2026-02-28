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
require_once __DIR__ . '/../includes/EmailTemplate.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $id      = $_GET['id'] ?? null;
    $preview = isset($_GET['preview']) && $_GET['preview'] == '1';

    if ($id && $preview) {
        $stmt = $db->prepare("
            SELECT et.body_template, et.name, et.subject_template,
                   tc.company_name, tc.logo_url
            FROM email_templates et
            JOIN tenant_companies tc ON tc.id = ?
            WHERE et.id = ?
              AND (et.company_id = ? OR et.company_id IS NULL)
            LIMIT 1
        ");
        $stmt->execute([$companyId, $id, $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Mal ikke funnet']);
            exit;
        }

        $html = EmailTemplate::wrap(
            $row['body_template'],
            $row['company_name'] ?? '',
            $row['logo_url'] ?? ''
        );

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'html'    => $html,
            'name'    => $row['name'],
            'subject' => $row['subject_template']
        ]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT id, template_key, name, subject_template, is_active
        FROM email_templates
        WHERE company_id = ? OR company_id IS NULL
        ORDER BY template_key ASC
    ");
    $stmt->execute([$companyId]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode(['success' => true, 'templates' => $templates]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
