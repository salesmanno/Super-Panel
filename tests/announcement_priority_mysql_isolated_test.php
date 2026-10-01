<?php
declare(strict_types=1);

/**
 * Runs News migrations and priority helpers against the disposable MySQL
 * instance started by announcement_priority_mysql_isolated_test.sh.
 * Never load config/database.php or accept a host/DSN from the environment.
 */
$root = $argv[1] ?? '';
$socket = getenv('ANNOUNCEMENT_PRIORITY_TEST_SOCKET') ?: '';
$tempDir = getenv('ANNOUNCEMENT_PRIORITY_TEST_TMPDIR') ?: '';
if ($root === '' || $socket === '' || $tempDir === '' || !file_exists($socket)) {
    throw new RuntimeException('Run through the isolated News priority MySQL harness');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function priorityMysqlAssert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

function priorityMysqlConnect(string $socket, bool $selectDb = true): mysqli {
    $db = new mysqli('localhost', 'root', '', '', 0, $socket);
    $db->set_charset('utf8mb4');
    if ($selectDb) $db->select_db('priority_test');
    return $db;
}

function priorityMysqlPdo(string $socket): PDO {
    return new PDO(
        'mysql:unix_socket=' . $socket . ';dbname=priority_test;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function priorityMysqlRunFile(mysqli $db, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false || !$db->multi_query($sql)) {
        throw new RuntimeException('Could not run isolated News migration: ' . $path);
    }
    do {
        $result = $db->store_result();
        if ($result instanceof mysqli_result) $result->free();
    } while ($db->more_results() && $db->next_result());
}

function priorityMysqlInsertCompany(mysqli $db, string $id): void {
    $stmt = $db->prepare('INSERT INTO tenant_companies (id) VALUES (?)');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->close();
}

function priorityMysqlInsertAnnouncement(mysqli $db, string $id, string $companyId, string $priority): void {
    $stmt = $db->prepare(
        'INSERT INTO tenant_announcements (id, company_id, priority) VALUES (?, ?, ?)'
    );
    $stmt->bind_param('sss', $id, $companyId, $priority);
    $stmt->execute();
    $stmt->close();
}

function priorityMysqlRows(mysqli $db, string $companyId): array {
    $stmt = $db->prepare(
        'SELECT id, priority FROM tenant_announcements WHERE company_id = ? ORDER BY id'
    );
    $stmt->bind_param('s', $companyId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $result = [];
    foreach ($rows as $row) $result[$row['id']] = (int)$row['priority'];
    return $result;
}

function priorityMysqlAssertRows(mysqli $db, string $companyId, array $expected, string $message): void {
    $actual = priorityMysqlRows($db, $companyId);
    ksort($expected, SORT_STRING);
    ksort($actual, SORT_STRING);
    priorityMysqlAssert($actual === $expected, $message . ': ' . json_encode($actual));
}

function priorityMysqlChild(
    string $mode,
    string $socket,
    string $companyId,
    string $targetId,
    string $newId,
    string $barrier,
    string $resultPath
): never {
    $root = dirname(__DIR__);
    require_once $root . '/includes/announcement_helpers.php';
    try {
        $deadline = microtime(true) + 10;
        while (!is_file($barrier) && microtime(true) < $deadline) usleep(1000);
        if (!is_file($barrier)) throw new RuntimeException('Concurrency start barrier timed out');

        $db = priorityMysqlPdo($socket);
        $db->beginTransaction();
        if ($mode === 'create') {
            announcement_apply_priority_assignment($db, $companyId, $newId, 1, true);
            // Keep the tenant lock long enough for the other client to contend.
            usleep(250000);
            $stmt = $db->prepare(
                'INSERT INTO tenant_announcements (id, company_id, priority) VALUES (?, ?, 1)'
            );
            $stmt->execute([$newId, $companyId]);
        } else {
            announcement_apply_priority_assignment($db, $companyId, $targetId, 1);
            usleep(250000);
            $stmt = $db->prepare(
                'UPDATE tenant_announcements SET priority = 1 WHERE id = ? AND company_id = ?'
            );
            $stmt->execute([$targetId, $companyId]);
        }
        $db->commit();
        file_put_contents($resultPath, 'ok');
        exit(0);
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        file_put_contents($resultPath, 'error: ' . $error->getMessage());
        exit(1);
    }
}

$db = priorityMysqlConnect($socket, false);
$db->query('CREATE DATABASE priority_test CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
$db->select_db('priority_test');
$db->query(
    'CREATE TABLE tenant_companies (
        id CHAR(36) NOT NULL PRIMARY KEY
    ) ENGINE=InnoDB'
);
$db->query(
    "CREATE TABLE tenant_announcements (
        id CHAR(36) NOT NULL PRIMARY KEY,
        company_id CHAR(36) NOT NULL,
        priority VARCHAR(32) NOT NULL DEFAULT '5',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY ix_tenant_announcements_company_priority (company_id, priority)
    ) ENGINE=InnoDB"
);

$companies = [
    'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
    'bbbbbbbb-bbbb-4bbb-bbbb-bbbbbbbbbbbb',
    'cccccccc-cccc-4ccc-cccc-cccccccccccc',
    'dddddddd-dddd-4ddd-dddd-dddddddddddd',
    'eeeeeeee-eeee-4eee-eeee-eeeeeeeeeeee',
];
foreach ($companies as $companyId) priorityMysqlInsertCompany($db, $companyId);

$companyA = $companies[0];
foreach ([
    ['00000000-0000-4000-a000-000000000001', '1'],
    ['00000000-0000-4000-a000-000000000002', '1'],
    ['00000000-0000-4000-a000-000000000003', '2'],
    ['00000000-0000-4000-a000-000000000004', '3'],
    ['00000000-0000-4000-a000-000000000005', '5'],
    ['00000000-0000-4000-a000-000000000006', '11'],
    ['00000000-0000-4000-a000-000000000007', '2'],
    ['00000000-0000-4000-a000-000000000008', '3'],
    ['00000000-0000-4000-a000-000000000009', '3'],
] as [$id, $priority]) {
    priorityMysqlInsertAnnouncement($db, $id, $companyA, $priority);
}

$companyB = $companies[1];
foreach ([
    ['00000000-0000-4000-a000-000000000010', 'urgent'],
    ['00000000-0000-4000-a000-000000000011', 'high'],
    ['00000000-0000-4000-a000-000000000012', 'normal'],
    ['00000000-0000-4000-a000-000000000013', 'low'],
    ['00000000-0000-4000-a000-000000000014', '9007199254740991'],
] as [$id, $priority]) {
    priorityMysqlInsertAnnouncement($db, $id, $companyB, $priority);
}

$companyConcurrent = $companies[2];
$concurrentTarget = '00000000-0000-4000-a000-000000000031';
$concurrentOccupant = '00000000-0000-4000-a000-000000000032';
priorityMysqlInsertAnnouncement($db, $concurrentTarget, $companyConcurrent, '2');
priorityMysqlInsertAnnouncement($db, $concurrentOccupant, $companyConcurrent, '1');

$companyFormerTop = $companies[3];
$formerTop = '00000000-0000-4000-a000-000000000041';
$formerTopTarget = '00000000-0000-4000-a000-000000000042';
$formerTopNext = '00000000-0000-4000-a000-000000000043';
priorityMysqlInsertAnnouncement($db, $formerTop, $companyFormerTop, '1');
priorityMysqlInsertAnnouncement($db, $formerTopTarget, $companyFormerTop, '7');
priorityMysqlInsertAnnouncement($db, $formerTopNext, $companyFormerTop, '2');

$companyRollback = $companies[4];
$rollbackTop = '00000000-0000-4000-a000-000000000051';
$rollbackNext = '00000000-0000-4000-a000-000000000052';
$rollbackTarget = '00000000-0000-4000-a000-000000000053';
priorityMysqlInsertAnnouncement($db, $rollbackTop, $companyRollback, '1');
priorityMysqlInsertAnnouncement($db, $rollbackNext, $companyRollback, '2');
priorityMysqlInsertAnnouncement($db, $rollbackTarget, $companyRollback, '3');

priorityMysqlRunFile($db, $root . '/db_migrations/tenant_announcement_priority_numeric.sql');
priorityMysqlRunFile($db, $root . '/db_migrations/tenant_announcement_priority_numeric_cascade.sql');

priorityMysqlAssertRows($db, $companyA, [
    '00000000-0000-4000-a000-000000000001' => 1,
    '00000000-0000-4000-a000-000000000002' => 2,
    '00000000-0000-4000-a000-000000000003' => 3,
    '00000000-0000-4000-a000-000000000004' => 4,
    '00000000-0000-4000-a000-000000000005' => 5,
    '00000000-0000-4000-a000-000000000006' => 11,
    '00000000-0000-4000-a000-000000000007' => 3,
    '00000000-0000-4000-a000-000000000008' => 4,
    '00000000-0000-4000-a000-000000000009' => 4,
], 'Duplicate-top migration did not cascade each occupied rank once');
priorityMysqlAssertRows($db, $companyB, [
    '00000000-0000-4000-a000-000000000010' => 1,
    '00000000-0000-4000-a000-000000000011' => 3,
    '00000000-0000-4000-a000-000000000012' => 5,
    '00000000-0000-4000-a000-000000000013' => 8,
    '00000000-0000-4000-a000-000000000014' => 9007199254740991,
], 'Numeric migration did not retain canonical or above-ten values');

// Replaying both migrations after the guard exists must leave all ranks intact.
priorityMysqlRunFile($db, $root . '/db_migrations/tenant_announcement_priority_numeric.sql');
priorityMysqlRunFile($db, $root . '/db_migrations/tenant_announcement_priority_numeric_cascade.sql');
priorityMysqlAssertRows($db, $companyA, [
    '00000000-0000-4000-a000-000000000001' => 1,
    '00000000-0000-4000-a000-000000000002' => 2,
    '00000000-0000-4000-a000-000000000003' => 3,
    '00000000-0000-4000-a000-000000000004' => 4,
    '00000000-0000-4000-a000-000000000005' => 5,
    '00000000-0000-4000-a000-000000000006' => 11,
    '00000000-0000-4000-a000-000000000007' => 3,
    '00000000-0000-4000-a000-000000000008' => 4,
    '00000000-0000-4000-a000-000000000009' => 4,
], 'Migration replay changed the priority result');
$db->close();

require_once $root . '/includes/announcement_helpers.php';
$pdo = priorityMysqlPdo($socket);

// Exercise the actual create-helper sequence with a contiguous tenant chain.
$createdId = '00000000-0000-4000-a000-000000000100';
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyA, $createdId, 1, true);
$pdo->prepare(
    'INSERT INTO tenant_announcements (id, company_id, priority) VALUES (?, ?, 1)'
)->execute([$createdId, $companyA]);
$pdo->commit();
$createdRanks = announcement_priority_map($pdo, $companyA);
$expectedCreateRanks = [
    '00000000-0000-4000-a000-000000000001' => 2,
    '00000000-0000-4000-a000-000000000002' => 3,
    '00000000-0000-4000-a000-000000000003' => 4,
    '00000000-0000-4000-a000-000000000004' => 5,
    '00000000-0000-4000-a000-000000000005' => 6,
    '00000000-0000-4000-a000-000000000006' => 11,
    '00000000-0000-4000-a000-000000000007' => 4,
    '00000000-0000-4000-a000-000000000008' => 5,
    '00000000-0000-4000-a000-000000000009' => 5,
    $createdId => 1,
];
ksort($expectedCreateRanks, SORT_STRING);
ksort($createdRanks, SORT_STRING);
priorityMysqlAssert($createdRanks === $expectedCreateRanks, 'Actual create helper did not serialize the full cascade');

