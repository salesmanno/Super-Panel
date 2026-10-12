<?php
require_once __DIR__ . '/../includes/rental_schemas.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['rental_lifecycle_csrf'])) {
    $_SESSION['rental_lifecycle_csrf'] = bin2hex(random_bytes(32));
}
$rentalLifecycleCsrf = (string) $_SESSION['rental_lifecycle_csrf'];
$schemas = getRentalSchemas();
?>

<style>
/* Rental / fjord signal — deliberately denser than the surrounding dashboard. */
#rental-module { --rental-ink:#17324d; --rental-muted:#60758a; --rental-line:#d8e3ea; --rental-paper:#f5f8fa; --rental-teal:#087f8c; --rental-coral:#e56f54; --rental-navy:#163b5c; }
#rental-module { color:var(--rental-ink); }
#rental-module .card { border:1px solid var(--rental-line); border-radius:12px; box-shadow:0 2px 8px rgba(22,59,92,.04); overflow:hidden; }
#rental-module .card-header { background:linear-gradient(90deg,#edf6f6,#f7fafb); border-bottom:1px solid var(--rental-line); }
#rental-module .card-title { letter-spacing:-.02em; font-weight:700; }
#rental-module .form-control,#rental-module .form-select { border-color:#cbd9e2; border-radius:9px; }
#rental-module .form-control:focus,#rental-module .form-select:focus { border-color:var(--rental-teal); box-shadow:0 0 0 .2rem rgba(8,127,140,.13); }
#rental-module .btn-primary { background:var(--rental-navy); border-color:var(--rental-navy); }
#rental-module .btn-primary:hover { background:#0f2e47; transform:translateY(-1px); }
#rental-module .btn-success { background:var(--rental-teal); border-color:var(--rental-teal); }
#rental-module .badge.bg-primary { background:var(--rental-teal)!important; }
#rental-module .rental-kicker { color:var(--rental-teal); font-size:.72rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
#rental-module .rental-toolbar { background:var(--rental-paper); border:1px solid var(--rental-line); border-radius:12px; padding:.7rem; }
#rental-module .rental-object-card { border-color:#dfe5e8; border-radius:10px; box-shadow:none; background:#fff; }
#rental-module .rental-object-card:hover { border-color:#cbd5da; }
#rental-module .rental-object-card[role="button"] { cursor:pointer; }
#rental-module .rental-object-card[role="button"]:focus-visible { outline:3px solid rgba(13,110,253,.35); outline-offset:2px; }
#rental-module .rental-object-card.is-expanded { border-color:var(--rental-teal); box-shadow:0 4px 16px rgba(8,127,140,.13); }
#rental-module .rental-object-expanded { border-top:1px solid var(--rental-line); background:var(--rental-paper); cursor:default; }
#rental-module .rental-object-expanded .form-control { background:#fff; }
#rental-module .rental-object-card.is-muted { opacity:.78; background:#fafafa; }
#rental-module .rental-object-card.is-rented { border-left:3px solid #7da08a; }
#rental-module .rental-object-image { height:150px; object-fit:cover; filter:saturate(.72); }
#rental-module .rental-object-card.is-muted .rental-object-image { filter:grayscale(55%); }
#rental-module .rental-category,#rental-module .rental-attribute { color:#53666f; background:#eef1f2; border:1px solid #dce2e5; font-weight:500; }
#rental-module .rental-price { color:var(--rental-ink); font-size:.92rem; font-weight:650; }
#rental-module .rental-object-actions .btn { color:#667780; border-color:#d5dde1; background:#fff; }
#rental-module .rental-object-actions .btn:hover { color:var(--rental-ink); border-color:#aebbc1; background:#f5f7f8; }
#rental-module .rental-object-actions .btn.rental-delete:hover { color:#a63f3f; border-color:#d9aaaa; background:#fff7f7; }
#rental-module .customer-field-row { background:#fff; border:1px solid var(--rental-line); border-radius:10px; padding:.65rem .8rem; transition:border-color .15s ease, background .15s ease; }
#rental-module .customer-field-row:has(.customer-selected:checked) { border-color:#79b9bd; background:#f1fbfb; }
#rental-module .metric-card { background:linear-gradient(135deg,#eef8f7,#f8fbfc); border-left:4px solid var(--rental-teal)!important; }
#rental-module .metric-value { color:var(--rental-navy); font-size:1.45rem; font-weight:800; }
#rental-module .table thead th { color:var(--rental-muted); font-size:.7rem; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
#rental-module .table tbody tr { transition:background .15s ease; }
#rental-module .table tbody tr:hover { background:#f1f8f8; }
#rental-module .rental-upcoming-list { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(290px,100%),1fr)); gap:.55rem; }
#rental-module .rental-upcoming-card { border:1px solid var(--rental-line); border-radius:10px; padding:.7rem .8rem; background:#fff; }
#rental-module .rental-upcoming-card:hover { border-color:#9ec9cc; box-shadow:0 3px 10px rgba(22,59,92,.07); }
#rental-module .rental-upcoming-card .rental-period { color:var(--rental-muted); font-size:.82rem; font-variant-numeric:tabular-nums; }
#rental-module .rental-upcoming-card .min-w-0 { min-width:0; }
#rental-module .rental-upcoming-card .rental-customer-links { display:flex; flex-wrap:wrap; gap:.25rem .9rem; }
#rental-module .rental-upcoming-card .rental-customer-links a { overflow-wrap:anywhere; }
#rental-module .rental-upcoming-card .rental-payment-short { font-size:.72rem; }
#rental-module .rental-customer-links a { color:var(--rental-teal); text-decoration:none; }
#rental-module .rental-customer-links a:hover { text-decoration:underline; }
#rental-module .rental-checkin-sent { color:#198754; }
#rental-module .rental-calendar-grid { min-width:0; width:100%; }
#rental-module #rentalCalendarHost { width:100%; overflow-x:visible; }
#rental-module .rental-calendar-day { min-height:72px; width:100%; padding:.65rem .25rem!important; font-size:.9rem; }
#rental-module .rental-calendar-bar { min-height:46px; line-height:19px!important; padding:4px 7px!important; }
#rental-module .rental-calendar-request { background:#ffd66b; border:1px solid #b8860b; color:#543d00; }
#rental-module .rental-calendar-request:hover { background:#ffcb4c; }
#rental-module .rental-calendar-request-key { display:inline-flex; align-items:center; gap:.35rem; color:#543d00; }
#rental-module .rental-calendar-request-key::before { content:""; width:.7rem; height:.7rem; border:1px solid #b8860b; border-radius:3px; background:#ffd66b; }
#rental-module .rental-upcoming-card[role="button"] { cursor:pointer; }
#rental-module .rental-upcoming-card[role="button"]:focus-visible { outline:3px solid rgba(8,127,140,.35); outline-offset:2px; }
#rental-module .rental-upcoming-card .rental-access-code { font-family:monospace; font-weight:700; letter-spacing:.04em; }
#rental-module .rental-unavailable { background:#fff3cd; border:1px solid #e6c86e; color:#664d03; }
#rental-module .rental-calendar-selectable { cursor:pointer; }
#rental-module .rental-calendar-selectable:hover { background:#e5f4f3!important; }
#rental-module .rental-unavailable-list { border-top:1px solid var(--rental-line); padding-top:.75rem; }
#rental-module .rental-unavailable-note { overflow-wrap:anywhere; }
#rental-module .save-busy { pointer-events:none; opacity:.68; }
@media(max-width:767.98px){#rental-module .btn-group{flex-wrap:wrap}#rental-module .table{min-width:760px}#rental-module .card-body{padding:1rem}.rental-mobile-scroll{overflow-x:auto}#rental-module .rental-calendar-day{min-height:64px;padding:.45rem .1rem!important;font-size:.8rem}#rental-module .rental-calendar-bar{font-size:.66rem!important;padding:3px 4px!important}}
</style>
<link rel="stylesheet" href="/assets/entity-form-fields.css">
<link rel="stylesheet" href="/assets/rental-dashboard.css">

<div class="module-content" id="rental-module">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div><div class="rental-kicker">Rental · kontrollsenter</div><h3 class="mb-1">Utleiedashboard</h3><p class="text-muted mb-0 small">Alt du trenger for å følge opp lokale utleier.</p></div>
    </div>
    <div id="rental-objects" class="subtab-content">
        <section class="rental-dashboard-summary" aria-label="Utleieoversikt">
            <button type="button" class="rental-summary-card rental-summary-requests" data-rental-open="requests">
                <span class="rental-summary-icon"><i class="bi bi-inbox"></i></span><span class="rental-summary-label">Forespørsler</span>
                <strong class="rental-summary-value" id="rentalMetricRequests">–</strong><span class="rental-summary-note">venter på behandling</span><i class="bi bi-arrow-up-right rental-summary-arrow"></i>
            </button>
            <button type="button" class="rental-summary-card rental-summary-upcoming" data-rental-open="upcoming">
                <span class="rental-summary-icon"><i class="bi bi-calendar2-week"></i></span><span class="rental-summary-label">Kommende utleie</span>
                <strong class="rental-summary-value" id="rentalMetricUpcoming">–</strong><span class="rental-summary-note">godkjente reservasjoner</span><i class="bi bi-arrow-up-right rental-summary-arrow"></i>
            </button>
            <div class="rental-summary-card rental-summary-objects">
                <span class="rental-summary-icon"><i class="bi bi-house"></i></span><span class="rental-summary-label">Utleieobjekter</span>
                <strong class="rental-summary-value" id="rentalMetricObjects">–</strong><span class="rental-summary-note">i bedriften</span>
            </div>
            <div class="rental-summary-card rental-summary-contracts">
                <span class="rental-summary-icon"><i class="bi bi-file-earmark-check"></i></span><span class="rental-summary-label">Aktive kontrakter</span>
                <strong class="rental-summary-value" id="rentalMetricContracts">–</strong><span class="rental-summary-note">pågående avtaler</span>
            </div>
        </section>
        <div class="rental-inventory card">
            <div class="card-body">
                <div class="rental-inventory-heading"><div><div class="rental-kicker">Inventar</div><h5 class="card-title mb-0">Utleieobjekter</h5><p class="small text-muted mb-0 mt-1">Velg et objektnavn for å tilpasse kundeopplysninger.</p></div><span class="badge rounded-pill text-bg-light border" id="rentalObjectCount">– objekter</span></div>
                <div class="rental-filter-row">
                    <label class="rental-search"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Søk utleieobjekter</span><input id="rentalObjectSearch" name="rental_inventory_query" type="search" class="form-control" placeholder="Søk etter navn eller kategori" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" data-lpignore="true" data-1p-ignore="true" data-bwignore="true"></label>
                    <select class="form-select" id="categoryFilter" onchange="filterByCategory()" aria-label="Filtrer kategori">
                        <option value="">Alle kategorier</option>
                        <?php foreach ($schemas as $key => $schema): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($schema['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="rentalObjectsContainer">
                    <div class="rental-object-skeleton" aria-label="Laster utleieobjekter">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-create" class="subtab-content" style="display: none;">
        <div id="createFormSection">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div><div class="rental-kicker">Nytt inventar</div><h5 class="card-title mb-1">Opprett utleieobjekt</h5><p class="small text-muted mb-0">Legg inn grunninfo og priser. Du kan finjustere detaljene senere.</p></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" onclick="closeRentalCreate()"><i class="bi bi-arrow-left me-1"></i>Til oversikten</button>
                    </div>
                    <form id="createRentalForm">
                        <div id="createFormFieldsContainer"></div>
                        <details class="rental-create-fields-details">
                            <summary>Tilpass kundeopplysninger</summary>
                            <div id="create-rental-fields-editor" class="px-3 pb-3"></div>
                        </details>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" onclick="showCreateSummary()">
                                <i class="bi bi-eye me-1"></i>Forhåndsvis og opprett
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="createSummarySection" style="display: none;">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Oppsummering - nytt utleieobjekt</h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="backToCreateForm()">
                            <i class="bi bi-arrow-left me-1"></i>Tilbake til skjema
                        </button>
                    </div>
                    <div id="createSummaryContent"></div>
                    <hr>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="confirmSaveNewRentalObject()">
                            <i class="bi bi-check-circle me-1"></i>Bekreft og opprett
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="backToCreateForm()">
                            <i class="bi bi-pencil me-1"></i>Rediger
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-contracts" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Utleiekontrakter</h5>
                    <div class="btn-group btn-group-sm" id="contractFilterBtns">
                        <button class="btn btn-primary" id="cf-btn-all" onclick="filterContracts('all')">Alle</button>
                        <button class="btn btn-outline-secondary" id="cf-btn-active" onclick="filterContracts('active')">Aktive</button>
                        <button class="btn btn-outline-secondary" id="cf-btn-completed" onclick="filterContracts('completed')">Avsluttede</button>
                        <button class="btn btn-outline-secondary" id="cf-btn-cancelled" onclick="filterContracts('cancelled')">Kansellerte</button>
                        <button class="btn btn-outline-secondary" id="cf-btn-draft" onclick="filterContracts('draft')">Utkast</button>
                    </div>
                </div>
                <div id="contractsListContainer">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Laster inn kontrakter...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-requests" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Forespurt utleie</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadRentalRequests()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Oppdater
                    </button>
                </div>
                <div id="rentalRequestsContainer">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Laster inn forespørsler...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-upcoming" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Kommende utleie</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadApprovedSchedule()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Oppdater
                    </button>
                </div>
                <div class="rental-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
                    <label for="rentalUpcomingMonth" class="small fw-semibold">Månedsoversikt</label>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rentalChangeMonth(-1)" aria-label="Forrige måned">‹</button>
                    <select id="rentalUpcomingMonth" class="form-select form-select-sm w-auto" onchange="renderRentalCalendar()" aria-label="Velg måned"></select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rentalChangeMonth(1)" aria-label="Neste måned">›</button>
                </div>
                <div id="upcomingContractsContainer" class="rental-upcoming-list mb-3" aria-live="polite"></div>
                <div id="rentalCalendarHost" aria-live="polite">
                    <div class="text-center text-muted py-3">Laster kalender...</div>
                </div>
                <div id="rentalUnavailableManager" class="rental-toolbar my-3">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <button type="button" id="rentalUnavailablePlanningHeader" class="btn text-start p-0 border-0 flex-grow-1" onclick="toggleRentalUnavailablePlanning()" aria-expanded="false" aria-controls="rentalUnavailableContent"><span class="rental-kicker d-block">Intern planlegging</span><span class="fw-semibold">Utilgjengelig periode</span></button>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="loadRentalUnavailable()" aria-label="Oppdater utilgjengelige perioder"><i class="bi bi-arrow-clockwise"></i></button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="rentalUnavailablePlanningToggle" aria-expanded="false" aria-controls="rentalUnavailableContent" onclick="toggleRentalUnavailablePlanning()"><span id="rentalUnavailablePlanningToggleLabel">Vis planlegging</span><i class="bi bi-chevron-down ms-2" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div id="rentalUnavailableContent" hidden>
                    <form id="rentalUnavailableForm" class="row g-2 align-items-end" onsubmit="saveRentalUnavailable(event)">
                        <input type="hidden" id="rentalUnavailableId">
                        <div class="col-12 col-lg-4"><label class="form-label small" for="rentalUnavailableObjects">Utleieobjekter</label><select id="rentalUnavailableObjects" class="form-select form-select-sm" multiple required size="3" aria-describedby="rentalUnavailableObjectHelp"></select><div id="rentalUnavailableObjectHelp" class="form-text">Velg ett eller flere objekter.</div></div>
                        <fieldset class="col-12 col-lg-8 border-0 p-0 m-0">
                            <legend class="visually-hidden">Velg dag eller periode</legend>
                            <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Type avstenging">
                                <input class="btn-check" type="radio" name="rentalUnavailableMode" id="rentalUnavailableModeDay" value="day" checked onchange="setRentalUnavailableMode('day')">
                                <label class="btn btn-outline-secondary" for="rentalUnavailableModeDay">Én dag</label>
                                <input class="btn-check" type="radio" name="rentalUnavailableMode" id="rentalUnavailableModePeriod" value="period" onchange="setRentalUnavailableMode('period')">
                                <label class="btn btn-outline-secondary" for="rentalUnavailableModePeriod">Periode</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small" for="rentalUnavailableStart">Fra dato</label><input id="rentalUnavailableStart" class="form-control form-control-sm rental-unavailable-date" type="date" required autocomplete="off"></div>
                                <div class="col-6" id="rentalUnavailableEndField" hidden><label class="form-label small" for="rentalUnavailableEnd">Til dato</label><input id="rentalUnavailableEnd" class="form-control form-control-sm rental-unavailable-date" type="date" required autocomplete="off"></div>
                            </div>
                        </fieldset>
                        <div class="col-12"><label class="form-label small" for="rentalUnavailableNotes">Stikkord / årsak <span class="text-muted">(valgfritt)</span></label><textarea id="rentalUnavailableNotes" class="form-control form-control-sm" rows="2" maxlength="500" aria-describedby="rentalUnavailableNotesHelp" placeholder="For eksempel: service, privat bruk eller rengjøring"></textarea><div id="rentalUnavailableNotesHelp" class="form-text">Maks 500 tegn. Vises i kalenderen og listen.</div></div>
                        <div class="col-12 d-flex gap-2"><button id="rentalUnavailableSubmit" class="btn btn-sm btn-primary" type="submit"><i class="bi bi-lock me-1"></i><span id="rentalUnavailableSubmitLabel">Marker utilgjengelig</span></button><button class="btn btn-sm btn-outline-secondary" type="button" onclick="resetRentalUnavailableForm()">Nullstill</button></div>
                    </form>
                    <div id="rentalUnavailableLoadState" class="small mt-2" role="status" aria-live="polite"></div>
                    <div class="rental-unavailable-list mt-3">
                        <button class="btn btn-sm btn-outline-secondary w-100 d-flex justify-content-between align-items-center" type="button" id="rentalUnavailableListToggle" aria-expanded="false" aria-controls="rentalUnavailableList" onclick="toggleRentalUnavailableList()">
                            <span>Aktive avstenginger <span class="badge rounded-pill text-bg-light border ms-1" id="rentalUnavailableCount">0</span></span>
                            <span class="small" id="rentalUnavailableListToggleLabel">Vis</span>
                        </button>
                        <div id="rentalUnavailableList" class="mt-2" hidden></div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-results" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3"><div><div class="rental-kicker">Innsikt</div><h5 class="card-title mb-1">Utleieresultat</h5><p class="text-muted small mb-0">Tall fra registrerte kontrakter, oppdatert fra systemet.</p></div><button class="btn btn-sm btn-outline-secondary" onclick="loadRentalResults()"><i class="bi bi-arrow-clockwise me-1"></i>Oppdater</button></div>
                <div id="rentalResultsState" class="text-muted small mb-3">Laster resultat…</div>
                <div id="rentalResultsMetrics" class="row g-3 mb-4"></div>
                <div id="rentalResultsTable"></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="rentalOverviewModal" tabindex="-1" aria-labelledby="rentalOverviewModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content rental-overview-modal">
                <div class="modal-header">
                    <div><div class="rental-kicker">Utleiedashboard</div><h5 class="modal-title" id="rentalOverviewModalTitle">Forespørsler</h5></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Lukk"></button>
                </div>
                <div class="modal-body" id="rentalOverviewModalBody"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for redigering av utleieobjekt -->
<div class="modal fade" id="rentalModal" tabindex="-1" aria-labelledby="rentalModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable rental-edit-dialog">
        <div class="modal-content rental-edit-modal">
            <div class="modal-header">
                <div class="rental-edit-heading">
                    <span class="rental-kicker">Objektinnstillinger</span>
                    <h5 class="modal-title" id="rentalModalTitle">Rediger utleieobjekt</h5>
                    <p id="rentalModalSubtitle" class="mb-0">Oppdater pris, adgang og praktiske detaljer.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Lukk redigering"></button>
            </div>
            <div class="modal-body">
                <form id="rentalForm">
                    <input type="hidden" id="rental_id" name="id">
                    <div id="formFieldsContainer"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" id="rentalSaveButton" onclick="saveRentalObject()">
                    <i class="bi bi-check-circle me-1"></i>Lagre
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Detaljvisning Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/rental_request_pricing.js"></script>
<script src="/assets/active-payment-methods.js"></script>
<script src="/assets/entity-form-fields.js"></script>
<script src="/assets/rental_request_workflow.js"></script>
<script src="/assets/rental_reservation_codes.js"></script>
<script src="/assets/rental-dashboard.js" defer></script>
<script>
const rentalSchemas = <?= json_encode($schemas) ?>;
const rentalLifecycleCsrf = <?= json_encode($rentalLifecycleCsrf) ?>;
let rentalCreateFieldsEditor = null;
let rentalCreateFieldsBaseline = '';
let rentalFormCsrfToken = '';
let rentalCreateSaving = false;
let rentalPendingRequests = new Map();
let rentalDecisionEmailEnabled = false;
let rentalRequestsLoadSequence = 0;
document.addEventListener('rental-request-decided', event => {
    rentalPendingRequests.delete(String(event.detail?.id ?? ''));
    document.dispatchEvent(new Event('rental-dashboard-update'));
});
let allRentalObjects = [];
let rentalFilter = '';
let allApprovedRentals = [];
let allPendingRentalRequests = [];
let allRentalUnavailable = [];
let calViewStart = null;
let rentalUnavailableToday = null;
let rentalUnavailableListExpanded = false;
let rentalUnavailableSaving = false;
const CAL_COLORS = [
    {bg:'#0d6efd',tx:'#fff'},{bg:'#198754',tx:'#fff'},{bg:'#dc3545',tx:'#fff'},
    {bg:'#fd7e14',tx:'#fff'},{bg:'#6610f2',tx:'#fff'},{bg:'#20c997',tx:'#fff'},
    {bg:'#d63384',tx:'#fff'},{bg:'#0dcaf0',tx:'#000'},{bg:'#6c757d',tx:'#fff'},
];

document.addEventListener('DOMContentLoaded', function() {
    setRentalUnavailableMode('day');
    loadRentalObjects();
    buildCreateForm();
    document.getElementById('createRentalForm')?.setAttribute('data-initialized', 'true');
    initializeRentalCreateFields();
    loadContracts('all');
    loadRentalRequests();
    loadApprovedSchedule();
    loadRentalUnavailable();
    loadRentalResults();
    const createForm = document.getElementById('createRentalForm');
    if (createForm) createForm.dataset.rentalBaseline = rentalFormSnapshot(createForm);
    const rentalTab = document.querySelector('[data-bs-target="#content-rental"]');
    rentalTab?.addEventListener('hide.bs.tab', event => {
        if (document.getElementById('rental-create')?.style.display !== 'block') return;
        if (!closeRentalCreate()) event.preventDefault();
    });
    document.getElementById('rentalModal')?.addEventListener('hide.bs.modal', event => {
        const form = document.getElementById('rentalForm');
        if (!form || !form.dataset.rentalBaseline || form.dataset.rentalBaseline === rentalFormSnapshot(form)) return;
        if (!window.confirm('Du har ulagrede endringer. Lukk uten å lagre?')) event.preventDefault();
    });
});

function rentalFormSnapshot(form) {
    return JSON.stringify([...form.querySelectorAll('input, select, textarea')].map(field => [
        field.name || field.id || field.className,
        field.type === 'checkbox' || field.type === 'radio' ? field.checked : field.value
    ]));
}

function openRentalCreate() {
    if (rentalCreateSaving) return;
    document.querySelectorAll('#rental-module .subtab-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById('rental-create');
    if (target) target.style.display = 'block';
    if (!document.getElementById('createRentalForm')?.dataset.initialized) {
        buildCreateForm();
        document.getElementById('createRentalForm').dataset.initialized = 'true';
        if (rentalCreateFieldsEditor) rentalCreateFieldsEditor.refresh().catch(error => console.error('Could not reload rental field defaults:', error));
    }
    document.getElementById('createFormSection')?.scrollIntoView({behavior:'smooth', block:'start'});
}

function closeRentalCreate() {
    const form = document.getElementById('createRentalForm');
    const formDirty = form && form.dataset.rentalBaseline !== rentalFormSnapshot(form);
    const fieldsDirty = rentalCreateFieldsEditor
        && rentalCreateFieldsBaseline !== JSON.stringify(rentalCreateFieldsEditor.getFields());
    if ((formDirty || fieldsDirty)
        && !window.confirm('Du har et utkast i opprettelsen. Gå tilbake til oversikten? Utkastet beholdes her.')) return false;
    const target = document.getElementById('rental-create');
    if (target) target.style.display = 'none';
    const objects = document.getElementById('rental-objects');
    if (objects) objects.style.display = 'block';
    return true;
}

async function ensureRentalFormToken(objectId) {
    const params = new URLSearchParams({entity_type:'rental_object', csrf_only:'1'});
    if (objectId !== null && objectId !== undefined && objectId !== '') params.set('entity_id', objectId);
    const response = await fetch(`/api/get_entity_form_fields.php?${params.toString()}`, {credentials:'same-origin'});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke hente sikkerhetstoken');
    rentalFormCsrfToken = result.csrf_token || window.entityFormCsrfToken || rentalFormCsrfToken;
    if (!rentalFormCsrfToken) throw new Error('Sikkerhetstoken mangler');
    return rentalFormCsrfToken;
}

async function initializeRentalCreateFields() {
    const host = document.getElementById('create-rental-fields-editor');
    if (!host) return;
    rentalCreateFieldsEditor = EntityFormFields.mount(host, {entityType: 'rental_object', entityId: null, createMode: true});
    try { await rentalCreateFieldsEditor.refresh(); }
    catch (error) { console.error('Could not load rental field defaults:', error); }
    rentalCreateFieldsBaseline = JSON.stringify(rentalCreateFieldsEditor.getFields());
}

function showRentalObjectsLoadError(message, warning) {
    const container = document.getElementById('rentalObjectsContainer');
    if (!container) return;
    const canPreservePanel = window.RentalDashboard?.isFieldPanelOpen();
    if (canPreservePanel) {
        let alert = container.querySelector('[data-rental-load-error]');
        if (!alert) {
            alert = document.createElement('div');
            alert.dataset.rentalLoadError = 'true';
            container.prepend(alert);
        }
        alert.className = `alert alert-${warning ? 'warning' : 'danger'} d-flex flex-wrap justify-content-between align-items-center gap-2`;
        alert.innerHTML = `<span>${escHtml(message)}</span><button class="btn btn-sm btn-outline-secondary" type="button" onclick="loadRentalObjects()">Prøv igjen</button>`;
        return;
    }
    container.innerHTML = `<div class="alert alert-${warning ? 'warning' : 'danger'} d-flex flex-wrap justify-content-between align-items-center gap-2"><span>${escHtml(message)}</span><button class="btn btn-sm btn-outline-secondary" type="button" onclick="loadRentalObjects()">Prøv igjen</button></div>`;
}

async function loadRentalObjects() {
    try {
        const response = await fetch('/api/get_rental_objects.php');
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            console.error('Invalid JSON from get_rental_objects:', text.substring(0, 200));
            throw new Error('Serveren returnerte ugyldig data');
        }
        
        if (data.success && data.objects) {
            allRentalObjects = data.objects;
            const countBadge = document.getElementById('rentalObjectCount');
            if (countBadge) countBadge.textContent = `${allRentalObjects.length} objekter`;
            allRentalObjects.forEach(obj => {
                if (typeof obj.images === 'string') {
                    try { obj.images = JSON.parse(obj.images); } catch(e) { obj.images = []; }
                }
                if (!Array.isArray(obj.images)) obj.images = [];
            });
            document.querySelectorAll('[data-rental-load-error]').forEach(error => error.remove());
            if (window.RentalDashboard?.isFieldPanelOpen()) window.RentalDashboard.deferObjectRender();
            else if (window.RentalDashboard) window.RentalDashboard.refreshObjects();
            else renderRentalObjects(allRentalObjects);
            renderRentalUnavailable();
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'objects',success:true}}));
            document.dispatchEvent(new Event('rental-dashboard-update'));
        } else {
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'objects',success:false}}));
            const countBadge = document.getElementById('rentalObjectCount');
            if (countBadge) countBadge.textContent = '– objekter';
            showRentalObjectsLoadError(data.message || 'Kunne ikke laste utleieobjektene.', true);
        }
    } catch (error) {
        document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'objects',success:false}}));
        console.error('Error loading rental objects:', error);
        const countBadge = document.getElementById('rentalObjectCount');
        if (countBadge) countBadge.textContent = '– objekter';
        showRentalObjectsLoadError(`Feil ved lasting av objekter. ${error.message || ''}`, false);
    }
}

