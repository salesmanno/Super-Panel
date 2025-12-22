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
    
    // Hent sist sjekket tidspunkt fra session
    $lastCheck = $_SESSION['last_polling_check'] ?? null;
    $now = date('Y-m-d H:i:s');
    
    // Sjekk om det finnes polling-oppdateringer med nye items siden sist sjekk
    $shouldRefresh = false;
    
    if ($lastCheck) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as update_count 
            FROM tenant_polling_tracker 
            WHERE company_id = ? 
              AND updated_at > ? 
              AND items_processed_last_poll > 0
        ");
        $stmt->execute([$companyId, $lastCheck]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $shouldRefresh = ($result && $result['update_count'] > 0);
    }
    
    // Oppdater sist sjekket tidspunkt
    $_SESSION['last_polling_check'] = $now;
    
    echo json_encode([
        'success' => true,
        'refresh' => $shouldRefresh
    ]);
    
} catch (Exception $e) {
    error_log("Error checking refresh flag: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sjekk av refresh-flagg'
    ]);
}
