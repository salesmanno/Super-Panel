<?php
// Included only from the authenticated dashboard, after delegated modules load.
require_once __DIR__ . '/portal_website_preview_data.php';
$previewData = portalWebsitePreviewData($db, (string)$_SESSION['company_id'], $allUserModules, $allModules);
$previewKeys = array_column($previewData['modules'], 'module_key');
?>
<link rel="stylesheet" href="/assets/portal-website-preview.css?v=<?= filemtime(__DIR__.'/../assets/portal-website-preview.css') ?>">
<section id="portal-website-preview" aria-label="Kundesidelignende administrasjon">
    <header class="pwp-brand"><img id="pwp-logo" alt="" hidden><strong id="pwp-company-name"></strong></header>
    <nav class="pwp-site-nav" aria-label="Sidenavigasjon">
        <button type="button" data-pwp-page="about">Om oss</button>
        <button type="button" data-pwp-page="home" aria-current="page">Hjem</button>
        <button type="button" data-pwp-page="services">Tjenester</button>
        <a href="/auth/logout.php">Logg ut</a>
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
        <button type="button" id="pwp-new" aria-expanded="false" aria-controls="pwp-create-menu">＋ Ny</button>
    </div>
    <div id="pwp-create-menu" class="pwp-create-menu" aria-label="Opprett tjeneste" hidden></div>
    <script type="application/json" id="pwp-data"><?= json_encode($previewData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
</section>
<div id="pwp-workspace-bar" hidden>
    <button type="button" id="pwp-close-workspace">← Tilbake til forhåndsvisning</button>
    <span>Eksisterende modul · ordinær lagring</span>
</div>
