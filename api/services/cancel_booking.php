<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ugyldig metode']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $bookingId = $data['booking_id'];
    
    $checkStmt = $db->prepare("SELECT id, status FROM service_bookings WHERE id = ? AND company_id = ?");
    $checkStmt->execute([$bookingId, $companyId]);
    $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking ikke funnet']);
        exit;
    }
    
    if ($booking['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Bookingen er allerede avbestilt']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE service_bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND company_id = ?");
    $stmt->execute([$bookingId, $companyId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking avbestilt'
    ]);
    
} catch (Exception $e) {
    error_log("Error cancelling booking: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved avbestilling: ' . $e->getMessage()
    ]);
}
