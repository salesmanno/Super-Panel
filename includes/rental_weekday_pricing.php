<?php

require_once __DIR__ . '/rental_customer_fields.php';

class RentalWeekdayPriceException extends InvalidArgumentException {}

/** NULL selects the historical flat rate; an active map must cover all weekdays. */
function rentalNormalizeWeekdayPrices($input): ?array {
    if ($input === null) return null;
    if (is_string($input)) {
        try {
            $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RentalWeekdayPriceException('Ukedagsprisene har ugyldig JSON.');
        }
        if ($input === null) return null;
    }
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    if (!is_array($input) || count($input) !== 5 || array_diff(array_keys($input), $days)) {
        throw new RentalWeekdayPriceException('Ukedagsprisene må inneholde mandag til fredag.');
    }
    $prices = [];
    foreach ($days as $day) {
        $value = $input[$day] ?? null;
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new RentalWeekdayPriceException('Alle ukedagspriser må være gyldige beløp.');
        }
        $value = str_replace(',', '.', trim((string)$value));
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $value)) {
            throw new RentalWeekdayPriceException('Ukedagspriser må være positive beløp eller 0, med maksimalt to desimaler.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $cents = ((int)$whole * 100) + (int)str_pad($fraction, 2, '0');
        if ($cents > 9007199254740991) throw new RentalWeekdayPriceException('Ukedagsprisen er for høy.');
        $prices[$day] = $whole . '.' . str_pad($fraction, 2, '0');
    }
    return $prices;
}

function rentalWeekdayPriceSelect(PDO $db, string $alias = ''): string {
    return rentalObjectColumnExists($db, 'weekday_prices')
        ? ($alias === '' ? '' : $alias . '.') . 'weekday_prices'
        : 'NULL';
}

function rentalWeekdayPricingEnabledSelect(PDO $db, string $alias = ''): string {
    return rentalObjectColumnExists($db, 'weekday_prices_enabled')
        ? ($alias === '' ? '' : $alias . '.') . 'weekday_prices_enabled'
        : 'NULL';
}

function rentalNormalizeWeekdayPricingEnabled($value): int {
    if ($value === 1 || $value === '1' || $value === true) return 1;
    if ($value === 0 || $value === '0' || $value === false) return 0;
    throw new RentalWeekdayPriceException('Valget for ukedagspriser må være 0 eller 1.');
}

function rentalWeekdayPricingEnabled(PDO $db, $value): int {
    $enabled = rentalNormalizeWeekdayPricingEnabled($value);
    if (!rentalObjectColumnExists($db, 'weekday_prices_enabled')) {
        throw new RentalWeekdayPriceException(
            'Kolonnen weekday_prices_enabled mangler. Kjør den oppgitte databaseoppdateringen før du lagrer ukedagsvalget.'
        );
    }
    return $enabled;
}

function rentalWeekdayPricingIsActive(array $object): bool {
    if (array_key_exists('weekday_prices_enabled', $object)
        && $object['weekday_prices_enabled'] !== null
        && $object['weekday_prices_enabled'] !== '') {
        return rentalNormalizeWeekdayPricingEnabled($object['weekday_prices_enabled']) === 1;
    }

    // Compatibility for installations that have not yet added the explicit
    // switch: a stored map keeps its historical active behavior.
    return rentalNormalizeWeekdayPrices($object['weekday_prices'] ?? null) !== null;
}

/** Never create/migrate a column, and never silently discard an active map. */
function rentalWeekdayPriceWrite(PDO $db, $input): ?string {
    $prices = rentalNormalizeWeekdayPrices($input);
    if (!rentalObjectColumnExists($db, 'weekday_prices')) {
        if ($prices !== null) throw new RentalWeekdayPriceException('Kolonnen weekday_prices mangler på denne serveren.');
        return null;
    }
    return $prices === null ? null : json_encode($prices, JSON_THROW_ON_ERROR);
}
