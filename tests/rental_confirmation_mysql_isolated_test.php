<?php
declare(strict_types=1);

// Use the socket-only disposable MySQL harness, never app database fixtures.
$root = $argv[1] ?? '';
$temp = $argv[2] ?? '';
if (!str_starts_with($temp, sys_get_temp_dir() . '/rental-unavailable-mysql.')
    || !file_exists($temp . '/mysql.sock') || !is_file($root . '/api/update_rental_schedule.php')) {
    throw new RuntimeException('Run the isolated MySQL harness with --email.');
}
require_once __DIR__ . '/smtp_capture_fixture.php';
require_once $root . '/includes/rental_schedule_editing.php';
define('EMAIL_QUEUE_PROCESSOR_NO_AUTO_RUN', true);
require_once $root . '/api/process_email_queue.php';
function checkRentalMail(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$dsn = 'mysql:unix_socket=' . $temp . '/mysql.sock;dbname=rental_mail_test;charset=utf8mb4';
$db = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE rental_mail_test CHARACTER SET utf8mb4');
$db->exec('USE rental_mail_test');
foreach ([
    "CREATE TABLE rental_schedule (
        id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), rental_object_id VARCHAR(36), approved INT DEFAULT 0,
        status VARCHAR(30), start_date DATE, end_date DATE, rental_fee DECIMAL(12,2), customer_id VARCHAR(36),
        pickup_time TIME, return_time TIME, start_time TIME, end_time TIME, key_box_code VARCHAR(6) DEFAULT '1234', logistics VARCHAR(30), notes TEXT,
        cleaning INT DEFAULT 0, price_adjustment_type VARCHAR(20), price_adjustment_cents INT DEFAULT 0,
        price_adjustment_explanation TEXT, customer_name VARCHAR(255), customer_email VARCHAR(255)
    ) ENGINE=InnoDB",
    "CREATE TABLE rental_objects (id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), object_name VARCHAR(255),
        category VARCHAR(30) DEFAULT 'venue', rental_text TEXT, rental_price DECIMAL(12,2), weekend_price DECIMAL(12,2), saturday_price DECIMAL(12,2), sunday_price DECIMAL(12,2))",
    "CREATE TABLE rental_venues (object_id VARCHAR(36), access_code VARCHAR(6), cleaning_fee DECIMAL(12,2))",
    "CREATE TABLE tenant_customers (id VARCHAR(36), company_id VARCHAR(36), email VARCHAR(255), first_name VARCHAR(255), last_name VARCHAR(255))",
    "CREATE TABLE tenant_companies (id VARCHAR(36), name VARCHAR(255), logo_url TEXT, contact_phone VARCHAR(100),
        address_street VARCHAR(255), address_zip VARCHAR(20), address_city VARCHAR(255), org_number VARCHAR(30),is_active INT DEFAULT 1)",
    "CREATE TABLE tenant_staff (id VARCHAR(36),company_id VARCHAR(36),first_name VARCHAR(255),last_name VARCHAR(255))",
    "CREATE TABLE tenant_admins (id VARCHAR(36),company_id VARCHAR(36),tenant_staff_id VARCHAR(36),username VARCHAR(255),role VARCHAR(50),is_active INT)",
    "CREATE TABLE system_modules (id VARCHAR(36),module_key VARCHAR(50),is_active INT)",
    "CREATE TABLE tenant_admin_modules (tenant_admin_id VARCHAR(36),module_id VARCHAR(36))",
    "CREATE TABLE audit_logs (id CHAR(36) PRIMARY KEY,company_id VARCHAR(36),user_id VARCHAR(36),user_type VARCHAR(20),
        user_email VARCHAR(255),action VARCHAR(50),resource_type VARCHAR(100),resource_id VARCHAR(36),resource_name VARCHAR(255),
        old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50)) ENGINE=InnoDB",
    "CREATE TABLE tenant_website_general (company_id VARCHAR(36), logo_url TEXT, header_bg_color VARCHAR(30))",
    "CREATE TABLE economy_bank_accounts (company_id VARCHAR(36), account_number VARCHAR(100), is_active INT, bank_name VARCHAR(255))",
    "CREATE TABLE email_configs (company_id VARCHAR(36), email VARCHAR(255), display_name VARCHAR(255), automail_rental_confirm INT, is_active INT)",
    "CREATE TABLE email_templates (id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), template_key VARCHAR(100),
        subject_template TEXT, body_template LONGTEXT, is_active INT)",
    "CREATE TABLE email_queue (id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), recipient_email VARCHAR(255),
        from_email VARCHAR(255), subject TEXT, body LONGTEXT, template_id VARCHAR(36), module_source VARCHAR(100),
        source_id VARCHAR(36), status VARCHAR(30), priority INT, scheduled_at DATETIME, created_at DATETIME, updated_at DATETIME) ENGINE=InnoDB",
] as $sql) $db->exec($sql);
$bodyA = "<html><body><h1>{{company_name}}: {{customer_name}}</h1>\n"
    . '{{#if (eq status "Godkjent")}}<p>Viktig: Betalingen må være registrert.</p>'
    . '<table role="presentation" width="100%"><tr><td>{{date_from}} – {{date_to}}</td>'
    . '<td>{{notes}}</td><td>{{price_adjustment_explanation}}</td></tr></table>'
    . '{{else}}<p>Leieforespørselen din er avslått.</p>{{/if}}'
    . '<p>Fast kontakttekst &amp; {{ukjent_tag}}</p></body></html>';
