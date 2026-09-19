<?php

/**
 * Opt-in, destructive MySQL/HTTP concurrency test.
 *
 * The test creates a dedicated tenant and removes it and its email queue rows
 * in finally. It never uses an existing tenant or rental object.
 */
if (getenv('RUN_RENTAL_MYSQL_INTEGRATION') !== '1'
    || getenv('RUN_RENTAL_CONCURRENCY_TEST') !== '1') {
    echo "rental schedule concurrency test skipped "
        . "(set RUN_RENTAL_MYSQL_INTEGRATION=1 and RUN_RENTAL_CONCURRENCY_TEST=1)\n";
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

function concurrencyTestUuid(): string {
    $hex = bin2hex(random_bytes(16));
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4'
        . substr($hex, 13, 3) . '-a' . substr($hex, 17, 3) . '-'
        . substr($hex, 20, 12);
}

function concurrencyTestAssert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function concurrencyTestNewConnection(): PDO {
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
        ]
    );
}

function concurrencyTestCreateSession(string $savePath, string $sessionId, string $companyId): void {
    session_save_path($savePath);
    session_id($sessionId);
    session_start();
    $_SESSION = [
        'tenant_admin_id' => concurrencyTestUuid(),
        'company_id' => $companyId,
    ];
    session_write_close();
}

function concurrencyTestStopServer($process): void {
    if (!is_resource($process)) {
        return;
    }
    $status = proc_get_status($process);
    $pid = (int) ($status['pid'] ?? 0);
    if ($pid > 0 && function_exists('posix_kill')) {
        @posix_kill(-$pid, SIGTERM);
        usleep(200000);
        $status = proc_get_status($process);
        if ($status['running'] ?? false) {
            @posix_kill(-$pid, SIGKILL);
        }
    } else {
        proc_terminate($process);
    }
    proc_close($process);
}

function concurrencyTestStartServer(
    string $root,
    string $sessionPath,
    int $port,
    string $logPath,
    string $testToken,
    string $holderPath,
    string $contenderPath
) {
    $setsid = trim((string) shell_exec('command -v setsid'));
    if ($setsid === '') {
        throw new RuntimeException('setsid is required for isolated test-server cleanup');
    }
    $command = [
        $setsid,
        PHP_BINARY,
        '-d',
        'session.save_path=' . $sessionPath,
        '-S',
        '127.0.0.1:' . $port,
        '-t',
        $root,
    ];
    $pipes = [];
    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['file', $logPath, 'a'],
            2 => ['file', $logPath, 'a'],
        ],
        $pipes,
        $root,
        array_merge(getenv(), [
            'PHP_CLI_SERVER_WORKERS' => '2',
            'RUN_RENTAL_CONCURRENCY_TEST' => '1',
            'RENTAL_CONCURRENCY_TEST_TOKEN' => $testToken,
            'RENTAL_CONCURRENCY_HOLDER_PATH' => $holderPath,
            'RENTAL_CONCURRENCY_CONTENDER_PATH' => $contenderPath,
        ])
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start local PHP test server');
    }
    fclose($pipes[0]);

    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
        if ($socket) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if (!$ready) {
        concurrencyTestStopServer($process);
        throw new RuntimeException('Local PHP test server did not start');
    }

    return $process;
}

