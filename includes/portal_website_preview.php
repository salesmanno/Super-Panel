<?php
// Included only from the authenticated dashboard, after delegated modules load.
require_once __DIR__ . '/portal_website_preview_data.php';
$previewData = portalWebsitePreviewData($db, (string)$_SESSION['company_id'], $allUserModules, $allModules);
$previewKeys = array_column($previewData['modules'], 'module_key');
$previewData['is_supervisor'] = $isSupervisor;
?>
<link rel="stylesheet" href="/assets/portal-website-preview.css?v=<?= filemtime(__DIR__.'/../assets/portal-website-preview.css') ?>">
<link rel="stylesheet" href="/assets/entity-form-fields.css?v=<?= filemtime(__DIR__.'/../assets/entity-form-fields.css') ?>">
<section id="portal-website-preview" aria-label="Kundesidelignende administrasjon">
    <header class="pwp-brand">
        <div class="pwp-brand-identity"><img id="pwp-logo" alt="" hidden><strong id="pwp-company-name"></strong></div>
        <a class="pwp-header-logout" href="/auth/logout.php">Logg ut</a>
    </header>
    <nav class="pwp-site-nav" aria-label="Sidenavigasjon">
        <button type="button" data-pwp-page="about">Om oss</button>
        <button type="button" data-pwp-page="home" aria-current="page">Hjem</button>
        <button type="button" data-pwp-page="services">Tjenester</button>
    </nav>
    <div id="pwp-errors" role="status"></div>
    <div id="pwp-content" class="pwp-content"></div>
    <footer class="pwp-footer"><strong id="pwp-footer-company"></strong><span id="pwp-contact"></span></footer>
    <div class="pwp-island" role="group" aria-label="Handling ved trykk på innhold">
        <button type="button" data-pwp-mode="read" aria-pressed="true">Se på</button>
        <button type="button" data-pwp-mode="edit" aria-pressed="false">Rediger</button>
        <?php if (in_array('membership', $previewKeys, true)): ?>
        <button type="button" data-pwp-mode="benefits" aria-pressed="false" hidden>Fordeler</button>
        <?php endif; ?>
        <button type="button" data-pwp-mode="fields" aria-pressed="false" hidden>Skjemafelt</button>
        <button type="button" id="pwp-new" aria-expanded="false" aria-controls="pwp-create-menu">＋ Ny</button>
    </div>
    <div id="pwp-create-menu" class="pwp-create-menu" aria-label="Opprett tjeneste" hidden></div>
    <script type="application/json" id="pwp-data"><?= json_encode($previewData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
</section>
<dialog id="pwp-dialog" aria-labelledby="pwp-dialog-title">
    <div class="pwp-dialog-shell dashboard-module-theme">
        <header class="pwp-dialog-head">
            <h2 id="pwp-dialog-title"></h2>
            <button type="button" id="pwp-dialog-close" aria-label="Lukk vinduet">×</button>
        </header>
        <div class="pwp-dialog-body" id="pwp-dialog-body"></div>
    </div>
</dialog>
