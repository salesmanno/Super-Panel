<link rel="stylesheet" href="/assets/sales-online-offers.css?v=<?= filemtime(__DIR__ . '/../assets/sales-online-offers.css') ?>">
<section id="sales-online-offers" class="subtab-content" style="display:none" aria-labelledby="soo-heading">
    <div class="soo-workspace">
        <header class="soo-toolbar">
            <div class="soo-title-block">
                <span class="soo-kicker">SALG · TJENESTESIMULATOR</span>
                <h2 id="soo-heading">Varetilbud nett</h2>
                <p>Forhåndsvis og rediger bedriftens varer for nett.</p>
            </div>
            <div class="soo-tools">
                <label class="soo-hidden-toggle"><input id="soo-show-hidden" type="checkbox"> Vis også kioskvarer og inaktive varer</label>
                <div class="soo-viewport" role="group" aria-label="Forhåndsvisningsstørrelse">
                    <button type="button" data-soo-viewport="desktop" aria-pressed="true">Desktop</button>
                    <button type="button" data-soo-viewport="phone" aria-pressed="false">Mobil</button>
                </div>
                <button type="button" class="soo-create" id="soo-create" disabled>Ny vare <span aria-hidden="true">+</span></button>
            </div>
        </header>
        <div class="soo-preview-note" role="note">
            <span class="soo-note-mark" aria-hidden="true">i</span>
            <span>Dette er en administrativ forhåndsvisning, ikke den eksterne nettbutikken. Velg en vare for å redigere den.</span>
            <span class="soo-live-mark" id="soo-live-mark">Henter forhåndsvisning</span>
        </div>
        <div id="soo-company-warning" class="soo-company-warning" hidden></div>
        <div id="soo-state" class="soo-state" role="status" aria-live="polite">
            <div class="soo-skeleton soo-skeleton-head"></div>
            <div class="soo-skeleton soo-skeleton-card"></div>
            <span>Laster varer …</span>
        </div>
        <div id="soo-preview" class="soo-preview" hidden></div>
    </div>
</section>
<script src="/assets/sales-online-offers.js?v=<?= filemtime(__DIR__ . '/../assets/sales-online-offers.js') ?>" defer></script>
