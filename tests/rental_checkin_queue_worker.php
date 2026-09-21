<?php

if (getenv('RUN_RENTAL_MYSQL_INTEGRATION') !== '1'
    || getenv('RUN_RENTAL_CONCURRENCY_TEST') !== '1') {
    fwrite(STDERR, "This worker is only available to the rental MySQL integration test.\n");
    exit(2);
}

$today = $argv[1] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $today)) {
    fwrite(STDERR, "A valid test date is required.\n");
    exit(2);
}
$companyId = $argv[2] ?? '';
$readyPath = $argv[3] ?? '';
$releasePath = $argv[4] ?? '';
if (!preg_match('/^[a-f0-9-]{36}$/i', $companyId)
    || $readyPath === '' || $releasePath === '') {
    fwrite(STDERR, "A fixture company and synchronization paths are required.\n");
    exit(2);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_checkin_email.php';

try {
    $db = getDBConnection();
    if (file_put_contents($readyPath, (string) getmypid(), LOCK_EX) === false) {
        throw new RuntimeException('Could not signal worker readiness');
    }
    $deadline = microtime(true) + 10;
    while (!is_file($releasePath) && microtime(true) < $deadline) {
        usleep(10000);
    }
    if (!is_file($releasePath)) {
        throw new RuntimeException('Timed out waiting for the worker release barrier');
    }
    echo json_encode(
        ['queued' => queueRentalCheckinEmails($db, $today, $companyId)],
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}