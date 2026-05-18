<?php
require_once __DIR__ . '/../includes/rental_schemas.php';
$schemas = getRentalSchemas();
?>

<div class="module-content">
    <div id="rental-objects" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Utleieobjekter</h5>
                
                <div class="mb-3">
                    <select class="form-select form-select-sm" id="categoryFilter" onchange="filterByCategory()">
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
                <div id="upcomingContractsContainer">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Laster inn data...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-results" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Utleie resultat</h5>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Total omsetning</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Antall utleier</small>
                                <h4 class="mb-0">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Denne måneden</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Gj.snitt per utleie</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Statistikk vil vises når du har registrert utleier
                </div>
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

<script>
const rentalSchemas = <?= json_encode($schemas) ?>;
let allRentalObjects = [];
let rentalFilter = '';

document.addEventListener('DOMContentLoaded', function() {
    loadRentalObjects();
    buildCreateForm();
    loadContracts('all');
    loadRentalRequests();
    loadApprovedSchedule();
});

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
            allRentalObjects.forEach(obj => {
                if (typeof obj.images === 'string') {
                    try { obj.images = JSON.parse(obj.images); } catch(e) { obj.images = []; }
                }
                if (!Array.isArray(obj.images)) obj.images = [];
            });
            renderRentalObjects(allRentalObjects);
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
    const schema = rentalSchemas[obj.category];
    const categoryLabel = schema ? schema.label : obj.category;
    const isAvailable = obj.is_available == 1 || obj.is_available === true;
    const isRented = !isAvailable && obj.display_status === 'rented';
    const toggleChecked = isAvailable ? 'checked' : '';
    const cardOpacity = isAvailable ? '' : 'opacity: 0.65;';
    const cardBorder = isRented ? 'border: 2px solid #198754;' : (isAvailable ? '' : 'border: 2px dashed #dee2e6;');
    
    let badges = obj.badges || [];
    const badgesHtml = badges.length > 0 
        ? badges.map(b => `<span class="badge bg-info me-1">${b}</span>`).join('')
        : '';
    
    const primaryImage = (obj.images && obj.images.length > 0) 
        ? obj.images.find(img => img.is_primary) || obj.images[0] 
        : (obj.image_url ? {url: obj.image_url} : null);
    const imageCount = obj.images ? obj.images.length : (obj.image_url ? 1 : 0);
    
    const imageHtml = primaryImage 
        ? `<div class="position-relative">
             <img src="${primaryImage.url}" class="card-img-top" style="height: 200px; object-fit: cover;${isAvailable ? '' : ' filter: grayscale(70%);'}" alt="${obj.object_name}">
             ${imageCount > 1 ? `<span class="position-absolute top-0 end-0 badge bg-dark bg-opacity-75 m-2"><i class="bi bi-images me-1"></i>${imageCount}</span>` : ''}
           </div>`
        : `<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
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
        ? '<span class="badge bg-success ms-2"><i class="bi bi-lock me-1"></i>Utleid</span>'
        : (!isAvailable ? '<span class="badge bg-warning text-dark ms-2"><i class="bi bi-eye-slash me-1"></i>Inaktiv</span>' : '');
    
    const dailyPrice = parseFloat(obj.rental_price) || 0;
    const defaultDeposit = parseFloat(obj.deposit_amount) || 0;

    return `
        <div class="col" id="rental-card-${obj.id}">
            <div class="card h-100" style="${cardBorder}${cardOpacity}">
                ${imageHtml}
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="card-title mb-0 d-inline">${obj.object_name}</h6>
                            ${hiddenLabel}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary">${categoryLabel}</span>
                        <div class="form-check form-switch mb-0" title="${isAvailable ? 'Synlig for kunder' : 'Skjult for kunder'}">
                            <input class="form-check-input" type="checkbox" role="switch" id="avail-${obj.id}" ${toggleChecked} onchange="toggleAvailability('${obj.id}', this.checked)">
                            <label class="form-check-label small" for="avail-${obj.id}">${isAvailable ? 'Synlig' : 'Skjult'}</label>
                        </div>
                    </div>
                    <div class="mb-2">
                        ${badgesHtml}
                    </div>
                    ${obj.description ? `<p class="card-text text-muted small">${obj.description.substring(0, 100)}${obj.description.length > 100 ? '...' : ''}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <strong class="text-primary">${dailyPrice.toFixed(0)} NOK/dag</strong>
                            ${weekendPrice}
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success" onclick="toggleContractForm('${obj.id}')" title="Kontrakter" id="contract-btn-${obj.id}">
                                <i class="bi bi-file-earmark-text me-1"></i>Opprett kontrakt
                            </button>
                            <button class="btn btn-outline-primary" onclick="viewDetails('${obj.id}')" title="Detaljer">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="editRentalObject('${obj.id}')" title="Rediger">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteRentalObject('${obj.id}')" title="Slett">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="contract-panel-${obj.id}" class="card mt-2 border-success" style="display: none;">
                <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 text-success"><i class="bi bi-file-earmark-text me-1"></i>Kontrakter – ${obj.object_name}</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleContractForm('${obj.id}')"><i class="bi bi-x"></i></button>
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
                            <button class="btn btn-success btn-sm" onclick="submitContract('${obj.id}')">
                                <i class="bi bi-check-circle me-1"></i>Lagre kontrakt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
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
                <input type="text" class="form-control" name="object_name" value="${existingData.object_name || ''}" required>
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
                        <input type="number" class="form-control form-control-sm" name="rental_price" step="0.01" value="${existingData.rental_price || ''}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1">Depositum (NOK)</label>
                        <input type="number" class="form-control form-control-sm" name="deposit_amount" step="0.01" value="${existingData.deposit_amount || 0}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1">Markedspris (NOK)</label>
                        <input type="number" class="form-control form-control-sm" name="market_price" step="0.01" value="${existingData.market_price || ''}">
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
                                <input type="number" class="form-control form-control-sm" name="weekend_price" step="0.01" value="${existingData.weekend_price || 0}">
                            </div>
                        </div>
                    </div>
                    <div id="${containerId}_perDayPrice" style="display: ${(existingData.saturday_price > 0 || existingData.sunday_price > 0) ? 'block' : 'none'};">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Lørdagspris (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="saturday_price" step="0.01" value="${existingData.saturday_price || 0}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Søndagspris (NOK)</label>
                                <input type="number" class="form-control form-control-sm" name="sunday_price" step="0.01" value="${existingData.sunday_price || 0}">
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
                <textarea class="form-control form-control-sm" name="description" rows="4">${existingData.description || ''}</textarea>
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
        html += `<h6 class="text-muted mb-2"><i class="bi bi-list-check me-1"></i>${schema.label} - spesifikke detaljer</h6>`;
        html += '<div class="card bg-light mb-3"><div class="card-body py-2"><div class="row">';
        
        Object.entries(schema.fields).forEach(([fieldName, field]) => {
            const value = existingData.type_data?.[fieldName] || '';
            html += generateIntegratedFieldHTML(fieldName, field, value);
        });
        
        html += '</div></div></div>';
    }
    
    container.innerHTML = html;
}

function generateIntegratedFieldHTML(fieldName, field, value = '') {
    const required = field.required ? '<span class="text-danger">*</span>' : '';
    const requiredAttr = field.required ? 'required' : '';
    
    let inputHTML = '';
    
    switch (field.type) {
        case 'text':
            inputHTML = `<input type="text" class="form-control" name="type_data[${fieldName}]" value="${value || ''}" ${requiredAttr}>`;
            break;
        case 'number':
            const step = field.step || '1';
            inputHTML = `<input type="number" class="form-control" name="type_data[${fieldName}]" step="${step}" value="${value || ''}" ${requiredAttr}>`;
            break;
        case 'checkbox':
            const checked = value === true || value === 1 || value === '1' ? 'checked' : '';
            return `
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="type_data[${fieldName}]" id="${fieldName}" value="1" ${checked}>
                        <label class="form-check-label" for="${fieldName}">
                            ${field.label} ${required}
                        </label>
                    </div>
                </div>
            `;
        case 'select':
            inputHTML = `
                <select class="form-select" name="type_data[${fieldName}]" ${requiredAttr}>
                    <option value="">Velg...</option>
                    ${field.options.map(opt => `<option value="${opt}" ${value === opt ? 'selected' : ''}>${opt}</option>`).join('')}
                </select>
            `;
            break;
        case 'textarea':
            inputHTML = `<textarea class="form-control" name="type_data[${fieldName}]" rows="2" ${requiredAttr}>${value || ''}</textarea>`;
            break;
        default:
            inputHTML = `<input type="text" class="form-control" name="type_data[${fieldName}]" value="${value || ''}" ${requiredAttr}>`;
    }
    
    return `
        <div class="col-md-6 mb-3">
            <label class="form-label">${field.label} ${required}</label>
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
        is_available: formData.get('is_available') === 'on',
        images: collectImagesFromForm(form, 'formFieldsContainer')
    };
    
    if (isEdit) {
        data.id = id;
    }
    
    const typeData = {};
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('type_data[')) {
            const fieldName = key.match(/type_data\[(.+)\]/)[1];
            const inputElement = form.querySelector(`[name="${key}"]`);
            if (inputElement && inputElement.type === 'checkbox') {
                typeData[fieldName] = value === '1';
            } else {
                typeData[fieldName] = value || null;
            }
        }
    }
    
    if (Object.keys(typeData).length > 0) {
        data.type_data = typeData;
    }
    
    const toastId = toast.loading(isEdit ? 'Oppdaterer objekt...' : 'Oppretter objekt...');
    
    try {
        const endpoint = isEdit ? '/api/update_rental_object.php' : '/api/create_rental_object.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
    }
}

