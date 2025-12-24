<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (isset($_SESSION['session_id']) && isset($_SESSION['tenant_admin_id'])) {
    // Check if database type has changed (UUID vs INTEGER mismatch)
    try {
        $currentDbType = getDBType();
        $sessionDbType = $_SESSION['db_type'] ?? 'mysql';
        
        // If database type changed, force logout
        if ($currentDbType !== $sessionDbType) {
            session_unset();
            session_destroy();
            header('Location: /index.php?error=database_changed');
            exit;
        }
        
        // Validate session is still active in database
        $db = getDBConnection();
        if (!$db) {
            session_unset();
            session_destroy();
            header('Location: /index.php?error=database_unavailable');
            exit;
        }
        
        // Check if session exists and is active
        $stmt = $db->prepare("SELECT is_active FROM tenant_admin_sessions WHERE session_id = ?");
        $stmt->execute([$_SESSION['session_id']]);
        $sessionData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sessionData || !$sessionData['is_active']) {
            session_unset();
            session_destroy();
            header('Location: /index.php?error=session_expired');
            exit;
        }
        
        // Check if admin account is still active
        $stmt = $db->prepare("
            SELECT ta.is_active, tc.is_active as company_active 
            FROM tenant_admins ta 
            JOIN tenant_companies tc ON ta.company_id = tc.id 
            WHERE ta.id = ?
        ");
        $stmt->execute([$_SESSION['tenant_admin_id']]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$adminData || !$adminData['is_active']) {
            session_unset();
            session_destroy();
            header('Location: /index.php?error=account_inactive');
            exit;
        }
        
        if (!$adminData['company_active']) {
            session_unset();
            session_destroy();
            header('Location: /index.php?error=company_inactive');
            exit;
        }
        
        updateSessionActivity($_SESSION['session_id']);
        endInactiveSessions(30);
        
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        session_unset();
        session_destroy();
        header('Location: /index.php?error=session_error');
        exit;
    }
} elseif (basename($_SERVER['PHP_SELF']) !== 'index.php' && 
          basename($_SERVER['PHP_SELF']) !== 'company_login.php' &&
          strpos($_SERVER['PHP_SELF'], '/auth/') === false) {
    // If we're on a protected page without session, redirect to login
    header('Location: /index.php?error=not_authenticated');
    exit;
}
