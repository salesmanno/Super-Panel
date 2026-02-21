<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/db_config.php';
    require_once __DIR__ . '/../includes/EmailService.php';

    $db = getDBConnection();
    $emailService = new EmailService($db, $_SESSION['company_id']);
    
    $action = $_GET['action'] ?? 'status';
    
    if ($action === 'check') {
        $result = $emailService->checkUnreadEmails();
        echo json_encode($result);
    } elseif ($action === 'reset') {
        $emailService->resetAlert();
        echo json_encode(['success' => true, 'alertNew' => 0]);
    } else {
        $alertNew = $emailService->getAlertStatus();
        echo json_encode(['success' => true, 'alertNew' => $alertNew]);
    }
    
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'alertNew' => 0, 'error' => $e->getMessage()]);
}
