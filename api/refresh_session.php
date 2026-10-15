<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    require_once __DIR__.'/../includes/company_login.php';
    companySessionStart();
    if (empty($_SESSION['tenant_admin_id'])) throw new CompanySessionFailure('missing_identity');
    $db = companyLoginDatabase();
    if (!$db) {
        echo json_encode(['success' => false, 'error' => 'Database unavailable']);
        exit;
    }
    
    $admin = companyLoginRequire($db);
    
    if ($admin) {
        $_SESSION['admin_email'] = $admin['staff_email'] ?? '';
        $_SESSION['admin_first_name'] = $admin['first_name'] ?? '';
        $_SESSION['admin_last_name'] = $admin['last_name'] ?? '';
        $_SESSION['admin_name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
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
    
} catch (Throwable $e) {
    companySessionRespondFailure($e,$db??null,'refresh_session',true);
}
