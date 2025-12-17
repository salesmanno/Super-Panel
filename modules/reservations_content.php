<style>
.service-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    background: #fff;
}
.service-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #dee2e6;
}
.service-card.inactive {
    opacity: 0.6;
    background: #f8f9fa;
}
.service-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2563eb;
}
.service-meta {
    display: flex;
    gap: 1rem;
    margin-top: 0.75rem;
    color: #6c757d;
    font-size: 0.85rem;
}
.staff-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    background: #e7f3ff;
    color: #1976d2;
    margin: 0.25rem;
}
.availability-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}
.day-cell {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.15s;
    border: 1px solid transparent;
}
.day-cell:hover {
    transform: scale(1.05);
}
.day-cell.working {
    background: #dcfce7;
    color: #166534;
}
.day-cell.off {
    background: #fee2e2;
    color: #991b1b;
}
.day-cell.today {
    border: 2px solid #2563eb;
}
.day-cell .day-num {
    font-weight: 600;
    font-size: 0.9rem;
}
.staff-row {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.15s;
}
.staff-row:hover {
    background: #f8f9fa;
}
.staff-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 1rem;
}
.quick-action-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="module-content">
    <div id="reservations-overview" class="subtab-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Tjenester</h4>
                <p class="text-muted mb-0">Administrer tjenestene dine</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateServiceModal()">
                <i class="bi bi-plus-lg me-2"></i>Ny tjeneste
            </button>
        </div>
        
        <div id="services-list">
            <div class="empty-state">
                <i class="bi bi-gear d-block"></i>
                <p>Laster tjenester...</p>
            </div>
        </div>
    </div>
    
    <div id="reservations-active-services" class="subtab-content" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Ansatte og tjenester</h4>
                <p class="text-muted mb-0">Koble ansatte til tjenestene de kan utføre</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <strong>Velg tjeneste</strong>
                    </div>
                    <div class="list-group list-group-flush" id="service-select-list">
                        <div class="text-center text-muted py-3">Laster...</div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong id="selected-service-title">Velg en tjeneste</strong>
                        <button class="btn btn-sm btn-outline-primary" id="add-staff-btn" style="display:none;" onclick="openAddStaffModal()">
                            <i class="bi bi-person-plus me-1"></i>Legg til ansatt
                        </button>
                    </div>
                    <div class="card-body" id="service-staff-list">
                        <div class="empty-state">
                            <i class="bi bi-arrow-left d-block"></i>
                            <p>Velg en tjeneste fra listen</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="reservations-create-service" class="subtab-content" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Arbeidstider</h4>
                <p class="text-muted mb-0">Sett arbeidstider og fridager for ansatte</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-white">
                        <strong>Ansatte</strong>
                    </div>
                    <div class="list-group list-group-flush" id="staff-list-availability">
                        <div class="text-center text-muted py-3">Laster...</div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="mb-3">Hurtigvalg</h6>
                        <button class="btn btn-sm btn-outline-success w-100 mb-2" onclick="setWeekWorking()">
                            <i class="bi bi-check-circle me-1"></i>Sett uke som arbeidsdag
                        </button>
                        <button class="btn btn-sm btn-outline-danger w-100" onclick="setWeekOff()">
                            <i class="bi bi-x-circle me-1"></i>Sett uke som fri
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="changeAvailabilityMonth(-1)">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <strong id="availability-month-title">Desember 2025</strong>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="changeAvailabilityMonth(1)">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <span class="badge bg-light text-dark" id="selected-staff-name">Velg ansatt</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-2">
                            <div class="col"><small class="text-muted">Man</small></div>
                            <div class="col"><small class="text-muted">Tir</small></div>
                            <div class="col"><small class="text-muted">Ons</small></div>
                            <div class="col"><small class="text-muted">Tor</small></div>
                            <div class="col"><small class="text-muted">Fre</small></div>
                            <div class="col"><small class="text-muted">Lør</small></div>
                            <div class="col"><small class="text-muted">Søn</small></div>
                        </div>
                        <div class="availability-grid" id="availability-calendar">
                            <div class="empty-state" style="grid-column: span 7;">
                                <i class="bi bi-person d-block"></i>
                                <p>Velg en ansatt for å se kalender</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex gap-3">
                            <span><span class="badge" style="background:#dcfce7;color:#166534;">&#9679;</span> Arbeidsdag</span>
                            <span><span class="badge" style="background:#fee2e2;color:#991b1b;">&#9679;</span> Fridag</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalTitle">Ny tjeneste</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="service-form">
                    <input type="hidden" id="service-id">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Navn *</label>
                            <input type="text" class="form-control" id="service-name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pris (NOK)</label>
                            <input type="number" step="1" class="form-control" id="service-price" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" id="service-description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Varighet (min)</label>
                            <input type="number" class="form-control" id="service-duration" value="60">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Buffer før (min)</label>
                            <input type="number" class="form-control" id="service-buffer-before" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Buffer etter (min)</label>
                            <input type="number" class="form-control" id="service-buffer-after" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" id="service-category" placeholder="F.eks. Frisør, Massasje">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maks bookinger/dag</label>
                            <input type="number" class="form-control" id="service-max-daily" placeholder="Ubegrenset">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Betalingsmetoder</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm-cash" value="cash">
                                <label class="form-check-label" for="pm-cash">Kontant</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm-card" value="card">
                                <label class="form-check-label" for="pm-card">Kort</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm-vipps" value="vipps">
                                <label class="form-check-label" for="pm-vipps">Vipps</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm-invoice" value="invoice">
                                <label class="form-check-label" for="pm-invoice">Faktura</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="service-active" checked>
                        <label class="form-check-label" for="service-active">Aktiv tjeneste</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="saveService()">Lagre</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Legg til ansatt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Velg ansatt</label>
                    <select class="form-select" id="add-staff-select">
                        <option value="">Velg...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Timepris (valgfritt)</label>
                    <input type="number" step="1" class="form-control" id="add-staff-rate" placeholder="Standard">
                </div>
                <div class="mb-3">
                    <label class="form-label">Maks bookinger/dag</label>
                    <input type="number" class="form-control" id="add-staff-max" value="5">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="assignStaff()">Legg til</button>
            </div>
        </div>
    </div>
