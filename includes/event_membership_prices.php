<?php
declare(strict_types=1);
require_once __DIR__ . '/tenant_audit.php';

/** Reconcile locked persisted rows, rather than deleting/recreating unchanged prices. */
function saveEventMembershipPrices(PDO $db, string $company, string $event, array $input): void {
    if (!$db->inTransaction()) throw new LogicException('Medlemspriser krever en transaksjon.');
    tenantAuditActor($db, $company);
    $q = $db->prepare('SELECT id FROM events_eventdata WHERE id=? AND company_id=? FOR UPDATE');
    $q->execute([$event, $company]);
    if (!$q->fetchColumn()) throw new OutOfBoundsException('Arrangement ikke funnet');
    $desired = [];
    foreach ($input as $price) {
        if (!is_array($price) || !isset($price['plan_id'], $price['price'])
            || !is_string($price['plan_id']) || !is_numeric($price['price'])
            || !is_finite((float)$price['price']) || (float)$price['price'] < 0
            || isset($desired[$price['plan_id']])) throw new InvalidArgumentException('Ugyldig medlemspris');
        $public = $price['public_display'] ?? 1;
        if (!in_array($public, [0,1,'0','1',true,false], true)) throw new InvalidArgumentException('Ugyldig prisvisning');
        $q = $db->prepare('SELECT id FROM membership_plans WHERE id=? AND company_id=? LOCK IN SHARE MODE');
        $q->execute([$price['plan_id'], $company]);
        if (!$q->fetchColumn()) throw new OutOfBoundsException('Medlemsplan ikke funnet');
        $desired[$price['plan_id']] = ['ticket_price' => $price['price'], 'public_display' => (int)$public];
    }
    $q = $db->prepare('SELECT membership_plan_id,ticket_price,public_display FROM event_membership_plans WHERE event_id=? FOR UPDATE');
    $q->execute([$event]);
    $stored = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (isset($stored[$row['membership_plan_id']])) throw new RuntimeException('Dupliserte medlemspriser må avklares.');
        $stored[$row['membership_plan_id']] = $row;
    }
    foreach ($desired as $plan => $price) {
        $old = $stored[$plan] ?? null;
        unset($stored[$plan]);
        if ($old && tenantAuditEquivalent('ticket_price', $old['ticket_price'], $price['ticket_price'])
            && tenantAuditEquivalent('public_display', $old['public_display'], $price['public_display'])) continue;
        if ($old) {
            $db->prepare('UPDATE event_membership_plans SET ticket_price=?,public_display=? WHERE event_id=? AND membership_plan_id=?')
                ->execute([$price['ticket_price'], $price['public_display'], $event, $plan]);
        } else {
            $db->prepare('INSERT INTO event_membership_plans (event_id,membership_plan_id,ticket_price,public_display,created_at) VALUES (?,?,?,?,NOW())')
                ->execute([$event, $plan, $price['ticket_price'], $price['public_display']]);
        }
    }
    $activePlan = $db->prepare('SELECT is_active FROM membership_plans WHERE id=? AND company_id=? LOCK IN SHARE MODE');
    foreach ($stored as $plan => $row) {
        // Editors list active plans only. Omission of a retired/missing plan
        // is not a request to destroy its previously persisted price.
        $activePlan->execute([$plan, $company]);
        if ((int)$activePlan->fetchColumn() !== 1) continue;
        $db->prepare('DELETE FROM event_membership_plans WHERE event_id=? AND membership_plan_id=?')->execute([$event, $plan]);
    }
}
