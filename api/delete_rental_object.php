<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_deletion.php';
try {
    $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($data) || !is_string($data['id']??null) || $data['id']==='') {
        throw new InvalidArgumentException('Objekt-ID mangler');
    }
    entityFormRequireCsrf($data);
    deleteRentalObjectSafely(getDBConnection(),(string)($_SESSION['company_id']??''),$data['id']);
    echo json_encode(['success'=>true,'message'=>'Tomt utleieobjekt slettet permanent.']);
} catch(Throwable $e) {
    http_response_code($e instanceof TenantAuditException?403:($e instanceof OutOfBoundsException?404:($e instanceof DomainException?409:400)));
    error_log('delete_rental_object: '.get_class($e));
    echo json_encode(['success'=>false,'message'=>$e instanceof PDOException
        ? 'Kunne ikke slette objektet. Ingen endringer ble lagret.' : $e->getMessage()]);
}
