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
        pickup_time TIME, start_time TIME, end_time TIME, logistics VARCHAR(30), notes TEXT,
        cleaning INT DEFAULT 0, price_adjustment_type VARCHAR(20), price_adjustment_cents INT DEFAULT 0,
        price_adjustment_explanation TEXT, customer_name VARCHAR(255), customer_email VARCHAR(255)
    ) ENGINE=InnoDB",
    "CREATE TABLE rental_objects (id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), object_name VARCHAR(255),
        rental_text TEXT, rental_price DECIMAL(12,2), weekend_price DECIMAL(12,2), saturday_price DECIMAL(12,2), sunday_price DECIMAL(12,2))",
    "CREATE TABLE rental_venues (object_id VARCHAR(36), cleaning_fee DECIMAL(12,2))",
    "CREATE TABLE tenant_customers (id VARCHAR(36), company_id VARCHAR(36), email VARCHAR(255), first_name VARCHAR(255), last_name VARCHAR(255))",
    "CREATE TABLE tenant_companies (id VARCHAR(36), name VARCHAR(255), logo_url TEXT, contact_phone VARCHAR(100),
        address_street VARCHAR(255), address_zip VARCHAR(20), address_city VARCHAR(255), org_number VARCHAR(30))",
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
}
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
file_put_contents($temp . '/app/config/database.php', '<?php function getDBConnection() { return new PDO('
    . var_export($dsn, true) . ', "root", "", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }');
ini_set('session.save_path', $temp . '/sessions');
foreach (['a', 'b'] as $company) {
    session_id('mail-fixture-' . $company);
    session_start();
    $_SESSION = ['company_id' => $company, 'tenant_admin_id' => 'fixture-admin-' . $company];
    session_write_close();
}
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
    $request = function(string $id, string $company, string $status) use ($address): array {
        $curl = curl_init('http://' . $address . '/api/update_rental_schedule.php');
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['id' => $id, 'status' => $status]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Cookie: PHPSESSID=mail-fixture-' . $company],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($curl);
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [$http, json_decode((string) $response, true)];
    };
    foreach (['one', 'two', 'rejected', 'other'] as $id) {
        [$http, $result] = $request($id, $id === 'other' ? 'b' : 'a', $id === 'rejected' ? 'rejected' : 'approved');
        checkRentalMail($http === 200 && $result['success'] && $result['email_queued'], 'Actual API decision and queue insert: ' . $id);
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
    foreach (['< table role=', '&lt;table role=', '&amp;lt;table role='] as $broken) {
        $db->prepare("UPDATE email_templates SET body_template=? WHERE id='template-a'")
            ->execute([str_replace('<table role=', $broken, $bodyA)]);
        [$http, $result] = $request('invalid', 'a', 'approved');
        checkRentalMail($http === 422 && !$result['success'] && str_contains($result['message'], 'tabellkode'), 'Actionable template error reaches API caller');
        checkRentalMail($db->query("SELECT status FROM rental_schedule WHERE id='invalid'")->fetchColumn() === 'pending'
            && (int) $db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 4, 'Bad template rolls back decision and queues nothing');
    }
    echo "Isolated rental API → MySQL queue → loopback SMTP checks passed\n";
} finally {
    proc_terminate($process);
    proc_close($process);
}