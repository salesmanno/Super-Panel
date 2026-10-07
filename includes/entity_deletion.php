<?php
declare(strict_types=1);
require_once __DIR__.'/entity_form_fields.php';
require_once __DIR__.'/event_results_helpers.php';
require_once __DIR__.'/rental_schemas.php';

/** Inspect real FKs so an unexpected historical child cannot be silently cascaded away. */
function entityDeletionCheckReferences(PDO $db, string $table, string $id, array $configurationTables): void {
    $q=$db->prepare('SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME=? AND REFERENCED_COLUMN_NAME=?');
    $q->execute([$table,'id']);
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $reference) {
        if(in_array($reference['TABLE_NAME'],$configurationTables,true)) continue;
        $child=$reference['TABLE_NAME'];$column=$reference['COLUMN_NAME'];
        if(!preg_match('/^[a-zA-Z0-9_]+$/',$child.$column)) throw new RuntimeException('Ukjent databasekobling');
        $check=$db->prepare("SELECT `$column` FROM `$child` WHERE `$column`=? LIMIT 1 FOR UPDATE");
        $check->execute([$id]);
        if($check->fetch()) throw new DomainException('Kan ikke slette permanent: posten har tilknyttet historikk eller andre koblinger. Bevar posten og gjør den inaktiv i stedet.');
    }
}

function entityDeletionHasRows(PDO $db,string $table,string $column,string $id): bool {
    $q=$db->prepare("SELECT `$column` FROM `$table` WHERE `$column`=? LIMIT 1 FOR UPDATE");
    $q->execute([$id]);
    return (bool)$q->fetch();
}

/** Both ownership and catalog identity are locked before any mutation. */
function entityDeletionCatalog(PDO $db,string $company,string $kind,string $id,?string $catalogId): array {
    $q=$db->prepare('SELECT id,company_id,product_id,product_type FROM output_products
        WHERE (company_id=? AND product_id=? AND product_type=?) OR id=? FOR UPDATE');
    $q->execute([$company,$id,$kind,$catalogId]);
    $rows=$q->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row) {
        if($row['company_id']!==$company || $row['product_id']!==$id || $row['product_type']!==$kind) {
            throw new DomainException('Katalogkoblingen må avklares før sletting.');
        }
    }
    return array_column($rows,'id');
}

function entityDeletionCatalogRemove(PDO $db,string $company,array $ids): void {
    foreach($ids as $id) {
        entityDeletionCheckReferences($db,'output_products',(string)$id,[]);
        $q=$db->prepare('DELETE FROM output_products WHERE id=? AND company_id=?');
        $q->execute([$id,$company]);
    }
}

