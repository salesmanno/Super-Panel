<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['start_date']) || empty($data['end_date']) || empty($data['staff_ids']) || empty($data['duration'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felter']);
        exit;
    }
    
    $db = getDBConnection();
    $startDate = $data['start_date'];
    $endDate = $data['end_date'];
    $staffIds = $data['staff_ids'];
    $duration = (int)$data['duration'];
    
    // Get all existing bookings for the period and staff members
    $placeholders = str_repeat('?,', count($staffIds) - 1) . '?';
    $stmt = $db->prepare("
        SELECT date_from, time_from, time_to, assigned_staff_id
        FROM service_bookings
        WHERE company_id = ?
        AND assigned_staff_id IN ($placeholders)
        AND date_from >= ?
        AND date_from <= ?
        AND status NOT IN ('cancelled', 'completed')
        ORDER BY date_from, time_from
    ");
    
    $params = array_merge([$_SESSION['company_id']], $staffIds, [$startDate, $endDate]);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get operating hours from request or use defaults (06:00-22:00, all days)
    $startHour = isset($data['start_hour']) ? (int)$data['start_hour'] : 6;
    $endHour = isset($data['end_hour']) ? (int)$data['end_hour'] : 22;
    $includeWeekends = isset($data['include_weekends']) ? (bool)$data['include_weekends'] : true;
    
    // Generate all time slots (30-min intervals)
    $timeSlots = [];
    for ($hour = $startHour; $hour < $endHour; $hour++) {
        foreach ([0, 30] as $minute) {
            $timeSlots[] = sprintf('%02d:%02d:00', $hour, $minute);
        }
    }
    
    // Generate availability grid
    $availability = [];
    
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    
    while ($currentDate <= $endDateObj) {
        $dateStr = $currentDate->format('Y-m-d');
        $dayOfWeek = (int)$currentDate->format('w');
        
        // Skip weekends if configured
        if (!$includeWeekends && ($dayOfWeek === 0 || $dayOfWeek === 6)) {
            $currentDate->add(new DateInterval('P1D'));
            continue;
        }
        
        $availability[$dateStr] = [];
        
        foreach ($staffIds as $staffId) {
            $availability[$dateStr][$staffId] = [];
            
            foreach ($timeSlots as $time) {
                $isAvailable = true;
                
                // Calculate end time for this slot
                $slotStart = new DateTime($dateStr . ' ' . $time);
                $slotEnd = clone $slotStart;
                $slotEnd->add(new DateInterval('PT' . $duration . 'M'));
                
                // Check against all bookings for this staff on this date
                foreach ($bookings as $booking) {
                    if ($booking['date_from'] === $dateStr && $booking['assigned_staff_id'] == $staffId) {
                        $bookingStart = new DateTime($dateStr . ' ' . $booking['time_from']);
                        $bookingEnd = new DateTime($dateStr . ' ' . $booking['time_to']);
                        
                        // Check for overlap
                        if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                            $isAvailable = false;
                            break;
                        }
                    }
                }
                
                $availability[$dateStr][$staffId][$time] = $isAvailable;
            }
        }
        
        $currentDate->add(new DateInterval('P1D'));
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $availability,
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    
} catch (Exception $e) {
    error_log("Error getting week availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av tilgjengelighet: ' . $e->getMessage()
    ]);
}
