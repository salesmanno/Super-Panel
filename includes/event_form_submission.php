<?php
declare(strict_types=1);
require_once __DIR__.'/entity_form_input.php';

function eventGuestFormNotes(PDO $db,string $company,string $eventId,array $input,string $first,string $last,?string $email): string {
    $fields=entityFormLoad($db,$company,'event',$eventId)['fields'];
    $submitted=$input['optional_fields']??[];
    if(!is_array($submitted))throw new InvalidArgumentException('Ugyldige skjemasvar');
    $core=['first_name'=>$first,'last_name'=>$last,'email'=>$email??''];
    foreach($fields as $field)if($field['is_visible'] && array_key_exists($field['field_name'],$core)) {
        $submitted[$field['field_name']]=$core[$field['field_name']];
    }
    $values=validateEntityFormValues($fields,$submitted);
    $snapshot=[];
    foreach($fields as $field)if($field['is_visible'])$snapshot[]=[
        'field_name'=>$field['field_name'],'field_label'=>$field['field_label'],
        'field_type'=>$field['field_type'],'value'=>$values[$field['field_name']],
    ];
    // Existing answer storage; snapshots remain independent of later field edits.
    $notes=json_encode(['format'=>'entity_form_v1','guest'=>$core,'form_fields'=>$snapshot],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    if(strlen($notes)>60000)throw new InvalidArgumentException('Skjemasvarene er samlet for lange');
    return $notes;
}

function eventGuestFormSnapshot(?string $notes): array {
    // Existing no-show handling appends a newline; retain the original snapshot.
    $value=json_decode(explode("\n",(string)$notes,2)[0],true);
    if(!is_array($value) || ($value['format']??'')!=='entity_form_v1')return [];
    return ['guest'=>is_array($value['guest']??null)?$value['guest']:[],
        'form_fields'=>is_array($value['form_fields']??null)?$value['form_fields']:[]];
}
