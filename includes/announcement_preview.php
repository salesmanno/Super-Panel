<?php
declare(strict_types=1);

/** Optional website styling must never prevent the editorial list from loading. */
function announcement_preview_company(PDO $db, string $companyId): array {
    $warnings = [];
    $profile = [];
    $theme = [];
    foreach (['firmaprofil' => 'tenant_companies', 'nettsideoppsett' => 'tenant_website_general'] as $label => $table) {
        try {
            // Read existing columns without requiring optional styling migrations.
            $key = $table === 'tenant_companies' ? 'id' : 'company_id';
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE {$key} = ? LIMIT 1");
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if ($table === 'tenant_companies') $profile = $row;
            else $theme = $row;
        } catch (PDOException $error) {
            error_log("News preview {$label}: " . $error->getMessage());
            $warnings[] = "Kunne ikke hente {$label}. Sakene kan fortsatt administreres.";
        }
    }
    $position = static fn($value, $default) => in_array($value, ['left', 'center', 'right'], true) ? $value : $default;
    return [
        'company' => [
            'name' => (string)($profile['name'] ?? ''),
            'img_url' => (string)($profile['img_url'] ?? $profile['logo_url'] ?? ''),
            'header_bg_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($theme['header_bg_color'] ?? ''))
                ? $theme['header_bg_color'] : '#176b69',
            'header_layout' => in_array($theme['header_layout'] ?? '', ['standard', 'centered', 'minimal'], true)
                ? $theme['header_layout'] : 'standard',
            'logo_position' => $position($theme['logo_position'] ?? null, ($theme['header_layout'] ?? '') === 'centered' ? 'center' : 'left'),
            'nav_position' => $position($theme['nav_position'] ?? null, 'center'),
            'contact_phone' => (string)($profile['contact_phone'] ?? ''),
            'address_street' => (string)($profile['address_street'] ?? ''),
            'address_zip' => (string)($profile['address_zip'] ?? ''),
            'address_city' => (string)($profile['address_city'] ?? ''),
        ],
        'warnings' => $warnings,
    ];
}
