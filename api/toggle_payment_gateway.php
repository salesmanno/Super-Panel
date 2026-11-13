<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/active_payment_methods.php';
require_once __DIR__ . '/../includes/tenant_audit.php';
require_once __DIR__ . '/../includes/EconomyVippsWebhookSetup.php';
require_once __DIR__ . '/../includes/economy_vipps_access.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];
$data = json_decode(file_get_contents('php://input'), true);

$gatewayId = $data['id'] ?? null;
$gatewayName = $data['gateway_name'] ?? null;
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : null;

if ((!$gatewayId && !$gatewayName) || $isActive === null) {
    echo json_encode(['success' => false, 'message' => 'Mangler id/gateway_name eller is_active']);
    exit;
}

try {
    $db = getDBConnection();
    $actor=tenantAuditActor($db,(string)$company_id);
    $access=$db->prepare("SELECT 1 FROM tenant_admin_modules tam JOIN system_modules sm ON sm.id=tam.module_id WHERE tam.tenant_admin_id=? AND sm.module_key='economy' AND sm.is_active=1");
    $access->execute([$actor['id']]);
    if (!$access->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Du trenger Økonomi-modulen for å aktivere betalingsmetoder.']);
        exit;
    }
    // Resolve the real type from the company's configuration, not the browser's label.
    $vippsQuery = $db->prepare($gatewayId
        ? 'SELECT id,gateway_type FROM economy_configuration_data WHERE id=? AND company_id=?'
        : 'SELECT id,gateway_type FROM economy_configuration_data WHERE company_id=? AND LOWER(gateway_type)=LOWER(?)');
    $vippsQuery->execute($gatewayId ? [$gatewayId,$company_id] : [$company_id,$gatewayName]);
    $resolved = $vippsQuery->fetchAll(PDO::FETCH_ASSOC);
    if (count($resolved) > 1 && strtolower((string)$gatewayName) === 'vipps') {
        throw new RuntimeException('Velg ett konkret Vipps-oppsett.');
    }
    if (isset($resolved[0]) && strtolower((string)$resolved[0]['gateway_type']) === 'vipps') {
        economyVippsCsrf($_SESSION, $data['csrf_token'] ?? null);
        if (!is_bool($data['is_active'] ?? null)) throw new RuntimeException('Ugyldig aktiveringsvalg.');
        $service = new EconomyVippsWebhookSetup($db);
        if ($isActive) {
            $state = $service->activate((string)$company_id, (string)$resolved[0]['id']);
            $success = $state['state'] === 'registered' && $state['is_active'];
            echo json_encode(['success'=>$success,'is_active'=>$state['is_active'],
                'message'=>$success ? 'Vipps aktivert med registrert webhook. Faktisk levering er ikke verifisert.' : $state['message'],
                'webhook'=>$state]);
        } else {
            // Off affects new payment choices only; keep subscriptions and in-flight callbacks.
            $service->deactivate((string)$company_id, (string)$resolved[0]['id']);
            echo json_encode(['success'=>true,'is_active'=>false,'message'=>'Vipps deaktivert. Eksisterende webhook er beholdt.']);
        }
        exit;
    }
    $db->beginTransaction();
    activePaymentMethodsLock($db,(string)$company_id);
    
    if ($gatewayName === 'cash') {
        if (!$isActive) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as active_count 
                FROM economy_configuration_data 
                WHERE company_id = ? AND is_active = 1
            ");
            $stmt->execute([$company_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (($result['active_count'] ?? 0) == 0) {
                $db->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Kan ikke deaktivere kontant uten andre aktive betalingsløsninger'
                ]);
                exit;
            }
        }
        
        $stmt = $db->prepare("UPDATE tenant_companies SET cash_payment = ? WHERE id = ?");
        $stmt->execute([$isActive ? 1 : 0, $company_id]);
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $isActive ? 'Kontant aktivert' : 'Kontant deaktivert',
            'is_active' => $isActive
        ]);
        exit;
    }
    
    if ($gatewayId) {
        $stmt = $db->prepare("
            SELECT id, config_name, gateway_type, is_active,
                   client_id, client_secret_encrypted, subscription_key, 
                   merchant_serial_number, public_key, private_key_encrypted
            FROM economy_configuration_data 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$gatewayId, $company_id]);
    } else {
        $stmt = $db->prepare("
            SELECT id, config_name, gateway_type, is_active,
                   client_id, client_secret_encrypted, subscription_key, 
                   merchant_serial_number, public_key, private_key_encrypted
            FROM economy_configuration_data 
            WHERE company_id = ? AND LOWER(gateway_type) = LOWER(?)
        ");
        $stmt->execute([$company_id, $gatewayName]);
    }
    
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config && strtolower((string)$gatewayName)==='faktura' && $isActive) {
        $newId=(string)$db->query('SELECT UUID()')->fetchColumn();
        $db->prepare("INSERT INTO economy_configuration_data
            (id,company_id,config_name,gateway_type,is_active,test_mode,created_at,updated_at)
            VALUES(?,?,'Faktura','faktura',0,0,NOW(),NOW())")->execute([$newId,$company_id]);
        $config=['id'=>$newId,'config_name'=>'Faktura','gateway_type'=>'faktura','is_active'=>0];
    }
    
    if (!$config) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Betalingsløsning ikke funnet']);
        exit;
    }
    
    $gatewayType = strtolower($config['gateway_type']);
    $missingFields = [];
    
    if ($isActive) {
        switch ($gatewayType) {
            case 'vipps':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                if (empty($config['merchant_serial_number'])) $missingFields[] = 'merchant_serial_number';
                if (empty($config['subscription_key'])) $missingFields[] = 'subscription_key';
                break;
            case 'stripe':
                if (empty($config['public_key'])) $missingFields[] = 'public_key';
                if (empty($config['private_key_encrypted'])) $missingFields[] = 'private_key';
                break;
            case 'paypal':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                break;
            case 'bank':
            case 'faktura':
                $baCheck = $db->prepare("SELECT COUNT(*) as cnt FROM economy_bank_accounts WHERE company_id = ? AND is_active = 1 AND account_number IS NOT NULL AND account_number <> ''");
                $baCheck->execute([$company_id]);
                $baResult = $baCheck->fetch(PDO::FETCH_ASSOC);
                if ((int)($baResult['cnt'] ?? 0) !== 1) {
                    $missingFields[] = 'nøyaktig én valgt primærkonto';
                }
                break;
        }
        
        if (!empty($missingFields)) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Kan ikke aktivere - mangler konfigurasjon: ' . implode(', ', $missingFields)
            ]);
            exit;
        }
    }
    
    $stmt = $db->prepare("
        UPDATE economy_configuration_data 
        SET is_active = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$isActive ? 1 : 0, $config['id']]);
    $db->commit();
    
    $displayName = $config['config_name'] ?: ucfirst($gatewayType);
    echo json_encode([
        'success' => true,
        'message' => $isActive ? "$displayName aktivert" : "$displayName deaktivert",
        'is_active' => $isActive
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    $safeMessage = $e instanceof PDOException || $e instanceof JsonException
        ? 'Kunne ikke oppdatere betalingsoppsettet. Kontroller databaseoppsettet.'
        : ($e instanceof SalesVippsFailure ? 'Vipps kunne ikke nås sikkert. Oppdater status før nytt forsøk.' : $e->getMessage());
    echo json_encode(['success' => false, 'message' => $safeMessage]);
}
