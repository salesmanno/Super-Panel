<?php
require_once __DIR__ . '/../includes/tenant_audit.php';
require_once __DIR__ . '/../includes/audit_resources.php';
function eqCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
eqCheck(tenantAuditEquivalent('ticket_price', '500.00', 500), 'Equivalent decimal');
eqCheck(tenantAuditEquivalent('is_active', '1', true), 'Equivalent boolean');
eqCheck(!tenantAuditEquivalent('access_code', '0037', '37'), 'Code leading zeros are meaningful');
eqCheck(!tenantAuditEquivalent('price', null, ''), 'Null is not empty');
eqCheck(!tenantAuditEquivalent('title', 'Name', 'Name '), 'Text differences remain meaningful');
eqCheck(!tenantAuditEquivalent('price', '9999999999999999', '9999999999999998'), 'No float rounding');
eqCheck(tenantAuditEquivalent('weekday_prices', '{"monday":500,"tuesday":600}', '{ "tuesday":600.0,"monday":500 }'), 'JSON object order/format');
eqCheck(!tenantAuditEquivalent('options', '["A","B"]', '["B","A"]'), 'JSON list order is meaningful');
eqCheck(!tenantAuditEquivalent('validation_rules', null, '[]'), 'JSON null not empty list');
eqCheck(!tenantAuditEquivalent('validation_rules', '{}', '[]'), 'JSON object not empty list');
eqCheck(!tenantAuditEquivalent('validation_rules', 'broken', '[]'), 'Invalid JSON not default');
eqCheck(tenantAuditChangedKeys('rental_objects', ['price'=>'500','updated_at'=>'old'], ['price'=>500,'updated_at'=>'new']) === [], 'Timestamp-only change excluded');
eqCheck(tenantAuditChangedKeys('events_eventdata', ['title'=>str_repeat('x',500).'a'], ['title'=>str_repeat('x',500).'b']) === ['title'], 'Compare before truncation');
echo "Audit comparison checks passed\n";
