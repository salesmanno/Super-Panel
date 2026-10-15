<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/SalesService.php';
header('Cache-Control: no-store');
header('Content-Type: application/json');
try {
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')throw new RuntimeException('Ugyldig webhook');
    $body=file_get_contents('php://input',false,null,0,65537);
    if(strlen($body)>65536)throw new RuntimeException('Ugyldig webhook');
    $db=getDBConnection();$provider=new SalesVipps();
    $auth=SalesWebhookAuth::verify($db,$provider,$body,$_SERVER);
    $service=new SalesService($db,$auth->company,'','',$provider,$auth);
    // Ignore payload state/amount. Fetch authoritative current provider state.
    $service->reconcile($auth->orderId);
    echo '{"success":true}';
} catch(Throwable $e) {
    http_response_code(403);echo '{"success":false,"message":"Webhook kunne ikke bekreftes."}';
}
