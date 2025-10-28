<!-- Reservasjonsoversikt Tab -->
<div id="reservations-overview" class="subtab-content">
        <div class="row">
            <!-- Left Sidebar: Filters & Stats -->
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><i class="bi bi-funnel me-1"></i>Filtre</h6>
                        
                        <div class="mb-3">
                            <label class="form-label small">Visning</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="viewType" id="viewCalendar" value="calendar" checked>
                                <label class="btn btn-outline-primary btn-sm" for="viewCalendar">
                                    <i class="bi bi-calendar3"></i> Kalender
                                </label>
                                
                                <input type="radio" class="btn-check" name="viewType" id="viewAgenda" value="agenda">
                                <label class="btn btn-outline-primary btn-sm" for="viewAgenda">
                                    <i class="bi bi-list-ul"></i> Liste
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="daysFilterContainer" style="display: none;">
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
                        
                        <button class="btn btn-primary btn-sm w-100" onclick="loadReservations()">
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
                
                <!-- Staff Legend for Calendar -->
                <div class="card mt-3" id="staffLegend">
                    <div class="card-body p-2">
                        <h6 class="small mb-2">Fargekoder:</h6>
                        <div id="staffLegendContent"></div>
                    </div>
                </div>
            </div>
            
            <!-- Main Area: Calendar, Day View, or Agenda -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <!-- Header with navigation -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <button class="btn btn-sm btn-outline-secondary" id="backToCal" style="display: none;" onclick="backToCalendar()">
                                    <i class="bi bi-arrow-left me-1"></i>Tilbake til kalender
                                </button>
                                <h5 class="card-title mb-0 d-inline-block ms-2">
                                    <i class="bi bi-calendar3 me-2"></i><span id="viewTitle">Reservasjoner</span>
                                </h5>
                            </div>
                            <div id="calendarNav">
                                <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(-1)">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="mx-3" id="currentMonth"></span>
                                <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(1)">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="goToToday()">
                                    I dag
                                </button>
                            </div>
                        </div>
                        
                        <!-- Calendar View -->
                        <div id="reservationsCalendar">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="calendarGrid">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Man</th>
                                            <th class="text-center">Tir</th>
                                            <th class="text-center">Ons</th>
                                            <th class="text-center">Tor</th>
                                            <th class="text-center">Fre</th>
                                            <th class="text-center">Lør</th>
                                            <th class="text-center">Søn</th>
                                        </tr>
                                    </thead>
                                    <tbody id="calendarBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Day View (24 hours) -->
                        <div id="reservationsDayView" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="dayViewGrid">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Tid</th>
                                            <th>Reservasjoner</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dayViewBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Agenda View -->
                        <div id="reservationsAgenda" style="display: none;">
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

    <!-- Opprett tjenester Tab -->
    <div id="reservations-create-service" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-plus-circle me-2"></i>Opprett ny tjeneste</h5>
                <p class="text-muted">Opprett en ny reserverbar tjeneste for dine kunder</p>
                
                <form id="create-service-form">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tjenestenavn *</label>
                            <input type="text" class="form-control" name="service_name" required>
                            <small class="text-muted">F.eks: "Bordreservasjon", "Konsultasjon", "Spa-behandling"</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type *</label>
                            <select class="form-select" name="service_type" required>
                                <option value="">Velg type</option>
                                <option value="appointment">Timeavtale</option>
                                <option value="table">Bordreservasjon</option>
                                <option value="room">Romreservasjon</option>
                                <option value="facility">Fasilitetsleie</option>
                                <option value="consultation">Konsultasjon</option>
                                <option value="other">Annet</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Beskrivelse</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Varighet (minutter) *</label>
                            <input type="number" class="form-control" name="duration" min="15" step="15" value="60" required>
                            <small class="text-muted">15, 30, 60, 90, 120, etc.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pris (NOK) *</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Maks kapasitet</label>
                            <input type="number" class="form-control" name="capacity" min="1" value="1">
                            <small class="text-muted">Antall personer per tidsslot</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tilgjengelig fra (tid)</label>
                            <input type="time" class="form-control" name="available_from" value="09:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tilgjengelig til (tid)</label>
                            <input type="time" class="form-control" name="available_to" value="17:00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tilgjengelige dager</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="1" id="day-mon" checked>
                                <label class="form-check-label" for="day-mon">Man</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="2" id="day-tue" checked>
                                <label class="form-check-label" for="day-tue">Tir</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="3" id="day-wed" checked>
                                <label class="form-check-label" for="day-wed">Ons</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="4" id="day-thu" checked>
                                <label class="form-check-label" for="day-thu">Tor</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="5" id="day-fri" checked>
                                <label class="form-check-label" for="day-fri">Fre</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="6" id="day-sat">
                                <label class="form-check-label" for="day-sat">Lør</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="available_days[]" value="0" id="day-sun">
                                <label class="form-check-label" for="day-sun">Søn</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ansatte som kan utføre denne tjenesten *</label>
                        <select class="form-select" name="assigned_staff[]" id="assigned-staff-select" multiple size="5">
                            <option value="" disabled>Laster ansatte...</option>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd for å velge flere ansatte. Jo flere ansatte, jo flere parallelle bookinger mulig.</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="service-active" checked>
                            <label class="form-check-label" for="service-active">Aktiv (synlig for kunder)</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Opprett tjeneste
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Nullstill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Aktive tjenester Tab -->
    <div id="reservations-active-services" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1"><i class="bi bi-list-check me-2"></i>Aktive tjenester</h5>
                        <p class="text-muted mb-0">Oversikt over alle dine reserverbare tjenester</p>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="document.querySelector('[href=\'#reservations-create-service\']').click()">
                        <i class="bi bi-plus-circle me-1"></i>Opprett ny
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tjeneste</th>
                                <th>Type</th>
                                <th>Varighet</th>
                                <th>Pris</th>
                                <th>Kapasitet</th>
                                <th>Ansatte</th>
                                <th>Tilgjengelighet</th>
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody id="active-services-list">
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Laster...</span>
                                    </div>
                                    <p class="text-muted mt-2 mb-0">Laster tjenester...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#calendarGrid {
    font-size: 0.85rem;
}

