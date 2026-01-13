<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ShareLinkService.php';

try {
    $type = $_GET['type'] ?? '';
    $itemId = $_GET['item_id'] ?? '';
    
    if (empty($type) || empty($itemId)) {
        echo json_encode(['success' => false, 'message' => 'Mangler type eller item_id']);
        exit;
    }
    
    $validTypes = ['event', 'service', 'membership'];
    if (!in_array($type, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig type']);
        exit;
    }
    
    $db = getDBConnection();
    $shareLinkService = new ShareLinkService($db, $_SESSION['company_id']);
    
    $shareLink = '';
    switch ($type) {
        case 'event':
            $shareLink = $shareLinkService->getEventShareLink($itemId);
            break;
        case 'service':
            $shareLink = $shareLinkService->getServiceShareLink($itemId);
            break;
        case 'membership':
            $shareLink = $shareLinkService->getMembershipShareLink($itemId);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'share_link' => $shareLink
    ]);
    
} catch (Exception $e) {
    error_log("Error getting share link: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av delingslenke: ' . $e->getMessage()
    ]);
}
