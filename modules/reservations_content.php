<div class="module-content">
    <!-- Reservasjonsoversikt Tab -->
    <div id="reservations-overview" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-calendar3 me-2"></i>Reservasjonsoversikt</h5>
                <p class="text-muted">Oversikt over kommende reservasjoner</p>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Antall dager fremover</label>
                        <select class="form-select" id="daysFilter">
                            <option value="7">7 dager</option>
                            <option value="14" selected>14 dager</option>
                            <option value="30">30 dager</option>
                            <option value="60">60 dager</option>
                            <option value="90">90 dager</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filtrer etter ansatt</label>
                        <select class="form-select" id="staffFilter">
                            <option value="">Alle ansatte</option>
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Alle statuser</option>
                            <option value="pending">Ventende</option>
                            <option value="confirmed">Bekreftet</option>
                            <option value="cancelled">Kansellert</option>
                            <option value="completed">Fullført</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="loadReservationsOverview()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Oppdater
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4" id="statsCards">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 opacity-75">Totalt reservasjoner</h6>
                                <h3 class="card-title mb-0" id="statTotal">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 opacity-75">Ventende</h6>
                                <h3 class="card-title mb-0" id="statPending">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 opacity-75">Bekreftede</h6>
                                <h3 class="card-title mb-0" id="statConfirmed">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 opacity-75">Total verdi</h6>
                                <h3 class="card-title mb-0" id="statValue">0 kr</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reservations Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dato & Tid</th>
                                <th>Kunde</th>
                                <th>Ansatt</th>
                                <th>Tittel</th>
                                <th>Antall gjester</th>
                                <th>Beløp</th>
                                <th>Status</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="reservationsTableBody">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Laster...</span>
                                    </div>
                                    <p class="mt-2">Laster reservasjoner...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservasjonsmottak Tab -->
    <div id="reservations-incoming" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-inbox me-2"></i>Ventende reservasjoner</h5>
                <p class="text-muted">Reservasjoner som venter på bekreftelse</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reservasjonsnr.</th>
                                <th>Kunde</th>
                                <th>Type</th>
                                <th>Dato</th>
                                <th>Status</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Ingen ventende reservasjoner
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bekreftede Reservasjoner Tab -->
    <div id="reservations-confirmed" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-check-circle me-2"></i>Bekreftede reservasjoner</h5>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="date" class="form-control" placeholder="Fra dato">
                        </div>
                        <div class="col-md-4">
                            <input type="date" class="form-control" placeholder="Til dato">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i>Søk
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reservasjonsnr.</th>
                                <th>Kunde</th>
                                <th>Type</th>
                                <th>Fra dato</th>
                                <th>Til dato</th>
                                <th>Beløp</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Ingen bekreftede reservasjoner
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load staff list for filter
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

// Load reservations overview
async function loadReservationsOverview() {
    const days = document.getElementById('daysFilter').value;
    const staffId = document.getElementById('staffFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const tableBody = document.getElementById('reservationsTableBody');
    tableBody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center text-muted py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Laster...</span>
                </div>
                <p class="mt-2">Laster reservasjoner...</p>
            </td>
        </tr>
    `;
    
    try {
        const params = new URLSearchParams({
            days: days,
            staff_id: staffId,
            status: status
        });
        
        const response = await fetch(`/api/get_reservations_overview.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            updateStatistics(result.stats);
            displayReservations(result.reservations);
        } else {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        ${result.error || 'Kunne ikke laste reservasjoner'}
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading reservations:', error);
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    Nettverksfeil ved lasting av reservasjoner
                </td>
            </tr>
        `;
    }
}

function updateStatistics(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPending').textContent = stats.pending || 0;
    document.getElementById('statConfirmed').textContent = stats.confirmed || 0;
    document.getElementById('statValue').textContent = (stats.total_value || 0).toLocaleString('no-NO') + ' kr';
}

function displayReservations(reservations) {
    const tableBody = document.getElementById('reservationsTableBody');
    
    if (!reservations || reservations.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    Ingen reservasjoner funnet for valgte kriterier
                </td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = reservations.map(res => {
        const startDate = new Date(res.start_datetime);
        const endDate = new Date(res.end_datetime);
        const dateStr = startDate.toLocaleDateString('no-NO', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        const timeStr = `${startDate.toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' })} - ${endDate.toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' })}`;
        
        const statusBadge = getStatusBadge(res.status);
        
        return `
            <tr>
                <td>
                    <strong>${dateStr}</strong><br>
                    <small class="text-muted">${timeStr}</small>
                </td>
                <td>${res.customer_name || 'Ingen kunde'}</td>
                <td>${res.staff_name || '-'}</td>
                <td>${res.title || '-'}</td>
                <td class="text-center">${res.guest_count || 1}</td>
                <td>${(res.total_amount || 0).toLocaleString('no-NO')} ${res.currency || 'NOK'}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewReservation('${res.id}')" title="Se detaljer">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="confirmReservation('${res.id}')" title="Bekreft">
                            <i class="bi bi-check"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="cancelReservation('${res.id}')" title="Avbryt">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function getStatusBadge(status) {
    const statusMap = {
        'pending': '<span class="badge bg-warning">Ventende</span>',
        'confirmed': '<span class="badge bg-success">Bekreftet</span>',
        'cancelled': '<span class="badge bg-danger">Kansellert</span>',
        'completed': '<span class="badge bg-info">Fullført</span>'
    };
    return statusMap[status] || '<span class="badge bg-secondary">Ukjent</span>';
}

function viewReservation(id) {
    alert('Se detaljer for reservasjon: ' + id);
}

function confirmReservation(id) {
    if (confirm('Er du sikker på at du vil bekrefte denne reservasjonen?')) {
        alert('Reservasjon bekreftet: ' + id);
        loadReservationsOverview();
    }
}

function cancelReservation(id) {
    if (confirm('Er du sikker på at du vil kansellere denne reservasjonen?')) {
        alert('Reservasjon kansellert: ' + id);
        loadReservationsOverview();
    }
}

// Initialize when tab is shown
document.addEventListener('DOMContentLoaded', function() {
    // Load staff filter
    loadStaffFilter();
    
    // Load reservations when the reservations module tab is activated
    const reservationTab = document.getElementById('tab-reservation');
    if (reservationTab) {
        reservationTab.addEventListener('shown.bs.tab', function() {
            loadReservationsOverview();
        });
    }
});
</script>
