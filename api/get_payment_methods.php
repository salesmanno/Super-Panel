<?php
session_start();
session_write_close();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/active_payment_methods.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Kun GET tillatt']);
    exit;
}
if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Ikke autentisert']);
    exit;
}
try {
    $db=getDBConnection();
    $methods = activePaymentMethods($db, (string)$_SESSION['company_id']);
    if (!empty($_GET['event_id'])) {
        $stmt=$db->prepare('SELECT registration_type,payment_methods_json FROM events_eventdata WHERE id=? AND company_id=?');
        $stmt->execute([$_GET['event_id'],$_SESSION['company_id']]);
        $event=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) throw new OutOfBoundsException('Arrangement ikke funnet.');
        $allowed=array_map('historicalPaymentMethodName',json_decode($event['payment_methods_json']??'[]',true)??[]);
        $methods=array_values(array_filter($methods,static fn($method)=>in_array($method['method_type'],$allowed,true) && ($event['registration_type']!=='binding' || $method['method_type']!=='cash')));
    }
    echo json_encode(['success'=>true,'payment_methods'=>$methods,'total'=>count($methods)]);
} catch (Exception $e) {
    error_log('Get payment methods error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kunne ikke laste aktiverte betalingsmetoder.']);
}
