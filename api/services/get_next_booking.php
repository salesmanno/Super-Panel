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
    
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    $sql = "
        SELECT 
            b.id,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.status,
            b.payment_status,
            b.notes,
            rs.service_name,
            rs.price,
            CONCAT(ts.first_name, ' ', ts.last_name) as staff_name,
            CONCAT(tc.first_name, ' ', tc.last_name) as customer_name
        FROM service_bookings b
        LEFT JOIN reservation_services rs ON b.service_id = rs.id
        LEFT JOIN tenant_staff ts ON b.staff_id = ts.id
        LEFT JOIN tenant_customers tc ON b.customer_id = tc.id
        WHERE b.company_id = ?
        AND (
            (b.booking_date = ? AND b.start_time > ?)
            OR b.booking_date > ?
        )
        AND b.status NOT IN ('cancelled', 'completed')
        ORDER BY b.booking_date ASC, b.start_time ASC
        LIMIT 1
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId, $today, $currentTime, $today]);
    $nextBooking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($nextBooking) {
        $bookingDateTime = new DateTime($nextBooking['booking_date'] . ' ' . $nextBooking['start_time']);
        $nowDateTime = new DateTime();
        $interval = $nowDateTime->diff($bookingDateTime);
        
        $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        $nextBooking['time_until'] = [
            'hours' => $hours,
            'minutes' => $minutes,
            'total_minutes' => $totalMinutes
        ];
        
        $nextBooking['display_customer'] = $nextBooking['customer_name'] ?: 'Ukjent kunde';
        
        $isPaid = in_array(strtolower($nextBooking['payment_status'] ?? ''), ['paid', 'completed', 'betalt']);
        $nextBooking['is_paid'] = $isPaid;
    }
    
    echo json_encode([
        'success' => true,
        'has_next' => $nextBooking ? true : false,
        'next_booking' => $nextBooking
    ]);
    
} catch (Exception $e) {
    error_log("Error getting next booking: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av neste booking: ' . $e->getMessage()
    ]);
}
