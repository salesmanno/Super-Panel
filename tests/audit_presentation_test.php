<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/tenant_audit.php';
function presentationCheck(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException($label);
}
$base = ['id'=>'log','performed_at'=>'2026-11-15 19:00:00','module'=>'membership',
    'action'=>'membership.update','resource_type'=>'membership_members','resource_id'=>'opaque-reference',
    'resource_name'=>'membership_members','company_id'=>'company','user_id'=>'admin','user_type'=>'tenant_admin',
    'old_values'=>json_encode(['member_code'=>'MBR-TEST','expiration_date'=>'2026-11-01','payment_status'=>'pending',
        'membership_plan_id'=>'old-plan','updated_at'=>'2026-11-01 10:30:00','auto_renew'=>0]),
    'new_values'=>json_encode(['member_code'=>'MBR-TEST','expiration_date'=>'2027-11-01','payment_status'=>'paid',
        'membership_plan_id'=>'new-plan','updated_at'=>'2026-11-15 10:30:00','auto_renew'=>1,
        '_audit'=>['actor_name'=>'Kari Nord','actor_role'=>'admin','outcome'=>'success']])];
$row = tenantAuditPublicRow($base);
presentationCheck(str_contains($row['description'], 'Administrator Kari Nord endret medlemskap «MBR-TEST»'), 'Name comes from recorded snapshot, role is readable');
presentationCheck(str_contains($row['description'], '01.11.2026 til 01.11.2027'), 'Dates are Norwegian');
presentationCheck(str_contains($row['description'], 'betalingsstatus fra Venter til Betalt'), 'Statuses translated');
presentationCheck(!str_contains($row['description'], 'new-plan') && !str_contains($row['description'], 'updated_at'), 'References do not overwhelm description');
presentationCheck($row['module_label'] === 'Medlemskap' && $row['action_label'] === 'Endring', 'Filter and detail labels');
$changes = array_column($row['changes'], null, 'key');
presentationCheck($changes['membership_plan_id']['technical'] && tenantAuditTechnicalField('updated_at'), 'References preserved in technical details');
presentationCheck($changes['auto_renew']['before'] === 'Nei' && $changes['auto_renew']['after'] === 'Ja', 'Flags read as yes/no');
presentationCheck(tenantAuditDisplayValue('price_adjustment_cents', -5050) === '-50,50 NOK', 'Cents are not mistaken for kroner');
presentationCheck(tenantAuditDisplayValue('title', 'pending') === 'pending', 'Free text is never translated as enum');
presentationCheck(tenantAuditDisplayValue('start_date', '2026-02-31') === '2026-02-31', 'Invalid recorded dates are not fabricated');
presentationCheck(tenantAuditDisplayValue('data', ['one','two']) === 'one; two', 'Arrays never become Array or PHP warnings');
$benefitChanges = tenantAuditDisplayChanges(['benefit_type'=>'percent_discount','benefit_value'=>10],
    ['benefit_type'=>'percent_discount','benefit_value'=>20]);
presentationCheck($benefitChanges[0]['before'] === '10 %' && $benefitChanges[0]['after'] === '20 %', 'Benefits show their actual unit');
$failed = $base;
$failed['new_values'] = json_encode(['_audit'=>['actor_name'=>'Kari','actor_role'=>'admin','outcome'=>'failed']]);
$failed['action'] = 'membership.delete';
presentationCheck(str_contains(tenantAuditPublicRow($failed)['description'], 'forsøkte å slette'), 'Failure is not described as successful deletion');
$deleted = $row;
$deleted['resource_type'] = 'events_checkin';
$deleted['action'] = 'events.delete';
$deleted['after'] = ['checked_in'=>1];
$deleted['before'] = [];
presentationCheck(!str_contains(tenantAuditNarrative($deleted), 'sjekket inn'), 'Deleting a participant must never look like check-in');
$withContext = $row;
$withContext['resource_name'] = 'rental_schedule';
$withContext['resource_type'] = 'rental_schedule';
$withContext['context'] = ['object_name'=>'Lokalet'];
$withContext['before'] = ['rental_fee'=>3000];
$withContext['after'] = ['rental_fee'=>2500];
presentationCheck(str_contains(tenantAuditNarrative($withContext), 'Lokalet') && str_contains(tenantAuditNarrative($withContext), '3 000,00 NOK til 2 500,00 NOK'), 'Recorded context and money are readable without lookups');
foreach (['events_eventdata','rental_objects','sales_products','membership_plans','tenant_announcements',
    'dynamic_form_fields','economy_payment_gateways','membership_product_modifications'] as $resource) {
    foreach (['insert','update','delete'] as $verb) {
        $example = $row;
        $example['resource_type'] = $example['resource_name'] = $resource;
        $example['action'] = 'module.'.$verb;
        $example['before'] = ['name'=>'Eksempel','price'=>100,'is_active'=>0];
        $example['after'] = ['name'=>'Eksempel','price'=>200,'is_active'=>1];
        $description = tenantAuditNarrative($example);
        presentationCheck(!str_contains($description, $resource) && !str_contains($description, 'is_active'), "Readable $resource $verb");
    }
}
echo "Audit presentation tests passed\n";
