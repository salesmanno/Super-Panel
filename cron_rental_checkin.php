#!/usr/bin/env php
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/rental_checkin_email.php';

try {
    $db = getDBConnection();
    $count = queueRentalCheckinEmails($db);
    echo '[' . date('Y-m-d H:i:s') . "] Rental innsjekk: {$count} lagt i kø\n";
} catch (Throwable $e) {
    error_log('Rental innsjekk-cron feil: ' . $e->getMessage());
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}