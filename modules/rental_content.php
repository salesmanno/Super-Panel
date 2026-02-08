<?php
require_once __DIR__ . '/../includes/rental_schemas.php';
$schemas = getRentalSchemas();
?>

<div class="module-content">
    <div id="rental-objects" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Utleieobjekter</h5>
                    <button class="btn btn-primary btn-sm" onclick="showCreateRentalModal()">
                        <i class="bi bi-plus-circle me-1"></i>Nytt objekt
                    </button>
                </div>
                
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

<!-- Modal for opprettelse/redigering av utleieobjekt -->
<div class="modal fade" id="rentalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rentalModalTitle">Opprett nytt utleieobjekt</h5>
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

function showCreateRentalModal() {
    document.getElementById('rentalModalTitle').textContent = 'Opprett nytt utleieobjekt';
    document.getElementById('rentalForm').reset();
    document.getElementById('rental_id').value = '';
    buildIntegratedForm();
    const modal = new bootstrap.Modal(document.getElementById('rentalModal'));
    modal.show();
}

function buildIntegratedForm(category = '', existingData = {}) {
    const container = document.getElementById('formFieldsContainer');
    
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Objektnavn <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="object_name" value="${existingData.object_name || ''}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                <select class="form-select" name="category" id="categorySelect" required onchange="buildIntegratedForm(this.value, getCurrentFormData())">
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
                <input type="text" class="form-control" name="image_url" value="${existingData.image_url || ''}" placeholder="https://...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Beskrivelse</label>
                <textarea class="form-control" name="description" rows="2">${existingData.description || ''}</textarea>
            </div>
        </div>
        
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_available" id="isAvailable" ${(existingData.is_available === undefined || existingData.is_available === null || existingData.is_available === true || existingData.is_available === 1 || existingData.is_available === '1') ? 'checked' : ''}>
            <label class="form-check-label" for="isAvailable">
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

function getCurrentFormData() {
    const form = document.getElementById('rentalForm');
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
