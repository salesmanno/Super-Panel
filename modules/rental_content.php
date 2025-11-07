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
                <div class="table-responsive">
                    <table class="table table-hover" id="rentalObjectsTable">
                        <thead>
                            <tr>
                                <th>Objekt navn</th>
                                <th>Type</th>
                                <th>Kategori</th>
                                <th>Serienummer</th>
                                <th>Tilstand</th>
                                <th>Status</th>
                                <th>Pris/dag</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="rentalObjectsBody">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Laster inn data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
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

<!-- Modal for opprettelse av utleieobjekt -->
<div class="modal fade" id="createRentalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-square me-2"></i>Opprett nytt utleieobjekt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="createRentalForm">
                    <div class="row">
                        <!-- Left Column: Image Upload -->
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <label class="form-label fw-bold">Bilde av objekt</label>
                                    <div id="imagePreview" class="border rounded p-3 mb-2" style="min-height: 200px; background: white; display: flex; align-items: center; justify-content: center;">
                                        <div class="text-muted">
                                            <i class="bi bi-image fs-1 d-block mb-2"></i>
                                            <small>Last opp bilde</small>
                                        </div>
                                    </div>
                                    <input type="file" class="form-control form-control-sm" id="objectImageInput" accept="image/*">
                                    <input type="hidden" name="image_url" id="imageUrlField">
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Form Fields -->
                        <div class="col-md-8">
                            <!-- Basic Info Section -->
                            <div class="card mb-3">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Grunnleggende info</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold mb-1">Objektnavn *</label>
                                            <input type="text" class="form-control form-control-sm" name="object_name" required>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label small fw-bold mb-1">Kategori</label>
                                            <select class="form-select form-select-sm" name="category">
                                                <option value="">Velg</option>
                                                <option value="hus">Hus/Hytte</option>
                                                <option value="verktoy">Verktøy</option>
                                                <option value="utstyr">Utstyr</option>
                                                <option value="kjoreto">Kjøretøy</option>
                                                <option value="annet">Annet</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label small fw-bold mb-1">Serienr</label>
                                            <input type="text" class="form-control form-control-sm" name="serial_number" placeholder="Valgfritt">
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold mb-1">Beskrivelse</label>
                                        <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Kort beskrivelse av objektet"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Section -->
                            <div class="card mb-3">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="bi bi-cash-coin me-1"></i>Priser & Depositum</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label small fw-bold mb-1">Markedspris (NOK)</label>
                                            <input type="number" class="form-control form-control-sm" name="market_price" step="0.01" placeholder="Objektets verdi">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label small fw-bold mb-1">Utleiepris/dag (NOK) *</label>
                                            <input type="number" class="form-control form-control-sm" name="daily_price" step="0.01" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label small fw-bold mb-1">Depositum (NOK)</label>
                                            <input type="number" class="form-control form-control-sm" name="deposit_amount" step="0.01" placeholder="0 = Ikke påkrevd">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rental Periods & Details -->
                            <div class="card mb-3">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="bi bi-calendar-range me-1"></i>Utleieperioder & Detaljer</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold mb-1">Tilgjengelige perioder</label>
                                            <input type="text" class="form-control form-control-sm" name="rental_periods" placeholder="F.eks: Hele året, Sommer (jun-aug)">
                                            <small class="text-muted">Når kan objektet leies?</small>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small fw-bold mb-1">Plassering</label>
                                            <input type="text" class="form-control form-control-sm" name="location" placeholder="F.eks: Lager A, Hylle 3">
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold mb-1">Tilstandsnotat</label>
                                        <textarea class="form-control form-control-sm" name="condition_notes" rows="2" placeholder="God tilstand, sjekket og vedlikeholdt"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Required Fields Configuration -->
                            <div class="card">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="bi bi-check2-square me-1"></i>Påkrevde felter ved utleie</h6>
                                </div>
                                <div class="card-body p-3">
                                    <small class="text-muted d-block mb-2">Velg hvilke kundefelter som må fylles ut ved utleie:</small>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" name="field_phone" id="field_phone" checked>
                                                <label class="form-check-label small" for="field_phone">
                                                    <i class="bi bi-telephone"></i> Telefon
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" name="field_address" id="field_address" checked>
                                                <label class="form-check-label small" for="field_address">
                                                    <i class="bi bi-house"></i> Adresse
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" name="field_id_number" id="field_id_number">
                                                <label class="form-check-label small" for="field_id_number">
                                                    <i class="bi bi-person-badge"></i> ID-nummer
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" name="field_emergency_contact" id="field_emergency_contact">
                                                <label class="form-check-label small" for="field_emergency_contact">
                                                    <i class="bi bi-exclamation-triangle"></i> Nødkontakt
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Availability Toggle -->
                            <div class="mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="isAvailable" checked>
                                    <label class="form-check-label fw-bold" for="isAvailable">
                                        <i class="bi bi-toggle-on text-success"></i> Tilgjengelig for utleie
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Avbryt
                </button>
                <button type="button" class="btn btn-primary" onclick="createRentalObject()">
                    <i class="bi bi-check-circle me-1"></i>Opprett utleieobjekt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load rental objects when tab is activated
