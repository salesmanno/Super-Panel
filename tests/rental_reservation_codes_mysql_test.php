<?php
declare(strict_types=1);
$root = $argv[1] ?? ''; $temp = $argv[2] ?? '';
if (!str_starts_with($temp, sys_get_temp_dir().'/rental-unavailable-mysql.') || !file_exists($temp.'/mysql.sock')) {
    throw new RuntimeException('Run only via isolated socket-only MySQL harness.');
}
require_once $root.'/includes/rental_reservation_codes.php';
require_once $root.'/includes/rental_checkin_email.php';
function codeCheck(bool $ok, string $label): void { if (!$ok) throw new RuntimeException($label); }
$dsn = 'mysql:unix_socket='.$temp.'/mysql.sock;dbname=reservation_codes';
$db = new PDO('mysql:unix_socket='.$temp.'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE reservation_codes'); $db->exec('USE reservation_codes');
$schema = [
    'tenant_companies(id VARCHAR(36) PRIMARY KEY,name TEXT,is_active INT,contact_phone TEXT,address_street TEXT,address_zip TEXT,address_city TEXT,org_number TEXT)',
    'tenant_staff(id VARCHAR(36),company_id VARCHAR(36),first_name TEXT,last_name TEXT)',
    'tenant_admins(id VARCHAR(36),company_id VARCHAR(36),tenant_staff_id VARCHAR(36),username TEXT,role TEXT,is_active INT)',
    'system_modules(id VARCHAR(36),module_key VARCHAR(50),is_active INT)',
    'tenant_admin_modules(tenant_admin_id VARCHAR(36),module_id VARCHAR(36))',
    'rental_objects(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),category VARCHAR(30),object_name TEXT,rental_text TEXT)',
    'rental_venues(object_id VARCHAR(36) PRIMARY KEY,access_code VARCHAR(6),cleaning_fee DECIMAL(12,2))',
    "rental_schedule(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),rental_object_id VARCHAR(36),approved INT,status VARCHAR(30),
        start_date DATE,end_date DATE,start_time TIME,pickup_time TIME,end_time TIME,key_box_code VARCHAR(20),updated_at DATETIME,
        checkin_email_queued_at DATETIME,checkin_email_sent_at DATETIME,customer_id VARCHAR(36),customer_email TEXT,customer_name TEXT,
        rental_fee DECIMAL(12,2),cleaning INT DEFAULT 0,KEY ix_dates(company_id,rental_object_id,start_date))",
    'audit_logs(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),user_id VARCHAR(36),user_type VARCHAR(20),user_email TEXT,action VARCHAR(50),resource_type VARCHAR(100),resource_id VARCHAR(36),resource_name TEXT,old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50))',
    'tenant_customers(id VARCHAR(36),company_id VARCHAR(36),email TEXT,first_name TEXT,last_name TEXT)',
    'tenant_website_general(company_id VARCHAR(36),logo_url TEXT,header_bg_color TEXT)',
    'email_configs(company_id VARCHAR(36),email TEXT,display_name TEXT,is_active INT,rental_confirm INT)',
    'email_templates(id VARCHAR(36),company_id VARCHAR(36),template_key VARCHAR(50),name TEXT,subject_template TEXT,body_template TEXT,is_active INT)',
    'email_queue(id VARCHAR(36),company_id VARCHAR(36),recipient_email TEXT,from_email TEXT,subject TEXT,body TEXT,template_id VARCHAR(36),module_source VARCHAR(50),source_id VARCHAR(36),idempotency_key VARCHAR(200) UNIQUE,status VARCHAR(30),priority INT,scheduled_at DATETIME,created_at DATETIME,updated_at DATETIME)',
];
foreach ($schema as $sql) $db->exec('CREATE TABLE '.$sql.' ENGINE=InnoDB');
$db->exec('ALTER TABLE rental_objects ADD rental_price DECIMAL(12,2), ADD weekend_price DECIMAL(12,2), ADD saturday_price DECIMAL(12,2), ADD sunday_price DECIMAL(12,2)');
$db->exec('ALTER TABLE rental_schedule ADD customer_phone TEXT, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
$db->exec('ALTER TABLE tenant_customers ADD phone TEXT, ADD date_of_birth DATE');
$db->exec('ALTER TABLE email_configs ADD automail_rental_confirm INT DEFAULT 0');
$db->exec("INSERT INTO tenant_companies(id,name,is_active) VALUES('a','A',1),('b','B',1)");
$db->exec("INSERT INTO tenant_admins VALUES('aa','a',NULL,'Test A','admin',1),('a2','a',NULL,'Test A2','admin',1),('bb','b',NULL,'Test B','admin',1),('none','a',NULL,'No module','admin',1)");
$db->exec("INSERT INTO system_modules VALUES('r','rental',1)");
$db->exec("INSERT INTO tenant_admin_modules VALUES('aa','r'),('a2','r'),('bb','r')");
$db->exec("INSERT INTO rental_objects(id,company_id,category,object_name) VALUES('v','a','venue','Sal'),('w','a','venue','Sal 2'),('foreign','b','venue','Sal B')");
$db->exec("INSERT INTO rental_venues VALUES('v','0123',0),('w',NULL,0),('foreign','9876',0)");
$today = rentalCodeToday();
$tomorrow = (new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
$later = (new DateTimeImmutable($today))->modify('+35 days')->format('Y-m-d');
$insert = $db->prepare("INSERT INTO rental_schedule(id,company_id,rental_object_id,approved,status,start_date,end_date,start_time,pickup_time,key_box_code,customer_email)
    VALUES(?,?,?,1,'approved',?,?,'08:00:00','08:00:00',?,'fixture@example.invalid')");
foreach ([['first','a','v',$tomorrow,null],['second','a','v',$later,null],['current','a','v',$today,null],
    ['missing','a','w',$tomorrow,null],['other','b','foreign',$tomorrow,null]] as [$id,$co,$obj,$day,$code]) {
    $insert->execute([$id,$co,$obj,$day,$day,$code]);
}
mkdir($temp.'/app'); mkdir($temp.'/app/api'); mkdir($temp.'/app/config');
symlink($root.'/includes', $temp.'/app/includes');
copy($root.'/api/rental_reservation_code.php', $temp.'/app/api/rental_reservation_code.php');
copy($root.'/api/get_rental_schedule.php', $temp.'/app/api/get_rental_schedule.php');
file_put_contents($temp.'/app/config/database.php', '<?php function getDBConnection(){return new PDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}');
session_save_path($temp.'/sessions');
foreach (['a'=>'aa','a2'=>'a2','b'=>'bb','none'=>'none'] as $session=>$admin) {
    session_id('codes-'.$session); session_start();
    $_SESSION=['tenant_admin_id'=>$admin,'company_id'=>$session==='b'?'b':'a','rental_lifecycle_csrf'=>'test-token'];
    session_write_close();
}
$socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
$address=stream_socket_get_name($socket,false); fclose($socket);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$temp.'/sessions','-S',$address,'-t',$temp.'/app'],
    [0=>['file','/dev/null','r'],1=>['file',$temp.'/http.log','a'],2=>['file',$temp.'/http.log','a']],$pipes);
try {
    for($i=0;$i<100;$i++){ $s=@stream_socket_client('tcp://'.$address,$err,$msg,.1); if($s){fclose($s);break;} usleep(20000); }
    $call = static function(array $data, string $session='a', string $csrf='test-token') use ($address): array {
        $context=stream_context_create(['http'=>['method'=>'POST','ignore_errors'=>true,
            'header'=>"Content-Type: application/json\r\nCookie: PHPSESSID=codes-$session\r\n",
            'content'=>json_encode($data+['csrf_token'=>$csrf])]]);
        $body=file_get_contents('http://'.$address.'/api/rental_reservation_code.php',false,$context);
        preg_match('/\s(\d{3})\s/',$http_response_header[0],$m);
        return [(int)$m[1],json_decode($body,true,512,JSON_THROW_ON_ERROR)];
    };
    $prepare = static fn($id,$session='a') => $call(['id'=>$id,'action'=>'prepare'],$session);
    $get = static function(string $query='approved=1') use($address): array {
        $body=file_get_contents('http://'.$address.'/api/get_rental_schedule.php?'.$query,false,
            stream_context_create(['http'=>['header'=>"Cookie: PHPSESSID=codes-a\r\n"]]));
        return json_decode($body,true,512,JSON_THROW_ON_ERROR);
    };
    $overview=$get();
    codeCheck($overview['success'] && $overview['today']===$today,'Overview API returns canonical Oslo date');
    $overviewRows=array_column($overview['requests'],null,'id');
    codeCheck($overviewRows['first']['can_prepare_code'] && !$overviewRows['second']['can_prepare_code']
        && !$overviewRows['current']['can_prepare_code'] && $overviewRows['current']['checkin_code_missing'],
        'Overview flags only next future reservation and missing-code status');
    codeCheck(!$get('request_id=second')['requests'][0]['can_prepare_code'],'Filtering cannot make a later rental eligible');
    codeCheck($prepare('other')[0]===404,'Cross-company prepare denied');
    codeCheck($prepare('first','none')[0]===403,'Missing Rental permission denied');
    codeCheck($call(['id'=>'first','action'=>'prepare'],'a','wrong')[0]===403,'CSRF denied');
    codeCheck($prepare('current')[0]===409 && $prepare('second')[0]===409,'Today and later reservation denied');
    codeCheck($prepare('missing')[0]===409,'Detail never invents a missing code or length');
    codeCheck($prepare('first')[0]===409,'Missing code cannot be generated from detail');
    $db->exec("UPDATE rental_schedule SET key_box_code='??' WHERE id='second'");
    [$http,$ensured]=$call(['action'=>'ensure_upcoming']);
    codeCheck($http===200 && $ensured['assigned_count']===2 && $ensured['unresolved_ids']===['missing'],
        'Overview assigns missing and repairs invalid future codes; reports unknown lengths');
    $firstCode=$db->query("SELECT key_box_code FROM rental_schedule WHERE id='first'")->fetchColumn();
    $secondCode=$db->query("SELECT key_box_code FROM rental_schedule WHERE id='second'")->fetchColumn();
    codeCheck(preg_match('/^\d{4}$/D',$firstCode) && preg_match('/^\d{4}$/D',$secondCode),
        'All future reservations get numeric codes with venue reference length');
    codeCheck($db->query("SELECT key_box_code FROM rental_schedule WHERE id='current'")->fetchColumn()===null,
        'Automatic assignment ignores today and older');
    codeCheck($db->query("SELECT key_box_code FROM rental_schedule WHERE id='other'")->fetchColumn()===null,
        'Automatic assignment cannot affect another tenant');
    codeCheck($call(['action'=>'ensure_upcoming'])[1]['assigned_count']===0,'Overview assignment is idempotent');
    codeCheck($call(['action'=>'ensure_upcoming'],'none')[0]===403
        && $call(['action'=>'ensure_upcoming'],'a','wrong')[0]===403,'Automatic assignment requires access and CSRF');
    [$http,$p]=$prepare('first');
    codeCheck($http===200 && preg_match('/^\d{4}$/D',$p['code']) && $p['code']!=='0123','Numeric proposal preserves reference length');
    codeCheck($db->query("SELECT key_box_code FROM rental_schedule WHERE id='first'")->fetchColumn()===$firstCode,'Opening never changes assigned code');
    [$http,$competitor]=$prepare('first','a2');
    $save=['id'=>'first','action'=>'save','token'=>$p['token'],'physical_changed'=>false];
    codeCheck($call($save)[0]===409,'Save without physical confirmation denied');
    codeCheck($db->query("SELECT access_code FROM rental_venues WHERE object_id='v'")->fetchColumn()==='0123','Unconfirmed action leaves venue unchanged');
    $save['physical_changed']=true;
    codeCheck($call($save)[0]===200 && $call($save)[0]===409,'Explicit physical confirmation works and replay denied');
    codeCheck($call(['id'=>'first','action'=>'save','token'=>$competitor['token'],'physical_changed'=>true],'a2')[0]===409,'Second administrator cannot overwrite');
    [$http,$assigned]=$prepare('first');
    codeCheck($assigned['assigned'] && $assigned['matches'] && $assigned['code']===$p['code'],'Reopen preserves assigned code; confirmed venue matches');
    codeCheck($call(['id'=>'first','action'=>'save','token'=>$assigned['token'],'physical_changed'=>true])[0]===200,'Physical confirmation succeeds');
    codeCheck($prepare('first')[1]['matches']===true,'No question once codes match');
    [$http,$stale]=$prepare('first');
    $db->exec("UPDATE rental_venues SET access_code='AB12' WHERE object_id='v'");
    codeCheck($call(['id'=>'first','action'=>'save','token'=>$stale['token'],'physical_changed'=>true])[0]===409,'Venue change invalidates confirmation');
    [$http,$stale]=$prepare('first');
    $db->exec("UPDATE rental_schedule SET status='cancelled' WHERE id='first'");
    codeCheck($call(['id'=>'first','action'=>'save','token'=>$stale['token'],'physical_changed'=>true])[0]===409,'Cancellation invalidates next rental');
    codeCheck($prepare('second')[0]===200,'Next advances after cancellation regardless of month');
    $db->exec("UPDATE rental_schedule SET key_box_code='00A1' WHERE id='missing'");
    [$http,$legacy]=$prepare('missing');
    codeCheck($http===200 && $legacy['code']==='00A1','Existing alphanumeric code works without venue reference');
    codeCheck($call(['id'=>'missing','action'=>'save','token'=>$legacy['token'],'physical_changed'=>true])[0]===200,'Existing code can set missing venue code');
    $audit=implode('', $db->query('SELECT CONCAT(old_values,new_values) FROM audit_logs')->fetchAll(PDO::FETCH_COLUMN));
    codeCheck(!str_contains($audit,$p['code']) && !str_contains($audit,'00A1'),'Audit excludes actual codes');

    // Cron: real queueing into isolated DB, never invokes the email transport.
    $db->exec("INSERT INTO email_configs(company_id,email,display_name,is_active,rental_confirm) VALUES('a','sender@example.invalid','Test',1,1)");
    $db->exec("INSERT INTO email_templates VALUES('tpl','a','rental_checkin','innsjekk utleie','Kode {{key_box_code}}','Uendret mal: {{key_box_code}}',1)");
    $insert->execute(['email-valid','a','v',$today,$today,'0012']);
    codeCheck(queueRentalCheckinEmails($db,$today,'a')===1,'One valid reservation queued; missing code skipped');
    codeCheck(queueRentalCheckinEmails($db,$today,'a')===0,'Repeat cron does not duplicate');
    codeCheck($db->query("SELECT body FROM email_queue WHERE source_id='email-valid'")->fetchColumn()==='Uendret mal: 0012','Same template uses assigned leading-zero code, not venue code');
    codeCheck($db->query("SELECT key_box_code FROM rental_schedule WHERE id='email-valid'")->fetchColumn()==='0012','Cron never overwrites assigned code');
    codeCheck($db->query("SELECT checkin_email_queued_at FROM rental_schedule WHERE id='current'")->fetchColumn()===null,'Missing code stays retryable');
    $db->exec("UPDATE email_queue SET status='failed' WHERE source_id='email-valid'");
    codeCheck($get('request_id=email-valid')['requests'][0]['checkin_queue_status']==='failed','Overview reports actual failed queue status');
    $db->exec("UPDATE rental_schedule SET key_box_code='AB34' WHERE id='current'");
    codeCheck(queueRentalCheckinEmails($db,$today,'a')===1,'Corrected missing code queues on retry');
    // Two independent cron processes both encounter a locked reservation after
    // their initial scan; they must re-read its code and queue exactly once.
    $insert->execute(['race-email','a','v',$today,$today,'0155']);
    file_put_contents($temp.'/cron-worker.php','<?php require '.var_export($root.'/includes/rental_checkin_email.php',true)
        .'; $db=new PDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'
        .'echo queueRentalCheckinEmails($db,'.var_export($today,true).',"a");');
    $db->beginTransaction();
    $db->query("SELECT id FROM rental_schedule WHERE id='race-email' FOR UPDATE")->fetch();
    $workers=[]; $workerPipes=[];
    for($i=0;$i<2;$i++) $workers[$i]=proc_open([PHP_BINARY,$temp.'/cron-worker.php'],
        [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$workerPipes[$i]);
    try {
        $deadline=microtime(true)+10; $waiting=0;
        do {
            $waiting=(int)$db->query("SELECT COUNT(*) FROM information_schema.PROCESSLIST
                WHERE INFO LIKE 'SELECT rental_object_id, key_box_code FROM rental_schedule%'")->fetchColumn();
            if($waiting>=2)break;
            usleep(20000);
        }while(microtime(true)<$deadline);
        codeCheck($waiting===2,'Both cron workers reached the row lock');
        $db->exec("UPDATE rental_schedule SET key_box_code='0166' WHERE id='race-email'");
        $db->commit();
        $counts=[];
        foreach($workers as $i=>$worker) {
            $counts[]=(int)stream_get_contents($workerPipes[$i][1]);
            $stderr=stream_get_contents($workerPipes[$i][2]);
            fclose($workerPipes[$i][1]);fclose($workerPipes[$i][2]);
            codeCheck(proc_close($worker)===0 && $stderr==='','Concurrent cron exits cleanly');
        }
        $workers=[];
        sort($counts);
        codeCheck($counts===[0,1],'Concurrent cron queues once');
        codeCheck($db->query("SELECT body FROM email_queue WHERE source_id='race-email'")->fetchColumn()==='Uendret mal: 0166',
            'Cron re-reads assigned code under lock, never its stale initial snapshot');
    }finally {
        if($db->inTransaction())$db->rollBack();
        foreach($workers as $worker)if(is_resource($worker)){proc_terminate($worker);proc_close($worker);}
    }
    codeCheck(rentalCodeToday(new DateTimeImmutable('2028-04-02T22:01:00Z'))==='2028-04-03','Oslo day boundary despite process timezone');
    foreach (['1','0123','AB1234'] as $reference) for($i=0;$i<50;$i++) {
        $code=rentalGenerateReservationCode($reference);
        codeCheck(ctype_digit($code) && strlen($code)===strlen($reference) && $code!==$reference,'Numeric generator exact length');
    }
    echo "Reservation codes: isolated HTTP authorization, proposals, stale administrators, physical confirmation, same-template cron and Oslo dates passed.\n";
} finally {
    proc_terminate($server); proc_close($server);
}
