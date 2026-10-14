<?php
declare(strict_types=1);
$root = $argv[1];
$temp = $argv[2];
require_once $root . '/includes/AuditedPDO.php';
$setup = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE audit_fixture');
$setup->exec('USE audit_fixture');
$setup->exec("CREATE TABLE tenant_companies (id CHAR(36) PRIMARY KEY,name VARCHAR(255),is_active INT)");
$setup->exec("CREATE TABLE tenant_staff (id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name VARCHAR(255),last_name VARCHAR(255),is_active INT,phone VARCHAR(255))");
$setup->exec("CREATE TABLE tenant_admins (id CHAR(36) PRIMARY KEY,company_id CHAR(36),tenant_staff_id CHAR(36),username VARCHAR(255),role VARCHAR(50),is_active INT,password_hash VARCHAR(255))");
$setup->exec("CREATE TABLE audit_logs (id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),user_email VARCHAR(255),
    action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name VARCHAR(255),old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50)) ENGINE=InnoDB");
$setup->exec("INSERT INTO tenant_companies VALUES ('a','Opprinnelig bedrift',1),('b','Annen bedrift',1)");
$setup->exec("INSERT INTO tenant_staff VALUES ('owner-person','a','Hoved','Admin',1,'private-phone'),('staff-person','a','Reell','Ansatt',1,'private-phone'),('b-person','b','Andre','Admin',1,'private-phone')");
$setup->exec("INSERT INTO tenant_admins VALUES ('owner','a','owner-person','owner','admin',1,'secret-fixture-hash'),('staff','a','staff-person','staff','staff',1,'secret-fixture-hash'),('b-owner','b','b-person','b-owner','admin',1,'secret-fixture-hash')");
foreach (['rental_objects','membership_plans','events_eventdata','economy_transactions','tenant_announcements','tenant_website_general'] as $table) {
    $setup->exec("CREATE TABLE `$table` (id CHAR(36) PRIMARY KEY,company_id CHAR(36),name VARCHAR(255),status VARCHAR(30),price DECIMAL(12,2),body TEXT) ENGINE=InnoDB");
}
$setup->exec("ALTER TABLE membership_plans ADD is_active INT DEFAULT 1");
$setup->exec("CREATE TABLE tenant_admin_modules (tenant_admin_id CHAR(36),module_id INT,is_active INT,PRIMARY KEY(tenant_admin_id,module_id))");
$setup->exec("CREATE TABLE event_membership_plans (event_id CHAR(36),membership_plan_id CHAR(36),ticket_price DECIMAL(12,2),
    public_display INT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(event_id,membership_plan_id))");
