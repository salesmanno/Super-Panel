<?php
declare(strict_types=1);

/** Formats recorded snapshots only. Never reconstructs history from today's customer/account. */
function tenantAuditNarrative(array $log): string {
    $before = $log['before'] ?? [];
    $after = $log['after'] ?? [];
    $context = $log['context'] ?? [];
    $action = strtolower((string) ($log['action'] ?? ''));
    $resource = (string) ($log['resource_type'] ?? '');
    $name = (string) ($log['resource_name'] ?: $log['resource_id']);
    $actor = trim(($log['actor_type'] === 'system' ? '' : $log['actor_role'] . ' ') . $log['actor_name']);
    $money = static fn($value): string => number_format((float) $value, 2, ',', ' ') . ' NOK';
    $value = static function ($v, string $key = ''): string {
        if ($v === null || $v === '') return 'ikke angitt';
        if (str_ends_with($key, '_date') && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $v, $m)) {
            return "$m[3].$m[2].$m[1]";
        }
        if (str_ends_with($key, '_time') && preg_match('/^\d{2}:\d{2}/', (string) $v)) return substr((string) $v, 0, 5);
        return (string) $v;
    };
    $labels = ['title'=>'navn','name'=>'navn','ticket_price'=>'billettpris','start_date'=>'startdato',
        'end_date'=>'sluttdato','start_time'=>'starttid','end_time'=>'sluttid','capacity'=>'kapasitet',
        'max_participants'=>'deltakergrense','is_active'=>'aktiv','stock_quantity'=>'beholdning',
        'selling_price'=>'salgspris','cost_price'=>'innkjøpspris','status'=>'status','payment_status'=>'betalingsstatus',
        'rental_price'=>'grunnpris','weekend_price'=>'helgepris','price'=>'pris','is_available'=>'tilgjengelig',
        'checked_in'=>'innsjekket','expiration_date'=>'utløpsdato'];
    $diffs = [];
    foreach ($after as $key => $new) {
        if ($key === 'id' || !array_key_exists($key, $before) || (string) $before[$key] === (string) $new) continue;
        $diffs[] = ($labels[$key] ?? $key) . ' fra ' . $value($before[$key], $key) . ' til ' . $value($new, $key);
    }
    $eventName = $context['event_name'] ?? $name;
    $person = $context['participant_name'] ?? $name;
    $verb = match (true) {
        str_contains($action, 'delete') => 'slettet',
        str_contains($action, 'reject'), ($after['approval_status'] ?? '') === 'rejected' && ($before['approval_status'] ?? '') !== 'rejected' => 'avslo',
        str_contains($action, 'approv'), ($after['approval_status'] ?? '') === 'approved' && ($before['approval_status'] ?? '') !== 'approved' => 'godkjente',
        str_contains($action, 'create'), str_contains($action, 'insert') => 'opprettet',
        default => 'endret',
    };
    if (str_starts_with($action, 'email.')) {
        $emailVerbs = ['email.insert'=>'la e-post i kø','email.queued'=>'la e-post i kø',
            'email.processing_started'=>'startet behandling av e-post','email.smtp_accepted'=>'fikk e-post akseptert av SMTP',
            'email.smtp_failed'=>'registrerte et mislykket SMTP-forsøk','email.update'=>'oppdaterte e-postkøen','email.delete'=>'fjernet e-post fra kø'];
        return "$actor " . ($emailVerbs[$action] ?? 'behandlet e-post') . ": $name."
            . (in_array($log['outcome'] ?? '',['failed','denied'],true) ? ' Handlingen ble ikke fullført.' : '');
    }
    if (in_array($log['outcome'] ?? '',['denied','failed','rolled_back'],true)) {
        return "$actor forsøkte å utføre «{$log['action']}» på $name. Handlingen ble ikke fullført.";
    }
    if ($action === 'audit.stock_reversed') return "$actor reverserte lagerjusteringen for $name. " . implode('. ', $diffs) . '.';
    if ($action === 'sales.stock_adjusted') {
        $delta = (int) ($after['stock_quantity'] ?? 0) - (int) ($before['stock_quantity'] ?? 0);
        $cost = isset($context['unit_cost']) ? ' Enhetskostnad ' . $money($context['unit_cost']) . ', samlet ' . $money($delta * (float) $context['unit_cost']) . '.' : '';
        return "$actor " . ($delta > 0 ? 'fylte på' : 'justerte beholdningen av') . " $name ×" . abs($delta) . ".$cost";
    }
    if ($resource === 'sales_orders' && in_array($after['status'] ?? '', ['paid','completed'], true)
        && !in_array($before['status'] ?? '', ['paid','completed'], true)) {
        return "$actor fullførte salg $name" . (!empty($context['items']) ? ': ' . $context['items'] : '')
            . (isset($context['total']) ? ' for ' . $money($context['total']) : '') . '.';
    }
    if ($resource === 'sales_products' && isset($before['stock_quantity'], $after['stock_quantity'])
        && (float) $after['stock_quantity'] < (float) $before['stock_quantity']) {
        return "$actor trakk " . ((int) $before['stock_quantity'] - (int) $after['stock_quantity']) . " $name fra beholdningen.";
    }
    if ($action === 'event_participant_manual_create') {
        return "$actor registrerte $name manuelt. Billettpris " . $money($after['ticket_price'] ?? 0)
            . ((int) ($after['checked_in'] ?? 0) === 1 ? '. Deltakeren ble sjekket inn.' : ' ved ankomst. Deltakeren er ikke sjekket inn.');
    }
    if ($resource === 'events_checkin' && (int) ($after['checked_in'] ?? 0) === 1 && (int) ($before['checked_in'] ?? 0) !== 1) {
        $payment = !empty($context['payment_name']) ? ' Kunden betalte med ' . $context['payment_name']
            . (!empty($context['paid_on_site']) ? ' på stedet' : '') . '.' : '';
        return "$actor sjekket inn $person" . (isset($context['ticket_price']) ? ' for ' . $money($context['ticket_price']) : '') . ".$payment";
    }
    if ($resource === 'events_eventdata') {
        if ($verb === 'opprettet') return "$actor opprettet arrangementet $name"
            . (isset($after['start_time']) ? ' med start ' . $value($after['start_time'], 'start_time') : '')
            . (isset($after['start_date']) ? ', ' . $value($after['start_date'], 'start_date') : '') . '.';
        return "$actor $verb arrangementet $name." . ($diffs ? ' Oppdatert ' . implode('; ', $diffs) . '.' : '');
    }
    if (in_array($resource, ['event_registrations','events_registrations'], true) && in_array($verb, ['godkjente','avslo'], true)) {
        return "$actor $verb " . ($context['reservation_count'] ?? 1) . " påmelding(er) for $eventName.";
    }
    if ($resource === 'rental_schedule' && (int) ($after['approved'] ?? 0) === 1 && (int) ($before['approved'] ?? 0) !== 1) {
        return "$actor godkjente utleie av " . ($context['object_name'] ?? $name) . ' i perioden '
            . $value($context['start_date'] ?? $after['start_date'] ?? $before['start_date'] ?? null, 'start_date')
            . ' – ' . $value($context['end_date'] ?? $after['end_date'] ?? $before['end_date'] ?? null, 'end_date') . '.';
    }
    return "$actor $verb $name." . ($diffs ? ' Oppdatert ' . implode('; ', $diffs) . '.' : '');
}
