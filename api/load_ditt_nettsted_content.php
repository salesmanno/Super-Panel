<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
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
    $contentFile = __DIR__ . '/../modules/deres_firma_content.php';
    
    if (!file_exists($contentFile)) {
        throw new Exception("Innholdsfil ikke funnet: {$contentFile}");
    }
    
    ob_start();
    $result = include $contentFile;
    $content = ob_get_clean();
    
    if ($content === false) {
        throw new Exception("Kunne ikke laste innhold fra deres_firma_content.php");
    }
    
    if (empty($content)) {
        // If content is empty, output a basic message
        echo '<div class="alert alert-info">Ingen innhold tilgjengelig</div>';
    } else {
        echo $content;
    }
} catch (Exception $e) {
    error_log("Load ditt nettsted content error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo '<div class="alert alert-danger">En feil oppstod ved lasting av innhold: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