$setup->exec("CREATE TABLE email_templates (id CHAR(36) PRIMARY KEY,company_id CHAR(36),name VARCHAR(255),template_key VARCHAR(100),body_template TEXT,subject_template TEXT,is_active INT)");
$setup->exec("CREATE TABLE email_configs (id INT AUTO_INCREMENT PRIMARY KEY,company_id CHAR(36),display_name VARCHAR(255),smtp_password_encrypted VARCHAR(255),automail_rental_confirm INT)");
$setup->exec("INSERT INTO email_templates VALUES ('tpl','a','Mal','rental_confirmation','first body','first subject',1)");
$setup->exec("INSERT INTO email_configs (company_id,display_name,smtp_password_encrypted,automail_rental_confirm) VALUES ('a','Avsender','first-secret',1)");
$_SESSION = ['company_id'=>'a','tenant_admin_id'=>'staff','admin_name'=>'Forged owner name','admin_role'=>'admin'];
$db = new AuditedPDO('mysql:unix_socket=' . $temp . '/mysql.sock;dbname=audit_fixture', 'root', '', [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,
]);
function auditAssert(bool $ok, string $label): void { if (!$ok) throw new RuntimeException($label); }
foreach (['rental_objects','membership_plans','events_eventdata','economy_transactions','tenant_announcements','tenant_website_general'] as $table) {
    $db->prepare("INSERT INTO `$table` (id,company_id,name,status,price,body) VALUES (?,?,?,?,?,?)")
        ->execute([$table,'a','Ressurs','pending',100,'private-customer-text']);
    $db->prepare("UPDATE `$table` SET status=?,price=? WHERE id=? AND company_id=?")->execute(['approved',120,$table,'a']);
    $db->prepare("UPDATE `$table` SET status=?,price=? WHERE id=? AND company_id=?")->execute(['approved',120,$table,'a']);
    $db->prepare("DELETE FROM `$table` WHERE id=? AND company_id=?")->execute([$table,'a']);
}
$logs = $setup->query('SELECT * FROM audit_logs')->fetchAll(PDO::FETCH_ASSOC);
auditAssert(count($logs) === 18, 'Create, edit and delete logged; unchanged saves skipped in every central module');
foreach ($logs as $log) {
    $meta = json_decode($log['new_values'],true)['_audit'];
    auditAssert($log['user_id'] === 'staff' && $meta['actor_name'] === 'Reell Ansatt' && $meta['actor_role'] === 'staff',
        'Exact staff account snapshot, no owner/session-name substitution');
    auditAssert(!str_contains($log['old_values'].$log['new_values'],'private-customer-text'), 'No full/private content in audit');
}
auditAssert(!tenantAuditCanRead($db,'a'), 'Staff cannot read company audit');
$db->prepare("UPDATE tenant_admins SET password_hash=? WHERE id=? AND company_id=?")->execute(['second-secret','staff','a']);
$last = $setup->query('SELECT new_values FROM audit_logs ORDER BY performed_at DESC')->fetchAll(PDO::FETCH_COLUMN);
auditAssert(count(array_filter($last,static fn($json) => in_array('password_hash',json_decode($json,true)['_audit']['redacted_fields'] ?? [],true))) === 1,
    'Credential change is logged without its value/hash');
