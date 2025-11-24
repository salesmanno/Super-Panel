<?php

require_once __DIR__ . '/../config/database.php';

function getTenantAdminByUsername($username) {
    $db = getDBConnection();
    if (!$db) {
        return false;
    }
    
    $activeValue = 1;
    $stmt = $db->prepare("
        SELECT ta.id, ta.company_id, ta.tenant_staff_id, ta.password_hash, ta.username, ta.is_active,
               tc.name as company_name, tc.subdomain, tc.is_active as company_active,
               ts.first_name, ts.last_name, ts.email as staff_email, ts.phone, ts.job_title
        FROM tenant_admins ta
        INNER JOIN tenant_companies tc ON ta.company_id = tc.id
        LEFT JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
        WHERE ta.username = ? AND ta.is_active = $activeValue
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        if (empty($admin['first_name'])) {
            $admin['first_name'] = '';
        }
        if (empty($admin['last_name'])) {
            $admin['last_name'] = '';
        }
        if (empty($admin['staff_email'])) {
            $admin['staff_email'] = '';
        }
    }
    
    return $admin;
}

function updateTenantAdminLastLogin($adminId) {
    $db = getDBConnection();
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("UPDATE tenant_admins SET last_login = NOW() WHERE id = ?");
    return $stmt->execute([$adminId]);
}