function concurrencyTestRunPair(
    int $port,
    string $sessionA,
    array $payloadA,
    string $sessionB,
    array $payloadB,
    string $testToken,
    string $holderPath,
    string $contenderPath
): array {
    @unlink($holderPath);
    @unlink($contenderPath);
    $multi = curl_multi_init();
    $createHandle = static function (
        string $sessionId,
        array $payload,
        string $role
    ) use ($port, $testToken) {
        $handle = curl_init('http://127.0.0.1:' . $port . '/api/update_rental_schedule.php');
        $headers = [
            'Content-Type: application/json',
            'X-Rental-Concurrency-Token: ' . $testToken,
        ];
        $headers[] = 'X-Rental-Concurrency-Role: ' . $role;
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIE => 'PHPSESSID=' . $sessionId,
            CURLOPT_TIMEOUT => 15,
        ]);
        return $handle;
    };
    $handleA = $createHandle($sessionA, $payloadA, 'holder');
    $handleB = $createHandle($sessionB, $payloadB, 'contender');
    curl_multi_add_handle($multi, $handleA);

    $barrierDeadline = microtime(true) + 5;
    do {
        $status = curl_multi_exec($multi, $active);
        if (is_file($holderPath)) {
            break;
        }
        if ($active && $status === CURLM_OK) {
            curl_multi_select($multi, 0.05);
        }
    } while ($active && $status === CURLM_OK && microtime(true) < $barrierDeadline);
    concurrencyTestAssert(
        is_file($holderPath),
        'First worker did not reach the row-lock barrier before the second request'
    );
    curl_multi_add_handle($multi, $handleB);

    do {
        $status = curl_multi_exec($multi, $active);
        if ($active) {
            curl_multi_select($multi, 0.2);
        }
    } while ($active && $status === CURLM_OK);

    concurrencyTestAssert(
        is_file($contenderPath),
        'Second request did not enter application code while the first held the row lock'
    );
    $holderPid = trim((string) file_get_contents($holderPath));
    $contenderPid = trim((string) file_get_contents($contenderPath));
    concurrencyTestAssert(
        $holderPid !== '' && $contenderPid !== '' && $holderPid !== $contenderPid,
        'Concurrency test requires two distinct PHP worker processes'
    );

    $parse = static function ($handle): array {
        $error = curl_error($handle);
        concurrencyTestAssert($error === '', 'Concurrent HTTP request failed: ' . $error);
        $body = json_decode((string) curl_multi_getcontent($handle), true, 512, JSON_THROW_ON_ERROR);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_HTTP_CODE), 'body' => $body];
    };
    $resultA = $parse($handleA);
    $resultB = $parse($handleB);
    curl_multi_remove_handle($multi, $handleA);
    curl_multi_remove_handle($multi, $handleB);
    curl_close($handleA);
    curl_close($handleB);
    curl_multi_close($multi);

    return [$resultA, $resultB];
}

function concurrencyTestInsertSchedule(
    PDO $db,
    string $id,
    string $companyId,
    string $objectId
): void {
    $stmt = $db->prepare(
        "INSERT INTO rental_schedule
            (id, company_id, rental_object_id, customer_id, customer_name,
             customer_email, customer_phone, start_date, start_time, end_date,
             pickup_time, return_time, approved, end_time, logistics, rental_fee,
             status, notes, created_by, cleaning)
         VALUES
            (?, ?, ?, NULL, 'Concurrency Customer', 'noreply@example.invalid',
             NULL, '2035-01-01', '09:00:00', '2035-01-02', '09:00:00',
             '23:55:00', 0, '11:00:00', 'pick_up', 1000.00, 'pending',
             'concurrency-test', ?, 0)"
    );
    $stmt->execute([$id, $companyId, $objectId, concurrencyTestUuid()]);
}

