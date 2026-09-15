<?php

/**
 * Opt-in MySQL integration test.
 *
 * It uses existing object/company IDs supplied through the environment and
 * rolls every write back. No fixtures are created or left behind.
 */
if (getenv('RUN_RENTAL_MYSQL_INTEGRATION') !== '1') {
    echo "rental MySQL integration test skipped (set RUN_RENTAL_MYSQL_INTEGRATION=1)\n";
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_customer_fields.php';
require_once __DIR__ . '/../includes/rental_request.php';

$companyA = getenv('RENTAL_TEST_COMPANY_A') ?: '';
$objectA = getenv('RENTAL_TEST_OBJECT_A') ?: '';
$companyB = getenv('RENTAL_TEST_COMPANY_B') ?: '';
$objectB = getenv('RENTAL_TEST_OBJECT_B') ?: '';
if ($companyA === '' || $objectA === '' || $companyB === '' || $objectB === '') {
    fwrite(STDERR, "Set RENTAL_TEST_COMPANY_A/OBJECT_A and _B variables for integration test\n");
    exit(2);
}

$db = getDBConnection();
$db->beginTransaction();
$failures = [];
try {
    foreach ([[$companyA, $objectA], [$companyB, $objectB]] as [$company, $object]) {
        $stmt = $db->prepare(
            'SELECT id FROM rental_objects WHERE id = ? AND company_id = ? FOR UPDATE'
        );
        $stmt->execute([$object, $company]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Configured test object is not tenant-scoped');
        }
    }

    saveRentalCustomerFieldSettings($db, $companyA, $objectA, [
        ['field' => 'first_name', 'selected' => 1, 'required' => 1, 'sort_order' => 0],
    ]);
    saveRentalCustomerFieldSettings($db, $companyB, $objectB, [
        ['field' => 'email', 'selected' => 1, 'required' => 1, 'sort_order' => 0],
    ]);

    $settingsA = getRentalCustomerFieldSettings($db, $companyA, $objectA);
    $settingsB = getRentalCustomerFieldSettings($db, $companyB, $objectB);
    $mapA = [];
    $mapB = [];
    foreach ($settingsA as $setting) $mapA[$setting['field']] = $setting;
    foreach ($settingsB as $setting) $mapB[$setting['field']] = $setting;
    if ($mapA['first_name']['required'] !== 1 || $mapB['email']['required'] !== 1
        || $mapA['email']['required'] !== 0 || $mapB['first_name']['required'] !== 0) {
        $failures[] = 'Tenant/object field settings leaked across objects';
    }

    try {
        validateRentalRequestInput([
            'fields' => [],
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'logistics' => 'pick_up',
        ], $settingsA);
        $failures[] = 'Required first_name was not enforced';
    } catch (InvalidArgumentException $expected) {
        // Expected.
    }
} finally {
    $db->rollBack();
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "rental MySQL integration test passed (rolled back)\n";