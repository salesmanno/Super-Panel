<?php
declare(strict_types=1);
require_once __DIR__.'/entity_form_fields.php';

/** Used by renderers and submission controllers; values are never sent to audit. */
function validateEntityFormValues(array $definitions,array $submitted): array {
    $visible=[];
    foreach($definitions as $f)if(!empty($f['is_visible']))$visible[$f['field_name']]=$f;
    if(array_diff(array_keys($submitted),array_keys($visible)))throw new InvalidArgumentException('Ugyldig eller skjult skjemafelt');
    $values=[];
    foreach($visible as $name=>$f) {
        $value=$submitted[$name]??$f['default_value']??'';
        if(!is_scalar($value))throw new InvalidArgumentException('Ugyldig feltverdi');
        $value=trim((string)$value);
        $label=$f['field_label'];
        $type=$f['field_type'];
        if($type==='checkbox') {
            if(!in_array($value,['','0','1'],true))throw new InvalidArgumentException("$label er ugyldig");
            $value=$value==='1'?'1':'0';
        }
        if(!empty($f['is_required']) && ($value==='' || $type==='checkbox' && $value!=='1')) {
            throw new InvalidArgumentException("$label må fylles ut");
        }
        $rules=entityFormDecode($f['validation_rules']??[]);
        $maxLength=(int)($rules['max_length']??($type==='textarea'?5000:255));
        // Names/contact columns remain bounded by the existing customer schema.
        if(in_array($name,['first_name','last_name','email','phone','street','zip','city','postal','street_address','postal_code'],true))$maxLength=min($maxLength,255);
        if(mb_strlen($value)>$maxLength)throw new InvalidArgumentException("$label er for lang");
        if($value!=='') {
            if(isset($rules['min_length']) && mb_strlen($value)<$rules['min_length'])throw new InvalidArgumentException("$label er for kort");
            if(isset($rules['pattern']) && @preg_match('~(*LIMIT_MATCH=10000)(*LIMIT_DEPTH=100)'.str_replace('~','\\~',$rules['pattern']).'~u',$value)!==1)throw new InvalidArgumentException("$label har ugyldig format");
            if(($type==='email' || $name==='email') && !filter_var($value,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('E-postadressen er ugyldig');
            if($type==='number' || $name==='age') {
                if(!is_numeric($value) || !is_finite((float)$value))throw new InvalidArgumentException("$label må være et tall");
                if($name==='age' && (!ctype_digit($value) || (int)$value>130))throw new InvalidArgumentException('Alder er ugyldig');
                if(isset($rules['min']) && (float)$value<$rules['min'] || isset($rules['max']) && (float)$value>$rules['max']) {
                    throw new InvalidArgumentException("$label er utenfor tillatt område");
                }
            }
            if($type==='date') {
                $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
                if(!$date || $date->format('Y-m-d')!==$value)throw new InvalidArgumentException("$label har ugyldig dato");
            }
            if($type==='select' && !in_array($value,entityFormDecode($f['options']??[]),true))throw new InvalidArgumentException("$label har ugyldig valg");
        }
        $values[$name]=$value;
    }
    return $values;
}

function renderEntityFormField(array $field,string $prefix='fields'): string {
    if(empty($field['is_visible']))return '';
    $escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
    $name=$escape($field['field_name']);
    $label=$escape($field['field_label']);
    $type=$field['field_type'];
    $value=(string)($field['default_value']??'');
    $required=!empty($field['is_required'])?' required':'';
    $attributes=' id="field-'.$name.'" name="'.$escape($prefix).'['.$name.']"'.$required;
    $rules=entityFormDecode($field['validation_rules']??[]);
    foreach($rules as $key=>$limit) {
        $attribute=match($key){'min'=>'min','max'=>'max','max_length'=>'maxlength','min_length'=>'minlength','pattern'=>'pattern',default=>null};
        if($attribute)$attributes.=' '.$attribute.'="'.$escape($limit).'"';
    }
    if($type==='select') {
        $input='<select class="form-select"'.$attributes.'><option value="">Velg …</option>';
        foreach(entityFormDecode($field['options']??[]) as $option)$input.='<option value="'.$escape($option).'"'.($value===$option?' selected':'').'>'.$escape($option).'</option>';
        $input.='</select>';
    } elseif($type==='textarea') {
        $input='<textarea class="form-control"'.$attributes.'>'.$escape($value).'</textarea>';
    } elseif($type==='checkbox') {
        $input='<input class="form-check-input" type="checkbox"'.$attributes.' value="1"'.($value==='1'?' checked':'').'>';
    } else {
        if(!in_array($type,['text','email','tel','number','date'],true))throw new InvalidArgumentException('Ukjent felttype');
        $input='<input class="form-control" type="'.$escape($type).'"'.$attributes.' value="'.$escape($value).'"'.($type==='number'?' step="any"':'').'>';
    }
    return '<div class="mb-3"><label class="form-label" for="field-'.$name.'">'.$label.($required?' <span class="text-danger">*</span>':'').'</label>'.$input.'</div>';
}