function concurrencyTestReadDecision(PDO $db, string $scheduleId): array {
    $stmt = $db->prepare(
        'SELECT approved, status, start_date, pickup_time, end_date, end_time, cleaning, rental_fee
         FROM rental_schedule WHERE id = ?'
    );
    $stmt->execute([$scheduleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Concurrency test schedule disappeared');
    }
    return $row;
}

function concurrencyTestQueueRows(PDO $db, string $companyId, string $scheduleId): array {
    $stmt = $db->prepare(
        "SELECT subject, body, status, scheduled_at FROM email_queue
         WHERE company_id = ? AND module_source = 'rental' AND source_id = ?
         ORDER BY created_at"
    );
    $stmt->execute([$companyId, $scheduleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$root = dirname(__DIR__);
$tempDir = sys_get_temp_dir() . '/rental-concurrency-' . bin2hex(random_bytes(8));
$sessionPath = $tempDir . '/sessions';
$serverLog = $tempDir . '/server.log';
$holderPath = $tempDir . '/holder-barrier';
$contenderPath = $tempDir . '/contender-barrier';
$testToken = bin2hex(random_bytes(24));
mkdir($sessionPath, 0700, true);
$companyId = concurrencyTestUuid();
$objectId = concurrencyTestUuid();
$sessionA = 'rentaltesta' . bin2hex(random_bytes(10));
$sessionB = 'rentaltestb' . bin2hex(random_bytes(10));
$portSocket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (!$portSocket) {
    throw new RuntimeException('Could not reserve local test port');
}
$port = (int) substr((string) stream_socket_get_name($portSocket, false), strrpos((string) stream_socket_get_name($portSocket, false), ':') + 1);
fclose($portSocket);
$server = null;
$db = concurrencyTestNewConnection();
$testPassed = false;

try {
    $db->prepare(
        'INSERT INTO tenant_companies (id, name, subdomain, contact_email)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $companyId,
        'Rental Concurrency Test',
        'rental-test-' . substr(str_replace('-', '', $companyId), 0, 12),
        'noreply@example.invalid',
    ]);
    $db->prepare(
        "INSERT INTO rental_objects
            (id, company_id, object_name, category, rental_price, created_by)
         VALUES (?, ?, 'Concurrency Venue', 'venue', 1000.00, ?)"
    )->execute([$objectId, $companyId, concurrencyTestUuid()]);
    $db->prepare(
        'INSERT INTO rental_venues (object_id, cleaning_fee) VALUES (?, 750.00)'
    )->execute([$objectId]);
    $db->prepare(
        "INSERT INTO email_configs
            (id, company_id, email, display_name, is_active, automail_rental_confirm)
         VALUES (?, ?, 'sender@example.invalid', 'Concurrency Test', 1, 1)"
    )->execute([concurrencyTestUuid(), $companyId]);
    $db->prepare(
        "INSERT INTO email_templates
            (id, company_id, template_key, name, subject_template, body_template, is_active)
         VALUES
            (?, ?, 'rental_confirmation', 'Concurrency Test',
             'CONF {{date_from}} {{pickup_time}} {{total_price}}',
             'BODY {{date_to}} {{end_time}} {{cleaning}} {{cleaning_fee}}', 1)"
    )->execute([concurrencyTestUuid(), $companyId]);

    concurrencyTestCreateSession($sessionPath, $sessionA, $companyId);
    concurrencyTestCreateSession($sessionPath, $sessionB, $companyId);
    $server = concurrencyTestStartServer(
        $root,
        $sessionPath,
        $port,
        $serverLog,
        $testToken,
        $holderPath,
        $contenderPath
    );

    // Two approvals: exactly one wins and exactly one confirmation is queued.
    $scheduleId = concurrencyTestUuid();
    concurrencyTestInsertSchedule($db, $scheduleId, $companyId, $objectId);
    $approvalPayload = [
        'id' => $scheduleId,
        'approved' => 1,
        'status' => 'approved',
        'start_date' => '2035-03-10',
        'pickup_time' => '15:30',
        'end_date' => '2035-03-12',
        'end_time' => '11:15',
        'cleaning' => 1,
    ];
    [$first, $second] = concurrencyTestRunPair(
        $port,
        $sessionA,
        $approvalPayload,
        $sessionB,
        $approvalPayload,
        $testToken,
        $holderPath,
        $contenderPath
    );
    $statuses = [$first['status'], $second['status']];
    sort($statuses);
    concurrencyTestAssert(
        $statuses === [200, 409],
        'Approve/approve must have one winner and one conflict: '
        . json_encode([$first, $second])
    );
    $loser = $first['status'] === 409 ? $first : $second;
    concurrencyTestAssert(
        ($loser['body']['already_approved'] ?? false) === true,
        'Losing duplicate approval must report already_approved'
    );
    $stored = concurrencyTestReadDecision($db, $scheduleId);
    concurrencyTestAssert(
        (int) $stored['approved'] === 1
        && $stored['status'] === 'approved'
        && $stored['start_date'] === '2035-03-10'
        && substr($stored['pickup_time'], 0, 5) === '15:30'
        && $stored['end_date'] === '2035-03-12'
        && substr($stored['end_time'], 0, 5) === '11:15'
        && (int) $stored['cleaning'] === 1
        && (float) $stored['rental_fee'] === 1750.0,
        'Winning approval must atomically persist all inline edits and cleaning total'
    );
    $queue = concurrencyTestQueueRows($db, $companyId, $scheduleId);
    concurrencyTestAssert(count($queue) === 1, 'Duplicate approval must queue exactly one confirmation');
    concurrencyTestAssert(
        str_contains($queue[0]['subject'], '10.03.2035 15:30 1 750,00')
        && str_contains($queue[0]['body'], '12.03.2035 11:15 Ja 750,00'),
        'Queued confirmation must render the winning edited values'
    );
    concurrencyTestAssert(
        $queue[0]['status'] === 'queued'
        && strtotime($queue[0]['scheduled_at']) > time() + 3600,
        'Test confirmation must remain queued and isolated from the live processor'
    );

    // Rejection holds the row lock first; stale approval cannot overwrite it.
    $mixedId = concurrencyTestUuid();
    concurrencyTestInsertSchedule($db, $mixedId, $companyId, $objectId);
    $approveMixed = [
        'id' => $mixedId,
        'approved' => 1,
        'status' => 'approved',
        'start_date' => '2035-04-10',
        'pickup_time' => '14:00',
        'end_date' => '2035-04-12',
        'end_time' => '10:00',
        'cleaning' => 1,
    ];
    $rejectMixed = [
        'id' => $mixedId,
        'approved' => 2,
        'status' => 'rejected',
        'start_date' => '2035-05-10',
        'pickup_time' => '16:00',
        'end_date' => '2035-05-11',
        'end_time' => '12:00',
        'cleaning' => 0,
    ];
    [$rejectResponse, $approveResponse] = concurrencyTestRunPair(
        $port,
        $sessionA,
        $rejectMixed,
        $sessionB,
        $approveMixed,
        $testToken,
        $holderPath,
        $contenderPath
    );
    $mixedStatuses = [$approveResponse['status'], $rejectResponse['status']];
    sort($mixedStatuses);
    concurrencyTestAssert(
        $mixedStatuses === [200, 409],
        'Approve/reject must have one winner and one conflict: '
        . json_encode([$approveResponse, $rejectResponse])
    );
    $storedMixed = concurrencyTestReadDecision($db, $mixedId);
    concurrencyTestAssert(
        $rejectResponse['status'] === 200
        && $approveResponse['status'] === 409
        && ($approveResponse['body']['stale_request'] ?? false) === true,
        'Locked rejection must win and stale approval must report a conflict'
    );
    concurrencyTestAssert(
        (int) $storedMixed['approved'] === 2
        && $storedMixed['status'] === 'rejected'
        && $storedMixed['start_date'] === '2035-05-10'
        && substr($storedMixed['pickup_time'], 0, 5) === '16:00'
        && $storedMixed['end_date'] === '2035-05-11'
        && substr($storedMixed['end_time'], 0, 5) === '12:00'
        && (int) $storedMixed['cleaning'] === 0
        && (float) $storedMixed['rental_fee'] === 1000.0,
        'Winning rejection must atomically persist its complete tuple'
    );
    concurrencyTestAssert(
        count(concurrencyTestQueueRows($db, $companyId, $mixedId)) === 0,
        'Winning rejection must not queue a confirmation'
    );
    $testPassed = true;
} finally {
    if (is_resource($server)) {
        concurrencyTestStopServer($server);
    }
    $cleanupError = null;
    for ($cleanupAttempt = 1; $cleanupAttempt <= 3; $cleanupAttempt++) {
        try {
            $cleanupDb = concurrencyTestNewConnection();
            $cleanupDb->prepare('DELETE FROM email_queue WHERE company_id = ?')->execute([$companyId]);
            $cleanupDb->prepare('DELETE FROM tenant_companies WHERE id = ?')->execute([$companyId]);
            $companyCheck = $cleanupDb->prepare('SELECT COUNT(*) FROM tenant_companies WHERE id = ?');
            $companyCheck->execute([$companyId]);
            $queueCheck = $cleanupDb->prepare('SELECT COUNT(*) FROM email_queue WHERE company_id = ?');
            $queueCheck->execute([$companyId]);
            if ((int) $companyCheck->fetchColumn() !== 0 || (int) $queueCheck->fetchColumn() !== 0) {
                throw new RuntimeException('Test fixture rows remain after cleanup');
            }
            $cleanupError = null;
            break;
        } catch (Throwable $error) {
            $cleanupError = $error;
            sleep($cleanupAttempt);
        }
    }
    if (is_dir($tempDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        rmdir($tempDir);
    }
    if ($cleanupError) {
        throw new RuntimeException('Cleanup failed: ' . $cleanupError->getMessage(), 0, $cleanupError);
    }
}

if ($testPassed) {
    echo "rental schedule concurrency MySQL test passed\n";
}