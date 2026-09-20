<?php

function rentalAmountToCents($amount): int {
    $value = trim(str_replace(',', '.', (string) $amount));
    if ($value === '' || !preg_match('/^-?\d+(?:\.\d{1,2})?$/', $value)) {
        return 0;
    }
    $negative = str_starts_with($value, '-');
    $value = ltrim($value, '-');
    [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
    $cents = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    return $negative ? -$cents : $cents;
}

function rentalMoneyToCents($amount): int {
    return rentalAmountToCents($amount);
}

function rentalPeriodPriceCents(array $object, string $startDate, string $endDate): int {
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    if ($end < $start) {
        throw new InvalidArgumentException('Sluttdato kan ikke være før startdato.');
    }
    $base = max(0, rentalAmountToCents($object['rental_price'] ?? 0));
    $weekend = max(0, rentalAmountToCents($object['weekend_price'] ?? 0));
    $saturday = max(0, rentalAmountToCents($object['saturday_price'] ?? 0));
    $sunday = max(0, rentalAmountToCents($object['sunday_price'] ?? 0));
    $total = 0;
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        $day = (int) $date->format('N');
        $total += $day === 6 && $saturday > 0 ? $saturday
            : ($day === 7 && $sunday > 0 ? $sunday
                : (($day >= 6 && $weekend > 0) ? $weekend : $base));
    }
    return $total;
}

function rentalAdjustmentCents($type, $amount): int {
    $cents = max(0, rentalMoneyToCents($amount));
    if ($cents === 0) {
        return 0;
    }
    if (!in_array($type, ['discount', 'surcharge'], true)) {
        throw new InvalidArgumentException('Ugyldig type prisjustering.');
    }
    return $type === 'discount' ? -$cents : $cents;
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