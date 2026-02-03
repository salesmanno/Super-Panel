<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];
$object_id = $_GET['object_id'] ?? null;

if (!$object_id) {
    echo json_encode(['success' => false, 'message' => 'object_id er påkrevd']);
    exit;
}

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT id, object_name, is_available, is_active
        FROM rental_objects 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$object_id, $company_id]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$object) {
        echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
        exit;
    }
    
    $currentReservation = null;
    $nextReservation = null;
    
    $today = date('Y-m-d');
    
    $currentStmt = $db->prepare("
        SELECT rb.*, tc.first_name, tc.last_name, tc.email, tc.phone
        FROM rental_bookings rb
        LEFT JOIN tenant_customers tc ON rb.customer_id = tc.id
        WHERE rb.object_id = ? 
          AND rb.company_id = ?
          AND rb.start_date <= ?
          AND rb.end_date >= ?
          AND rb.status IN ('confirmed', 'active')
        ORDER BY rb.start_date ASC
        LIMIT 1
    ");
    $currentStmt->execute([$object_id, $company_id, $today, $today]);
    $currentReservation = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    $nextStmt = $db->prepare("
        SELECT rb.*, tc.first_name, tc.last_name, tc.email, tc.phone
        FROM rental_bookings rb
        LEFT JOIN tenant_customers tc ON rb.customer_id = tc.id
        WHERE rb.object_id = ? 
          AND rb.company_id = ?
          AND rb.start_date > ?
          AND rb.status IN ('confirmed', 'pending')
        ORDER BY rb.start_date ASC
        LIMIT 1
    ");
    $nextStmt->execute([$object_id, $company_id, $today]);
    $nextReservation = $nextStmt->fetch(PDO::FETCH_ASSOC);
    
    $upcomingStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM rental_bookings
        WHERE object_id = ? 
          AND company_id = ?
          AND start_date >= ?
          AND status IN ('confirmed', 'pending')
    ");
    $upcomingStmt->execute([$object_id, $company_id, $today]);
    $upcomingCount = $upcomingStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $availabilityStatus = 'available';
    $availabilityText = 'Ledig nå';
    
    if (!$object['is_active']) {
        $availabilityStatus = 'inactive';
        $availabilityText = 'Inaktiv';
    } elseif (!$object['is_available']) {
        $availabilityStatus = 'unavailable';
        $availabilityText = 'Ikke tilgjengelig';
    } elseif ($currentReservation) {
        $availabilityStatus = 'rented';
        $availabilityText = 'Utleid';
    }
    
    echo json_encode([
        'success' => true,
        'availability' => [
            'status' => $availabilityStatus,
            'text' => $availabilityText,
            'is_available' => $object['is_available'] && !$currentReservation,
            'current_reservation' => $currentReservation ? [
                'id' => $currentReservation['id'],
                'customer_name' => trim($currentReservation['first_name'] . ' ' . $currentReservation['last_name']),
                'customer_email' => $currentReservation['email'],
                'customer_phone' => $currentReservation['phone'],
                'start_date' => $currentReservation['start_date'],
                'end_date' => $currentReservation['end_date'],
                'status' => $currentReservation['status']
            ] : null,
            'next_reservation' => $nextReservation ? [
                'id' => $nextReservation['id'],
                'customer_name' => trim($nextReservation['first_name'] . ' ' . $nextReservation['last_name']),
                'customer_email' => $nextReservation['email'],
                'customer_phone' => $nextReservation['phone'],
                'start_date' => $nextReservation['start_date'],
                'end_date' => $nextReservation['end_date'],
                'status' => $nextReservation['status']
            ] : null,
            'upcoming_count' => intval($upcomingCount)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feil: ' . $e->getMessage()]);
}
