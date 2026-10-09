<?php
session_start();
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/company_login.php';
try {
    $id=(string)($_SESSION['tenant_admin_id']??$_SESSION['verified_company_user']??'');
    if ($id!=='') {
        $db=getDBConnection();
        if (!$db) throw new RuntimeException('Database unavailable');
        companyLoginLock($db,$id);
        $end=static function() use($db,$id): void {
            $db->beginTransaction();
            $db->prepare('UPDATE tenant_admin_sessions SET is_active=0,logout_at=NOW() WHERE tenant_admin_id=? AND session_id=?')
                ->execute([$id,session_id()]);
            $db->commit();
        };
        if ($db instanceof AuditedPDO) $db->withExplicitAudit($end); else $end();
    }
} catch (Throwable $e) {
    if (isset($db) && $db && $db->inTransaction()) $db->rollBack();
    error_log('Logout registry update failed; exception='.get_class($e));
}
$_SESSION=[];
session_destroy();
header('Location: /index.php?message=logged_out');
exit;
