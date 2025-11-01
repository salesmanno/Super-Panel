<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $db->beginTransaction();
    
    // Check if settings already exist
    $stmt = $db->prepare("SELECT id FROM tenant_website_general WHERE company_id = ?");
    $stmt->execute([$companyId]);
    if ($stmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Websideinnstillinger eksisterer allerede']);
        exit;
    }
    
    // Get company name for default title
    $companyStmt = $db->prepare("SELECT name FROM tenant_companies WHERE id = ?");
    $companyStmt->execute([$companyId]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);
    $companyName = $company['name'] ?? 'Min Bedrift';
    
    // Create tenant_website_general entry with default values
    $stmt = $db->prepare("
        INSERT INTO tenant_website_general (
            company_id,
            site_title,
            site_description,
            is_active,
            maintenance_mode,
            primary_color,
            secondary_color,
            navigation_style,
            header_layout,
            footer_layout,
            hero_enabled,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $companyId,
        $companyName,
        'Velkommen til vår nettside',
        true, // is_active
        false, // maintenance_mode
        '#3366CC', // primary_color
        '#666666', // secondary_color
        'top', // navigation_style
        'standard', // header_layout
        'standard', // footer_layout
        true, // hero_enabled
        $_SESSION['tenant_admin_id']
    ]);
    
    // Create tenant_website_config entry with default values  
    $stmt = $db->prepare("
        INSERT INTO tenant_website_config (
            company_id,
            module_id,
            is_enabled,
            is_public,
            show_in_navigation,
            display_name,
            seo_title,
            seo_description,
            is_published,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $companyId,
        NULL, // module_id - optional
        true, // is_enabled
        true, // is_public
        true, // show_in_navigation
        $companyName,
        $companyName . ' - Hjem',
        'Velkommen til ' . $companyName,
        false, // is_published (draft by default)
        $_SESSION['tenant_admin_id']
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Websideinnstillinger opprettet. Last inn siden på nytt for å se innstillingene.'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error creating website settings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke opprette websideinnstillinger: ' . $e->getMessage()
    ]);
}