// A former priority-1 occupant is moved before the edited target gets priority 1.
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyFormerTop, $formerTopTarget, 1);
$pdo->commit();
$formerTopRanks = announcement_priority_map($pdo, $companyFormerTop);
priorityMysqlAssert(
    ($formerTopRanks[$formerTopTarget] ?? null) === 1
    && ($formerTopRanks[$formerTop] ?? null) === 2
    && ($formerTopRanks[$formerTopNext] ?? null) === 3,
    'Moving an existing target to rank 1 did not move its former occupant first'
);

// Trigger a real helper cascade inside a transaction, then roll it back.
$beforeRollback = announcement_priority_map($pdo, $companyRollback);
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyRollback, $rollbackTarget, 1);
$duringRollback = announcement_priority_map($pdo, $companyRollback);
priorityMysqlAssert(
    ($duringRollback[$rollbackTarget] ?? null) === 1
    && ($duringRollback[$rollbackTop] ?? null) === 2,
    'Rollback fixture did not exercise applied helper updates'
);
$pdo->rollBack();
priorityMysqlAssert(
    announcement_priority_map($pdo, $companyRollback) === $beforeRollback,
    'Rolling back helper changes did not restore the original ranks'
);
$pdo = null;

// Two actual PHP helper clients contend on the same tenant lock: one creates,
// the other edits to priority 1. The tenant lock serializes each whole write.
priorityMysqlAssert(function_exists('pcntl_fork'), 'pcntl is required for the helper concurrency test');
$barrier = $tempDir . '/priority-helper-go';
$createResult = $tempDir . '/priority-helper-create.result';
$updateResult = $tempDir . '/priority-helper-update.result';
@unlink($barrier);
@unlink($createResult);
@unlink($updateResult);