function getTenantAdminModules($adminId) {
    $db = getDBConnection();
    if (!$db) {
        return [];
    }
    $activeValue = 1;
    $stmt = $db->prepare("
        SELECT sm.id, sm.name, sm.module_key, sm.description, sm.icon, sm.is_active, sm.created_at
        FROM system_modules sm
        INNER JOIN tenant_admin_modules tam ON sm.id = tam.module_id
        WHERE tam.tenant_admin_id = ? AND sm.is_active = $activeValue
        ORDER BY sm.name
    ");
    $stmt->execute([$adminId]);
    return $stmt->fetchAll();
}

function getCompanyModules($companyId) {
    $db = getDBConnection();
    if (!$db) {
        return [];
    }
    $activeValue = 1;
    $stmt = $db->prepare("
        SELECT sm.id, sm.name, sm.module_key, sm.description, sm.icon, sm.is_active, sm.created_at
        FROM system_modules sm
        INNER JOIN tenant_company_modules tcm ON sm.id = tcm.module_id
        WHERE tcm.company_id = ? AND tcm.is_active = $activeValue AND sm.is_active = $activeValue
        ORDER BY sm.name
    ");
    $stmt->execute([$companyId]);
    return $stmt->fetchAll();
}

function getLatestNews($limit = 5) {
    $db = getDBConnection();
    if (!$db) {
        return [];
    }
    $limit = (int)$limit;
    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                title,
                content,
                news_type,
                severity,
                is_published,
                publish_at,
                expire_at,
                created_at
            FROM core_sys_news 
            WHERE is_published
            AND (expire_at IS NULL OR expire_at > NOW())
            AND publish_at <= NOW()
            ORDER BY publish_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($results) && !empty($results)) {
            return $results;
        }
        return [];
    } catch (PDOException $e) {
        error_log("News query error: " . $e->getMessage());
        return [];
    }
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
    $activeValue = 1;
    $stmt = $db->prepare("
        SELECT * FROM tenant_companies 
        WHERE subdomain = ? AND is_active = $activeValue
    ");
    $stmt->execute([$subdomain]);
    return $stmt->fetch();
}

// ==================== SESSION TRACKING FUNCTIONS ====================

function createTenantAdminSession($tenantAdminId, $sessionId) {
    $db = getDBConnection();
    if (!$db) return false;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        // Create new session (existing sessions are now blocked in auth/company_auth.php)
        $stmt = $db->prepare("
            INSERT INTO tenant_admin_sessions 
            (tenant_admin_id, session_id, ip_address, user_agent, login_at, last_activity, is_active)
            VALUES (?, ?, ?, ?, NOW(), NOW(), " . 1 . ")
        ");
        return $stmt->execute([$tenantAdminId, $sessionId, $ipAddress, $userAgent]);
    } catch (PDOException $e) {
        error_log("Error creating session: " . $e->getMessage());
        return false;
    }
}

function updateSessionActivity($sessionId) {
    $db = getDBConnection();
    if (!$db) return false;
    
    try {
        $activeValue = 1;
        $stmt = $db->prepare("
            UPDATE tenant_admin_sessions 
            SET last_activity = NOW() 
            WHERE session_id = ? AND is_active = $activeValue
        ");
        return $stmt->execute([$sessionId]);
    } catch (PDOException $e) {
        error_log("Error updating session activity: " . $e->getMessage());
        return false;
    }
}

function endTenantAdminSession($sessionId) {
    $db = getDBConnection();
    if (!$db) return false;
    
    try {
        $activeValue = 1;
        $inactiveValue = 0;
        
        // Get tenant_admin_id from session before ending it
        $stmt = $db->prepare("
            SELECT tenant_admin_id 
            FROM tenant_admin_sessions 
            WHERE session_id = ? AND is_active = $activeValue
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // End the session
        $stmt = $db->prepare("
            UPDATE tenant_admin_sessions 
            SET logout_at = NOW(), is_active = $inactiveValue 
            WHERE session_id = ? AND is_active = $activeValue
        ");
        $stmt->execute([$sessionId]);
        
        // Update last_logout in tenant_admins
        if ($session && isset($session['tenant_admin_id'])) {
            $stmt = $db->prepare("
                UPDATE tenant_admins 
                SET last_logout = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$session['tenant_admin_id']]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error ending session: " . $e->getMessage());
        return false;
    }
}

function getTenantAdminActiveSessions($tenantAdminId) {
    $db = getDBConnection();
    if (!$db) return [];
    
    try {
        $activeValue = 1;
        $stmt = $db->prepare("
            SELECT * FROM tenant_admin_sessions 
            WHERE tenant_admin_id = ? AND is_active = $activeValue
            ORDER BY last_activity DESC
        ");
        $stmt->execute([$tenantAdminId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting active sessions: " . $e->getMessage());
        return [];
    }
}

function getTenantAdminSessionHistory($tenantAdminId, $limit = 50) {
    $db = getDBConnection();
    if (!$db) return [];
    
    try {
        $limit = (int)$limit;
        $stmt = $db->prepare("
            SELECT * FROM tenant_admin_sessions 
            WHERE tenant_admin_id = ?
            ORDER BY login_at DESC
            LIMIT " . $limit
        );
        $stmt->execute([$tenantAdminId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting session history: " . $e->getMessage());
        return [];
    }
}

function getAllActiveSessions($companyId = null) {
    $db = getDBConnection();
    if (!$db) return [];
    
    try {
        $activeValue = 1;
        if ($companyId) {
            $stmt = $db->prepare("
                SELECT tas.*, ta.username, ta.name, ta.email
                FROM tenant_admin_sessions tas
                INNER JOIN tenant_admins ta ON tas.tenant_admin_id = ta.id
                WHERE ta.company_id = ? AND tas.is_active = $activeValue
                ORDER BY tas.last_activity DESC
            ");
            $stmt->execute([$companyId]);
        } else {
            $stmt = $db->query("
                SELECT tas.*, ta.username, ta.name, ta.email, tc.name as company_name
                FROM tenant_admin_sessions tas
                INNER JOIN tenant_admins ta ON tas.tenant_admin_id = ta.id
                INNER JOIN tenant_companies tc ON ta.company_id = tc.id
                WHERE tas.is_active = $activeValue
                ORDER BY tas.last_activity DESC
            ");
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting all active sessions: " . $e->getMessage());
        return [];
    }
}

function endInactiveSessions($inactiveMinutes = 30) {
    $db = getDBConnection();
    if (!$db) return false;
    
    try {
        // MySQL: Use INTERVAL syntax directly
        $stmt = $db->prepare("
            UPDATE tenant_admin_sessions 
            SET is_active = ?, logout_at = NOW() 
            WHERE is_active = ? 
            AND last_activity < NOW() - INTERVAL ? MINUTE
        ");
        return $stmt->execute([0, 1, $inactiveMinutes]);
    } catch (PDOException $e) {
        error_log("Error ending inactive sessions: " . $e->getMessage());
        return false;
    }
}
