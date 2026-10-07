<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_deletion.php';
try {
    $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($data) || !is_string($data['event_id']??null) || $data['event_id']==='') {
        throw new InvalidArgumentException('Arrangement-ID mangler');
    }
    entityFormRequireCsrf($data);
    $mode=$data['delete_mode']??'soft';
    if(!is_string($mode)) throw new InvalidArgumentException('Ugyldig slettevalg');
    deleteEventSafely(getDBConnection(),(string)($_SESSION['company_id']??''),$data['event_id'],$mode);
    echo json_encode(['success'=>true,'message'=>$mode==='soft'
        ? 'Arrangement gjort usynlig. Historikken er bevart.' : 'Tomt arrangement slettet permanent.']);
} catch(Throwable $e) {
    http_response_code($e instanceof TenantAuditException?403:($e instanceof OutOfBoundsException?404:($e instanceof DomainException?409:400)));
    error_log('delete_event: '.get_class($e));
    echo json_encode(['success'=>false,'message'=>$e instanceof PDOException
        ? 'Kunne ikke slette arrangementet. Ingen endringer ble lagret.' : $e->getMessage()]);
}
