<?php
declare(strict_types=1);
// Provider-only stub for the disposable HTTP/MySQL test. Never uses cURL,
// the application's database configuration, or a real Vipps endpoint.
final class SalesHttpVippsFixture extends SalesVipps {
    public function __construct(private string $directory) {}
    public function request(array $c,string $company,string $method,string $path,?array $body=null,?string $idempotency=null):array {
        $this->credentials($c,$company);
        $reference=$path===''?($body['reference']??''):explode('/',trim($path,'/'))[0];
        if(!preg_match('/^sales-[a-f0-9-]{36}$/D',$reference))throw new RuntimeException('Fixture reference invalid');
        $file=$this->directory.'/provider-'.hash('sha256',$reference).'.json';
        $payment=is_file($file)?json_decode(file_get_contents($file),true,32,JSON_THROW_ON_ERROR):null;
        if($method==='POST' && $path==='') {
            if(($body['userFlow']??null)!=='PUSH_MESSAGE' || ($body['customer']['phoneNumber']??null)!=='4749999999')
                return ['code'=>400,'data'=>['title'=>'Invalid fixture push']];
            $payment??=['reference'=>$reference,'amount'=>$body['amount'],'state'=>'CREATED','checks'=>0,
                'aggregate'=>['capturedAmount'=>['currency'=>'NOK','value'=>0],'refundedAmount'=>['currency'=>'NOK','value'=>0]]];
        } elseif(!$payment)return ['code'=>404,'data'=>[]];
        elseif($method==='GET') {
            $payment['checks']++;
            if($payment['checks']>=2 && $payment['state']==='CREATED')$payment['state']='AUTHORIZED';
        } elseif(str_ends_with($path,'/capture')) {
            $payment['aggregate']['capturedAmount']['value']=$body['modificationAmount']['value'];
        } elseif(str_ends_with($path,'/cancel'))$payment['state']='TERMINATED';
        file_put_contents($file,json_encode($payment,JSON_THROW_ON_ERROR),LOCK_EX);
        return ['code'=>$method==='POST'&&$path===''?201:200,'data'=>$payment];
    }
}