#calendarGrid th {
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 0.5rem;
}

#calendarGrid td {
    height: 100px;
    vertical-align: top;
    padding: 0.25rem;
    position: relative;
    cursor: pointer;
}

#calendarGrid td:hover {
    background-color: #f8f9fa;
}

.calendar-day-header {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.calendar-day-other-month {
    background-color: #f8f9fa;
    color: #6c757d;
}

.calendar-day-today {
    background-color: #e7f3ff;
}

.calendar-reservation {
    font-size: 0.7rem;
    padding: 0.15rem 0.25rem;
    margin-bottom: 0.15rem;
    border-radius: 3px;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.calendar-reservation:hover {
    opacity: 0.8;
}

.staff-legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.25rem;
}

.staff-color-box {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    margin-right: 0.5rem;
}

/* Day View Styles */
#dayViewGrid td {
    cursor: default;
    height: auto;
    padding: 0.5rem;
}

.time-slot {
    border-bottom: 1px solid #dee2e6;
}

.time-slot-empty {
    color: #6c757d;
    font-style: italic;
    font-size: 0.85rem;
}

.day-view-reservation {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 5px;
    border-left: 4px solid;
    cursor: pointer;
}

.day-view-reservation:hover {
    opacity: 0.9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.day-view-time {
    font-weight: 600;
    color: #495057;
}

.day-view-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.day-view-details {
    font-size: 0.85rem;
    color: #6c757d;
}
</style>

<script>
let currentViewDate = new Date();
let selectedDate = null;
let allReservations = [];
let allStaff = [];
const staffColors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'];

async function loadStaffFilter() {
    try {
        const response = await fetch('/api/get_staff.php');
        const result = await response.json();
        
        if (result.success && result.staff) {
            allStaff = result.staff;
            const staffFilter = document.getElementById('staffFilter');
            result.staff.forEach((staff, index) => {
                const option = document.createElement('option');
                option.value = staff.id;
                option.textContent = `${staff.first_name} ${staff.last_name}`;
                option.dataset.color = staffColors[index % staffColors.length];
                staffFilter.appendChild(option);
            });
            
            updateStaffLegend();
        }
    } catch (error) {
        console.error('Error loading staff:', error);
    }
}

function updateStaffLegend() {
    const legendContent = document.getElementById('staffLegendContent');
    legendContent.innerHTML = allStaff.map((staff, index) => {
        const color = staffColors[index % staffColors.length];
        return `
            <div class="staff-legend-item">
                <div class="staff-color-box" style="background-color: ${color};"></div>
                <small>${staff.first_name} ${staff.last_name}</small>
            </div>
        `;
    }).join('');
}

function getStaffColor(staffId) {
    const index = allStaff.findIndex(s => s.id === staffId);
    return index >= 0 ? staffColors[index % staffColors.length] : '#6c757d';
}

async function loadReservations() {
    const viewType = document.querySelector('input[name="viewType"]:checked').value;
    
    if (viewType === 'agenda') {
        await loadReservationsOverview();
    } else {
        await loadCalendarView();
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
            allReservations = result.reservations;
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

async function loadCalendarView() {
    const staffId = document.getElementById('staffFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    try {
        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const firstDayOfWeek = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
        const startDate = new Date(year, month, 1 - firstDayOfWeek);
        const endDate = new Date(year, month + 1, 7);
        
        const startDateStr = formatDateLocal(startDate);
        const endDateStr = formatDateLocal(endDate);
        
        const params = new URLSearchParams({ 
            start_date: startDateStr, 
            end_date: endDateStr, 
            staff_id: staffId, 
            status 
        });
        
        const response = await fetch(`/api/get_reservations_overview.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            allReservations = result.reservations;
            updateStatistics(result.stats);
            renderCalendar();
        }
    } catch (error) {
        console.error('Error:', error);
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
    const staffColor = getStaffColor(res.staff_id);
    
    return `
        <div class="card mb-2 border-start border-3" style="border-color: ${staffColor} !important;">
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

function renderCalendar() {
    const year = currentViewDate.getFullYear();
    const month = currentViewDate.getMonth();
    
    const monthName = currentViewDate.toLocaleDateString('no-NO', { year: 'numeric', month: 'long' });
    document.getElementById('currentMonth').textContent = monthName;
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    
    let firstDayOfWeek = firstDay.getDay() - 1;
    if (firstDayOfWeek === -1) firstDayOfWeek = 6;
    
    const calendarBody = document.getElementById('calendarBody');
    calendarBody.innerHTML = '';
    
    let dayCounter = 1;
    let nextMonthCounter = 1;
    
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    const prevMonthStart = prevMonthLastDay - firstDayOfWeek + 1;
    
    for (let week = 0; week < 6; week++) {
        const row = document.createElement('tr');
        
        for (let day = 0; day < 7; day++) {
            const cell = document.createElement('td');
            let currentDate;
            
            if (week === 0 && day < firstDayOfWeek) {
                const prevMonthDay = prevMonthStart + day;
                cell.innerHTML = `<div class="calendar-day-header text-muted">${prevMonthDay}</div>`;
                cell.classList.add('calendar-day-other-month');
                currentDate = new Date(year, month - 1, prevMonthDay);
            }
            else if (dayCounter > daysInMonth) {
                cell.innerHTML = `<div class="calendar-day-header text-muted">${nextMonthCounter}</div>`;
                cell.classList.add('calendar-day-other-month');
                currentDate = new Date(year, month + 1, nextMonthCounter);
                nextMonthCounter++;
            }
            else {
                currentDate = new Date(year, month, dayCounter);
                const isToday = isDateToday(currentDate);
                
                cell.innerHTML = `<div class="calendar-day-header ${isToday ? 'text-primary' : ''}">${dayCounter}</div>`;
                if (isToday) cell.classList.add('calendar-day-today');
                
                const dayReservations = getReservationsForDate(currentDate);
                dayReservations.forEach(res => {
                    const staffColor = getStaffColor(res.staff_id);
                    const time = new Date(res.start_datetime).toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
                    const resDiv = document.createElement('div');
                    resDiv.className = 'calendar-reservation';
                    resDiv.style.backgroundColor = staffColor;
                    resDiv.style.color = '#fff';
                    resDiv.textContent = `${time} ${res.title}`;
                    resDiv.onclick = (e) => {
                        e.stopPropagation();
                        viewReservation(res.id);
                    };
                    cell.appendChild(resDiv);
                });
                
                // Click on date to see day view
                cell.onclick = () => showDayView(currentDate);
                
                dayCounter++;
            }
            
            row.appendChild(cell);
        }
        
        calendarBody.appendChild(row);
        
        if (dayCounter > daysInMonth && nextMonthCounter > 7) break;
    }
}

function showDayView(date) {
    selectedDate = date;
    
    // Update title
    const dateStr = date.toLocaleDateString('no-NO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('viewTitle').textContent = dateStr;
    
    // Hide calendar, show day view
    document.getElementById('reservationsCalendar').style.display = 'none';
    document.getElementById('reservationsDayView').style.display = 'block';
    document.getElementById('calendarNav').style.display = 'none';
    document.getElementById('backToCal').style.display = 'inline-block';
    
    // Render 24-hour view
    renderDayView(date);
}

function backToCalendar() {
    selectedDate = null;
    
    document.getElementById('viewTitle').textContent = 'Reservasjoner';
    document.getElementById('reservationsCalendar').style.display = 'block';
    document.getElementById('reservationsDayView').style.display = 'none';
    document.getElementById('calendarNav').style.display = 'block';
    document.getElementById('backToCal').style.display = 'none';
}

function renderDayView(date) {
    const dayViewBody = document.getElementById('dayViewBody');
    dayViewBody.innerHTML = '';
    
    const dayReservations = getReservationsForDate(date);
    
    // Create 24 hour slots
    for (let hour = 0; hour < 24; hour++) {
        const row = document.createElement('tr');
        row.className = 'time-slot';
        
        const timeCell = document.createElement('td');
        timeCell.className = 'text-center fw-bold';
        timeCell.textContent = `${hour.toString().padStart(2, '0')}:00`;
        
        const reservationCell = document.createElement('td');
        
        // Find reservations in this hour
        const hourReservations = dayReservations.filter(res => {
            const resStart = new Date(res.start_datetime);
            const resEnd = new Date(res.end_datetime);
            const slotStart = new Date(date);
            slotStart.setHours(hour, 0, 0, 0);
            const slotEnd = new Date(date);
            slotEnd.setHours(hour, 59, 59, 999);
            
            return (resStart >= slotStart && resStart < slotEnd) || 
                   (resEnd > slotStart && resEnd <= slotEnd) ||
                   (resStart < slotStart && resEnd > slotEnd);
        });
        
        if (hourReservations.length === 0) {
            reservationCell.innerHTML = '<span class="time-slot-empty">Ingen reservasjoner</span>';
        } else {
            hourReservations.forEach(res => {
                const staffColor = getStaffColor(res.staff_id);
                const startTime = new Date(res.start_datetime).toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
                const endTime = new Date(res.end_datetime).toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
                const statusBadge = getStatusBadge(res.status);
                
                const resDiv = document.createElement('div');
                resDiv.className = 'day-view-reservation';
                resDiv.style.borderColor = staffColor;
                resDiv.style.backgroundColor = staffColor + '15';
                resDiv.innerHTML = `
                    <div class="day-view-title">${res.title || 'Ingen tittel'}</div>
                    <div class="day-view-time">${startTime} - ${endTime}</div>
                    <div class="day-view-details">
                        <i class="bi bi-person me-1"></i>${res.staff_name || 'Ikke tildelt'}
                        ${res.guest_count ? `<span class="ms-2"><i class="bi bi-people me-1"></i>${res.guest_count} gjester</span>` : ''}
                        <span class="ms-2">${(res.total_amount || 0).toLocaleString('no-NO')} kr</span>
                        <span class="ms-2">${statusBadge}</span>
                    </div>
                `;
                resDiv.onclick = () => viewReservation(res.id);
                reservationCell.appendChild(resDiv);
            });
        }
        
        row.appendChild(timeCell);
        row.appendChild(reservationCell);
        dayViewBody.appendChild(row);
    }
}

function formatDateLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getReservationsForDate(date) {
    // Get midnight boundaries for the selected date
    const dayStart = new Date(date);
    dayStart.setHours(0, 0, 0, 0);
    const dayEnd = new Date(date);
    dayEnd.setHours(23, 59, 59, 999);
    
    return allReservations.filter(res => {
        const resStart = new Date(res.start_datetime);
        const resEnd = new Date(res.end_datetime);
        
        // Reservation overlaps with this day if:
        // - It starts before day ends AND ends after day starts
        return resStart <= dayEnd && resEnd >= dayStart;
    });
}

function isDateToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

function changeMonth(delta) {
    currentViewDate.setMonth(currentViewDate.getMonth() + delta);
    loadCalendarView();
}

function goToToday() {
    currentViewDate = new Date();
    loadCalendarView();
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

function viewReservation(id) {
    alert('Se detaljer: ' + id);
}

function confirmReservation(id) {
    if (confirm('Bekreft denne reservasjonen?')) {
        alert('Bekreftet: ' + id);
        loadReservations();
    }
}

function cancelReservation(id) {
    if (confirm('Kansellere denne reservasjonen?')) {
        alert('Kansellert: ' + id);
        loadReservations();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadStaffFilter();
    
    const reservationTab = document.getElementById('tab-reservation');
    if (reservationTab) {
        reservationTab.addEventListener('shown.bs.tab', function() {
            loadReservations();
        });
    }
    
    // Load active services when the subtab is clicked
    const activeServicesLink = document.querySelector('[href="#reservations-active-services"]');
    if (activeServicesLink) {
        activeServicesLink.addEventListener('click', function() {
            setTimeout(() => loadActiveServices(), 100);
        });
    }
    
    // Load staff when create service tab is clicked
    const createServiceLink = document.querySelector('[href="#reservations-create-service"]');
    if (createServiceLink) {
        createServiceLink.addEventListener('click', function() {
            setTimeout(() => loadStaffForService(), 100);
        });
    }
    
    // View type toggle
    document.querySelectorAll('input[name="viewType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const agendaView = document.getElementById('reservationsAgenda');
            const calendarView = document.getElementById('reservationsCalendar');
            const calendarNav = document.getElementById('calendarNav');
            const daysFilterContainer = document.getElementById('daysFilterContainer');
            const staffLegend = document.getElementById('staffLegend');
            const dayView = document.getElementById('reservationsDayView');
            const backBtn = document.getElementById('backToCal');
            
            if (this.value === 'calendar') {
                agendaView.style.display = 'none';
                calendarView.style.display = 'block';
                dayView.style.display = 'none';
                calendarNav.style.display = 'block';
                daysFilterContainer.style.display = 'none';
                staffLegend.style.display = 'block';
                backBtn.style.display = 'none';
                document.getElementById('viewTitle').textContent = 'Reservasjoner';
                loadCalendarView();
            } else {
                agendaView.style.display = 'block';
                calendarView.style.display = 'none';
                dayView.style.display = 'none';
                calendarNav.style.display = 'none';
                daysFilterContainer.style.display = 'block';
                staffLegend.style.display = 'none';
                backBtn.style.display = 'none';
                document.getElementById('viewTitle').textContent = 'Reservasjoner';
                loadReservationsOverview();
            }
        });
    });
    
    // Filter changes
    document.getElementById('staffFilter').addEventListener('change', loadReservations);
    document.getElementById('statusFilter').addEventListener('change', loadReservations);
    
    // Create Service Form Handler
    const createServiceForm = document.getElementById('create-service-form');
    if (createServiceForm) {
        createServiceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                service_name: formData.get('service_name'),
                service_type: formData.get('service_type'),
                description: formData.get('description'),
                duration: formData.get('duration'),
                price: formData.get('price'),
                capacity: formData.get('capacity'),
                available_from: formData.get('available_from'),
                available_to: formData.get('available_to'),
                available_days: formData.getAll('available_days[]'),
                assigned_staff: formData.getAll('assigned_staff[]'),
                is_active: formData.get('is_active') ? 1 : 0
            };
            
            // Validate staff selection
            if (!data.assigned_staff || data.assigned_staff.length === 0) {
                alert('Vennligst velg minst én ansatt som kan utføre denne tjenesten');
                return;
            }
            
            fetch('api/create_reservation_service.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Tjeneste opprettet!');
                    createServiceForm.reset();
                    // Switch to active services tab
                    document.querySelector('[href="#reservations-active-services"]').click();
                    loadActiveServices();
                } else {
                    alert('Feil: ' + (result.message || 'Kunne ikke opprette tjeneste'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Feil ved opprettelse av tjeneste');
            });
        });
    }
});

// Load Staff for Service Assignment
function loadStaffForService() {
    const select = document.getElementById('assigned-staff-select');
    if (!select) return;
    
    select.innerHTML = '<option value="" disabled>Laster ansatte...</option>';
    
    fetch('api/get_staff.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.staff || data.staff.length === 0) {
                select.innerHTML = '<option value="" disabled>Ingen ansatte funnet</option>';
                return;
            }
            
            select.innerHTML = data.staff.map(staff => 
                `<option value="${staff.id}">${staff.first_name} ${staff.last_name}</option>`
            ).join('');
        })
        .catch(error => {
            console.error('Error loading staff:', error);
            select.innerHTML = '<option value="" disabled>Feil ved lasting av ansatte</option>';
        });
}

