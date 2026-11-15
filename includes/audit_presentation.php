<?php
declare(strict_types=1);

/** Presentation only: these labels never change stored evidence. */
function tenantAuditPresentationLabels(): array {
    return [
        'modules' => ['events'=>'Arrangementer','rental'=>'Utleie','membership'=>'Medlemskap',
            'memberships'=>'Medlemskap','sales'=>'Salg','economy'=>'Økonomi','news'=>'Nyheter',
            'email'=>'E-post','forms'=>'Skjemafelt','access'=>'Brukertilgang','settings'=>'Innstillinger',
            'customers'=>'Kunder','reservation'=>'Reservasjoner','system'=>'System'],
        'resources' => [
            'events_eventdata'=>'arrangement','events_checkin'=>'deltaker','event_registrations'=>'påmelding',
            'events_registrations'=>'påmelding','rental_objects'=>'utleieobjekt','rental_venues'=>'lokale',
            'rental_schedule'=>'utleiereservasjon','rental_contracts'=>'leiekontrakt',
            'membership_members'=>'medlemskap','membership_plans'=>'medlemsplan',
            'membership_product_modifications'=>'medlemsfordel','event_membership_pricing'=>'medlemspris',
            'event_membership_plans'=>'medlemskrav','sales_products'=>'vare','sales_orders'=>'salg',
            'sales_orders_items'=>'salgslinje','sales_cart_items'=>'vare i handlekurv',
            'sales_registers'=>'kassepunkt','sales_register_periods'=>'kasseperiode',
            'sales_register_journal'=>'kassejournal','sales_register_documents'=>'kassedokument',
            'unified_ledger'=>'hovedbokspost','economy_transactions'=>'transaksjon',
            'economy_payment_gateways'=>'betalingsløsning','economy_configuration_data'=>'betalingsoppsett',
            'economy_bank_accounts'=>'bankkonto','economy_invoices'=>'faktura',
            'tenant_announcements'=>'nyhetssak','announcements'=>'nyhetssak','company_announcements'=>'nyhetssak',
            'tenant_news_elements'=>'nyhetselement','announcement_products'=>'produktkobling',
            'email_queue'=>'e-postkø','email_templates'=>'e-postmal','email_configs'=>'e-postoppsett',
            'dynamic_form_fields'=>'skjemafelt','tenant_companies'=>'bedrift','tenant_staff'=>'ansatt',
            'tenant_admins'=>'brukerkonto','tenant_admin_modules'=>'modultilgang','tenant_staff_modules'=>'modultilgang',
            'tenant_customers'=>'kunde','tenant_customer_contacts'=>'kundekontakt','output_products'=>'tjenesteprodukt',
        ],
        'fields' => [
            'id'=>'Referanse','name'=>'Navn','title'=>'Tittel','object_name'=>'Objektnavn','plan_name'=>'Plannavn',
            'start_date'=>'Startdato','end_date'=>'Sluttdato','start_time'=>'Starttid','end_time'=>'Sluttid',
            'pickup_time'=>'Hentetid','return_time'=>'Returtid','expiration_date'=>'Utløpsdato',
            'join_date'=>'Innmeldingsdato','published_at'=>'Publisert','publish_at'=>'Publiseringstidspunkt',
            'expires_at'=>'Utløpstidspunkt','expire_at'=>'Utløpstidspunkt','expiry_time'=>'Utløpstid',
            'status'=>'Status','approval_status'=>'Godkjenning','payment_status'=>'Betalingsstatus',
            'payment_state'=>'Betalingstilstand','payment_method'=>'Betalingsmåte','paymentMethod'=>'Betalingsmåte',
            'ticket_price'=>'Billettpris','rental_price'=>'Grunnpris','rental_fee'=>'Leiepris',
            'weekend_price'=>'Helgepris','saturday_price'=>'Lørdagspris','sunday_price'=>'Søndagspris',
            'weekday_prices'=>'Ukedagspriser','weekday_prices_enabled'=>'Egne ukedagspriser',
            'deposit_amount'=>'Depositum','price'=>'Pris','amount'=>'Beløp','total'=>'Totalbeløp',
            'total_amount'=>'Totalbeløp','paid_amount'=>'Betalt beløp','due_amount'=>'Utestående beløp',
            'subtotal'=>'Beløp uten MVA','tax_total'=>'MVA-beløp','selling_price'=>'Salgspris',
            'cost_price'=>'Innkjøpspris','unit_price'=>'Enhetspris','list_price'=>'Listepris',
            'line_total'=>'Linjesum','line_subtotal'=>'Linjesum uten MVA','tax_amount'=>'MVA-beløp',
            'quantity'=>'Antall','stock_quantity'=>'Beholdning','reorder_level'=>'Bestillingsgrense',
            'discount_percent'=>'Rabattprosent','tax_rate'=>'MVA-sats','tax_class'=>'MVA-klasse',
            'benefit_type'=>'Fordelstype','benefit_value'=>'Fordelsverdi','price_basis'=>'Beregningsgrunnlag',
            'public_display'=>'Vis fordelen offentlig','memberverification'=>'Medlemsverifisering',
            'member_exclusive'=>'Kun for medlemmer','is_member_exclusive'=>'Kun for medlemmer',
            'is_active'=>'Aktiv','is_available'=>'Tilgjengelig','is_visible'=>'Synlig','is_required'=>'Obligatorisk',
            'is_published'=>'Publisert','published'=>'Publisert','is_pinned'=>'Festet øverst',
            'checked_in'=>'Sjekket inn','approved'=>'Godkjent','cleaning'=>'Rengjøring',
            'auto_renew'=>'Automatisk fornyelse','cancellation_active'=>'Oppsigelse aktiv','kontrollert'=>'Kontrollert',
            'capacity'=>'Kapasitet','max_participants'=>'Deltakergrense','duration_months'=>'Varighet i måneder',
            'field_name'=>'Feltnøkkel','field_label'=>'Feltnavn','field_type'=>'Felttype','sort_order'=>'Rekkefølge',
            'priority'=>'Prioritet','news_priority'=>'Nyhetsprioritet','role'=>'Rolle','username'=>'Brukernavn',
            'member_code'=>'Medlemsnummer','customer_number'=>'Kundenummer','order_number'=>'Ordrenummer',
            'sku'=>'Varenummer','barcode'=>'Strekkode','currency'=>'Valuta','category'=>'Kategori',
            'template_id'=>'Malreferanse','module_source'=>'Kildemodul','source_id'=>'Kildereferanse',
            'attempts'=>'Forsøk','attempt_number'=>'Forsøksnummer','max_attempts'=>'Maksimalt antall forsøk',
            'scheduled_at'=>'Planlagt tidspunkt','next_retry_at'=>'Neste forsøk','sent_at'=>'Godtatt av e-postserver',
            'created_at'=>'Opprettet','updated_at'=>'Sist endret','payment_checked_at'=>'Betaling kontrollert',
            'price_adjustment_type'=>'Type prisjustering','price_adjustment_cents'=>'Prisjustering',
        ],
        'values' => [
            'pending'=>'Venter','unpaid'=>'Ubetalt','paid'=>'Betalt','completed'=>'Fullført',
            'cancelled'=>'Avbrutt','canceled'=>'Avbrutt','refunded'=>'Refundert','expired'=>'Utløpt',
            'failed'=>'Mislyktes','approved'=>'Godkjent','rejected'=>'Avslått','active'=>'Aktiv',
            'inactive'=>'Inaktiv','draft'=>'Utkast','published'=>'Publisert','hidden'=>'Skjult',
            'processing'=>'Behandles','queued'=>'I kø','sent'=>'Sendt','authorized'=>'Reservert',
            'reserved'=>'Reservert','captured'=>'Belastet','cash'=>'Kontant','invoice'=>'Faktura',
            'card'=>'Kort','vipps'=>'Vipps','stripe'=>'Stripe','bank_transfer'=>'Bankoverføring',
            'amount_discount'=>'Kroneavslag','percent_discount'=>'Prosentavslag','fixed_price'=>'Fast medlemspris',
            'included'=>'Inkludert','access'=>'Tilgang','unit'=>'Per enhet','booking_total'=>'Hele bestillingen',
            'rental_day'=>'Per utleiedag','signup'=>'Påmelding','binding'=>'Bindende påmelding',
            'admin'=>'Administrator','owner'=>'Eier','super_admin'=>'Superadministrator','staff'=>'Ansatt',
        ],
    ];
}

