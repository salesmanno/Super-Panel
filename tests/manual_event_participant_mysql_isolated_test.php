<?php
declare(strict_types=1);

$root=$argv[1];
$temp=$argv[2];
if (!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.') || !file_exists($temp.'/mysql.sock')) {
    throw new RuntimeException('Disposable harness required.');
}

$base='mysql:unix_socket='.$temp.'/mysql.sock';
$setup=new PDO($base,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE manual_event_test CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
$dsn=$base.';dbname=manual_event_test;charset=utf8mb4';
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

foreach ([
    "CREATE TABLE tenant_companies(id CHAR(36) PRIMARY KEY,name VARCHAR(255),is_active TINYINT)",
    "CREATE TABLE tenant_admins(id CHAR(36) PRIMARY KEY,company_id CHAR(36),tenant_staff_id CHAR(36),username VARCHAR(100),role VARCHAR(30),is_active TINYINT)",
    "CREATE TABLE tenant_staff(id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name VARCHAR(100),last_name VARCHAR(100))",
    "CREATE TABLE system_modules(id CHAR(36) PRIMARY KEY,module_key VARCHAR(50),is_active TINYINT)",
    "CREATE TABLE tenant_admin_modules(tenant_admin_id CHAR(36),module_id CHAR(36))",
    "CREATE TABLE events_eventdata(id CHAR(36) PRIMARY KEY,company_id CHAR(36),title VARCHAR(255),registration_type VARCHAR(30),start_date DATE DEFAULT '2020-01-01',ticket_price DECIMAL(12,2) DEFAULT 0)",
    "CREATE TABLE dynamic_form_fields(id CHAR(36) PRIMARY KEY,company_id CHAR(36),entity_type VARCHAR(50),entity_id CHAR(36),field_name VARCHAR(100),field_label VARCHAR(255),field_type VARCHAR(50),is_required INT DEFAULT 0,is_visible INT DEFAULT 1,default_value TEXT,options TEXT,validation_rules TEXT,sort_order INT DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,UNIQUE(company_id,entity_type,entity_id,field_name))",
    "CREATE TABLE events_checkin(id CHAR(36) PRIMARY KEY,event_id CHAR(36),membership_plan_id CHAR(36) NULL,ticket_price DECIMAL(10,2) NOT NULL,payment_status VARCHAR(30),payment_method VARCHAR(50) NULL,checked_in TINYINT NOT NULL,check_in_time DATETIME NULL,checked_in_by CHAR(36) NULL,barcode VARCHAR(100),notes TEXT,created_at DATETIME,updated_at DATETIME)",
    "CREATE TABLE unified_ledger(id CHAR(36) PRIMARY KEY,company_id CHAR(36),order_id VARCHAR(100))",
    "CREATE TABLE email_queue(id CHAR(36) PRIMARY KEY,company_id CHAR(36),status VARCHAR(30))",
    "CREATE TABLE audit_logs(id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),user_email VARCHAR(255),action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name VARCHAR(255),old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50))",
    "INSERT INTO tenant_companies VALUES('company-a','Firma A',1),('company-b','Firma B',1)",
    "INSERT INTO tenant_admins VALUES('admin-a','company-a',NULL,'admin-a','admin',1)",
    "INSERT INTO system_modules VALUES('events-module','events',1)",
    "INSERT INTO tenant_admin_modules VALUES('admin-a','events-module')",
    "INSERT INTO events_eventdata(id,company_id,title,registration_type) VALUES('event-a','company-a','Lokalt arrangement','signup'),('event-b','company-b','Annet firma','signup')",
    "INSERT INTO dynamic_form_fields(id,company_id,entity_type,entity_id,field_name,field_label,field_type,is_required,is_visible,options,validation_rules,sort_order) VALUES('configured','company-a','event','event-a','__configured','','text',0,1,NULL,'{}',0),('topic','company-a','event','event-a','topic','Interesse','text',1,1,NULL,'{\"max_length\":100}',1),('age','company-a','event','event-a','age','Alder','number',1,1,NULL,'{\"min\":0,\"max\":130}',2)",
] as $sql) {
    $db->exec($sql);
}

$dir=$temp.'/manual-event-http';
mkdir($dir);
$source=file_get_contents($root.'/api/create_manual_event_participant.php');
$bootstrap='function getDBConnection(){return new PDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}';
$source=str_replace("require_once __DIR__.'/../config/database.php';",$bootstrap,$source);
$source=preg_replace_callback("~__DIR__\\s*\\.\\s*'/\\.\\./includes/([^']+)'~",static fn($m)=>var_export($root.'/includes/'.$m[1],true),$source);
$sessionSetup='session_save_path('.var_export($temp.'/sessions',true).');session_start();'
    .'$_SESSION["tenant_admin_id"]="admin-a";$_SESSION["company_id"]="company-a";$_SESSION["entity_form_csrf"]="manual-token";';
$source=str_replace('session_start();',$sessionSetup,$source);
file_put_contents($dir.'/create_manual_event_participant.php',$source);

$sock=stream_socket_server('tcp://127.0.0.1:0');
$port=(int)substr(strrchr(stream_socket_get_name($sock,false),':'),1);
fclose($sock);
$worker=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$dir],
    [0=>['file','/dev/null','r'],1=>['file',$temp.'/manual-event.log','a'],2=>['file',$temp.'/manual-event.log','a']],$pipes);

