<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_fields.php';
require_once __DIR__.'/../includes/SalesCode128.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    $db=getDBConnection();$company=(string)($_SESSION['company_id']??'');
    entityFormRequireModuleAccess($db,$company,'sales_product');
    $q=$db->prepare('SELECT barcode FROM sales_products WHERE company_id=? AND id=?');
    $q->execute([$company,(string)($_GET['product_id']??'')]);$code=$q->fetchColumn();
    if(!$code)throw new OutOfBoundsException('Lagre produktet for å generere kode');
    header('Content-Type: image/svg+xml');echo SalesCode128::svg($code);
} catch(Throwable $e) {http_response_code(400);header('Content-Type: text/plain; charset=utf-8');echo 'Strekkoden kunne ikke vises.';}
