<?php
declare(strict_types=1);
require_once __DIR__ . '/entity_deletion.php';

/** Use the same locked ownership/catalog relationship as protected deletion. */
function setEventVisibility(PDO $db, string $company, string $id, int $active): void {
    if (!in_array($active, [0, 1], true)) throw new InvalidArgumentException('Ugyldig synlighet');
    entityFormRequireModuleAccess($db, $company, 'event');
    $db->beginTransaction();
    try {
        $q = $db->prepare('SELECT id,output_product_id FROM events_eventdata WHERE id=? AND company_id=? FOR UPDATE');
        $q->execute([$id, $company]);
        $event = $q->fetch(PDO::FETCH_ASSOC);
        if (!$event) throw new OutOfBoundsException('Arrangement ikke funnet eller ingen tilgang');
        $catalog = entityDeletionCatalog($db, $company, 'events', $id, $event['output_product_id'] ?? null);
        $db->prepare('UPDATE events_eventdata SET is_active=? WHERE id=? AND company_id=?')->execute([$active, $id, $company]);
        foreach ($catalog as $catalogId) {
            // "Vis" explicitly reverses the public/catalog hiding performed by soft deletion.
            $db->prepare('UPDATE output_products SET is_visible=?,is_public=? WHERE id=? AND company_id=?')
                ->execute([$active, $active, $catalogId, $company]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