function tenantAuditLabel(string $group, string $key): string {
    return tenantAuditPresentationLabels()[$group][$key]
        ?? ucfirst(str_replace(['_', '.'], ' ', $key));
}

function tenantAuditActionLabel(string $action): string {
    $action = strtolower($action);
    $exact = ['email.insert'=>'E-post lagt i kø','email.queued'=>'E-post lagt i kø',
        'email.processing_started'=>'Behandling av e-post startet','email.smtp_accepted'=>'E-post godtatt av e-postserver',
        'email.smtp_failed'=>'E-postforsøk mislyktes','email.update'=>'E-postkø oppdatert',
        'email.delete'=>'E-post fjernet fra kø','sales.stock_adjusted'=>'Beholdning justert',
        'audit.stock_reversed'=>'Lagerjustering reversert','event_participant_manual_create'=>'Deltaker registrert manuelt'];
    if (isset($exact[$action])) return $exact[$action];
    return match (true) {
        str_contains($action, 'delete'), str_contains($action, 'remove') => 'Sletting',
        str_contains($action, 'reject') => 'Avslag',
        str_contains($action, 'approv') => 'Godkjenning',
        str_contains($action, 'deactiv'), str_contains($action, 'disable') => 'Deaktivering',
        str_contains($action, 'activ'), str_contains($action, 'enable') => 'Aktivering',
        str_contains($action, 'checkin'), str_contains($action, 'check_in') => 'Innsjekking',
        str_contains($action, 'refund') => 'Refusjon',
        str_contains($action, 'captur') => 'Betaling belastet',
        str_contains($action, 'cancel') => 'Avbrudd',
        str_contains($action, 'renew') => 'Fornyelse',
        str_contains($action, 'hide'), str_contains($action, 'hidden') => 'Skjuling',
        str_contains($action, 'publish') => 'Publisering',
        str_contains($action, 'create'), str_contains($action, 'insert') => 'Opprettelse',
        str_contains($action, 'update'), str_contains($action, 'edit'), str_contains($action, 'chang') => 'Endring',
        default => 'Registrert hendelse',
    };
}

