<?php
// Real orders/ledger/registers; only the Vipps transport is simulated.
declare(strict_types=1);
class SalesDiagnosticVipps extends SalesLifecycleVipps {
    public ?array $createResponse=null,$lookupResponse=null,$modifyResponse=null;
    private bool $created=false;
    public function request(array $c,string $company,string $method,string $path,?array $body=null,?string $key=null): array {
        if($method==='POST' && $path==='') {
            $this->created=true;
            if($this->createResponse) {
                $this->requests[]=[$method,$path,$key,$body];
                return $this->createResponse;
            }
        }
        if($method==='GET' && $this->created && $this->lookupResponse)return $this->lookupResponse;
        if($method==='POST' && $path!=='' && $this->modifyResponse)return $this->modifyResponse;
        return parent::request($c,$company,$method,$path,$body,$key);
    }
}
$diagnosticProvider=new SalesDiagnosticVipps();
$diagnosticProvider->createResponse=['code'=>403,'data'=>['extraDetails'=>[['name'=>'ErrorCode','reason'=>'5080']],'detail'=>'fixture-private-value']];
$diagnosticProvider->lookupResponse=['code'=>403,'data'=>['detail'=>'fixture-private-value']];
[$diagnosticService,$diagnosticProvider,$diagnosticOrder]=$makeAttempt($diagnosticProvider);
$diagnosticId=$diagnosticOrder['id'];$diagnosticStock=$stockNow();
salesAssert($diagnosticOrder['status']==='pending' && $diagnosticOrder['payment_status']==='unknown','Unconfirmed absence never cancels the order or invents payment');
salesAssert($diagnosticOrder['payment_diagnostics']['error_code']==='SALES_VIPPS_PUSH_NOT_ALLOWED'
    && $diagnosticOrder['payment_diagnostics']['operation']==='payment_creation','Create cause not overwritten by lookup forbidden');
$same=$diagnosticService->reconcile($diagnosticId);
salesAssert($same['payment_diagnostics']===$diagnosticOrder['payment_diagnostics'],'Further polling retains creation cause and support reference');
salesAssert($stockNow()===$diagnosticStock && !str_contains(json_encode($same['payment_diagnostics']),'fixture-'),'Unknown outcome retains stock and sanitized diagnosis');
$diagnosticProvider->createResponse=$diagnosticProvider->lookupResponse=null;
$recovered=$diagnosticService->reconcile($diagnosticId);
salesAssert($recovered['payment_diagnostics']===null && $recovered['payment_error']===null,'Valid provider status clears stale creation diagnostics');
$diagnosticProvider->payments['sales-'.$diagnosticId]['state']='AUTHORIZED';
$paidDiagnostic=$diagnosticService->reconcile($diagnosticId);
salesAssert($paidDiagnostic['payment_status']==='paid' && $paidDiagnostic['register_document'],'Recovered capture atomically creates paid order and frozen receipt');
$documentId=$paidDiagnostic['register_document']['id'];
salesAssert($diagnosticService->reconcile($diagnosticId)['register_document']['id']===$documentId,'Retry returns same frozen receipt');
$creates=array_filter($diagnosticProvider->requests,fn($r)=>$r[0]==='POST' && $r[1]==='');
salesAssert(count(array_unique(array_column($creates,2)))===1,'Retry after creation uncertainty keeps the same idempotency key');
$diagnosticService->nextCustomer();

$diagnosticProvider=new SalesDiagnosticVipps();
$diagnosticProvider->lookupResponse=['code'=>200,'data'=>[],'json_valid'=>false];
[$diagnosticService,$diagnosticProvider,$diagnosticOrder]=$makeAttempt($diagnosticProvider);
salesAssert($diagnosticOrder['payment_diagnostics']['error_code']==='SALES_VIPPS_INVALID_RESPONSE'
    && $diagnosticOrder['payment_diagnostics']['operation']==='payment_lookup'
    && $diagnosticOrder['payment_status']==='unknown' && !$diagnosticOrder['register_document'],'Malformed lookup cannot mark paid or issue receipt');
$firstDiagnosis=$diagnosticOrder['payment_diagnostics'];$diagnosticStock=$stockNow();
salesAssert($diagnosticService->reconcile($diagnosticOrder['id'])['payment_diagnostics']===$firstDiagnosis
    && $stockNow()===$diagnosticStock,'Repeated same failure has stable reference and no stock release');
$diagnosticProvider->lookupResponse=null;
$diagnosticService->reconcile($diagnosticOrder['id'],true);

foreach(['payment_capture','payment_cancel'] as $operation) {
    $diagnosticProvider=new SalesDiagnosticVipps();
    [$diagnosticService,$diagnosticProvider,$diagnosticOrder]=$makeAttempt($diagnosticProvider);
    $diagnosticId=$diagnosticOrder['id'];$diagnosticStock=$stockNow();
    if($operation==='payment_capture')$diagnosticProvider->payments['sales-'.$diagnosticId]['state']='AUTHORIZED';
    $diagnosticProvider->modifyResponse=['code'=>429,'data'=>['detail'=>'fixture-private-value']];
    $failed=$diagnosticService->reconcile($diagnosticId,$operation==='payment_cancel');
    salesAssert($failed['payment_diagnostics']['operation']===$operation
        && $failed['payment_diagnostics']['error_code']==='SALES_VIPPS_RATE_LIMITED'
        && $failed['status']==='pending' && $stockNow()===$diagnosticStock,'Mutation failure identifies '.$operation.' without inventing payment/cancellation');
    $diagnosticProvider->modifyResponse=null;
    $fixed=$diagnosticService->reconcile($diagnosticId);
    salesAssert($operation==='payment_capture'?$fixed['payment_status']==='paid':$fixed['payment_status']==='cancelled','Mutation recovers using the existing order');
    if($operation==='payment_capture')$diagnosticService->nextCustomer();
}
$diagnosticProvider=new SalesDiagnosticVipps();
$diagnosticProvider->createResponse=['code'=>503,'data'=>[]];
$diagnosticProvider->lookupResponse=['code'=>403,'data'=>[]];
[$diagnosticService,$diagnosticProvider,$diagnosticOrder]=$makeAttempt($diagnosticProvider);
$diagnosticId=$diagnosticOrder['id'];$reference='sales-'.$diagnosticId;
$diagnosticProvider->createResponse=$diagnosticProvider->lookupResponse=null;
$diagnosticProvider->payments[$reference]=['reference'=>$reference,'amount'=>['currency'=>'NOK','value'=>10000],
    'state'=>'AUTHORIZED','aggregate'=>['capturedAmount'=>['currency'=>'NOK','value'=>0],'refundedAmount'=>['currency'=>'NOK','value'=>0]]];
$diagnosticProvider->modifyResponse=['code'=>403,'data'=>[]];
$newFailure=$diagnosticService->reconcile($diagnosticId);
salesAssert($newFailure['payment_diagnostics']['operation']==='payment_capture'
    && $newFailure['payment_diagnostics']['error_code']==='SALES_VIPPS_FORBIDDEN','Verified lookup lets new capture failure replace older creation cause');
$diagnosticProvider->modifyResponse=null;
salesAssert($diagnosticService->reconcile($diagnosticId)['payment_status']==='paid','Recovered authoritative capture still completes same sale');
echo "Sales safe diagnostics, operation context, retained creation cause, recovery and stable references passed\n";
