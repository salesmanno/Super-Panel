#!/usr/bin/env php
<?php
/**
 * Email IMAP Check Cron
 * Runs every few minutes via crontab:
 *   * * * * * /usr/bin/php /path/to/cron_email_check.php >> /tmp/email_check_cron.log 2>&1
 *
 * For each company with IMAP configured, checks for UNSEEN messages
 * and updates email_configs.alertNew accordingly.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/EmailService.php';

$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Email IMAP check started\n";

try {
    $db = getDBConnection();

    $stmt = $db->query("
        SELECT company_id
        FROM email_configs
        WHERE imap_host IS NOT NULL
          AND imap_host != ''
          AND imap_username IS NOT NULL
          AND imap_username != ''
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($companies)) {
        echo "[" . date('Y-m-d H:i:s') . "] Ingen selskaper med IMAP konfigurert. Avslutter.\n";
        exit(0);
    }

    foreach ($companies as $companyId) {
        $emailService = new EmailService($db, $companyId);
        $result = $emailService->checkUnreadEmails();

        if (!$result['success']) {
            echo "[" . date('Y-m-d H:i:s') . "] Selskap {$companyId}: FEIL – " . ($result['error'] ?? 'ukjent') . "\n";
            continue;
        }

        $unread = $result['unread_count'];
        $flag   = $result['alertNew'];
        echo "[" . date('Y-m-d H:i:s') . "] Selskap {$companyId}: {$unread} uleste, alertNew={$flag}\n";
    }

    echo "[" . date('Y-m-d H:i:s') . "] Ferdig.\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] FEIL: " . $e->getMessage() . "\n";
    exit(1);
}
