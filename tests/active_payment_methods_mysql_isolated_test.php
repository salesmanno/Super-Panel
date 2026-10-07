<?php
declare(strict_types=1);
$root=$argv[1];$temp=$argv[2];
if (!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.') || !file_exists($temp.'/mysql.sock')) throw new RuntimeException('Disposable harness required.');
require_once $root.'/includes/active_payment_methods.php';
require_once $root.'/includes/event_settings.php';
function checkPayment(bool $condition,string $message):void {if (!$condition) throw new RuntimeException($message);}
$base='mysql:unix_socket='.$temp.'/mysql.sock';
$setup=new PDO($base,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE active_fixture');
$dsn=$base.';dbname=active_fixture;charset=utf8mb4';
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
foreach ([
"CREATE TABLE tenant_companies(id VARCHAR(36) PRIMARY KEY,name TEXT,is_active INT,cash_payment INT)",
"CREATE TABLE tenant_admins(id VARCHAR(36),company_id VARCHAR(36),tenant_staff_id VARCHAR(36),username TEXT,role VARCHAR(20),is_active INT)",
"CREATE TABLE tenant_staff(id VARCHAR(36),company_id VARCHAR(36),first_name TEXT,last_name TEXT)",
"CREATE TABLE system_modules(id VARCHAR(36),module_key VARCHAR(30),is_active INT)",
"CREATE TABLE tenant_admin_modules(tenant_admin_id VARCHAR(36),module_id VARCHAR(36))",
"CREATE TABLE economy_configuration_data(id VARCHAR(36),company_id VARCHAR(36),gateway_type VARCHAR(30),config_name TEXT,is_active INT,test_mode INT,client_id TEXT,client_secret_encrypted TEXT,merchant_serial_number TEXT,subscription_key TEXT,public_key TEXT,private_key_encrypted TEXT,updated_at DATETIME)",
"CREATE TABLE economy_bank_accounts(company_id VARCHAR(36),is_active INT,account_number TEXT)",
"CREATE TABLE economy_payment_gateways(company_id VARCHAR(36),gateway_name VARCHAR(30),is_active INT)",
"CREATE TABLE membership_plans(id VARCHAR(36),company_id VARCHAR(36),name TEXT,price DECIMAL(12,2),billing_cycle VARCHAR(30))",
"CREATE TABLE tenant_customers(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),customer_code TEXT,first_name TEXT,last_name TEXT,email TEXT,phone TEXT,address_street TEXT,address_city TEXT,address_zip TEXT,date_of_birth DATE,notes TEXT,customer_type TEXT,status TEXT,created_at DATETIME,updated_at DATETIME)",
"CREATE TABLE membership_members(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),customer_id VARCHAR(36),membership_plan_id VARCHAR(36),member_code TEXT,share_code TEXT,status TEXT,join_date DATE,expiration_date DATE,payment_status VARCHAR(20),paymentMethod TEXT,auto_renew INT,created_at DATETIME)",
"CREATE TABLE unified_ledger(id VARCHAR(36) PRIMARY KEY,order_id VARCHAR(100) UNIQUE,company_id VARCHAR(36),module_source TEXT,item_type TEXT,source_id TEXT,item_name TEXT,quantity INT,selling_price DECIMAL(12,2),cost_price DECIMAL(12,2),customer_id TEXT,payment_method TEXT,payment_status TEXT,status TEXT,transaction_date DATETIME,transaction_type TEXT,category TEXT,metadata JSON)",
"CREATE TABLE events_eventdata(id VARCHAR(36),company_id VARCHAR(36),title TEXT,is_active INT,registration_type VARCHAR(20),payment_methods_json JSON)",
"ALTER TABLE events_eventdata ADD start_date DATE,ADD start_time TIME,ADD venue_name TEXT,ADD event_city TEXT,ADD ticket_price DECIMAL(12,2),ADD is_member_exclusive INT,ADD default_payment_method TEXT",
"ALTER TABLE membership_members ADD expiry_date DATE",
"CREATE TABLE events_checkin(id VARCHAR(36) PRIMARY KEY,event_id VARCHAR(36),customer_id VARCHAR(36),membership_plan_id VARCHAR(36),ticket_price DECIMAL(12,2),payment_status VARCHAR(20),payment_method TEXT,checked_in INT,check_in_time DATETIME,checked_in_by VARCHAR(36),barcode TEXT,notes TEXT,created_at DATETIME,updated_at DATETIME)",
"INSERT INTO tenant_companies VALUES('a','A',1,0),('b','B',1,1)",
"INSERT INTO tenant_admins VALUES('admin','a',NULL,'fixture','admin',1)",
"INSERT INTO system_modules VALUES('member','membership',1),('econ','economy',1)",
"INSERT INTO tenant_admin_modules VALUES('admin','member'),('admin','econ')",
"INSERT INTO economy_payment_gateways VALUES('a','paypal',1)",
"INSERT INTO economy_configuration_data(id,company_id,gateway_type,config_name,is_active,test_mode) VALUES('v','a','vipps','Vipps',1,1),('off','a','stripe','Kort',0,0),('foreign','b','paypal','PayPal',1,0),('unknown','a','mystery','Mystery',1,0)",
"INSERT INTO membership_plans VALUES('priced','a','Betalt',100,'yearly'),('free','a','Gratis',0,'yearly'),('other','b','Annet',50,'yearly')",
"INSERT INTO events_eventdata(id,company_id,title,is_active,registration_type,payment_methods_json,ticket_price) VALUES('binding','a','Bindende',1,'binding','[\"vipps\"]',100),('signup','a','Påmelding',1,'signup','[\"cash\",\"vipps\"]',100)",
] as $sql) $db->exec($sql);
$_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin'];
checkPayment(array_column(activePaymentMethods($db,'a'),'method_type')===['vipps'],'Activation source, tenant boundary, unknown and inactive methods');
checkPayment(activePaymentMethods($db,'a')[0]['is_demo'] && !activePaymentMethods($db,'a')[0]['is_configured'],'Test/configuration flags remain distinct');
checkPayment(activePaymentMethods($db,'missing')===[],'Missing company has no automatic cash');
foreach (['Kontant','paypal','unknown',null,[]] as $method) {
    $blocked=false;try {requireActivePaymentMethod($db,'a',$method);}catch(InvalidArgumentException $e){$blocked=true;}
    checkPayment($blocked,'Invalid/unactivated method rejected');
}
$db->exec("UPDATE tenant_companies SET cash_payment=1 WHERE id='a'");
checkPayment(requireActivePaymentMethod($db,'a','Kontant')==='cash','Cash alias after explicit activation');
$blocked=false;try {requireActiveEventPayment($db,'a','binding','cash',100);}catch(InvalidArgumentException $e){$blocked=true;}
checkPayment($blocked,'Binding rejects active cash too');
checkPayment(requireActiveEventPayment($db,'a','signup','Vipps',100)==='vipps','Ticket validates event selected method');
$db->exec("UPDATE economy_configuration_data SET is_active=0 WHERE id='v'");
$db->beginTransaction();
$old=['registration_type'=>'binding','payment_methods_json'=>'["Vipps"]','default_payment_method'=>'Vipps'];
$settings=eventSettings($db,'a',['title'=>'Endret'],$old);
checkPayment($settings['payment_methods_json']==='["Vipps"]' && $settings['default_payment_method']==='Vipps','Basic edit preserves historical spelling and inactive selection');
$blocked=false;try {eventSettings($db,'a',['registration_type'=>'binding','allowed_payment_methods'=>['vipps']]);}catch(InvalidArgumentException $e){$blocked=true;}
checkPayment($blocked,'New binding cannot use deactivated gateway');
$db->rollBack();
// A pre-existing consistent-read snapshot must not authorize an old activation.
$db->exec("UPDATE economy_configuration_data SET is_active=1 WHERE id='v'");
$db->beginTransaction();$db->query('SELECT is_active FROM economy_configuration_data')->fetchAll();
$other=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$other->exec("UPDATE economy_configuration_data SET is_active=0 WHERE id='v'");
activePaymentMethodsLock($db,'a');
$blocked=false;try {requireActivePaymentMethod($db,'a','vipps');}catch(InvalidArgumentException $e){$blocked=true;}
checkPayment($blocked,'Current-read validation sees activation changed after a transaction snapshot');
$db->rollBack();
$dir=$temp.'/payment-http';mkdir($dir);
$bootstrap='function getDBConnection(){return new PDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}';
foreach (['get_payment_methods.php','create_member.php','log_membership_payment.php','toggle_payment_gateway.php','add_event_income.php','add_event_finance.php','renew_membership.php','checkin_event.php'] as $file) {
    $source=file_get_contents($root.'/api/'.$file);
    // No SMTP or email credentials are loaded. Fixture check-ins omit email.
    $source=preg_replace('~require_once[^;]*EmailService\\.php[^;]*;~','',$source);
    $source=str_replace('session_start();','session_save_path('.var_export($temp.'/sessions',true).');session_start();$_SESSION["company_id"]="a";$_SESSION["tenant_admin_id"]="admin";',$source);
    $source=preg_replace("~require_once\\s+(?:__DIR__\\s*\\.\\s*)?'/?\\.\\./config/database.php';~",$bootstrap,$source);
    $source=preg_replace_callback("~__DIR__\\s*\\.\\s*'/\\.\\./includes/([^']+)'~",static fn($m)=>var_export($root.'/includes/'.$m[1],true),$source);
    $source=preg_replace_callback("~'\\.\\./includes/([^']+)'~",static fn($m)=>var_export($root.'/includes/'.$m[1],true),$source);
    file_put_contents($dir.'/'.$file,$source);
}
$sock=stream_socket_server('tcp://127.0.0.1:0');$port=(int)substr(strrchr(stream_socket_get_name($sock,false),':'),1);fclose($sock);
$worker=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$dir],[0=>['file','/dev/null','r'],1=>['file',$temp.'/payments.log','a'],2=>['file',$temp.'/payments.log','a']],$pipes);
function paymentHttp(string $route,?array $input=null):array {
    global $port;
    $body=file_get_contents('http://127.0.0.1:'.$port.'/'.$route,false,stream_context_create(['http'=>['method'=>$input===null?'GET':'POST','header'=>"Content-Type: application/json\r\n",'content'=>$input===null?'':json_encode($input),'ignore_errors'=>true]]));
    return json_decode($body,true,512,JSON_THROW_ON_ERROR);
}
try {
    for($i=0;$i<60;$i++){if($s=@fsockopen('127.0.0.1',$port)){fclose($s);break;}usleep(100000);}
    $list=paymentHttp('get_payment_methods.php');
    checkPayment($list['success'] && array_column($list['payment_methods'],'method_type')===['cash'],'HTTP only returns active company methods, not stale gateway table');
    checkPayment(!str_contains(json_encode($list),'secret') && !str_contains(json_encode($list),'client_id'),'No credential data exposed');
    $input=['first_name'=>'Fixture','last_name'=>'Test','membership_plan_id'=>'priced','payment_status'=>'paid'];
    foreach ([null,'vipps','paypal','mystery'] as $method) {
        checkPayment(!paymentHttp('create_member.php',$input+['paymentMethod'=>$method])['success'],'Member rejects missing/inactive/foreign/unknown method');
        checkPayment((int)$db->query('SELECT COUNT(*) FROM tenant_customers')->fetchColumn()===0,'Rejected member never leaves customer behind');
    }
    $input['payment_status']='unpaid';
    $unpaid=paymentHttp('create_member.php',$input);
    checkPayment($unpaid['success'],'Unpaid membership needs no fictitious payment');
    checkPayment((int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===0,'No ledger for unpaid membership');
    $free=paymentHttp('create_member.php',['first_name'=>'Gratis','last_name'=>'Test','membership_plan_id'=>'free','payment_status'=>'paid']);
    checkPayment($free['success'] && (int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===0,'Free paid membership has no invented transaction');
    $paid=paymentHttp('create_member.php',['first_name'=>'Betalt','last_name'=>'Test','dateofbirth'=>'','membership_plan_id'=>'priced','payment_status'=>'paid','paymentMethod'=>'Kontant']);
    checkPayment($paid['success'] && $db->query('SELECT payment_method FROM unified_ledger')->fetchColumn()==='cash','Explicit active cash canonicalized and logged');
    $before=(int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn();
    $log=['member_id'=>$paid['member_id'],'amount'=>100];
    checkPayment(!paymentHttp('log_membership_payment.php',$log)['success'],'No implicit Vipps in payment logging');
    checkPayment(!paymentHttp('log_membership_payment.php',$log+['payment_method'=>'vipps'])['success'],'Logging rejects deactivation');
    checkPayment(paymentHttp('log_membership_payment.php',$log+['payment_method'=>'Kontant'])['success'],'Logging accepts explicit active method');
    $renew=['member_id'=>$paid['member_id'],'renewal_date'=>'2026-10-08'];
    $oldExpiry=$db->query("SELECT expiry_date FROM membership_members WHERE id='".$paid['member_id']."'")->fetchColumn();
    checkPayment(!paymentHttp('renew_membership.php',$renew)['success'],'Paid renewal needs a chosen payment method');
    checkPayment(!paymentHttp('renew_membership.php',$renew+['payment_method'=>'vipps'])['success'],'Paid renewal rejects inactive method');
    checkPayment($db->query("SELECT expiry_date FROM membership_members WHERE id='".$paid['member_id']."'")->fetchColumn()===$oldExpiry,'Rejected renewal leaves dates unchanged');
    checkPayment(paymentHttp('renew_membership.php',$renew+['payment_method'=>'Kontant'])['success'],'Paid renewal accepts chosen active method');
    $count=(int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn();
    checkPayment(paymentHttp('renew_membership.php',['member_id'=>$free['member_id'],'renewal_date'=>'2026-10-08'])['success'],'Free renewal needs no method');
    checkPayment((int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===$count,'Free renewal creates no fictitious payment');
    $db->exec("UPDATE tenant_companies SET cash_payment=0 WHERE id='a'");
    $checkin=['event_id'=>'signup','attendee_first_name'=>'Fixture','attendee_last_name'=>'Pending','is_paying'=>false,'payment_method'=>null];
    checkPayment(paymentHttp('checkin_event.php',$checkin)['success'],'Priced unpaid check-in succeeds with no active methods');
    $row=$db->query('SELECT payment_status,payment_method,ticket_price FROM events_checkin')->fetch();
    checkPayment($row['payment_status']==='pending' && $row['payment_method']===null && (float)$row['ticket_price']===100.0,'Unpaid check-in preserves price and pending status without a method');
    checkPayment((int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===$count,'Pending check-in creates no revenue');
    checkPayment(paymentHttp('checkin_event.php',array_replace($checkin,['payment_method'=>'free']))['success'],'Legacy unpaid free sentinel does not invent payment');
    $records=(int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn();
    checkPayment(!paymentHttp('checkin_event.php',array_replace($checkin,['is_paying'=>true,'paid'=>true,'payment_method'=>'cash']))['success'],'Actual paid check-in rejects inactive method');
    checkPayment((int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===$records,'Rejected payment creates no check-in');
    $db->exec("UPDATE tenant_companies SET cash_payment=1 WHERE id='a'");
    checkPayment(paymentHttp('checkin_event.php',array_replace($checkin,['is_paying'=>true,'paid'=>true,'payment_method'=>'Kontant']))['success'],'Actual paid check-in accepts active alias');
    checkPayment((int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===$count+1,'Actual paid check-in records revenue');
    $db->exec("DELETE FROM tenant_admin_modules WHERE module_id='econ'");
    checkPayment(!paymentHttp('toggle_payment_gateway.php',['gateway_name'=>'cash','is_active'=>true])['success'],'Activation requires Economy access');
    checkPayment(paymentHttp('get_payment_methods.php')['success'],'Membership admin can choose already activated company methods without personal Economy access');
    $db->exec("INSERT INTO tenant_admin_modules VALUES('admin','econ')");
    // Only synthetic fixture values, never genuine provider credentials.
    $db->exec("UPDATE economy_configuration_data SET client_id='fixture',client_secret_encrypted='fixture',merchant_serial_number='fixture',subscription_key='fixture' WHERE id='v'");
    checkPayment(paymentHttp('toggle_payment_gateway.php',['id'=>'v','is_active'=>true])['success'],'Economy activates authoritative configuration');
    checkPayment(in_array('vipps',array_column(paymentHttp('get_payment_methods.php')['payment_methods'],'method_type'),true),'New activation immediately offered');
    checkPayment(paymentHttp('toggle_payment_gateway.php',['id'=>'v','is_active'=>false])['success'],'Economy deactivates authoritative configuration');
    checkPayment(!paymentHttp('create_member.php',['first_name'=>'Stale','last_name'=>'Form','membership_plan_id'=>'priced','payment_status'=>'paid','paymentMethod'=>'Vipps'])['success'],'Open form cannot save a now deactivated selection');
    $income=['event_id'=>'signup','description'=>'Fixture','amount'=>10,'date'=>'2026-10-08'];
    checkPayment(!paymentHttp('add_event_income.php',$income)['success'],'Income has no manual or cash fallback');
    checkPayment(paymentHttp('add_event_income.php',$income+['payment_method'=>'cash'])['success'],'Income records chosen active method');
    checkPayment(!paymentHttp('add_event_finance.php',['event_id'=>'signup','type'=>'income','item_name'=>'Fixture','amount'=>10,'payment_method'=>'paypal'])['success'],'Other income endpoint rejects foreign gateway');
    // Inject a ledger failure. The member and new customer must both roll back.
    $db->exec("CREATE TRIGGER fail_payment BEFORE INSERT ON unified_ledger FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'");
    $customers=(int)$db->query('SELECT COUNT(*) FROM tenant_customers')->fetchColumn();
    $members=(int)$db->query('SELECT COUNT(*) FROM membership_members')->fetchColumn();
    checkPayment(!paymentHttp('create_member.php',['first_name'=>'Rollback','last_name'=>'Test','membership_plan_id'=>'priced','payment_status'=>'paid','paymentMethod'=>'cash'])['success'],'Ledger failure reported');
    checkPayment((int)$db->query('SELECT COUNT(*) FROM tenant_customers')->fetchColumn()===$customers && (int)$db->query('SELECT COUNT(*) FROM membership_members')->fetchColumn()===$members,'Atomic rollback on ledger failure');
    echo "Active payment policy and real HTTP/MySQL tests passed.\n";
} finally {proc_terminate($worker);proc_close($worker);}
