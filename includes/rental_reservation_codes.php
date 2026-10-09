<?php
declare(strict_types=1);

function rentalCodeToday(?DateTimeImmutable $now = null): string {
    return ($now ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Oslo'))->format('Y-m-d');
}

function rentalValidAssignedCode($value): bool {
    return is_string($value) && preg_match('/^[A-Za-z0-9]{1,6}$/D', trim($value))
        && !in_array(trim($value), ['', '0', '0000'], true);
}

function rentalGenerateReservationCode(string $reference): string {
    $reference = trim($reference);
    if (!rentalValidAssignedCode($reference)) {
        throw new InvalidArgumentException('Sett en gyldig tilgangskode på utleieobjektet først, slik at antall sifre er kjent.');
    }
    do {
        $code = '';
        for ($i = 0; $i < strlen($reference); $i++) $code .= (string) random_int(0, 9);
    } while (!rentalValidAssignedCode($code) || $code === $reference);
    return $code;
}

/** Called explicitly by the upcoming overview, never by a detail window or cron.
 * Valid assigned codes stay unchanged; unknown reference lengths are reported. */
function rentalEnsureUpcomingCodes(PDO $db, string $company, array $actor): array {
    $today = rentalCodeToday();
    $q = $db->prepare("SELECT ro.id, rv.access_code FROM rental_objects ro
        LEFT JOIN rental_venues rv ON rv.object_id = ro.id
        WHERE ro.company_id = ? AND ro.category = 'venue' ORDER BY ro.id FOR UPDATE");
    $q->execute([$company]);
    $objects = $q->fetchAll(PDO::FETCH_ASSOC);
    $assigned = 0;
    $unresolved = [];
    foreach ($objects as $object) {
        $q = $db->prepare("SELECT id, key_box_code, checkin_email_queued_at, checkin_email_sent_at
            FROM rental_schedule WHERE company_id = ? AND rental_object_id = ?
            AND approved = 1 AND status = 'approved' AND start_date > ? ORDER BY start_date, id FOR UPDATE");
        $q->execute([$company, $object['id'], $today]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (rentalValidAssignedCode($row['key_box_code'])) continue;
            if (!rentalValidAssignedCode($object['access_code'])
                || !empty($row['checkin_email_queued_at']) || !empty($row['checkin_email_sent_at'])) {
                $unresolved[] = (string)$row['id'];
                continue;
            }
            $code = rentalGenerateReservationCode((string)$object['access_code']);
            $db->prepare('UPDATE rental_schedule SET key_box_code = ?, updated_at = NOW() WHERE id = ? AND company_id = ?')
                ->execute([$code, $row['id'], $company]);
            tenantAuditRecord($db, $company, 'rental', 'rental.code.assign', 'rental_schedule', (string)$row['id'],
                'Automatisk tildelt reservasjonskode', [], [], 'success', [], $actor, ['key_box_code']);
            $assigned++;
        }
    }
    return ['assigned_count' => $assigned, 'unresolved_ids' => $unresolved];
}

/** Unfiltered by the UI month; used by the overview as well as the locked action. */
function rentalNextCodeReservations(PDO $db, string $company, string $today, ?string $object = null, bool $lock = false): array {
    $sql = "SELECT id, rental_object_id FROM rental_schedule
        WHERE company_id = ? AND approved = 1 AND status = 'approved' AND start_date > ?";
    $args = [$company, $today];
    if ($object !== null) { $sql .= ' AND rental_object_id = ?'; $args[] = $object; }
    $sql .= " ORDER BY start_date, COALESCE(start_time, pickup_time, '08:00:00'), id";
    if ($lock) $sql .= ' FOR UPDATE';
    $q = $db->prepare($sql);
    $q->execute($args);
    $next = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $next[(string)$row['rental_object_id']] ??= (string)$row['id'];
    }
    return $next;
}

/** Caller owns transaction. Lock the object (also used by object editing), then
 * the future schedule range and current row; never infer eligibility from UI data. */
function rentalCodeContext(PDO $db, string $company, string $id): array {
    $q = $db->prepare('SELECT rental_object_id FROM rental_schedule WHERE id = ? AND company_id = ?');
    $q->execute([$id, $company]);
    $object = $q->fetchColumn();
    if (!$object) throw new DomainException('Utleien ble ikke funnet.', 404);
    $q = $db->prepare("SELECT id FROM rental_objects WHERE id = ? AND company_id = ? AND category = 'venue' FOR UPDATE");
    $q->execute([$object, $company]);
    if (!$q->fetchColumn()) throw new DomainException('Utleieobjektet er ikke et tilgjengelig lokale.', 409);
    $today = rentalCodeToday();
    $next = rentalNextCodeReservations($db, $company, $today, (string)$object, true);
    if (($next[$object] ?? null) !== $id) {
        throw new DomainException('Koden kan bare forberedes for neste utleie som starter etter i dag. Last inn oversikten på nytt.', 409);
    }
    $q = $db->prepare('SELECT rs.*, rv.access_code AS venue_code
        FROM rental_schedule rs LEFT JOIN rental_venues rv ON rv.object_id = rs.rental_object_id
        WHERE rs.id = ? AND rs.company_id = ? FOR UPDATE');
    $q->execute([$id, $company]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (rentalCodeToday() !== $today || !$row || $row['start_date'] <= $today || $row['status'] !== 'approved' || (int)$row['approved'] !== 1) {
        throw new DomainException('Utleien er endret. Last inn på nytt.', 409);
    }
    return $row;
}

function rentalCodeVersion(array $row): string {
    $values = [];
    foreach (['id','company_id','rental_object_id','start_date','end_date','start_time','pickup_time',
        'end_time','status','approved','key_box_code','venue_code'] as $key) $values[$key] = $row[$key] ?? null;
    return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
}

function rentalCodeProposal(array $row): array {
    $assigned = trim((string)($row['key_box_code'] ?? ''));
    if (!rentalValidAssignedCode($assigned)) {
        throw new DomainException('Reservasjonen mangler en gyldig tildelt kode. Oppdater kommende utleier. Kontroller at lokalet har en gyldig kode som angir kodelengden.', 409);
    }
    $venue = trim((string)($row['venue_code'] ?? ''));
    return [
        'id' => (string)$row['id'], 'company' => (string)$row['company_id'],
        'version' => rentalCodeVersion($row), 'expires' => time() + 1800,
        'code' => $assigned,
        'assigned' => true, 'matches' => $assigned === $venue,
    ];
}

function rentalSaveCodeProposal(PDO $db, string $company, string $id, array $proposal, bool $physical, array $actor): void {
    $row = rentalCodeContext($db, $company, $id);
    if (($proposal['company'] ?? '') !== $company || ($proposal['id'] ?? '') !== $id
        || ($proposal['actor_id'] ?? '') !== $actor['id']
        || ($proposal['expires'] ?? 0) < time()
        || !hash_equals(rentalCodeVersion($row), (string)($proposal['version'] ?? ''))) {
        throw new DomainException('Utleien eller lokalets kode er endret. Lukk vinduet og åpne utleien på nytt.', 409);
    }
    $code = $proposal['code'];
    if (!rentalValidAssignedCode($code)) throw new DomainException('Kodeforslaget er ugyldig.', 409);
    if (!$physical || $code !== trim((string)$row['key_box_code'])) {
        throw new DomainException('Bekreft at lokalets kode er endret til reservasjonens tildelte kode.', 409);
    }
    if ($physical && trim((string)($row['venue_code'] ?? '')) !== $code) {
        $db->prepare('INSERT INTO rental_venues (object_id, access_code) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE access_code = VALUES(access_code)')->execute([$row['rental_object_id'], $code]);
        tenantAuditRecord($db, $company, 'rental', 'rental.code.physical_confirm', 'rental_objects',
            (string)$row['rental_object_id'], 'Bekreftet fysisk kodebytte', [], [], 'success', [], $actor, ['access_code']);
    }
}
