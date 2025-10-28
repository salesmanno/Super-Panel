-- Sette inn nettstedinnstillinger for alle aktive bedrifter
-- Modul ID for "Ditt Nettsted": f9d42479-b182-11f0-b9ad-b02628506e5c

INSERT INTO tenant_website_config (
    company_id,
    module_id,
    is_enabled,
    is_public,
    show_in_navigation,
    display_name,
    display_order,
    navigation_icon,
    color_scheme,
    seo_title,
    seo_description,
    seo_keywords,
    is_published,
    published_at,
    created_by,
    created_at,
    updated_at
)
SELECT 
    tc.id as company_id,
    'f9d42479-b182-11f0-b9ad-b02628506e5c' as module_id,
    1 as is_enabled,
    1 as is_public,
    1 as show_in_navigation,
    tc.name as display_name,
    1 as display_order,
    'bi-globe' as navigation_icon,
    'default' as color_scheme,
    CONCAT(tc.name, ' - Offisiell Nettside') as seo_title,
    CONCAT('Velkommen til ', tc.name, '. Vi er en ledende leverandør av profesjonelle tjenester og produkter. Kontakt oss for mer informasjon om våre tilbud.') as seo_description,
    CONCAT(tc.name, ', tjenester, produkter, ', COALESCE(tc.industry, 'bedrift')) as seo_keywords,
    1 as is_published,
    NOW() as published_at,
    '4541881c-b0e4-11f0-a1bb-b02628506e5c' as created_by,
    NOW() as created_at,
    NOW() as updated_at
FROM tenant_companies tc
WHERE tc.is_active = 1
AND NOT EXISTS (
    SELECT 1 
    FROM tenant_website_config twc 
    WHERE twc.company_id = tc.id 
    AND twc.module_id = 'f9d42479-b182-11f0-b9ad-b02628506e5c'
);

-- Vis resultatet
SELECT 
    twc.id,
    tc.name as company_name,
    twc.display_name,
    twc.seo_title,
    twc.is_published,
    twc.is_enabled,
    twc.created_at
FROM tenant_website_config twc
JOIN tenant_companies tc ON twc.company_id = tc.id
ORDER BY twc.created_at DESC
LIMIT 10;
