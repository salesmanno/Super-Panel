<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/company_login.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
try {
    $method=$_SERVER['REQUEST_METHOD']??'GET';
    if(!in_array($method,['GET','POST'],true)){
        header('Allow: GET, POST');http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'method_not_allowed']);exit;
    }
    $db=getDBConnection();
    if(!$db)throw new RuntimeException('Database unavailable');
    $id=(string)($_SESSION['tenant_admin_id']??$_SESSION['verified_company_user']??'');
    if($id==='')throw new DomainException('Økten din har utløpt. Logg inn på nytt.',401);
    companyLoginLock($db,$id);
    $pending=companyLoginIsPending();
    companyLoginRequire($db,$pending);
    $expected=$pending?(string)($_SESSION['company_choice_csrf']??''):companyLoginContext();
    $body=$method==='POST'
        ?(str_contains($_SERVER['CONTENT_TYPE']??'','application/json')
            ?json_decode(file_get_contents('php://input'),true,8,JSON_THROW_ON_ERROR):$_POST):[];
    if(!is_array($body)){
        http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_request']);exit;
    }
    $given=$_SERVER['HTTP_X_COMPANY_CONTEXT']??($body['_company_context']??null);
    if(!is_string($given)||$expected===''||!hash_equals($expected,$given)){
        http_response_code(409);
        echo json_encode(['success'=>false,'error'=>'context_changed']);exit;
    }
    if($method==='POST'){
        if(!is_array($body)||!in_array($body['action']??null,['activity','logout'],true)){
            http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_action']);exit;
        }
        if($body['action']==='logout'){
            companyLoginClearSession($db,false);
            echo json_encode(['success'=>true,'active'=>false]);exit;
        }
        // Company choice has a fixed lifetime, even if someone keeps clicking.
        if(!$pending)companyLoginTouch($db);
    }
    $expires=(int)$_SESSION['company_idle_expires_at'];
    if($pending)$expires=min($expires,(int)$_SESSION['company_choice_expires']);
    $remaining=max(0,$expires-time());
    session_write_close();
    echo json_encode(['success'=>true,'active'=>true,'expires_in'=>$remaining,'idle_seconds'=>COMPANY_LOGIN_IDLE_SECONDS]);
} catch(DomainException $e){
    if($e->getCode()!==401){
        http_response_code($e->getCode()?:409);
        header('Retry-After: 5');
        echo json_encode(['success'=>false,'error'=>'session_unavailable']);exit;
    }
    companyLoginClearSession($db??null,false);
    http_response_code(401);
    echo json_encode(['success'=>false,'active'=>false,'error'=>'session_expired',
        'message'=>'Økten din er avsluttet eller har utløpt. Logg inn på nytt.']);
} catch(JsonException $e){
    http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_request']);
} catch(Throwable $e){
    // A temporary outage must not destroy a valid authenticated session.
    error_log('Company session check failed; exception='.get_class($e));
    http_response_code(503);
    echo json_encode(['success'=>false,'error'=>'session_unavailable']);
}