async function filterByCategory() {
    if (window.RentalDashboard && !(await window.RentalDashboard.beforeFilter())) return;
    rentalFilter = document.getElementById('categoryFilter').value;
    if (window.RentalDashboard) window.RentalDashboard.refreshObjects();
    else {
        const filtered = rentalFilter ? allRentalObjects.filter(obj => obj.category === rentalFilter) : allRentalObjects;
        renderRentalObjects(filtered);
    }
}

function renderRentalObjects(objects) {
    const container = document.getElementById('rentalObjectsContainer');
    
    if (objects.length === 0) {
        container.innerHTML = `
            <div class="rental-object-list">
                <div class="rental-empty-state"><span class="rental-empty-icon"><i class="bi bi-inbox"></i></span>
                    <strong>${allRentalObjects.length ? 'Ingen objekter i dette utvalget' : 'Start med ditt første utleieobjekt'}</strong>
                    <span>${allRentalObjects.length ? 'Prøv et annet søk eller en annen kategori.' : 'Legg inn lokale, utstyr eller kjøretøy i oversikten.'}</span>
                </div>${renderRentalCreateTile()}
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="rental-object-list">
            ${objects.map(obj => renderObjectCard(obj)).join('')}
            ${renderRentalCreateTile()}
        </div>
    `;
}

function renderRentalCreateTile() {
    return `<button type="button" class="rental-create-tile" onclick="openRentalCreate()">
        <span class="rental-create-tile-mark"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
        <span><strong>Legg til utleieobjekt</strong><small>Opprett lokale, utstyr eller kjøretøy</small></span>
        <i class="bi bi-arrow-up-right ms-auto" aria-hidden="true"></i>
    </button>`;
}

function renderObjectCard(obj) {
    const objectToken = encodeURIComponent(String(obj.id));
    const schema = rentalSchemas[obj.category];
    const categoryLabel = schema ? schema.label : obj.category;
    const isAvailable = obj.is_available == 1 || obj.is_available === true;
    const isRented = !isAvailable && obj.display_status === 'rented';
    const cardStateClass = isRented ? ' is-rented is-muted' : (isAvailable ? '' : ' is-muted');
    
    let badges = obj.badges || [];
    const badgesHtml = badges.length > 0 
        ? badges.map(b => `<span class="badge rental-attribute me-1">${escHtml(b)}</span>`).join('')
        : '';
    
    const safeImages = (Array.isArray(obj.images) ? obj.images : []).map(img => typeof img === 'string' ? {url: img} : img).filter(img => validRentalImageUrl(img?.url));
    const primaryImage = safeImages.length
        ? safeImages.find(img => img.is_primary) || safeImages[0]
        : (validRentalImageUrl(obj.image_url) ? {url: obj.image_url} : null);
    const imageCount = safeImages.length || (primaryImage ? 1 : 0);
    
    const imageHtml = primaryImage 
        ? `<div class="position-relative">
             <img src="${escHtml(primaryImage.url)}" class="card-img-top rental-object-image" alt="${escHtml(obj.object_name)}">
             ${imageCount > 1 ? `<span class="position-absolute top-0 end-0 badge bg-dark bg-opacity-75 m-2"><i class="bi bi-images me-1"></i>${imageCount}</span>` : ''}
           </div>`
        : `<div class="card-img-top rental-object-image-placeholder d-flex align-items-center justify-content-center">
             <i class="bi bi-image fs-1 text-muted"></i>
           </div>`;
    
    const weekdayPriceState = parseRentalWeekdayPrices(obj.weekday_prices ?? obj.object_weekday_prices);
    const weekdayFlag = obj.weekday_prices_enabled;
    const hasWeekdayPrices = rentalObjectUsesWeekdayPrices(obj, weekdayPriceState);
    const invalidWeekdayFlag = weekdayFlag !== null && weekdayFlag !== undefined && weekdayFlag !== ''
        && !['0','1'].includes(String(weekdayFlag));
    const priceFormat = value => Number(value).toLocaleString('nb-NO',{maximumFractionDigits:2});
    const weekdayPriceDisplay = hasWeekdayPrices
        ? (weekdayPriceState.valid && weekdayPriceState.present
            ? `<span class="rental-weekday-price">${rentalWeekdays.map(day => `${day.short} ${Object.prototype.hasOwnProperty.call(weekdayPriceState.prices, day.key) && weekdayPriceState.prices[day.key] !== '' ? priceFormat(weekdayPriceState.prices[day.key]) : '–'}`).join(' · ')} NOK</span>`
            : '<span class="rental-weekday-price rental-weekday-price-error">Ukedagspriser må kontrolleres</span>')
        : '';
    const weekdayFlagWarning = invalidWeekdayFlag
        ? '<span class="rental-weekday-price rental-weekday-price-error">Prisvalg må kontrolleres</span>'
        : '';
    let weekendPrice = '';
    if (obj.saturday_price > 0 || obj.sunday_price > 0) {
        const parts = [];
        if (obj.saturday_price > 0) parts.push(`Lørdag ${parseFloat(obj.saturday_price).toLocaleString('nb-NO',{maximumFractionDigits:0})}`);
        if (obj.sunday_price > 0) parts.push(`Søndag ${parseFloat(obj.sunday_price).toLocaleString('nb-NO',{maximumFractionDigits:0})}`);
        weekendPrice = `<span class="rental-weekend-price">${parts.join(' · ')} NOK/dag</span>`;
    } else if (obj.weekend_price > 0) {
        weekendPrice = `<span class="rental-weekend-price">Helg ${parseFloat(obj.weekend_price).toLocaleString('nb-NO',{maximumFractionDigits:0})} NOK/dag</span>`;
    }
    
    const statusLabel = isRented
        ? '<span class="badge rental-state-badge rental-state-rented ms-2">Utleid</span>'
        : (isAvailable
            ? '<span class="badge rental-state-badge rental-state-available ms-2">Tilgjengelig</span>'
            : '<span class="badge rental-state-badge rental-state-inactive ms-2">Skjult</span>');
    
    const dailyPrice = parseFloat(obj.rental_price) || 0;
    if (hasWeekdayPrices && !weekendPrice) {
        weekendPrice = `<span class="rental-weekend-price">Helg fra ${priceFormat(dailyPrice)} NOK/dag</span>`;
    }
    const defaultDeposit = parseFloat(obj.deposit_amount) || 0;

    return `
        <article class="rental-object-entry" id="rental-card-${escAttr(obj.id)}">
            <div class="card h-100 rental-object-card${cardStateClass}">
                ${imageHtml}
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <button type="button" class="rental-object-name" data-rental-fields="${escAttr(obj.id)}" aria-expanded="false" aria-controls="rental-inline-${escAttr(obj.id)}">${escHtml(obj.object_name)}</button>
                            <span class="rental-card-status">${statusLabel}</span>
                        </div>
                        <div class="dropdown rental-card-menu" data-rental-object-id="${escAttr(obj.id)}" onclick="event.stopPropagation()">
                            <button class="btn rental-menu-trigger" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Flere valg for ${escAttr(obj.object_name)}">
                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" data-rental-menu-owner="${escAttr(obj.id)}">
                                <li><button class="dropdown-item" type="button" onclick="editRentalObject(decodeURIComponent('${objectToken}'))"><i class="bi bi-pencil me-2"></i>Rediger</button></li>
                                <li><button class="dropdown-item" type="button" data-rental-visibility="${escAttr(obj.id)}" data-rental-visible="${isAvailable ? '1' : '0'}" onclick="toggleAvailability(decodeURIComponent('${objectToken}'), this.dataset.rentalVisible !== '1')"><i class="bi bi-eye-slash me-2"></i>${isAvailable ? 'Skjul' : 'Vis igjen'}</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item text-danger" type="button" onclick="deleteRentalObject(decodeURIComponent('${objectToken}'))"><i class="bi bi-trash me-2"></i>Slett</button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 rental-object-category">
                        <span class="badge rental-category">${escHtml(categoryLabel)}</span>
                    </div>
                    <div class="mb-2">
                        ${badgesHtml}
                    </div>
                        ${obj.description ? `<p class="card-text text-muted small">${escHtml(obj.description.substring(0, 100))}${obj.description.length > 100 ? '...' : ''}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-3 rental-object-footer">
                        <div class="rental-price-block">
                            <strong class="rental-price">${hasWeekdayPrices ? 'Ukedagspriser' : `${dailyPrice.toLocaleString('nb-NO',{maximumFractionDigits:0})} NOK/dag`}</strong>
            ${weekdayPriceDisplay || weekdayFlagWarning}
                            ${weekendPrice}
                        </div>
                        <div class="btn-group btn-group-sm rental-object-actions">
                            ${obj.category === 'venue' ? `<button class="btn rental-access-control" type="button" aria-expanded="false" aria-controls="rental-expanded-${escAttr(obj.id)}" onclick="toggleRentalObjectCard(decodeURIComponent('${objectToken}'))" title="Tilgangskode for lokalet"><i class="bi bi-key me-1"></i>Tilgangskode</button><span class="rental-card-access-value" data-rental-access-value>Aktuell <code>${escHtml(obj.type_data?.access_code || 'Ikke satt')}</code></span>` : ''}
                            <button class="btn rental-contract-control" type="button" onclick="event.stopPropagation(); toggleContractForm(decodeURIComponent('${objectToken}'))" title="Opprett kontrakt" id="contract-btn-${escAttr(obj.id)}"><i class="bi bi-file-earmark-text me-1"></i>Kontrakt</button>
                        </div>
                    </div>
                </div>
                <div id="rental-expanded-${escAttr(obj.id)}" class="rental-object-expanded p-3" hidden onclick="event.stopPropagation()"></div>
            </div>
            <div id="rental-inline-${escAttr(obj.id)}" class="rental-inline-fields" hidden></div>
            <div id="contract-panel-${obj.id}" class="card mt-2 border-success" style="display: none;">
                <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 text-success"><i class="bi bi-file-earmark-text me-1"></i>Kontrakter – ${escHtml(obj.object_name)}</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleContractForm(decodeURIComponent('${objectToken}'))"><i class="bi bi-x"></i></button>
                </div>
                <div class="card-body p-3">
                    <div id="obj-contracts-${obj.id}" class="mb-3 small"></div>
                    <h6 class="text-muted mb-2 small text-uppercase">Opprett ny kontrakt</h6>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label mb-1 small">Kunde</label>
                            <div class="position-relative">
                                <input type="text" class="form-control form-control-sm" id="cf-customer-name-${obj.id}"
                                       placeholder="Søk etter kunde (navn / e-post)..." autocomplete="off"
                                       oninput="searchContractCustomer('${obj.id}', this.value)">
                                <input type="hidden" id="cf-customer-id-${obj.id}">
                                <div id="cf-customer-dropdown-${obj.id}" class="dropdown-menu p-0" style="display:none; position:absolute; z-index:1050; width:100%; max-height:180px; overflow-y:auto;"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1 small">Fra dato <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="cf-start-${obj.id}"
                                   onchange="calcContractAmount('${obj.id}', ${dailyPrice})">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1 small">Til dato <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="cf-end-${obj.id}"
                                   onchange="calcContractAmount('${obj.id}', ${dailyPrice})">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1 small">Dager</label>
                            <input type="number" class="form-control form-control-sm" id="cf-days-${obj.id}" readonly placeholder="–">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1 small">Totalpris (NOK) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="cf-amount-${obj.id}" step="0.01" min="0" placeholder="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1 small">Depositum (NOK)</label>
                            <input type="number" class="form-control form-control-sm" id="cf-deposit-${obj.id}" step="0.01" min="0" value="${defaultDeposit}">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1 small">Betalingsmetode</label>
                            <select class="form-select form-select-sm" id="cf-payment-method-${obj.id}">
                                <option value="">Laster aktiverte betalingsmåter…</option>
                            </select>
                            <div id="cf-payment-method-state-${obj.id}" class="form-text" aria-live="polite"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1 small">Status</label>
                            <select class="form-select form-select-sm" id="cf-status-${obj.id}">
                                <option value="active" selected>Aktiv (marker objekt som utleid)</option>
                                <option value="draft">Utkast</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1 small">Notater</label>
                            <textarea class="form-control form-control-sm" id="cf-notes-${obj.id}" rows="2" placeholder="Eventuelle merknader..."></textarea>
                        </div>
                        <div class="col-12 mt-1">
                            <button class="btn btn-success btn-sm" onclick="submitContract(decodeURIComponent('${objectToken}'))">
                                <i class="bi bi-check-circle me-1"></i>Lagre kontrakt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    `;
}

function toggleRentalObjectCard(objectId) {
    const wrapper = document.getElementById(`rental-card-${objectId}`);
    const card = wrapper?.querySelector('.rental-object-card');
    const panel = document.getElementById(`rental-expanded-${objectId}`);
    const obj = allRentalObjects.find(o => String(o.id) === String(objectId));
    if (!card || !panel || !obj) return;

    const opening = panel.hidden;
    panel.hidden = !opening;
    card.classList.toggle('is-expanded', opening);
    card.querySelector('.rental-access-control')?.setAttribute('aria-expanded', String(opening));
    if (!opening) {
        panel.replaceChildren();
        return;
    }

    if (obj.category === 'venue') {
        panel.innerHTML = `
            <form onsubmit="saveRentalCardAccessCode(event, decodeURIComponent('${encodeURIComponent(String(objectId))}'))">
                <label class="form-label fw-semibold mb-1" for="rental-code-${escAttr(objectId)}">Tilgangskode</label>
                <p class="small text-muted mb-2">Kode til utleielokalet, 1–6 bokstaver eller tall.</p>
                <input type="text" class="form-control form-control-sm" id="rental-code-${escAttr(objectId)}"
                       autocomplete="off" maxlength="6" pattern="[A-Za-z0-9]{1,6}" disabled>
                <div class="small mt-2" role="status" aria-live="polite" data-code-status>Laster kode…</div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-sm btn-primary" disabled>Lagre kode</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editRentalObject(decodeURIComponent('${encodeURIComponent(String(objectId))}'))">Grunnopplysninger</button>
                </div>
            </form>`;
        loadRentalCardAccessCode(objectId, panel);
    } else {
        panel.innerHTML = `<p class="small text-muted mb-2">Administrer opplysninger og kundeinformasjon for dette objektet.</p>
            <div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-sm btn-primary" onclick="editRentalObject(decodeURIComponent('${encodeURIComponent(String(objectId))}'))">Grunnopplysninger</button>
            </div>`;
    }
}

async function loadRentalCardAccessCode(objectId, panel) {
    const input = panel.querySelector('input');
    const status = panel.querySelector('[data-code-status]');
    const saveButton = panel.querySelector('button[type="submit"]');
    try {
        const response = await fetch(`/api/get_rental_objects.php?id=${encodeURIComponent(objectId)}`);
        const data = await response.json();
        if (!response.ok || !data.success || data.object?.category !== 'venue') {
            throw new Error(data.message || 'Kunne ikke hente tilgangskoden');
        }
        if (!panel.isConnected || panel.hidden || panel.querySelector('input') !== input) return;
        input.value = data.object.type_data?.access_code || '';
        updateRentalCardAccessCodeDisplay(objectId, input.value);
        input.disabled = false;
        saveButton.disabled = false;
        status.textContent = 'La feltet stå tomt for å fjerne koden.';
        const obj = allRentalObjects.find(o => String(o.id) === String(objectId));
        if (obj) obj.type_data = {...obj.type_data, ...data.object.type_data};
    } catch (error) {
        if (!panel.isConnected || panel.hidden || panel.querySelector('input') !== input) return;
        status.textContent = error.message || 'Kunne ikke hente tilgangskoden';
        status.classList.add('text-danger');
    }
}

async function saveRentalCardAccessCode(event, objectId) {
    event.preventDefault();
    event.stopPropagation();
    const form = event.currentTarget;
    const input = form.querySelector('input');
    const saveButton = form.querySelector('button[type="submit"]');
    const status = form.querySelector('[data-code-status]');
    if (saveButton.disabled || !input.reportValidity()) return;
    const code = input.value.trim();
    if (code === '0000' || (code !== '' && !/^[A-Za-z0-9]{1,6}$/.test(code))) {
        status.textContent = 'Bruk 1–6 bokstaver eller tall. Koden 0000 kan ikke brukes.';
        status.classList.add('text-danger');
        return;
    }
    saveButton.disabled = true;
    input.disabled = true;
    status.classList.remove('text-danger');
    status.textContent = 'Lagrer…';
    try {
        const csrfToken = await ensureRentalFormToken(objectId);
        const response = await fetch('/api/update_rental_object.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({id: objectId, category: 'venue', type_data: {access_code: code}, csrf_token: csrfToken})
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Kunne ikke lagre koden');
        if (!form.isConnected) return;
        status.textContent = 'Koden er lagret.';
        const obj = allRentalObjects.find(o => String(o.id) === String(objectId));
        if (obj) obj.type_data = {...obj.type_data, access_code: code || null};
        input.value = code;
        updateRentalCardAccessCodeDisplay(objectId, code);
    } catch (error) {
        if (!form.isConnected) return;
        status.textContent = error.message || 'Kunne ikke lagre koden';
        status.classList.add('text-danger');
    } finally {
        if (form.isConnected) {
            input.disabled = false;
            saveButton.disabled = false;
        }
    }
}

function updateRentalCardAccessCodeDisplay(objectId, code) {
    const display = document.querySelector(`#rental-card-${CSS.escape(String(objectId))} [data-rental-access-value] code`);
    if (display) display.textContent = code || 'Ikke satt';
}

function buildCreateForm(category = '', existingData = {}) {
    buildFormFields('createFormFieldsContainer', category, existingData);
}

function buildIntegratedForm(category = '', existingData = {}) {
    buildFormFields('formFieldsContainer', category, existingData);
    organizeRentalEditForm(document.getElementById('formFieldsContainer'), category, existingData);
}

const rentalWeekdays = [
    {key:'monday', label:'Mandag', short:'Man'},
    {key:'tuesday', label:'Tirsdag', short:'Tir'},
    {key:'wednesday', label:'Onsdag', short:'Ons'},
    {key:'thursday', label:'Torsdag', short:'Tor'},
    {key:'friday', label:'Fredag', short:'Fre'}
];

function parseRentalWeekdayPrices(raw) {
    if (raw === null || raw === undefined || raw === '') return {valid:true, present:false, prices:{}};
    let prices = raw;
    if (typeof raw === 'string') {
        try {
            prices = JSON.parse(raw);
        } catch (error) {
            return {valid:false, present:true, prices:{}, message:'Ukedagsprisene kunne ikke leses. Velg prisform på nytt eller fyll inn alle fem ukedagssatsene.'};
        }
    }
    if (prices === null) return {valid:true, present:false, prices:{}};
    if (typeof prices !== 'object' || Array.isArray(prices)) {
        return {valid:false, present:true, prices:{}, message:'Ukedagsprisene har ugyldig format. Velg prisform på nytt eller fyll inn alle fem ukedagssatsene.'};
    }
    const allowed = new Set(rentalWeekdays.map(day => day.key));
    if (Object.keys(prices).some(key => !allowed.has(key))) {
        return {valid:false, present:true, prices, message:'Ukedagsprisene inneholder ukjente dager. Velg prisform på nytt eller fyll inn alle fem ukedagssatsene.'};
    }
    return {valid:true, present:true, prices};
}

function rentalObjectUsesWeekdayPrices(object, parsed = parseRentalWeekdayPrices(object?.weekday_prices ?? object?.object_weekday_prices)) {
    const flag = object?.weekday_prices_enabled ?? object?.object_weekday_prices_enabled;
    if (flag === null || flag === undefined || flag === '') return parsed.present;
    return String(flag) === '1';
}

function rentalWeekdayMode(form, containerId) {
    const toggle = form.querySelector(`[name="weekday_prices_enabled"]`);
    if (toggle) return toggle.checked ? 'weekdays' : 'flat';
    if (form.dataset.weekdayPricesInvalid === 'true') return '';
    return document.getElementById(containerId)?.dataset.weekdayPricingMode || 'flat';
}

function rentalWeekdayPricesFromForm(form, containerId) {
    return Object.fromEntries(rentalWeekdays.map(day => {
        const value = form.querySelector(`[name="weekday_prices[${day.key}]"]`)?.value ?? '';
        return [day.key, value === '' ? '' : Number(value)];
    }));
}

function rentalWeekdayFormState(form, containerId) {
    const invalid = form.dataset.weekdayPricesInvalid === 'true';
    const mode = rentalWeekdayMode(form, containerId) || (invalid ? '' : 'flat');
    const prices = rentalWeekdayPricesFromForm(form, containerId);
    const hasCompleteMap = rentalWeekdays.every(day => {
        const value = prices[day.key];
        return value !== '' && Number.isFinite(value) && value >= 0;
    });
    return {
        weekday_prices: mode === 'weekdays' || hasCompleteMap ? prices : undefined,
        weekday_prices_enabled: mode === 'weekdays' ? 1 : 0,
        _weekdayPricingMode: mode,
        _weekdayPricesDraft: prices,
        _weekdayPricesRaw: invalid ? (form.dataset.weekdayPricesRaw || '') : null,
        _weekdayPricesInvalid: invalid
    };
}

function rentalPriceForPayload(form, rawValue, mode) {
    if (rawValue !== null && rawValue !== undefined && rawValue !== '') return Number(rawValue);
    if (mode === 'weekdays' && form._rentalFallbackRentalPrice !== null && form._rentalFallbackRentalPrice !== undefined && form._rentalFallbackRentalPrice !== '') {
        return Number(form._rentalFallbackRentalPrice);
    }
    return null;
}

function updateRentalWeekdayPricing(containerId, mode, clearParseError = true) {
    const container = document.getElementById(containerId);
    const form = container?.closest('form');
    if (!container || !form) return;
    container.dataset.weekdayPricingMode = mode;
    const rate = container.querySelector('[name="rental_price"]');
    const rateLabel = container.querySelector('[data-rental-base-rate-label]');
    const rateHelp = container.querySelector('[data-rental-base-rate-help]');
    const weekdayFields = container.querySelector(`#${containerId}_weekdayPrices`);
    const weekdayNote = container.querySelector(`#${containerId}_weekdayPriceNote`);
    const weekdayInputs = [...container.querySelectorAll('[name^="weekday_prices["]')];
    const toggle = container.querySelector('[name="weekday_prices_enabled"]');
    const isWeekdayMode = mode === 'weekdays';
    if (toggle) toggle.checked = isWeekdayMode;
    if (rate) rate.required = !isWeekdayMode || containerId === 'createFormFieldsContainer';
    weekdayInputs.forEach(input => input.required = isWeekdayMode);
    if (rateLabel) rateLabel.textContent = isWeekdayMode ? 'Grunnpris for helg (NOK)' : 'Flat dagspris (NOK)';
    if (rateHelp) rateHelp.textContent = isWeekdayMode
        ? (containerId === 'createFormFieldsContainer'
            ? 'Grunnpris for lørdag og søndag når egne helgesatser ikke er satt. Legg inn en sats for hver ukedag nedenfor.'
            : 'Brukes lørdag og søndag når egne helgesatser ikke er satt. Mandag–fredag prises med satsene nedenfor.')
        : 'Samme grunnpris for alle dager. Egne helgepriser kan fortsatt overstyre helgen.';
    if (weekdayFields) weekdayFields.hidden = !isWeekdayMode;
    if (weekdayNote) weekdayNote.hidden = !isWeekdayMode;
    const error = container.querySelector('[data-weekday-pricing-error]');
    if (clearParseError && error) {
        if (isWeekdayMode) {
            error.hidden = true;
            form.dataset.weekdayPricesInvalid = 'false';
            delete form.dataset.weekdayPricesRaw;
        } else if (form.dataset.weekdayPricesInvalid === 'true') {
            error.hidden = true;
        }
    }
}

function setupRentalWeekdayPricing(containerId, existingData, parsedState) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const form = container.closest('form');
    const source = existingData._weekdayPricesInvalid
        ? existingData._weekdayPricesRaw
        : (existingData._weekdayPricesDraft ?? existingData.weekday_prices ?? existingData.object_weekday_prices);
    const parsed = parsedState || parseRentalWeekdayPrices(source);
    const preservedMode = ['flat','weekdays'].includes(existingData._weekdayPricingMode)
        ? existingData._weekdayPricingMode : null;
    const hasStoredFlag = existingData.weekday_prices_enabled !== null
        && existingData.weekday_prices_enabled !== undefined
        && existingData.weekday_prices_enabled !== '';
    const mode = preservedMode || (hasStoredFlag
        ? (Number(existingData.weekday_prices_enabled) === 1 ? 'weekdays' : 'flat')
        : (parsed.present ? 'weekdays' : 'flat'));
    const map = parsed.prices || {};
    rentalWeekdays.forEach(day => {
        const input = container.querySelector(`[name="weekday_prices[${day.key}]"]`);
        if (input && Object.prototype.hasOwnProperty.call(map, day.key)) {
            input.value = map[day.key] === null || map[day.key] === undefined ? '' : String(map[day.key]);
        }
    });
    const toggle = container.querySelector('[name="weekday_prices_enabled"]');
    if (toggle) toggle.checked = mode === 'weekdays';
    const error = container.querySelector('[data-weekday-pricing-error]');
    if (!parsed.valid) {
        form.dataset.weekdayPricesRaw = typeof source === 'string' ? source : JSON.stringify(source);
        if (error) {
            error.textContent = parsed.message;
            error.hidden = false;
        }
        form.dataset.weekdayPricesInvalid = 'true';
    } else {
        delete form.dataset.weekdayPricesRaw;
        form.dataset.weekdayPricesInvalid = 'false';
    }
    updateRentalWeekdayPricing(containerId, mode, false);
    if (!parsed.valid) {
        if (mode === 'weekdays' && error) error.hidden = false;
    }
    toggle?.addEventListener('change', () =>
        updateRentalWeekdayPricing(containerId, toggle.checked ? 'weekdays' : 'flat', true));
}

