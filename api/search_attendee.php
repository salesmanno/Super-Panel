<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_customer_contacts.php';

try {
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 3) {
        echo json_encode(['success' => false, 'message' => 'Søket må være minst 3 tegn']);
        exit;
    }
    
    $db = getDBConnection();
    $customerEmailSql = tenantCustomerContactSql('tc', 'email');
    $customerPhoneSql = tenantCustomerContactSql('tc', 'phone');
    
    // Search in tenant_customers for existing customers
    $stmt = $db->prepare("
        SELECT 
            tc.id,
            CONCAT(COALESCE(tc.first_name, ''), ' ', COALESCE(tc.last_name, '')) as name,
            {$customerEmailSql} AS email,
            {$customerPhoneSql} AS phone
        FROM tenant_customers tc
        WHERE tc.company_id = ?
        AND (
            CAST(tc.id AS CHAR) LIKE ?
            OR CONCAT(COALESCE(tc.first_name, ''), ' ', COALESCE(tc.last_name, '')) LIKE ?
            OR {$customerEmailSql} LIKE ?
            OR {$customerPhoneSql} LIKE ?
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
