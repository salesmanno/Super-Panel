<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Sjekk at bruker er innlogget
if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Ikke innlogget</div>';
    exit;
}

// Sjekk at bruker har låst opp "Ditt nettsted"
if (!isset($_SESSION['ditt_nettsted_unlocked']) || 
    !isset($_SESSION['ditt_nettsted_expires']) || 
    time() > $_SESSION['ditt_nettsted_expires']) {
    http_response_code(403);
    echo '<div class="alert alert-warning">Tilgang utløpt. Vennligst lås opp igjen.</div>';
    exit;
}

// Last inn innholdet
try {
    $db = getDBConnection();
    ob_start();
    include __DIR__ . '/../modules/deres_firma_content.php';
    $content = ob_get_clean();
    echo $content;
} catch (Exception $e) {
    error_log("Load ditt nettsted content error: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-danger">En feil oppstod ved lasting av innhold</div>';
}