$bodyB = str_replace('Fast kontakttekst', 'Bedrift B sin faste tekst', $bodyA);
foreach (['a', 'b'] as $company) {
    $db->prepare('INSERT INTO tenant_companies (id,name) VALUES (?,?)')->execute([$company, 'Testbedrift ' . $company . ' æøå']);
    $db->prepare("INSERT INTO rental_objects (id,company_id,object_name,rental_price) VALUES (?,?,?,100)")
        ->execute(['object-' . $company, $company, 'Testlokale']);
    $db->prepare("INSERT INTO email_configs VALUES (?, 'admin@example.invalid', 'Testbedrift', 1, 1)")->execute([$company]);
    $db->prepare("INSERT INTO email_templates VALUES (?,?, 'rental_confirmation', 'Din leie: {{customer_name}}', ?,1)")
        ->execute(['template-' . $company, $company, $company === 'a' ? $bodyA : $bodyB]);
    $db->prepare("INSERT INTO tenant_staff VALUES (?,?,?,?)")->execute(['person-' . $company, $company, 'Hovedadmin', $company]);
    $db->prepare("INSERT INTO tenant_admins VALUES (?,?,?,?,'admin',1)")
        ->execute(['fixture-admin-' . $company, $company, 'person-' . $company, 'admin-' . $company]);
}
$db->exec("INSERT INTO tenant_staff VALUES ('person-staff-a','a','Ansatt','Test')");
$db->exec("INSERT INTO tenant_admins VALUES ('fixture-staff-a','a','person-staff-a','staff-a','staff',1)");
$db->exec("INSERT INTO system_modules VALUES ('rental','rental',1)");
$db->exec("INSERT INTO tenant_admin_modules VALUES ('fixture-admin-a','rental'),('fixture-admin-b','rental'),('fixture-staff-a','rental')");
foreach (['one', 'two', 'rejected', 'other', 'invalid'] as $index => $id) {
    $company = $id === 'other' ? 'b' : 'a';
    $day = 1 + $index * 3;
    $db->prepare("INSERT INTO rental_schedule
        (id,company_id,rental_object_id,status,start_date,end_date,rental_fee,pickup_time,end_time,customer_name,customer_email,notes)
        VALUES (?,?,?,'pending',?,?,100,'08:00:00','18:00:00',?,'admin@example.invalid',?)")->execute([
        $id, $company, 'object-' . $company, sprintf('2030-01-%02d', $day), sprintf('2030-01-%02d', $day + 1),
        $id === 'one' ? '<img src=x onerror=alert(1)> & "Kunde"' : 'Testkunde ' . $id,
        '<table role="presentation">kundens tekst</table>',
    ]);
}
mkdir($temp . '/app/api', 0700, true);
mkdir($temp . '/app/config', 0700, true);
symlink($root . '/includes', $temp . '/app/includes');
copy($root . '/api/update_rental_schedule.php', $temp . '/app/api/update_rental_schedule.php');
copy($root . '/api/get_audit_logs.php', $temp . '/app/api/get_audit_logs.php');
file_put_contents($temp . '/app/config/database.php', '<?php function getDBConnection() { return new PDO('
    . var_export($dsn, true) . ', "root", "", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }');
ini_set('session.save_path', $temp . '/sessions');
foreach (['a', 'b'] as $company) {
    session_id('mail-fixture-' . $company);
    session_start();
    $_SESSION = ['company_id' => $company, 'tenant_admin_id' => 'fixture-admin-' . $company, 'rental_lifecycle_csrf'=>'fixture-csrf'];
    session_write_close();
}
session_id('mail-fixture-staff-a');
session_start();
$_SESSION = ['company_id' => 'a','tenant_admin_id' => 'fixture-staff-a',
    'admin_name' => 'Forged main admin','admin_role' => 'admin','rental_lifecycle_csrf'=>'fixture-csrf'];
