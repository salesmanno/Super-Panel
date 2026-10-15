<?php
declare(strict_types=1);
// Included only by the disposable MySQL suite, never the live application DB.
$setup->exec("INSERT INTO tenant_admins VALUES('persistent-admin','a',NULL,'Persistent','admin',1),('persistent-other','a',NULL,'Separate','admin',1)");
$setup->exec("INSERT INTO tenant_admin_modules VALUES('persistent-admin','sales-module',1),('persistent-admin','economy-module',1),('persistent-other','sales-module',1)");
$savedSession=$_SESSION;
$_SESSION=['company_id'=>'a','tenant_admin_id'=>'persistent-admin'];
$owner=SalesService::cartOwnerToken('a','persistent-admin');
salesAssert($owner===SalesService::cartOwnerToken('a','persistent-admin'),'Login rotation cannot change persistent cart identity');
salesAssert($owner!==SalesService::cartOwnerToken('b','persistent-admin')
    && $owner!==SalesService::cartOwnerToken('a','persistent-other'),'Persistent identity separates both companies and actors');
salesAssert(SalesService::cartOwnerToken('a|b','c')!==SalesService::cartOwnerToken('a','b|c'),'Identity encoding avoids delimiter collisions');
$legacy='sales-admin:'.hash('sha256','fixture-old-session|a|persistent-admin');
$p=new SalesFixtureVipps();
$oldService=new SalesService($db,'a','persistent-admin',$legacy,$p);
$cart=$oldService->cartResult()['cart'];
$cart=$oldService->mutateCart('add',['product_id'=>$lifecycleProduct['id'],'quantity'=>2,'notes'=>'Behold ved innlogging',
    'revision'=>$cart['revision'],'mutation_id'=>$newMutation()])['cart'];
$service=new SalesService($db,'a','persistent-admin',$owner,$p);
$service->adoptSessionCart($legacy);
$resumed=$service->cartResult()['cart'];
salesAssert($resumed===$cart,'Current legacy session cart is transferred without losing lines, revision or notes');
$service->adoptSessionCart($legacy);
salesAssert($service->cartResult()['cart']===$cart,'Legacy adoption is idempotent');
$relogin=new SalesService($db,'a','persistent-admin',SalesService::cartOwnerToken('a','persistent-admin'),$p);
salesAssert($relogin->cartResult()['cart']===$cart,'Reconstructed authenticated service restores two items');
$_SESSION=['company_id'=>'a','tenant_admin_id'=>'persistent-other'];
$separate=new SalesService($db,'a','persistent-other',SalesService::cartOwnerToken('a','persistent-other'),$p);
salesAssert(!$separate->cartResult()['cart']['items'],'A different cashier cannot inherit the saved cart');
$_SESSION=['company_id'=>'b','tenant_admin_id'=>'admin-b'];
$companyB=new SalesService($db,'b','admin-b',SalesService::cartOwnerToken('b','admin-b'),$p);
salesAssert(!$companyB->cartResult()['cart']['items'],'A different company cannot inherit the saved cart');
$_SESSION=['company_id'=>'a','tenant_admin_id'=>'persistent-admin'];
$changed=$service->mutateCart('quantity',['line_id'=>$cart['items'][0]['id'],'quantity'=>3,'revision'=>$cart['revision'],'mutation_id'=>$newMutation()])['cart'];
try {
    $relogin->mutateCart('quantity',['line_id'=>$cart['items'][0]['id'],'quantity'=>4,'revision'=>$cart['revision'],'mutation_id'=>$newMutation()]);
    salesAssert(false,'Stale tab must not overwrite persistent cart');
} catch(SalesConflict $expected) {}
salesAssert($relogin->cartResult()['cart']===$changed,'Stale-tab conflict preserves the newer saved cart');
$request=['revision'=>$changed['revision'],'expected_total_minor'=>$changed['total_minor'],'payment_method'=>'vipps','gateway_id'=>'gateway-a','phone'=>'49999999'];
salesFixtureRegister($service);
$order=$service->checkout($request)['order'];
$relogin=new SalesService($db,'a','persistent-admin',$owner,$p);
$waiting=$relogin->cartResult();
salesAssert($waiting['pending_order']['id']===$order['id'],'New login finds the original pending payment');
salesAssert($relogin->checkout($request)['order']['id']===$order['id'],'New login cannot create a replacement payment');
salesAssert(count(array_filter($p->requests,fn($r)=>$r[0]==='POST'&&$r[1]===''))===1,'Payment creation occurs only once across logins');
try {
    $relogin->mutateCart('add',['product_id'=>$lifecycleProduct['id'],'quantity'=>1,'revision'=>'new','mutation_id'=>$newMutation()]);
    salesAssert(false,'Unresolved payment must block a new cart');
} catch(SalesConflict $expected) {}
$ref='sales-'.$order['id'];$p->payments[$ref]['state']='AUTHORIZED';
salesAssert($relogin->reconcile($order['id'])['payment_status']==='paid','Resumed payment captures the original order');
$again=new SalesService($db,'a','persistent-admin',$owner,$p);
salesAssert($again->cartResult()['pending_order']['payment_status']==='paid','Completed receipt remains recoverable on another login');
$empty=$again->nextCustomer()['cart'];
salesAssert(!$empty['items'] && !$again->cartResult()['pending_order'],'Acknowledging sale starts a clean persistent cart');
$oldService=new SalesService($db,'a','persistent-admin',$legacy,$p);
$old=$oldService->cartResult()['cart'];
$old=$oldService->mutateCart('add',['product_id'=>$lifecycleProduct['id'],'quantity'=>1,'revision'=>$old['revision'],'mutation_id'=>$newMutation()])['cart'];
try {$again->adoptSessionCart($legacy);salesAssert(false,'Distinct old/new active carts must not be silently merged');}
catch(SalesConflict $expected) {}
salesAssert($oldService->cartResult()['cart']===$old && $again->cartResult()['cart']===$empty,'Conflicting legacy carts are preserved for explicit resolution');
$unknown='sales-admin:'.hash('sha256','a-lost-session-that-cannot-be-attributed');
$unknownService=new SalesService($db,'a','persistent-admin',$unknown,$p);
$uncart=$unknownService->cartResult()['cart'];
$unknownService->mutateCart('add',['product_id'=>$lifecycleProduct['id'],'quantity'=>1,'revision'=>$uncart['revision'],'mutation_id'=>$newMutation()]);
$again->adoptSessionCart('sales-admin:'.hash('sha256','current-known-session'));
salesAssert(!$again->cartResult()['cart']['items'],'Unrelated legacy hashes are never guessed or automatically assigned');
$setup->exec("DELETE FROM tenant_admin_modules WHERE tenant_admin_id='persistent-admin'");
try {new SalesService($db,'a','persistent-admin',$owner,$p);salesAssert(false,'Login without Sales access cannot recover cart');}
catch(TenantAuditException|DomainException $expected) {}
$_SESSION=$savedSession;
echo "Sales persistent cart, legacy adoption, cashier/company isolation, stale-tab and pending-payment recovery cases passed\n";
