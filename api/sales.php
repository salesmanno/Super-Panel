<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/SalesService.php';
try {
    if(empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
        http_response_code(401);throw new TenantAuditException('Logg inn før du bruker kassen');
    }
    $action=(string)($_GET['action']??'');
    $read=in_array($action,['bootstrap','products','cart','orders','order','fields','register_state','register_document'],true);
    if(($_SERVER['REQUEST_METHOD']??'')!==($read?'GET':'POST')) {
        http_response_code(405);throw new InvalidArgumentException('Ugyldig forespørselsmetode');
    }
    $token=entityFormCsrfToken();
    $data=[];
    if(!$read) {
        $raw=file_get_contents('php://input');
        if(strlen($raw)>100000)throw new InvalidArgumentException('Forespørselen er for stor');
        $data=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
        if(!is_array($data))throw new InvalidArgumentException('Ugyldig forespørsel');
        entityFormRequireCsrf($data);
    }
    // The same authenticated actor/company resumes its cart after login. A cart
    // key is never accepted from the client, and provider HTTP holds no session lock.
    $cartToken=SalesService::cartOwnerToken((string)$_SESSION['company_id'],(string)$_SESSION['tenant_admin_id']);
    $legacyCartToken='sales-admin:'.hash('sha256',session_id().'|'.$_SESSION['company_id'].'|'.$_SESSION['tenant_admin_id']);
    session_write_close();
    $service=new SalesService(getDBConnection(),(string)$_SESSION['company_id'],(string)$_SESSION['tenant_admin_id'],$cartToken);
    $service->adoptSessionCart($legacyCartToken);
    $result=match($action) {
        'bootstrap'=>$service->bootstrap(),
        'products'=>$service->productList(),
        'cart'=>$service->cartResult(),
        'orders'=>['orders'=>$service->orders()],
        'order'=>['order'=>$service->orderView((string)($_GET['order_id']??''))],
        'fields'=>['fields'=>$service->fields((string)($_GET['product_id']??''))],
        'product_save'=>$service->saveProduct($data),
        'stock'=>$service->adjustStock($data),
        'add','quantity','remove'=>$service->mutateCart($action,$data),
        'checkout'=>$service->checkout($data),
        'payment_status'=>['order'=>$service->reconcile((string)($data['order_id']??''))],
        'cancel_payment'=>['order'=>$service->reconcile((string)($data['order_id']??''),true)],
        'receipt_email'=>$service->sendReceipt($data),
        'next_customer'=>$service->nextCustomer(),
        'register_state'=>$service->registerState(),
        'register_document'=>$service->registerDocument((string)($_GET['document_id']??'')),
        'register_create','register_activate','register_select','register_open','register_cash'=>$service->registerAction($action,$data),
        'register_report'=>$service->registerReport($data),
        'register_copy'=>$service->registerCopy($data),
        'register_return'=>$service->registerReturn($data),
        'register_print'=>$service->registerPrint($data),
        default=>throw new InvalidArgumentException('Ukjent Sales-handling'),
    };
    echo json_encode(['success'=>true]+$result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    if(http_response_code()<400)http_response_code($e instanceof SalesConflict?409:($e instanceof TenantAuditException || $e instanceof DomainException?403:($e instanceof OutOfBoundsException?404:($e instanceof InvalidArgumentException || $e instanceof JsonException?400:500))));
    // SQL, credentials and provider payloads never leave this controller.
    $message=$e instanceof PDOException?'Sales kunne ikke fullføre databasehandlingen. Ingen ny betaling skal startes før status er kontrollert.':$e->getMessage();
    echo json_encode(['success'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE);
}
