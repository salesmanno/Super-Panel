<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    
    // Update or insert tenant_website_config
    if (isset($data['config'])) {
        $config = $data['config'];
        
        // Check if config exists
        $stmt = $db->prepare("SELECT id FROM tenant_website_config WHERE company_id = ?");
        $stmt->execute([$companyId]);
        $existingConfig = $stmt->fetch();
        
        if ($existingConfig) {
            // Update existing
            $stmt = $db->prepare("
                UPDATE tenant_website_config 
                SET is_published = ?, 
                    is_enabled = ?,
                    display_name = ?,
                    seo_title = ?,
                    seo_description = ?,
                    updated_at = NOW()
                WHERE company_id = ?
            ");
            $stmt->execute([
                $config['is_published'] ?? 0,
                $config['is_enabled'] ?? 0,
                $config['display_name'] ?? null,
                $config['seo_title'] ?? null,
                $config['seo_description'] ?? null,
                $companyId
            ]);
        } else {
            // Insert new
            $configId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("
                INSERT INTO tenant_website_config 
                (id, company_id, is_published, is_enabled, display_name, seo_title, seo_description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $configId,
                $companyId,
                $config['is_published'] ?? 0,
                $config['is_enabled'] ?? 0,
                $config['display_name'] ?? null,
                $config['seo_title'] ?? null,
                $config['seo_description'] ?? null
            ]);
        }
    }
    
    // Update or insert tenant_website_general
    if (isset($data['general'])) {
        $general = $data['general'];
        
        // Check if general settings exist
        $stmt = $db->prepare("SELECT id FROM tenant_website_general WHERE company_id = ?");
        $stmt->execute([$companyId]);
        $existingGeneral = $stmt->fetch();
        
        if ($existingGeneral) {
            // Update existing
            $stmt = $db->prepare("
                UPDATE tenant_website_general 
                SET site_title = ?,
                    site_description = ?,
                    primary_color = ?,
                    secondary_color = ?,
                    navigation_style = ?,
                    header_layout = ?,
                    footer_layout = ?,
                    hero_enabled = ?,
                    hero_title = ?,
                    hero_description = ?,
                    hero_button_text = ?,
                    contact_email = ?,
                    contact_phone = ?,
                    contact_address = ?,
                    google_analytics_id = ?,
                    is_active = ?,
                    maintenance_mode = ?,
                    maintenance_message = ?,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE company_id = ?
            ");
            $stmt->execute([
                $general['site_title'] ?? null,
                $general['site_description'] ?? null,
                $general['primary_color'] ?? '#3366CC',
                $general['secondary_color'] ?? '#666666',
                $general['navigation_style'] ?? 'top',
                $general['header_layout'] ?? 'standard',
                $general['footer_layout'] ?? 'standard',
                $general['hero_enabled'] ?? 1,
                $general['hero_title'] ?? null,
                $general['hero_description'] ?? null,
                $general['hero_button_text'] ?? null,
                $general['contact_email'] ?? null,
                $general['contact_phone'] ?? null,
                $general['contact_address'] ?? null,
                $general['google_analytics_id'] ?? null,
                $general['is_active'] ?? 1,
                $general['maintenance_mode'] ?? 0,
                $general['maintenance_message'] ?? null,
                $adminId,
                $companyId
            ]);
        } else {
            // Insert new
            $generalId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("
                INSERT INTO tenant_website_general 
                (id, company_id, site_title, site_description, primary_color, secondary_color,
                 navigation_style, header_layout, footer_layout, hero_enabled, hero_title,
                 hero_description, hero_button_text, contact_email, contact_phone, contact_address,
                 google_analytics_id, is_active, maintenance_mode, maintenance_message,
                 created_by, created_at, updated_at, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
            ");
            $stmt->execute([
                $generalId,
                $companyId,
                $general['site_title'] ?? null,
                $general['site_description'] ?? null,
                $general['primary_color'] ?? '#3366CC',
                $general['secondary_color'] ?? '#666666',
                $general['navigation_style'] ?? 'top',
                $general['header_layout'] ?? 'standard',
                $general['footer_layout'] ?? 'standard',
                $general['hero_enabled'] ?? 1,
                $general['hero_title'] ?? null,
                $general['hero_description'] ?? null,
                $general['hero_button_text'] ?? null,
                $general['contact_email'] ?? null,
                $general['contact_phone'] ?? null,
                $general['contact_address'] ?? null,
                $general['google_analytics_id'] ?? null,
                $general['is_active'] ?? 1,
                $general['maintenance_mode'] ?? 0,
                $general['maintenance_message'] ?? null,
                $adminId,
                $adminId
            ]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Websideinnstillinger oppdatert'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Error updating webside data: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke oppdatere websideinnstillinger: ' . $e->getMessage()
    ]);
}
