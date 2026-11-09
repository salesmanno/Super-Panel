<?php
declare(strict_types=1);
// Disposable MySQL only. Provider traffic is replaced, not merely intercepted.
[$script,$root,$temp]=$argv;
if (!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.') || !is_file($temp.'/mysql.pid')) throw new RuntimeException('Disposable fixture required.');
require_once $root.'/includes/EventCheckin.php';
$db=new PDO('mysql:unix_socket='.$temp.'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE event_checkin_fixture CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$db->exec('USE event_checkin_fixture');
foreach ([
    'tenant_companies'=>'id VARCHAR(36) PRIMARY KEY,name VARCHAR(100),cash_payment INT,is_active INT',
    'tenant_customers'=>'id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),first_name VARCHAR(100),last_name VARCHAR(100),email VARCHAR(255),phone VARCHAR(30)',
    'tenant_customer_contacts'=>'id VARCHAR(36),customer_id VARCHAR(36),contact_type VARCHAR(10),contact_value VARCHAR(255),is_primary INT,created_at DATETIME',
    'events_eventdata'=>'id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),title VARCHAR(255),start_date DATE,start_time TIME,end_time TIME,venue_name VARCHAR(255),ticket_price DECIMAL(12,2),registration_type VARCHAR(30),payment_methods_json JSON',
    'events_checkin'=>'id VARCHAR(36) PRIMARY KEY,event_id VARCHAR(36),registration_id VARCHAR(36),customer_id VARCHAR(36),ticket_price DECIMAL(12,2),payment_status VARCHAR(30),payment_method VARCHAR(30),checked_in INT DEFAULT 0,check_in_time DATETIME,checked_in_by VARCHAR(36),barcode VARCHAR(30),notes TEXT,updated_at DATETIME',
    'event_registrations'=>'id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),event_id VARCHAR(36)',
    'unified_ledger'=>'id VARCHAR(36) PRIMARY KEY,order_id VARCHAR(100),company_id VARCHAR(36),module_source VARCHAR(30),item_type VARCHAR(30),source_id VARCHAR(36),item_name VARCHAR(255),quantity DECIMAL(12,2),selling_price DECIMAL(12,2),cost_price DECIMAL(12,2),customer_id VARCHAR(36),payment_method VARCHAR(30),payment_status VARCHAR(30),status VARCHAR(30),transaction_date DATETIME,transaction_type VARCHAR(30),category VARCHAR(30),metadata JSON',
    'audit_logs'=>'id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),user_id VARCHAR(36),user_type VARCHAR(30),user_email VARCHAR(255),action VARCHAR(50),resource_type VARCHAR(100),resource_id VARCHAR(36),resource_name VARCHAR(255),old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50)',
    'economy_configuration_data'=>'id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),gateway_type VARCHAR(30),config_name VARCHAR(100),is_active INT,test_mode INT,client_id VARCHAR(255),client_secret_encrypted VARCHAR(255),subscription_key VARCHAR(255),merchant_serial_number VARCHAR(255),public_key VARCHAR(255),private_key_encrypted VARCHAR(255)',
    'economy_payment_gateways'=>'id VARCHAR(36),company_id VARCHAR(36),configuration_id VARCHAR(36),is_active INT',
    'economy_bank_accounts'=>'company_id VARCHAR(36),is_active INT,account_number VARCHAR(100)',
    'system_modules'=>'id VARCHAR(36),module_key VARCHAR(50),is_active INT',
    'tenant_company_modules'=>'company_id VARCHAR(36),module_id VARCHAR(36),is_active INT'
] as $table=>$columns) $db->exec("CREATE TABLE $table ($columns) ENGINE=InnoDB");
$db->exec("INSERT INTO tenant_companies VALUES ('a','Testbedrift',1,1),('b','Annen bedrift',1,1)");
$db->exec("INSERT INTO events_eventdata VALUES ('event','a','Testarrangement','2020-01-01','18:00','23:00','Testlokale',100,'signup','[\"cash\",\"vipps\"]'),('foreign','b','Annet','2020-01-01','18:00','23:00','Annet',100,'signup','[\"cash\"]')");
$db->exec("INSERT INTO economy_configuration_data(id,company_id,gateway_type,config_name,is_active,test_mode,client_id,client_secret_encrypted,subscription_key,merchant_serial_number) VALUES ('config','a','vipps','Vipps',1,0,'fixture-client','fixture-secret','fixture-subscription','fixture-msn')");
$db->exec("INSERT INTO economy_payment_gateways VALUES ('gateway','a','config',1)");
$db->exec("INSERT INTO system_modules VALUES ('economy','economy_module',1)");
$db->exec("INSERT INTO tenant_company_modules VALUES ('a','economy',1)");
for($i=1;$i<=18;$i++) {
    $id='p'.$i;
    $db->prepare("INSERT INTO tenant_customers VALUES (?,'a','Test',?,'test@example.invalid',?)")->execute([$id,$id,in_array($i,[9,12,14,15,16],true)?'99999999':null]);
    $db->prepare("INSERT INTO events_checkin(id,event_id,customer_id,ticket_price,payment_status,payment_method,barcode) VALUES (?,'event',?,100,'pending',NULL,?)")->execute([$id,$id,'barcode-'.$id]);
}
$db->exec("INSERT INTO events_checkin(id,event_id,ticket_price,payment_status) VALUES ('other','foreign',100,'pending')");
$_SESSION=[];
$actor=['id'=>'operator','name'=>'Testoperatør','role'=>'admin','type'=>'admin','company_name'=>'Testbedrift'];
final class CheckinProvider extends SalesVipps {
    public array $payments=[]; public array $calls=[]; public bool $uncertain=false; public bool $reject=false;
    public function request(array $configuration,string $company,string $method,string $path,?array $body=null,?string $idempotency=null): array {
        $this->calls[]=[$method,$path,$body,$idempotency];
        if ($method==='POST' && $path==='/epayment/v1/payments') {
            if($this->reject)return ['code'=>400,'data'=>[],'json_valid'=>true];
            $ref=$body['reference'];
            $this->payments[$ref]=['reference'=>$ref,'amount'=>$body['amount'],'state'=>'CREATED',
                'aggregate'=>['capturedAmount'=>['currency'=>'NOK','value'=>0]]];
            if($this->uncertain)throw new RuntimeException('Simulated network timeout');
            return ['code'=>201,'data'=>$this->payments[$ref],'json_valid'=>true];
        }
        preg_match('~/payments/([^/]+)~',$path,$match);$ref=$match[1];
        if(!isset($this->payments[$ref]))return ['code'=>404,'data'=>[],'json_valid'=>true];
        if(str_ends_with($path,'/capture'))$this->payments[$ref]['aggregate']['capturedAmount']=$body['modificationAmount'];
        if(str_ends_with($path,'/cancel'))$this->payments[$ref]['state']='ABORTED';
        return ['code'=>200,'data'=>$this->payments[$ref],'json_valid'=>true];
    }
}
$provider=new CheckinProvider();
$service=new EventCheckin($db,'a',$actor,$provider);
$checks=0;
function check(bool $condition,string $message): void {global $checks; if(!$condition)throw new RuntimeException($message);$checks++;}
function fails(callable $work,string $message): void {
    try{$work();}catch(Throwable $e){check(true,$message);return;}
    throw new RuntimeException($message);
}
function scalar(string $sql): mixed {global $db;return $db->query($sql)->fetchColumn();}
$load=$service->load('event');
check(count($load['people'])===18 && $load['methods']['cash'] && $load['methods']['vipps'],'Real tenant roster and active methods');
fails(fn()=>EventCheckin::ids(['p1','p1'],'p1'),'Duplicate people rejected');
fails(fn()=>EventCheckin::ids(['p1','p2','p3','p4','p5','p6'],'p1'),'Five total enforced');
fails(fn()=>$service->commit('event',['p1','other'],'p1','cash',true),'Cross-tenant group rejected');
fails(fn()=>$service->commit('event',['p1'],'p1','cash',false),'Cash confirmation required');
fails(fn()=>$service->commit('event',['p1'],'p1','vipps',true),'Manual Vipps confirmation rejected');
$service->commit('event',['p1','p2','p3','p4','p5'],'p1','cash',true);
check((int)scalar('SELECT COUNT(*) FROM events_checkin WHERE checked_in=1')===5,'Entire cash group checked in');
check((int)scalar("SELECT COUNT(*) FROM unified_ledger WHERE payment_status='completed'")===5,'Cash ledger per attendee');
check(scalar("SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.payer_checkin_id')) FROM unified_ledger LIMIT 1")==='p1','Original payer preserved');
fails(fn()=>$service->commit('event',['p1'],'p1','cash',true),'Duplicate checkin blocked');
$db->exec("UPDATE events_checkin SET ticket_price=0 WHERE id='p6'");
$service->commit('event',['p6'],'p6','',false);
check((int)scalar("SELECT COUNT(*) FROM unified_ledger WHERE source_id='p6'")===0,'Free admission has no payment row');
$db->exec("UPDATE events_checkin SET payment_status='paid',payment_method='vipps' WHERE id='p8'");
$before=count($provider->calls);
$service->commit('event',['p8'],'p8','previous',true);
check(count($provider->calls)===$before && (int)scalar("SELECT COUNT(*) FROM unified_ledger WHERE source_id='p8'")===1,'Previous payment repaired without charging');
$copy=$db->query("SELECT * FROM unified_ledger WHERE source_id='p1'")->fetch(PDO::FETCH_ASSOC);
unset($copy['id']);$copy['source_id']='p7';$copy['order_id']='prepaid';
$columns=array_keys($copy);
$db->prepare('INSERT INTO unified_ledger(id,'.implode(',',$columns).') VALUES (UUID(),'.implode(',',array_fill(0,count($copy),'?')).')')->execute(array_values($copy));
$service->commit('event',['p7'],'p7','',false);
check((int)scalar("SELECT COUNT(*) FROM unified_ledger WHERE source_id='p7'")===1,'Valid ledger never duplicated');
fails(fn()=>$service->start('event',['p11'],'p11'),'Missing phone rejected on server');
$start=$service->start('event',['p9','p10'],'p9');$ref=$start['reference'];
check($start['state']==='CREATED' && (int)scalar("SELECT SUM(checked_in) FROM events_checkin WHERE id IN ('p9','p10')")===0,'Provider request does not admit');
$sent=array_values(array_filter($provider->calls,fn($c)=>$c[1]==='/epayment/v1/payments'))[0][2];
check($sent['customer']['phoneNumber']==='4799999999' && $sent['amount']['value']===20000,'Server-owned payer phone and combined amount');
$resumed=array_column($service->load('event')['people'],null,'id');
check($resumed['p10']['pending_payment']['payer_id']==='p9','Group can resume with original payer after reopening');
fails(fn()=>$service->commit('event',['p10'],'p10','cash',true),'Unresolved payment blocks another method');
$provider->payments[$ref]['state']='AUTHORIZED';
$service->status($ref,false);
check((int)scalar("SELECT SUM(checked_in) FROM events_checkin WHERE id IN ('p9','p10')")===2,'Real provider capture precedes group admission');
$count=(int)scalar('SELECT COUNT(*) FROM unified_ledger');$service->status($ref,false);
check((int)scalar('SELECT COUNT(*) FROM unified_ledger')===$count,'Duplicate callback/status is idempotent');
$start=$service->start('event',['p12'],'p12');$service->status($start['reference'],true);
check((int)scalar("SELECT checked_in FROM events_checkin WHERE id='p12'")===0 && (int)scalar("SELECT COUNT(*) FROM unified_ledger WHERE source_id='p12' AND status='active'")===0,'Cancellation keeps participant unpaid and ledger inactive');
$provider->uncertain=true;$start=$service->start('event',['p14'],'p14');$provider->uncertain=false;
check($start['state']==='UNKNOWN','Timeout preserves uncertain attempt');
fails(fn()=>$service->start('event',['p14'],'p14'),'Ambiguous creation cannot be retried blindly');
$provider->payments[$start['reference']]['state']='AUTHORIZED';$service->status($start['reference'],false);
check((int)scalar("SELECT checked_in FROM events_checkin WHERE id='p14'")===1,'Uncertain request is safely resumed');
$provider->reject=true;$start=$service->start('event',['p15'],'p15');$provider->reject=false;
check($start['state']==='REJECTED' && (int)scalar("SELECT COUNT(*) FROM unified_ledger WHERE source_id='p15' AND status='active'")===0,'Definitive rejection safely releases participant');
$start=$service->start('event',['p16'],'p16');$ref=$start['reference'];
$db->exec("UPDATE unified_ledger SET metadata=JSON_SET(metadata,'$.event_checkin.expires_at',1) WHERE source_id='p16'");
$provider->payments[$ref]['state']='AUTHORIZED';$result=$service->status($ref,false);
check($result['state']==='ABORTED' && (int)scalar("SELECT checked_in FROM events_checkin WHERE id='p16'")===0,'Expired authorization is cancelled, not captured');
fails(fn()=>EventCheckin::remote(['code'=>200,'data'=>['reference'=>'wrong','amount'=>['currency'=>'NOK','value'=>100]]],['reference'=>'expected','amount_minor'=>100]),'Provider reference mismatch rejected');
$db->exec("UPDATE events_eventdata SET start_date='2099-01-01' WHERE id='event'");
fails(fn()=>$service->commit('event',['p17'],'p17','cash',true),'Server enforces event start date');
$db->exec("UPDATE events_eventdata SET start_date='2020-01-01' WHERE id='event'");
$db->exec("CREATE TRIGGER fail_ledger BEFORE INSERT ON unified_ledger FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fixture failure'");
fails(fn()=>$service->commit('event',['p17','p18'],'p17','cash',true),'Ledger failure aborts group');
check((int)scalar("SELECT SUM(checked_in) FROM events_checkin WHERE id IN ('p17','p18')")===0,'Group transaction rolls back all checkins');
$db->exec('DROP TRIGGER fail_ledger');
// Public registrations have one ledger entry per registration, not per checkin.
foreach([
    ['web-one','a','event'],['web-group','a','event'],['web-short','a','event'],
    ['web-foreign','b','foreign'],['web-wrong-event','a','event'],['web-expense','a','event'],
    ['web-pending','a','event'],['web-cancelled','a','event']
] as $r)$db->prepare('INSERT INTO event_registrations VALUES(?,?,?)')->execute($r);
foreach([
    ['w1','web-one'],['w2','web-group'],['w3','web-group'],['w4','web-short'],['w5','web-short'],
    ['w6','web-foreign'],['w7','web-wrong-event'],['w8','web-expense'],['w9','web-pending'],['w10','web-cancelled']
] as [$id,$reg]){
    $db->prepare("INSERT INTO tenant_customers VALUES(?,'a','Web',?,'web@example.invalid',NULL)")->execute([$id,$id]);
    $db->prepare("INSERT INTO events_checkin(id,event_id,registration_id,customer_id,ticket_price,payment_status,payment_method)
        VALUES(?,'event',?,?,100,'paid',NULL)")->execute([$id,$reg,$id]);
}
foreach([
    ['web-one',100,'a','event','completed','active','income'],
    ['web-group',200,'a','event','completed','active','income'],
    ['web-short',100,'a','event','completed','active','income'],
    ['web-foreign',100,'b','foreign','completed','active','income'],
    ['web-wrong-event',100,'a','foreign','completed','active','income'],
    ['web-expense',100,'a','event','completed','active','expense'],
    ['web-pending',100,'a','event','pending','active','income'],
    ['web-cancelled',100,'a','event','completed','cancelled','income']
] as [$reg,$amount,$company,$e,$pay,$status,$type]){
    $db->prepare("INSERT INTO unified_ledger(id,order_id,company_id,module_source,item_type,source_id,selling_price,
        payment_method,payment_status,status,transaction_type,metadata)
        VALUES(UUID(),?,?,'events','event_registration',?,?,'vipps',?,?,?,?)")
        ->execute(['EVT-'.$reg,$company,$reg,$amount,$pay,$status,$type,json_encode(['event_id'=>$e])]);
}
$web=array_column($service->load('event')['people'],null,'id');
check($web['w1']['has_valid_ledger'] && $web['w1']['payment_method']==='vipps','Website payment displays paid with original method');
check($web['w2']['has_valid_ledger'] && $web['w3']['has_valid_ledger'],'Fully funded shared registration covers its own tickets');
check(!$web['w4']['has_valid_ledger'] && !$web['w5']['has_valid_ledger'],'One paid ticket cannot fund an underpaid group');
foreach(['w6','w7','w8','w9','w10'] as $id)check(!$web[$id]['has_valid_ledger'],'Foreign, wrong-event, expense, pending or inactive ledger rejected: '.$id);
$ledgerCount=(int)scalar('SELECT COUNT(*) FROM unified_ledger');$calls=count($provider->calls);
$service->commit('event',['w1'],'w1','',false);
$service->commit('event',['w2'],'w2','',false);
$service->commit('event',['w3'],'w3','',false);
check((int)scalar('SELECT COUNT(*) FROM unified_ledger')===$ledgerCount && count($provider->calls)===$calls,
    'Web-paid admission creates neither new income nor a payment request');
check((int)scalar("SELECT SUM(checked_in) FROM events_checkin WHERE id IN ('w1','w2','w3')")===3,'Single and shared web-paid tickets can be admitted');
echo "Event checkin MySQL: $checks checks passed; no external calls.\n";
