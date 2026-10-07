<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_fields.php';
try {
    $company=(string)($_SESSION['company_id']??'');
    $db=getDBConnection();
    $type=(string)($_GET['entity_type']??'');
    if(!in_array($type,['event','rental_object'],true))throw new InvalidArgumentException('Ugyldig skjematype');
    entityFormRequireModuleAccess($db,$company,$type);
    $id=(string)($_GET['entity_id']??'');
    $loaded=$id!==''?entityFormLoad($db,$company,$type,$id):['fields'=>entityFormDefaults($type),'source'=>'default','registration_type'=>null];
    $token=entityFormCsrfToken();
    session_write_close();
    echo json_encode(['success'=>true]+$loaded+['revision'=>entityFormRevision($loaded),'presets'=>entityFormPresets($type),'csrf_token'=>$token],JSON_THROW_ON_ERROR);
} catch(Throwable $e) {
    http_response_code($e instanceof TenantAuditException?403:($e instanceof OutOfBoundsException?404:400));
    echo json_encode(['success'=>false,'message'=>$e instanceof PDOException?'Kunne ikke hente skjemafelt':$e->getMessage()]);
}
