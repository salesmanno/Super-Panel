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
    
    // Helper function to normalize boolean values
    function normalizeBool($value) {
        return (int)filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    // Helper function to validate and normalize color
    function normalizeColor($color, $default = '#3366CC') {
        if (empty($color)) return $default;
        // Ensure color starts with # and is valid hex
        $color = trim($color);
        if (!preg_match('/^#[0-9A-F]{6}$/i', $color)) {
            return $default;
        }
        return strtoupper($color);
    }
    
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
                normalizeBool($config['is_published'] ?? 0),
                normalizeBool($config['is_enabled'] ?? 0),
                !empty($config['display_name']) ? trim($config['display_name']) : null,
                !empty($config['seo_title']) ? trim($config['seo_title']) : null,
                !empty($config['seo_description']) ? trim($config['seo_description']) : null,
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
                normalizeBool($config['is_published'] ?? 0),
                normalizeBool($config['is_enabled'] ?? 0),
                !empty($config['display_name']) ? trim($config['display_name']) : null,
                !empty($config['seo_title']) ? trim($config['seo_title']) : null,
                !empty($config['seo_description']) ? trim($config['seo_description']) : null
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
                    header_bg_color = ?,
                    body_bg_color = ?,
                    logo_height = ?,
                    header_height = ?,
                    logo_position = ?,
                    nav_position = ?,
                    font_family = ?,
                    button_style = ?,
                    border_radius = ?,
                    spacing_unit = ?,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE company_id = ?
            ");
            $stmt->execute([
                !empty($general['site_title']) ? trim($general['site_title']) : null,
                !empty($general['site_description']) ? trim($general['site_description']) : null,
                normalizeColor($general['primary_color'] ?? '#3366CC', '#3366CC'),
                normalizeColor($general['secondary_color'] ?? '#666666', '#666666'),
                in_array($general['navigation_style'] ?? 'top', ['top', 'side']) ? $general['navigation_style'] : 'top',
                in_array($general['header_layout'] ?? 'standard', ['standard', 'centered', 'minimal']) ? $general['header_layout'] : 'standard',
                in_array($general['footer_layout'] ?? 'standard', ['standard', 'minimal', 'extended']) ? $general['footer_layout'] : 'standard',
                normalizeBool($general['hero_enabled'] ?? 1),
                !empty($general['hero_title']) ? trim($general['hero_title']) : null,
                !empty($general['hero_description']) ? trim($general['hero_description']) : null,
                !empty($general['hero_button_text']) ? trim($general['hero_button_text']) : null,
                !empty($general['contact_email']) ? filter_var(trim($general['contact_email']), FILTER_VALIDATE_EMAIL) : null,
                !empty($general['contact_phone']) ? trim($general['contact_phone']) : null,
                !empty($general['contact_address']) ? trim($general['contact_address']) : null,
                !empty($general['google_analytics_id']) ? trim($general['google_analytics_id']) : null,
                normalizeBool($general['is_active'] ?? 1),
                normalizeBool($general['maintenance_mode'] ?? 0),
                !empty($general['maintenance_message']) ? trim($general['maintenance_message']) : null,
                normalizeColor($general['header_bg_color'] ?? '#ffffff', '#ffffff'),
                normalizeColor($general['body_bg_color'] ?? '#f8f9fa', '#f8f9fa'),
                !empty($general['logo_height']) ? trim($general['logo_height']) : '50px',
                !empty($general['header_height']) ? trim($general['header_height']) : '80px',
                in_array($general['logo_position'] ?? 'left', ['left', 'center', 'right']) ? $general['logo_position'] : 'left',
                in_array($general['nav_position'] ?? 'center', ['left', 'center', 'right']) ? $general['nav_position'] : 'center',
                !empty($general['font_family']) ? trim($general['font_family']) : 'Inter, sans-serif',
                in_array($general['button_style'] ?? 'rounded', ['rounded', 'square', 'pill']) ? $general['button_style'] : 'rounded',
                !empty($general['border_radius']) ? trim($general['border_radius']) : '16px',
                !empty($general['spacing_unit']) ? trim($general['spacing_unit']) : '1rem',
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
                 header_bg_color, body_bg_color, logo_height, header_height, logo_position,
                 nav_position, font_family, button_style, border_radius, spacing_unit,
                 created_by, created_at, updated_at, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
            ");
            $stmt->execute([
                $generalId,
                $companyId,
                !empty($general['site_title']) ? trim($general['site_title']) : null,
                !empty($general['site_description']) ? trim($general['site_description']) : null,
                normalizeColor($general['primary_color'] ?? '#3366CC', '#3366CC'),
                normalizeColor($general['secondary_color'] ?? '#666666', '#666666'),
                in_array($general['navigation_style'] ?? 'top', ['top', 'side']) ? $general['navigation_style'] : 'top',
                in_array($general['header_layout'] ?? 'standard', ['standard', 'centered', 'minimal']) ? $general['header_layout'] : 'standard',
                in_array($general['footer_layout'] ?? 'standard', ['standard', 'minimal', 'extended']) ? $general['footer_layout'] : 'standard',
                normalizeBool($general['hero_enabled'] ?? 1),
                !empty($general['hero_title']) ? trim($general['hero_title']) : null,
                !empty($general['hero_description']) ? trim($general['hero_description']) : null,
                !empty($general['hero_button_text']) ? trim($general['hero_button_text']) : null,
                !empty($general['contact_email']) ? filter_var(trim($general['contact_email']), FILTER_VALIDATE_EMAIL) : null,
                !empty($general['contact_phone']) ? trim($general['contact_phone']) : null,
                !empty($general['contact_address']) ? trim($general['contact_address']) : null,
                !empty($general['google_analytics_id']) ? trim($general['google_analytics_id']) : null,
                normalizeBool($general['is_active'] ?? 1),
                normalizeBool($general['maintenance_mode'] ?? 0),
                !empty($general['maintenance_message']) ? trim($general['maintenance_message']) : null,
                normalizeColor($general['header_bg_color'] ?? '#ffffff', '#ffffff'),
                normalizeColor($general['body_bg_color'] ?? '#f8f9fa', '#f8f9fa'),
                !empty($general['logo_height']) ? trim($general['logo_height']) : '50px',
                !empty($general['header_height']) ? trim($general['header_height']) : '80px',
                in_array($general['logo_position'] ?? 'left', ['left', 'center', 'right']) ? $general['logo_position'] : 'left',
                in_array($general['nav_position'] ?? 'center', ['left', 'center', 'right']) ? $general['nav_position'] : 'center',
                !empty($general['font_family']) ? trim($general['font_family']) : 'Inter, sans-serif',
                in_array($general['button_style'] ?? 'rounded', ['rounded', 'square', 'pill']) ? $general['button_style'] : 'rounded',
                !empty($general['border_radius']) ? trim($general['border_radius']) : '16px',
                !empty($general['spacing_unit']) ? trim($general['spacing_unit']) : '1rem',
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
