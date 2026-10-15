<?php
require_once __DIR__.'/../includes/company_login.php';
try { companySessionStart(); }
catch (Throwable $e) { companySessionRespondFailure($e,null,'password_login'); }
require_once __DIR__.'/../includes/functions.php';

function companyLoginFail(string $error): never {
    header('Location: /index.php?error='.$error); exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') companyLoginFail('invalid_request');
$username=$_POST['username']??''; $password=$_POST['password']??'';
if (!is_string($username)||!is_string($password)||$username===''||$password==='') companyLoginFail('invalid_credentials');
$newSession = false;
try {
    $db=companyLoginDatabase();
    if (!$db) companyLoginFail('database_unavailable');
    // Username uniqueness in older schemas is per company. Never guess between accounts.
    $q=$db->prepare('SELECT id,password_hash FROM tenant_admins WHERE username=?');
    $q->execute([$username]);
    $matches=[];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
        if (verifyPassword($password,$candidate['password_hash'])) $matches[]=$candidate['id'];
    }
    if (count($matches)!==1) companyLoginFail('invalid_credentials');
    $id=(string)$matches[0];
    companyLoginLock($db,$id);
    $a=companyLoginAccount($db,$id);
    if (!$a || !(int)$a['is_active']) companyLoginFail('account_inactive');
    $pending=$a['tenant_staff_id']===null;
    if (!$pending && !companyLoginValidAccount($a,false)) {
        companyLoginFail(!(int)$a['company_active']?'company_inactive':'access_changed');
    }
    if ($pending) {
        $q=$db->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenant_admins' AND COLUMN_NAME='company_id'");
        if ($q->fetchColumn()!=='YES') companyLoginFail('company_choice_setup_required');
    }
    $operation=static function() use($db,$id,$pending,$password,&$newSession): void {
        $db->beginTransaction();
        $db->prepare('SELECT id FROM tenant_admins WHERE id=? FOR UPDATE')->execute([$id]);
        $a=companyLoginAccount($db,$id,true);
        if (!$a || !(int)$a['is_active'] || !verifyPassword($password,$a['password_hash'])
            || ($a['tenant_staff_id']===null)!==$pending) {
            throw new CompanySessionFailure('account_changed');
        }
        if (!$pending) {
            $db->prepare('SELECT id FROM tenant_staff WHERE id=? LOCK IN SHARE MODE')->execute([$a['tenant_staff_id']]);
            $db->prepare('SELECT id FROM tenant_companies WHERE id=? LOCK IN SHARE MODE')->execute([$a['company_id']]);
            if (!companyLoginValidAccount(companyLoginAccount($db,$id),false)) throw new CompanySessionFailure('account_changed');
        }
        // Only after password/account revalidation, replace all previous
        // sessions atomically. The account lock prevents old writes racing in.
        $db->prepare('UPDATE tenant_admin_sessions SET is_active=0,logout_at=NOW()
            WHERE tenant_admin_id=? AND is_active=1')->execute([$id]);
        $db->prepare('UPDATE tenant_admins SET last_login=NOW()'.($pending?',company_id=NULL':'').' WHERE id=?')->execute([$id]);
        if (!@session_regenerate_id(true)) throw new CompanySessionFailure('php_session_rotation_failed',503,false);
        $newSession = true;
        $_SESSION=[];
        $db->prepare('INSERT INTO tenant_admin_sessions
            (tenant_admin_id,session_id,ip_address,user_agent,login_at,last_activity,is_active)
            VALUES (?,?,?,?,NOW(),NOW(),1)')->execute([$id,session_id(),$_SERVER['REMOTE_ADDR']??null,$_SERVER['HTTP_USER_AGENT']??null]);
        if ($pending) {
            $_SESSION=['verified_company_user'=>$id,'session_id'=>session_id(),'db_type'=>'mysql',
                'company_auth_version'=>1,'company_choice_expires'=>time()+1800,
                'company_choice_csrf'=>bin2hex(random_bytes(32))];
        } else companyLoginComplete($a);
        $q=$db->prepare('SELECT COUNT(*) FROM tenant_admin_sessions
            WHERE tenant_admin_id=? AND BINARY session_id=BINARY ? AND is_active=1');
        $q->execute([$id,session_id()]);
        if ((int)$q->fetchColumn()!==1) throw new CompanySessionFailure('registry_write_mismatch',503,false);
        companySessionPersist();
        $db->commit();
    };
    if ($db instanceof AuditedPDO) $db->withExplicitAudit($operation); else $operation();
    header('Location: /pages/login_transition.php'); exit;
} catch (Throwable $e) {
    try {
        if (isset($db) && $db && $db->inTransaction()) $db->rollBack();
    } catch (Throwable $cleanup) {
        companySessionLog($cleanup,'login_rollback');
    }
    companySessionLog($e,'password_login');
    if ($newSession) {
        // Only this attempted login is cleared, never another browser's login.
        if (session_status()!==PHP_SESSION_ACTIVE) @session_start();
        companyLoginClearSession($db??null,false);
    }
    companyLoginFail($e instanceof CompanySessionFailure
        ? match($e->reason) {
            'request_busy'=>'login_busy',
            'database_unavailable'=>'database_unavailable',
            'account_changed'=>'access_changed',
            default=>'session_error',
        } : 'system_error');
}