// Load Active Services Function
function loadActiveServices() {
    const tbody = document.getElementById('active-services-list');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Laster...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Laster tjenester...</p>
            </td>
        </tr>
    `;
    
    fetch('api/get_reservation_services.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
                            <p>${data.error || 'Kunne ikke laste tjenester'}</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            const services = data.services || [];
            
            if (services.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            <p>Ingen tjenester opprettet ennå</p>
                            <button class="btn btn-primary btn-sm mt-2" onclick="document.querySelector('[href=\\'#reservations-create-service\\']').click()">
                                <i class="bi bi-plus-circle me-1"></i>Opprett din første tjeneste
                            </button>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = services.map(service => {
                const typeLabels = {
                    'appointment': 'Timeavtale',
                    'table': 'Bordreservasjon',
                    'room': 'Romreservasjon',
                    'facility': 'Fasilitetsleie',
                    'consultation': 'Konsultasjon',
                    'other': 'Annet'
                };
                
                const statusBadge = service.is_active == 1 
                    ? '<span class="badge bg-success">Aktiv</span>' 
                    : '<span class="badge bg-secondary">Inaktiv</span>';
                
                const staffDisplay = service.assigned_staff_names 
                    ? `<span class="badge bg-info text-dark" title="${service.assigned_staff_names}">${service.staff_count} ansatt${service.staff_count > 1 ? 'e' : ''}</span>`
                    : '<span class="text-muted small">Ingen</span>';
                
                return `
                    <tr>
                        <td><strong>${service.service_name}</strong></td>
                        <td>${typeLabels[service.service_type] || service.service_type}</td>
                        <td>${service.duration_minutes} min</td>
                        <td>${parseFloat(service.price).toFixed(2)} NOK</td>
                        <td>${service.capacity || 1} pers</td>
                        <td class="small">${staffDisplay}</td>
                        <td class="small">${service.available_from} - ${service.available_to}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editService('${service.id}')" title="Rediger">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteService('${service.id}')" title="Slett">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(error => {
            console.error('Error loading services:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        <p>Feil ved lasting av tjenester</p>
                    </td>
                </tr>
            `;
        });
}

function editService(id) {
    alert('Rediger tjeneste: ' + id + ' (kommer snart)');
}

function deleteService(id) {
    if (confirm('Er du sikker på at du vil slette denne tjenesten?')) {
        fetch('api/delete_reservation_service.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ service_id: id })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Tjeneste slettet');
                loadActiveServices();
            } else {
                alert('Feil: ' + (result.message || 'Kunne ikke slette tjeneste'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Feil ved sletting');
        });
    }
}
</script>
