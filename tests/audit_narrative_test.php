<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/tenant_audit.php';
function narrativeCheck(bool $condition,string $label): void {
    if (!$condition) throw new RuntimeException($label);
}
$base=['actor_role'=>'Kasserer','actor_name'=>'Kari Nord','actor_type'=>'tenant_admin','action'=>'events.insert',
    'resource_type'=>'events_eventdata','resource_name'=>'Sommerfest','resource_id'=>'event','before'=>[],
    'after'=>['start_date'=>'2026-07-15','start_time'=>'19:00:00'],'outcome'=>'success'];
$text=tenantAuditNarrative($base);
narrativeCheck(str_contains($text,'Kasserer Kari Nord opprettet arrangementet Sommerfest med start 19:00, 15.07.2026'),'Recorded role, date and 24h time');
$edited=array_replace($base,['action'=>'events.update','before'=>['ticket_price'=>200,'start_date'=>'2026-07-15'],
    'after'=>['ticket_price'=>250,'start_date'=>'2026-07-15']]);
$text=tenantAuditNarrative($edited);
narrativeCheck(str_contains($text,'billettpris fra 200 til 250') && !str_contains($text,'startdato fra'),'Only actual differences appear');
$manual=array_replace($base,['action'=>'EVENT_PARTICIPANT_MANUAL_CREATE','resource_type'=>'events_checkin',
    'resource_name'=>'Sommerfest / Ada Nord','after'=>['ticket_price'=>475.5,'checked_in'=>0]]);
$text=tenantAuditNarrative($manual);
narrativeCheck(str_contains($text,'475,50 NOK ved ankomst') && str_contains($text,'ikke sjekket inn'),'Manual registration does not falsely claim check-in/payment');
$sale=array_replace($base,['action'=>'sales.update','resource_type'=>'sales_orders','before'=>['status'=>'pending'],
    'after'=>['status'=>'paid'],'context'=>['total'=>75,'items'=>'Kaffe ×2, Kake ×1']]);
narrativeCheck(str_contains(tenantAuditNarrative($sale),'Kaffe ×2, Kake ×1 for 75,00 NOK'),'Multi-item sale and total');
$rental=array_replace($base,['resource_type'=>'rental_schedule','before'=>['approved'=>0],'after'=>['approved'=>1],
    'context'=>['object_name'=>'Grendehuset','start_date'=>'2026-12-01','end_date'=>'2026-12-02']]);
narrativeCheck(str_contains(tenantAuditNarrative($rental),'Grendehuset i perioden 01.12.2026 – 02.12.2026'),'Rental approval uses frozen object and dates');
$system=array_replace($base,['actor_type'=>'system','actor_role'=>'system','actor_name'=>'System / email_queue',
    'action'=>'email.smtp_failed','outcome'=>'failed']);
$text=tenantAuditNarrative($system);
narrativeCheck(str_contains($text,'System / email_queue registrerte et mislykket SMTP-forsøk') && !str_contains($text,'Kari'),'System is not attributed to administrator');
$legacy=tenantAuditPublicRow(['id'=>'old','performed_at'=>'2026-01-01','module'=>'events','action'=>'events.delete',
    'resource_type'=>'events_eventdata','resource_id'=>'event','resource_name'=>'Historisk fest','company_id'=>'company','user_id'=>null,
    'user_type'=>'tenant_admin','old_values'=>'{}','new_values'=>'{}']);
narrativeCheck(str_contains($legacy['description'],'Historisk navn ikke registrert') && !$legacy['reversal_candidate'],'Missing historical identity stays unknown; deletion cannot be undone from empty snapshot');
echo "Audit narrative tests passed\n";
