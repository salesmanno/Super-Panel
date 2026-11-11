<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Kun POST tillatt']);
    exit;
}
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Ikke autentisert']);
    exit;
}

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/active_payment_methods.php';
require_once __DIR__.'/../includes/tenant_audit.php';

$data=json_decode(file_get_contents('php://input'),true);
$company=(string)$_SESSION['company_id'];
$action=(string)($data['action']??'');

try {
    $db=getDBConnection();
    $actor=tenantAuditActor($db,$company);
    $permission=$db->prepare("SELECT 1 FROM tenant_admin_modules tam
        JOIN system_modules sm ON sm.id=tam.module_id
        WHERE tam.tenant_admin_id=? AND sm.module_key='economy' AND sm.is_active=1");
    $permission->execute([$actor['id']]);
    if (!$permission->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Du trenger Økonomi-modulen for å administrere bankkontoer.']);
        exit;
    }
    if (!in_array($action,['create','select'],true)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Ugyldig handling.']);
        exit;
    }

    $db->beginTransaction();
    activePaymentMethodsLock($db,$company);
    if ($action==='create') {
        $bank=trim((string)($data['bank_name']??''));
        $account=trim((string)($data['account_number']??''));
        if ($bank==='' || mb_strlen($bank)>120 || !preg_match('/^[A-Za-z0-9 .-]{5,34}$/D',$account)) {
            $db->rollBack();
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'Oppgi banknavn og et gyldig kontonummer (5–34 tegn).']);
            exit;
        }
        $duplicate=$db->prepare('SELECT id FROM economy_bank_accounts WHERE company_id=? AND account_number=? LIMIT 1');
        $duplicate->execute([$company,$account]);
        if ($duplicate->fetchColumn()) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['success'=>false,'message'=>'Denne bankkontoen er allerede lagt til.']);
            exit;
        }
        $id=(string)$db->query('SELECT UUID()')->fetchColumn();
        $insert=$db->prepare('INSERT INTO economy_bank_accounts(id,company_id,bank_name,account_number,is_active) VALUES(?,?,?,?,0)');
        $insert->execute([$id,$company,$bank,$account]);
        tenantAuditChange($db,$company,'economy','BANK_ACCOUNT_CREATE','economy_bank_accounts',$id,$bank,[],
            ['id'=>$id,'bank_name'=>$bank,'is_active'=>0],[],$actor);
        $db->commit();
        echo json_encode(['success'=>true,'message'=>'Bankkonto lagt til. Velg den som primærkonto for å aktivere faktura.']);
        exit;
    }

    $id=trim((string)($data['id']??''));
    $target=$db->prepare('SELECT id,bank_name,account_number,is_active FROM economy_bank_accounts WHERE id=? AND company_id=? FOR UPDATE');
    $target->execute([$id,$company]);
    $selected=$target->fetch(PDO::FETCH_ASSOC);
    if (!$selected || trim((string)$selected['account_number'])==='') {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Bankkontoen ble ikke funnet eller mangler kontonummer.']);
        exit;
    }
    $current=$db->prepare('SELECT id,bank_name,is_active FROM economy_bank_accounts WHERE company_id=? AND is_active=1 FOR UPDATE');
    $current->execute([$company]);
    $previous=$current->fetchAll(PDO::FETCH_ASSOC);
    $db->prepare('UPDATE economy_bank_accounts SET is_active=0 WHERE company_id=? AND is_active<>0')->execute([$company]);
    $db->prepare('UPDATE economy_bank_accounts SET is_active=1 WHERE id=? AND company_id=?')->execute([$id,$company]);
    foreach ($previous as $old) {
        if ($old['id']===$id) continue;
        tenantAuditChange($db,$company,'economy','BANK_ACCOUNT_PRIMARY_REMOVED','economy_bank_accounts',
            (string)$old['id'],(string)$old['bank_name'],['id'=>$old['id'],'bank_name'=>$old['bank_name'],'is_active'=>1],
            ['id'=>$old['id'],'bank_name'=>$old['bank_name'],'is_active'=>0],[],$actor);
    }
    $beforeSelected=(int)$selected['is_active'];
    if ($beforeSelected!==1) {
        tenantAuditChange($db,$company,'economy','BANK_ACCOUNT_PRIMARY_SELECTED','economy_bank_accounts',
            $id,(string)$selected['bank_name'],['id'=>$id,'bank_name'=>$selected['bank_name'],'is_active'=>$beforeSelected],
            ['id'=>$id,'bank_name'=>$selected['bank_name'],'is_active'=>1],[],$actor);
    }
    $db->commit();
    echo json_encode(['success'=>true,'message'=>'Primærkonto oppdatert. Kun denne kontoen brukes på nye fakturaer.']);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Manage economy bank account failed: '.$error->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kunne ikke oppdatere bankkontoen. Kontroller at bankkontotabellen er opprettet.']);
}
