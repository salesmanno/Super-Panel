<?php

$getEvents = file_get_contents(__DIR__ . '/../api/get_events.php');
$eventsUi = file_get_contents(__DIR__ . '/../modules/events_content.php');
$createEventApi = file_get_contents(__DIR__ . '/../api/create_event.php');
$updateEventApi = file_get_contents(__DIR__ . '/../api/update_event.php');

$failures = [];

$assertContains = static function (string $needle, string $haystack, string $label) use (&$failures): void {
    if (strpos($haystack, $needle) === false) {
        $failures[] = $label;
    }
};

$assertNotContains = static function (string $needle, string $haystack, string $label) use (&$failures): void {
    if (strpos($haystack, $needle) !== false) {
        $failures[] = $label;
    }
};

$assertContains(
    'e.memberverification',
    $getEvents,
    'Arrangementslisten må hente memberverification'
);
$assertContains(
    "memberVerificationCheckbox.checked = event.memberverification == 1",
    $eventsUi,
    'Redigeringsskjemaet må initialisere lagret memberverification'
);
$assertNotContains(
    "setCheckboxFalse('edit-memberverification')",
    $eventsUi,
    'Registreringsfeltene må ikke nullstille memberverification'
);
$assertContains(
    "setFieldValue('edit-start-date', sqlDateToNorsk(event.start_date))",
    $eventsUi,
    'Eksisterende arrangementsdato må vises som norsk dato'
);
$assertContains(
    "validateDateIsNotPast(\$data['start_date'])",
    $createEventApi,
    'API-et må blokkere oppretting med fortidsdato'
);
$assertNotContains(
    'validateDateIsNotPast',
    $updateEventApi,
    'API-et må fortsatt tillate redigering av historiske arrangementer'
);
$assertContains(
    'validateNewEventStartDate(this)',
    $eventsUi,
    'Opprettingsskjemaet må blokkere fortidsdato i nettleseren'
);

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "event edit regression tests passed\n";