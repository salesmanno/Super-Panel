#!/usr/bin/env php
<?php
/**
 * Membership Expiry Cron
 * Runs daily via crontab: 0 7 * * * /usr/bin/php /path/to/cron_membership_check.php >> /tmp/membership_cron.log 2>&1
 *
 * Finds members whose membership has expired and Automailer_Sent = 0,
 * queues a renewal reminder email, then sets Automailer_Sent = 1.
 * Safe to re-run: already-notified members are skipped.
 */

require_once __DIR__ . '/config/database.php';

$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Membership expiry check started\n";

try {
    $db = getDBConnection();

    // Step 1: Get all companies with renewal AutoMailer enabled
    $companyStmt = $db->query("
        SELECT ec.company_id, ec.email AS from_email, ec.display_name,
               tc.name AS company_name
        FROM email_configs ec
        JOIN tenant_companies tc ON tc.id = ec.company_id
        WHERE ec.automail_membership_renewal = 1
    ");
    $companies = $companyStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($companies)) {
        echo "[" . date('Y-m-d H:i:s') . "] No companies with renewal AutoMailer enabled. Exiting.\n";
        exit(0);
    }

    $totalQueued = 0;

    foreach ($companies as $company) {
        $companyId   = $company['company_id'];
        $fromEmail   = $company['from_email'];
        $displayName = $company['display_name'] ?: $company['company_name'];
        $companyName = $company['company_name'];

        // Step 2: Find expired members not yet notified
        $memberStmt = $db->prepare("
            SELECT
                mm.id          AS member_id,
                mm.expiration_date,
                c.first_name,
                c.last_name,
                c.email,
                p.name         AS plan_name
            FROM membership_members mm
            JOIN tenant_customers c   ON c.id = mm.customer_id
            JOIN membership_plans p   ON p.id = mm.membership_plan_id
            WHERE mm.company_id = ?
              AND mm.expiration_date <= CURDATE()
              AND mm.Automailer_Sent = 0
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
              AND c.email IS NOT NULL
              AND c.email != ''
        ");
        $memberStmt->execute([$companyId]);
        $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($members)) {
            echo "[" . date('Y-m-d H:i:s') . "] Company {$companyId}: ingen utløpte medlemmer å varsle.\n";
            continue;
        }

        // Step 3: Prepare INSERT and UPDATE statements
        $insertStmt = $db->prepare("
            INSERT INTO email_queue
                (id, company_id, recipient_email, from_email, subject, body,
                 module_source, status, priority, scheduled_at, created_at, updated_at)
            VALUES
                (UUID(), ?, ?, ?, ?, ?, 'membership', 'queued', 5, NOW(), NOW(), NOW())
        ");

        $updateStmt = $db->prepare("
            UPDATE membership_members
            SET Automailer_Sent = 1
            WHERE id = ?
        ");

        $count = 0;

        foreach ($members as $m) {
            $fullName   = trim($m['first_name'] . ' ' . $m['last_name']);
            $expiryFmt  = date('d.m.Y', strtotime($m['expiration_date']));
            $subject    = "Ditt medlemskap i {$companyName} har utløpt";

            $body = "
<p>Hei {$fullName},</p>
<p>Ditt <strong>{$m['plan_name']}</strong>-medlemskap i <strong>{$companyName}</strong>
utløpte <strong>{$expiryFmt}</strong>.</p>
<p>Ta kontakt med oss for å fornye medlemskapet ditt og fortsette å nyte fordelene.</p>
<p>Med vennlig hilsen,<br>{$displayName}</p>
";

            // Queue the email
            $insertStmt->execute([
                $companyId,
                $m['email'],
                $fromEmail,
                $subject,
                $body
            ]);

            // Mark as notified
            $updateStmt->execute([$m['member_id']]);

            $count++;
            echo "[" . date('Y-m-d H:i:s') . "] Queued renewal reminder → {$m['email']} (utløpt {$expiryFmt})\n";
        }

        $totalQueued += $count;
        echo "[" . date('Y-m-d H:i:s') . "] Company {$companyId} ({$companyName}): {$count} e-post(er) lagt i kø.\n";
    }

    echo "[" . date('Y-m-d H:i:s') . "] Ferdig. Totalt {$totalQueued} e-post(er) lagt i kø.\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] FEIL: " . $e->getMessage() . "\n";
    exit(1);
}
