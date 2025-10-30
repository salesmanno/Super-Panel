<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?error=invalid_request');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: ../index.php?error=invalid_credentials');
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        header('Location: ../index.php?error=database_unavailable');
        exit;
    }
    
    $admin = getTenantAdminByUsername($username);
    
    if (!$admin) {
        header('Location: ../index.php?error=invalid_credentials');
        exit;
    }
    
    if (!verifyPassword($password, $admin['password_hash'])) {
        header('Location: ../index.php?error=invalid_credentials');
        exit;
    }
    
    if (!$admin['is_active']) {
        header('Location: ../index.php?error=account_inactive');
        exit;
    }
    
    if (!$admin['company_active']) {
        header('Location: ../index.php?error=company_inactive');
        exit;
    }
    
    updateTenantAdminLastLogin($admin['id']);
    
    session_regenerate_id(true);
    
    $_SESSION['tenant_admin_id'] = $admin['id'];
    $_SESSION['company_id'] = $admin['company_id'];
    $_SESSION['admin_email'] = $admin['staff_email'] ?? '';
    $_SESSION['admin_first_name'] = $admin['first_name'] ?? '';
    $_SESSION['db_type'] = getDBType();
    $_SESSION['admin_last_name'] = $admin['last_name'] ?? '';
    $_SESSION['admin_name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
    $_SESSION['company_name'] = $admin['company_name'];
    $_SESSION['subdomain'] = $admin['subdomain'];
    $_SESSION['logged_in_at'] = time();
    $_SESSION['session_id'] = session_id();
    
    createTenantAdminSession($admin['id'], session_id());
    
    header('Location: ../pages/company_dashboard.php');
    exit;
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    header('Location: ../index.php?error=system_error');
    exit;
}
