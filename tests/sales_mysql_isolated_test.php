<?php
declare(strict_types=1);
$root=$argv[1];$temp=$argv[2];
require_once $root.'/includes/AuditedPDO.php';
require_once $root.'/includes/SalesService.php';
require_once $root.'/includes/SalesCode128.php';
function salesAssert(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
function salesReject(callable $fn,string $message): void {
    try{$fn();}catch(Throwable $e){return;}throw new RuntimeException($message);
}
// Server is started before opening any PDO connection; do not fork live PDO.
$sock=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
$port=(int)substr(strrchr(stream_socket_get_name($sock,false),':'),1);fclose($sock);
$dsn='mysql:unix_socket='.$temp.'/mysql.sock;dbname=sales_fixture;charset=utf8mb4';
$fixtureConfig='<?php require_once '.var_export($root.'/includes/AuditedPDO.php',true).'; function getDBConnection(){return new AuditedPDO('.var_export($dsn,true).', "root", "", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}';
file_put_contents($temp.'/sales-db.php',$fixtureConfig);
$source=file_get_contents($root.'/api/sales.php');
$source=str_replace("require_once __DIR__.'/../config/database.php';","require_once ".var_export($temp.'/sales-db.php',true).';',$source);
$source=str_replace("require_once __DIR__.'/../includes/SalesService.php';","require_once ".var_export($root.'/includes/SalesService.php',true).';',$source);
$source=str_replace('session_start();',"session_start(); \$fixtureB=str_starts_with(session_id(),'salesfixture-e2e-b'); \$_SESSION=['tenant_admin_id'=>\$fixtureB?'admin-b':'admin','company_id'=>\$fixtureB?'b':'a','entity_form_csrf'=>'fixture-token'];",$source);
// Use the real shared CSRF token's key, discovered below rather than bypassing it.
preg_match("/\\\$_SESSION\\['([^']+)'\\].*random_bytes/",file_get_contents($root.'/includes/entity_form_fields.php'),$csrfMatch);
if(isset($csrfMatch[1]))$source=str_replace("'entity_form_csrf'=>'fixture-token'","'".$csrfMatch[1]."'=>'fixture-token'",$source);
file_put_contents($temp.'/sales.php',$source);
mkdir($temp.'/api');mkdir($temp.'/assets');
file_put_contents($temp.'/api/sales.php',$source);
foreach(['sales-admin.js','sales-admin.css','entity-form-fields.js'] as $asset)copy($root.'/assets/'.$asset,$temp.'/assets/'.$asset);
ob_start();include $root.'/modules/sales_content.php';$markup=ob_get_clean();
$baseStyle='*{box-sizing:border-box}body{margin:8px}button,input,select,textarea{font:inherit}.form-control,.form-select{display:block;width:100%;min-height:38px;padding:.45rem;border:1px solid #cbd9e2;border-radius:8px}.input-group{display:flex}.input-group input{min-width:0}.btn{padding:.45rem .7rem;border:1px solid #cbd9e2;border-radius:8px}.btn-primary{color:white;background:#163b5c}.visually-hidden{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}';
$captureScript="document.cookie='PHPSESSID=salesfixture-e2e-'+(new URLSearchParams(location.search).get('company')==='b'?'b':'a')+'; path=/'; window.apiCalls=[]; const nativeFetch=window.fetch; window.fetch=async function(url,opts){const response=await nativeFetch(url,opts); if(String(url).includes('/api/sales.php')){window.apiCalls.push({action:new URL(url,location.href).searchParams.get('action'),payload:opts?.body?JSON.parse(opts.body):null,status:response.status,result:await response.clone().json()});}return response;};";
file_put_contents($temp.'/sales-e2e.html','<!doctype html><html lang="nb"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$baseStyle.'</style><script>'.$captureScript.'</script></head><body>'.$markup.'</body></html>');
$process=proc_open(['php','-d','session.save_path='.$temp,'-S','127.0.0.1:'.$port,'-t',$temp],
    [0=>['pipe','r'],1=>['file',$temp.'/sales-http.log','a'],2=>['file',$temp.'/sales-http.log','a']],$pipes);
fclose($pipes[0]);
try {
    for($i=0;$i<40;$i++){if($s=@fsockopen('127.0.0.1',$port)){fclose($s);break;}usleep(50000);}
    $setup=new PDO('mysql:unix_socket='.$temp.'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $setup->exec('CREATE DATABASE sales_fixture CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');$setup->exec('USE sales_fixture');
    $ddl=[
        "CREATE TABLE tenant_companies(id CHAR(36) PRIMARY KEY,name VARCHAR(255),is_active INT,tax_registered INT,cash_payment INT)",
        "CREATE TABLE tenant_staff(id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name VARCHAR(255),last_name VARCHAR(255))",
        "CREATE TABLE tenant_admins(id CHAR(36) PRIMARY KEY,company_id CHAR(36),tenant_staff_id CHAR(36),username VARCHAR(255),role VARCHAR(30),is_active INT)",
        "CREATE TABLE system_modules(id CHAR(36) PRIMARY KEY,module_key VARCHAR(30),is_active INT)",
        "CREATE TABLE tenant_admin_modules(tenant_admin_id CHAR(36),module_id CHAR(36),is_active INT DEFAULT 1)",
        "CREATE TABLE tenant_company_modules(company_id CHAR(36),module_id CHAR(36),is_active INT DEFAULT 1)",
        "CREATE TABLE audit_logs(id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),user_email VARCHAR(255),action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name VARCHAR(255),old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50))",
        "CREATE TABLE email_configs(id CHAR(36) PRIMARY KEY,company_id CHAR(36),automail_sales_order INT DEFAULT 0,from_email VARCHAR(255))",
        "CREATE TABLE email_templates(id CHAR(36) PRIMARY KEY,company_id CHAR(36),template_key VARCHAR(100),is_active INT,subject_template TEXT,body_template TEXT)",
        "CREATE TABLE email_queue(id CHAR(36) PRIMARY KEY,company_id CHAR(36),recipient_email VARCHAR(255),from_email VARCHAR(255),subject TEXT,body LONGTEXT,template_id CHAR(36),module_source VARCHAR(50),source_id CHAR(36),status VARCHAR(30),priority INT,scheduled_at DATETIME,created_at DATETIME,updated_at DATETIME)",
        "CREATE TABLE economy_bank_accounts(id CHAR(36),company_id CHAR(36),is_active INT,account_number VARCHAR(50))",
        "CREATE TABLE economy_configuration_data(id CHAR(36) PRIMARY KEY,company_id CHAR(36),config_name VARCHAR(100),gateway_type VARCHAR(30),test_mode INT,is_active INT,client_id VARCHAR(20),client_secret_encrypted TEXT,subscription_key TEXT,merchant_serial_number VARCHAR(20),public_key TEXT,private_key_encrypted TEXT)",
        "CREATE TABLE economy_payment_gateways(id CHAR(36) PRIMARY KEY,company_id CHAR(36),configuration_id CHAR(36),gateway_name VARCHAR(30),is_active INT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE dynamic_form_fields(id CHAR(36) PRIMARY KEY,company_id CHAR(36),entity_type VARCHAR(50),entity_id CHAR(36),field_name VARCHAR(100),field_label VARCHAR(255),field_type VARCHAR(50),is_required INT,is_visible INT,default_value TEXT,options TEXT,validation_rules TEXT,sort_order INT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE output_products(id CHAR(36) PRIMARY KEY,company_id CHAR(36),module_id CHAR(36),product_type VARCHAR(30),product_id CHAR(36),is_visible INT,is_public INT,is_marketplace_featured INT,marketplace_priority INT,discount_percent DECIMAL(5,2),created_by CHAR(36))",
        "CREATE TABLE unified_ledger(id CHAR(36) PRIMARY KEY,order_id VARCHAR(100),company_id CHAR(36),module_source VARCHAR(50),item_type VARCHAR(50),source_id CHAR(36),item_name VARCHAR(255),quantity INT,selling_price DECIMAL(10,2),cost_price DECIMAL(10,2),customer_id CHAR(36),payment_method VARCHAR(50),payment_status VARCHAR(50),status VARCHAR(50),transaction_date DATETIME,transaction_type VARCHAR(20),category VARCHAR(100),metadata JSON)",
    ];
    foreach($ddl as $sql)$setup->exec($sql);
    $attachment=file_get_contents($root.'/attached_assets/Pasted-Jeg-vil-at-du-n-skal-se-p-sales-modulen-Denne-har-aller_1792017775339.txt');
    foreach(['sales_products','sales_cart','sales_cart_items'] as $table) {
        salesAssert((bool)preg_match('/CREATE TABLE '.preg_quote($table,'/').' \\(.*?ENGINE=InnoDB[^;]*;/s',$attachment,$match),'Fixture DDL found: '.$table);
        // The disposable MySQL is not MariaDB; every tested insert supplies ID.
        $setup->exec(str_replace(' DEFAULT uuid()','',$match[0]));
    }
    // Run the DELIVERED manual deployment script against the original base
    // schema, twice. Do not invent an easier order schema only in this fixture.
    $migration=file_get_contents($root.'/sql/sales_checkout_setup.sql');
    $statements=array_filter(array_map('trim',explode(';',preg_replace('/^\\s*--.*$/m','',$migration))));
    for($pass=0;$pass<2;$pass++)foreach($statements as $sql)$setup->query($sql)->closeCursor();
    $setup->exec("INSERT INTO tenant_companies VALUES('a','Firma A',1,0,0),('b','Firma B',1,1,0)");
    $setup->exec("INSERT INTO tenant_admins VALUES('admin','a',NULL,'Kasserer','admin',1),('admin-b','b',NULL,'Annen','admin',1)");
    $setup->exec("INSERT INTO system_modules VALUES('sales-module','sales',1),('economy-module','economy',1)");
    $setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','sales-module',1),('admin-b','sales-module',1),('admin','economy-module',1),('admin-b','economy-module',1)");
    $setup->exec("INSERT INTO tenant_company_modules VALUES('a','sales-module',1),('b','sales-module',1),('a','economy-module',1),('b','economy-module',1)");
    $setup->exec("INSERT INTO email_configs VALUES('mail-a','a',0,'sender@example.invalid')");
    $setup->exec("INSERT INTO economy_configuration_data VALUES('cfg-a','a','Vipps','vipps',0,1,'fixture','fixture','fixture','fixture',NULL,NULL),('cfg-b','b','Vipps B','vipps',0,1,'fixture','fixture','fixture','fixture',NULL,NULL)");
    $setup->exec("INSERT INTO economy_payment_gateways(id,company_id,configuration_id,gateway_name,is_active) VALUES('gateway-a','a','cfg-a','vipps',1),('gateway-b','b','cfg-b','vipps',1)");
    $_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin'];
    $db=new AuditedPDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    class SalesFixtureVipps extends SalesVipps {
        public array $payments=[],$requests=[];public bool $outage=false,$captureNow=true;
        public function configured(array $c,string $company): bool{return $company==='a' && $c['configuration_id']==='cfg-a';}
        public function binding(array $c,string $company): string{return hash('sha256',$company.'|'.$c['configuration_id']);}
        public function request(array $c,string $company,string $method,string $path,?array $body=null,?string $key=null): array {
            $this->requests[]=[$method,$path,$key,$body];
            if($this->outage)throw new RuntimeException('Fixture outage');
            if($method==='POST' && $path==='') {
                $ref=$body['reference'];
                $this->payments[$ref]??=['reference'=>$ref,'amount'=>$body['amount'],'state'=>'CREATED','aggregate'=>['capturedAmount'=>['currency'=>'NOK','value'=>0],'refundedAmount'=>['currency'=>'NOK','value'=>0]]];
                return ['code'=>201,'data'=>$this->payments[$ref]];
            }
            $parts=explode('/',trim($path,'/'));$ref=$parts[0]??'';
            if(!isset($this->payments[$ref]))return ['code'=>404,'data'=>[]];
            if($method==='POST' && ($parts[1]??'')==='capture' && $this->captureNow)$this->payments[$ref]['aggregate']['capturedAmount']['value']=$body['modificationAmount']['value'];
            if($method==='POST' && ($parts[1]??'')==='cancel')$this->payments[$ref]['state']='TERMINATED';
            return ['code'=>200,'data'=>$this->payments[$ref]];
        }
    }
    $provider=new SalesFixtureVipps();$sales=new SalesService($db,'a','admin','fixture-cart',$provider);
    // Sales must use the configuration's type, not its user-facing gateway
    // label or the obsolete presence of database secrets.
    $setup->exec("UPDATE economy_configuration_data SET gateway_type=' VIPPS ',client_id=NULL,client_secret_encrypted=NULL,subscription_key=NULL,merchant_serial_number=NULL WHERE id='cfg-a'");
    $setup->exec("UPDATE economy_payment_gateways SET gateway_name='Resepsjonen' WHERE id='gateway-a'");
    $methods=array_column($sales->paymentMethods()['methods'],null,'id');
    salesAssert($methods['gateway-a']['available'] && $methods['gateway-a']['method_type']==='vipps','Authoritative normalized type and secure provider configuration enable Vipps without legacy DB credentials');
    $setup->exec("INSERT INTO economy_configuration_data(id,company_id,config_name,gateway_type,test_mode,is_active) VALUES('cfg-off','a','Deaktivert','vipps',0,0),('cfg-test','a','Test','vipps',1,1),('cfg-card','a','Kort','card',0,1)");
    $setup->exec("INSERT INTO economy_payment_gateways(id,company_id,configuration_id,gateway_name,is_active) VALUES('gateway-missing','a','missing','Mangler',1),('gateway-foreign','a','cfg-b','Feilkoblet',1),('gateway-off','a','cfg-off','Av',1),('gateway-test','a','cfg-test','Test',1),('gateway-card','a','cfg-card','Terminal',1),('gateway-inactive','a','cfg-a','Inaktiv',0),('gateway-a-2','a','cfg-a','Andre kasse',1)");
    $methods=array_column($sales->paymentMethods()['methods'],null,'id');
    foreach(['gateway-missing','gateway-foreign','gateway-off','gateway-test','gateway-card'] as $id)
        salesAssert(isset($methods[$id]) && !$methods[$id]['available'] && $methods[$id]['reason'],'Incomplete/unsupported active gateway stays visible with a reason: '.$id);
    salesAssert(!isset($methods['gateway-b']) && !isset($methods['gateway-inactive']),'Inactive and foreign-company gateways are not offered');
    salesAssert($methods['gateway-card']['method_type']==='stripe' && $methods['gateway-a-2']['available'],'Aliases normalize and multiple configured gateways keep separate identities');
    $setup->exec("UPDATE tenant_company_modules SET is_active=0 WHERE company_id='a' AND module_id='economy-module'");
    salesAssert(!$sales->paymentMethods()['methods'][1]['available'],'Company Economy activation is required');
    $setup->exec("UPDATE tenant_company_modules SET is_active=1 WHERE company_id='a' AND module_id='economy-module'");
    $base=['name'=>'Kaffe','selling_price'=>'125.00','cost_price'=>'40.00','stock_quantity'=>12,'tax_class'=>'standard','tax_rate'=>'25','item_taxable'=>1];
    $product=$sales->saveProduct($base)['product'];$pid=$product['id'];
    salesAssert($product['tax_class']==='exempt' && (int)$product['item_taxable']===0,'Nonregistered firm forced exempt even with malicious VAT input');
    salesAssert(strlen($product['barcode'])===14 && str_starts_with($product['sku'],'S-'),'Stable internal SKU and Code128 generated');
    $before=(int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $same=$sales->saveProduct(['id'=>$pid,'expected_revision'=>$product['revision']]+$base)['product'];
    salesAssert((int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===$before,'No audit for unchanged product save');
    salesAssert($same['barcode']===$product['barcode'],'No regeneration on save');
    $blocked=new SalesService($db,'a','admin','blocked-cart',new SalesVipps());
    salesAssert($blocked->paymentMethods()['methods'][0]['available'],'Cash default even when global cash=0 and no secure Vipps keys');
    salesReject(fn()=>$blocked->saveProduct($base+['visibility'=>'online']),'Online product blocked without usable server configuration');
    $online=$sales->saveProduct($base+['visibility'=>'online'])['product'];
    salesAssert($online['visibility']==='online','Configured actual gateway binding enables declared web channel');
    $setup->exec("UPDATE economy_payment_gateways SET is_active=0 WHERE id='gateway-a'");
    salesAssert($sales->saveProduct($base+['visibility'=>'online'])['product']['visibility']==='online','A second usable gateway still permits online products');
    $setup->exec("UPDATE economy_payment_gateways SET is_active=0 WHERE id='gateway-a-2'");
    salesReject(fn()=>$sales->saveProduct(['id'=>$pid,'expected_revision'=>$product['revision'],'visibility'=>'both']),'Deactivated gateway blocks editing product into online');
    $setup->exec("UPDATE economy_payment_gateways SET is_active=1 WHERE id IN ('gateway-a','gateway-a-2')");
    $db->beginTransaction();
    entityFormSave($db,'a','sales_product',$pid,[
        ['field_name'=>'color','field_label'=>'Farge','field_type'=>'select','is_visible'=>1,'is_required'=>1,'options'=>['Rød','Blå'],'validation_rules'=>[],'sort_order'=>1],
        ['field_name'=>'engraving','field_label'=>'Tekst','field_type'=>'text','is_visible'=>1,'is_required'=>0,'validation_rules'=>['max_length'=>12],'sort_order'=>0],
    ]);
    $db->commit();
    $newMutation=static fn()=>bin2hex(random_bytes(16));
    $cart=$sales->cartResult()['cart'];
    salesReject(fn()=>$sales->mutateCart('add',['product_id'=>$pid,'quantity'=>1,'form_data'=>[],'revision'=>$cart['revision'],'mutation_id'=>$newMutation()]),'Required dynamic field enforced');
    $add=['product_id'=>$pid,'quantity'=>1,'form_data'=>['color'=>'Rød','engraving'=>'A'],'notes'=>'Første','revision'=>$cart['revision'],'mutation_id'=>$newMutation()];
    $cart=$sales->mutateCart('add',$add)['cart'];
    $again=$sales->mutateCart('add',$add)['cart'];
    salesAssert(count($again['items'])===1 && $again['items'][0]['quantity']===1,'Mutation retry never duplicates line');
    $second=$add;$second['form_data']['color']='Blå';$second['revision']=$cart['revision'];$second['mutation_id']=$newMutation();$second['notes']='Andre';
    $cart=$sales->mutateCart('add',$second)['cart'];
    salesAssert(count($cart['items'])===2 && $cart['total_minor']===25000,'Same product with different answers remains separate');
    salesReject(fn()=>$sales->mutateCart('quantity',['line_id'=>$cart['items'][0]['id'],'quantity'=>12,'revision'=>$cart['revision'],'mutation_id'=>$newMutation()]),'Aggregate stock limit includes all matching product lines');
    $checkout=['revision'=>$cart['revision'],'expected_total_minor'=>25000,'payment_method'=>'cash','received_minor'=>30000];
    salesReject(fn()=>$sales->checkout(array_replace($checkout,['received_minor'=>24999])),'Insufficient cash rejected');
    $receipt=$sales->checkout($checkout)['order'];
    salesAssert($receipt['payment_status']==='paid' && $receipt['change_minor']===5000 && count($receipt['items'])===2,'Cash completes with exact persisted change and separate snapshots');
    salesAssert($receipt['tenant_admin_id']==='admin','Receipt links initiating tenant administrator');
    salesAssert($receipt['tenant_admin_name']==='Kasserer','Receipt preserves readable administrator name at sale time');
    $ledgerActors=$setup->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.tenant_admin_id')) FROM unified_ledger")->fetchAll(PDO::FETCH_COLUMN);
    salesAssert(array_unique($ledgerActors)===['admin'],'Every cash ledger line links verified initiating administrator');
    salesAssert((int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn()===10,'Combined quantity deducted once');
    salesAssert(count(array_unique(array_column($receipt['items'],'id')))===2,'Order/ledger idempotence uses line identity, not product identity');
    $replay=$sales->checkout($checkout)['order'];
    salesAssert($replay['id']===$receipt['id'] && (int)$setup->query('SELECT COUNT(*) FROM sales_orders')->fetchColumn()===1,'Lost cash response retry returns original order, no double sale');
    salesAssert($sales->cartResult()['pending_order']['id']===$receipt['id'],'Reload recovers unacknowledged cash receipt until next customer');
    $fields=array_column($receipt['items'],'form_data');
    salesAssert(count(array_unique(array_column($fields,'color')))===2,'Both immutable answer snapshots retained');
    $fresh=$sales->productList()['products'];$current=array_values(array_filter($fresh,fn($p)=>$p['id']===$pid))[0];
    $sales->saveProduct(['id'=>$pid,'expected_revision'=>$current['revision'],'name'=>'Nytt navn','selling_price'=>'199.00']);
    salesAssert($sales->orderView($receipt['id'])['items'][0]['description']==='Kaffe','Later product editing cannot rewrite receipt');
    $sales->nextCustomer();
    $cart=$sales->cartResult()['cart'];$add['revision']=$cart['revision'];$add['mutation_id']=$newMutation();
    $cart=$sales->mutateCart('add',$add)['cart'];
    $vipps=['revision'=>$cart['revision'],'expected_total_minor'=>$cart['total_minor'],'payment_method'=>'vipps','gateway_id'=>'gateway-a','phone'=>'49999999'];
    salesReject(fn()=>$sales->checkout(array_replace($vipps,['gateway_id'=>'gateway-b'])),'Foreign gateway cannot be used');
    $setup->exec("UPDATE economy_payment_gateways SET is_active=0 WHERE id='gateway-a'");
    salesReject(fn()=>$sales->checkout($vipps),'Gateway disabled after showing the picker is rejected at checkout');
    $setup->exec("UPDATE economy_payment_gateways SET is_active=1 WHERE id='gateway-a'");
    $setup->exec("DELETE FROM tenant_admin_modules WHERE tenant_admin_id='admin' AND module_id='economy-module'");
    salesReject(fn()=>$sales->checkout($vipps),'Economy permission revoked after rendering is rejected at checkout');
    salesAssert(count($provider->requests)===0,'Rejected gateway/permission never creates a provider payment');
    $setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','economy-module',1)");
    $pending=$sales->checkout($vipps)['order'];$ref='sales-'.$pending['id'];
    salesAssert($pending['payment_status']==='pending','Created request is not paid');
    salesAssert($provider->requests[1][2]==='sales-create-'.$pending['id'],'Create key fixed to durable attempt');
    salesReject(fn()=>$sales->nextCustomer(),'Unresolved payment blocks next customer');
    $provider->outage=true;
    $unknown=$sales->reconcile($pending['id']);$provider->outage=false;
    salesAssert($unknown['payment_status']==='unknown','Uncertain remote response remains unknown, never paid');
    $stock=(int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn();
    $provider->payments[$ref]['state']='AUTHORIZED';$provider->captureNow=false;
    $authorized=$sales->reconcile($pending['id']);
    salesAssert($authorized['payment_status']==='authorized','Authorized/capture request acceptance is not proof of payment');
    $provider->captureNow=true;
    $paid=$sales->reconcile($pending['id']);
    salesAssert($paid['payment_status']==='paid','Only GET-verified full capture marks paid');
    $logs=(int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $sales->reconcile($pending['id']);
    salesAssert((int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===$logs,'Repeated callback/poll after capture emits no audit');
    salesAssert((int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn()===$stock,'Capture never deducts reserved stock twice');
    $sales->nextCustomer();$cart=$sales->cartResult()['cart'];$add['revision']=$cart['revision'];$add['mutation_id']=$newMutation();
    $cart=$sales->mutateCart('add',$add)['cart'];$vipps['revision']=$cart['revision'];$vipps['expected_total_minor']=$cart['total_minor'];
    $cancelOrder=$sales->checkout($vipps)['order'];
    $sales->reconcile($cancelOrder['id'],true);$cancelled=$sales->reconcile($cancelOrder['id']);
    salesAssert($cancelled['payment_status']==='cancelled','Cancellation requires verified provider terminal state');
    $restored=$sales->cartResult()['cart'];
    salesAssert(count($restored['items'])===1 && $restored['items'][0]['form_data']['color']==='Rød','Cancelled payment restores original cart customisations');
    $stockAfter=(int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn();
    $sales->reconcile($cancelOrder['id'],true);
    salesAssert((int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn()===$stockAfter,'Duplicate cancellation does not release twice');
    $_SESSION=['company_id'=>'b','tenant_admin_id'=>'admin-b'];
    $other=new SalesService($db,'b','admin-b','other-cart',$provider);
    salesReject(fn()=>$other->orderView($receipt['id']),'Foreign order is inaccessible');
    $taxed=$other->saveProduct($base)['product'];
    $money=SalesMoney::line($taxed,1,true);
    salesAssert($money['total_minor']===12500 && $money['subtotal_minor']===10000 && $money['tax_minor']===2500,'125 gross extracts 25 VAT rather than adding VAT');
    $defaults=$other->saveProduct(['name'=>'Default VAT','selling_price'=>'125','cost_price'=>'','discount_percent'=>'','reorder_level'=>'','low_stock_threeshold'=>''])['product'];
    salesAssert($defaults['tax_class']==='standard' && (int)$defaults['item_taxable']===1 && $defaults['tax_rate']==='25.00','API defaults agree with registered-company UI');
    $kept=$other->saveProduct(['id'=>$defaults['id'],'expected_revision'=>$defaults['revision'],'name'=>'Renamed only'])['product'];
    salesAssert($kept['tax_rate']==='25.00' && $kept['selling_price']==='125.00','Partial edits preserve price and VAT');
    foreach(['food'=>'15.00','transport'=>'12.00','custom'=>'0.00','exempt'=>'0.00'] as $class=>$rate) {
        $p=$other->saveProduct(['name'=>'Class '.$class,'selling_price'=>'125','tax_class'=>$class,'tax_rate'=>$rate])['product'];
        salesAssert($p['tax_class']===$class && $p['tax_rate']===$rate && (int)$p['item_taxable']===($class==='exempt'?0:1),'Supported VAT class round-trips: '.$class);
    }
    foreach([['selling_price'=>''],['cost_price'=>'bad'],['discount_percent'=>'3.333'],['reorder_level'=>'1.5'],['tax_rate'=>''],['tax_class'=>'zero']] as $invalid)
        salesReject(fn()=>$other->saveProduct(array_replace(['name'=>'Invalid','selling_price'=>'125'],$invalid)),'Malformed filled values or missing required VAT rate are rejected');
    salesAssert(str_contains(SalesCode128::svg($product['barcode']),'<rect'),'Code128 barcode rendered from stable text');
    salesAssert(SalesCode128::symbols('AB')===[104,33,34,102,106],'Code128 set B weighted checksum');
    salesAssert(count($sales->orders())===3,'History groups each ledger-backed order once, not each item');
    $rounding=SalesMoney::line(array_replace($taxed,['selling_price'=>'0.05','discount_percent'=>'10.00']),3,true);
    salesAssert($rounding['total_minor']===15 && $rounding['subtotal_minor']+$rounding['tax_minor']===15,'Half-øre discount rounds per unit and tax always reconciles');
    salesReject(fn()=>SalesMoney::integer('1.50',1),'Fractional stock quantity is rejected, never silently truncated');
    $_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin'];
    $current=$sales->productList()['products'];
    $latest=array_values(array_filter($current,fn($p)=>$p['id']===$pid))[0];
    salesReject(fn()=>$sales->saveProduct(['id'=>$pid,'expected_revision'=>$product['revision'],'name'=>'Stale draft']),'Concurrent price/stock edits require latest product revision');
    $before=(int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $sales->adjustStock(['product_id'=>$pid,'quantity'=>$latest['stock_quantity'],'expected_quantity'=>$latest['stock_quantity'],'reason'=>'Kontroll uten endring']);
    salesAssert((int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===$before,'Unchanged stock adjustment emits no audit');
    salesReject(fn()=>$sales->adjustStock(['product_id'=>$pid,'quantity'=>4,'expected_quantity'=>999,'reason'=>'Stale count']),'Stale available-stock adjustment cannot overwrite a sale');
    // Real HMAC verification, then a signed callback with a deliberately stale
    // payload state. Only GET provider state may complete the financial record.
    class SalesFixtureSignedVipps extends SalesFixtureVipps {
        protected function credentials(array $config,string $company): array{return ['webhook_secret'=>'fixture-hmac-secret'];}
    }
    $signed=new SalesFixtureSignedVipps();
    $signedSales=new SalesService($db,'a','admin','fixture-cart',$signed);
    $restored=$signedSales->cartResult()['cart'];$vipps['revision']=$restored['revision'];$vipps['expected_total_minor']=$restored['total_minor'];
    $signedOrder=$signedSales->checkout($vipps)['order'];$signedRef='sales-'.$signedOrder['id'];
    $signed->payments[$signedRef]['state']='AUTHORIZED';
    $body=json_encode(['reference'=>$signedRef,'state'=>'ABORTED']);
    $date=gmdate('D, d M Y H:i:s').' GMT';$hash=base64_encode(hash('sha256',$body,true));
    $server=['HTTP_X_MS_DATE'=>$date,'HTTP_HOST'=>'fixture.example','REQUEST_URI'=>'/api/sales_vipps_webhook.php','HTTP_X_MS_CONTENT_SHA256'=>$hash];
    $signature=base64_encode(hash_hmac('sha256',"POST\n/api/sales_vipps_webhook.php\n$date;fixture.example;$hash",'fixture-hmac-secret',true));
    $server['HTTP_AUTHORIZATION']='HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature='.$signature;
    salesReject(fn()=>SalesWebhookAuth::verify($db,$signed,$body.' ',$server),'Tampered signed body rejected');
    $auth=SalesWebhookAuth::verify($db,$signed,$body,$server);
    $_SESSION=[];
    $callback=new SalesService($db,'a','','',$signed,$auth);
    salesAssert($callback->reconcile($signedOrder['id'])['payment_status']==='paid','Verified webhook needs no administrator session and ignores stale payload state');
    $count=(int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();$callback->reconcile($signedOrder['id']);
    salesAssert((int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===$count,'Repeated signed webhook is a no-op');
    salesAssert((int)$setup->query("SELECT COUNT(*) FROM audit_logs WHERE user_type='system'")->fetchColumn()>=2,'Webhook transitions attributed to system, not impersonated admin');
    $createdActor=$setup->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.tenant_admin_id')) FROM unified_ledger WHERE order_id='".$signedOrder['id']."'")->fetchColumn();
    salesAssert($createdActor==='admin','System capture preserves original administrator linkage');
    salesReject(fn()=>$callback->reconcile($pending['id']),'Signed capability cannot reconcile another order');
    $_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin'];
    $otherTx=(new UnifiedLedgerService($db,'a'))->logTransaction([
        'order_id'=>'fixture-event-attribution','module_source'=>'events','item_type'=>'event','source_id'=>bin2hex(random_bytes(16)),
        'item_name'=>'Sporbarhet fra annen modul','selling_price'=>'0.00','metadata'=>['tenant_admin_id'=>'admin-b','tenant_admin_name'=>'Forged']]);
    salesAssert($otherTx['success'],'Other module uses same traceability service');
    $otherMeta=json_decode($setup->query("SELECT metadata FROM unified_ledger WHERE order_id='fixture-event-attribution'")->fetchColumn(),true);
    salesAssert($otherMeta['tenant_admin_id']==='admin' && $otherMeta['tenant_admin_name']==='Kasserer','Client-supplied foreign administrator cannot forge a transaction actor');
    $mailRequest=['order_id'=>$receipt['id'],'email'=>'receipt@example.invalid','mutation_id'=>$newMutation()];
    salesReject(fn()=>$sales->sendReceipt($mailRequest),'automail_sales_order=0 blocks email receipt server-side');
    $setup->exec("UPDATE email_configs SET automail_sales_order=1 WHERE company_id='a'");
    $setup->exec("INSERT INTO email_templates VALUES('tpl-old','a','sale_receipt',1,'Wrong key','Must not send')");
    salesReject(fn()=>$sales->sendReceipt($mailRequest),'Singular legacy sale_receipt is not used as fallback');
    $setup->exec("INSERT INTO email_templates VALUES('tpl-sales','a','sales_receipt',1,'Kvittering {{order_number}}','<h1>{{company_name}}</h1>{{items_table}}<p>{{total_amount}}</p><p>Fast tekst</p>')");
    $queued=$sales->sendReceipt($mailRequest);
    salesAssert($queued['queued'] && $sales->sendReceipt($mailRequest)['queue_id']===$queued['queue_id'],'Email receipt retries reuse the same queue message');
    salesReject(fn()=>$sales->sendReceipt(array_replace($mailRequest,['email'=>'other@example.invalid'])),'Same send intent cannot be changed to another recipient');
    $queuedRow=$setup->query("SELECT * FROM email_queue")->fetch(PDO::FETCH_ASSOC);
    salesAssert($queuedRow['template_id']==='tpl-sales' && $queuedRow['module_source']==='sales'
        && $queuedRow['source_id']===$receipt['id'] && str_contains($queuedRow['body'],'250,00 kr')
        && str_contains($queuedRow['body'],'Fast tekst'),'Canonical template renders immutable receipt and preserves literal text');
    salesAssert((int)$setup->query('SELECT COUNT(*) FROM email_queue')->fetchColumn()===1,'No duplicate receipt on repeated request');
    salesReject(fn()=>$sales->sendReceipt(array_replace($mailRequest,['order_id'=>$cancelOrder['id'],'mutation_id'=>$newMutation()])),'Cancelled order cannot send a paid-sale receipt');
    $setup->exec("UPDATE email_configs SET automail_sales_order=0 WHERE company_id='a'");
    salesReject(fn()=>$sales->sendReceipt(array_replace($mailRequest,['mutation_id'=>$newMutation()])),'Stale enabled UI cannot bypass a later disabled automailer');
    salesAssert((int)$setup->query('SELECT COUNT(*) FROM email_queue')->fetchColumn()===1,'Disabled automailer never queues a new receipt');
    function salesHttp(string $action,?array $data=null): array {
        global $port;
        $c=curl_init('http://127.0.0.1:'.$port.'/sales.php?action='.$action);
        curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIE=>'PHPSESSID=salesfixture-fixed-session',CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
        if($data!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data)]);
        $raw=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);
        $body=json_decode($raw,true);
        if(!is_array($body))throw new RuntimeException('Fixture HTTP non-JSON response: '.$raw);
        return $body+['_status'=>$status];
    }
    $boot=salesHttp('bootstrap');salesAssert($boot['success'] && $boot['methods'][0]['method_type']==='cash','Real controller bootstrap serves default cash');
    salesAssert(in_array(salesHttp('product_save',['name'=>'Blocked','selling_price'=>'10'])['_status'],[400,403],true),'Real controller rejects missing CSRF');
    salesAssert(salesHttp('checkout')['_status']===405,'Checkout cannot mutate through GET');
    $created=salesHttp('product_save',['name'=>'HTTP product','selling_price'=>'10','csrf_token'=>$boot['csrf_token']]);
    salesAssert($created['success'],'Real controller product creation with CSRF');
    $httpCart=salesHttp('cart')['cart'];
    $httpAdd=salesHttp('add',['csrf_token'=>$boot['csrf_token'],'product_id'=>$created['product']['id'],'quantity'=>1,'revision'=>$httpCart['revision'],'mutation_id'=>$newMutation()]);
    salesAssert(!$httpAdd['success'],'Zero-stock HTTP product cannot be sold');
    $savedOrderCount=$setup->query('SELECT COUNT(*) FROM sales_orders')->fetchColumn();
    $savedLedgerCount=$setup->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn();
    $savedStock=$setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn();
    foreach($statements as $sql)$setup->query($sql)->closeCursor();
    salesAssert($setup->query('SELECT COUNT(*) FROM sales_orders')->fetchColumn()===$savedOrderCount
        && $setup->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===$savedLedgerCount
        && $setup->query("SELECT stock_quantity FROM sales_products WHERE id='$pid'")->fetchColumn()===$savedStock,
        'Delivered migration can be rerun against populated tables without changing sales, ledger or stock');
    $setup->exec("DELETE FROM tenant_admin_modules WHERE tenant_admin_id='admin'");
    salesAssert(salesHttp('bootstrap')['_status']===403,'Real HTTP controller enforces Sales module access');
    $setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','sales-module',1),('admin','economy-module',1)");
    // Browser talks to the existing isolated HTTP fixture, never the app DB.
    // Release PDO owners before spawning the browser process.
    unset($sales,$blocked,$other,$signedSales,$callback,$db,$setup);
    passthru('node --experimental-websocket '.escapeshellarg($root.'/tests/sales-product-api-browser.test.js').' '.escapeshellarg((string)$port).' '.escapeshellarg($temp),$browserStatus);
    salesAssert($browserStatus===0,'Actual product form/API browser regression passed');
    echo "Isolated Sales MySQL, controller, ledger, dynamic fields, VAT and payment state checks passed\n";
} finally {
    proc_terminate($process);proc_close($process);
}
