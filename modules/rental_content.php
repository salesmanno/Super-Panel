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
#rental-module .rental-calendar-grid { min-width:680px; }
#rental-module #rentalCalendarHost { overflow-x:auto; }
#rental-module .rental-calendar-day { min-height:62px; padding:.55rem .35rem!important; font-size:.82rem; }
#rental-module .rental-calendar-bar { min-height:38px; line-height:17px!important; padding:3px 7px!important; }
#rental-module .rental-unavailable { background:#fff3cd; border:1px solid #e6c86e; color:#664d03; }
#rental-module .rental-calendar-selectable { cursor:pointer; }
#rental-module .rental-calendar-selectable:hover { background:#e5f4f3!important; }
#rental-module .rental-unavailable-list { border-top:1px solid var(--rental-line); padding-top:.75rem; }
#rental-module .rental-unavailable-note { overflow-wrap:anywhere; }
#rental-module .save-busy { pointer-events:none; opacity:.68; }
@media(max-width:767.98px){#rental-module .btn-group{flex-wrap:wrap}#rental-module .table{min-width:760px}#rental-module .card-body{padding:1rem}.rental-mobile-scroll{overflow-x:auto}#rental-module .rental-calendar-grid{min-width:560px}#rental-module .rental-calendar-day{min-height:52px;padding:.4rem .15rem!important}}
</style>
<link rel="stylesheet" href="/assets/entity-form-fields.css">

<div class="module-content" id="rental-module">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div><div class="rental-kicker">Rental · kontrollsenter</div><h3 class="mb-1">Utleie med oversikt</h3><p class="text-muted mb-0 small">Objekter, forespørsler og kontrakter samlet på ett sted.</p></div>
        <button class="btn btn-primary" type="button" onclick="openRentalCreate()"><i class="bi bi-plus-lg me-2"></i>Nytt utleieobjekt</button>
    </div>
    <div id="rental-objects" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="rental-kicker">Inventar</div><h5 class="card-title mb-0">Utleieobjekter</h5></div><span class="badge rounded-pill text-bg-light border" id="rentalObjectCount">– objekter</span></div>
                
                <div class="mb-3">
                    <select class="form-select form-select-sm" id="categoryFilter" onchange="filterByCategory()" aria-label="Filtrer kategori">
                        <option value="">Alle kategorier</option>
                        <?php foreach ($schemas as $key => $schema): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($schema['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="rentalObjectsContainer">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Laster inn data...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-create" class="subtab-content" style="display: none;">
        <div id="createFormSection">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Opprett nytt utleieobjekt</h5>
                    <form id="createRentalForm">
                        <div id="createFormFieldsContainer"></div>
                        <div id="create-rental-fields-editor" class="mt-3"></div>
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
                <div id="upcomingContractsContainer" class="rental-upcoming-list mb-3" aria-live="polite"></div>
                <div id="rentalCalendarHost" aria-live="polite">
                    <div class="text-center text-muted py-3">Laster kalender...</div>
                </div>
                <div id="rentalUnavailableManager" class="rental-toolbar my-3">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div><div class="rental-kicker">Intern planlegging</div><h6 class="mb-0">Utilgjengelig periode</h6></div>
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="loadRentalUnavailable()"><i class="bi bi-arrow-clockwise"></i><span class="visually-hidden">Oppdater utilgjengelige perioder</span></button>
                    </div>
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
</div>

<!-- Modal for redigering av utleieobjekt -->
<div class="modal fade" id="rentalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rentalModalTitle">Rediger utleieobjekt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rentalForm">
                    <input type="hidden" id="rental_id" name="id">
                    <div id="formFieldsContainer"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="saveRentalObject()">
                    <i class="bi bi-check-circle me-1"></i>Lagre
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rentalFieldsModal" tabindex="-1" aria-labelledby="rentalFieldsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><div class="small text-uppercase text-muted">Dette utleieobjektet</div><h5 class="modal-title" id="rentalFieldsModalTitle">Skjemafelt</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Lukk"></button>
            </div>
            <div class="modal-body"><div id="rental-fields-editor"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Lukk</button></div>
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
<script src="/assets/entity-form-fields.js"></script>
<script>
const rentalSchemas = <?= json_encode($schemas) ?>;
const rentalLifecycleCsrf = <?= json_encode($rentalLifecycleCsrf) ?>;
let rentalCreateFieldsEditor = null;
let rentalEditFieldsEditor = null;
let rentalFormCsrfToken = '';
let rentalCreateSaving = false;
let allRentalObjects = [];
let rentalFilter = '';
let allApprovedRentals = [];
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
    initializeRentalCreateFields();
    loadContracts('all');
    loadRentalRequests();
    loadApprovedSchedule();
    loadRentalUnavailable();
    loadRentalResults();
});

function openRentalCreate() {
    if (rentalCreateSaving) return;
    document.querySelectorAll('.subtab-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById('rental-create');
    if (target) target.style.display = 'block';
    buildCreateForm();
    if (rentalCreateFieldsEditor) {
        rentalCreateFieldsEditor.reset();
        rentalCreateFieldsEditor.refresh().catch(error => console.error('Could not reload rental field defaults:', error));
    }
    pendingCreateData = null;
    document.getElementById('createFormSection')?.scrollIntoView({behavior:'smooth', block:'start'});
}

async function ensureRentalFormToken(objectId) {
    const result = await EntityFormFields.load('rental_object', objectId || null);
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
            renderRentalObjects(allRentalObjects);
            renderRentalUnavailable();
        } else {
            document.getElementById('rentalObjectsContainer').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    ${data.message || 'Ingen utleieobjekter funnet'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading rental objects:', error);
        document.getElementById('rentalObjectsContainer').innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Feil ved lasting av data
            </div>
        `;
    }
}

function filterByCategory() {
    rentalFilter = document.getElementById('categoryFilter').value;
    const filtered = rentalFilter 
        ? allRentalObjects.filter(obj => obj.category === rentalFilter)
        : allRentalObjects;
    renderRentalObjects(filtered);
}

function renderRentalObjects(objects) {
    const container = document.getElementById('rentalObjectsContainer');
    
    if (objects.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Ingen objekter funnet
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            ${objects.map(obj => renderObjectCard(obj)).join('')}
        </div>
    `;
}

function renderObjectCard(obj) {
    const objectToken = encodeURIComponent(String(obj.id));
    const schema = rentalSchemas[obj.category];
    const categoryLabel = schema ? schema.label : obj.category;
    const isAvailable = obj.is_available == 1 || obj.is_available === true;
    const isRented = !isAvailable && obj.display_status === 'rented';
    const toggleChecked = isAvailable ? 'checked' : '';
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
        : `<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
             <i class="bi bi-image fs-1 text-muted"></i>
           </div>`;
    
    let weekendPrice = '';
    if (obj.saturday_price > 0 || obj.sunday_price > 0) {
        const parts = [];
        if (obj.saturday_price > 0) parts.push(`Lør: ${parseFloat(obj.saturday_price).toFixed(0)}`);
        if (obj.sunday_price > 0) parts.push(`Søn: ${parseFloat(obj.sunday_price).toFixed(0)}`);
        weekendPrice = `<br><small class="text-muted">${parts.join(' / ')} NOK/dag</small>`;
    } else if (obj.weekend_price > 0) {
        weekendPrice = `<br><small class="text-muted">Helg: ${parseFloat(obj.weekend_price).toFixed(0)} NOK/dag</small>`;
    }
    
    const hiddenLabel = isRented
        ? '<span class="badge text-bg-light border text-secondary ms-2"><i class="bi bi-lock me-1"></i>Utleid</span>'
        : (!isAvailable ? '<span class="badge text-bg-light border text-secondary ms-2"><i class="bi bi-eye-slash me-1"></i>Inaktiv</span>' : '');
    
    const dailyPrice = parseFloat(obj.rental_price) || 0;
    const defaultDeposit = parseFloat(obj.deposit_amount) || 0;

    return `
        <div class="col" id="rental-card-${escAttr(obj.id)}">
            <div class="card h-100 rental-object-card${cardStateClass}" role="button" tabindex="0"
                 aria-label="Utvid ${escAttr(obj.object_name)}" aria-expanded="false"
                 aria-controls="rental-expanded-${escAttr(obj.id)}"
                 onclick="handleRentalObjectCardClick(event, decodeURIComponent('${objectToken}'))"
                 onkeydown="handleRentalObjectCardKeydown(event, decodeURIComponent('${objectToken}'))">
                ${imageHtml}
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="card-title mb-0 d-inline">${escHtml(obj.object_name)}</h6>
                            <span class="rental-card-status">${hiddenLabel}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge rental-category">${escHtml(categoryLabel)}</span>
                        <div class="form-check form-switch mb-0" title="${isAvailable ? 'Synlig for kunder' : 'Skjult for kunder'}" onclick="event.stopPropagation()">
                            <input class="form-check-input" type="checkbox" role="switch" id="avail-${escAttr(obj.id)}" ${toggleChecked} onclick="event.stopPropagation()" onchange="toggleAvailability(decodeURIComponent('${objectToken}'), this.checked)">
                            <label class="form-check-label small" for="avail-${obj.id}">${isAvailable ? 'Synlig' : 'Skjult'}</label>
                        </div>
                    </div>
                    <div class="mb-2">
                        ${badgesHtml}
                    </div>
                        ${obj.description ? `<p class="card-text text-muted small">${escHtml(obj.description.substring(0, 100))}${obj.description.length > 100 ? '...' : ''}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                    <strong class="rental-price">${dailyPrice.toLocaleString('nb-NO',{maximumFractionDigits:0})} NOK/dag</strong>
                            ${weekendPrice}
                        </div>
                        <div class="btn-group btn-group-sm rental-object-actions">
                            <button class="btn" onclick="event.stopPropagation(); toggleContractForm(decodeURIComponent('${objectToken}'))" title="Kontrakter" id="contract-btn-${escAttr(obj.id)}">
                                <i class="bi bi-file-earmark-text me-1"></i>Opprett kontrakt
                            </button>
                            <button class="btn" onclick="event.stopPropagation(); viewDetails(decodeURIComponent('${objectToken}'))" title="Detaljer">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn rental-delete" onclick="event.stopPropagation(); deleteRentalObject(decodeURIComponent('${objectToken}'))" title="Slett">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2 rental-expand-hint"><i class="bi bi-chevron-down me-1"></i>Trykk på kortet for å utvide</div>
                </div>
                <div id="rental-expanded-${escAttr(obj.id)}" class="rental-object-expanded p-3" hidden onclick="event.stopPropagation()"></div>
            </div>
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
                                <option value="">Ikke valgt</option>
                                <option value="Kontant">Kontant</option>
                                <option value="Vipps">Vipps</option>
                                <option value="Faktura">Faktura</option>
                                <option value="Kortbetaling">Kortbetaling</option>
                                <option value="Bankoverføring">Bankoverføring</option>
                            </select>
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
        </div>
    `;
}

function handleRentalObjectCardClick(event, objectId) {
    if (event.target.closest('button, input, label, select, textarea, a, .form-switch, .rental-object-expanded')) return;
    toggleRentalObjectCard(objectId);
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
    card.setAttribute('aria-expanded', String(opening));
    card.setAttribute('aria-label', `${opening ? 'Lukk' : 'Utvid'} ${obj.object_name}`);
    card.querySelector('.rental-expand-hint').innerHTML = opening
        ? '<i class="bi bi-chevron-up me-1"></i>Trykk på kortet for å lukke'
        : '<i class="bi bi-chevron-down me-1"></i>Trykk på kortet for å utvide';
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
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="openRentalFormFields(decodeURIComponent('${encodeURIComponent(String(objectId))}'))">Skjemafelt</button>
                </div>
            </form>`;
        loadRentalCardAccessCode(objectId, panel);
    } else {
        panel.innerHTML = `<p class="small text-muted mb-2">Administrer opplysninger og kundeinformasjon for dette objektet.</p>
            <div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-sm btn-primary" onclick="editRentalObject(decodeURIComponent('${encodeURIComponent(String(objectId))}'))">Grunnopplysninger</button>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="openRentalFormFields(decodeURIComponent('${encodeURIComponent(String(objectId))}'))">Skjemafelt</button></div>`;
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

function handleRentalObjectCardKeydown(event, objectId) {
    if (event.target !== event.currentTarget || (event.key !== 'Enter' && event.key !== ' ')) {
        return;
    }
    event.preventDefault();
    toggleRentalObjectCard(objectId);
}

function buildCreateForm(category = '', existingData = {}) {
    buildFormFields('createFormFieldsContainer', category, existingData);
}

function buildIntegratedForm(category = '', existingData = {}) {
    buildFormFields('formFieldsContainer', category, existingData);
}

function buildFormFields(containerId, category = '', existingData = {}) {
    const container = document.getElementById(containerId);
    const formId = containerId === 'createFormFieldsContainer' ? 'createRentalForm' : 'rentalForm';
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
                <select class="form-select" name="category" id="${selectId}" required onchange="${rebuildFn}(this.value, getFormData('${formId}'))">
                    <option value="">Velg kategori</option>
    `;
    
    Object.entries(rentalSchemas).forEach(([key, schema]) => {
        html += `<option value="${key}" ${key === category ? 'selected' : ''}>${schema.label}</option>`;
    });
    
    html += `
                </select>
            </div>
        </div>
        
        <h6 class="text-muted mt-3 mb-2"><i class="bi bi-cash-stack me-1"></i>Priser</h6>
        <div class="card bg-light mb-3">
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1">Leiepris per dag (NOK) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" name="rental_price" step="0.01" value="${escAttr(existingData.rental_price || '')}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1">Depositum (NOK)</label>
                        <input type="number" class="form-control form-control-sm" name="deposit_amount" step="0.01" value="${escAttr(existingData.deposit_amount || 0)}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1">Markedspris (NOK)</label>
                        <input type="number" class="form-control form-control-sm" name="market_price" step="0.01" value="${escAttr(existingData.market_price || '')}">
                        <small class="text-muted">Veiledende/innkjøpspris</small>
                    </div>
                </div>
                <div class="mt-1 mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="${containerId}_weekendToggle" 
                            ${(existingData.weekend_price > 0 || existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'checked' : ''}
                            onchange="toggleWeekendPricing(this, '${containerId}')">
                        <label class="form-check-label" for="${containerId}_weekendToggle">Egne helgepriser</label>
                    </div>
                </div>
                <div id="${containerId}_weekendPricing" style="display: ${(existingData.weekend_price > 0 || existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'block' : 'none'};">
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
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Helgepris per dag (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="weekend_price" step="0.01" value="${escAttr(existingData.weekend_price || 0)}">
                            </div>
                        </div>
                    </div>
                    <div id="${containerId}_perDayPrice" style="display: ${(existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'block' : 'none'};">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Lørdagspris (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="saturday_price" step="0.01" value="${escAttr(existingData.saturday_price || 0)}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Søndagspris (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="sunday_price" step="0.01" value="${escAttr(existingData.sunday_price || 0)}">
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
            const value = existingData.type_data?.[fieldName] || '';
            html += generateIntegratedFieldHTML(fieldName, field, value);
        });
        if (category === 'venue' && !Object.prototype.hasOwnProperty.call(schema.fields, 'access_code')) {
            html += generateIntegratedFieldHTML('access_code', {
                type: 'text',
                label: 'Tilgangskode',
                required: false
            }, existingData.type_data?.access_code || '');
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
}

function generateIntegratedFieldHTML(fieldName, field, value = '') {
    const required = field.required ? '<span class="text-danger">*</span>' : '';
    const requiredAttr = field.required ? 'required' : '';
    
    let inputHTML = '';
    
    switch (field.type) {
        case 'text':
            inputHTML = `<input type="text" class="form-control" name="type_data[${escAttr(fieldName)}]" value="${escAttr(value || '')}" ${field.maxlength ? `maxlength="${Number(field.maxlength)}"` : ''} ${requiredAttr}>`;
            break;
        case 'number':
            const step = field.step || '1';
            inputHTML = `<input type="number" class="form-control" name="type_data[${escAttr(fieldName)}]" step="${escAttr(step)}" value="${escAttr(value || '')}" ${requiredAttr}>`;
            break;
        case 'checkbox':
            const checked = value === true || value === 1 || value === '1' ? 'checked' : '';
            return `
                <div class="col-md-6 mb-3">
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
                    ${field.options.map(opt => `<option value="${escAttr(opt)}" ${value === opt ? 'selected' : ''}>${escHtml(opt)}</option>`).join('')}
                </select>
            `;
            break;
        case 'textarea':
            inputHTML = `<textarea class="form-control" name="type_data[${escAttr(fieldName)}]" rows="2" ${requiredAttr}>${escHtml(value || '')}</textarea>`;
            break;
        default:
            inputHTML = `<input type="text" class="form-control" name="type_data[${escAttr(fieldName)}]" value="${escAttr(value || '')}" ${requiredAttr}>`;
    }
    
    return `
        <div class="col-md-6 mb-3">
            <label class="form-label">${escHtml(field.label)} ${required}</label>
            ${inputHTML}
        </div>
    `;
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
    
    const data = {
        object_name: formData.get('object_name') || '',
        rental_price: formData.get('rental_price') || '',
        weekend_price: formData.get('weekend_price') || 0,
        saturday_price: formData.get('saturday_price') || 0,
        sunday_price: formData.get('sunday_price') || 0,
        deposit_amount: formData.get('deposit_amount') || 0,
        market_price: formData.get('market_price') || '',
        description: formData.get('description') || '',
        rental_text: formData.get('rental_text') || '',
        is_available: formData.get('is_available') === 'on',
        images: collectImagesFromForm(form, formId === 'createRentalForm' ? 'createFormFieldsContainer' : 'formFieldsContainer'),
        type_data: {}
    };
    
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
    
    return data;
}

function getCurrentFormData() {
    return getFormData('rentalForm');
}

async function saveRentalObject() {
    const saveButton = document.querySelector('#rentalModal .modal-footer .btn-primary');
    if (saveButton?.disabled) return;
    const form = document.getElementById('rentalForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const id = formData.get('id');
    const isEdit = id && id !== '';
    
    const data = {
        object_name: formData.get('object_name'),
        category: formData.get('category'),
        rental_price: parseFloat(formData.get('rental_price')),
        weekend_price: parseFloat(formData.get('weekend_price')) || 0,
        saturday_price: parseFloat(formData.get('saturday_price')) || 0,
        sunday_price: parseFloat(formData.get('sunday_price')) || 0,
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        market_price: parseFloat(formData.get('market_price')) || null,
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
    
    if (!category) {
        toast.quickError('Feil', 'Du må velge en kategori');
        return;
    }
    
    const data = {
        object_name: formData.get('object_name'),
        category: category,
        rental_price: parseFloat(formData.get('rental_price')),
        weekend_price: parseFloat(formData.get('weekend_price')) || 0,
        saturday_price: parseFloat(formData.get('saturday_price')) || 0,
        sunday_price: parseFloat(formData.get('sunday_price')) || 0,
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        market_price: parseFloat(formData.get('market_price')) || null,
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
    summaryHTML += `<tr><td class="text-muted">Leiepris/dag</td><td><strong class="text-success">${data.rental_price.toFixed(0)} NOK</strong></td></tr>`;
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
            document.getElementById('createRentalForm').reset();
            buildCreateForm();
            rentalCreateFieldsEditor?.reset();
            rentalCreateFieldsEditor?.refresh().catch(error => console.warn('Could not reload rental defaults:', error));
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
    const obj = allRentalObjects.find(o => o.id === objectId);
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
            const card = document.getElementById(`rental-card-${objectId}`)?.querySelector('.rental-object-card');
            if (card) {
                card.classList.toggle('is-muted', !isAvailable);
                card.classList.toggle('is-rented', !isAvailable && obj.display_status === 'rented');
                card.querySelector('.rental-card-status').innerHTML = isAvailable ? ''
                    : obj.display_status === 'rented'
                        ? '<span class="badge text-bg-light border text-secondary ms-2"><i class="bi bi-lock me-1"></i>Utleid</span>'
                        : '<span class="badge text-bg-light border text-secondary ms-2"><i class="bi bi-eye-slash me-1"></i>Inaktiv</span>';
                const switchLabel = card.querySelector('.form-switch .form-check-label');
                if (switchLabel) switchLabel.textContent = isAvailable ? 'Synlig' : 'Skjult';
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
    document.getElementById('rentalModalTitle').textContent = 'Grunnopplysninger';
    document.getElementById('rental_id').value = obj.id;
    
    buildIntegratedForm(obj.category, obj);
    
    const modal = new bootstrap.Modal(document.getElementById('rentalModal'));
    modal.show();
}

async function openRentalFormFields(objectId) {
    const obj = allRentalObjects.find(item => String(item.id) === String(objectId));
    const title = document.getElementById('rentalFieldsModalTitle');
    if (title) title.textContent = `Skjemafelt · ${obj?.object_name || 'Utleieobjekt'}`;
    const host = document.getElementById('rental-fields-editor');
    if (!host) return;
    rentalEditFieldsEditor = EntityFormFields.mount(host, {entityType: 'rental_object', entityId: objectId});
    try {
        await rentalEditFieldsEditor.refresh();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalFieldsModal')).show();
    } catch (error) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalFieldsModal')).show();
    }
}

async function deleteRentalObject(objectId) {
    const obj = allRentalObjects.find(o => o.id === objectId);
    if (!confirm(`Er du sikker på at du vil slette "${obj?.object_name}"?`)) {
        return;
    }
    
    const toastId = toast.loading('Sletter objekt...');
    
    try {
        const response = await fetch('/api/delete_rental_object.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: objectId })
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
        toast.error(toastId, 'Feil', 'Kunne ikke slette objekt');
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
    
    let detailsHTML = `
        <div class="row">
            <div class="col-md-6">
                ${detailPrimary ? `<img src="${escAttr(detailPrimary.url)}" class="img-fluid rounded mb-2" alt="${escAttr(obj.object_name)}" id="detailMainImage">` : ''}
                ${safeDetailImages.length > 1 ? `<div class="d-flex gap-2 flex-wrap mb-3">${safeDetailImages.map((img) => `<img src="${escAttr(img.url)}" class="img-thumbnail detail-thumb" data-detail-url="${escAttr(img.url)}" style="max-height: 60px; cursor: pointer;${img === detailPrimary ? ' border: 2px solid #0d6efd;' : ''}" title="${escAttr(img.caption || '')}" alt="">`).join('')}</div>` : ''}
                <p><strong>Kategori:</strong> ${escHtml(schema?.label || obj.category)}</p>
                <p><strong>Leiepris (ukedag):</strong> ${parseFloat(obj.rental_price).toFixed(0)} NOK/dag</p>
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
        } else {
            container.innerHTML = `<div class="alert alert-warning">${escHtml(data.message || 'Feil ved henting av kontrakter')}</div>`;
        }
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger">Feil ved lasting av kontrakter</div>`;
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

function refreshRentalRequestPrice(row) {
    const baseEl = row.querySelector('.rental-request-base-fee');
    const totalEl = row.querySelector('.rental-request-total');
    const cleaningEl = row.querySelector('.rental-request-cleaning-fee');
    const adjustmentEl = row.querySelector('.rental-request-adjustment-summary');
    const errorEl = row.querySelector('.rental-request-price-error');
    const approveButton = row.querySelector('.rental-request-approve');
    try {
        const adjustment = rentalAdjustmentFromRow(row);
        const quote = RentalRequestPricing.calculateRentalQuote({
            rates: {
                rental_price: row.dataset.dailyPrice,
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

    try {
        const resp = await fetch('/api/get_rental_schedule.php?approved=0');
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            container.innerHTML = `<div class="alert alert-danger">
                Kunne ikke laste forespørslene. Databasen kan være midlertidig utilgjengelig.
            </div>`;
            return;
        }

        if (!data.requests || data.requests.length === 0) {
            container.innerHTML = `<div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Ingen ventende forespørsler
            </div>`;
            return;
        }

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
            const endTime = (r.end_time || '').substring(0, 5);

            return `<tr data-rental-request-id="${escHtml(r.id)}"
                        data-object-id="${escHtml(r.rental_object_id || '')}"
                        data-daily-price="${escAttr(r.object_daily_price ?? '')}"
                        data-weekend-price="${escAttr(r.object_weekend_price ?? '')}"
                        data-saturday-price="${escAttr(r.object_saturday_price ?? '')}"
                        data-sunday-price="${escAttr(r.object_sunday_price ?? '')}"
                        data-base-rental-fee="${baseRentalFee}"
                        data-cleaning-fee="${escAttr(r.cleaning_fee ?? '')}">
                <td class="small">
                    <div class="fw-semibold">${escHtml(r.object_name || '–')}</div>
                    ${r.object_category ? `<span class="badge bg-light text-dark border">${escHtml(r.object_category)}</span>` : ''}
                </td>
                <td class="small">
                    <div>${escHtml(r.display_customer_name)}${ageStr}</div>
                    ${contactInfo}
                    ${rentalRequestFieldsHtml(r.request_fields, true)}
                </td>
                <td class="small">
                    <div class="d-flex flex-column gap-1" style="min-width:8.5rem">
                        <input type="date" class="form-control form-control-sm rental-request-start-date"
                               data-rental-request-field="start-date"
                               value="${escHtml(r.start_date || '')}" aria-label="Startdato">
                        <span class="badge text-bg-light border py-2">Henting kl. 08:00</span>
                    </div>
                </td>
                <td class="small">
                    <div class="d-flex flex-column gap-1" style="min-width:8.5rem">
                        <input type="date" class="form-control form-control-sm rental-request-end-date"
                               data-rental-request-field="end-date"
                               value="${escHtml(r.end_date || '')}" aria-label="Sluttdato">
                        <input type="time" class="form-control form-control-sm rental-request-end-time"
                               data-rental-request-field="end-time"
                               value="${escHtml(endTime)}" aria-label="Sluttid">
                    </div>
                </td>
                <td class="small">
                    ${logisticsLabel}
                    ${deliveryAddr}
                </td>
                <td class="small" style="min-width:9rem">
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
                <td class="small text-nowrap text-muted">${createdAt}</td>
                <td class="small">${notesCell || '<span class="text-muted">–</span>'}</td>
                <td class="text-nowrap">
                    <button class="btn btn-success btn-sm me-1 rental-request-approve" onclick="approveScheduleRequest('${escHtml(r.id)}')">
                        <i class="bi bi-check-lg me-1"></i>Godkjenn
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="declineScheduleRequest('${escHtml(r.id)}')">
                        <i class="bi bi-x-lg me-1"></i>Avslå
                    </button>
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
        });
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger">Feil ved lasting av forespørsler</div>`;
    }
}

function getRentalRequestInlinePayload(id) {
    const row = document.querySelector(`[data-rental-request-id="${CSS.escape(String(id))}"]`);
    if (!row) throw new Error('Forespørselsraden ble ikke funnet');

    const startDate = row.querySelector('[data-rental-request-field="start-date"]')?.value || '';
    const endDate = row.querySelector('[data-rental-request-field="end-date"]')?.value || '';
    const endTime = row.querySelector('[data-rental-request-field="end-time"]')?.value || '';
    if (!startDate || !endDate || !endTime) {
        throw new Error('Fra-, til- og sluttid må fylles ut');
    }
    if (`${endDate}T${endTime}` < `${startDate}T08:00`) {
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
        pickup_time: '08:00',
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
    const toastId = toast.loading('Godkjenner forespørsel...');
    try {
        const inlineData = getRentalRequestInlinePayload(id);
        setRentalRequestRowBusy(id, true);
        const resp = await fetch('/api/update_rental_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, ...inlineData, approved: 1, status: 'approved' })
        });
        const data = await resp.json();
        if (data.success) {
            const approvedFee = Number(data.rental_fee);
            const feeLabel = Number.isFinite(approvedFee)
                ? `${approvedFee.toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:2})} NOK`
                : 'ikke tilgjengelig';
            const msg = data.email_queued
                ? `Forespørselen er godkjent — bekreftelse lagt i e-postkø. Serverberegnet leiebeløp: ${feeLabel}.`
                : `Forespørselen er godkjent. Serverberegnet leiebeløp: ${feeLabel}.`;
            toast.success(toastId, 'Godkjent', msg);
            loadRentalRequests();
            loadApprovedSchedule();
        } else {
            const title = data.already_approved ? 'Allerede godkjent' : 'Feil';
            toast.error(toastId, title, data.message || 'Kunne ikke godkjenne forespørsel');
            setRentalRequestRowBusy(id, false);
        }
    } catch (e) {
        setRentalRequestRowBusy(id, false);
        toast.error(toastId, 'Feil', e.message || 'Nettverksfeil. Prøv igjen.');
    }
}

async function declineScheduleRequest(id) {
    if (!confirm('Er du sikker på at du vil avslå denne forespørselen?')) return;
    const toastId = toast.loading('Avslår forespørsel...');
    try {
        setRentalRequestRowBusy(id, true);
        const resp = await fetch('/api/update_rental_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, approved: 1, status: 'rejected' })
        });
        const data = await resp.json();
        if (data.success) {
            const msg = data.email_queued
                ? 'Forespørselen er avslått — beskjed lagt i e-postkø'
                : 'Forespørselen er avslått';
            toast.success(toastId, 'Avslått', msg);
            loadRentalRequests();
        } else {
            toast.error(toastId, 'Feil', data.message || 'Kunne ikke avslå forespørsel');
            setRentalRequestRowBusy(id, false);
        }
    } catch (e) {
        setRentalRequestRowBusy(id, false);
        toast.error(toastId, 'Feil', e.message || 'Nettverksfeil. Prøv igjen.');
    }
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
    allRentalUnavailable.forEach(item => {
        const e = calParseDate(item.end_date);
        if (e && e > lastEnd) lastEnd = e;
    });
    return lastEnd > sixMonths ? lastEnd : sixMonths;
}

async function loadApprovedSchedule() {
    const calendarHost = document.getElementById('rentalCalendarHost');
    if (!calendarHost) return;
    calendarHost.innerHTML = `<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Laster kalender...</div>`;

    try {
        const resp = await fetch('/api/get_rental_schedule.php?approved=1');
        const data = await resp.json();

        if (!data.success) {
            calendarHost.innerHTML = `<div class="alert alert-danger">Feil ved lasting av utleier <button class="btn btn-sm btn-outline-danger ms-2" type="button" onclick="loadApprovedSchedule()">Prøv igjen</button></div>`;
            return;
        }

        allApprovedRentals = data.requests || [];

        if (!calViewStart) calViewStart = calMondayOf(calParseDate(rentalUnavailableToday || getRentalOsloToday()));
        renderRentalCalendar();
    } catch (e) {
        calendarHost.innerHTML = `<div class="alert alert-danger">Feil ved lasting av kommende utleier <button class="btn btn-sm btn-outline-danger ms-2" type="button" onclick="loadApprovedSchedule()">Prøv igjen</button></div>`;
    }
}

function calNavigate(dir) {
    if (!calViewStart) return;
    calViewStart = calAddDays(calViewStart, dir * 14);
    renderRentalCalendar();
}

function validAssignedRentalCode(value) {
    if (value === null || value === undefined) return '';
    const code = String(value).trim();
    return /^[A-Za-z0-9]{1,6}$/.test(code) && code !== '0' && code !== '0000' ? code : '';
}

function renderRentalCalendar() {
    const container = document.getElementById('rentalCalendarHost');
    const cardsContainer = document.getElementById('upcomingContractsContainer');
    if (!container || !cardsContainer || !calViewStart) return;
    const todayStr = rentalUnavailableToday || getRentalOsloToday();
    const today = calParseDate(todayStr);
    const maxDate    = calGetMaxDate();
    const minStart   = calMondayOf(today);

    const canPrev   = calViewStart > minStart;
    const nextStart = calAddDays(calViewStart, 14);
    const canNext   = calAddDays(nextStart, 13) <= maxDate;

    const DAY_LABELS = ['Man','Tir','Ons','Tor','Fre','Lør','Søn'];

    // Assign stable colors per rental
    const colorMap = {};
    allApprovedRentals.forEach((r, i) => { colorMap[r.id] = CAL_COLORS[i % CAL_COLORS.length]; });

    // Build the 2-week grid
    let weeksHtml = '';
    for (let w = 0; w < 2; w++) {
        const wkStart = calAddDays(calViewStart, w * 7);
        const wkEnd   = calAddDays(wkStart, 6);

        // Day header row
        let headerCells = '';
        for (let d = 0; d < 7; d++) {
            const day     = calAddDays(wkStart, d);
            const ds      = calDateStr(day);
            const isToday = ds === todayStr;
            const isPast  = day > maxDate;
            const bg      = isPast ? '#f0f0f0' : isToday ? '#dbeafe' : '#f8f9fa';
            const selectable = ds >= todayStr && day <= maxDate;
            headerCells += `<button type="button" class="rental-calendar-day rental-calendar-selectable border-0${selectable ? '' : ' pe-none'}" ${selectable ? `onclick="prefillRentalUnavailableDate('${ds}')"` : 'disabled'} aria-label="${DAY_LABELS[d]} ${day.getDate()}.${String(day.getMonth()+1).padStart(2,'0')}${isToday ? ', i dag' : ''}${selectable ? ', velg denne dagen for avstenging' : ''}" style="text-align:center;background:${bg};border-right:1px solid #dee2e6!important;border-bottom:1px solid #dee2e6!important;${isPast ? 'opacity:0.45;' : ''}">
                <span class="d-block text-muted">${DAY_LABELS[d]}</span>
                <span class="d-block fw-bold${isToday ? ' text-primary' : ''}">${day.getDate()}.${String(day.getMonth()+1).padStart(2,'0')}</span>
            </button>`;
        }

        // Find rentals overlapping this week
        const active = allApprovedRentals.filter(r => {
            const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
            return rs && re && rs <= wkEnd && re >= wkStart;
        });

        // Assign non-overlapping rows using greedy interval stacking
        const placed = []; // {endCol, row}
        const withRows = active.map(r => {
            const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
            const clampS = rs < wkStart ? wkStart : rs;
            const clampE = re > wkEnd   ? wkEnd   : re;
            const startCol = Math.round((clampS - wkStart) / 86400000) + 1;
            const endCol   = Math.round((clampE - wkStart) / 86400000) + 2;
            // Find lowest row with no conflict
            let row = 1;
            while (placed.some(p => p.row === row && p.endCol > startCol)) row++;
            placed.push({ endCol, row });
            return { r, startCol, endCol, row };
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
        const rentalAreaHeight = `${totalRows * 42 + 8}px`;

        let bars = '';
        withRows.forEach(({ r, startCol, endCol, row }) => {
            const c     = colorMap[r.id];
            const label = r.object_name || 'Ukjent';
            const short = label.length > 22 ? label.substring(0, 20) + '…' : label;
            const code = validAssignedRentalCode(r.key_box_code);
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
            <div class="rental-calendar-grid" style="display:grid;grid-template-columns:repeat(7,1fr);grid-template-rows:repeat(${totalRows},42px);min-height:${rentalAreaHeight};padding:4px 0;background:#fff;align-items:start;">
                ${bars}${emptyMsg}
            </div>
        </div>`;
    }

    // Date range label
    const endOfView = calAddDays(calViewStart, 13);
    const rangeLbl  = `${calViewStart.getDate()}.${String(calViewStart.getMonth()+1).padStart(2,'0')}.${calViewStart.getFullYear()} – ${endOfView.getDate()}.${String(endOfView.getMonth()+1).padStart(2,'0')}.${endOfView.getFullYear()}`;

    // Legend — only rentals visible in current 2-week window
    const viewWindowEnd = calAddDays(calViewStart, 13);
    const visibleRentals = allApprovedRentals
        .filter(r => {
            const rs = calParseDate(r.start_date), re = calParseDate(r.end_date);
            return rs && re && rs <= viewWindowEnd && re >= calViewStart;
        });
    const legend = visibleRentals
        .map((r) => {
            const globalIdx = allApprovedRentals.indexOf(r);
            const c = CAL_COLORS[globalIdx % CAL_COLORS.length];
            return `<span class="badge me-1 mb-1" style="background:${c.bg};color:${c.tx};cursor:pointer;font-weight:400;" onclick="showRentalCalDetail(decodeURIComponent('${encodeURIComponent(String(r.id))}'))">${escHtml(r.object_name || 'Ukjent')}</span>`;
        }).join('');
    const upcomingCards = visibleRentals.map(r => {
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
        return `<article class="rental-upcoming-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate">${customerName}</div>
                    <div class="small text-muted text-truncate">${escHtml(r.object_name || 'Utleieobjekt')}</div>
                    <div class="rental-period">${formatRentalDisplayDate(r.start_date)} – ${formatRentalDisplayDate(r.end_date)}</div>
                </div>
                <span class="badge ${paymentClass} rental-payment-short">${paymentText}</span>
            </div>
            ${email || phone ? `<div class="rental-customer-links small mt-2">${phone}${email}</div>` : ''}
            <div class="d-flex justify-content-end mt-2">
                <button class="btn btn-sm btn-outline-primary" type="button" onclick="showRentalCalDetail(decodeURIComponent('${id}'))">Detaljer</button>
            </div>
        </article>`;
    }).join('');

    container.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="calNavigate(-1)" ${canPrev ? '' : 'disabled'}>
                <i class="bi bi-chevron-left"></i> Forrige
            </button>
            <span class="fw-semibold small text-muted">${rangeLbl}</span>
            <button class="btn btn-outline-secondary btn-sm" onclick="calNavigate(1)" ${canNext ? '' : 'disabled'}>
                Neste <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        ${weeksHtml}
        <div class="mt-1">${legend}</div>`;
    cardsContainer.innerHTML = upcomingCards || '<div class="col-12 text-center text-muted py-4">Ingen kommende leieforhold i denne perioden.</div>';
}

function showRentalCalDetail(id) {
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
    const assignedCode = validAssignedRentalCode(r.key_box_code);
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
                        <option value="">Velg betalingsmåte</option>
                        <option value="vipps">Vipps</option>
                        <option value="card">Kort</option>
                        <option value="cash">Kontant</option>
                        <option value="invoice">Faktura</option>
                        <option value="bank_transfer">Bankoverføring</option>
                        <option value="other">Annet</option>
                    </select>
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
            <dd class="col-sm-8">${formatRentalDisplayDate(r.start_date)}${r.pickup_time ? ' kl. ' + r.pickup_time.substring(0,5) : ''}</dd>
            <dt class="col-sm-4">Til</dt>
            <dd class="col-sm-8">${formatRentalDisplayDate(r.end_date)}${r.end_time ? ' kl. ' + r.end_time.substring(0,5) : ''}</dd>
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
             <dd class="col-sm-8">${r.checkin_email_sent_at
                 ? `<span class="rental-checkin-sent"><i class="bi bi-envelope-check me-1"></i>Sendt ${formatRentalDisplayDate(r.checkin_email_sent_at)}</span>`
                 : '<span class="text-muted"><i class="bi bi-envelope me-1"></i>Ikke sendt</span>'}</dd>
            <dt class="col-sm-4">Logistikk</dt>
            <dd class="col-sm-8">${logistics}</dd>
            ${r.notes ? `<dt class="col-sm-4">Notater</dt><dd class="col-sm-8">${escHtml(r.notes)}</dd>` : ''}
        </dl>
        ${actionPanel}`;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('detailsModal')).show();
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