function buildFormFields(containerId, category = '', existingData = {}) {
    const container = document.getElementById(containerId);
    const isEditForm = containerId === 'formFieldsContainer';
    const fieldValue = value => isEditForm && (value === 0 || value === '0') ? '0' : (value || '');
    const weekdaySource = existingData._weekdayPricesInvalid
        ? existingData._weekdayPricesRaw
        : (existingData._weekdayPricesDraft ?? existingData.weekday_prices ?? existingData.object_weekday_prices);
    const weekdayState = parseRentalWeekdayPrices(weekdaySource);
    const hasStoredWeekdayFlag = existingData.weekday_prices_enabled !== null
        && existingData.weekday_prices_enabled !== undefined
        && existingData.weekday_prices_enabled !== '';
    const initialWeekdayMode = ['flat','weekdays'].includes(existingData._weekdayPricingMode)
        ? existingData._weekdayPricingMode
        : (hasStoredWeekdayFlag
            ? (Number(existingData.weekday_prices_enabled) === 1 ? 'weekdays' : 'flat')
            : (weekdayState.present ? 'weekdays' : 'flat'));
    const formId = containerId === 'createFormFieldsContainer' ? 'createRentalForm' : 'rentalForm';
    const form = document.getElementById(formId);
    const cached = existingData._categoryDrafts?.[category];
    if (cached) existingData = {...existingData, type_data: {...existingData.type_data, ...cached.type_data}};
    form._rentalCategoryDrafts = existingData._categoryDrafts || {};
    form._rentalMarketPrice = existingData.market_price ?? '';
    form._rentalFallbackRentalPrice = existingData.rental_price !== '' && existingData.rental_price !== null && existingData.rental_price !== undefined
        ? existingData.rental_price : (existingData._rentalFallbackRentalPrice ?? null);
    container.dataset.category = category;
    const rebuildFn = containerId === 'createFormFieldsContainer' ? 'buildCreateForm' : 'buildIntegratedForm';
    const selectId = containerId === 'createFormFieldsContainer' ? 'createCategorySelect' : 'categorySelect';
    const checkboxId = containerId === 'createFormFieldsContainer' ? 'createIsAvailable' : 'isAvailable';
    
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Objektnavn <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="object_name" value="${escAttr(existingData.object_name || '')}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kategori <span class="text-danger">*</span></label>
    `;
    if (containerId === 'formFieldsContainer') {
        html += `<div class="rental-locked-category"><span class="badge rental-category">${escHtml(rentalSchemas[category]?.label || category)}</span><span class="small text-muted">Kategorien kan ikke endres etter opprettelse.</span></div><input type="hidden" name="category" value="${escAttr(category)}">`;
    } else {
        html += `<select class="form-select" name="category" id="${selectId}" required onchange="${rebuildFn}(this.value, getFormData('${formId}'))"><option value="">Velg kategori</option>`;
        Object.entries(rentalSchemas).forEach(([key, schema]) => {
            html += `<option value="${key}" ${key === category ? 'selected' : ''}>${escHtml(schema.label)}</option>`;
        });
        html += '</select>';
    }
    html += `
            </div>
        </div>
        
        <h6 class="text-muted mt-3 mb-2" data-rental-pricing-heading><i class="bi bi-cash-stack me-1"></i>Grunnpris og helgepris</h6>
        <div class="card bg-light mb-3">
            <div class="card-body py-2 rental-pricing-card-body">
                <fieldset class="rental-weekday-mode-selector">
                    <legend class="form-label">Ukedagspriser</legend>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            name="weekday_prices_enabled" id="${containerId}_weekdayPricesEnabled"
                            value="1" ${initialWeekdayMode === 'weekdays' ? 'checked' : ''}
                            aria-controls="${containerId}_weekdayPrices"
                            >
                        <label class="form-check-label" for="${containerId}_weekdayPricesEnabled">Bruk egne priser mandag–fredag</label>
                    </div>
                    <small class="form-text text-muted">Av: grunnprisen gjelder mandag–fredag. På: satsene nedenfor brukes.</small>
                </fieldset>
                <div class="row g-3 rental-base-price-fields">
                    <div class="col-12 col-md-6 mb-2 rental-base-rate-field">
                        <label class="form-label mb-1" data-rental-base-rate-label for="${containerId}_rentalPrice">Flat dagspris (NOK)</label>
                        <input type="number" class="form-control form-control-sm" id="${containerId}_rentalPrice" name="rental_price" step="0.01" min="0" value="${escAttr(String(existingData.rental_price ?? ''))}">
                        <small class="form-text text-muted" data-rental-base-rate-help></small>
                    </div>
                    <div class="col-12 col-md-6 mb-2 rental-deposit-field">
                        <label class="form-label mb-1">Depositum (NOK)</label>
                        <input type="number" class="form-control form-control-sm" name="deposit_amount" step="0.01" min="0" value="${escAttr(fieldValue(existingData.deposit_amount ?? 0))}">
                    </div>
                    ${category !== 'venue' ? `<div class="col-12 col-md-6 mb-2 rental-market-field">
                        <label class="form-label mb-1">Markedspris (NOK)</label>
                        <input type="number" class="form-control form-control-sm" name="market_price" step="0.01" min="0" value="${escAttr(fieldValue(existingData.market_price ?? ''))}">
                        <small class="text-muted">Veiledende/innkjøpspris</small>
                    </div>` : ''}
                </div>
                <div id="${containerId}_weekdayPrices" class="row g-2 rental-weekday-prices" hidden>
                    ${rentalWeekdays.map(day => {
                        const value = weekdayState.prices?.[day.key] ?? '';
                        const displayValue = value === 0 || value === '0' ? '0' : value;
                        return `<div class="col-6 col-md rental-weekday-rate">
                            <label class="form-label mb-1" for="${containerId}_weekday_${day.key}">${day.label} <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm"><input type="number" class="form-control" id="${containerId}_weekday_${day.key}" name="weekday_prices[${day.key}]" value="${escAttr(displayValue)}" min="0" step="0.01" inputmode="decimal" aria-label="${day.label} pris"><span class="input-group-text">NOK</span></div>
                        </div>`;
                    }).join('')}
                </div>
                <small class="form-text text-muted rental-weekday-prices-note" id="${containerId}_weekdayPriceNote">Alle fem ukedager må ha en sats, også når en dag er gratis.</small>
                <div class="alert alert-warning rental-weekday-pricing-error" data-weekday-pricing-error role="alert" hidden></div>
                <div class="mt-3 mb-2 rental-weekend-toggle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="${containerId}_weekendToggle"
                            ${(existingData.weekend_price > 0 || existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'checked' : ''}
                            onchange="toggleWeekendPricing(this, '${containerId}')">
                        <label class="form-check-label" for="${containerId}_weekendToggle">Egne helgepriser</label>
                    </div>
                </div>
                <div id="${containerId}_weekendPricing" class="rental-weekend-pricing" style="display: ${(existingData.weekend_price > 0 || existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'block' : 'none'};">
                    <div class="mb-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="${containerId}_pricingMode" id="${containerId}_modeUnified" value="unified"
                                ${(existingData.saturday_price > 0 || existingData.sunday_price > 0) ? '' : 'checked'}
                                onchange="togglePricingMode('${containerId}', 'unified')">
                            <label class="form-check-label" for="${containerId}_modeUnified">Felles helgepris</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="${containerId}_pricingMode" id="${containerId}_modePerDay" value="perday"
                                ${(existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'checked' : ''}
                                onchange="togglePricingMode('${containerId}', 'perday')">
                            <label class="form-check-label" for="${containerId}_modePerDay">Per dag (lør/søn)</label>
                        </div>
                    </div>
                    <div id="${containerId}_unifiedPrice" style="display: ${(existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'none' : 'block'};">
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <label class="form-label mb-1">Helgepris per dag (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="weekend_price" step="0.01" min="0" value="${escAttr(fieldValue(existingData.weekend_price ?? 0))}">
                            </div>
                        </div>
                    </div>
                    <div id="${containerId}_perDayPrice" style="display: ${(existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'block' : 'none'};">
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <label class="form-label mb-1">Lørdagspris (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="saturday_price" step="0.01" min="0" value="${escAttr(fieldValue(existingData.saturday_price ?? 0))}">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label mb-1">Søndagspris (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="sunday_price" step="0.01" min="0" value="${escAttr(fieldValue(existingData.sunday_price ?? 0))}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <h6 class="text-muted mb-2"><i class="bi bi-card-image me-1"></i>Bilder og beskrivelse</h6>
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label mb-1">Bilder (URL-er)</label>
                <div id="${containerId}_imagesContainer">
    `;
    
    const existingImages = existingData.images || [];
    if (existingImages.length > 0) {
        existingImages.forEach((img, idx) => {
            const imgUrl = typeof img === 'string' ? img : (img.url || '');
            const isPrimary = typeof img === 'object' && img.is_primary;
            const caption = typeof img === 'object' ? (img.caption || '') : '';
            html += buildImageRow(containerId, idx, imgUrl, isPrimary, caption);
        });
    } else {
        html += buildImageRow(containerId, 0, '', true, '');
    }
    
    html += `
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addImageRow('${containerId}')">
                    <i class="bi bi-plus-circle me-1"></i>Legg til bilde
                </button>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Beskrivelse</label>
                        <textarea class="form-control form-control-sm" name="description" rows="4">${escHtml(existingData.description || '')}</textarea>
            </div>
        </div>
        
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_available" id="${checkboxId}" ${(existingData.is_available === undefined || existingData.is_available === null || existingData.is_available === true || existingData.is_available === 1 || existingData.is_available === '1') ? 'checked' : ''}>
            <label class="form-check-label" for="${checkboxId}">
                Tilgjengelig for utleie
            </label>
        </div>
    `;

    if (category && rentalSchemas[category]) {
        const schema = rentalSchemas[category];
        html += `<h6 class="text-muted mb-2"><i class="bi bi-list-check me-1"></i>${escHtml(schema.label)} - spesifikke detaljer</h6>`;
        html += '<div class="card bg-light mb-3"><div class="card-body py-2"><div class="row">';
        
        Object.entries(schema.fields).forEach(([fieldName, field]) => {
            if (fieldName === 'age_required' && !isEditForm) return;
            const value = existingData.type_data?.[fieldName] ?? '';
            html += generateIntegratedFieldHTML(fieldName, field, value, isEditForm);
        });
        if (category === 'venue' && !Object.prototype.hasOwnProperty.call(schema.fields, 'access_code')) {
            html += generateIntegratedFieldHTML('access_code', {
                type: 'text',
                label: 'Tilgangskode',
                required: false
            }, existingData.type_data?.access_code || '', isEditForm);
        }
        
        html += '</div></div></div>';
    }

    html += `
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label mb-1" for="${containerId}_rentalText">Leietekst</label>
                <textarea class="form-control" id="${containerId}_rentalText" name="rental_text" rows="3" maxlength="1000"
                    placeholder="Praktisk informasjon om leien...">${escHtml(existingData.rental_text || '')}</textarea>
                <small class="text-muted">Teksten kan brukes i informasjonen til leietaker (maks. 1000 tegn).</small>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    setupRentalWeekdayPricing(containerId, existingData, weekdayState);
    if (containerId === 'createFormFieldsContainer') compactRentalCreateFields(container, category);
}

function organizeRentalEditForm(container, category, existingData) {
    if (!container) return;
    const children = [...container.children];
    const identity = children.find(node => node.matches('.row') && node.querySelector('[name="object_name"]'));
    const priceHeading = children.find(node => node.matches('[data-rental-pricing-heading]'));
    const priceCard = priceHeading?.nextElementSibling;
    const marketField = priceCard?.querySelector('[name="market_price"]')?.closest('.rental-market-field');
    if (!identity || !priceHeading || !priceCard) return;

    const imagesHeading = children.find(node => node.tagName === 'H6' && node.textContent.includes('Bilder'));
    const imagesRow = imagesHeading?.nextElementSibling;
    const imageColumn = imagesRow?.querySelector('.col-md-8');
    const descriptionColumn = imagesRow?.querySelector('.col-md-4');
    const availability = children.find(node => node.matches('.form-check.form-switch'));
    const schema = rentalSchemas[category];
    const schemaHeading = schema && children.find(node => node.tagName === 'H6' && node.textContent.includes('spesifikke detaljer'));
    const schemaCard = schemaHeading?.nextElementSibling;
    const rentalText = children.find(node => node.matches('.card') && node.querySelector('[name="rental_text"]'));
    const priceBody = priceCard.querySelector('.card-body');
    const weekendActive = Number(existingData.weekend_price || 0) > 0
        || Number(existingData.saturday_price || 0) > 0
        || Number(existingData.sunday_price || 0) > 0;
    const weekdaySource = existingData._weekdayPricesInvalid
        ? existingData._weekdayPricesRaw
        : (existingData._weekdayPricesDraft ?? existingData.weekday_prices ?? existingData.object_weekday_prices);
    const weekdayState = parseRentalWeekdayPrices(weekdaySource);
    const hasStoredWeekdayFlag = existingData.weekday_prices_enabled !== null
        && existingData.weekday_prices_enabled !== undefined
        && existingData.weekday_prices_enabled !== '';
    const weekdayMode = ['flat','weekdays'].includes(existingData._weekdayPricingMode)
        ? existingData._weekdayPricingMode
        : (hasStoredWeekdayFlag
            ? (Number(existingData.weekday_prices_enabled) === 1 ? 'weekdays' : 'flat')
            : (weekdayState.present ? 'weekdays' : 'flat'));

    const makeDisclosure = (title, status, key) => {
        const details = document.createElement('details');
        details.className = 'rental-edit-section';
        details.dataset.rentalEditSection = key;
        const summary = document.createElement('summary');
        const titleNode = document.createElement('span');
        titleNode.className = 'rental-edit-section-title';
        titleNode.textContent = title;
        const statusNode = document.createElement('span');
        statusNode.className = 'rental-edit-section-status';
        statusNode.textContent = status;
        summary.append(titleNode, statusNode);
        const body = document.createElement('div');
        body.className = 'rental-edit-section-body';
        details.append(summary, body);
        return {details, body, statusNode};
    };

    priceHeading.remove();
    priceCard.className = 'card rental-edit-base-price';
    if (marketField) marketField.remove();

    const core = document.createElement('section');
    core.className = 'rental-edit-core';
    identity.classList.add('rental-edit-identity');
    core.append(identity);

    if (availability) {
        availability.className = 'form-check form-switch rental-edit-availability';
        core.append(availability);
    }

    const pricing = makeDisclosure(
        'Grunnpris og helgepris',
        weekdayMode === 'weekdays' ? 'Ukedagssatser' : (weekendActive ? 'Flat pris · helgepris' : 'Flat dagspris'),
        'pricing'
    );
    priceCard.querySelector('.rental-pricing-card-body')?.classList.add('rental-edit-pricing-body');
    pricing.body.append(priceCard);
    const weekdayToggle = container.querySelector('[name="weekday_prices_enabled"]');
    weekdayToggle?.addEventListener('change', () => {
        pricing.statusNode.textContent = weekdayToggle.checked ? 'Ukedagssatser' : 'Flat dagspris';
    });
    priceCard.querySelector('#formFieldsContainer_weekendToggle')?.addEventListener('change', event => {
        const currentMode = rentalWeekdayMode(container.closest('form'), 'formFieldsContainer');
        pricing.statusNode.textContent = currentMode === 'weekdays' ? 'Ukedagssatser' : (event.target.checked ? 'Flat pris · helgepris' : 'Flat dagspris');
    });

    const typeRows = schemaCard ? [...schemaCard.querySelectorAll('[data-rental-field-name]')] : [];
    const accessRows = typeRows.filter(row => row.dataset.rentalFieldName === 'access_code');
    const timeFields = new Set(['pickup_time', 'return_time', 'start_time', 'end_time']);
    const timeRows = typeRows.filter(row => timeFields.has(row.dataset.rentalFieldName));
    const otherRows = typeRows.filter(row => !accessRows.includes(row) && !timeRows.includes(row));

    if (accessRows.length) {
        const access = document.createElement('section');
        access.className = 'rental-edit-access';
        const heading = document.createElement('div');
        heading.className = 'rental-edit-access-heading';
        heading.innerHTML = '<span class="rental-edit-access-mark"><i class="bi bi-key-fill" aria-hidden="true"></i></span><span><strong>Fysisk tilgang</strong><small>Oppdater koden som brukes ved inngang.</small></span>';
        const fields = document.createElement('div');
        fields.className = 'row g-2';
        accessRows.forEach(row => {
            row.className = 'col-12 mb-0';
            fields.append(row);
        });
        access.append(heading, fields);
        core.append(access);
    }

    if (timeRows.length) {
        const timeSection = document.createElement('section');
        timeSection.className = 'rental-edit-time';
        const title = document.createElement('h6');
        title.className = 'rental-edit-subheading';
        title.innerHTML = '<i class="bi bi-clock me-2" aria-hidden="true"></i>Henting og retur';
        const fields = document.createElement('div');
        fields.className = 'row g-2';
        timeRows.forEach(row => {
            row.className = 'col-12 col-sm-6 mb-0';
            fields.append(row);
        });
        timeSection.append(title, fields);
        core.append(timeSection);
    }

    const disclosures = [pricing.details];
    if (otherRows.length || marketField) {
        const specifications = makeDisclosure(`${schema?.label || 'Objekt'} · spesifikasjoner`, `${otherRows.length + (marketField ? 1 : 0)} felt`, 'specifications');
        const fields = document.createElement('div');
        fields.className = 'row';
        otherRows.forEach(row => fields.append(row));
        if (marketField) {
            marketField.className = 'col-12 col-sm-6 mb-3 rental-market-field';
            fields.append(marketField);
        }
        specifications.body.append(fields);
        disclosures.push(specifications.details);
    }

    if (imageColumn) {
        imageColumn.className = 'col-12';
        const imageCount = [...imageColumn.querySelectorAll('.image-url-input')].filter(input => input.value.trim() !== '').length;
        const images = makeDisclosure('Bilder', imageCount ? `${imageCount} bilde${imageCount === 1 ? '' : 'r'}` : 'Ingen bilder', 'images');
        images.body.append(imageColumn);
        disclosures.push(images.details);
    }

    if (descriptionColumn || rentalText) {
        const hasDescription = !!descriptionColumn?.querySelector('[name="description"]')?.value.trim();
        const hasRentalText = !!rentalText?.querySelector('[name="rental_text"]')?.value.trim();
        const text = makeDisclosure('Beskrivelse og leietekst', hasDescription || hasRentalText ? 'Tekst er lagt til' : 'Valgfritt', 'copy');
        if (descriptionColumn) {
            descriptionColumn.className = 'col-12';
            text.body.append(descriptionColumn);
        }
        if (rentalText) {
            rentalText.classList.add('rental-edit-rental-text');
            text.body.append(rentalText);
        }
        disclosures.push(text.details);
    }

    container.replaceChildren(core, ...disclosures);
}

function compactRentalCreateFields(container, category) {
    const children = [...container.children];
    const identity = children.find(node => node.matches('.row') && node.querySelector('[name="object_name"]'));
    const priceHeading = children.find(node => node.matches('[data-rental-pricing-heading]'));
    const priceCard = priceHeading?.nextElementSibling;
    const pricingBody = priceCard?.querySelector('.card-body');
    const basePriceFields = pricingBody?.querySelector('.rental-base-price-fields');
    const dailyField = basePriceFields?.querySelector('[name="rental_price"]')?.closest('.rental-base-rate-field');
    const modeSelector = pricingBody?.querySelector('.rental-weekday-mode-selector');
    const weekdayPrices = pricingBody?.querySelector(`#${container.id}_weekdayPrices`);
    const weekdayNote = pricingBody?.querySelector(`#${container.id}_weekdayPriceNote`);
    const weekdayError = pricingBody?.querySelector('[data-weekday-pricing-error]');
    const depositField = basePriceFields?.querySelector('[name="deposit_amount"]')?.closest('.rental-deposit-field');
    const marketField = basePriceFields?.querySelector('[name="market_price"]')?.closest('.rental-market-field');
    if (!identity || !priceHeading || !priceCard || !dailyField) return;

    const imagesHeading = children.find(node => node.tagName === 'H6' && node.textContent.includes('Bilder'));
    const imagesRow = imagesHeading?.nextElementSibling;
    const availability = children.find(node => node.matches('.form-check.form-switch'));
    const schema = rentalSchemas[category];
    const schemaHeading = schema && children.find(node => node.tagName === 'H6' && node.textContent.includes('spesifikke detaljer'));
    const schemaCard = schemaHeading?.nextElementSibling;
    const rentalText = children.find(node => node.matches('.card') && node.querySelector('[name="rental_text"]'));
    const weekendToggle = pricingBody.querySelector(`#${container.id}_weekendToggle`)?.closest('.rental-weekend-toggle');
    const weekendPricing = pricingBody.querySelector(`#${container.id}_weekendPricing`);

    const basePriceRow = document.createElement('div');
    basePriceRow.className = 'rental-create-pricing-choice';
    if (modeSelector) basePriceRow.append(modeSelector);
    basePriceRow.append(dailyField);
    if (weekdayPrices) basePriceRow.append(weekdayPrices);
    if (weekdayNote) basePriceRow.append(weekdayNote);
    if (weekdayError) basePriceRow.append(weekdayError);
    priceHeading.textContent = 'Grunnpris og helgepris';
    priceCard.className = 'card rental-create-primary-price mb-3';
    pricingBody.replaceChildren(basePriceRow);

    const advanced = document.createElement('details');
    advanced.className = 'rental-create-advanced';
    advanced.innerHTML = '<summary>Flere innstillinger</summary><div class="rental-create-advanced-body"></div>';
    const advancedBody = advanced.querySelector('.rental-create-advanced-body');

    if (depositField || marketField || weekendToggle || weekendPricing) {
        const pricingCard = document.createElement('div');
        pricingCard.className = 'card bg-light mb-3';
        const pricingRow = document.createElement('div');
        pricingRow.className = 'row g-2';
        if (depositField) pricingRow.append(depositField);
        if (marketField) pricingRow.append(marketField);
        const body = document.createElement('div');
        body.className = 'card-body py-2';
        if (pricingRow.childElementCount) body.append(pricingRow);
        if (weekendToggle) body.append(weekendToggle);
        if (weekendPricing) body.append(weekendPricing);
        pricingCard.append(body);
        advancedBody.append(pricingCard);
    }

    if (imagesHeading) advancedBody.append(imagesHeading);
    if (imagesRow) advancedBody.append(imagesRow);
    if (availability) advancedBody.append(availability);
    if (schemaCard && schema) {
        const schemaRows = [...schemaCard.querySelectorAll('[data-rental-field-name]')];
        const requiredRows = schemaRows.filter(row => schema.fields[row.dataset.rentalFieldName]?.required || (category === 'venue' && row.dataset.rentalFieldName === 'access_code'));
        const optionalRows = schemaRows.filter(row => !requiredRows.includes(row));
        const makeFieldCard = (heading, rows, className) => {
            if (!rows.length) return null;
            const section = document.createElement('section');
            section.className = className;
            const title = document.createElement('h6');
            title.className = 'small text-uppercase fw-bold mb-3';
            title.textContent = heading;
            const row = document.createElement('div');
            row.className = 'row';
            rows.forEach(field => row.append(field));
            section.append(title, row);
            return section;
        };
        const requiredSection = makeFieldCard(`${schema.label} · nødvendig`, requiredRows, 'rental-required-fields');
        const optionalSection = makeFieldCard(`${schema.label} · valgfritt`, optionalRows, 'rental-optional-fields');
        if (optionalSection) advancedBody.append(optionalSection);
        if (requiredSection) {
            container.replaceChildren(identity, priceHeading, priceCard, requiredSection, advanced);
            if (rentalText) advancedBody.append(rentalText);
            return;
        }
        if (rentalText) advancedBody.append(rentalText);
    }
    if (rentalText) advancedBody.append(rentalText);
    container.replaceChildren(identity, priceHeading, priceCard, advanced);
}

function generateIntegratedFieldHTML(fieldName, field, value = '', preserveZero = false) {
    if (fieldName === 'age_required') {
        const age = Number(value);
        const enabled = Number.isInteger(age) && age > 0;
        return `
            <div class="col-md-6 mb-3" data-rental-field-name="age_required" data-rental-age-required>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                        id="rentalAgeRequiredToggle" name="age_required_enabled_ui"
                        data-rental-age-required-toggle ${enabled ? 'checked' : ''}
                        onchange="toggleRentalAgeRequirement(this)">
                    <label class="form-check-label" for="rentalAgeRequiredToggle">Krev minstealder</label>
                </div>
                <label class="form-label" for="rentalAgeRequiredValue">Minste alder (år)</label>
                <input class="form-control" type="number" id="rentalAgeRequiredValue"
                    name="type_data[age_required]" data-rental-age-required-value
                    min="1" step="1" value="${enabled ? escAttr(String(age)) : ''}"
                    ${enabled ? 'required' : 'disabled'}
                    aria-describedby="rentalAgeRequiredHelp">
                <div id="rentalAgeRequiredHelp" class="form-text">Slå på bryteren og angi laveste tillatte alder.</div>
            </div>`;
    }

    const required = field.required ? '<span class="text-danger">*</span>' : '';
    const requiredAttr = field.required ? 'required' : '';
    const displayValue = preserveZero && (value === 0 || value === '0') ? '0' : (value || '');
    
    let inputHTML = '';
    
    switch (field.type) {
        case 'text':
            inputHTML = `<input type="text" class="form-control" name="type_data[${escAttr(fieldName)}]" value="${escAttr(displayValue)}" ${field.maxlength ? `maxlength="${Number(field.maxlength)}"` : ''} ${requiredAttr}>`;
            break;
        case 'number':
            const step = field.step || '1';
            inputHTML = `<input type="number" class="form-control" name="type_data[${escAttr(fieldName)}]" step="${escAttr(step)}" value="${escAttr(displayValue)}" ${requiredAttr}>`;
            break;
        case 'checkbox':
            const checked = value === true || value === 1 || value === '1' ? 'checked' : '';
            return `
                <div class="col-md-6 mb-3" data-rental-field-name="${escAttr(fieldName)}">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="type_data[${fieldName}]" id="${fieldName}" value="1" ${checked}>
                        <label class="form-check-label" for="${fieldName}">
                            ${escHtml(field.label)} ${required}
                        </label>
                    </div>
                </div>
            `;
        case 'select':
            inputHTML = `
                <select class="form-select" name="type_data[${fieldName}]" ${requiredAttr}>
                    <option value="">Velg...</option>
                    ${field.options.map(opt => `<option value="${escAttr(opt)}" ${String(displayValue) === String(opt) ? 'selected' : ''}>${escHtml(opt)}</option>`).join('')}
                </select>
            `;
            break;
        case 'textarea':
            inputHTML = `<textarea class="form-control" name="type_data[${escAttr(fieldName)}]" rows="2" ${requiredAttr}>${escHtml(displayValue)}</textarea>`;
            break;
        case 'time':
            inputHTML = `<input type="time" class="form-control" name="type_data[${escAttr(fieldName)}]" value="${escAttr(displayValue)}" ${requiredAttr}>`;
            break;
        default:
            inputHTML = `<input type="text" class="form-control" name="type_data[${escAttr(fieldName)}]" value="${escAttr(value || '')}" ${requiredAttr}>`;
    }
    
    return `
        <div class="col-md-6 mb-3" data-rental-field-name="${escAttr(fieldName)}">
            <label class="form-label">${escHtml(field.label)} ${required}</label>
            ${inputHTML}
        </div>
    `;
}

function toggleRentalAgeRequirement(toggle) {
    const input = toggle?.closest('[data-rental-age-required]')
        ?.querySelector('[data-rental-age-required-value]');
    if (!input) return;
    input.disabled = !toggle.checked;
    input.required = toggle.checked;
}

function toggleWeekendPricing(checkbox, containerId) {
    const section = document.getElementById(containerId + '_weekendPricing');
    section.style.display = checkbox.checked ? 'block' : 'none';
    if (!checkbox.checked) {
        const wpInput = section.querySelector('[name="weekend_price"]');
        const spInput = section.querySelector('[name="saturday_price"]');
        const snInput = section.querySelector('[name="sunday_price"]');
        if (wpInput) wpInput.value = 0;
        if (spInput) spInput.value = 0;
        if (snInput) snInput.value = 0;
    }
}

function togglePricingMode(containerId, mode) {
    document.getElementById(containerId + '_unifiedPrice').style.display = mode === 'unified' ? 'block' : 'none';
    document.getElementById(containerId + '_perDayPrice').style.display = mode === 'perday' ? 'block' : 'none';
    if (mode === 'unified') {
        const spInput = document.querySelector(`#${containerId}_perDayPrice [name="saturday_price"]`);
        const snInput = document.querySelector(`#${containerId}_perDayPrice [name="sunday_price"]`);
        if (spInput) spInput.value = 0;
        if (snInput) snInput.value = 0;
    } else {
        const wpInput = document.querySelector(`#${containerId}_unifiedPrice [name="weekend_price"]`);
        if (wpInput) wpInput.value = 0;
    }
}

function getFormData(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const containerId = formId === 'createRentalForm' ? 'createFormFieldsContainer' : 'formFieldsContainer';
    const weekdayState = rentalWeekdayFormState(form, containerId);
    
    const data = {
        object_name: formData.get('object_name') || '',
        rental_price: formData.get('rental_price') ?? '',
        weekend_price: formData.get('weekend_price') || 0,
        saturday_price: formData.get('saturday_price') || 0,
        sunday_price: formData.get('sunday_price') || 0,
        deposit_amount: formData.get('deposit_amount') || 0,
        market_price: formData.has('market_price') ? formData.get('market_price') : form._rentalMarketPrice ?? '',
        description: formData.get('description') || '',
        rental_text: formData.get('rental_text') || '',
        is_available: formData.get('is_available') === 'on',
        images: collectImagesFromForm(form, formId === 'createRentalForm' ? 'createFormFieldsContainer' : 'formFieldsContainer'),
        type_data: {}
    };
    Object.assign(data, weekdayState);
    data._rentalFallbackRentalPrice = form._rentalFallbackRentalPrice ?? null;
    
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('type_data[')) {
            const fieldName = key.match(/type_data\[(.+)\]/)[1];
            const inputElement = form.querySelector(`[name="${key}"]`);
            if (inputElement && inputElement.type === 'checkbox') {
                data.type_data[fieldName] = value === '1';
            } else {
                data.type_data[fieldName] = value || '';
            }
        }
    }
    const container = form.querySelector('[id$="FormFieldsContainer"], #formFieldsContainer');
    const activeCategory = container?.dataset.category || '';
    data._categoryDrafts = {...form._rentalCategoryDrafts, [activeCategory]: {type_data: {...data.type_data}}};
    
    return data;
}

function getCurrentFormData() {
    return getFormData('rentalForm');
}

async function saveRentalObject() {
    const saveButton = document.getElementById('rentalSaveButton');
    if (saveButton?.disabled) return;
    const form = document.getElementById('rentalForm');
    if (!form.checkValidity()) {
        const invalidFields = [...form.querySelectorAll(':invalid')];
        invalidFields.forEach(field => {
            let section = field.closest('details');
            while (section && form.contains(section)) {
                section.open = true;
                section = section.parentElement?.closest('details') || null;
            }
        });
        const firstInvalid = invalidFields[0];
        firstInvalid?.focus({preventScroll:true});
        firstInvalid?.scrollIntoView({block:'center', behavior:'smooth'});
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const id = formData.get('id');
    const isEdit = id && id !== '';
    const weekdayMode = rentalWeekdayMode(form, 'formFieldsContainer');
    const weekdayState = rentalWeekdayFormState(form, 'formFieldsContainer');
    
    const data = {
        object_name: formData.get('object_name'),
        category: formData.get('category'),
        rental_price: rentalPriceForPayload(form, formData.get('rental_price'), weekdayMode),
        weekday_prices_enabled: weekdayMode === 'weekdays' ? 1 : 0,
        ...(weekdayState.weekday_prices !== undefined ? {weekday_prices: weekdayState.weekday_prices} : {}),
        weekend_price: parseFloat(formData.get('weekend_price')) || 0,
        saturday_price: parseFloat(formData.get('saturday_price')) || 0,
        sunday_price: parseFloat(formData.get('sunday_price')) || 0,
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        ...(formData.get('category') !== 'venue' && formData.has('market_price') ? {market_price: formData.get('market_price') === '' ? null : Number(formData.get('market_price'))} : {}),
        description: formData.get('description') || null,
        rental_text: formData.get('rental_text') || null,
        is_available: formData.get('is_available') === 'on',
        images: collectImagesFromForm(form, 'formFieldsContainer')
    };
    
    if (isEdit) {
        data.id = id;
    }
    
    // Build type_data from the active schema, not FormData entries: unchecked
    // checkboxes are omitted by the browser and must be sent explicitly as 0.
    const typeData = {};
    const activeSchema = rentalSchemas[data.category];
    if (activeSchema && activeSchema.fields) {
        Object.entries(activeSchema.fields).forEach(([fieldName, field]) => {
            const inputElement = form.querySelector(`[name="type_data[${fieldName}]"]`);
            if (!inputElement) return;
            if (field.type === 'checkbox' || inputElement.type === 'checkbox') {
                typeData[fieldName] = inputElement.checked ? 1 : 0;
            } else {
                typeData[fieldName] = inputElement.value === '' ? null : inputElement.value;
            }
        });
    }
    if (data.category === 'venue') {
        const accessCodeInput = form.querySelector('[name="type_data[access_code]"]');
        if (accessCodeInput) {
            typeData.access_code = accessCodeInput.value.trim() || null;
        }
        const ageToggle = form.querySelector('[data-rental-age-required-toggle]');
        const ageInput = form.querySelector('[name="type_data[age_required]"]');
        if (ageToggle && ageInput) {
            typeData.age_required = ageToggle.checked ? Number(ageInput.value) : 0;
        }
    }
    
    if (Object.keys(typeData).length > 0) {
        data.type_data = typeData;
    }
    
    const toastId = toast.loading(isEdit ? 'Oppdaterer objekt...' : 'Oppretter objekt...');
    if (saveButton) { saveButton.disabled = true; saveButton.classList.add('save-busy'); }
    
    try {
        const csrfToken = await ensureRentalFormToken(isEdit ? id : null);
        data.csrf_token = csrfToken;
        if (!isEdit) data.form_fields = rentalCreateFieldsEditor?.getFields() || [];
        const endpoint = isEdit ? '/api/update_rental_object.php' : '/api/create_rental_object.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(data)
        });
        
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch(e) {
            console.error('Invalid JSON from save:', text.substring(0, 200));
            throw new Error('Serveren returnerte ugyldig data');
        }
        
        if (result.success) {
            toast.success(toastId, isEdit ? 'Objekt oppdatert' : 'Objekt opprettet', `${data.object_name} er lagret`);
            form.dataset.rentalBaseline = rentalFormSnapshot(form);
            bootstrap.Modal.getInstance(document.getElementById('rentalModal')).hide();
            loadRentalObjects();
        } else {
            toast.error(toastId, 'Feil', result.message || 'Kunne ikke lagre objekt');
        }
    } catch (error) {
        console.error('Error saving rental object:', error);
        toast.error(toastId, 'Feil', 'Kunne ikke lagre objekt');
    } finally {
        if (saveButton) { saveButton.disabled = false; saveButton.classList.remove('save-busy'); }
    }
}

let imageRowCounter = 0;

function buildImageRow(containerId, idx, url, isPrimary, caption) {
    const rowId = 'imgrow_' + (imageRowCounter++);
    const safeImageUrl = validRentalImageUrl(url) ? escAttr(url) : '';
    return `
        <div class="input-group input-group-sm mb-2 image-row" data-row-id="${escAttr(rowId)}">
            <span class="input-group-text" title="Hovedbilde">
                <input type="radio" name="${containerId}_primaryImage" value="${rowId}" ${isPrimary ? 'checked' : ''}>
            </span>
            <input type="text" class="form-control image-url-input" value="${safeImageUrl}" placeholder="https://bilde-url..." oninput="previewImageRow(this)">
            <input type="text" class="form-control image-caption-input" value="${escAttr(caption)}" placeholder="Bildetekst" style="max-width: 150px;">
            <button type="button" class="btn btn-outline-danger" onclick="removeImageRow(this)" title="Fjern bilde">
                <i class="bi bi-x"></i>
            </button>
            <div class="w-100">
                ${safeImageUrl ? `<img src="${safeImageUrl}" class="img-thumbnail mt-1" style="max-height: 60px;" alt="" onerror="this.remove()">` : ''}
            </div>
        </div>
    `;
}

function addImageRow(containerId) {
    const container = document.getElementById(containerId + '_imagesContainer');
    container.insertAdjacentHTML('beforeend', buildImageRow(containerId, 0, '', false, ''));
}

function removeImageRow(btn) {
    const row = btn.closest('.image-row');
    const container = row.parentElement;
    const wasPrimary = row.querySelector('input[type="radio"]').checked;
    row.remove();
    if (wasPrimary) {
        const firstRadio = container.querySelector('input[type="radio"]');
        if (firstRadio) firstRadio.checked = true;
    }
}

function previewImageRow(input) {
    const row = input.closest('.image-row');
    const previewDiv = row.querySelector('.w-100');
    const url = input.value.trim();
    if (validRentalImageUrl(url)) {
        previewDiv.innerHTML = `<img src="${escAttr(url)}" class="img-thumbnail mt-1" alt="" style="max-height: 60px;" onerror="this.remove()">`;
    } else {
        previewDiv.innerHTML = '';
    }
}

function validRentalImageUrl(value) {
    const valueString = String(value || '').trim();
    if (!valueString || /[\u0000-\u001f"'<>]/.test(valueString)) return false;
    if (/^https?:\/\//i.test(valueString)) return true;
    return valueString.startsWith('/') && !valueString.startsWith('//');
}

function escAttr(value) {
    return escHtml(value).replace(/'/g, '&#39;');
}

function collectImagesFromForm(formElement, containerId) {
    const rows = formElement.querySelectorAll('.image-row');
    const images = [];
    rows.forEach(row => {
        const urlInput = row.querySelector('.image-url-input');
        const captionInput = row.querySelector('.image-caption-input');
        const radio = row.querySelector('input[type="radio"]');
        const url = urlInput ? urlInput.value.trim() : '';
        if (url && validRentalImageUrl(url)) {
            images.push({
                url: url,
                is_primary: radio ? radio.checked : false,
                caption: captionInput ? captionInput.value.trim() : ''
            });
        }
    });
    if (images.length > 0 && !images.some(img => img.is_primary)) {
        images[0].is_primary = true;
    }
    return images;
}

let pendingCreateData = null;

function showCreateSummary() {
    const form = document.getElementById('createRentalForm');
    if (!rentalCreateFieldsEditor?.isReady()) {
        toast.quickWarning('Skjemafelt', 'Vent til standardfeltene er lastet, eller prøv å laste dem på nytt.');
        return;
    }
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const category = formData.get('category');
    const weekdayMode = rentalWeekdayMode(form, 'createFormFieldsContainer');
    const weekdayState = rentalWeekdayFormState(form, 'createFormFieldsContainer');
    
    if (!category) {
        toast.quickError('Feil', 'Du må velge en kategori');
        return;
    }
    
    const data = {
        object_name: formData.get('object_name'),
        category: category,
        rental_price: rentalPriceForPayload(form, formData.get('rental_price'), weekdayMode),
        weekday_prices_enabled: weekdayMode === 'weekdays' ? 1 : 0,
        ...(weekdayState.weekday_prices !== undefined ? {weekday_prices: weekdayState.weekday_prices} : {}),
        weekend_price: parseFloat(formData.get('weekend_price')) || 0,
        saturday_price: parseFloat(formData.get('saturday_price')) || 0,
        sunday_price: parseFloat(formData.get('sunday_price')) || 0,
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        ...(category !== 'venue' && formData.has('market_price') ? {market_price: formData.get('market_price') === '' ? null : Number(formData.get('market_price'))} : {}),
        description: formData.get('description') || null,
        rental_text: formData.get('rental_text') || null,
        is_available: form.querySelector('[name="is_available"]')?.checked ?? true,
        images: collectImagesFromForm(form, 'createFormFieldsContainer'),
        form_fields: rentalCreateFieldsEditor?.getFields() || [],
        csrf_token: rentalCreateFieldsEditor?.getCsrfToken() || rentalFormCsrfToken
    };
    if (!data.csrf_token) {
        toast.quickError('Sikkerhet', 'Skjemafelt kunne ikke lastes. Prøv igjen før du oppretter.');
        return;
    }
    
    const typeData = {};
    const schema = rentalSchemas[category];
    if (schema) {
        Object.entries(schema.fields).forEach(([fieldName, field]) => {
            const inputElement = form.querySelector(`[name="type_data[${fieldName}]"]`);
            if (inputElement) {
                if (field.type === 'checkbox') {
                    typeData[fieldName] = inputElement.checked ? 1 : 0;
                } else {
                    typeData[fieldName] = inputElement.value || null;
                }
            }
        });
        if (category === 'venue') {
            const accessCodeInput = form.querySelector('[name="type_data[access_code]"]');
            if (accessCodeInput) typeData.access_code = accessCodeInput.value.trim() || null;
        }
    }
    
    if (Object.keys(typeData).length > 0) {
        data.type_data = typeData;
    }
    
    pendingCreateData = data;
    
    const categoryLabel = schema ? schema.label : category;
    
    let summaryHTML = '<div class="row">';
    
    summaryHTML += '<div class="col-md-4 mb-3">';
    if (data.images && data.images.length > 0) {
        const primaryImg = data.images.find(i => i.is_primary) || data.images[0];
        summaryHTML += `<img src="${primaryImg.url}" class="img-fluid rounded shadow-sm mb-2" style="max-height: 250px; width: 100%; object-fit: cover;" onerror="this.outerHTML='<div class=\\'bg-light rounded d-flex align-items-center justify-content-center\\' style=\\'height:200px\\'><i class=\\'bi bi-image fs-1 text-muted\\'></i></div>'">`;
        if (data.images.length > 1) {
            summaryHTML += `<div class="d-flex gap-1 flex-wrap">`;
            data.images.forEach((img, i) => {
                if (img.url !== primaryImg.url) {
                    summaryHTML += `<img src="${img.url}" class="img-thumbnail" style="max-height: 50px;" onerror="this.style.display='none'">`;
                }
            });
            summaryHTML += `</div>`;
        }
    } else {
        summaryHTML += `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;"><i class="bi bi-image fs-1 text-muted"></i></div>`;
    }
    summaryHTML += '</div>';
    
    summaryHTML += '<div class="col-md-4 mb-3">';
    summaryHTML += '<h6 class="text-muted mb-2">Grunndata</h6>';
    summaryHTML += `<table class="table table-sm table-borderless">`;
    summaryHTML += `<tr><td class="text-muted" style="width:45%">Objektnavn</td><td><strong>${escHtml(data.object_name)}</strong></td></tr>`;
    summaryHTML += `<tr><td class="text-muted">Kategori</td><td><span class="badge bg-primary">${escHtml(categoryLabel)}</span></td></tr>`;
    summaryHTML += `<tr><td class="text-muted">Grunnpris og helgepris</td><td></td></tr>`;
    if (data.weekday_prices_enabled === 1 && data.weekday_prices) {
        summaryHTML += '<tr><td colspan="2" class="small fw-semibold pt-1">Ukedager</td></tr>';
        rentalWeekdays.forEach(day => {
            summaryHTML += `<tr><td class="text-muted">${day.label}</td><td><strong class="text-success">${Number(data.weekday_prices[day.key]).toLocaleString('nb-NO',{minimumFractionDigits:0,maximumFractionDigits:2})} NOK</strong></td></tr>`;
        });
        summaryHTML += `<tr><td class="text-muted">Helgegrunnpris ved manglende helgesats</td><td>${Number(data.rental_price).toLocaleString('nb-NO',{minimumFractionDigits:0,maximumFractionDigits:2})} NOK</td></tr>`;
    } else {
        summaryHTML += `<tr><td class="text-muted">Flat dagspris</td><td><strong class="text-success">${Number(data.rental_price).toLocaleString('nb-NO',{minimumFractionDigits:0,maximumFractionDigits:2})} NOK</strong></td></tr>`;
    }
    if (data.saturday_price > 0 || data.sunday_price > 0) {
        if (data.saturday_price > 0) summaryHTML += `<tr><td class="text-muted">Lørdagspris</td><td>${data.saturday_price.toFixed(0)} NOK</td></tr>`;
        if (data.sunday_price > 0) summaryHTML += `<tr><td class="text-muted">Søndagspris</td><td>${data.sunday_price.toFixed(0)} NOK</td></tr>`;
    } else if (data.weekend_price > 0) {
        summaryHTML += `<tr><td class="text-muted">Helgepris/dag</td><td>${data.weekend_price.toFixed(0)} NOK</td></tr>`;
    }
    if (data.deposit_amount > 0) {
        summaryHTML += `<tr><td class="text-muted">Depositum</td><td>${data.deposit_amount.toFixed(0)} NOK</td></tr>`;
    }
    summaryHTML += `<tr><td class="text-muted">Status</td><td>${data.is_available ? '<span class="badge bg-success">Tilgjengelig</span>' : '<span class="badge bg-secondary">Ikke tilgjengelig</span>'}</td></tr>`;
    if (data.description) {
        summaryHTML += `<tr><td class="text-muted">Beskrivelse</td><td>${escHtml(data.description)}</td></tr>`;
    }
    summaryHTML += '</table></div>';
    
    summaryHTML += '<div class="col-md-4 mb-3">';
    if (data.type_data && schema && Object.keys(data.type_data).length > 0) {
        summaryHTML += `<h6 class="text-muted mb-2">${escHtml(categoryLabel)} - detaljer</h6>`;
        summaryHTML += '<table class="table table-sm table-borderless">';
        Object.entries(data.type_data).forEach(([key, value]) => {
            const field = schema.fields[key];
            if (field && value !== null && value !== '') {
                let displayValue = value;
                if (field.type === 'checkbox') {
                    displayValue = value ? '<i class="bi bi-check-circle-fill text-success"></i> Ja' : '<i class="bi bi-x-circle-fill text-danger"></i> Nei';
                }
                summaryHTML += `<tr><td class="text-muted" style="width:50%">${escHtml(field.label)}</td><td>${field.type === 'checkbox' ? displayValue : escHtml(displayValue)}</td></tr>`;
            }
        });
        summaryHTML += '</table>';
    } else {
        summaryHTML += '<div class="text-muted"><small>Ingen kategori-spesifikke detaljer fylt inn</small></div>';
    }
    summaryHTML += '</div>';
    
    summaryHTML += '</div>';
    
    document.getElementById('createSummaryContent').innerHTML = summaryHTML;
    document.getElementById('createFormSection').style.display = 'none';
    document.getElementById('createSummarySection').style.display = 'block';
}

async function loadRentalResults() {
    const state = document.getElementById('rentalResultsState');
    const metrics = document.getElementById('rentalResultsMetrics');
    const table = document.getElementById('rentalResultsTable');
    if (!state || !metrics || !table) return;
    state.innerHTML = '<span class="placeholder-wave"><span class="placeholder col-4"></span></span>';
    metrics.innerHTML = '';
    table.innerHTML = '';
    try {
        const response = await fetch('/api/get_rental_results.php', {headers:{'Accept':'application/json'}});
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Kunne ikke hente resultat');
        const summary = data.summary || {};
        const money = value => `${Number(value || 0).toLocaleString('nb-NO',{maximumFractionDigits:0})} NOK`;
        const cards = [
            ['Total omsetning', money(summary.total_revenue), 'bi-graph-up-arrow'],
            ['Antall utleier', Number(summary.total_rentals || 0).toLocaleString('nb-NO'), 'bi-calendar2-check'],
            ['Denne måneden', money(summary.monthly_revenue), 'bi-calendar3'],
            ['Gj.snitt per betalt utleie', money(summary.average_per_paid_rental), 'bi-bar-chart']
        ];
        metrics.innerHTML = cards.map(([label,value,icon]) => `<div class="col-12 col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><div class="d-flex justify-content-between text-muted small"><span>${label}</span><i class="bi ${icon}"></i></div><div class="metric-value mt-2">${value}</div></div></div></div>`).join('');
        const distribution = summary.status_distribution || {};
        const distributionHtml = Object.entries(distribution).map(([status,count]) => `<span class="badge rounded-pill text-bg-light border me-1">${rentalStatusBadge(status)} ${Number(count || 0).toLocaleString('nb-NO')}</span>`).join('');
        if (distributionHtml) metrics.innerHTML += `<div class="col-12"><div class="small text-muted">Statusfordeling <span class="ms-2">${distributionHtml}</span></div></div>`;
        const rows = Array.isArray(summary.recent_paid_contracts) ? summary.recent_paid_contracts : [];
        state.textContent = rows.length ? `${rows.length} resultat` : 'Ingen utleier registrert ennå';
        if (!rows.length) { table.innerHTML = '<div class="alert alert-light border"><i class="bi bi-inbox me-2"></i>Resultat vises når første utleie er registrert.</div>'; return; }
        table.innerHTML = `<div class="rental-mobile-scroll"><table class="table table-sm align-middle"><thead><tr><th>Objekt</th><th>Periode</th><th>Kunde</th><th class="text-end">Beløp</th><th>Status</th></tr></thead><tbody>${rows.map(row => `<tr><td class="fw-semibold">${escHtml(row.object_name || '–')}</td><td>${fmtDate(row.start_date)}–${fmtDate(row.end_date)}</td><td>${escHtml(row.display_customer_name || row.customer_name || '–')}</td><td class="text-end">${money(row.total_amount ?? row.rental_fee)}</td><td>${rentalStatusBadge(row.status)}</td></tr>`).join('')}</tbody></table></div>`;
    } catch (error) {
        state.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Resultatet kunne ikke lastes.</span> <button class="btn btn-link btn-sm p-0" onclick="loadRentalResults()">Prøv igjen</button>';
        metrics.innerHTML = '';
        table.innerHTML = '';
    }
}

function rentalStatusBadge(status) {
    const labels = {approved:'Godkjent', rejected:'Avslått', active:'Aktiv', completed:'Avsluttet', cancelled:'Kansellert', draft:'Utkast'};
    const classes = {approved:'success', active:'success', rejected:'danger', cancelled:'danger', completed:'info', draft:'secondary'};
    return `<span class="badge text-bg-${classes[status] || 'light'}">${labels[status] || escHtml(status || '–')}</span>`;
}

function backToCreateForm() {
    document.getElementById('createSummarySection').style.display = 'none';
    document.getElementById('createFormSection').style.display = 'block';
}

async function confirmSaveNewRentalObject() {
    if (rentalCreateSaving) return;
    if (!pendingCreateData) {
        toast.quickError('Feil', 'Ingen data å lagre');
        return;
    }
    
    const data = {...pendingCreateData};
    rentalCreateSaving = true;
    const confirmButton = document.querySelector('#createSummarySection .btn-success');
    if (confirmButton) { confirmButton.disabled = true; confirmButton.classList.add('save-busy'); }
    data.is_available = data.is_available ? 1 : 0;
    
    if (data.type_data) {
        const convertedTypeData = {};
        Object.entries(data.type_data).forEach(([key, value]) => {
            if (typeof value === 'boolean') {
                convertedTypeData[key] = value ? 1 : 0;
            } else {
                convertedTypeData[key] = value;
            }
        });
        data.type_data = convertedTypeData;
    }
    
    const toastId = toast.loading('Oppretter objekt...');
    
    try {
        const response = await fetch('/api/create_rental_object.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': data.csrf_token },
            body: JSON.stringify(data)
        });
        
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch(e) {
            console.error('Invalid JSON from create:', text.substring(0, 200));
            throw new Error('Serveren returnerte ugyldig data');
        }
        
        if (result.success) {
            toast.success(toastId, 'Objekt opprettet', `${data.object_name} er lagret`);
            pendingCreateData = null;
            backToCreateForm();
            const createForm = document.getElementById('createRentalForm');
            createForm.reset();
            buildCreateForm();
            createForm.dataset.rentalBaseline = rentalFormSnapshot(createForm);
            rentalCreateFieldsEditor?.reset();
            rentalCreateFieldsEditor?.refresh().then(() => {
                rentalCreateFieldsBaseline = JSON.stringify(rentalCreateFieldsEditor.getFields());
            }).catch(error => console.warn('Could not reload rental defaults:', error));
            closeRentalCreate();
            loadRentalObjects();
        } else {
            toast.error(toastId, 'Feil', result.message || 'Kunne ikke opprette objekt');
        }
    } catch (error) {
        console.error('Error creating rental object:', error);
        toast.error(toastId, 'Feil', error.message || 'Kunne ikke opprette objekt');
    } finally {
        rentalCreateSaving = false;
        if (confirmButton) { confirmButton.disabled = false; confirmButton.classList.remove('save-busy'); }
    }
}

async function toggleAvailability(objectId, isAvailable) {
    const obj = allRentalObjects.find(o => String(o.id) === String(objectId));
    if (!obj) return;
    
    try {
        const csrfToken = await ensureRentalFormToken(objectId);
        const response = await fetch('/api/update_rental_object.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ id: objectId, is_available: isAvailable ? 1 : 0, csrf_token: csrfToken })
        });
        
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch(e) { throw new Error('Ugyldig svar'); }
        
        if (result.success) {
            obj.is_available = isAvailable ? 1 : 0;
            obj.display_status = isAvailable ? 'available'
                : (obj.display_status === 'rented' ? 'rented' : 'inactive');
            const visibilityAction = document.querySelector(`[data-rental-visibility="${CSS.escape(String(objectId))}"]`);
            if (visibilityAction) {
                visibilityAction.dataset.rentalVisible = isAvailable ? '1' : '0';
                visibilityAction.innerHTML = `<i class="bi bi-${isAvailable ? 'eye-slash' : 'eye'} me-2"></i>${isAvailable ? 'Skjul' : 'Vis igjen'}`;
            }
            const card = document.getElementById(`rental-card-${objectId}`)?.querySelector('.rental-object-card');
            if (card) {
                card.classList.toggle('is-muted', !isAvailable);
                card.classList.toggle('is-rented', !isAvailable && obj.display_status === 'rented');
                card.querySelector('.rental-card-status').innerHTML = isAvailable
                    ? '<span class="badge rental-state-badge rental-state-available ms-2">Tilgjengelig</span>'
                    : obj.display_status === 'rented'
                        ? '<span class="badge text-bg-light border text-secondary ms-2"><i class="bi bi-lock me-1"></i>Utleid</span>'
                        : '<span class="badge text-bg-light border text-secondary ms-2"><i class="bi bi-eye-slash me-1"></i>Skjult</span>';
            }
            toast.quickSuccess(isAvailable ? 'Synlig' : 'Skjult', `${obj.object_name} er nå ${isAvailable ? 'synlig for kunder' : 'skjult fra kunder'}`);
        } else {
            const toggle = document.getElementById(`avail-${objectId}`);
            if (toggle) toggle.checked = !isAvailable;
            toast.quickError('Feil', result.message || 'Kunne ikke oppdatere');
        }
    } catch (error) {
        console.error('Error toggling availability:', error);
        const toggle = document.getElementById(`avail-${objectId}`);
        if (toggle) toggle.checked = !isAvailable;
        toast.quickError('Feil', 'Kunne ikke oppdatere tilgjengelighet');
    }
}

async function editRentalObject(objectId) {
    const obj = allRentalObjects.find(o => String(o.id) === String(objectId));
    if (!obj) {
        toast.quickError('Feil', 'Objektet ble ikke funnet');
        return;
    }
    
    try {
        await ensureRentalFormToken(objectId);
    } catch (error) {
        toast.quickError('Sikkerhet', error.message || 'Kunne ikke hente sikkerhetstoken');
        return;
    }
    document.getElementById('rentalModalTitle').textContent = 'Rediger utleieobjekt';
    document.getElementById('rentalModalSubtitle').textContent = `${rentalSchemas[obj.category]?.label || obj.category} · ${obj.object_name}`;
    document.getElementById('rental_id').value = obj.id;
    
    buildIntegratedForm(obj.category, obj);
    const editForm = document.getElementById('rentalForm');
    editForm.dataset.rentalBaseline = rentalFormSnapshot(editForm);
    
    const modal = new bootstrap.Modal(document.getElementById('rentalModal'));
    modal.show();
}

const rentalDeletingObjects = new Set();
async function deleteRentalObject(objectId) {
    if (rentalDeletingObjects.has(objectId)) return;
    const obj = allRentalObjects.find(o => String(o.id) === String(objectId));
    if (!confirm(`Slette "${obj?.object_name || 'utleieobjektet'}" permanent? Feltoppsettet fjernes. Objekter med forespørsler, kontrakter eller historikk kan ikke slettes; sett dem inaktive i stedet.`)) {
        return;
    }
    
    const toastId = toast.loading('Sletter objekt...');
    rentalDeletingObjects.add(objectId);
    
    try {
        const csrfToken = await ensureRentalFormToken(objectId);
        const response = await fetch('/api/delete_rental_object.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token':csrfToken },
            body: JSON.stringify({ id: objectId, csrf_token:csrfToken })
        });
        
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch(e) { throw new Error('Ugyldig svar'); }
        
        if (result.success) {
            toast.success(toastId, 'Objekt slettet', 'Objektet er fjernet fra systemet');
            loadRentalObjects();
        } else {
            toast.error(toastId, 'Feil', result.message || 'Kunne ikke slette objekt');
        }
    } catch (error) {
        console.error('Error deleting rental object:', error);
        toast.error(toastId, 'Feil', error.message || 'Kunne ikke slette objekt');
    } finally {
        rentalDeletingObjects.delete(objectId);
    }
}

