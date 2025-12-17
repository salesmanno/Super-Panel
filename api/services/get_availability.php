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
    
    $staffId = $_GET['staff_id'] ?? null;
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    
    $sql = "
        SELECT 
            sa.id,
            sa.staff_id,
            sa.date,
            sa.day_of_week,
            sa.start_time,
            sa.end_time,
            sa.is_working_day,
            sa.availability_type,
            sa.notes,
            ts.first_name,
            ts.last_name
        FROM staff_availability sa
        INNER JOIN tenant_staff ts ON sa.staff_id = ts.id
        WHERE sa.company_id = ?
        AND sa.date BETWEEN ? AND ?
    ";
    
    $params = [$companyId, $startDate, $endDate];
    
    if ($staffId) {
        $sql .= " AND sa.staff_id = ?";
        $params[] = $staffId;
    }
    
    $sql .= " ORDER BY sa.date, ts.first_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'availability' => $availability
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av tilgjengelighet'
    ]);
}
