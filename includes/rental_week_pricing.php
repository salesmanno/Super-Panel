<?php
/**
 * Weekly tariffs, version 1. Dates are inclusive calendar dates.
 * A coupled interval is charged once per occurrence touched, just like the
 * historical weekend package. Its occurrence is anchored on start_day.
 * This file never creates or migrates database objects.
 */
require_once __DIR__ . '/rental_weekday_pricing.php';

class RentalWeekPriceException extends RentalWeekdayPriceException {}

function rentalWeekDays(): array {
    return ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
}

function rentalWeekModel(array $object): string {
    $model = $object['pricing_model'] ?? 'legacy';
    if (!in_array($model, ['legacy','weekly'], true)) {
        throw new RentalWeekPriceException('Ukjent prismodell for utleieobjektet.');
    }
    return $model;
}

function rentalWeekMoney($value): string {
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        throw new RentalWeekPriceException('Alle dags- og koblingspriser må være beløp.');
    }
    $value = str_replace(',', '.', trim((string)$value));
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value)) {
        throw new RentalWeekPriceException('Prisen må være 0–99 999 999,99 kr, med høyst to desimaler.');
    }
    [$whole,$fraction] = array_pad(explode('.', $value), 2, '');
    return (string)(int)$whole . '.' . str_pad($fraction, 2, '0');
}

function rentalWeekCents(string $value): int {
    return (int)str_replace('.', '', rentalWeekMoney($value));
}

function rentalWeekJson($input, string $label): array {
    if (is_string($input)) {
        try { $input = json_decode($input, true, 16, JSON_THROW_ON_ERROR); }
        catch (JsonException $e) { throw new RentalWeekPriceException("$label har ugyldig JSON."); }
    }
    if (!is_array($input)) throw new RentalWeekPriceException("$label mangler eller er ugyldig.");
    return $input;
}

function rentalDailyPrices($input): array {
    $input = rentalWeekJson($input, 'Dagsprisene');
    $days = rentalWeekDays();
    if (count($input) !== 7 || array_diff(array_keys($input), $days)) {
        throw new RentalWeekPriceException('Fyll ut enkeltpris for alle dager fra mandag til søndag.');
    }
    $out = [];
    foreach ($days as $day) $out[$day] = rentalWeekMoney($input[$day] ?? null);
    return $out;
}

function rentalPriceGroups($input): array {
    $input = rentalWeekJson($input, 'Dagskoblingene');
    if (!array_is_list($input) || count($input) > 2) {
        throw new RentalWeekPriceException('Det kan være høyst to dagskoblinger per objekt.');
    }
    $groups = []; $occupied = []; $slots = [];
    foreach ($input as $group) {
        if (!is_array($group)) throw new RentalWeekPriceException('Ugyldig dagskobling.');
        $ints = [];
        foreach (['slot'=>2,'start_day'=>7,'end_day'=>7] as $key=>$max) {
            $v = $group[$key] ?? null;
            if ((!is_int($v) && !is_string($v)) || !preg_match('/^[1-7]$/D', (string)$v)
                || (int)$v > $max) throw new RentalWeekPriceException('Ugyldig fra-dag, til-dag eller koblingsnummer.');
            $ints[$key] = (int)$v;
        }
        if (isset($slots[$ints['slot']]) || $ints['start_day'] === $ints['end_day']) {
            throw new RentalWeekPriceException('Koblinger må ha unikt nummer og dekke minst to dager.');
        }
        $slots[$ints['slot']] = true;
        $length = ($ints['end_day'] - $ints['start_day'] + 7) % 7 + 1;
        for ($i=0; $i<$length; $i++) {
            $day = ($ints['start_day'] - 1 + $i) % 7 + 1;
            if (isset($occupied[$day])) throw new RentalWeekPriceException('Dagskoblingene kan ikke overlappe.');
            $occupied[$day] = true;
        }
        $groups[] = $ints + ['price'=>rentalWeekMoney($group['price'] ?? null)];
    }
    usort($groups, static fn($a,$b)=>$a['slot']<=>$b['slot']);
    return $groups;
}

function rentalWeekDate(string $value): DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Europe/Oslo'));
    if (!$date || $date->format('Y-m-d') !== $value) throw new RentalWeekPriceException('Ugyldig leiedato.');
    return $date;
}

