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
$event_id = $input['event_id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'event_id er påkrevd']);
    exit;
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
    
    $eventStmt = $db->prepare("SELECT id FROM events_eventdata WHERE id = ? AND company_id = ?");
    $eventStmt->execute([$event_id, $company_id]);
    if (!$eventStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arrangementet ble ikke funnet']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT 
            ec.id,
            ec.registration_id,
            ec.customer_id,
            ec.payment_status,
            ec.payment_reference,
            c.first_name,
            c.last_name
        FROM events_checkin ec
        INNER JOIN events_eventdata e ON ec.event_id = e.id AND e.company_id = ?
        INNER JOIN tenant_customers c ON ec.customer_id = c.id
        WHERE ec.event_id = ? 
          AND ec.payment_method = 'vipps' 
          AND ec.payment_authorization IS NULL
    ");
    $stmt->execute([$company_id, $event_id]);
    $vippsPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($vippsPayments)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Ingen ventende Vipps-betalinger funnet',
            'updated' => 0,
            'results' => []
        ]);
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
    
    $results = [];
    $updatedCount = 0;
    
    foreach ($vippsPayments as $payment) {
        $orderId = $payment['payment_reference'] ?: $payment['registration_id'];
        
        $paymentStatus = checkVippsPaymentStatus(
            $orderId, 
            $accessToken, 
            $vippsConfig['subscription_key'],
            $vippsConfig['merchant_serial_number'],
            $baseUrl
        );
        
        $customerName = trim($payment['first_name'] . ' ' . $payment['last_name']);
        
        if ($paymentStatus) {
            $state = $paymentStatus['state'] ?? 'unknown';
            $authCode = $paymentStatus['authorization_code'] ?? null;
            
            $results[] = [
                'customer_name' => $customerName,
                'registration_id' => $orderId,
                'vipps_state' => $state,
                'authorization_code' => $authCode,
                'amount' => $paymentStatus['amount'] ?? null
            ];
            
            $newPaymentStatus = in_array($state, ['AUTHORIZED', 'CAPTURED']) ? 'paid' : 'pending';
            $normalizedState = strtolower($state);
            
            $updateStmt = $db->prepare("
                UPDATE events_checkin
                SET payment_reference = COALESCE(payment_reference, ?),
                    payment_authorization = ?,
                    payment_state = ?,
                    payment_checked_at = NOW(),
                    payment_status = ?,
                    updated_at = NOW()
                WHERE id = ? AND EXISTS (
                    SELECT 1 FROM events_eventdata e WHERE e.id = events_checkin.event_id AND e.company_id = ?
                )
            ");
            $updateStmt->execute([
                $orderId,
                $authCode,
                $normalizedState,
                $newPaymentStatus,
                $payment['id'],
                $company_id
            ]);
            
            if ($newPaymentStatus === 'paid') {
                $updatedCount++;
            }
        } else {
            $results[] = [
                'customer_name' => $customerName,
                'registration_id' => $orderId,
                'vipps_state' => 'error',
                'error' => 'Kunne ikke hente status'
            ];
        }
    }
    
    $totalChecked = count($vippsPayments);
    echo json_encode([
        'success' => true,
        'message' => "Sjekket {$totalChecked} Vipps-betalinger, {$updatedCount} oppdatert til betalt",
        'updated' => $updatedCount,
        'total_checked' => $totalChecked,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Check Vipps payments error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}

function getVippsAccessToken($config, $baseUrl) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/accesstoken/get',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'client_id: ' . $config['client_id'],
            'client_secret: ' . $config['client_secret_encrypted'],
            'Ocp-Apim-Subscription-Key: ' . $config['subscription_key'],
            'Merchant-Serial-Number: ' . $config['merchant_serial_number']
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    error_log("Vipps access token error: HTTP $httpCode - $response");
    return null;
}

function checkVippsPaymentStatus($orderId, $accessToken, $subscriptionKey, $msn, $baseUrl) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/epayment/v1/payments/' . $orderId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
            'Merchant-Serial-Number: ' . $msn
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return [
            'state' => $data['state'] ?? 'unknown',
            'authorization_code' => $data['pspReference'] ?? null,
            'amount' => isset($data['amount']) ? $data['amount']['value'] / 100 : null
        ];
    }
    
    error_log("Vipps payment status error for $orderId: HTTP $httpCode - $response");
    return null;
}