function viewDetails(objectId) {
    const obj = allRentalObjects.find(o => o.id === objectId);
    if (!obj) return;
    
    const schema = rentalSchemas[obj.category];
    document.getElementById('detailsModalTitle').textContent = obj.object_name;
    
    const detailImages = (obj.images && obj.images.length > 0) ? obj.images : (obj.image_url ? [{url: obj.image_url, is_primary: true}] : []);
    const safeDetailImages = detailImages.map(img => typeof img === 'string' ? {url: img} : img).filter(img => validRentalImageUrl(img?.url));
    const detailPrimary = safeDetailImages.find(i => i.is_primary) || safeDetailImages[0] || null;
    const weekdayPriceState = parseRentalWeekdayPrices(obj.weekday_prices ?? obj.object_weekday_prices);
    const usesWeekdayPrices = rentalObjectUsesWeekdayPrices(obj, weekdayPriceState);
    const weekdayPriceDetails = usesWeekdayPrices
        ? (weekdayPriceState.valid
            ? `<p><strong>Ukedagspriser:</strong><br>${rentalWeekdays.map(day => `${day.label}: ${Object.prototype.hasOwnProperty.call(weekdayPriceState.prices, day.key) ? `${Number(weekdayPriceState.prices[day.key]).toLocaleString('nb-NO',{maximumFractionDigits:2})} NOK` : 'Ikke satt'}`).join('<br>')}</p>`
            : '<p class="text-danger"><strong>Ukedagspriser:</strong> Må kontrolleres i redigering.</p>')
        : '';
    
    let detailsHTML = `
        <div class="row">
            <div class="col-md-6">
                ${detailPrimary ? `<img src="${escAttr(detailPrimary.url)}" class="img-fluid rounded mb-2" alt="${escAttr(obj.object_name)}" id="detailMainImage">` : ''}
                ${safeDetailImages.length > 1 ? `<div class="d-flex gap-2 flex-wrap mb-3">${safeDetailImages.map((img) => `<img src="${escAttr(img.url)}" class="img-thumbnail detail-thumb" data-detail-url="${escAttr(img.url)}" style="max-height: 60px; cursor: pointer;${img === detailPrimary ? ' border: 2px solid #0d6efd;' : ''}" title="${escAttr(img.caption || '')}" alt="">`).join('')}</div>` : ''}
                <p><strong>Kategori:</strong> ${escHtml(schema?.label || obj.category)}</p>
                ${usesWeekdayPrices ? `${weekdayPriceDetails}<p><strong>Helgegrunnpris ved manglende helgesats:</strong> ${parseFloat(obj.rental_price || 0).toLocaleString('nb-NO',{maximumFractionDigits:2})} NOK/dag</p>` : `<p><strong>Flat dagspris:</strong> ${parseFloat(obj.rental_price || 0).toLocaleString('nb-NO',{maximumFractionDigits:2})} NOK/dag</p>`}
                ${obj.saturday_price > 0 ? `<p><strong>Lørdagspris:</strong> ${parseFloat(obj.saturday_price).toFixed(0)} NOK/dag</p>` : ''}
                ${obj.sunday_price > 0 ? `<p><strong>Søndagspris:</strong> ${parseFloat(obj.sunday_price).toFixed(0)} NOK/dag</p>` : ''}
                ${(obj.weekend_price > 0 && !obj.saturday_price && !obj.sunday_price) ? `<p><strong>Helgepris:</strong> ${parseFloat(obj.weekend_price).toFixed(0)} NOK/dag</p>` : ''}
                ${obj.deposit_amount > 0 ? `<p><strong>Depositum:</strong> ${parseFloat(obj.deposit_amount).toFixed(0)} NOK</p>` : ''}
                <p><strong>Status:</strong> ${obj.is_available ? '<span class="badge bg-success">Tilgjengelig</span>' : '<span class="badge bg-secondary">Ikke tilgjengelig</span>'}</p>
                ${obj.description ? `<p><strong>Beskrivelse:</strong><br>${escHtml(obj.description)}</p>` : ''}
                ${obj.rental_text ? `<p><strong>Leietekst:</strong><br>${escHtml(obj.rental_text)}</p>` : ''}
            </div>
            <div class="col-md-6">
                <h6>Detaljer:</h6>
    `;
    
    if (obj.type_data && schema) {
        Object.entries(obj.type_data).forEach(([key, value]) => {
            const field = schema.fields[key];
            if (field && value !== null && value !== '') {
                let displayValue = value;
                if (field.type === 'checkbox') {
                    displayValue = value ? '<i class="bi bi-check-circle-fill text-success"></i> Ja' : '<i class="bi bi-x-circle-fill text-danger"></i> Nei';
                }
                detailsHTML += `<p><strong>${escHtml(field.label)}:</strong> ${field.type === 'checkbox' ? displayValue : escHtml(displayValue)}</p>`;
            }
        });
    }
    if (obj.category === 'venue' && obj.type_data?.access_code) {
        detailsHTML += `<p><strong>Tilgangskode:</strong> ${escHtml(obj.type_data.access_code)}</p>`;
    }
    
    detailsHTML += `
            </div>
        </div>
    `;
    
    document.getElementById('detailsModalBody').innerHTML = detailsHTML;
    document.querySelectorAll('#detailsModalBody .detail-thumb').forEach((thumb) => {
        thumb.addEventListener('click', () => {
            const main = document.getElementById('detailMainImage');
            if (main) main.src = thumb.dataset.detailUrl;
        });
    });
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

// ─── CONTRACT MODULE ──────────────────────────────────────────────────────────

let allContracts = [];
let contractStatusFilter = 'all';
let allCustomersCache = null;

async function loadCustomersCache() {
    if (allCustomersCache !== null) return allCustomersCache;
    try {
        const resp = await fetch('/api/get_customers_simple.php');
        const data = await resp.json();
        allCustomersCache = (data.success && Array.isArray(data.customers)) ? data.customers : [];
    } catch (e) {
        allCustomersCache = [];
    }
    return allCustomersCache;
}

async function toggleContractForm(objectId) {
    const panel = document.getElementById('contract-panel-' + objectId);
    if (!panel) return;
    const isVisible = panel.style.display !== 'none';
    if (isVisible) {
        panel.style.display = 'none';
        return;
    }
    panel.style.display = 'block';
    loadObjectContracts(objectId);
    loadCustomersCache();
    loadRentalContractPaymentMethods(objectId);
}

async function loadRentalContractPaymentMethods(objectId) {
    const select = document.getElementById(`cf-payment-method-${objectId}`);
    const state = document.getElementById(`cf-payment-method-state-${objectId}`);
    if (!select || !state || select.dataset.loaded === 'true') return;
    select.disabled = true;
    try {
        const methods = await window.ActivePaymentMethods.load();
        if (!select.isConnected) return;
        select.replaceChildren(new Option(methods.length ? 'Velg aktivert metode' : 'Ingen aktiverte betalingsmåter', ''));
        methods.forEach(method => select.add(new Option(window.ActivePaymentMethods.label(method), method.method_type)));
        select.dataset.loaded = 'true';
        select.disabled = methods.length === 0;
        state.textContent = methods.length ? '' : 'Aktiver en metode i Økonomi først.';
    } catch (error) {
        if (!select.isConnected) return;
        select.replaceChildren(new Option('Kunne ikke laste betalingsmåter', ''));
        state.textContent = error.message || 'Kunne ikke hente betalingsmåter.';
        state.classList.add('text-danger');
        select.disabled = true;
        select.dataset.loaded = 'error';
    }
}

async function loadObjectContracts(objectId) {
    const container = document.getElementById('obj-contracts-' + objectId);
    if (!container) return;
    try {
        const resp = await fetch('/api/get_rental_contracts.php?object_id=' + encodeURIComponent(objectId));
        const data = await resp.json();
        if (!data.success || !data.contracts || data.contracts.length === 0) {
            container.innerHTML = '<p class="text-muted small mb-0">Ingen kontrakter registrert for dette objektet ennå.</p>';
            return;
        }
        const rows = data.contracts.map(c => {
            const statusBadge = contractStatusBadge(c.status);
            const payBadge = contractPaymentBadge(c.payment_status);
            return `<div class="d-flex justify-content-between align-items-center border-bottom py-1">
                <span>${escHtml(c.display_customer_name || '–')} &mdash; ${fmtDate(c.start_date)} – ${fmtDate(c.end_date)}</span>
                <span class="d-flex gap-1">${statusBadge}${payBadge}</span>
            </div>`;
        }).join('');
        container.innerHTML = `<div class="mb-2"><strong class="small">Eksisterende kontrakter:</strong>${rows}</div>`;
    } catch (e) {
        container.innerHTML = '';
    }
}

function searchContractCustomer(objectId, query) {
    const dropdown = document.getElementById('cf-customer-dropdown-' + objectId);
    const hiddenId = document.getElementById('cf-customer-id-' + objectId);
    if (!dropdown) return;

    if (!query || query.length < 2) {
        dropdown.style.display = 'none';
        hiddenId.value = '';
        return;
    }

    loadCustomersCache().then(customers => {
        const q = query.toLowerCase();
        const matches = customers.filter(c =>
            (c.name && c.name.toLowerCase().includes(q)) ||
            (c.email && c.email.toLowerCase().includes(q))
        ).slice(0, 8);

        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        dropdown.innerHTML = '';
        matches.forEach(c => {
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'dropdown-item py-1 small';
            a.textContent = c.name;
            if (c.email) {
                const em = document.createElement('span');
                em.className = 'text-muted';
                em.textContent = ' (' + c.email + ')';
                a.appendChild(em);
            }
            a.addEventListener('click', function(e) {
                e.preventDefault();
                selectContractCustomer(objectId, c.id, c.name);
            });
            dropdown.appendChild(a);
        });
        dropdown.style.display = 'block';
    });
}

function selectContractCustomer(objectId, customerId, customerName) {
    const nameInput  = document.getElementById('cf-customer-name-' + objectId);
    const hiddenId   = document.getElementById('cf-customer-id-'   + objectId);
    const dropdown   = document.getElementById('cf-customer-dropdown-' + objectId);
    if (nameInput) nameInput.value = customerName;
    if (hiddenId)  hiddenId.value  = customerId;
    if (dropdown)  dropdown.style.display = 'none';
}

function calcContractAmount(objectId, dailyPrice) {
    const startInput = document.getElementById('cf-start-'  + objectId);
    const endInput   = document.getElementById('cf-end-'    + objectId);
    const daysInput  = document.getElementById('cf-days-'   + objectId);
    const amtInput   = document.getElementById('cf-amount-' + objectId);
    if (!startInput || !endInput) return;

    const start = new Date(startInput.value);
    const end   = new Date(endInput.value);
    if (!startInput.value || !endInput.value || end < start) {
        if (daysInput) daysInput.value = '';
        return;
    }

    const days = Math.round((end - start) / 86400000) + 1;
    if (daysInput) daysInput.value = days;
    if (amtInput) {
        amtInput.value = (days * dailyPrice).toFixed(2);
    }
}

async function submitContract(objectId) {
    const startVal  = document.getElementById('cf-start-'          + objectId)?.value;
    const endVal    = document.getElementById('cf-end-'            + objectId)?.value;
    const amtVal    = document.getElementById('cf-amount-'         + objectId)?.value;
    const depVal    = document.getElementById('cf-deposit-'        + objectId)?.value;
    const pmVal     = document.getElementById('cf-payment-method-' + objectId)?.value;
    const statusVal = document.getElementById('cf-status-'         + objectId)?.value;
    const notesVal  = document.getElementById('cf-notes-'          + objectId)?.value;
    const custId    = document.getElementById('cf-customer-id-'    + objectId)?.value;
    const custName  = document.getElementById('cf-customer-name-'  + objectId)?.value;

    if (!startVal || !endVal) {
        toast.quickError('Feil', 'Fra dato og til dato er påkrevd');
        return;
    }
    if (new Date(endVal) < new Date(startVal)) {
        toast.quickError('Feil', 'Til-dato kan ikke være før fra-dato');
        return;
    }
    const inclusiveDays = Math.round((new Date(endVal) - new Date(startVal)) / 86400000) + 1;
    if (!Number.isFinite(inclusiveDays) || inclusiveDays < 1) {
        toast.quickError('Feil', 'Datoperioden er ugyldig');
        return;
    }

    const payload = {
        rental_object_id: objectId,
        start_date:       startVal,
        end_date:         endVal,
        total_amount:     parseFloat(amtVal)  || 0,
        rental_days:      inclusiveDays,
        deposit_amount:   parseFloat(depVal)  || 0,
        payment_method:   pmVal   || null,
        status:           statusVal || 'active',
        notes:            notesVal  || null,
        customer_id:      custId    || null,
        customer_name:    custName  || null
    };

    const toastId = toast.loading('Lagrer kontrakt...');
    try {
        const resp = await fetch('/api/create_rental_contract.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const text = await resp.text();
        let result;
        try { result = JSON.parse(text); } catch(e) { throw new Error('Ugyldig svar fra server'); }

        if (result.success) {
            toast.success(toastId, 'Kontrakt lagret', result.status === 'active' ? 'Objektet er markert som utleid' : 'Lagret som utkast');
            document.getElementById('contract-panel-' + objectId).style.display = 'none';
            loadRentalObjects();
            loadContracts(contractStatusFilter);
            loadApprovedSchedule();
        } else {
            toast.error(toastId, 'Feil', result.message || 'Kunne ikke lagre kontrakt');
        }
    } catch (err) {
        toast.error(toastId, 'Feil', err.message);
    }
}

// ─── CONTRACTS TAB ───────────────────────────────────────────────────────────

async function loadContracts(status) {
    contractStatusFilter = status || 'all';
    const container = document.getElementById('contractsListContainer');
    if (!container) return;

    container.innerHTML = `<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Laster...</div>`;

    try {
        const url = status && status !== 'all'
            ? '/api/get_rental_contracts.php?status=' + encodeURIComponent(status)
            : '/api/get_rental_contracts.php';
        const resp = await fetch(url);
        const data = await resp.json();
        if (data.success) {
            allContracts = data.contracts || [];
            renderContractsList(allContracts);
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'contracts',success:true,status:status || 'all',activeCount:allContracts.filter(item => item.status === 'active').length}}));
        } else {
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'contracts',success:false,status:status || 'all'}}));
            container.innerHTML = `<div class="alert alert-warning d-flex justify-content-between align-items-center gap-2"><span>${escHtml(data.message || 'Feil ved henting av kontrakter')}</span><button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadContracts(contractStatusFilter)">Prøv igjen</button></div>`;
        }
    } catch (e) {
        document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'contracts',success:false,status:status || 'all'}}));
        container.innerHTML = `<div class="alert alert-danger d-flex justify-content-between align-items-center gap-2"><span>Feil ved lasting av kontrakter</span><button type="button" class="btn btn-sm btn-outline-danger" onclick="loadContracts(contractStatusFilter)">Prøv igjen</button></div>`;
    }
}