function deleteEventSafely(PDO $db,string $company,string $id,string $mode): void {
    if(!in_array($mode,['soft','hard'],true)) throw new InvalidArgumentException('Ugyldig slettevalg');
    entityFormRequireModuleAccess($db,$company,'event');
    $db->beginTransaction();
    try {
        $q=$db->prepare('SELECT id,output_product_id FROM events_eventdata WHERE id=? AND company_id=? FOR UPDATE');
        $q->execute([$id,$company]);
        $event=$q->fetch(PDO::FETCH_ASSOC);
        if(!$event) throw new OutOfBoundsException('Arrangement ikke funnet eller ingen tilgang');
        $catalog=entityDeletionCatalog($db,$company,'events',$id,$event['output_product_id']??null);
        if($mode==='soft') {
            $q=$db->prepare('UPDATE events_eventdata SET is_active=0 WHERE id=? AND company_id=?');
            $q->execute([$id,$company]);
            foreach($catalog as $catalogId) {
                $q=$db->prepare('UPDATE output_products SET is_visible=0,is_public=0 WHERE id=? AND company_id=?');
                $q->execute([$catalogId,$company]);
            }
        } else {
            foreach(['event_registrations','events_checkin','events_results','events_expenses'] as $table) {
                if(entityDeletionHasRows($db,$table,'event_id',$id)) {
                    throw new DomainException('Arrangementet har påmeldinger eller økonomisk historikk og kan ikke slettes permanent. Velg «Gjør usynlig» for å bevare historikken.');
                }
            }
            $eventReference=event_results_ledger_event_id_sql('l');
            $q=$db->prepare("SELECT l.id FROM unified_ledger l WHERE l.company_id=? AND l.module_source='events'
                AND ($eventReference=? OR l.source_id=?) LIMIT 1 FOR UPDATE");
            $q->execute([$company,$id,$id]);
            if($q->fetch()) throw new DomainException('Arrangementet har regnskapshistorikk. Velg «Gjør usynlig» i stedet.');
            entityDeletionCheckReferences($db,'events_eventdata',$id,['event_registration_fields','event_membership_plans']);
            entityFormDelete($db,$company,'event',$id);
            foreach(['event_registration_fields','event_membership_plans'] as $table) {
                $q=$db->prepare("DELETE FROM `$table` WHERE event_id=?");$q->execute([$id]);
            }
            $q=$db->prepare('DELETE FROM events_eventdata WHERE id=? AND company_id=?');$q->execute([$id,$company]);
            // Never delete the catalog parent before the event: its FK cascades.
            entityDeletionCatalogRemove($db,$company,$catalog);
        }
        $db->commit();
    } catch(Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}

function deleteRentalObjectSafely(PDO $db,string $company,string $id): void {
    entityFormRequireModuleAccess($db,$company,'rental_object');
    $db->beginTransaction();
    try {
        $q=$db->prepare('SELECT id,output_product_id FROM rental_objects WHERE id=? AND company_id=? FOR UPDATE');
        $q->execute([$id,$company]);
        $object=$q->fetch(PDO::FETCH_ASSOC);
        if(!$object) throw new OutOfBoundsException('Utleieobjekt ikke funnet eller ingen tilgang');
        $catalog=entityDeletionCatalog($db,$company,'rental',$id,$object['output_product_id']??null);
        foreach(['rental_schedule','rental_contracts'] as $table) {
            $q=$db->prepare("SELECT status FROM `$table` WHERE rental_object_id=? FOR UPDATE");$q->execute([$id]);
            $statuses=$q->fetchAll(PDO::FETCH_COLUMN);
            if(!$statuses) continue;
            $active=array_diff($statuses,['completed','cancelled','rejected','expired']);
            throw new DomainException($active
                ? 'Objektet har aktive forespørsler eller kontrakter og kan ikke slettes. Sett objektet inaktivt for å bevare historikken.'
                : 'Objektet har historiske forespørsler eller kontrakter og kan ikke slettes permanent. Avslåtte forespørsler er historikk, ikke aktive bookinger. Sett objektet inaktivt i stedet.');
        }
        if(entityDeletionHasRows($db,'rental_request_field_values','rental_object_id',$id)) {
            throw new DomainException('Objektet har lagrede kundesvar. Sett objektet inaktivt for å bevare historikken.');
        }
        $q=$db->prepare("SELECT id FROM unified_ledger WHERE company_id=? AND module_source='rental'
            AND (source_id=? OR JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.rental_object_id'))=?
            OR JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.object_id'))=?) LIMIT 1 FOR UPDATE");
        $q->execute([$company,$id,$id,$id]);
        if($q->fetch()) throw new DomainException('Objektet har regnskapshistorikk. Sett objektet inaktivt i stedet.');
        // Use the same authoritative type tables as creation. Their rows describe
        // the object, not customer history, and must cascade with an empty object.
        $configurationTables=array_column(getRentalSchemas(),'table');
        $configurationTables[]='rental_customer_field_settings';
        entityDeletionCheckReferences($db,'rental_objects',$id,$configurationTables);
        entityFormDelete($db,$company,'rental_object',$id);
        $q=$db->prepare('DELETE FROM rental_customer_field_settings WHERE company_id=? AND rental_object_id=?');
        $q->execute([$company,$id]);
        $q=$db->prepare('DELETE FROM rental_objects WHERE id=? AND company_id=?');$q->execute([$id,$company]);
        entityDeletionCatalogRemove($db,$company,$catalog);
        $db->commit();
    } catch(Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}
