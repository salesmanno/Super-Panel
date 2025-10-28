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

// Check if unlocked
if (!isset($_SESSION['ditt_nettsted_unlocked']) || !isset($_SESSION['ditt_nettsted_expires'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ikke låst opp. Oppgi admin-passord først.']);
    exit;
}

// Check if unlock is still valid (30 minutes)
if (time() > $_SESSION['ditt_nettsted_expires']) {
    unset($_SESSION['ditt_nettsted_unlocked']);
    unset($_SESSION['ditt_nettsted_expires']);
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Opplåsingen har utløpt. Lås opp igjen.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Ugyldig forespørsel']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Kun felter som faktisk finnes i MySQL tenant_companies tabellen
$allowedFields = [
    'name',
    'legal_name',
    'entity_type',
    'domain',
    'contact_phone',
    'contact_email',
    'about_us',
    'industry',
    'address_street',
    'address_city',
    'address_zip',
    'address_country',
    'org_number',
    'tax_id',
    'logo_url'
];
$updateFields = [];
$params = [];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        $updateFields[] = "$field = ?";
        $params[] = $input[$field];
    }
}

if (empty($updateFields)) {
    echo json_encode(['success' => false, 'error' => 'Ingen felter å oppdatere']);
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $params[] = $_SESSION['company_id'];
    $sql = "UPDATE tenant_companies SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Firmadata oppdatert'
    ]);
    
} catch (Exception $e) {
    error_log("Update company data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Serverfeil']);
}
