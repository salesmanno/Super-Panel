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
            b.service_id,
            b.staff_id,
            b.customer_id,
            rs.service_name,
            rs.price,
            rs.duration_minutes,
            CONCAT(ts.first_name, ' ', ts.last_name) as staff_name,
            tc.first_name as customer_first_name,
            tc.last_name as customer_last_name,
            tc.email as customer_email,
            tc.phone as customer_phone
        FROM service_bookings b
        LEFT JOIN reservation_services rs ON b.service_id = rs.id
        LEFT JOIN tenant_staff ts ON b.staff_id = ts.id
        LEFT JOIN tenant_customers tc ON b.customer_id = tc.id
        WHERE b.company_id = ?
        AND b.booking_date IS NOT NULL
        AND b.start_time IS NOT NULL
        AND (
            (b.booking_date = ? AND b.start_time > ?)
            OR b.booking_date > ?
        )
        AND b.status NOT IN ('cancelled', 'completed')
        ORDER BY b.booking_date ASC, b.start_time ASC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId, $today, $currentTime, $today]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $nowDateTime = new DateTime();
    $validBookings = [];
    
    foreach ($bookings as $booking) {
        if (empty($booking['booking_date']) || empty($booking['start_time'])) {
            continue;
        }
        
        try {
            $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['start_time']);
            $interval = $nowDateTime->diff($bookingDateTime);
            
            $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            
            $booking['time_until'] = [
                'hours' => $hours,
                'minutes' => $minutes,
                'total_minutes' => $totalMinutes
            ];
            
            $isPaid = in_array(strtolower($booking['payment_status'] ?? ''), ['paid', 'completed', 'betalt']);
            $booking['is_paid'] = $isPaid;
            
            $validBookings[] = $booking;
        } catch (Exception $e) {
            continue;
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($validBookings),
        'bookings' => $validBookings
    ]);
    
} catch (Exception $e) {
    error_log("Error getting upcoming bookings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av bookinger: ' . $e->getMessage()
    ]);
}