$db->prepare("UPDATE email_templates SET body_template=?,subject_template=? WHERE id=? AND company_id=?")->execute(['new full body','new full subject','tpl','a']);
$db->prepare("INSERT INTO email_templates (id,company_id,name,template_key,body_template,subject_template,is_active)
    VALUES ('tpl','a',?,'rental_confirmation',?,?,1) ON DUPLICATE KEY UPDATE
    name=VALUES(name),body_template=VALUES(body_template),subject_template=VALUES(subject_template)")
    ->execute(['Mal oppdatert','upsert private body','upsert private subject']);
$templateAuditCount = (int)$setup->query("SELECT COUNT(*) FROM audit_logs WHERE resource_type='email_templates'")->fetchColumn();
auditAssert($templateAuditCount === 2, 'Multi-assignment template upsert preserves actual before/after hashes');
$db->prepare("UPDATE email_configs SET smtp_password_encrypted=?,automail_rental_confirm=? WHERE company_id=?")->execute(['new-smtp-secret',0,'a']);
$all = implode('', $setup->query('SELECT CONCAT(old_values,new_values) FROM audit_logs')->fetchAll(PDO::FETCH_COLUMN));
foreach (['secret-fixture-hash','second-secret','new full body','new full subject','new-smtp-secret','upsert private body'] as $secret) {
    auditAssert(!str_contains($all,$secret), 'Private values are absent');
}
$db->prepare("INSERT INTO tenant_admin_modules (tenant_admin_id,module_id,is_active) VALUES (?,?,1),(?,?,1)")->execute(['staff',1,'staff',2]);
$db->prepare("DELETE FROM tenant_admin_modules WHERE tenant_admin_id=?")->execute(['staff']);
$setup->exec("INSERT INTO events_eventdata (id,company_id,name,status,price) VALUES ('pricing-event','a','Priser','active',100)");
$db->prepare("INSERT INTO event_membership_plans (event_id,membership_plan_id,ticket_price,public_display) VALUES (?,?,?,1),(?,?,?,1)")
    ->execute(['pricing-event','plan-1',100,'pricing-event','plan-2',150]);
$db->prepare("UPDATE event_membership_plans SET ticket_price=? WHERE event_id=? AND membership_plan_id=?")
    ->execute([200,'pricing-event','plan-2']);
auditAssert($setup->query("SELECT COUNT(*) FROM audit_logs WHERE resource_type='event_membership_plans'")->fetchColumn() == 3,
    'Parent-scoped compound event-price resources and bulk writes are audited');
$countLogs = static fn() => (int)$setup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
$count = $countLogs();
$db->prepare('UPDATE tenant_admins SET password_hash=? WHERE id=? AND company_id=?')->execute(['second-secret','staff','a']);
$db->prepare('UPDATE membership_plans SET price=? WHERE id=?')->execute([100,'missing']);
$db->prepare("INSERT INTO email_templates (id,company_id,name,template_key,body_template,subject_template,is_active)
    VALUES ('tpl','a',?,'rental_confirmation',?,?,1) ON DUPLICATE KEY UPDATE
    name=VALUES(name),body_template=VALUES(body_template),subject_template=VALUES(subject_template)")
    ->execute(['Mal oppdatert','upsert private body','upsert private subject']);
auditAssert($countLogs() === $count, 'Identical private value, identical upsert and zero-match UPDATE create no audit rows');
$setup->exec("ALTER TABLE rental_objects ADD weekday_prices TEXT, ADD access_code VARCHAR(6), ADD updated_at DATETIME");
$db = new AuditedPDO('mysql:unix_socket=' . $temp . '/mysql.sock;dbname=audit_fixture', 'root', '', [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,
]);
$setup->exec("INSERT INTO rental_objects (id,company_id,name,status,price,weekday_prices,access_code)
    VALUES ('json-object','a','JSON','active',500,'{\"monday\":500,\"tuesday\":600}','0037')");
$db->prepare('UPDATE rental_objects SET weekday_prices=?,price=?,updated_at=NOW() WHERE id=? AND company_id=?')
    ->execute(['{ "tuesday":600.0, "monday":500 }','500.00','json-object','a']);
auditAssert($countLogs() === $count, 'Equivalent private JSON object order/numbers and timestamp are not changes');
$db->prepare('UPDATE rental_objects SET access_code=? WHERE id=? AND company_id=?')->execute(['37','json-object','a']);
auditAssert($countLogs() === ++$count, 'Leading zero change in private access code is audited (actual '.$countLogs().', expected '.$count.')');
$db->prepare('UPDATE rental_objects SET weekday_prices=? WHERE id=? AND company_id=?')
    ->execute(['{"monday":501,"tuesday":600}','json-object','a']);
auditAssert($countLogs() === ++$count, 'Actual private JSON price change is audited');
$db->prepare("INSERT INTO rental_objects (id,company_id,name,status,price,access_code) VALUES (?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE access_code=VALUES(access_code)")
    ->execute(['json-object','a','JSON','active',500,'0038']);
auditAssert($countLogs() === ++$count, 'Private-only upsert change is audited');
$db->prepare('UPDATE rental_objects SET body=? WHERE id=?')->execute([str_repeat('x',501),'json-object']);
++ $count;
$db->prepare('UPDATE rental_objects SET body=? WHERE id=?')->execute([str_repeat('x',500).'y','json-object']);
auditAssert($countLogs() === ++$count, 'Change beyond displayed audit truncation remains audited');
$db->beginTransaction();
auditAssert(tenantAuditChange($db,'a','rental','rental.save','rental_objects','json-object','JSON',
    ['price'=>'500.00'],['price'=>500]) === '', 'Explicit unchanged change returns no audit ID');
tenantAuditRecord($db,'a','email','email.smtp_accepted','email_queue','mail','Mail',[],[],'smtp_accepted');
$db->commit();
auditAssert($countLogs() === ++$count, 'Actual event with empty snapshots retains its audit');
$db->beginTransaction();
$db->prepare('UPDATE rental_objects SET price=? WHERE id=?')->execute(['500.000','json-object']);
$db->rollBack();
auditAssert($countLogs() === $count, 'Rolling back an unchanged save adds no rollback log');
require_once $root . '/includes/event_membership_prices.php';
$setup->exec("INSERT INTO membership_plans (id,company_id,name,status,price) VALUES ('plan-1','a','Plan 1','active',100),('plan-2','a','Plan 2','active',150)");
$db->beginTransaction();
saveEventMembershipPrices($db,'a','pricing-event',[
    ['plan_id'=>'plan-2','price'=>'200.00','public_display'=>true],
    ['plan_id'=>'plan-1','price'=>100,'public_display'=>'1']]);
$db->commit();
auditAssert($countLogs() === $count, 'Identical membership prices with reordered input preserve rows and create no audit');
$db->beginTransaction();
saveEventMembershipPrices($db,'a','pricing-event',[
    ['plan_id'=>'plan-1','price'=>101,'public_display'=>1],
    ['plan_id'=>'plan-2','price'=>200,'public_display'=>1]]);
$db->commit();
auditAssert($countLogs() === ++$count, 'One membership price edit creates exactly one log, not delete/reinsert logs');
// Execute the actual controller with only its database/session fixture replaced.
$httpDir = $temp . '/prices-http';
mkdir($httpDir);
$endpoint = file_get_contents($root . '/api/save_event_membership_pricing.php');
$endpoint = str_replace('session_start();', "session_start(); \$_SESSION=['company_id'=>'a','tenant_admin_id'=>'staff'];", $endpoint);
$fixtureFactory = 'require_once ' . var_export($root.'/includes/AuditedPDO.php',true) . '; function getDBConnection() { return new AuditedPDO('
    . var_export('mysql:unix_socket='.$temp.'/mysql.sock;dbname=audit_fixture',true)
    . ', "root", "", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]); }';
$endpoint = str_replace("require_once __DIR__ . '/../config/database.php';", $fixtureFactory, $endpoint);
$endpoint = str_replace("__DIR__ . '/../includes/event_membership_prices.php'", var_export($root.'/includes/event_membership_prices.php',true), $endpoint);
file_put_contents($httpDir.'/save.php', $endpoint);
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$address = stream_socket_get_name($listener, false);
fclose($listener);
$httpServer = proc_open([PHP_BINARY,'-S',$address,'-t',$httpDir], [
    0=>['file','/dev/null','r'],1=>['file',$temp.'/prices-http.log','a'],2=>['file',$temp.'/prices-http.log','a']], $pipes);
try {
    $ready = false;
    for ($i=0;$i<100;$i++) {
        $socket=@stream_socket_client('tcp://'.$address,$errno,$error,.1);
        if ($socket) { fclose($socket); $ready=true; break; }
        usleep(20000);
    }
    auditAssert($ready, 'Price HTTP fixture ready');
    $savePrices = static function(array $prices) use ($address): array {
        $curl=curl_init('http://'.$address.'/save.php');
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode(['event_id'=>'pricing-event','pricing'=>$prices])]);
        $body=curl_exec($curl);curl_close($curl);
        return json_decode((string)$body,true,512,JSON_THROW_ON_ERROR);
    };
    $same = [['plan_id'=>'plan-1','price'=>'101.00','public_display'=>1],['plan_id'=>'plan-2','price'=>200,'public_display'=>1]];
    auditAssert($savePrices($same)['success'] && $countLogs()===$count, 'Real price API unchanged save creates no log');
    $same[1]['price']=201;
    auditAssert($savePrices($same)['success'] && $countLogs()===++$count, 'Real price API one edit creates one log');
    auditAssert($savePrices([$same[0]])['success'] && $countLogs()===++$count, 'Real price API one deletion creates one log');
    $same[1]['price']=201;
    auditAssert($savePrices($same)['success'] && $countLogs()===++$count, 'Real price API new price creates one log');
    $countBeforeDenied=$countLogs();
    auditAssert(!$savePrices([['plan_id'=>'b-plan','price'=>1]])['success']
        && $countLogs()===$countBeforeDenied, 'Foreign plan rejected without losing stored prices or creating false mutation audit');
    auditAssert((int)$setup->query("SELECT COUNT(*) FROM event_membership_plans WHERE event_id='pricing-event'")->fetchColumn()===2,
        'Foreign plan attempt preserves existing prices');
} finally {
    proc_terminate($httpServer);proc_close($httpServer);
}
$db->beginTransaction();
$db->prepare("INSERT INTO membership_plans (id,company_id,name,status,price) VALUES (?,?,?,?,?)")->execute(['rolled-back','a','Plan','active',10]);
$db->rollBack();
$rollbackLogs = $setup->query("SELECT new_values FROM audit_logs WHERE resource_id='rolled-back'")->fetchAll(PDO::FETCH_COLUMN);
auditAssert(count($rollbackLogs) === 1 && json_decode($rollbackLogs[0],true)['_audit']['outcome'] === 'rolled_back',
    'Success audit rolls back, leaving only an explicit rollback outcome');
$foreignBlocked = false;
try {
    $db->prepare("INSERT INTO membership_plans (id,company_id,name,status,price) VALUES (?,?,?,?,?)")->execute(['foreign','b','Plan','active',10]);
} catch (TenantAuditException $error) { $foreignBlocked = true; }
auditAssert($foreignBlocked && $setup->query("SELECT COUNT(*) FROM membership_plans WHERE id='foreign'")->fetchColumn() == 0, 'Foreign company cannot be attributed to acting staff');
$_SESSION = ['company_id'=>'a','tenant_admin_id'=>'owner'];
auditAssert(tenantAuditCanRead($db,'a'), 'Verified owner audit access');
auditAssert(!tenantAuditCanRead($db,'b'), 'No cross-company read authorization');
$db->prepare("INSERT INTO email_configs (company_id,display_name,automail_rental_confirm) VALUES (?,?,?)")->execute(['a','Ny avsender',1]);
auditAssert((int)$db->lastInsertId() === 2, 'Application-generated insert IDs survive audit INSERTs');
$count = $countLogs();
$db->prepare('UPDATE membership_plans SET price=? WHERE company_id=?')->execute([100,'a']);
auditAssert($countLogs() === $count + 1, 'Multi-row save logs only the row actually changed');
$db->prepare('UPDATE membership_plans SET price=? WHERE company_id=?')->execute(['100.00','a']);
auditAssert($countLogs() === $count + 1, 'Repeated multi-row save writes no new audit');
// An active-only selector omits inactive plans, but its omission is not a
// deletion request and must not create a false audit event.
$setup->exec("INSERT INTO events_eventdata(id,company_id,name) VALUES('retained-prices','a','Retained prices')");
$setup->exec("INSERT INTO event_membership_plans(event_id,membership_plan_id,ticket_price,public_display) VALUES('retained-prices','plan-1',65,0),('retained-prices','missing-plan',10,1)");
$setup->exec("UPDATE membership_plans SET is_active=0 WHERE id='plan-1'");
$auditBeforeRetention = $countLogs();
$db->beginTransaction();
saveEventMembershipPrices($db,'a','retained-prices',[]);
$db->commit();
auditAssert((int)$setup->query("SELECT COUNT(*) FROM event_membership_plans WHERE event_id='retained-prices'")->fetchColumn()===2,
    'Inactive and missing plan associations survive an empty editable price list');
auditAssert($countLogs()===$auditBeforeRetention,'Retaining omitted historical prices emits no audit');
$setup->exec("UPDATE membership_plans SET is_active=1 WHERE id='plan-1'");
$setup->exec('DROP TABLE audit_logs');
$db->prepare('UPDATE membership_plans SET price=? WHERE id=? AND company_id=?')->execute([100,'plan-1','a']);
auditAssert(tenantAuditRecord($db,'a','membership','membership.save','membership_plans','plan-1','Plan',[],[],'noop') === '',
    'Unchanged save needs no INSERT even when the audit table is unavailable');
$failed = false;
try {
    $db->prepare("INSERT INTO membership_plans (id,company_id,name,status,price) VALUES (?,?,?,?,?)")->execute(['missing-audit','a','Plan','active',10]);
} catch (TenantAuditException $error) { $failed = true; }
auditAssert($failed && $setup->query("SELECT COUNT(*) FROM membership_plans WHERE id='missing-audit'")->fetchColumn() == 0, 'Audit failure rolls back business mutation');
echo "Isolated tenant audit MySQL checks passed\n";