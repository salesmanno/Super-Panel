<?php
declare(strict_types=1);
[$script, $root, $tmp] = $argv;
require_once $root . '/includes/tenant_customer_contacts.php';
$checks = 0;
function contactCheck(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $checks++;
}
function contactExpectInvalid(callable $operation, string $message): void {
    try { $operation(); } catch (InvalidArgumentException $e) { contactCheck(true, $message); return; }
    throw new RuntimeException('FAIL: ' . $message);
}
function contactFixtureDb(string $socket): PDO {
    return new PDO('mysql:unix_socket=' . $socket . ';dbname=fixture', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
$socket = $tmp . '/mysql.sock';
$db = new PDO('mysql:unix_socket=' . $socket, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE fixture CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; USE fixture');
$db->exec("CREATE TABLE tenant_customers(
    id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name VARCHAR(255),last_name VARCHAR(255),
    email VARCHAR(255),phone VARCHAR(255),status VARCHAR(20) DEFAULT 'active',
    customer_code VARCHAR(100),customer_type VARCHAR(20),address_street TEXT,address_city TEXT,address_zip VARCHAR(20),date_of_birth DATE,notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$schemaMigration = file_get_contents($root . '/db_migrations/tenant_customer_contacts_setup.sql');
$db->exec($schemaMigration);
$db->exec("CREATE TABLE membership_members(
    id CHAR(36) PRIMARY KEY,company_id CHAR(36),customer_id CHAR(36),membership_plan_id CHAR(36),
    status VARCHAR(20),auto_renew TINYINT,cancellation_active TINYINT DEFAULT 0,expiration_date DATE,
    member_code VARCHAR(100),share_code VARCHAR(100),join_date DATE,payment_status VARCHAR(20),paymentMethod VARCHAR(20),created_at DATETIME);
    CREATE TABLE tenant_companies(id CHAR(36) PRIMARY KEY);
    INSERT INTO tenant_companies VALUES('company-a');
    CREATE TABLE membership_plans(id CHAR(36),company_id CHAR(36),name VARCHAR(100),price DECIMAL(12,2),billing_cycle VARCHAR(20));
    INSERT INTO membership_plans VALUES('plan-a','company-a','Fixture',100,'yearly');
    CREATE TABLE historical_contact_snapshots(id INT PRIMARY KEY,kind VARCHAR(40),email VARCHAR(255),phone VARCHAR(255));
    INSERT INTO tenant_customers(id,company_id,first_name,last_name,email,phone) VALUES
    ('customer-a','company-a','First','Customer','legacy@example.test','12 34 56 78'),
    ('customer-b','company-a','Second','Customer','old@example.test','22.33.44.55'),
    ('foreign','company-b','Foreign','Customer','current@example.test',NULL);
    INSERT INTO membership_members(id,company_id,customer_id,membership_plan_id,status,auto_renew,cancellation_active,expiration_date)
    VALUES('member-a','company-a','customer-a','plan-a','active',0,0,NULL),
    ('member-b','company-a','customer-b','plan-a','inactive',0,0,NULL),
    ('member-missing','company-a',NULL,NULL,'active',0,0,NULL);
    INSERT INTO tenant_customer_contacts VALUES
    ('email-a','customer-a','email','Current@Example.test','current@example.test',1,'2025-01-01',NOW()),
    ('email-secondary','customer-a','email','office@example.test','office@example.test',0,NULL,NOW());
    INSERT INTO historical_contact_snapshots VALUES
    (1,'event','registration@example.test','11111111'),(2,'rental','request@example.test','22222222'),
    (3,'sales','receipt@example.test','33333333'),(4,'invoice','invoice@example.test','44444444'),
    (5,'ledger','ledger@example.test','55555555'),(6,'queued_email','queued@example.test','66666666');");
$snapshotHash = hash('sha256', serialize($db->query('SELECT * FROM historical_contact_snapshots ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)));
$db->exec($schemaMigration);
contactCheck((int)$db->query('SELECT COUNT(*) FROM tenant_customer_contacts')->fetchColumn() === 2, 'reapplying real schema migration retains existing contacts');

contactCheck(tenantCustomerNormalizeContact('email', ' User@EXAMPLE.test ') === 'user@example.test', 'email normalization');
contactCheck(tenantCustomerNormalizeContact('phone', ' +47 (12).34-56 78 ') === '+4712345678', 'phone normalization');
contactExpectInvalid(fn() => tenantCustomerContactSql('tc;DROP', 'email'), 'unsafe SQL aliases rejected');
contactExpectInvalid(fn() => tenantCustomerNormalizeContact('unknown', 'value'), 'unsupported type rejected');
contactExpectInvalid(fn() => tenantCustomerValidateContact('phone', '...'), 'punctuation-only phone rejected');
contactExpectInvalid(fn() => tenantCustomerValidateContact('phone', str_repeat('1', 256)), 'oversize contact rejected');
$emailSql = tenantCustomerContactSql('tc', 'email');
$phoneSql = tenantCustomerContactSql('tc', 'phone');
$read = function(string $id) use (&$db, $emailSql, $phoneSql): array {
    return $db->query("SELECT {$emailSql} email, {$phoneSql} phone FROM tenant_customers tc WHERE tc.id=" . $db->quote($id))->fetch(PDO::FETCH_ASSOC);
};
contactCheck($read('customer-a')['email'] === 'Current@Example.test', 'primary overrides stale legacy');
contactCheck($read('customer-b')['email'] === 'old@example.test', 'legacy-only profile remains readable');
contactCheck(tenantCustomerFindByContact($db, 'company-a', 'email', 'OFFICE@example.test')['id'] === 'customer-a', 'secondary contacts support lookup');
contactCheck(tenantCustomerFindByContact($db, 'company-a', 'email', 'legacy@example.test') === null, 'stale legacy does not override registered primary');
contactCheck(tenantCustomerFindByContact($db, 'company-b', 'email', 'office@example.test') === null, 'lookup cannot cross companies');
contactCheck(tenantCustomerFindByContact($db, 'company-a', 'phone', '22 33 44 55')['id'] === 'customer-b', 'legacy phone lookup normalizes punctuation');
$db->exec("INSERT INTO tenant_customers(id,company_id,email) VALUES('ambiguous','company-a','office@example.test')");
contactExpectInvalid(fn() => tenantCustomerFindByContact($db, 'company-a', 'email', 'office@example.test'), 'shared value never arbitrarily selects a customer');
$db->exec("DELETE FROM tenant_customers WHERE id='ambiguous'");
tenantCustomerSetPrimaryContact($db, 'company-a', 'customer-a', 'email', 'current@example.test');
contactCheck($db->query("SELECT verified_at FROM tenant_customer_contacts WHERE id='email-a'")->fetchColumn() !== null, 'equivalent primary retains verification');
contactCheck((int)$db->query("SELECT COUNT(*) FROM tenant_customer_contacts WHERE customer_id='customer-a' AND normalized_value='legacy@example.test'")->fetchColumn() === 1, 'editing before backfill preserves legacy contact');
tenantCustomerSetPrimaryContact($db, 'company-a', 'customer-a', 'email', 'office@example.test');
contactCheck($read('customer-a')['email'] === 'office@example.test', 'secondary can become primary');
contactCheck((int)$db->query("SELECT SUM(is_primary) FROM tenant_customer_contacts WHERE customer_id='customer-a' AND contact_type='email'")->fetchColumn() === 1, 'single primary after switching');
contactExpectInvalid(fn() => tenantCustomerSetPrimaryContact($db, 'company-a', 'foreign', 'email', 'bad@example.test'), 'foreign primary edit rejected');
contactCheck(!$db->inTransaction(), 'failed owned transaction rolled back');
// Execute actual migration twice; no duplicates, primary replacement or verification changes.
$db->exec("UPDATE tenant_customers SET email='leftover@example.test' WHERE id='customer-a'");
$migration = file_get_contents($root . '/db_migrations/tenant_customer_contacts_backfill.sql');
$runMigration = function() use ($db, $migration): void {
    $query = $db->query($migration);
    do { if ($query->columnCount()) $query->fetchAll(); } while ($query->nextRowset());
    $query->closeCursor();
};
$runMigration();
$count = (int)$db->query('SELECT COUNT(*) FROM tenant_customer_contacts')->fetchColumn();
$runMigration();
contactCheck((int)$db->query('SELECT COUNT(*) FROM tenant_customer_contacts')->fetchColumn() === $count, 'backfill is idempotent');
contactCheck($read('customer-a')['email'] === 'office@example.test', 'backfill preserves chosen primary');
contactCheck($read('customer-b')['email'] === 'old@example.test', 'backfill retains legacy meaning');
contactCheck((int)$db->query("SELECT COUNT(*) FROM (SELECT customer_id,contact_type FROM tenant_customer_contacts GROUP BY customer_id,contact_type HAVING SUM(is_primary)>1) duplicates")->fetchColumn() === 0, 'backfill never creates multiple primaries');
$db->exec("INSERT INTO tenant_customers(id,company_id,first_name,email) VALUES('legacy-ui','company-a','Legacy','fallback@example.test')");
// Fixture-only HTTP server; release PDO before starting a subprocess.
unset($runMigration);
$db = null;
$app = $tmp . '/app';
foreach (['api','config','includes'] as $directory) mkdir($app . '/' . $directory, 0700, true);
foreach (['get_customer_contacts.php','manage_customer_contact.php','update_member.php','get_recipients.php','create_member.php'] as $file) {
    copy($root . '/api/' . $file, $app . '/api/' . $file);
}
symlink($root . '/includes/tenant_customer_contacts.php', $app . '/includes/tenant_customer_contacts.php');
foreach (['active_payment_methods.php','UnifiedLedgerService.php'] as $file) {
    symlink($root . '/includes/' . $file, $app . '/includes/' . $file);
}
file_put_contents($app . '/config/database.php', '<?php function getDBConnection(){return new PDO(' .
    var_export('mysql:unix_socket=' . $socket . ';dbname=fixture', true) . ',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);}');
file_put_contents($app . '/session.php', '<?php session_start(); $_SESSION=["tenant_admin_id"=>"fixture-admin","company_id"=>"company-a"]; echo "ok";');
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
$port = (int) substr(strrchr(stream_socket_get_name($listener, false), ':'), 1);
fclose($listener);
$process = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-S', '127.0.0.1:' . $port, '-t', $app],
    [0=>['pipe','r'],1=>['file',$tmp . '/php.log','a'],2=>['file',$tmp . '/php.log','a']], $pipes, $app);
fclose($pipes[0]);
$base = 'http://127.0.0.1:' . $port;
try {
    for ($attempt = 0; $attempt < 100; $attempt++) {
        if (@file_get_contents($base . '/session.php') !== false) break;
        usleep(50000);
    }
    file_get_contents($base . '/session.php');
    $cookie = '';
    foreach ($http_response_header as $header) {
        if (preg_match('/^Set-Cookie: ([^;]+)/i', $header, $matches)) $cookie = $matches[1];
    }
    contactCheck($cookie !== '', 'fixture session initialized');
    $request = function(string $path, ?array $body = null, bool $authenticated = true) use ($base, $cookie): array {
        $headers = "Content-Type: application/json\r\n" . ($authenticated ? "Cookie: {$cookie}\r\n" : '');
        $context = stream_context_create(['http'=>[
            'method'=>$body === null ? 'GET' : 'POST', 'header'=>$headers,
            'content'=>$body === null ? '' : json_encode($body), 'ignore_errors'=>true, 'timeout'=>10,
        ]]);
        $raw = file_get_contents($base . '/api/' . $path, false, $context);
        $status = (int) explode(' ', $http_response_header[0])[1];
        $result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return [$status, $result];
    };
    $db = contactFixtureDb($socket);
    [$status,$result] = $request('get_customer_contacts.php?customer_id=customer-a', null, false);
    contactCheck($status === 401 && !$result['success'], 'anonymous contact access rejected');
    [$status,$result] = $request('get_customer_contacts.php?customer_id=foreign');
    contactCheck($status === 404 && !$result['success'], 'foreign contact access rejected');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'foreign','action'=>'add','contact_type'=>'email','contact_value'=>'not-allowed@example.test']);
    contactCheck($status === 404 && !$result['success'], 'foreign contact writes rejected');
    [$status,$result] = $request('get_customer_contacts.php?customer_id=legacy-ui');
    contactCheck($result['contacts'][0]['is_legacy'] === true, 'legacy-only profile visible without writes');
    contactCheck((int)$db->query("SELECT COUNT(*) FROM tenant_customer_contacts WHERE customer_id='legacy-ui'")->fetchColumn() === 0, 'GET never imports legacy data');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'legacy-ui','action'=>'add','contact_type'=>'email','contact_value'=>'fallback@example.test','is_primary'=>true]);
    contactCheck($status === 200 && $result['success'], 'legacy value can be adopted through UI');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'add','contact_type'=>'email','contact_value'=>'extra@example.test']);
    contactCheck($status === 200 && $result['success'], 'secondary contact can be added');
    $newId = $db->query("SELECT id FROM tenant_customer_contacts WHERE normalized_value='extra@example.test'")->fetchColumn();
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'add','contact_type'=>'email','contact_value'=>'EXTRA@example.test']);
    contactCheck($status === 409 && !$result['success'], 'normalized duplicate contact rejected');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'primary','contact_id'=>$newId]);
    contactCheck($status === 200 && $result['success'] && $read('customer-a')['email'] === 'extra@example.test', 'API primary selection updates reader');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'edit','contact_id'=>$newId,'contact_value'=>'edited@example.test']);
    contactCheck($status === 200 && $result['success'] && $read('customer-a')['email'] === 'edited@example.test', 'editing primary syncs legacy');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'edit','contact_id'=>'email-a','contact_value'=>'CURRENT@example.test']);
    contactCheck($status === 200 && $db->query("SELECT verified_at FROM tenant_customer_contacts WHERE id='email-a'")->fetchColumn() !== null, 'format-only edit retains verification');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'edit','contact_id'=>'email-a','contact_value'=>'changed@example.test']);
    contactCheck($status === 200 && $db->query("SELECT verified_at FROM tenant_customer_contacts WHERE id='email-a'")->fetchColumn() === null, 'changed value loses old verification');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'delete','contact_id'=>$newId]);
    contactCheck($status === 200 && $result['success'] && $read('customer-a')['email'] !== 'edited@example.test', 'deleting primary promotes remaining contact');
    contactCheck((int)$db->query("SELECT COUNT(*) FROM tenant_customer_contacts WHERE customer_id='customer-a' AND normalized_value='edited@example.test'")->fetchColumn() === 0, 'deleted primary is not recreated from legacy');
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-a','action'=>'add','contact_type'=>'phone','contact_value'=>'...']);
    contactCheck($status === 400 && !$result['success'], 'invalid phone rejected by API');
    [$status,$result] = $request('update_member.php', ['member_id'=>'member-a','email'=>'member-edit@example.test','phone'=>'99 88 77 66']);
    contactCheck($status === 200 && $result['success'] && $read('customer-a')['email'] === 'member-edit@example.test', 'existing member editing writes contact register');
    contactCheck($read('customer-a')['phone'] === '99 88 77 66', 'member phone editing writes contact register');
    [$status,$result] = $request('update_member.php', ['member_id'=>'member-missing','email'=>'not-created@example.test']);
    contactCheck(!$result['success'], 'missing customer link does not create orphan contacts');
    [$status,$result] = $request('get_recipients.php?source=members');
    contactCheck(count($result['recipients']) === 1 && $result['recipients'][0]['email'] === 'member-edit@example.test', 'member email selection uses current primary and eligibility');
    $intake = ['first_name'=>'New','last_name'=>'Member','membership_plan_id'=>'plan-a','payment_status'=>'unpaid','email'=>'new-member@example.test','phone'=>'40 50 60 70'];
    [$status,$result] = $request('create_member.php', $intake);
    contactCheck($status === 200 && $result['success'], 'member registration intake succeeds');
    $createdCustomer = tenantCustomerFindByContact($db, 'company-a', 'email', 'new-member@example.test');
    contactCheck($createdCustomer !== null && (int)$db->query("SELECT COUNT(*) FROM tenant_customer_contacts WHERE customer_id=" . $db->quote($createdCustomer['id']) . " AND is_primary=1")->fetchColumn() === 2, 'registration writes both primary contact types');
    $customersBefore = (int)$db->query('SELECT COUNT(*) FROM tenant_customers')->fetchColumn();
    [$status,$result] = $request('create_member.php', array_replace($intake, ['email'=>'OFFICE@example.test','phone'=>'12345670']));
    contactCheck(!$result['success'] && (int)$db->query('SELECT COUNT(*) FROM tenant_customers')->fetchColumn() === $customersBefore, 'intake rejects an existing secondary email without partial rows');
    $phoneId = $db->query("SELECT id FROM tenant_customer_contacts WHERE customer_id='customer-b' AND contact_type='phone'")->fetchColumn();
    [$status,$result] = $request('manage_customer_contact.php', ['customer_id'=>'customer-b','action'=>'delete','contact_id'=>$phoneId]);
    contactCheck($result['success'] && $read('customer-b')['phone'] === null, 'deleting last phone clears legacy fallback');
    contactCheck((int)$db->query("SELECT COUNT(*) FROM tenant_customer_contacts WHERE customer_id='customer-b' AND contact_type='phone'")->fetchColumn() === 0, 'deleting last contact leaves no residual contact row');
    contactCheck(hash('sha256', serialize($db->query('SELECT * FROM historical_contact_snapshots ORDER BY id')->fetchAll(PDO::FETCH_ASSOC))) === $snapshotHash, 'profile changes leave all historical snapshots untouched');
    echo "Customer contact tests passed ({$checks} checks).\n";
} finally {
    proc_terminate($process);
    proc_close($process);
}
