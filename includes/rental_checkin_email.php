<?php
require_once __DIR__ . '/tenant_audit.php';

require_once __DIR__ . '/rental_confirmation_email.php';

function rentalCheckinTemplateDefaults(): array
{
    $path = __DIR__ . '/../attached_assets/Pasted--E-postmal-for-innsjekk-av-utleie-Sendes-til-leietaker-_1789895464304.txt';
    $body = file_get_contents($path);
    if ($body === false) {
        throw new RentalConfirmationEmailException('Innsjekkmalen kunne ikke lastes.');
    }
    return [
        'subject' => 'Innsjekk for {{rental_object}} – {{start_date}}',
        'body' => $body,
    ];
}

function requireValidRentalVenueAccessCode(?string $accessCode): string
{
    $accessCode = trim((string) $accessCode);
    if ($accessCode === '' || $accessCode === '0000'
        || !preg_match('/^[A-Za-z0-9]{1,6}$/', $accessCode)) {
        throw new RentalConfirmationEmailException(
            'Utleieobjektet mangler en gyldig tilgangskode.'
        );
    }

    return $accessCode;
}

function queueRentalCheckinEmails(
    PDO $db,
    ?string $today = null,
    ?string $companyId = null
): int
{
    if ($db instanceof AuditedPDO && !$db->isAutomaticAuditSuppressed()) {
        return $db->withExplicitAudit(static fn() => queueRentalCheckinEmails($db, $today, $companyId));
    }
    $today = $today ?: date('Y-m-d');
    $companyScope = $companyId === null ? '' : ' AND rs.company_id = ?';
    $find = $db->prepare("
        SELECT rs.*, ro.object_name, ro.rental_text,
               COALESCE(rv.cleaning_fee, 0) AS cleaning_fee,
               tc.email AS profile_customer_email, tc.first_name, tc.last_name,
               company.name AS company_name, company.contact_phone,
               ec.display_name AS contact_person,
               company.address_street, company.address_zip, company.address_city,
               company.org_number,
               COALESCE(NULLIF(twg.logo_url, ''), '') AS logo_url,
               COALESCE(NULLIF(twg.header_bg_color, ''), '#ffffff') AS header_bg_color
        FROM rental_schedule rs
        INNER JOIN rental_objects ro ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        LEFT JOIN rental_venues rv ON rv.object_id = ro.id
        LEFT JOIN tenant_customers tc ON tc.id = rs.customer_id AND tc.company_id = rs.company_id
        LEFT JOIN tenant_companies company ON company.id = rs.company_id
        LEFT JOIN tenant_website_general twg ON twg.company_id = rs.company_id
        INNER JOIN email_configs ec ON ec.company_id = rs.company_id
            AND ec.is_active = 1 AND ec.rental_confirm = 1
        WHERE rs.approved = 1 AND rs.status = 'approved'
          AND rs.start_date = ? AND rs.checkin_email_queued_at IS NULL
          {$companyScope}
        ORDER BY rs.company_id, rs.id
    ");
    $findParams = [$today];
    if ($companyId !== null) {
        $findParams[] = $companyId;
    }
    $find->execute($findParams);
    $rentals = $find->fetchAll(PDO::FETCH_ASSOC);
    $queued = 0;

    foreach ($rentals as $rental) {
        $db->beginTransaction();
        try {
            $auditActor = tenantAuditSystemActor($db, (string) $rental['company_id'], 'rental_checkin');
            $auditCorrelation = tenantAuditId();
            $lock = $db->prepare(
                "SELECT rental_object_id FROM rental_schedule
                 WHERE id = ? AND company_id = ?
                   AND approved = 1 AND status = 'approved'
                   AND start_date = ? AND checkin_email_queued_at IS NULL
                 FOR UPDATE"
            );
            $lock->execute([$rental['id'], $rental['company_id'], $today]);
            $rentalObjectId = $lock->fetchColumn();
            if (!$rentalObjectId) {
                $db->rollBack();
                continue;
            }
            $codeStmt = $db->prepare(
                "SELECT rv.access_code
                 FROM rental_venues rv
                 INNER JOIN rental_objects ro
                   ON ro.id = rv.object_id AND ro.company_id = ?
                 WHERE rv.object_id = ?
                 LIMIT 1"
            );
            $codeStmt->execute([$rental['company_id'], $rentalObjectId]);
            $currentAccessCode = $codeStmt->fetchColumn();
            $rental['key_box_code'] = requireValidRentalVenueAccessCode(
                $currentAccessCode === false ? null : (string) $currentAccessCode
            );
            $cfgStmt = $db->prepare(
                "SELECT email, display_name FROM email_configs
                 WHERE company_id = ? AND is_active = 1 AND rental_confirm = 1 LIMIT 1"
            );
            $cfgStmt->execute([$rental['company_id']]);
            $cfg = $cfgStmt->fetch(PDO::FETCH_ASSOC);
            $recipient = trim((string) ($rental['customer_email'] ?: $rental['profile_customer_email']));
            if (!$cfg || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
                || !filter_var($cfg['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
                $db->rollBack();
                continue;
            }
            $tplStmt = $db->prepare(
                "SELECT id, subject_template, body_template FROM email_templates
                 WHERE company_id = ? AND is_active = 1
                   AND (LOWER(name) = 'innsjekk utleie' OR template_key = 'rental_checkin')
                  ORDER BY (template_key = 'rental_checkin') DESC LIMIT 1"
            );
            $tplStmt->execute([$rental['company_id']]);
            $tpl = $tplStmt->fetch(PDO::FETCH_ASSOC);
            if (!$tpl) {
                $defaults = rentalCheckinTemplateDefaults();
                $db->prepare(
                    "INSERT INTO email_templates
                     (id, company_id, template_key, name, subject_template, body_template,
                      is_active, created_at, updated_at)
                     VALUES (UUID(), ?, 'rental_checkin', 'innsjekk utleie', ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                       name = VALUES(name),
                       subject_template = VALUES(subject_template),
                       body_template = VALUES(body_template),
                       is_active = 1,
                       updated_at = NOW()"
                )->execute([
                    $rental['company_id'],
                    $defaults['subject'],
                    $defaults['body'],
                ]);
                $tplStmt->execute([$rental['company_id']]);
                $tpl = $tplStmt->fetch(PDO::FETCH_ASSOC);
                if (!$tpl) {
                    throw new RentalConfirmationEmailException(
                        'E-postmalen «innsjekk utleie» kunne ikke opprettes.'
                    );
                }
                tenantAuditRecord($db, (string) $rental['company_id'], 'email', 'email.template.bootstrap',
                    'email_templates', (string) $tpl['id'], 'innsjekk utleie', [],
                    $tpl + ['name' => 'innsjekk utleie','template_key' => 'rental_checkin','is_active' => 1],
                    'success', ['correlation_id' => $auditCorrelation], $auditActor);
            }
            $vars = buildRentalConfirmationVariables($rental);
            $subject = renderRentalConfirmationTemplate($tpl['subject_template'], $vars);
            $body = renderRentalConfirmationTemplate($tpl['body_template'], $vars);
            $key = 'rental-checkin:' . $rental['company_id'] . ':' . $rental['id'] . ':' . $today;
            $insert = $db->prepare(
                "INSERT INTO email_queue
                 (id, company_id, recipient_email, from_email, subject, body, template_id,
                  module_source, source_id, idempotency_key, status, priority, scheduled_at, created_at, updated_at)
                 VALUES (UUID(), ?, ?, ?, ?, ?, ?, 'rental_checkin', ?, ?, 'queued', 5, NOW(), NOW(), NOW())"
            );
            $insert->execute([
                $rental['company_id'], $recipient, $cfg['email'], $subject, $body,
                $tpl['id'], $rental['id'], $key,
            ]);
            $queueIdQuery = $db->prepare('SELECT id FROM email_queue WHERE company_id = ? AND idempotency_key = ? LIMIT 1');
            $queueIdQuery->execute([$rental['company_id'], $key]);
            $auditQueueId = (string) $queueIdQuery->fetchColumn();
            if ($auditQueueId === '') throw new TenantAuditException('Innsjekk-køens identitet kunne ikke bekreftes.');
            tenantAuditRecord($db, (string) $rental['company_id'], 'email', 'email.queued', 'email_queue',
                $auditQueueId, 'Automatisk innsjekk utleie', [],
                ['id' => $auditQueueId, 'template_id' => $tpl['id'], 'status' => 'queued',
                    'module_source' => 'rental_checkin', 'source_id' => $rental['id']],
                'success', [
                    'queue_id' => $auditQueueId, 'template_id' => $tpl['id'], 'source_id' => $rental['id'],
                    'source_module' => 'rental_checkin', 'correlation_id' => $auditCorrelation,
                    'template_body_sha256' => hash('sha256', (string) $tpl['body_template']),
                    'template_subject_sha256' => hash('sha256', (string) $tpl['subject_template']),
                ], $auditActor);
            $db->prepare(
                "UPDATE rental_schedule
                 SET key_box_code = ?, checkin_email_queued_at = NOW()
                 WHERE id = ? AND company_id = ?"
            )->execute([
                $rental['key_box_code'],
                $rental['id'],
                $rental['company_id'],
            ]);
            $db->commit();
            $queued++;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ((int) $e->getCode() !== 23000) {
                error_log('Rental innsjekk-kø feil: ' . $e->getMessage());
            }
        }
    }
    return $queued;
}