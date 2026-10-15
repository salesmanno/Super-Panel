<?php
declare(strict_types=1);

// Socket-only disposable database created by the isolated test runner.
// Never load the application's database configuration here.
$root = $argv[1] ?? '';
$temp = $argv[2] ?? '';
if ($temp === '' || !str_contains(basename($temp), 'rental-unavailable-mysql.')
    || !file_exists($temp . '/mysql.sock')) {
    throw new RuntimeException('Use the isolated MySQL test runner.');
}
$db = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock;dbname=sales_register_schema_test;charset=utf8mb4',
    'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
$db->exec("SET time_zone='+00:00'");

function srId(): string {
    $h = bin2hex(random_bytes(16));
    return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-4' . substr($h, 13, 3)
        . '-a' . substr($h, 17, 3) . '-' . substr($h, 20);
}
function srQuery(PDO $db, string $sql, array $params = []): PDOStatement {
    $q = $db->prepare($sql);
    $q->execute($params);
    return $q;
}
function srEvent(PDO $db, string $company, string $register, ?string $period, array $extra = []): array {
    $data = array_replace(['id' => srId(), 'company_id' => $company, 'register_id' => $register,
        'period_id' => $period, 'event_type' => 'test.event', 'idempotency_key' => srId(),
        'actor_id' => null, 'order_id' => null, 'payload' => '{"name": "Prøvevare", "amount_minor":10000}'], $extra);
    srQuery($db, 'INSERT INTO sales_register_journal
        (id,company_id,register_id,period_id,event_type,idempotency_key,actor_id,order_id,payload,sequence_number)
        VALUES (?,?,?,?,?,?,?,?,?,999)', array_values($data));
    return srQuery($db, 'SELECT * FROM sales_register_journal WHERE id=?', [$data['id']])->fetch(PDO::FETCH_ASSOC);
}
if (($argv[3] ?? '') === '--worker') {
    for ($i = 0; $i < 10; $i++) {
        srEvent($db, $argv[4], $argv[5], null, [
            'event_type' => 'test.concurrent', 'idempotency_key' => $argv[6] . '-' . $i]);
    }
    echo "worker ok\n";
    exit(0);
}

$checks = 0;
function srAssert(bool $condition, string $message): void {
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function srFails(callable $fn, string $message, ?string $state = null): void {
    try {
        $fn();
    } catch (PDOException $e) {
        srAssert($state === null || $e->getCode() === $state, $message . ': unexpected SQLSTATE ' . $e->getCode());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $message);
}
function srRegister(PDO $db, string $company, string $code): string {
    $id = srId();
    srQuery($db, 'INSERT INTO sales_registers
        (id,company_id,register_code,name,system_identifier,system_model,system_version,created_at_utc)
        VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP(6))',
        [$id, $company, $code, 'Isolert testkasse', srId(), 'Salesman fixture', 'test-1']);
    return $id;
}
function srPeriod(PDO $db, string $company, string $register, int $float = 50000): string {
    $id = srId();
    srQuery($db, 'INSERT INTO sales_register_periods
        (id,company_id,register_id,business_date,opening_float_minor,opened_by)
        VALUES (?,?,?,?,?,?)', [$id, $company, $register, '2026-10-15', $float, srId()]);
    return $id;
}
function srDocument(PDO $db, string $company, string $register, string $period, string $type,
    int $amount, ?string $source = null, array $extra = []): array {
    $db->beginTransaction();
    try {
        $event = srEvent($db, $company, $register, $period, ['event_type' => 'document.' . $type]);
        $data = array_replace(['id' => srId(), 'company_id' => $company, 'register_id' => $register,
            'period_id' => $period, 'journal_id' => $event['id'], 'document_type' => $type,
            'currency' => 'NOK', 'total_minor' => $amount,
            'payload' => '{"name": "Prøvevare", "vat": {"rate":25}, "amount_minor":' . $amount . '}',
            'source_document_id' => $source], $extra);
        srQuery($db, 'INSERT INTO sales_register_documents
            (id,company_id,register_id,period_id,journal_id,document_type,currency,total_minor,payload,source_document_id)
            VALUES (?,?,?,?,?,?,?,?,?,?)', array_values($data));
        $document = srQuery($db, 'SELECT * FROM sales_register_documents WHERE id=?',
            [$data['id']])->fetch(PDO::FETCH_ASSOC);
        $db->commit();
        return $document;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}
function srHead(PDO $db, string $id): array {
    return srQuery($db, 'SELECT last_journal_number,last_journal_hash,last_document_number,
        last_receipt_number,last_training_number,last_z_number FROM sales_registers WHERE id=?',
        [$id])->fetch(PDO::FETCH_ASSOC);
}

srAssert((int)$db->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn() === 4,
    'Exactly four new tables');
srAssert((int)$db->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchColumn() === 12,
    'All twelve triggers installed');

$companyA = srId();
$companyB = srId();
$registerA = srRegister($db, $companyA, 'MOBILE-1');
$registerB = srRegister($db, $companyB, 'MOBILE-1');
$registerA2 = srRegister($db, $companyA, 'MOBILE-2');
$settings = srQuery($db, 'SELECT operation_mode,status FROM sales_registers WHERE id=?', [$registerA])->fetch(PDO::FETCH_ASSOC);
srAssert($settings === ['operation_mode' => 'mobile', 'status' => 'draft'], 'Mobile, not automatically activated');
srFails(fn() => srRegister($db, $companyA, 'MOBILE-1'), 'Duplicate company register code', '23000');

$periodA = srPeriod($db, $companyA, $registerA);
$periodB = srPeriod($db, $companyB, $registerB);
$periodA2 = srPeriod($db, $companyA, $registerA2);
srFails(fn() => srPeriod($db, $companyA, $registerA), 'Only one open period', '23000');
srFails(fn() => srPeriod($db, $companyB, $registerA), 'Foreign company register', '45000');
srFails(fn() => srPeriod($db, $companyA, $registerA, -1), 'Negative opening float', '45000');
srAssert((int)srQuery($db, 'SELECT last_period_number FROM sales_registers WHERE id=?', [$registerA])->fetchColumn() === 1,
    'Rejected period does not advance its number');

$event = srEvent($db, $companyA, $registerA, $periodA);
srAssert((int)$event['sequence_number'] === 1 && $event['previous_hash'] === null, 'First chain event');
srAssert($event['payload_sha256'] === hash('sha256', $event['payload']), 'Exact UTF-8 payload hash');
srAssert($event['system_version'] === 'test-1', 'System version snapshot');
$head = srHead($db, $registerA);
srFails(fn() => srEvent($db, $companyA, $registerA, $periodA,
    ['idempotency_key' => $event['idempotency_key']]), 'Duplicate retry key', '23000');
srFails(fn() => srEvent($db, $companyA, $registerA, $periodA, ['payload' => '{broken']),
    'Malformed journal JSON', '45000');
srFails(fn() => srEvent($db, $companyA, $registerA, $periodA, ['payload' => '[]']),
    'Journal array is not an object', '45000');
srFails(fn() => srEvent($db, $companyA, $registerA, $periodA, ['event_type' => '']),
    'Empty event type', '45000');
srFails(fn() => srEvent($db, $companyB, $registerA, null), 'Company/register isolation', '45000');
srFails(fn() => srEvent($db, $companyB, $registerB, $periodA), 'Company/period isolation', '45000');
srAssert(srHead($db, $registerA) === $head, 'Failed events leave head unchanged');
$db->beginTransaction();
$rolled = srEvent($db, $companyA, $registerA, $periodA);
$db->rollBack();
srAssert(srHead($db, $registerA) === $head, 'Rollback restores chain head');
srAssert((int)srQuery($db, 'SELECT COUNT(*) FROM sales_register_journal WHERE id=?', [$rolled['id']])->fetchColumn() === 0,
    'Rollback removes uncommitted event');
srFails(fn() => srQuery($db, 'UPDATE sales_register_journal SET payload=payload WHERE id=?', [$event['id']]),
    'Even no-op journal update rejected', '45000');
srFails(fn() => srQuery($db, 'DELETE FROM sales_register_journal WHERE id=?', [$event['id']]),
    'Journal delete rejected', '45000');

$sale = srDocument($db, $companyA, $registerA, $periodA, 'sale', 10000);
$x = srDocument($db, $companyA, $registerA, $periodA, 'x_report', 10000);
$training = srDocument($db, $companyA, $registerA, $periodA, 'training', 100);
$return = srDocument($db, $companyA, $registerA, $periodA, 'return', -2500, $sale['id']);
srAssert((int)$sale['receipt_number'] === 1 && (int)$return['receipt_number'] === 2, 'Receipt series includes returns');
srAssert($x['receipt_number'] === null && $training['receipt_number'] === null
    && (int)$training['training_number'] === 1, 'Reports/training do not consume receipt series');
srAssert($sale['payload_sha256'] === hash('sha256', $sale['payload']), 'Frozen document checksum');
$head = srHead($db, $registerA);
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'sale', -1), 'Negative sale', '45000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'return', 1), 'Positive return', '45000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'sale', 1, null, ['payload' => 'no']),
    'Malformed document JSON', '45000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'sale', 1, null, ['currency' => 'EUR']),
    'Period currency mismatch', '45000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'sale', 1, null, ['journal_id' => $sale['journal_id']]),
    'One document per journal event', '23000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'sale', 1, null,
    ['journal_id' => srEvent($db, $companyB, $registerB, $periodB)['id']]),
    'Foreign journal cannot attach to own document', '23000');
