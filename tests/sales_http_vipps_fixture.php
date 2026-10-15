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
            if(($body['customer']['phoneNumber']??null)==='4740000000')
                return ['code'=>403,'data'=>['extraDetails'=>[['name'=>'ErrorCode','reason'=>'5080']],'detail'=>'fixture-private-value']];
            $phone=$body['customer']['phoneNumber']??'';
            if(($body['userFlow']??null)!=='PUSH_MESSAGE' || !in_array($phone,['4749999999','4740000001','4740000002','4740000003','4740000004','4740000005'],true))
                return ['code'=>400,'data'=>['title'=>'Invalid fixture push']];
            $payment??=['reference'=>$reference,'amount'=>$body['amount'],'state'=>'CREATED','checks'=>0,'phone'=>$phone,
                'aggregate'=>['capturedAmount'=>['currency'=>'NOK','value'=>0],'refundedAmount'=>['currency'=>'NOK','value'=>0]]];
        } elseif(!$payment)return ['code'=>404,'data'=>[]];
        elseif($method==='GET') {
            $payment['checks']++;
            $phone=$payment['phone']??'4749999999';
            if($payment['state']==='CREATED') {
                if($phone==='4740000004' && in_array($payment['checks'],[2,3],true)) {
                    file_put_contents($file,json_encode($payment,JSON_THROW_ON_ERROR),LOCK_EX);
                    return ['code'=>503,'data'=>[]];
                }
                if($payment['checks']>=2 && $phone==='4749999999')$payment['state']='AUTHORIZED';
                if($payment['checks']>=3 && $phone==='4740000001')$payment['state']='ABORTED';
                if($payment['checks']>=3 && $phone==='4740000003')$payment['state']='EXPIRED';
                if($payment['checks']>=4 && $phone==='4740000004')$payment['state']='ABORTED';
            }
        } elseif(str_ends_with($path,'/capture')) {
            $payment['aggregate']['capturedAmount']['value']=$body['modificationAmount']['value'];
        } elseif(str_ends_with($path,'/cancel')) {
            if(($payment['phone']??'')==='4740000005') {
                // A charge may have completed before a cashier's cancel.
                $payment['state']='AUTHORIZED';
                $payment['aggregate']['capturedAmount']['value']=$payment['amount']['value'];
            } else $payment['state']='TERMINATED';
        }
        file_put_contents($file,json_encode($payment,JSON_THROW_ON_ERROR),LOCK_EX);
        return ['code'=>$method==='POST'&&$path===''?201:200,'data'=>$payment];
    }
}
