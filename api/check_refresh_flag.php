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
    
    $lastCheck = $_SESSION['last_polling_check'] ?? null;
    $now = date('Y-m-d H:i:s');
    
    $updates = [];
    $shouldRefresh = false;
    
    if ($lastCheck) {
        $stmt = $db->prepare("
            SELECT module_source, action_type, items_processed_last_poll, metadata
            FROM tenant_polling_tracker 
            WHERE company_id = ? 
              AND updated_at > ? 
              AND items_processed_last_poll > 0
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$companyId, $lastCheck]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $shouldRefresh = true;
            
            foreach ($rows as $row) {
                $updates[] = [
                    'module' => $row['module_source'],
                    'action' => $row['action_type'],
                    'count' => (int)$row['items_processed_last_poll'],
                    'metadata' => $row['metadata'] ? json_decode($row['metadata'], true) : null
                ];
            }
        }
    }
    
    $_SESSION['last_polling_check'] = $now;
    
    echo json_encode([
        'success' => true,
        'refresh' => $shouldRefresh,
        'updates' => $updates
    ]);
    
} catch (Exception $e) {
    error_log("Error checking refresh flag: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sjekk av refresh-flagg'
    ]);
}
