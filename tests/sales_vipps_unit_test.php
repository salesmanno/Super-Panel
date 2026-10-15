<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/SalesVipps.php';
function vippsCheck(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);}
final class SalesVippsTransportFixture extends SalesVipps {
    public array $calls=[];
    public bool $denyAuth=false;
    public array $replies=[];
    protected function send(string $url,string $method,array $headers,?array $body):array {
        $this->calls[]=[$url,$method,$headers,$body];
        if($this->replies) {
            $reply=array_shift($this->replies);
            if($reply instanceof Throwable)throw $reply;
            return $reply;
        }
        if(str_ends_with($url,'/accesstoken/get'))
            return $this->denyAuth?['code'=>401,'data'=>['detail'=>'fixture-private-value']]:
                ['code'=>200,'data'=>['access_token'=>'fixture-token']];
        return ['code'=>201,'data'=>['reference'=>'fixture-reference']];
    }
}
$config=['configuration_id'=>'fixture-config','company_id'=>'fixture-company','test_mode'=>0,
    'client_id'=>'fixture-client','client_secret_encrypted'=>'fixture-secret','subscription_key'=>'fixture-subscription',
    'merchant_serial_number'=>'fixture-merchant','webhook_secret'=>'fixture-signing-secret'];
$provider=new SalesVippsTransportFixture();
$payload=['userFlow'=>'PUSH_MESSAGE','customer'=>['phoneNumber'=>'4799999999']];
$provider->request($config,'fixture-company','POST','',$payload,'fixture-idempotency');
vippsCheck($provider->calls[0][0]==='https://api.vipps.no/accesstoken/get','Live configuration uses live token host');
vippsCheck($provider->calls[1][0]==='https://api.vipps.no/epayment/v1/payments'
    && $provider->calls[1][3]===$payload,'Push body reaches ePayment API without redirect');
