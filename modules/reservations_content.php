<div class="module-content">
    <!-- Reservasjonsoversikt Tab -->
    <div id="reservations-overview" class="subtab-content">
        <div class="row">
            <!-- Left Sidebar: Filters & Stats -->
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><i class="bi bi-funnel me-1"></i>Filtre</h6>
                        
                        <div class="mb-3">
                            <label class="form-label small">Dager fremover</label>
                            <select class="form-select form-select-sm" id="daysFilter">
                                <option value="7">7 dager</option>
                                <option value="14" selected>14 dager</option>
                                <option value="30">30 dager</option>
                                <option value="60">60 dager</option>
                                <option value="90">90 dager</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small">Ansatt</label>
                            <select class="form-select form-select-sm" id="staffFilter">
                                <option value="">Alle</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="statusFilter">
                                <option value="">Alle</option>
                                <option value="pending">Ventende</option>
                                <option value="confirmed">Bekreftet</option>
                                <option value="cancelled">Kansellert</option>
                            </select>
                        </div>
                        
                        <button class="btn btn-primary btn-sm w-100" onclick="loadReservationsOverview()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Oppdater
                        </button>
                    </div>
                </div>
                
                <!-- Compact Stats -->
                <div class="card">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="small text-muted">Totalt</span>
                            <strong id="statTotal">0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="small text-muted">Ventende</span>
                            <span class="badge bg-warning" id="statPending">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="small text-muted">Bekreftede</span>
                            <span class="badge bg-success" id="statConfirmed">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Verdi</span>
                            <strong class="text-primary" id="statValue">0 kr</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Area: Agenda Timeline -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0"><i class="bi bi-calendar3 me-2"></i>Kommende reservasjoner</h5>
                        </div>
                        
                        <div id="reservationsAgenda">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Laster...</span>
                                </div>
                                <p class="mt-2 small">Laster reservasjoner...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservasjonsmottak Tab -->
    <div id="reservations-incoming" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-inbox me-2"></i>Ventende reservasjoner</h5>
                <p class="text-muted small">Reservasjoner som venter på bekreftelse</p>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <p>Ingen ventende reservasjoner</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bekreftede Reservasjoner Tab -->
    <div id="reservations-confirmed" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-check-circle me-2"></i>Bekreftede reservasjoner</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="date" class="form-control form-control-sm" placeholder="Fra dato">
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control form-control-sm" placeholder="Til dato">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i>Søk
                        </button>
                    </div>
                </div>
                <div class="text-center text-muted py-4">
                    <p>Ingen bekreftede reservasjoner</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadStaffFilter() {
    try {
        const response = await fetch('/api/get_staff.php');
        const result = await response.json();
        
        if (result.success && result.staff) {
            const staffFilter = document.getElementById('staffFilter');
            result.staff.forEach(staff => {
                const option = document.createElement('option');
                option.value = staff.id;
                option.textContent = `${staff.first_name} ${staff.last_name}`;
                staffFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading staff:', error);
    }
}

async function loadReservationsOverview() {
    const days = document.getElementById('daysFilter').value;
    const staffId = document.getElementById('staffFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const agendaDiv = document.getElementById('reservationsAgenda');
    agendaDiv.innerHTML = `
        <div class="text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Laster...</span>
            </div>
            <p class="mt-2 small">Laster reservasjoner...</p>
        </div>
    `;
    
    try {
        const params = new URLSearchParams({ days, staff_id: staffId, status });
        const response = await fetch(`/api/get_reservations_overview.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            updateStatistics(result.stats);
            displayReservationsAgenda(result.reservations);
        } else {
            agendaDiv.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Kunne ikke laste reservasjoner'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        agendaDiv.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>Nettverksfeil
            </div>
        `;
    }
}

function updateStatistics(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPending').textContent = stats.pending || 0;
    document.getElementById('statConfirmed').textContent = stats.confirmed || 0;
    document.getElementById('statValue').textContent = (stats.total_value || 0).toLocaleString('no-NO') + ' kr';
}

function displayReservationsAgenda(reservations) {
    const agendaDiv = document.getElementById('reservationsAgenda');
    
    if (!reservations || reservations.length === 0) {
        agendaDiv.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                <p class="small">Ingen reservasjoner funnet</p>
            </div>
        `;
        return;
    }
    
    // Group by date
    const grouped = {};
    reservations.forEach(res => {
        const date = res.start_datetime.split(' ')[0];
        if (!grouped[date]) grouped[date] = [];
        grouped[date].push(res);
    });
    
    let html = '';
    Object.keys(grouped).sort().forEach(date => {
        const d = new Date(date);
        const dateStr = d.toLocaleDateString('no-NO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        
        html += `
            <div class="mb-4">
                <h6 class="text-muted border-bottom pb-2 mb-3">${dateStr}</h6>
                ${grouped[date].map(res => createReservationCard(res)).join('')}
            </div>
        `;
    });
    
    agendaDiv.innerHTML = html;
}

function createReservationCard(res) {
    const startTime = new Date(res.start_datetime).toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
    const endTime = new Date(res.end_datetime).toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
    const statusBadge = getStatusBadge(res.status);
    
    return `
        <div class="card mb-2 border-start border-3 border-${getStatusColor(res.status)}">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-center">
                            <div class="fw-bold">${startTime}</div>
                            <div class="small text-muted">${endTime}</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fw-bold">${res.title || 'Ingen tittel'}</div>
                        <div class="small text-muted">
                            <i class="bi bi-person me-1"></i>${res.staff_name || 'Ikke tildelt'}
                            ${res.guest_count ? `<span class="ms-2"><i class="bi bi-people me-1"></i>${res.guest_count} gjester</span>` : ''}
                        </div>
                    </div>
                    <div class="col-auto">
                        ${statusBadge}
                    </div>
                    <div class="col-auto">
                        <strong class="text-primary">${(res.total_amount || 0).toLocaleString('no-NO')} kr</strong>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" onclick="viewReservation('${res.id}')" title="Se">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${res.status === 'pending' ? `
                            <button class="btn btn-outline-success" onclick="confirmReservation('${res.id}')" title="Bekreft">
                                <i class="bi bi-check"></i>
                            </button>
                            ` : ''}
                            <button class="btn btn-outline-danger" onclick="cancelReservation('${res.id}')" title="Avbryt">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getStatusBadge(status) {
    const map = {
        'pending': '<span class="badge bg-warning text-dark">Ventende</span>',
        'confirmed': '<span class="badge bg-success">Bekreftet</span>',
        'cancelled': '<span class="badge bg-danger">Kansellert</span>',
        'completed': '<span class="badge bg-secondary">Fullført</span>'
    };
    return map[status] || '<span class="badge bg-secondary">Ukjent</span>';
}

function getStatusColor(status) {
    const map = {
        'pending': 'warning',
        'confirmed': 'success',
        'cancelled': 'danger',
        'completed': 'secondary'
    };
    return map[status] || 'secondary';
}

function viewReservation(id) {
    alert('Se detaljer: ' + id);
}

function confirmReservation(id) {
    if (confirm('Bekreft denne reservasjonen?')) {
        alert('Bekreftet: ' + id);
        loadReservationsOverview();
    }
}

function cancelReservation(id) {
    if (confirm('Kansellere denne reservasjonen?')) {
        alert('Kansellert: ' + id);
        loadReservationsOverview();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadStaffFilter();
    
    const reservationTab = document.getElementById('tab-reservation');
    if (reservationTab) {
        reservationTab.addEventListener('shown.bs.tab', function() {
            loadReservationsOverview();
        });
    }
});
</script>
