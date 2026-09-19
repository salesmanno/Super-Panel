<?php

require_once __DIR__ . '/../includes/rental_confirmation_email.php';

function rental_email_check(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$variables = buildRentalConfirmationVariables([
    'customer_name' => 'Kari Nordmann',
    'object_name' => 'Fjordglimt',
    'start_date' => '2026-09-25',
    'end_date' => '2026-09-27',
    'rental_fee' => '4400.00',
    'pickup_time' => '15:00:00',
    'end_time' => '11:00:00',
    'logistics' => 'pickup',
    'notes' => 'Sen innsjekk',
    'company_name' => 'Utleie AS',
]);

rental_email_check($variables['customer_name'] === 'Kari Nordmann', 'customer name is assembled');
rental_email_check($variables['cus_first_name'] === 'Kari', 'snapshot first name alias is populated');
rental_email_check($variables['cus_last_name'] === 'Nordmann', 'snapshot last name alias is populated');
rental_email_check($variables['date_from'] === '25.09.2026', 'start date uses Norwegian format');
rental_email_check($variables['date_to'] === '27.09.2026', 'end date uses Norwegian format');
rental_email_check($variables['total_price'] === '4 400,00', 'price is formatted');
rental_email_check($variables['rental_start'] === $variables['date_from'], 'legacy start alias is populated');
rental_email_check($variables['rental_fee'] === $variables['total_price'], 'legacy fee alias is populated');
rental_email_check($variables['pickup_time'] === '15:00', 'pickup time is rendered without seconds');
rental_email_check($variables['end_time'] === '11:00', 'end time is rendered without seconds');

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