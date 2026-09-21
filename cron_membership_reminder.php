#!/usr/bin/env php
<?php
/**
 * Membership Reminder Cron (Purring)
 * Runs via cron alongside cron_membership_check.php
 *
 * Kriterier:
 *   - Automailer_Sent = 1  (utløpsvarsel allerede sendt)
 *   - CURDATE() > expiration_date + 14 dager (14 dager etter utløp uten fornyelse)
 *   - Bedriften har automail_membership_reminder = 1
 *
 * Etter utsending: Automailer_Sent settes til 2 ("purring sendt")
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/email_template_tags.php';

$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Membership reminder (purring) check started\n";

try {
    $db = getDBConnection();

    // Step 1: Hent bedrifter med purring-AutoMailer aktivert
    $companyStmt = $db->query("
        SELECT ec.company_id, ec.email AS from_email, ec.display_name,
               tc.name AS company_name, tc.logo_url, tc.contact_phone,
               tc.address_street, tc.address_zip, tc.address_city, tc.org_number
        FROM email_configs ec
        JOIN tenant_companies tc ON tc.id = ec.company_id
        WHERE ec.automail_membership_reminder = 1
    ");
    $companies = $companyStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($companies)) {
        echo "[" . date('Y-m-d H:i:s') . "] Ingen bedrifter med purring-AutoMailer aktivert. Avslutter.\n";
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

        // Hent subdomain for fornyelseslenke
        $subStmt = $db->prepare("SELECT subdomain FROM tenant_companies WHERE id = ? LIMIT 1");
        $subStmt->execute([$companyId]);
        $subdomain = $subStmt->fetchColumn() ?: '';

        // Hent e-postmal membership_reminder (bedriftsspesifikk først, deretter global)
        $tplStmt = $db->prepare("
            SELECT id, subject_template, body_template
            FROM email_templates
            WHERE template_key = 'membership_reminder'
              AND (company_id = ? OR company_id IS NULL)
            ORDER BY company_id DESC
            LIMIT 1
        ");
        $tplStmt->execute([$companyId]);
        $template = $tplStmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            echo "[" . date('Y-m-d H:i:s') . "] Bedrift {$companyId}: ingen membership_reminder-mal funnet — hopper over.\n";
            continue;
        }

        // Step 2: Finn medlemmer som skal purres
        //   - Automailer_Sent = 1 (utløpsvarsel sendt)
        //   - CURDATE() > expiration_date + 14 dager
        //   - Ikke kansellert
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
            JOIN tenant_customers c ON c.id = mm.customer_id
            JOIN membership_plans p ON p.id = mm.membership_plan_id
            WHERE mm.company_id = ?
              AND mm.Automailer_Sent = 1
              AND CURDATE() > DATE_ADD(mm.expiration_date, INTERVAL 14 DAY)
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
              AND c.email IS NOT NULL
              AND c.email != ''
        ");
        $memberStmt->execute([$companyId]);
        $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($members)) {
            echo "[" . date('Y-m-d H:i:s') . "] Bedrift {$companyId}: ingen medlemmer å purre.\n";
            continue;
        }

        // Step 3: Klargjør INSERT og UPDATE
        $insertStmt = $db->prepare("
            INSERT INTO email_queue
                (id, company_id, recipient_email, from_email, subject, body,
                 template_id, module_source, status, priority, scheduled_at, created_at, updated_at)
            VALUES
                (UUID(), ?, ?, ?, ?, ?, ?, 'membership', 'queued', 5, NOW(), NOW(), NOW())
        ");

        $updateStmt = $db->prepare("
            UPDATE membership_members
            SET Automailer_Sent = 2
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

            $subject = replaceKnownEmailTemplatePlaceholders(
                $template['subject_template'],
                $placeholders
            );
            $body = replaceKnownEmailTemplatePlaceholders(
                $template['body_template'],
                $placeholders
            );

            $insertStmt->execute([
                $companyId,
                $m['email'],
                $fromEmail,
                $subject,
                $body,
                $template['id']
            ]);

            // Sett Automailer_Sent = 2 (purring sendt)
            $updateStmt->execute([$m['member_id']]);

            $count++;
            echo "[" . date('Y-m-d H:i:s') . "] Purring sendt → {$m['email']} (utløpt {$expiryFmt})\n";
        }

        $totalQueued += $count;
        echo "[" . date('Y-m-d H:i:s') . "] Bedrift {$companyId} ({$companyName}): {$count} purring(er) lagt i kø.\n";
    }

    echo "[" . date('Y-m-d H:i:s') . "] Ferdig. Totalt {$totalQueued} purring(er) lagt i kø.\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] FEIL: " . $e->getMessage() . "\n";
    exit(1);
}
