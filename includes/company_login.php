<?php
declare(strict_types=1);
require_once __DIR__.'/company_session_support.php';

/** Existing password auth, with a separate, non-tenant-authenticated choice stage. */
function companyLoginIsPending(): bool {
    return !empty($_SESSION['verified_company_user']) && empty($_SESSION['tenant_admin_id']);
}
function companyLoginContext(): string { return (string) ($_SESSION['company_context'] ?? ''); }
const COMPANY_LOGIN_IDLE_SECONDS = 1800;

/** The legacy connector may wrap PDO failures in a generic Exception. */
function companyLoginDatabase(): PDO {
    try {
        $db=getDBConnection();
        if (!$db instanceof PDO) throw new RuntimeException('Database unavailable');
        return $db;
    } catch (CompanySessionFailure $e) {
        throw $e;
    } catch (Throwable $e) {
        throw new CompanySessionFailure('database_unavailable',503,false);
    }
}

function companyLoginClearSession(?PDO $db = null, bool $expireCookie = true): void {
    $adminId = (string) ($_SESSION['tenant_admin_id'] ?? $_SESSION['verified_company_user'] ?? '');
    if ($db && session_id() !== '') {
        try {
            $db->prepare('UPDATE tenant_admin_sessions SET is_active=0,logout_at=NOW()
                WHERE session_id=? AND is_active=1'.($adminId!==''?' AND tenant_admin_id=?':''))
                ->execute($adminId!==''?[session_id(),$adminId]:[session_id()]);
        } catch (Throwable $cleanupError) {
            error_log('Unable to retire invalid company session.');
        }
    }
    $_SESSION = [];
    if (session_status() !== PHP_SESSION_ACTIVE) return;

    // An old API response must not delete a newer login's shared cookie.
    if ($expireCookie && ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $cookie = [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
        ];
        if (!empty($params['domain'])) $cookie['domain'] = $params['domain'];
        if (!empty($params['samesite'])) $cookie['samesite'] = $params['samesite'];
        setcookie(session_name(), '', $cookie);
    }
    session_destroy();
}

function companyLoginAccount(PDO $db, string $id, bool $lock=false): array {
    $q = $db->prepare('SELECT a.*, c.name AS company_name,c.subdomain,c.is_active AS company_active,
        s.id AS staff_found,s.company_id AS staff_company,s.is_active AS staff_active,
        s.first_name,s.last_name,s.email AS staff_email
        FROM tenant_admins a LEFT JOIN tenant_companies c ON c.id=a.company_id
        LEFT JOIN tenant_staff s ON s.id=a.tenant_staff_id WHERE a.id=?'.($lock?' FOR UPDATE':''));
    $q->execute([$id]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: [];
}
function companyLoginValidAccount(array $a, bool $pending): bool {
    if (!$a || !(int)$a['is_active']) return false;
    if ($pending) return $a['tenant_staff_id'] === null && $a['company_id'] === null;
    if (empty($a['company_id']) || !(int)$a['company_active']) return false;
    return $a['tenant_staff_id'] === null || (!empty($a['staff_found'])
        && (int)$a['staff_active'] === 1 && (string)$a['staff_company'] === (string)$a['company_id']);
}

/** Connection-scoped lock also serializes an in-flight old request against a new login. */
function companyLoginLock(PDO $db, string $id): void {
    $q=$db->prepare('SELECT GET_LOCK(?, 5)');
    $q->execute(['company-login:'.substr(hash('sha256',$id),0,40)]);
    if ((int)$q->fetchColumn() !== 1) throw new CompanySessionFailure('request_busy',409,false);
}

function companyLoginRequire(PDO $db, bool $pending=false): array {
    $id=(string)($_SESSION[$pending?'verified_company_user':'tenant_admin_id']??'');
    if ($id==='') throw new CompanySessionFailure('missing_identity');
    if (empty($_SESSION['session_id'])) throw new CompanySessionFailure('missing_session_id');
    if (!hash_equals((string)$_SESSION['session_id'],session_id())) throw new CompanySessionFailure('session_id_mismatch');
    if ($pending && time() >= (int)($_SESSION['company_choice_expires']??0)) throw new CompanySessionFailure('choice_expired');
    if (isset($_SESSION['db_type']) && $_SESSION['db_type'] !== 'mysql') throw new CompanySessionFailure('database_changed');
    $db->prepare('UPDATE tenant_admin_sessions SET is_active=0,logout_at=NOW()
        WHERE tenant_admin_id=? AND session_id=? AND is_active=1
        AND last_activity<=DATE_SUB(NOW(),INTERVAL '.COMPANY_LOGIN_IDLE_SECONDS.' SECOND)')
        ->execute([$id,session_id()]);
    $q=$db->prepare('SELECT '.COMPANY_LOGIN_IDLE_SECONDS.'-TIMESTAMPDIFF(SECOND,last_activity,NOW())
        FROM tenant_admin_sessions WHERE tenant_admin_id=? AND session_id=? AND is_active=1');
    $q->execute([$id,session_id()]);
    $remaining=$q->fetchColumn();
    $a=companyLoginAccount($db,$id);
    if ($remaining===false || (int)$remaining<=0) throw new CompanySessionFailure('registry_expired_or_revoked');
    if (!companyLoginValidAccount($a,$pending)) throw new CompanySessionFailure('account_changed');
    if (!$pending && (string)$a['company_id'] !== (string)($_SESSION['company_id']??'')) throw new CompanySessionFailure('company_changed');
    if (!$pending && isset($_SESSION['company_auth_version'])
        && (string)($a['tenant_staff_id']??'') !== (string)($_SESSION['tenant_staff_id']??'')) throw new CompanySessionFailure('employee_changed');
    $_SESSION['company_idle_expires_at']=time()+min(COMPANY_LOGIN_IDLE_SECONDS,(int)$remaining);
    return $a;
}

/** Only navigation or genuine client activity extends idle time, never polling. */
function companyLoginTouch(PDO $db): void {
    $id=(string)($_SESSION['tenant_admin_id']??$_SESSION['verified_company_user']??'');
    $db->prepare('UPDATE tenant_admin_sessions SET last_activity=NOW()
        WHERE tenant_admin_id=? AND session_id=? AND is_active=1
        AND last_activity>DATE_SUB(NOW(),INTERVAL '.COMPANY_LOGIN_IDLE_SECONDS.' SECOND)')
        ->execute([$id,session_id()]);
    companyLoginRequire($db,companyLoginIsPending());
}

function companyLoginComplete(array $a): void {
    $_SESSION = [
        'tenant_admin_id'=>(string)$a['id'], 'company_id'=>(string)$a['company_id'],
        'tenant_staff_id'=>$a['tenant_staff_id'], 'admin_role'=>$a['role'],
        'admin_username'=>$a['username'], 'admin_email'=>$a['staff_email']??'',
        'admin_first_name'=>$a['first_name']??'', 'admin_last_name'=>$a['last_name']??'',
        'admin_name'=>trim(($a['first_name']??'').' '.($a['last_name']??'')) ?: $a['username'],
        'company_name'=>$a['company_name'],'subdomain'=>$a['subdomain'],
        'session_id'=>session_id(),'logged_in_at'=>time(),'db_type'=>'mysql',
        'company_auth_version'=>1,'company_context'=>bin2hex(random_bytes(24)),
    ];
}

function companyLoginModules(PDO $db, string $admin): array {
    $q=$db->prepare('SELECT DISTINCT sm.id,sm.name,sm.module_key,sm.description,sm.icon,sm.is_active,sm.created_at
        FROM system_modules sm JOIN tenant_admin_modules am ON am.module_id=sm.id
        JOIN tenant_admins a ON a.id=am.tenant_admin_id
        JOIN tenant_company_modules cm ON cm.module_id=sm.id AND cm.company_id=a.company_id
        WHERE a.id=? AND a.is_active=1 AND sm.is_active=1 AND cm.is_active=1 ORDER BY sm.name');
    $q->execute([$admin]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function companyLoginModuleKeys(PDO $db, string $admin): array {
    $q=$db->prepare('SELECT DISTINCT sm.module_key FROM system_modules sm
        JOIN tenant_admin_modules am ON am.module_id=sm.id JOIN tenant_admins a ON a.id=am.tenant_admin_id
        JOIN tenant_company_modules cm ON cm.module_id=sm.id AND cm.company_id=a.company_id
        WHERE a.id=? AND a.is_active=1 AND sm.is_active=1 AND cm.is_active=1');
    $q->execute([$admin]);
    return $q->fetchAll(PDO::FETCH_COLUMN);
}

/** Runs before legacy endpoints query company data, not just on dashboard navigation. */
function companyLoginGuardConnection(PDO $db): void {
    if (PHP_SAPI==='cli' || empty($_SESSION['session_id'])
        || (empty($_SESSION['tenant_admin_id']) && !companyLoginIsPending())) return;
    $path=(string)(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)??'');
    // Password login and logout manage the state themselves (including expired choice).
    if (in_array($path,['/auth/company_auth.php','/auth/logout.php','/api/company_session.php'],true)) return;
    $sessionValidated = false;
    $pending = companyLoginIsPending();
    try {
        companyLoginLock($db,(string)($_SESSION['tenant_admin_id']??$_SESSION['verified_company_user']));
        companyLoginRequire($db,$pending);
        $sessionValidated = true;
        if ($pending && !in_array($path,['/pages/login_transition.php','/api/company_choice.php'],true)) {
            throw new DomainException('Velg bedrift før du fortsetter.',401);
        }
        if (!$pending && !empty($_SESSION['company_auth_version'])) {
            $given=$_SERVER['HTTP_X_COMPANY_CONTEXT']??$_POST['_company_context']??$_GET['_company_context']??null;
            if (($given !== null && (!is_string($given) || !hash_equals(companyLoginContext(),$given)))
                || (($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $given===null)) {
                throw new DomainException('Denne fanen tilhører en tidligere innlogging. Last siden på nytt.',409);
            }
            header('Cache-Control: no-store');
            $_SESSION['admin_role']=companyLoginAccount($db,(string)$_SESSION['tenant_admin_id'])['role'];
            companyLoginRequireRouteModule($db,$path);
        }
        if (!str_starts_with($path,'/api/')) companyLoginInjectContext();
    } catch (DomainException $e) {
        if (!$sessionValidated && $e instanceof CompanySessionFailure) {
            companySessionRespondFailure($e,$db,'connection_guard',str_starts_with($path,'/api/'));
        }
        http_response_code($e->getCode()?:401);
        header('Cache-Control: no-store');
        // A denied route is not an invalid login: keep verified company choices
        // and valid sessions rejected only for module/context permissions.
        if (!$sessionValidated && $e->getCode() === 401) {
            companySessionRespondFailure($e,$db,'connection_guard',str_starts_with($path,'/api/'));
        }
        if (str_starts_with($path,'/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'message'=>$e->getMessage(),
                'error'=>!$sessionValidated && $e->getCode()===401?'session_expired':
                    ($e->getCode()===409?'context_changed':'access_denied')]);
        } elseif (!$sessionValidated && $e->getCode() === 409) {
            companySessionRespondFailure($e,$db,'connection_guard');
        } else {
            header('Location: '.($sessionValidated && $pending
                ? '/pages/login_transition.php' : '/index.php?error=session_expired'));
        }
        exit;
    } catch (Throwable $e) {
        companySessionRespondFailure($e,$db,'connection_guard',str_starts_with($path,'/api/'));
    }
}

/** Existing shared actions retain their own permissions; module-specific routes also
 * require a license in the selected company, never just an old user assignment. */
function companyLoginRequireRouteModule(PDO $db, string $path): void {
    if (!str_starts_with($path,'/api/')) return;
    $name=basename($path,'.php');
    if (str_contains($name,'public') || in_array($name,[
        'create_rental_request','register_event','register_member','get_payment_methods',
        'get_active_payment_methods'],true)) return;
    // Several module actions do not contain their module name. Shared reads may
    // belong to multiple modules; mutations below still require their owner.
    $shared=[
        'get_transactions'=>['sales','economy'],
        'get_single_product'=>['sales'],
        'get_all_products'=>['sales'],
        'get_week_availability'=>['sales','reservation'],
        'find_first_available'=>['sales','reservation'],
        'check_reservation_availability'=>['sales','reservation'],
        'get_membership_plans'=>['membership','events','economy'],
        'get_membership_renewals'=>['membership','economy'],
        'update_renewal_status'=>['membership','economy'],
        'update_member_fields'=>['membership','economy'],
    ];
    $required=$shared[$name]??null;
    if (str_starts_with($path,'/api/services/')) $required=['reservation'];
    $patterns=[
        'rental'=>'/(?:^|_)(?:rental)(?:_|$)/',
        'events'=>'/(?:^|_)(?:event|events|registration|registrations|checkin|guest|attendee)(?:_|$)/',
        'membership'=>'/(?:^|_)(?:membership|member|members|renewal)(?:_|$)/',
        'economy'=>'/(?:^|_)(?:economy|bank|ledger|transaction|transactions|invoice|invoices|pending_payment|pending_payments|payment_gateway|payment_gateways|gateway_fees|vipps|cash_payment)(?:_|$)/',
        'sales'=>'/(?:^|_)(?:sales|sale|cart|purchase|inventory)(?:_|$)/',
        'reservation'=>'/(?:^|_)(?:reservation|reservations)(?:_|$)/',
        'webmail'=>'/(?:^|_)(?:webmail)(?:_|$)/',
    ];
    if ($required===null) {
        foreach ($patterns as $module=>$pattern) if (preg_match($pattern,$name)) {
            $required=[$module];
            break;
        }
    }
    if ($required!==null && !array_intersect($required,companyLoginModuleKeys($db,(string)$_SESSION['tenant_admin_id']))) {
        throw new DomainException('Modulen er ikke tilgjengelig for deg i valgt bedrift.',403);
    }
}

function companyLoginInjectContext(): void {
    static $started=false;
    if ($started) return;
    $started=true;
    $pending=companyLoginIsPending();
    $context=htmlspecialchars($pending?(string)($_SESSION['company_choice_csrf']??''):companyLoginContext(),ENT_QUOTES,'UTF-8');
    $scripts=$pending?'':'<script src="/assets/company_context.js" data-context="'.$context.'"></script>';
    $remaining=max(0,(int)($_SESSION['company_idle_expires_at']??time())-time());
    $version=filemtime(__DIR__.'/../assets/company_session.js');
    $scripts.='<script src="/assets/company_session.js?v='.$version.'" data-context="'.$context.'" data-expires-in="'.$remaining.'" defer></script>';
    ob_start(static fn(string $html): string => preg_replace('/<head(\s[^>]*)?>/i',
        '$0'.$scripts,$html,1));
}
