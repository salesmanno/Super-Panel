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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ugyldig forespørsel']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $allowedColumns = [
        'automail_events_confirm',
        'automail_events_reminder_3d',
        'automail_events_thankyou',
        'automail_sales_order',
        'automail_membership_welcome',
        'automail_membership_renewal',
        'automail_membership_expired',
        'automail_membership_reminder',
        'automail_reservation_confirm',
        'automail_reservation_reminder',
        'rental_confirm',
        'automail_rental_confirm',
        'automail_rental_return'
    ];
    
    $updates = [];
    $params = [];
    
    foreach ($allowedColumns as $col) {
        if (array_key_exists($col, $data)) {
            $updates[] = "$col = ?";
            $params[] = $data[$col] === null ? null : ($data[$col] ? 1 : 0);
        }
    }
    
    if (empty($updates)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ingen innstillinger å lagre']);
        exit;
    }
    
    $params[] = $companyId;
    
    $sql = "UPDATE email_configs SET " . implode(', ', $updates) . " WHERE company_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Innstillinger lagret']);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
