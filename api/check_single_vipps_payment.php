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

function checkVippsPaymentStatus($orderId, $accessToken, $subscriptionKey, $msn, $baseUrl) {
    $statusUrl = $baseUrl . '/epayment/v1/payments/' . urlencode($orderId);
    
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
        'Merchant-Serial-Number: ' . $msn,
        'Vipps-System-Name: SalesmanSuperportal',
        'Vipps-System-Version: 1.0.0'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $statusUrl);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return [
            'state' => $data['state'] ?? null,
            'authorization_code' => $data['pspReference'] ?? null,
            'amount' => isset($data['amount']['value']) ? $data['amount']['value'] / 100 : null
        ];
    }
    
    return null;
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
    
    $orderId = $checkin['payment_reference'] ?: $checkin['registration_id'];
    
    $baseUrl = $vippsConfig['test_mode'] 
        ? 'https://apitest.vipps.no' 
        : 'https://api.vipps.no';
    
    $accessToken = getVippsAccessToken($vippsConfig, $baseUrl);
    
    if (!$accessToken) {
        echo json_encode(['success' => false, 'message' => 'Kunne ikke autentisere med Vipps']);
        exit;
    }
    
    $paymentStatus = checkVippsPaymentStatus(
        $orderId, 
        $accessToken, 
        $vippsConfig['subscription_key'],
        $vippsConfig['merchant_serial_number'],
        $baseUrl
    );
    
    if ($paymentStatus) {
        $state = $paymentStatus['state'] ?? 'unknown';
        $authCode = $paymentStatus['authorization_code'] ?? null;
        $normalizedState = strtolower($state);
        
        $newPaymentStatus = ($state === 'CAPTURED') ? 'paid' : 'pending';
        
        $updateStmt = $db->prepare("
            UPDATE events_checkin ec
            INNER JOIN events_eventdata e ON ec.event_id = e.id AND e.company_id = ?
            SET ec.payment_reference = COALESCE(ec.payment_reference, ?),
                ec.payment_authorization = COALESCE(ec.payment_authorization, ?),
                ec.payment_state = ?,
                ec.payment_checked_at = NOW(),
                ec.payment_status = ?,
                ec.updated_at = NOW()
            WHERE ec.id = ?
        ");
        $updateStmt->execute([
            $company_id, 
            $orderId,
            $authCode,
            $normalizedState,
            $newPaymentStatus,
            $checkin_id
        ]);
        
        $customerName = trim($checkin['first_name'] . ' ' . $checkin['last_name']);
        
        echo json_encode([
            'success' => true,
            'state' => $state,
            'authorization_code' => $authCode,
            'amount' => $paymentStatus['amount'],
            'customer_name' => $customerName
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke hente status fra Vipps. Sjekk at ordreID er korrekt.',
            'order_id' => $orderId
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
