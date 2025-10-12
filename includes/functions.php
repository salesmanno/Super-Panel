<?php

require_once __DIR__ . '/../config/database.php';

function getTenantAdminByUsername($username) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT ta.*, tc.name as company_name, tc.subdomain, tc.is_active as company_active
        FROM tenant_admins ta
        INNER JOIN tenant_companies tc ON ta.company_id = tc.id
        WHERE ta.username = ? AND ta.is_active = 1
    ");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function updateTenantAdminLastLogin($adminId) {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE tenant_admins SET last_login = NOW() WHERE id = ?");
    return $stmt->execute([$adminId]);
}

function getTenantAdminModules($adminId) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT sm.* 
        FROM system_modules sm
        INNER JOIN tenant_admin_modules tam ON sm.id = tam.module_id
        WHERE tam.tenant_admin_id = ? AND sm.is_active = 1
        ORDER BY sm.name
    ");
    $stmt->execute([$adminId]);
    return $stmt->fetchAll();
}

function getCompanyModules($companyId) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT sm.* 
        FROM system_modules sm
        INNER JOIN tenant_company_modules tcm ON sm.id = tcm.module_id
        WHERE tcm.company_id = ? AND tcm.is_active = 1 AND sm.is_active = 1
        ORDER BY sm.name
    ");
    $stmt->execute([$companyId]);
    return $stmt->fetchAll();
}

function getLatestNews($limit = 5) {
    $db = getDBConnection();
    $limit = (int)$limit;
    $stmt = $db->prepare("
        SELECT * FROM core_sys_news 
        WHERE is_published = 1 
        AND (expire_at IS NULL OR expire_at > NOW())
        AND publish_at <= NOW()
        ORDER BY publish_at DESC 
        LIMIT " . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function getCompanyBySubdomain($subdomain) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT * FROM tenant_companies 
        WHERE subdomain = ? AND is_active = 1
    ");
    $stmt->execute([$subdomain]);
    return $stmt->fetch();
}
