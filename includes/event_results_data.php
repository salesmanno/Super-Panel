<?php
require_once __DIR__ . '/event_results_helpers.php';

/** Read-only reporting: never settles payments or changes participant status. */
function event_results_calculate(array $events, array $tickets, array $registrations, array $ledger): array {
    $byEvent = $people = $groups = $registrationsById = $transactions = [];
    foreach ($events as $event) {
        $event = array_replace($event, [
            'attendee_count'=>0, 'unpaid_attendee_count'=>0, 'projected_income'=>0.0,
            'income'=>0.0, 'expense'=>0.0, 'projected_expense'=>0.0, 'unreconciled_paid_count'=>0,
        ]);
        $byEvent[$event['id']] = $event;
        $transactions[$event['id']] = [];
    }
    foreach ($registrations as $registration) {
        if (isset($byEvent[$registration['event_id']])) $registrationsById[$registration['id']] = $registration;
    }
    foreach ($tickets as $ticket) {
        if (!isset($byEvent[$ticket['event_id']])) continue;
        $status = strtolower((string)($ticket['payment_status']??''));
        $registration = (string)($ticket['registration_id']??'');
        $approval = strtolower((string)($registrationsById[$registration]['approval_status']??'approved'));
        if (in_array($status,['cancelled','canceled','rejected'],true)
            || in_array($approval,['rejected','cancelled','canceled'],true)) continue;
        $ticket['remaining_minor'] = max(0, (int) round((float) $ticket['ticket_price'] * 100));
        $ticket['completed_coverage_minor'] = 0;
        $people[$ticket['id']] = $ticket;
        $byEvent[$ticket['event_id']]['attendee_count']++;
        if (isset($registrationsById[$registration])
            && $registrationsById[$registration]['event_id'] === $ticket['event_id']) {
            $groups[$registration][] = $ticket['id'];
        }
    }
    $groupPaid = $groupCompletedPaid = $seen = [];
    foreach ($ledger as $entry) {
        if (isset($seen[$entry['id']])) continue;
        $seen[$entry['id']] = true;
        $actual = event_results_ledger_amounts($entry);
        $projected = event_results_ledger_projected_amounts($entry);
        if ($projected['income'] == 0 && $projected['expense'] == 0) continue;
        $metadata = is_array($entry['metadata'] ?? null) ? $entry['metadata'] : json_decode($entry['metadata'] ?? '{}', true);
        $metadata = is_array($metadata) ? $metadata : [];
        $source = (string) ($entry['source_id'] ?? '');
        $order = (string) ($entry['order_id'] ?? '');
        $person = $registration = null;
        foreach ([$source, (string) ($metadata['checkin_id'] ?? ''), $order] as $key) {
            if (isset($people[$key])) { $person = $key; break; }
        }
        foreach ([$source, (string) ($metadata['registration_id'] ?? ''), $order] as $key) {
            if (isset($registrationsById[$key])) { $registration = $key; break; }
        }
        $candidates = [];
        if (!empty($metadata['event_id'])) $candidates[] = (string) $metadata['event_id'];
        if ($person !== null) $candidates[] = $people[$person]['event_id'];
        if ($registration !== null) $candidates[] = $registrationsById[$registration]['event_id'];
        if (isset($byEvent[$source])) $candidates[] = $source;
        $candidates = array_values(array_unique($candidates));
        // Conflicting references are not silently assigned to an arbitrary event.
        if (count($candidates) !== 1 || !isset($byEvent[$candidates[0]])) continue;
        $eventId = $candidates[0];
        if (isset($entry['company_id'], $byEvent[$eventId]['company_id'])
            && $entry['company_id'] !== $byEvent[$eventId]['company_id']) continue;
        if (($person !== null && !isset($people[$person]))
            || ($registration !== null && in_array(strtolower((string)($registrationsById[$registration]['approval_status']??'')),['rejected','cancelled','canceled'],true))) continue;
        $byEvent[$eventId]['income'] += $actual['income'];
        $byEvent[$eventId]['expense'] += $actual['expense'];
        $byEvent[$eventId]['projected_income'] += $projected['income'];
        $byEvent[$eventId]['projected_expense'] += $projected['expense'];
        $transactions[$eventId][] = $entry;
        if ($projected['income'] <= 0) continue;
        $minor = (int) round($projected['income'] * 100);
        if ($person !== null) {
            $people[$person]['remaining_minor'] = max(0, $people[$person]['remaining_minor'] - $minor);
            $people[$person]['completed_coverage_minor'] += (int)round($actual['income']*100);
        } elseif ($registration !== null) {
            $groupPaid[$registration] = ($groupPaid[$registration] ?? 0) + $minor;
            $groupCompletedPaid[$registration] = ($groupCompletedPaid[$registration] ?? 0) + (int)round($actual['income']*100);
        }
    }
    foreach ($groupPaid as $registration => $credit) {
        $ids = $groups[$registration] ?? [];
        // Allocate existing group coverage once, including paid siblings even
        // when only one ticket's local payment flag is stale.
        usort($ids, static function ($a, $b) use ($people) {
            $paidA = $people[$a]['payment_status'] === 'paid' ? 0 : 1;
            $paidB = $people[$b]['payment_status'] === 'paid' ? 0 : 1;
            return ($paidA <=> $paidB) ?: strcmp($a, $b);
        });
        foreach ($ids as $id) {
            $used = min($credit, $people[$id]['remaining_minor']);
            $people[$id]['remaining_minor'] -= $used;
            $credit -= $used;
        }
    }
    foreach ($groupCompletedPaid as $registration => $credit) {
        $ids = $groups[$registration] ?? [];
        usort($ids, static function ($a, $b) use ($people) {
            $paidA = in_array($people[$a]['payment_status'],['paid','completed'],true) ? 0 : 1;
            $paidB = in_array($people[$b]['payment_status'],['paid','completed'],true) ? 0 : 1;
            return ($paidA <=> $paidB) ?: strcmp($a, $b);
        });
        foreach ($ids as $id) {
            $used = min($credit, max(0, $people[$id]['remaining_minor'] + $people[$id]['completed_coverage_minor']));
            $people[$id]['completed_coverage_minor'] += $used;
            $credit -= $used;
        }
    }
    foreach ($people as $ticket) {
        $eventId = $ticket['event_id'];
        $status = strtolower((string) ($ticket['payment_status'] ?? ''));
        if (in_array($status,['paid','completed'],true) && $ticket['completed_coverage_minor'] < (int)round((float)$ticket['ticket_price']*100)) {
            $byEvent[$eventId]['unreconciled_paid_count']++;
        }
        if (!in_array($status, ['pending','unpaid','payment_pending','paid','completed'], true) || $ticket['remaining_minor'] <= 0) continue;
        $registrationId = (string) ($ticket['registration_id'] ?? '');
        if ($registrationId !== '' && (!isset($registrationsById[$registrationId])
            || $registrationsById[$registrationId]['event_id'] !== $eventId
            || $registrationsById[$registrationId]['approval_status'] !== 'approved')) continue;
        if (in_array($status,['pending','unpaid','payment_pending'],true)) $byEvent[$eventId]['unpaid_attendee_count']++;
        $byEvent[$eventId]['projected_income'] += $ticket['remaining_minor'] / 100;
    }
    foreach ($byEvent as &$event) {
        foreach (['income','expense','projected_income','projected_expense'] as $key) $event[$key] = round($event[$key], 2);
        $event['is_active'] = (int) ($event['is_active'] ?? 0);
        $event['ticket_price'] = round((float) ($event['ticket_price'] ?? 0), 2);
        $event['net_result'] = round($event['income'] - $event['expense'], 2);
        $event['projected_net_result'] = round($event['projected_income'] - $event['projected_expense'], 2);
    }
    unset($event);
    return ['events'=>array_values($byEvent), 'transactions'=>$transactions];
}

