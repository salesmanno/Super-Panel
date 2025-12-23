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
    font-size: 0.75rem;
    background: #e7f3ff;
    color: #1976d2;
    margin: 0.15rem;
}
.staff-badge.primary {
    background: #fef3c7;
    color: #d97706;
    font-weight: 600;
}
.staff-assignment-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 6px;
    margin-bottom: 0.25rem;
}
.staff-assignment-row:hover {
    background: #f8f9fa;
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

.booking-carousel-container {
    display: flex;
    justify-content: center;
    padding: 2rem 1rem;
    min-height: 400px;
}
.booking-carousel-card {
    position: relative;
    width: 100%;
    max-width: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 1.5rem;
    color: white;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
    touch-action: pan-y;
    user-select: none;
    overflow: hidden;
    transition: transform 0.2s ease;
}
.booking-carousel-card.swiping-left {
    transform: translateX(-10px);
}
.booking-carousel-card.swiping-right {
    transform: translateX(10px);
}
.booking-carousel-card.swiping-down {
    transform: translateY(10px);
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
.booking-carousel-card.swiping-up {
    transform: translateY(-10px);
}
.carousel-index-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.75rem;
    opacity: 0.8;
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
}
.carousel-countdown {
    text-align: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    cursor: pointer;
}
.countdown-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}
.countdown-time {
    font-size: 2.5rem;
    font-weight: 700;
    letter-spacing: 2px;
}
.carousel-booking-info {
    padding: 0.5rem 0;
}
.booking-service {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}
.booking-customer, .booking-staff, .booking-datetime {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.35rem;
}
.booking-price {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0.5rem 0;
}
.booking-payment .badge {
    font-size: 0.75rem;
}
.carousel-edit-panel {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    color: #333;
    border-radius: 20px 20px 0 0;
    padding: 1rem 1.5rem 1.5rem;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    max-height: 70%;
    overflow-y: auto;
}
.carousel-edit-panel.show {
    transform: translateY(0);
}
.edit-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}
.carousel-swipe-hints {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 0 0.5rem;
    pointer-events: none;
    opacity: 0.3;
    transform: translateY(-50%);
}
.swipe-hint {
    font-size: 1.5rem;
}
.carousel-empty {
    text-align: center;
    padding: 3rem 1rem;
}
.carousel-empty i {
    font-size: 4rem;
    opacity: 0.5;
    margin-bottom: 1rem;
    display: block;
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

<div id="reservations-schedule" class="subtab-content" style="display: none;">
    <div class="booking-carousel-container">
        <div class="booking-carousel-card" id="booking-carousel">
            <div class="carousel-index-indicator">
                <span id="carousel-current">1</span> / <span id="carousel-total">0</span>
            </div>
            
            <div class="carousel-main-content" id="carousel-main">
                <div class="carousel-countdown" id="carousel-countdown">
                    <div class="countdown-label">Neste time om</div>
                    <div class="countdown-time" id="countdown-display">--:--</div>
                </div>
                
                <div class="carousel-booking-info" id="carousel-info">
                    <div class="booking-service" id="carousel-service">Laster...</div>
                    <div class="booking-customer" id="carousel-customer"></div>
                    <div class="booking-staff" id="carousel-staff"></div>
                    <div class="booking-price" id="carousel-price"></div>
                    <div class="booking-datetime" id="carousel-datetime"></div>
                    <div class="booking-payment" id="carousel-payment"></div>
                </div>
            </div>
            
            <div class="carousel-edit-panel" id="carousel-edit-panel" style="display: none;">
                <div class="edit-panel-header">
                    <strong>Flytt time</strong>
                    <button class="btn btn-sm btn-outline-secondary" onclick="closeEditPanel()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="edit-panel-content">
                    <div class="mb-2">
                        <label class="form-label small mb-1">Tjeneste</label>
                        <div id="edit-service-name" class="fw-semibold"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Kunde</label>
                        <div id="edit-customer-name"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Pris</label>
                        <div id="edit-price"></div>
                    </div>
                    <hr class="my-2">
                    <div class="mb-2">
                        <label class="form-label small mb-1">Behandler</label>
                        <select class="form-select form-select-sm" id="edit-staff-select">
                            <option value="">Laster...</option>
                        </select>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Ny dato</label>
                            <input type="date" class="form-control form-control-sm" id="edit-new-date">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Ny tid</label>
                            <select class="form-select form-select-sm" id="edit-new-time">
                                <option value="08:00">08:00</option>
                                <option value="08:30">08:30</option>
                                <option value="09:00">09:00</option>
                                <option value="09:30">09:30</option>
                                <option value="10:00">10:00</option>
                                <option value="10:30">10:30</option>
                                <option value="11:00">11:00</option>
                                <option value="11:30">11:30</option>
                                <option value="12:00">12:00</option>
                                <option value="12:30">12:30</option>
                                <option value="13:00">13:00</option>
                                <option value="13:30">13:30</option>
                                <option value="14:00">14:00</option>
                                <option value="14:30">14:30</option>
                                <option value="15:00">15:00</option>
                                <option value="15:30">15:30</option>
                                <option value="16:00">16:00</option>
                                <option value="16:30">16:30</option>
                                <option value="17:00">17:00</option>
                                <option value="17:30">17:30</option>
                                <option value="18:00">18:00</option>
                                <option value="18:30">18:30</option>
                                <option value="19:00">19:00</option>
                                <option value="19:30">19:30</option>
                                <option value="20:00">20:00</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm w-100" onclick="saveReschedule()">
                        <i class="bi bi-check-lg me-1"></i>Lagre endringer
                    </button>
                </div>
            </div>
            
            <div class="carousel-swipe-hints">
                <div class="swipe-hint left"><i class="bi bi-chevron-left"></i></div>
                <div class="swipe-hint right"><i class="bi bi-chevron-right"></i></div>
            </div>
            
            <div class="carousel-empty" id="carousel-empty" style="display: none;">
                <i class="bi bi-calendar-x"></i>
                <p>Ingen kommende bookinger</p>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="cancelConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Avbestill time?</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="cancel-confirm-text">Er du sikker på at du vil avbestille denne timen?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Nei</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancelBooking()">Ja, avbestill</button>
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" id="service-category" placeholder="F.eks. Frisør, Verksted">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kapasitet (ansatte kreves)</label>
                            <input type="number" class="form-control" id="service-capacity" value="1" min="1">
                            <small class="text-muted">Hvor mange ansatte trengs for jobben</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Maks bookinger/dag</label>
                            <input type="number" class="form-control" id="service-max-daily" placeholder="Ubegrenset">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bilde-URL</label>
                            <input type="url" class="form-control" id="service-img-url" placeholder="https://eksempel.no/bilde.jpg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tilgjengelig tid</label>
                            <div class="input-group">
                                <input type="time" class="form-control" id="service-available-from" value="09:00">
                                <span class="input-group-text">til</span>
                                <input type="time" class="form-control" id="service-available-to" value="17:00">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tilknytt ansatte</label>
                        <div id="service-staff-checkboxes" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                            <div class="text-muted small">Laster ansatte...</div>
                        </div>
                        <small class="text-muted">Velg hvilke ansatte som kan utføre denne tjenesten</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Betalingsmetoder</label>
                        <div id="service-payment-methods-container" class="d-flex gap-3 flex-wrap">
                            <div class="text-muted small">Laster betalingsmetoder...</div>
                        </div>
                        <small class="text-muted">Vis kun metoder konfigurert i Økonomi-modulen</small>
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
                    <label class="form-label">Maks bookinger/dag</label>
                    <input type="number" class="form-control" id="add-staff-max" value="5">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="add-staff-primary">
                    <label class="form-check-label" for="add-staff-primary">Primærarbeider</label>
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
let allPaymentMethods = [];
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

async function loadNextBooking() {
    try {
        const response = await fetch('/api/services/get_next_booking.php');
        const result = await response.json();
        
        const banner = document.getElementById('next-booking-schedule-banner');
        const content = document.getElementById('next-booking-schedule-content');
        
        if (!banner || !content) return;
        
        if (result.success && result.has_next) {
            const b = result.next_booking;
            const hours = b.time_until.hours;
            const mins = b.time_until.minutes;
            const timeStr = hours > 0 ? `${hours} t ${mins} min` : `${mins} min`;
            const priceFormatted = b.price ? parseFloat(b.price).toFixed(0) + ' kr' : '0 kr';
            const paymentBadge = b.is_paid ? 
                '<span class="badge bg-success">betalt</span>' : 
                '<span class="badge bg-warning text-dark">skal betale</span>';
            
            content.innerHTML = `<strong>Neste time:</strong> ${b.service_name || 'ukjent'} om <strong>${timeStr}</strong> &mdash; ${b.customer_last_name || ''}, ${b.customer_first_name || ''} ${paymentBadge} ${priceFormatted}`;
            banner.style.display = 'block';
        } else {
            banner.style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading next booking:', error);
        const banner = document.getElementById('next-booking-schedule-banner');
        if (banner) banner.style.display = 'none';
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
        const primaryStaff = (service.assigned_staff || []).find(s => s.is_primary);
        const otherStaff = (service.assigned_staff || []).filter(s => !s.is_primary);
        const staffBadges = (service.assigned_staff || []).map(s => 
            `<span class="staff-badge ${s.is_primary ? 'primary' : ''}">
                <i class="bi bi-${s.is_primary ? 'star-fill' : 'person-fill'} me-1"></i>${s.name}
            </span>`
        ).join('');
        const capacity = service.capacity || 1;
        const staffCount = service.staff_count || 0;
        const availFrom = (service.available_from || '09:00:00').substring(0,5);
        const availTo = (service.available_to || '17:00:00').substring(0,5);
        
        return `
            <div class="service-card ${service.is_active ? '' : 'inactive'}">
                <div class="d-flex gap-3">
                    ${service.img_url ? `
                        <div class="flex-shrink-0">
                            <img src="${service.img_url}" alt="${service.name}" 
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;"
                                 onerror="this.style.display='none'">
                        </div>
                    ` : ''}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="mb-0 fw-semibold">${service.name}</h6>
                                    ${!service.is_active ? '<span class="badge bg-secondary">Inaktiv</span>' : ''}
                                    ${service.category ? `<span class="badge bg-info">${service.category}</span>` : ''}
                                </div>
                                ${service.description ? `<p class="text-muted mb-1 small">${service.description}</p>` : ''}
                                <div class="service-meta small">
                                    <span><i class="bi bi-clock me-1"></i>${service.duration_minutes || 60} min</span>
                                    <span><i class="bi bi-calendar3 me-1"></i>${availFrom}-${availTo}</span>
                                    <span title="Antall ansatte kreves"><i class="bi bi-person-workspace me-1"></i>${capacity} kreves</span>
                                    <span title="Tilknyttede ansatte"><i class="bi bi-people me-1"></i>${staffCount} tilknyttet</span>
                                </div>
                                ${staffBadges ? `<div class="mt-1">${staffBadges}</div>` : '<div class="mt-1 text-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Ingen ansatte tilknyttet</div>'}
                            </div>
                            <div class="text-end">
                                <div class="service-price">${parseInt(service.price || 0).toLocaleString('nb-NO')} kr</div>
                                <div class="btn-group btn-group-sm mt-2">
                                    <button class="btn btn-outline-primary" onclick="editService('${service.id}')" title="Rediger">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteService('${service.id}', '${service.name.replace(/'/g, "\\'")}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function loadPaymentMethods() {
    try {
        const response = await fetch('/api/services/get_payment_methods.php');
        const result = await response.json();
        if (result.success) {
            allPaymentMethods = result.payment_methods;
            renderPaymentMethodsCheckboxes();
        }
    } catch (error) {
        console.error('Error loading payment methods:', error);
    }
}

function renderPaymentMethodsCheckboxes(selectedMethods = []) {
    const container = document.getElementById('service-payment-methods-container');
    if (!container) return;
    
    if (!allPaymentMethods || allPaymentMethods.length === 0) {
        container.innerHTML = '<div class="text-muted small">Ingen betalingsmetoder konfigurert i Økonomi-modulen</div>';
        return;
    }
    
    container.innerHTML = allPaymentMethods.map(pm => `
        <div class="form-check">
            <input class="form-check-input payment-method-check" type="checkbox" 
                   id="pm-${pm.type}" value="${pm.type}" 
                   data-name="${pm.name}"
                   ${selectedMethods.includes(pm.type) ? 'checked' : ''}>
            <label class="form-check-label" for="pm-${pm.type}">${pm.name}</label>
        </div>
    `).join('');
}

async function openCreateServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'Ny tjeneste';
    document.getElementById('service-form').reset();
    document.getElementById('service-id').value = '';
    document.getElementById('service-active').checked = true;
    document.getElementById('service-capacity').value = 1;
    document.getElementById('service-available-from').value = '09:00';
    document.getElementById('service-available-to').value = '17:00';
    
    await Promise.all([
        allPaymentMethods.length === 0 ? loadPaymentMethods() : Promise.resolve(),
        allStaff.length === 0 ? loadStaff() : Promise.resolve()
    ]);
    
    renderPaymentMethodsCheckboxes([]);
    renderStaffCheckboxes([]);
    updatePrimaryRadios();
    
    new bootstrap.Modal(document.getElementById('createServiceModal')).show();
}

function renderStaffCheckboxes(assignedStaff = []) {
    const container = document.getElementById('service-staff-checkboxes');
    if (!container) return;
    
    if (!allStaff || allStaff.length === 0) {
        container.innerHTML = '<div class="text-muted small">Ingen ansatte registrert. Opprett ansatte i Deres Firma-modulen.</div>';
        return;
    }
    
    const assignedMap = {};
    assignedStaff.forEach(s => {
        assignedMap[s.id] = s;
    });
    
    container.innerHTML = allStaff.map(staff => {
        const assigned = assignedMap[staff.id];
        const isChecked = !!assigned;
        const isPrimary = assigned?.is_primary || false;
        
        return `
            <div class="staff-assignment-row">
                <input class="form-check-input staff-check" type="checkbox" 
                       id="staff-${staff.id}" value="${staff.id}"
                       ${isChecked ? 'checked' : ''}
                       onchange="updatePrimaryRadios()">
                <label class="form-check-label flex-grow-1" for="staff-${staff.id}">
                    ${staff.first_name} ${staff.last_name}
                    ${staff.position ? `<small class="text-muted">(${staff.position})</small>` : ''}
                </label>
                <div class="primary-radio-wrapper" style="${isChecked ? '' : 'display:none'}">
                    <input type="radio" class="form-check-input primary-radio" 
                           name="primary-worker" value="${staff.id}"
                           ${isPrimary ? 'checked' : ''}>
                    <label class="form-check-label small text-muted">Primær</label>
                </div>
            </div>
        `;
    }).join('');
}

function updatePrimaryRadios() {
    document.querySelectorAll('.staff-assignment-row').forEach(row => {
        const checkbox = row.querySelector('.staff-check');
        const radioWrapper = row.querySelector('.primary-radio-wrapper');
        const radio = row.querySelector('.primary-radio');
        
        if (checkbox.checked) {
            radioWrapper.style.display = '';
        } else {
            radioWrapper.style.display = 'none';
            radio.checked = false;
        }
    });
}

async function editService(id) {
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
    document.getElementById('service-capacity').value = service.capacity || 1;
    document.getElementById('service-max-daily').value = service.max_daily_bookings || '';
    document.getElementById('service-img-url').value = service.img_url || '';
    document.getElementById('service-available-from').value = (service.available_from || '09:00:00').substring(0,5);
    document.getElementById('service-available-to').value = (service.available_to || '17:00:00').substring(0,5);
    document.getElementById('service-active').checked = service.is_active == 1;
    
    await Promise.all([
        allPaymentMethods.length === 0 ? loadPaymentMethods() : Promise.resolve(),
        allStaff.length === 0 ? loadStaff() : Promise.resolve()
    ]);
    
    renderPaymentMethodsCheckboxes(service.payment_methods || []);
    renderStaffCheckboxes(service.assigned_staff || []);
    
    new bootstrap.Modal(document.getElementById('createServiceModal')).show();
}

async function saveService() {
    const id = document.getElementById('service-id').value;
    const paymentMethods = [];
    document.querySelectorAll('.payment-method-check:checked').forEach(cb => {
        paymentMethods.push(cb.value);
    });
    
    const staffIds = [];
    document.querySelectorAll('.staff-check:checked').forEach(cb => {
        staffIds.push(cb.value);
    });
    
    const primaryRadio = document.querySelector('.primary-radio:checked');
    const primaryStaffId = primaryRadio ? primaryRadio.value : null;
    
    const data = {
        name: document.getElementById('service-name').value,
        description: document.getElementById('service-description').value,
        price: parseFloat(document.getElementById('service-price').value) || 0,
        duration_minutes: parseInt(document.getElementById('service-duration').value) || 60,
        buffer_before_minutes: parseInt(document.getElementById('service-buffer-before').value) || 0,
        buffer_after_minutes: parseInt(document.getElementById('service-buffer-after').value) || 0,
        category: document.getElementById('service-category').value || null,
        capacity: parseInt(document.getElementById('service-capacity').value) || 1,
        max_daily_bookings: parseInt(document.getElementById('service-max-daily').value) || null,
        img_url: document.getElementById('service-img-url').value || null,
        available_from: document.getElementById('service-available-from').value || '09:00',
        available_to: document.getElementById('service-available-to').value || '17:00',
        is_active: document.getElementById('service-active').checked,
        payment_methods: paymentMethods,
        staff_ids: staffIds,
        primary_staff_id: primaryStaffId
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
                            ${ss.worker_primary ? '<span class="badge bg-success me-1">Primærarbeider</span>' : ''}
                            ${ss.max_daily_bookings ? 'Maks ' + ss.max_daily_bookings + '/dag' : ''}
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
    
    document.getElementById('add-staff-max').value = 5;
    document.getElementById('add-staff-primary').checked = false;
    
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
                worker_primary: document.getElementById('add-staff-primary').checked ? 1 : 0,
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
        } else if (target === 'reservations-schedule') {
            initScheduleFilters();
            loadSchedule();
            loadNextBooking();
        }
    });
});

function initScheduleFilters() {
    const dateInput = document.getElementById('schedule-date');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    const staffSelect = document.getElementById('schedule-staff-filter');
    if (staffSelect && staffSelect.options.length <= 1 && allStaff.length > 0) {
        allStaff.forEach(staff => {
            const option = document.createElement('option');
            option.value = staff.id;
            option.textContent = `${staff.first_name} ${staff.last_name}`;
            staffSelect.appendChild(option);
        });
    }
    
    const serviceSelect = document.getElementById('schedule-service-filter');
    if (serviceSelect && serviceSelect.options.length <= 1 && allServices.length > 0) {
        allServices.forEach(service => {
            const option = document.createElement('option');
            option.value = service.id;
            option.textContent = service.name;
            serviceSelect.appendChild(option);
        });
    }
}

async function loadSchedule() {
    const dateInput = document.getElementById('schedule-date');
    const staffFilter = document.getElementById('schedule-staff-filter');
    const serviceFilter = document.getElementById('schedule-service-filter');
    
    const date = dateInput?.value || new Date().toISOString().split('T')[0];
    const staffId = staffFilter?.value || '';
    const serviceId = serviceFilter?.value || '';
    
    const dateTitle = document.getElementById('schedule-date-title');
    const dateObj = new Date(date);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateTitle.textContent = dateObj.toLocaleDateString('nb-NO', options);
    
    try {
        const params = new URLSearchParams({ date });
        if (staffId) params.append('staff_id', staffId);
        if (serviceId) params.append('service_id', serviceId);
        
        const response = await fetch(`/api/services/get_schedule.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            renderScheduleTable(result.bookings || []);
            document.getElementById('schedule-booking-count').textContent = 
                result.bookings?.length || 0;
        } else {
            console.error('Error loading schedule:', result.message);
        }
    } catch (error) {
        console.error('Error loading schedule:', error);
    }
}

function renderScheduleTable(bookings) {
    const tbody = document.getElementById('schedule-customers-table');
    
    if (!bookings || bookings.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    Ingen bookinger for valgt dato
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = bookings.map(b => {
        const statusBadge = {
            'confirmed': '<span class="badge bg-success">Bekreftet</span>',
            'pending': '<span class="badge bg-warning">Venter</span>',
            'cancelled': '<span class="badge bg-danger">Kansellert</span>',
            'completed': '<span class="badge bg-info">Fullført</span>'
        }[b.status] || '<span class="badge bg-secondary">Ukjent</span>';
        
        return `
            <tr>
                <td><strong>${b.start_time?.substring(0, 5) || '--:--'}</strong></td>
                <td>${b.customer_name || 'Ukjent kunde'}</td>
                <td>${b.service_name || '-'}</td>
                <td>${b.staff_name || '-'}</td>
                <td>${statusBadge}</td>
                <td>
                    ${b.customer_phone ? `<a href="tel:${b.customer_phone}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-telephone"></i></a>` : ''}
                    ${b.customer_email ? `<a href="mailto:${b.customer_email}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope"></i></a>` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

let carouselBookings = [];
let carouselIndex = 0;
let carouselTouchStart = { x: 0, y: 0 };
let carouselTouchEnd = { x: 0, y: 0 };
let countdownInterval = null;
const SWIPE_THRESHOLD = 50;
const ORTHOGONAL_THRESHOLD = 15;

async function loadCarouselBookings() {
    try {
        const response = await fetch('/api/services/get_upcoming_bookings.php');
        const result = await response.json();
        
        if (result.success && result.bookings.length > 0) {
            carouselBookings = result.bookings;
            carouselIndex = 0;
            document.getElementById('carousel-total').textContent = carouselBookings.length;
            document.getElementById('carousel-main').style.display = 'block';
            document.getElementById('carousel-empty').style.display = 'none';
            renderCurrentBooking();
            startCountdown();
        } else {
            carouselBookings = [];
            document.getElementById('carousel-main').style.display = 'none';
            document.getElementById('carousel-empty').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading carousel bookings:', error);
    }
}

function renderCurrentBooking() {
    if (carouselBookings.length === 0) return;
    
    const b = carouselBookings[carouselIndex];
    document.getElementById('carousel-current').textContent = carouselIndex + 1;
    document.getElementById('carousel-service').textContent = b.service_name || 'Ukjent tjeneste';
    document.getElementById('carousel-customer').innerHTML = `<i class="bi bi-person me-1"></i>${b.customer_last_name || ''}, ${b.customer_first_name || ''}`;
    document.getElementById('carousel-staff').innerHTML = `<i class="bi bi-scissors me-1"></i>${b.staff_name || 'Ikke tildelt'}`;
    document.getElementById('carousel-price').textContent = b.price ? `${parseFloat(b.price).toFixed(0)} kr` : '0 kr';
    
    const dateFormatted = new Date(b.booking_date).toLocaleDateString('nb-NO', { day: 'numeric', month: 'short' });
    const timeFormatted = b.start_time?.substring(0, 5) || '--:--';
    document.getElementById('carousel-datetime').innerHTML = `<i class="bi bi-calendar me-1"></i>${dateFormatted} kl ${timeFormatted}`;
    
    const paymentBadge = b.is_paid ? 
        '<span class="badge bg-success">Betalt</span>' : 
        '<span class="badge bg-warning text-dark">Skal betale</span>';
    document.getElementById('carousel-payment').innerHTML = paymentBadge;
    
    updateCountdownDisplay();
}

function updateCountdownDisplay() {
    if (carouselBookings.length === 0) return;
    
    const b = carouselBookings[carouselIndex];
    
    if (!b.booking_date || !b.start_time) {
        document.getElementById('countdown-display').textContent = '--:--';
        return;
    }
    
    const dateTimeStr = b.booking_date + 'T' + b.start_time;
    const bookingDateTime = new Date(dateTimeStr);
    
    if (isNaN(bookingDateTime.getTime())) {
        document.getElementById('countdown-display').textContent = '--:--';
        return;
    }
    
    const now = new Date();
    const diff = bookingDateTime - now;
    
    if (diff <= 0) {
        document.getElementById('countdown-display').textContent = 'Nå';
        return;
    }
    
    const totalMinutes = Math.floor(diff / 60000);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    
    if (hours > 0) {
        document.getElementById('countdown-display').textContent = `${hours}t ${minutes}m`;
    } else {
        document.getElementById('countdown-display').textContent = `${minutes} min`;
    }
}

function startCountdown() {
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(updateCountdownDisplay, 30000);
}

function initCarouselSwipe() {
    const card = document.getElementById('booking-carousel');
    if (!card) return;
    
    card.addEventListener('touchstart', handleTouchStart, { passive: true });
    card.addEventListener('touchmove', handleTouchMove, { passive: true });
    card.addEventListener('touchend', handleTouchEnd);
    
    card.addEventListener('mousedown', handleMouseDown);
    card.addEventListener('mousemove', handleMouseMove);
    card.addEventListener('mouseup', handleMouseUp);
    card.addEventListener('mouseleave', handleMouseUp);
}

function handleTouchStart(e) {
    carouselTouchStart.x = e.touches[0].clientX;
    carouselTouchStart.y = e.touches[0].clientY;
}

function handleTouchMove(e) {
    carouselTouchEnd.x = e.touches[0].clientX;
    carouselTouchEnd.y = e.touches[0].clientY;
    
    const dx = carouselTouchEnd.x - carouselTouchStart.x;
    const dy = carouselTouchEnd.y - carouselTouchStart.y;
    
    updateSwipeVisual(dx, dy);
}

function handleTouchEnd(e) {
    processSwipe();
    resetSwipeVisual();
}

let isMouseDown = false;
function handleMouseDown(e) {
    isMouseDown = true;
    carouselTouchStart.x = e.clientX;
    carouselTouchStart.y = e.clientY;
}

function handleMouseMove(e) {
    if (!isMouseDown) return;
    carouselTouchEnd.x = e.clientX;
    carouselTouchEnd.y = e.clientY;
    
    const dx = carouselTouchEnd.x - carouselTouchStart.x;
    const dy = carouselTouchEnd.y - carouselTouchStart.y;
    
    updateSwipeVisual(dx, dy);
}

function handleMouseUp(e) {
    if (!isMouseDown) return;
    isMouseDown = false;
    processSwipe();
    resetSwipeVisual();
}

function updateSwipeVisual(dx, dy) {
    const card = document.getElementById('booking-carousel');
    card.classList.remove('swiping-left', 'swiping-right', 'swiping-up', 'swiping-down');
    
    const absDx = Math.abs(dx);
    const absDy = Math.abs(dy);
    
    if (absDx > SWIPE_THRESHOLD && absDy < ORTHOGONAL_THRESHOLD) {
        card.classList.add(dx < 0 ? 'swiping-left' : 'swiping-right');
    } else if (absDy > SWIPE_THRESHOLD && absDx < ORTHOGONAL_THRESHOLD) {
        card.classList.add(dy < 0 ? 'swiping-up' : 'swiping-down');
    }
}

function resetSwipeVisual() {
    const card = document.getElementById('booking-carousel');
    card.classList.remove('swiping-left', 'swiping-right', 'swiping-up', 'swiping-down');
}

function processSwipe() {
    const dx = carouselTouchEnd.x - carouselTouchStart.x;
    const dy = carouselTouchEnd.y - carouselTouchStart.y;
    const absDx = Math.abs(dx);
    const absDy = Math.abs(dy);
    
    if (absDx > SWIPE_THRESHOLD && absDy < ORTHOGONAL_THRESHOLD) {
        if (dx < 0) {
            navigateCarousel(1);
        } else {
            navigateCarousel(-1);
        }
    } else if (absDy > SWIPE_THRESHOLD && absDx < ORTHOGONAL_THRESHOLD) {
        if (dy > 0) {
            showCancelConfirm();
        } else {
            openEditPanel();
        }
    }
    
    carouselTouchStart = { x: 0, y: 0 };
    carouselTouchEnd = { x: 0, y: 0 };
}

function navigateCarousel(direction) {
    if (carouselBookings.length === 0) return;
    
    carouselIndex += direction;
    
    if (carouselIndex >= carouselBookings.length) {
        carouselIndex = 0;
    } else if (carouselIndex < 0) {
        carouselIndex = carouselBookings.length - 1;
    }
    
    renderCurrentBooking();
}

function showCancelConfirm() {
    if (carouselBookings.length === 0) return;
    
    const b = carouselBookings[carouselIndex];
    const text = `Avbestille time for ${b.customer_first_name} ${b.customer_last_name}?`;
    document.getElementById('cancel-confirm-text').textContent = text;
    
    new bootstrap.Modal(document.getElementById('cancelConfirmModal')).show();
}

async function confirmCancelBooking() {
    if (carouselBookings.length === 0) return;
    
    const b = carouselBookings[carouselIndex];
    
    try {
        const response = await fetch('/api/services/cancel_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: b.id })
        });
        
        const result = await response.json();
        
        bootstrap.Modal.getInstance(document.getElementById('cancelConfirmModal')).hide();
        
        if (result.success) {
            showToast('Booking avbestilt', 'success');
            await loadCarouselBookings();
        } else {
            showToast('Feil: ' + result.message, 'danger');
        }
    } catch (error) {
        console.error('Error cancelling booking:', error);
        showToast('Feil ved avbestilling', 'danger');
    }
}

async function openEditPanel() {
    if (carouselBookings.length === 0) return;
    
    const b = carouselBookings[carouselIndex];
    const panel = document.getElementById('carousel-edit-panel');
    
    document.getElementById('edit-service-name').textContent = b.service_name || 'Ukjent';
    document.getElementById('edit-customer-name').textContent = `${b.customer_first_name || ''} ${b.customer_last_name || ''}`;
    document.getElementById('edit-price').textContent = b.price ? `${parseFloat(b.price).toFixed(0)} kr` : '0 kr';
    
    document.getElementById('edit-new-date').value = b.booking_date;
    
    const timeSelect = document.getElementById('edit-new-time');
    const currentTime = b.start_time?.substring(0, 5) || '09:00';
    for (let option of timeSelect.options) {
        if (option.value === currentTime) {
            option.selected = true;
            break;
        }
    }
    
    try {
        const response = await fetch(`/api/services/get_staff_for_service.php?service_id=${b.service_id}`);
        const result = await response.json();
        
        const staffSelect = document.getElementById('edit-staff-select');
        staffSelect.innerHTML = '';
        
        if (result.success && result.staff.length > 0) {
            result.staff.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = s.name + (s.is_primary ? ' (primær)' : '');
                if (s.id === b.staff_id) option.selected = true;
                staffSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading staff:', error);
    }
    
    panel.style.display = 'block';
    setTimeout(() => panel.classList.add('show'), 10);
}

function closeEditPanel() {
    const panel = document.getElementById('carousel-edit-panel');
    panel.classList.remove('show');
    setTimeout(() => panel.style.display = 'none', 300);
}

async function saveReschedule() {
    if (carouselBookings.length === 0) return;
    
    const b = carouselBookings[carouselIndex];
    const newDate = document.getElementById('edit-new-date').value;
    const newTime = document.getElementById('edit-new-time').value;
    const newStaffId = document.getElementById('edit-staff-select').value;
    
    try {
        const response = await fetch('/api/services/reschedule_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: b.id,
                new_date: newDate,
                new_time: newTime,
                new_staff_id: newStaffId || null
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeEditPanel();
            await loadCarouselBookings();
        } else {
            showToast('Feil: ' + result.message, 'danger');
        }
    } catch (error) {
        console.error('Error rescheduling:', error);
        showToast('Feil ved flytting av time', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const scheduleTab = document.querySelector('[href="#reservations-schedule"]');
    if (scheduleTab) {
        scheduleTab.addEventListener('click', function() {
            setTimeout(() => {
                loadCarouselBookings();
                initCarouselSwipe();
            }, 100);
        });
    }
});
</script>
