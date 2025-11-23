<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($_GET['company_id']) && !isset($_GET['subdomain'])) {
        throw new Exception('company_id eller subdomain kreves');
    }
    
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database tilkobling feilet');
    }
    
    if (isset($_GET['subdomain'])) {
        $stmt = $db->prepare("SELECT id, name, subdomain FROM tenant_companies WHERE subdomain = ? AND is_active = ?");
        $stmt->execute([$_GET['subdomain'], 1]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            throw new Exception('Firma ikke funnet');
        }
        
        $companyId = $company['id'];
    } else {
        $companyId = intval($_GET['company_id']);
        
        $stmt = $db->prepare("SELECT id, name, subdomain FROM tenant_companies WHERE id = ? AND is_active = ?");
        $stmt->execute([$companyId, 1]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            throw new Exception('Firma ikke funnet');
        }
    }
    
    $stmt = $db->prepare("
        SELECT 
            wc.*,
            wt.name as template_name,
            wt.description as template_description,
            wt.template_type
        FROM webconfiguration wc
        JOIN website_templates wt ON wc.template_id = wt.template_id
        WHERE wc.company_id = ?
    ");
    $stmt->execute([$companyId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        echo json_encode([
            'success' => true,
            'has_website' => false,
            'company' => $company,
            'message' => 'Ingen nettside konfigurert'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'has_website' => true,
        'company' => $company,
        'template' => [
            'id' => $settings['template_id'],
            'name' => $settings['template_name'],
            'description' => $settings['template_description'],
            'type' => $settings['template_type']
        ],
        'settings' => json_decode($settings['custom_settings'] ?? '{}', true),
        'is_published' => (bool)$settings['is_published'],
        'published_at' => $settings['published_at'],
        'updated_at' => $settings['updated_at']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