srAssert(srHead($db, $registerA) === $head, 'Rejected documents roll back journal and all number heads');

$copy = srDocument($db, $companyA, $registerA2, $periodA2, 'copy', 10000, $sale['id']);
srAssert($copy['copy_of_id'] === $sale['id'] && $copy['receipt_number'] === null, 'Cross-register copy links original');
srFails(fn() => srDocument($db, $companyA, $registerA2, $periodA2, 'copy', 10000, $sale['id']),
    'Second copy on same register', '23000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'copy', 10000, $sale['id']),
    'Second copy on another register', '23000');
srFails(fn() => srDocument($db, $companyA, $registerA2, $periodA2, 'copy', 10000, $copy['id']),
    'Copy of copy', '45000');
srFails(fn() => srDocument($db, $companyB, $registerB, $periodB, 'copy', 10000, $sale['id']),
    'Cannot copy another company document', '45000');
srFails(fn() => srDocument($db, $companyA, $registerA2, $periodA2, 'copy', -1, $sale['id']),
    'Cannot change amount on copy', '45000');
srFails(fn() => srQuery($db, 'UPDATE sales_register_documents SET payload=payload WHERE id=?', [$sale['id']]),
    'Even no-op document update rejected', '45000');
srFails(fn() => srQuery($db, 'DELETE FROM sales_register_documents WHERE id=?', [$sale['id']]),
    'Document delete rejected', '45000');
