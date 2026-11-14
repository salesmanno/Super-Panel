<?php
/**
 * Ensure a company-owned source has a company-owned catalogue link.
 * Caller owns the transaction. Lock the source before checking the catalogue
 * so concurrent repairs through this helper cannot create duplicate entries.
 * Existing catalogue visibility and referenced duplicate IDs are preserved.
 */
function ensureSourceCatalogLink(PDO $db, string $company, string $module, string $sourceId, ?string $actor = null, bool $syncVisibility = false): array {
    $definitions = [
        'rental' => ['rental_objects', ['rental']],
        'sales' => ['sales_products', ['product', 'sales_item']],
    ];
    if (!isset($definitions[$module])) throw new InvalidArgumentException('Ukjent katalogmodul');
    if (!$db->inTransaction()) throw new LogicException('Katalogkobling krever en aktiv transaksjon');
    [$table, $types] = $definitions[$module];
    $lock = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    $q = $db->prepare("SELECT * FROM $table WHERE id=? AND company_id=?".$lock);
    $q->execute([$sourceId, $company]);
    $source = $q->fetch(PDO::FETCH_ASSOC);
    if (!$source) throw new InvalidArgumentException('Produktet finnes ikke i valgt bedrift');

    $placeholders = implode(',', array_fill(0, count($types), '?'));
    $q = $db->prepare("SELECT id FROM output_products WHERE company_id=? AND product_id=?
        AND product_type IN ($placeholders) ORDER BY id".$lock);
    $q->execute([$company, $sourceId, ...$types]);
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);
    $pointer = (string)($source['output_product_id'] ?? '');
    $catalogId = in_array($pointer, $ids, true) ? $pointer : ($ids[0] ?? null);
    $created = false;
    $visible = (int)($source['is_active'] ?? 0) === 1
        && ($module !== 'rental' || (int)($source['is_available'] ?? 1) === 1) ? 1 : 0;
    $public = $visible && ($module !== 'sales' || ($source['visibility'] ?? 'kiosk') !== 'kiosk') ? 1 : 0;
    if ($catalogId === null) {
        $q = $db->prepare('SELECT id FROM system_modules WHERE module_key=?');
        $q->execute([$module]);
        $moduleId = $q->fetchColumn();
        if (!$moduleId) throw new RuntimeException('Modulen mangler i system_modules: '.$module);
        $catalogId = bin2hex(random_bytes(16));
        $q = $db->prepare('INSERT INTO output_products
            (id,company_id,module_id,product_type,product_id,is_visible,is_public,
             is_marketplace_featured,marketplace_priority,discount_percent,created_by)
            VALUES (?,?,?,?,?,?,?,0,0,0,?)');
        $q->execute([$catalogId, $company, $moduleId, $types[0], $sourceId, $visible, $public, $actor]);
        $created = true;
    }
    if (!$created && $syncVisibility) {
        // Only an explicit source visibility/status change may republish an
        // existing hidden catalogue entry; price/name edits must not do so.
        $q = $db->prepare('UPDATE output_products SET is_visible=?,is_public=?
            WHERE company_id=? AND id=? AND (COALESCE(is_visible,-1)<>? OR COALESCE(is_public,-1)<>?)');
        $q->execute([$visible,$public,$company,$catalogId,$visible,$public]);
    }
    $linked = $pointer !== $catalogId;
    if ($linked) {
        $q = $db->prepare("UPDATE $table SET output_product_id=? WHERE id=? AND company_id=?");
        $q->execute([$catalogId, $sourceId, $company]);
    }
    return ['id' => $catalogId, 'created' => $created, 'linked' => $linked, 'duplicates' => max(0, count($ids) - 1)];
}

/** Explicit, company-scoped backfill. Dry-run never opens a write transaction. */
function repairSourceCatalogLinks(PDO $db, string $company, bool $apply = false, ?string $actor = null): array {
    if ($company === '') throw new InvalidArgumentException('Bedrift må oppgis');
    if ($db->inTransaction()) throw new LogicException('Etterkobling må eie sin egen transaksjon');
    $report = ['company_id'=>$company, 'applied'=>$apply, 'checked'=>0, 'missing'=>0, 'unlinked'=>0, 'duplicates'=>0, 'created'=>0, 'linked'=>0];
    if ($apply) $db->beginTransaction();
    try {
        foreach (['rental'=>'rental_objects', 'sales'=>'sales_products'] as $module=>$table) {
            $types = $module === 'sales' ? ['product','sales_item'] : ['rental'];
            $q = $db->prepare("SELECT id,output_product_id FROM $table WHERE company_id=? ORDER BY id");
            $q->execute([$company]);
            $sources = $q->fetchAll(PDO::FETCH_ASSOC);
            $lookup = $db->prepare('SELECT id FROM output_products WHERE company_id=? AND product_id=? AND product_type IN ('
                .implode(',', array_fill(0, count($types), '?')).')');
            foreach ($sources as $source) {
                $report['checked']++;
                $lookup->execute([$company, $source['id'], ...$types]);
                $ids = $lookup->fetchAll(PDO::FETCH_COLUMN);
                $report['missing'] += !$ids ? 1 : 0;
                $report['unlinked'] += !in_array((string)($source['output_product_id'] ?? ''), $ids, true) ? 1 : 0;
                $report['duplicates'] += max(0, count($ids)-1);
                if ($apply) {
                    $result = ensureSourceCatalogLink($db, $company, $module, (string)$source['id'], $actor);
                    $report['created'] += $result['created'] ? 1 : 0;
                    $report['linked'] += $result['linked'] ? 1 : 0;
                }
            }
        }
        if ($apply) $db->commit();
        return $report;
    } catch (Throwable $e) {
        if ($apply && $db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