function filterContracts(status) {
    contractStatusFilter = status;
    const ids = ['all', 'active', 'completed', 'cancelled', 'draft'];
    ids.forEach(s => {
        const btn = document.getElementById('cf-btn-' + s);
        if (!btn) return;
        btn.className = s === status ? 'btn btn-primary' : 'btn btn-outline-secondary';
    });
    loadContracts(status);
}

function renderContractsList(contracts) {
    const container = document.getElementById('contractsListContainer');
    if (!container) return;

    if (!contracts || contracts.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-5">
            <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
            Ingen kontrakter funnet
        </div>`;
        return;
    }

    const rows = contracts.map(c => {
        const contractToken = encodeURIComponent(String(c.id));
        const objectToken = encodeURIComponent(String(c.rental_object_id));
        const statusBadge = contractStatusBadge(c.status);
        const payBadge    = contractPaymentBadge(c.payment_status);

        const markPaidBtn = c.payment_status !== 'paid'
            ? `<button class="btn btn-outline-success btn-sm" onclick="markContractPaid(decodeURIComponent('${contractToken}'))" title="Merk som betalt"><i class="bi bi-check-circle"></i></button>`
            : '';

        let statusActionBtn = '';
        if (c.status === 'draft') {
            statusActionBtn = `<button class="btn btn-outline-primary btn-sm" onclick="updateContractStatus(decodeURIComponent('${contractToken}'), 'active', decodeURIComponent('${objectToken}'))" title="Aktiver"><i class="bi bi-play-circle"></i></button>`;
        } else if (c.status === 'active') {
            statusActionBtn = `<button class="btn btn-outline-secondary btn-sm" onclick="updateContractStatus(decodeURIComponent('${contractToken}'), 'completed', decodeURIComponent('${objectToken}'))" title="Avslutt"><i class="bi bi-stop-circle"></i></button>`;
        }

        const cancelBtn = !['completed','cancelled'].includes(c.status)
            ? `<button class="btn btn-outline-warning btn-sm" onclick="updateContractStatus(decodeURIComponent('${contractToken}'), 'cancelled', decodeURIComponent('${objectToken}'))" title="Kanseller"><i class="bi bi-x-circle"></i></button>`
            : '';

        const deleteBtn = c.status === 'draft'
            ? `<button class="btn btn-outline-danger btn-sm" onclick="deleteContract(decodeURIComponent('${contractToken}'), decodeURIComponent('${objectToken}'))" title="Slett"><i class="bi bi-trash"></i></button>`
            : '';

        return `<tr>
            <td class="small fw-semibold">${escHtml(c.object_name)}</td>
            <td class="small">${escHtml(c.display_customer_name)}</td>
            <td class="small text-nowrap">${fmtDate(c.start_date)}</td>
            <td class="small text-nowrap">${fmtDate(c.end_date)}</td>
            <td class="small text-nowrap">${c.rental_days ?? '–'} dager</td>
            <td class="small text-nowrap">${parseFloat(c.total_amount || 0).toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:0})} NOK</td>
            <td>${statusBadge}</td>
            <td>${payBadge}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    ${markPaidBtn}${statusActionBtn}${cancelBtn}${deleteBtn}
                </div>
            </td>
        </tr>`;
    }).join('');

    container.innerHTML = `<div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Objekt</th>
                    <th>Kunde</th>
                    <th>Fra</th>
                    <th>Til</th>
                    <th>Dager</th>
                    <th>Beløp</th>
                    <th>Status</th>
                    <th>Betaling</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

