<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/SalesVipps.php';
function vippsCheck(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);}
final class SalesVippsTransportFixture extends SalesVipps {
    public array $calls=[];
    public bool $denyAuth=false;
    protected function send(string $url,string $method,array $headers,?array $body):array {
        $this->calls[]=[$url,$method,$headers,$body];
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
