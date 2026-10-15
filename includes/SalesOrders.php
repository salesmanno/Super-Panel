<?php
declare(strict_types=1);

trait SalesOrders {
    private function orderRow(string $id,bool $lock=false): array {
        $row=$this->q('SELECT * FROM sales_orders WHERE id=? AND company_id=?'.($lock?' FOR UPDATE':''),[$id,$this->company])->fetch(PDO::FETCH_ASSOC);
        if(!$row)throw new OutOfBoundsException('Ordren finnes ikke');
        return $row;
    }
    private function orderLedger(string $id,bool $lock=false): array {
        return $this->q("SELECT * FROM unified_ledger WHERE company_id=? AND module_source='sales' AND order_id=? ORDER BY source_id".($lock?' FOR UPDATE':''),[$this->company,$id])->fetchAll(PDO::FETCH_ASSOC);
    }
    private function orderItems(string $id): array {
        return $this->q('SELECT i.* FROM sales_orders_items i JOIN sales_orders o ON o.id=i.order_id WHERE o.id=? AND o.company_id=? ORDER BY i.id',[$id,$this->company])->fetchAll(PDO::FETCH_ASSOC);
    }
    private function control(array $ledger): array {
        if(!$ledger)throw new RuntimeException('Ordren mangler regnskapsføring. Avstemming kreves.');
        $metadata=json_decode($ledger[0]['metadata']??'',true,32,JSON_THROW_ON_ERROR);
        if(($metadata['sales']['version']??null)!==1)throw new RuntimeException('Ordren mangler sikkert betalingsgrunnlag. Avstemming kreves.');
        return $metadata['sales'];
    }
    private function writeControl(string $id,array $control,?string $paymentStatus=null): void {
        foreach($this->orderLedger($id,true) as $row) {
            $metadata=json_decode($row['metadata'],true,32,JSON_THROW_ON_ERROR);
            $metadata['sales']=array_replace($metadata['sales'],$control);
            $json=self::json($metadata);
            $status=$paymentStatus==='unknown'?$row['payment_status']:($paymentStatus??$row['payment_status']);
            if($status==='pending' && $row['payment_status']==='authorized')$status='authorized';
            if($json!==$row['metadata'] || $status!==$row['payment_status']) {
                $write=fn()=>$this->q('UPDATE unified_ledger SET metadata=?,payment_status=? WHERE id=? AND company_id=?',[$json,$status,$row['id'],$this->company]);
                if($status===$row['payment_status'] && method_exists($this->db,'withExplicitAudit'))$this->db->withExplicitAudit($write);else$write();
                if($status!==$row['payment_status'] && $this->systemAuth)tenantAuditChange($this->db,$this->company,'sales','update','unified_ledger',$row['id'],$row['item_name'],
                    ['payment_status'=>$row['payment_status']],['payment_status'=>$status],['order_id'=>$id],$this->actor);
            }
        }
    }
    public function orderView(string $id): array {
        $row=$this->orderRow($id);$ledger=$this->orderLedger($id);$items=$this->orderItems($id);
        $safe= $ledger && count($ledger)===count($items);
        $control=$safe?$this->control($ledger):[];
        $origin=$ledger?json_decode($ledger[0]['metadata'],true,32,JSON_THROW_ON_ERROR):[];
        $status=$safe?(string)$ledger[0]['payment_status']:'unknown';
        foreach($ledger as $entry)if($entry['payment_status']!==$status)$status='unknown';
        if($row['status']==='pending' && ($control['provider_state']??'')==='UNKNOWN')$status='unknown';
        $view=['id'=>$id,'order_number'=>$row['order_number'],'created_at'=>$row['created_at'],'status'=>$row['status'],
            'tenant_admin_id'=>$row['created_by'],
            'tenant_admin_name'=>($origin['tenant_admin_id']??null)===$row['created_by']?($origin['tenant_admin_name']??null):null,
            'payment_method'=>$ledger[0]['payment_method']??null,'payment_status'=>$status==='completed'?'paid':$status,
            'provider_state'=>$control['provider_state']??'UNKNOWN','received_minor'=>$control['received_minor']??null,
            'payment_error'=>$control['payment_error']??null,
            'payment_diagnostics'=>$control['payment_diagnostics']??null,
            'expires_at'=>isset($control['expires_at'])?gmdate('Y-m-d\TH:i:s\Z',(int)$control['expires_at']):null,
            'server_time'=>gmdate('Y-m-d\TH:i:s\Z'),
            'change_minor'=>$control['change_minor']??null,'total_minor'=>SalesMoney::minor($row['total']),
            'subtotal_minor'=>SalesMoney::minor($row['subtotal']),'tax_minor'=>SalesMoney::minor($row['tax_total']),'items'=>[]];
        if(!$safe)$view['warning']='Ordren mangler fullstendig regnskapsføring. Avstemming kreves.';
        foreach($items as $item) {
            $view['items'][]=['id'=>$item['id'],'product_id'=>$item['product_id'],'description'=>$item['description'],'sku'=>$item['sku'],
                'quantity'=>(int)$item['quantity'],'notes'=>$item['notes'],'form_data'=>json_decode($item['form_data']??'{}',true,32,JSON_THROW_ON_ERROR),
                'unit_price_minor'=>SalesMoney::minor($item['unit_price']),'list_price_minor'=>SalesMoney::minor($item['list_price']),
                'discount_percent'=>$item['discount_percent'],'tax_rate'=>$item['tax_rate'],'tax_minor'=>SalesMoney::minor($item['tax_amount']),
                'subtotal_minor'=>SalesMoney::minor($item['line_subtotal']),'total_minor'=>SalesMoney::minor($item['line_total'])];
        }
        $view['register_document']=!empty($control['register_binding'])?$this->registerStore()->orderDocument($id):null;
        if($row['status']==='refunded')$view['payment_status']='refunded';
        return $view+$this->receiptEmailCapability();
    }
    public function orders(): array {
        // One grouped read, never one remote database roundtrip per receipt.
        $rows=$this->q("SELECT o.*,COUNT(l.id) AS ledger_count,COUNT(DISTINCT l.payment_status) AS status_count,
            MIN(l.payment_status) AS ledger_status,MIN(l.payment_method) AS method,SUM(l.selling_price) AS ledger_total,
            (SELECT COUNT(*) FROM sales_orders_items i WHERE i.order_id=o.id) AS item_count,
            MAX(CASE WHEN JSON_VALID(l.metadata) THEN JSON_UNQUOTE(JSON_EXTRACT(l.metadata,'$.sales.provider_state')) ELSE 'UNKNOWN' END) AS provider_state
            FROM sales_orders o JOIN unified_ledger l ON l.company_id=o.company_id AND l.order_id=o.id AND l.module_source='sales'
            WHERE o.company_id=? GROUP BY o.id ORDER BY o.created_at DESC,o.id DESC LIMIT 200",[$this->company])->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static function($row){
            $consistent=(int)$row['ledger_count']===(int)$row['item_count'] && (int)$row['status_count']===1
                && SalesMoney::minor($row['ledger_total'])===SalesMoney::minor($row['total']);
            $status=$consistent?$row['ledger_status']:'unknown';
            if($row['status']==='pending' && $row['provider_state']==='UNKNOWN')$status='unknown';
            return ['id'=>$row['id'],'order_number'=>$row['order_number'],'created_at'=>$row['created_at'],'status'=>$row['status'],
                'payment_status'=>$status==='completed'?'paid':$status,'payment_method'=>$row['method'],'provider_state'=>$row['provider_state'],
                'total_minor'=>SalesMoney::minor($row['total']),'subtotal_minor'=>SalesMoney::minor($row['subtotal']),
                'tax_minor'=>SalesMoney::minor($row['tax_total'])];
        },$rows);
    }
    public function checkout(array $data): array {
        $id=$this->transaction(function()use($data){
            if($pending=$this->pendingOrderId())return $pending; // retry resumes, never reinitiates a different payment
            $cart=$this->activeCart(true);
            if(!$cart) {
                $prior=$this->q("SELECT * FROM sales_cart WHERE company_id=? AND session_token=? AND status='converted' ORDER BY converted_at DESC,id DESC LIMIT 1",[$this->company,$this->sessionToken])->fetch(PDO::FETCH_ASSOC);
                if($prior && ($this->cartMeta($prior)['checkout_revision']??null)===($data['revision']??null))return $prior['converted_order_id'];
                throw new SalesConflict('Kurven finnes ikke lenger. Kontroller siste salg før du prøver igjen.');
            }
            $meta=$this->cartMeta($cart);
            if(!is_string($data['revision']??null) || !hash_equals($meta['revision'],$data['revision']))
                throw new SalesConflict('Kurven er endret. Kontroller oppdatert kurv før betaling.');
            $lines=$this->cartRows($cart['id'],true);
            if(!$lines)throw new InvalidArgumentException('Handlekurven er tom');
            $binding=$this->checkoutRegister($cart);
            $method=$data['payment_method']??'cash';
            if(!in_array($method,['cash','vipps'],true))throw new InvalidArgumentException('Betalingsmetoden støttes ikke i kassen');
            $config=$method==='vipps'?$this->paymentConfiguration(self::text($data['gateway_id']??'',36)):null;
            $phone='';
            if($method==='vipps') {
                $phone=preg_replace('/[\s+]/','',self::text($data['phone']??'',30));
                if(preg_match('/^[49]\d{7}$/D',$phone))$phone='47'.$phone;
                if(!preg_match('/^47[49]\d{7}$/D',$phone))throw new InvalidArgumentException('Oppgi et gyldig norsk mobilnummer');
            }
            $products=[];$quantities=[];$snapshots=[];$total=0;$net=0;$tax=0;
            // Deterministic row locking; all repeated product lines are counted.
            $ids=array_unique(array_column($lines,'product_id'));sort($ids);
            foreach($ids as $productId)$products[$productId]=$this->product($productId,true);
            foreach($lines as $line) {
                $p=$products[$line['product_id']];$qty=SalesMoney::integer((string)$line['quantity'],1);
                if((int)$p['is_active']!==1 || !in_array($p['visibility'],['kiosk','both'],true) || $p['currency']!=='NOK')
                    throw new InvalidArgumentException('En vare er ikke lenger tilgjengelig i kassen');
                $answers=json_decode($line['form_data']??'{}',true,32,JSON_THROW_ON_ERROR);
                $answers=validateEntityFormValues($this->fields($p['id']),$answers);
                $money=SalesMoney::line($p,$qty,$this->taxRegistered);
                $quantities[$p['id']]=($quantities[$p['id']]??0)+$qty;
                $snapshots[]=['id'=>self::uuid(),'product'=>$p,'quantity'=>$qty,'notes'=>$line['notes'],'answers'=>$answers,'money'=>$money];
                $total+=$money['total_minor'];$net+=$money['subtotal_minor'];$tax+=$money['tax_minor'];
            }
            if($total>SalesMoney::MAX)throw new InvalidArgumentException('Ordrebeløpet overstiger regnskapsgrensen');
            if(SalesMoney::integer($data['expected_total_minor']??null,0,SalesMoney::MAX)!==$total)
                throw new SalesConflict('Prisene er endret. Kontroller oppdatert totalsum før betaling.');
            $received=null;
            if($method==='cash') {
                $received=SalesMoney::integer($data['received_minor']??null,0,SalesMoney::MAX);
                if($received<$total)throw new InvalidArgumentException('Mottatt kontantbeløp er for lavt');
            } elseif($total<100 || $total>65000000)throw new InvalidArgumentException('Vipps-beløpet må være mellom 1 og 650 000 kr. Nullbeløp avsluttes uten betalingsleverandør.');
            foreach($quantities as $productId=>$qty) {
                $changed=$this->q('UPDATE sales_products SET stock_quantity=stock_quantity-?,updated_at=NOW() WHERE id=? AND company_id=? AND stock_quantity>=?',[$qty,$productId,$this->company,$qty])->rowCount();
                if($changed!==1)throw new SalesConflict('En vare har ikke tilstrekkelig lager. Kurven er bevart.');
            }
            $id=self::uuid();$number='S-'.date('Ymd').'-'.strtoupper(str_replace('-','',$id));
            $this->q('INSERT INTO sales_orders(id,company_id,order_number,guest_phone,channel,status,payment_gateway_id,source_reference,subtotal,tax_total,total,currency,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$id,$this->company,$number,$phone?:null,'kiosk',$method==='cash'?'paid':'pending',$config['id']??null,'cart:'.$cart['id'],
                    SalesMoney::decimal($net),SalesMoney::decimal($tax),SalesMoney::decimal($total),'NOK',$this->admin]);
            $control=['version'=>1,'cart_id'=>$cart['id'],'reference'=>'sales-'.$id,'configuration_id'=>$config['configuration_id']??null,
                'register_binding'=>['register_id'=>$binding['register_id'],'period_id'=>$binding['period_id']],
                'account_binding'=>$config?$this->provider->binding($config,$this->company):null,'decision'=>null,
                'provider_state'=>$method==='cash'?'CASH_CONFIRMED':'PREPARED','init_state'=>'prepared',
                'expires_at'=>$method==='vipps'?time()+80:null,
                'received_minor'=>$received,'change_minor'=>$received===null?null:$received-$total];
            $ledger=new UnifiedLedgerService($this->db,$this->company);
            foreach($snapshots as $snapshot) {
                $p=$snapshot['product'];$m=$snapshot['money'];$lineId=$snapshot['id'];
                $cost=SalesMoney::minor($p['cost_price']??'0');
                if($cost*$snapshot['quantity']>SalesMoney::MAX)throw new InvalidArgumentException('Kostbeløpet overstiger regnskapsgrensen');
                $this->q('INSERT INTO sales_orders_items(id,order_id,product_id,description,sku,unit,quantity,list_price,discount_percent,unit_price,tax_rate,tax_amount,line_subtotal,line_total,cost_price,notes,form_data) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$lineId,$id,$p['id'],$p['name'],$p['sku'],$p['unit'],$snapshot['quantity'],SalesMoney::decimal($m['list_price_minor']),
                        $m['discount_percent'],SalesMoney::decimal($m['unit_price_minor']),$m['tax_rate'],SalesMoney::decimal($m['tax_minor']),
                        SalesMoney::decimal($m['subtotal_minor']),SalesMoney::decimal($m['total_minor']),SalesMoney::decimal($cost),$snapshot['notes'],self::json($snapshot['answers'])]);
                $result=$ledger->logTransaction(['order_id'=>$id,'module_source'=>'sales','item_type'=>'product','source_id'=>$lineId,
                    'item_name'=>$p['name'],'quantity'=>$snapshot['quantity'],'selling_price'=>SalesMoney::decimal($m['total_minor']),
                    'cost_price'=>SalesMoney::decimal($cost*$snapshot['quantity']),'payment_method'=>$method,
                    'payment_status'=>$method==='cash'?'completed':'pending','transaction_type'=>'income','category'=>'sales',
                    'metadata'=>['sales'=>$control+['product_id'=>$p['id'],'reserved_quantity'=>$snapshot['quantity']],'tax_minor'=>$m['tax_minor']]]);
                if(empty($result['success']))throw new RuntimeException('Regnskapsføringen feilet. Salget er ikke fullført.');
            }
            $control['register_snapshot']=$this->registerSaleSnapshot($binding,$this->orderView($id));
            $this->writeControl($id,['register_snapshot'=>$control['register_snapshot']]);
            $this->registerStore()->event($binding['register_id'],$binding['period_id'],'order.prepared','order:'.$id,
                ['order_id'=>$id,'total_minor'=>$total,'method'=>$method,'cart_id'=>$cart['id']],$id);
            if($method==='cash')$this->registerPaidOrder($id,$control);
            $meta['checkout_revision']=$meta['revision'];
            $this->q("UPDATE sales_cart SET status='converted',converted_order_id=?,converted_at=NOW(),notes=?,updated_at=NOW() WHERE id=? AND company_id=?",
                [$id,self::json($meta),$cart['id'],$this->company]);
            return $id;
        });
        $view=$this->orderView($id);
        return ['order'=>$view['status']==='pending'?$this->reconcile($id):$view];
    }
    private function orderConfiguration(array $order,array $control): array {
        $config=$this->q('SELECT id AS configuration_id,company_id,gateway_type,test_mode,
            client_id,client_secret_encrypted,subscription_key,merchant_serial_number FROM economy_configuration_data WHERE id=? AND company_id=?',
            [$control['configuration_id'],$this->company])->fetch(PDO::FETCH_ASSOC);
        if(!$config || self::salesPaymentType($config['gateway_type'])!=='vipps' || !hash_equals((string)$control['account_binding'],$this->provider->binding($config,$this->company)))
            throw new RuntimeException('Vipps-kontoen for den ventende betalingen er endret. Avstemming kreves.');
        return $config;
    }
    private function decidePayment(string $id,string $decision): bool {
        return $this->transaction(function()use($id,$decision){
            $order=$this->orderRow($id,true);if($order['status']!=='pending')return false;
            $control=$this->control($this->orderLedger($id,true));
            if($decision==='capture' && ($control['expires_at']??PHP_INT_MAX)<=time())$decision='cancel';
            if($control['decision']!==null && $control['decision']!==$decision)return false;
            if($control['decision']===null)$this->writeControl($id,['decision'=>$decision]);
            return true;
        });
    }
    public function reconcile(string $id,bool $cancel=false): array {
        if($this->systemAuth && $this->systemAuth->orderId!==$id)throw new TenantAuditException('Systemtilgang gjelder en annen ordre');
        $this->orderRow($id);
        // Serialise HTTP operations across poll/callback/cron without keeping a
        // transaction open. A timed-out create must not race a cancellation.
        $lock='sales:'.substr(hash('sha256',$this->company.'|'.$id),0,58);
        if((int)$this->q('SELECT GET_LOCK(?,0)',[$lock])->fetchColumn()!==1)return $this->orderView($id);
        try {return $this->reconcileLocked($id,$cancel);}
        finally {$this->q('SELECT RELEASE_LOCK(?)',[$lock]);}
    }
    private function validatedPayment(array $remote,string $ref,int $total,string $environment='unknown'): array {
        if($remote['code']!==200 || ($remote['json_valid']??true)!==true)
            throw $this->provider->failure($remote,'payment_lookup',$environment);
        $body=$remote['data'];
        if(($body['reference']??null)!==$ref || ($body['amount']['currency']??null)!=='NOK'
            || ($body['amount']['value']??null)!==$total)throw new SalesVippsFailure('payment_mismatch','payment_lookup',200,$environment);
        $captured=$body['aggregate']['capturedAmount']['value']??null;
        if(!is_int($captured) || $captured<0 || ($body['aggregate']['capturedAmount']['currency']??null)!=='NOK'
            || ($body['aggregate']['refundedAmount']['value']??0)!==0
            || ($body['aggregate']['refundedAmount']['currency']??'NOK')!=='NOK')
            throw new SalesVippsFailure('invalid_payment_state','payment_lookup',200,$environment);
        $state=$body['state']??'UNKNOWN';
        if(is_array($state))$state=count($state)===1?$state[0]:'UNKNOWN';
        if(!in_array($state,['CREATED','AUTHORIZED','ABORTED','EXPIRED','TERMINATED'],true))
            throw new SalesVippsFailure('invalid_payment_state','payment_lookup',200,$environment);
        return [$state,$captured];
    }
    private function paymentNote(string $id,?SalesVippsFailure $failure,bool $preserveCreation=true): void {
        $this->transaction(function()use($id,$failure,$preserveCreation){
            if($this->orderRow($id,true)['status']!=='pending')return;
            $old=$this->control($this->orderLedger($id,true))['payment_diagnostics']??null;
            if($failure && is_array($old)) {
                // Polling cannot erase the creation cause until a valid lookup
                // establishes the payment's actual state.
                if($preserveCreation && !in_array($failure->reason,['payment_mismatch','invalid_payment_state'],true)
                    && in_array($failure->operation,['payment_lookup','access_token'],true)
                    && ($old['operation']??null)==='payment_creation')
                    $failure=SalesVippsFailure::fromDiagnostics($old)??$failure;
                $current=$failure->diagnostics();unset($current['support_reference']);
                $prior=$old;unset($prior['support_reference']);
                ksort($current);ksort($prior);
                if($current===$prior)$failure=$failure->withReference((string)($old['support_reference']??''));
            }
            $this->writeControl($id,['payment_error'=>$failure?->getMessage(),'payment_diagnostics'=>$failure?->diagnostics()]);
        });
    }
    private function reconcileLocked(string $id,bool $cancel): array {
        $order=$this->orderRow($id);if($order['status']!=='pending')return $this->orderView($id);
        $this->transaction(function()use($id){
            if($this->orderRow($id,true)['status']!=='pending')return;
            $control=$this->control($this->orderLedger($id,true));
            if(!isset($control['expires_at']))$this->writeControl($id,['expires_at'=>time()+80]);
        });
        $control=$this->control($this->orderLedger($id));$ref=$control['reference'];$total=SalesMoney::minor($order['total']);
        if($cancel || (int)$control['expires_at']<=time())$this->decidePayment($id,'cancel');
        $control=$this->control($this->orderLedger($id));
        // Respect a capture committed before timeout. Never overwrite it with
        // cancellation while Vipps may already be debiting the customer.
        $cancel=$control['decision']==='cancel';
        $expired=$cancel && (int)$control['expires_at']<=time();
        $path='/'.rawurlencode($ref);
        $environment='unknown';$operation='payment_lookup';$precedingFailure=null;$verifiedLookup=false;
        try {
            $config=$this->orderConfiguration($order,$control);
            $environment=(int)$config['test_mode']===1?'test':'production';
            $remote=$this->provider->request($config,$this->company,'GET',$path);
            if($remote['code']===404 && $cancel && $control['init_state']==='prepared')
                return $this->applyRemote($id,$expired?'EXPIRED':'TERMINATED',0,true);
            if($remote['code']===404 && !$cancel) {
                // The status lookup itself can consume the remaining window.
                if((int)$control['expires_at']<=time()) {
                    $this->decidePayment($id,'cancel');
                    if($control['init_state']==='prepared')return $this->applyRemote($id,'EXPIRED',0,true);
                    throw new RuntimeException('Tidligere betalingsopprettelse må avstemmes');
                }
                $this->transaction(fn()=>$this->writeControl($id,['init_state'=>'creating']));
                $operation='payment_creation';
                $created=$this->provider->request($config,$this->company,'POST','',[
                    'amount'=>['currency'=>'NOK','value'=>$total],'paymentMethod'=>['type'=>'WALLET'],
                    'customer'=>['phoneNumber'=>$order['guest_phone']],'reference'=>$ref,'userFlow'=>'PUSH_MESSAGE',
                    'customerInteraction'=>'CUSTOMER_PRESENT','paymentDescription'=>'Salg '.$order['order_number']],'sales-create-'.$id);
                if(!in_array($created['code'],[200,201],true) || ($created['json_valid']??true)!==true)
                    $precedingFailure=$this->provider->failure($created,'payment_creation',$environment);
                // Stable reference and key are persisted before any external call.
                $operation='payment_lookup';
                $remote=$this->provider->request($config,$this->company,'GET',$path);
                if($remote['code']===404 && in_array($created['code'],[400,401,403,422],true)) {
                    $this->paymentNote($id,$precedingFailure);
                    return $this->applyRemote($id,'REJECTED',0,true);
                }
            }
            [$state,$captured]=$this->validatedPayment($remote,$ref,$total,$environment);
            $verifiedLookup=true;
            // A valid authoritative lookup supersedes a failed/ambiguous POST.
            $precedingFailure=null;
            if((int)$control['expires_at']<=time()) {
                $this->decidePayment($id,'cancel');
                $cancel=$this->control($this->orderLedger($id))['decision']==='cancel';
                $expired=$cancel;
            }
            if($captured===$total)return $this->applyRemote($id,'CAPTURED',$captured);
            if($captured!==0)throw new SalesVippsFailure('invalid_payment_state','payment_lookup',200,$environment);
            if(in_array($state,['ABORTED','EXPIRED','TERMINATED'],true))return $this->applyRemote($id,$expired?'EXPIRED':$state,0,true);
            if($state==='AUTHORIZED' || ($cancel && $state==='CREATED')) {
                $this->decidePayment($id,$cancel?'cancel':'capture');
                $decision=$this->control($this->orderLedger($id))['decision'];
                if(in_array($decision,['capture','cancel'],true)) {
                    $op=$decision;
                    $operation=$op==='capture'?'payment_capture':'payment_cancel';
                    $modified=$this->provider->request($config,$this->company,'POST',$path.'/'.$op,
                        $op==='cancel'?null:['modificationAmount'=>['currency'=>'NOK','value'=>$total]],'sales-'.$op.'-'.$id);
                    if($modified['code']<200 || $modified['code']>=300
                        || (($modified['json_valid']??true)!==true && $modified['code']!==204))
                        $precedingFailure=$this->provider->failure($modified,$operation,$environment);
                    // A 200/202 capture request is NOT final proof. Verify GET.
                    $operation='payment_lookup';
                    $confirmed=$this->provider->request($config,$this->company,'GET',$path);
                    [$state,$captured]=$this->validatedPayment($confirmed,$ref,$total,$environment);
                    if($captured===$total)return $this->applyRemote($id,'CAPTURED',$total);
                    if($captured!==0)throw new SalesVippsFailure('invalid_payment_state','payment_lookup',200,$environment);
                    if(in_array($state,['ABORTED','EXPIRED','TERMINATED'],true))
                        return $this->applyRemote($id,(int)$control['expires_at']<=time()?'EXPIRED':$state,0,true);
                    if($precedingFailure)throw $precedingFailure;
                }
            }
            $this->paymentNote($id,null);
            return $this->applyRemote($id,is_string($state)?$state:'UNKNOWN',0);
        } catch(Throwable $e) {
            // Unknown is resumable, not failed/paid. Never release its stock.
            $failure=$e instanceof SalesVippsFailure && in_array($e->reason,['payment_mismatch','invalid_payment_state'],true)?$e:
                ($precedingFailure??($e instanceof SalesVippsFailure?$e:
                new SalesVippsFailure('reconciliation_required',$operation==='payment_lookup'?'payment_reconciliation':$operation,0,$environment)));
            $this->paymentNote($id,$failure,!$verifiedLookup);
            return $this->applyRemote($id,'UNKNOWN',0);
        }
    }
    private function applyRemote(string $id,string $state,int $captured,bool $cancelled=false): array {
        return $this->transaction(function()use($id,$state,$captured,$cancelled){
            $order=$this->orderRow($id,true);
            if($order['status']!=='pending')return $this->orderView($id);
            $control=$this->control($this->orderLedger($id,true));
            if($captured===SalesMoney::minor($order['total']) && $state==='CAPTURED') {
                $this->registerPaidOrder($id,array_replace($control,['provider_state'=>$state]));
                $this->q("UPDATE sales_orders SET status='paid',updated_at=NOW() WHERE id=? AND company_id=?",[$id,$this->company]);
                $this->writeControl($id,['provider_state'=>$state,'init_state'=>'created','payment_error'=>null,'payment_diagnostics'=>null],'completed');
            } elseif($cancelled && $captured===0) {
                if(!empty($control['register_binding'])) {
                    $b=$control['register_binding'];
                    $this->registerStore()->event($b['register_id'],$b['period_id'],'payment.cancelled','cancel:'.$id,['state'=>$state,'order_id'=>$id],$id);
                }
                // The order row lock makes release terminal and exactly-once.
                foreach($this->orderItems($id) as $item) {
                    $product=$this->product($item['product_id'],true);
                    $this->q('UPDATE sales_products SET stock_quantity=stock_quantity+?,updated_at=NOW() WHERE id=? AND company_id=?',[(int)$item['quantity'],$item['product_id'],$this->company]);
                    if($this->systemAuth)tenantAuditChange($this->db,$this->company,'sales','update','sales_products',$product['id'],$product['name'],
                        ['stock_quantity'=>$product['stock_quantity']],['stock_quantity'=>(int)$product['stock_quantity']+(int)$item['quantity']],['order_id'=>$id],$this->actor);
                }
                $this->q("UPDATE sales_orders SET status='cancelled',updated_at=NOW() WHERE id=? AND company_id=?",[$id,$this->company]);
                $this->writeControl($id,['provider_state'=>$state],$state==='EXPIRED'?'expired':($state==='REJECTED'?'declined':'cancelled'));
                $cart=$this->q('SELECT * FROM sales_cart WHERE id=? AND company_id=? FOR UPDATE',[$control['cart_id'],$this->company])->fetch(PDO::FETCH_ASSOC);
                if($cart && $cart['converted_order_id']===$id) {
                    $meta=$this->cartMeta($cart);$meta['revision']=self::uuid();unset($meta['checkout_revision']);
                    $this->q("UPDATE sales_cart SET status='active',converted_order_id=NULL,converted_at=NULL,notes=?,updated_at=NOW() WHERE id=? AND company_id=?",
                        [self::json($meta),$cart['id'],$this->company]);
                }
            } else $this->writeControl($id,['provider_state'=>$state,'checked_at'=>date('Y-m-d H:i:s'),'init_state'=>$state==='UNKNOWN'?$control['init_state']:'created'],
                $state==='UNKNOWN'?'unknown':($state==='AUTHORIZED'?'authorized':'pending'));
            $view=$this->orderView($id);
            if($this->systemAuth && $view['status']!==$order['status'])tenantAuditChange($this->db,$this->company,'sales','update','sales_orders',$id,$order['order_number'],
                ['status'=>$order['status']],['status'=>$view['status']],[],$this->actor);
            return $view;
        });
    }
}
