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
    
    if (empty($data['duration']) || empty($data['assigned_staff_ids'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felter']);
        exit;
    }
    
    $db = getDBConnection();
    $daysAhead = $data['days_ahead'] ?? 14;
    $duration = $data['duration'];
    $staffIds = $data['assigned_staff_ids'];
    
    // Get staff names
    $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
    $stmt = $db->prepare("
        SELECT id, first_name, last_name
        FROM tenant_staff
        WHERE company_id = ? AND id IN ($placeholders)
    ");
    $stmt->execute(array_merge([$_SESSION['company_id']], $staffIds));
    $staffMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMap[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
    }
    
    // Working hours: 09:00 - 17:00
    $workStart = '09:00';
    $workEnd = '17:00';
    
    // Search for next available slot
    for ($dayOffset = 0; $dayOffset < $daysAhead; $dayOffset++) {
        $date = date('Y-m-d', strtotime("+$dayOffset days"));
        
        // Skip weekends (optional - can be removed if needed)
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek >= 6) continue; // Skip Saturday (6) and Sunday (7)
        
        // Try each 30-minute slot during working hours
        $currentTime = new DateTime($date . ' ' . $workStart);
        $endOfDay = new DateTime($date . ' ' . $workEnd);
        
        while ($currentTime < $endOfDay) {
            $timeSlot = $currentTime->format('H:i');
            
            // Calculate end time for this slot
            $slotEnd = clone $currentTime;
            $slotEnd->add(new DateInterval('PT' . $duration . 'M'));
            
            // Skip if slot extends beyond working hours
            if ($slotEnd > $endOfDay) {
                break;
            }
            
            // Check each staff member for availability
            foreach ($staffIds as $staffId) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count
                    FROM service_bookings
                    WHERE company_id = ?
                    AND assigned_staff_id = ?
                    AND date_from = ?
                    AND status NOT IN ('cancelled', 'completed')
                    AND (
                        (time_from < ? AND time_to > ?) OR
                        (time_from >= ? AND time_from < ?) OR
                        (time_to > ? AND time_to <= ?)
                    )
                ");
                
                $endTimeStr = $slotEnd->format('H:i:s');
                $startTimeStr = $currentTime->format('H:i:s');
                
                $stmt->execute([
                    $_SESSION['company_id'],
                    $staffId,
                    $date,
                    $endTimeStr, $startTimeStr,
                    $startTimeStr, $endTimeStr,
                    $startTimeStr, $endTimeStr
                ]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] == 0) {
                    // Found available slot!
                    echo json_encode([
                        'success' => true,
                        'slot' => [
                            'date' => $date,
                            'time' => $timeSlot,
                            'staff_id' => $staffId,
                            'staff_name' => $staffMap[$staffId] ?? $staffId
                        ]
                    ]);
                    exit;
                }
            }
            
            // Move to next 30-minute slot
            $currentTime->add(new DateInterval('PT30M'));
        }
    }
    
    // No available slots found
    echo json_encode([
        'success' => true,
        'slot' => null,
        'message' => 'Ingen ledige tider funnet de neste ' . $daysAhead . ' dagene'
    ]);
    
} catch (Exception $e) {
    error_log("Error finding first available: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved søk: ' . $e->getMessage()
    ]);
}
