<?php
declare(strict_types=1);

trait SalesCart {
    public function adoptSessionCart(string $legacyToken): void {
        // Only the controller may supply its own current-session legacy key.
        // Past logged-out sessions cannot safely be attributed from hashes alone.
        if($this->systemAuth || hash_equals($this->sessionToken,$legacyToken)
            || !preg_match('/^sales-admin:[a-f0-9]{64}$/D',$legacyToken))return;
        if(!$this->q("SELECT id FROM sales_cart c WHERE company_id=? AND session_token=? AND
            (status='converted' OR (status='active' AND EXISTS(SELECT 1 FROM sales_cart_items i WHERE i.cart_id=c.id))) LIMIT 1",
            [$this->company,$legacyToken])->fetchColumn())return;
        $this->transaction(function()use($legacyToken){
            $rows=$this->q("SELECT id,session_token,status FROM sales_cart WHERE company_id=? AND session_token IN (?,?) AND status IN ('active','converted') ORDER BY id FOR UPDATE",
                [$this->company,$legacyToken,$this->sessionToken])->fetchAll(PDO::FETCH_ASSOC);
            $oldActive=[];$ownedActive=[];$oldRows=[];
            foreach($rows as $row) {
                if($row['session_token']===$legacyToken) {
                    $oldRows[]=$row;
                    if($row['status']==='active')$oldActive[]=$row;
                } elseif($row['status']==='active')$ownedActive[]=$row;
            }
            if(!$oldRows)return;
            if(count($oldActive)>1 || count($ownedActive)>1)
                throw new SalesConflict('Flere aktive kurver må avklares. Ingen varer er flyttet eller slettet.');
            $pending=$this->q("SELECT c.session_token FROM sales_cart c JOIN sales_orders o ON o.id=c.converted_order_id AND o.company_id=c.company_id
                WHERE c.company_id=? AND c.session_token IN (?,?) AND o.status='pending' FOR UPDATE",
                [$this->company,$legacyToken,$this->sessionToken])->fetchAll(PDO::FETCH_COLUMN);
            $oldPending=in_array($legacyToken,$pending,true);$ownedPending=in_array($this->sessionToken,$pending,true);
            $oldHasItems=$oldActive && (bool)$this->q('SELECT id FROM sales_cart_items WHERE cart_id=? LIMIT 1',[$oldActive[0]['id']])->fetchColumn();
            if(($oldPending && ($ownedPending || $ownedActive)) || ($oldHasItems && ($ownedActive || $ownedPending)))
                throw new SalesConflict('Det finnes både en lagret kurv og en kurv eller betaling fra denne eldre økten. De må avklares hver for seg; ingen varer eller betalinger er overskrevet.');
            foreach($oldRows as $row) {
                // An unused empty legacy cart must not conceal a recovered receipt
                // or create a second active cart beside the persistent one.
                if($row['status']==='active' && !$oldHasItems)continue;
                $this->q('UPDATE sales_cart SET session_token=? WHERE id=? AND company_id=? AND session_token=?',
                    [$this->sessionToken,$row['id'],$this->company,$legacyToken]);
            }
        });
    }
    private function activeCart(bool $lock=false): ?array {
        $rows=$this->q("SELECT * FROM sales_cart WHERE company_id=? AND session_token=? AND status='active' ORDER BY created_at,id".($lock?' FOR UPDATE':''),[$this->company,$this->sessionToken])->fetchAll(PDO::FETCH_ASSOC);
        if(count($rows)>1)throw new SalesConflict('Flere aktive kurver må avklares før kassen kan brukes');
        return $rows[0]??null;
    }
    private function createCart(): array {
        $id=self::uuid();
        $register=$this->registerSelection();
        $meta=['version'=>1,'revision'=>'new','mutations'=>[]];
        if($register)$meta['register_id']=$register;
        $this->q("INSERT INTO sales_cart(id,company_id,session_token,channel,status,notes) VALUES(?,?,?,'kiosk','active',?)",
            [$id,$this->company,$this->sessionToken,self::json($meta)]);
        return $this->activeCart(true);
    }
    private function cartMeta(array $cart): array {
        $meta=json_decode($cart['notes']??'',true,32,JSON_THROW_ON_ERROR);
        if(!is_array($meta) || ($meta['version']??null)!==1 || !is_string($meta['revision']??null))
            throw new SalesConflict('Kurvens versjon støttes ikke. Avklar kurven før nye salg.');
        return $meta;
    }
    private function cartRows(string $id,bool $lock=false): array {
        return $this->q('SELECT * FROM sales_cart_items WHERE cart_id=? ORDER BY id'.($lock?' FOR UPDATE':''),[$id])->fetchAll(PDO::FETCH_ASSOC);
    }
    private function pendingOrderId(): ?string {
        $ids=$this->q("SELECT o.id FROM sales_cart c JOIN sales_orders o ON o.id=c.converted_order_id AND o.company_id=c.company_id
            WHERE c.company_id=? AND c.session_token=? AND o.status='pending' ORDER BY o.created_at,o.id",[$this->company,$this->sessionToken])->fetchAll(PDO::FETCH_COLUMN);
        if(count($ids)>1)throw new SalesConflict('Flere uavklarte betalinger må avstemmes før neste kunde');
        return $ids[0]??null;
    }
    private function cartView(?array $cart): array {
        $view=['id'=>$cart['id']??null,'revision'=>$cart?$this->cartMeta($cart)['revision']:'new',
            'items'=>[],'total_minor'=>0,'subtotal_minor'=>0,'tax_minor'=>0];
        if(!$cart)return $view;
        foreach($this->cartRows($cart['id']) as $line) {
            $p=$this->product($line['product_id']);
            $money=SalesMoney::line($p,SalesMoney::integer((string)$line['quantity'],1),$this->taxRegistered);
            $view['items'][]=['id'=>$line['id'],'product_id'=>$p['id'],'name'=>$p['name'],'img_product'=>$p['img_product'],
                'quantity'=>(int)$line['quantity'],'notes'=>$line['notes'],
                'form_data'=>json_decode($line['form_data']??'{}',true,32,JSON_THROW_ON_ERROR),
                'available'=>(int)$p['stock_quantity']]+$money;
            foreach(['total_minor','subtotal_minor','tax_minor'] as $key)$view[$key]+=$money[$key];
        }
        if($view['total_minor']>SalesMoney::MAX)throw new InvalidArgumentException('Kurvens beløp overstiger regnskapsgrensen');
        return $view;
    }
    public function cartResult(): array {
        // Read a coherent snapshot, not half of a concurrent cart mutation.
        $this->db->beginTransaction();
        try {
            $cart=$this->activeCart();$pending=$this->pendingOrderId();
            $resume=$pending;
            if(!$cart && !$resume)$resume=$this->q("SELECT converted_order_id FROM sales_cart WHERE company_id=? AND session_token=? AND status='converted' ORDER BY converted_at DESC,id DESC LIMIT 1",[$this->company,$this->sessionToken])->fetchColumn()?:null;
            $result=['cart'=>$this->cartView($cart),'pending_order'=>$resume?$this->orderView($resume):null];
            $this->db->commit();return $result;
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function mutateCart(string $action,array $data): array {
        return $this->transaction(function()use($action,$data){
            if($this->pendingOrderId())throw new SalesConflict('Avklar betalingen som venter før du endrer kurven');
            $cart=$this->activeCart(true);
            if(!$cart && $action!=='add')throw new SalesConflict('Kurven finnes ikke lenger');
            $cart??=$this->createCart();$meta=$this->cartMeta($cart);
            $mutation=self::text($data['mutation_id']??'',64);
            if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$mutation))throw new InvalidArgumentException('Handlingsreferanse mangler');
            $signatureData=$data;unset($signatureData['csrf_token']);
            $signature=hash('sha256',self::json(['action'=>$action,'data'=>$signatureData]));
            if(isset($meta['mutations'][$mutation])) {
                if(!hash_equals($signature,$meta['mutations'][$mutation]))throw new SalesConflict('Handlingsreferansen er allerede brukt');
                return ['cart'=>$this->cartView($cart)];
            }
            // Only full VAT-registered mode adds the start-register barrier.
            // Payment reconciliation is deliberately outside this mutation path.
            if($this->taxRegistered) {
                $register=$meta['register_id']??null;
                if(!$register)throw new SalesConflict('Start kassen før du legger til eller endrer varer.');
                $this->registerStore()->openContext($register);
            }
            if(!is_string($data['revision']??null) || !hash_equals($meta['revision'],$data['revision']))
                throw new SalesConflict('Kurven er endret i en annen fane. Last inn oppdatert kurv.');
            $rows=$this->cartRows($cart['id'],true);
            if($action==='add') {
                if(count($rows)>=100)throw new InvalidArgumentException('Kurven kan ha høyst 100 linjer');
                $p=$this->product(self::text($data['product_id']??'',36),true);
                if((int)$p['is_active']!==1 || !in_array($p['visibility'],['kiosk','both'],true) || $p['currency']!=='NOK')
                    throw new InvalidArgumentException('Varen er ikke tilgjengelig i kassen');
                $quantity=SalesMoney::integer($data['quantity']??1,1);
                $answers=$data['form_data']??[];
                if(!is_array($answers))throw new InvalidArgumentException('Ugyldige produkttilpasninger');
                $answers=validateEntityFormValues($this->fields($p['id']),$answers);ksort($answers);
                $form=self::json($answers);
                if(strlen($form)>16000)throw new InvalidArgumentException('For omfattende produkttilpasninger');
                $notes=self::text($data['notes']??'',500);
                $existing=null;$reserved=0;
                foreach($rows as $line)if($line['product_id']===$p['id']) {
                    $reserved+=(int)$line['quantity'];
                    if(($line['notes']??'')===$notes && ($line['form_data']??'[]')===$form)$existing=$line;
                }
                if($reserved+$quantity>(int)$p['stock_quantity'])throw new SalesConflict('Ikke nok varer på lager');
                $unit=SalesMoney::decimal(SalesMoney::line($p,1,$this->taxRegistered)['unit_price_minor']);
                if($existing)$this->q('UPDATE sales_cart_items SET quantity=?,unit_price=?,updated_at=NOW() WHERE id=? AND cart_id=?',
                    [(int)$existing['quantity']+$quantity,$unit,$existing['id'],$cart['id']]);
                else $this->q('INSERT INTO sales_cart_items(id,cart_id,product_id,quantity,unit_price,notes,form_data) VALUES(?,?,?,?,?,?,?)',
                    [self::uuid(),$cart['id'],$p['id'],$quantity,$unit,$notes,$form]);
            } else {
                $lineId=self::text($data['line_id']??'',36);
                $line=null;foreach($rows as $r)if($r['id']===$lineId)$line=$r;
                if(!$line)throw new OutOfBoundsException('Kurvlinjen finnes ikke i din kurv');
                if($action==='remove')$this->q('DELETE FROM sales_cart_items WHERE id=? AND cart_id=?',[$lineId,$cart['id']]);
                elseif($action==='quantity') {
                    $quantity=SalesMoney::integer($data['quantity']??null,1);
                    $p=$this->product($line['product_id'],true);$total=$quantity;
                    foreach($rows as $r)if($r['product_id']===$p['id'] && $r['id']!==$lineId)$total+=(int)$r['quantity'];
                    if($total>(int)$p['stock_quantity'])throw new SalesConflict('Ikke nok varer på lager');
                    if($quantity!==(int)$line['quantity'])$this->q('UPDATE sales_cart_items SET quantity=?,updated_at=NOW() WHERE id=? AND cart_id=?',[$quantity,$lineId,$cart['id']]);
                } else throw new InvalidArgumentException('Ukjent kurvhandling');
            }
            $meta['revision']=self::uuid();$meta['mutations'][$mutation]=$signature;
            $meta['mutations']=array_slice($meta['mutations'],-50,null,true);
            $write=fn()=>$this->q('UPDATE sales_cart SET notes=?,updated_at=NOW() WHERE id=? AND company_id=?',[self::json($meta),$cart['id'],$this->company]);
            if(method_exists($this->db,'withExplicitAudit'))$this->db->withExplicitAudit($write);else$write();
            $cart['notes']=self::json($meta);
            $this->registerCartEvent('cart.'.$action,$cart,'cart:'.$mutation,['action'=>$action,'revision'=>$meta['revision'],'items'=>$this->cartView($cart)['items']]);
            return ['cart'=>$this->cartView($cart)];
        });
    }
}
