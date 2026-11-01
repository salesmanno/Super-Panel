<?php
declare(strict_types=1);

require_once __DIR__.'/entity_form_fields.php';
require_once __DIR__.'/tenant_customer_contacts.php';
require_once __DIR__.'/event_form_submission.php';
require_once __DIR__.'/active_payment_methods.php';
require_once __DIR__.'/UnifiedLedgerService.php';
require_once __DIR__.'/SalesVipps.php';

/**
 * Event admission and payment. Pending provider requests live in the existing
 * ledger, not in PHP sessions; a new login must not lose an outstanding payment.
 * Provider responses, never browser assertions, authorize electronic payments.
 */
final class EventCheckin {
    public function __construct(private PDO $db, private string $company, private array $actor,
        private ?SalesVipps $provider = null) {
        $this->provider ??= new SalesVipps();
    }
    private function query(string $sql, array $params = []): PDOStatement {
        $q = $this->db->prepare($sql); $q->execute($params); return $q;
    }
    private function transaction(callable $work): mixed {
        $this->db->beginTransaction();
        try {
            activePaymentMethodsLock($this->db, $this->company);
            $result = $work(); $this->db->commit(); return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    private function event(string $id, bool $lock = false): array {
        $e = $this->query('SELECT id,title,start_date,start_time,end_time,venue_name,ticket_price,
            registration_type,payment_methods_json FROM events_eventdata WHERE id=? AND company_id=?'.
            ($lock ? ' FOR UPDATE' : ''), [$id,$this->company])->fetch(PDO::FETCH_ASSOC);
        if (!$e) throw new InvalidArgumentException('Arrangementet finnes ikke i valgt bedrift.');
        $day = (new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
        $e['editable'] = !empty($e['start_date']) && substr($e['start_date'],0,10) <= $day;
        if ($lock && !$e['editable']) throw new InvalidArgumentException('Innsjekking og deltakerendringer åpner på arrangementsdatoen.');
        return $e;
    }
    public static function ids(mixed $ids, string $payer): array {
        if (!is_array($ids) || !$ids || count($ids)>5 || !is_string($payer) || $payer==='') {
            throw new InvalidArgumentException('Velg mellom én og fem deltakere, inkludert betaleren.');
        }
        foreach ($ids as $id) if (!is_string($id) || !preg_match('/^[a-zA-Z0-9-]{1,64}$/D',$id)) {
            throw new InvalidArgumentException('Ugyldig deltaker.');
        }
        if (count(array_unique($ids))!==count($ids) || !in_array($payer,$ids,true)) {
            throw new InvalidArgumentException('Betaleren må være med én gang.');
        }
        return array_merge([$payer],array_values(array_diff($ids,[$payer])));
    }
    private function people(string $event, ?array $ids = null, bool $lock = false): array {
        $email = tenantCustomerContactSql('tc','email');
        $phone = tenantCustomerContactSql('tc','phone');
        $params = [$this->company,$event];
        $sql = "SELECT c.*,tc.first_name,tc.last_name,{$email} AS customer_email,
            {$phone} AS customer_phone FROM events_checkin c
            LEFT JOIN tenant_customers tc ON tc.id=c.customer_id AND tc.company_id=?
            WHERE c.event_id=?";
        if ($ids !== null) {
            $sql .= ' AND c.id IN ('.implode(',',array_fill(0,count($ids),'?')).')';
            $params = array_merge($params,$ids);
        }
        $rows = $this->query($sql.' ORDER BY c.id'.($lock?' FOR UPDATE':''),$params)->fetchAll(PDO::FETCH_ASSOC);
        if ($ids!==null && count($rows)!==count($ids)) throw new InvalidArgumentException('En deltaker tilhører ikke arrangementet.');
        // Batch financial lookups: two network queries per person would make a
        // large roster unusable against the externally hosted database.
        $payments=[];
        foreach (array_chunk(array_column($rows,'id'),400) as $chunk) {
            $slots=implode(',',array_fill(0,count($chunk),'?'));
            $entries=$this->query("SELECT source_id,order_id,status,payment_status,selling_price,metadata
                FROM unified_ledger WHERE company_id=? AND module_source='events'
                AND (source_id IN ($slots) OR order_id IN ($slots)) ORDER BY id".($lock?' FOR UPDATE':''),
                array_merge([$this->company],$chunk,$chunk))->fetchAll(PDO::FETCH_ASSOC);
            foreach($entries as $entry) foreach(array_unique([$entry['source_id'],$entry['order_id']]) as $key) {
                $payments[$key][]=$entry;
            }
        }
        foreach ($rows as &$row) {
            $snapshot=eventGuestFormSnapshot($row['notes']??null);
            if (empty($row['customer_id']) && $snapshot) {
                $row['first_name']=$snapshot['guest']['first_name']??'';
                $row['last_name']=$snapshot['guest']['last_name']??'';
                $row['customer_email']=$snapshot['guest']['email']??'';
                $row['customer_phone']=$snapshot['guest']['phone']??'';
            }
            $row['customer_name']=trim(($row['first_name']??'').' '.($row['last_name']??'')) ?: 'Ukjent deltaker';
            $row['has_valid_ledger']=false; $pending=null;
            foreach($payments[$row['id']]??[] as $entry) {
                if ($entry['status']!=='active') continue;
                if ($entry['payment_status']==='completed' && (float)$entry['selling_price']>=(float)$row['ticket_price']) {
                    $row['has_valid_ledger']=true;
                }
                if ($entry['payment_status']==='pending') {
                    $m=json_decode($entry['metadata']??'',true);
                    if (is_array($m['event_checkin']??null)) $pending=$m;
                }
            }
            $row['pending_reference']=$pending['event_checkin']['reference']??null;
            $control=$pending['event_checkin']??null;
            $row['pending_payment']=$control?array_intersect_key($control,array_flip([
                'payer_id','participant_ids','amount_minor','expires_at'])):null;
        }
        unset($row);
        if ($ids!==null) {
            $byId=array_column($rows,null,'id');
            return array_map(fn($id)=>$byId[$id],$ids);
        }
        return $rows;
    }
    private function methods(array $event): array {
        $allowed=json_decode($event['payment_methods_json']??'[]',true);
        $allowed=array_map('historicalPaymentMethodName',is_array($allowed)?$allowed:[]);
        $active=activePaymentMethods($this->db,$this->company);
        $result=['cash'=>false,'vipps'=>false];
        foreach ($active as $method) {
            $type=$method['method_type'];
            if (isset($result[$type]) && in_array($type,$allowed,true) && $method['is_configured']
                && !($type==='cash' && $event['registration_type']==='binding')) $result[$type]=true;
        }
        try { $this->configuration(); } catch (Throwable $e) { $result['vipps']=false; }
        return $result;
    }
    private function configuration(?string $id = null, bool $new = true): array {
        if (!$new && $id!==null) {
            $c=$this->query("SELECT *,id AS configuration_id FROM economy_configuration_data
                WHERE company_id=? AND id=? AND LOWER(gateway_type)='vipps'",[$this->company,$id])->fetch(PDO::FETCH_ASSOC);
            if (!$c || !$this->provider->configured($c,$this->company)) throw new RuntimeException('Det opprinnelige Vipps-oppsettet må gjenopprettes for å kontrollere betalingen.');
            return $c;
        }
        $economy=$this->query("SELECT 1 FROM tenant_company_modules cm JOIN system_modules sm ON sm.id=cm.module_id
            WHERE cm.company_id=? AND cm.is_active=1 AND sm.is_active=1
            AND sm.module_key IN ('economy','economy_module') LIMIT 1",[$this->company])->fetchColumn();
        if (!$economy) throw new InvalidArgumentException('Bedriften har ikke aktiv Økonomi-modul.');
        $sql="SELECT c.*,c.id AS configuration_id FROM economy_payment_gateways g
            JOIN economy_configuration_data c ON c.id=g.configuration_id AND c.company_id=g.company_id
            WHERE g.company_id=? AND g.is_active=1 AND c.is_active=1 AND LOWER(c.gateway_type)='vipps'";
        $params=[$this->company];
        if ($id!==null) { $sql.=' AND c.id=?'; $params[]=$id; }
        $c=$this->query($sql.' ORDER BY g.id LIMIT 1',$params)->fetch(PDO::FETCH_ASSOC);
        if (!$c || !$this->provider->configured($c,$this->company)) throw new InvalidArgumentException('Vipps er ikke aktivert og konfigurert for bedriften.');
        return $c;
    }
    public function load(string $event): array {
        $e=$this->event($event);
        // Return only roster data needed by the operator, never provider credentials.
        $people=$this->people($event);
        foreach ($people as &$p) $p=array_intersect_key($p,array_flip([
            'id','first_name','last_name','customer_name','customer_email','customer_phone','ticket_price',
            'payment_status','payment_method','checked_in','check_in_time','barcode','has_valid_ledger','pending_reference','pending_payment']));
        return ['event'=>$e,'people'=>$people,'methods'=>$this->methods($e)];
    }
    private function validateGroup(array $rows, bool $previous, ?string $reference = null): void {
        foreach ($rows as $index=>$p) {
            if ((int)$p['checked_in']===1) throw new InvalidArgumentException('En deltaker er allerede sjekket inn. Oppdater oversikten.');
            if ($p['pending_reference'] && $p['pending_reference']!==$reference) {
                throw new InvalidArgumentException('En deltaker har en uavklart Vipps-betaling. Kontroller denne først.');
            }
            if ($index>0 && ((float)$p['ticket_price']<=0 || $p['has_valid_ledger'] || $p['payment_status']==='paid')) {
                throw new InvalidArgumentException('Bare andre ubetalte deltakere kan legges til gruppen.');
            }
            if ($previous && (count($rows)!==1 || $p['payment_status']!=='paid' || empty($p['payment_method']))) {
                throw new InvalidArgumentException('Tidligere betaling mangler en registrert betalingsmåte.');
            }
        }
    }
    private function ledger(array $event,array $p,string $method,string $status,array $meta): string {
        $result=(new UnifiedLedgerService($this->db,$this->company))->logTransaction([
            'order_id'=>$meta['event_checkin']['reference']??('EC-'.bin2hex(random_bytes(12))),
            'module_source'=>'events','item_type'=>'event','source_id'=>$p['id'],'item_name'=>$event['title'],
            'selling_price'=>(float)$p['ticket_price'],'customer_id'=>$p['customer_id']?:null,
            'payment_method'=>$method,'payment_status'=>$status,'status'=>'active',
            'transaction_type'=>'income','category'=>'Arrangement','metadata'=>$meta+[
                'event_id'=>$event['id'],'checkin_id'=>$p['id'],'customer_name'=>$p['customer_name']]]);
        if (empty($result['success'])) throw new RuntimeException('Betalingen kunne ikke journalføres. Ingen innsjekking ble lagret.');
        return $result['ledger_id'];
    }
    private function admit(array $event,array $rows,?string $method,?array $attempt = null): void {
        foreach ($rows as $p) {
            $paid=(float)$p['ticket_price']>0;
            $used=$p['has_valid_ledger']?$p['payment_method']:$method;
            // Both changes are in the same transaction; neither can be observed alone.
            $this->query("UPDATE events_checkin SET checked_in=1,check_in_time=NOW(),checked_in_by=?,
                payment_status='paid',payment_method=?,updated_at=NOW() WHERE id=? AND event_id=?",
                [$this->actor['id'],$used,$p['id'],$event['id']]);
            if ($paid && !$p['has_valid_ledger']) {
                if ($attempt) {
                    $this->query("UPDATE unified_ledger SET payment_status='completed' WHERE company_id=?
                        AND module_source='events' AND BINARY source_id=BINARY ? AND payment_status='pending'
                        AND JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.event_checkin.reference'))=?",
                        [$this->company,$p['id'],$attempt['reference']]);
                } else {
                    $this->ledger($event,$p,(string)$used,'completed',[
                        'payer_checkin_id'=>$rows[0]['id'],'payer_name'=>$rows[0]['customer_name'],
                        'participant_ids'=>array_column($rows,'id'),'previous_payment'=>$p['payment_status']==='paid']);
                }
            }
            tenantAuditChange($this->db,$this->company,'events','EVENT_CHECKIN','events_checkin',$p['id'],
                $event['title'].' / '.$p['customer_name'],['checked_in'=>$p['checked_in'],'payment_status'=>$p['payment_status']],
                 ['checked_in'=>1,'payment_status'=>'paid','payment_method'=>$used],
                 ['event_name'=>$event['title'],'participant_name'=>$p['customer_name'],'ticket_price'=>$p['ticket_price'],
                     'paid_on_site'=>$paid && !$p['has_valid_ledger'] && $p['payment_status']!=='paid' ? 1 : 0,
                     'payment_name'=>$this->paymentAuditName($used)], $this->actor);
        }
    }
    private function paymentAuditName(?string $method): string {
        if (!$method) return '';
        $builtIn = ['cash'=>'Kontant','card'=>'Kort','vipps'=>'Vipps'];
        if (isset($builtIn[$method])) return $builtIn[$method];
        $q = $this->query('SELECT name FROM economy_payment_gateways WHERE id=? AND company_id=?',[$method,$this->company]);
        return (string)($q->fetchColumn() ?: 'Betalingsmetode ikke registrert');
    }
    public function commit(string $event,array $ids,string $payer,string $method,bool $confirmed): array {
        $ids=self::ids($ids,$payer);
        return $this->transaction(function()use($event,$ids,$method,$confirmed) {
            $e=$this->event($event,true); $rows=$this->people($event,$ids,true);
            $previous=$method==='previous';
            $this->validateGroup($rows,$previous);
            $due=array_filter($rows,fn($p)=>(float)$p['ticket_price']>0 && !$p['has_valid_ledger']);
            if (!$previous && array_filter($due,fn($p)=>$p['payment_status']==='paid')) {
                throw new InvalidArgumentException('Bekreft den tidligere betalingen uten å belaste deltakeren på nytt.');
            }
            if ($due && !$confirmed) throw new InvalidArgumentException('Bekreft mottatt betaling før innsjekking.');
            $used=null;
            if ($previous) $used=$rows[0]['payment_method'];
            elseif ($due) {
                if ($method!=='cash') throw new InvalidArgumentException('Vipps må bekreftes av betalingstjenesten, ikke manuelt.');
                $used=requireActiveEventPayment($this->db,$this->company,$event,'cash',
                    array_sum(array_column($rows,'ticket_price')));
            }
            $this->admit($e,$rows,$used);
            return ['checked_in_ids'=>$ids];
        });
    }
    private function attempt(string $ref,bool $lock = false): array {
        if (!preg_match('/^EC-[a-f0-9]{32}$/D',$ref)) throw new InvalidArgumentException('Ugyldig betalingsreferanse.');
        $rows=$this->query("SELECT id,metadata,payment_status FROM unified_ledger WHERE company_id=?
            AND module_source='events' AND JSON_VALID(metadata)
            AND JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.event_checkin.reference'))=?
            ORDER BY id".($lock?' FOR UPDATE':''),[$this->company,$ref])->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) throw new InvalidArgumentException('Betalingen finnes ikke i valgt bedrift.');
        $control=json_decode($rows[0]['metadata'],true,32,JSON_THROW_ON_ERROR)['event_checkin'];
        $control['settled']=count(array_filter($rows,fn($r)=>$r['payment_status']==='completed'))===count($rows);
        return $control;
    }
    public function start(string $event,array $ids,string $payer): array {
        $ids=self::ids($ids,$payer);
        $control=$this->transaction(function()use($event,$ids,$payer) {
            $e=$this->event($event,true); $rows=$this->people($event,$ids,true);
            $this->validateGroup($rows,false);
            foreach($rows as $p) if ($p['payment_status']==='paid' || $p['has_valid_ledger'] || (float)$p['ticket_price']<=0) {
                throw new InvalidArgumentException('Disse deltakerne skal ikke belastes på nytt.');
            }
            $phone=preg_replace('/[\s()+-]/','',(string)$rows[0]['customer_phone']);
            if (str_starts_with($phone,'0047')) $phone=substr($phone,2);
            if (strlen($phone)===8) $phone='47'.$phone;
            if (!preg_match('/^47[49][0-9]{7}$/D',$phone)) throw new InvalidArgumentException('Betaleren må ha et gyldig registrert mobilnummer.');
            $total=(int)round(array_sum(array_column($rows,'ticket_price'))*100);
            requireActiveEventPayment($this->db,$this->company,$event,'vipps',$total/100);
            $c=$this->configuration();
            $control=['reference'=>'EC-'.bin2hex(random_bytes(16)),'event_id'=>$event,'payer_id'=>$payer,
                'participant_ids'=>$ids,'amount_minor'=>$total,'expires_at'=>time()+80,
                'configuration_id'=>$c['configuration_id'],'binding'=>$this->provider->binding($c,$this->company),
                'description'=>mb_substr($e['title'],0,80),'phone'=>$phone,'initiated_by'=>$this->actor];
            // Phone is needed only for transport, not stored in payment metadata.
            $saved=$control; unset($saved['phone']);
            foreach ($rows as $p) $this->ledger($e,$p,'vipps','pending',['event_checkin'=>$saved,
                'payer_checkin_id'=>$payer,'payer_name'=>$rows[0]['customer_name']]);
            return $control;
        });
        $c=$this->configuration($control['configuration_id']);
        try {
            $created=$this->provider->request($c,$this->company,'POST','/epayment/v1/payments',[
                'amount'=>['currency'=>'NOK','value'=>$control['amount_minor']],
                'paymentMethod'=>['type'=>'WALLET'],'customer'=>['phoneNumber'=>$control['phone']],
                'reference'=>$control['reference'],'userFlow'=>'PUSH_MESSAGE',
                'paymentDescription'=>$control['description']],$control['reference'].'-create');
            if (in_array($created['code']??0,[400,401,403,422],true)) {
                $lookup=$this->provider->request($c,$this->company,'GET','/epayment/v1/payments/'.$control['reference']);
                if (($lookup['code']??0)===404) {
                    $this->failAttempt($control['reference']);
                    return ['reference'=>$control['reference'],'state'=>'REJECTED'];
                }
            }
        } catch (Throwable $e) {
            // Keep the persisted attempt: a timeout does not prove rejection.
            return ['reference'=>$control['reference'],'expires_at'=>$control['expires_at'],
                'state'=>'UNKNOWN','message'=>'Vipps-svaret er uavklart. Kontroller status før du prøver noe annet.'];
        }
        return $this->status($control['reference'],false);
    }
    public static function remote(array $response,array $control): array {
        $d=$response['data']??[];
        if (($response['code']??0)!==200 || !($response['json_valid']??true)
            || ($d['reference']??null)!==$control['reference']
            || ($d['amount']['currency']??null)!=='NOK'
            || ($d['amount']['value']??null)!==$control['amount_minor']) {
            throw new RuntimeException('Vipps-betalingen kunne ikke bekreftes. Ikke betal på nytt før status er avklart.');
        }
        $captured=$d['aggregate']['capturedAmount']??['currency'=>'NOK','value'=>0];
        if (($captured['currency']??'')!=='NOK' || !is_int($captured['value']??null)
            || $captured['value']<0 || $captured['value']>$control['amount_minor']) {
            throw new RuntimeException('Vipps-beløpet stemmer ikke. Betalingen må avstemmes.');
        }
        if (($d['aggregate']['refundedAmount']['value']??0)>0) throw new RuntimeException('Betalingen er refundert og må avstemmes.');
        return [(string)($d['state']??''),$captured['value']];
    }
    private function failAttempt(string $reference): void {
        $this->transaction(function()use($reference) {
            $this->attempt($reference,true);
            $this->query("UPDATE unified_ledger SET payment_status='failed',status='cancelled' WHERE company_id=?
                AND module_source='events' AND payment_status='pending'
                AND JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.event_checkin.reference'))=?",[$this->company,$reference]);
        });
    }
    public function status(string $reference,bool $cancel): array {
        $a=$this->attempt($reference);
        if ($a['settled']) return ['state'=>'COMPLETED','reference'=>$reference,'checked_in_ids'=>$a['participant_ids']];
        $c=$this->configuration($a['configuration_id'],false);
        if (!hash_equals($a['binding'],$this->provider->binding($c,$this->company))) throw new RuntimeException('Vipps-oppsettet er endret. Betalingen må avstemmes.');
        $path='/epayment/v1/payments/'.rawurlencode($reference);
        $r=$this->provider->request($c,$this->company,'GET',$path);
        // 404 after a definitive create rejection is safe; timeouts are not.
        if (($r['code']??0)===404) {
            if (time() < (int)$a['expires_at']) return ['state'=>'UNKNOWN','reference'=>$reference,'expires_at'=>$a['expires_at']];
            // Do not release a missing reference on absence alone: delayed provider creation can still complete.
            throw new RuntimeException('Vipps har ikke funnet forespørselen. Status er uavklart; ikke betal på nytt.');
        }
        [$state,$captured]=self::remote($r,$a);
        if ($captured===0 && ($cancel || time()>=(int)$a['expires_at']) && in_array($state,['CREATED','AUTHORIZED'],true)) {
            $this->provider->request($c,$this->company,'POST',$path.'/cancel',null,$reference.'-cancel');
            [$state,$captured]=self::remote($this->provider->request($c,$this->company,'GET',$path),$a);
        } elseif ($state==='AUTHORIZED' && $captured===0) {
            $this->provider->request($c,$this->company,'POST',$path.'/capture',
                ['modificationAmount'=>['currency'=>'NOK','value'=>$a['amount_minor']]],$reference.'-capture');
            [$state,$captured]=self::remote($this->provider->request($c,$this->company,'GET',$path),$a);
        }
        if ($captured===$a['amount_minor']) {
            return $this->transaction(function()use($reference) {
                $a=$this->attempt($reference,true);
                if (!$a['settled']) {
                    // A verified callback/poll keeps the clerk who initiated payment,
                    // not whichever operator happened to refresh the status.
                    if (is_array($a['initiated_by']??null)) $this->actor=$a['initiated_by'];
                    $e=$this->event($a['event_id'],true);
                    $rows=$this->people($a['event_id'],$a['participant_ids'],true);
                    $this->validateGroup($rows,false,$reference);
                    if ((int)round(array_sum(array_column($rows,'ticket_price'))*100)!==$a['amount_minor']) throw new RuntimeException('Billettbeløpet er endret. Betalingen må avstemmes.');
                    $this->admit($e,$rows,'vipps',$a);
                }
                return ['state'=>'COMPLETED','reference'=>$reference,'checked_in_ids'=>$a['participant_ids']];
            });
        }
        if (in_array($state,['ABORTED','EXPIRED','TERMINATED','CANCELLED'],true) && $captured===0) {
            $this->failAttempt($reference);
        }
        return ['state'=>$state,'reference'=>$reference,'expires_at'=>$a['expires_at']];
    }
}
