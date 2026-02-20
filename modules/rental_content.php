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
                <h5 class="card-title">Kontrakt & betingelser</h5>
                <p class="text-muted">Administrer leieavtaler og betingelser</p>
                <div class="mb-3">
                    <label class="form-label">Last opp kontrakt mal</label>
                    <input type="file" class="form-control" accept=".pdf,.doc,.docx">
                    <small class="text-muted">Støttede formater: PDF, DOC, DOCX</small>
                </div>
                <div class="mt-4">
                    <h6>Eksisterende dokumenter</h6>
                    <div class="list-group">
                        <div class="list-group-item text-center text-muted py-4">
                            Ingen dokumenter lastet opp
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="rental-upcoming" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Kommende utleie</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Objekt</th>
                                <th>Kunde</th>
                                <th>Fra dato</th>
                                <th>Til dato</th>
                                <th>Varighet</th>
                                <th>Beløp</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                    Ingen kommende utleie
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
});

async function loadRentalObjects() {
    try {
        const response = await fetch('/api/get_rental_objects.php');
        const data = await response.json();
        
        if (data.success && data.objects) {
            allRentalObjects = data.objects;
            renderRentalObjects(allRentalObjects);
        } else {
            document.getElementById('rentalObjectsContainer').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Ingen utleieobjekter funnet
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
    const statusBadge = obj.is_available 
        ? '<span class="badge bg-success">Tilgjengelig</span>'
        : '<span class="badge bg-secondary">Ikke tilgjengelig</span>';
    
    let badges = obj.badges || [];
    const badgesHtml = badges.length > 0 
        ? badges.map(b => `<span class="badge bg-info me-1">${b}</span>`).join('')
        : '';
    
    const imageHtml = obj.image_url 
        ? `<img src="${obj.image_url}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="${obj.object_name}">`
        : `<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
             <i class="bi bi-image fs-1 text-muted"></i>
           </div>`;
    
    const weekendPrice = obj.weekend_price > 0 ? `<br><small class="text-muted">Helg: ${parseFloat(obj.weekend_price).toFixed(0)} NOK/dag</small>` : '';
    
    return `
        <div class="col">
            <div class="card h-100">
                ${imageHtml}
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">${obj.object_name}</h6>
                        ${statusBadge}
                    </div>
                    <p class="badge bg-primary mb-2">${categoryLabel}</p>
                    <div class="mb-2">
                        ${badgesHtml}
                    </div>
                    ${obj.description ? `<p class="card-text text-muted small">${obj.description.substring(0, 100)}${obj.description.length > 100 ? '...' : ''}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <strong class="text-primary">${parseFloat(obj.rental_price).toFixed(0)} NOK/dag</strong>
                            ${weekendPrice}
                        </div>
                        <div class="btn-group btn-group-sm">
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
        
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Leiepris per dag (NOK) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="rental_price" step="0.01" value="${existingData.rental_price || ''}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Helgepris per dag (NOK)</label>
                <input type="number" class="form-control" name="weekend_price" step="0.01" value="${existingData.weekend_price || 0}">
                <small class="text-muted">0 = samme som ukedag</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Depositum (NOK)</label>
                <input type="number" class="form-control" name="deposit_amount" step="0.01" value="${existingData.deposit_amount || 0}">
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Bilde URL</label>
                <input type="text" class="form-control" name="image_url" value="${existingData.image_url || ''}" placeholder="https://..." oninput="updateImagePreview(this, '${containerId}')">
                <div id="${containerId}_imagePreview" class="mt-2">
                    ${existingData.image_url ? `<img src="${existingData.image_url}" class="img-thumbnail" style="max-height: 150px; max-width: 100%;" onerror="this.style.display='none'">` : ''}
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Beskrivelse</label>
                <textarea class="form-control" name="description" rows="2">${existingData.description || ''}</textarea>
            </div>
        </div>
        
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_available" id="${checkboxId}" ${(existingData.is_available === undefined || existingData.is_available === null || existingData.is_available === true || existingData.is_available === 1 || existingData.is_available === '1') ? 'checked' : ''}>
            <label class="form-check-label" for="${checkboxId}">
                Tilgjengelig for utleie
            </label>
        </div>
    `;
    
    if (category && rentalSchemas[category]) {
        const schema = rentalSchemas[category];
        html += '<hr><div class="row">';
        
        Object.entries(schema.fields).forEach(([fieldName, field]) => {
            const value = existingData.type_data?.[fieldName] || '';
            html += generateIntegratedFieldHTML(fieldName, field, value);
        });
        
        html += '</div>';
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

function getFormData(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    const data = {
        object_name: formData.get('object_name') || '',
        rental_price: formData.get('rental_price') || '',
        weekend_price: formData.get('weekend_price') || 0,
        deposit_amount: formData.get('deposit_amount') || 0,
        image_url: formData.get('image_url') || '',
        description: formData.get('description') || '',
        is_available: formData.get('is_available') === 'on',
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
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        image_url: formData.get('image_url') || null,
        description: formData.get('description') || null,
        is_available: formData.get('is_available') === 'on'
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
        
        const result = await response.json();
        
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

function updateImagePreview(input, containerId) {
    const previewDiv = document.getElementById(containerId + '_imagePreview');
    const url = input.value.trim();
    if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
        previewDiv.innerHTML = `<img src="${url}" class="img-thumbnail" style="max-height: 150px; max-width: 100%;" onerror="this.parentElement.innerHTML='<small class=\\'text-danger\\'>Kunne ikke laste bildet</small>'">`;
    } else {
        previewDiv.innerHTML = '';
    }
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
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        image_url: formData.get('image_url') || null,
        description: formData.get('description') || null,
        is_available: form.querySelector('[name="is_available"]')?.checked ?? true
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
    if (data.image_url) {
        summaryHTML += `<img src="${data.image_url}" class="img-fluid rounded shadow-sm mb-2" style="max-height: 250px; width: 100%; object-fit: cover;" onerror="this.outerHTML='<div class=\\'bg-light rounded d-flex align-items-center justify-content-center\\' style=\\'height:200px\\'><i class=\\'bi bi-image fs-1 text-muted\\'></i></div>'">`;
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
    if (data.weekend_price > 0) {
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
        
        const result = await response.json();
        
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
        
        const result = await response.json();
        
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
    
    let detailsHTML = `
        <div class="row">
            <div class="col-md-6">
                ${obj.image_url ? `<img src="${obj.image_url}" class="img-fluid rounded mb-3" alt="${obj.object_name}">` : ''}
                <p><strong>Kategori:</strong> ${schema.label}</p>
                <p><strong>Leiepris (ukedag):</strong> ${parseFloat(obj.rental_price).toFixed(0)} NOK/dag</p>
                ${obj.weekend_price > 0 ? `<p><strong>Leiepris (helg):</strong> ${parseFloat(obj.weekend_price).toFixed(0)} NOK/dag</p>` : ''}
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
</script>