function tenantAuditTechnicalField(string $key): bool {
    return $key === 'id' || str_ends_with($key, '_id') || str_ends_with($key, '_sha256')
        || in_array($key, ['created_at','updated_at','admin_name','payment_checked_at'], true);
}

function tenantAuditDisplayValue(string $key, mixed $value): string {
    if ($value === null || $value === '') return 'Ikke angitt';
    if (is_array($value)) {
        $parts = [];
        foreach ($value as $k => $v) $parts[] = (is_int($k) ? '' : tenantAuditLabel('fields', (string) $k) . ': ')
            . tenantAuditDisplayValue((string) $k, $v);
        return implode('; ', $parts);
    }
    $text = (string) $value;
    if (in_array($key, ['approved','checked_in','cleaning','auto_renew','cancellation_active','kontrollert',
        'memberverification','member_exclusive','is_member_exclusive','public_display','weekday_prices_enabled','published'], true)
        || str_starts_with($key, 'is_') || str_starts_with($key, 'automail_')) {
        if (in_array($text, ['0','1'], true)) return $text === '1' ? 'Ja' : 'Nei';
    }
    if (preg_match('/(?:date|_at$|_time$)/', $key)
        && preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/D', $text, $m)) {
        if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return "$m[3].$m[2].$m[1]" . (isset($m[4]) ? " $m[4]:$m[5]" : '');
        }
    }
    if (str_contains($key, 'time') && preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/D', $text, $m)) return "$m[1]:$m[2]";
    if (is_numeric($value)) {
        if (preg_match('/(?:_price|_fee|_amount|_cents)$/D', $key)
            || in_array($key, ['price','amount','total','subtotal','tax_total','line_total','line_subtotal'], true)) {
            return number_format((float)$value / (str_ends_with($key, '_cents') ? 100 : 1), 2, ',', ' ') . ' NOK';
        }
        if (in_array($key, ['discount_percent','tax_rate','vat_rate'], true)) return str_replace('.', ',', $text) . ' %';
    }
    // Only enum fields are translated: never translate a person's name or a news title.
    if (in_array($key, ['status','approval_status','payment_status','payment_state','payment_method','paymentMethod',
        'benefit_type','price_basis','role','registration_type'], true)) {
        return tenantAuditPresentationLabels()['values'][strtolower($text)] ?? $text;
    }
    return $text;
}

function tenantAuditDisplayChanges(array $before, array $after): array {
    $changes = [];
    foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
        $old = $before[$key] ?? null;
        $new = $after[$key] ?? null;
        if ($old === $new || (is_scalar($old) && is_scalar($new) && (string)$old === (string)$new)) continue;
        $format = static function (mixed $v, array $snapshot) use ($key): string {
            if ($key === 'benefit_value' && $v !== null && is_numeric($v)) {
                return match ($snapshot['benefit_type'] ?? '') {
                    'percent_discount' => str_replace('.', ',', (string)$v) . ' %',
                    'fixed_price', 'amount_discount' => tenantAuditDisplayValue('amount', $v),
                    default => tenantAuditDisplayValue($key, $v),
                };
            }
            return tenantAuditDisplayValue($key, $v);
        };
        $changes[] = ['key'=>$key, 'label'=>tenantAuditLabel('fields', $key),
            'before'=>$format($old, $before), 'after'=>$format($new, $after),
            'technical'=>tenantAuditTechnicalField($key)];
    }
    return $changes;
}
