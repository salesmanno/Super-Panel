<?php

function rentalMoneyToCents($amount): int {
    return (int) round((float) $amount * 100);
}

function rentalFeeAfterCleaningToggle($currentFee, int $currentCleaning, int $newCleaning, $cleaningFee): float {
    $currentCents = rentalMoneyToCents($currentFee);
    $cleaningCents = max(0, rentalMoneyToCents($cleaningFee));
    $baseCents = $currentCents - ($currentCleaning === 1 ? $cleaningCents : 0);
    $newTotalCents = max(0, $baseCents + ($newCleaning === 1 ? $cleaningCents : 0));

    return $newTotalCents / 100;
}

function validateRentalSchedulePeriod(
    string $startDate,
    string $pickupTime,
    string $endDate,
    string $endTime
): void {
    $start = new DateTimeImmutable($startDate . ' ' . $pickupTime);
    $end = new DateTimeImmutable($endDate . ' ' . $endTime);
    if ($end < $start) {
        throw new InvalidArgumentException('Sluttdato og sluttid kan ikke være før oppstart.');
    }
}