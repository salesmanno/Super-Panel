<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$staffId = isset($_GET['staff_id']) ? $_GET['staff_id'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Support both days-from-now and explicit date range
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$days = isset($_GET['days']) ? (int)$_GET['days'] : 14;

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $companyId = $_SESSION['company_id'];
    
    // Build WHERE clause
    $whereConditions = ["r.company_id = ?"];
    $params = [$companyId];
    
    // Date range filter
    if ($startDate && $endDate) {
        // Explicit date range
        $whereConditions[] = "DATE(r.start_datetime) >= ?";
        $whereConditions[] = "DATE(r.start_datetime) <= ?";
        $params[] = $startDate;
        $params[] = $endDate;
    } else {
        // Days from now (default)
        $whereConditions[] = "r.start_datetime >= NOW()";
        $whereConditions[] = "r.start_datetime <= DATE_ADD(NOW(), INTERVAL ? DAY)";
        $params[] = $days;
    }
    
    // Staff filter
    if (!empty($staffId)) {
        $whereConditions[] = "r.staff_id = ?";
        $params[] = $staffId;
    }
    
    // Status filter
    if (!empty($statusFilter)) {
        $whereConditions[] = "r.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get reservations with joined data
    $sql = "
        SELECT 
            r.*,
            CONCAT(s.first_name, ' ', s.last_name) as staff_name,
            'Kunde' as customer_name
        FROM reservations_reservations r
        LEFT JOIN tenant_staff s ON r.staff_id = s.id
        WHERE $whereClause
        ORDER BY r.start_datetime ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $stats = [
        'total' => count($reservations),
        'pending' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'completed' => 0,
        'total_value' => 0
    ];
    
    foreach ($reservations as $res) {
        $status = $res['status'] ?? 'pending';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        $stats['total_value'] += floatval($res['total_amount'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Get reservations overview error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Serverfeil: ' . $e->getMessage()]);
}