</div>

<script>
let allServices = [];
let allStaff = [];
let selectedServiceId = null;
let selectedStaffId = null;
let availabilityMonth = new Date();
let staffAvailabilityData = {};

async function loadServices() {
    try {
        const response = await fetch('/api/services/get_services.php');
        const result = await response.json();
        
        if (result.success) {
            allServices = result.services;
            renderServices();
        }
    } catch (error) {
        console.error('Error loading services:', error);
    }
}

function renderServices() {
    const container = document.getElementById('services-list');
    
    if (!allServices || allServices.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-gear d-block"></i>
                <p>Ingen tjenester opprettet ennå</p>
                <button class="btn btn-primary" onclick="openCreateServiceModal()">
                    <i class="bi bi-plus-lg me-2"></i>Opprett første tjeneste
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = allServices.map(service => {
        const paymentMethods = service.payment_methods || [];
        const paymentBadges = paymentMethods.map(pm => {
            const labels = {cash: 'Kontant', card: 'Kort', vipps: 'Vipps', invoice: 'Faktura'};
            return `<span class="badge bg-light text-dark me-1">${labels[pm] || pm}</span>`;
        }).join('');
        
        return `
            <div class="service-card ${service.is_active ? '' : 'inactive'}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="mb-0">${service.name}</h5>
                            ${!service.is_active ? '<span class="badge bg-secondary">Inaktiv</span>' : ''}
                            ${service.category ? `<span class="badge bg-info">${service.category}</span>` : ''}
                        </div>
                        ${service.description ? `<p class="text-muted mb-2">${service.description}</p>` : ''}
                        <div class="service-meta">
                            <span><i class="bi bi-clock me-1"></i>${service.duration_minutes || 60} min</span>
                            <span><i class="bi bi-people me-1"></i>${service.staff_count || 0} ansatte</span>
                            ${service.max_daily_bookings ? `<span><i class="bi bi-calendar me-1"></i>Maks ${service.max_daily_bookings}/dag</span>` : ''}
                        </div>
                        ${paymentBadges ? `<div class="mt-2">${paymentBadges}</div>` : ''}
                    </div>
                    <div class="text-end">
                        <div class="service-price">${parseInt(service.price || 0).toLocaleString('nb-NO')} kr</div>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editService('${service.id}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteService('${service.id}', '${service.name.replace(/'/g, "\\'")}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function openCreateServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'Ny tjeneste';
    document.getElementById('service-form').reset();
    document.getElementById('service-id').value = '';
    document.getElementById('service-active').checked = true;
    new bootstrap.Modal(document.getElementById('createServiceModal')).show();
}

function editService(id) {
    const service = allServices.find(s => s.id === id);
    if (!service) return;
    
    document.getElementById('serviceModalTitle').textContent = 'Rediger tjeneste';
    document.getElementById('service-id').value = service.id;
    document.getElementById('service-name').value = service.name || '';
    document.getElementById('service-description').value = service.description || '';
    document.getElementById('service-price').value = service.price || 0;
    document.getElementById('service-duration').value = service.duration_minutes || 60;
    document.getElementById('service-buffer-before').value = service.buffer_before_minutes || 0;
    document.getElementById('service-buffer-after').value = service.buffer_after_minutes || 0;
    document.getElementById('service-category').value = service.category || '';
    document.getElementById('service-max-daily').value = service.max_daily_bookings || '';
    document.getElementById('service-active').checked = service.is_active == 1;
    
    ['cash', 'card', 'vipps', 'invoice'].forEach(pm => {
        document.getElementById('pm-' + pm).checked = (service.payment_methods || []).includes(pm);
    });
    
    new bootstrap.Modal(document.getElementById('createServiceModal')).show();
}

async function saveService() {
    const id = document.getElementById('service-id').value;
    const paymentMethods = [];
    ['cash', 'card', 'vipps', 'invoice'].forEach(pm => {
        if (document.getElementById('pm-' + pm).checked) paymentMethods.push(pm);
    });
    
    const data = {
        name: document.getElementById('service-name').value,
        description: document.getElementById('service-description').value,
        price: parseFloat(document.getElementById('service-price').value) || 0,
        duration_minutes: parseInt(document.getElementById('service-duration').value) || 60,
        buffer_before_minutes: parseInt(document.getElementById('service-buffer-before').value) || 0,
        buffer_after_minutes: parseInt(document.getElementById('service-buffer-after').value) || 0,
        category: document.getElementById('service-category').value || null,
        max_daily_bookings: parseInt(document.getElementById('service-max-daily').value) || null,
        is_active: document.getElementById('service-active').checked,
        payment_methods: paymentMethods
    };
    
    if (id) data.id = id;
    
    try {
        const url = id ? '/api/services/update_service.php' : '/api/services/create_service.php';
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('createServiceModal')).hide();
            loadServices();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving service:', error);
        alert('Feil ved lagring');
    }
}

async function deleteService(id, name) {
    if (!confirm(`Slette tjenesten "${name}"?\n\nDette fjerner også alle ansatt-tilknytninger.`)) return;
    
    try {
        const response = await fetch('/api/services/delete_service.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        if (result.success) {
            loadServices();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting service:', error);
    }
}

async function loadStaff() {
    try {
        const response = await fetch('/api/get_staff.php');
        const result = await response.json();
        if (result.success) {
            allStaff = result.staff;
        }
    } catch (error) {
        console.error('Error loading staff:', error);
    }
}

function loadServiceSelectList() {
    const container = document.getElementById('service-select-list');
    
    if (!allServices || allServices.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">Ingen tjenester</div>';
        return;
    }
    
    container.innerHTML = allServices.filter(s => s.is_active == 1).map(service => `
        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                onclick="selectServiceForStaff('${service.id}', '${service.name.replace(/'/g, "\\'")}')">
            <span>${service.name}</span>
            <span class="badge bg-primary rounded-pill">${service.staff_count || 0}</span>
        </button>
    `).join('');
}

async function selectServiceForStaff(serviceId, serviceName) {
    selectedServiceId = serviceId;
    document.getElementById('selected-service-title').textContent = serviceName;
    document.getElementById('add-staff-btn').style.display = 'block';
    
    document.querySelectorAll('#service-select-list .list-group-item').forEach(el => {
        el.classList.remove('active');
    });
    event.target.closest('.list-group-item').classList.add('active');
    
    try {
        const response = await fetch(`/api/services/get_staff_services.php?service_id=${serviceId}`);
        const result = await response.json();
        
        const container = document.getElementById('service-staff-list');
        
        if (result.success && result.staff_services.length > 0) {
            container.innerHTML = result.staff_services.map(ss => `
                <div class="staff-row">
                    <div class="staff-avatar">${(ss.first_name?.[0] || '')+(ss.last_name?.[0] || '')}</div>
                    <div class="flex-grow-1">
                        <strong>${ss.first_name} ${ss.last_name}</strong>
                        <div class="small text-muted">
                            ${ss.hourly_rate ? ss.hourly_rate + ' kr/t' : 'Standard pris'}
                            ${ss.max_daily_bookings ? ' • Maks ' + ss.max_daily_bookings + '/dag' : ''}
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeStaffFromService('${ss.staff_id}', '${ss.first_name} ${ss.last_name}')">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-person-plus d-block"></i>
                    <p>Ingen ansatte tilknyttet denne tjenesten</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading staff services:', error);
    }
}

function openAddStaffModal() {
    const select = document.getElementById('add-staff-select');
    select.innerHTML = '<option value="">Velg...</option>' + 
        allStaff.map(s => `<option value="${s.id}">${s.first_name} ${s.last_name}</option>`).join('');
    
    document.getElementById('add-staff-rate').value = '';
    document.getElementById('add-staff-max').value = 5;
    
    new bootstrap.Modal(document.getElementById('addStaffModal')).show();
}

async function assignStaff() {
    const staffId = document.getElementById('add-staff-select').value;
    if (!staffId || !selectedServiceId) return;
    
    try {
        const response = await fetch('/api/services/assign_staff.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                service_id: selectedServiceId,
                staff_id: staffId,
                hourly_rate: parseFloat(document.getElementById('add-staff-rate').value) || null,
                max_daily_bookings: parseInt(document.getElementById('add-staff-max').value) || 5
            })
        });
        
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('addStaffModal')).hide();
            loadServices();
            selectServiceForStaff(selectedServiceId, document.getElementById('selected-service-title').textContent);
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Error assigning staff:', error);
    }
}

