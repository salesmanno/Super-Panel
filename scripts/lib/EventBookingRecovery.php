<?php
declare(strict_types=1);

require_once __DIR__.'/../../includes/SalesVipps.php';
require_once __DIR__.'/../../includes/UnifiedLedgerService.php';
require_once __DIR__.'/../../includes/tenant_audit.php';

/**
 * Operator-approved repair of one existing customer's ticket, using an existing
 * Vipps authorization. Not a public registration endpoint or automatic job.
 */
final class EventBookingRecovery {
    public function __construct(private PDO $db, private SalesVipps $provider) {}

    public static function minor(mixed $value): int {
        if(!is_scalar($value) || !preg_match('/^([0-9]{1,7})(?:\.([0-9]{1,2}))?$/D',(string)$value,$m)) {
            throw new RuntimeException('Ugyldig beløp.');
        }
        return (int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
    }

    /** A successful capture response alone is not proof of settlement. */
    public static function captured(array $response,string $reference,int $amount): int {
        $d=$response['data']??[];
        if(($response['code']??0)!==200 || ($response['json_valid']??false)!==true
            || ($d['reference']??null)!==$reference || ($d['amount']['currency']??null)!=='NOK'
            || ($d['amount']['value']??null)!==$amount || ($d['state']??null)!=='AUTHORIZED') {
            throw new RuntimeException('Vipps bekrefter ikke riktig autorisert betaling.');
        }
        foreach(['authorizedAmount','capturedAmount','cancelledAmount','refundedAmount'] as $key) {
            $v=$d['aggregate'][$key]??[];
            if(($v['currency']??null)!=='NOK' || !is_int($v['value']??null) || $v['value']<0) {
                throw new RuntimeException('Ufullstendig betalingsstatus fra Vipps.');
            }
        }
        $a=$d['aggregate'];
        if($a['authorizedAmount']['value']!==$amount || $a['cancelledAmount']['value']!==0
            || $a['refundedAmount']['value']!==0 || !in_array($a['capturedAmount']['value'],[0,$amount],true)) {
            throw new RuntimeException('Delvis belastet, kansellert eller refundert betaling må avstemmes manuelt.');
        }
        return $a['capturedAmount']['value'];
    }

    private function q(string $sql,array $args=[]): PDOStatement {
        $q=$this->db->prepare($sql);$q->execute($args);return $q;
    }

    private function transaction(callable $work): mixed {
        $operation=function()use($work) {
            $this->db->beginTransaction();
            try{$result=$work();$this->db->commit();return $result;}
            catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
        };
        return method_exists($this->db,'withExplicitAudit')
            ? $this->db->withExplicitAudit($operation) : $operation();
    }

    private function context(string $company,string $reference,string $event,int $amount,bool $lock=false): array {
        $suffix=$lock?' FOR UPDATE':'';
        $bookings=$this->q('SELECT * FROM pending_bookings WHERE BINARY company_id=BINARY ? AND order_id=?'.$suffix,
            [$company,$reference])->fetchAll(PDO::FETCH_ASSOC);
        if(count($bookings)!==1 || $bookings[0]['booking_type']!=='event')throw new RuntimeException('Fant ikke én entydig arrangementspåmelding.');
        $b=$bookings[0];$data=json_decode($b['booking_data'],true,512,JSON_THROW_ON_ERROR);
        if(($data['company_id']??null)!==$company || ($data['event_id']??null)!==$event
            || (int)($data['total_attendees']??0)!==1 || count($data['attendees']??[])!==1
            || (int)($data['vipps_payment_started']??0)!==1 || self::minor($data['total_price']??null)!==$amount) {
            throw new RuntimeException('Bestillingen samsvarer ikke med godkjent gjenoppretting.');
        }
        $a=$data['attendees'][0];
        if(self::minor($a['final_price']??null)!==$amount || ($a['payment_method']??null)!=='vipps'
            || empty($a['existing_customer_id']) || empty($a['first_name']) || empty($a['last_name'])) {
            throw new RuntimeException('Deltaker, eksisterende kundekobling eller billettpris mangler.');
        }
        $e=$this->q('SELECT id,title,max_attendees,is_active,is_member_exclusive,memberverification FROM events_eventdata
            WHERE BINARY company_id=BINARY ? AND BINARY id=BINARY ?'.$suffix,[$company,$event])->fetch(PDO::FETCH_ASSOC);
        if(!$e || !(int)$e['is_active'])throw new RuntimeException('Arrangementet er ikke aktivt i bedriften.');
        $customer=$this->q('SELECT id FROM tenant_customers WHERE BINARY company_id=BINARY ? AND BINARY id=BINARY ?',
            [$company,$a['existing_customer_id']])->fetchColumn();
        if(!$customer)throw new RuntimeException('Kunden tilhører ikke bedriften.');
        $recovery=$data['_portal_recovery']??null;
        if($recovery===null) {
            if(!in_array($b['status'],['pending','expired'],true))throw new RuntimeException('Bestillingen er allerede behandlet eller avbrutt.');
            if(!empty($a['membership_plan_id'])) {
                $member=$this->q("SELECT id FROM membership_members WHERE BINARY company_id=BINARY ?
                    AND BINARY customer_id=BINARY ? AND BINARY membership_plan_id=BINARY ? AND status='active'
                    AND COALESCE(cancellation_active,0)=0 AND (expiration_date IS NULL OR expiration_date>=CURDATE()) LIMIT 1",
                    [$company,$customer,$a['membership_plan_id']])->fetchColumn();
                if(!$member)throw new RuntimeException('Medlemskap for den lagrede medlemsprisen kan ikke bekreftes.');
            }elseif((int)$e['is_member_exclusive'] || (int)$e['memberverification']) {
                throw new RuntimeException('Medlemskravet kan ikke bekreftes.');
            }
            $duplicate=$this->q('SELECT id FROM event_registrations WHERE BINARY company_id=BINARY ? AND BINARY event_id=BINARY ?
                AND BINARY customer_id=BINARY ? LIMIT 1',[$company,$event,$customer])->fetchColumn()
                || $this->q('SELECT id FROM events_checkin WHERE BINARY event_id=BINARY ?
                    AND (BINARY customer_id=BINARY ? OR payment_reference=?) LIMIT 1',[$event,$customer,$reference])->fetchColumn()
                || $this->q("SELECT id FROM unified_ledger WHERE BINARY company_id=BINARY ? AND module_source='events'
                    AND (order_id=? OR JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.vipps_order_id'))=?) LIMIT 1",
                    [$company,$reference,$reference])->fetchColumn();
            if($duplicate)throw new RuntimeException('Eksisterende deltaker eller regnskapspost krever manuell avstemming.');
            $count=(int)$this->q('SELECT COUNT(*) FROM events_checkin WHERE BINARY event_id=BINARY ?',[$event])->fetchColumn();
            $waiting=(int)$this->q("SELECT COALESCE(SUM(GREATEST(number_of_attendees,1)),0) FROM event_registrations
                WHERE BINARY event_id=BINARY ? AND approval_status IN ('approved','pending')
                AND NOT EXISTS(SELECT 1 FROM events_checkin c WHERE BINARY c.registration_id=BINARY event_registrations.id)",[$event])->fetchColumn();
            if((int)$e['max_attendees']>0 && $count+$waiting>=(int)$e['max_attendees'])throw new RuntimeException('Arrangementet er fullt. Ingen belastning utført.');
        }else {
            if(!is_array($recovery) || ($recovery['version']??null)!==1 || ($recovery['amount_minor']??null)!==$amount
                || ($recovery['reference']??null)!==$reference || $b['status']!=='completed')throw new RuntimeException('Ugyldig gjenopprettingsmarkør.');
            $linked=$this->q("SELECT c.id FROM events_checkin c JOIN event_registrations r ON BINARY r.id=BINARY c.registration_id
                JOIN unified_ledger l ON BINARY l.source_id=BINARY c.id AND BINARY l.company_id=BINARY r.company_id
                WHERE c.id=? AND r.id=? AND l.id=? AND BINARY c.event_id=BINARY ? AND BINARY r.event_id=BINARY c.event_id
                AND BINARY r.company_id=BINARY ? AND BINARY c.customer_id=BINARY ? AND BINARY r.customer_id=BINARY c.customer_id
                AND r.approval_status='approved' AND c.payment_reference=? AND l.order_id=? AND l.module_source='events'
                AND c.payment_method='vipps' AND l.payment_method='vipps' AND l.status='active' AND c.ticket_price=? AND l.selling_price=?",
                [$recovery['checkin_id'],$recovery['registration_id'],$recovery['ledger_id'],$event,$company,$customer,$reference,$reference,
                    number_format($amount/100,2,'.',''),number_format($amount/100,2,'.','')])->fetchColumn();
            if(!$linked)throw new RuntimeException('Gjenopprettingens koblinger er endret. Belastning blokkert.');
        }
        return compact('b','data','a','e','customer','recovery');
    }

    private function audit(array $actor,string $company,string $reference,string $action,string $table,string $id,
        string $name,array $before,array $after): void {
        tenantAuditRecord($this->db,$company,'events',$action,$table,$id,$name,$before,$after,
            'success',['correlation_id'=>$reference],$actor);
    }

    private static function barcode(string $seed): string {
        $digits=str_pad((string)hexdec(substr(hash('sha256',$seed),0,10)),12,'0',STR_PAD_LEFT);
        $sum=0;for($i=0;$i<12;$i++)$sum+=(int)$digits[$i]*($i%2===0?1:3);
        return $digits.(string)((10-$sum%10)%10);
    }

    public function run(string $company,string $reference,string $event,int $amount,bool $apply=false): array {
        if(PHP_SAPI!=='cli')throw new RuntimeException('Kun tilgjengelig som godkjent CLI-vedlikehold.');
        if($amount<=0 || !preg_match('/^EVT-[0-9]+-[a-f0-9]{8}$/D',$reference))throw new RuntimeException('Ugyldig referanse eller beløp.');
        $lock='booking-recovery:'.substr(hash('sha256',$company.'|'.$reference),0,40);
        if((int)$this->q('SELECT GET_LOCK(?,0)',[$lock])->fetchColumn()!==1)throw new RuntimeException('Gjenoppretting kjører allerede.');
        try {
            $ctx=$this->context($company,$reference,$event,$amount);
            $configs=$this->q("SELECT c.id AS configuration_id,c.company_id,c.test_mode,c.client_id,c.client_secret_encrypted,
                c.subscription_key,c.merchant_serial_number FROM economy_payment_gateways g JOIN economy_configuration_data c
                ON BINARY c.id=BINARY g.configuration_id AND BINARY c.company_id=BINARY g.company_id
                WHERE BINARY g.company_id=BINARY ? AND g.is_active=1 AND c.is_active=1 AND LOWER(c.gateway_type)='vipps'",
                [$company])->fetchAll(PDO::FETCH_ASSOC);
            if(count($configs)!==1 || (int)$configs[0]['test_mode']!==0)throw new RuntimeException('Fant ikke ett entydig aktivt produksjonsoppsett.');
            $config=$configs[0];
            $lookup=fn()=>self::captured($this->provider->request($config,$company,'GET','/'.rawurlencode($reference)),$reference,$amount);
            $captured=$lookup();
            if(!$apply)return ['reference'=>$reference,'mode'=>'read_only','amount_minor'=>$amount,'captured_minor'=>$captured,
                'booking_status'=>$ctx['b']['status'],'existing_recovery'=>$ctx['recovery']!==null];
            $name=$this->q('SELECT name FROM tenant_companies WHERE BINARY id=BINARY ?',[$company])->fetchColumn();
            if($name===false)throw new RuntimeException('Ukjent bedrift.');
            $actor=['id'=>null,'type'=>'system','name'=>'System / godkjent påmeldingsreparasjon','role'=>'system','company_name'=>$name];
            $ids=$this->transaction(function()use($company,$reference,$event,$amount,$actor) {
                $ctx=$this->context($company,$reference,$event,$amount,true);
                if($ctx['recovery']!==null)return $ctx['recovery'];
                $reg=bin2hex(random_bytes(16));$checkin=bin2hex(random_bytes(16));
                $price=number_format($amount/100,2,'.','');$a=$ctx['a'];
                $answers=$a['form_answers_json']??'{}';
                if(!is_string($answers))$answers=json_encode($answers,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
                json_decode($answers,true,512,JSON_THROW_ON_ERROR);
                $this->q("INSERT INTO event_registrations(id,event_id,company_id,customer_id,approval_status,approval_reason,
                    approved_by,approved_at,number_of_attendees,form_answers_json,created_at,updated_at)
                    VALUES(?,?,?,?,'approved',?,NULL,NOW(),1,?,NOW(),NOW())",
                    [$reg,$event,$company,$ctx['customer'],'Gjenopprettet fra eksisterende Vipps-reservasjon',$answers]);
                $this->q("INSERT INTO events_checkin(id,registration_id,event_id,customer_id,membership_plan_id,ticket_price,
                    payment_status,payment_method,checked_in,barcode,payment_reference,payment_state,payment_checked_at,created_at,updated_at)
                    VALUES(?,?,?,?,?,?,'pending','vipps',0,?,?,'authorized',NOW(),NOW(),NOW())",
                    [$checkin,$reg,$event,$ctx['customer'],$a['membership_plan_id']??null,$price,self::barcode($checkin.$event),$reference]);
                $result=(new UnifiedLedgerService($this->db,$company))->logTransaction([
                    'order_id'=>$reference,'module_source'=>'events','item_type'=>'event_registration','source_id'=>$checkin,
                    'item_name'=>$ctx['e']['title'],'quantity'=>1,'selling_price'=>$price,'customer_id'=>$ctx['customer'],
                    'payment_method'=>'vipps','payment_status'=>'pending','status'=>'active','transaction_type'=>'income','category'=>'Arrangement',
                    'metadata'=>['event_id'=>$event,'attendee_count'=>1,'vipps_order_id'=>$reference,'checkin_id'=>$checkin,
                        'registration_id'=>$reg,'recovery'=>'operator_approved']]);
                if(empty($result['success']))throw new RuntimeException('Regnskapsføringen kunne ikke klargjøres.');
                $ids=['version'=>1,'reference'=>$reference,'amount_minor'=>$amount,'registration_id'=>$reg,
                    'checkin_id'=>$checkin,'ledger_id'=>$result['ledger_id'],'phase'=>'registered'];
                $data=$ctx['data'];$data['_portal_recovery']=$ids;
                // The booking is fulfilled now: actual participants exist, but
                // payment stays pending until capturedAmount is verified.
                $changed=$this->q("UPDATE pending_bookings SET status='completed',completed_at=NOW(),booking_data=?
                    WHERE id=? AND BINARY company_id=BINARY ? AND status IN ('pending','expired')",
                    [json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$ctx['b']['id'],$company])->rowCount();
                if($changed!==1)throw new RuntimeException('Bestillingen ble behandlet samtidig.');
                $this->audit($actor,$company,$reference,'events.registration.recovered','event_registrations',$reg,$ctx['e']['title'],[],
                    ['id'=>$reg,'event_id'=>$event,'approval_status'=>'approved']);
                $this->audit($actor,$company,$reference,'events.payment.recovery_prepared','events_checkin',$checkin,$ctx['e']['title'],[],
                    ['id'=>$checkin,'registration_id'=>$reg,'event_id'=>$event,'ticket_price'=>$price,'payment_status'=>'pending',
                        'payment_state'=>'authorized','payment_method'=>'vipps','checked_in'=>0]);
                $this->audit($actor,$company,$reference,'events.ledger.recovery_prepared','unified_ledger',$ids['ledger_id'],$ctx['e']['title'],[],
                    ['id'=>$ids['ledger_id'],'order_id'=>$reference,'source_id'=>$checkin,'selling_price'=>$price,'payment_status'=>'pending','status'=>'active']);
                return $ids;
            });
            // No open database transaction during HTTP. Durable participant and
            // pending ledger remain recoverable if capture or final commit fails.
            $captured=$lookup();
            if($captured===0) {
                try {
                    $this->provider->request($config,$company,'POST','/'.rawurlencode($reference).'/capture',
                        ['modificationAmount'=>['currency'=>'NOK','value'=>$amount]],
                        'portal-recovery-'.substr(hash('sha256',$company.'|'.$reference),0,32));
                }catch(Throwable $e) {
                    // Resolve the actual provider state, never blindly retry.
                }
                for($i=0;$i<3;$i++) {
                    $captured=$lookup();if($captured===$amount)break;
                    if($i<2)usleep(400000);
                }
            }
            if($captured!==$amount)throw new RuntimeException('Påmeldingen er gjenopprettet, men belastningen er ikke bekreftet. Avstem samme referanse før videre handling.');
            $this->transaction(function()use($company,$reference,$event,$amount,$actor) {
                $ctx=$this->context($company,$reference,$event,$amount,true);$ids=$ctx['recovery'];
                $old=$this->q('SELECT id,payment_status,payment_state FROM events_checkin WHERE id=? FOR UPDATE',
                    [$ids['checkin_id']])->fetch(PDO::FETCH_ASSOC);
                $ledger=$this->q('SELECT id,payment_status FROM unified_ledger WHERE id=? AND BINARY company_id=BINARY ? FOR UPDATE',
                    [$ids['ledger_id'],$company])->fetch(PDO::FETCH_ASSOC);
                if(!in_array($old['payment_status'],['pending','paid'],true) || !in_array($ledger['payment_status'],['pending','completed'],true)) {
                    throw new RuntimeException('Lokal status er endret. Belastningen er bekreftet, men manuell avstemming kreves.');
                }
                if($old['payment_status']!=='paid' || $old['payment_state']!=='captured') {
                    $this->q("UPDATE events_checkin SET payment_status='paid',payment_state='captured',payment_checked_at=NOW(),
                        updated_at=NOW() WHERE id=?",[$ids['checkin_id']]);
                    $this->audit($actor,$company,$reference,'events.payment.recovery_captured','events_checkin',$ids['checkin_id'],$ctx['e']['title'],
                        $old,['payment_status'=>'paid','payment_state'=>'captured']);
                }
                if($ledger['payment_status']!=='completed') {
                    $this->q("UPDATE unified_ledger SET payment_status='completed' WHERE id=? AND BINARY company_id=BINARY ?",
                        [$ids['ledger_id'],$company]);
                    $this->audit($actor,$company,$reference,'events.ledger.recovery_completed','unified_ledger',$ids['ledger_id'],$ctx['e']['title'],
                        $ledger,['payment_status'=>'completed']);
                }
                if($ids['phase']!=='captured') {
                    $data=$ctx['data'];$data['_portal_recovery']['phase']='captured';
                    $this->q('UPDATE pending_bookings SET booking_data=? WHERE id=? AND BINARY company_id=BINARY ?',
                        [json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$ctx['b']['id'],$company]);
                }
            });
            return ['reference'=>$reference,'amount_minor'=>$amount,'captured_minor'=>$captured,'registration_id'=>$ids['registration_id'],
                'checkin_id'=>$ids['checkin_id'],'ledger_id'=>$ids['ledger_id'],'status'=>'completed'];
        }finally{$this->q('SELECT RELEASE_LOCK(?)',[$lock]);}
    }
}