async function markContractPaid(contractId) {
    const c = allContracts.find(x => x.id === contractId);
    if (!c) return;
    const toastId = toast.loading('Merker som betalt...');
    try {
        const resp = await fetch('/api/update_rental_contract.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: contractId, payment_status: 'paid', total_amount: c.total_amount, payment_method: c.payment_method })
        });
        const data = await resp.json();
        if (data.success) {
            toast.success(toastId, 'Betalt', 'Kontrakt merket som betalt og logget i regnskap');
            loadContracts(contractStatusFilter);
        } else {
            toast.error(toastId, 'Feil', data.message || 'Kunne ikke oppdatere');
        }
    } catch (e) {
        toast.error(toastId, 'Feil', 'Nettverksfeil');
    }
}

async function updateContractStatus(contractId, newStatus, objectId) {
    const labels = { active: 'aktivere', completed: 'avslutte', cancelled: 'kansellere' };
    if (!confirm(`Er du sikker på at du vil ${labels[newStatus] || 'oppdatere'} denne kontrakten?`)) return;
    const toastId = toast.loading('Oppdaterer kontrakt...');
    try {
        const resp = await fetch('/api/update_rental_contract.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: contractId, status: newStatus })
        });
        const data = await resp.json();
        if (data.success) {
            toast.success(toastId, 'Oppdatert', 'Kontraktstatus endret');
            loadContracts(contractStatusFilter);
            loadApprovedSchedule();
            loadRentalObjects();
        } else {
            toast.error(toastId, 'Feil', data.message || 'Kunne ikke oppdatere');
        }
    } catch (e) {
        toast.error(toastId, 'Feil', 'Nettverksfeil');
    }
}

async function deleteContract(contractId, objectId) {
    if (!confirm('Slette dette utkastet?')) return;
    const toastId = toast.loading('Sletter...');
    try {
        const resp = await fetch('/api/delete_rental_contract.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: contractId })
        });
        const data = await resp.json();
        if (data.success) {
            toast.success(toastId, 'Slettet', 'Kontrakt slettet');
            loadContracts(contractStatusFilter);
            loadRentalObjects();
        } else {
            toast.error(toastId, 'Feil', data.message || 'Kunne ikke slette');
        }
    } catch (e) {
        toast.error(toastId, 'Feil', 'Nettverksfeil');
    }
}

// ─── FORESPURT UTLEIE ────────────────────────────────────────────────────────

function calcAge(dob) {
    if (!dob) return null;
    const b = new Date(dob), n = new Date();
    let age = n.getFullYear() - b.getFullYear();
    const m = n.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && n.getDate() < b.getDate())) age--;
    return age >= 0 ? age : null;
}

function rentalRequestFieldsHtml(fields, compact = false) {
    if (!Array.isArray(fields) || fields.length === 0) return '';
    const items = fields.filter(field => field && field.value !== null && field.value !== undefined && String(field.value).trim() !== '');
    if (!items.length) return '';
    const content = items.map(field => `
        <div class="d-flex ${compact ? 'gap-1' : 'gap-2'}">
            <span class="text-muted">${escHtml(field.label || field.field || 'Opplysning')}:</span>
            <span class="fw-medium">${escHtml(field.value)}</span>
        </div>`).join('');
    return compact
        ? `<div class="rental-request-fields small mt-1">${content}</div>`
        : `<section class="rental-request-fields border rounded-3 p-3 mt-3 bg-light-subtle"><h6 class="small text-uppercase text-muted mb-2">Innsendte kundeopplysninger</h6><div class="row row-cols-1 row-cols-md-2 g-2">${items.map(field => `<div class="col"><div class="d-flex flex-column"><span class="text-muted small">${escHtml(field.label || field.field || 'Opplysning')}</span><span class="fw-medium">${escHtml(field.value)}</span></div></div>`).join('')}</div></section>`;
}

function rentalAdjustmentFromRow(row) {
    const type = row.querySelector('.rental-request-adjustment-type')?.value || 'none';
    const amount = row.querySelector('.rental-request-adjustment-amount')?.value || '';
    const explanation = row.querySelector('.rental-request-adjustment-explanation')?.value || '';
    return RentalRequestPricing.normalizeAdjustment(type, amount, explanation);
}

function rentalRequestWeekdayLabel(value) {
    if (!value) return 'Velg dato';
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) return 'Ugyldig dato';
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    if (date.getFullYear() !== Number(match[1])
        || date.getMonth() !== Number(match[2]) - 1
        || date.getDate() !== Number(match[3])) return 'Ugyldig dato';
    const weekday = date.toLocaleDateString('nb-NO', {weekday:'long'});
    return weekday.charAt(0).toLocaleUpperCase('nb-NO') + weekday.slice(1);
}

function updateRentalRequestWeekdayLabels(row) {
    ['start-date', 'end-date'].forEach(field => {
        const input = row.querySelector(`[data-rental-request-field="${field}"]`);
        const label = row.querySelector(`[data-rental-weekday-label="${field}"]`);
        if (label) label.textContent = rentalRequestWeekdayLabel(input?.value || '');
    });
}

function refreshRentalRequestPrice(row) {
    const baseEl = row.querySelector('.rental-request-base-fee');
    const totalEl = row.querySelector('.rental-request-total');
    const cleaningEl = row.querySelector('.rental-request-cleaning-fee');
    const adjustmentEl = row.querySelector('.rental-request-adjustment-summary');
    const errorEl = row.querySelector('.rental-request-price-error');
    const approveButton = row.querySelector('.rental-request-approve');
    try {
        updateRentalRequestWeekdayLabels(row);
        const adjustment = rentalAdjustmentFromRow(row);
        const quote = RentalRequestPricing.calculateRentalQuote({
            rates: {
                rental_price: row.dataset.dailyPrice,
                weekday_prices: row.dataset.weekdayPrices,
                weekday_prices_enabled: row.dataset.weekdayPricesEnabled === ''
                    ? undefined : row.dataset.weekdayPricesEnabled,
                weekend_price: row.dataset.weekendPrice,
                saturday_price: row.dataset.saturdayPrice,
                sunday_price: row.dataset.sundayPrice,
            },
            startDate: row.querySelector('[data-rental-request-field="start-date"]')?.value,
            endDate: row.querySelector('[data-rental-request-field="end-date"]')?.value,
            cleaningFee: row.dataset.cleaningFee,
            cleaning: row.querySelector('.rental-request-cleaning')?.checked,
            adjustment: {
                type: adjustment.type,
                amount: adjustment.amountCents / 100,
                explanation: adjustment.explanation,
            },
        });
        const formatCents = cents => (cents / 100).toLocaleString('nb-NO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
        row.dataset.baseRentalFee = String(quote.baseCents / 100);
        row.dataset.rentalTotal = String(quote.totalCents / 100);
        row.dataset.priceError = '';
        if (baseEl) baseEl.textContent = `${formatCents(quote.baseCents)} NOK`;
        if (cleaningEl) cleaningEl.textContent = quote.cleaningCents > 0
            ? `+ ${formatCents(quote.cleaningCents)} NOK renhold`
            : 'Ingen renhold';
        if (totalEl) totalEl.textContent = `Totalt: ${formatCents(quote.totalCents)} NOK`;
        if (adjustmentEl) adjustmentEl.textContent = adjustment.amountCents > 0
            ? `${adjustment.type === 'discount' ? '−' : '+'} ${formatCents(adjustment.amountCents)} NOK · ${adjustment.explanation}`
            : 'Ingen prisjustering';
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.hidden = true;
        }
        if (approveButton) approveButton.disabled = row.dataset.busy === 'true';
        return quote;
    } catch (error) {
        updateRentalRequestWeekdayLabels(row);
        const message = error.message || 'Kunne ikke beregne leieprisen.';
        row.dataset.rentalTotal = '';
        row.dataset.priceError = message;
        if (baseEl) baseEl.textContent = '–';
        if (cleaningEl) cleaningEl.textContent = '';
        if (totalEl) totalEl.textContent = 'Kunne ikke beregne pris';
        if (adjustmentEl) adjustmentEl.textContent = 'Ingen prisjustering';
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
        }
        if (approveButton) approveButton.disabled = true;
        return null;
    }
}

function handleRentalRequestAdjustmentChange(row) {
    const type = row.querySelector('.rental-request-adjustment-type')?.value || 'none';
    const amountInput = row.querySelector('.rental-request-adjustment-amount');
    const explanationInput = row.querySelector('.rental-request-adjustment-explanation');
    const disabled = type === 'none';
    if (disabled) {
        if (amountInput) amountInput.value = '';
        if (explanationInput) explanationInput.value = '';
    }
    if (amountInput) amountInput.disabled = disabled || row.dataset.busy === 'true';
    if (explanationInput) explanationInput.disabled = disabled || row.dataset.busy === 'true';
    refreshRentalRequestPrice(row);
}

async function loadRentalRequests() {
    const container = document.getElementById('rentalRequestsContainer');
    if (!container) return;
    if (container.querySelector('[data-busy="true"]') || RentalRequestWorkflow.hasDrafts()
        || document.getElementById('rental-decision-dialog')?.open) {
        toast.error(null, 'Ulagrede endringer', 'Lagre endringene før du oppdaterer forespørselslisten.');
        return false;
    }

    const sequence = ++rentalRequestsLoadSequence;
    try {
        const resp = await fetch('/api/get_rental_schedule.php?approved=0');
        const data = await resp.json();
        // A user may start editing while a refresh request is in flight.
        if (sequence !== rentalRequestsLoadSequence || RentalRequestWorkflow.hasDrafts()
            || container.querySelector('[data-busy="true"]')
            || document.getElementById('rental-decision-dialog')?.open) return false;

        if (!resp.ok || !data.success) {
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'requests',success:false}}));
            container.innerHTML = `<div class="alert alert-danger">
                Kunne ikke laste forespørslene. Databasen kan være midlertidig utilgjengelig.
            </div>`;
            return;
        }

        if (!data.requests || data.requests.length === 0) {
            rentalPendingRequests.clear();
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'requests',success:true}}));
            container.innerHTML = `<div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Ingen ventende forespørsler
            </div>`;
            return true;
        }
        rentalPendingRequests = new Map(data.requests.map(r => [String(r.id), r]));
        document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'requests',success:true}}));
        rentalDecisionEmailEnabled = data.decision_email_enabled === true;

        const rows = data.requests.map(r => {
            const logisticsLabel = r.logistics === 'delivery'
                ? `<span class="badge bg-info text-dark">Levering</span>`
                : `<span class="badge bg-secondary">Hentes</span>`;

            const deliveryAddr = r.logistics === 'delivery' && r.delivery_address
                ? `<div class="text-muted small mt-1"><i class="bi bi-geo-alt me-1"></i>${escHtml(r.delivery_address)}</div>`
                : '';

            const age = calcAge(r.date_of_birth);
            const ageStr = age !== null ? `<span class="text-muted small ms-1">(${age} år)</span>` : '';

            const contactInfo = [
                r.display_email ? `<a href="mailto:${escHtml(r.display_email)}" class="d-block small">${escHtml(r.display_email)}</a>` : '',
                r.display_phone ? `<span class="d-block small text-muted">${escHtml(r.display_phone)}</span>` : ''
            ].join('');

            const notesCell = r.notes
                ? `<span class="d-block small text-muted mt-1" title="${escHtml(r.notes)}">
                       <i class="bi bi-chat-left-text me-1"></i>${escHtml(r.notes.length > 60 ? r.notes.substring(0,60) + '…' : r.notes)}
                   </span>`
                : '';

            const createdAt = r.created_at ? r.created_at.substring(0,10).split('-').reverse().join('.') : '–';
            const rentalFee = parseFloat(r.rental_fee || 0);
            const cleaningFee = parseFloat(r.cleaning_fee || 0);
            const cleaningSelected = Number(r.cleaning || 0) === 1;
            const storedAdjustmentType = ['discount', 'surcharge'].includes(r.price_adjustment_type)
                ? r.price_adjustment_type : 'none';
            const storedAdjustmentAmount = storedAdjustmentType === 'none'
                ? 0 : Math.max(0, Number(r.price_adjustment_cents || 0) / 100);
            const storedAdjustmentValue = storedAdjustmentType === 'discount'
                ? -storedAdjustmentAmount
                : storedAdjustmentType === 'surcharge' ? storedAdjustmentAmount : 0;
            const baseRentalFee = Math.max(
                0,
                rentalFee - (cleaningSelected ? cleaningFee : 0) - storedAdjustmentValue
            );
            const pickupTime = (r.pickup_time || r.start_time || '').substring(0, 5);
            const endTime = (r.return_time || r.end_time || '').substring(0, 5);
            const weekdayPricesJson = typeof r.object_weekday_prices === 'string'
                ? r.object_weekday_prices
                : JSON.stringify(r.object_weekday_prices ?? null);
            const weekdayPricesEnabled = r.object_weekday_prices_enabled === null
                || r.object_weekday_prices_enabled === undefined
                ? '' : String(r.object_weekday_prices_enabled);

            return `<tr data-rental-request-id="${escHtml(r.id)}"
                        data-object-id="${escHtml(r.rental_object_id || '')}"
                        data-daily-price="${escAttr(r.object_daily_price ?? '')}"
                        data-weekday-prices="${escAttr(weekdayPricesJson)}"
                        data-weekday-prices-enabled="${escAttr(weekdayPricesEnabled)}"
                        data-weekend-price="${escAttr(r.object_weekend_price ?? '')}"
                        data-saturday-price="${escAttr(r.object_saturday_price ?? '')}"
                        data-sunday-price="${escAttr(r.object_sunday_price ?? '')}"
                        data-base-rental-fee="${baseRentalFee}"
                        data-pickup-time="${escAttr(pickupTime)}"
                        data-cleaning-fee="${escAttr(r.cleaning_fee ?? '')}">
                <td class="small" data-label="Objekt">
                    <div class="fw-semibold">${escHtml(r.object_name || '–')}</div>
                    ${r.object_category ? `<span class="badge bg-light text-dark border">${escHtml(r.object_category)}</span>` : ''}
                </td>
                <td class="small" data-label="Kunde">
                    <div>${escHtml(r.display_customer_name)}${ageStr}</div>
                    ${contactInfo}
                    ${rentalRequestFieldsHtml(r.request_fields, true)}
                </td>
                <td class="small" data-label="Fra">
                    <div class="d-flex flex-column gap-1" style="min-width:8.5rem">
                        <input type="date" class="form-control form-control-sm rental-request-start-date"
                               data-rental-request-field="start-date"
                               value="${escHtml(r.start_date || '')}" aria-label="Startdato">
                        <span class="small text-muted rental-request-weekday" data-rental-weekday-label="start-date" aria-live="polite"></span>
                        <span class="badge text-bg-light border py-2">Henting${pickupTime ? ` kl. ${escHtml(pickupTime)}` : ''}</span>
                    </div>
                </td>
                <td class="small" data-label="Til">
                    <div class="d-flex flex-column gap-1" style="min-width:8.5rem">
                        <input type="date" class="form-control form-control-sm rental-request-end-date"
                               data-rental-request-field="end-date"
                               value="${escHtml(r.end_date || '')}" aria-label="Sluttdato">
                        <span class="small text-muted rental-request-weekday" data-rental-weekday-label="end-date" aria-live="polite"></span>
                        <input type="time" class="form-control form-control-sm rental-request-end-time"
                               data-rental-request-field="end-time"
                               value="${escHtml(endTime)}" aria-label="Sluttid">
                    </div>
                </td>
                <td class="small" data-label="Logistikk">
                    ${logisticsLabel}
                    ${deliveryAddr}
                </td>
                <td class="small" data-label="Leiebeløp" style="min-width:9rem">
                    <div class="fw-semibold text-nowrap rental-request-base-fee">
                        ${baseRentalFee.toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:2})} NOK
                    </div>
                    <div class="small text-muted rental-request-cleaning-fee">
                        ${cleaningSelected && cleaningFee > 0
                            ? `+ ${cleaningFee.toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:2})} NOK renhold`
                            : 'Ingen renhold'}
                    </div>
                    <div class="small fw-semibold text-nowrap mt-1 rental-request-total">
                        Totalt: ${rentalFee.toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:2})} NOK
                    </div>
                    <div class="row g-1 mt-1">
                        <div class="col-5"><label class="visually-hidden" for="adjustment-type-${escAttr(r.id)}">Prisjusteringstype</label><select id="adjustment-type-${escAttr(r.id)}" class="form-select form-select-sm rental-request-adjustment-type" onchange="handleRentalRequestAdjustmentChange(this.closest('[data-rental-request-id]'))"><option value="none"${storedAdjustmentType === 'none' ? ' selected' : ''}>Ingen justering</option><option value="discount"${storedAdjustmentType === 'discount' ? ' selected' : ''}>Rabatt</option><option value="surcharge"${storedAdjustmentType === 'surcharge' ? ' selected' : ''}>Tillegg</option></select></div>
                        <div class="col-7"><label class="visually-hidden" for="adjustment-amount-${escAttr(r.id)}">Prisjustering beløp</label><input id="adjustment-amount-${escAttr(r.id)}" class="form-control form-control-sm rental-request-adjustment-amount" type="number" min="0" step="0.01" value="${storedAdjustmentAmount || ''}" placeholder="Beløp" oninput="refreshRentalRequestPrice(this.closest('[data-rental-request-id]'))"></div>
                        <div class="col-12"><label class="visually-hidden" for="adjustment-explanation-${escAttr(r.id)}">Prisjustering forklaring</label><input id="adjustment-explanation-${escAttr(r.id)}" class="form-control form-control-sm rental-request-adjustment-explanation" type="text" maxlength="1000" value="${escAttr(storedAdjustmentType === 'none' ? '' : (r.price_adjustment_explanation || ''))}" placeholder="Forklaring til prisjustering" oninput="refreshRentalRequestPrice(this.closest('[data-rental-request-id]'))"></div>
                    </div>
                    <div class="small text-muted rental-request-adjustment-summary">Ingen prisjustering</div>
                    <div class="small text-danger rental-request-price-error" data-price-error hidden></div>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input rental-request-cleaning" type="checkbox"
                               ${cleaningSelected ? 'checked' : ''}
                               ${cleaningFee <= 0 ? 'disabled' : ''}
                                onchange="refreshRentalRequestPrice(this.closest('[data-rental-request-id]'))"
                               aria-label="Inkluder renhold">
                        <label class="form-check-label small text-nowrap">
                            Renhold${cleaningFee > 0 ? ` (${cleaningFee.toLocaleString('nb-NO', {maximumFractionDigits:2})} kr)` : ''}
                        </label>
                    </div>
                </td>
                <td class="small text-nowrap text-muted" data-label="Innsendt">${createdAt}</td>
                <td class="small" data-label="Notater">${notesCell || '<span class="text-muted">–</span>'}</td>
                <td class="text-nowrap" data-label="Handling">
                    <div class="rental-request-actions">
                        <button class="btn btn-outline-primary btn-sm rental-request-save" onclick="RentalRequestWorkflow.save('${escHtml(r.id)}')">Lagre endringer</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="RentalRequestWorkflow.discard('${escHtml(r.id)}')">Forkast endringer</button>
                        <button class="btn btn-success btn-sm rental-request-approve" onclick="approveScheduleRequest('${escHtml(r.id)}')">
                            <i class="bi bi-check-lg me-1"></i>Godkjenn
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="declineScheduleRequest('${escHtml(r.id)}')">
                            <i class="bi bi-x-lg me-1"></i>Avslå
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        container.innerHTML = `<div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Objekt</th>
                        <th>Kunde</th>
                        <th>Fra</th>
                        <th>Til</th>
                        <th>Logistikk</th>
                        <th>Leiebeløp</th>
                        <th>Innsendt</th>
                        <th>Notater</th>
                        <th>Handling</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
        container.querySelectorAll('[data-rental-request-id]').forEach(row => {
            row.querySelectorAll('[data-rental-request-field="start-date"], [data-rental-request-field="end-date"]').forEach(input => {
                input.addEventListener('change', () => refreshRentalRequestPrice(row));
            });
            handleRentalRequestAdjustmentChange(row);
            RentalRequestWorkflow.capture(row);
        });
        return true;
    } catch (e) {
        document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'requests',success:false}}));
        if (sequence === rentalRequestsLoadSequence && !RentalRequestWorkflow.hasDrafts()
            && !container.querySelector('[data-busy="true"]')
            && !document.getElementById('rental-decision-dialog')?.open) {
            container.innerHTML = `<div class="alert alert-danger">Feil ved lasting av forespørsler</div>`;
        }
        return false;
    }
}

