<?php
session_start();
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/company_login.php';

function companyLoginFail(string $error): never {
    header('Location: /index.php?error='.$error); exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') companyLoginFail('invalid_request');
$username=$_POST['username']??''; $password=$_POST['password']??'';
if (!is_string($username)||!is_string($password)||$username===''||$password==='') companyLoginFail('invalid_credentials');
try {
    $db=getDBConnection();
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
    if (!$pending && !companyLoginValidAccount($a,false)) companyLoginFail('company_inactive');
    if ($pending) {
        $q=$db->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenant_admins' AND COLUMN_NAME='company_id'");
        if ($q->fetchColumn()!=='YES') companyLoginFail('company_choice_setup_required');
    }
    $operation=static function() use($db,$id,$pending,$password): void {
        $db->beginTransaction();
        $db->prepare('SELECT id FROM tenant_admins WHERE id=? FOR UPDATE')->execute([$id]);
        $a=companyLoginAccount($db,$id,true);
        if (!$a || !(int)$a['is_active'] || !verifyPassword($password,$a['password_hash'])
            || ($a['tenant_staff_id']===null)!==$pending) {
            throw new DomainException('Tilgangen er endret.',401);
        }
        if (!$pending) {
            $db->prepare('SELECT id FROM tenant_staff WHERE id=? LOCK IN SHARE MODE')->execute([$a['tenant_staff_id']]);
            $db->prepare('SELECT id FROM tenant_companies WHERE id=? LOCK IN SHARE MODE')->execute([$a['company_id']]);
            if (!companyLoginValidAccount(companyLoginAccount($db,$id),false)) throw new DomainException('Tilgangen er endret.',401);
        }
        // Only after password/account revalidation, replace all previous
        // sessions atomically. The account lock prevents old writes racing in.
        $db->prepare('UPDATE tenant_admin_sessions SET is_active=0,logout_at=NOW()
            WHERE tenant_admin_id=? AND is_active=1')->execute([$id]);
        $db->prepare('UPDATE tenant_admins SET last_login=NOW()'.($pending?',company_id=NULL':'').' WHERE id=?')->execute([$id]);
        session_regenerate_id(true);
        $_SESSION=[];
        $db->prepare('INSERT INTO tenant_admin_sessions
            (tenant_admin_id,session_id,ip_address,user_agent,login_at,last_activity,is_active)
            VALUES (?,?,?,?,NOW(),NOW(),1)')->execute([$id,session_id(),$_SERVER['REMOTE_ADDR']??null,$_SERVER['HTTP_USER_AGENT']??null]);
        $db->commit();
        if ($pending) {
            $_SESSION=['verified_company_user'=>$id,'session_id'=>session_id(),'db_type'=>'mysql',
                'company_auth_version'=>1,'company_choice_expires'=>time()+1800,
                'company_choice_csrf'=>bin2hex(random_bytes(32))];
        } else companyLoginComplete($a);
    };
    if ($db instanceof AuditedPDO) $db->withExplicitAudit($operation); else $operation();
    header('Location: /pages/login_transition.php'); exit;
} catch (Throwable $e) {
    if (isset($db) && $db && $db->inTransaction()) $db->rollBack();
    error_log('Company login failed; exception='.get_class($e));
    companyLoginFail('system_error');
}
