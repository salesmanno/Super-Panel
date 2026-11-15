<link rel="stylesheet" href="/assets/membership-benefits.css?v=<?= filemtime(__DIR__ . '/../assets/membership-benefits.css') ?>">
<section id="membership-benefits" class="subtab-content" style="display:none" aria-labelledby="mb-heading">
    <div class="mb-workspace">
        <header class="mb-toolbar">
            <div>
                <span class="mb-kicker">MEDLEMSKAP · FORHÅNDSVISNING</span>
                <h2 id="mb-heading">Fordeler</h2>
                <p>Gjør medlemsfordelene tydelige i den visuelle tjenestekatalogen.</p>
            </div>
            <div class="mb-toolbar-actions">
                <label class="mb-plan-label" for="mb-plan-select">Vis som</label>
                <select id="mb-plan-select" class="mb-select" aria-label="Velg hvem som ser forhåndsvisningen">
                    <option value="">Vanlig besøkende</option>
                </select>
                <label class="mb-hidden-toggle"><input type="checkbox" id="mb-show-hidden"> Vis skjulte tilbud</label>
                <div class="mb-viewport" role="group" aria-label="Forhåndsvisningsstørrelse">
                    <button type="button" data-mb-viewport="desktop" aria-pressed="true">Desktop</button>
                    <button type="button" data-mb-viewport="phone" aria-pressed="false">Mobil</button>
                </div>
            </div>
        </header>
        <div class="mb-preview-note" role="note">
            <span class="mb-note-mark" aria-hidden="true">i</span>
            <span>Trykk på en tjeneste for å se, endre eller fjerne medlemsfordeler.</span>
            <span class="mb-mode-label" id="mb-mode-label">Henter katalog</span>
        </div>
        <aside id="mb-admin-overview" class="mb-admin-overview" aria-label="Administrativ oversikt over medlemsfordeler" hidden></aside>
        <div id="mb-state" class="mb-state" role="status" aria-live="polite">
            <div class="mb-skeleton mb-skeleton-title"></div>
            <div class="mb-skeleton mb-skeleton-row"></div>
            <span>Laster tjenestekatalog …</span>
        </div>
        <div id="mb-catalog" class="mb-catalog" hidden></div>
        <div id="mb-dialog" class="mb-dialog" hidden>
            <button type="button" class="mb-dialog-backdrop" data-mb-close aria-label="Lukk fordelsredigering"></button>
            <section class="mb-editor" role="dialog" aria-modal="true" aria-labelledby="mb-editor-title" tabindex="-1">
                <header class="mb-editor-head">
                    <div><span class="mb-kicker">TJENESTE · INNSTILLINGER</span><h3 id="mb-editor-title">Rediger tjeneste</h3></div>
                    <button type="button" class="mb-close" data-mb-close aria-label="Lukk">Lukk <span aria-hidden="true">×</span></button>
                </header>
                <p class="mb-editor-intro">Tilgang, medlemskontroll og planpriser. Hver innstilling er separat.</p>
                <p id="mb-save-feedback" class="mb-save-feedback" role="status" aria-live="polite" hidden></p>
                <section class="mb-service-controls" aria-label="Tilgang og medlemskontroll">
                    <article class="mb-service-control">
                        <div class="mb-control-copy"><span class="mb-control-scope">HELE TILBUDET</span><h4>Kun for medlemmer</h4><p id="mb-exclusive-help">Begrenser hvem som kan booke eller melde seg på.</p></div>
                        <label class="mb-control-switch" for="mb-member-exclusive"><span class="mb-control-switch-label">Kun for medlemmer</span><input id="mb-member-exclusive" type="checkbox" role="switch" aria-label="Kun for medlemmer" aria-describedby="mb-exclusive-help mb-exclusive-status"><span class="mb-toggle-track" aria-hidden="true"></span></label>
                        <div class="mb-control-state"><strong id="mb-exclusive-state">Henter status …</strong><span id="mb-exclusive-status" role="status" aria-live="polite"></span></div>
                    </article>
                    <article class="mb-service-control">
                        <div class="mb-control-copy"><span class="mb-control-scope">HELE TILBUDET</span><h4>Medlemsverifisering</h4><p id="mb-verification-help">Kontrollerer om medlemmet må bekrefte tilhørighet til klubben.</p></div>
                        <label class="mb-control-switch" for="mb-verification"><span class="mb-control-switch-label">Medlemsverifisering</span><input id="mb-verification" type="checkbox" role="switch" aria-label="Medlemsverifisering" aria-describedby="mb-verification-help mb-verification-state"><span class="mb-toggle-track" aria-hidden="true"></span></label>
                        <div class="mb-control-state"><strong id="mb-verification-state" role="status" aria-live="polite">Henter status …</strong></div>
                    </article>
                </section>
                <div class="mb-list-heading"><div><span class="mb-section-eyebrow">PRISER · PER MEDLEMSPLAN</span><h4>Medlemsfordeler</h4></div><span id="mb-plan-count"></span></div>
                <ul id="mb-editor-plan" class="mb-plan-list" aria-label="Lagrede fordeler per medlemsplan"></ul>
                <div id="mb-plan-panel" hidden>
                    <p id="mb-rule-status" class="mb-rule-status" aria-live="polite"></p>
                    <p id="mb-source-access" class="mb-editor-intro" hidden>Tilgangsvalget gjelder hele tilbudet og endrer ikke prisfordeler for denne planen.</p>
                    <p id="mb-legacy-warning" class="mb-legacy-warning" hidden>Dette tilbudet har en lagret fast medlemspris. Verdien er ikke et dagsavslag. Velg en ny fordel for å erstatte den.</p>
                    <div class="mb-benefit-form">
                        <div class="mb-type-field"><label class="mb-field-label" for="mb-benefit-type">Fordelstype</label>
                            <select id="mb-benefit-type" class="mb-benefit-select" aria-describedby="mb-input-help"></select></div>
                        <input id="mb-rental-mode" type="hidden" value="per_day">
                        <div class="mb-value-field" id="mb-value-field">
                            <label class="mb-field-label" for="mb-value">Verdi</label>
                            <div class="mb-input-wrap"><input id="mb-value" type="text" inputmode="decimal" autocomplete="off" aria-describedby="mb-input-help"><span id="mb-value-suffix">kr</span></div>
                            <small id="mb-input-help" class="mb-input-help"></small>
                        </div>
                        <div class="mb-live-result" aria-live="polite"><span>Pris for medlem</span><strong id="mb-live-price">–</strong></div>
                        <details id="mb-price-details" class="mb-price-details" hidden>
                            <summary>Prøv leiepris for en periode <span>(valgfritt)</span></summary>
                            <section id="mb-rental-rate-section" class="mb-rental-rate-section" hidden aria-label="Ordinære leiesatser">
                                <p class="mb-rates-caption">Ordinære satser</p><div id="mb-rental-rates" class="mb-rate-list"></div>
                            </section>
                            <div id="mb-rental-quote" hidden>
                                <div class="mb-date-fields">
                                    <label class="mb-field-label" for="mb-start-date">Fra<input id="mb-start-date" type="date" lang="nb-NO"></label>
                                    <label class="mb-field-label" for="mb-end-date">Til<input id="mb-end-date" type="date" lang="nb-NO"></label>
                                </div>
                                <p class="mb-input-help" id="mb-rental-period-help">Datoene teller med, også returdagen. Depositum og gebyrer er ikke med.</p>
                                <div id="mb-quote-result" class="mb-quote-result" aria-live="polite"></div>
                            </div>
                        </details>
                    </div>
                    <div class="mb-row-tools"><button type="button" class="mb-button" id="mb-reset-item">Avbryt</button><button type="button" class="mb-button mb-button-primary" id="mb-apply">Lagre fordel</button></div>
                </div>
                <footer class="mb-editor-foot"><button type="button" class="mb-button mb-button-quiet" data-mb-close>Lukk</button></footer>
            </section>
        </div>
    </div>
</section>
<script src="/assets/rental_request_pricing.js?v=<?= filemtime(__DIR__ . '/../assets/rental_request_pricing.js') ?>" defer></script>
<script src="/assets/membership-benefit-pricing.js?v=<?= filemtime(__DIR__ . '/../assets/membership-benefit-pricing.js') ?>" defer></script>
<script src="/assets/membership-benefits.js?v=<?= filemtime(__DIR__ . '/../assets/membership-benefits.js') ?>" defer></script>
