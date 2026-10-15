<?php
declare(strict_types=1);

trait SalesReceipts {
    private function receiptEmailCapability(): array {
        $config=$this->q("SELECT c.automail_sales_order,
            EXISTS(SELECT 1 FROM email_templates t WHERE t.company_id=c.company_id AND t.template_key='sales_receipt' AND t.is_active=1) AS template_ready
            FROM email_configs c WHERE c.company_id=? LIMIT 1",[$this->company])->fetch(PDO::FETCH_ASSOC);
        $enabled=(int)($config['automail_sales_order']??0)===1;
        $ready=(int)($config['template_ready']??0)===1;
        return ['receipt_email_enabled'=>$enabled && $ready,
            'receipt_email_reason'=>!$enabled?'Kvittering på e-post er ikke aktivert i automaileren.':(!$ready?'Den aktive e-postmalen sales_receipt mangler.':null)];
    }
    public function sendReceipt(array $data): array {
        return $this->transaction(function()use($data){
            $order=$this->orderRow(self::text($data['order_id']??'',36),true);
            $view=$this->orderView($order['id']);
            if($view['payment_status']!=='paid')throw new InvalidArgumentException('Bare fullførte, betalte salg kan få e-postkvittering');
            $to=self::text($data['email']??'',254);
            if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Oppgi en gyldig e-postadresse');
            $intent=self::text($data['mutation_id']??'',64);
            if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$intent))throw new InvalidArgumentException('Ugyldig sendereferanse');
            $control=$this->control($this->orderLedger($order['id'],true));
            $requests=$control['receipt_requests']??[];
            $hash=hash('sha256',$order['id'].'|'.$intent.'|'.$to);
            if(isset($requests[$intent])) {
                if(!hash_equals($requests[$intent]['intent_hash'],$hash))throw new SalesConflict('Sendeforsøket gjelder en annen mottaker. Kontroller e-postkøen før ny utsending.');
                return ['queued'=>true,'queue_id'=>$requests[$intent]['queue_id'],'message'=>'Kvitteringen er allerede lagt i e-postkøen'];
            }
            // Recheck and lock the actual switch at write time. No UI-only gate.
            $flag=$this->q('SELECT automail_sales_order FROM email_configs WHERE company_id=? LIMIT 1 FOR UPDATE',[$this->company])->fetchColumn();
            if((int)$flag!==1)throw new DomainException('Kvittering på e-post krever automail_sales_order = 1');
            if(!$view['receipt_email_enabled'])throw new DomainException($view['receipt_email_reason']);
            if(count($requests)>=20)throw new DomainException('Ordren har mange sendeforsøk. Kontroller e-postkøen før flere kvitteringer sendes.');
            $mailer=new EmailService($this->db,$this->company);
            $sent=$mailer->send(['template'=>'sales_receipt','to'=>$to,'module_source'=>'sales','source_id'=>$order['id'],
                'data'=>$this->receiptEmailVariables($view)]);
            if(empty($sent['success']))throw new RuntimeException('Kvitteringen ble ikke lagt i kø: '.($sent['message']??'E-postoppsettet kunne ikke brukes'));
            $requests[$intent]=['intent_hash'=>$hash,'queue_id'=>$sent['queue_id']];
            $this->writeControl($order['id'],['receipt_requests'=>$requests]);
            return ['queued'=>true,'queue_id'=>$sent['queue_id'],'message'=>'Kvitteringen er lagt i e-postkøen'];
        });
    }
    private function receiptEmailVariables(array $order): array {
        $frozen=$order['register_document']['payload']??null;
        if($frozen)$order=array_replace($order,$frozen['order'],['items'=>$frozen['items']]);
        $escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $money=static fn($minor)=>number_format($minor/100,2,',',' ').' kr';
        $rows='';
        foreach($order['items'] as $item) {
            $details='';
            if($item['notes'])$details.='<br><small>'.$escape($item['notes']).'</small>';
            foreach($item['form_data'] as $name=>$value)$details.='<br><small>'.$escape($name).': '.$escape(is_array($value)?self::json($value):$value).'</small>';
            $rows.='<tr><td style="padding:8px;border-bottom:1px solid #ddd">'.$escape($item['description']).$details.'</td><td style="padding:8px;text-align:center">'.$item['quantity'].'</td><td style="padding:8px;text-align:right">'.$money($item['total_minor']).'</td></tr>';
        }
        $table='<table style="width:100%;border-collapse:collapse"><thead><tr><th align="left">Vare</th><th>Antall</th><th align="right">Sum</th></tr></thead><tbody>'.$rows.'</tbody></table>';
        $company=$frozen?($frozen['seller']['name']??$frozen['seller']['company_name']??''):
            $this->q('SELECT name FROM tenant_companies WHERE id=?',[$this->company])->fetchColumn();
        return ['company_name'=>$escape($company),'order_id'=>$escape($order['id']),'order_number'=>$escape($order['order_number']),
            'order_date'=>(new DateTimeImmutable($order['created_at']))->format('d.m.Y H:i'),
            'payment_method'=>$order['payment_method']==='cash'?'Kontant':'Vipps','payment_status'=>'Betalt',
            'total_amount'=>$money($order['total_minor']),'total'=>$money($order['total_minor']),
            'subtotal'=>$money($order['subtotal_minor']),'tax_total'=>$money($order['tax_minor']),
            'amount_paid'=>$money($order['total_minor']),'received_amount'=>$order['received_minor']===null?'':$money($order['received_minor']),
            'change_amount'=>$order['change_minor']===null?'':$money($order['change_minor']),
            'tenant_admin_name'=>$escape($order['tenant_admin_name']??''),'items'=>$table,'order_items'=>$table,'items_html'=>$table,'items_table'=>$table];
    }
}
