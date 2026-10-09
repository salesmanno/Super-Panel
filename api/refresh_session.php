<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'error' => 'Database unavailable']);
        exit;
    }
    
    require_once __DIR__.'/../includes/company_login.php';
    $admin = companyLoginRequire($db);
    
    if ($admin) {
        $_SESSION['admin_email'] = $admin['staff_email'] ?? '';
        $_SESSION['admin_first_name'] = $admin['first_name'] ?? '';
        $_SESSION['admin_last_name'] = $admin['last_name'] ?? '';
        $_SESSION['admin_name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        updateSessionActivity(session_id());
        $_SESSION['company_name'] = $admin['company_name'];
        $_SESSION['subdomain'] = $admin['subdomain'];
        
        echo json_encode([
            'success' => true, 
            'admin_name' => $_SESSION['admin_name'],
            'company_name' => $_SESSION['company_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Admin not found']);
    }
    
} catch (Exception $e) {
    error_log("Refresh session error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error']);
}