srFails(fn() => srQuery($db, 'UPDATE sales_register_periods SET opening_float_minor=1 WHERE id=?', [$periodA]),
    'Opening float cannot be rewritten', '45000');
srFails(fn() => srQuery($db, 'DELETE FROM sales_register_periods WHERE id=?', [$periodA2]),
    'Periods cannot be deleted', '45000');

srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'z_report', 7500), 'Z requires closing state', '45000');
srQuery($db, "UPDATE sales_register_periods SET status='closing' WHERE id=?", [$periodA]);
srFails(fn() => srPeriod($db, $companyA, $registerA), 'Closing period still holds active slot', '23000');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'sale', 1), 'No new sale while closing', '45000');
$z = srDocument($db, $companyA, $registerA, $periodA, 'z_report', 7500);
srAssert((int)$z['z_number'] === 1, 'First Z number');
srFails(fn() => srDocument($db, $companyA, $registerA, $periodA, 'z_report', 7500),
    'Only one Z per period', '23000');
srFails(fn() => srQuery($db, "UPDATE sales_register_periods SET status='closed' WHERE id=?", [$periodA]),
    'Cannot close without Z/count/operator', '45000');
$closeSql = "UPDATE sales_register_periods SET status='closed',closed_by=?,closing_z_document_id=?,
    expected_cash_minor=57500,counted_cash_minor=57000 WHERE id=?";
srFails(fn() => srQuery($db, $closeSql, [srId(), $sale['id'], $periodA]),
    'Cannot substitute sale document for Z', '45000');
