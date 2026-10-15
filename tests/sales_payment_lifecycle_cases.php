<?php
// Included only by the socket-only, disposable Sales MySQL fixture.
declare(strict_types=1);
class SalesLifecycleVipps extends SalesFixtureVipps {
    public bool $rejectCreate=false,$loseCreate=false;
    public ?Closure $afterGet=null;
    public function request(array $c,string $company,string $method,string $path,?array $body=null,?string $key=null): array {
        if($method==='POST' && $path==='' && ($this->rejectCreate || $this->loseCreate)) {
            $this->requests[]=[$method,$path,$key,$body];
            if($this->loseCreate)throw new RuntimeException('Simulated lost provider response');
            return ['code'=>403,'data'=>['title'=>'PUSH_MESSAGE flow not allowed','detail'=>'Never expose fixture-private-value']];
        }
        $result=parent::request($c,$company,$method,$path,$body,$key);
        if($method==='GET' && $this->afterGet) {
            $callback=$this->afterGet;$this->afterGet=null;$callback();
        }
        return $result;
    }
}
$lifecycleProduct=$sales->saveProduct(['name'=>'Payment lifecycle','selling_price'=>'100','stock_quantity'=>40])['product'];
$makeAttempt=function(?SalesLifecycleVipps $p=null)use($db,$lifecycleProduct,$newMutation):array{
    $p??=new SalesLifecycleVipps();
    $service=new SalesService($db,'a','admin','lifecycle-'.bin2hex(random_bytes(8)),$p);
    salesFixtureRegister($service);
    $cart=$service->cartResult()['cart'];
    $cart=$service->mutateCart('add',['product_id'=>$lifecycleProduct['id'],'quantity'=>1,'form_data'=>[],
        'revision'=>$cart['revision'],'mutation_id'=>$newMutation()])['cart'];
    $request=['revision'=>$cart['revision'],'expected_total_minor'=>10000,'payment_method'=>'vipps','gateway_id'=>'gateway-a','phone'=>'49 99 99 99'];
    $view=$service->checkout($request)['order'];
    return [$service,$p,$view,$request];
};
$setPast=function(string $id)use($setup):void {
    $q=$setup->prepare("UPDATE unified_ledger SET metadata=JSON_SET(metadata,'$.sales.expires_at',?) WHERE order_id=? AND company_id='a'");
    $q->execute([time()-1,$id]);
};
$stockNow=fn()=>(int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id='".$lifecycleProduct['id']."'")->fetchColumn();

[$s,$p,$o,$request]=$makeAttempt();
salesAssert($s->checkout($request)['order']['expires_at']===$o['expires_at'],'Retries do not extend 80-second deadline');
$stock=$stockNow();$setPast($o['id']);$before=count($p->requests);
$expired=$s->reconcile($o['id']);
salesAssert($expired['payment_status']==='expired' && $stockNow()===$stock+1,'Deadline cancels provider and releases stock only after confirmed terminal state');
salesAssert(count(array_filter(array_slice($p->requests,$before),fn($r)=>$r[0]==='POST' && str_ends_with($r[1],'/capture')))===0,'Expired attempt never starts capture');
$s->reconcile($o['id']);salesAssert($stockNow()===$stock+1,'Repeated expiry does not release stock twice');
salesAssert($s->cartResult()['cart']['items'][0]['product_id']===$lifecycleProduct['id'],'Expiry restores the original cart');

[$s,$p,$o]=$makeAttempt();$p->payments['sales-'.$o['id']]['state']='AUTHORIZED';$setPast($o['id']);
salesAssert($s->reconcile($o['id'])['payment_status']==='expired','Authorization arriving after deadline is cancelled, not captured');

[$s,$p,$o]=$makeAttempt();$ref='sales-'.$o['id'];$stock=$stockNow();
$p->payments[$ref]['aggregate']['capturedAmount']['value']=10000;$setPast($o['id']);
salesAssert($s->reconcile($o['id'])['payment_status']==='paid' && $stockNow()===$stock,'Late server-confirmed full capture wins over timeout without restoring stock');

[$s,$p,$o]=$makeAttempt();$ref='sales-'.$o['id'];$p->payments[$ref]['state']='AUTHORIZED';$p->captureNow=false;
salesAssert($s->reconcile($o['id'])['payment_status']==='authorized','Capture in flight is not marked paid early');
$setPast($o['id']);$p->captureNow=true;
salesAssert($s->reconcile($o['id'])['payment_status']==='paid','Predeadline capture decision is preserved across timeout');

[$s,$p,$o]=$makeAttempt();$setPast($o['id']);$stock=$stockNow();$p->outage=true;
salesAssert($s->reconcile($o['id'])['payment_status']==='unknown' && $stockNow()===$stock,'Timeout plus outage is not false cancellation or payment');
salesReject(fn()=>$s->nextCustomer(),'Unconfirmed timeout blocks duplicate sale');
$p->outage=false;salesAssert($s->reconcile($o['id'])['payment_status']==='expired','Timed-out attempt recovers to confirmed expiry after outage');

$p=new SalesLifecycleVipps();$p->rejectCreate=true;[$s,$p,$o]=$makeAttempt($p);
salesAssert($o['payment_status']==='declined' && str_contains($o['payment_error'],'PUSH_MESSAGE'),'Rejected push reports actionable salgssted approval error');
salesAssert(!str_contains(json_encode($o),'fixture-private-value') && $s->cartResult()['cart']['total_minor']===10000,'Provider diagnostics are sanitized and rejected cart is restored');

$p=new SalesLifecycleVipps();$p->loseCreate=true;[$s,$p,$o]=$makeAttempt($p);$setPast($o['id']);
$before=count($p->requests);$unknown=$s->reconcile($o['id']);
salesAssert($unknown['payment_status']==='unknown','Lost create response cannot be treated as confirmed cancellation');
salesAssert(count(array_filter(array_slice($p->requests,$before),fn($r)=>$r[0]==='POST' && $r[1]===''))===0,'No new provider payment is created after deadline');

[$s,$p,$o]=$makeAttempt();$ref='sales-'.$o['id'];$p->payments[$ref]['amount']['value']=9999;$stock=$stockNow();
salesAssert($s->reconcile($o['id'])['payment_status']==='unknown' && $stockNow()===$stock,'Mismatched provider amount never completes or releases reservation');
$p->payments[$ref]['amount']['value']=10000;$setPast($o['id']);$s->reconcile($o['id']);

[$s,$p,$o]=$makeAttempt();
$lock='sales:'.substr(hash('sha256','a|'.$o['id']),0,58);
$q=$setup->prepare('SELECT GET_LOCK(?,0)');$q->execute([$lock]);salesAssert((int)$q->fetchColumn()===1,'Fixture owns a competing provider-operation lock');
$before=count($p->requests);$s->reconcile($o['id']);
salesAssert(count($p->requests)===$before,'Concurrent callback/poll does not issue competing HTTP operations');
$q=$setup->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$lock]);$q->closeCursor();
$setPast($o['id']);
$cronAuth=SalesWebhookAuth::forCron($db,$o['id']);$_SESSION=[];
$cron=new SalesService($db,'a','','',$p,$cronAuth);
salesAssert($cron->reconcile($o['id'])['payment_status']==='expired','Existing cron capability expires payment without a live cashier session');
$_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin'];
foreach(['ABORTED','TERMINATED','EXPIRED'] as $terminalState) {
    [$s,$p,$o]=$makeAttempt();$ref='sales-'.$o['id'];$reserved=$stockNow();
    $p->payments[$ref]['state']=$terminalState;
    $cancelled=$s->reconcile($o['id']);
    $restored=$s->cartResult()['cart'];
    salesAssert($cancelled['status']==='cancelled' && !$cancelled['register_document']
        && $restored['items'][0]['quantity']===1 && $stockNow()===$reserved+1,
        "$terminalState restores editable goods without a fiscal sale");
    $s->reconcile($o['id']);salesAssert($stockNow()===$reserved+1,"$terminalState releases stock exactly once");
    $cash=$s->checkout(['revision'=>$restored['revision'],'expected_total_minor'=>$restored['total_minor'],
        'payment_method'=>'cash','received_minor'=>$restored['total_minor']])['order'];
    salesAssert($cash['payment_status']==='paid' && $cash['register_document']
        && $cash['items'][0]['product_id']===$lifecycleProduct['id'] && $stockNow()===$reserved,
        "$terminalState can continue as the same goods paid in cash");
    $s->reconcile($o['id']);$same=$s->reconcile($cash['id']);
    salesAssert($same['register_document']['id']===$cash['register_document']['id']
        && $stockNow()===$reserved && !$s->cartResult()['cart']['items'],
        "$terminalState repeated old notification cannot reopen a paid cart or duplicate stock");
    $q=$setup->prepare("SELECT COUNT(*) FROM sales_register_documents WHERE company_id='a' AND order_id IN (?,?) AND document_type='sale'");
    $q->execute([$o['id'],$cash['id']]);
    salesAssert((int)$q->fetchColumn()===1,"$terminalState plus cash retry issues exactly one sale document");
}
echo "Sales push/deadline/timeout/capture-race/diagnostic/concurrency/cron cases passed\n";
