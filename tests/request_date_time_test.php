<?php

require_once __DIR__ . '/../includes/request_date_time.php';

$failures = [];

$assertSame = static function ($expected, $actual, string $label) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $label . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true);
    }
};

$assertThrows = static function (callable $callback, string $label) use (&$failures): void {
    try {
        $callback();
        $failures[] = $label . ': expected InvalidArgumentException';
    } catch (InvalidArgumentException $e) {
        // Expected.
    }
};

$assertDoesNotThrow = static function (callable $callback, string $label) use (&$failures): void {
    try {
        $callback();
    } catch (Throwable $e) {
        $failures[] = $label . ': unexpected ' . get_class($e) . ': ' . $e->getMessage();
    }
};

$assertSame('2024-02-29', normalizeRequestDate('29.02.2024'), 'Norwegian leap date');
$assertSame('2024-02-29', normalizeRequestDate('2024-02-29'), 'Canonical date');
$assertSame(null, normalizeRequestDate(null), 'Optional null date');
$assertSame('09:05:00', normalizeRequestTime('09:05'), 'Short time');
$assertSame('23:59:58', normalizeRequestTime('23:59:58'), 'Full time');
$assertSame(null, normalizeRequestTime(''), 'Optional empty time');
$assertDoesNotThrow(
    static fn() => validateDateIsNotPast('2026-09-14', 'Startdato', '2026-09-14'),
    'Creating an event today is allowed'
);
$assertDoesNotThrow(
    static fn() => validateDateIsNotPast('2026-10-07', 'Startdato', '2026-09-14'),
    'Creating a future event is allowed'
);
$assertThrows(
    static fn() => validateDateIsNotPast('2026-07-10', 'Startdato', '2026-09-14'),
    'Creating a past event is rejected'
);

$assertThrows(
    static fn() => normalizeRequestDate('31.04.2024'),
    'Impossible Norwegian date'
);
$assertThrows(
    static fn() => normalizeRequestDate('2024-02-30'),
    'Impossible canonical date'
);
$assertThrows(
    static fn() => normalizeRequestDate('1.01.2024'),
    'Non-strict date format'
);
$assertThrows(
    static fn() => normalizeRequestTime('24:00'),
    'Hour outside 24-hour clock'
);
$assertThrows(
    static fn() => normalizeRequestTime('12:60:00'),
    'Minute outside 24-hour clock'
);
$assertThrows(
    static fn() => normalizeRequestTime('9:00'),
    'Non-strict time format'
);
$assertThrows(
    static fn() => normalizeRequestTime('09:00 AM'),
    'AM suffix is rejected'
);
$assertThrows(
    static fn() => normalizeRequestTime('09:00 PM'),
    'PM suffix is rejected'
);
$assertThrows(
    static fn() => normalizeRequestDate('', 'Dato', false),
    'Required date cannot be empty'
);

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "request_date_time tests passed\n";