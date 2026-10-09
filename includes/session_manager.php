<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/functions.php';
require_once __DIR__.'/company_login.php';
try {
    $db=getDBConnection();
    if (!$db) throw new RuntimeException('Database unavailable');
    companyLoginRequire($db);
    updateSessionActivity(session_id());
} catch (Throwable $e) {
    session_unset();
    session_destroy();
    header('Location: /index.php?error=session_expired');
    exit;
}
