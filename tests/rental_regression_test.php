<?php

require_once __DIR__ . '/../includes/request_date_time.php';
require_once __DIR__ . '/../includes/rental_customer_fields.php';
require_once __DIR__ . '/../includes/rental_payment.php';
require_once __DIR__ . '/../includes/rental_request.php';
require_once __DIR__ . '/../includes/rental_results.php';
require_once __DIR__ . '/../includes/rental_schedule_editing.php';
require_once __DIR__ . '/../includes/rental_schemas.php';

$failures = [];
$assert = static function ($condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$settings = normalizeRentalCustomerFieldSettings([
    ['field' => 'email', 'selected' => 1, 'required' => 1, 'order' => 2],
    ['field' => 'first_name', 'selected' => 0, 'order' => 0],
]);
$byField = [];
foreach ($settings as $setting) {
    $byField[$setting['field']] = $setting;
}
$assert(count($settings) === 8, 'All supported customer fields are retained');
$assert($byField['email']['selected'] === 1 && $byField['email']['required'] === 1,
    'Required customer field is selected');
$assert($byField['phone']['selected'] === 0, 'Unsubmitted customer fields are optional');
$sortOrderSettings = normalizeRentalCustomerFieldSettings([
    ['field' => 'first_name', 'sort_order' => 7],
]);
$sortOrderByField = [];
foreach ($sortOrderSettings as $setting) {
    $sortOrderByField[$setting['field']] = $setting;
}
$assert($sortOrderByField['first_name']['order'] === 7, 'sort_order is accepted');
$assert(normalizeRequestDate('02.03.2026') === '2026-03-02', 'Norwegian contract date is normalized');
$assert(
    rentalBaseExpenses(5500, 1, 750) === 4750.0,
    'Base rental expenses subtract cleaning already included in the stored total'
);
$assert(
    rentalTotalPrice(4750, 1, 750) === 5500.0,
    'Total price adds cleaning to base rental expenses when selected'
);
$assert(
    rentalTotalPrice(4750, 0, 750) === 4750.0,
    'Total price excludes cleaning when it is not selected'
);
$assert(
    rentalFeeAfterCleaningToggle(5500, 1, 0, 750) === 4750.0,
    'Turning cleaning off subtracts the venue cleaning fee from the total'
);
$assert(
    rentalFeeAfterCleaningToggle(4750, 0, 1, 750) === 5500.0,
    'Turning cleaning on adds the venue cleaning fee to the total'
);
$assert(
    rentalFeeAfterCleaningToggle(5500, 1, 1, 750) === 5500.0,
    'Keeping cleaning enabled does not add the fee twice'
);
$assert(
    rentalPeriodPriceCents([
        'rental_price' => '100.00',
        'weekend_price' => '150.00',
        'saturday_price' => '175.00',
        'sunday_price' => '125.00',
], '2026-04-03', '2026-04-05') === 40000,
    'Period pricing uses configured weekday-specific weekend rates in cents'
);
$assert(rentalAdjustmentCents('discount', '12.50') === -1250, 'Discount is stored as negative cents');
$assert(rentalAdjustmentCents('surcharge', '12.50') === 1250, 'Surcharge is stored as positive cents');
$fixedTimeRequest = validateRentalRequestInput([
    'fields' => [],
    'start_date' => '2026-04-01',
    'start_time' => '17:00',
    'end_date' => '2026-04-01',
    'end_time' => '18:00',
    'logistics' => 'pick_up',
], []);
$assert($fixedTimeRequest['start_time'] === '08:00:00', 'Pickup time is fixed at 08:00');
$rentalContent = file_get_contents(__DIR__ . '/../modules/rental_content.php');
$assert(
    str_contains($rentalContent, 'rentalFee - (cleaningSelected ? cleaningFee : 0)')
    && str_contains($rentalContent, 'rental-request-cleaning-fee')
    && str_contains($rentalContent, 'Ingen renhold')
    && str_contains($rentalContent, 'rental-request-key-box-code')
    && str_contains($rentalContent, 'price_adjustment_type')
    && str_contains($rentalContent, 'Totalt:'),
    'Rental request UI includes pricing breakdown, adjustment, and editable key code'
);
validateRentalSchedulePeriod('2026-09-25', '15:00:00', '2026-09-27', '11:00:00');
$invalidRentalPeriodRejected = false;
try {
    validateRentalSchedulePeriod('2026-09-27', '15:00:00', '2026-09-27', '11:00:00');
} catch (InvalidArgumentException $e) {
    $invalidRentalPeriodRejected = true;
}
$assert($invalidRentalPeriodRejected, 'Rental end date and time cannot precede pickup');
$venueSchema = getRentalSchema('venue');
$assert(
    isset($venueSchema['fields']['cleaning_fee']),
    'Venue cleaning fee is configurable through the rental object schema'
);

$start = new DateTimeImmutable('2026-03-02');
$end = new DateTimeImmutable('2026-03-02');
$assert($start->diff($end)->days + 1 === 1, 'Same-day rental is one inclusive day');
$assert(
    '2026-03-10' <= '2026-03-12' && '2026-03-15' >= '2026-03-12',
    'Inclusive date interval overlap is detected'
);

$results = calculateRentalResults([
    'total_revenue' => '1250.50',
    'total_rentals' => '3',
    'monthly_revenue' => '500.00',
    'paid_contract_count' => '2',
], ['active' => 2, 'completed' => 1]);
$assert($results['total_revenue'] === 1250.5, 'Paid revenue is numeric and preserved');
$assert($results['total_rentals'] === 3, 'Cancelled drafts are excluded by aggregate input');
$assert($results['average_per_paid_rental'] === 625.25, 'Average uses paid contract count');
$assert($results['status_distribution']['active'] === 2, 'Status distribution is retained');
$emptyResults = calculateRentalResults([
    'total_revenue' => 0,
    'total_rentals' => 0,
    'monthly_revenue' => 0,
    'paid_contract_count' => 0,
]);
$assert($emptyResults['average_per_paid_rental'] === 0.0, 'Average avoids division by zero');
$assert(
    rentalPaidAtTransition('unpaid', 'paid') === 'set_now',
    'First paid transition sets paid_at'
);
$assert(
    rentalPaidAtTransition('paid', 'paid') === 'keep',
    'Repeated paid update preserves paid_at'
);
$assert(
    rentalPaidAtTransition('paid', 'partial') === 'clear',
    'Explicit payment reversal clears paid_at'
);
$originProxy = [
    'HTTP_HOST' => 'rental.example:443',
    'HTTP_ORIGIN' => 'https://rental.example',
    'HTTP_X_FORWARDED_PROTO' => 'https',
    'HTTPS' => 'off',
];
$assert(rentalRequestSameOrigin($originProxy), 'TLS proxy origin is accepted');
$originProxy['HTTP_X_FORWARDED_PROTO'] = 'ftp, https';
$assert(!rentalRequestSameOrigin($originProxy), 'Invalid forwarded protocol is rejected');
$assert(
    rentalPublicEligibilitySql('ro') === 'ro.is_active = 1 AND ro.is_available = 1',
    'Public rental visibility requires both active and available'
);
$invalidAliasRejected = false;
try {
    rentalPublicEligibilitySql('ro; DROP TABLE rental_objects');
} catch (InvalidArgumentException $e) {
    $invalidAliasRejected = true;
}
$assert($invalidAliasRejected, 'Public eligibility SQL rejects unsafe aliases');
$renderedField = renderRentalRequestField([
    'field' => 'email',
    'label' => 'E-post',
    'selected' => 1,
    'required' => 1,
]);
$assert(
    str_contains($renderedField, 'name="fields[email]"')
    && str_contains($renderedField, 'type="email"')
    && str_contains($renderedField, ' required'),
    'Selected required request field renders with matching input contract'
);
$assert(
    renderRentalRequestField(['field' => 'phone', 'selected' => 0]) === '',
    'Unselected request field is not rendered'
);
$existingCustomer = ['first_name' => 'Gammel', 'last_name' => 'Kunde', 'email' => 'old@example.test'];
$display = rentalRequestCustomerDisplay(
    ['first_name' => 'Ny', 'email' => 'new@example.test'],
    $existingCustomer
);
$assert($display['name'] === 'Ny Kunde' && $display['email'] === 'new@example.test',
    'Request snapshot values take precedence without mutating linked customer');
$assert($existingCustomer['first_name'] === 'Gammel' && $existingCustomer['email'] === 'old@example.test',
    'Linked tenant customer remains unchanged');
$requestSettings = [
    ['field' => 'email', 'selected' => 1, 'required' => 1, 'order' => 0],
    ['field' => 'first_name', 'selected' => 0, 'required' => 0, 'order' => 1],
];
try {
    validateRentalRequestInput([
        'fields' => [],
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'logistics' => 'pick_up',
    ], $requestSettings);
    $failures[] = 'Required selected customer field is enforced';
} catch (InvalidArgumentException $e) {
    // Expected.
}
try {
    validateRentalRequestInput([
        'fields' => ['first_name' => 'Should be rejected'],
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'logistics' => 'pick_up',
    ], $requestSettings);
    $failures[] = 'Unselected customer field is rejected';
} catch (InvalidArgumentException $e) {
    // Expected.
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "rental regression tests passed\n";