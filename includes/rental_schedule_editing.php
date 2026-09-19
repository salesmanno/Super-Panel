<?php

function rentalMoneyToCents($amount): int {
    return (int) round((float) $amount * 100);
}

function rentalBaseExpenses($storedTotal, int $cleaning, $cleaningFee): float {
    $storedCents = rentalMoneyToCents($storedTotal);
    $cleaningCents = max(0, rentalMoneyToCents($cleaningFee));

    return max(0, $storedCents - ($cleaning === 1 ? $cleaningCents : 0)) / 100;
}

function rentalTotalPrice($totalRentalExpenses, int $cleaning, $cleaningFee): float {
    $baseCents = max(0, rentalMoneyToCents($totalRentalExpenses));
    $cleaningCents = max(0, rentalMoneyToCents($cleaningFee));

    return ($baseCents + ($cleaning === 1 ? $cleaningCents : 0)) / 100;
}

function rentalFeeAfterCleaningToggle($currentFee, int $currentCleaning, int $newCleaning, $cleaningFee): float {
    $totalRentalExpenses = rentalBaseExpenses($currentFee, $currentCleaning, $cleaningFee);
    return rentalTotalPrice($totalRentalExpenses, $newCleaning, $cleaningFee);
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