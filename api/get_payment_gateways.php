<?php
session_start();
session_write_close();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun GET tillatt']);
    exit;
}

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

$gatewayDisplayNames = [
    'vipps'    => 'Vipps',
    'stripe'   => 'Stripe',
    'paypal'   => 'PayPal',
    'klarna'   => 'Klarna',
    'bank'     => 'Bankoverføring',
    'faktura'  => 'Faktura',
    'cash'     => 'Kontant'
];

$gatewayIcons = [
    'vipps'   => 'phone',
    'stripe'  => 'credit-card',
    'paypal'  => 'paypal',
    'klarna'  => 'bag-check',
    'bank'    => 'bank',
    'faktura' => 'receipt-cutoff',
    'cash'    => 'cash-stack'
];

try {
    $db = getDBConnection();

    // Fetch configurations — try with fee columns first, fall back without them
    try {
        $stmt = $db->prepare("
            SELECT 
                ecd.id,
                ecd.config_name,
                ecd.gateway_type,
                ecd.client_id,
                ecd.client_secret_encrypted,
                ecd.subscription_key,
                ecd.merchant_serial_number,
                ecd.public_key,
                ecd.private_key_encrypted,
                ecd.webhook_secret,
                ecd.is_active,
                ecd.test_mode,
                ecd.fee_fixed,
                ecd.fee_percent
            FROM economy_configuration_data ecd
            WHERE ecd.company_id = ?
            ORDER BY ecd.config_name
        ");
        $stmt->execute([$company_id]);
        $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // fee_fixed/fee_percent columns may not exist yet — retry without them
        $stmt = $db->prepare("
            SELECT 
                ecd.id,
                ecd.config_name,
                ecd.gateway_type,
                ecd.client_id,
                ecd.client_secret_encrypted,
                ecd.subscription_key,
                ecd.merchant_serial_number,
                ecd.public_key,
                ecd.private_key_encrypted,
                ecd.webhook_secret,
                ecd.is_active,
                ecd.test_mode,
                0 AS fee_fixed,
                0 AS fee_percent
            FROM economy_configuration_data ecd
            WHERE ecd.company_id = ?
            ORDER BY ecd.config_name
        ");
        $stmt->execute([$company_id]);
        $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $stmt = $db->prepare("SELECT cash_payment FROM tenant_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    $cashEnabled = $company ? (bool)($company['cash_payment'] ?? true) : true;

    // economy_bank_accounts may not exist yet — handle gracefully
    $hasActiveBankAccount = false;
    $bankAccounts = [];
    try {
        $bankStmt = $db->prepare("SELECT COUNT(*) as count FROM economy_bank_accounts WHERE company_id = ? AND is_active = 1 AND account_number IS NOT NULL");
        $bankStmt->execute([$company_id]);
        $bankResult = $bankStmt->fetch(PDO::FETCH_ASSOC);
        $hasActiveBankAccount = ($bankResult['count'] ?? 0) > 0;

        $baStmt = $db->prepare("SELECT id, bank_name, account_number FROM economy_bank_accounts WHERE company_id = ? AND is_active = 1 ORDER BY bank_name");
        $baStmt->execute([$company_id]);
        $bankAccounts = $baStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("economy_bank_accounts not available: " . $e->getMessage());
    }

    // Auto-create 'faktura' entry in economy_configuration_data if not exists
    try {
        $fkCheck = $db->prepare("SELECT id FROM economy_configuration_data WHERE company_id = ? AND LOWER(gateway_type) = 'faktura' LIMIT 1");
        $fkCheck->execute([$company_id]);
        if (!$fkCheck->fetch()) {
            $fkId = bin2hex(random_bytes(18));
            $fkInsert = $db->prepare("
                INSERT INTO economy_configuration_data (id, company_id, config_name, gateway_type, is_active, test_mode, created_at, updated_at)
                VALUES (?, ?, 'Faktura', 'faktura', 0, 0, NOW(), NOW())
            ");
            $fkInsert->execute([$fkId, $company_id]);
            // Re-fetch to include new faktura row
            $stmt = $db->prepare("
                SELECT id, config_name, gateway_type, client_id, client_secret_encrypted,
                       subscription_key, merchant_serial_number, public_key, private_key_encrypted,
                       webhook_secret, is_active, test_mode,
                       IFNULL(fee_fixed, 0) AS fee_fixed, IFNULL(fee_percent, 0) AS fee_percent
                FROM economy_configuration_data
                WHERE company_id = ?
                ORDER BY config_name
            ");
            $stmt->execute([$company_id]);
            $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Faktura auto-create error: " . $e->getMessage());
    }

    $result = [];
    
    $result[] = [
        'gateway_name' => 'cash',
        'gateway_type' => 'cash',
        'display_name' => 'Kontant',
        'icon' => 'cash-stack',
        'is_configured' => true,
        'is_active' => $cashEnabled,
        'test_mode' => false,
        'missing_fields' => []
    ];
    
    foreach ($configurations as $config) {
        $gatewayType = strtolower($config['gateway_type']);
        $missingFields = [];
        $isConfigured = false;
        
        switch ($gatewayType) {
            case 'vipps':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                if (empty($config['merchant_serial_number'])) $missingFields[] = 'merchant_serial_number';
                if (empty($config['subscription_key'])) $missingFields[] = 'subscription_key';
                $isConfigured = empty($missingFields);
                break;
            case 'stripe':
                if (empty($config['public_key'])) $missingFields[] = 'public_key';
                if (empty($config['private_key_encrypted'])) $missingFields[] = 'private_key';
                $isConfigured = empty($missingFields);
                break;
            case 'paypal':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                $isConfigured = empty($missingFields);
                break;
            case 'bank':
                $isConfigured = $hasActiveBankAccount;
                if (!$isConfigured) {
                    $missingFields[] = 'bankkonto (opprett i Økonomi > Bankkontoer)';
                }
                break;
            case 'faktura':
                $isConfigured = $hasActiveBankAccount;
                if (!$isConfigured) {
                    $missingFields[] = 'bankkonto for faktura (opprett i Økonomi > Bankkontoer)';
                }
                break;
            case 'klarna':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                $isConfigured = empty($missingFields);
                break;
            default:
                $hasCredentials = !empty($config['client_id']) || !empty($config['public_key']);
                $isConfigured = $hasCredentials;
                if (!$hasCredentials) {
                    $missingFields[] = 'credentials';
                }
        }
        
        $entry = [
            'id'            => $config['id'],
            'gateway_name'  => $gatewayType,
            'gateway_type'  => $gatewayType,
            'display_name'  => $config['config_name'] ?: ($gatewayDisplayNames[$gatewayType] ?? ucfirst($gatewayType)),
            'icon'          => $gatewayIcons[$gatewayType] ?? 'wallet2',
            'is_configured' => $isConfigured,
            'is_active'     => (bool)$config['is_active'],
            'test_mode'     => (bool)$config['test_mode'],
            'missing_fields'=> $missingFields,
            'fee_fixed'     => (float)($config['fee_fixed']   ?? 0),
            'fee_percent'   => (float)($config['fee_percent'] ?? 0)
        ];
        if ($gatewayType === 'faktura') {
            $entry['bank_accounts'] = $bankAccounts;
        }
        $result[] = $entry;
    }
    
    echo json_encode([
        'success' => true,
        'gateways' => $result
    ]);
    
} catch (Exception $e) {
    error_log("Get payment gateways error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
