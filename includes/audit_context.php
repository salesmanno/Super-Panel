<?php
declare(strict_types=1);

/**
 * Freeze names at mutation time. Optional old-schema columns are discovered once
 * per connection; no current profile lookup is performed when displaying logs.
 */
function tenantAuditContextColumns(PDO $db, string $table): array {
    static $cache;
    $cache ??= new WeakMap();
    $tables = $cache[$db] ?? [];
    if (!array_key_exists($table,$tables)) {
        try {
            $tables[$table] = array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC),'Field');
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) !== 1146) throw $error;
            $tables[$table] = []; // Optional relation absent in an older schema.
        }
        $cache[$db] = $tables;
    }
    return $tables[$table];
}

function tenantAuditContextParent(PDO $db, string $company, string $table, string $id, array $fields): array {
    // Identifiers are from a closed list, never an API request or audit row.
    if (!in_array($table,['events_eventdata','tenant_customers','rental_objects','economy_payment_gateways'],true) || $id === '') return [];
    $columns = tenantAuditContextColumns($db,$table);
    if (!in_array('company_id',$columns,true)) return [];
    $select = array_intersect($fields,$columns);
    if (!$select) return [];
    $query = $db->prepare('SELECT `'.implode('`,`',$select)."` FROM `$table` WHERE id=? AND company_id=?");
    $query->execute([$id,$company]);
    return $query->fetch(PDO::FETCH_ASSOC) ?: [];
}

function tenantAuditBusinessContext(PDO $db, string $company, string $resource, string $id, array $before, array $after): array {
    $row = array_replace($before,$after);
    $context = [];
    if (in_array($resource,['events_checkin','event_registrations','events_registrations'],true)) {
        $event = tenantAuditContextParent($db,$company,'events_eventdata',(string)($row['event_id'] ?? ''),['title','name']);
        if ($event) $context['event_name'] = $event['title'] ?? $event['name'] ?? '';
        $customer = tenantAuditContextParent($db,$company,'tenant_customers',(string)($row['customer_id'] ?? ''),['first_name','last_name']);
        if ($customer) $context['participant_name'] = trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? ''));
        if (isset($row['ticket_price'])) $context['ticket_price'] = $row['ticket_price'];
        if (isset($row['quantity'])) $context['reservation_count'] = $row['quantity'];
        if (!empty($row['payment_method'])) {
            $gateway = tenantAuditContextParent($db,$company,'economy_payment_gateways',(string)$row['payment_method'],['name']);
            $label = $gateway['name'] ?? (['cash'=>'Kontant','vipps'=>'Vipps','card'=>'Kort'][(string)$row['payment_method']] ?? null);
            if ($label) $context['payment_name'] = $label;
        }
    } elseif ($resource === 'rental_schedule') {
        $object = tenantAuditContextParent($db,$company,'rental_objects',(string)($row['rental_object_id'] ?? ''),['object_name']);
        if ($object) $context['object_name'] = $object['object_name'];
        foreach (['start_date','end_date'] as $key) if (isset($row[$key])) $context[$key] = $row[$key];
    } elseif ($resource === 'sales_orders' && in_array($after['status'] ?? '',['paid','completed'],true)
        && !in_array($before['status'] ?? '',['paid','completed'],true)) {
        $columns = tenantAuditContextColumns($db,'sales_orders_items');
        if (count(array_intersect(['order_id','description','quantity'],$columns)) === 3) {
            $q = $db->prepare('SELECT i.description,i.quantity FROM sales_orders_items i
                JOIN sales_orders o ON o.id=i.order_id WHERE o.id=? AND o.company_id=? ORDER BY i.id');
            $q->execute([$id,$company]);
            $context['items'] = implode(', ',array_map(static fn($item) => $item['description'].' ×'.(float)$item['quantity'],$q->fetchAll(PDO::FETCH_ASSOC)));
        }
        if (isset($row['total'])) $context['total'] = $row['total'];
    }
    return $context;
}

function tenantAuditSafeContext(array $context): array {
    $safe = [];
    foreach (['event_name','participant_name','object_name','payment_name','paid_on_site','reservation_count','unit_cost','items',
        'ticket_price','total','start_date','end_date'] as $key) {
        if (isset($context[$key]) && is_scalar($context[$key])) $safe[$key] = mb_substr((string)$context[$key],0,2000);
    }
    return $safe;
}