function event_results_load(PDO $db, string $company, array $events): array {
    if (!$events) return ['events'=>[], 'transactions'=>[]];
    $stmt = $db->prepare("SELECT ec.id, ec.event_id, ec.registration_id, ec.ticket_price, ec.payment_status
        FROM events_checkin ec JOIN events_eventdata e ON e.id=ec.event_id
        WHERE e.company_id=?");
    $stmt->execute([$company]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT id, event_id, approval_status FROM event_registrations WHERE company_id=?");
    $stmt->execute([$company]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $valid = event_results_ledger_reportable_sql('ul');
    $stmt = $db->prepare("SELECT ul.id, ul.company_id, ul.source_id, ul.order_id, ul.item_name, ul.item_type,
        ul.quantity, ul.selling_price, ul.cost_price, ul.module_source, ul.payment_status, ul.status,
        ul.transaction_type, ul.transaction_date, ul.created_at, ul.payment_method, ul.metadata
        FROM unified_ledger ul WHERE ul.company_id=? AND {$valid}
        ORDER BY ul.transaction_date DESC, ul.created_at DESC, ul.id ASC");
    $stmt->execute([$company]);
    return event_results_calculate($events, $tickets, $registrations, $stmt->fetchAll(PDO::FETCH_ASSOC));
}
