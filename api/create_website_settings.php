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
            header_bg_color,
            body_bg_color,
            logo_height,
            header_height,
            logo_position,
            nav_position,
            font_family,
            button_style,
            border_radius,
            spacing_unit,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        '#ffffff', // header_bg_color
        '#f8f9fa', // body_bg_color
        '50px', // logo_height
        '80px', // header_height
        'left', // logo_position
        'center', // nav_position
        'Inter, sans-serif', // font_family
        'rounded', // button_style
        '16px', // border_radius
        '1rem', // spacing_unit
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
    
    // Bind parameters with explicit NULL type for PostgreSQL compatibility
    $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
    $stmt->bindValue(2, null, PDO::PARAM_NULL); // module_id - optional
    $stmt->bindValue(3, true, PDO::PARAM_BOOL);
    $stmt->bindValue(4, true, PDO::PARAM_BOOL);
    $stmt->bindValue(5, true, PDO::PARAM_BOOL);
    $stmt->bindValue(6, $companyName, PDO::PARAM_STR);
    $stmt->bindValue(7, $companyName . ' - Hjem', PDO::PARAM_STR);
    $stmt->bindValue(8, 'Velkommen til ' . $companyName, PDO::PARAM_STR);
    $stmt->bindValue(9, false, PDO::PARAM_BOOL);
    $stmt->bindValue(10, $_SESSION['tenant_admin_id'], PDO::PARAM_INT);
    $stmt->execute();
    
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
