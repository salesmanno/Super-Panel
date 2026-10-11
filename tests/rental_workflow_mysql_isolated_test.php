<?php
declare(strict_types=1);
$root=$argv[1]??''; $temp=$argv[2]??'';
if (!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.') || !file_exists($temp.'/mysql.sock')) {
    throw new RuntimeException('Only the disposable MySQL harness is allowed');
}
function workflowAssert(bool $ok,string $message):void {
    if (!$ok) throw new RuntimeException($message);
}
require_once $root.'/includes/rental_schedule_editing.php';
$db=new PDO('mysql:unix_socket='.$temp.'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE rental_workflow CHARACTER SET utf8mb4');$db->exec('USE rental_workflow');
$schema=[
    "tenant_companies(id CHAR(36) PRIMARY KEY,name VARCHAR(255),is_active INT,logo_url TEXT,contact_phone TEXT,address_street TEXT,address_zip TEXT,address_city TEXT,org_number TEXT)",
    "tenant_staff(id CHAR(36),company_id CHAR(36),first_name TEXT,last_name TEXT)",
    "tenant_admins(id CHAR(36),company_id CHAR(36),tenant_staff_id CHAR(36),username TEXT,role VARCHAR(30),is_active INT)",
    "system_modules(id CHAR(36),module_key VARCHAR(50),is_active INT)",
    "tenant_admin_modules(tenant_admin_id CHAR(36),module_id CHAR(36))",
    "rental_objects(id CHAR(36) PRIMARY KEY,company_id CHAR(36),object_name TEXT,category VARCHAR(50),rental_text TEXT,rental_price DECIMAL(12,2),weekend_price DECIMAL(12,2),saturday_price DECIMAL(12,2),sunday_price DECIMAL(12,2),is_active INT DEFAULT 1,is_available INT DEFAULT 1,created_by CHAR(36),market_price DECIMAL(12,2),serial_number TEXT,description TEXT,deposit_amount DECIMAL(12,2),condition_notes TEXT,image_url TEXT,images TEXT,rental_periods TEXT,output_product_id CHAR(36))",
    "rental_venues(object_id CHAR(36) PRIMARY KEY,cleaning_fee DECIMAL(12,2),capacity_seated INT)",
    "rental_tools(object_id CHAR(36) PRIMARY KEY,brand TEXT)",
    "rental_contracts(id CHAR(36),company_id CHAR(36),rental_object_id CHAR(36),status VARCHAR(30),start_date DATE,end_date DATE)",
    "tenant_customers(id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name TEXT,last_name TEXT,email TEXT,phone TEXT,address_street TEXT,address_zip TEXT,address_city TEXT,date_of_birth DATE,customer_code TEXT,customer_type TEXT,status TEXT,source TEXT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    "rental_schedule(id CHAR(36) PRIMARY KEY,company_id CHAR(36),rental_object_id CHAR(36),customer_id CHAR(36),customer_name TEXT,customer_email TEXT,customer_phone TEXT,start_date DATE,start_time TIME,end_date DATE,end_time TIME,logistics TEXT,rental_fee DECIMAL(12,2),pickup_time TIME,status VARCHAR(30),approved INT DEFAULT 0,notes TEXT,created_by CHAR(36),created_at DATETIME DEFAULT CURRENT_TIMESTAMP,cleaning INT DEFAULT 0,price_adjustment_type VARCHAR(30),price_adjustment_cents INT DEFAULT 0,price_adjustment_explanation TEXT)",
    "dynamic_form_fields(id CHAR(36) PRIMARY KEY,company_id CHAR(36),entity_type VARCHAR(50),entity_id CHAR(36),field_name VARCHAR(100),field_label VARCHAR(255),field_type VARCHAR(50),is_required INT,is_visible INT,sort_order INT,default_value TEXT,options TEXT,validation_rules TEXT,updated_at DATETIME)",
    "rental_request_field_values(id CHAR(36),company_id CHAR(36),rental_object_id CHAR(36),rental_request_id CHAR(36),field_key VARCHAR(100),field_value TEXT)",
    "tenant_polling_tracker(id CHAR(36) PRIMARY KEY,company_id CHAR(36),module_source VARCHAR(50),action_type VARCHAR(50),polling_source VARCHAR(100),polling_type VARCHAR(50),polling_status VARCHAR(20),polling_interval_seconds INT DEFAULT 300,items_processed_last_poll INT DEFAULT 0,metadata TEXT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,last_successful_poll DATETIME,UNIQUE KEY unique_company_module_source(company_id,module_source,polling_source))",
    "audit_logs(id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),user_email TEXT,action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name TEXT,old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50))",
    "email_configs(company_id CHAR(36),email TEXT,display_name TEXT,automail_rental_confirm INT,is_active INT)",
    "email_templates(id CHAR(36),company_id CHAR(36),template_key VARCHAR(100),subject_template TEXT,body_template TEXT,is_active INT)",
    "email_queue(id CHAR(36) PRIMARY KEY,company_id CHAR(36),recipient_email TEXT,from_email TEXT,subject TEXT,body TEXT,template_id CHAR(36),module_source VARCHAR(50),source_id CHAR(36),status VARCHAR(30),priority INT,scheduled_at DATETIME,created_at DATETIME,updated_at DATETIME)",
    "tenant_website_general(company_id CHAR(36),logo_url TEXT,header_bg_color TEXT)",
    "economy_bank_accounts(company_id CHAR(36),account_number TEXT,is_active INT,bank_name TEXT)",
    "economy_configuration_data(id VARCHAR(36),company_id VARCHAR(36),gateway_type VARCHAR(30),config_name TEXT,is_active INT,test_mode INT,client_id TEXT,client_secret_encrypted TEXT,merchant_serial_number TEXT,subscription_key TEXT,public_key TEXT,private_key_encrypted TEXT)",
    "unified_ledger(id VARCHAR(36) PRIMARY KEY,order_id VARCHAR(100) UNIQUE,company_id VARCHAR(36),module_source VARCHAR(30),item_type VARCHAR(30),source_id VARCHAR(36),item_name TEXT,quantity INT,selling_price DECIMAL(12,2),cost_price DECIMAL(12,2),customer_id VARCHAR(36),payment_method VARCHAR(50),payment_status VARCHAR(30),status VARCHAR(30),transaction_date DATETIME,transaction_type VARCHAR(30),category TEXT,metadata JSON,rental_schedule_id VARCHAR(36),UNIQUE KEY rental_payment(company_id,rental_schedule_id))",
];
foreach($schema as $ddl)$db->exec('CREATE TABLE '.$ddl.' ENGINE=InnoDB');
$db->exec('ALTER TABLE rental_schedule ADD return_time TIME, ADD key_box_code VARCHAR(6), ADD checkin_email_queued_at DATETIME, ADD checkin_email_sent_at DATETIME');
$db->exec('ALTER TABLE rental_venues ADD access_code VARCHAR(6)');
$db->exec('ALTER TABLE rental_objects ADD weekday_prices JSON');
$db->exec('ALTER TABLE tenant_companies ADD cash_payment INT DEFAULT 0');
$db->exec("ALTER TABLE rental_schedule ADD payment_status VARCHAR(30) DEFAULT 'unpaid', ADD payment_method VARCHAR(50), ADD paid_at DATETIME, ADD cancelled_at DATETIME, ADD updated_at DATETIME");
$db->exec("INSERT INTO tenant_companies(id,name,is_active) VALUES('a','Bedrift A',1),('b','Bedrift B',1)");
$db->exec("INSERT INTO tenant_staff VALUES('pa','a','Ansatt','A'),('pb','b','Ansatt','B')");
$db->exec("INSERT INTO tenant_admins VALUES('aa','a','pa','ansatt-a','staff',1),('ab','b','pb','ansatt-b','staff',1),('none','a','pa','uten-modul','staff',1)");
$db->exec("INSERT INTO system_modules VALUES('rental','rental',1),('events','events',1)");
$db->exec("INSERT INTO tenant_admin_modules VALUES('aa','rental'),('ab','rental')");
$db->exec("INSERT INTO rental_objects(id,company_id,object_name,category,rental_price,created_by,market_price)
    VALUES('oa','a','Sal <img src=x>','venue',100,'aa',9000),('ob','b','Sal B','venue',100,'ab',NULL)");
$db->exec("INSERT INTO rental_venues(object_id,cleaning_fee,capacity_seated,access_code) VALUES('oa',20,50,'1234'),('ob',20,25,'9876')");
$db->exec("INSERT INTO email_configs VALUES('a','sender@example.invalid','Bedrift A',1,1),('b','sender@example.invalid','Bedrift B',0,1)");
$db->exec("INSERT INTO email_templates VALUES('ta','a','rental_confirmation','Beslutning {{status}}','Hei {{customer_name}}: {{status}}',1)");
mkdir($temp.'/app/api',0700,true);mkdir($temp.'/app/config',0700,true);
symlink($root.'/includes',$temp.'/app/includes');
foreach(['create_rental_request','get_rental_schedule','update_rental_schedule','check_refresh_flag',
    'set_polling_complete','update_rental_object','create_rental_object',
    'get_rental_objects','get_entity_form_fields','update_rental_schedule_lifecycle'] as $api) {
    copy($root.'/api/'.$api.'.php',$temp.'/app/api/'.$api.'.php');
}
$dsn='mysql:unix_socket='.$temp.'/mysql.sock;dbname=rental_workflow;charset=utf8mb4';
file_put_contents($temp.'/app/config/database.php','<?php function getDBConnection(){return new PDO('
    .var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}');
ini_set('session.save_path',$temp.'/sessions');
foreach(['a'=>'aa','b'=>'ab','none'=>'none','public'=>null] as $session=>$admin) {
    session_id('workflow-'.$session);session_start();
    $_SESSION=['company_id'=>$session==='b'?'b':'a','rental_lifecycle_csrf'=>'decision-token',
        'notification_csrf'=>'notification-token','entity_form_csrf'=>'form-token','rental_request_token'=>'public-token'];
    if($admin)$_SESSION['tenant_admin_id']=$admin;
    session_write_close();
}
$listener=stream_socket_server('tcp://127.0.0.1:0',$errno,$err);
$address=stream_socket_get_name($listener,false);fclose($listener);
$process=proc_open([PHP_BINARY,'-d','session.save_path='.$temp.'/sessions','-S',$address,'-t',$temp.'/app'],
    [0=>['file','/dev/null','r'],1=>['file',$temp.'/http.log','a'],2=>['file',$temp.'/http.log','a']],$pipes);
if(!is_resource($process))throw new RuntimeException('Fixture server failed');
try {
    $ready=false;
    for($i=0;$i<100;$i++){$socket=@stream_socket_client('tcp://'.$address,$errno,$err,.1);if($socket){fclose($socket);$ready=true;break;}usleep(20000);}
    workflowAssert($ready,'Server ready');
    $call=static function(string $api,string $session,?array $data=null,string $query='')use($address):array{
        $curl=curl_init('http://'.$address.'/api/'.$api.'.php'.($query!==''?'?'.$query:''));
        $options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','Origin: http://'.$address,'Cookie: PHPSESSID=workflow-'.$session]];
        if($data!==null){$options[CURLOPT_POST]=true;$options[CURLOPT_POSTFIELDS]=json_encode($data,JSON_THROW_ON_ERROR);}
        curl_setopt_array($curl,$options);$body=curl_exec($curl);$http=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
        $result=json_decode((string)$body,true);
        workflowAssert(is_array($result),'JSON response for '.$api.': '.$body);
        return [$http,$result];
    };
    $payload=['csrf_token'=>'public-token','submission_id'=>'first','object_id'=>'oa',
        'fields'=>['first_name'=>'John <img src=x>','last_name'=>'Kunde','email'=>'john@example.invalid'],
        'start_date'=>'2031-05-04','end_date'=>'2031-05-05','end_time'=>'18:00','logistics'=>'pick_up'];
    [$http,$created]=$call('create_rental_request','public',$payload);
    workflowAssert($http===200&&$created['success'],'Public request and notification saved');
    $id=$created['request_id'];
    workflowAssert((int)$db->query('SELECT COUNT(*) FROM tenant_polling_tracker')->fetchColumn()===1,'One event');
    [$http,$retry]=$call('create_rental_request','public',$payload);
    workflowAssert($http===200&&$retry['request_id']===$id&&(int)$db->query('SELECT COUNT(*) FROM rental_schedule')->fetchColumn()===1,'Retry has same request, no duplicate event or customer');
    $bad=$payload;$bad['submission_id']='invalid';$bad['fields']['email']='not-email';
    workflowAssert($call('create_rental_request','public',$bad)[0]===400,'Invalid request rejected');
    workflowAssert((int)$db->query('SELECT COUNT(*) FROM tenant_polling_tracker')->fetchColumn()===1,'Invalid request cannot notify');
    [$http,$polls]=$call('check_refresh_flag','a');
    workflowAssert($http===200&&count($polls['polls'])===1&&$polls['poll']['metadata']['resource_id']===$id,'Authorized concrete event');
    workflowAssert($polls['poll']['metadata']['customer_name']==='John <img src=x> Kunde','Stored name snapshot');
    workflowAssert(!$call('check_refresh_flag','b')[1]['hasPending']&&!$call('check_refresh_flag','none')[1]['hasPending'],'Company and module boundaries');
    $ack=['poll_id'=>$id,'csrf_token'=>$polls['csrf_token']];
    workflowAssert($call('set_polling_complete','a',['poll_id'=>$id])[0]===403,'Ack requires CSRF');
    workflowAssert($call('set_polling_complete','b',$ack)[0]===404&&$call('set_polling_complete','none',$ack)[0]===403,'Unauthorized acknowledgements denied');
    $db->exec("INSERT INTO tenant_polling_tracker(id,company_id,module_source,action_type,polling_source,polling_type,polling_status,metadata)
        VALUES('foreign','b','rental','rental_booking','fixture','event','pending','{}'),
        ('unassigned','a','events','ticket_purchase','fixture','event','pending','{}'),
        ('older','a','rental','rental_booking','legacy','batch','pending','{\"access_code\":\"secret-fixture\",\"customer_name\":\"Legacy\"}')");
    $polls=$call('check_refresh_flag','a')[1];
    workflowAssert(count($polls['polls'])===2&&!isset($polls['polls'][0]['metadata']['access_code'])&&!isset($polls['polls'][1]['metadata']['access_code']),'Multiple events and metadata allowlist');
    workflowAssert($call('set_polling_complete','a',$ack)[0]===200&&$call('set_polling_complete','a',$ack)[0]===200,'Acknowledgement idempotent');
    $db->prepare("UPDATE rental_schedule SET pickup_time='15:30:00', return_time='18:45:00', end_time='12:00:00' WHERE id=?")->execute([$id]);
    $list=$call('get_rental_schedule','a')[1];
    workflowAssert($list['success']&&$list['decision_email_enabled']&&count($list['requests'])===1,'Decision context and queue policy');
    $version=$list['requests'][0]['decision_version'];
    $decision=['id'=>$id,'status'=>'approved','csrf_token'=>'decision-token','expected_version'=>$version];
    workflowAssert($call('update_rental_schedule','a',['id'=>$id,'status'=>'approved'])[0]===403,'Decision requires CSRF');
    workflowAssert($call('update_rental_schedule','none',$decision)[0]===403,'Decision requires module grant');
    workflowAssert($call('get_rental_schedule','none')[0]===403,'Request read requires module grant');
    workflowAssert($call('update_rental_schedule','b',$decision)[0]===404,'Foreign request blocked');
    workflowAssert($call('update_rental_schedule','a',$decision+['start_date'=>'2031-05-06'])[0]===400,'Decision cannot implicitly edit');
    $save=['id'=>$id,'csrf_token'=>'decision-token','expected_version'=>$version,'end_date'=>'2031-05-06'];
    [$http,$saved]=$call('update_rental_schedule','a',$save);
    workflowAssert($http===200&&$saved['success']&&$saved['status']==='pending'&&!$saved['email_queued']&&$saved['rental_fee']==300,'Separate save remains pending, server amount, no email');
    workflowAssert($call('update_rental_schedule','a',$decision)[0]===409,'Stale decision blocked');
    [$http,$saved]=$call('update_rental_schedule','a',[
        'id'=>$id,'csrf_token'=>'decision-token','expected_version'=>$saved['request']['decision_version'],
        'return_time'=>'19:10','price_adjustment_type'=>'discount','price_adjustment_amount'=>50,
        'price_adjustment_explanation'=>'Avtalt rabatt']);
    workflowAssert($http===200 && $saved['success'] && $saved['rental_fee']==250
        && $saved['request']['return_time']==='19:10:00' && !$saved['email_queued'],'Return-time edit and price adjustment save independently of approval');
    [$http,$saved]=$call('update_rental_schedule','a',[
        'id'=>$id,'csrf_token'=>'decision-token','expected_version'=>$saved['request']['decision_version'],
        'end_date'=>'2031-05-07']);
    workflowAssert($http===200 && $saved['success'] && $saved['rental_fee']==350
        && $saved['request']['return_time']==='19:10:00','Date change recalculates base fee while retaining discount and return time');
    $decision['expected_version']=$saved['request']['decision_version'];
    [$http,$approved]=$call('update_rental_schedule','a',$decision);
    workflowAssert($http===200&&$approved['success']&&$approved['email_queued'],'Actual decision queues one email');
    $stored=$db->query("SELECT pickup_time,return_time,end_time,key_box_code FROM rental_schedule WHERE id=".$db->quote($id))->fetch(PDO::FETCH_ASSOC);
    workflowAssert($stored['pickup_time']==='15:30:00' && $stored['return_time']==='19:10:00'
        && $stored['end_time']==='19:10:00','Approval retains edited pickup and explicit return, not legacy noon');
    workflowAssert(preg_match('/^\d{4}$/D',$stored['key_box_code']) && $stored['key_box_code']!=='1234',
        'Approval assigns numeric code of venue length without copying venue code');
    workflowAssert($db->query("SELECT access_code FROM rental_venues WHERE object_id='oa'")->fetchColumn()==='1234',
        'Approval never changes physical venue code');
    workflowAssert($call('update_rental_schedule','a',$decision)[0]===409&&(int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn()===1,'Repeated decision never duplicates email');
    $history=$call('get_rental_schedule','a',null,'request_id='.$id)[1]['requests'];
    workflowAssert(count($history)===1&&$history[0]['status']==='approved','Notification link can retrieve processed request');
    workflowAssert($call('get_rental_schedule','b',null,'request_id='.$id)[1]['requests']===[],'Resource lookup remains company scoped');
    $payload['submission_id']='second';$payload['start_date']='2031-06-01';$payload['end_date']='2031-06-02';
    $second=$call('create_rental_request','public',$payload)[1]['request_id'];
    $sources=$db->query("SELECT polling_source FROM tenant_polling_tracker WHERE company_id='a' AND module_source='rental' AND polling_type='event'")->fetchAll(PDO::FETCH_COLUMN);
    workflowAssert(count($sources)===2&&count(array_unique($sources))===2,
        'Two distinct requests in the same company have independent event source keys under the real unique index');
    workflowAssert($call('create_rental_request','public',$payload)[1]['request_id']===$second
        &&(int)$db->query("SELECT COUNT(*) FROM tenant_polling_tracker WHERE company_id='a' AND module_source='rental' AND polling_type='event'")->fetchColumn()===2,
        'Retry of second request remains idempotent under the real unique index');
    $q=$db->prepare('SELECT * FROM rental_schedule WHERE id=?');$q->execute([$second]);
    $reject=['id'=>$second,'csrf_token'=>'decision-token','expected_version'=>rentalDecisionVersion($q->fetch(PDO::FETCH_ASSOC)),'status'=>'rejected'];
    workflowAssert($call('update_rental_schedule','a',$reject)[1]['email_queued'],'Rejection uses same confirmed flow');
    workflowAssert($call('update_rental_schedule','a',$reject)[0]===409&&(int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn()===2,'Repeated rejection never duplicates email');
    $edit=['id'=>'oa','csrf_token'=>'form-token','category'=>'venue','object_name'=>'Nytt navn','market_price'=>null];
    workflowAssert($call('update_rental_object','a',$edit)[1]['success'],'Basic object edit');
    workflowAssert((float)$db->query("SELECT market_price FROM rental_objects WHERE id='oa'")->fetchColumn()===9000.0,'Hidden price preserved even for legacy client');
    $typeChange=$call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','category'=>'tool','object_name'=>'Should not save','type_data'=>['brand'=>'Brand']]);
    workflowAssert($typeChange[0]===400 && !$typeChange[1]['success'],'Object type change is rejected');
    workflowAssert($db->query("SELECT category FROM rental_objects WHERE id='oa'")->fetchColumn()==='venue'
        && $db->query("SELECT object_name FROM rental_objects WHERE id='oa'")->fetchColumn()==='Nytt navn','Rejected type change saves nothing');
    workflowAssert((int)$db->query("SELECT COUNT(*) FROM rental_tools WHERE object_id='oa'")->fetchColumn()===0
        && (int)$db->query("SELECT COUNT(*) FROM rental_venues WHERE object_id='oa'")->fetchColumn()===1,'Rejected type change preserves typed records');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','rental_price'=>150,'weekend_price'=>200,'saturday_price'=>null,'sunday_price'=>null,'type_data'=>['access_code'=>'2468']])[1]['success'],'Rates and physical code remain editable with category omitted');
    $editedObject=$db->query("SELECT rental_price,weekend_price,saturday_price,sunday_price FROM rental_objects WHERE id='oa'")->fetch(PDO::FETCH_ASSOC);
    workflowAssert((float)$editedObject['rental_price']===150.0 && (float)$editedObject['weekend_price']===200.0
        && $editedObject['saturday_price']===null && $editedObject['sunday_price']===null,'Unified weekend prices persisted');
    workflowAssert($db->query("SELECT access_code FROM rental_venues WHERE object_id='oa'")->fetchColumn()==='2468','Physical venue code updated explicitly');
    workflowAssert($db->query("SELECT key_box_code FROM rental_schedule WHERE id=".$db->quote($id))->fetchColumn()===$stored['key_box_code'],'Object code edit never changes assigned reservation code');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','weekend_price'=>null,'saturday_price'=>250,'sunday_price'=>175,'is_available'=>0])[1]['success'],'Separate weekend rates and hiding remain editable');
    $editedObject=$db->query("SELECT weekend_price,saturday_price,sunday_price,is_available FROM rental_objects WHERE id='oa'")->fetch(PDO::FETCH_ASSOC);
    workflowAssert($editedObject['weekend_price']===null && (float)$editedObject['saturday_price']===250.0
        && (float)$editedObject['sunday_price']===175.0 && (int)$editedObject['is_available']===0,'Separate weekend prices and hidden flag persisted');
    workflowAssert($call('update_rental_object','b',['id'=>'oa','csrf_token'=>'form-token','is_available'=>1])[0]===404,'Other company cannot unhide object');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','is_available'=>1])[1]['success'],'Hidden object can be shown again');
    $payment=['id'=>$id,'action'=>'mark_paid','csrf_token'=>'decision-token','payment_method'=>'stripe'];
    workflowAssert($call('update_rental_schedule_lifecycle','a',array_diff_key($payment,['csrf_token'=>true]))[0]===403,'Payment requires CSRF');
    workflowAssert($call('update_rental_schedule_lifecycle','b',$payment)[0]===404,'Payment enforces company boundary');
    workflowAssert($call('update_rental_schedule_lifecycle','none',$payment)[0]===403,'Payment requires Rental module');
    workflowAssert($call('update_rental_schedule_lifecycle','a',$payment)[0]===400,'Inactive payment method rejected');
    workflowAssert((int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===0
        && $db->query("SELECT payment_status FROM rental_schedule WHERE id=".$db->quote($id))->fetchColumn()==='unpaid','Rejected payment changes no status or ledger');
    $db->exec("INSERT INTO economy_configuration_data(id,company_id,gateway_type,config_name,is_active,test_mode)
        VALUES('card-a','a','stripe','Kort',1,0),('bank-a','a','bank','Bankoverføring',1,0),('card-b','b','stripe','Kort',1,0)");
    workflowAssert($call('update_rental_schedule_lifecycle','a',$payment)[1]['success'],'Activated card method accepted');
    $paymentRow=$db->query("SELECT payment_status,payment_method,status FROM rental_schedule WHERE id=".$db->quote($id))->fetch(PDO::FETCH_ASSOC);
    $ledgerRow=$db->query("SELECT * FROM unified_ledger WHERE rental_schedule_id=".$db->quote($id))->fetch(PDO::FETCH_ASSOC);
    workflowAssert($paymentRow['payment_status']==='paid' && $paymentRow['payment_method']==='stripe'
        && $paymentRow['status']==='approved','Payment updates paid status and preserves approved rental');
    workflowAssert($ledgerRow['module_source']==='rental' && $ledgerRow['source_id']===$id
        && $ledgerRow['payment_method']==='stripe' && $ledgerRow['payment_status']==='completed'
        && $ledgerRow['status']==='active' && (float)$ledgerRow['selling_price']===350.0,'Card payment creates correctly linked ledger entry for final adjusted rental fee');
    $db->exec("UPDATE economy_configuration_data SET is_active=0 WHERE id='card-a'");
    workflowAssert($call('update_rental_schedule_lifecycle','a',$payment)[1]['success']
        && (int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===1,'Retry retains historical payment after method deactivation without duplicate revenue');
    workflowAssert($call('update_rental_schedule_lifecycle','a',['id'=>$second,'action'=>'mark_paid','csrf_token'=>'decision-token','payment_method'=>'bank'])[0]===409,'Rejected request cannot be marked paid');
    $db->prepare("INSERT INTO rental_schedule(id,company_id,rental_object_id,status,approved,start_date,end_date,rental_fee)
        VALUES('bank-booking','a','oa','approved',1,'2031-08-01','2031-08-02',250)")->execute();
    workflowAssert($call('update_rental_schedule_lifecycle','a',['id'=>'bank-booking','action'=>'mark_paid','csrf_token'=>'decision-token','payment_method'=>'bank'])[1]['success'],'Activated bank method accepted');
    workflowAssert($db->query("SELECT payment_method FROM unified_ledger WHERE rental_schedule_id='bank-booking'")->fetchColumn()==='bank','Bank method canonical in ledger');
    workflowAssert($call('update_rental_schedule_lifecycle','a',['id'=>'bank-booking','action'=>'cancel','csrf_token'=>'decision-token'])[1]['success'],'Paid booking can be cancelled');
    workflowAssert($db->query("SELECT status FROM rental_schedule WHERE id='bank-booking'")->fetchColumn()==='cancelled'
        && $db->query("SELECT status FROM unified_ledger WHERE rental_schedule_id='bank-booking'")->fetchColumn()==='cancelled','Cancellation preserves linked payment history with cancelled status');
    $db->exec("DELETE FROM unified_ledger WHERE rental_schedule_id='bank-booking'");
    $db->exec("DELETE FROM rental_schedule WHERE id='bank-booking'");
    // Fail the notification insert: all request/customer/answers writes must roll back.
    $db->exec('RENAME TABLE tenant_polling_tracker TO unavailable_polling');
    $payload['submission_id']='notification-outage';$payload['start_date']='2031-07-01';$payload['end_date']='2031-07-02';
    workflowAssert($call('create_rental_request','public',$payload)[0]===500,'Notification outage fails explicitly');
    workflowAssert((int)$db->query('SELECT COUNT(*) FROM rental_schedule')->fetchColumn()===2,'Notification outage rolls back request');
    $db->exec('RENAME TABLE unavailable_polling TO tenant_polling_tracker');
    // Broken field JSON cannot interfere with unrelated base object
    // operations. Test only in this disposable database, never the app DB.
    $db->exec('ALTER TABLE rental_objects ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
    $brokenRule = '"pattern": "^[^@]+@[^@]+$", "maxLength": 254}';
    $db->prepare("INSERT INTO dynamic_form_fields
        (id,company_id,entity_type,entity_id,field_name,field_label,field_type,is_required,is_visible,sort_order,validation_rules)
        VALUES ('broken-email','a','rental_object','oa','email','E-post','email',0,1,0,?)")->execute([$brokenRule]);
    workflowAssert($call('get_rental_objects','a')[1]['success'],'Invalid field JSON does not break object list');
    workflowAssert($call('get_rental_objects','a',null,'id=oa')[1]['success'],'Invalid field JSON does not break object details');
    workflowAssert($call('get_entity_form_fields','a',null,'entity_type=rental_object&entity_id=oa&csrf_only=1')[1]['csrf_token']==='form-token','Token issuance ignores broken field rules');
    workflowAssert($call('get_entity_form_fields','b',null,'entity_type=rental_object&entity_id=oa&csrf_only=1')[0]===404,'Token-only request enforces tenant boundary');
    workflowAssert($call('get_entity_form_fields','none',null,'entity_type=rental_object&entity_id=oa&csrf_only=1')[0]===403,'Token-only request enforces module access');
    workflowAssert($call('get_rental_objects','b',null,'id=oa')[0]===404,'Object details enforce tenant boundary');
    workflowAssert($call('get_rental_objects','none')[0]===403,'Object list enforces module access');
    workflowAssert($call('update_rental_object','none',['id'=>'oa','csrf_token'=>'form-token','object_name'=>'Unauthorized'])[0]===403,'Basic edit enforces module access');
    workflowAssert($call('update_rental_object','b',['id'=>'oa','csrf_token'=>'form-token','object_name'=>'Unauthorized'])[0]===404,'Basic edit enforces tenant boundary');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','object_name'=>'Changed independently','csrf_token'=>'form-token'])[1]['success'],'Basic edit ignores broken field rules');
    $fieldFailure=$call('get_entity_form_fields','a',null,'entity_type=rental_object&entity_id=oa');
    workflowAssert($fieldFailure[0]===400 && str_contains($fieldFailure[1]['message'],'validation_rules') && str_contains($fieldFailure[1]['message'],'email'),'Field editor identifies malformed JSON explicitly');
    workflowAssert($db->query("SELECT validation_rules FROM dynamic_form_fields WHERE id='broken-email'")->fetchColumn()===$brokenRule,'Base edits preserve malformed rule unchanged');
    $weekdayPrices=['monday'=>500,'tuesday'=>500,'wednesday'=>600,'thursday'=>600,'friday'=>800];
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','weekday_prices'=>$weekdayPrices])[1]['success'],'Weekday prices save on own object');
    $map=json_decode($db->query("SELECT weekday_prices FROM rental_objects WHERE id='oa'")->fetchColumn(),true);
    workflowAssert($map['monday']==500 && $map['friday']==800,'All weekday prices persist');
    workflowAssert($call('get_rental_objects','a',null,'id=oa')[1]['object']['weekday_prices']!==null,'Details reload weekday JSON');
    $scheduleMap=$call('get_rental_schedule','a',null,'request_id='.$id)[1]['requests'][0]['object_weekday_prices'];
    workflowAssert(json_decode($scheduleMap,true)==$map,'Request list exposes rates to browser');
    workflowAssert($call('update_rental_object','b',['id'=>'oa','csrf_token'=>'form-token','weekday_prices'=>null])[0]===404,'Cannot alter another tenant weekday prices');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','weekday_prices'=>['monday'=>500]])[0]===400,'Incomplete weekday map rejected');
    workflowAssert(json_decode($db->query("SELECT weekday_prices FROM rental_objects WHERE id='oa'")->fetchColumn(),true)==$map,'Invalid save rolls back unchanged map');
    $newFormFields=array_values(array_filter(entityFormDefaults('rental_object'),static fn($field)=>in_array($field['field_name'],['first_name','last_name','email'],true)));
    foreach($newFormFields as &$newField) { $newField['is_visible']=true; }
    unset($newField);
    [$http,$newObject]=$call('create_rental_object','a',['csrf_token'=>'form-token','object_name'=>'Ukedagslokale','category'=>'venue','rental_price'=>100,'weekday_prices'=>$weekdayPrices,'form_fields'=>$newFormFields]);
    workflowAssert($http===200&&$newObject['success'],'Creation saves weekday prices');
    $newId=$newObject['object_id'];
    $newRequest=$payload;
    $newRequest['submission_id']='weekday-request';$newRequest['object_id']=$newId;
    $newRequest['start_date']='2031-07-07';$newRequest['end_date']='2031-07-11';
    [$http,$weekdayRequest]=$call('create_rental_request','public',$newRequest);
    if ($http !== 200) {
        preg_match_all('/create_rental_request validation error: ([^\r\n]+)/', file_get_contents($temp.'/http.log'), $fixtureErrors);
        $weekdayRequest['fixture_error'] = end($fixtureErrors[1]);
    }
    workflowAssert($http===200&&$weekdayRequest['success'],'Public request accepts weekday pricing: '.json_encode($weekdayRequest));
    $weekdayRequestId=$weekdayRequest['request_id'];
    workflowAssert((float)$db->query('SELECT rental_fee FROM rental_schedule WHERE id='.$db->quote($weekdayRequestId))->fetchColumn()===3000.0,'Public fee sums Mon-Fri rates');
    $requestVersion=$call('get_rental_schedule','a',null,'request_id='.$weekdayRequestId)[1]['requests'][0]['decision_version'];
    [$http,$recalculated]=$call('update_rental_schedule','a',['id'=>$weekdayRequestId,'csrf_token'=>'decision-token','expected_version'=>$requestVersion,'end_date'=>'2031-07-12']);
    workflowAssert($http===200&&$recalculated['success']&&(float)$recalculated['rental_fee']===3100.0,'Date edit prices added Saturday using fallback');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','weekday_prices'=>null])[1]['success'],'Flat mode clears weekday map');
    workflowAssert($db->query("SELECT weekday_prices FROM rental_objects WHERE id='oa'")->fetchColumn()===null,'SQL NULL restored for flat mode');
    $db->exec('ALTER TABLE rental_objects DROP COLUMN weekday_prices');
    workflowAssert($call('get_rental_schedule','a')[1]['success'],'Legacy server without column still reads flat rates');
    workflowAssert($call('update_rental_object','a',['id'=>'oa','csrf_token'=>'form-token','weekday_prices'=>$weekdayPrices])[0]===400,'Missing column rejects active map instead of silently losing prices');
    echo "Isolated rental workflow, decisions, notifications and permissions passed\n";
} finally {proc_terminate($process);proc_close($process);}
