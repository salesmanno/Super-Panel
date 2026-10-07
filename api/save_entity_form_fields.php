<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_fields.php';
try {
    $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($data) || !is_array($data['fields']??null))throw new InvalidArgumentException('Skjemafelt mangler');
    entityFormRequireCsrf($data);
    if (!is_string($data['expected_revision']??null) || !preg_match('/^[a-f0-9]{64}$/',$data['expected_revision'])) {
        throw new InvalidArgumentException('Åpne skjemafeltene på nytt før du lagrer.');
    }
    $db=getDBConnection();
    $company=(string)($_SESSION['company_id']??'');
    $type=(string)($data['entity_type']??'');$id=(string)($data['entity_id']??'');
    if($type==='event' && (entityFormContext($db,$company,$type,$id)['registration_type']??'')==='none') {
        throw new DomainException('Arrangementer uten påmelding har ingen redigerbare skjemafelt. Det tidligere feltoppsettet er bevart.');
    }
    $db->beginTransaction();
    try {
        $fields=entityFormSave($db,$company,$type,$id,$data['fields'],$data['expected_revision']);
        $revision=entityFormRevision(entityFormLoad($db,$company,$type,$id));
        $db->commit();
    } catch(Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
    echo json_encode(['success'=>true,'fields'=>$fields,'revision'=>$revision],JSON_THROW_ON_ERROR);
} catch(Throwable $e) {
    http_response_code($e instanceof EntityFormConflictException?409:($e instanceof DomainException || $e instanceof TenantAuditException?403:($e instanceof OutOfBoundsException?404:400)));
    echo json_encode(['success'=>false,'message'=>$e instanceof PDOException?'Skjemafeltene kunne ikke lagres':$e->getMessage()]);
}
