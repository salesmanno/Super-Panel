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
                            <label class="form-label small">Visning</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="viewType" id="viewAgenda" value="agenda" checked>
                                <label class="btn btn-outline-primary btn-sm" for="viewAgenda">
                                    <i class="bi bi-list-ul"></i>
                                </label>
                                
                                <input type="radio" class="btn-check" name="viewType" id="viewCalendar" value="calendar">
                                <label class="btn btn-outline-primary btn-sm" for="viewCalendar">
                                    <i class="bi bi-calendar3"></i>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="daysFilterContainer">
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
                <div class="card mt-3" id="staffLegend" style="display: none;">
                    <div class="card-body p-2">
                        <h6 class="small mb-2">Fargekoder:</h6>
                        <div id="staffLegendContent"></div>
                    </div>
                </div>
            </div>
            
            <!-- Main Area: Agenda or Calendar -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0"><i class="bi bi-calendar3 me-2"></i>Reservasjoner</h5>
                            <div id="calendarNav" style="display: none;">
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
                        
                        <!-- Agenda View -->
                        <div id="reservationsAgenda">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Laster...</span>
                                </div>
                                <p class="mt-2 small">Laster reservasjoner...</p>
                            </div>
                        </div>
                        
                        <!-- Calendar View -->
                        <div id="reservationsCalendar" style="display: none;">
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
</style>

<script>
let currentViewDate = new Date();
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
        // Get full month range including some overflow for display
        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();
        
        // First day of month
        const firstDay = new Date(year, month, 1);
        
        // Last day of month
        const lastDay = new Date(year, month + 1, 0);
        
        // Include overflow days from prev/next month for calendar grid
        const firstDayOfWeek = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
        const startDate = new Date(year, month, 1 - firstDayOfWeek);
        
        // Calculate end with potential overflow
        const endDate = new Date(year, month + 1, 7);
        
        // Format dates for API (use local timezone, not UTC)
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
    
    // Update month display
    const monthName = currentViewDate.toLocaleDateString('no-NO', { year: 'numeric', month: 'long' });
    document.getElementById('currentMonth').textContent = monthName;
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    
    // Get day of week for first day (0=Sunday, adjust to Monday=0)
    let firstDayOfWeek = firstDay.getDay() - 1;
    if (firstDayOfWeek === -1) firstDayOfWeek = 6;
    
    const calendarBody = document.getElementById('calendarBody');
    calendarBody.innerHTML = '';
    
    let dayCounter = 1;
    let nextMonthCounter = 1;
    
    // Calculate days from previous month
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    const prevMonthStart = prevMonthLastDay - firstDayOfWeek + 1;
    
    // Create calendar rows (6 weeks max)
    for (let week = 0; week < 6; week++) {
        const row = document.createElement('tr');
        
        for (let day = 0; day < 7; day++) {
            const cell = document.createElement('td');
            let currentDate;
            let isCurrentMonth = true;
            
            // Previous month days
            if (week === 0 && day < firstDayOfWeek) {
                const prevMonthDay = prevMonthStart + day;
                cell.innerHTML = `<div class="calendar-day-header text-muted">${prevMonthDay}</div>`;
                cell.classList.add('calendar-day-other-month');
                isCurrentMonth = false;
                currentDate = new Date(year, month - 1, prevMonthDay);
            }
            // Next month days
            else if (dayCounter > daysInMonth) {
                cell.innerHTML = `<div class="calendar-day-header text-muted">${nextMonthCounter}</div>`;
                cell.classList.add('calendar-day-other-month');
                isCurrentMonth = false;
                currentDate = new Date(year, month + 1, nextMonthCounter);
                nextMonthCounter++;
            }
            // Current month days
            else {
                currentDate = new Date(year, month, dayCounter);
                const isToday = isDateToday(currentDate);
                
                cell.innerHTML = `<div class="calendar-day-header ${isToday ? 'text-primary' : ''}">${dayCounter}</div>`;
                if (isToday) cell.classList.add('calendar-day-today');
                
                // Add reservations for this day
                const dayReservations = getReservationsForDate(currentDate);
                dayReservations.forEach(res => {
                    const staffColor = getStaffColor(res.staff_id);
                    const time = new Date(res.start_datetime).toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
                    const resDiv = document.createElement('div');
                    resDiv.className = 'calendar-reservation';
                    resDiv.style.backgroundColor = staffColor;
                    resDiv.style.color = '#fff';
                    resDiv.textContent = `${time} ${res.title}`;
                    resDiv.onclick = () => viewReservation(res.id);
                    cell.appendChild(resDiv);
                });
                
                dayCounter++;
            }
            
            row.appendChild(cell);
        }
        
        calendarBody.appendChild(row);
        
        // Stop if we've filled all days
        if (dayCounter > daysInMonth && nextMonthCounter > 7) break;
    }
}

function formatDateLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getReservationsForDate(date) {
    const dateStr = formatDateLocal(date);
    return allReservations.filter(res => {
        const resDate = res.start_datetime.split(' ')[0];
        return resDate === dateStr;
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

// View type change handler
document.addEventListener('DOMContentLoaded', function() {
    loadStaffFilter();
    
    const reservationTab = document.getElementById('tab-reservation');
    if (reservationTab) {
        reservationTab.addEventListener('shown.bs.tab', function() {
            loadReservations();
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
            
            if (this.value === 'calendar') {
                agendaView.style.display = 'none';
                calendarView.style.display = 'block';
                calendarNav.style.display = 'block';
                daysFilterContainer.style.display = 'none';
                staffLegend.style.display = 'block';
                loadCalendarView();
            } else {
                agendaView.style.display = 'block';
                calendarView.style.display = 'none';
                calendarNav.style.display = 'none';
                daysFilterContainer.style.display = 'block';
                staffLegend.style.display = 'none';
                loadReservationsOverview();
            }
        });
    });
    
    // Filter changes
    document.getElementById('staffFilter').addEventListener('change', loadReservations);
    document.getElementById('statusFilter').addEventListener('change', loadReservations);
});
</script>
