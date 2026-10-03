<?php
declare(strict_types=1);
$root = $argv[1] ?? '';
$temp = $argv[2] ?? '';
if (!str_starts_with($temp, sys_get_temp_dir() . '/rental-unavailable-mysql.') || !file_exists($temp . '/mysql.sock')) {
    throw new RuntimeException('Use the disposable MySQL harness.');
}
require_once $root . '/includes/AuditedPDO.php';
function paymentAssert(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function endpointSql(string $root, string $file, string $prefix): string {
    $source = file_get_contents($root . '/api/' . $file);
    preg_match_all('/->prepare\("([^"]*)"\)/s', $source, $matches);
    foreach ($matches[1] as $sql) if (str_starts_with(ltrim($sql), $prefix)) return $sql;
    throw new RuntimeException('Actual endpoint SQL not found: ' . $file);
}
/** Only disposable endpoint copies replace provider transport, never application code. */
function stubProviderFunction(string $source, string $name, string $body): string {
    $tokens = token_get_all($source);
    $offset = 0;
    for ($i = 0; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;
        if (is_array($token) && $token[0] === T_FUNCTION) {
            $start = $offset;
            $j = $i + 1;
            while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (is_array($tokens[$j] ?? null) && $tokens[$j][1] === $name) {
                $position = $offset;
                $open = null;
                $depth = 0;
                for ($k = $i; $k < count($tokens); $k++) {
                    $part = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                    if ($tokens[$k] === '{') { $open ??= $position; $depth++; }
                    if ($tokens[$k] === '}' && --$depth === 0) {
                        return substr($source, 0, $open + 1) . $body . substr($source, $position);
                    }
                    $position += strlen($part);
                }
            }
        }
        $offset += strlen($text);
    }
    throw new RuntimeException('Provider stub not found: ' . $name);
}
$dsn = 'mysql:unix_socket=' . $temp . '/mysql.sock;dbname=payment_audit_fixture;charset=utf8mb4';
$setup = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE payment_audit_fixture');
$setup->exec('USE payment_audit_fixture');
$setup->exec("CREATE TABLE tenant_companies (id CHAR(36) PRIMARY KEY,name VARCHAR(255),is_active INT)");
$setup->exec("CREATE TABLE tenant_staff (id CHAR(36),company_id CHAR(36),first_name VARCHAR(255),last_name VARCHAR(255))");
$setup->exec("CREATE TABLE tenant_admins (id CHAR(36),company_id CHAR(36),tenant_staff_id CHAR(36),username VARCHAR(255),role VARCHAR(50),is_active INT)");
$setup->exec("CREATE TABLE tenant_customers (id CHAR(36),first_name VARCHAR(255),last_name VARCHAR(255))");
$setup->exec("CREATE TABLE events_eventdata (id CHAR(36) PRIMARY KEY,company_id CHAR(36),title VARCHAR(255))");
$setup->exec("CREATE TABLE event_registrations (id CHAR(36) PRIMARY KEY,event_id CHAR(36),company_id CHAR(36),customer_id CHAR(36),
    approval_status VARCHAR(30),approved_by CHAR(36),approved_at DATETIME,rejected_by CHAR(36),rejected_at DATETIME,
    approval_reason TEXT,rejection_reason TEXT,created_at DATETIME)");
$setup->exec("CREATE TABLE events_checkin (id CHAR(36) PRIMARY KEY,event_id CHAR(36),registration_id CHAR(36),customer_id CHAR(36),
    ticket_price DECIMAL(12,2),payment_method VARCHAR(30),payment_state VARCHAR(30),payment_status VARCHAR(30),
    payment_reference VARCHAR(100),payment_authorization VARCHAR(100),payment_checked_at DATETIME,updated_at DATETIME)");
$setup->exec("CREATE TABLE economy_configuration_data (company_id CHAR(36),gateway_type VARCHAR(30),is_active INT,
    client_id VARCHAR(100),client_secret_encrypted VARCHAR(100),subscription_key VARCHAR(100),merchant_serial_number VARCHAR(100),test_mode INT)");
$setup->exec("CREATE TABLE audit_logs (id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),user_email VARCHAR(255),
    action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name VARCHAR(255),old_values TEXT,new_values TEXT,severity VARCHAR(20),
    performed_at DATETIME,module VARCHAR(50)) ENGINE=InnoDB");
$setup->exec("INSERT INTO tenant_companies VALUES ('a','Bedrift A',1),('b','Bedrift B',1)");
$setup->exec("INSERT INTO tenant_staff VALUES ('person','a','Faktisk','Ansatt')");
$setup->exec("INSERT INTO tenant_admins VALUES ('staff','a','person','employee','admin',1)");
$setup->exec("INSERT INTO tenant_customers VALUES ('customer','PRIVATECustomer','PRIVATEName'),('delete-customer','PRIVATECustomer','PRIVATEName')");
$setup->exec("INSERT INTO events_eventdata VALUES ('event-a','a','Arrangement A'),('event-b','b','Arrangement B')");
$setup->exec("INSERT INTO economy_configuration_data VALUES ('a','vipps',1,'fake-client','fake-secret','fake-key','fake-msn',1)");
$_SESSION = ['company_id'=>'a','tenant_admin_id'=>'staff'];
$db = new AuditedPDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
// Use exact SQL from the live create/approve/reject paths, not synthetic table names.
$create = $db->prepare(endpointSql($root,'create_event_registration.php','INSERT INTO event_registrations'));
$create->execute(['reg-a','event-a','a','customer','pending',null,null]);
$create->execute(['reg-delete','event-a','a','delete-customer','pending',null,null]);
$db->prepare(endpointSql($root,'approve_registration.php','UPDATE event_registrations'))->execute(['private reason','staff','reg-a']);
$db->prepare(endpointSql($root,'reject_registration.php','UPDATE event_registrations'))->execute(['staff','private rejection','reg-delete','a']);
$registrationLogs = $setup->query("SELECT * FROM audit_logs WHERE resource_type='event_registrations'")->fetchAll(PDO::FETCH_ASSOC);
paymentAssert(count($registrationLogs) === 4, 'Actual registration create/approve/reject operations are audited');
$setup->exec("INSERT INTO events_checkin (id,event_id,registration_id,customer_id,ticket_price,payment_method,payment_state,payment_status)
    VALUES ('capture','event-a','reg-a','customer',100,'vipps','authorized','pending'),
    ('single','event-a','reg-a','customer',100,'vipps','pending','pending'),
    ('bulk','event-a','reg-a','customer',100,'vipps','pending','pending'),
    ('delete-checkin','event-a','reg-delete','delete-customer',100,'vipps','pending','pending'),
    ('foreign','event-b','foreign-reg','customer',100,'vipps','authorized','pending')");
$serverDir = $temp . '/payment-http';
mkdir($serverDir);
$counter = $temp . '/capture-count';
$bootstrap = 'require_once ' . var_export($root . '/includes/AuditedPDO.php',true) . '; function getDBConnection(){return new AuditedPDO('
    . var_export($dsn,true) . ',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}';
foreach (['capture_vipps_payment.php','check_single_vipps_payment.php','check_vipps_payments.php','delete_event_registration.php'] as $file) {
    $source = file_get_contents($root . '/api/' . $file);
    $source = str_replace('session_start();', 'session_save_path(' . var_export($temp . '/sessions',true)
        . ');session_start();$_SESSION=["company_id"=>"a","tenant_admin_id"=>"staff","admin_name"=>"Forged owner"];', $source);
    $source = str_replace("require_once __DIR__ . '/../config/database.php';",$bootstrap,$source,$replacements);
    paymentAssert($replacements === 1, 'Disposable database bootstrap replaced exactly once');
    if ($file !== 'delete_event_registration.php') {
        $source = stubProviderFunction($source,'getVippsAccessToken','return "fixture-access-token";');
        if ($file === 'capture_vipps_payment.php') {
            $source = stubProviderFunction($source,'captureVippsPayment','file_put_contents(' . var_export($counter,true)
                . ',"captured\n",FILE_APPEND);return ["success"=>true,"http_code"=>202];');
        } else {
            $source = stubProviderFunction($source,'checkVippsPaymentStatus',
                'return ["state"=>"CAPTURED","authorization_code"=>"private-authorization","amount"=>["value"=>10000,"currency"=>"NOK"]];');
        }
    }
    file_put_contents($serverDir . '/' . $file,$source);
}
$listener = stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
$port = (int)substr(strrchr(stream_socket_get_name($listener,false),':'),1);
fclose($listener);
$server = proc_open([PHP_BINARY,'-S','127.0.0.1:' . $port,'-t',$serverDir],
    [0=>['file','/dev/null','r'],1=>['file',$temp . '/payment-http.log','a'],2=>['file',$temp . '/payment-http.log','a']],$pipes);
paymentAssert(is_resource($server), 'Disposable payment HTTP worker started');
function paymentCall(string $file,array $data): array {
    global $port;
    $body = file_get_contents('http://127.0.0.1:' . $port . '/' . $file,false,stream_context_create(['http'=>[
        'method'=>'POST','header'=>"Content-Type: application/json\r\n",'content'=>json_encode($data),'ignore_errors'=>true,'timeout'=>15,
    ]]));
    $result = json_decode((string)$body,true);
    paymentAssert(is_array($result), 'Actual endpoint returned JSON: ' . $file);
    return $result;
}
try {
    for ($i=0;$i<60;$i++) {
        $socket = @fsockopen('127.0.0.1',$port,$errno,$error,0.1);
        if ($socket) {fclose($socket);break;}
        usleep(100000);
    }
    $single = paymentCall('check_single_vipps_payment.php',['checkin_id'=>'single']);
    paymentAssert($single['success'] === true && $setup->query("SELECT payment_state FROM events_checkin WHERE id='single'")->fetchColumn() === 'captured',
        'Actual single Vipps status endpoint persists and audits');
    $bulk = paymentCall('check_vipps_payments.php',['event_id'=>'event-a']);
    paymentAssert($bulk['success'] === true && $setup->query("SELECT payment_state FROM events_checkin WHERE id='bulk'")->fetchColumn() === 'captured',
        'Actual bulk Vipps status endpoint persists and audits');
    $setup->exec("UPDATE events_checkin SET payment_state='authorized',payment_status='pending' WHERE id='capture'");
    $captured = paymentCall('capture_vipps_payment.php',['checkin_id'=>'capture']);
    paymentAssert($captured['success'] === true && $captured['audit_confirmed'] === true
        && $setup->query("SELECT payment_status FROM events_checkin WHERE id='capture'")->fetchColumn() === 'paid',
        'Successful simulated external capture persists locally with transactional intent and final audit');
    paymentAssert($setup->query("SELECT COUNT(*) FROM audit_logs WHERE resource_id='capture' AND action='events.payment.captured'")->fetchColumn() == 1,
        'Capture completion is journaled exactly once');
    $beforeCalls = file_get_contents($counter);
    paymentAssert(paymentCall('capture_vipps_payment.php',['checkin_id'=>'capture'])['success'] === false,
        'Repeated capture does not charge again');
    paymentAssert(paymentCall('capture_vipps_payment.php',['checkin_id'=>'foreign'])['success'] === false
        && file_get_contents($counter) === $beforeCalls, 'Foreign or already captured payments cannot reach provider capture');
    $deleted = paymentCall('delete_event_registration.php',['registration_id'=>'reg-delete']);
    paymentAssert($deleted['success'] === true
        && $setup->query("SELECT COUNT(*) FROM event_registrations WHERE id='reg-delete'")->fetchColumn() == 0
        && $setup->query("SELECT COUNT(*) FROM events_checkin WHERE id='delete-checkin'")->fetchColumn() == 0,
        'Actual registration endpoint deletes both resources');
    paymentAssert($setup->query("SELECT COUNT(*) FROM audit_logs WHERE action='events.delete' AND resource_id IN ('reg-delete','delete-checkin')")->fetchColumn() == 2,
        'Both explicit deletions are audited inside their transaction');
    $privacy = implode('', $setup->query('SELECT CONCAT(old_values,new_values) FROM audit_logs')->fetchAll(PDO::FETCH_COLUMN));
    foreach (['PRIVATECustomer','PRIVATEName','private-authorization','private reason','private rejection','fake-secret'] as $value) {
        paymentAssert(!str_contains($privacy,$value),'No customer/provider secrets or private reasons in audit');
    }
    $parsed = tenantAuditMutation('DELETE ec FROM events_checkin ec INNER JOIN events_eventdata e ON ec.event_id=e.id WHERE e.company_id=?');
    paymentAssert($parsed['table'] === 'events_checkin','Joined DELETE cannot silently bypass instrumentation');
    $blocked = false;
    try {$db->prepare($parsed['sql'])->execute(['a']);} catch (TenantAuditException $error) {$blocked=true;}
    paymentAssert($blocked,'Unhooked joined mutation fails before deleting data');
    // A final-audit outage after provider success must retain the captured local status.
    $setup->exec("UPDATE events_checkin SET payment_state='authorized',payment_status='pending' WHERE id='capture'");
    $setup->exec("CREATE TRIGGER fail_final_capture BEFORE INSERT ON audit_logs FOR EACH ROW BEGIN
        IF NEW.action='events.payment.captured' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='forced final audit failure'; END IF; END");
    $uncertainAudit = paymentCall('capture_vipps_payment.php',['checkin_id'=>'capture']);
    paymentAssert($uncertainAudit['success'] === true && $uncertainAudit['audit_confirmed'] === false
        && $setup->query("SELECT payment_state FROM events_checkin WHERE id='capture'")->fetchColumn() === 'captured',
        'Final audit failure cannot undo an accepted capture or invite another charge');
    $beforeCalls = file_get_contents($counter);
    $setup->exec("UPDATE events_checkin SET payment_state='authorized',payment_status='pending' WHERE id='capture'");
    $setup->exec('DROP TABLE audit_logs');
    $preflight = paymentCall('capture_vipps_payment.php',['checkin_id'=>'capture']);
    paymentAssert($preflight['success'] === false && $preflight['external_capture_accepted'] === false
        && file_get_contents($counter) === $beforeCalls,'Missing audit storage prevents external capture before charging');
    echo "Actual event-registration and Vipps endpoint audit checks passed (provider transport stubbed)\n";
} finally {
    proc_terminate($server);
    proc_close($server);
}