<?php
declare(strict_types=1);
require_once __DIR__ . '/tenant_audit.php';
require_once __DIR__ . '/audit_resources.php';

/** Shared definitions, never submitted answers. All access is entity + tenant scoped. */
function entityFormContext(PDO $db, string $company, string $type, string $id, bool $lock = false): array {
    $table = match ($type) {
        'event' => 'events_eventdata', 'rental_object' => 'rental_objects',
        default => throw new InvalidArgumentException('Ugyldig skjematype'),
    };
    $columns = $type === 'event' ? 'id,title AS name,registration_type' : 'id,object_name AS name';
    $q = $db->prepare("SELECT $columns FROM $table WHERE id = ? AND company_id = ?" . ($lock ? ' FOR UPDATE' : ''));
    $q->execute([$id, $company]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new OutOfBoundsException('Arrangement eller objekt ikke funnet');
    return $row;
}

function entityFormCanonicalName(string $type, string $name): string {
    $aliases = $type === 'rental_object'
        ? ['address_street'=>'street_address','address_zip'=>'postal_code','address_city'=>'city']
        : [];
    return $aliases[$name] ?? $name;
}

function entityFormRequireModuleAccess(PDO $db,string $company,string $type): array {
    $actor=tenantAuditActor($db,$company);
    $module=match($type){'event'=>'events','rental_object'=>'rental',default=>throw new InvalidArgumentException('Ugyldig skjematype')};
    $q=$db->prepare('SELECT sm.id FROM system_modules sm JOIN tenant_admin_modules tam ON tam.module_id=sm.id WHERE tam.tenant_admin_id=? AND sm.module_key=? AND sm.is_active=1');
    $q->execute([$actor['id'],$module]);
    if(!$q->fetchColumn())throw new TenantAuditException('Du har ikke tilgang til denne modulen');
    return $actor;
}

function entityFormDefaults(string $type): array {
    $definitions = $type === 'event'
        ? ['first_name'=>'Fornavn','last_name'=>'Etternavn','email'=>'E-postadresse','phone'=>'Telefonnummer',
            'street'=>'Gateadresse','zip'=>'Postnummer','city'=>'Poststed','postal'=>'Postboks','age'=>'Alder']
        : ['first_name'=>'Fornavn','last_name'=>'Etternavn','phone'=>'Telefon','email'=>'E-post',
            'postal_code'=>'Postnummer','street_address'=>'Gateadresse','city'=>'Poststed','age'=>'Alder'];
    $fields = [];
    foreach ($definitions as $name => $label) {
        $fields[] = [
            'field_name'=>$name,'field_label'=>$label,
            'field_type'=>match($name) {'email'=>'email','phone'=>'tel','age'=>'number',default=>'text'},
            'is_required'=>(int)($type === 'event' && in_array($name,['first_name','last_name'],true)),
            'is_visible'=>(int)($type !== 'event' || in_array($name,['first_name','last_name','email'],true)),
            'sort_order'=>count($fields),'default_value'=>null,'options'=>[],
            'validation_rules'=>$name === 'age' ? ['min'=>0,'max'=>130] : [],
        ];
    }
    return $fields;
}

function entityFormPresets(string $type): array {
    $standard = entityFormDefaults($type);
    $minimal = $standard;
    foreach ($minimal as &$field) {
        $field['is_visible'] = (int)in_array($field['field_name'],['first_name','last_name','email'],true);
        $field['is_required'] = (int)in_array($field['field_name'],['first_name','last_name'],true);
    }
    unset($field);
    return [['key'=>'standard','label'=>'Standardoppsett','fields'=>$standard],
        ['key'=>'contact','label'=>'Minimalt kontaktoppsett','fields'=>$minimal]];
}

function entityFormDecode($value): array {
    if ($value === null || $value === '') return [];
    if (is_array($value)) return $value;
    $decoded = json_decode((string)$value,true,512,JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) throw new InvalidArgumentException('Feltinnstillinger er ugyldige');
    return $decoded;
}

function entityFormNormalize(string $type, array $fields): array {
    if (count($fields)>40) throw new InvalidArgumentException('For mange skjemafelt');
    $result = [];
    foreach ($fields as $index=>$f) {
        if (!is_array($f)) throw new InvalidArgumentException('Ugyldig skjemafelt');
        $nameInput=$f['field_name']??$f['field']??'';
        if(!is_string($nameInput))throw new InvalidArgumentException('Ugyldig feltnavn');
        $name = entityFormCanonicalName($type,$nameInput);
        if (!preg_match('/^[a-z][a-z0-9_]{0,99}$/D',$name)
            || preg_match('/password|secret|token|access_code/',$name) || isset($result[$name])) {
            throw new InvalidArgumentException('Ugyldig eller gjentatt feltnavn');
        }
        $labelInput=$f['field_label']??$f['label']??$name;
        $kind=$f['field_type']??'text';
        if(!is_string($labelInput) || !is_string($kind))throw new InvalidArgumentException('Ugyldig etikett eller felttype');
        $label = trim($labelInput);
        if ($label === '' || mb_strlen($label)>255 || !in_array($kind,['text','email','tel','number','date','textarea','select','checkbox'],true)) {
            throw new InvalidArgumentException('Ugyldig etikett eller felttype');
        }
        $options = entityFormDecode($f['options'] ?? []);
        if (!array_is_list($options) || count($options)>50) throw new InvalidArgumentException('Ugyldige feltvalg');
        foreach ($options as $option) if (!is_string($option) || $option === '' || mb_strlen($option)>255) {
            throw new InvalidArgumentException('Ugyldig feltvalg');
        }
        if ($kind === 'select' && !$options) throw new InvalidArgumentException('Valgfelt må ha minst ett valg');
        $rules = entityFormDecode($f['validation_rules'] ?? []);
        foreach(['maxLength'=>'max_length','minLength'=>'min_length'] as $oldKey=>$newKey) {
            if(array_key_exists($oldKey,$rules)) {
                $rules[$newKey]=$rules[$newKey]??$rules[$oldKey];
                unset($rules[$oldKey]);
            }
        }
        if (array_diff(array_keys($rules),['min','max','max_length','min_length','pattern'])) throw new InvalidArgumentException('Ukjent valideringsregel');
        foreach ($rules as $key=>$value) {
            if($key==='pattern') {
                if(!is_string($value) || strlen($value)>300 || str_contains($value,"\0")
                    || @preg_match('~(*LIMIT_MATCH=10000)(*LIMIT_DEPTH=100)'.str_replace('~','\\~',$value).'~u','')===false) {
                    throw new InvalidArgumentException('Ugyldig feltmønster');
                }
                continue;
            }
            if (!is_numeric($value) || !is_finite((float)$value)) throw new InvalidArgumentException('Ugyldig valideringsregel');
            $rules[$key] = in_array($key,['max_length','min_length'],true) ? (int)$value : (float)$value;
        }
        if (isset($rules['max_length']) && ($rules['max_length']<1 || $rules['max_length']>5000)
            || isset($rules['min'],$rules['max']) && $rules['min']>$rules['max']) {
            throw new InvalidArgumentException('Ugyldig valideringsgrense');
        }
        if(isset($rules['min_length']) && ($rules['min_length']<0 || $rules['min_length']>5000
            || isset($rules['max_length']) && $rules['min_length']>$rules['max_length']))throw new InvalidArgumentException('Ugyldig minste lengde');
        $visibleInput=$f['is_visible']??$f['selected']??true;
        $requiredInput=$f['is_required']??$f['required']??false;
        foreach([$visibleInput,$requiredInput] as $flag)if(!in_array($flag,[0,1,'0','1',false,true],true))throw new InvalidArgumentException('Ugyldig feltstatus');
        $visible = (int)(bool)$visibleInput;
        $required = (int)(bool)$requiredInput;
        if ($required && !$visible) throw new InvalidArgumentException('Et påkrevd felt må være synlig');
        $default = $f['default_value'] ?? null;
        if ($default !== null && (!is_scalar($default) || mb_strlen((string)$default)>5000)) throw new InvalidArgumentException('Ugyldig standardverdi');
        $order = $f['sort_order'] ?? $f['order'] ?? $f['display_order'] ?? $index;
        if (!is_numeric($order) || (int)$order<0 || (int)$order>10000) throw new InvalidArgumentException('Ugyldig rekkefølge');
        $result[$name] = ['field_name'=>$name,'field_label'=>$label,'field_type'=>$kind,
            'is_required'=>$required,'is_visible'=>$visible,'sort_order'=>(int)$order,
            'default_value'=>$default === null ? null : (string)$default,'options'=>array_values(array_unique($options)),
            'validation_rules'=>$rules];
    }
    usort($result,static fn($a,$b)=>$a['sort_order']<=>$b['sort_order']);
    return array_values($result);
}

function entityFormRaw(PDO $db,string $company,string $type,string $id,bool $lock=false): array {
    $q=$db->prepare('SELECT * FROM dynamic_form_fields WHERE company_id=? AND entity_type=? AND entity_id=? ORDER BY sort_order,id'.($lock?' FOR UPDATE':''));
    $q->execute([$company,$type,$id]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function entityFormLegacy(PDO $db,string $company,string $type,string $id): array {
    try {
        if ($type==='event') {
            $q=$db->prepare('SELECT f.* FROM event_registration_fields f JOIN events_eventdata e ON e.id=f.event_id WHERE e.company_id=? AND e.id=? ORDER BY f.display_order,f.id');
        } else {
            $q=$db->prepare('SELECT field_key AS field_name,selected AS is_visible,required AS is_required,display_order AS sort_order FROM rental_customer_field_settings WHERE company_id=? AND rental_object_id=? ORDER BY display_order,id');
        }
        $q->execute([$company,$id]);
        $rows=$q->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $error) {
        if (($error->errorInfo[1]??0)!==1146) throw $error;
        return [];
    }
    $defaults=array_column(entityFormDefaults($type),null,'field_name');
    foreach($rows as &$r) {
        $name=(string)$r['field_name'];
        $r=array_replace($defaults[$name]??[], $r);
        $r['sort_order']=$r['display_order']??$r['sort_order']??0;
    }
    unset($r);
    return entityFormNormalize($type,$rows);
}

/** Legacy reads are transitional and read-only; never copy at page load. */
function entityFormLoad(PDO $db,string $company,string $type,string $id): array {
    $context=entityFormContext($db,$company,$type,$id);
    $raw=entityFormRaw($db,$company,$type,$id);
    $fields=array_values(array_filter($raw,static fn($f)=>$f['field_name']!=='__configured'));
    $configured=in_array('__configured',array_column($raw,'field_name'),true);
    $legacy=$configured?[]:entityFormLegacy($db,$company,$type,$id);
    if ($configured || ($raw && !$legacy)) {
        $fields=entityFormNormalize($type,$fields);
        $source='dynamic';
    } else {
        $fields=$legacy;
        $source=$fields?'legacy':'default';
        if (!$fields) $fields=entityFormDefaults($type);
    }
    if ($type==='event' && ($context['registration_type']??'')==='none') $fields=[];
    return ['fields'=>$fields,'source'=>$source,'registration_type'=>$context['registration_type']??null];
}

function entityFormEventSelection(array $data): array {
    $fields=entityFormDefaults('event');
    foreach ($fields as &$field) {
        $name=$field['field_name'];
        $field['is_visible']=(int)(in_array($name,['first_name','last_name'],true) || !empty($data['field_'.$name]));
    }
    unset($field);
    return $fields;
}

function entityFormSave(PDO $db,string $company,string $type,string $id,array $input): array {
    $own=!$db->inTransaction();
    if($own)$db->beginTransaction();
    try {
        $context=entityFormContext($db,$company,$type,$id,true);
        $actor=entityFormRequireModuleAccess($db,$company,$type);
        $fields=entityFormNormalize($type,$input);
        require_once __DIR__.'/entity_form_input.php';
        foreach($fields as $field)if($field['default_value']!==null && $field['default_value']!=='') {
            validateEntityFormValues([array_replace($field,['is_visible'=>1,'is_required'=>0])],[$field['field_name']=>$field['default_value']]);
        }
        if ($type==='event') {
            $visible=array_column(array_filter($fields,static fn($f)=>$f['is_visible']),null,'field_name');
            if (($context['registration_type']??'')==='none') {
                if ($visible) throw new InvalidArgumentException('Arrangement uten påmelding kan ikke ha synlige påmeldingsfelt');
            } elseif (empty($visible['first_name']['is_required']) || empty($visible['last_name']['is_required'])
                || (!isset($visible['email']) && !isset($visible['phone']))) {
                throw new InvalidArgumentException('Fornavn og etternavn må være påkrevd, og minst ett kontaktfelt må være synlig');
            }
        }
        $before=entityFormRaw($db,$company,$type,$id,true);
        $byName=[];
        foreach ($before as $row) {
            $canonical=entityFormCanonicalName($type,$row['field_name']);
            if(isset($byName[$canonical])) throw new RuntimeException('Motstridende eksisterende feltnavn må avklares');
            $byName[$canonical]=$row;
        }
        // Explicit invisible marker distinguishes intentionally empty from not configured.
        $desired=$fields;
        $desired[]=['field_name'=>'__configured','field_label'=>'Skjema konfigurert','field_type'=>'hidden',
            'is_required'=>0,'is_visible'=>0,'sort_order'=>10000,'default_value'=>null,'options'=>[],'validation_rules'=>[]];
        $operation=static function() use($db,$company,$type,$id,$desired,$byName,$context,$actor) {
            $module=$type==='event'?'events':'rental';
            foreach($desired as $field) {
                $old=$byName[$field['field_name']]??[];
                unset($byName[$field['field_name']]);
                $new=$field;
                $new['id']=$old['id']??bin2hex(random_bytes(16));
                $new['entity_type']=$type;
                $new['entity_id']=$id;
                // Preserve alias names already present in shared storage.
                $new['field_name']=$old['field_name']??$field['field_name'];
                $values=[$new['field_label'],$new['field_type'],$new['is_required'],$new['is_visible'],
                    $new['default_value'],json_encode($new['options'],JSON_THROW_ON_ERROR),
                    json_encode($new['validation_rules'],JSON_THROW_ON_ERROR),$new['sort_order']];
                if($old) {
                    $oldComparable=entityFormDecode($old['options']??null);
                    $oldRules=entityFormDecode($old['validation_rules']??null);
                    if ($old['field_label']===$new['field_label'] && $old['field_type']===$new['field_type']
                        && (int)$old['is_required']===$new['is_required'] && (int)$old['is_visible']===$new['is_visible']
                        && (int)$old['sort_order']===$new['sort_order'] && $old['default_value']===$new['default_value']
                        && $oldComparable===$new['options'] && $oldRules==$new['validation_rules']) continue;
                    $db->prepare('UPDATE dynamic_form_fields SET field_label=?,field_type=?,is_required=?,is_visible=?,default_value=?,options=?,validation_rules=?,sort_order=?,updated_at=NOW() WHERE id=? AND company_id=? AND entity_type=? AND entity_id=?')
                        ->execute(array_merge($values,[$new['id'],$company,$type,$id]));
                } else {
                    $db->prepare('INSERT INTO dynamic_form_fields (field_label,field_type,is_required,is_visible,default_value,options,validation_rules,sort_order,id,company_id,entity_type,entity_id,field_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                        ->execute(array_merge($values,[$new['id'],$company,$type,$id,$new['field_name']]));
                }
                $links=['entity_type'=>$type,'entity_id'=>$id];
                tenantAuditRecord($db,$company,$module,$module.'.form_fields.'.($old?'update':'insert'),
                    'dynamic_form_fields',$new['id'],$context['name'].' / '.$new['field_label'],$old,$new,'success',$links,$actor,
                    ['default_value','options','validation_rules']);
            }
            foreach($byName as $old) {
                $db->prepare('DELETE FROM dynamic_form_fields WHERE id=? AND company_id=? AND entity_type=? AND entity_id=?')
                    ->execute([$old['id'],$company,$type,$id]);
                tenantAuditRecord($db,$company,$module,$module.'.form_fields.delete','dynamic_form_fields',
                    $old['id'],$context['name'].' / '.$old['field_label'],$old,[],'success',
                    ['entity_type'=>$type,'entity_id'=>$id],$actor);
            }
        };
        if($db instanceof AuditedPDO)$db->withExplicitAudit($operation); else $operation();
        if($own)$db->commit();
        return $fields;
    } catch(Throwable $e) {
        if($own && $db->inTransaction())$db->rollBack();
        throw $e;
    }
}

function entityFormCsrfToken(): string {
    if(empty($_SESSION['entity_form_csrf']))$_SESSION['entity_form_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['entity_form_csrf'];
}

function entityFormDelete(PDO $db,string $company,string $type,string $id): void {
    if(!$db->inTransaction())throw new LogicException('Sletting av felt krever samme transaksjon som ressursen');
    $context=entityFormContext($db,$company,$type,$id,true);
    $actor=tenantAuditActor($db,$company);
    $rows=entityFormRaw($db,$company,$type,$id,true);
    $operation=static function()use($db,$company,$type,$id,$rows,$actor,$context) {
        $module=$type==='event'?'events':'rental';
        $db->prepare('DELETE FROM dynamic_form_fields WHERE company_id=? AND entity_type=? AND entity_id=?')->execute([$company,$type,$id]);
        foreach($rows as $row)tenantAuditRecord($db,$company,$module,$module.'.form_fields.delete',
            'dynamic_form_fields',$row['id'],$context['name'].' / '.$row['field_label'],$row,[],'success',[],$actor);
    };
    if($db instanceof AuditedPDO)$db->withExplicitAudit($operation);else $operation();
}

function entityFormRequireCsrf(array $input): void {
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')throw new InvalidArgumentException('Kun POST tillatt');
    $token=$_SERVER['HTTP_X_CSRF_TOKEN']??$input['csrf_token']??'';
    if(!is_string($token) || empty($_SESSION['entity_form_csrf']) || !hash_equals($_SESSION['entity_form_csrf'],$token)) {
        throw new DomainException('Ugyldig sikkerhetskode. Last skjemaet på nytt.');
    }
}