/** Return auditable price lines; no client-supplied total is trusted. */
function rentalWeekQuote(array $object, string $from, string $to): array {
    $prices = rentalDailyPrices($object['daily_prices'] ?? null);
    // Missing groups is an error, not proof that the object has no packages.
    $groups = rentalPriceGroups($object['price_groups'] ?? null);
    $start = rentalWeekDate($from); $end = rentalWeekDate($to);
    if ($end < $start || $start->diff($end)->days >= 3660) {
        throw new RentalWeekPriceException('Velg en gyldig leieperiode på høyst 3660 dager.');
    }
    $total = 0; $seen = []; $lines = [];
    foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $date) {
        $weekday = (int)$date->format('N'); $matched = false;
        foreach ($groups as $group) {
            $offset = ($weekday - $group['start_day'] + 7) % 7;
            $length = ($group['end_day'] - $group['start_day'] + 7) % 7 + 1;
            if ($offset >= $length) continue;
            $matched = true;
            $anchor = $date->modify("-$offset days")->format('Y-m-d');
            $key = $group['slot'].':'.$anchor;
            if (!isset($seen[$key])) {
                $cents = rentalWeekCents($group['price']);
                $total += $cents;
                $lines[] = ['type'=>'group','slot'=>$group['slot'],'anchor_date'=>$anchor,
                    'start_day'=>$group['start_day'],'end_day'=>$group['end_day'],'amount_cents'=>$cents];
                $seen[$key] = true;
            }
            break;
        }
        if (!$matched) {
            $cents = rentalWeekCents($prices[rentalWeekDays()[$weekday-1]]);
            $total += $cents;
            $lines[] = ['type'=>'day','date'=>$date->format('Y-m-d'),'amount_cents'=>$cents];
        }
    }
    return ['base_cents'=>$total,'rental_days'=>$start->diff($end)->days+1,'lines'=>$lines];
}

function rentalWeekColumnExists(PDO $db, string $column): bool {
    if (!in_array($column, ['pricing_model','daily_prices'], true)) throw new LogicException('Ukjent priskolonne.');
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        foreach ($db->query('PRAGMA table_info(rental_objects)')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['name'] === $column) return true;
        }
        return false;
    }
    return rentalObjectColumnExists($db, $column);
}

function rentalWeekSelect(PDO $db, string $alias = ''): string {
    $prefix = $alias === '' ? '' : $alias.'.';
    return (rentalWeekColumnExists($db,'pricing_model') ? $prefix.'pricing_model' : "'legacy'")
        .' AS pricing_model, '
        .(rentalWeekColumnExists($db,'daily_prices') ? $prefix.'daily_prices' : 'NULL').' AS daily_prices';
}

