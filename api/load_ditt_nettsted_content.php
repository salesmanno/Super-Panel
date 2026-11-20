<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/company_settings_access.php';

// Sjekk at bruker er innlogget
if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Ikke innlogget</div>';
    exit;
}

// Last inn innholdet
try {
    $db = getDBConnection();
    companySettingsRequireAccess($db, (string)$_SESSION['company_id']);
    ob_start();
    include __DIR__ . '/../modules/deres_firma_content.php';
    $content = ob_get_clean();
    echo $content;
} catch (DomainException $e) {
    http_response_code(403);
    echo '<div class="alert alert-warning">Du har ikke tilgang til bedriftsinnstillingene.</div>';
} catch (Exception $e) {
    error_log("Load ditt nettsted content error: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-danger">En feil oppstod ved lasting av innhold</div>';
}
