<?php
$controlName = trim((string)($adminName ?? 'Bruker')) ?: 'Bruker';
?>
<section id="pwp-control-room" class="pwp-control-room" aria-label="Administrasjonsheader" aria-hidden="true" inert>
    <div class="pwp-control-inner">
        <div class="pwp-control-eyebrow"><span aria-hidden="true"></span> KONTROLLROM <span>·</span> <span id="pwp-control-company"><?= htmlspecialchars((string)($previewData['company']['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" data-pwp-admin-close aria-label="Lukk administrasjonsheader">×</button>
        </div>
        <h2>Hei, <?= htmlspecialchars(explode(' ', $controlName)[0], ENT_QUOTES, 'UTF-8') ?>.</h2>
        <p>Administrer bedriften og innholdet på nettsiden.</p>
        <nav aria-label="Administrasjonsområder">
            <button type="button" data-pwp-page="about">Bedrift og innstillinger</button>
            <button type="button" data-pwp-page="home">Nyheter</button>
            <button type="button" data-pwp-page="services">Tjenester</button>
        </nav>
    </div>
</section>
