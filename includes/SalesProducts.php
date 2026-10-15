<?php
declare(strict_types=1);

trait SalesProducts {
    private function product(string $id,bool $lock=false): array {
        $row=$this->q('SELECT * FROM sales_products WHERE id=? AND company_id=?'.($lock?' FOR UPDATE':''),[$id,$this->company])->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new OutOfBoundsException('Produktet finnes ikke');
        return $row;
    }
    private function productRevision(array $row): string {
        ksort($row);return hash('sha256',self::json($row));
    }
    private function productView(array $row): array {
        $hasFields=array_key_exists('_has_fields',$row)?(bool)$row['_has_fields']:null;
        unset($row['_has_fields']);
        $row['revision']=$this->productRevision($row);
        $row['price_minor']=SalesMoney::line($row,1,$this->taxRegistered)['unit_price_minor'];
        $row['has_fields']=$hasFields??(bool)$this->q("SELECT COUNT(*) FROM dynamic_form_fields WHERE company_id=? AND entity_type='sales_product' AND entity_id=? AND field_name<>'__configured' AND is_visible=1",[$this->company,$row['id']])->fetchColumn();
        return $row;
    }
    public function fields(string $product): array {
        $loaded=entityFormLoad($this->db,$this->company,'sales_product',$product);
        return array_values(array_filter($loaded['fields'],static fn($f)=>!empty($f['is_visible'])));
    }
    private function salesEconomyAccess(): bool {
        return (bool)$this->q("SELECT sm.id FROM system_modules sm
            JOIN tenant_admin_modules am ON am.module_id=sm.id
            JOIN tenant_company_modules cm ON cm.module_id=sm.id AND cm.company_id=?
            WHERE am.tenant_admin_id=? AND sm.module_key IN ('economy','economy_module')
                AND sm.is_active=1 AND cm.is_active=1 LIMIT 1",[$this->company,$this->admin])->fetchColumn();
    }
    private static function salesPaymentType(?string $value): string {
        try {return activePaymentMethodName($value)??'unknown';}
        catch(InvalidArgumentException $e){return strtolower(trim($value??''))?:'unknown';}
    }
    private function paymentConfiguration(string $id,bool $forNewPayment=true): array {
        $row=$this->q('SELECT g.id,g.gateway_name,g.is_active AS gateway_active,g.configuration_id,
            c.company_id,c.gateway_type,c.test_mode,c.is_active AS configuration_active,c.config_name,
            c.client_id,c.client_secret_encrypted,c.subscription_key,c.merchant_serial_number
            FROM economy_payment_gateways g JOIN economy_configuration_data c ON c.id=g.configuration_id AND c.company_id=g.company_id
            WHERE g.id=? AND g.company_id=?',[$id,$this->company])->fetch(PDO::FETCH_ASSOC);
        if (!$row || self::salesPaymentType($row['gateway_type'])!=='vipps')
            throw new InvalidArgumentException('Betalingsoppsettet tilhører ikke firmaet eller støttes ikke');
        if ($forNewPayment) {
            if(!$this->salesEconomyAccess())throw new DomainException('Økonomimodulen må være aktiv for bedriften og tilgjengelig for brukeren');
            if(!in_array($row['test_mode'],[0,'0'],true))throw new InvalidArgumentException('Testoppsett kan ikke brukes til ordinære salg');
            if ((int)$row['gateway_active']!==1 || (int)$row['configuration_active']!==1)
                throw new InvalidArgumentException('Vipps-oppsettet eller den tilknyttede konfigureringen er deaktivert');
        }
        if (!$this->provider->configured($row,$this->company)) throw new RuntimeException('Vipps-nøkkelsettet i Økonomi er ufullstendig eller ugyldig');
        return $row;
    }
    public function paymentMethods(): array {
        $result=[['id'=>null,'method_type'=>'cash','method_name'=>'Kontant','available'=>true,'is_demo'=>false,'reason'=>null]];
        try {
            $rows=$this->q('SELECT g.id,g.gateway_name,g.configuration_id,c.id AS config_id,c.is_active AS configuration_active,c.config_name,c.gateway_type,c.test_mode
                FROM economy_payment_gateways g LEFT JOIN economy_configuration_data c ON c.id=g.configuration_id AND c.company_id=g.company_id
                WHERE g.company_id=? AND g.is_active=1 ORDER BY g.created_at,g.id',[$this->company])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $type=self::salesPaymentType($row['gateway_type']);
                if ($type==='cash') continue; // Sales has one unconditional cash default.
                $reason=!$row['config_id']?'Betalingsoppsettet mangler tilknyttet konfigurering for denne bedriften'
                    :((int)$row['configuration_active']!==1?'Den tilknyttede konfigureringen er deaktivert'
                    :($type!=='vipps'?'Denne betalingsintegrasjonen støttes ikke i kassen ennå':null));
                if ($reason===null) try {$this->paymentConfiguration($row['id']);}
                    catch(PDOException $e){throw $e;}
                    catch(InvalidArgumentException|DomainException|RuntimeException $e){$reason=$e->getMessage();}
                $result[]=['id'=>$row['id'],'method_type'=>$type,'gateway_name'=>$row['gateway_name'],'method_name'=>$row['config_name']?:($row['gateway_name']?:'Betalingsoppsett'),
                    'available'=>$reason===null,'is_demo'=>(bool)$row['test_mode'],'reason'=>$reason];
            }
            return ['methods'=>$result,'method_error'=>null];
        } catch(Throwable $e) {
            // Cash is an explicit Sales default, not a permissive gateway fallback.
            return ['methods'=>$result,'method_error'=>'Nettbetalingsoppsettet kunne ikke kontrolleres. Kontant er fortsatt tilgjengelig.'];
        }
    }
    private function requireOnlinePayment(): void {
        $methods=$this->paymentMethods();
        if ($methods['method_error']) throw new RuntimeException($methods['method_error']);
        foreach($methods['methods'] as $method) if ($method['method_type']==='vipps' && $method['available']) return;
        throw new InvalidArgumentException('Nettvarer krever en aktiv, konfigurert og brukbar nettbetalingsmetode i Økonomi. Kontant alene er ikke tilstrekkelig.');
    }
    public function productList(): array {
        $rows=$this->q("SELECT p.*,EXISTS(SELECT 1 FROM dynamic_form_fields f WHERE f.company_id=p.company_id AND f.entity_type='sales_product' AND f.entity_id=p.id AND f.is_visible=1) AS _has_fields FROM sales_products p WHERE p.company_id=? ORDER BY p.name,p.id",[$this->company])->fetchAll(PDO::FETCH_ASSOC);
        return ['products'=>array_map(fn($row)=>$this->productView($row),$rows),'tax_registered'=>$this->taxRegistered]+$this->paymentMethods();
    }
    public function saveProduct(array $data): array {
        return $this->transaction(function() use($data) {
            $id=self::text($data['id']??'',36);$old=$id!==''?$this->product($id,true):null;
            if ($old && (!is_string($data['expected_revision']??null) || !hash_equals($this->productRevision($old),$data['expected_revision'])))
                throw new SalesConflict('Produktet er endret av en annen. Last inn siste versjon før lagring.');
            $row=$old??['id'=>self::uuid(),'company_id'=>$this->company,'category_id'=>null,
                'sku'=>'S-'.strtoupper(bin2hex(random_bytes(6))),'barcode'=>'SM'.strtoupper(bin2hex(random_bytes(6))),
                'name'=>'','description'=>null,'cost_price'=>'0.00','selling_price'=>'0.00','img_product'=>null,
                'discount_percent'=>'0.00','discount_starts_at'=>null,'discount_ends_at'=>null,
                'currency'=>'NOK','tax_class'=>$this->taxRegistered?'standard':'exempt','tax_rate'=>$this->taxRegistered?'25.00':'0.00','item_taxable'=>$this->taxRegistered?1:0,'unit'=>'stk',
                'reorder_level'=>0,'low_stock_threeshold'=>5,'stock_quantity'=>0,'is_active'=>1,'visibility'=>'kiosk','is_backorder'=>0];
            foreach (['name'=>255,'description'=>10000,'img_product'=>500,'unit'=>20] as $field=>$max) {
                if (array_key_exists($field,$data)) $row[$field]=self::text($data[$field]??'',$max);
            }
            if ($row['name']==='') throw new InvalidArgumentException('Produktnavn er påkrevd');
            if (!empty($row['img_product']) && (!preg_match('~^(?:https?://|/(?!/))~',$row['img_product']) || preg_match('/[\x00-\x20]/',$row['img_product'])))
                throw new InvalidArgumentException('Bruk en gyldig bildeadresse');
            if(!$old && !array_key_exists('selling_price',$data))throw new InvalidArgumentException('Salgspris er påkrevd');
            // Missing optional fields preserve existing/default values. Explicit
            // blanks clear optional numeric values to zero, never the sale price.
            foreach (['cost_price','selling_price','discount_percent'] as $field) if(array_key_exists($field,$data)) {
                $value=$data[$field];
                if($field!=='selling_price' && ($value===null || (is_string($value) && trim($value)==='')))$value=0;
                $row[$field]=SalesMoney::decimal(SalesMoney::minor($value));
            }
            foreach (['is_active','is_backorder','item_taxable'] as $field) if(array_key_exists($field,$data)) {
                if(!in_array($data[$field],[0,1,'0','1',true,false],true))throw new InvalidArgumentException('Ugyldig avkrysning');
                $row[$field]=(int)(bool)$data[$field];
            }
            foreach (['reorder_level','low_stock_threeshold'] as $field)if(array_key_exists($field,$data)) {
                $value=$data[$field];
                if($value===null || (is_string($value) && trim($value)===''))$value=0;
                $row[$field]=SalesMoney::integer($value);
            }
            if(array_key_exists('stock_quantity',$data)) {
                $quantity=SalesMoney::integer($data['stock_quantity']);
                if($old && $quantity!==(int)$old['stock_quantity'])throw new InvalidArgumentException('Endre lager med egen lagerjustering og begrunnelse');
                if(!$old)$row['stock_quantity']=$quantity;
            }
            if(array_key_exists('visibility',$data))$row['visibility']=$data['visibility'];
            if(!in_array($row['visibility'],['kiosk','online','both'],true))throw new InvalidArgumentException('Ugyldig salgskanal');
            if($row['visibility']!=='kiosk')$this->requireOnlinePayment();
            if(array_key_exists('tax_class',$data))$row['tax_class']=$data['tax_class'];
            if(!$this->taxRegistered)$row['tax_class']='exempt';
            if(!in_array($row['tax_class'],['standard','food','transport','exempt','custom'],true))throw new InvalidArgumentException('Ugyldig MVA-klasse');
            if($this->taxRegistered && array_key_exists('tax_class',$data) && (!$old || $row['tax_class']!==$old['tax_class'])) {
                if(!array_key_exists('item_taxable',$data))$row['item_taxable']=$row['tax_class']==='exempt'?0:1;
                if(!array_key_exists('tax_rate',$data) && (int)$row['item_taxable']===1) {
                    $row['tax_rate']=match($row['tax_class']) {
                        'standard'=>'25.00','food'=>'15.00','transport'=>'12.00',
                        default=>throw new InvalidArgumentException('Oppgi MVA-sats for egen sats / nullsats'),
                    };
                }
            }
            if(!$this->taxRegistered || $row['tax_class']==='exempt' || !(int)$row['item_taxable']) {
                $row['tax_class']='exempt';$row['tax_rate']='0.00';$row['item_taxable']=0;
            } elseif(array_key_exists('tax_rate',$data)) {
                $row['tax_rate']=SalesMoney::decimal(SalesMoney::minor($data['tax_rate']));
            }
            foreach (['discount_starts_at','discount_ends_at'] as $field)if(array_key_exists($field,$data)) {
                $value=self::text($data[$field]??'',19);
                $value=str_replace('T',' ',$value);if(strlen($value)===16)$value.=':00';
                if($value!=='' && !SalesMoney::date($value))throw new InvalidArgumentException('Ugyldig rabattdato');
                $row[$field]=$value?:null;
            }
            if($row['discount_starts_at'] && $row['discount_ends_at'] && $row['discount_ends_at']<$row['discount_starts_at'])
                throw new InvalidArgumentException('Rabattens sluttdato må være etter startdato');
            SalesMoney::line($row,1,$this->taxRegistered);
            if(empty($row['barcode']) || !preg_match('/^[\x20-\x7e]{1,100}$/D',(string)$row['barcode']))$row['barcode']='SM'.strtoupper(bin2hex(random_bytes(6)));
            if(!$old) {
                for($attempt=0;$attempt<5;$attempt++) {
                    if(!$this->q('SELECT id FROM sales_products WHERE company_id=? AND (sku=? OR barcode=?) LIMIT 1',[$this->company,$row['sku'],$row['barcode']])->fetchColumn())break;
                    $row['sku']='S-'.strtoupper(bin2hex(random_bytes(6)));$row['barcode']='SM'.strtoupper(bin2hex(random_bytes(6)));
                }
                if($attempt===5)throw new RuntimeException('En unik produktkode kunne ikke genereres');
            }
            if($old) {
                $changes=array_diff_assoc($row,$old);
                if($changes) {
                    $keys=array_keys($changes);
                    $this->q('UPDATE sales_products SET '.implode(',',array_map(static fn($key)=>"`$key`=?",$keys)).',updated_at=NOW() WHERE id=? AND company_id=?',[...array_values($changes),$id,$this->company]);
                }
            } else {
                $keys=array_keys($row);
                $this->q('INSERT INTO sales_products (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')',array_values($row));
            }
            if(!$old || !empty($changes)) {
                $catalogId=$this->q("SELECT id FROM output_products WHERE company_id=? AND product_id=? AND product_type IN('product','sales_item') ORDER BY id LIMIT 1",[$this->company,$row['id']])->fetchColumn();
                if(!$catalogId)$catalogId=(new ProductCatalogService($this->db))->createProductEntry($this->company,'sales',$row['id'],
                    ['is_visible'=>(int)$row['is_active'],'is_public'=>(int)$row['is_active']===1 && $row['visibility']!=='kiosk'],$this->admin);
                if(!$catalogId)throw new RuntimeException('Produktet kunne ikke knyttes til produktkatalogen');
                $visible=(int)$row['is_active'];$public=$visible===1 && $row['visibility']!=='kiosk'?1:0;
                $this->q('UPDATE output_products SET is_visible=?,is_public=? WHERE id=? AND company_id=? AND (is_visible<>? OR is_public<>?)',[$visible,$public,$catalogId,$this->company,$visible,$public]);
                if(($row['output_product_id']??null)!==$catalogId)$this->q('UPDATE sales_products SET output_product_id=? WHERE id=? AND company_id=?',[$catalogId,$row['id'],$this->company]);
            }
            return ['product'=>$this->productView($this->product($row['id']))];
        });
    }
    public function adjustStock(array $data): array {
        return $this->transaction(function()use($data){
            $old=$this->product(self::text($data['product_id']??'',36),true);
            $quantity=SalesMoney::integer($data['quantity']??null);$expected=SalesMoney::integer($data['expected_quantity']??null);
            if((int)$old['stock_quantity']!==$expected)throw new SalesConflict('Lageret er endret. Last oppdatert beholdning før justering.');
            $reason=self::text($data['reason']??'',500);if($reason==='')throw new InvalidArgumentException('Oppgi begrunnelse for lagerjusteringen');
            if($quantity!==$expected) {
                $write=fn()=>$this->q('UPDATE sales_products SET stock_quantity=?,updated_at=NOW() WHERE id=? AND company_id=?',[$quantity,$old['id'],$this->company]);
                if(method_exists($this->db,'withExplicitAudit'))$this->db->withExplicitAudit($write);else$write();
                tenantAuditChange($this->db,$this->company,'sales','sales.stock_adjusted','sales_products',$old['id'],$old['name'],
                    ['stock_quantity'=>$expected],['stock_quantity'=>$quantity,'stock_adjustment_reason'=>$reason],[],$this->actor);
            }
            return ['product'=>$this->productView($this->product($old['id']))];
        });
    }
}
