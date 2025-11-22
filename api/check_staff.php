<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$email = $_GET['email'] ?? '';
$companyId = $_GET['company_id'] ?? $_SESSION['company_id'];

if (empty($email)) {
    echo json_encode(['success' => false, 'is_staff' => false]);
    exit;
}

try {
    $db = getDBConnection();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Sjekk om e-post matcher en ansatt
    $stmt = $db->prepare("
        SELECT id, first_name, last_name, email, job_title 
        FROM tenant_staff 
        WHERE company_id = ? AND email = ? AND is_active = ?
    ");
    
    $activeValue = 1;
    $stmt->execute([$companyId, $email, $activeValue]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff) {
        echo json_encode([
            'success' => true,
            'is_staff' => true,
            'name' => $staff['first_name'] . ' ' . $staff['last_name'],
            'job_title' => $staff['job_title']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'is_staff' => false
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