srQuery($db, $closeSql, [srId(), $z['id'], $periodA]);
$closed = srQuery($db, 'SELECT status,cash_difference_minor,closed_at_utc FROM sales_register_periods WHERE id=?',
    [$periodA])->fetch(PDO::FETCH_ASSOC);
srAssert($closed['status'] === 'closed' && (int)$closed['cash_difference_minor'] === -500
    && $closed['closed_at_utc'] !== null, 'Close records cash difference/time');
srFails(fn() => srQuery($db, 'UPDATE sales_register_periods SET status=status WHERE id=?', [$periodA]),
    'Closed period cannot be updated', '45000');
srFails(fn() => srEvent($db, $companyA, $registerA, $periodA), 'No events into closed period', '45000');
$next = srPeriod($db, $companyA, $registerA);
srAssert((int)srQuery($db, 'SELECT period_number FROM sales_register_periods WHERE id=?', [$next])->fetchColumn() === 2,
    'New period after Z keeps monotonic period numbers');
$nextSale = srDocument($db, $companyA, $registerA, $next, 'sale', 99);
srAssert((int)$nextSale['receipt_number'] === 3, 'Receipt series does not reset after Z');
srQuery($db, "UPDATE sales_register_periods SET status='closing' WHERE id=?", [$next]);
$nextZ = srDocument($db, $companyA, $registerA, $next, 'z_report', 99);
srAssert((int)$nextZ['z_number'] === 2, 'Z sequence continues across periods');

// Separate PHP processes / separate PDO connections, not forked live PDO fixtures.
$workers = [];
foreach (['worker-a', 'worker-b'] as $worker) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, $root, $temp, '--worker', $companyA, $registerA, $worker],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not launch isolated concurrency worker.');
    }
    fclose($pipes[0]);
    $workers[] = [$process, $pipes];
}
foreach ($workers as [$process, $pipes]) {
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    srAssert(proc_close($process) === 0, 'Concurrent worker succeeded: ' . $stderr . $stdout);
}
srAssert((int)srQuery($db, "SELECT COUNT(*) FROM sales_register_journal WHERE register_id=? AND event_type='test.concurrent'",
    [$registerA])->fetchColumn() === 20, 'All concurrent appends retained');

$db->exec("SET time_zone='+07:00'");
$timeEvent = srEvent($db, $companyA, $registerA, null);
srAssert(abs((int)srQuery($db, 'SELECT TIMESTAMPDIFF(SECOND,recorded_at_utc,UTC_TIMESTAMP())
    FROM sales_register_journal WHERE id=?', [$timeEvent['id']])->fetchColumn()) < 5, 'Trigger timestamp remains UTC');
$db->exec("SET time_zone='+00:00'");

$previous = null;
$sequence = 0;
foreach (srQuery($db, 'SELECT * FROM sales_register_journal WHERE company_id=? AND register_id=? ORDER BY sequence_number',
    [$companyA, $registerA])->fetchAll(PDO::FETCH_ASSOC) as $entry) {
    srAssert((int)$entry['sequence_number'] === ++$sequence && $entry['previous_hash'] === $previous,
        'Gap-free chain across concurrency, rollbacks and periods');
    $fields = [$entry['company_id'], $entry['register_id'], $entry['id'], $entry['sequence_number'],
        $entry['period_id'] ?? '', $entry['actor_id'] ?? '', $entry['order_id'] ?? '',
        $entry['event_type'], $entry['idempotency_key'], $entry['system_version'],
        str_replace(' ', 'T', $entry['recorded_at_utc']), $previous ?? str_repeat('0', 64),
        hash('sha256', $entry['payload'])];
    $material = 'SRJ1';
    foreach ($fields as $value) {
        $value = (string)$value;
        $material .= strlen($value) . ':' . $value;
    }
    srAssert($entry['entry_hash'] === hash('sha256', $material), 'Independent PHP hash verification');
    $previous = $entry['entry_hash'];
}
$last = srHead($db, $registerA);
srAssert((int)$last['last_journal_number'] === $sequence && $last['last_journal_hash'] === $previous,
    'Register head matches independently verified journal');

echo "PASS: {$checks} isolated register schema checks; " . $db->query('SELECT VERSION()')->fetchColumn() . "\n";