function rentalRequestContext(id) {
    return {request: rentalPendingRequests.get(String(id)), emailEnabled: rentalDecisionEmailEnabled,
        csrfToken: rentalLifecycleCsrf};
}

function rentalRequestAcceptSaved(id, result) {
    const request = rentalPendingRequests.get(String(id));
    if (request) Object.assign(request, result.request);
}

async function openRentalRequestNotification(id) {
    if (RentalRequestWorkflow.hasDrafts()) {
        toast.error(null, 'Ulagrede endringer', 'Lagre endringene før du åpner en annen forespørsel.');
        return false;
    }
    const tab = document.querySelector('[data-bs-target="#content-rental"]');
    if (!tab) throw new Error('Du har ikke tilgang til utleiemodulen');
    bootstrap.Tab.getOrCreateInstance(tab).show();
    document.querySelectorAll('#content-rental .subtab-content').forEach(section => section.style.display = section.id === 'rental-objects' ? 'block' : 'none');
    if (!await loadRentalRequests()) return false;
    window.RentalDashboard?.openOverview('requests', false);
    const row = document.querySelector(`[data-rental-request-id="${CSS.escape(String(id))}"]`);
    if (!row) {
        const response = await fetch('/api/get_rental_schedule.php?request_id='+encodeURIComponent(id));
        const result = await response.json();
        if (!response.ok || !result.success || result.requests?.length !== 1) {
            throw new Error('Forespørselen er ikke lenger tilgjengelig.');
        }
        RentalRequestWorkflow.inspect(result.requests[0]);
        return true;
    }
    row.scrollIntoView({block:'center'});
    const button = row.querySelector('.rental-request-approve');
    button?.focus();
    return true;
}

function getRentalRequestInlinePayload(id) {
    const row = document.querySelector(`[data-rental-request-id="${CSS.escape(String(id))}"]`);
    if (!row) throw new Error('Forespørselsraden ble ikke funnet');

    const startDate = row.querySelector('[data-rental-request-field="start-date"]')?.value || '';
    const endDate = row.querySelector('[data-rental-request-field="end-date"]')?.value || '';
    const endTime = row.querySelector('[data-rental-request-field="end-time"]')?.value || '';
    const pickupTime = row.dataset.pickupTime || '';
    if (!startDate || !endDate || !endTime) {
        throw new Error('Fra-, til- og sluttid må fylles ut');
    }
    if (endDate < startDate || (endDate === startDate && pickupTime && endTime < pickupTime)) {
        throw new Error('Sluttdato og sluttid kan ikke være før oppstart');
    }

    const adjustment = rentalAdjustmentFromRow(row);
    const quote = refreshRentalRequestPrice(row);
    if (!quote) {
        throw new Error(row.dataset.priceError || 'Kunne ikke beregne leieprisen.');
    }
    const rentalFee = quote.totalCents / 100;
    return {
        start_date: startDate,
        pickup_time: pickupTime,
        end_date: endDate,
        end_time: endTime,
        cleaning: row.querySelector('.rental-request-cleaning')?.checked ? 1 : 0,
        rental_fee: rentalFee,
        price_adjustment_type: adjustment.type === 'none' ? '' : adjustment.type,
        price_adjustment_amount: adjustment.amountCents / 100,
        price_adjustment_explanation: adjustment.explanation
    };
}

function updateRentalRequestInlineTotal(checkbox) {
    const row = checkbox.closest('[data-rental-request-id]');
    if (row) refreshRentalRequestPrice(row);
}

function setRentalRequestRowBusy(id, busy) {
    const row = document.querySelector(`[data-rental-request-id="${CSS.escape(String(id))}"]`);
    if (row) row.dataset.busy = busy ? 'true' : 'false';
    row?.querySelectorAll('button, input, select').forEach(control => {
        const isAdjustmentControl = control.classList.contains('rental-request-adjustment-amount')
            || control.classList.contains('rental-request-adjustment-explanation');
        const adjustmentIsNone = row.querySelector('.rental-request-adjustment-type')?.value === 'none';
        const approvalBlocked = control.classList.contains('rental-request-approve')
            && Boolean(row.dataset.priceError);
        control.disabled = busy
            || approvalBlocked
            || (isAdjustmentControl && adjustmentIsNone)
            || (control.classList.contains('rental-request-cleaning') && Number(row.dataset.cleaningFee || 0) <= 0);
    });
}

async function approveScheduleRequest(id) {
    return RentalRequestWorkflow.review(id, 'approved');
}

async function declineScheduleRequest(id) {
    return RentalRequestWorkflow.review(id, 'rejected');
}

function renderRentalUnavailable() {
    const select = document.getElementById('rentalUnavailableObjects');
    if (select && select.options.length === 0) {
        select.innerHTML = allRentalObjects.map(obj => `<option value="${escAttr(obj.id)}">${escHtml(obj.object_name || 'Utleieobjekt')}</option>`).join('');
    }
    const list = document.getElementById('rentalUnavailableList');
    if (!list) return;
    const count = document.getElementById('rentalUnavailableCount');
    if (count) count.textContent = String(allRentalUnavailable.length);
    const toggle = document.getElementById('rentalUnavailableListToggle');
    const toggleLabel = document.getElementById('rentalUnavailableListToggleLabel');
    if (toggle) toggle.setAttribute('aria-expanded', rentalUnavailableListExpanded ? 'true' : 'false');
    if (toggleLabel) toggleLabel.textContent = rentalUnavailableListExpanded ? 'Skjul' : 'Vis';
    list.hidden = !rentalUnavailableListExpanded;
    list.innerHTML = allRentalUnavailable.length ? allRentalUnavailable.map(item => {
        const objectName = item.object_name || item.objectName || (item.objects || []).map(obj => obj.object_name || obj.name).filter(Boolean).join(', ') || 'Utleieobjekt';
        const note = String(item.notes || '').trim();
        const description = note || 'Utilgjengelig';
        return `<div class="rental-unavailable rounded-2 p-2 mb-2 d-flex justify-content-between align-items-center gap-2">
            <div class="min-w-0"><strong>${escHtml(objectName)}</strong><div class="small">${formatRentalDisplayDate(item.start_date)} – ${formatRentalDisplayDate(item.end_date)}</div><div class="small rental-unavailable-note" title="${escAttr(description)}">${escHtml(description.length > 100 ? description.slice(0, 97) + '…' : description)}</div></div>
            <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" type="button" onclick="editRentalUnavailable('${escAttr(item.id)}')">Rediger</button><button class="btn btn-outline-danger" type="button" onclick="deleteRentalUnavailable('${escAttr(item.id)}')">Slett</button></div>
        </div>`;
    }).join('') : '<div class="small text-muted">Ingen aktive avstenginger.</div>';
}

async function loadRentalUnavailable() {
    const state = document.getElementById('rentalUnavailableLoadState');
    if (state && allRentalUnavailable.length === 0) state.textContent = 'Laster avstenginger...';
    try {
        const response = await fetch('/api/rental_unavailable.php');
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Kunne ikke laste utilgjengelige perioder');
        rentalUnavailableToday = /^\d{4}-\d{2}-\d{2}$/.test(String(data.today || '')) ? data.today : getRentalOsloToday();
        allRentalUnavailable = (data.periods || []).filter(item => !item.end_date || String(item.end_date).slice(0, 10) >= rentalUnavailableToday);
        if (Array.isArray(data.objects) && allRentalObjects.length === 0) allRentalObjects = data.objects;
        renderRentalUnavailable();
        if (state) state.textContent = '';
        if (calViewStart) renderRentalCalendar();
    } catch (error) {
        if (state) state.innerHTML = `<span class="text-danger">${escHtml(error.message)}</span> <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadRentalUnavailable()">Prøv igjen</button>`;
    }
}

function getRentalOsloToday() {
    try {
        const parts = new Intl.DateTimeFormat('en-CA', {timeZone: 'Europe/Oslo', year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(new Date());
        const values = Object.fromEntries(parts.map(part => [part.type, part.value]));
        return `${values.year}-${values.month}-${values.day}`;
    } catch (_) {
        return calDateStr(new Date());
    }
}

function toggleRentalUnavailableList() {
    rentalUnavailableListExpanded = !rentalUnavailableListExpanded;
    renderRentalUnavailable();
}

function toggleRentalUnavailablePlanning(expanded) {
    const content = document.getElementById('rentalUnavailableContent');
    const toggle = document.getElementById('rentalUnavailablePlanningToggle');
    const label = document.getElementById('rentalUnavailablePlanningToggleLabel');
    if (!content) return;
    const nextExpanded = typeof expanded === 'boolean' ? expanded : content.hidden;
    content.hidden = !nextExpanded;
    toggle?.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    document.getElementById('rentalUnavailablePlanningHeader')?.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    if (label) label.textContent = nextExpanded ? 'Skjul planlegging' : 'Vis planlegging';
}

function getRentalUnavailableDateProxy(id) {
    const previous = document.getElementById(id)?.previousElementSibling;
    return previous?.matches('[data-norwegian-date-time-proxy="1"]') ? previous : null;
}

function clearRentalUnavailableDateValidity() {
    ['rentalUnavailableStart', 'rentalUnavailableEnd'].forEach(id => {
        getRentalUnavailableDateProxy(id)?.setCustomValidity('');
        document.getElementById(id)?.setCustomValidity('');
    });
}

function setRentalUnavailableMode(mode, preserveEnd = false) {
    const isPeriod = mode === 'period';
    const start = document.getElementById('rentalUnavailableStart');
    const end = document.getElementById('rentalUnavailableEnd');
    const endField = document.getElementById('rentalUnavailableEndField');
    const endProxy = getRentalUnavailableDateProxy('rentalUnavailableEnd');
    if (!isPeriod && !preserveEnd && start?.value && end) end.value = start.value;
    if (endField) endField.hidden = !isPeriod;
    if (end) { end.required = isPeriod; end.disabled = !isPeriod; }
    if (endProxy) { endProxy.required = isPeriod; endProxy.disabled = !isPeriod; endProxy.setCustomValidity(''); }
    clearRentalUnavailableDateValidity();
}

function prefillRentalUnavailableDate(date) {
    if (rentalUnavailableSaving) return;
    toggleRentalUnavailablePlanning(true);
    document.getElementById('rentalUnavailableId').value = '';
    document.getElementById('rentalUnavailableSubmitLabel').textContent = 'Marker utilgjengelig';
    const start = document.getElementById('rentalUnavailableStart');
    const end = document.getElementById('rentalUnavailableEnd');
    const mode = document.getElementById('rentalUnavailableModeDay');
    if (mode) mode.checked = true;
    if (start) start.value = date;
    if (end) end.value = date;
    setRentalUnavailableMode('day', true);
    document.getElementById('rentalUnavailableForm')?.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}

function resetRentalUnavailableForm(afterSave = false) {
    if (rentalUnavailableSaving && !afterSave) return;
    const form = document.getElementById('rentalUnavailableForm');
    form?.reset();
    ['rentalUnavailableStart', 'rentalUnavailableEnd'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) { field.value = ''; field.defaultValue = ''; }
    });
    const id = document.getElementById('rentalUnavailableId');
    if (id) id.value = '';
    const label = document.getElementById('rentalUnavailableSubmitLabel');
    if (label) label.textContent = 'Marker utilgjengelig';
    const mode = document.getElementById('rentalUnavailableModeDay');
    if (mode) mode.checked = true;
    const notes = document.getElementById('rentalUnavailableNotes');
    if (notes) notes.value = '';
    clearRentalUnavailableDateValidity();
    setRentalUnavailableMode('day');
}

function editRentalUnavailable(id) {
    if (rentalUnavailableSaving) return;
    const item = allRentalUnavailable.find(entry => String(entry.id) === String(id));
    if (!item) return;
    document.getElementById('rentalUnavailableId').value = item.id;
    document.getElementById('rentalUnavailableStart').value = item.start_date || '';
    document.getElementById('rentalUnavailableEnd').value = item.end_date || '';
    document.getElementById('rentalUnavailableNotes').value = item.notes || '';
    const isSingleDay = String(item.start_date || '').slice(0, 10) === String(item.end_date || '').slice(0, 10);
    const mode = document.getElementById(isSingleDay ? 'rentalUnavailableModeDay' : 'rentalUnavailableModePeriod');
    if (mode) mode.checked = true;
    setRentalUnavailableMode(isSingleDay ? 'day' : 'period', true);
    const selected = new Set([
        ...(item.object_ids || []),
        ...(item.objects || []).map(obj => obj.id),
        item.rental_object_id
    ].filter(Boolean).map(String));
    document.querySelectorAll('#rentalUnavailableObjects option').forEach(option => option.selected = selected.has(String(option.value)));
    document.getElementById('rentalUnavailableSubmitLabel').textContent = 'Lagre endring';
    document.getElementById('rentalUnavailableForm')?.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}

async function saveRentalUnavailable(event) {
    event.preventDefault();
    if (rentalUnavailableSaving) return;
    if (window.validateNorwegianDateTimeInputs && !window.validateNorwegianDateTimeInputs(document.getElementById('rentalUnavailableForm'))) return;
    const objectIds = Array.from(document.getElementById('rentalUnavailableObjects').selectedOptions).map(option => option.value);
    const startDate = document.getElementById('rentalUnavailableStart').value;
    const isPeriod = document.getElementById('rentalUnavailableModePeriod').checked;
    const endDate = isPeriod ? document.getElementById('rentalUnavailableEnd').value : startDate;
    const notes = document.getElementById('rentalUnavailableNotes').value.trim();
    if (!objectIds.length || !startDate || !endDate || endDate < startDate) {
        toast.error(null, 'Ugyldig periode', 'Velg objekter og en gyldig fra-/til-dato.');
        return;
    }
    const id = document.getElementById('rentalUnavailableId').value;
    if (notes.length > 500) {
        toast.error(null, 'Notatet er for langt', 'Stikkord kan inneholde maksimalt 500 tegn.');
        return;
    }
    rentalUnavailableSaving = true;
    const submit = document.getElementById('rentalUnavailableSubmit');
    submit?.classList.add('save-busy');
    if (submit) submit.disabled = true;
    let enabledControls = Array.from(document.getElementById('rentalUnavailableForm').elements)
        .filter(control => !control.disabled);
    enabledControls.forEach(control => { control.disabled = true; });
    try {
        const response = await fetch('/api/rental_unavailable.php' + (id ? `?id=${encodeURIComponent(id)}` : ''), {
            method: id ? 'PUT' : 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': rentalLifecycleCsrf},
            body: JSON.stringify({id, rental_object_ids: objectIds, start_date: startDate, end_date: endDate, notes})
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            toast.error(null, 'Kunne ikke lagre perioden', data.message || 'Prøv igjen.');
            return;
        }
        enabledControls.forEach(control => { control.disabled = false; });
        enabledControls = [];
        resetRentalUnavailableForm(true);
        await loadRentalUnavailable();
    } catch (error) {
        toast.error(null, 'Kunne ikke lagre perioden', 'Nettverksfeil. Verdiene er beholdt, prøv igjen.');
    } finally {
        rentalUnavailableSaving = false;
        enabledControls.forEach(control => { control.disabled = false; });
        submit?.classList.remove('save-busy');
        if (submit) submit.disabled = false;
    }
}

async function deleteRentalUnavailable(id) {
    if (!confirm('Slett denne utilgjengelige perioden?')) return;
    try {
        const response = await fetch(`/api/rental_unavailable.php?id=${encodeURIComponent(id)}`, {
            method:'DELETE',
            headers: {'X-CSRF-Token': rentalLifecycleCsrf}
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            toast.error(null, 'Kunne ikke slette perioden', data.message || 'Prøv igjen.');
            return;
        }
        await loadRentalUnavailable();
    } catch (error) {
        toast.error(null, 'Kunne ikke slette perioden', 'Nettverksfeil. Prøv igjen.');
    }
}

// ─── KOMMENDE UTLEIE — KALENDER ──────────────────────────────────────────────

function calMondayOf(d) {
    const date = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const day = date.getDay();
    const diff = day === 0 ? -6 : 1 - day;
    date.setDate(date.getDate() + diff);
    return date;
}

function calAddDays(d, n) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
}

function calDateStr(d) {
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

function calParseDate(s) {
    if (!s) return null;
    const [y, m, d] = s.substring(0,10).split('-').map(Number);
    return new Date(y, m - 1, d);
}

function calGetMaxDate() {
    const now = calParseDate(rentalUnavailableToday || getRentalOsloToday()) || new Date();
    const sixMonths = new Date(now.getFullYear(), now.getMonth() + 6, 1);
    let lastEnd = new Date(0);
    allApprovedRentals.forEach(r => {
        const e = calParseDate(r.end_date);
        if (e && e > lastEnd) lastEnd = e;
    });
    allPendingRentalRequests.forEach(r => {
        const e = calParseDate(r.end_date);
        if (e && e > lastEnd) lastEnd = e;
    });
    allRentalUnavailable.forEach(item => {
        const e = calParseDate(item.end_date);
        if (e && e > lastEnd) lastEnd = e;
    });
    return lastEnd > sixMonths ? lastEnd : sixMonths;
}

async function loadApprovedSchedule() {
    const calendarHost = document.getElementById('rentalCalendarHost');
    if (!calendarHost) return;
    try {
        const codeResult = await RentalReservationCodes.ensureUpcoming();
        const unresolvedCount = Array.isArray(codeResult?.unresolved_ids) ? codeResult.unresolved_ids.length : 0;
        if (unresolvedCount) {
            toast.error(null, 'Reservasjonskoder trenger oppfølging',
                `${unresolvedCount} kommende utleie(r) mangler en avklart kode.`);
        }
    } catch (error) {
        toast.error(null, 'Kunne ikke klargjøre reservasjonskoder', error.message || 'Oppdater oversikten og prøv igjen.');
    }
    calendarHost.innerHTML = `<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Laster kalender...</div>`;

    try {
        const [approvedResp, pendingResp] = await Promise.all([
            fetch('/api/get_rental_schedule.php?approved=1'),
            fetch('/api/get_rental_schedule.php?approved=0'),
        ]);
        const [data, pendingData] = await Promise.all([approvedResp.json(), pendingResp.json()]);

        if (!approvedResp.ok || !pendingResp.ok || !data.success || !pendingData.success) {
            document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'schedule',success:false}}));
            calendarHost.innerHTML = `<div class="alert alert-danger">Feil ved lasting av utleier og forespørsler <button class="btn btn-sm btn-outline-danger ms-2" type="button" onclick="loadApprovedSchedule()">Prøv igjen</button></div>`;
            return;
        }

        allApprovedRentals = data.requests || [];
        const today = data.today || pendingData.today || getRentalOsloToday();
        allPendingRentalRequests = (pendingData.requests || []).filter(request =>
            request.status === 'pending'
            && (request.end_date || '') >= today
        );
        document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'schedule',success:true}}));
        if (today) rentalUnavailableToday = today;

        if (!calViewStart) calViewStart = calMondayOf(calParseDate(rentalUnavailableToday || getRentalOsloToday()));
        renderRentalCalendar();
    } catch (e) {
        document.dispatchEvent(new CustomEvent('rental-dashboard-source', {detail:{source:'schedule',success:false}}));
        calendarHost.innerHTML = `<div class="alert alert-danger">Feil ved lasting av kommende utleier <button class="btn btn-sm btn-outline-danger ms-2" type="button" onclick="loadApprovedSchedule()">Prøv igjen</button></div>`;
    }
}

function calNavigate(dir) {
    const select = document.getElementById('rentalUpcomingMonth');
    if (!select?.options.length) return;
    select.selectedIndex = Math.max(0, Math.min(select.options.length - 1, select.selectedIndex + dir));
    renderRentalCalendar();
}

function validAssignedRentalCode(value) {
    if (value === null || value === undefined) return '';
    const code = String(value).trim();
    return /^[A-Za-z0-9]{1,6}$/.test(code) && code !== '0' && code !== '0000' ? code : '';
}

function rentalChangeMonth(direction) {
    const select = document.getElementById('rentalUpcomingMonth');
    if (!select) return;
    select.selectedIndex = Math.max(0, Math.min(select.options.length - 1, select.selectedIndex + direction));
    renderRentalCalendar();
}

function rentalMonthlyRows(todayStr) {
    const select = document.getElementById('rentalUpcomingMonth');
    const previous = select?.value || todayStr.slice(0, 7);
    const first = calParseDate(todayStr), max = calGetMaxDate();
    const months = [];
    for (let d = new Date(first.getFullYear(), first.getMonth(), 1); d <= max; d = new Date(d.getFullYear(), d.getMonth() + 1, 1)) {
        const key = calDateStr(d).slice(0, 7);
        months.push(`<option value="${key}">${d.toLocaleDateString('nb-NO', {month:'long', year:'numeric'})}</option>`);
    }
    if (select) {
        select.innerHTML = months.join('');
        select.value = previous;
        if (!select.value) select.selectedIndex = 0;
    }
    const month = select?.value || todayStr.slice(0, 7);
    return allApprovedRentals.filter(r => r.end_date >= todayStr
        && r.start_date.slice(0, 7) <= month && r.end_date.slice(0, 7) >= month);
}

function rentalCheckinStatus(r) {
    if (r.checkin_email_sent_at) return 'Sendt ' + formatRentalDisplayDate(r.checkin_email_sent_at);
    if (r.checkin_queue_status === 'failed') return 'Utsending feilet';
    if (r.checkin_queue_status === 'processing') return 'Sender e-post';
    if (r.checkin_queue_status === 'sent') return 'Sendt';
    if (r.checkin_email_queued_at) return 'I e-postkø';
    if (r.checkin_code_missing) return 'Mangler tildelt kode – innsjekk-e-post kan ikke sendes';
    return 'Ikke sendt';
}

