<?php
declare(strict_types=1);
// Only disposable sales_fixture. Production configuration is never imported.
salesInstallRegisterFixture($setup,$root);
$provider=new SalesFixtureVipps();
$sales=new SalesService($db,'a','admin','dashboard-backend',$provider);
$setup->exec("INSERT INTO tenant_staff VALUES('staff-a','a','Ola','Kasserer'),('foreign-staff','b','Feil','Firma')");
$setup->exec("UPDATE tenant_admins SET tenant_staff_id='staff-a' WHERE id='admin'");
$sales=new SalesService($db,'a','admin','dashboard-backend',$provider);
salesAssert($sales->registerState()['cashier_name']==='Ola Kasserer','Same-company staff name displayed');
$setup->exec("UPDATE tenant_admins SET tenant_staff_id='foreign-staff' WHERE id='admin'");
$sales=new SalesService($db,'a','admin','dashboard-backend',$provider);
salesAssert($sales->registerState()['cashier_name']==='Kasserer','Foreign staff cannot supply cashier identity');
$setup->exec("UPDATE tenant_admins SET tenant_staff_id='staff-a' WHERE id='admin'");
$point=$sales->registerAction('register_create',['idempotency_key'=>'dashboard-first-point']);
$id=$point['selected_register_id'];
salesAssert($point['registers'][0]['register_code']==='1','First register automatically numbered');
$again=$sales->registerAction('register_create',['idempotency_key'=>'dashboard-first-point']);
salesAssert(count($again['registers'])===1 && $again['selected_register_id']===$id,'Retry does not allocate again');
$sales->registerAction('register_activate',['register_id'=>$id,'idempotency_key'=>'dashboard-activate-one']);
$sales->registerAction('register_open',['register_id'=>$id,'opening_float_minor'=>0,'idempotency_key'=>'dashboard-opening-one']);
$empty=$sales->saveProduct(['name'=>'Tom vare','selling_price'=>'10','stock_quantity'=>0])['product'];
$stale=$empty['revision'];
$empty=$sales->saveProduct(['id'=>$empty['id'],'expected_revision'=>$stale,'name'=>'Endret tom vare'])['product'];
salesReject(fn()=>$sales->removeProduct(['id'=>$empty['id'],'expected_revision'=>$stale]),'Stale product removal rejected');
$removed=$sales->removeProduct(['id'=>$empty['id'],'expected_revision'=>$empty['revision']]);
salesAssert($removed['mode']==='deleted','Verified empty product physically removed');
salesAssert(!$setup->query("SELECT id FROM sales_products WHERE id='{$empty['id']}'")->fetchColumn(),'Empty product no longer exists');
$stock=$sales->saveProduct(['name'=>'Beholdning','selling_price'=>'10','stock_quantity'=>2])['product'];
salesAssert($sales->removeProduct(['id'=>$stock['id'],'expected_revision'=>$stock['revision']])['mode']==='deactivated','Inventory retained on deactivation');
$inactive=array_values(array_filter($sales->productList()['products'],fn($v)=>$v['id']===$stock['id']))[0];
$auditCount=(int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
$journalCount=(int)$setup->query('SELECT COUNT(*) FROM sales_register_journal')->fetchColumn();
$sales->removeProduct(['id'=>$inactive['id'],'expected_revision'=>$inactive['revision']]);
salesAssert((int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===$auditCount
    && (int)$setup->query('SELECT COUNT(*) FROM sales_register_journal')->fetchColumn()===$journalCount,'Already inactive no-op writes no audit or journal event');
$history=$sales->saveProduct(['name'=>'Lagerhistorikk','selling_price'=>'10','stock_quantity'=>0])['product'];
$history=$sales->adjustStock(['product_id'=>$history['id'],'quantity'=>1,'expected_quantity'=>0,'reason'=>'Testlager'])['product'];
$history=$sales->adjustStock(['product_id'=>$history['id'],'quantity'=>0,'expected_quantity'=>1,'reason'=>'Retur testlager'])['product'];
salesAssert($sales->removeProduct(['id'=>$history['id'],'expected_revision'=>$history['revision']])['mode']==='deactivated','Zero remaining stock does not erase stock history');
$p=$sales->saveProduct(['name'=>'Historikkvare','selling_price'=>'10','stock_quantity'=>1])['product'];
$cart=$sales->cartResult()['cart'];
$cart=$sales->mutateCart('add',['product_id'=>$p['id'],'quantity'=>1,'form_data'=>[],
    'mutation_id'=>SalesRegisterStore::id(),'revision'=>$cart['revision']])['cart'];
salesReject(fn()=>$sales->removeProduct(['id'=>$p['id'],'expected_revision'=>$p['revision']]),'Active cart prevents removal');
$order=$sales->checkout(['revision'=>$cart['revision'],'payment_method'=>'cash','received_minor'=>1000,'expected_total_minor'=>1000])['order'];
$p=array_values(array_filter($sales->productList()['products'],fn($v)=>$v['id']===$p['id']))[0];
salesAssert($sales->removeProduct(['id'=>$p['id'],'expected_revision'=>$p['revision']])['mode']==='deactivated','Sold-out product retained for order/cart history');
salesAssert($sales->orderView($order['id'])['items'][0]['description']==='Historikkvare','Removal preserves frozen receipt');
$send=['order_id'=>$order['id'],'email'=>'receipt@example.invalid','mutation_id'=>'dashboard-receipt-intent'];
salesReject(fn()=>$sales->sendReceipt($send),'Disabled automail blocks receipt email');
$setup->exec("UPDATE email_configs SET automail_sales_order=1 WHERE company_id='a'");
salesReject(fn()=>$sales->sendReceipt($send),'Missing active receipt template blocks email');
$setup->exec("INSERT INTO email_templates VALUES('receipt-template','a','sales_receipt',1,'Kvittering {{order_number}}','Hei {{company_name}}')");
salesReject(fn()=>$sales->sendReceipt(array_replace($send,['email'=>'not-an-email'])),'Invalid email rejected');
$sales->sendReceipt($send);$sales->sendReceipt($send);
salesAssert((int)$setup->query("SELECT COUNT(*) FROM email_queue")->fetchColumn()===1,'Sales-only access queues exact email intent once');
$sales->nextCustomer();
$pendingProduct=$sales->saveProduct(['name'=>'Venter på Vipps','selling_price'=>'10','stock_quantity'=>1])['product'];
$pendingCart=$sales->cartResult()['cart'];
$pendingCart=$sales->mutateCart('add',['product_id'=>$pendingProduct['id'],'quantity'=>1,'revision'=>$pendingCart['revision'],'form_data'=>[],'mutation_id'=>SalesRegisterStore::id()])['cart'];
$pending=$sales->checkout(['revision'=>$pendingCart['revision'],'payment_method'=>'vipps','gateway_id'=>'gateway-a','phone'=>'91234567','expected_total_minor'=>1000])['order'];
salesReject(fn()=>$sales->removeProduct(['id'=>$pendingProduct['id'],'expected_revision'=>$pendingProduct['revision']]),'Pending payment prevents removing reserved product');
salesReject(fn()=>$sales->sendReceipt(array_replace($send,['order_id'=>$pending['id'],'mutation_id'=>'dashboard-unpaid-email'])),'Unpaid order cannot receive receipt email');
$sales->reconcile($pending['id'],true);
$pendingCart=$sales->cartResult()['cart'];
$sales->mutateCart('remove',['line_id'=>$pendingCart['items'][0]['id'],'revision'=>$pendingCart['revision'],'mutation_id'=>SalesRegisterStore::id()]);
$x=$sales->registerReport(['register_id'=>$id,'kind'=>'x_report','idempotency_key'=>'dashboard-x-report'])['document'];
salesAssert($sales->registerState()['period']!==null,'X report does not close period');
$zData=['register_id'=>$id,'kind'=>'z_report','counted_cash_minor'=>1000,'reason'=>'','idempotency_key'=>'dashboard-z-report'];
$z=$sales->registerReport($zData)['document'];
salesAssert($sales->registerReport($zData)['document']['id']===$z['id'],'Z retry produces no duplicate');
salesAssert($sales->registerState()['period']===null,'Z closes period');
$reports=$sales->registerReports($id)['documents'];
salesAssert(count($reports)===2 && $reports[0]['id']===$z['id'],'Closed-period X/Z remain accessible');
salesAssert($sales->registerReports($id,(string)$z['sequence_number'])['documents'][0]['id']===$x['id'],'History cursor filters older documents');
salesReject(fn()=>$sales->registerReports($id,'invalid'),'Malformed history cursor rejected');

// VAT1 gate blocks direct server mutations before a register has been opened.
$_SESSION=['company_id'=>'b','tenant_admin_id'=>'admin-b'];
$other=new SalesService($db,'b','admin-b','dashboard-vat1',$provider);
$bp=$other->saveProduct(['name'=>'MVA-vare','selling_price'=>'25','stock_quantity'=>5])['product'];
$bc=$other->cartResult()['cart'];
$add=['product_id'=>$bp['id'],'quantity'=>1,'revision'=>$bc['revision'],'form_data'=>[],'mutation_id'=>SalesRegisterStore::id()];
salesReject(fn()=>$other->mutateCart('add',$add),'VAT1 direct add before register rejected');
salesReject(fn()=>$other->removeProduct(['id'=>$p['id'],'expected_revision'=>$p['revision']]),'Foreign product removal rejected');
salesReject(fn()=>$other->registerReports($id),'Foreign report history rejected');
salesReject(fn()=>$other->sendReceipt($send),'Foreign receipt email rejected');
salesFixtureRegister($other);
$bc=$other->mutateCart('add',$add)['cart'];
salesAssert(count($bc['items'])===1,'VAT1 open register allows adding');
$bid=$other->registerState()['selected_register_id'];
salesReject(fn()=>$other->registerReport(['register_id'=>$bid,'kind'=>'z_report','counted_cash_minor'=>0,'reason'=>'','idempotency_key'=>'dashboard-z-active-cart']),'Open cart blocks Z');
$other->mutateCart('remove',['line_id'=>$bc['items'][0]['id'],'revision'=>$bc['revision'],'mutation_id'=>SalesRegisterStore::id()]);
$other->registerReport(['register_id'=>$bid,'kind'=>'z_report','counted_cash_minor'=>0,'reason'=>'','idempotency_key'=>'dashboard-z-empty-cart']);
salesReject(fn()=>$other->mutateCart('add',array_replace($add,['mutation_id'=>SalesRegisterStore::id(),'revision'=>$other->cartResult()['cart']['revision']])),'Closed VAT1 period blocks new products');

$_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin'];
$sales=new SalesService($db,'a','admin','dashboard-numbering',$provider);
$second=$sales->registerAction('register_create',['idempotency_key'=>'dashboard-second-point']);
$secondId=$second['selected_register_id'];
$setup->prepare("UPDATE sales_registers SET status='retired',register_code='9' WHERE company_id='a' AND id=?")->execute([$secondId]);
$third=$sales->registerAction('register_create',['idempotency_key'=>'dashboard-third-point']);
$thirdId=$third['selected_register_id'];
salesAssert(array_column($third['registers'],'register_code','id')[$thirdId]==='10','Retired numeric point included in max+1');
$setup->prepare("UPDATE sales_registers SET register_code='LEGACY-A' WHERE company_id='a' AND id=?")->execute([$thirdId]);
// Independent PHP processes, started only after all live PDO handles are closed.
$child='<?php require '.var_export($root.'/includes/AuditedPDO.php',true).'; require '.var_export($root.'/includes/SalesService.php',true).'; $_SESSION=["company_id"=>"a","tenant_admin_id"=>"admin"]; $db=new AuditedPDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); $s=new SalesService($db,"a","admin",$argv[1]); echo json_encode($s->registerAction("register_create",["idempotency_key"=>$argv[1]]));';
file_put_contents($temp.'/numbering-worker.php',$child);
unset($sales,$other,$db,$setup);gc_collect_cycles();
$jobs=[];
foreach(['dashboard-concurrent-a','dashboard-concurrent-b'] as $key) {
    $jobs[]=[$key,proc_open(['php',$temp.'/numbering-worker.php',$key],[0=>['file','/dev/null','r'],1=>['file',$temp.'/'.$key.'.json','w'],2=>['file',$temp.'/'.$key.'.err','w']],$pipes)];
}
$codes=[];
foreach($jobs as [$key,$job]) {
    salesAssert(proc_close($job)===0,'Concurrent allocator succeeded: '.@file_get_contents($temp.'/'.$key.'.err').' '.@file_get_contents($temp.'/'.$key.'.json'));
    $r=json_decode(file_get_contents($temp.'/'.$key.'.json'),true,512,JSON_THROW_ON_ERROR);
    $codes[]=array_column($r['registers'],'register_code','id')[$r['selected_register_id']];
}
sort($codes);salesAssert($codes===['10','11'],'Concurrent creates allocate distinct max+1 without rewriting legacy code');
echo "Sales dashboard backend passed: numbering/retry/concurrency, identity, deletion/history, VAT gate, X/Z history, optional email.\n";
if(!in_array('--backend-only',$argv,true)) {
    foreach(['sales-dashboard-api-browser.test.js','sales-product-removal-receipt-browser.test.js'] as $test) {
        passthru('node --experimental-websocket '.escapeshellarg($root.'/tests/'.$test).' '.escapeshellarg((string)$port).' '.escapeshellarg($temp),$status);
        salesAssert($status===0,'Actual compact Sales UI regression: '.$test);
    }
    if(getenv('SALES_CAPTURE_HOLD')==='1'){echo "Sales fixture ready for capture on port $port\n";sleep(150);}
}
