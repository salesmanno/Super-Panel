<?php

require_once __DIR__ . '/../includes/rental_confirmation_email.php';

function rental_email_check(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$variables = buildRentalConfirmationVariables([
    'id' => 'RENT-2026-001',
    'customer_name' => 'Kari Nordmann',
    'object_name' => 'Fjordglimt',
    'start_date' => '2026-09-25',
    'end_date' => '2026-09-27',
    'rental_fee' => '4400.00',
    'pickup_time' => '15:00:00',
    'end_time' => '11:00:00',
    'cleaning' => 1,
    'cleaning_fee' => '400.00',
    'logistics' => 'pickup',
    'notes' => 'Sen innsjekk',
    'company_name' => 'Utleie AS',
    'logo_url' => 'https://example.test/logo.png',
    'header_bg_color' => '#123456',
    'contact_phone' => '99 88 77 66',
    'address_street' => 'Testveien 1',
    'address_zip' => '0123',
    'address_city' => 'Oslo',
    'org_number' => '999 888 777',
    'account_number' => '1234.56.78901',
]);

rental_email_check($variables['customer_name'] === 'Kari Nordmann', 'customer name is assembled');
rental_email_check($variables['cus_first_name'] === 'Kari', 'snapshot first name alias is populated');
rental_email_check($variables['cus_last_name'] === 'Nordmann', 'snapshot last name alias is populated');
rental_email_check($variables['date_from'] === '25.09.2026', 'start date uses Norwegian format');
rental_email_check($variables['date_to'] === '27.09.2026', 'end date uses Norwegian format');
rental_email_check($variables['total_price'] === '4 400,00', 'price is formatted');
rental_email_check($variables['price'] === '4 000,00', 'base price excludes selected cleaning');
rental_email_check(
    $variables['total_price'] === '4 400,00'
    && $variables['total_rental_expenses'] === '4 000,00',
    'email total price adds cleaning to the base rental expenses'
);
rental_email_check($variables['rental_start'] === $variables['date_from'], 'legacy start alias is populated');
rental_email_check($variables['rental_fee'] === $variables['total_price'], 'legacy fee alias is populated');
rental_email_check($variables['pickup_time'] === '15:00', 'pickup time is rendered without seconds');
rental_email_check($variables['end_time'] === '11:00', 'end time is rendered without seconds');
rental_email_check($variables['rental_days'] === '3', 'rental days are inclusive');

$withoutCleaning = buildRentalConfirmationVariables([
    'rental_fee' => '4000.00',
    'cleaning' => 0,
    'cleaning_fee' => '400.00',
]);
rental_email_check(
    $withoutCleaning['price'] === '4 000,00'
    && $withoutCleaning['total_price'] === '4 000,00',
    'email total price excludes cleaning when it is not selected'
);

$completeTemplate = implode('|', [
    '{{logo_url}}', '{{first_name}}', '{{last_name}}', '{{tenant_name}}',
    '{{rental_object}}', '{{rental_start}}', '{{rental_end}}', '{{rental_days}}',
    '{{start_date}}', '{{end_date}}', '{{pickup_time}}', '{{end_time}}',
    '{{account_number}}', '{{price}}', '{{total_price}}', '{{order_id}}',
    '{{company_phone}}', '{{company_address}}', '{{org_number}}',
    '{{header_bg_color}}',
]);
$completeRendered = renderRentalConfirmationTemplate($completeTemplate, $variables);
rental_email_check(!str_contains($completeRendered, '{{'), 'all rental template placeholders are rendered');
foreach ([
    'https://example.test/logo.png', 'Kari', 'Nordmann', 'Utleie AS',
    'Fjordglimt', '25.09.2026', '27.09.2026', '3', '15:00', '11:00',
    '1234.56.78901', '4 400,00', 'RENT-2026-001', '99 88 77 66',
    'Testveien 1, 0123 Oslo', '999 888 777', '#123456',
] as $expectedValue) {
    rental_email_check(
        str_contains($completeRendered, $expectedValue),
        "complete rental template contains {$expectedValue}"
    );
}

$rendered = renderRentalConfirmationTemplate(
    '{{customer_name}}: {{object_name}}, {{date_from}}–{{date_to}}, {{total_price}} NOK, {{company_name}} {{unknown}}',
    $variables
);
rental_email_check(
    $rendered === 'Kari Nordmann: Fjordglimt, 25.09.2026–27.09.2026, 4 400,00 NOK, Utleie AS ',
    'template is fully rendered and unknown placeholders are removed'
);

$endpoint = file_get_contents(__DIR__ . '/../api/update_rental_schedule.php');
foreach ([
    "automail_rental_confirm",
    "template_key = 'rental_confirmation'",
    "SELECT id, subject_template, body_template",
    "buildRentalConfirmationVariables",
    "renderRentalConfirmationTemplate",
    "template_id, module_source, source_id",
    "FOR UPDATE",
    "rs.customer_name AS request_customer_name",
    "rs.customer_email AS request_customer_email",
    "FILTER_VALIDATE_EMAIL",
    "throw new RentalConfirmationEmailException",
    "'already_approved' => true",
    "'stale_request' => true",
    "AND (approved = 0 OR approved IS NULL) AND status = 'pending'",
    "validateRentalSchedulePeriod",
    "rentalFeeAfterCleaningToggle",
] as $fragment) {
    rental_email_check(str_contains($endpoint, $fragment), "endpoint contains {$fragment}");
}
rental_email_check(
    strpos($endpoint, '$db->commit();') > strpos($endpoint, 'INSERT INTO email_queue'),
    'approval and queue insert commit together'
);
rental_email_check(!str_contains($endpoint, "template_key = 'rentalinvoice'"), 'legacy wrong template key is removed');
rental_email_check(!str_contains($endpoint, "catch (\\Throwable \$emailErr)"), 'queue failures cannot be swallowed');

echo "Rental confirmation email checks passed\n";