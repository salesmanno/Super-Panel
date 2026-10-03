<?php
declare(strict_types=1);

/** Explicit portal coverage; session history and full email logs are not audit resources. */
function tenantAuditResourceSpec(string $table): ?array {
    $exact = [
        'tenant_companies' => 'settings', 'tenant_staff' => 'access', 'tenant_admins' => 'access',
        'tenant_admin_modules' => 'access', 'tenant_staff_modules' => 'access',
        'tenant_customers' => 'customers',
        'announcements' => 'news', 'company_announcements' => 'news', 'tenant_announcements' => 'news',
        'announcement_products' => 'news',
        'email_configs' => 'email', 'email_templates' => 'email', 'email_queue' => 'email',
        'unified_ledger' => 'economy', 'event_registration_fields' => 'events', 'event_registrations' => 'events',
        'event_membership_plans' => 'events', 'event_membership_pricing' => 'events',
    ];
    $module = $exact[$table] ?? null;
    foreach (['rental_' => 'rental','membership_' => 'membership','events_' => 'events',
        'economy_' => 'economy','tenant_website_' => 'settings'] as $prefix => $candidate) {
        if (str_starts_with($table, $prefix)) $module = $candidate;
    }
    if ($module === null || !preg_match('/^[a-z_]+$/D', $table)) return null;
    $fields = $table === 'tenant_customers' ? ['id','status','is_active','customer_number'] : (tenantAuditFields()[$table] ?? [
        'id','name','title','object_id','rental_object_id','event_id','member_id','membership_plan_id',
        'tenant_admin_id','tenant_staff_id','module_id','is_active','is_published','is_pinned','priority',
        'status','price','amount','quantity','payment_status','payment_method','start_date','end_date',
        'start_time','end_time','capacity','duration_months','field_name','field_type','is_required','sort_order',
    ]);
    $fields = array_values(array_unique(array_merge($fields, [
        'id','object_id','is_pinned','published','priority','news_priority','publish_at','expires_at',
        'price','price_per_day','price_per_week','price_per_month','capacity','bedrooms','area','is_blocked',
        'is_active','is_available','membership_type_id','billing_cycle','plan_name',
        'max_participants','event_date','event_time','default_price','default_payment_method',
        'payment_methods_json','registration_required','is_public','vat_rate',
        'balance','opening_balance','closing_balance','total_amount','paid_amount','due_amount','net_amount','vat_amount',
        'cash_payment','card_payment','vipps_enabled','memberships_enabled','events_enabled','rental_enabled',
        'billing_interval','currency','max_members','published_at','valid_from','valid_until',
        'ticket_price','public_display',
        'background_color','header_bg_color','text_color','theme','font_family',
    ])));
    return ['module' => $module, 'fields' => $fields];
}

function tenantAuditScopeExpression(string $table, array $columns): string {
    if ($table === 'tenant_companies') return "`$table`.`id`";
    if (in_array('company_id', $columns, true)) return "`$table`.`company_id`";
    $parents = [
        'tenant_admin_id' => 'tenant_admins', 'tenant_staff_id' => 'tenant_staff',
        'event_id' => 'events_eventdata', 'member_id' => 'membership_members',
        'registration_id' => 'event_registrations', 'checkin_id' => 'events_checkin',
        'rental_schedule_id' => 'rental_schedule', 'contract_id' => 'rental_contracts',
        'announcement_id' => 'tenant_announcements',
        'bank_account_id' => 'economy_bank_accounts', 'account_id' => 'economy_bank_accounts',
        'invoice_id' => 'economy_invoices', 'transaction_id' => 'economy_transactions',
        'membership_plan_id' => 'membership_plans',
        'object_id' => 'rental_objects', 'rental_object_id' => 'rental_objects',
    ];
    foreach ($parents as $foreignKey => $parent) {
        if (in_array($foreignKey, $columns, true)) {
            return "(SELECT audit_parent.company_id FROM `$parent` audit_parent"
                . " WHERE audit_parent.id = `$table`.`$foreignKey` LIMIT 1)";
        }
    }
    throw new TenantAuditException('Ressursen kan ikke avgrenses til en bedrift for audit.');
}