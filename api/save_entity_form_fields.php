<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_fields.php';
try {
    $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($data) || !is_array($data['fields']??null))throw new InvalidArgumentException('Skjemafelt mangler');
    entityFormRequireCsrf($data);
    $fields=entityFormSave(getDBConnection(),(string)($_SESSION['company_id']??''),
        (string)($data['entity_type']??''),(string)($data['entity_id']??''),$data['fields']);
    echo json_encode(['success'=>true,'fields'=>$fields],JSON_THROW_ON_ERROR);
} catch(Throwable $e) {
    http_response_code($e instanceof DomainException || $e instanceof TenantAuditException?403:($e instanceof OutOfBoundsException?404:400));
    echo json_encode(['success'=>false,'message'=>$e instanceof PDOException?'Skjemafeltene kunne ikke lagres':$e->getMessage()]);
}
