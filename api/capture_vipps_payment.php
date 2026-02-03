<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];
$input = json_decode(file_get_contents('php://input'), true);
$checkin_id = $input['checkin_id'] ?? null;

if (!$checkin_id) {
    echo json_encode(['success' => false, 'message' => 'checkin_id er påkrevd']);
    exit;
}

function getVippsAccessToken($config, $baseUrl) {
    $authUrl = $baseUrl . '/accesstoken/get';
    
    $headers = [
        'Content-Type: application/json',
        'client_id: ' . $config['client_id'],
        'client_secret: ' . $config['client_secret_encrypted'],
        'Ocp-Apim-Subscription-Key: ' . $config['subscription_key'],
        'Merchant-Serial-Number: ' . $config['merchant_serial_number']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    return null;
}

function captureVippsPayment($orderId, $amount, $accessToken, $subscriptionKey, $msn, $baseUrl) {
    $captureUrl = $baseUrl . '/epayment/v1/payments/' . urlencode($orderId) . '/capture';
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
        'Merchant-Serial-Number: ' . $msn,
        'Idempotency-Key: ' . uniqid('cap_', true),
        'Vipps-System-Name: SalesmanSuperportal',
        'Vipps-System-Version: 1.0.0'
    ];
    
    $body = json_encode([
        'modificationAmount' => [
            'currency' => 'NOK',
            'value' => (int)($amount * 100)
        ]
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $captureUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 202 || $httpCode === 200,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            client_id,
            client_secret_encrypted,
            subscription_key,
            merchant_serial_number,
            test_mode
        FROM economy_configuration_data 
        WHERE company_id = ? AND gateway_type = 'vipps' AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$company_id]);
    $vippsConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vippsConfig) {
        echo json_encode(['success' => false, 'message' => 'Vipps er ikke konfigurert']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT 
            ec.id,
            ec.registration_id,
            ec.payment_reference,
            ec.payment_state,
            ec.ticket_price,
            c.first_name,
            c.last_name
        FROM events_checkin ec
        INNER JOIN events_eventdata e ON ec.event_id = e.id AND e.company_id = ?
        INNER JOIN tenant_customers c ON ec.customer_id = c.id
        WHERE ec.id = ? AND ec.payment_method = 'vipps'
    ");
    $stmt->execute([$company_id, $checkin_id]);
    $checkin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$checkin) {
        echo json_encode(['success' => false, 'message' => 'Vipps-betaling ikke funnet']);
        exit;
    }
    
    if ($checkin['payment_state'] === 'captured') {
        echo json_encode(['success' => false, 'message' => 'Betalingen er allerede fanget']);
        exit;
    }
    
    if ($checkin['payment_state'] !== 'authorized') {
        echo json_encode(['success' => false, 'message' => 'Betalingen er ikke reservert (status: ' . ($checkin['payment_state'] ?: 'ukjent') . ')']);
        exit;
    }
    
    $orderId = $checkin['payment_reference'] ?: $checkin['registration_id'];
    $amount = floatval($checkin['ticket_price']);
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig beløp']);
        exit;
    }
    
    $baseUrl = $vippsConfig['test_mode'] 
        ? 'https://apitest.vipps.no' 
        : 'https://api.vipps.no';
    
    $accessToken = getVippsAccessToken($vippsConfig, $baseUrl);
    
    if (!$accessToken) {
        echo json_encode(['success' => false, 'message' => 'Kunne ikke autentisere med Vipps']);
        exit;
    }
    
    $captureResult = captureVippsPayment(
        $orderId, 
        $amount, 
        $accessToken, 
        $vippsConfig['subscription_key'],
        $vippsConfig['merchant_serial_number'],
        $baseUrl
    );
    
    if ($captureResult['success']) {
        $updateStmt = $db->prepare("
            UPDATE events_checkin ec
            INNER JOIN events_eventdata e ON ec.event_id = e.id AND e.company_id = ?
            SET ec.payment_state = 'captured',
                ec.payment_status = 'paid',
                ec.payment_checked_at = NOW(),
                ec.updated_at = NOW()
            WHERE ec.id = ?
        ");
        $updateStmt->execute([$company_id, $checkin_id]);
        
        $customerName = trim($checkin['first_name'] . ' ' . $checkin['last_name']);
        
        echo json_encode([
            'success' => true,
            'message' => "Betaling fanget for {$customerName}",
            'amount' => $amount,
            'customer_name' => $customerName
        ]);
    } else {
        $errorMsg = 'Kunne ikke fange betalingen';
        if ($captureResult['response']) {
            $responseData = json_decode($captureResult['response'], true);
            if (isset($responseData['title'])) {
                $errorMsg = $responseData['title'];
            } elseif (isset($responseData['message'])) {
                $errorMsg = $responseData['message'];
            }
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
            'http_code' => $captureResult['http_code'],
            'details' => $captureResult['response']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
