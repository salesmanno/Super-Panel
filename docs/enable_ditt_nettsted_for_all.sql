-- ============================================
-- ENABLE "DITT NETTSTED" FOR ALL USERS
-- ============================================
-- This script grants access to "Ditt nettsted" module for all active admins

-- Fix collation conflicts
SET collation_connection = 'utf8mb4_general_ci';

-- Set the module ID and saas_admin ID
SET @module_id = 'f9d42479-b182-11f0-b9ad-b02628506e5c';
SET @saas_admin_id = '4538cad6-b0e4-11f0-a1bb-b02628506e5c';

-- Grant access to all tenant admins who don't already have it
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by, granted_at)
SELECT 
    UUID(),
    ta.id,
    @module_id,
    @saas_admin_id,
    NOW()
FROM tenant_admins ta
WHERE ta.is_active = TRUE
AND NOT EXISTS (
    SELECT 1 FROM tenant_admin_modules tam 
    WHERE tam.tenant_admin_id = ta.id 
    AND tam.module_id = @module_id
);

-- Verify results
SELECT 
    'Access granted successfully' as status,
    COUNT(*) as admins_with_access,
    (SELECT COUNT(*) FROM tenant_admins WHERE is_active = TRUE) as total_active_admins
FROM tenant_admin_modules tam
WHERE tam.module_id = @module_id;
