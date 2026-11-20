<?php
declare(strict_types=1);
require_once __DIR__.'/portal_view_mode.php';
require_once __DIR__.'/announcement_preview.php';
require_once __DIR__.'/membership_benefits_preview.php';

function portalWebsitePreviewData(PDO $db, string $company, array $userModules, array $companyModules): array
{
    $modules = portalPreviewModules($userModules, $companyModules);
    $keys = array_column($modules, 'module_key');
    if (in_array('reservation', $keys, true)) $keys[] = 'reservations';
    $data = ['company'=>[], 'news'=>[], 'products'=>[], 'modules'=>$modules, 'errors'=>[]];
    try {
        $branding = announcement_preview_company($db, $company);
        $data['company'] = $branding['company'];
        $data['errors'] = $branding['warnings'];
        $query = $db->prepare(
            'SELECT id, title, content, image_url FROM tenant_announcements
             WHERE company_id = ? AND is_published = 1
             AND COALESCE(publish_at, NOW()) <= NOW() AND (expire_at IS NULL OR expire_at > NOW())
             ORDER BY is_pinned DESC, CAST(priority AS UNSIGNED), publish_at DESC, id'
        );
        $query->execute([$company]);
        $data['news'] = $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $error) {
        error_log('Portal website preview news: '.$error->getMessage());
        $data['errors'][] = 'Nyheter kunne ikke lastes. Du kan fortsatt åpne Nyhetsadministrasjon.';
    }
    $tables = ['membership'=>'membership_plans', 'rental'=>'rental_objects',
        'events'=>'events_eventdata', 'sales'=>'sales_products', 'reservations'=>'reservation_services'];
    $sources = [];
    foreach ($tables as $module => $table) {
        if (!in_array($module, $keys, true)) continue;
        try {
            $query = $db->prepare("SELECT * FROM {$table} WHERE company_id = ?");
            $query->execute([$company]);
            $sources[$module] = array_column($query->fetchAll(PDO::FETCH_ASSOC), null, 'id');
        } catch (Throwable $error) {
            error_log("Portal website preview {$module}: ".$error->getMessage());
            $data['errors'][] = "Kunne ikke laste tilbud fra {$module}.";
        }
    }
    try {
        // Read-only projection. Source rows, including access codes, are never returned.
        $query = $db->prepare('SELECT id, product_type, product_id, name, description, is_visible, is_public FROM output_products WHERE company_id = ? AND is_visible = 1 AND is_public = 1 ORDER BY created_at DESC, id');
        $query->execute([$company]);
        $seen = [];
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $catalog) {
            $module = portalPreviewModuleForType((string)$catalog['product_type']);
            $source = $sources[$module ?? ''][(string)$catalog['product_id']] ?? null;
            if (!$source) continue;
            if ($module === 'events' && !empty($source['end_date']) && substr($source['end_date'], 0, 10) < date('Y-m-d')) continue;
            $key = $module.':'.$catalog['product_id'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $item = membershipBenefitsPreviewProduct($catalog, $source);
            if (!$item['is_visible'] || !$item['is_public']) continue;
            $item['module'] = $module;
            $data['products'][] = $item;
        }
    } catch (Throwable $error) {
        error_log('Portal website preview catalogue: '.$error->getMessage());
        $data['errors'][] = 'Tjenestekatalogen kunne ikke lastes. Modulene er fortsatt tilgjengelige.';
    }
    return $data;
}