document.addEventListener('DOMContentLoaded', function() {
    loadRentalObjects();
    
    // Image upload preview
    document.getElementById('objectImageInput')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
                document.getElementById('imageUrlField').value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});

async function loadRentalObjects() {
    try {
        const response = await fetch('/api/get_rental_objects.php');
        const data = await response.json();
        
        if (data.success && data.objects.length > 0) {
            renderRentalObjects(data.objects);
        } else {
            document.getElementById('rentalObjectsBody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Ingen utleieobjekter funnet
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading rental objects:', error);
        document.getElementById('rentalObjectsBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Feil ved lasting av data
                </td>
            </tr>
        `;
    }
}

function renderRentalObjects(objects) {
    const tbody = document.getElementById('rentalObjectsBody');
    tbody.innerHTML = objects.map(obj => {
        const statusBadge = obj.is_available 
            ? '<span class="badge bg-success">Tilgjengelig</span>'
            : '<span class="badge bg-secondary">Ikke tilgjengelig</span>';
        
        const typeBadge = obj.type === 'large' 
            ? '<span class="badge bg-info">Stort</span>'
            : obj.type === 'small'
            ? '<span class="badge bg-primary">Lite</span>'
            : '';
        
        return `
            <tr>
                <td>
                    ${obj.image_url ? `<img src="${obj.image_url}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">` : ''}
                    <strong>${obj.name}</strong>
                    ${obj.description ? `<br><small class="text-muted">${obj.description.substring(0, 50)}${obj.description.length > 50 ? '...' : ''}</small>` : ''}
                </td>
                <td>${typeBadge}</td>
                <td>${obj.category || '-'}</td>
                <td><code>${obj.serial_number || '-'}</code></td>
                <td>
                    <small class="text-muted">${obj.condition_notes || 'Ingen notater'}</small>
                </td>
                <td>${statusBadge}</td>
                <td><strong>${obj.daily_price.toFixed(2)} NOK</strong>${obj.market_price ? `<br><small class="text-muted">Verdi: ${obj.market_price.toFixed(2)} NOK</small>` : ''}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editRentalObject(${obj.id})" title="Rediger">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRentalObject(${obj.id})" title="Slett">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function showCreateRentalModal() {
    const modal = new bootstrap.Modal(document.getElementById('createRentalModal'));
    document.getElementById('createRentalForm').reset();
    document.getElementById('imagePreview').innerHTML = `
        <div class="text-muted">
            <i class="bi bi-image fs-1 d-block mb-2"></i>
            <small>Last opp bilde</small>
        </div>
    `;
    document.getElementById('imageUrlField').value = '';
    modal.show();
}

async function createRentalObject() {
    const form = document.getElementById('createRentalForm');
    const formData = new FormData(form);
    
    const requiredFields = [];
    if (formData.get('field_phone') === 'on') requiredFields.push({field_name: 'phone', is_required: true});
    if (formData.get('field_address') === 'on') requiredFields.push({field_name: 'address', is_required: true});
    if (formData.get('field_id_number') === 'on') requiredFields.push({field_name: 'id_number', is_required: true});
    if (formData.get('field_emergency_contact') === 'on') requiredFields.push({field_name: 'emergency_contact', is_required: true});
    
    const data = {
        object_name: formData.get('object_name'),
        category: formData.get('category'),
        description: formData.get('description'),
        daily_price: parseFloat(formData.get('daily_price')) || 0,
        market_price: parseFloat(formData.get('market_price')) || null,
        rental_periods: formData.get('rental_periods') || null,
        location: formData.get('location'),
        serial_number: formData.get('serial_number'),
        condition_notes: formData.get('condition_notes'),
        deposit_amount: parseFloat(formData.get('deposit_amount')) || 0,
        is_available: formData.get('is_available') === 'on',
        image_url: formData.get('image_url') || null,
        required_fields: requiredFields
    };
    
    const toast = toastManager.loading('Oppretter utleieobjekt');
    
    try {
        const response = await fetch('/api/create_rental.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success('Utleieobjekt opprettet!');
            bootstrap.Modal.getInstance(document.getElementById('createRentalModal')).hide();
            loadRentalObjects();
        } else {
            toast.error(`Kunne ikke opprette: ${result.message}`);
        }
    } catch (error) {
        console.error('Error creating rental object:', error);
        toast.error('Kunne ikke opprette utleieobjekt');
    }
}

async function deleteRentalObject(objectId) {
    if (!confirm('Er du sikker på at du vil slette dette utleieobjektet?')) {
        return;
    }
    
    const toast = toastManager.loading('Sletter utleieobjekt');
    
    try {
        const response = await fetch('/api/delete_rental_object.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ object_id: objectId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success('Utleieobjekt slettet!');
            loadRentalObjects();
        } else {
            toast.error(`Kunne ikke slette: ${result.message}`);
        }
    } catch (error) {
        console.error('Error deleting rental object:', error);
        toast.error('Kunne ikke slette utleieobjekt');
    }
}

function editRentalObject(objectId) {
    toastManager.info('Redigeringsfunksjon kommer snart');
}
</script>

