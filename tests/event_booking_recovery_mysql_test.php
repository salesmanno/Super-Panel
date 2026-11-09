<?php
declare(strict_types=1);

[$script,$root,$tmp]=$argv;
if(!str_starts_with($tmp,'/tmp/event-booking-recovery.') || !is_file($tmp.'/mysql.pid'))throw new RuntimeException('Disposable harness required.');
require_once $root.'/scripts/lib/EventBookingRecovery.php';
require_once $root.'/includes/AuditedPDO.php';
$base='mysql:unix_socket='.$tmp.'/mysql.sock';
$setup=new PDO($base,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE recovery_test CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
$dsn=$base.';dbname=recovery_test;charset=utf8mb4';
$db=new AuditedPDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

final class RecoveryProvider extends SalesVipps {
    public int $captures=0;
    public int $captured=0;
    public string $outcome='ok';
    public int $amount=50000;
    public array $keys=[];
    public function __construct(public PDO $db) {}
    public function request(array $config,string $company,string $method,string $path,?array $body=null,?string $idempotency=null): array {
        if($this->db->inTransaction())throw new RuntimeException('HTTP under database transaction.');
        $ref=explode('/',trim($path,'/'))[0];
        if($method==='POST') {
            assertRecovery((int)$this->db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===1,'Participant durable before capture');
            assertRecovery($body===['modificationAmount'=>['currency'=>'NOK','value'=>50000]],'Exact authorized capture amount');
            assertRecovery($idempotency!==null,'Capture idempotency required');
            $this->captures++;$this->keys[]=$idempotency;
            if($this->outcome!=='unavailable')$this->captured=50000;
            if($this->outcome==='audit_failure')$this->db->exec('DROP TABLE audit_logs');
            if($this->outcome==='lost_response')throw new RuntimeException('Simulated network uncertainty');
            return ['code'=>$this->outcome==='unavailable'?503:201,'json_valid'=>true,'data'=>[]];
        }
        $aggregate=[];
        foreach(['authorizedAmount'=>$this->amount,'capturedAmount'=>$this->captured,'cancelledAmount'=>0,'refundedAmount'=>0] as $key=>$value){
            $aggregate[$key]=['currency'=>'NOK','value'=>$value];
        }
        return ['code'=>200,'json_valid'=>true,'data'=>['reference'=>$ref,'state'=>'AUTHORIZED',
            'amount'=>['currency'=>'NOK','value'=>$this->amount],'aggregate'=>$aggregate]];
    }
}
function assertRecovery(bool $condition,string $message):void {
    if(!$condition)throw new RuntimeException($message);
    echo "PASS: $message\n";
}
function rejects(callable $work,string $message):void {
    try{$work();}catch(Throwable $e){assertRecovery(true,$message);return;}
    throw new RuntimeException('Expected failure: '.$message);
}
function auditTable(PDO $db):void {
    $db->exec("CREATE TABLE audit_logs(id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),
        user_email VARCHAR(255),action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name VARCHAR(255),
        old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50))");
}
function fixture(AuditedPDO $db):RecoveryProvider {
    $db->withExplicitAudit(function()use($db) {
        foreach(['pending_bookings','events_eventdata','tenant_customers','membership_members','event_registrations',
            'events_checkin','unified_ledger','economy_configuration_data','economy_payment_gateways','tenant_companies','audit_logs'] as $t)$db->exec("DROP TABLE IF EXISTS `$t`");
        foreach([
            "tenant_companies(id VARCHAR(36) PRIMARY KEY,name TEXT)",
            "pending_bookings(id INT PRIMARY KEY,order_id VARCHAR(100),company_id VARCHAR(36),booking_type VARCHAR(20),booking_data LONGTEXT,status VARCHAR(20),created_at DATETIME,completed_at DATETIME)",
            "events_eventdata(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),title TEXT,max_attendees INT,is_active INT,is_member_exclusive INT,memberverification INT)",
            "tenant_customers(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36))",
            "membership_members(id VARCHAR(36),company_id VARCHAR(36),customer_id VARCHAR(36),membership_plan_id VARCHAR(36),status VARCHAR(20),cancellation_active INT,expiration_date DATE)",
            "event_registrations(id CHAR(36) PRIMARY KEY,event_id VARCHAR(36),company_id VARCHAR(36),customer_id VARCHAR(36),approval_status VARCHAR(20),approval_reason TEXT,approved_by VARCHAR(36),approved_at DATETIME,number_of_attendees INT,form_answers_json TEXT,created_at DATETIME,updated_at DATETIME)",
            "events_checkin(id CHAR(36) PRIMARY KEY,registration_id VARCHAR(36),event_id VARCHAR(36),customer_id VARCHAR(36),membership_plan_id VARCHAR(36),ticket_price DECIMAL(10,2),payment_status VARCHAR(20),payment_method VARCHAR(50),checked_in INT,barcode VARCHAR(255),payment_reference VARCHAR(100),payment_state VARCHAR(20),payment_checked_at DATETIME,created_at DATETIME,updated_at DATETIME)",
            "unified_ledger(id CHAR(36) PRIMARY KEY,order_id VARCHAR(100),company_id VARCHAR(36),module_source VARCHAR(50),item_type VARCHAR(50),source_id VARCHAR(36),item_name TEXT,quantity INT,selling_price DECIMAL(12,2),cost_price DECIMAL(12,2),customer_id VARCHAR(36),payment_method VARCHAR(50),payment_status VARCHAR(20),status VARCHAR(20),transaction_date DATETIME,transaction_type VARCHAR(20),category TEXT,metadata JSON)",
            "economy_configuration_data(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),test_mode INT,client_id TEXT,client_secret_encrypted TEXT,subscription_key TEXT,merchant_serial_number TEXT,is_active INT,gateway_type VARCHAR(20))",
            "economy_payment_gateways(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),configuration_id VARCHAR(36),is_active INT)"
        ] as $schema)$db->exec("CREATE TABLE $schema");
        auditTable($db);
        $db->exec("INSERT INTO tenant_companies VALUES('company','Synthetic company')");
        $db->exec("INSERT INTO events_eventdata VALUES('event','company','Synthetic event',50,1,1,1)");
        $db->exec("INSERT INTO tenant_customers VALUES('customer','company')");
        $db->exec("INSERT INTO membership_members VALUES('member','company','customer','plan','active',0,'2099-01-01')");
        $db->exec("INSERT INTO economy_configuration_data VALUES('config','company',0,'fixture','fixture','fixture','fixture',1,'vipps')");
        $db->exec("INSERT INTO economy_payment_gateways VALUES('gateway','company','config',1)");
        $data=['company_id'=>'company','event_id'=>'event','total_attendees'=>1,'total_price'=>500,'vipps_payment_started'=>1,
            'attendees'=>[['existing_customer_id'=>'customer','first_name'=>'Test','last_name'=>'Person','final_price'=>'500.00',
                'payment_method'=>'vipps','membership_plan_id'=>'plan','form_answers_json'=>'{"diet":"vegetarian"}']]];
        $q=$db->prepare("INSERT INTO pending_bookings VALUES(1,'EVT-1-aaaaaaaa','company','event',?,'pending',NOW(),NULL)");
        $q->execute([json_encode($data)]);
    });
    return new RecoveryProvider($db);
}
function service(AuditedPDO $db,RecoveryProvider $p,bool $apply=true):array {
    return (new EventBookingRecovery($db,$p))->run('company','EVT-1-aaaaaaaa','event',50000,$apply);
}
$p=fixture($db);$r=service($db,$p,false);
assertRecovery($r['mode']==='read_only' && (int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===0 && $p->captures===0,'Dry run changes nothing');
$r=service($db,$p);
assertRecovery($r['status']==='completed' && $p->captures===1,'Authorized payment captured once');
$row=$db->query('SELECT * FROM events_checkin')->fetch();
assertRecovery($row['payment_status']==='paid' && $row['payment_state']==='captured' && (int)$row['checked_in']===0
    && $row['ticket_price']==='500.00' && $row['membership_plan_id']==='plan' && strlen($row['barcode'])===13,'Correct ticket, membership and barcode; no admission');
assertRecovery($db->query('SELECT form_answers_json FROM event_registrations')->fetchColumn()==='{"diet":"vegetarian"}','Original form answers retained');
assertRecovery((int)$db->query("SELECT COUNT(*) FROM unified_ledger l JOIN events_checkin c ON l.source_id=c.id
    WHERE l.payment_status='completed' AND l.status='active'")->fetchColumn()===1,'Checkin can recognize completed ledger without duplicate income');
$auditCount=(int)$db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
service($db,$p);
assertRecovery($p->captures===1 && (int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===1
    && (int)$db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===$auditCount,'Rerun creates no payment, participant or audit duplicates');
$p=fixture($db);$p->captured=50000;service($db,$p);
assertRecovery($p->captures===0,'Already captured payment never charged again');
$p=fixture($db);$p->amount=60000;
rejects(fn()=>service($db,$p),'Amount mismatch blocks repair and charge');
assertRecovery((int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===0,'Mismatch leaves database untouched');
$p=fixture($db);$p->captured=25000;
rejects(fn()=>service($db,$p),'Partial capture requires manual reconciliation');
$p=fixture($db);$p->outcome='lost_response';service($db,$p);
assertRecovery($p->captures===1 && $db->query('SELECT payment_status FROM events_checkin')->fetchColumn()==='paid','Lost capture response resolved by GET');
$p=fixture($db);$p->outcome='unavailable';
rejects(fn()=>service($db,$p),'Unconfirmed capture reported as incomplete');
assertRecovery($db->query('SELECT payment_status FROM events_checkin')->fetchColumn()==='pending'
    && $db->query('SELECT payment_status FROM unified_ledger')->fetchColumn()==='pending','Unconfirmed charge preserves unpaid participant and ledger');
$p->outcome='ok';service($db,$p);
assertRecovery(count(array_unique($p->keys))===1 && (int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===1,'Recovery resumes with same idempotency key and participant');
$p=fixture($db);$db->withExplicitAudit(fn()=>$db->exec('DROP TABLE audit_logs'));
rejects(fn()=>service($db,$p),'Preparation audit failure aborts before charge');
assertRecovery($p->captures===0 && (int)$db->query('SELECT COUNT(*) FROM event_registrations')->fetchColumn()===0,'Preparation rolls back fully');
$p=fixture($db);$p->outcome='audit_failure';
rejects(fn()=>service($db,$p),'Settlement audit failure leaves recoverable state');
assertRecovery($p->captured===50000 && $db->query('SELECT payment_status FROM events_checkin')->fetchColumn()==='pending','Captured amount never falsely inferred from local status');
auditTable($db);$p->outcome='ok';service($db,$p);
assertRecovery($p->captures===1 && $db->query('SELECT payment_status FROM events_checkin')->fetchColumn()==='paid','Post-capture failure repaired without second charge');
$p=fixture($db);$db->withExplicitAudit(fn()=>$db->exec("INSERT INTO event_registrations(id,event_id,company_id,customer_id) VALUES('existing','event','company','customer')"));
rejects(fn()=>service($db,$p),'Existing participant registration blocks duplicate repair');
assertRecovery($p->captures===0,'Duplicate registration never charged');
$p=fixture($db);$db->withExplicitAudit(fn()=>$db->exec("UPDATE membership_members SET status='inactive'"));
rejects(fn()=>service($db,$p),'Invalid membership blocks charge');
echo "Event booking recovery isolated tests passed.\n";
