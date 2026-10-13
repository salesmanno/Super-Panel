<?php
// Included by the disposable MySQL/HTTP forms harness, never by the app.
$setup->exec("INSERT INTO membership_plans VALUES('second-plan','Årsmedlem','a'),('foreign-plan','Annet firma','b')");
$priceEdit = $edit;
$priceEdit['title'] = 'Grunnopplysninger og to medlemspriser';
$priceEdit['membership_prices'] = [
    ['plan_id' => 'member-plan', 'price' => 40, 'public_display' => 0],
    ['plan_id' => 'second-plan', 'price' => 0, 'public_display' => 1],
];
formsAssert(formsHttp('update_event.php', $priceEdit)['success'], 'Basic edit and multiple/free membership prices commit together');
$priceRows = $setup->query("SELECT membership_plan_id,ticket_price,public_display FROM event_membership_plans WHERE event_id='$id' ORDER BY membership_plan_id")->fetchAll(PDO::FETCH_ASSOC);
formsAssert(count($priceRows) === 2 && (float)$priceRows[0]['ticket_price'] === 40.0 && (int)$priceRows[0]['public_display'] === 0
    && (float)$priceRows[1]['ticket_price'] === 0.0, 'Membership prices and private/free flags persisted accurately');
$badPrice = $priceEdit;
$badPrice['title'] = 'Must not commit';
$badPrice['membership_prices'][1]['plan_id'] = 'foreign-plan';
formsAssert(!formsHttp('update_event.php', $badPrice)['success'], 'Foreign membership plan rejected');
formsAssert($setup->query("SELECT title FROM events_eventdata WHERE id='$id'")->fetchColumn() === $priceEdit['title'], 'Rejected price update rolls basic data back');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM event_membership_plans WHERE event_id='$id'")->fetchColumn() === 2, 'Rejected price update preserves all prior prices');
$setup->exec("DELETE FROM tenant_admin_modules WHERE module_id='membership-module'");
$badPrice['membership_prices'] = [];
formsAssert(!formsHttp('update_event.php', $badPrice)['success'], 'Empty submitted pricing cannot erase hidden settings without Membership access');
formsAssert(formsHttp('update_event.php', $edit)['success'], 'Unavailable membership prices omitted safely');
$setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','membership-module')");

$visibility = ['event_id' => $id, 'is_active' => 1, 'csrf_token' => $token];
formsAssert(formsHttp('set_event_visibility.php', $visibility)['success'], 'Hidden event can be reactivated through real endpoint');
formsAssert((int)$setup->query("SELECT is_active FROM events_eventdata WHERE id='$id'")->fetchColumn() === 1, 'Reactivation persisted');
formsAssert((int)$setup->query("SELECT MIN(is_visible * is_public) FROM output_products WHERE product_id='$id'")->fetchColumn() === 1, 'Reactivation restores public/catalog visibility');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM event_registrations WHERE event_id='$id'")->fetchColumn() > 0, 'Reactivation keeps registration history');
$visibility['is_active'] = 0;
formsAssert(formsHttp('set_event_visibility.php', $visibility)['success'], 'Hide through real endpoint');
formsAssert((int)$setup->query("SELECT MAX(is_visible + is_public) FROM output_products WHERE product_id='$id'")->fetchColumn() === 0, 'Hiding synchronizes catalog');
$visibility['csrf_token'] = 'invalid';
formsAssert(!formsHttp('set_event_visibility.php', $visibility)['success'], 'Visibility rejects bad CSRF');
$visibility['csrf_token'] = $token;
$visibility['event_id'] = 'foreign';
formsAssert(!formsHttp('set_event_visibility.php', $visibility)['success'], 'Visibility rejects foreign company event');
$visibility['event_id'] = $id;
$setup->exec("DELETE FROM tenant_admin_modules WHERE module_id='events-module'");
formsAssert(!formsHttp('set_event_visibility.php', $visibility)['success'], 'Visibility enforces Events permission');
formsAssert(!formsHttp('get_events.php')['success'], 'Dashboard source enforces Events permission');
$setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','events-module')");

// Match the current production counting columns; this is fixture-only DDL.
$setup->exec("ALTER TABLE events_checkin ADD checked_in INT DEFAULT 0");
$setup->exec("ALTER TABLE event_registrations ADD approval_status VARCHAR(30) DEFAULT 'pending'");
$list = formsHttp('get_events.php');
formsAssert($list['success'], 'Real dashboard source returns events');
$listed = array_values(array_filter($list['events'], static fn($row) => $row['id'] === $id))[0];
formsAssert($listed['counts_loaded'] && $listed['pending_registrations'] > 0 && $listed['membership_prices_loaded']
    && count($listed['membership_prices']) === 2, 'Dashboard source exposes accurate counts and every membership plan price');
formsAssert(!in_array('foreign', array_column($list['events'], 'id'), true), 'Dashboard events remain company scoped');
$setup->exec("ALTER TABLE events_checkin RENAME COLUMN checked_in TO unavailable_checked_in");
$list = formsHttp('get_events.php');
$listed = array_values(array_filter($list['events'], static fn($row) => $row['id'] === $id))[0];
formsAssert(!$listed['counts_loaded'] && $listed['current_attendees'] === null && $listed['pending_registrations'] === null,
    'Count-query errors are not displayed as a successful zero');
$setup->exec("ALTER TABLE events_checkin RENAME COLUMN unavailable_checked_in TO checked_in");
$setup->exec("RENAME TABLE event_membership_plans TO unavailable_membership_prices");
$list = formsHttp('get_events.php');
$listed = array_values(array_filter($list['events'], static fn($row) => $row['id'] === $id))[0];
formsAssert(!$listed['membership_prices_loaded'], 'Price-query failure is explicit, never a successful empty setup');
$setup->exec("RENAME TABLE unavailable_membership_prices TO event_membership_plans");