function manualEventAssert(bool $condition,string $message):void {
    if (!$condition) throw new RuntimeException($message);
}

function manualEventHttp(array $input):array {
    global $port;
    $csrfHeader=$input['test_csrf_header']??'manual-token';
    unset($input['test_csrf_header']);
    $body=file_get_contents('http://127.0.0.1:'.$port.'/create_manual_event_participant.php',false,
        stream_context_create(['http'=>[
            'method'=>'POST',
            'header'=>"Content-Type: application/json\r\nX-CSRF-Token: {$csrfHeader}\r\n",
            'content'=>json_encode($input,JSON_THROW_ON_ERROR),
            'ignore_errors'=>true,
        ]]));
    try {
        return json_decode($body,true,512,JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        throw new RuntimeException('Manual participant API did not return JSON: '.substr((string)$body,0,1200),0,$error);
    }
}

try {
    for ($i=0;$i<60;$i++) {
        if ($ready=@fsockopen('127.0.0.1',$port)) {fclose($ready);break;}
        usleep(100000);
    }

    $eventDay=(new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
    $db->prepare("UPDATE events_eventdata SET start_date=? WHERE id='event-a'")->execute([$eventDay]);
    $baseInput=['event_id'=>'event-a','checked_in'=>false,'csrf_token'=>'manual-token'];
    manualEventAssert(!manualEventHttp($baseInput+['attendee_first_name'=>'Ada'])['success'],
        'Missing surname is rejected before any participant is inserted');
    manualEventAssert(!manualEventHttp($baseInput+['attendee_last_name'=>'Nord'])['success'],
        'Missing first name is rejected before any participant is inserted');
    manualEventAssert((int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===0,
        'Rejected incomplete rows do not become participants');

    $badToken=array_replace($baseInput,['attendee_first_name'=>'Ada','attendee_last_name'=>'Nord','test_csrf_header'=>'wrong']);
    manualEventAssert(!manualEventHttp($badToken)['success'],'Invalid CSRF token is rejected');
    $foreign=array_replace($baseInput,['event_id'=>'event-b','attendee_first_name'=>'Ada','attendee_last_name'=>'Nord']);
    manualEventAssert(!manualEventHttp($foreign)['success'],'A different company event cannot receive the participant');

    $invalidField=$baseInput+[
        'attendee_first_name'=>'Ada','attendee_last_name'=>'Nord','optional_fields'=>['age'=>'not-a-number'],
    ];
    manualEventAssert(!manualEventHttp($invalidField)['success'],'Configured field formats are validated');
    manualEventAssert((int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===0,
        'Invalid rows leave no participant, payment, or audit record');

    $pending=manualEventHttp($baseInput+[
        'attendee_first_name'=>'Ada','attendee_last_name'=>'Nord','attendee_email'=>'','attendee_phone'=>'12345678',
        // The configured topic and age fields are marked required; the manual
        // admin flow intentionally allows them to be left empty.
    ]);
    manualEventAssert($pending['success']===true,'Manual entry saves with required names and optional configured fields omitted');
    $pendingRow=$db->query("SELECT * FROM events_checkin WHERE id=".$db->quote($pending['checkin_id']))->fetch();
    manualEventAssert($pendingRow && (int)$pendingRow['checked_in']===0 && $pendingRow['check_in_time']===null
        && $pendingRow['checked_in_by']===null,'Pending row remains in the waiting list');
    manualEventAssert($pendingRow['payment_status']==='pending' && $pendingRow['payment_method']===null
        && (float)$pendingRow['ticket_price']===0.0,'Manual row is not treated as paid or as revenue');
    $snapshot=json_decode($pendingRow['notes'],true,512,JSON_THROW_ON_ERROR);
    manualEventAssert(($snapshot['guest']['first_name']??'')==='Ada' && ($snapshot['guest']['last_name']??'')==='Nord'
        && ($snapshot['guest']['phone']??'')==='12345678','Core participant values are preserved in the submission snapshot');

    $checked=manualEventHttp([
        'event_id'=>'event-a','checked_in'=>true,'attendee_first_name'=>'Liv','attendee_last_name'=>'Berg',
        'attendee_email'=>'liv@example.test','attendee_phone'=>'','optional_fields'=>['topic'=>'Dans','age'=>'24'],
        'csrf_token'=>'manual-token',
    ]);
    manualEventAssert($checked['success']===true,'Checked-in manual entry saves');
    $checkedRow=$db->query("SELECT * FROM events_checkin WHERE id=".$db->quote($checked['checkin_id']))->fetch();
    manualEventAssert($checkedRow && (int)$checkedRow['checked_in']===1 && $checkedRow['check_in_time']!==null
        && $checkedRow['checked_in_by']==='admin-a','Checked-in row records its status, timestamp, and administrator');
    manualEventAssert($checkedRow['payment_status']==='pending' && (float)$checkedRow['ticket_price']===0.0,
        'Check-in does not mark payment as received');
    $checkedSnapshot=json_decode($checkedRow['notes'],true,512,JSON_THROW_ON_ERROR);
    $fields=array_column($checkedSnapshot['form_fields'],'value','field_name');
    manualEventAssert(($fields['topic']??'')==='Dans' && ($fields['age']??'')==='24',
        'Configured dynamic field answers are retained');
    manualEventAssert((int)$db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===0
        && (int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn()===0,
        'Manual entry neither creates revenue nor queues email');
    manualEventAssert((int)$db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===2,
        'Each saved participant is audited');

    $db->exec("UPDATE events_eventdata SET ticket_price=475.50 WHERE id='event-a'");
    $priced=manualEventHttp($baseInput+['attendee_first_name'=>'Rett','attendee_last_name'=>'Pris','ticket_price'=>0]);
    manualEventAssert($priced['success'] && (float)$priced['ticket_price']===475.50,
        'API returns the actual server-selected price, not zero or a client override');
    $pricedRow=$db->query('SELECT * FROM events_checkin WHERE id='.$db->quote($priced['checkin_id']))->fetch();
    manualEventAssert((float)$pricedRow['ticket_price']===475.50 && $pricedRow['payment_status']==='pending' && !$pricedRow['checked_in'],
        'Nonzero price remains payable and not checked in');
    $pricedAudit=json_decode($db->query('SELECT new_values FROM audit_logs WHERE resource_id='.$db->quote($priced['checkin_id']))->fetchColumn(),true);
    manualEventAssert((float)$pricedAudit['ticket_price']===475.50,'Audit retains the same nonzero price');
    $bypass=manualEventHttp(array_replace($baseInput,['attendee_first_name'=>'Uten','attendee_last_name'=>'Betaling','checked_in'=>true]));
    manualEventAssert(!$bypass['success'],'A paid ticket cannot bypass payment using manual creation');
    $db->exec('DELETE FROM events_checkin WHERE id='.$db->quote($priced['checkin_id']));
    $db->exec('DELETE FROM audit_logs WHERE resource_id='.$db->quote($priced['checkin_id']));

    $tomorrow=(new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
    $db->prepare("UPDATE events_eventdata SET start_date=? WHERE id='event-a'")->execute([$tomorrow]);
    $beforeStart=manualEventHttp($baseInput+['attendee_first_name'=>'Før','attendee_last_name'=>'Tid']);
    manualEventAssert(!$beforeStart['success'],'Manual API blocks changes before the event starts');
    manualEventAssert((int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===2
        && (int)$db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===2,
        'A pre-event attempt creates no participant or audit row');
    $db->prepare("UPDATE events_eventdata SET start_date=? WHERE id='event-a'")->execute([$eventDay]);

    $db->exec("DELETE FROM tenant_admin_modules WHERE tenant_admin_id='admin-a'");
    $denied=manualEventHttp($baseInput+['attendee_first_name'=>'Per','attendee_last_name'=>'Test']);
    manualEventAssert(!$denied['success'],'Users without Events module access cannot create manual participants');
    manualEventAssert((int)$db->query('SELECT COUNT(*) FROM events_checkin')->fetchColumn()===2,
        'Denied user leaves existing participants unchanged');
    echo "Manual event participant MySQL/API tests passed.\n";
} finally {
    proc_terminate($worker);
    proc_close($worker);
}
