<?php
declare(strict_types=1);
require_once __DIR__.'/SalesRegisterStore.php';

trait SalesRegisterActions {
    private function registerStore(): SalesRegisterStore {
        return new SalesRegisterStore($this->db,$this->company,$this->systemAuth?null:$this->admin);
    }
    private function registerSelection(): ?string {
        $cart=$this->activeCart();
        if(!$cart)$cart=$this->q("SELECT * FROM sales_cart WHERE company_id=? AND session_token=? ORDER BY created_at DESC,id DESC LIMIT 1",[$this->company,$this->sessionToken])->fetch(PDO::FETCH_ASSOC)?:null;
        return $cart?($this->cartMeta($cart)['register_id']??null):null;
    }
    public function registerState(): array {
        $state=['registers'=>[],'selected_register_id'=>null,'period'=>null,'readiness_error'=>null,
            'cashier_name'=>$this->actor['name']??''];
        try {
            $s=$this->registerStore();$s->ready();
            $state['registers']=$s->q('SELECT id,name,register_code,status FROM sales_registers WHERE company_id=? ORDER BY name,id',[$this->company])->fetchAll(PDO::FETCH_ASSOC);
            $state['selected_register_id']=$this->registerSelection();
            if($state['selected_register_id']) {
                $s->register($state['selected_register_id']);
                $state['period']=$s->period($state['selected_register_id']);
            }
        } catch(Throwable $e) {
            $state['readiness_error']=$e instanceof PDOException?'Kassetabellene eller tilgang til integritetsvernet mangler. Kontroller innført kasseoppsett.':$e->getMessage();
        }
        return $state;
    }
    private function registerKey(array $data): string {
        $key=self::text($data['idempotency_key']??'',90);
        if(!preg_match('/^[A-Za-z0-9_-]{16,90}$/D',$key))throw new InvalidArgumentException('Kassehandlingen mangler en gyldig retry-referanse');
        return $key;
    }
    private function selectRegisterInCart(string $id): void {
        if($this->pendingOrderId())throw new SalesConflict('Avklar ventende betaling før du bytter kassapunkt');
        $cart=$this->activeCart(true)??$this->createCart();$meta=$this->cartMeta($cart);
        if(($meta['register_id']??$id)!==$id && $this->cartRows($cart['id']))
            throw new SalesConflict('Fullfør eller tøm salget før bytte av kassapunkt');
        $meta['register_id']=$id;
        $p=$this->registerStore()->period($id,true);
        $meta['register_period_id']=$p['id']??null;
        $this->q('UPDATE sales_cart SET notes=? WHERE company_id=? AND id=?',[self::json($meta),$this->company,$cart['id']]);
    }
    public function registerAction(string $action,array $data): array {
        return $this->transaction(function()use($action,$data){
            $s=$this->registerStore();$s->ready();
            $id=self::text($data['register_id']??'',36);
            if($action==='register_create') {
                $key=$this->registerKey($data);
                $id=substr(hash('sha256',$this->company.'register'.$key),0,36);
                $old=$s->q('SELECT * FROM sales_registers WHERE company_id=? AND id=? FOR UPDATE',[$this->company,$id])->fetch(PDO::FETCH_ASSOC);
                if(!$old) {
                    // transaction() holds the company row: concurrent creates cannot
                    // allocate the same number. Use a current/locking read as
                    // permission checks may already have opened an older RR snapshot.
                    // Retired points still reserve their numbers.
                    $max=0;
                    foreach($s->q('SELECT register_code FROM sales_registers WHERE company_id=? FOR UPDATE',[$this->company])->fetchAll(PDO::FETCH_COLUMN) as $historical) {
                        if(!ctype_digit((string)$historical))continue;
                        $number=ltrim((string)$historical,'0')?:'0';
                        $limit=(string)(PHP_INT_MAX-1);
                        if(strlen($number)>strlen($limit) || (strlen($number)===strlen($limit) && strcmp($number,$limit)>0))
                            throw new DomainException('Eksisterende kassenummer er for stort til automatisk nummerering.');
                        $max=max($max,(int)$number);
                    }
                    $code=(string)($max+1);$name='Kasse '.$code;
                    $s->q("INSERT INTO sales_registers(id,company_id,register_code,name,system_identifier,system_model,system_version,created_at_utc) VALUES(?,?,?,?,?,'Salesman Mobile POS','1.0',UTC_TIMESTAMP(6))",
                        [$id,$this->company,$code,$name,'salesman-'.SalesRegisterStore::id()]);
                    $s->event($id,null,'register.created','create:'.$key,['name'=>$name,'register_code'=>$code]);
                }
                $this->selectRegisterInCart($id);
            } elseif($action==='register_activate') {
                $key=$this->registerKey($data);$r=$s->register($id,true);$s->verify($id);
                if($r['status']==='retired')throw new DomainException('Et avsluttet kassapunkt kan ikke aktiveres igjen');
                if($r['status']==='draft') {
                    $s->q("UPDATE sales_registers SET status='active',activated_at_utc=UTC_TIMESTAMP(6) WHERE company_id=? AND id=?",[$this->company,$id]);
                    $s->event($id,null,'register.activated','activate:'.$key,['register_id'=>$id]);
                }
            } elseif($action==='register_select') {
                $s->register($id,true);$this->selectRegisterInCart($id);
            } elseif($action==='register_open') {
                $key=$this->registerKey($data);$r=$s->register($id,true);$s->verify($id);
                $float=SalesMoney::integer($data['opening_float_minor']??null,0,SalesMoney::MAX);
                $prior=$s->q("SELECT * FROM sales_register_journal WHERE company_id=? AND register_id=? AND idempotency_key=?",[$this->company,$id,'open:'.$key])->fetch(PDO::FETCH_ASSOC);
                if($prior) {
                    if(json_decode($prior['payload'],true)['opening_float_minor']!==$float)throw new SalesConflict('Åpningsreferansen er allerede brukt med annet beløp');
                    return $this->registerState();
                }
                if($r['status']!=='active' || $s->period($id,true))throw new DomainException('Aktiver kassapunktet og avslutt eventuell åpen periode først');
                $period=SalesRegisterStore::id();
                $date=(new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
                $s->q('INSERT INTO sales_register_periods(id,company_id,register_id,business_date,opening_float_minor,opened_by) VALUES(?,?,?,?,?,?)',
                    [$period,$this->company,$id,$date,$float,$this->admin]);
                $s->event($id,$period,'period.opened','open:'.$key,['opening_float_minor'=>$float,'business_date'=>$date]);
                $this->selectRegisterInCart($id);
            } elseif($action==='register_cash') {
                $key=$this->registerKey($data);$binding=$s->openContext($id);
                $amount=SalesMoney::integer($data['amount_minor']??null,1,SalesMoney::MAX);
                $reason=self::text($data['reason']??'',500);$direction=$data['direction']??'';
                if($reason==='' || !in_array($direction,['in','out'],true))throw new InvalidArgumentException('Oppgi inn/ut og begrunnelse');
                $s->event($id,$binding['period_id'],'cash.'.$direction,'cash:'.$key,['amount_minor'=>$amount,'reason'=>$reason]);
            } else throw new InvalidArgumentException('Ukjent kassehandling');
            return $this->registerState();
        });
    }
    public function registerReports(string $id,?string $before=null): array {
        $s=$this->registerStore();$s->ready();$s->register($id);
        if($before!==null && (!ctype_digit($before) || strlen($before)>20 || ltrim($before,'0')===''))
            throw new InvalidArgumentException('Ugyldig referanse for eldre rapporter.');
        $args=[$this->company,$id];$filter='';
        if($before!==null){$filter=' AND sequence_number<?';$args[]=$before;}
        $rows=$s->q("SELECT id,document_type,sequence_number,z_number,period_id,issued_at_utc AS created_at
            FROM sales_register_documents WHERE company_id=? AND register_id=? AND document_type IN ('x_report','z_report')
            $filter ORDER BY sequence_number DESC LIMIT 101",$args)->fetchAll(PDO::FETCH_ASSOC);
        $more=count($rows)>100;if($more)array_pop($rows);
        return ['register_id'=>$id,'documents'=>$rows,'next_before_sequence'=>$more?(string)end($rows)['sequence_number']:null];
    }
    private function checkoutRegister(array $cart): array {
        $id=$this->cartMeta($cart)['register_id']??null;
        if(!$id)throw new DomainException('Velg kassapunkt og åpne kassen før betaling');
        return $this->registerStore()->openContext($id);
    }
    /** Frozen before any provider call; never reconstruct seller data at capture. */
    private function registerSaleSnapshot(array $binding,array $order): array {
        $company=$this->q('SELECT * FROM tenant_companies WHERE id=?',[$this->company])->fetch(PDO::FETCH_ASSOC);
        $seller=[];
        foreach(['name','company_name','org_number','organization_number','org_nr','address','street_address','postal_code','city','tax_registered'] as $key)
            if(array_key_exists($key,$company))$seller[$key]=$company[$key];
        return ['seller'=>$seller,'register'=>array_intersect_key($binding['register'],array_flip(['id','name','register_code','system_identifier','system_model','system_version'])),
            'period'=>array_intersect_key($binding['period'],array_flip(['id','business_date','period_number'])),
            'order'=>$order,'items'=>$order['items'],'payment'=>['method'=>$order['payment_method'],'amount_minor'=>$order['total_minor'],
                'received_minor'=>$order['received_minor'],'change_minor'=>$order['change_minor']]];
    }
    private function registerPaidOrder(string $id,array $control): void {
        if(empty($control['register_binding']))return; // Historical payment: never fabricate fiscal history.
        $s=$this->registerStore();$binding=$control['register_binding'];
        $active=$s->openContext($binding['register_id']);
        if($active['period_id']!==$binding['period_id'])throw new DomainException('Betalingen tilhører en annen oppgjørsperiode; avstemming kreves');
        $payload=$control['register_snapshot'];
        $payload['order']['status']='paid';$payload['order']['payment_status']='paid';
        $payload['payment']['reference']=$control['reference'];
        $payload['payment']['state']=$control['provider_state'];
        $s->issue($binding,'sale','sale:'.$id,$payload,$payload['order']['total_minor'],$id);
    }
    private function registerCartEvent(string $type,array $cart,string $key,array $payload): void {
        $meta=$this->cartMeta($cart);$id=$meta['register_id']??null;
        if(!$id)return; // A not-yet-assigned basket cannot be paid.
        $binding=$this->registerStore()->openContext($id);
        $this->registerStore()->event($id,$binding['period_id'],$type,$key,$payload+['cart_id'=>$cart['id']]);
    }
    private function registerProductEvent(string $type,array $payload): void {
        // Catalogue changes affect every live point in this company. No invented
        // register is assigned before the first point has been created.
        $ids=$this->q("SHOW TABLES LIKE 'sales_registers'")->fetchColumn();
        if(!$ids)return;
        $s=$this->registerStore();$s->ready();
        foreach($s->q("SELECT id FROM sales_registers WHERE company_id=? AND status='active' ORDER BY id",[$this->company])->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $s->register($id,true);$s->verify($id);$p=$s->period($id,true);
            $s->event($id,$p['id']??null,$type,'product:'.SalesRegisterStore::id(),$payload);
        }
    }
    public function registerDocument(string $id): array {
        return ['document'=>$this->registerStore()->document($id)];
    }
    public function registerPrint(array $data): array {
        return $this->transaction(function()use($data){
            $s=$this->registerStore();$doc=$s->document(self::text($data['document_id']??'',36));
            $s->ready();$s->register($doc['register_id'],true);$s->verify($doc['register_id']);
            $intent=$this->registerKey($data);
            $report=in_array($doc['document_type'],['x_report','z_report'],true);
            $key=$report?'print-report:'.$intent:'print-document:'.$doc['id'];
            $prior=$s->q('SELECT payload FROM sales_register_journal WHERE company_id=? AND register_id=? AND idempotency_key=?',[$this->company,$doc['register_id'],$key])->fetchColumn();
            if(!$report && $prior && json_decode($prior,true)['intent']!==$intent)
                throw new DomainException('Utskriftsforsøket er allerede registrert. Vis dokumentet på skjermen eller bruk en merket kopi. Nettleseren kan ikke bekrefte fysisk utskrift.');
            $s->event($doc['register_id'],null,'document.print_requested',$key,
                ['document_id'=>$doc['id'],'intent'=>$intent,'document_type'=>$doc['document_type'],
                    'notice'=>'Browser print requested; physical printing not confirmed'],$doc['order_id']);
            return ['document'=>$doc,'print_attempt_recorded'=>true];
        });
    }
    private function registerRequestHash(array $data): string {
        unset($data['csrf_token']);ksort($data);return hash('sha256',self::json($data));
    }
    private function registerDocumentRetry(string $register,string $key,array $data): ?array {
        $s=$this->registerStore();
        $id=$s->q('SELECT d.id FROM sales_register_documents d JOIN sales_register_journal j ON j.id=d.journal_id AND j.company_id=d.company_id WHERE d.company_id=? AND d.register_id=? AND j.idempotency_key=?',[$this->company,$register,$key])->fetchColumn();
        if(!$id)return null;
        $doc=$s->document($id);
        if(($doc['payload']['request_hash']??'')!==$this->registerRequestHash($data))throw new SalesConflict('Referansen er brukt med andre opplysninger');
        return $doc;
    }
    public function registerCopy(array $data): array {
        return $this->transaction(function()use($data){
            $s=$this->registerStore();$source=$s->document(self::text($data['document_id']??'',36));
            $key='copy:'.$this->registerKey($data);
            if($old=$this->registerDocumentRetry($source['register_id'],$key,$data))return ['document'=>$old];
            if(!in_array($source['document_type'],['sale','return','delivery'],true))throw new DomainException('Bare originalkvitteringer kan kopieres');
            $binding=$s->openContext($source['register_id']);
            $payload=['original'=>$source,'label'=>'KOPI','request_hash'=>$this->registerRequestHash($data)];
            return ['document'=>$s->issue($binding,'copy',$key,$payload,(int)$source['total_minor'],$source['order_id'],$source['id'])];
        });
    }
    private function registerReportData(array $binding): array {
        $s=$this->registerStore();$s->verify($binding['register_id']);
        $rows=$s->q('SELECT * FROM sales_register_documents WHERE company_id=? AND register_id=? AND period_id=? ORDER BY sequence_number',
            [$this->company,$binding['register_id'],$binding['period_id']])->fetchAll(PDO::FETCH_ASSOC);
        $report=['register'=>$binding['register'],'period'=>$binding['period'],'total_minor'=>0,'tax_minor'=>0,
            'subtotal_minor'=>0,'sales_count'=>0,'returns_count'=>0,'discount_minor'=>0,'payments'=>[],'tax'=>[],'events'=>[],
            'sales_total_minor'=>0,'returns_total_minor'=>0,
            'opening_float_minor'=>(int)$binding['period']['opening_float_minor'],'cash_in_minor'=>0,'cash_out_minor'=>0];
        foreach($rows as $row) {
            if(!in_array($row['document_type'],['sale','return'],true))continue;
            $p=$s->document($row['id'])['payload'];$isReturn=$row['document_type']==='return';
            $report[$isReturn?'returns_count':'sales_count']++;
            $report[$isReturn?'returns_total_minor':'sales_total_minor']+=(int)$row['total_minor'];
            $report['total_minor']+=(int)$row['total_minor'];
            $method=$p['payment']['method'];$report['payments'][$method]=($report['payments'][$method]??0)+(int)$row['total_minor'];
            foreach($p['items'] as $item) {
                $tax=$item['tax_minor'];$net=$item['subtotal_minor'];$rate=(string)$item['tax_rate'];
                $report['tax_minor']+=$tax;$report['subtotal_minor']+=$net;
                $report['tax'][$rate]??=['tax_minor'=>0,'subtotal_minor'=>0,'total_minor'=>0];
                $report['tax'][$rate]['tax_minor']+=$tax;$report['tax'][$rate]['subtotal_minor']+=$net;
                $report['tax'][$rate]['total_minor']+=$item['total_minor'];
                $report['discount_minor']+=$item['list_price_minor']*$item['quantity']-$item['total_minor'];
            }
        }
        $journal=$s->q('SELECT event_type,payload FROM sales_register_journal WHERE company_id=? AND register_id=? AND period_id=? ORDER BY sequence_number',
            [$this->company,$binding['register_id'],$binding['period_id']])->fetchAll(PDO::FETCH_ASSOC);
        $open=null;
        foreach($journal as $j) {
            $p=json_decode($j['payload'],true,64,JSON_THROW_ON_ERROR);$report['events'][$j['event_type']]=($report['events'][$j['event_type']]??0)+1;
            if($j['event_type']==='period.opened')$open=$p;
            if($j['event_type']==='cash.in')$report['cash_in_minor']+=$p['amount_minor'];
            if($j['event_type']==='cash.out')$report['cash_out_minor']+=$p['amount_minor'];
        }
        if(!$open || $open['opening_float_minor']!==$report['opening_float_minor'] || $open['business_date']!==$binding['period']['business_date'])
            throw new RuntimeException('Periodens åpningsgrunnlag samsvarer ikke med journalen');
        $report['expected_cash_minor']=$report['opening_float_minor']+($report['payments']['cash']??0)+$report['cash_in_minor']-$report['cash_out_minor'];
        $report['grand_totals']=$s->q("SELECT document_type,COALESCE(SUM(total_minor),0) AS total_minor,COUNT(*) AS document_count FROM sales_register_documents WHERE company_id=? AND register_id=? AND document_type IN ('sale','return') GROUP BY document_type",[$this->company,$binding['register_id']])->fetchAll(PDO::FETCH_ASSOC);
        return $report;
    }
    public function registerReport(array $data): array {
        return $this->transaction(function()use($data){
            $s=$this->registerStore();$id=self::text($data['register_id']??'',36);$s->ready();$s->register($id,true);
            $key='report:'.$this->registerKey($data);
            if($old=$this->registerDocumentRetry($id,$key,$data))return ['document'=>$old];
            $binding=$s->openContext($id);$kind=$data['kind']??'';
            if(!in_array($kind,['x_report','z_report'],true))throw new InvalidArgumentException('Velg X- eller Z-rapport');
            if($kind==='z_report') {
                // Includes all operators, not just the authenticated user's cart.
                $pending=$s->q("SELECT j.order_id FROM sales_register_journal j JOIN sales_orders o ON o.id=j.order_id AND o.company_id=j.company_id WHERE j.company_id=? AND j.register_id=? AND j.period_id=? AND o.status='pending' LIMIT 1",[$this->company,$id,$binding['period_id']])->fetchColumn();
                $cart=$s->q("SELECT c.id FROM sales_cart c WHERE c.company_id=? AND c.status='active' AND JSON_VALID(c.notes) AND JSON_UNQUOTE(JSON_EXTRACT(c.notes,'$.register_id'))=? AND EXISTS(SELECT 1 FROM sales_cart_items i WHERE i.cart_id=c.id) LIMIT 1",[$this->company,$id])->fetchColumn();
                if($pending || $cart)throw new SalesConflict('Avklar alle åpne salg og betalinger på kassapunktet før Z-oppgjør');
            }
            $report=$this->registerReportData($binding);$report['request_hash']=$this->registerRequestHash($data);
            if($kind==='z_report') {
                $count=SalesMoney::integer($data['counted_cash_minor']??null,0,SalesMoney::MAX);$reason=self::text($data['reason']??'',500);
                $difference=$count-$report['expected_cash_minor'];
                if($difference!==0 && $reason==='')throw new InvalidArgumentException('Forklar avviket mellom opptalt og forventet kontantbeholdning');
                $report+=['counted_cash_minor'=>$count,'cash_difference_minor'=>$difference,'reason'=>$reason];
                $s->q("UPDATE sales_register_periods SET status='closing' WHERE company_id=? AND id=?",[$this->company,$binding['period_id']]);
            }
            $doc=$s->issue($binding,$kind,$key,$report,$report['total_minor']);
            if($kind==='z_report')$s->q("UPDATE sales_register_periods SET status='closed',closed_by=?,expected_cash_minor=?,counted_cash_minor=?,closing_z_document_id=? WHERE company_id=? AND id=?",
                [$this->admin,$report['expected_cash_minor'],$count,$doc['id'],$this->company,$binding['period_id']]);
            return ['document'=>$doc];
        });
    }
    public function registerReturn(array $data): array {
        return $this->transaction(function()use($data){
            $s=$this->registerStore();$source=$s->orderDocument(self::text($data['order_id']??'',36));
            if(!$source)throw new DomainException('Historiske salg uten kassedokument kan ikke etterkonstrueres som kasseretur');
            $key='return:'.$this->registerKey($data);
            if($old=$this->registerDocumentRetry($source['register_id'],$key,$data))return ['document'=>$old];
            $binding=$s->openContext($source['register_id']);$order=$this->orderRow($source['order_id'],true);
            $reason=self::text($data['reason']??'',500);$payload=$source['payload'];
            if($reason==='' || $order['status']!=='paid' || $payload['payment']['method']!=='cash')
                throw new DomainException('Oppgi begrunnelse. Bare full retur av et betalt kontantsalg støttes her; Vipps må refunderes og bekreftes separat.');
            if($s->q("SELECT id FROM sales_register_documents WHERE company_id=? AND source_document_id=? AND document_type='return'",[$this->company,$source['id']])->fetchColumn())
                throw new SalesConflict('Salget er allerede returnert');
            $payload['payment']['amount_minor']=-(int)$source['total_minor'];$payload['reason']=$reason;
            $payload['payment']['received_minor']=0;$payload['payment']['change_minor']=0;
            $payload['payment']['refund_minor']=(int)$source['total_minor'];
            $payload['period']=array_intersect_key($binding['period'],array_flip(['id','business_date','period_number']));
            $payload['order']['status']='refunded';$payload['order']['payment_status']='refunded';
            foreach(['total_minor','subtotal_minor','tax_minor'] as $field)$payload['order'][$field]=-$payload['order'][$field];
            $payload['request_hash']=$this->registerRequestHash($data);$payload['source_document_id']=$source['id'];
            foreach($payload['items'] as &$item)foreach(['quantity','total_minor','subtotal_minor','tax_minor'] as $field)$item[$field]=-$item[$field];
            unset($item);
            $doc=$s->issue($binding,'return',$key,$payload,-(int)$source['total_minor'],$source['order_id'],$source['id']);
            $ledger=new UnifiedLedgerService($this->db,$this->company);
            foreach($this->orderItems($source['order_id']) as $item) {
                $this->q('UPDATE sales_products SET stock_quantity=stock_quantity+? WHERE company_id=? AND id=?',[(int)$item['quantity'],$this->company,$item['product_id']]);
                $result=$ledger->logTransaction(['order_id'=>$doc['id'],'module_source'=>'sales','item_type'=>'product','source_id'=>$item['id'],
                    'item_name'=>'Retur: '.$item['description'],'quantity'=>(int)$item['quantity'],'selling_price'=>SalesMoney::decimal(-SalesMoney::minor($item['line_total'])),
                    'cost_price'=>SalesMoney::decimal(-SalesMoney::minor($item['cost_price']??'0')*(int)$item['quantity']),
                    'payment_method'=>'cash','payment_status'=>'completed','transaction_type'=>'income','category'=>'sales',
                    'metadata'=>['original_order_id'=>$source['order_id'],'register_document_id'=>$doc['id'],'reason'=>$reason]]);
                if(empty($result['success']))throw new RuntimeException('Returen kunne ikke regnskapsføres');
            }
            $this->q("UPDATE sales_orders SET status='refunded' WHERE company_id=? AND id=?",[$this->company,$source['order_id']]);
            return ['document'=>$doc];
        });
    }
}