vippsCheck(in_array('Idempotency-Key: fixture-idempotency',$provider->calls[1][2],true),'Idempotency header preserved');
$test=array_replace($config,['test_mode'=>1]);$provider->request($test,'fixture-company','GET','/fixture');
vippsCheck(str_starts_with($provider->calls[2][0],'https://apitest.vipps.no/'),'Test keys never use production API');
$provider->denyAuth=true;
try {$provider->request($config,'fixture-company','GET','/fixture');throw new LogicException('Expected denied authentication');}
catch(SalesVippsFailure $e) {
    vippsCheck(str_contains($e->getMessage(),'test-/produksjonsmiljø')
        && !str_contains($e->getMessage(),'fixture-private-value'),'Authentication failure is safe and actionable');
}
$pushError=$provider->paymentError(['code'=>403,'data'=>['title'=>'PUSH_MESSAGE flow not allowed','detail'=>'fixture-private-value']]);
vippsCheck(str_contains($pushError,'godkjent') && !str_contains($pushError,'fixture-private-value'),'No raw provider values in push diagnostic');
vippsCheck(str_contains($provider->paymentError(['code'=>422,'data'=>[]]),'mobilnummer'),'Invalid request diagnostic');
vippsCheck(str_contains($provider->paymentError(['code'=>429,'data'=>[]]),'mange forespørsler'),'Rate limit diagnostic');
$private='fixture-private-value 4799999999 Bearer fixture-token fixture-secret';
$cases=[
    [401,['message'=>'Access denied due to invalid subscription key. '.$private],'invalid_subscription_key'],
    [401,['message'=>'Access denied due to missing subscription key. '.$private],'missing_subscription_key'],
    [401,['message'=>'Subscription not found. '.$private],'subscription_not_found'],
    [401,['error_description'=>'Token has expired. '.$private],'token_expired'],
    [401,['detail'=>'Invalid access token. '.$private],'invalid_token'],
    [403,['title'=>'Merchant Not Allowed for Ecommerce Payment '.$private],'ecommerce_not_enabled'],
    [403,['extraDetails'=>[['name'=>'ErrorCode','reason'=>'5080']],'detail'=>$private],'push_not_allowed'],
    [400,['extraDetails'=>[['name'=>'ErrorCode','reason'=>'4070']],'detail'=>$private],'invalid_phone_number'],
    [409,['detail'=>$private],'reference_conflict'],[429,['detail'=>$private],'rate_limited'],
    [503,['detail'=>$private],'provider_unavailable'],
    [200,[],'invalid_response'],[0,[],'network_failure'],
];
foreach($cases as [$status,$data,$reason]) {
    $failure=$provider->failure(['code'=>$status,'data'=>$data],'payment_creation','test');
    $diagnostic=$failure->diagnostics();
    vippsCheck($failure->reason===$reason && $diagnostic['operation']==='payment_creation'
        && $diagnostic['environment']==='test' && $diagnostic['provider_http_status']===$status,'Classifies '.$reason);
    vippsCheck(preg_match('/^[a-f0-9]{16}$/D',$diagnostic['support_reference'])===1,'Safe random support reference');
    vippsCheck(count($diagnostic)===5 && !preg_match('/fixture-|4799999999|Bearer/',json_encode($diagnostic).$failure->getMessage()),'No raw values in '.$reason);
    vippsCheck(SalesVippsFailure::fromDiagnostics($diagnostic)?->getMessage()===$failure->getMessage(),'Diagnostic restores without changing its reference');
    ksort($diagnostic);
    vippsCheck(SalesVippsFailure::fromDiagnostics($diagnostic)?->getMessage()===$failure->getMessage(),'MySQL JSON key ordering preserves reference');
}
vippsCheck(SalesVippsFailure::fromDiagnostics(['error_code'=>$private])===null,'Untrusted persisted diagnostics fail closed');
$scripted=new SalesVippsTransportFixture();
$scripted->replies=[['code'=>200,'data'=>[],'json_valid'=>false]];
try {$scripted->request($test,'fixture-company','POST','',$payload);throw new LogicException('Malformed token must fail');}
catch(SalesVippsFailure $e){vippsCheck($e->reason==='invalid_response' && $e->operation==='access_token' && $e->environment==='test','Malformed token has correct stage and environment');}
$scripted->replies=[new RuntimeException($private)];
try {$scripted->request($config,'fixture-company','GET','/fixture');throw new LogicException('Network must fail');}
catch(SalesVippsFailure $e){vippsCheck($e->reason==='network_failure' && $e->operation==='access_token' && !str_contains($e->getMessage(),'fixture-'),'Token transport exception sanitized');}
$scripted->replies=[['code'=>200,'data'=>['access_token'=>'fixture-token']],new RuntimeException($private)];
try {$scripted->request($test,'fixture-company','POST','/fixture/capture',[],'same-key');throw new LogicException('Capture network must fail');}
catch(SalesVippsFailure $e){vippsCheck($e->reason==='network_failure' && $e->operation==='payment_capture' && $e->environment==='test','Capture transport stage preserved');}
$scripted->replies=[['code'=>200,'data'=>['access_token'=>'fixture-token']],['code'=>403,'data'=>['title'=>'Forbidden']]];
$denied=$scripted->request($config,'fixture-company','POST','',$payload,'same-key');
vippsCheck($denied['code']===403 && $scripted->failure($denied,'payment_creation')->environment==='production','HTTP create failure returned for authoritative reconciliation with correct environment');
$body='{"reference":"fixture-reference"}';$date=gmdate('D, d M Y H:i:s').' GMT';
$hash=base64_encode(hash('sha256',$body,true));$path='/api/sales_vipps_webhook.php?source=vipps';
$server=['HTTP_X_MS_DATE'=>$date,'HTTP_HOST'=>'fixture.invalid','REQUEST_URI'=>$path,'HTTP_X_MS_CONTENT_SHA256'=>$hash,
    'HTTP_AUTHORIZATION'=>'HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature='.
        base64_encode(hash_hmac('sha256',"POST\n$path\n$date;fixture.invalid;$hash",$config['webhook_secret'],true))];
vippsCheck($provider->verifyWebhook($config,'fixture-company',$body,$server),'Stored per-company secret verifies callback');
vippsCheck(!$provider->verifyWebhook($config,'other-company',$body,$server),'Signing secret cannot bypass company binding');
vippsCheck(!$provider->verifyWebhook($config,'fixture-company',$body.' ',$server),'Changed body rejected');
vippsCheck(!$provider->verifyWebhook(array_replace($config,['webhook_secret'=>null]),'fixture-company',$body,$server),'Missing stored secret fails closed');
$old=$server;$old['HTTP_X_MS_DATE']=gmdate('D, d M Y H:i:s',time()-600).' GMT';
vippsCheck(!$provider->verifyWebhook($config,'fixture-company',$body,$old),'Old callback timestamp rejected');
unset($config['webhook_secret']);
vippsCheck($provider->configured($config,'fixture-company'),'Webhook secret is not required to initiate or poll a payment');
echo "Sales Vipps transport, environment, signing and safe diagnostic unit checks passed\n";