async function removeStaffFromService(staffId, staffName) {
    if (!confirm(`Fjerne ${staffName} fra denne tjenesten?`)) return;
    
    try {
        const response = await fetch('/api/services/remove_staff.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                service_id: selectedServiceId,
                staff_id: staffId
            })
        });
        
        const result = await response.json();
        if (result.success) {
            loadServices();
            selectServiceForStaff(selectedServiceId, document.getElementById('selected-service-title').textContent);
        }
    } catch (error) {
        console.error('Error removing staff:', error);
    }
}

function loadStaffListForAvailability() {
    const container = document.getElementById('staff-list-availability');
    
    if (!allStaff || allStaff.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">Ingen ansatte</div>';
        return;
    }
    
    container.innerHTML = allStaff.map(staff => `
        <button class="list-group-item list-group-item-action" onclick="selectStaffForAvailability('${staff.id}', '${staff.first_name} ${staff.last_name}')">
            <div class="d-flex align-items-center">
                <div class="staff-avatar me-2" style="width:32px;height:32px;font-size:0.75rem;">
                    ${(staff.first_name?.[0] || '')+(staff.last_name?.[0] || '')}
                </div>
                ${staff.first_name} ${staff.last_name}
            </div>
        </button>
    `).join('');
}

async function selectStaffForAvailability(staffId, staffName) {
    selectedStaffId = staffId;
    document.getElementById('selected-staff-name').textContent = staffName;
    
    document.querySelectorAll('#staff-list-availability .list-group-item').forEach(el => {
        el.classList.remove('active');
    });
    event.target.closest('.list-group-item').classList.add('active');
    
    await loadAvailabilityCalendar();
}

