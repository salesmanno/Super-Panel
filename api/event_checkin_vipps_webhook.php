<?php
declare(strict_types=1);
// May be configured by the separate payment-configuration project. A callback
// is only a wake-up signal: its signature AND authoritative Vipps state are checked.
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/EventCheckin.php';
header('Content-Type: application/json; charset=utf-8');
try {
    if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { http_response_code(405); exit; }
    $body=file_get_contents('php://input',false,null,0,65537);
    if (strlen($body)>65536) throw new RuntimeException('Invalid callback');
    $event=json_decode($body,true,32,JSON_THROW_ON_ERROR);
    $ref=$event['reference']??'';
    if (!is_string($ref) || !preg_match('/^EC-[a-f0-9]{32}$/D',$ref)) throw new RuntimeException('Invalid callback');
    $db=getDBConnection();
    $q=$db->prepare("SELECT company_id,metadata FROM unified_ledger WHERE module_source='events'
        AND JSON_VALID(metadata) AND JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.event_checkin.reference'))=? LIMIT 1");
    $q->execute([$ref]); $row=$q->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Invalid callback');
    $control=json_decode($row['metadata'],true,32,JSON_THROW_ON_ERROR)['event_checkin'];
    $q=$db->prepare('SELECT *,id AS configuration_id FROM economy_configuration_data WHERE id=? AND company_id=?');
    $q->execute([$control['configuration_id'],$row['company_id']]); $config=$q->fetch(PDO::FETCH_ASSOC);
    $provider=new SalesVipps();
    if (!$config || !$provider->verifyWebhook($config,$row['company_id'],$body,$_SERVER)
        || !hash_equals($control['binding'],$provider->binding($config,$row['company_id']))) {
        throw new RuntimeException('Invalid callback');
    }
    // No browser session, no arbitrary caller-supplied cashier.
    $actor=$control['initiated_by']??null;
    if (!is_array($actor) || empty($actor['id'])) throw new RuntimeException('Invalid callback');
    (new EventCheckin($db,$row['company_id'],$actor,$provider))->status($ref,false);
    echo '{"success":true}';
} catch (Throwable $e) {
    http_response_code(400);
    echo '{"success":false}';
}