$createPid = pcntl_fork();
if ($createPid === -1) throw new RuntimeException('Could not fork create helper client');
if ($createPid === 0) {
    priorityMysqlChild(
        'create',
        $socket,
        $companyConcurrent,
        $concurrentTarget,
        '00000000-0000-4000-a000-000000000061',
        $barrier,
        $createResult
    );
}

$updatePid = pcntl_fork();
if ($updatePid === -1) {
    posix_kill($createPid, SIGTERM);
    pcntl_waitpid($createPid, $status);
    throw new RuntimeException('Could not fork update helper client');
}
if ($updatePid === 0) {
    priorityMysqlChild(
        'update',
        $socket,
        $companyConcurrent,
        $concurrentTarget,
        '',
        $barrier,
        $updateResult
    );
}
file_put_contents($barrier, 'start');
pcntl_waitpid($createPid, $createStatus);
pcntl_waitpid($updatePid, $updateStatus);
priorityMysqlAssert(
    pcntl_wifexited($createStatus) && pcntl_wexitstatus($createStatus) === 0
    && pcntl_wifexited($updateStatus) && pcntl_wexitstatus($updateStatus) === 0
    && file_get_contents($createResult) === 'ok'
    && file_get_contents($updateResult) === 'ok',
    'Concurrent create/update helper clients failed: '
    . (is_file($createResult) ? file_get_contents($createResult) : 'create missing')
    . ' / '
    . (is_file($updateResult) ? file_get_contents($updateResult) : 'update missing')
);

$finalConcurrent = priorityMysqlPdo($socket);
$stmt = $finalConcurrent->prepare(
    'SELECT COUNT(*) FROM tenant_announcements WHERE company_id = ? AND priority = 1'
);
$stmt->execute([$companyConcurrent]);
priorityMysqlAssert((int)$stmt->fetchColumn() === 1, 'Concurrent helper writes left multiple priority-1 rows');
$stmt = $finalConcurrent->prepare(
    'SELECT COUNT(*) FROM tenant_announcements WHERE company_id = ?'
);
$stmt->execute([$companyConcurrent]);
priorityMysqlAssert((int)$stmt->fetchColumn() === 3, 'Concurrent create/update did not persist both tenant changes');

echo "Isolated News migration replay and PHP helper concurrency test passed\n";