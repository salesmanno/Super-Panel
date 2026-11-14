<link rel="stylesheet" href="/assets/membership-benefits.css">
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
            <span><strong>Kun forhåndsvisning.</strong> Endringer er midlertidige og nullstilles når du går ut av Fordeler eller laster siden på nytt. Ingenting lagres.</span>
            <span class="mb-mode-label" id="mb-mode-label">Henter katalog</span>
        </div>
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
                    <div><span class="mb-kicker">PREVIEW · IKKE LAGRET</span><h3 id="mb-editor-title">Rediger fordel</h3></div>
                    <button type="button" class="mb-close" data-mb-close aria-label="Lukk">Lukk <span aria-hidden="true">×</span></button>
                </header>
                <p class="mb-editor-intro">Prøv hvordan tilbudet oppleves for en besøkende eller et medlem.</p>
                <label class="mb-field-label" for="mb-editor-plan">Medlemskapsplan</label>
                <select id="mb-editor-plan" class="mb-select mb-editor-plan"></select>
                <fieldset class="mb-mechanisms">
                    <legend>Fordelens pris</legend>
                    <label><input type="radio" name="mb-mechanism" value="own"> <span><strong>Egen medlemspris</strong><small>Sett prisen medlemmet betaler</small></span></label>
                    <label><input type="radio" name="mb-mechanism" value="percentage"> <span><strong>Prosentvis rabatt</strong><small>Trekk en prosent fra ordinær pris</small></span></label>
                    <label><input type="radio" name="mb-mechanism" value="fixed"> <span><strong>Fast avslag</strong><small>Trekk et beløp fra ordinær pris</small></span></label>
                    <label><input type="radio" name="mb-mechanism" value="included"> <span><strong>Inkludert</strong><small>Ingen ekstra kostnad for medlemmet</small></span></label>
                </fieldset>
                <div class="mb-value-field" id="mb-value-field">
                    <label class="mb-field-label" for="mb-value">Medlemspris</label>
                    <div class="mb-input-wrap"><input id="mb-value" type="number" min="0" step="0.01" inputmode="decimal"><span id="mb-value-suffix">kr</span></div>
                </div>
                <label class="mb-switch">
                    <input type="checkbox" id="mb-members-only">
                    <span class="mb-switch-track" aria-hidden="true"></span>
                    <span><strong>Kun for medlemmer</strong><small>Skjul pris og tilbud for vanlige besøkende</small></span>
                </label>
                <div class="mb-live-result" aria-live="polite"><span>Medlemspris i forhåndsvisningen</span><strong id="mb-live-price">–</strong></div>
                <footer class="mb-editor-foot">
                    <button type="button" class="mb-reset" id="mb-reset-item">Tilbakestill dette tilbudet</button>
                    <div><button type="button" class="mb-button mb-button-quiet" data-mb-close>Avbryt</button><button type="button" class="mb-button mb-button-primary" id="mb-apply">Vis i forhåndsvisning</button></div>
                </footer>
            </section>
        </div>
    </div>
</section>
<script src="/assets/membership-benefits.js" defer></script>