function rentalWeekAttach(PDO $db, array $object): array {
    if (rentalWeekModel($object) !== 'weekly') {
        $object['pricing_model'] = 'legacy';
        $object['price_groups'] = [];
    } else {
        try {
            $q = $db->prepare('SELECT slot,start_day,end_day,price FROM rental_price_groups
                WHERE company_id=? AND rental_object_id=? ORDER BY slot');
            $q->execute([$object['company_id'], $object['id']]);
            $object['price_groups'] = rentalPriceGroups($q->fetchAll(PDO::FETCH_ASSOC));
            $object['daily_prices'] = rentalDailyPrices($object['daily_prices'] ?? null);
        } catch (PDOException $e) {
            throw new RentalWeekPriceException('Dagskoblingene kunne ikke hentes. Kontroller databaseoppdateringen.');
        }
    }
    $object['pricing_version'] = hash('sha256', json_encode([
        $object['pricing_model'], $object['daily_prices'] ?? null, $object['price_groups'],
        ...array_map(static fn($key)=>$object[$key] ?? null,
            ['rental_price','weekday_prices','weekday_prices_enabled','weekend_price','saturday_price','sunday_price'])
    ], JSON_THROW_ON_ERROR));
    return $object;
}

/** Caller holds the company-scoped object lock and owns the transaction. */
function rentalWeekSave(PDO $db, string $company, string $id, array $input, ?array $existing): void {
    if (!$db->inTransaction()) throw new LogicException('Prislagring krever transaksjon.');
    if ($existing !== null && (($existing['id'] ?? null) !== $id || ($existing['company_id'] ?? null) !== $company)) {
        throw new RentalWeekPriceException('Ingen tilgang til prisoppsettet.');
    }
    $hasWrite = array_key_exists('pricing_model',$input) || array_key_exists('daily_prices',$input)
        || array_key_exists('price_groups',$input);
    if (!$hasWrite) {
        if (($existing['pricing_model'] ?? 'legacy') === 'weekly'
            && array_intersect(array_keys($input), ['rental_price','weekday_prices','weekday_prices_enabled',
                'weekend_price','saturday_price','sunday_price'])) {
            throw new RentalWeekPriceException('Prisoppsettet er oppdatert. Last inn objektet på nytt før du lagrer.');
        }
        return;
    }
    if (!array_key_exists('pricing_model',$input)) {
        throw new RentalWeekPriceException('Prismodell må oppgis sammen med dagspriser og koblinger.');
    }
    $model = rentalWeekModel($input);
    if (!rentalWeekColumnExists($db,'pricing_model') || !rentalWeekColumnExists($db,'daily_prices')) {
        if ($model === 'legacy' && !array_key_exists('daily_prices',$input) && !array_key_exists('price_groups',$input)) return;
        throw new RentalWeekPriceException('Kjør SQL-oppdateringen rental_week_pricing.sql før du lagrer det nye ukeoppsettet.');
    }
    if ($existing !== null) {
        $old = rentalWeekAttach($db,$existing);
        if (!is_string($input['pricing_version'] ?? null)
            || !hash_equals($old['pricing_version'], $input['pricing_version'])) {
            throw new RentalWeekPriceException('Prisene er endret eller må lastes på nytt. Åpne objektet igjen før lagring.');
        }
    }
    if ($model === 'legacy') {
        if (($existing['pricing_model'] ?? 'legacy') !== 'legacy') {
            $db->prepare("UPDATE rental_objects SET pricing_model='legacy' WHERE id=? AND company_id=?")->execute([$id,$company]);
        }
        return;
    }
    $daily = rentalDailyPrices($input['daily_prices'] ?? null);
    $groups = rentalPriceGroups($input['price_groups'] ?? null);
    // Require the table even for zero groups. No implicit schema creation.
    $q = $db->prepare('SELECT id,slot,start_day,end_day,price FROM rental_price_groups
        WHERE company_id=? AND rental_object_id=? ORDER BY slot');
    try { $q->execute([$company,$id]); }
    catch (PDOException $e) { throw new RentalWeekPriceException('Kjør SQL-oppdateringen rental_week_pricing.sql for dagskoblinger.'); }
    $saved = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) $saved[(int)$row['slot']] = $row;
    $json = json_encode($daily, JSON_THROW_ON_ERROR);
    if (($existing['pricing_model'] ?? '') !== 'weekly'
        || rentalDailyPrices($existing['daily_prices'] ?? $daily) !== $daily) {
        $db->prepare("UPDATE rental_objects SET pricing_model='weekly',daily_prices=? WHERE company_id=? AND id=?")
            ->execute([$json,$company,$id]);
    }
    $kept = [];
    foreach ($groups as $group) {
        $slot = $group['slot']; $kept[] = $slot;
        if (isset($saved[$slot])) {
            $before = rentalPriceGroups([array_intersect_key($saved[$slot],array_flip(['slot','start_day','end_day','price']))])[0];
            if ($before !== $group) {
                $db->prepare('UPDATE rental_price_groups SET start_day=?,end_day=?,price=?
                    WHERE company_id=? AND rental_object_id=? AND slot=?')
                    ->execute([$group['start_day'],$group['end_day'],$group['price'],$company,$id,$slot]);
            }
        } else {
            $uuid = bin2hex(random_bytes(16));
            $db->prepare('INSERT INTO rental_price_groups (id,company_id,rental_object_id,slot,start_day,end_day,price)
                VALUES (?,?,?,?,?,?,?)')
                ->execute([$uuid,$company,$id,$slot,$group['start_day'],$group['end_day'],$group['price']]);
        }
    }
    foreach ($saved as $slot=>$row) {
        if (!in_array($slot,$kept,true)) {
            $db->prepare('DELETE FROM rental_price_groups WHERE company_id=? AND rental_object_id=? AND slot=?')
                ->execute([$company,$id,$slot]);
        }
    }
}

/** Prevent the old form fields from overwriting legacy prices in weekly mode. */
function rentalWeekLegacyPayload(array $input): array {
    if (($input['pricing_model'] ?? '') === 'weekly') {
        foreach (['rental_price','weekday_prices','weekday_prices_enabled','weekend_price','saturday_price','sunday_price'] as $key) {
            unset($input[$key]);
        }
    }
    return $input;
}