function renderRentalCalendar() {
    const container = document.getElementById('rentalCalendarHost');
    const cardsContainer = document.getElementById('upcomingContractsContainer');
    if (!container || !cardsContainer || !calViewStart) return;
    const todayStr = rentalUnavailableToday || getRentalOsloToday();
    const today = calParseDate(todayStr);
    const maxDate    = calGetMaxDate();
    const rentalsForMonth = rentalMonthlyRows(todayStr);
    const monthSelect = document.getElementById('rentalUpcomingMonth');
    const selectedMonth = monthSelect?.value || todayStr.slice(0, 7);
    const [selectedYear, selectedMonthNumber] = selectedMonth.split('-').map(Number);
    const monthFirst = new Date(selectedYear, selectedMonthNumber - 1, 1);
    const monthLast = new Date(selectedYear, selectedMonthNumber, 0);
    const monthGridStart = calMondayOf(monthFirst);
    calViewStart = monthGridStart;
    const monthGridEnd = calMondayOf(monthLast);
    const monthWeeks = Math.round((monthGridEnd - monthGridStart) / 604800000) + 1;
    const canPrev = Boolean(monthSelect && monthSelect.selectedIndex > 0);
    const canNext = Boolean(monthSelect && monthSelect.selectedIndex < monthSelect.options.length - 1);

    const DAY_LABELS = ['Man','Tir','Ons','Tor','Fre','Lør','Søn'];

    // Assign stable colors per rental
    const colorMap = {};
    allApprovedRentals.forEach((r, i) => { colorMap[r.id] = CAL_COLORS[i % CAL_COLORS.length]; });

    // Render every week touching the selected calendar month.
    let weeksHtml = '';
    for (let w = 0; w < monthWeeks; w++) {
        const wkStart = calAddDays(calViewStart, w * 7);
        const wkEnd   = calAddDays(wkStart, 6);

        // Day header row
        let headerCells = '';
        for (let d = 0; d < 7; d++) {
            const day     = calAddDays(wkStart, d);
            const ds      = calDateStr(day);
            const isToday = ds === todayStr;
            const isInMonth = day.getMonth() === monthFirst.getMonth();
            const isOutsideRange = day < today || day > maxDate || !isInMonth;
            const bg      = !isInMonth ? '#f1f3f4' : isToday ? '#dbeef0' : '#f8fafb';
            const selectable = ds >= todayStr && day <= maxDate && isInMonth;
            headerCells += `<button type="button" class="rental-calendar-day rental-calendar-selectable border-0" ${selectable ? `onclick="prefillRentalUnavailableDate('${ds}')"` : 'disabled'} aria-label="${DAY_LABELS[d]} ${day.getDate()}.${String(day.getMonth()+1).padStart(2,'0')}${isToday ? ', i dag' : ''}${selectable ? ', velg denne dagen for avstenging' : ''}" style="text-align:center;background:${bg};border-right:1px solid #dee2e6!important;border-bottom:1px solid #dee2e6!important;${isOutsideRange ? 'opacity:0.48;' : ''}">
                <span class="d-block text-muted">${DAY_LABELS[d]}</span>
                 <span class="d-block fw-bold${isToday ? ' text-primary' : ''}">${day.getDate()}${isInMonth ? '' : `<span class="visually-hidden">, neste eller forrige måned</span>`}</span>
            </button>`;
        }

        // Find rentals overlapping this week
        const active = [
            ...allApprovedRentals.map(r => ({r, kind:'approved'})),
            ...allPendingRentalRequests.map(r => ({r, kind:'pending'})),
        ].filter(({r}) => {
            const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
            return rs && re && rs <= wkEnd && re >= wkStart;
        }).sort((a, b) => String(a.r.start_date).localeCompare(String(b.r.start_date))
            || (a.kind === b.kind ? 0 : a.kind === 'pending' ? 1 : -1));

        // Assign non-overlapping rows using greedy interval stacking
        const placed = []; // {endCol, row}
        const withRows = active.map(({r, kind}) => {
            const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
            const clampS = rs < wkStart ? wkStart : rs;
            const clampE = re > wkEnd   ? wkEnd   : re;
            const startCol = Math.round((clampS - wkStart) / 86400000) + 1;
            const endCol   = Math.round((clampE - wkStart) / 86400000) + 2;
            // Find lowest row with no conflict
            let row = 1;
            while (placed.some(p => p.row === row && p.endCol > startCol)) row++;
            placed.push({ endCol, row });
            return { r, kind, startCol, endCol, row };
        });
        const maxRow = withRows.length ? Math.max(...withRows.map(item => item.row)) : 0;

        const unavailableRows = allRentalUnavailable.filter(item => {
            const bs = calParseDate(item.start_date), be = calParseDate(item.end_date);
            return bs && be && bs <= wkEnd && be >= wkStart;
        }).map((item, index) => {
            const bs = calParseDate(item.start_date), be = calParseDate(item.end_date);
            const clampS = bs < wkStart ? wkStart : bs;
            const clampE = be > wkEnd ? wkEnd : be;
            const startCol = Math.round((clampS - wkStart) / 86400000) + 1;
            const endCol = Math.round((clampE - wkStart) / 86400000) + 2;
            const row = maxRow + index + 1;
            return {item, startCol, endCol, row};
        });
        const unavailableMaxRow = unavailableRows.length ? Math.max(...unavailableRows.map(x => x.row)) : 0;
        const totalRows = Math.max(maxRow, unavailableMaxRow, 1);
        const rentalAreaHeight = `${totalRows * 48 + 10}px`;

        let bars = '';
        withRows.forEach(({ r, kind, startCol, endCol, row }) => {
            const label = r.object_name || 'Ukjent';
            const short = label.length > 22 ? label.substring(0, 20) + '…' : label;
            if (kind === 'pending') {
                const requestId = encodeURIComponent(String(r.id));
                const customer = r.display_customer_name || r.customer_name || 'Ukjent kunde';
                bars += `<div class="rental-calendar-bar rental-calendar-request" role="button" tabindex="0"
                             style="grid-column:${startCol}/${endCol};grid-row:${row};border-radius:4px;font-size:0.72rem;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;margin:1px 2px;"
                             onclick="showRentalPendingCalDetail(decodeURIComponent('${requestId}'))"
                             onkeydown="handleRentalPendingCalKey(event, decodeURIComponent('${requestId}'))"
                             title="Forespørsel fra ${escAttr(customer)} · ${escAttr(label)} · ${formatRentalDisplayDate(r.start_date)} → ${formatRentalDisplayDate(r.end_date)}">
                       <strong>${escHtml(short)}</strong><span class="d-block small">Forespurt · ${escHtml(customer)}</span>
                   </div>`;
                return;
            }
            const c = colorMap[r.id];
            const code = validAssignedRentalCode(r.key_box_code ?? r.access_code);
            bars += `<div class="rental-calendar-bar" style="grid-column:${startCol}/${endCol};grid-row:${row};background:${c.bg};color:${c.tx};border-radius:4px;font-size:0.72rem;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;margin:1px 2px;"
                         onclick="showRentalCalDetail('${escHtml(r.id)}')"
                         title="${escHtml(label)} — ${formatRentalDisplayDate(r.start_date)} → ${formatRentalDisplayDate(r.end_date)}">
                  <strong>${escHtml(short)}</strong>${code ? `<span class="d-block small">Kode: ${escHtml(code)}</span>` : ''}
            </div>`;
        });
        unavailableRows.forEach(({item, startCol, endCol, row}) => {
            const objectName = item.object_name || (item.objects || []).map(obj => obj.object_name || obj.name).filter(Boolean).join(', ') || 'Utleieobjekt';
            const note = String(item.notes || '').trim();
            const label = note || 'Utilgjengelig';
            const visibleLabel = `${objectName}: ${label}`;
            bars += `<div class="rental-calendar-bar rental-unavailable" style="grid-column:${startCol}/${endCol};grid-row:${row};border-radius:4px;font-size:0.72rem;overflow:hidden;text-overflow:ellipsis;margin:1px 2px;" title="${escAttr(visibleLabel)}"><strong>${escHtml(objectName)}</strong><span class="d-block small">${escHtml(label.length > 54 ? label.slice(0, 51) + '…' : label)}</span></div>`;
        });

        const emptyMsg = active.length === 0 && unavailableRows.length === 0
            ? `<div style="grid-column:1/8;text-align:center;color:#bbb;font-size:0.72rem;padding:6px 0;">Ingen utleier</div>` : '';

        weeksHtml += `<div style="margin-bottom:10px;border:1px solid #dee2e6;border-radius:6px;overflow:hidden;">
            <div class="rental-calendar-grid" style="display:grid;grid-template-columns:repeat(7,1fr);">${headerCells}</div>
            <div class="rental-calendar-grid" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));grid-template-rows:repeat(${totalRows},48px);min-height:${rentalAreaHeight};padding:4px 0;background:#fff;align-items:start;">
                ${bars}${emptyMsg}
            </div>
        </div>`;
    }

    const rangeLbl = monthFirst.toLocaleDateString('nb-NO', {month:'long', year:'numeric'});

    // Legend — rentals overlapping the selected month.
    const viewWindowEnd = monthLast;
    const visibleRentals = allApprovedRentals
        .filter(r => {
            const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
            return rs && re && rs <= viewWindowEnd && re >= monthFirst;
        });
    const visiblePendingRequests = allPendingRentalRequests.filter(r => {
        const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
        return rs && re && rs <= viewWindowEnd && re >= monthFirst;
    });
    const legend = visibleRentals
        .map((r) => {
            const globalIdx = allApprovedRentals.indexOf(r);
            const c = CAL_COLORS[globalIdx % CAL_COLORS.length];
            return `<span class="badge me-1 mb-1" style="background:${c.bg};color:${c.tx};cursor:pointer;font-weight:400;" onclick="showRentalCalDetail(decodeURIComponent('${encodeURIComponent(String(r.id))}'))">${escHtml(r.object_name || 'Ukjent')}</span>`;
        }).join('')
        + (visiblePendingRequests.length
            ? '<span class="badge me-1 mb-1 rental-calendar-request-key">Forespurte</span>'
            : '');
    const upcomingCards = rentalsForMonth.map(r => {
        const id = encodeURIComponent(String(r.id));
        const customerName = escHtml(r.display_customer_name || r.customer_name || 'Ukjent kunde');
        const email = r.display_email
            ? `<a href="mailto:${escAttr(r.display_email)}"><i class="bi bi-envelope me-1"></i>${escHtml(r.display_email)}</a>`
            : '';
        const phone = r.display_phone
            ? `<a href="tel:${escAttr(r.display_phone)}"><i class="bi bi-telephone me-1"></i>${escHtml(r.display_phone)}</a>`
            : '';
        const paymentStatus = r.payment_status || 'unpaid';
        const paymentText = paymentStatus === 'paid' ? 'Betalt' : paymentStatus === 'partial' ? 'Delvis betalt' : 'Ubetalt';
        const paymentClass = paymentStatus === 'paid' ? 'text-bg-success' : paymentStatus === 'partial' ? 'text-bg-info' : 'text-bg-warning';
        const assignedCode = validAssignedRentalCode(r.key_box_code ?? r.access_code);
        const canPromptForNextCode = Boolean(r.can_prepare_code && r.start_date > todayStr);
        const pickupTime = (r.pickup_time || r.start_time || '').slice(0,5);
        const returnTime = (r.return_time || r.end_time || '').slice(0,5);
        return `<article class="rental-upcoming-card" role="button" tabindex="0"
                    aria-label="Vis detaljer for ${escAttr(r.object_name || 'utleieobjekt')} — ${escAttr(customerName)}"
                    onclick="openRentalUpcomingCard(event, decodeURIComponent('${id}'))"
                    onkeydown="handleRentalUpcomingCardKey(event, decodeURIComponent('${id}'))">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate">${customerName}</div>
                    <div class="small text-muted text-truncate">${escHtml(r.object_name || 'Utleieobjekt')}</div>
                    <div class="rental-period">${formatRentalDisplayDate(r.start_date)} – ${formatRentalDisplayDate(r.end_date)}</div>
                    ${pickupTime || returnTime ? `<div class="small text-muted">${escHtml(pickupTime || '–')} – ${escHtml(returnTime || '–')}</div>` : ''}
                </div>
                <span class="badge ${paymentClass} rental-payment-short">${paymentText}</span>
            </div>
            ${email || phone ? `<div class="rental-customer-links small mt-2">${phone}${email}</div>` : ''}
            <div class="small mt-2">
                <div class="fw-semibold">${Number(r.rental_fee || 0).toLocaleString('nb-NO', {maximumFractionDigits:2})} NOK</div>
                <div>${assignedCode ? `Tilgangskode: <span class="rental-access-code">${escHtml(assignedCode)}</span>` : 'Kode ikke tildelt'}${canPromptForNextCode ? ' · Neste utleie for objektet' : ''}</div>
                <div class="${r.checkin_code_missing ? 'text-warning-emphasis' : 'text-muted'}">Innsjekk-e-post: ${escHtml(rentalCheckinStatus(r))}</div>
            </div>
        </article>`;
    }).join('');

    container.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="calNavigate(-1)" ${canPrev ? '' : 'disabled'}>
                <i class="bi bi-chevron-left"></i> Forrige
            </button>
            <span class="fw-semibold small text-capitalize text-muted">${rangeLbl}</span>
            <button class="btn btn-outline-secondary btn-sm" onclick="calNavigate(1)" ${canNext ? '' : 'disabled'}>
                Neste <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        ${weeksHtml}
        <div class="mt-1">${legend}</div>`;
    cardsContainer.innerHTML = upcomingCards || '<div class="col-12 text-center text-muted py-4">Ingen kommende leieforhold i denne perioden.</div>';
}

function openRentalUpcomingCard(event, id) {
    if (event.target.closest('a')) return;
    showRentalCalDetail(id);
}

function handleRentalUpcomingCardKey(event, id) {
    if (event.target !== event.currentTarget || !['Enter', ' '].includes(event.key)) return;
    event.preventDefault();
    showRentalCalDetail(id);
}

function showRentalPendingCalDetail(id) {
    const request = allPendingRentalRequests.find(item => String(item.id) === String(id));
    if (!request) return;
    RentalRequestWorkflow.inspect(request);
}

function handleRentalPendingCalKey(event, id) {
    if (!['Enter', ' '].includes(event.key)) return;
    event.preventDefault();
    showRentalPendingCalDetail(id);
}

function showRentalCalDetail(id, promptCode = true) {
    const r = allApprovedRentals.find(x => String(x.id) === String(id));
    if (!r) return;

    const duration = (() => {
        const s = calParseDate(r.start_date), e = calParseDate(r.end_date);
        if (!s || !e) return '–';
        const d = Math.round((e - s) / 86400000) + 1;
        return `${d} dag${d !== 1 ? 'er' : ''}`;
    })();

    const age = calcAge(r.date_of_birth);
    const ageRow = age !== null
        ? `<dt class="col-sm-4">Alder</dt><dd class="col-sm-8">${age} år</dd>` : '';

    const logistics = r.logistics === 'delivery'
        ? `Levering${r.delivery_address ? ' — ' + escHtml(r.delivery_address) : ''}` : 'Hentes';
    const assignedCode = validAssignedRentalCode(r.key_box_code ?? r.access_code);
    const rentalTotal = parseFloat(r.rental_fee || 0);
    const cleaningFee = parseFloat(r.cleaning_fee || 0);
    const cleaningSelected = Number(r.cleaning || 0) === 1;
    const adjustmentAmount = Math.max(0, Number(r.price_adjustment_cents || 0) / 100);
    const adjustmentValue = r.price_adjustment_type === 'discount'
        ? -adjustmentAmount
        : r.price_adjustment_type === 'surcharge' ? adjustmentAmount : 0;
    const baseRentalFee = Math.max(
        0,
        rentalTotal - (cleaningSelected ? cleaningFee : 0) - adjustmentValue
    );
    const moneyNoDecimals = value => value.toLocaleString('nb-NO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
    const cleaningBreakdown = cleaningSelected && cleaningFee > 0
        ? `<div class="text-muted">+ ${moneyNoDecimals(cleaningFee)} NOK renhold</div>`
        : '<div class="text-muted">Ingen renhold</div>';
    const adjustmentBreakdown = adjustmentAmount > 0 && r.price_adjustment_type
        ? `<div class="text-muted">${r.price_adjustment_type === 'discount' ? '−' : '+'} ${moneyNoDecimals(adjustmentAmount)} NOK Prisjustering${r.price_adjustment_explanation ? ` · ${escHtml(r.price_adjustment_explanation)}` : ''}</div>`
        : '<div class="text-muted">Ingen prisjustering</div>';
    const isPaid = (r.payment_status || 'unpaid') === 'paid';
    const paymentDetails = isPaid
        ? `<div class="small text-muted mt-1">${r.payment_method ? escHtml(r.payment_method) : 'Betalingsmåte ikke oppgitt'}${r.paid_at ? ' · ' + formatRentalDisplayDate(r.paid_at) : ''}</div>`
        : '';
    const paidLabel = isPaid
        ? `Betalt${r.paid_at ? ' (' + formatRentalDisplayDate(r.paid_at) + ')' : ''}`
        : 'Registrer betaling';
    const actionPanel = `
        <div class="rental-toolbar mt-4">
            <div class="d-grid gap-2">
                <div>
                    <label class="form-label small fw-semibold" for="rentalSchedulePaymentMethod">Betalingsmåte</label>
                    <select class="form-select form-select-sm" id="rentalSchedulePaymentMethod" onchange="toggleRentalPaymentButton(this.value)" ${isPaid ? 'disabled' : ''}>
                        <option value="">Laster aktiverte betalingsmåter…</option>
                    </select>
                    <div id="rentalPaymentMethodsState" class="small text-muted mt-1" aria-live="polite"></div>
                </div>
                <div class="d-grid">
                    <button class="btn btn-success btn-sm" type="button" id="rentalSchedulePaymentButton"
                            onclick="registerRentalSchedulePayment('${escAttr(String(r.id))}')"
                            disabled>
                        <i class="bi bi-check-circle me-1"></i>${paidLabel}
                    </button>
                </div>
                <div class="d-grid">
                    <button class="btn btn-outline-danger btn-sm" type="button"
                            onclick="cancelRentalSchedule('${escAttr(String(r.id))}')">
                        <i class="bi bi-x-circle me-1"></i>Avlys leie
                    </button>
                </div>
            </div>
        </div>`;

    document.getElementById('detailsModalTitle').textContent = r.object_name || 'Utleie';
    document.getElementById('detailsModalBody').innerHTML = `
        <dl class="row mb-0 small">
            <dt class="col-sm-4">Kunde</dt>
            <dd class="col-sm-8">${escHtml(r.display_customer_name)}</dd>
            ${r.request_fields?.length ? `<div class="col-12">${rentalRequestFieldsHtml(r.request_fields)}</div>` : ''}
            ${ageRow}
            ${r.display_email ? `<dt class="col-sm-4">E-post</dt><dd class="col-sm-8"><a href="mailto:${escAttr(r.display_email)}">${escHtml(r.display_email)}</a></dd>` : ''}
            ${r.display_phone ? `<dt class="col-sm-4">Telefon</dt><dd class="col-sm-8"><a href="tel:${escAttr(r.display_phone)}">${escHtml(r.display_phone)}</a></dd>` : ''}
            <dt class="col-sm-4">Fra</dt>
            <dd class="col-sm-8">${formatRentalDisplayDate(r.start_date)}${(r.pickup_time || r.start_time) ? ' kl. ' + (r.pickup_time || r.start_time).substring(0,5) : ''}</dd>
            <dt class="col-sm-4">Til</dt>
            <dd class="col-sm-8">${formatRentalDisplayDate(r.end_date)}${(r.return_time || r.end_time) ? ' kl. ' + (r.return_time || r.end_time).substring(0,5) : ''}</dd>
            <dt class="col-sm-4">Varighet</dt>
            <dd class="col-sm-8">${duration}</dd>
            <dt class="col-sm-4">Leiebeløp</dt>
            <dd class="col-sm-8">
                <div>${moneyNoDecimals(baseRentalFee)} NOK</div>
                ${cleaningBreakdown}
                 ${adjustmentBreakdown}
                <div class="fw-semibold mt-1">Totalt: ${moneyNoDecimals(rentalTotal)} NOK</div>
            </dd>
            <dt class="col-sm-4">Betaling</dt>
            <dd class="col-sm-8">${contractPaymentBadge(r.payment_status || 'unpaid')}${paymentDetails}</dd>
             ${assignedCode ? `<dt class="col-sm-4">Tilgangskode</dt><dd class="col-sm-8"><span class="font-monospace">${escHtml(assignedCode)}</span></dd>` : ''}
             <dt class="col-sm-4">Innsjekk-e-post</dt>
             <dd class="col-sm-8">${escHtml(rentalCheckinStatus(r))}</dd>
            <dt class="col-sm-4">Logistikk</dt>
            <dd class="col-sm-8">${logistics}</dd>
            ${r.notes ? `<dt class="col-sm-4">Notater</dt><dd class="col-sm-8">${escHtml(r.notes)}</dd>` : ''}
        </dl>
        <div id="rentalReservationCodeHost" aria-live="polite"></div>
        ${actionPanel}`;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('detailsModal')).show();
    if (!isPaid) loadRentalSchedulePaymentMethods();
    if (promptCode && r.can_prepare_code && r.start_date > getRentalOsloToday()) {
        RentalReservationCodes.open(document.getElementById('rentalReservationCodeHost'), String(r.id));
    }
}

async function loadRentalSchedulePaymentMethods() {
    const select = document.getElementById('rentalSchedulePaymentMethod');
    const state = document.getElementById('rentalPaymentMethodsState');
    const submit = document.getElementById('rentalSchedulePaymentButton');
    if (!select || !state || !submit) return;
    select.disabled = true;
    submit.disabled = true;
    state.textContent = 'Henter betalingsmåter som er aktivert i Økonomi…';
    try {
        const methods = await window.ActivePaymentMethods.load();
        if (!select.isConnected) return;
        select.replaceChildren(new Option(methods.length ? 'Velg betalingsmåte' : 'Ingen betalingsmåter aktivert', ''));
        methods.forEach(method => select.add(new Option(window.ActivePaymentMethods.label(method), method.method_type)));
        select.disabled = methods.length === 0;
        state.textContent = methods.length ? 'Kun aktiverte betalingsmåter kan velges.' : 'Aktiver en betalingsmåte i Økonomi for å registrere betaling.';
    } catch (error) {
        if (!select.isConnected) return;
        select.replaceChildren(new Option('Kunne ikke laste betalingsmåter', ''));
        state.textContent = error.message || 'Kunne ikke hente aktiverte betalingsmåter.';
        state.classList.add('text-danger');
        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'btn btn-link btn-sm p-0';
        retry.textContent = 'Prøv igjen';
        retry.addEventListener('click', loadRentalSchedulePaymentMethods);
        state.append(' ', retry);
    }
}

async function updateRentalScheduleLifecycle(id, action, paymentMethod = null) {
    const toastId = toast.loading(action === 'cancel' ? 'Avlyser leie...' : 'Registrerer betaling...');
    try {
        const response = await fetch('/api/update_rental_schedule_lifecycle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id,
                action,
                csrf_token: rentalLifecycleCsrf,
                ...(paymentMethod ? { payment_method: paymentMethod } : {})
            })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            toast.error(toastId, 'Kunne ikke oppdatere', data.message || 'Prøv igjen.');
            return false;
        }
        toast.success(toastId, 'Oppdatert', data.message);
        bootstrap.Modal.getInstance(document.getElementById('detailsModal'))?.hide();
        await loadApprovedSchedule();
        return true;
    } catch (error) {
        toast.error(toastId, 'Nettverksfeil', 'Kunne ikke kontakte serveren. Prøv igjen.');
        return false;
    }
}

async function registerRentalSchedulePayment(id) {
    const method = document.getElementById('rentalSchedulePaymentMethod')?.value || '';
    if (!method) {
        toast.quickWarning('Velg betalingsmåte', 'Betalingsmåte må velges før betalingen registreres.');
        return;
    }
    await updateRentalScheduleLifecycle(id, 'mark_paid', method);
}

function toggleRentalPaymentButton(method) {
    const button = document.getElementById('rentalSchedulePaymentButton');
    if (button) button.disabled = !method;
}

async function cancelRentalSchedule(id) {
    if (!window.confirm('Vil du avlyse denne leien? Handlingen fjerner den fra kommende leie.')) {
        return;
    }
    await updateRentalScheduleLifecycle(id, 'cancel');
}

async function saveScheduleTime(id, field, value) {
    const toastId = toast.loading('Lagrer tidspunkt...');
    try {
        const body = { id };
        body[field] = value || null;
        const resp = await fetch('/api/update_rental_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await resp.json();
        if (data.success) {
            toast.success(toastId, 'Lagret', 'Tidspunkt er oppdatert');
        } else {
            toast.error(toastId, 'Feil', data.message || 'Kunne ikke lagre tidspunkt');
        }
    } catch (e) {
        toast.error(toastId, 'Feil', 'Nettverksfeil. Prøv igjen.');
    }
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────

function contractStatusBadge(status) {
    const map = {
        draft:     '<span class="badge bg-secondary">Utkast</span>',
        active:    '<span class="badge bg-success">Aktiv</span>',
        completed: '<span class="badge bg-info text-dark">Avsluttet</span>',
        cancelled: '<span class="badge bg-danger">Kansellert</span>',
        rejected:  '<span class="badge bg-danger">Avslått</span>'
    };
    return map[status] || `<span class="badge bg-light text-dark">${status}</span>`;
}

function contractPaymentBadge(paymentStatus) {
    const map = {
        unpaid:  '<span class="badge bg-warning text-dark">Ikke betalt</span>',
        partial: '<span class="badge bg-info text-dark">Delvis betalt</span>',
        paid:    '<span class="badge bg-success">Betalt</span>'
    };
    return map[paymentStatus] || `<span class="badge bg-light text-dark">${paymentStatus}</span>`;
}

function fmtDate(dateStr) {
    if (!dateStr) return '–';
    const [y, m, d] = String(dateStr).split(/[ T]/)[0].split('-');
    return `${d}.${m}.${y}`;
}

function formatRentalDisplayDate(dateStr) {
    if (!dateStr) return '–';
    const match = String(dateStr).trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:$|[T ])/);
    if (!match) return '–';
    return `${match[3]}.${match[2]}.${match[1]}`;
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
