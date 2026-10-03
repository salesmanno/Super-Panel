<?php

/** Calendar expiry follows the Norwegian day, not the database/server timezone. */
function rentalUnavailableToday(?DateTimeImmutable $now = null): string
{
    $zone = new DateTimeZone('Europe/Oslo');
    return ($now ?? new DateTimeImmutable('now', $zone))->setTimezone($zone)->format('Y-m-d');
}

function normalizeRentalUnavailableNotes($notes): ?string
{
    if ($notes === null) {
        return null;
    }
    if (!is_string($notes)) {
        throw new InvalidArgumentException('Stikkord / årsak må være tekst.');
    }
    $notes = trim($notes);
    if (mb_strlen($notes, 'UTF-8') > 500) {
        throw new InvalidArgumentException('Stikkord / årsak kan inneholde maksimalt 500 tegn.');
    }
    return $notes === '' ? null : $notes;
}