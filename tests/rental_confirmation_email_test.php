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
    'status' => 'approved',
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
rental_email_check($variables['pickup_time'] === '08:00', 'pickup time is fixed at 08:00');
rental_email_check($variables['end_time'] === '11:00', 'end time is rendered without seconds');
rental_email_check($variables['rental_days'] === '3', 'rental days are inclusive');
rental_email_check($variables['status'] === 'Godkjent', 'approved status is ready for display');
rental_email_check($variables['cleaning'] === 1, 'cleaning remains a boolean-like integer');
rental_email_check($variables['cleaning_label'] === 'Ja', 'cleaning label remains available for legacy templates');

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
    '{{header_bg_color}}', '{{status}}', '{{cleaning}}', '{{cleaning_fee}}',
]);
$completeRendered = renderRentalConfirmationTemplate($completeTemplate, $variables);
rental_email_check(!str_contains($completeRendered, '{{'), 'all rental template placeholders are rendered');
foreach ([
    'https://example.test/logo.png', 'Kari', 'Nordmann', 'Utleie AS',
    'Fjordglimt', '25.09.2026', '27.09.2026', '3', '08:00', '11:00',
    '1234.56.78901', '4 400,00', 'RENT-2026-001', '99 88 77 66',
    'Testveien 1, 0123 Oslo', '999 888 777', '#123456', 'Godkjent', '400,00',
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

$nestedTemplate = '{{#if (neq status "Avslått")}}GODKJENT {{#if cleaning}}MED RENHOLD{{/if}}{{else}}AVSLÅTT{{/if}}';
rental_email_check(
    renderRentalConfirmationTemplate($nestedTemplate, $variables) === 'GODKJENT MED RENHOLD',
    'nested approved and cleaning branches render'
);
$rejectedVariables = buildRentalConfirmationVariables([
    'status' => 'rejected',
    'cleaning' => 0,
]);
rental_email_check($rejectedVariables['status'] === 'Avslått', 'rejected status is ready for display');
rental_email_check(
    renderRentalConfirmationTemplate($nestedTemplate, $rejectedVariables) === 'AVSLÅTT',
    'rejected branch renders and omits nested approved content'
);
foreach ([
    '{{#if (eq status "Godkjent")}}OK{{else}}NO{{else}}BAD{{/if}}',
    '{{#if (unknown status "Godkjent")}}OK{{else}}NO{{/if}}',
    '{{#if cleaning}}OK',
] as $malformedTemplate) {
    try {
        renderRentalConfirmationTemplate($malformedTemplate, $variables);
        rental_email_check(false, 'malformed conditional template must be rejected');
    } catch (RentalConfirmationEmailException $expected) {
        // Expected.
    }
}
$invalidInactiveBranch = '{{#if cleaning}}OK{{else}}{{#if (invalid status "x")}}BAD{{/if}}{{/if}}';
try {
    renderRentalConfirmationTemplate($invalidInactiveBranch, $variables);
    rental_email_check(false, 'invalid inactive branch must be rejected');
} catch (RentalConfirmationEmailException $expected) {
    // Expected.
}

$suppliedTemplate = file_get_contents(
    __DIR__ . '/../attached_assets/Pasted--E-postmal-for-bekreftelse-av-utleieperiode-Brukes-for-_1789844944612.txt'
);
rental_email_check($suppliedTemplate !== false, 'supplied rental decision template is readable');
$defaultTemplates = rentalDecisionDefaultTemplates();
rental_email_check(
    $defaultTemplates['body'] === $suppliedTemplate,
    'deployed default body is the supplied rental decision template'
);
$resolvedLegacyTemplates = resolveRentalDecisionTemplates(
    'Din leie er bekreftet',
    '<h1>Reservasjonen er bekreftet</h1>'
);
rental_email_check(
    $resolvedLegacyTemplates['body'] === $suppliedTemplate
    && str_contains($resolvedLegacyTemplates['subject'], '{{status}}'),
    'legacy approval-only database templates use the deployed decision defaults'
);
$approvedHtml = renderRentalConfirmationTemplate($suppliedTemplate, $variables);
$rejectedHtml = renderRentalConfirmationTemplate($suppliedTemplate, array_merge($variables, [
    'status' => 'Avslått',
    'cleaning' => 0,
]));
rental_email_check(!str_contains($approvedHtml, '{{'), 'approved supplied template is fully rendered');
rental_email_check(str_contains($approvedHtml, 'Godkjent leie av Fjordglimt'), 'approved heading is rendered');
rental_email_check(str_contains($approvedHtml, 'Totalt å betale'), 'approved email includes payment content');
rental_email_check(!str_contains($rejectedHtml, '{{'), 'rejected supplied template is fully rendered');
rental_email_check(
    str_contains($rejectedHtml, 'Leieforespørselen din er avslått'),
    'rejected heading is rendered'
);
rental_email_check(!str_contains($rejectedHtml, 'Totalt å betale'), 'rejected email omits payment content');
$legacyApprovedSubject = renderRentalConfirmationTemplate(
    $resolvedLegacyTemplates['subject'],
    $variables
);
$legacyRejectedSubject = renderRentalConfirmationTemplate(
    $resolvedLegacyTemplates['subject'],
    array_merge($variables, ['status' => 'Avslått'])
);
rental_email_check(
    str_starts_with($legacyApprovedSubject, 'Godkjent:')
    && str_starts_with($legacyRejectedSubject, 'Avslått:'),
    'deployed fallback subject identifies both decisions'
);

$endpoint = file_get_contents(__DIR__ . '/../api/update_rental_schedule.php');
foreach ([
    "automail_rental_confirm",
    "template_key = 'rental_confirmation'",
    "SELECT id, subject_template, body_template",
    "buildRentalConfirmationVariables",
    "resolveRentalDecisionTemplates",
    "renderRentalConfirmationTemplate",
    "template_id, module_source, source_id",
    "FOR UPDATE",
    "rs.customer_name AS request_customer_name",
    "rs.customer_email AS request_customer_email",
    "FILTER_VALIDATE_EMAIL",
    "throw new RentalConfirmationEmailException",
    "'already_approved' => \$alreadyApproved",
    "'stale_request' => true",
    "AND (approved = 0 OR approved IS NULL) AND status = 'pending'",
    "validateRentalSchedulePeriod",
    "rentalPeriodPriceCents",
    "price_adjustment_type",
    "key_box_code",
] as $fragment) {
    rental_email_check(str_contains($endpoint, $fragment), "endpoint contains {$fragment}");
}
rental_email_check(
    strpos($endpoint, '$db->commit();') > strpos($endpoint, 'INSERT INTO email_queue'),
    'approval and queue insert commit together'
);
rental_email_check(!str_contains($endpoint, "template_key = 'rentalinvoice'"), 'legacy wrong template key is removed');
rental_email_check(!str_contains($endpoint, "catch (\\Throwable \$emailErr)"), 'queue failures cannot be swallowed');

$scheduleEndpoint = file_get_contents(__DIR__ . '/../api/get_rental_schedule.php');
rental_email_check(
    str_contains($scheduleEndpoint, "rs.approved = 1 AND rs.status = \\'approved\\'"),
    'approved schedule filter excludes processed rejections'
);
$requestEndpoint = file_get_contents(__DIR__ . '/../api/create_rental_request.php');
rental_email_check(
    str_contains($requestEndpoint, "rs.status IN ('approved', 'active')")
    && !str_contains($requestEndpoint, 'rs.approved = 1 OR'),
    'rejected decisions do not reserve rental dates'
);

echo "Rental confirmation email checks passed\n";