<?php
declare(strict_types=1);

// Only the disposable, socket-only database created by our shell harness.
$root = $argv[1] ?? '';
$temp = $argv[2] ?? '';
if ($root === '' || !str_starts_with($temp, sys_get_temp_dir() . '/rental-unavailable-mysql.')
    || !is_file($root . '/api/rental_unavailable.php') || !file_exists($temp . '/mysql.sock')) {
    throw new RuntimeException('Run through the isolated rental MySQL harness.');
}
require_once $root . '/includes/rental_unavailable_rules.php';
function checkUnavailable(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$dsn = 'mysql:unix_socket=' . $temp . '/mysql.sock;dbname=rental_unavailable_test;charset=utf8mb4';
$db = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$db->exec('CREATE DATABASE rental_unavailable_test CHARACTER SET utf8mb4');
$db->exec('USE rental_unavailable_test');
$db->exec("CREATE TABLE rental_objects (
    id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), object_name VARCHAR(100), created_by VARCHAR(36)
) ENGINE=InnoDB");
$db->exec("CREATE TABLE rental_schedule (
    id VARCHAR(36) PRIMARY KEY, company_id VARCHAR(36), rental_object_id VARCHAR(36),
    customer_id VARCHAR(36) NULL, customer_name VARCHAR(100) NULL,
    customer_email VARCHAR(100) NULL, customer_phone VARCHAR(100) NULL,
    start_date DATE, start_time TIME, end_date DATE, end_time TIME,
    rental_fee DECIMAL(12,2), status VARCHAR(20), notes TEXT NULL,
    created_by VARCHAR(36), approved INT, pickup_time TIME,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY ix_company_object_dates(company_id, rental_object_id, start_date, end_date)
) ENGINE=InnoDB");
$db->exec("INSERT INTO rental_objects VALUES
    ('object-a1','company-a','Sal A','staff-a'),
    ('object-a2','company-a','Sal B','staff-a'),
    ('object-b1','company-b','Privat sal','staff-b')");

// Run the actual endpoint in separate PHP processes, replacing ONLY its database
// bootstrap and resolving include paths. No app config or secrets are loaded.
$source = file_get_contents($root . '/api/rental_unavailable.php');
$bootstrap = 'function getDBConnection() { return new PDO(' . var_export($dsn, true)
    . ', "root", "", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,'
    . 'PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }';
$source = str_replace("require_once __DIR__ . '/../config/database.php';", $bootstrap, $source, $count);
checkUnavailable($count === 1, 'Endpoint database bootstrap changed; update the test safely.');
foreach (['request_date_time.php', 'rental_unavailable_rules.php'] as $file) {
    $source = str_replace("__DIR__ . '/../includes/$file'", var_export("$root/includes/$file", true), $source);
}
file_put_contents($temp . '/endpoint.php', $source);
file_put_contents($temp . '/worker.php', '<?php
session_save_path($argv[1]."/sessions");
session_id($argv[2]);
date_default_timezone_set("America/Los_Angeles");
$_SERVER["REQUEST_METHOD"]=$argv[3];
$_POST=json_decode(base64_decode($argv[4]),true);
if($argv[5]!=="none")$_SERVER["HTTP_X_CSRF_TOKEN"]=$argv[5];
register_shutdown_function(function(){echo "\n".(http_response_code()?:200);});
require $argv[1]."/endpoint.php";
');
session_save_path($temp . '/sessions');
foreach (['a', 'b'] as $tenant) {
    session_id('rental-test-' . $tenant);
    session_start();
    $_SESSION = [
        'tenant_admin_id' => 'staff-' . $tenant,
        'company_id' => 'company-' . $tenant,
        'rental_lifecycle_csrf' => 'fixture-csrf',
    ];
    session_write_close();
}
function callUnavailable(string $method, array $data = [], string $tenant = 'a', string $csrf = 'fixture-csrf'): array {
    global $temp;
    $process = proc_open([
        PHP_BINARY, $temp . '/worker.php', $temp, 'rental-test-' . $tenant,
        $method, base64_encode(json_encode($data, JSON_THROW_ON_ERROR)), $csrf,
    ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start endpoint worker');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    checkUnavailable($exit === 0 && $stderr === '', 'Endpoint error: ' . $stderr);
    $parts = explode("\n", trim($stdout));
    return [json_decode($parts[0], true, 512, JSON_THROW_ON_ERROR), (int) end($parts)];
}
$today = rentalUnavailableToday();
$day = new DateTimeImmutable($today, new DateTimeZone('Europe/Oslo'));
$future = $day->modify('+10 days')->format('Y-m-d');
$next = $day->modify('+11 days')->format('Y-m-d');
$old = $day->modify('-1 day')->format('Y-m-d');
$make = ['rental_object_ids' => ['object-a1', 'object-a2'], 'start_date' => $future,
    'end_date' => $next, 'notes' => '  Vedlikehold – blå sal  '];
[$result, $status] = callUnavailable('POST', $make);
checkUnavailable($status === 200 && $result['success'], 'Multi-object creation failed');
$created = $db->query("SELECT * FROM rental_schedule ORDER BY rental_object_id")->fetchAll(PDO::FETCH_ASSOC);
checkUnavailable(count($created) === 2, 'Multi-object creation must save two periods');
foreach ($created as $row) {
    checkUnavailable($row['notes'] === 'Vedlikehold – blå sal' && $row['customer_id'] === null
        && (float) $row['rental_fee'] === 0.0 && $row['status'] === 'unavailable',
        'Notes, customerless status and zero-price semantics must be preserved');
}
$id = $created[0]['id'];
[$result, $status] = callUnavailable('PUT', [
    'id' => $id, 'rental_object_ids' => ['object-a1'], 'start_date' => $future,
    'end_date' => $future, 'notes' => '<b>Åpen tekst</b>',
]);
checkUnavailable($status === 200 && $result['success'], 'Single-day edit failed');
$notesQuery = $db->prepare('SELECT notes FROM rental_schedule WHERE id=?');
$notesQuery->execute([$id]);
checkUnavailable($notesQuery->fetchColumn() === '<b>Åpen tekst</b>', 'Notes must be stored as text, not rendered HTML');
[$result, $status] = callUnavailable('PUT', [
    'id' => $id, 'rental_object_ids' => ['object-a1'], 'start_date' => $future, 'end_date' => $future,
]);
checkUnavailable($status === 200 && $result['success'], 'Date-only legacy edit failed');
$notesQuery->execute([$id]);
checkUnavailable($notesQuery->fetchColumn() === '<b>Åpen tekst</b>', 'Omitted notes must not erase existing text');
[$result, $status] = callUnavailable('PUT', [
    'id' => $id, 'rental_object_ids' => ['object-a1'], 'start_date' => $future,
    'end_date' => $future, 'notes' => '',
]);
checkUnavailable($status === 200 && $result['success'], 'Explicitly clearing notes failed');
$notesQuery->execute([$id]);
checkUnavailable($notesQuery->fetchColumn() === null, 'Empty notes should be NULL');

// Conflict, tenant and CSRF checks exercise actual MySQL transactions and locks.
[$result, $status] = callUnavailable('POST', $make);
checkUnavailable($status === 400 && !$result['success'], 'Overlapping block must be rejected');
checkUnavailable((int) $db->query('SELECT COUNT(*) FROM rental_schedule')->fetchColumn() === 2,
    'Conflict must roll back without partially inserting');
[$result, $status] = callUnavailable('POST', array_replace($make, ['rental_object_ids' => ['object-a1', 'object-b1']]));
checkUnavailable($status === 400 && !$result['success'], 'Cross-tenant object creation must be rejected');
[$result, $status] = callUnavailable('PUT', ['id' => $id, 'rental_object_ids' => ['object-b1'],
    'start_date' => $future, 'end_date' => $future, 'notes' => 'Leaked'], 'b');
checkUnavailable($status === 400 && !$result['success'], 'Cross-tenant period editing must be rejected');
[$result, $status] = callUnavailable('DELETE', ['id' => $id], 'b');
checkUnavailable($status === 404 && !$result['success'], 'Cross-tenant deletion must be rejected');
foreach (['POST', 'PUT', 'DELETE'] as $method) {
    [$result, $status] = callUnavailable($method, $make, 'a', 'none');
    checkUnavailable($status === 403 && !$result['success'], "$method without CSRF must be rejected");
}
[$result, $status] = callUnavailable('GET', [], 'anonymous');
checkUnavailable($status === 401 && !$result['success'], 'Anonymous access must be rejected');
foreach ([str_repeat('å', 501), ['invalid'], 123] as $notes) {
    [$result, $status] = callUnavailable('POST', array_replace($make, ['notes' => $notes]));
    checkUnavailable($status === 400 && !$result['success'], 'Invalid/overlong notes must be rejected');
}
[$result, $status] = callUnavailable('POST', array_replace($make, [
    'start_date' => $next, 'end_date' => $future,
]));
checkUnavailable($status === 400 && !$result['success'], 'Reversed period must be rejected');

// Expiry is by end date, inclusive, with historical rows preserved.
$insert = $db->prepare("INSERT INTO rental_schedule
    (id,company_id,rental_object_id,start_date,end_date,status,approved,notes)
    VALUES (?,?,?, ?,?,'unavailable',0,?)");
$insert->execute(['expired', 'company-a', 'object-a1', $old, $old, 'Gammel']);
$insert->execute(['today', 'company-a', 'object-a1', $today, $today, 'Stengt i dag']);
$insert->execute(['ongoing', 'company-a', 'object-a2', $old, $today, 'Pågår']);
$insert->execute(['tenant-b', 'company-b', 'object-b1', $today, $today, 'Privat']);
[$result, $status] = callUnavailable('GET');
checkUnavailable($status === 200 && $result['success'] && $result['today'] === $today,
    'GET must expose the Oslo day independently of the worker timezone');
$visible = array_column($result['periods'], 'id');
checkUnavailable(!in_array('expired', $visible, true) && !in_array('tenant-b', $visible, true)
    && in_array('today', $visible, true) && in_array('ongoing', $visible, true),
    'Expiry must hide only ended periods and preserve today/ongoing with tenant isolation');
checkUnavailable(array_key_exists('notes', $result['periods'][0]), 'GET must return notes');
checkUnavailable((int) $db->query("SELECT COUNT(*) FROM rental_schedule WHERE id='expired'")->fetchColumn() === 1,
    'Automatic expiry must retain history');
$db->exec("INSERT INTO rental_schedule
    (id,company_id,rental_object_id,start_date,end_date,status,approved)
    VALUES ('approved','company-a','object-a1','2099-04-01','2099-04-02','approved',1)");
[$result, $status] = callUnavailable('POST', [
    'rental_object_ids' => ['object-a1'], 'start_date' => '2099-04-01', 'end_date' => '2099-04-01',
]);
checkUnavailable($status === 400 && !$result['success'], 'Approved rentals must still block closures');
[$result, $status] = callUnavailable('DELETE', ['id' => 'approved']);
checkUnavailable($status === 404 && !$result['success'], 'Closure deletion must never delete a rental');
[$result, $status] = callUnavailable('DELETE', ['id' => $id]);
checkUnavailable($status === 200 && $result['success'], 'Owned closure deletion failed');
checkUnavailable((int) $db->query("SELECT COUNT(*) FROM rental_schedule WHERE id=" . $db->quote($id))->fetchColumn() === 0,
    'Explicit deletion must remove the selected owned closure');
checkUnavailable(rentalUnavailableToday(new DateTimeImmutable('2026-10-02T22:30:00+00:00')) === '2026-10-03',
    'Expiry must use the Oslo day around UTC midnight');
checkUnavailable(rentalUnavailableToday(new DateTimeImmutable('2026-01-02T23:30:00+00:00')) === '2026-01-03',
    'Expiry must also use the Oslo day in winter');
echo "Isolated rental unavailable MySQL endpoint tests passed (no app database used)\n";