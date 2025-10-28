-- Sette inn generelle nettstedinnstillinger for alle aktive bedrifter

INSERT INTO tenant_website_general (
    company_id,
    site_title,
    site_description,
    logo_url,
    primary_color,
    secondary_color,
    header_layout,
    footer_layout,
    navigation_style,
    hero_enabled,
    hero_title,
    hero_description,
    hero_button_text,
    hero_button_url,
    contact_email,
    contact_phone,
    contact_address,
    footer_text,
    is_active,
    maintenance_mode,
    created_by,
    created_at,
    updated_at
)
SELECT 
    tc.id as company_id,
    CONCAT(tc.name, ' - Offisiell Nettside') as site_title,
    COALESCE(tc.about_us, CONCAT('Velkommen til ', tc.name, '. Vi leverer kvalitetstjenester og produkter til våre kunder.')) as site_description,
    COALESCE(tc.logo_url, '/logos/default-logo.png') as logo_url,
    '#667eea' as primary_color,
    '#764ba2' as secondary_color,
    'standard' as header_layout,
    'standard' as footer_layout,
    'top' as navigation_style,
    1 as hero_enabled,
    CONCAT('Velkommen til ', tc.name) as hero_title,
    CONCAT('Vi tilbyr profesjonelle tjenester og produkter av høyeste kvalitet.') as hero_description,
    'Kontakt oss' as hero_button_text,
    '/kontakt' as hero_button_url,
    COALESCE(tc.contact_email, CONCAT('post@', LOWER(REPLACE(tc.name, ' ', '')), '.no')) as contact_email,
    COALESCE(tc.contact_phone, '+47 22 00 00 00') as contact_phone,
    COALESCE(
        CONCAT(tc.address_street, ', ', tc.address_zip, ' ', tc.address_city, ', Norge'),
        'Kontakt oss for adresse'
    ) as contact_address,
    CONCAT('© ', YEAR(NOW()), ' ', tc.name, '. Alle rettigheter reservert.') as footer_text,
    1 as is_active,
    0 as maintenance_mode,
    '4541881c-b0e4-11f0-a1bb-b02628506e5c' as created_by,
    NOW() as created_at,
    NOW() as updated_at
FROM tenant_companies tc
WHERE tc.is_active = 1
AND NOT EXISTS (
    SELECT 1 
    FROM tenant_website_general twg 
    WHERE twg.company_id = tc.id
);

-- Vis resultatet
SELECT 
    twg.id,
    tc.name as company_name,
    twg.site_title,
    twg.primary_color,
    twg.hero_enabled,
    twg.is_active
FROM tenant_website_general twg
JOIN tenant_companies tc ON twg.company_id = tc.id
ORDER BY twg.created_at DESC
LIMIT 10;
