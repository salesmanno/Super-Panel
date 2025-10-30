<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (isset($_SESSION['session_id']) && isset($_SESSION['tenant_admin_id'])) {
    try {
        $sessionUpdated = updateSessionActivity($_SESSION['session_id']);
        
        if ($sessionUpdated === false) {
            error_log("Session update failed - clearing session");
            session_unset();
            session_destroy();
            
            if (!headers_sent()) {
                header('Location: /index.php?error=session_expired');
                exit;
            }
        }
        
        endInactiveSessions(30);
    } catch (Exception $e) {
        error_log("Session manager error: " . $e->getMessage());
        session_unset();
        session_destroy();
        
        if (!headers_sent()) {
            header('Location: /index.php?error=session_expired');
            exit;
        }
    }
}