let imageRowCounter = 0;

function buildImageRow(containerId, idx, url, isPrimary, caption) {
    const rowId = 'imgrow_' + (imageRowCounter++);
    return `
        <div class="input-group input-group-sm mb-2 image-row" data-row-id="${rowId}">
            <span class="input-group-text" title="Hovedbilde">
                <input type="radio" name="${containerId}_primaryImage" value="${rowId}" ${isPrimary ? 'checked' : ''}>
            </span>
            <input type="text" class="form-control image-url-input" value="${url}" placeholder="https://bilde-url..." oninput="previewImageRow(this)">
            <input type="text" class="form-control image-caption-input" value="${caption}" placeholder="Bildetekst" style="max-width: 150px;">
            <button type="button" class="btn btn-outline-danger" onclick="removeImageRow(this)" title="Fjern bilde">
                <i class="bi bi-x"></i>
            </button>
            <div class="w-100">
                ${url ? `<img src="${url}" class="img-thumbnail mt-1" style="max-height: 60px;" onerror="this.style.display='none'">` : ''}
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
    if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
        previewDiv.innerHTML = `<img src="${url}" class="img-thumbnail mt-1" style="max-height: 60px;" onerror="this.style.display='none'">`;
    } else {
        previewDiv.innerHTML = '';
    }
}

function collectImagesFromForm(formElement, containerId) {
    const rows = formElement.querySelectorAll('.image-row');
    const images = [];
    rows.forEach(row => {
        const urlInput = row.querySelector('.image-url-input');
        const captionInput = row.querySelector('.image-caption-input');
        const radio = row.querySelector('input[type="radio"]');
        const url = urlInput ? urlInput.value.trim() : '';
        if (url) {
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
        is_available: form.querySelector('[name="is_available"]')?.checked ?? true,
        images: collectImagesFromForm(form, 'createFormFieldsContainer')
    };
    
    const typeData = {};
    const schema = rentalSchemas[category];
    if (schema) {
        Object.entries(schema.fields).forEach(([fieldName, field]) => {
            const inputElement = form.querySelector(`[name="type_data[${fieldName}]"]`);
            if (inputElement) {
                if (field.type === 'checkbox') {
                    typeData[fieldName] = inputElement.checked;
                } else {
                    typeData[fieldName] = inputElement.value || null;
                }
            }
        });
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
    summaryHTML += `<tr><td class="text-muted" style="width:45%">Objektnavn</td><td><strong>${data.object_name}</strong></td></tr>`;
    summaryHTML += `<tr><td class="text-muted">Kategori</td><td><span class="badge bg-primary">${categoryLabel}</span></td></tr>`;
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
        summaryHTML += `<tr><td class="text-muted">Beskrivelse</td><td>${data.description}</td></tr>`;
    }
    summaryHTML += '</table></div>';
    
    summaryHTML += '<div class="col-md-4 mb-3">';
    if (data.type_data && schema && Object.keys(data.type_data).length > 0) {
        summaryHTML += `<h6 class="text-muted mb-2">${categoryLabel} - detaljer</h6>`;
        summaryHTML += '<table class="table table-sm table-borderless">';
        Object.entries(data.type_data).forEach(([key, value]) => {
            const field = schema.fields[key];
            if (field && value !== null && value !== '') {
                let displayValue = value;
                if (field.type === 'checkbox') {
                    displayValue = value ? '<i class="bi bi-check-circle-fill text-success"></i> Ja' : '<i class="bi bi-x-circle-fill text-danger"></i> Nei';
                }
                summaryHTML += `<tr><td class="text-muted" style="width:50%">${field.label}</td><td>${displayValue}</td></tr>`;
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

function backToCreateForm() {
    document.getElementById('createSummarySection').style.display = 'none';
    document.getElementById('createFormSection').style.display = 'block';
}

async function confirmSaveNewRentalObject() {
    if (!pendingCreateData) {
        toast.quickError('Feil', 'Ingen data å lagre');
        return;
    }
    
    const data = {...pendingCreateData};
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
            headers: { 'Content-Type': 'application/json' },
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
            loadRentalObjects();
        } else {
            toast.error(toastId, 'Feil', result.message || 'Kunne ikke opprette objekt');
        }
    } catch (error) {
        console.error('Error creating rental object:', error);
        toast.error(toastId, 'Feil', 'Kunne ikke opprette objekt');
    }
}

async function toggleAvailability(objectId, isAvailable) {
    const obj = allRentalObjects.find(o => o.id === objectId);
    if (!obj) return;
    
    try {
        const response = await fetch('/api/update_rental_object.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: objectId, is_available: isAvailable ? 1 : 0 })
        });
        
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch(e) { throw new Error('Ugyldig svar'); }
        
        if (result.success) {
            obj.is_available = isAvailable ? 1 : 0;
            const filtered = rentalFilter 
                ? allRentalObjects.filter(o => o.category === rentalFilter)
                : allRentalObjects;
            renderRentalObjects(filtered);
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
    const obj = allRentalObjects.find(o => o.id === objectId);
    if (!obj) {
        toast.quickError('Feil', 'Objektet ble ikke funnet');
        return;
    }
    
    document.getElementById('rentalModalTitle').textContent = 'Rediger utleieobjekt';
    document.getElementById('rental_id').value = obj.id;
    
    buildIntegratedForm(obj.category, obj);
    
    const modal = new bootstrap.Modal(document.getElementById('rentalModal'));
    modal.show();
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
    const detailPrimary = detailImages.find(i => i.is_primary) || detailImages[0] || null;
    
    let detailsHTML = `
        <div class="row">
            <div class="col-md-6">
                ${detailPrimary ? `<img src="${detailPrimary.url}" class="img-fluid rounded mb-2" alt="${obj.object_name}" id="detailMainImage">` : ''}
                ${detailImages.length > 1 ? `<div class="d-flex gap-2 flex-wrap mb-3">${detailImages.map((img, i) => `<img src="${img.url}" class="img-thumbnail" style="max-height: 60px; cursor: pointer;${img === detailPrimary ? ' border: 2px solid #0d6efd;' : ''}" onclick="document.getElementById('detailMainImage').src='${img.url}'" title="${img.caption || ''}">`).join('')}</div>` : ''}
                <p><strong>Kategori:</strong> ${schema.label}</p>
                <p><strong>Leiepris (ukedag):</strong> ${parseFloat(obj.rental_price).toFixed(0)} NOK/dag</p>
                ${obj.saturday_price > 0 ? `<p><strong>Lørdagspris:</strong> ${parseFloat(obj.saturday_price).toFixed(0)} NOK/dag</p>` : ''}
                ${obj.sunday_price > 0 ? `<p><strong>Søndagspris:</strong> ${parseFloat(obj.sunday_price).toFixed(0)} NOK/dag</p>` : ''}
                ${(obj.weekend_price > 0 && !obj.saturday_price && !obj.sunday_price) ? `<p><strong>Helgepris:</strong> ${parseFloat(obj.weekend_price).toFixed(0)} NOK/dag</p>` : ''}
                ${obj.deposit_amount > 0 ? `<p><strong>Depositum:</strong> ${parseFloat(obj.deposit_amount).toFixed(0)} NOK</p>` : ''}
                <p><strong>Status:</strong> ${obj.is_available ? '<span class="badge bg-success">Tilgjengelig</span>' : '<span class="badge bg-secondary">Ikke tilgjengelig</span>'}</p>
                ${obj.description ? `<p><strong>Beskrivelse:</strong><br>${obj.description}</p>` : ''}
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
                detailsHTML += `<p><strong>${field.label}:</strong> ${displayValue}</p>`;
            }
        });
    }
    
    detailsHTML += `
            </div>
        </div>
    `;
    
    document.getElementById('detailsModalBody').innerHTML = detailsHTML;
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
                <span>${c.display_customer_name} &mdash; ${fmtDate(c.start_date)} – ${fmtDate(c.end_date)}</span>
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
    if (!startInput.value || !endInput.value || end <= start) {
        if (daysInput) daysInput.value = '';
        return;
    }

    const days = Math.round((end - start) / 86400000);
    if (daysInput) daysInput.value = days;
    if (amtInput && (!amtInput.value || parseFloat(amtInput.value) === 0)) {
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
    if (new Date(endVal) <= new Date(startVal)) {
        toast.quickError('Feil', 'Sluttdato må være etter startdato');
        return;
    }

    const payload = {
        rental_object_id: objectId,
        start_date:       startVal,
        end_date:         endVal,
        total_amount:     parseFloat(amtVal)  || 0,
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
            container.innerHTML = `<div class="alert alert-warning">${data.message || 'Feil ved henting av kontrakter'}</div>`;
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
        const statusBadge = contractStatusBadge(c.status);
        const payBadge    = contractPaymentBadge(c.payment_status);

        const markPaidBtn = c.payment_status !== 'paid'
            ? `<button class="btn btn-outline-success btn-sm" onclick="markContractPaid('${c.id}')" title="Merk som betalt"><i class="bi bi-check-circle"></i></button>`
            : '';

        let statusActionBtn = '';
        if (c.status === 'draft') {
            statusActionBtn = `<button class="btn btn-outline-primary btn-sm" onclick="updateContractStatus('${c.id}', 'active', '${c.rental_object_id}')" title="Aktiver"><i class="bi bi-play-circle"></i></button>`;
        } else if (c.status === 'active') {
            statusActionBtn = `<button class="btn btn-outline-secondary btn-sm" onclick="updateContractStatus('${c.id}', 'completed', '${c.rental_object_id}')" title="Avslutt"><i class="bi bi-stop-circle"></i></button>`;
        }

        const cancelBtn = !['completed','cancelled'].includes(c.status)
            ? `<button class="btn btn-outline-warning btn-sm" onclick="updateContractStatus('${c.id}', 'cancelled', '${c.rental_object_id}')" title="Kanseller"><i class="bi bi-x-circle"></i></button>`
            : '';

        const deleteBtn = c.status === 'draft'
            ? `<button class="btn btn-outline-danger btn-sm" onclick="deleteContract('${c.id}', '${c.rental_object_id}')" title="Slett"><i class="bi bi-trash"></i></button>`
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

// ─── FORESPURT UTLEIE TAB ────────────────────────────────────────────────────

async function loadRentalRequests() {
    const container = document.getElementById('rentalRequestsContainer');
    if (!container) return;

    try {
        const resp = await fetch('/api/get_rental_schedule.php?approved=0');
        const data = await resp.json();

        if (!data.success || !data.requests || data.requests.length === 0) {
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

            return `<tr>
                <td class="small">
                    <div class="fw-semibold">${escHtml(r.object_name)}</div>
                    ${r.object_category ? `<span class="badge bg-light text-dark border">${escHtml(r.object_category)}</span>` : ''}
                </td>
                <td class="small">
                    <div>${escHtml(r.display_customer_name)}</div>
                    ${contactInfo}
                </td>
                <td class="small text-nowrap">
                    ${fmtDate(r.start_date)} ${r.start_time ? r.start_time.substring(0,5) : ''}
                </td>
                <td class="small text-nowrap">
                    ${fmtDate(r.end_date)} ${r.end_time ? r.end_time.substring(0,5) : ''}
                </td>
                <td class="small">
                    ${logisticsLabel}
                    ${deliveryAddr}
                </td>
                <td class="small text-nowrap">
                    ${parseFloat(r.rental_fee || 0).toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:0})} NOK
                </td>
                <td class="small text-nowrap text-muted">${createdAt}</td>
                <td class="small">${notesCell || '<span class="text-muted">–</span>'}</td>
                <td class="text-nowrap">
                    <button class="btn btn-success btn-sm me-1" onclick="approveScheduleRequest('${escHtml(r.id)}')">
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
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger">Feil ved lasting av forespørsler</div>`;
    }
}

async function approveScheduleRequest(id) {
    try {
        const resp = await fetch('/api/update_rental_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, approved: 1, status: 'approved' })
        });
        const data = await resp.json();
        if (data.success) {
            toast.success('Forespørsel godkjent');
            loadRentalRequests();
            loadApprovedSchedule();
        } else {
            toast.error(data.message || 'Feil ved godkjenning');
        }
    } catch (e) {
        toast.error('Nettverksfeil. Prøv igjen.');
    }
}

async function declineScheduleRequest(id) {
    if (!confirm('Er du sikker på at du vil avslå denne forespørselen?')) return;
    try {
        const resp = await fetch('/api/update_rental_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, approved: 0, status: 'declined' })
        });
        const data = await resp.json();
        if (data.success) {
            toast.success('Forespørsel avslått');
            loadRentalRequests();
        } else {
            toast.error(data.message || 'Feil ved avvisning');
        }
    } catch (e) {
        toast.error('Nettverksfeil. Prøv igjen.');
    }
}

// ─── KOMMENDE UTLEIE TAB ─────────────────────────────────────────────────────

async function loadApprovedSchedule() {
    const container = document.getElementById('upcomingContractsContainer');
    if (!container) return;

    try {
        const resp = await fetch('/api/get_rental_schedule.php?approved=1');
        const data = await resp.json();

        if (!data.success || !data.requests || data.requests.length === 0) {
            container.innerHTML = `<div class="text-center text-muted py-5">
                <i class="bi bi-calendar-check fs-1 d-block mb-2"></i>
                Ingen godkjente kommende utleier
            </div>`;
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const rows = data.requests.map(r => {
            const endDate = new Date(r.end_date);
            const diffMs  = endDate - today;
            const daysLeft = Math.max(0, Math.round(diffMs / 86400000));
            const daysLeftBadge = daysLeft === 0
                ? '<span class="badge bg-danger">I dag</span>'
                : daysLeft <= 3
                ? `<span class="badge bg-warning text-dark">${daysLeft} dager</span>`
                : `<span class="badge bg-light text-dark border">${daysLeft} dager</span>`;

            const contactInfo = [
                r.display_email ? `<a href="mailto:${escHtml(r.display_email)}" class="d-block small">${escHtml(r.display_email)}</a>` : '',
                r.display_phone ? `<span class="d-block small text-muted">${escHtml(r.display_phone)}</span>` : ''
            ].join('');

            const pickupVal  = r.pickup_time ? r.pickup_time.substring(0,5) : '';
            const endTimeVal = r.end_time    ? r.end_time.substring(0,5)    : '';

            return `<tr>
                <td class="small fw-semibold">${escHtml(r.object_name)}</td>
                <td class="small">
                    <div>${escHtml(r.display_customer_name)}</div>
                    ${contactInfo}
                </td>
                <td class="small text-nowrap">${fmtDate(r.start_date)}</td>
                <td class="small">
                    <input type="time" class="form-control form-control-sm"
                        style="width:110px"
                        value="${escHtml(pickupVal)}"
                        title="Hentetidspunkt"
                        onchange="saveScheduleTime('${escHtml(r.id)}', 'pickup_time', this.value)">
                </td>
                <td class="small text-nowrap">${fmtDate(r.end_date)}</td>
                <td class="small">
                    <input type="time" class="form-control form-control-sm"
                        style="width:110px"
                        value="${escHtml(endTimeVal)}"
                        title="Sluttidspunkt"
                        onchange="saveScheduleTime('${escHtml(r.id)}', 'end_time', this.value)">
                </td>
                <td>${daysLeftBadge}</td>
                <td class="small text-nowrap">
                    ${parseFloat(r.rental_fee || 0).toLocaleString('nb-NO', {minimumFractionDigits:0, maximumFractionDigits:0})} NOK
                </td>
            </tr>`;
        }).join('');

        container.innerHTML = `<div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Objekt</th>
                        <th>Kunde</th>
                        <th>Fra dato</th>
                        <th>Hentetid</th>
                        <th>Til dato</th>
                        <th>Sluttid</th>
                        <th>Dager igjen</th>
                        <th>Leiebeløp</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger">Feil ved lasting av kommende utleier</div>`;
    }
}

async function saveScheduleTime(id, field, value) {
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
            toast.success('Tidspunkt lagret');
        } else {
            toast.error(data.message || 'Feil ved lagring');
        }
    } catch (e) {
        toast.error('Nettverksfeil. Prøv igjen.');
    }
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────

function contractStatusBadge(status) {
    const map = {
        draft:     '<span class="badge bg-secondary">Utkast</span>',
        active:    '<span class="badge bg-success">Aktiv</span>',
        completed: '<span class="badge bg-info text-dark">Avsluttet</span>',
        cancelled: '<span class="badge bg-danger">Kansellert</span>'
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
    const [y, m, d] = dateStr.split('T')[0].split('-');
    return `${d}.${m}.${y}`;
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
