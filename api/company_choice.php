<?php
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/company_login.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
$rotatedSession=false;
try {
    companySessionStart();
    $db=companyLoginDatabase();
    if (!$db) throw new RuntimeException('Database unavailable');
    companyLoginLock($db,(string)($_SESSION['verified_company_user']??''));
    $admin=companyLoginRequire($db,true);
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        $companies=$db->query('SELECT id,name,logo_url FROM tenant_companies WHERE is_active=1 ORDER BY name,id')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'companies'=>$companies,'csrf_token'=>$_SESSION['company_choice_csrf']]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') throw new DomainException('Ugyldig forespørsel.',405);
    $body=json_decode(file_get_contents('php://input'),true);
    if (!is_array($body) || !is_string($body['csrf_token']??null)
        || !hash_equals((string)$_SESSION['company_choice_csrf'],$body['csrf_token'])) {
        throw new DomainException('Sikkerhetskontrollen feilet. Last siden på nytt.',403);
    }
    $company=$body['company_id']??null;
    if (!is_string($company)||$company==='') throw new DomainException('Velg en bedrift.',400);
    $operation=static function() use($db,$company,$admin,&$rotatedSession): array {
        $db->beginTransaction();
        $q=$db->prepare('SELECT id FROM tenant_admins WHERE id=? FOR UPDATE');
        $q->execute([$admin['id']]);
        companyLoginRequire($db,true);
        $q=$db->prepare('SELECT id FROM tenant_companies WHERE id=? AND is_active=1 FOR UPDATE');
        $q->execute([$company]);
        if (!$q->fetchColumn()) throw new DomainException('Bedriften er ikke lenger tilgjengelig. Velg en annen.',409);
        $db->prepare('UPDATE tenant_admins SET company_id=? WHERE id=? AND tenant_staff_id IS NULL AND is_active=1')
            ->execute([$company,$admin['id']]);
        $a=companyLoginAccount($db,(string)$admin['id'],true);
        if (!companyLoginValidAccount($a,false)||(string)$a['company_id']!==$company) throw new DomainException('Tilgangen er endret.',409);
        $old=session_id();
        if (!@session_regenerate_id(true)) throw new CompanySessionFailure('php_session_rotation_failed',503,false);
        $rotatedSession=true;
        $db->prepare('UPDATE tenant_admin_sessions SET session_id=?,last_activity=NOW() WHERE session_id=? AND tenant_admin_id=? AND is_active=1')
            ->execute([session_id(),$old,$admin['id']]);
        $check=$db->prepare('SELECT COUNT(*) FROM tenant_admin_sessions
            WHERE tenant_admin_id=? AND BINARY session_id=BINARY ? AND is_active=1');
        $check->execute([$admin['id'],session_id()]);
        if ((int)$check->fetchColumn()!==1) throw new CompanySessionFailure('registry_write_mismatch',503,false);
        companyLoginComplete($a);
        companySessionPersist();
        $db->commit();
        return $a;
    };
    $a=$db instanceof AuditedPDO ? $db->withExplicitAudit($operation) : $operation();
    echo json_encode(['success'=>true,'redirect'=>'/pages/login_transition.php']);
} catch (Throwable $e) {
    try {
        if (isset($db) && $db && $db->inTransaction()) $db->rollBack();
    } catch (Throwable $cleanup) {
        companySessionLog($cleanup,'choice_rollback');
    }
    if ($rotatedSession) {
        if (session_status()!==PHP_SESSION_ACTIVE) @session_start();
        companyLoginClearSession($db??null,false);
    }
    if ($e instanceof CompanySessionFailure) companySessionRespondFailure($e,$db??null,'company_choice',true);
    if (!$e instanceof DomainException) companySessionLog($e,'company_choice');
    if ($e instanceof PDOException && (string)$e->getCode()==='23000') {
        $e=new DomainException('Kontoen kolliderer med en eksisterende konto i denne bedriften. Velg en annen bedrift eller kontakt administrator.',409);
    }
    http_response_code($e instanceof DomainException ? ($e->getCode()?:400) : 503);
    echo json_encode(['success'=>false,'message'=>$e instanceof DomainException?$e->getMessage():'Bedriftsvalget kunne ikke lagres. Prøv igjen.']);
}
