<?php
declare(strict_types=1);
require_once __DIR__.'/entity_form_fields.php';

/** Read-only plan; preserves unrelated shared rows, especially membership_plan. */
function entityFormMigrationPlan(PDO $db): array {
    $entities=[];
    foreach(['event'=>'SELECT DISTINCT e.company_id,e.id FROM events_eventdata e JOIN event_registration_fields f ON f.event_id=e.id ORDER BY e.company_id,e.id',
        'rental_object'=>'SELECT DISTINCT o.company_id,o.id FROM rental_objects o JOIN rental_customer_field_settings f ON f.rental_object_id=o.id AND f.company_id=o.company_id ORDER BY o.company_id,o.id'] as $type=>$sql) {
        try{$rows=$db->query($sql)->fetchAll(PDO::FETCH_ASSOC);}
        catch(PDOException $e){if(($e->errorInfo[1]??0)!==1146)throw $e;$rows=[];}
        foreach($rows as $r) {
            $raw=entityFormRaw($db,$r['company_id'],$type,$r['id']);
            $configured=in_array('__configured',array_column($raw,'field_name'),true);
            $legacy=entityFormLegacy($db,$r['company_id'],$type,$r['id']);
            $shared=[];$invalid=[];
            foreach($raw as $field) {
                if($field['field_name']==='__configured')continue;
                try{$shared[]=entityFormNormalize($type,[$field])[0];}
                catch(JsonException|InvalidArgumentException $error) {
                    // Report invalid stored configuration; only an explicit legacy
                    // choice may replace it. Never silently repair the live row.
                    $invalid[]=entityFormCanonicalName($type,$field['field_name']);
                    $shared[]=entityFormNormalize($type,[array_replace($field,['options'=>[],'validation_rules'=>[]])])[0];
                }
            }
            $byName=array_column($shared,null,'field_name');
            $conflicts=[];
            foreach($legacy as $f)if(isset($byName[$f['field_name']]) && $byName[$f['field_name']]!=$f)$conflicts[]=$f['field_name'];
            $conflicts=array_values(array_unique(array_merge($conflicts,$invalid)));
            $entities[]=['company'=>$r['company_id'],'type'=>$type,'id'=>$r['id'],'configured'=>$configured,
                'raw'=>$raw,'legacy'=>$legacy,'shared'=>$shared,'invalid'=>$invalid,'conflicts'=>$configured?[]:$conflicts];
        }
    }
    $report=['entities'=>count($entities),'already_configured'=>0,'legacy_fields'=>0,'shared_fields'=>0,'conflicting_entities'=>0,'conflicting_fields'=>0,'invalid_shared_fields'=>0];
    foreach($entities as $e) {
        $report['already_configured']+=(int)$e['configured'];
        $report['legacy_fields']+=count($e['legacy']);
        $report['shared_fields']+=count($e['shared']);
        $report['conflicting_entities']+=(int)(bool)$e['conflicts'];
        $report['conflicting_fields']+=count($e['conflicts']);
        $report['invalid_shared_fields']+=count($e['invalid']);
    }
    $fingerprint=hash('sha256',json_encode($entities,JSON_THROW_ON_ERROR));
    return ['report'=>$report,'fingerprint'=>$fingerprint,'entities'=>$entities];
}

