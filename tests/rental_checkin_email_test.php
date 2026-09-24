<?php

require_once __DIR__ . '/../includes/rental_checkin_email.php';

function rental_checkin_check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$defaults = rentalCheckinTemplateDefaults();
rental_checkin_check(
    str_contains($defaults['body'], '{{contact_person}}')
        && str_contains($defaults['body'], '{{contact_phone}}'),
    'default check-in template uses dynamic contact tags'
);
rental_checkin_check(
    !str_contains($defaults['body'], 'Britt Inger Gusdal')
        && !str_contains($defaults['body'], '48145008'),
    'default check-in template has no hard-coded contact details'
);
$hardCodedContact = '<strong>Fast kontakt</strong><a href="tel:99887766">99887766</a>';
rental_checkin_check(
    renderRentalConfirmationTemplate($hardCodedContact, [
        'contact_person' => 'Skal ikke brukes',
        'contact_phone' => '11111111',
    ]) === $hardCodedContact,
    'hard-coded contact details remain unchanged without explicit tags'
);

$vars = buildRentalConfirmationVariables([
    'customer_name' => 'Ola Nordmann',
    'object_name' => 'Testlokale',
    'start_date' => '2026-09-20',
    'end_date' => '2026-09-21',
    'pickup_time' => '12:00:00',
    'end_time' => '16:00:00',
    'key_box_code' => '123456',
    'contact_person' => 'Utleieansvarlig',
    'contact_phone' => '12345678',
    'delivery_address' => 'Testveien 1',
    'company_name' => 'Utleie AS',
]);
$rendered = renderRentalConfirmationTemplate(
    '{{tenant_name}}|{{rental_object}}|{{key_box_code}}|{{contact_person}}|{{contact_phone}}|{{pickup_address}}',
    $vars
);
rental_checkin_check(
    $rendered === 'Utleie AS|Testlokale|123456|Utleieansvarlig|12345678|Testveien 1',
    'check-in variables render the access code and contact/address values'
);
rental_checkin_check(
    requireValidRentalVenueAccessCode(' AB12 ') === 'AB12',
    'configured venue access code is trimmed and accepted'
);
rental_checkin_check(
    requireValidRentalVenueAccessCode('0') === '0',
    'single-character numeric access code is not treated as missing'
);
foreach ([null, '', '0000', 'TOO-LONG'] as $invalidCode) {
    try {
        requireValidRentalVenueAccessCode($invalidCode);
        rental_checkin_check(false, 'invalid access code must stop check-in queueing');
    } catch (RentalConfirmationEmailException $error) {
        rental_checkin_check(
            str_contains($error->getMessage(), 'gyldig tilgangskode'),
            'invalid access code reports a retryable configuration error'
        );
    }
}

$queueSource = file_get_contents(__DIR__ . '/../includes/rental_checkin_email.php');
rental_checkin_check(
    str_contains($queueSource, 'ec.rental_confirm = 1')
        && !str_contains($queueSource, 'automail_rental_confirm')
        && !str_contains($queueSource, 'rentalNormalizeCheckinTemplate')
        && !str_contains($queueSource, 'Britt Inger Gusdal')
        && !str_contains($queueSource, '48145008'),
    'daily queue requires the dedicated rental_confirm setting'
);
rental_checkin_check(
    str_contains(
        $queueSource,
        "renderRentalConfirmationTemplate(\$tpl['body_template'], \$vars)"
    )
        && str_contains($queueSource, 'SELECT rental_object_id FROM rental_schedule')
        && str_contains($queueSource, 'SELECT rv.access_code')
        && str_contains($queueSource, 'SET key_box_code = ?, checkin_email_queued_at = NOW()')
        && strpos($queueSource, 'SELECT rental_object_id FROM rental_schedule')
            < strpos($queueSource, 'SELECT rv.access_code')
        && strpos($queueSource, 'SET key_box_code = ?, checkin_email_queued_at = NOW()')
            > strpos($queueSource, 'INSERT INTO email_queue'),
    'daily queue locks first, fetches the current venue code, renders it, and snapshots atomically'
);

$processorSource = file_get_contents(__DIR__ . '/../api/process_email_queue.php');
rental_checkin_check(
    str_contains($processorSource, "WHERE id = ? AND company_id = ? AND status = 'queued'")
        && str_contains($processorSource, 'if ($claim->rowCount() !== 1)'),
    'queue processor atomically claims a queued message before SMTP delivery'
);

$migration = file_get_contents(__DIR__ . '/../db_migrations/rental_checkin_email.sql');
$addKeyCodeAt = strpos($migration, 'ADD COLUMN key_box_code');
rental_checkin_check(
    $addKeyCodeAt !== false
        && !str_contains($migration, 'UPDATE rental_schedule rs')
        && str_contains($migration, 'WHERE checkin_email_queued_at IS NULL')
        && str_contains($migration, "UPDATE rental_venues SET access_code = NULL WHERE access_code = '0000'")
        && str_contains($migration, 'ADD UNIQUE KEY uq_email_queue_idempotency'),
    'migration removes early snapshots, old fallback codes, and preserves unique idempotency'
);
$templateMigration = file_get_contents(
    __DIR__ . '/../db_migrations/rental_checkin_template_contact_tags.sql'
);
rental_checkin_check(
    $templateMigration !== false
        && str_contains($templateMigration, "template_key = 'rental_checkin'")
        && str_contains($templateMigration, '{{contact_person}}')
        && str_contains($templateMigration, '{{contact_phone}}')
        && str_contains($templateMigration, '<strong>Britt Inger Gusdal</strong>')
        && str_contains($templateMigration, '>48145008</a>'),
    'legacy contact conversion is an explicit template migration'
);

$uiSource = file_get_contents(__DIR__ . '/../modules/rental_content.php');
rental_checkin_check(
    str_contains($uiSource, 'typeData.access_code = accessCodeInput.value.trim() || null')
        && !str_contains($uiSource, "accessCodeInput.value.trim() || '0000'")
        && !str_contains($uiSource, 'rental-request-key-box-code')
        && str_contains($uiSource, 'handleRentalObjectCardKeydown(event')
        && str_contains($uiSource, 'event.stopPropagation(); viewDetails')
        && str_contains($uiSource, 'role="button" tabindex="0"')
        && str_contains($uiSource, 'onclick="handleRentalObjectCardClick(event,')
        && str_contains($uiSource, 'aria-expanded="false"'),
    'venue code has no fallback, request approval has no code field, and cards have accessible expansion'
);

$automailerReadSource = file_get_contents(__DIR__ . '/../api/get_automailer_settings.php');
$automailerSaveSource = file_get_contents(__DIR__ . '/../api/save_automailer_settings.php');
$webmailSource = file_get_contents(__DIR__ . '/../modules/webmail_content.php');
rental_checkin_check(
    str_contains($automailerReadSource, 'rental_confirm,')
        && str_contains($automailerSaveSource, "'rental_confirm'")
        && str_contains($webmailSource, "{col:'rental_confirm'")
        && str_contains($webmailSource, "rental_confirm:               'rental_checkin'"),
    'rental_confirm can be read, changed, and previewed from automailer settings'
);
rental_checkin_check(
    str_contains($webmailSource, '<code>{{contact_person}}</code>')
        && str_contains($webmailSource, '<code>{{contact_phone}}</code>'),
    'template preview documents the explicit check-in contact tags'
);

echo "Rental check-in email tests passed\n";
