<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 3) {
        echo json_encode(['success' => false, 'message' => 'Søket må være minst 3 tegn']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Search in tenant_customers for existing customers
    $stmt = $db->prepare("
        SELECT 
            id,
            first_name || ' ' || last_name as name,
            email,
            phone
        FROM tenant_customers
        WHERE company_id = ?
        AND (
            id::text ILIKE ?
            OR first_name || ' ' || last_name ILIKE ?
            OR email ILIKE ?
            OR phone ILIKE ?
        )
        LIMIT 1
    ");
    
    $searchPattern = '%' . $query . '%';
    $stmt->execute([
        $_SESSION['company_id'],
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $searchPattern
    ]);
    
    $attendee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendee) {
        echo json_encode([
            'success' => true,
            'attendee' => $attendee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ingen deltaker funnet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error searching attendee: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved søk'
    ]);
}
