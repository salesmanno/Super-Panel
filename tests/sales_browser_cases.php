<?php
// UI-only verification for an existing verified backend change. The fixture
// still uses the real controller, schemas, triggers and a disposable database.
declare(strict_types=1);
salesInstallRegisterFixture($setup,$root);
// Match the payment-method cases exercised by the full backend fixture.
$setup->exec("INSERT INTO economy_configuration_data(id,company_id,config_name,gateway_type,test_mode,is_active) VALUES('cfg-off','a','Deaktivert','vipps',0,0),('cfg-test','a','Test','vipps',1,1),('cfg-card','a','Kort','card',0,1)");
$setup->exec("INSERT INTO economy_configuration_data SELECT 'cfg-custom','a','Andre kasse',gateway_type,test_mode,is_active,client_id,client_secret_encrypted,subscription_key,merchant_serial_number,public_key,private_key_encrypted FROM economy_configuration_data WHERE id='cfg-a'");
$setup->exec("INSERT INTO economy_payment_gateways(id,company_id,configuration_id,gateway_name,is_active) VALUES('gateway-missing','a','missing','Mangler',1),('gateway-foreign','a','cfg-b','Feilkoblet',1),('gateway-off','a','cfg-off','Av',1),('gateway-test','a','cfg-test','Test',1),('gateway-card','a','cfg-card','Terminal',1),('gateway-inactive','a','cfg-a','Inaktiv',0),('gateway-a-2','a','cfg-custom','Andre kasse',1)");
$fixtureService=new SalesService($db,'a','admin','browser-seed',new SalesFixtureVipps());
$fixtureService->saveProduct(['name'=>'Browserprodukt','selling_price'=>'125.00','stock_quantity'=>30]);
require $root.'/tests/sales_retry_fixture.php';
unset($fixtureService,$db,$setup);gc_collect_cycles();
foreach(['sales-register-api-browser.test.js','sales-product-api-browser.test.js',
    'sales-vipps-api-browser.test.js','sales-vipps-retry-api-browser.test.js','sales-responsive-api-browser.test.js'] as $browserTest) {
    passthru('node --experimental-websocket '.escapeshellarg($root.'/tests/'.$browserTest).' '.escapeshellarg((string)$port).' '.escapeshellarg($temp),$status);
    salesAssert($status===0,'Actual Sales controller/browser regression passed: '.$browserTest);
}
echo "Sales browser-only verification passed: registers, products, cash, Vipps diagnostics, responsive fullscreen.\n";
if(getenv('SALES_CAPTURE_HOLD')==='1') {
    echo "Sales fixture ready for capture on port $port\n";
    sleep(150);
}
