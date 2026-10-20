<?php
declare(strict_types=1);
[$script, $root, $temp] = $argv;
if (!str_starts_with($temp, '/tmp/portal-users-mysql.')) throw new RuntimeException('Disposable DB only');
session_id('portal-fixture-current');
session_start();
require $root . '/includes/tenant_supervisor.php';
require $root . '/includes/tenant_notifications.php';
$db = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$db->exec('CREATE DATABASE portal_fixture; USE portal_fixture');
foreach ([
    'tenant_companies(id VARCHAR(36) PRIMARY KEY,name TEXT,is_active INT)',
    'tenant_staff(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),name TEXT,first_name TEXT,last_name TEXT)',
    'tenant_admins(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),tenant_staff_id VARCHAR(36),username TEXT,role TEXT,is_active INT,tenant_supervisor INT,password_hash TEXT)',
    'system_modules(id VARCHAR(36) PRIMARY KEY,name TEXT,module_key VARCHAR(50),is_active INT)',
    'tenant_company_modules(company_id VARCHAR(36),module_id VARCHAR(36),is_active INT,PRIMARY KEY(company_id,module_id))',
    'tenant_admin_modules(tenant_admin_id VARCHAR(36),module_id VARCHAR(36),granted_by VARCHAR(36),granted_at DATETIME,PRIMARY KEY(tenant_admin_id,module_id))',
    'tenant_admin_sessions(tenant_admin_id VARCHAR(36),session_id VARCHAR(100),is_active INT,logout_at DATETIME NULL)',
    'audit_logs(id VARCHAR(36),company_id VARCHAR(36),user_id VARCHAR(36),user_type TEXT,user_email TEXT,action TEXT,resource_type TEXT,resource_id VARCHAR(36),resource_name TEXT,old_values JSON,new_values JSON,severity VARCHAR(20),performed_at DATETIME,module TEXT)',
    'tenant_polling_tracker(id VARCHAR(36),company_id VARCHAR(36),module_source TEXT,action_type TEXT,polling_source TEXT,polling_interval_seconds INT,items_processed_last_poll INT,metadata JSON,created_at DATETIME,polling_status TEXT,last_successful_poll DATETIME)',
] as $definition) $db->exec('CREATE TABLE ' . $definition . ' ENGINE=InnoDB');
$db->exec("INSERT INTO tenant_companies VALUES ('a','Demo A',1),('b','Demo B',1)");
$db->exec("INSERT INTO tenant_staff VALUES ('s1','a','Riktig Navn','Gammelt','Navn'),('s2','a',NULL,'Fallback','Navn'),('s3','b','Other Company',NULL,NULL)");
$db->exec("INSERT INTO tenant_admins VALUES
    ('owner','a','s1','owner','admin',1,1,'initial'),
    ('user','a','s2','user','admin',1,0,'initial'),
    ('unlinked','a',NULL,'unlinked','admin',1,0,'initial'),
    ('foreign','b','s3','foreign','admin',1,1,'initial'),
    ('bad-link','a','s3','bad-link','admin',1,0,'initial')");
$rental = 'a1111111-1111-1111-1111-111111111111';
$events = 'b2222222-2222-2222-2222-222222222222';
$economy = 'c3333333-3333-3333-3333-333333333333';
$db->prepare("INSERT INTO system_modules VALUES (?,'Rental','rental',1),(?,'Events','events',1),(?,'Økonomi','economy',1)")
    ->execute([$rental,$events,$economy]);
$db->prepare('INSERT INTO tenant_company_modules VALUES (?, ?, 1),(?, ?, 0)')->execute(['a',$rental,'a',$events]);
$_SESSION = ['tenant_admin_id' => 'owner', 'company_id' => 'a'];
function portalAssert(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function denied(callable $fn): void {
    try { $fn(); } catch (DomainException | InvalidArgumentException $e) { return; }
    throw new RuntimeException('Unauthorized operation succeeded');
}
$users = tenantManagedUsers($db,'a');
portalAssert(count($users['users']) === 2, 'Only linked same-company staff');
portalAssert($users['users'][0]['name'] === 'Riktig Navn', 'Staff name preferred');
portalAssert($users['users'][1]['name'] === 'Fallback Navn', 'Legacy name supported');
portalAssert(array_column($users['modules'],'id') === [$rental], 'Only active company modules');
denied(fn() => tenantChangeManagedUser($db,'b',['user_id'=>'foreign','action'=>'grant','module_id'=>$rental]));
foreach (['unlinked','foreign','bad-link'] as $id) {
    denied(fn() => tenantChangeManagedUser($db,'a',['user_id'=>$id,'action'=>'grant','module_id'=>$rental]));
}
foreach ([$events,$economy] as $module) {
    denied(fn() => tenantChangeManagedUser($db,'a',['user_id'=>'user','action'=>'grant','module_id'=>$module]));
}
tenantChangeManagedUser($db,'a',['user_id'=>'user','action'=>'grant','module_id'=>$rental]);
tenantChangeManagedUser($db,'a',['user_id'=>'user','action'=>'grant','module_id'=>$rental]);
portalAssert((int)$db->query('SELECT COUNT(*) FROM tenant_admin_modules')->fetchColumn() === 1, 'UUID grant and replay idempotent');
portalAssert((int)$db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn() === 1, 'Unchanged grant produces no extra audit');
tenantChangeManagedUser($db,'a',['user_id'=>'user','action'=>'revoke','module_id'=>$rental]);
portalAssert((int)$db->query('SELECT COUNT(*) FROM tenant_admin_modules')->fetchColumn() === 0, 'Revoke removes effective presence-based grant');
$_SESSION['tenant_admin_id'] = 'user';
portalAssert(!tenantIsSupervisor($db,'a'), 'Role admin is not supervisor');
denied(fn() => tenantManagedUsers($db,'a'));
denied(fn() => tenantChangeManagedUser($db,'a',['user_id'=>'owner','action'=>'password','password'=>'LongFixturePassword']));
$_SESSION['tenant_admin_id'] = 'owner';
$db->exec("INSERT INTO tenant_admin_sessions VALUES ('user','old-user-session',1,NULL),('owner','portal-fixture-current',1,NULL),('owner','old-owner-session',1,NULL)");
denied(fn() => tenantChangeManagedUser($db,'a',['user_id'=>'user','action'=>'password','password'=>'short']));
tenantChangeManagedUser($db,'a',['user_id'=>'user','action'=>'password','password'=>'LongFixturePassword']);
portalAssert(password_verify('LongFixturePassword', $db->query("SELECT password_hash FROM tenant_admins WHERE id='user'")->fetchColumn()),'Password is hashed');
portalAssert((int)$db->query("SELECT is_active FROM tenant_admin_sessions WHERE session_id='old-user-session'")->fetchColumn() === 0,'Reset revokes old sessions');
tenantChangeManagedUser($db,'a',['user_id'=>'owner','action'=>'password','password'=>'LongFixturePassword']);
portalAssert((int)$db->query("SELECT is_active FROM tenant_admin_sessions WHERE session_id='portal-fixture-current'")->fetchColumn() === 1,'Self-reset retains verified session');
$json = implode('', $db->query('SELECT new_values FROM audit_logs')->fetchAll(PDO::FETCH_COLUMN));
portalAssert(!str_contains($json,'LongFixturePassword') && !str_contains($json,'$2y$'),'Audit excludes passwords and hashes');
// Existing notifications remain tenant- and module-scoped, now newest first.
$db->prepare('INSERT INTO tenant_admin_modules(tenant_admin_id,module_id) VALUES (?,?)')->execute(['owner',$rental]);
$db->exec("INSERT INTO tenant_polling_tracker VALUES
    ('older','a','rental','rental_booking','one',10,1,NULL,'2026-01-01','pending',NULL),
    ('newer','a','rental','rental_booking','two',10,1,NULL,'2026-01-02','pending',NULL),
    ('hidden','a','events','event_registration','three',10,1,NULL,'2026-01-03','pending',NULL),
    ('other','b','rental','rental_booking','four',10,1,NULL,'2026-01-04','pending',NULL)");
portalAssert(array_column(tenantPendingNotifications($db,'a'),'id') === ['newer','older'],'Newest first, excludes unauthorized and other-company notifications');
tenantAcknowledgeNotification($db,'a','newer',false);
portalAssert(array_column(tenantPendingNotifications($db,'a'),'id') === ['older'],'Acknowledgement reveals next notification');
try { tenantAcknowledgeNotification($db,'a','other',true); throw new RuntimeException('Cross-company acknowledgement'); }
catch (OutOfBoundsException $e) {}
echo "Portal user permissions, UUID grants, no-op auditing, password reset/session revocation and notification scoping: passed\n";
