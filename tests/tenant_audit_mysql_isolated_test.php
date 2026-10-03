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
$setup->exec("CREATE TABLE tenant_admin_modules (tenant_admin_id CHAR(36),module_id INT,is_active INT,PRIMARY KEY(tenant_admin_id,module_id))");
$setup->exec("CREATE TABLE event_membership_plans (event_id CHAR(36),membership_plan_id CHAR(36),ticket_price DECIMAL(12,2),
    public_display INT,PRIMARY KEY(event_id,membership_plan_id))");
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
auditAssert(count($logs) === 24, 'Create, edit, no-op and delete coverage in every central module');
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
$setup->exec('DROP TABLE audit_logs');
$failed = false;
try {
    $db->prepare("INSERT INTO membership_plans (id,company_id,name,status,price) VALUES (?,?,?,?,?)")->execute(['missing-audit','a','Plan','active',10]);
} catch (TenantAuditException $error) { $failed = true; }
auditAssert($failed && $setup->query("SELECT COUNT(*) FROM membership_plans WHERE id='missing-audit'")->fetchColumn() == 0, 'Audit failure rolls back business mutation');
echo "Isolated tenant audit MySQL checks passed\n";