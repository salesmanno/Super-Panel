<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $date = $_GET['date'] ?? date('Y-m-d');
    $staffId = $_GET['staff_id'] ?? null;
    $serviceId = $_GET['service_id'] ?? null;
    
    $sql = "
        SELECT 
            b.id,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.status,
            b.customer_name,
            b.customer_email,
            b.customer_phone,
            b.notes,
            rs.service_name,
            rs.price as service_price,
            rs.duration_minutes,
            CONCAT(ts.first_name, ' ', ts.last_name) as staff_name
        FROM service_bookings b
        LEFT JOIN reservation_services rs ON b.service_id = rs.id
        LEFT JOIN tenant_staff ts ON b.staff_id = ts.id
        WHERE b.company_id = ?
        AND b.booking_date = ?
    ";
    
    $params = [$companyId, $date];
    
    if ($staffId) {
        $sql .= " AND b.staff_id = ?";
        $params[] = $staffId;
    }
    
    if ($serviceId) {
        $sql .= " AND b.service_id = ?";
        $params[] = $serviceId;
    }
    
    $sql .= " ORDER BY b.start_time ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'date' => $date,
        'count' => count($bookings)
    ]);
    
} catch (Exception $e) {
    error_log("Error getting schedule: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av timeoversikt: ' . $e->getMessage()
    ]);
}