/** Explicit CLI operation only; never invoked from read endpoints or startup. */
function entityFormMigrationApply(PDO $db,string $expected,string $choice): array {
    if(!in_array($choice,['abort','keep-shared','use-legacy'],true))throw new InvalidArgumentException('Ugyldig konfliktvalg');
    if($db->inTransaction())throw new LogicException('Migreringen må eie transaksjonen');
    $db->beginTransaction();
    try {
        $plan=entityFormMigrationPlan($db);
        // Acquire the same entity locks as the interactive editor before rechecking.
        foreach($plan['entities'] as $e)entityFormContext($db,$e['company'],$e['type'],$e['id'],true);
        $plan=entityFormMigrationPlan($db);
        if(!hash_equals($plan['fingerprint'],$expected))throw new RuntimeException('Datagrunnlaget er endret. Kjør ny kontrollrapport før godkjenning.');
        if($plan['report']['conflicting_entities'] && $choice==='abort')throw new RuntimeException('Konflikter må avklares før overføring.');
        $changed=0;
        foreach($plan['entities'] as $e) {
            if($e['configured'])continue;
            if($e['invalid'] && ($choice!=='use-legacy' || array_diff($e['invalid'],array_column($e['legacy'],'field_name')))) {
                throw new RuntimeException('Ugyldige fellesinnstillinger må avklares eller erstattes av det eksisterende moduloppsettet.');
            }
            $desired=array_column($e['shared'],null,'field_name');
            foreach($e['legacy'] as $f) {
                if(!isset($desired[$f['field_name']]) || $choice==='use-legacy')$desired[$f['field_name']]=$f;
            }
            $q=$db->prepare('SELECT name FROM tenant_companies WHERE id=?');
            $q->execute([$e['company']]);$companyName=$q->fetchColumn();
            if($companyName===false)throw new RuntimeException('Manglende bedrift i overføringen');
            $actor=['id'=>null,'type'=>'system','name'=>'System / skjemafelt-migrering','role'=>'system','company_name'=>(string)$companyName];
            $context=entityFormContext($db,$e['company'],$e['type'],$e['id']);
            $rawByName=[];
            foreach($e['raw'] as $r)$rawByName[entityFormCanonicalName($e['type'],$r['field_name'])]=$r;
            $operation=static function()use($db,$e,$desired,$rawByName,$actor,$context) {
                $module=$e['type']==='event'?'events':'rental';
                foreach($desired as $name=>$f) {
                    $old=$rawByName[$name]??[];
                    $id=$old['id']??bin2hex(random_bytes(16));
                    $storageName=$old['field_name']??$name;
                    $values=[$f['field_label'],$f['field_type'],$f['is_required'],$f['is_visible'],$f['default_value'],
                        json_encode($f['options'],JSON_THROW_ON_ERROR),json_encode($f['validation_rules'],JSON_THROW_ON_ERROR),$f['sort_order']];
                    if($old) {
                        try{$oldNormalized=entityFormNormalize($e['type'],[$old])[0];}
                        catch(JsonException|InvalidArgumentException $error){$oldNormalized=[];}
                        if($oldNormalized==$f)continue;
                        $db->prepare('UPDATE dynamic_form_fields SET field_label=?,field_type=?,is_required=?,is_visible=?,default_value=?,options=?,validation_rules=?,sort_order=?,updated_at=NOW() WHERE id=? AND company_id=? AND entity_type=? AND entity_id=?')
                            ->execute(array_merge($values,[$id,$e['company'],$e['type'],$e['id']]));
                    } else {
                        $db->prepare('INSERT INTO dynamic_form_fields (field_label,field_type,is_required,is_visible,default_value,options,validation_rules,sort_order,id,company_id,entity_type,entity_id,field_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                            ->execute(array_merge($values,[$id,$e['company'],$e['type'],$e['id'],$storageName]));
                    }
                    $after=$f+['id'=>$id,'entity_type'=>$e['type'],'entity_id'=>$e['id']];
                    tenantAuditRecord($db,$e['company'],$module,$module.'.form_fields.migrate','dynamic_form_fields',$id,
                        $context['name'].' / '.$f['field_label'],$old,$after,'success',[],$actor,['default_value','options','validation_rules']);
                }
                $db->prepare('INSERT INTO dynamic_form_fields (id,company_id,entity_type,entity_id,field_name,field_label,field_type,is_visible,is_required,sort_order) VALUES (?,?,?,?,?,?,?,0,0,10000)')
                    ->execute([bin2hex(random_bytes(16)),$e['company'],$e['type'],$e['id'],'__configured','Skjema konfigurert','hidden']);
            };
            if($db instanceof AuditedPDO)$db->withExplicitAudit($operation);else $operation();
            $changed++;
        }
        $db->commit();
        return ['converted_entities'=>$changed]+$plan['report'];
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
