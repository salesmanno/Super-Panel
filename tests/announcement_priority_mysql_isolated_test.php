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
    'ffffffff-ffff-4fff-afff-ffffffffffff',
    '11111111-1111-4111-a111-111111111111',
    '22222222-2222-4222-a222-222222222222',
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
    '00000000-0000-4000-a000-000000000005' => 6,
    '00000000-0000-4000-a000-000000000006' => 9,
    '00000000-0000-4000-a000-000000000007' => 3,
    '00000000-0000-4000-a000-000000000008' => 4,
    '00000000-0000-4000-a000-000000000009' => 4,
], 'Duplicate-top migration did not shift each rank by its duplicate count');
priorityMysqlAssertRows($db, $companyB, [
    '00000000-0000-4000-a000-000000000010' => 1,
    '00000000-0000-4000-a000-000000000011' => 3,
    '00000000-0000-4000-a000-000000000012' => 5,
    '00000000-0000-4000-a000-000000000013' => 8,
    '00000000-0000-4000-a000-000000000014' => 9,
], 'Numeric migration did not map canonical values into the 1–9 range');

// Replaying both migrations after the guard exists must leave all ranks intact.
priorityMysqlRunFile($db, $root . '/db_migrations/tenant_announcement_priority_numeric.sql');
priorityMysqlRunFile($db, $root . '/db_migrations/tenant_announcement_priority_numeric_cascade.sql');
priorityMysqlAssertRows($db, $companyA, [
    '00000000-0000-4000-a000-000000000001' => 1,
    '00000000-0000-4000-a000-000000000002' => 2,
    '00000000-0000-4000-a000-000000000003' => 3,
    '00000000-0000-4000-a000-000000000004' => 4,
    '00000000-0000-4000-a000-000000000005' => 6,
    '00000000-0000-4000-a000-000000000006' => 9,
    '00000000-0000-4000-a000-000000000007' => 3,
    '00000000-0000-4000-a000-000000000008' => 4,
    '00000000-0000-4000-a000-000000000009' => 4,
], 'Migration replay changed the priority result');
$db->close();

require_once $root . '/includes/announcement_helpers.php';
$pdo = priorityMysqlPdo($socket);
$db = priorityMysqlConnect($socket);

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
    '00000000-0000-4000-a000-000000000005' => 7,
    '00000000-0000-4000-a000-000000000006' => 9,
    '00000000-0000-4000-a000-000000000007' => 4,
    '00000000-0000-4000-a000-000000000008' => 5,
    '00000000-0000-4000-a000-000000000009' => 5,
    $createdId => 1,
];
ksort($expectedCreateRanks, SORT_STRING);
ksort($createdRanks, SORT_STRING);
priorityMysqlAssert($createdRanks === $expectedCreateRanks, 'Actual create helper did not serialize the full cascade');

// Assigning priority 4 moves only rows at/after 4. Earlier ranks remain intact,
// and rank 9 is a cap rather than a unique slot.
$companyNineToFour = $companies[5];
$nineToFourTarget = '00000000-0000-4000-a000-000000000071';
foreach ([
    ['00000000-0000-4000-a000-000000000072', '1'],
    ['00000000-0000-4000-a000-000000000073', '2'],
    ['00000000-0000-4000-a000-000000000074', '3'],
    ['00000000-0000-4000-a000-000000000075', '4'],
    [$nineToFourTarget, '9'],
    ['00000000-0000-4000-a000-000000000076', '9'],
] as [$id, $priority]) {
    priorityMysqlInsertAnnouncement($db, $id, $companyNineToFour, $priority);
}
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyNineToFour, $nineToFourTarget, 4);
$pdo->prepare(
    'UPDATE tenant_announcements SET priority = 4 WHERE id = ? AND company_id = ?'
)->execute([$nineToFourTarget, $companyNineToFour]);
$pdo->commit();
priorityMysqlAssertRows($db, $companyNineToFour, [
    '00000000-0000-4000-a000-000000000072' => 1,
    '00000000-0000-4000-a000-000000000073' => 2,
    '00000000-0000-4000-a000-000000000074' => 3,
    '00000000-0000-4000-a000-000000000075' => 5,
    $nineToFourTarget => 4,
    '00000000-0000-4000-a000-000000000076' => 9,
], 'Assigning 9 to 4 changed earlier ranks or failed to cap priority 9');
$unchangedPriorityRanks = announcement_priority_map($pdo, $companyNineToFour);
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyNineToFour, $nineToFourTarget, 4);
$pdo->prepare(
    'UPDATE tenant_announcements SET priority = 4 WHERE id = ? AND company_id = ?'
)->execute([$nineToFourTarget, $companyNineToFour]);
$pdo->commit();
priorityMysqlAssert(
    announcement_priority_map($pdo, $companyNineToFour) === $unchangedPriorityRanks,
    'Reassigning an unchanged priority shifted tenant rows'
);