async function loadAvailabilityCalendar() {
    if (!selectedStaffId) return;
    
    const year = availabilityMonth.getFullYear();
    const month = availabilityMonth.getMonth();
    
    const monthNames = ['Januar', 'Februar', 'Mars', 'April', 'Mai', 'Juni', 
                        'Juli', 'August', 'September', 'Oktober', 'November', 'Desember'];
    document.getElementById('availability-month-title').textContent = `${monthNames[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - (firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1));
    
    try {
        const response = await fetch(`/api/services/get_availability.php?staff_id=${selectedStaffId}&start_date=${startDate.toISOString().split('T')[0]}&end_date=${lastDay.toISOString().split('T')[0]}`);
        const result = await response.json();
        
        staffAvailabilityData = {};
        if (result.success) {
            result.availability.forEach(a => {
                staffAvailabilityData[a.date] = a;
            });
        }
        
        renderAvailabilityCalendar(startDate, lastDay, month);
    } catch (error) {
        console.error('Error loading availability:', error);
    }
}

function renderAvailabilityCalendar(startDate, lastDay, currentMonth) {
    const container = document.getElementById('availability-calendar');
    const today = new Date().toISOString().split('T')[0];
    
    let html = '';
    let current = new Date(startDate);
    
    while (current <= lastDay || current.getDay() !== 1) {
        const dateStr = current.toISOString().split('T')[0];
        const isCurrentMonth = current.getMonth() === currentMonth;
        const isToday = dateStr === today;
        const availability = staffAvailabilityData[dateStr];
        const isWorking = availability ? availability.is_working_day == 1 : (current.getDay() !== 0 && current.getDay() !== 6);
        
        html += `
            <div class="day-cell ${isWorking ? 'working' : 'off'} ${isToday ? 'today' : ''}"
                 style="${!isCurrentMonth ? 'opacity:0.4;' : ''}"
                 onclick="toggleDayAvailability('${dateStr}', ${isWorking ? 'false' : 'true'})">
                <div class="day-num">${current.getDate()}</div>
                ${availability ? `<small>${availability.start_time?.substring(0,5) || ''}</small>` : ''}
            </div>
        `;
        
        current.setDate(current.getDate() + 1);
        if (current > lastDay && current.getDay() === 1) break;
    }
    
    container.innerHTML = html;
}

async function toggleDayAvailability(date, setWorking) {
    if (!selectedStaffId) return;
    
    try {
        const response = await fetch('/api/services/set_availability.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                staff_id: selectedStaffId,
                date: date,
                is_working_day: setWorking,
                start_time: '09:00:00',
                end_time: '17:00:00'
            })
        });
        
        const result = await response.json();
        if (result.success) {
            await loadAvailabilityCalendar();
        }
    } catch (error) {
        console.error('Error toggling availability:', error);
    }
}

function changeAvailabilityMonth(delta) {
    availabilityMonth.setMonth(availabilityMonth.getMonth() + delta);
    loadAvailabilityCalendar();
}

async function setWeekWorking() {
    if (!selectedStaffId) { alert('Velg en ansatt først'); return; }
    await setBulkAvailability(true);
}

async function setWeekOff() {
    if (!selectedStaffId) { alert('Velg en ansatt først'); return; }
    await setBulkAvailability(false);
}

async function setBulkAvailability(isWorking) {
    const today = new Date();
    const dates = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(d.getDate() + i);
        dates.push({
            date: d.toISOString().split('T')[0],
            is_working_day: isWorking
        });
    }
    
    try {
        const response = await fetch('/api/services/bulk_availability.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                staff_id: selectedStaffId,
                dates: dates,
                default_start_time: '09:00:00',
                default_end_time: '17:00:00'
            })
        });
        
        const result = await response.json();
        if (result.success) {
            await loadAvailabilityCalendar();
        }
    } catch (error) {
        console.error('Error setting bulk availability:', error);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadServices();
    await loadStaff();
});

document.getElementById('tab-reservations')?.addEventListener('shown.bs.tab', async () => {
    await loadServices();
    await loadStaff();
});

document.querySelectorAll('#subtabs-reservation .nav-link').forEach(tab => {
    tab.addEventListener('click', function() {
        const target = this.getAttribute('href')?.replace('#', '');
        if (target === 'reservations-active-services') {
            loadServiceSelectList();
        } else if (target === 'reservations-create-service') {
            loadStaffListForAvailability();
        }
    });
});
</script>
