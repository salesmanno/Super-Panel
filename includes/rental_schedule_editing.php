<?php

function rentalDecisionVersion(array $row): string {
    $values = [];
    foreach (['id','company_id','rental_object_id','status','start_date','end_date',
        'pickup_time','return_time','end_time','price_adjustment_type','price_adjustment_explanation'] as $key) {
        $values[$key] = (string)($row[$key] ?? '');
    }
    foreach (['approved','cleaning','price_adjustment_cents'] as $key) $values[$key]=(int)($row[$key]??0);
    $values['rental_fee']=number_format((float)($row['rental_fee']??0),2,'.','');
    return hash('sha256',json_encode($values,JSON_THROW_ON_ERROR));
}

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
    $base = rentalConfiguredRateCents($object, 'rental_price', 'Dagsprisen', true);
    $weekend = rentalConfiguredRateCents($object, 'weekend_price', 'Helgeprisen');
    $saturday = rentalConfiguredRateCents($object, 'saturday_price', 'Lørdagsprisen');
    $sunday = rentalConfiguredRateCents($object, 'sunday_price', 'Søndagsprisen');
    $total = 0;
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        $day = (int) $date->format('N');
        $total += $day === 6 && $saturday > 0 ? $saturday
            : ($day === 7 && $sunday > 0 ? $sunday
                : (($day >= 6 && $weekend > 0) ? $weekend : $base));
    }
    return $total;
}

function rentalConfiguredRateCents(array $object, string $field, string $label, bool $required = false): int {
    if (!array_key_exists($field, $object)
        || $object[$field] === null
        || (is_string($object[$field]) && trim($object[$field]) === '')) {
        if ($required) {
            throw new InvalidArgumentException(
                $label . ' mangler. Oppdater prisene på utleieobjektet.'
            );
        }
        return 0;
    }

    $value = str_replace(',', '.', trim((string) $object[$field]));
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
        throw new InvalidArgumentException(
            $label . ' er ugyldig. Oppdater prisene på utleieobjektet.'
        );
    }

    return rentalAmountToCents($value);
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