// Gaps do not stop the cascade, and absence of a priority-1 incumbent does not
// cause ranks below the requested priority to move.
$companyGap = $companies[6];
$gapTarget = '00000000-0000-4000-a000-000000000081';
foreach ([
    ['00000000-0000-4000-a000-000000000082', '2'],
    ['00000000-0000-4000-a000-000000000083', '6'],
    [$gapTarget, '9'],
] as [$id, $priority]) {
    priorityMysqlInsertAnnouncement($db, $id, $companyGap, $priority);
}
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyGap, $gapTarget, 4);
$pdo->prepare(
    'UPDATE tenant_announcements SET priority = 4 WHERE id = ? AND company_id = ?'
)->execute([$gapTarget, $companyGap]);
$pdo->commit();
priorityMysqlAssertRows($db, $companyGap, [
    '00000000-0000-4000-a000-000000000082' => 2,
    '00000000-0000-4000-a000-000000000083' => 7,
    $gapTarget => 4,
], 'Assignment stopped at a gap or moved a rank below 4');
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyGap, $gapTarget, 1);
$pdo->commit();
priorityMysqlAssertRows($db, $companyGap, [
    '00000000-0000-4000-a000-000000000082' => 3,
    '00000000-0000-4000-a000-000000000083' => 8,
    $gapTarget => 1,
], 'Assigning one with no incumbent must still shift every other rank');

// Any number of rows can share rank 9 after an assignment.
$companyManyNines = $companies[7];
for ($i = 0; $i < 100; $i++) {
    $id = sprintf('00000000-0000-4000-a000-%012d', 1000 + $i);
    priorityMysqlInsertAnnouncement($db, $id, $companyManyNines, '9');
}
$manyNinesCreatedId = '00000000-0000-4000-a000-000000000091';
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyManyNines, $manyNinesCreatedId, 4, true);
$pdo->prepare(
    'INSERT INTO tenant_announcements (id, company_id, priority) VALUES (?, ?, 4)'
)->execute([$manyNinesCreatedId, $companyManyNines]);
$pdo->commit();
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM tenant_announcements WHERE company_id = ? AND priority = 9'
);
$stmt->execute([$companyManyNines]);
priorityMysqlAssert((int)$stmt->fetchColumn() === 100, 'Priority 9 did not allow 100 shared rows');
$manyNinesRanks = announcement_priority_map($pdo, $companyManyNines);
priorityMysqlAssert(
    ($manyNinesRanks[$manyNinesCreatedId] ?? null) === 4,
    'Creating at priority 4 did not preserve the requested priority with 100 rank-9 rows'
);
$createdNineId = '00000000-0000-4000-a000-000000000092';
$pdo->beginTransaction();
announcement_apply_priority_assignment($pdo, $companyManyNines, $createdNineId, 9, true);
$pdo->prepare('INSERT INTO tenant_announcements (id, company_id, priority) VALUES (?, ?, 9)')
    ->execute([$createdNineId, $companyManyNines]);
$pdo->commit();
$stmt->execute([$companyManyNines]);
priorityMysqlAssert((int)$stmt->fetchColumn() === 101, 'Creating another nine must not shift existing nines');
priorityMysqlAssert(announcement_priority_map($pdo, $companyManyNines)[$manyNinesCreatedId] === 4, 'Creating nine must preserve priority four');

// The database guard rejects out-of-band duplicate top and out-of-range writes.
foreach ([1 => 1062, 10 => 3819] as $invalidPriority => $expectedError) {
    $rejected = false;
    try {
        $pdo->prepare('INSERT INTO tenant_announcements (id, company_id, priority) VALUES (?, ?, ?)')
            ->execute(['00000000-0000-4000-a000-000000000095', $companyGap, $invalidPriority]);
    } catch (PDOException $error) {
        $rejected = (int)($error->errorInfo[1] ?? 0) === $expectedError;
    }
    priorityMysqlAssert($rejected, 'Database guard must reject priority ' . $invalidPriority);
}

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
$db->close();

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
$concurrentRanks = announcement_priority_map($finalConcurrent, $companyConcurrent);
priorityMysqlAssert($concurrentRanks[$concurrentOccupant] === 3, 'Both concurrent assignments must shift the former top twice');
$sortedRanks = array_values($concurrentRanks);
sort($sortedRanks);
priorityMysqlAssert($sortedRanks === [1, 2, 3], 'Concurrent saves must preserve a complete serialized shift');

echo "Isolated News migration replay and PHP helper concurrency test passed\n";