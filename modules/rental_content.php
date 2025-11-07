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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Opprett nytt utleieobjekt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createRentalForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Objektnavn *</label>
                            <input type="text" class="form-control" name="object_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="object_type">
                                <option value="">Velg type</option>
                                <option value="small">Lite objekt</option>
                                <option value="large">Stort objekt</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category">
                                <option value="">Velg kategori</option>
                                <option value="hus">Hus/Hytte</option>
                                <option value="verktoy">Verktøy</option>
                                <option value="utstyr">Utstyr</option>
                                <option value="kjoreto">Kjøretøy</option>
                                <option value="annet">Annet</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serienummer</label>
                            <input type="text" class="form-control" name="serial_number">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pris per dag (NOK) *</label>
                            <input type="number" class="form-control" name="daily_price" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plassering</label>
                            <input type="text" class="form-control" name="location" placeholder="F.eks. Lager A">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="requires_deposit" id="requiresDeposit">
                                <label class="form-check-label" for="requiresDeposit">
                                    Krever depositum
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3" id="depositAmountGroup" style="display: none;">
                            <label class="form-label">Depositum beløp (NOK)</label>
                            <input type="number" class="form-control" name="deposit_amount" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tilstandsnotat</label>
                        <textarea class="form-control" name="condition_notes" rows="2" placeholder="F.eks. God tilstand, sjekket og vedlikeholdt"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_available" checked>
                            <label class="form-check-label">
                                Tilgjengelig for utleie
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="createRentalObject()">
                    <i class="bi bi-check-circle me-1"></i>Opprett objekt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load rental objects when tab is activated
document.addEventListener('DOMContentLoaded', function() {
    loadRentalObjects();
    
    // Toggle deposit amount field
    document.querySelector('input[name="requires_deposit"]')?.addEventListener('change', function() {
        document.getElementById('depositAmountGroup').style.display = this.checked ? 'block' : 'none';
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
                <td><strong>${obj.daily_price.toFixed(2)} NOK</strong></td>
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
    modal.show();
}

async function createRentalObject() {
    const form = document.getElementById('createRentalForm');
    const formData = new FormData(form);
    
    const data = {
        object_name: formData.get('object_name'),
        object_type: formData.get('object_type'),
        category: formData.get('category'),
        description: formData.get('description'),
        daily_price: parseFloat(formData.get('daily_price')),
        location: formData.get('location'),
        serial_number: formData.get('serial_number'),
        condition_notes: formData.get('condition_notes'),
        requires_deposit: formData.get('requires_deposit') === 'on',
        deposit_amount: formData.get('requires_deposit') === 'on' ? parseFloat(formData.get('deposit_amount')) : 0,
        is_available: formData.get('is_available') === 'on'
    };
    
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
            bootstrap.Modal.getInstance(document.getElementById('createRentalModal')).hide();
            loadRentalObjects();
            showToast('Suksess', result.message, 'success');
        } else {
            showToast('Feil', result.message, 'danger');
        }
    } catch (error) {
        console.error('Error creating rental object:', error);
        showToast('Feil', 'Kunne ikke opprette utleieobjekt', 'danger');
    }
}

async function deleteRentalObject(objectId) {
    if (!confirm('Er du sikker på at du vil slette dette utleieobjektet?')) {
        return;
    }
    
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
            loadRentalObjects();
            showToast('Suksess', result.message, 'success');
        } else {
            showToast('Feil', result.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting rental object:', error);
        showToast('Feil', 'Kunne ikke slette utleieobjekt', 'danger');
    }
}

function editRentalObject(objectId) {
    // TODO: Implementer redigeringsfunksjon
    showToast('Info', 'Redigeringsfunksjon kommer snart', 'info');
}

function showToast(title, message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <strong>${title}</strong><br>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}
</script>

