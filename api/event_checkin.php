<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/EventCheckin.php';
try {
    if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
        http_response_code(401); throw new InvalidArgumentException('Innloggingen har utløpt.');
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); throw new InvalidArgumentException('Kun POST tillatt.'); }
    $input=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Ugyldig forespørsel.');
    entityFormRequireCsrf($input);
    $db=getDBConnection();
    $actor=entityFormRequireModuleAccess($db,(string)$_SESSION['company_id'],'event');
    $service=new EventCheckin($db,(string)$_SESSION['company_id'],$actor);
    $event=(string)($input['event_id']??'');
    $action=(string)($input['action']??'load');
    $result=match($action) {
        'load'=>$service->load($event),
        'commit'=>$service->commit($event,$input['participant_ids']??[],(string)($input['payer_id']??''),
            (string)($input['method']??''),($input['confirmed']??false)===true),
        'start_vipps'=>$service->start($event,$input['participant_ids']??[],(string)($input['payer_id']??'')),
        'status_vipps','cancel_vipps'=>$service->status((string)($input['reference']??''),$action==='cancel_vipps'),
        default=>throw new InvalidArgumentException('Ukjent handling.')
    };
    echo json_encode(['success'=>true]+$result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    if(http_response_code()===200) http_response_code(422);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
} catch (Throwable $e) {
    error_log('Event checkin failed: '.get_class($e));
    http_response_code(503);
    echo json_encode(['success'=>false,'message'=>'Innsjekkingen eller betalingen kunne ikke bekreftes. Kontroller status før du prøver igjen.']);
}
