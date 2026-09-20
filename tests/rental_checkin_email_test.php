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

$queueSource = file_get_contents(__DIR__ . '/../includes/rental_checkin_email.php');
rental_checkin_check(
    str_contains($queueSource, 'ec.rental_confirm = 1')
        && !str_contains($queueSource, 'automail_rental_confirm'),
    'daily queue requires the dedicated rental_confirm setting'
);

$processorSource = file_get_contents(__DIR__ . '/../api/process_email_queue.php');
rental_checkin_check(
    str_contains($processorSource, "WHERE id = ? AND company_id = ? AND status = 'queued'")
        && str_contains($processorSource, 'if ($claim->rowCount() !== 1)'),
    'queue processor atomically claims a queued message before SMTP delivery'
);

$migration = file_get_contents(__DIR__ . '/../db_migrations/rental_checkin_email.sql');
$addKeyCodeAt = strpos($migration, 'ADD COLUMN key_box_code');
$backfillKeyCodeAt = strpos($migration, 'UPDATE rental_schedule rs');
rental_checkin_check(
    $addKeyCodeAt !== false
        && $backfillKeyCodeAt !== false
        && $addKeyCodeAt < $backfillKeyCodeAt,
    'migration creates key_box_code before backfilling approved rentals'
);

$uiSource = file_get_contents(__DIR__ . '/../modules/rental_content.php');
rental_checkin_check(
    str_contains($uiSource, "typeData.access_code = accessCodeInput.value.trim() || '0000'")
        && !str_contains($uiSource, 'data.type_data.access_code = accessCodeInput.value.trim()'),
    'venue access code is serialized through initialized type data'
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

echo "Rental check-in email tests passed\n";