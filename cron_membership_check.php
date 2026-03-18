#!/usr/bin/env php
<?php
/**
 * Membership Expiry Cron
 * Runs every 15 min via crontab: *\/15 * * * * /usr/bin/php /path/to/cron_membership_check.php >> /tmp/membership_cron.log 2>&1
 *
 * Automailer_Sent verdier (TINYINT):
 *   0 = ikke varslet
 *   1 = utløpsvarsel sendt (dette skriptet)
 *   2 = purring sendt (fremtidig cron_membership_reminder.php)
 *
 * Safe to re-run: allerede-varslete medlemmer (Automailer_Sent >= 1) hoppes over.
 */

require_once __DIR__ . '/config/database.php';

$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Membership expiry check started\n";

try {
    $db = getDBConnection();

    // Step 1: Get all companies with expiry AutoMailer enabled
    $companyStmt = $db->query("
        SELECT ec.company_id, ec.email AS from_email, ec.display_name,
               tc.name AS company_name, tc.logo_url, tc.contact_phone,
               tc.address_street, tc.address_zip, tc.address_city, tc.org_number
        FROM email_configs ec
        JOIN tenant_companies tc ON tc.id = ec.company_id
        WHERE ec.automail_membership_expired = 1
    ");
    $companies = $companyStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($companies)) {
        echo "[" . date('Y-m-d H:i:s') . "] No companies with renewal AutoMailer enabled. Exiting.\n";
        exit(0);
    }

    $totalQueued = 0;

    foreach ($companies as $company) {
        $companyId      = $company['company_id'];
        $fromEmail      = $company['from_email'];
        $displayName    = $company['display_name'] ?: $company['company_name'];
        $companyName    = $company['company_name'];
        $logoUrl        = $company['logo_url']       ?? '';
        $contactPhone   = $company['contact_phone']  ?? '';
        $orgNumber      = $company['org_number']     ?? '';
        $companyAddress = trim(
            ($company['address_street'] ?? '') . ', ' .
            ($company['address_zip']    ?? '') . ' ' .
            ($company['address_city']   ?? ''),
            ', '
        );

        // Fetch subdomain for renewal link
        $subStmt = $db->prepare("SELECT subdomain FROM tenant_companies WHERE id = ? LIMIT 1");
        $subStmt->execute([$companyId]);
        $subdomain = $subStmt->fetchColumn() ?: '';

        // Fetch email template (company-specific first, then global fallback)
        $tplStmt = $db->prepare("
            SELECT subject_template, body_template
            FROM email_templates
            WHERE template_key = 'membership_expired'
              AND (company_id = ? OR company_id IS NULL)
            ORDER BY company_id DESC
            LIMIT 1
        ");
        $tplStmt->execute([$companyId]);
        $template = $tplStmt->fetch(PDO::FETCH_ASSOC);

        // Step 2: Find inactive expired members not yet notified
        $memberStmt = $db->prepare("
            SELECT
                mm.id          AS member_id,
                mm.expiration_date,
                mm.member_code,
                c.first_name,
                c.last_name,
                c.email,
                p.name         AS plan_name,
                p.share_code
            FROM membership_members mm
            JOIN tenant_customers c   ON c.id = mm.customer_id
            JOIN membership_plans p   ON p.id = mm.membership_plan_id
            WHERE mm.company_id = ?
              AND mm.status = 'inactive'
              AND mm.expiration_date < CURDATE()
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

        if (!$template) {
            echo "[" . date('Y-m-d H:i:s') . "] Company {$companyId}: ingen membership_expired-mal funnet — hopper over.\n";
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
            SET status         = 'inactive',
                payment_status = 'unpaid',
                Automailer_Sent = 1
            WHERE id = ?
        ");

        $count = 0;

        foreach ($members as $m) {
            $expiryFmt = date('d.m.Y', strtotime($m['expiration_date']));

            $renewalUrl = ($subdomain && $m['share_code'] && $m['member_code'])
                ? "https://{$subdomain}.salesman.no/{$m['share_code']}/{$m['member_code']}"
                : '';

            $placeholders = [
                '{{cus_first_name}}'  => $m['first_name'] ?? '',
                '{{cus_last_name}}'   => $m['last_name']  ?? '',
                '{{plan_name}}'       => $m['plan_name']  ?? '',
                '{{expiry_date}}'     => $expiryFmt,
                '{{renewal_url}}'     => $renewalUrl,
                '{{company_name}}'    => $companyName,
                '{{firmanavn}}'       => $companyName,
                '{{display_name}}'    => $displayName,
                '{{logo_url}}'        => $logoUrl,
                '{{contact_phone}}'   => $contactPhone,
                '{{org_number}}'      => $orgNumber,
                '{{company_address}}' => $companyAddress,
                '{{first_name}}'      => $m['first_name']  ?? '',
                '{{last_name}}'       => $m['last_name']   ?? '',
                '{{membership_plan}}' => $m['plan_name']   ?? '',
                '{{name}}'            => $companyName,
                '{{tenant_name}}'     => $companyName,
                '{{expiration_date}}' => $expiryFmt,
                '{{company_phone}}'   => $contactPhone,
                '{{subdomain}}'       => $subdomain,
                '{{share_code}}'      => $m['share_code']  ?? '',
                '{{sharecode}}'       => $m['share_code']  ?? '',
                '{{member_code}}'     => $m['member_code'] ?? '',
            ];
            $subject = str_replace(array_keys($placeholders), array_values($placeholders), $template['subject_template']);
            $body    = str_replace(array_keys($placeholders), array_values($placeholders), $template['body_template']);

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