session_write_close();
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$address = stream_socket_get_name($listener, false);
fclose($listener);
$process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . $temp . '/sessions',
    '-S', $address, '-t', $temp . '/app'], [
    0 => ['file', '/dev/null', 'r'], 1 => ['file', $temp . '/http.log', 'a'], 2 => ['file', $temp . '/http.log', 'a'],
], $pipes);
if (!is_resource($process)) throw new RuntimeException('Fixture API server could not start');
try {
    $ready = false;
    for ($i = 0; $i < 100; $i++) {
        $socket = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
        if ($socket) { fclose($socket); $ready = true; break; }
        usleep(20000);
    }
    checkRentalMail($ready, 'Fixture API server readiness');
    $request = function(string $id, string $company, string $status, string $sessionKey = '') use ($address,$db): array {
        $q=$db->prepare('SELECT * FROM rental_schedule WHERE id=?'); $q->execute([$id]);
        $version=rentalDecisionVersion($q->fetch(PDO::FETCH_ASSOC));
        $curl = curl_init('http://' . $address . '/api/update_rental_schedule.php');
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['id' => $id, 'status' => $status,
                'csrf_token'=>'fixture-csrf','expected_version'=>$version]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Cookie: PHPSESSID=mail-fixture-' . ($sessionKey ?: $company)],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($curl);
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [$http, json_decode((string) $response, true)];
    };
    foreach (['one', 'two', 'rejected', 'other'] as $id) {
        [$http, $result] = $request($id, $id === 'other' ? 'b' : 'a', $id === 'rejected' ? 'rejected' : 'approved',
            $id === 'two' ? 'staff-a' : '');
        checkRentalMail($http === 200 && $result['success'] && $result['email_queued'], 'Actual API decision and queue insert: ' . $id . ' response=' . json_encode($result));
    }
    $emails = $db->query('SELECT * FROM email_queue ORDER BY source_id')->fetchAll(PDO::FETCH_ASSOC);
    checkRentalMail(count($emails) === 4, 'Exactly one email per decision including self-approval');
    foreach ($emails as $email) {
        $body = emailQueueBodyForDelivery($email, 'Should not replace', '', '#ffffff');
        checkRentalMail($body === $email['body'], 'Queue processor preserves original template output');
        checkRentalMail(str_contains($body, '{{ukjent_tag}}')
            && str_contains($body, '&lt;table role=') === ($email['source_id'] !== 'rejected'),
            'Unknown tags and customer HTML preserved as safe text only in the selected branch');
        if ($email['source_id'] === 'one') {
            checkRentalMail(str_contains($body, '&lt;img') && !str_contains($body, '<img'), 'Customer-name HTML remains text');
        }
        checkRentalMail(str_contains($body, 'Betalingen') === ($email['source_id'] !== 'rejected'), 'Conditional payment section');
        checkRentalMail(str_contains($body, 'Bedrift B sin faste tekst') === ($email['company_id'] === 'b'), 'Correct company-specific template');
        checkFixtureSmtp(captureFixtureSmtp($body, $email['subject']), $body);
    }
    $audits = $db->query('SELECT * FROM audit_logs')->fetchAll(PDO::FETCH_ASSOC);
    checkRentalMail(count($audits) === 8, 'Decision and queue audit are committed together');
    foreach ($audits as $audit) {
        $values = json_decode($audit['new_values'], true);
        $meta = $values['_audit'];
        $source = $meta['links']['source_id'] ?? '';
        $expectedActor = $source === 'two' ? 'fixture-staff-a' : 'fixture-admin-' . $audit['company_id'];
        checkRentalMail($audit['user_id'] === $expectedActor, 'Actual acting administrator, never the company owner');
        checkRentalMail($meta['actor_name'] === ($source === 'two' ? 'Ansatt Test' : 'Hovedadmin ' . $audit['company_id']),
            'Server-resolved actor name, not a client/session display label');
        checkRentalMail(!str_contains($audit['new_values'], 'onerror') && !str_contains($audit['new_values'], 'kundens tekst')
            && !str_contains($audit['new_values'], 'recipient_email'), 'Audit excludes customer text and complete email bodies');
    }
    $readAudit = function(array $params, string $sessionKey) use ($address): array {
        $curl = curl_init('http://' . $address . '/api/get_audit_logs.php?' . http_build_query($params));
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Cookie: PHPSESSID=mail-fixture-' . $sessionKey], CURLOPT_TIMEOUT => 10]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [$status,json_decode((string) $body,true)];
    };
    [$http,$list] = $readAudit([], 'a');
    checkRentalMail($http === 200 && $list['pagination']['total'] === 6, 'Company-scoped audit list');
    $foreign = array_values(array_filter($audits, static fn($a) => $a['company_id'] === 'b'))[0];
    checkRentalMail($readAudit(['id'=>$foreign['id']], 'a')[0] === 404, 'Foreign audit detail ID is inaccessible');
    checkRentalMail($readAudit([], 'staff-a')[0] === 403, 'Staff cannot elevate audit-read access through session role');
    checkRentalMail($readAudit(['admin_id'=>'fixture-admin-b'], 'a')[1]['pagination']['total'] === 0,
        'Administrator filter cannot cross company boundary');
    checkRentalMail($readAudit(['from'=>'2030-02-31'], 'a')[0] === 400, 'Strict date-filter validation');
    [$http,$result] = $request('one','a','approved');
    checkRentalMail($http === 409 && $db->query("SELECT COUNT(*) FROM email_queue")->fetchColumn() == 4,
        'Repeated approval records a no-op without duplicate queue insertion');
    foreach (['< table role=', '&lt;table role=', '&amp;lt;table role='] as $broken) {
        $db->prepare("UPDATE email_templates SET body_template=? WHERE id='template-a'")
            ->execute([str_replace('<table role=', $broken, $bodyA)]);
        [$http, $result] = $request('invalid', 'a', 'approved');
        checkRentalMail($http === 422 && !$result['success'] && str_contains($result['message'], 'tabellkode'), 'Actionable template error reaches API caller');
        checkRentalMail($db->query("SELECT status FROM rental_schedule WHERE id='invalid'")->fetchColumn() === 'pending'
            && (int) $db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 4, 'Bad template rolls back decision and queues nothing');
    }
    checkRentalMail((int) $db->query("SELECT COUNT(*) FROM audit_logs WHERE action='email.queued'")->fetchColumn() === 4,
        'Failed approvals roll back their successful/queue audit entries');
    $db->exec("UPDATE tenant_staff SET first_name='Omdøpt' WHERE id='person-staff-a'");
    $db->exec("DELETE FROM tenant_admins WHERE id='fixture-staff-a'");
    $db->exec("UPDATE tenant_companies SET name='Nytt bedriftsnavn' WHERE id='a'");
    [$http,$list] = $readAudit(['admin_id'=>'fixture-staff-a'],'a');
    checkRentalMail($http === 200 && count($list['logs']) === 2
        && $list['logs'][0]['actor_name'] === 'Ansatt Test'
        && $list['logs'][0]['company_name'] === 'Testbedrift a æøå', 'Historic snapshots survive renaming and actor deletion');
    // Exercise the actual queue processor against loopback SMTP, including a post-acceptance audit outage.
    $db->exec("ALTER TABLE email_queue ADD sent_at DATETIME NULL,ADD error_message TEXT,ADD attempts INT DEFAULT 0,ADD max_attempts INT DEFAULT 3");
    $db->exec("ALTER TABLE email_configs ADD smtp_address VARCHAR(255),ADD smtp_host VARCHAR(255),ADD smtp_port INT,
        ADD smtp_username VARCHAR(255),ADD smtp_password_encrypted VARCHAR(255)");
    $db->exec("CREATE TABLE email_logs (id VARCHAR(36),company_id VARCHAR(36),from_email VARCHAR(255),from_name VARCHAR(255),
        to_email VARCHAR(255),subject TEXT,body_html LONGTEXT,module_source VARCHAR(100),status VARCHAR(30),error_message TEXT,sent_at DATETIME)");
    $smtpProcess = proc_open([PHP_BINARY, $root . '/tests/smtp_capture_fixture.php','--smtp-capture-worker'],
        [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']], $smtpPipes);
    checkRentalMail(is_resource($smtpProcess), 'Queue SMTP worker started');
    try {
        $smtpPort = (int) fgets($smtpPipes[1]);
        $db->prepare("UPDATE email_configs SET smtp_address='127.0.0.1',smtp_host='127.0.0.1',smtp_port=?,
            smtp_username='fixture-user',smtp_password_encrypted='fixture-password' WHERE company_id='a'")->execute([$smtpPort]);
        $queued = $db->query("SELECT * FROM email_queue WHERE company_id='a' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $db->exec('DROP TABLE audit_logs');
        ob_start();
        processEmail($db,$queued);
        ob_end_clean();
        $state = $db->query("SELECT status,attempts FROM email_queue WHERE id=" . $db->quote($queued['id']))->fetch(PDO::FETCH_ASSOC);
        checkRentalMail($state['status'] === 'sent' && (int)$state['attempts'] === 0,
            'Audit failure after SMTP acceptance never requeues or increments retry count');
        checkFixtureSmtp((string) stream_get_contents($smtpPipes[1]), $queued['body']);
    } finally {
        fclose($smtpPipes[1]); fclose($smtpPipes[2]); proc_terminate($smtpProcess); proc_close($smtpProcess);
    }
    echo "Isolated rental API → MySQL queue → loopback SMTP checks passed\n";
} finally {
    proc_terminate($process);
    proc_close($process);
}