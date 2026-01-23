<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT id, module_source, action_type, polling_source, polling_interval_seconds, 
               items_processed_last_poll, metadata, created_at
        FROM tenant_polling_tracker 
        WHERE company_id = ? 
          AND polling_status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $pendingPoll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pendingPoll) {
        echo json_encode([
            'success' => true,
            'hasPending' => true,
            'poll' => [
                'id' => $pendingPoll['id'],
                'module_source' => $pendingPoll['module_source'],
                'action_type' => $pendingPoll['action_type'],
                'polling_source' => $pendingPoll['polling_source'],
                'interval_seconds' => (int)($pendingPoll['polling_interval_seconds'] ?? 5),
                'items_count' => (int)$pendingPoll['items_processed_last_poll'],
                'metadata' => $pendingPoll['metadata'] ? json_decode($pendingPoll['metadata'], true) : null,
                'created_at' => $pendingPoll['created_at']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'hasPending' => false
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error checking refresh flag: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sjekk av refresh-flagg'
    ]);
}
?>
