<?php
// Note: $db is available from parent context (company_dashboard.php)
// Fetch company payment settings
$companyData = [];
try {
    if (isset($_SESSION['company_id']) && isset($db)) {
        $stmt = $db->prepare("SELECT Cash_payment FROM tenant_companies WHERE id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        $companyData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching company data: " . $e->getMessage());
}
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="/assets/entity-form-fields.css">
<link rel="stylesheet" href="/assets/events-dashboard.css">
<script src="/assets/active-payment-methods.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.event-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: white;
    height: 100%;
    position: relative;
}

.event-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.event-card.event-card-inactive {
    opacity: 0.62;
    filter: grayscale(70%);
    background: #f8f9fa;
}

.event-card.event-card-inactive:hover {
    opacity: 0.82;
    filter: grayscale(45%);
}

.event-status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
}

.event-image {
    height: 180px;
    position: relative;
    width: 100%;
    object-fit: cover;
    display: block;
}

.event-image-shell {
    height: 180px;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.event-image-shell .event-image {
    position: absolute;
    inset: 0;
    z-index: 1;
}

.event-image-shell > i {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    opacity: .7;
}

.event-image-placeholder {
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.event-image-placeholder i {
    font-size: 3rem;
    color: white;
    opacity: 0.7;
}

.event-badge-overlay {
    position: absolute;
    top: 12px;
    right: 12px;
}

.event-body {
    padding: 1.5rem;
}

.event-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.event-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 1rem 0;
}

.event-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.event-meta-item i {
    width: 20px;
    color: #667eea;
}

.event-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.event-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
}

.event-price-free {
    color: #10b981;
}

.event-actions {
    display: flex;
    gap: 0.5rem;
}

.event-results-collapse {
    border-top: 2px solid #667eea;
    background: #f8f9fa;
}

.event-results-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.event-results-body {
    padding: 1.5rem;
}

.event-finance-list {
    background: white;
    border-radius: 8px;
    padding: 0.75rem;
    min-height: 60px;
}

.event-finance-item {
    display: flex;
    justify-content-between;
    align-items-center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.event-finance-item:last-child {
    border-bottom: none;
}

.event-results-summary {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.loading-events {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-events {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-events i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

/* ========== CHECK-IN FULLSCREEN STYLES ========== */
#fullscreen-checkin-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    z-index: 9999;
    overflow-y: auto;
    display: none;
}

#fullscreen-checkin-overlay.active {
    display: block;
}

.fullscreen-checkin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}

.fullscreen-checkin-header h4 {
    margin: 0;
    font-weight: 600;
}

/* Enhanced Event Cards for Check-in Selector */
.checkin-event-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.checkin-event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    border-color: #667eea;
}

.checkin-event-card .card-img-top {
    height: 120px;
    object-fit: cover;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.checkin-event-card .card-body {
    padding: 1.25rem;
}

.checkin-event-card .event-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.checkin-event-card .event-date {
    font-size: 0.85rem;
    color: #6b7280;
}

.checkin-event-card .event-stats {
    display: flex;
    gap: 1rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f3f4f6;
}

.checkin-event-card .stat-item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    color: #6b7280;
}

.checkin-event-card .stat-item i {
    color: #667eea;
}

/* Scanner Card Styling */
.checkin-scanner-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.checkin-scanner-card .form-control {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    font-size: 1.1rem;
    background: rgba(255,255,255,0.95);
}

.checkin-scanner-card .form-control:focus {
    box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
}

.checkin-scanner-card .form-control::placeholder {
    color: #9ca3af;
}

/* Stats Cards */
.checkin-stat-card {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
}

.checkin-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.checkin-stat-card .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.2;
}

.checkin-stat-card .stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.25rem;
}

.checkin-stat-card.stat-success {
    border-left: 4px solid #10b981;
}

.checkin-stat-card.stat-warning {
    border-left: 4px solid #f59e0b;
}

.checkin-stat-card.stat-secondary {
    border-left: 4px solid #6b7280;
}

.checkin-stat-card.stat-primary {
    border-left: 4px solid #667eea;
}

.checkin-stat-card.stat-revenue {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.checkin-stat-card.stat-revenue .stat-label {
    color: rgba(255,255,255,0.8);
}

.checkin-stat-card.stat-expected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.checkin-stat-card.stat-expected .stat-label {
    color: rgba(255,255,255,0.8);
}

/* Attendee Lists */
.checkin-list-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.checkin-list-card .card-header {
    padding: 1rem 1.5rem;
    font-weight: 600;
    border: none;
}

.checkin-list-card .card-header.bg-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
}

.checkin-list-card .card-header.bg-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: white !important;
}

.checkin-list-card .table {
    margin-bottom: 0;
}

.checkin-list-card .table thead th {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    padding: 0.875rem 1rem;
}

.checkin-list-card .table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.checkin-list-card .table tbody tr:hover {
    background: #f9fafb;
}

.checkin-list-card .table tbody tr:last-child td {
    border-bottom: none;
}

/* Attendee Row Styling */
.attendee-name {
    font-weight: 600;
    color: #1f2937;
}

.attendee-email {
    font-size: 0.8rem;
    color: #6b7280;
}

.ticket-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.ticket-badge.member {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.ticket-badge.regular {
    background: #e5e7eb;
    color: #374151;
}

.price-display {
    font-weight: 600;
    color: #10b981;
}

/* Action Buttons */
.checkin-action-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.checkin-action-btn.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
}

.checkin-action-btn.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.checkin-action-btn.btn-outline-secondary:hover {
    background: #374151;
    border-color: #374151;
}

/* Search Results */
.search-result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 10px;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
}

.search-result-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.search-result-item.match-found {
    border-left: 4px solid #10b981;
}

/* Guest Registration Card */
.guest-registration-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.guest-registration-card .card-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    padding: 1rem 1.25rem;
}

/* Fullscreen Specific Styles */
.fullscreen-scanner-container {
    max-width: 800px;
    margin: 0 auto 2rem;
}

.fullscreen-stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 1200px) {
    .fullscreen-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .fullscreen-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.fullscreen-lists-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

@media (max-width: 992px) {
    .fullscreen-lists-container {
        grid-template-columns: 1fr;
    }
}

/* Empty State */
.checkin-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #9ca3af;
}

.checkin-empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Animations */
@keyframes pulse-success {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
}

.checkin-success-pulse {
    animation: pulse-success 0.6s ease-out;
}

/* Back Button */
.checkin-back-btn {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s ease;
}

.checkin-back-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
}

/* Event Info Header */
.event-info-header {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.event-info-header .event-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.event-info-header .event-details h5 {
    margin: 0 0 0.25rem;
    font-weight: 600;
    color: #1f2937;
}

.event-info-header .event-details p {
    margin: 0;
    color: #6b7280;
    font-size: 0.9rem;
}

/* ========== EVENT WIZARD STYLES ========== */
.wizard-progress {
    padding: 0 1rem;
}

.wizard-progress .progress-line {
    position: absolute;
    top: 15px;
    left: 40px;
    right: 40px;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 1;
    background: white;
    padding: 0 8px;
}

.wizard-step .step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.wizard-step.active .step-circle {
    background: #667eea;
    color: white;
}

.wizard-step.completed .step-circle {
    background: #10b981;
    color: white;
}

.wizard-step .step-label {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.wizard-step.active .step-label {
    color: #667eea;
    font-weight: 600;
}

.wizard-step.completed .step-label {
    color: #10b981;
}
#edit-tab-7, #edit-panel-7 { display: none !important; }
#registration-fields-section > :not(#create-event-fields-editor) { display: none !important; }

.wizard-option-card {
    cursor: pointer;
    display: block;
}

.wizard-option-card .option-content {
    transition: all 0.2s ease;
    background: white;
}

.wizard-option-card:hover .option-content {
    border-color: #667eea !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.wizard-option-card input:checked + .option-content {
    border-color: #667eea !important;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.wizard-option-card input:checked + .option-content i {
    transform: scale(1.1);
}

.wizard-content {
    min-height: 400px;
}

@media (max-width: 768px) {
    .wizard-step .step-label {
        display: none;
    }
    
    .wizard-step .step-circle {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    
    .wizard-progress .progress-line {
        left: 20px;
        right: 20px;
    }
}
</style>
<div class="module-content" id="events-module" data-membership-access="<?php echo !empty($hasMembershipAccess) ? '1' : '0'; ?>">
    <div id="events-dashboard" class="subtab-content">
        <div class="events-dashboard-head">
            <div><span class="events-dashboard-kicker">Arrangementer</span>
                <h2 class="events-dashboard-title">Planlegg det som samler folk.</h2>
                <p class="events-dashboard-intro">En tydelig oversikt over det som kommer, og det som allerede har skjedd.</p></div>
            <div class="events-dashboard-head-actions">
                <button type="button" class="btn btn-sm btn-outline-success" data-open-overview="requests"><i class="bi bi-inbox me-1"></i>Påmeldinger</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-open-overview="checkin"><i class="bi bi-qr-code-scan me-1"></i>Innsjekking</button>
                <button type="button" class="btn events-dashboard-create" data-event-create><i class="bi bi-plus-lg me-2"></i>Nytt arrangement</button>
            </div>
        </div>
        <div id="eventsDashboardSummary" aria-live="polite"></div>
        <div class="events-dashboard-toolbar">
            <label class="events-dashboard-search"><i class="bi bi-search" aria-hidden="true"></i>
                <span class="visually-hidden">Søk etter arrangement</span>
                <input id="eventsDashboardSearch" type="search" class="form-control" placeholder="Søk etter navn, beskrivelse eller sted" autocomplete="off">
            </label>
            <label class="visually-hidden" for="eventsDashboardStatus">Visning</label>
            <select id="eventsDashboardStatus" class="form-select events-dashboard-filter">
                <option value="active">Synlige</option><option value="hidden">Skjulte</option><option value="past">Avsluttede</option><option value="all">Alle</option>
            </select>
        </div>
        <p id="eventsDashboardNotice" class="events-dashboard-status" role="status" aria-live="polite">Arrangementene lastes…</p>
        <div id="eventsDashboardList"><div class="events-dashboard-skeleton"><span></span><span></span><span></span></div></div>
    </div>

    <div id="events-my" class="subtab-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="bi bi-calendar-event me-2"></i>Dine arrangement</h4>
                <p class="text-muted mb-0">Oversikt over alle dine arrangementer 
                    <a href="#" onclick="document.querySelector('[href=\'#events-registrations\']').click(); return false;" style="text-decoration: none;">
                        <span class="badge bg-danger ms-1" id="pending-count-badge" data-event-pending-count style="display: none;">0 venter godkjenning</span>
                    </a>
                </p>
            </div>
            <button class="btn btn-primary" onclick="document.querySelector('[href=\'#events-create\']').click()">
                <i class="bi bi-plus-circle me-2"></i>Opprett nytt
            </button>
        </div>
        
        <div id="events-list" class="row g-4">
            <div class="col-12">
                <div class="loading-events">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="mb-0">Laster arrangementer...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="events-create" class="subtab-content" style="display: none;">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Wizard Progress Indicator -->
                <div class="wizard-progress mb-4">
                    <div class="d-flex justify-content-between align-items-center position-relative">
                        <div class="progress-line"></div>
                        <div class="wizard-step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Type</div>
                        </div>
                        <div class="wizard-step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Grunninfo</div>
                        </div>
                        <div class="wizard-step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Sted</div>
                        </div>
                        <div class="wizard-step" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Priser</div>
                        </div>
                        <?php if (!empty($hasMembershipAccess)): ?>
                        <div class="wizard-step" data-step="5">
                            <div class="step-circle">5</div>
                            <div class="step-label">Medlemskap</div>
                        </div>
                        <?php endif; ?>
                        <div class="wizard-step" data-step="6">
                            <div class="step-circle">6</div>
                            <div class="step-label">Betaling</div>
                        </div>
                        <div class="wizard-step" data-step="7">
                            <div class="step-circle">7</div>
                            <div class="step-label">Oppsummering</div>
                        </div>
                    </div>
                </div>

                <form id="create-event-form" data-membership-access="<?= !empty($hasMembershipAccess) ? '1' : '0' ?>">
                    <!-- Step 1: Registration Type -->
                    <div class="wizard-content" id="wizard-step-1">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Hvilket arrangement vil du opprette?</h4>
                            <p class="text-muted">Velg hvordan deltakere registrerer seg</p>
                        </div>
                        
                        <div class="row justify-content-center g-3">
                            <div class="col-md-4">
                                <label class="wizard-option-card">
                                    <input type="radio" name="registration_type" value="signup" checked class="d-none">
                                    <div class="option-content p-4 text-center rounded-3 border">
                                        <i class="bi bi-person-plus display-4 text-primary mb-3"></i>
                                        <h5>Påmelding</h5>
                                        <p class="text-muted small mb-0">Deltakere melder seg på og betaler senere</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="wizard-option-card">
                                    <input type="radio" name="registration_type" value="none" class="d-none">
                                    <div class="option-content p-4 text-center rounded-3 border">
                                        <i class="bi bi-door-open display-4 text-secondary mb-3"></i>
                                        <h5>Uten påmelding</h5>
                                        <p class="text-muted small mb-0">Fritt arrangement, ingen registrering</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="wizard-option-card">
                                    <input type="radio" name="registration_type" value="binding" class="d-none">
                                    <div class="option-content p-4 text-center rounded-3 border">
                                        <i class="bi bi-lock display-4 text-warning mb-3"></i>
                                        <h5>Bindende</h5>
                                        <p class="text-muted small mb-0">Krever betaling ved påmelding</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div id="binding-payment-container" class="mt-4" style="display: none;">
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="card bg-warning bg-opacity-10 border-warning">
                                        <div class="card-body">
                                            <h6 class="mb-3"><i class="bi bi-credit-card me-2"></i>Velg betalingsmetode for bindende påmelding</h6>
                                            <p class="text-muted small mb-3">Kontant betaling er ikke tilgjengelig for bindende arrangementer.</p>
                                            <div id="binding-payment-methods">
                                                <div class="text-center py-2">
                                                    <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                                                    <small class="text-muted">Laster betalingsmetoder...</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Basic Info -->
                    <div class="wizard-content" id="wizard-step-2" style="display: none;">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Grunnleggende informasjon</h4>
                            <p class="text-muted">Navn, beskrivelse og deltakerantall</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Arrangementsnavn *</label>
                                    <input type="text" class="form-control form-control-lg" name="title" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Beskrivelse</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Arrangementsbilde (URL)</label>
                                    <input type="url" class="form-control" name="event_image_url">
                                    <small class="text-muted">Valgfritt. Vises på arrangementkortet.</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Min. deltakere</label>
                                        <input type="number" class="form-control" name="min_attendees" id="min_attendees" min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maks. deltakere</label>
                                        <input type="number" class="form-control" name="max_attendees" min="0">
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="registration_deadline_container" style="display: none;">
                                    <label class="form-label">Frist for min. deltakere *</label>
                                    <input type="text" class="form-control" name="registration_deadline" id="registration_deadline"
                                           aria-label="Fristdato"
                                           inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                                           placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                                    <small class="text-muted">Må være før startdato</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Startdato *</label>
                                        <input type="text" class="form-control" name="start_date" required
                                               aria-label="Startdato"
                                               inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                                               placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Starttid *</label>
                                        <input type="time" class="form-control" name="start_time" required step="60"
                                               aria-label="Starttid"
                                               pattern="[0-9]{2}:[0-9]{2}" inputmode="numeric" placeholder="HH:mm" lang="nb-NO">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sluttdato</label>
                                        <input type="text" class="form-control" name="end_date"
                                               aria-label="Sluttdato"
                                               inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                                               placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sluttid</label>
                                        <input type="time" class="form-control" name="end_time" step="60"
                                               aria-label="Sluttid"
                                               pattern="[0-9]{2}:[0-9]{2}" inputmode="numeric" placeholder="HH:mm" lang="nb-NO">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Aldersgrense</label>
                                    <input type="number" class="form-control" name="age_limit" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Location -->
                    <div class="wizard-content" id="wizard-step-3" style="display: none;">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Hvor finner arrangementet sted?</h4>
                            <p class="text-muted">Angi lokasjon for arrangementet</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div id="venue-fields">
                                    <div class="mb-3">
                                        <label class="form-label">Gateadresse</label>
                                        <input type="text" class="form-control address-field" name="event_address">
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="has_venue_name">
                                        <label class="form-check-label small" for="has_venue_name">
                                            Legg til eget stedsnavn (f.eks. Kulturhuset, Konsertlokale)
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3" id="venue-name-field" style="display: none;">
                                        <label class="form-label">Stedsnavn</label>
                                        <input type="text" class="form-control" name="venue_name">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Postnummer</label>
                                            <input type="text" class="form-control zip-field" name="event_postal" maxlength="4">
                                        </div>
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">By</label>
                                            <input type="text" class="form-control city-field" name="event_city">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">
                                            <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                                            Kartvisning
                                        </label>
                                        <div id="wizard-map-container" class="border rounded" style="height: 250px; background: #f8f9fa;">
                                            <div id="wizard-map" style="height: 100%; width: 100%;"></div>
                                        </div>
                                        <small class="text-muted mt-1 d-block">
                                            <i class="bi bi-info-circle me-1"></i>Kartet oppdateres når du fyller ut adressefeltene
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Pricing -->
                    <div class="wizard-content" id="wizard-step-4" style="display: none;">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Hva koster billettene?</h4>
                            <p class="text-muted">Sett ordinær billettpris</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label class="form-label">Ordinær billettpris (NOK)</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg" name="ticket_price" id="ticket_price">
                                    <small class="text-muted" id="ticket-price-help">Sett til 0 for gratis arrangement</small>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_public_pricing" name="is_public" checked>
                                    <label class="form-check-label" for="is_public_pricing">
                                        <i class="bi bi-globe text-primary me-1"></i> Vis priser offentlig
                                    </label>
                                    <small class="text-muted d-block">Priser vises på arrangementsiden</small>
                                </div>
                                
                                <hr class="my-4">
                                
                                <?php if (!empty($hasMembershipAccess)): ?>
                                <h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Medlemspriser</h6>
                                <div id="membership-pricing-container">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        <small class="text-muted">Laster medlemsplaner...</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Membership Add-ons -->
                    <?php if (!empty($hasMembershipAccess)): ?>
                    <div class="wizard-content" id="wizard-step-5" style="display: none;">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Medlemstillegg</h4>
                            <p class="text-muted">Spesielle innstillinger for medlemmer</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card border-0 bg-light mb-3">
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="memberverification" id="memberverification">
                                            <label class="form-check-label" for="memberverification">
                                                <i class="bi bi-shield-check text-success me-2"></i>
                                                <strong>Autofyll og autogodkjenning</strong>
                                            </label>
                                            <small class="text-muted d-block ms-4">Medlemmer får automatisk utfylt informasjon og godkjennes umiddelbart</small>
                                        </div>
                                        
                                        <div class="form-check" id="members-only-container">
                                            <input class="form-check-input" type="checkbox" name="is_member_exclusive" id="memberExclusive">
                                            <label class="form-check-label" for="memberExclusive">
                                                <i class="bi bi-lock-fill text-warning me-2"></i>
                                                <strong>Kun for medlemmer</strong>
                                            </label>
                                            <small class="text-muted d-block ms-4">Bare medlemmer kan melde seg på dette arrangementet</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Disse valgene vises bare for arrangementer med påmelding.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Payment Methods -->
                    <?php endif; ?>
                    <div class="wizard-content" id="wizard-step-6" style="display: none;">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Betalingsmetoder</h4>
                            <p class="text-muted">Velg hvordan deltakere kan betale</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div id="payment-methods-container" class="mb-4">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        <small class="text-muted">Laster betalingsmetoder...</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Standard betalingsmetode</label>
                                    <select class="form-select" name="default_payment_method" id="default-payment-method">
                                        <option value="">Velg standardmetode...</option>
                                    </select>
                                    <small class="text-muted">Denne metoden vises som anbefalt ved betaling</small>
                                </div>
                                
                                <p class="text-muted small"><strong>Status:</strong> <span id="payment-timing-status">Velg betalingsmetoder ovenfor</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 7: Summary -->
                    <div class="wizard-content" id="wizard-step-7" style="display: none;">
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Oppsummering</h4>
                            <p class="text-muted">Se over og bekreft arrangementet</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <!-- Summary Card -->
                                <div class="card border-0 shadow-sm mb-4" id="wizard-summary-card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 id="summary-title" class="mb-2">-</h5>
                                                <p class="text-muted mb-2" id="summary-description">-</p>
                                                <div class="d-flex gap-3 flex-wrap mb-3">
                                                    <span class="badge bg-primary" id="summary-type">-</span>
                                                    <span class="text-muted"><i class="bi bi-calendar me-1"></i><span id="summary-date">-</span></span>
                                                    <span class="text-muted"><i class="bi bi-geo-alt me-1"></i><span id="summary-location">-</span></span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Pris:</strong> <span id="summary-price">-</span>
                                                </div>
                                                <div>
                                                    <strong>Deltakere:</strong> <span id="summary-attendees">-</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div id="summary-image" class="rounded bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                                    <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Registration Fields -->
                                <div id="registration-fields-section">
                                    <div id="create-event-fields-editor"></div>
                                    <h6 class="mb-3"><i class="bi bi-ui-checks me-2"></i>Påmeldingsfelt</h6>
                                    
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Påkrevde felt:</strong> Fornavn og Etternavn er alltid påkrevd.
                                    </div>
                                    
                                    <div class="alert alert-warning mb-3">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Kontaktinformasjon (minst én påkrevd):</strong><br>
                                        <small>Velg E-post og/eller Telefonnummer.</small>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input contact-field-required" type="checkbox" name="field_email" id="field_email" checked>
                                                <label class="form-check-label" for="field_email">
                                                    <i class="bi bi-envelope text-primary me-1"></i> E-post
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input contact-field-required" type="checkbox" name="field_phone" id="field_phone" checked>
                                                <label class="form-check-label" for="field_phone">
                                                    <i class="bi bi-telephone text-primary me-1"></i> Telefon
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="field_street" id="field_street">
                                                <label class="form-check-label" for="field_street">
                                                    <i class="bi bi-house text-primary me-1"></i> Gateadresse
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="field_zip" id="field_zip">
                                                <label class="form-check-label" for="field_zip">
                                                    <i class="bi bi-mailbox text-primary me-1"></i> Postnummer
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="field_city" id="field_city">
                                                <label class="form-check-label" for="field_city">
                                                    <i class="bi bi-geo-alt text-primary me-1"></i> Poststed
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="field_age" id="field_age">
                                                <label class="form-check-label" for="field_age">
                                                    <i class="bi bi-person-badge text-primary me-1"></i> Alder
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="wizard-navigation d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                        <button type="button" class="btn btn-outline-secondary" id="wizard-prev" style="display: none;">
                            <i class="bi bi-chevron-left me-1"></i>Tilbake
                        </button>
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="wizard-reset">
                                <i class="bi bi-x-circle me-1"></i>Nullstill
                            </button>
                            <button type="button" class="btn btn-primary" id="wizard-next">
                                Neste<i class="bi bi-chevron-right ms-1"></i>
                            </button>
                            <button type="submit" class="btn btn-success" id="wizard-submit" style="display: none;">
                                <i class="bi bi-check-circle me-1"></i>Opprett arrangement
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Success Message (hidden by default) -->
                <div id="wizard-success" style="display: none;">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        </div>
                        <h3 class="mb-3">Arrangementet er opprettet!</h3>
                        <p class="text-muted mb-4">Du kan nå administrere arrangementet fra oversikten.</p>
                        <button type="button" class="btn btn-primary" onclick="resetWizardAndGoToList()">
                            <i class="bi bi-arrow-left me-1"></i>Gå til mine arrangementer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="events-checkin" class="subtab-content" style="display: none;">
        <!-- Event Cards Selector -->
        <div class="mb-4" id="checkin-event-selector">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-qr-code-scan me-2"></i>Innsjekking</h4>
                    <p class="text-muted mb-0">Velg et arrangement for å starte innsjekking</p>
                </div>
            </div>
            <div class="row g-3" id="checkin-event-cards">
                <div class="col-12 text-center text-muted py-5">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    Laster arrangementer...
                </div>
            </div>
        </div>

        <!-- Event Details & Check-in Panel -->
        <div id="event-details-panel" style="display: none;">
            <!-- Event Info Header with Fullscreen Button -->
            <div class="event-info-header">
                <div class="event-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="event-details flex-grow-1">
                    <h5 id="selected-event-title">Valgt arrangement</h5>
                    <p id="selected-event-date"></p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="open-fullscreen-checkin">
                        <i class="bi bi-arrows-fullscreen me-2"></i>Fullskjerm
                    </button>
                    <button class="checkin-back-btn" onclick="goBackToEventList()">
                        <i class="bi bi-chevron-left me-2"></i>Tilbake
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Column: Scanner & Stats -->
                <div class="col-lg-4">
                    <!-- Barcode Scanner / Search -->
                    <div id="checkin-scanner" class="card checkin-scanner-card mb-4">
                        <div class="card-body py-4 px-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <i class="bi bi-upc-scan text-white" style="font-size: 2rem;"></i>
                                <div class="text-white">
                                    <h6 class="mb-0 fw-bold">Søk / Scan</h6>
                                    <small class="opacity-75">Navn, e-post eller strekkode</small>
                                </div>
                            </div>
                            <input type="text" class="form-control" id="checkin-barcode-search" 
                                   placeholder="Skriv eller scan..." 
                                   autocomplete="off" autofocus>
                            <div id="checkin-search-results" class="mt-3" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div id="checkin-stats" class="mb-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="checkin-stat-card stat-primary">
                                    <div class="stat-value" id="checkin-total-attendees">0</div>
                                    <div class="stat-label">Registrerte</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="checkin-stat-card stat-success">
                                    <div class="stat-value text-success" id="checkin-checked-in-stat">0</div>
                                    <div class="stat-label">Innsjekket</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="checkin-stat-card stat-warning">
                                    <div class="stat-value text-warning" id="checkin-pending-count-stat">0</div>
                                    <div class="stat-label">Venter</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="checkin-stat-card stat-secondary">
                                    <div class="stat-value text-secondary" id="checkin-noshow-count">0</div>
                                    <div class="stat-label">No-show</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="checkin-stat-card stat-revenue">
                                    <div class="stat-value" id="checkin-revenue">0 kr</div>
                                    <div class="stat-label">Innsjekket inntekt</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="checkin-stat-card stat-expected">
                                    <div class="stat-value" id="checkin-expected-revenue">0 kr</div>
                                    <div class="stat-label">Forventet</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Guest Registration -->
                    <div id="quick-guest-registration" class="card guest-registration-card">
                        <div class="card-header text-white">
                            <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registrer gjest</h6>
                        </div>
                        <div class="card-body">
                            <form id="quick-guest-form">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1" for="guest-fornavn">Fornavn *</label>
                                        <input type="text" class="form-control form-control-sm" id="guest-fornavn" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1" for="guest-etternavn">Etternavn *</label>
                                        <input type="text" class="form-control form-control-sm" id="guest-etternavn" required>
                                    </div>
                                </div>
                                <div class="mb-2 mt-2">
                                    <label class="form-label small mb-1" for="guest-email">E-post</label>
                                    <input type="email" class="form-control form-control-sm" id="guest-email">
                                </div>
                                <div id="dynamic-registration-fields" class="dynamic-registration-fields" aria-live="polite"></div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Antall personer</label>
                                    <input type="number" class="form-control form-control-sm" id="guest-number-of-people" min="1" max="10" value="1" required>
                                </div>
                                <div class="mb-2" id="guest-membership-container" style="display: none;">
                                    <label class="form-label small mb-1">Medlemsplan</label>
                                    <select class="form-select form-select-sm" id="guest-membership-plan">
                                        <option value="">Ingen (ordinær pris)</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Barcode</label>
                                    <input type="text" class="form-control form-control-sm" id="guest-barcode" placeholder="Scan eller skriv inn">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Pris (kr)</label>
                                        <input type="number" class="form-control form-control-sm" id="guest-price" min="0" step="0.01" required readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Betaling</label>
                                        <select class="form-select form-select-sm" id="guest-payment-method" required></select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small mb-1">Status</label>
                                    <select class="form-select form-select-sm" id="guest-payment-status" required>
                                        <option value="paid">Betalt</option>
                                        <option value="pending">Venter betaling</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100 checkin-action-btn">
                                    <i class="bi bi-check-circle me-1"></i>Registrer og sjekk inn
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Attendee Lists -->
                <div class="col-lg-8">
                    <ul class="nav nav-tabs mb-3" role="tablist" id="checkin-tabs">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#checkin-confirmed-tab" type="button">
                                <i class="bi bi-check-circle-fill me-2 text-success"></i>Innsjekket
                                <span class="badge bg-success ms-2" id="checkin-confirmed-count">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#checkin-pending-tab" type="button">
                                <i class="bi bi-clock-fill me-2 text-warning"></i>Ikke innsjekket
                                <span class="badge bg-warning text-dark ms-2" id="checkin-pending-count">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="checkin-tabs-content">
                        <!-- Checked In Tab -->
                        <div class="tab-pane fade show active" id="checkin-confirmed-tab" role="tabpanel">
                            <div class="card checkin-list-card">
                                <div class="card-header bg-success text-white">
                                    <i class="bi bi-check-circle-fill me-2"></i>Ankommet / Innsjekket
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" id="checkin-confirmed-container">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Deltaker</th>
                                                    <th>Medlemskap</th>
                                                    <th>Pris</th>
                                                    <th>Betaling</th>
                                                    <th>Tid</th>
                                                </tr>
                                            </thead>
                                            <tbody id="checkin-confirmed-list">
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="checkin-empty-state">
                                                            <i class="bi bi-inbox"></i>
                                                            <p class="mb-0">Ingen innsjekket ennå</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Not Checked In Tab -->
                        <div class="tab-pane fade" id="checkin-pending-tab" role="tabpanel">
                            <div class="card checkin-list-card">
                                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-clock-fill me-2"></i>Venter på innsjekking</span>
                                    <button id="create-group-btn" class="btn btn-sm btn-light" style="display: none;" onclick="openGroupCheckoutModal()">
                                        <i class="bi bi-people-fill me-1"></i>Gruppesjekk <span class="badge bg-warning text-dark">0</span>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" id="checkin-pending-container">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Deltaker</th>
                                                    <th>Medlemskap</th>
                                                    <th>Pris</th>
                                                    <th>Status</th>
                                                    <th>Handlinger</th>
                                                </tr>
                                            </thead>
                                            <tbody id="checkin-pending-list">
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="checkin-empty-state">
                                                            <i class="bi bi-inbox"></i>
                                                            <p class="mb-0">Alle er innsjekket!</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Fullscreen Check-in Overlay -->
<div id="fullscreen-checkin-overlay">
    <div class="fullscreen-checkin-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-qr-code-scan" style="font-size: 1.5rem;"></i>
            <div>
                <h4 class="mb-0" id="fullscreen-event-title">Innsjekking</h4>
                <small class="opacity-75" id="fullscreen-event-date"></small>
            </div>
        </div>
        <button class="btn btn-light btn-sm" id="close-fullscreen-checkin">
            <i class="bi bi-x-lg me-1"></i>Lukk fullskjerm
        </button>
    </div>
    
    <div class="container-fluid p-4">
        <!-- Fullscreen Scanner -->
        <div class="fullscreen-scanner-container">
            <div class="card checkin-scanner-card">
                <div class="card-body py-4 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-upc-scan text-white" style="font-size: 2.5rem;"></i>
                        <div style="flex: 1;">
                            <input type="text" class="form-control form-control-lg" 
                                   id="fullscreen-barcode-input" 
                                   placeholder="Scan strekkode eller søk på navn/e-post..."
                                   autocomplete="off">
                        </div>
                        <div id="fullscreen-barcode-feedback" class="text-white" style="min-width: 200px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fullscreen Stats Grid -->
        <div class="fullscreen-stats-grid">
            <div class="checkin-stat-card stat-primary">
                <div class="stat-value" id="fullscreen-total-attendees">0</div>
                <div class="stat-label">Registrerte</div>
            </div>
            <div class="checkin-stat-card stat-success">
                <div class="stat-value text-success" id="fullscreen-checked-in">0</div>
                <div class="stat-label">Innsjekket</div>
            </div>
            <div class="checkin-stat-card stat-warning">
                <div class="stat-value text-warning" id="fullscreen-pending-count">0</div>
                <div class="stat-label">Venter</div>
            </div>
            <div class="checkin-stat-card stat-secondary">
                <div class="stat-value text-secondary" id="fullscreen-noshow">0</div>
                <div class="stat-label">No-show</div>
            </div>
            <div class="checkin-stat-card stat-revenue">
                <div class="stat-value" id="fullscreen-revenue">0 kr</div>
                <div class="stat-label">Innsjekket inntekt</div>
            </div>
            <div class="checkin-stat-card stat-expected">
                <div class="stat-value" id="fullscreen-expected">0 kr</div>
                <div class="stat-label">Forventet</div>
            </div>
        </div>

        <!-- Fullscreen Lists Side by Side -->
        <div class="fullscreen-lists-container">
            <!-- Not Checked In List -->
            <div class="card checkin-list-card">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-fill me-2"></i>Ikke innsjekket</span>
                    <span class="badge bg-light text-dark" id="fullscreen-pending-badge">0</span>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Deltaker</th>
                                    <th>Medlemskap</th>
                                    <th>Pris</th>
                                    <th>Status</th>
                                    <th>Handling</th>
                                </tr>
                            </thead>
                            <tbody id="fullscreen-pending-list">
                                <tr>
                                    <td colspan="5">
                                        <div class="checkin-empty-state">
                                            <i class="bi bi-check-circle"></i>
                                            <p class="mb-0">Alle er innsjekket!</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Checked In List -->
            <div class="card checkin-list-card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check-circle-fill me-2"></i>Innsjekket</span>
                    <span class="badge bg-light text-dark" id="fullscreen-confirmed-badge">0</span>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Deltaker</th>
                                    <th>Medlemskap</th>
                                    <th>Pris</th>
                                    <th>Betaling</th>
                                    <th>Tid</th>
                                </tr>
                            </thead>
                            <tbody id="fullscreen-checked-list">
                                <tr>
                                    <td colspan="5">
                                        <div class="checkin-empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p class="mb-0">Ingen innsjekket ennå</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div id="events-results" class="subtab-content" style="display: none;">
        <!-- Summary Stats - Horizontal Layout -->
        <div class="mb-4" id="events-results-summary" style="display: none;">
            <div class="row g-2">
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Arrangementer</div>
                            <div class="fs-5 fw-bold text-primary" id="summary-total-events">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Inntekt</div>
                            <div class="fs-5 fw-bold text-success" id="summary-total-revenue">0 kr</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Deltakere</div>
                            <div class="fs-5 fw-bold text-info" id="summary-total-attendees">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Kommende</div>
                            <div class="fs-5 fw-bold text-warning" id="summary-upcoming-events">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Mest lønnsom</div>
                            <div class="fs-6 fw-bold text-primary" id="summary-most-profitable">-</div>
                            <div class="small text-muted" id="summary-most-profitable-value">0 kr</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Dyreste</div>
                            <div class="fs-6 fw-bold text-danger" id="summary-most-expensive">-</div>
                            <div class="small text-muted" id="summary-most-expensive-value">0 kr</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center p-2">
                            <div class="text-muted small">Mest besøkt</div>
                            <div class="fs-6 fw-bold text-info" id="summary-most-attended">-</div>
                            <div class="small text-muted" id="summary-most-attended-value">0 deltakere</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Cards -->
        <div class="row" id="events-results-list">
            <div class="col-12 text-center py-5">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <span class="text-muted">Laster arrangementer...</span>
            </div>
        </div>
    </div>

    <!-- Event Registrations Approval Tab -->
    <div id="events-registrations" class="subtab-content" style="display: none;">
        <!-- Ventende påmeldinger (Godkjenning) -->
        <div class="card mb-4">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0">
                    <i class="bi bi-hourglass-split me-2 text-warning"></i>Ventende påmeldinger
                    <span class="badge bg-warning text-dark ms-2" id="registrations-pending-count-badge" data-event-pending-count style="display: none;">0</span>
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Godkjenn eller avvis ventende bestillinger</p>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 250px;">Påmeldingsdata</th>
                                <th class="text-center" style="min-width: 100px;">Pris</th>
                                <th>Arrangement</th>
                                <th class="text-end" style="min-width: 160px;">Handlinger</th>
                            </tr>
                        </thead>
                        <tbody id="pending-registrations-list">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Laster påmeldinger...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Alle påmeldinger per arrangement -->
        <div class="card">
            <div class="card-header bg-primary bg-opacity-10">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2 text-primary"></i>Alle påmeldinger
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Alle påmeldinger gruppert etter arrangement</p>
                
                <div id="all-registrations-container">
                    <div class="text-center py-5">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span class="text-muted">Laster alle påmeldinger...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventsOverviewModal" tabindex="-1" aria-labelledby="eventsOverviewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content events-overview-modal">
            <div class="modal-header"><h5 class="modal-title" id="eventsOverviewModalTitle">Eventbestillinger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Lukk"></button></div>
            <div class="modal-body" id="eventsOverviewModalBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventFieldsModal" tabindex="-1" aria-labelledby="eventFieldsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><div class="small text-uppercase text-muted">Dette arrangementet</div><h5 class="modal-title" id="eventFieldsModalTitle">Skjemafelt</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Lukk"></button>
            </div>
            <div class="modal-body"><div id="event-fields-editor"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Lukk</button></div>
        </div>
    </div>
</div>

<!-- Edit Event Modal with Vertical Tabs -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Rediger arrangement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <form id="edit-event-form">
                    <input type="hidden" name="event_id" id="edit-event-id">
                    
                    <div class="d-flex">
                        <!-- Vertical Tabs Navigation -->
                        <div class="edit-tabs-nav border-end" style="width: 200px; min-height: 500px; background: #f8f9fa;">
                            <div class="nav flex-column nav-pills p-3" id="edit-tabs" role="tablist">
                                <button class="nav-link active text-start mb-2" id="edit-tab-1" data-bs-toggle="pill" data-bs-target="#edit-panel-1" type="button" role="tab">
                                    <span class="badge bg-primary rounded-circle me-2">1</span>Type
                                </button>
                                <button class="nav-link text-start mb-2" id="edit-tab-2" data-bs-toggle="pill" data-bs-target="#edit-panel-2" type="button" role="tab">
                                    <span class="badge bg-secondary rounded-circle me-2">2</span>Grunnleggende
                                </button>
                                <button class="nav-link text-start mb-2" id="edit-tab-3" data-bs-toggle="pill" data-bs-target="#edit-panel-3" type="button" role="tab">
                                    <span class="badge bg-secondary rounded-circle me-2">3</span>Sted
                                </button>
                                <button class="nav-link text-start mb-2" id="edit-tab-4" data-bs-toggle="pill" data-bs-target="#edit-panel-4" type="button" role="tab">
                                    <span class="badge bg-secondary rounded-circle me-2">4</span>Priser
                                </button>
                                <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                                <button class="nav-link text-start mb-2" id="edit-tab-5" data-bs-toggle="pill" data-bs-target="#edit-panel-5" type="button" role="tab">
                                    <span class="badge bg-secondary rounded-circle me-2">5</span>Medlemstillegg
                                </button>
                                <?php endif; ?>
                                <button class="nav-link text-start mb-2" id="edit-tab-6" data-bs-toggle="pill" data-bs-target="#edit-panel-6" type="button" role="tab">
                                    <span class="badge bg-secondary rounded-circle me-2">6</span>Betaling
                                </button>
                                <button class="nav-link text-start" id="edit-tab-7" data-bs-toggle="pill" data-bs-target="#edit-panel-7" type="button" role="tab">
                                    <span class="badge bg-secondary rounded-circle me-2">7</span>Påmeldingsfelt
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tab Content Panels -->
                        <div class="tab-content flex-grow-1 p-4" id="edit-tabs-content" style="min-height: 500px;">
                            
                            <!-- Panel 1: Registration Type (Locked) -->
                            <div class="tab-pane fade show active" id="edit-panel-1" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-list-check me-2"></i>Registreringstype</h5>
                                <div class="alert alert-secondary">
                                    <i class="bi bi-lock me-2"></i>
                                    <strong>Registreringstypen er låst</strong> og kan ikke endres etter opprettelse.
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="registration_type" id="edit-reg-signup" value="signup" disabled>
                                            <label class="form-check-label text-muted" for="edit-reg-signup">
                                                <i class="bi bi-person-plus me-1"></i> <strong>Påmelding</strong><br>
                                                <small>Deltakere melder seg på</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="registration_type" id="edit-reg-none" value="none" disabled>
                                            <label class="form-check-label text-muted" for="edit-reg-none">
                                                <i class="bi bi-x-circle me-1"></i> <strong>Ingen påmelding</strong><br>
                                                <small>Fritt arrangement uten registrering</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="registration_type" id="edit-reg-binding" value="binding" disabled>
                                            <label class="form-check-label text-muted" for="edit-reg-binding">
                                                <i class="bi bi-lock me-1"></i> <strong>Bindende</strong><br>
                                                <small>Krever betaling ved påmelding</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Panel 2: Basic Info -->
                            <div class="tab-pane fade" id="edit-panel-2" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Grunnleggende informasjon</h5>
                                
                                <div class="row">
                                    <div class="col-md-<?php echo isset($hasMembershipAccess) && $hasMembershipAccess ? '8' : '12'; ?> mb-3">
                                        <label class="form-label">Arrangementsnavn *</label>
                                        <input type="text" class="form-control" name="title" id="edit-title" required>
                                    </div>
                                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Kun for medlemmer?</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_member_exclusive" id="edit-member-exclusive">
                                            <label class="form-check-label" for="edit-member-exclusive">Kun medlemmer</label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Beskrivelse</label>
                                    <textarea class="form-control" name="description" id="edit-description" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bilde-URL</label>
                                    <input type="url" class="form-control" name="event_image_url" id="edit-event-image-url">
                                </div>
                                
                                <div class="row" id="edit-attendees-row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Min. deltakere</label>
                                        <input type="number" class="form-control" name="min_attendees" id="edit-min-attendees">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maks. deltakere</label>
                                        <input type="number" class="form-control" name="max_attendees" id="edit-max-attendees">
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="edit-registration-deadline-container" style="display: none;">
                                    <label class="form-label">Frist for min. deltakere *</label>
                                    <input type="text" class="form-control" name="registration_deadline" id="edit-registration-deadline"
                                           aria-label="Fristdato"
                                           inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                                           placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                                    <small class="form-text text-muted">Må være før startdato</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Startdato *</label>
                                        <input type="text" class="form-control" name="start_date" id="edit-start-date" required
                                               aria-label="Startdato"
                                               inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                                               placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Starttid *</label>
                                        <input type="time" class="form-control" name="start_time" id="edit-start-time" required step="60"
                                               aria-label="Starttid"
                                               pattern="[0-9]{2}:[0-9]{2}" inputmode="numeric" placeholder="HH:mm" lang="nb-NO">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sluttdato</label>
                                        <input type="text" class="form-control" name="end_date" id="edit-end-date"
                                               aria-label="Sluttdato"
                                               inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                                               placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sluttid</label>
                                        <input type="time" class="form-control" name="end_time" id="edit-end-time" step="60"
                                               aria-label="Sluttid"
                                               pattern="[0-9]{2}:[0-9]{2}" inputmode="numeric" placeholder="HH:mm" lang="nb-NO">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Aldersgrense</label>
                                    <input type="number" class="form-control" name="age_limit" id="edit-age-limit" min="0" placeholder="Ingen aldersgrense">
                                </div>
                            </div>
                            
                            <!-- Panel 3: Location -->
                            <div class="tab-pane fade" id="edit-panel-3" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Sted</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Gateadresse</label>
                                    <input type="text" class="form-control address-field" name="event_address" id="edit-event-address">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Stedsnavn</label>
                                    <input type="text" class="form-control" name="venue_name" id="edit-venue-name" placeholder="F.eks. Kulturhuset">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Postnummer</label>
                                        <input type="text" class="form-control zip-field" name="event_postal" id="edit-event-postal" maxlength="4">
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">By</label>
                                        <input type="text" class="form-control city-field" name="event_city" id="edit-event-city">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Panel 4: Pricing -->
                            <div class="tab-pane fade" id="edit-panel-4" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-tag me-2"></i>Priser</h5>
                                
                                <div class="mb-4" id="edit-ticket-price-container">
                                    <label class="form-label">Ordinær billettpris (NOK)</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg" name="ticket_price" id="edit-ticket-price" placeholder="0.00">
                                    <small class="text-muted">Sett til 0 for gratis arrangement</small>
                                </div>
                                
                                <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                                <hr class="my-4">
                                <h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Medlemspriser</h6>
                                <div id="edit-membership-pricing-container">
                                    <div class="text-center py-2">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        <small class="text-muted">Laster medlemsplaner...</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                            <!-- Panel 5: Membership Add-ons (only shown with membership access) -->
                            <div class="tab-pane fade" id="edit-panel-5" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-people me-2"></i>Medlemstillegg</h5>
                                
                                <div class="card border-0 bg-light mb-3">
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" name="memberverification" id="edit-memberverification">
                                            <label class="form-check-label" for="edit-memberverification">
                                                <i class="bi bi-shield-check text-success me-2"></i>
                                                <strong>Autofyll og autogodkjenning</strong>
                                                <small class="text-muted d-block">Medlemmer får automatisk utfylt informasjon og godkjennes umiddelbart</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Panel 6: Payment Methods -->
                            <div class="tab-pane fade" id="edit-panel-6" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-credit-card me-2"></i>Betalingsmetoder</h5>
                                
                                <div id="edit-payment-methods-container" class="mb-4">
                                    <div class="text-center py-2">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        <small class="text-muted">Laster betalingsmetoder...</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Standard betalingsmetode</label>
                                    <select class="form-select" name="default_payment_method" id="edit-default-payment-method">
                                        <option value="">Velg standardmetode...</option>
                                    </select>
                                    <small class="form-text text-muted">Denne metoden vises som anbefalt ved betaling</small>
                                </div>
                            </div>
                            
                            <!-- Panel 7: Registration Fields -->
                            <div class="tab-pane fade" id="edit-panel-7" role="tabpanel">
                                <h5 class="mb-3"><i class="bi bi-ui-checks me-2"></i>Påmeldingsfelt</h5>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" checked disabled>
                                            <label class="form-check-label text-muted">
                                                <i class="bi bi-lock-fill text-secondary me-1"></i> Fornavn <span class="badge bg-secondary">Låst</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" checked disabled>
                                            <label class="form-check-label text-muted">
                                                <i class="bi bi-lock-fill text-secondary me-1"></i> Etternavn <span class="badge bg-secondary">Låst</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning mb-3">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Kontaktinformasjon (minst én påkrevd):</strong><br>
                                    <small>Velg E-post og/eller Telefonnummer.</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input edit-contact-field-required" type="checkbox" name="field_email" id="edit-field-email">
                                            <label class="form-check-label" for="edit-field-email">
                                                <i class="bi bi-envelope text-primary me-1"></i> E-post
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input edit-contact-field-required" type="checkbox" name="field_phone" id="edit-field-phone">
                                            <label class="form-check-label" for="edit-field-phone">
                                                <i class="bi bi-telephone text-primary me-1"></i> Telefon
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="field_street" id="edit-field-street">
                                            <label class="form-check-label" for="edit-field-street">
                                                <i class="bi bi-house text-primary me-1"></i> Gateadresse
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="field_zip" id="edit-field-zip">
                                            <label class="form-check-label" for="edit-field-zip">
                                                <i class="bi bi-mailbox text-primary me-1"></i> Postnummer
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="field_city" id="edit-field-city">
                                            <label class="form-check-label" for="edit-field-city">
                                                <i class="bi bi-geo-alt text-primary me-1"></i> Poststed
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="field_age" id="edit-field-age">
                                            <label class="form-check-label" for="edit-field-age">
                                                <i class="bi bi-person-badge text-primary me-1"></i> Alder
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="saveEventChanges()">
                    <i class="bi bi-check-circle me-1"></i>Lagre endringer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Income Modal -->
<div class="modal fade" id="addIncomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Legg til inntekt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-income-form">
                    <input type="hidden" id="income-event-id" name="event_id">
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse *</label>
                        <input type="text" class="form-control" name="description" required placeholder="F.eks. sponsorstøtte, salg av varer, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beløp (NOK) *</label>
                        <input type="number" class="form-control" name="amount" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dato *</label>
                        <input type="text" class="form-control" name="date" required
                               inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                               placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-success" onclick="saveIncome()">
                    <i class="bi bi-check-circle me-1"></i>Legg til
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-dash-circle me-2"></i>Legg til utgift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-expense-form">
                    <input type="hidden" id="expense-event-id" name="event_id">
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse *</label>
                        <input type="text" class="form-control" name="description" required placeholder="F.eks. lokale, catering, utstyr, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beløp (NOK) *</label>
                        <input type="number" class="form-control" name="amount" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dato *</label>
                        <input type="text" class="form-control" name="date" required
                               inputmode="numeric" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                               placeholder="DD.MM.YYYY" maxlength="10" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category">
                            <option value="">Velg kategori</option>
                            <option value="venue">Lokale</option>
                            <option value="catering">Catering</option>
                            <option value="equipment">Utstyr</option>
                            <option value="marketing">Markedsføring</option>
                            <option value="staff">Personal</option>
                            <option value="other">Annet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-danger" onclick="saveExpense()">
                    <i class="bi bi-check-circle me-1"></i>Legg til
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Event Details Modal -->
<div class="modal fade" id="viewEventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-graph-up me-2"></i><span id="details-event-title">Arrangement Resultat</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="details-event-id">
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success bg-opacity-10">
                            <div class="card-body">
                                <small class="text-muted">Total inntekt</small>
                                <h4 class="mb-0 text-success" id="details-total-income">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger bg-opacity-10">
                            <div class="card-body">
                                <small class="text-muted">Total utgift</small>
                                <h4 class="mb-0 text-danger" id="details-total-expense">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-primary bg-opacity-10">
                            <div class="card-body">
                                <small class="text-muted">Netto resultat</small>
                                <h4 class="mb-0 text-primary" id="details-net-result">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="btn-group mb-3 w-100" role="group">
                    <button type="button" class="btn btn-success" onclick="openAddIncomeModal()">
                        <i class="bi bi-plus-circle me-1"></i>Legg til inntekt
                    </button>
                    <button type="button" class="btn btn-danger" onclick="openAddExpenseModal()">
                        <i class="bi bi-dash-circle me-1"></i>Legg til utgift
                    </button>
                </div>

                <!-- Transactions List -->
                <h6 class="mb-3">Transaksjoner (summert)</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Beskrivelse (antall)</th>
                                <th class="text-end">Sum</th>
                            </tr>
                        </thead>
                        <tbody id="details-transactions-list">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    Ingen transaksjoner ennå
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/event_form_helpers.js"></script>
<script src="/assets/entity-form-fields.js"></script>
<script src="/assets/event-settings.js"></script>
<script src="/assets/events-dashboard.js"></script>
<script>
const norskMnd = ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'];
const norskMndLang = ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'];
const norskDag = ['søndag', 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag'];

const norskDatePattern = /^\d{2}\.\d{2}\.\d{4}$/;
const sqlDatePattern = /^\d{4}-\d{2}-\d{2}$/;
const time24HourPattern = /^\d{2}:\d{2}$/;

function daysInCalendarMonth(year, month) {
    if (month === 2) {
        return (year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0)) ? 29 : 28;
    }
    return [4, 6, 9, 11].includes(month) ? 30 : 31;
}

// Parse dates as calendar components, never as a date string. This prevents
// browser timezone/UTC conversion from changing the day being edited.
function parseCalendarDate(value) {
    const normalized = String(value || '').trim();
    let match = normalized.match(norskDatePattern);
    if (match) {
        const [day, month, year] = normalized.split('.').map(Number);
        if (month < 1 || month > 12 || day < 1 || day > daysInCalendarMonth(year, month)) {
            return null;
        }
        return {year, month, day};
    }

    match = normalized.match(sqlDatePattern);
    if (match) {
        const [year, month, day] = normalized.split('-').map(Number);
        if (month < 1 || month > 12 || day < 1 || day > daysInCalendarMonth(year, month)) {
            return null;
        }
        return {year, month, day};
    }

    return null;
}

function sqlDateToNorsk(value) {
    const displayDate = EventFormHelpers.toNorskDate(value);
    if (displayDate) return displayDate;
    const parts = parseCalendarDate(value);
    return parts ? `${String(parts.day).padStart(2, '0')}.${String(parts.month).padStart(2, '0')}.${parts.year}` : (value || '');
}

function norskDateToSql(value) {
    const parts = parseCalendarDate(value);
    return parts ? `${parts.year}-${String(parts.month).padStart(2, '0')}-${String(parts.day).padStart(2, '0')}` : (value || '');
}

function compareCalendarDates(first, second) {
    const a = parseCalendarDate(first);
    const b = parseCalendarDate(second);
    if (!a || !b) return null;
    if (a.year !== b.year) return a.year < b.year ? -1 : 1;
    if (a.month !== b.month) return a.month < b.month ? -1 : 1;
    if (a.day !== b.day) return a.day < b.day ? -1 : 1;
    return 0;
}

function calendarWeekday(year, month, day) {
    const offsets = [0, 3, 2, 5, 0, 3, 5, 1, 4, 6, 2, 4];
    const adjustedYear = month < 3 ? year - 1 : year;
    return (adjustedYear + Math.floor(adjustedYear / 4) - Math.floor(adjustedYear / 100) +
        Math.floor(adjustedYear / 400) + offsets[month - 1] + day) % 7;
}

function formatNorskDato(dateStr, timeStr, opts = {}) {
    if (!dateStr) return '';
    const dateParts = parseCalendarDate(dateStr);
    if (!dateParts) return dateStr;
    const d = dateParts.day;
    const m = dateParts.month - 1;
    const y = dateParts.year;
    
    let result = '';
    if (opts.weekday) {
        result += norskDag[calendarWeekday(y, m + 1, d)] + ' ';
    }
    if (opts.longMonth) {
        result += `${d}. ${norskMndLang[m]} ${y}`;
    } else {
        result += `${d}. ${norskMnd[m]} ${y}`;
    }
    if (opts.includeTime && timeStr) {
        result += ` kl. ${timeStr.substring(0, 5)}`;
    }
    return result;
}

function formatNorskTid(timeStr) {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
}

function validateDateTimeInput(element, label) {
    if (!element) return true;
    const value = element.value;
    const isRequired = element.required || element.dataset.eventTimeRequired === 'true';
    let message = '';

    if (!value) {
        if (element.dataset.eventTimePartial === 'true') {
            message = `Velg både timer og minutter for ${label.toLowerCase()}`;
        } else if (isRequired) {
            message = `${label} må fylles ut`;
        }
    } else if (element.name === 'start_time' || element.name === 'end_time') {
        const [hours, minutes] = time24HourPattern.test(value) ? value.split(':').map(Number) : [NaN, NaN];
        if (!time24HourPattern.test(value) || hours > 23 || minutes > 59) {
            message = `${label} må være på formatet HH:mm (24-timers klokke)`;
        }
    } else if (!norskDatePattern.test(value) || !parseCalendarDate(value)) {
        message = `${label} må være en gyldig dato på formatet DD.MM.YYYY`;
    }

    element.setCustomValidity(message);
    return !message;
}

function validateEventDateTimeInputs(form) {
    if (!form) return true;
    const fields = [
        ['input[name="registration_deadline"]', 'Fristdato'],
        ['input[name="start_date"]', 'Startdato'],
        ['input[name="end_date"]', 'Sluttdato'],
        ['input[name="start_time"]', 'Starttid'],
        ['input[name="end_time"]', 'Sluttid']
    ];
    let valid = true;
    fields.forEach(([selector, label]) => {
        const element = form.querySelector(selector);
        if (!validateDateTimeInput(element, label)) valid = false;
    });
    return valid;
}

function validateNewEventStartDate(form) {
    const startDateInput = form?.querySelector('input[name="start_date"]');
    const startDate = parseCalendarDate(startDateInput?.value || '');
    if (!startDateInput || !startDate) return false;

    const today = new Date();
    const startDateNumber = startDate.year * 10000 + startDate.month * 100 + startDate.day;
    const todayNumber = today.getFullYear() * 10000 + (today.getMonth() + 1) * 100 + today.getDate();
    if (startDateNumber < todayNumber) {
        const message = 'Startdato kan ikke være i fortiden når du oppretter et nytt arrangement';
        startDateInput.setCustomValidity(message);
        startDateInput.reportValidity();
        toast.quickWarning('Validering', message);
        return false;
    }

    startDateInput.setCustomValidity('');
    return true;
}

function installDateTimeValidation() {
    EventFormHelpers.attachDateTimeControls(document);
    document.querySelectorAll(
        'input[name="registration_deadline"], input[name="start_date"], input[name="end_date"], ' +
        'input[name="start_time"], input[name="end_time"], #add-income-form input[name="date"], #add-expense-form input[name="date"]'
    ).forEach(input => {
        const label = input.name === 'start_date' ? 'Startdato' :
            input.name === 'end_date' ? 'Sluttdato' :
            input.name === 'start_time' ? 'Starttid' :
            input.name === 'end_time' ? 'Sluttid' : 'Dato';
        input.addEventListener('input', () => validateDateTimeInput(input, label));
        input.addEventListener('blur', () => validateDateTimeInput(input, label));
    });
}

document.addEventListener('DOMContentLoaded', function() {
    installDateTimeValidation();

    // Load membership plans and payment methods for create form (these are needed for any tab)
    loadMembershipPlansForCreate();
    loadPaymentMethodsForCreate();

    // Handle min_attendees field - show/hide deadline
    const minAttendeesInput = document.getElementById('min_attendees');
    const deadlineContainer = document.getElementById('registration_deadline_container');
    const deadlineInput = document.getElementById('registration_deadline');
    
    if (minAttendeesInput && deadlineContainer) {
        minAttendeesInput.addEventListener('input', function() {
            if (this.value && parseInt(this.value) > 0) {
                deadlineContainer.style.display = 'block';
                deadlineInput.required = true;
            } else {
                deadlineContainer.style.display = 'none';
                deadlineInput.required = false;
                deadlineInput.value = '';
            }
        });
    }

    // Create event form submission
    const createEventForm = document.getElementById('create-event-form');
    if (createEventForm) {
        createEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (eventCreationBusy) return;

            if (!validateEventDateTimeInputs(this)) {
                toast.quickWarning('Validering', 'Kontroller datoer og klokkeslett. Bruk DD.MM.YYYY og HH:mm.');
                return;
            }

            if (!validateNewEventStartDate(this)) {
                return;
            }
            
            const formData = new FormData(this);
            
            // Get registration_type directly from radio buttons (more reliable than FormData)
            const selectedRegType = this.querySelector('input[name="registration_type"]:checked');
            const registrationType = selectedRegType ? selectedRegType.value : 'signup';
            if (!eventCreateFieldsEditor?.isReady()) {
                toast.quickWarning('Skjemafelt', 'Vent til standardfeltene er lastet, eller prøv å laste dem på nytt.');
                return;
            }
            
            const createFields = registrationType === 'none' ? [] : (eventCreateFieldsEditor?.getFields() || []);
            if (registrationType !== 'none') {
                const visibleNames = new Set(createFields.filter(field => field.is_visible).map(field => field.field_name));
                const requiredNames = new Set(createFields.filter(field => field.is_visible && field.is_required).map(field => field.field_name));
                if (!visibleNames.has('first_name') || !visibleNames.has('last_name') ||
                    !requiredNames.has('first_name') || !requiredNames.has('last_name') ||
                    !['email', 'phone'].some(name => visibleNames.has(name))) {
                    toast.quickWarning('Skjemafelt', 'Fornavn, etternavn og minst ett kontaktfelt må være synlige.');
                    return;
                }
            }
            const csrfToken = eventCreateFieldsEditor?.getCsrfToken() || window.entityFormCsrfToken || '';
            if (!csrfToken) {
                toast.quickError('Skjemafelt', 'Sikkerhetstoken mangler. Last skjemafelt på nytt før du oppretter.');
                return;
            }
            
            // Validate registration deadline
            const minAttendees = formData.get('min_attendees');
            const registrationDeadline = formData.get('registration_deadline');
            const startDate = formData.get('start_date');
            
            if (minAttendees && parseInt(minAttendees) > 0 && !registrationDeadline) {
                toast.quickWarning('Validering', 'Du må velge en frist for minimum deltakere');
                return;
            }
            
            if (registrationDeadline && startDate) {
                const dateComparison = compareCalendarDates(registrationDeadline, startDate);
                if (dateComparison === null || dateComparison >= 0) {
                    toast.quickWarning('Validering', 'Frist for min. deltakere må være før startdato');
                    return;
                }
            }
            
            // Collect membership plan pricing
            const membershipPricing = [];
            membershipPlansData.forEach(plan => {
                // Use plan_id if available, otherwise use id (matches loadWizardMembershipPlans)
                const planId = plan.plan_id || plan.id;
                const priceInput = document.querySelector(`input[data-plan-id="${planId}"]`);
                const publicCheckbox = document.querySelector(`input[name="membership_public_${planId}"]`);
                if (priceInput && priceInput.value) {
                    membershipPricing.push({
                        plan_id: planId,
                        price: parseFloat(priceInput.value),
                        public_display: publicCheckbox && publicCheckbox.checked ? 1 : 0
                    });
                }
            });
            
            // Collect allowed payment methods from Economy configuration
            let allowedPaymentMethods;
            try { allowedPaymentMethods = EventSettings.paymentMethods(this,'#payment-methods-container input[type="checkbox"]',registrationType); }
            catch(error) { toast.quickWarning('Betaling',error.message); return; }
            
            // Build payment timing methods array: always has 'arrival', plus economy methods if any selected
            const paymentTimingMethods = ['arrival'];
            allowedPaymentMethods.forEach(method => {
                if (method !== 'cash') {
                    paymentTimingMethods.push(method); // Add actual method type (card, vipps, etc)
                }
            });
            
            // Get selected default payment method
            const defaultPaymentMethodSelect = document.getElementById('default-payment-method');
            const defaultPaymentMethod = allowedPaymentMethods.includes(defaultPaymentMethodSelect?.value)
                ? defaultPaymentMethodSelect.value : allowedPaymentMethods[0] || null;
            
            const data = {
                title: formData.get('title'),
                description: formData.get('description'),
                start_date: startDate,
                start_time: formData.get('start_time'),
                end_date: formData.get('end_date') || null,
                end_time: formData.get('end_time') || null,
                venue_name: formData.get('venue_name'),
                event_address: formData.get('event_address'),
                event_city: formData.get('event_city'),
                event_postal: formData.get('event_postal'),
                min_attendees: minAttendees || null,
                registration_deadline: registrationDeadline || null,
                max_attendees: formData.get('max_attendees') || null,
                age_limit: formData.get('age_limit') || null,
                ticket_price: registrationType === 'none' ? 0 : (formData.get('ticket_price') || 0),
                event_image_url: formData.get('event_image_url') || null,
                ...EventSettings.membership(this),
                registration_type: registrationType,
                payment_timing_methods: paymentTimingMethods,
                membership_pricing: membershipPricing,
                allowed_payment_methods: allowedPaymentMethods,
                default_payment_method: defaultPaymentMethod,
                form_fields: createFields,
                csrf_token: csrfToken
            };

            setEventCreationBusy(true);
            try {
                const response = await fetch('/api/create_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                // Unlock before the successful reset restores default controls.
                setEventCreationBusy(false);
                EventFormHelpers.handleCreateResult(result, {
                    onSuccess: () => {
                        toast.quickSuccess('Arrangement', 'Arrangement opprettet!');
                        showWizardSuccess();
                    },
                    onFailure: message => toast.quickError('Feil', message)
                });
            } catch (error) {
                console.error('Error creating event:', error);
                toast.quickError('Feil', 'Feil ved opprettelse av arrangement');
            } finally {
                setEventCreationBusy(false);
            }
        });
    }
});

// Prevent multiple simultaneous calls
let loadEventsRunning = false;

function safeEventImageUrl(value) {
    if (!value) return '';
    try {
        const parsed = new URL(String(value));
        return ['http:', 'https:'].includes(parsed.protocol) ? parsed.href : '';
    } catch (error) {
        return '';
    }
}

function eventImageAttribute(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

async function loadEvents() {
    if (loadEventsRunning) {
        console.log('loadEvents already running, skipping...');
        return;
    }
    
    loadEventsRunning = true;
    
    try {
        const response = await fetch('/api/get_events.php');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const result = await response.json();

        const eventsList = document.getElementById('events-list');
        
        if (!result.success) {
            document.dispatchEvent(new CustomEvent('events-dashboard-source', {detail:{success:false,message:result.message || 'Kunne ikke laste arrangementer'}}));
            eventsList.innerHTML = `<div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> ${result.message || 'Kunne ikke laste arrangementer'}
            </div>`;
            return;
        }
        document.dispatchEvent(new CustomEvent('events-dashboard-source', {
            detail:{success:true,events:Array.isArray(result.events) ? result.events : []}
        }));
        
        if (result.success && result.events && result.events.length > 0) {
            // DEDUPLICATE: Remove duplicate events by ID
            const uniqueEvents = Array.from(new Map(result.events.map(e => [e.id, e])).values());
            
            console.log('Rendering', uniqueEvents.length, 'unique events');
            
            eventsList.innerHTML = uniqueEvents.map(event => {
                const dateStr = formatNorskDato(event.start_date, event.start_time);
                const timeStr = formatNorskTid(event.start_time);
                
                // Check if "Ingen påmelding" (no registration) event
                const isNoRegistration = event.registration_type === 'none';
                
                let priceDisplay = '';
                let priceMetaItem = '';
                
                // Check if member-exclusive event
                if (event.is_member_exclusive && event.membership_prices && event.membership_prices.length > 0) {
                    // Show all membership prices in a clean list (including non-public in gray)
                    const priceList = event.membership_prices
                        .map(mp => {
                            const isPublic = mp.public_display == 1;
                            const textClass = isPublic ? 'text-primary' : 'text-muted';
                            const opacity = isPublic ? '' : 'opacity: 0.6;';
                            return `<div class="d-flex justify-content-between align-items-center mb-1" style="${opacity}">
                                <small>${mp.plan_name}${!isPublic ? ' (skjult)' : ''}</small>
                                <strong class="${textClass}">${mp.price} kr</strong>
                            </div>`;
                        })
                        .join('');
                    priceDisplay = `<span class="event-price" style="display: block; padding: 8px 0;">${priceList}</span>`;
                    priceMetaItem = `<div class="event-meta-item"><i class="bi bi-tag"></i><span>Medlemspriser konfigurert</span></div>`;
                } else if (event.ticket_price && event.ticket_price > 0) {
                    // Show price - if no registration, add "(betales ved inngang)"
                    if (isNoRegistration) {
                        priceDisplay = `<span class="event-price">${event.ticket_price} kr (betales ved inngang)</span>`;
                        priceMetaItem = `<div class="event-meta-item"><i class="bi bi-tag"></i><span>${event.ticket_price} kr (betales ved inngang)</span></div>`;
                    } else {
                        priceDisplay = `<span class="event-price">${event.ticket_price} kr</span>`;
                        priceMetaItem = `<div class="event-meta-item"><i class="bi bi-tag"></i><span>${event.ticket_price} kr</span></div>`;
                    }
                } else {
                    priceDisplay = '';
                    priceMetaItem = `<div class="event-meta-item"><i class="bi bi-tag"></i><span>Gratis</span></div>`;
                }
                
                const eventImageUrl = safeEventImageUrl(event.event_image_url);
                const imageHtml = eventImageUrl
                    ? `<div class="event-image-shell">
                           <i class="bi bi-calendar-event"></i>
                           <img class="event-image" data-event-image src="${eventImageAttribute(eventImageUrl)}" alt="" loading="lazy">
                       </div>`
                    : `<div class="event-image-placeholder"><i class="bi bi-calendar-event"></i></div>`;
                
                const memberBadge = event.is_member_exclusive == 1 
                    ? '<span class="badge bg-warning event-badge-overlay">Kun medlemmer</span>' 
                    : '';
                const isPast = event.is_past == 1;
                const isInactive = event.is_active == 0;
                const isUnavailable = isPast || isInactive;
                const statusBadge = isInactive
                    ? '<span class="badge bg-secondary event-status-badge">Inaktiv</span>'
                    : (isPast ? '<span class="badge bg-dark event-status-badge">Avsluttet</span>' : '');
                
                const location = event.venue_name || event.event_city || 'Ikke angitt';
                const capacity = event.max_attendees ? `${event.current_attendees || 0} / ${event.max_attendees}` : 'Ubegrenset';
                const createdBy = (event.first_name && event.last_name) 
                    ? `${event.first_name} ${event.last_name}` 
                    : 'Ukjent bruker';
                
                // Build meta items based on registration type
                let metaItems = `
                    <div class="event-meta-item">
                        <i class="bi bi-calendar3"></i>
                        <span>${dateStr} ${timeStr}</span>
                    </div>
                    ${priceMetaItem}
                    <div class="event-meta-item">
                        <i class="bi bi-geo-alt"></i>
                        <span>${location}</span>
                    </div>`;
                
                // Only show participant capacity if NOT "Ingen påmelding"
                if (!isNoRegistration) {
                    metaItems += `
                    <div class="event-meta-item">
                        <i class="bi bi-people"></i>
                        <span>${capacity} plasser</span>
                    </div>`;
                }
                
                metaItems += `
                    <div class="event-meta-item">
                        <i class="bi bi-person-circle"></i>
                        <span>Opprettet av ${createdBy}</span>
                    </div>`;
                
                return `
                    <div class="col-md-4">
                        <div class="event-card${isUnavailable ? ' event-card-inactive' : ''}">
                            ${imageHtml}
                            ${memberBadge}
                            ${statusBadge}
                            <div class="event-body" onclick="toggleEventResults('${event.id}')" style="cursor: pointer;">
                                <h5 class="event-title">${event.title}</h5>
                                <div class="event-meta">
                                    ${metaItems}
                                </div>
                            </div>
                            <div class="event-footer">
                                <div>${priceDisplay}</div>
                                <div class="event-actions">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation(); goToShareLink('event', '${event.id}')" title="Gå til delingslenke">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); viewEvent('${event.id}')" title="Vis detaljer">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" data-event='${JSON.stringify(event).replace(/'/g, "&apos;")}' onclick="event.stopPropagation(); editEvent('${event.id}')" title="Grunnopplysninger" aria-label="Grunnopplysninger">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">Grunnopplysninger</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="event.stopPropagation(); openEventFormFields('${event.id}')" title="Skjemafelt">
                                        <i class="bi bi-ui-checks"></i><span class="visually-hidden">Skjemafelt</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteEvent('${event.id}')" title="Slett">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Event Results Collapse -->
                            <div id="event-results-${event.id}" class="event-results-collapse" style="display: none;">
                                <div class="event-results-header">
                                    <i class="bi bi-graph-up me-2"></i>Arrangement Resultat
                                </div>
                                <div class="event-results-body">
                                    <!-- Income Section -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="text-success"><i class="bi bi-arrow-down-circle me-1"></i>Inntekter</strong>
                                            <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); addEventIncome('${event.id}')">
                                                <i class="bi bi-plus-circle me-1"></i>Legg til
                                            </button>
                                        </div>
                                        <div id="event-income-${event.id}" class="event-finance-list">
                                            <small class="text-muted">Ingen inntekter registrert</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Expense Section -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="text-danger"><i class="bi bi-arrow-up-circle me-1"></i>Utgifter</strong>
                                            <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); addEventExpense('${event.id}')">
                                                <i class="bi bi-plus-circle me-1"></i>Legg til
                                            </button>
                                        </div>
                                        <div id="event-expense-${event.id}" class="event-finance-list">
                                            <small class="text-muted">Ingen utgifter registrert</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Summary -->
                                    <div class="event-results-summary">
                                        <div class="d-flex justify-content-between">
                                            <span>Total inntekt:</span>
                                            <span class="text-success fw-bold" id="event-total-income-${event.id}">0 NOK</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Total utgift:</span>
                                            <span class="text-danger fw-bold" id="event-total-expense-${event.id}">0 NOK</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                            <strong>Netto resultat:</strong>
                                            <strong id="event-net-result-${event.id}">0 NOK</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            eventsList.querySelectorAll('img[data-event-image]').forEach((image) => {
                image.addEventListener('error', () => image.remove(), { once: true });
            });
        } else {
            eventsList.innerHTML = `
                <div class="col-12">
                    <div class="empty-events">
                        <i class="bi bi-calendar-x"></i>
                        <h5>Ingen arrangementer ennå</h5>
                        <p class="text-muted">Opprett ditt første arrangement ved å klikke på "Opprett nytt" knappen ovenfor</p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        document.dispatchEvent(new CustomEvent('events-dashboard-source', {detail:{success:false,message:'Feil ved lasting av arrangementer'}}));
        document.getElementById('events-list').innerHTML = `
            <div class="col-12">
                <div class="empty-events">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <h5>Feil ved lasting av arrangementer</h5>
                    <p class="text-muted">Vennligst prøv igjen senere</p>
                </div>
            </div>
        `;
    } finally {
        loadEventsRunning = false;
    }
}

async function goToShareLink(type, itemId) {
    try {
        const response = await fetch(`/api/get_share_link.php?type=${type}&item_id=${itemId}`);
        const result = await response.json();
        
        if (result.success && result.share_link) {
            window.open(result.share_link, '_blank');
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke hente delingslenke');
        }
    } catch (error) {
        console.error('Error opening share link:', error);
        toast.quickError('Feil', 'Kunne ikke åpne delingslenke');
    }
}

function viewEvent(eventId) {
    toast.quickInfo('Info', 'Visning av arrangement: ' + eventId);
}

function deleteEvent(eventId) {
    // Show modal to choose delete mode
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'deleteEventModal';
    modal.setAttribute('tabindex', '-1');
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Slett arrangement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Velg hvordan du ønsker å slette dette arrangementet:</p>
                    <div class="row">
                        <div class="col-6">
                            <button class="btn btn-outline-warning w-100" onclick="performDeleteEvent('${eventId}', 'soft')">
                                <i class="bi bi-eye-slash me-2"></i>
                                <strong>Gjør usynlig</strong>
                                <br>
                                <small class="text-muted">Data bevart i databasen</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-danger w-100" onclick="performDeleteEvent('${eventId}', 'hard')">
                                <i class="bi bi-trash me-2"></i>
                                <strong>Slett permanent</strong>
                                <br>
                                <small class="text-muted">Alt fjernes fra DB</small>
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Tips:</strong> Velg "Gjør usynlig" for å bevare historikk. Velg "Slett permanent" kun når du ønsker fullstendig sletting.
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Clean up modal when hidden
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

let eventDeleteBusy = false;
async function performDeleteEvent(eventId, deleteMode) {
    if (eventDeleteBusy) return;
    if (deleteMode === 'hard' && !confirm('Slette permanent? Dette fjerner arrangementet og feltoppsettet. Bare arrangementer uten påmeldinger eller historikk kan slettes. Handlingen refunderer ingen betalinger.')) return;
    eventDeleteBusy = true;
    const deleteTypeLabel = deleteMode === 'soft' ? 'gjort usynlig' : 'slettet permanent';
    try {
    const tokenResult = await EntityFormFields.load('event',eventId);
    await fetch('/api/delete_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json', 'X-CSRF-Token':tokenResult.csrf_token
        },
        body: JSON.stringify({ 
            event_id: eventId,
            delete_mode: deleteMode, csrf_token:tokenResult.csrf_token
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            toast.quickSuccess('Arrangement', 'Arrangement ' + deleteTypeLabel);
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteEventModal'));
            if (modal) modal.hide();
            loadEvents();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke slette');
        }
    })
    .catch(error => {
        console.error('Error deleting event:', error);
        toast.quickError('Feil', 'Feil ved sletting');
    });
    } catch(error) {
        toast.quickError('Feil', error.message || 'Kunne ikke slette');
    } finally { eventDeleteBusy=false; }
}

// ========== ADDRESS FIELD FORMATTING ==========
function formatAddressFields() {
    // Format Street Address - Title Case
    document.querySelectorAll('.address-field').forEach(field => {
        field.addEventListener('blur', function() {
            this.value = this.value.trim().split(' ').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        });
    });
    
    // Format City - Title Case
    document.querySelectorAll('.city-field').forEach(field => {
        field.addEventListener('blur', function() {
            this.value = this.value.trim().split(' ').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        });
    });
    
    // Format Zip - Digits Only
    document.querySelectorAll('.zip-field').forEach(field => {
        field.addEventListener('blur', function() {
            this.value = this.value.trim().replace(/\D/g, '').slice(0, 4);
        });
    });
}

// Run formatting on page load
document.addEventListener('DOMContentLoaded', formatAddressFields);

// ========== CHECK-IN FUNCTIONALITY ==========

let selectedEventId = null;
let availablePaymentMethods = [];
let selectedForGroup = new Set();

// Load available payment methods from economy configuration
async function loadPaymentMethods(eventId = '') {
    availablePaymentMethods=[];
    try {
        const response = await fetch('/api/get_payment_methods.php'+(eventId ? '?event_id='+encodeURIComponent(eventId) : ''));
        const result = await response.json();
        if (result.success && result.payment_methods && result.payment_methods.length > 0) {
            availablePaymentMethods = result.payment_methods;
        } else {
            availablePaymentMethods = [];
        }
    } catch (error) {
        console.error('Error loading payment methods:', error);
        availablePaymentMethods = [];
    }
}

// Generate payment method buttons HTML
function getPaymentButtonsHtml(checkinId, customerName, ticketPrice, isPaid) {
    if (isPaid) {
        return `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Allerede betalt</span>
                <button class="btn btn-sm btn-outline-success ms-2" onclick="performCheckinPrepaid('${checkinId}')" title="Sjekk inn (allerede betalt)">
                    <i class="bi bi-check-circle"></i> Sjekk inn
                </button>`;
    }
    
    const escapedName = customerName.replace(/'/g, "\\'");
    if (availablePaymentMethods.length === 0 && Number(ticketPrice) > 0) return '<span class="text-warning small">Ingen tilgjengelige betalingsmetoder. Last betalingslisten på nytt.</span>';
    const defaultMethod = availablePaymentMethods.length > 0 ? availablePaymentMethods[0].method_type : '';
    
    let buttons = `<button class="btn btn-sm btn-success me-1" onclick="performCheckinWithMethod('${checkinId}', '${defaultMethod}')" title="Sjekk inn">
        <i class="bi bi-check-circle me-1"></i>Sjekk inn
    </button>`;
    
    if (availablePaymentMethods.length > 1) {
        buttons += '<div class="btn-group me-1" role="group">';
        availablePaymentMethods.forEach((pm, index) => {
            if (index === 0) return;
            const icon = pm.config?.icon || 'wallet2';
            const name = pm.method_name || pm.method_type;
            let btnClass = 'btn-outline-secondary';
            
            if (pm.method_type === 'vipps') btnClass = 'btn-outline-warning';
            else if (pm.method_type === 'cash') btnClass = 'btn-outline-success';
            else if (pm.method_type === 'stripe') btnClass = 'btn-outline-primary';
            else if (pm.method_type === 'paypal') btnClass = 'btn-outline-info';
            
            buttons += `<button class="btn btn-sm ${btnClass}" onclick="performCheckinWithMethod('${checkinId}', '${pm.method_type}')" title="${name}">
                <i class="bi bi-${icon}"></i>
            </button>`;
        });
        buttons += '</div>';
    }
    
    buttons += `<button class="btn btn-sm btn-outline-dark ms-1" onclick="markNoShow('${checkinId}', '${escapedName}')" title="No-show">
        <i class="bi bi-x-circle"></i>
    </button>`;
    
    return buttons;
}

// Toggle selection for group checkout
function toggleGroupSelection(checkinId, isPaid) {
    if (isPaid) return; // Don't allow selecting already paid guests
    
    if (selectedForGroup.has(checkinId)) {
        selectedForGroup.delete(checkinId);
    } else {
        selectedForGroup.add(checkinId);
    }
    updateGroupSelectionUI();
}

// Update UI to show group selection state
function updateGroupSelectionUI() {
    document.querySelectorAll('.group-select-checkbox').forEach(cb => {
        cb.checked = selectedForGroup.has(cb.dataset.checkinId);
    });
    
    const groupBtn = document.getElementById('create-group-btn');
    if (groupBtn) {
        groupBtn.style.display = selectedForGroup.size >= 2 ? 'inline-flex' : 'none';
        groupBtn.querySelector('.badge').textContent = selectedForGroup.size;
    }
}

// Load events into check-in selector as cards
async function loadCheckinEventSelector() {
    // Also load payment methods when entering check-in section
    await loadPaymentMethods();
    
    const cardsContainer = document.getElementById('checkin-event-cards');
    
    try {
        const response = await fetch('/api/get_events.php');
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        const result = await response.json();
        
        if (result.success && result.events && result.events.length > 0) {
            cardsContainer.innerHTML = result.events.map(event => {
                const dateStr = formatNorskDato(event.start_date, event.start_time, { includeTime: true });
                
                const escapedTitle = event.title.replace(/'/g, "\\'").replace(/"/g, '\\"');
                const escapedDateStr = dateStr.replace(/'/g, "\\'");
                
                const eventImageUrl = safeEventImageUrl(event.event_image_url);
                const hasImage = eventImageUrl !== '';
                const imageStyle = hasImage 
                    ? `background-image: url(&quot;${eventImageAttribute(eventImageUrl)}&quot;); background-size: cover; background-position: center;`
                    : 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);';
                
                const pendingBadge = event.pending_registrations > 0 
                    ? `<span class="badge bg-warning text-dark ms-1" title="Ventende godkjenning">${event.pending_registrations}</span>`
                    : '';
                
                return `
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="checkin-event-card" onclick="openEventDetail('${event.id}', '${escapedTitle}', '${escapedDateStr}')">
                            <div class="card-img-top d-flex align-items-center justify-content-center" style="${imageStyle}">
                                ${!hasImage ? '<i class="bi bi-calendar-event text-white" style="font-size: 2.5rem; opacity: 0.8;"></i>' : ''}
                            </div>
                            <div class="card-body">
                                <div class="event-title text-truncate">${event.title}</div>
                                <div class="event-date">
                                    <i class="bi bi-clock me-1"></i>${dateStr}
                                </div>
                                <div class="event-stats">
                                    <div class="stat-item">
                                        <i class="bi bi-people"></i>
                                        <span>${event.current_attendees || 0} godkjent${pendingBadge}</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="bi bi-check-circle"></i>
                                        <span>${event.checked_in_count || 0}</span> innsjekket
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Load check-in counts for each event
            result.events.forEach(event => {
                loadEventCheckInCount(event.id);
            });
        } else {
            cardsContainer.innerHTML = `
                <div class="col-12">
                    <div class="checkin-empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <p class="mb-0">Ingen arrangementer tilgjengelig</p>
                    </div>
                </div>`;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        cardsContainer.innerHTML = `
            <div class="col-12">
                <div class="checkin-empty-state">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="mb-0 text-danger">Feil ved lasting av arrangementer</p>
                </div>
            </div>`;
    }
}

async function loadEventCheckInCount(eventId) {
    try {
        const response = await fetch(`/api/get_event_checkins.php?event_id=${eventId}`);
        const result = await response.json();
        if (result.success && result.summary) {
            const el = document.getElementById(`checked-${eventId}`);
            if (el) el.textContent = result.summary.checked_in_count || 0;
        }
    } catch (error) {
        console.error('Error loading check-in count:', error);
    }
}

let currentEventTitle = '';
let currentEventDate = '';

async function openEventDetail(eventId, eventTitle, eventDate) {
    selectedEventId = eventId;
    currentEventTitle = eventTitle;
    currentEventDate = eventDate || '';
    
    document.getElementById('selected-event-title').textContent = eventTitle;
    document.getElementById('selected-event-date').textContent = eventDate || '';
    document.getElementById('checkin-event-selector').style.display = 'none';
    document.getElementById('event-details-panel').style.display = 'block';
    
    // Focus on barcode scanner
    setTimeout(() => {
        document.getElementById('checkin-barcode-search')?.focus();
    }, 100);
    
    // Load checkins and event details
    await loadPaymentMethods(eventId);
    loadCheckins(eventId);
    loadEventDetailsForCheckin(eventId);
    loadEventRegistrationFields(eventId);
}

function goBackToEventList() {
    selectedEventId = null;
    currentEventTitle = '';
    currentEventDate = '';
    document.getElementById('event-details-panel').style.display = 'none';
    document.getElementById('checkin-event-selector').style.display = 'block';
    document.getElementById('checkin-search-results').style.display = 'none';
    document.getElementById('checkin-barcode-search').value = '';
    loadCheckinEventSelector();
}

async function loadEventDetailsForCheckin(eventId) {
    try {
        const response = await fetch(`/api/get_event.php?id=${eventId}`);
        const result = await response.json();
        if (result.success && result.event) {
            document.getElementById('guest-price').value = result.event.ticket_price || 0;
            const paymentMethodSelect = document.getElementById('guest-payment-method');
            paymentMethodSelect.innerHTML = '';
            paymentMethodSelect.required=Number(result.event.ticket_price || 0)>0;
            const methods=await ActivePaymentMethods.load(eventId);
            paymentMethodSelect.append(new Option(methods.length ? '— Velg betalingsmetode —' : 'Ingen aktivert betalingsmetode',''));
            methods.forEach(method => {
                const option = document.createElement('option');
                option.value = method.method_type;
                option.textContent = ActivePaymentMethods.label(method);
                if (method.method_type === ActivePaymentMethods.normalize(result.event.default_payment_method || '')) option.selected = true;
                paymentMethodSelect.appendChild(option);
            });
            loadMembershipPlansForGuest(eventId);
        }
    } catch (error) {
        console.error('Error loading event details:', error);
    }
}

// Handle payment toggle
document.getElementById('isPayingCheckbox')?.addEventListener('change', function(e) {
    const paymentContainer = document.getElementById('payment-method-container');
    const paymentSelect = document.querySelector('[name="payment_method"]');
    
    if (e.target.checked) {
        paymentContainer.style.display = 'block';
        paymentSelect.required = true;
    } else {
        paymentContainer.style.display = 'none';
        paymentSelect.required = false;
    }
});

// Check if email matches staff member
document.querySelector('[name="attendee_email"]')?.addEventListener('blur', async function(e) {
    const email = e.target.value.trim();
    if (!email) return;
    
    try {
        const response = await fetch(`../api/check_staff.php?email=${encodeURIComponent(email)}&company_id=<?= $_SESSION['company_id'] ?>`);
        const result = await response.json();
        
        if (result.is_staff) {
            document.getElementById('staff-info').style.display = 'block';
            document.getElementById('staff-info-text').textContent = `${result.name} er registrert som ansatt`;
            document.querySelector('[name="ticket_type"]').value = 'staff';
        } else {
            document.getElementById('staff-info').style.display = 'none';
        }
    } catch (error) {
        console.error('Error checking staff:', error);
    }
});


// Load membership plans for guest registration
async function loadMembershipPlansForGuest(eventId) {
    try {
        const response = await fetch(`/api/get_event_membership_pricing.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success && result.pricing && result.pricing.length > 0) {
            const container = document.getElementById('guest-membership-container');
            const select = document.getElementById('guest-membership-plan');
            
            // Clear existing options except the first "None" option
            select.innerHTML = '<option value="">Ingen (ordinær pris)</option>';
            
            // Add membership plan options
            result.pricing.forEach(plan => {
                const option = document.createElement('option');
                option.value = plan.membership_plan_id;
                option.textContent = `${plan.plan_name} (${plan.ticket_price} kr)`;
                option.dataset.price = plan.ticket_price;
                select.appendChild(option);
            });
            
            // Show container
            container.style.display = 'block';
            
            // Update price when membership plan changes
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.dataset.price) {
                    document.getElementById('guest-price').value = selectedOption.dataset.price;
                } else {
                    // Reset to default event price
                    fetch(`/api/get_event.php?id=${eventId}`)
                        .then(r => r.json())
                        .then(d => {
                            if (d.success && d.event) {
                                document.getElementById('guest-price').value = d.event.ticket_price || 0;
                            }
                        });
                }
            });
        } else {
            // Hide membership container if no plans
            document.getElementById('guest-membership-container').style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading membership plans:', error);
    }
}

// Handle quick guest registration
document.getElementById('quick-guest-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!selectedEventId) {
        toast.quickWarning('Validering', 'Velg et arrangement først');
        return;
    }
    
    const numberOfPeople = parseInt(document.getElementById('guest-number-of-people').value) || 1;
    
    if (numberOfPeople < 1 || numberOfPeople > 10) {
        toast.quickWarning('Validering', 'Antall personer må være mellom 1 og 10');
        return;
    }

    const firstName = document.getElementById('guest-fornavn').value.trim();
    const lastName = document.getElementById('guest-etternavn').value.trim();
    if (!firstName || !lastName) {
        toast.quickWarning('Validering', 'Fornavn og etternavn er påkrevd.');
        return;
    }
    
    const formData = {
        event_id: selectedEventId,
        attendee_first_name: firstName,
        attendee_last_name: lastName,
        attendee_email: document.getElementById('guest-email').value.trim() || null,
        membership_plan_id: document.getElementById('guest-membership-plan').value || null,
        payment_method: document.getElementById('guest-payment-method').value,
        payment_status: document.getElementById('guest-payment-status').value,
        number_of_people: numberOfPeople,
        optional_fields: collectDynamicRegistrationValues()
    };
    
    try {
        const response = await fetch('/api/register_guest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reset form
            document.getElementById('quick-guest-form').reset();
            document.getElementById('guest-number-of-people').value = '1';
            
            // Reload event price
            try {
                const eventResponse = await fetch(`/api/get_event.php?id=${selectedEventId}`);
                const eventResult = await eventResponse.json();
                if (eventResult.success && eventResult.event) {
                    document.getElementById('guest-price').value = eventResult.event.ticket_price || 0;
                }
            } catch (error) {
                console.error('Error reloading event price:', error);
            }
            
            // Reload checkins
            await loadCheckins(selectedEventId);
            
            const messageText = numberOfPeople > 1 
                ? `${numberOfPeople} gjester registrert!` 
                : 'Gjest registrert!';
            toast.quickSuccess('Registrert', messageText);
        } else {
            // Handle duplicate registration with specific Norwegian status message
            if (result.is_duplicate) {
                const statusText = result.existing_status_text || 
                    (result.existing_status === 'approved' ? 'godkjent' :
                     result.existing_status === 'pending' ? 'ventende' :
                     result.existing_status === 'rejected' ? 'avslått' :
                     result.existing_status === 'paid' ? 'godkjent' : result.existing_status);
                toast.quickWarning('Allerede registrert', `Status: ${statusText}`);
            } else {
                toast.quickError('Feil', result.message || 'Kunne ikke registrere gjest');
            }
        }
    } catch (error) {
        console.error('Error registering guest:', error);
        toast.quickError('Feil', 'Kunne ikke registrere gjest');
    }
});

// Load and display dynamic registration fields
async function loadEventRegistrationFields(eventId) {
    const builtIns = {
        first_name: {control: document.getElementById('guest-fornavn'), label: 'Fornavn', required: true},
        last_name: {control: document.getElementById('guest-etternavn'), label: 'Etternavn', required: true},
        email: {control: document.getElementById('guest-email'), label: 'E-post', required: false}
    };
    Object.values(builtIns).forEach(item => {
        if (!item.control) return;
        const previousDefault = item.control.dataset.registrationDefault;
        if (previousDefault !== undefined) {
            if (item.control.value === previousDefault) item.control.value = '';
            item.control.defaultValue = '';
            delete item.control.dataset.registrationDefault;
        }
        item.control.required = item.required;
        const label = item.control.parentElement?.querySelector('label');
        if (label) label.textContent = item.label + (item.required ? ' *' : '');
    });
    try {
        const response = await fetch(`/api/get_event_fields.php?event_id=${encodeURIComponent(eventId)}`);
        const result = await response.json();
        
        const container = document.getElementById('dynamic-registration-fields');
        if (!container || String(selectedEventId) !== String(eventId)) return;
        container.replaceChildren();
        if (!response.ok || !result?.success || !Array.isArray(result.fields)) return;

        const definitions = result.fields.filter(field => {
            const visibility = field.is_visible;
            const visible = visibility === undefined || ![false, 0, '0', 'false'].includes(visibility);
            return visible;
        });
        if (!definitions.length) return;

        const row = document.createElement('div');
        row.className = 'row g-2';
        definitions.forEach((field, index) => {
            const fieldName = String(field.field_name || '').trim();
            if (!fieldName) return;
            const canonicalName = fieldName.toLowerCase();
            const required = field.is_required === true || field.is_required === 1 || field.is_required === '1' || field.is_required === 'true';
            if (builtIns[canonicalName]) {
                const builtIn = builtIns[canonicalName];
                const control = builtIn.control;
                if (control) {
                    control.required = builtIn.required || required;
                    if (field.default_value !== undefined && field.default_value !== null && control.value.trim() === '') {
                        control.value = String(field.default_value);
                        control.defaultValue = String(field.default_value);
                        control.dataset.registrationDefault = String(field.default_value);
                    }
                    const label = control.parentElement?.querySelector('label');
                    if (label) label.textContent = `${String(field.field_label || builtIn.label)}${control.required ? ' *' : ''}`;
                }
                return;
            }
            const fieldType = ['text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'checkbox'].includes(field.field_type)
                ? field.field_type : 'text';
            const safeId = `guest-dynamic-${fieldName.replace(/[^a-zA-Z0-9_-]/g, '-')}-${index}`;
            const wrapper = document.createElement('div');
            wrapper.className = 'col-12 col-md-6 mb-2';
            const label = document.createElement('label');
            label.className = fieldType === 'checkbox' ? 'form-check-label small' : 'form-label small mb-1';
            label.htmlFor = safeId;
            label.textContent = `${String(field.field_label || fieldName)}${required ? ' *' : ''}`;

            let control;
            if (fieldType === 'textarea') {
                control = document.createElement('textarea');
                control.rows = 2;
            } else if (fieldType === 'select') {
                control = document.createElement('select');
                control.appendChild(new Option('Velg…', ''));
                let options = Array.isArray(field.options) ? field.options : [];
                if (!options.length && typeof field.options === 'string') {
                    try { options = JSON.parse(field.options); } catch (_) { options = []; }
                }
                options.forEach(option => {
                    const value = String(option && typeof option === 'object' ? (option.value ?? option.label ?? '') : option ?? '');
                    const optionLabel = String(option && typeof option === 'object' ? (option.label ?? value) : value);
                    control.appendChild(new Option(optionLabel, value));
                });
            } else {
                control = document.createElement('input');
                control.type = fieldType;
            }

            control.id = safeId;
            control.name = `dynamic_registration_fields[${fieldName}]`;
            control.dataset.dynamicFieldName = fieldName;
            control.className = fieldType === 'checkbox' ? 'form-check-input me-2' : 'form-control form-control-sm';
            control.required = required;
            const defaultValue = field.default_value ?? '';
            if (fieldType === 'checkbox') {
                const checked = defaultValue === true || defaultValue === 1 || ['1', 'true', 'yes', 'on'].includes(String(defaultValue).toLowerCase());
                control.defaultChecked = checked;
                control.checked = checked;
                const checkWrap = document.createElement('div');
                checkWrap.className = 'form-check';
                checkWrap.append(control, label);
                wrapper.appendChild(checkWrap);
            } else {
                control.value = String(defaultValue);
                control.defaultValue = String(defaultValue);
                if (fieldType === 'select') {
                    Array.from(control.options).forEach(option => option.defaultSelected = option.value === String(defaultValue));
                }
                wrapper.append(label, control);
            }

            let rules = field.validation_rules;
            if (typeof rules === 'string') {
                try { rules = JSON.parse(rules); } catch (_) { rules = {}; }
            }
            rules = rules && typeof rules === 'object' ? rules : {};
            const maxLength = rules.max_length ?? rules.maxLength;
            const minLength = rules.min_length ?? rules.minLength;
            if (rules.min !== undefined && ['number', 'date'].includes(fieldType)) control.min = String(rules.min);
            if (rules.max !== undefined && ['number', 'date'].includes(fieldType)) control.max = String(rules.max);
            const lengthCapable = ['text', 'email', 'tel', 'textarea'].includes(fieldType);
            if (lengthCapable && Number.isFinite(Number(maxLength)) && Number(maxLength) > 0) control.maxLength = Number(maxLength);
            if (lengthCapable && Number.isFinite(Number(minLength)) && Number(minLength) > 0) control.minLength = Number(minLength);
            if (['text', 'email', 'tel'].includes(fieldType) && typeof rules.pattern === 'string' && rules.pattern.length <= 256) {
                try { new RegExp(rules.pattern); control.pattern = rules.pattern; } catch (_) { /* Ignore invalid legacy patterns safely. */ }
            }
            row.appendChild(wrapper);
        });
        container.appendChild(row);
    } catch (error) {
        console.error('Error loading event fields:', error);
    }
}

function collectDynamicRegistrationValues() {
    const values = Object.create(null);
    document.querySelectorAll('#dynamic-registration-fields [data-dynamic-field-name]').forEach(control => {
        values[control.dataset.dynamicFieldName] = control.type === 'checkbox' ? (control.checked ? 1 : 0) : control.value;
    });
    return values;
}

// Attendee scanner functionality
let scannerTimeout;
document.getElementById('attendee-scanner-input')?.addEventListener('input', function(e) {
    const scanInput = e.target.value.trim();
    
    // Clear previous timeout
    clearTimeout(scannerTimeout);
    
    // Only search if 3+ characters
    if (scanInput.length < 3) {
        return;
    }
    
    // Add visual feedback
    this.style.borderColor = '#ffc107';
    this.style.boxShadow = '0 0 0 0.2rem rgba(255, 193, 7, 0.25)';
    
    // Debounce search
    scannerTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../api/search_attendee.php?query=${encodeURIComponent(scanInput)}&company_id=<?= $_SESSION['company_id'] ?>`);
            const result = await response.json();
            
            if (result.success && result.attendee) {
                // Fill form with attendee data
                document.querySelector('[name="attendee_name"]').value = result.attendee.name || '';
                document.querySelector('[name="attendee_email"]').value = result.attendee.email || '';
                document.querySelector('[name="attendee_phone"]').value = result.attendee.phone || '';
                
                // Success feedback
                e.target.style.borderColor = '#28a745';
                e.target.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                
                // Clear scanner after 1 second
                setTimeout(() => {
                    e.target.value = '';
                    e.target.style.borderColor = '';
                    e.target.style.boxShadow = '';
                }, 1000);
            } else {
                // Not found feedback
                e.target.style.borderColor = '#dc3545';
                e.target.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
                
                setTimeout(() => {
                    e.target.style.borderColor = '';
                    e.target.style.boxShadow = '';
                }, 2000);
            }
        } catch (error) {
            console.error('Error searching attendee:', error);
            e.target.style.borderColor = '#dc3545';
            e.target.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
        }
    }, 300);
});

// Check-in form submission
document.getElementById('checkin-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!selectedEventId) {
        toast.quickWarning('Validering', 'Velg et arrangement først');
        return;
    }
    
    const formData = new FormData(this);
    const isPaying = document.getElementById('isPayingCheckbox').checked;
    const data = {
        event_id: selectedEventId,
        attendee_first_name: formData.get('attendee_first_name') || '',
        attendee_last_name: formData.get('attendee_last_name') || '',
        attendee_email: formData.get('attendee_email'),
        customer_id: formData.get('customer_id') || null,
        registration_id: formData.get('registration_id') || null,
        membership_plan_id: formData.get('membership_plan_id') || null,
        ticket_type: formData.get('ticket_type'),
        payment_method: isPaying ? formData.get('payment_method') : null,
        is_paying: isPaying,
        notes: formData.get('notes')
    };
    
    // Include all dynamic field values
    for (const [key, value] of formData.entries()) {
        if (key.startsWith('field_')) {
            data[key] = value;
        }
    }
    
    try {
        const response = await fetch('/api/checkin_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Innsjekking', 'Deltaker sjekket inn!');
            this.reset();
            loadCheckins(selectedEventId);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke sjekke inn');
        }
    } catch (error) {
        console.error('Error checking in:', error);
        toast.quickError('Innsjekking', 'Feil ved innsjekking');
    }
});

// Load check-ins for selected event
function eventFormEscapeText(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[character]));
}

function eventSubmittedFieldDetails(fields) {
    const extra = Array.isArray(fields) ? fields.filter(field =>
        !['first_name', 'last_name', 'email'].includes(field.field_name)) : [];
    if (!extra.length) return '';
    const entries = extra.map(field => {
        let value = field.value ?? '';
        if (field.field_type === 'checkbox') value = String(value) === '1' ? 'Ja' : 'Nei';
        if (field.field_type === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(String(value))) value = String(value).split('-').reverse().join('.');
        return `<dt>${eventFormEscapeText(field.field_label)}</dt><dd>${eventFormEscapeText(value)}</dd>`;
    }).join('');
    return `<details class="small mt-1"><summary>Skjemasvar</summary><dl class="mb-0">${entries}</dl></details>`;
}

async function loadCheckins(eventId) {
    try {
        const response = await fetch(`/api/get_event_checkins.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            // Update statistics with new fields
            document.getElementById('checkin-total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('checkin-checked-in-stat').textContent = result.summary.checked_in_count;
            document.getElementById('checkin-noshow-count').textContent = result.summary.no_show_count || 0;
            document.getElementById('checkin-pending-count').textContent = result.summary.not_checked_in_count;
            document.getElementById('checkin-pending-count-stat').textContent = result.summary.not_checked_in_count;
            document.getElementById('checkin-revenue').textContent = result.summary.checkin_revenue.toFixed(0) + ' kr';
            document.getElementById('checkin-expected-revenue').textContent = result.summary.expected_revenue.toFixed(0) + ' kr';
            
            // Payment method names for display
            const paymentMethodNames = {
                'cash': 'Kontant',
                'card': 'Kort',
                'vipps': 'Vipps',
                'invoice': 'Faktura',
                'bank_transfer': 'Bank',
                'on_arrival': 'Ved oppmøte',
                'free': 'Gratis',
                'no_show': 'No-show'
            };
            
            // Update confirmed list (ankommet/innsjekket)
            const confirmedList = document.getElementById('checkin-confirmed-list');
            const confirmedCount = document.getElementById('checkin-confirmed-count');
            
            if (result.checked_in && result.checked_in.length > 0) {
                confirmedCount.textContent = result.checked_in.length;
                confirmedList.innerHTML = result.checked_in.map(checkin => {
                    const customerName = checkin.customer_name || 'Ukjent';
                    const customerEmail = checkin.customer_email || '';
                    const checkinTime = checkin.check_in_time 
                        ? (checkin.check_in_time ? checkin.check_in_time.substring(11, 16) || new Date(checkin.check_in_time).toLocaleTimeString('no-NO', {hour: '2-digit', minute: '2-digit', hour12: false}) : '') 
                        : '-';
                    const membershipPlanName = checkin.membership_plan_name || null;
                    const isNoShow = checkin.payment_status === 'no_show';
                    const isMember = membershipPlanName !== null;
                    
                    // Type badge with membership plan name
                    const typeBadge = isMember 
                        ? `<span class="ticket-badge member"><i class="bi bi-star-fill"></i>${membershipPlanName}</span>` 
                        : `<span class="ticket-badge regular">Ordinær</span>`;
                    
                    // Payment badge
                    let paymentBadge;
                    if (isNoShow) {
                        paymentBadge = '<span class="badge bg-dark"><i class="bi bi-x-circle me-1"></i>No-show</span>';
                    } else if (checkin.payment_status === 'paid') {
                        paymentBadge = `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>${paymentMethodNames[checkin.payment_method] || checkin.payment_method}</span>`;
                    } else {
                        paymentBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Venter</span>';
                    }
                    
                    return `
                        <tr class="${isNoShow ? 'table-secondary' : ''}">
                            <td>
                                <div class="attendee-name">${eventFormEscapeText(customerName)}</div>
                                ${customerEmail ? `<div class="attendee-email">${eventFormEscapeText(customerEmail)}</div>` : ''}
                                ${eventSubmittedFieldDetails(checkin.submitted_fields)}
                            </td>
                            <td>${typeBadge}</td>
                            <td><span class="price-display">${isNoShow ? '<s class="text-muted">' + checkin.ticket_price + ' kr</s>' : checkin.ticket_price + ' kr'}</span></td>
                            <td>${paymentBadge}</td>
                            <td><span class="text-muted">${checkinTime}</span></td>
                        </tr>
                    `;
                }).join('');
            } else {
                confirmedCount.textContent = '0';
                confirmedList.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="checkin-empty-state">
                                <i class="bi bi-inbox"></i>
                                <p class="mb-0">Ingen ankommet ennå</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
            
            // Update pending list (ikke innsjekket)
            const pendingList = document.getElementById('checkin-pending-list');
            const pendingTabCount = document.querySelector('#checkin-tabs .nav-link:nth-child(2) .badge');
            
            // Store checkins for group selection
            window.pendingCheckins = result.not_checked_in || [];
            
            if (result.not_checked_in && result.not_checked_in.length > 0) {
                if (pendingTabCount) pendingTabCount.textContent = result.not_checked_in.length;
                pendingList.innerHTML = result.not_checked_in.map(checkin => {
                    const customerName = checkin.customer_name || 'Ukjent';
                    const customerEmail = checkin.customer_email || '';
                    const membershipPlanName = checkin.membership_plan_name || null;
                    const isMember = membershipPlanName !== null;
                    const isPaid = checkin.payment_status === 'paid';
                    
                    // Type badge with membership plan name
                    const typeBadge = isMember 
                        ? `<span class="ticket-badge member"><i class="bi bi-star-fill"></i>${membershipPlanName}</span>` 
                        : `<span class="ticket-badge regular">Ordinær</span>`;
                    
                    // Payment status badge
                    const paymentStatusBadge = isPaid 
                        ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Betalt</span>'
                        : '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Ikke betalt</span>';
                    
                    // Dynamic payment buttons
                    const paymentButtons = getPaymentButtonsHtml(checkin.id, customerName, checkin.ticket_price, isPaid);
                    
                    // Group selection checkbox (only for unpaid)
                    const groupCheckbox = isPaid ? '' : `
                        <input type="checkbox" class="form-check-input group-select-checkbox me-2" 
                               data-checkin-id="${checkin.id}" data-price="${checkin.ticket_price}"
                               data-name="${eventFormEscapeText(customerName)}"
                               onchange="toggleGroupSelection('${checkin.id}', ${isPaid})"
                               ${selectedForGroup.has(checkin.id) ? 'checked' : ''}>`;
                    
                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    ${groupCheckbox}
                                    <div>
                                        <div class="attendee-name">${eventFormEscapeText(customerName)}</div>
                                        ${customerEmail ? `<div class="attendee-email">${eventFormEscapeText(customerEmail)}</div>` : ''}
                                        ${eventSubmittedFieldDetails(checkin.submitted_fields)}
                                    </div>
                                </div>
                            </td>
                            <td>${typeBadge}</td>
                            <td><span class="price-display">${checkin.ticket_price} kr</span></td>
                            <td>${paymentStatusBadge}</td>
                            <td>${paymentButtons}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                if (pendingTabCount) pendingTabCount.textContent = '0';
                pendingList.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="checkin-empty-state">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <p class="mb-0">Alle er innsjekket!</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading check-ins:', error);
    }
}

// Perform check-in with payment
async function performCheckin(checkinId, skipPayment = false) {
    const paymentMethod = document.getElementById('guest-payment-method')?.value || null;
    
    try {
        const response = await fetch('/api/perform_checkin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                checkin_id: checkinId,
                payment_method: paymentMethod,
                skip_payment: skipPayment
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Innsjekking', `${result.customer_name} sjekket inn!`);
            loadCheckins(selectedEventId);
            document.getElementById('checkin-barcode-search').value = '';
            document.getElementById('checkin-barcode-search').focus();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke sjekke inn');
        }
    } catch (error) {
        console.error('Error performing check-in:', error);
        toast.quickError('Feil', 'Kunne ikke sjekke inn');
    }
}

// Perform check-in for already-paid guests (preserves original payment method)
async function performCheckinPrepaid(checkinId) {
    try {
        const response = await fetch('/api/perform_checkin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                checkin_id: checkinId,
                skip_payment: true,
                preserve_payment_method: true
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Innsjekking', `${result.customer_name} sjekket inn!`);
            loadCheckins(selectedEventId);
            selectedForGroup.clear();
            updateGroupSelectionUI();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke sjekke inn');
        }
    } catch (error) {
        console.error('Error performing check-in:', error);
        toast.quickError('Feil', 'Kunne ikke sjekke inn');
    }
}

// Perform check-in with specific payment method
async function performCheckinWithMethod(checkinId, paymentMethod) {
    try {
        const response = await fetch('/api/perform_checkin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                checkin_id: checkinId,
                payment_method: paymentMethod,
                skip_payment: paymentMethod === 'free'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const methodName = paymentMethod === 'free' ? '' : ` (${paymentMethod})`;
            toast.quickSuccess('Innsjekking', `${result.customer_name} sjekket inn${methodName}!`);
            loadCheckins(selectedEventId);
            selectedForGroup.clear();
            updateGroupSelectionUI();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke sjekke inn');
        }
    } catch (error) {
        console.error('Error performing check-in:', error);
        toast.quickError('Feil', 'Kunne ikke sjekke inn');
    }
}

// Mark attendee as no-show
async function markNoShow(checkinId, customerName) {
    if (!confirm(`Er du sikker på at du vil markere ${customerName} som no-show?\n\nDette flytter personen til ankommet-listen uten å registrere betaling.`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/perform_checkin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                checkin_id: checkinId,
                no_show: true
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickWarning('No-show', `${customerName} markert som no-show`);
            loadCheckins(selectedEventId);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke markere som no-show');
        }
    } catch (error) {
        console.error('Error marking no-show:', error);
        toast.quickError('Feil', 'Kunne ikke markere som no-show');
    }
}

// Barcode scanner / search functionality
let searchTimeout;
document.getElementById('checkin-barcode-search')?.addEventListener('input', function(e) {
    const searchValue = e.target.value.trim();
    clearTimeout(searchTimeout);
    
    if (searchValue.length < 2) {
        document.getElementById('checkin-search-results').style.display = 'none';
        return;
    }
    
    // Visual feedback during search
    this.style.borderColor = '#ffc107';
    
    searchTimeout = setTimeout(async () => {
        await searchAndCheckin(searchValue);
    }, 300);
});

// Handle Enter key for quick check-in
document.getElementById('checkin-barcode-search')?.addEventListener('keypress', async function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const searchValue = this.value.trim();
        if (searchValue.length >= 2) {
            await searchAndCheckin(searchValue, true);
        }
    }
});

async function searchAndCheckin(searchValue, autoCheckin = false) {
    const searchInput = document.getElementById('checkin-barcode-search');
    const resultsContainer = document.getElementById('checkin-search-results');
    const search = searchValue.toLowerCase().trim();
    
    // Hide results if search too short
    if (search.length < 2) {
        resultsContainer.style.display = 'none';
        searchInput.style.borderColor = '';
        return;
    }
    
    try {
        const response = await fetch(`/api/get_event_checkins.php?event_id=${selectedEventId}`);
        const result = await response.json();
        
        if (result.success) {
            // Search in not_checked_in list with strict filtering
            const matches = result.not_checked_in.filter(c => {
                const name = (c.customer_name || '').toLowerCase();
                const barcode = (c.barcode || '').toLowerCase();
                const email = (c.customer_email || '').toLowerCase();
                
                // Check if search term matches any field
                const nameMatch = name.includes(search);
                const barcodeMatch = barcode.includes(search);
                const emailMatch = email.includes(search);
                
                return nameMatch || barcodeMatch || emailMatch;
            });
            
            console.log(`Search "${search}" found ${matches.length} of ${result.not_checked_in.length} total`);
            
            // Sort matches by relevance (exact barcode match first, then name starts with, then includes)
            matches.sort((a, b) => {
                const aBarcode = (a.barcode || '').toLowerCase();
                const bBarcode = (b.barcode || '').toLowerCase();
                const aName = (a.customer_name || '').toLowerCase();
                const bName = (b.customer_name || '').toLowerCase();
                
                // Exact barcode match first
                if (aBarcode === search && bBarcode !== search) return -1;
                if (bBarcode === search && aBarcode !== search) return 1;
                
                // Name starts with search term next
                if (aName.startsWith(search) && !bName.startsWith(search)) return -1;
                if (bName.startsWith(search) && !aName.startsWith(search)) return 1;
                
                // Alphabetical by name
                return aName.localeCompare(bName);
            });
            
            if (matches.length === 1 && autoCheckin) {
                // Single match with Enter key - auto check-in
                searchInput.style.borderColor = '#28a745';
                await performCheckin(matches[0].id);
                resultsContainer.style.display = 'none';
                searchInput.value = '';
                return;
            }
            
            if (matches.length > 0) {
                searchInput.style.borderColor = '#28a745';
                resultsContainer.style.display = 'block';
                
                // Show count and limit to 10 results
                const limitedMatches = matches.slice(0, 10);
                const moreText = matches.length > 10 ? `<div class="text-center text-muted small p-1">+ ${matches.length - 10} flere resultater...</div>` : '';
                
                resultsContainer.innerHTML = limitedMatches.map(m => {
                    const isMember = m.ticket_type !== 'regular';
                    const ticketLabel = isMember ? `<span class="badge bg-primary">${m.ticket_type}</span>` : '<span class="badge bg-secondary">Ordinær</span>';
                    const escapedName = (m.customer_name || 'Ukjent').replace(/'/g, "\\'");
                    
                    return `
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom search-result-item">
                        <div>
                            <strong>${m.customer_name || 'Ukjent'}</strong>
                            <small class="text-muted d-block">${ticketLabel} - ${m.ticket_price} kr</small>
                            ${m.barcode ? `<small class="text-muted font-monospace">${m.barcode}</small>` : ''}
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success" onclick="performCheckin('${m.id}'); document.getElementById('checkin-search-results').style.display='none';">
                                <i class="bi bi-check"></i> Sjekk inn
                            </button>
                            <button class="btn btn-outline-secondary" onclick="markNoShow('${m.id}', '${escapedName}'); document.getElementById('checkin-search-results').style.display='none';">
                                No-show
                            </button>
                        </div>
                    </div>
                `}).join('') + moreText;
            } else {
                searchInput.style.borderColor = '#dc3545';
                resultsContainer.style.display = 'block';
                resultsContainer.innerHTML = `<div class="text-center text-muted p-2"><i class="bi bi-search me-2"></i>Ingen treff for "${searchValue}". Prøv å søke på navn, e-post eller strekkode.</div>`;
            }
        }
    } catch (error) {
        console.error('Error searching:', error);
        searchInput.style.borderColor = '#dc3545';
        resultsContainer.style.display = 'block';
        resultsContainer.innerHTML = '<div class="text-center text-danger p-2"><i class="bi bi-exclamation-triangle me-2"></i>Feil ved søk</div>';
    }
}

// Load check-in selector when check-in tab is shown
document.addEventListener('shown.bs.tab', function(e) {
    if (e.target.id === 'events-checkin-tab' || (e.target.getAttribute('data-bs-target') === '#events-checkin')) {
        loadCheckinEventSelector();
    }
});

// Also support old click behavior as fallback
document.querySelector('[href="#events-checkin"]')?.addEventListener('click', function() {
    setTimeout(() => loadCheckinEventSelector(), 100);
});

function eventResultsEscape(value) {
    const element = document.createElement('div');
    element.textContent = value == null ? '' : String(value);
    return element.innerHTML;
}

function eventResultsMoney(value) {
    return `${new Intl.NumberFormat('nb-NO', {maximumFractionDigits: 0}).format(Number(value) || 0)} kr`;
}

// Load event results from canonical event, ledger and attendee data
async function loadEventsResults() {
    const resultsList = document.getElementById('events-results-list');
    const summaryDiv = document.getElementById('events-results-summary');
    resultsList.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Laster resultater...</div>';
    
    try {
        const response = await fetch('/api/get_events_results.php');
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || `HTTP ${response.status}`);
        
        // Show/update summary
        if (summaryDiv && result.summary) {
            summaryDiv.style.display = 'block';
            document.getElementById('summary-total-events').textContent = result.summary.total_events;
            document.getElementById('summary-total-revenue').textContent = eventResultsMoney(result.summary.total_revenue);
            document.getElementById('summary-total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('summary-upcoming-events').textContent = result.summary.upcoming_events;
            
            // New stats
            const mostProf = result.summary.most_profitable || {title: 'N/A', profit: 0};
            const mostExp = result.summary.most_expensive || {title: 'N/A', price: 0};
            const mostAtt = result.summary.most_attended || {title: 'N/A', attendees: 0};
            
            document.getElementById('summary-most-profitable').textContent = mostProf.title && mostProf.title !== 'N/A' ? mostProf.title : '-';
            document.getElementById('summary-most-profitable-value').textContent = eventResultsMoney(mostProf.profit);
            
            document.getElementById('summary-most-expensive').textContent = mostExp.title && mostExp.title !== 'N/A' ? mostExp.title : '-';
            document.getElementById('summary-most-expensive-value').textContent = eventResultsMoney(mostExp.price);
            
            document.getElementById('summary-most-attended').textContent = mostAtt.title && mostAtt.title !== 'N/A' ? mostAtt.title : '-';
            document.getElementById('summary-most-attended-value').textContent = (mostAtt.attendees || 0) + ' deltakere';
        }
        
        // Render event results as table per arrangement
        const resultsList = document.getElementById('events-results-list');
        
        if (result.events && result.events.length > 0) {
            resultsList.innerHTML = `
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Arrangement</th>
                                    <th>Dato</th>
                                    <th>Status</th>
                                    <th class="text-center">Deltakere</th>
                                    <th class="text-end">Inntekt</th>
                                    <th class="text-end">Utgift</th>
                                    <th class="text-end">Netto resultat</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${result.events.map(event => {
                                    const statusBadge = event.status === 'Kommende' ? 'bg-info' : event.status === 'Pågår' ? 'bg-warning' : 'bg-secondary';
                                    const startDate = formatNorskDato(event.start_date, event.start_time);
                                    const netResult = Number(event.net_result) || 0;
                                    const netColor = netResult >= 0 ? 'text-success' : 'text-danger';
                                    
                                    return `
                                        <tr>
                                            <td>
                                                <strong>${eventResultsEscape(event.title)}</strong>
                                            </td>
                                            <td>
                                                ${eventResultsEscape(startDate)}
                                                ${event.start_time ? `<br><small class="text-muted">kl. ${formatNorskTid(event.start_time)}</small>` : ''}
                                            </td>
                                            <td>
                                                <span class="badge ${statusBadge}">${eventResultsEscape(event.status)}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">${Number(event.attendee_count) || 0}</span>
                                            </td>
                                            <td class="text-end text-success">
                                                <strong>${eventResultsMoney(event.income)}</strong>
                                            </td>
                                            <td class="text-end text-danger">
                                                ${eventResultsMoney(event.expense)}
                                            </td>
                                            <td class="text-end fw-bold ${netColor}">
                                                ${eventResultsMoney(netResult)}
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } else {
            resultsList.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info text-center mb-0">
                        <i class="bi bi-info-circle me-2"></i>Ingen resultat tilgjengelig ennå
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading events results:', error);
        if (summaryDiv) summaryDiv.style.display = 'none';
        document.getElementById('events-results-list').innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Feil ved lasting av resultater
                </div>
            </div>
        `;
    }
}

// Results loading is now handled by subtab-changed event at bottom of file

// Toggle event results dropdown
async function toggleEventResults(eventId) {
    const resultsDiv = document.getElementById(`event-results-${eventId}`);
    if (resultsDiv) {
        const isHidden = resultsDiv.style.display === 'none';
        resultsDiv.style.display = isHidden ? 'block' : 'none';
        
        // Load finances when opening
        if (isHidden) {
            await loadEventFinances(eventId);
        }
    }
}

// Load actual paid income and expenses from unified_ledger
async function loadEventFinances(eventId) {
    try {
        const response = await fetch(`/api/get_event_finances.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            const incomeList = document.getElementById(`event-income-${eventId}`);
            const expenseList = document.getElementById(`event-expense-${eventId}`);
            const incomeGroups = result.income_groups || result.checkin_income || [];
            
            let incomeHtml = '';
            
            if (incomeGroups.length > 0) {
                incomeGroups.forEach(group => {
                    incomeHtml += `
                        <div class="event-finance-item">
                            <div>
                                <span><strong>${eventResultsEscape(group.label)}</strong></span>
                                <small class="text-muted d-block">${group.count} stk @ ${group.price.toFixed(0)} NOK</small>
                            </div>
                            <strong class="text-success">+${group.total.toFixed(2)} NOK</strong>
                        </div>
                    `;
                });
            }
            
            incomeList.innerHTML = incomeHtml || '<small class="text-muted">Ingen inntekter registrert</small>';
            
            if (result.expense_groups && Object.keys(result.expense_groups).length > 0) {
                expenseList.innerHTML = Object.entries(result.expense_groups)
                    .sort((a, b) => b[1].total - a[1].total)
                    .map(([name, data]) => `
                        <div class="event-finance-item">
                            <div>
                                <span>${eventResultsEscape(name)}</span>
                                <small class="text-muted d-block">${data.count} stk</small>
                            </div>
                            <strong class="text-danger">-${data.total.toFixed(2)} NOK</strong>
                        </div>
                    `).join('');
            } else {
                expenseList.innerHTML = '<small class="text-muted">Ingen utgifter registrert</small>';
            }
            
            // Update summary
            document.getElementById(`event-total-income-${eventId}`).textContent = `${result.summary.total_income.toFixed(2)} NOK`;
            document.getElementById(`event-total-expense-${eventId}`).textContent = `${result.summary.total_expense.toFixed(2)} NOK`;
            
            const netResultEl = document.getElementById(`event-net-result-${eventId}`);
            netResultEl.textContent = `${result.summary.net_result.toFixed(2)} NOK`;
            netResultEl.className = result.summary.net_result >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
        }
    } catch (error) {
        console.error('Error loading event finances:', error);
    }
}

// Add event income
async function addEventIncome(eventId) {
    const description = prompt('Beskrivelse av inntekt:');
    if (!description) return;
    
    const amount = parseFloat(prompt('Beløp (NOK):'));
    if (!amount || amount <= 0) {
        toast.quickWarning('Validering', 'Ugyldig beløp');
        return;
    }
    
    try {
        const paymentMethod=await ActivePaymentMethods.choose();
        if (!paymentMethod) return;
        const response = await fetch('/api/add_event_finance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: eventId,
                type: 'income',
                item_name: description,
                amount: amount,
                payment_method: paymentMethod
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Inntekt', 'Inntekt lagt til');
            // Reload finances to show updated results
            await new Promise(resolve => setTimeout(resolve, 300));
            await loadEventFinances(eventId);
        } else {
            toast.quickError('Feil', result.message);
        }
    } catch (error) {
        console.error('Error adding income:', error);
        toast.quickError('Inntekt', 'Feil ved lagring av inntekt');
    }
}

// Add event expense
async function addEventExpense(eventId) {
    const description = prompt('Beskrivelse av utgift:');
    if (!description) return;
    
    const amount = parseFloat(prompt('Beløp (NOK):'));
    if (!amount || amount <= 0) {
        toast.quickWarning('Validering', 'Ugyldig beløp');
        return;
    }
    
    try {
        const paymentMethod=await ActivePaymentMethods.choose();
        if (!paymentMethod) return;
        const response = await fetch('/api/add_event_finance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: eventId,
                type: 'expense',
                item_name: description,
                amount: amount,
                payment_method: paymentMethod
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Utgift', 'Utgift lagt til');
            // Reload finances to show updated results
            await new Promise(resolve => setTimeout(resolve, 300));
            await loadEventFinances(eventId);
        } else {
            toast.quickError('Feil', result.message);
        }
    } catch (error) {
        console.error('Error adding expense:', error);
        toast.quickError('Utgift', 'Feil ved lagring av utgift');
    }
}

// Edit event function
let editMembershipPricingLoaded = false;
let editMembershipPricingEventId = null;
let editEventModalReady = false;
let editEventSaveBusy = false;
let editEventDirty = false;

async function loadEditMembershipPricing(eventId) {
    const container = document.getElementById('edit-membership-pricing-container');
    if (!container) return;
    editMembershipPricingLoaded = false;
    editMembershipPricingEventId = String(eventId);
    container.dataset.membershipState = 'loading';
    container.innerHTML = '<div class="events-pricing-loading"><span></span><span></span></div>';
    try {
        const [plansResponse, pricingResponse] = await Promise.all([
            fetch('/api/get_membership_plans.php', {credentials:'same-origin'}),
            fetch(`/api/get_event_membership_pricing.php?event_id=${encodeURIComponent(eventId)}`, {credentials:'same-origin'})
        ]);
        if (!plansResponse.ok || !pricingResponse.ok) throw new Error('Medlemspriser kunne ikke lastes.');
        const [plansResult, pricingResult] = await Promise.all([plansResponse.json(),pricingResponse.json()]);
        if (!plansResult.success || !Array.isArray(plansResult.plans) || !pricingResult.success || !Array.isArray(pricingResult.pricing)) {
            throw new Error(plansResult.message || pricingResult.message || 'Medlemspriser kunne ikke lastes.');
        }
        const existing = new Map(pricingResult.pricing.map(price => [String(price.plan_id),price]));
        const safe = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
        if (!plansResult.plans.length) {
            container.innerHTML = '<p class="small text-muted mb-0">Ingen medlemskapsplaner er tilgjengelige.</p>';
        } else {
            container.innerHTML = `<div class="edit-membership-plan-list">${plansResult.plans.map(plan => {
                const planId = String(plan.plan_id ?? plan.id ?? '');
                const price = existing.get(planId);
                const amount = price ? (price.ticket_price ?? price.price ?? '') : '';
                const isPublic = price ? (price.public_display == 1 || price.public_display === true) : true;
                return `<div class="edit-membership-plan-row">
                    <label class="edit-membership-plan-enable"><input type="checkbox" class="form-check-input" data-membership-enabled ${price ? 'checked' : ''} aria-label="Bruk medlemspris for ${safe(plan.name || plan.plan_name)}">
                        <span><strong>${safe(plan.name || plan.plan_name || 'Medlemsplan')}</strong><small>Planpris ${safe(plan.price ?? plan.plan_price ?? '–')} kr/år</small></span></label>
                    <label class="edit-membership-price-field"><span>Arrangementpris</span><span class="input-group input-group-sm"><input type="number" min="0" step="0.01" class="form-control" data-membership-price value="${safe(amount)}" ${price ? '' : 'disabled'} aria-label="Arrangementpris for ${safe(plan.name || plan.plan_name)}"><span class="input-group-text">kr</span></span></label>
                    <label class="edit-membership-public"><input type="checkbox" class="form-check-input" data-membership-public ${isPublic ? 'checked' : ''} ${price ? '' : 'disabled'}> Vis offentlig</label>
                    <input type="hidden" data-membership-plan-id value="${safe(planId)}">
                </div>`;
            }).join('')}</div>`;
            container.querySelectorAll('.edit-membership-plan-row').forEach(row => {
                const enable = row.querySelector('[data-membership-enabled]');
                const price = row.querySelector('[data-membership-price]');
                const publicDisplay = row.querySelector('[data-membership-public]');
                enable.addEventListener('change',() => { price.disabled = !enable.checked;publicDisplay.disabled = !enable.checked; });
            });
        }
        container.dataset.membershipState = 'ready';
        editMembershipPricingLoaded = true;
    } catch (error) {
        container.dataset.membershipState = 'error';
        container.innerHTML = `<div class="events-pricing-error" role="alert"><span>${String(error.message || 'Medlemspriser kunne ikke lastes.').replace(/[&<>"]/g,char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char]))}</span><button type="button" class="btn btn-sm btn-outline-secondary" data-retry-membership-prices>Prøv igjen</button></div>`;
        container.querySelector('[data-retry-membership-prices]')?.addEventListener('click',() => loadEditMembershipPricing(eventId));
    }
}

async function editEvent(eventId) {
    editEventModalReady = false;
    editEventDirty = false;
    console.log('editEvent called with ID:', eventId, 'Type:', typeof eventId);
    
    try {
        // Try to get event data from button data attribute first (avoids API call)
        let event = null;
        const editBtn = document.querySelector(`button[data-event][onclick*="editEvent('${eventId}')"]`);
        
        if (editBtn && editBtn.dataset.event) {
            try {
                event = JSON.parse(editBtn.dataset.event);
                console.log('Using event data from button attribute');
            } catch (e) {
                console.warn('Failed to parse button event data:', e);
                event = null;
            }
        }
        
        // If no button data or parsing failed, fall back to API
        if (!event) {
            console.log('Fetching event from API...');
            const response = await fetch(`/api/get_event.php?id=${eventId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: Kunne ikke hente arrangement data`);
            }
            
            const result = await response.json();
            
            if (!result || !result.success) {
                throw new Error(result?.message || 'API returnerte feildata');
            }
            
            event = result.event;
        }
        
        if (!event) {
            toast.quickError('Arrangement', 'Arrangement ikke funnet');
            return;
        }
        
        // Populate form fields - with defensive checks for optional elements
        const setFieldValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.value = value || '';
        };
        
        setFieldValue('edit-event-id', event.id);
        setFieldValue('edit-title', event.title);
        setFieldValue('edit-description', event.description);
        setFieldValue('edit-start-date', sqlDateToNorsk(event.start_date));
        setFieldValue('edit-start-time', formatNorskTid(event.start_time));
        setFieldValue('edit-end-date', sqlDateToNorsk(event.end_date));
        setFieldValue('edit-end-time', formatNorskTid(event.end_time));
        setFieldValue('edit-age-limit', event.age_limit);
        setFieldValue('edit-venue-name', event.venue_name);
        setFieldValue('edit-event-address', event.event_address);
        setFieldValue('edit-event-city', event.event_city);
        setFieldValue('edit-event-postal', event.event_postal);
        setFieldValue('edit-min-attendees', event.min_attendees);
        setFieldValue('edit-registration-deadline', sqlDateToNorsk(event.registration_deadline));
        EventFormHelpers.syncDateTimeControls(document.getElementById('edit-event-form'));
        setFieldValue('edit-max-attendees', event.max_attendees);
        setFieldValue('edit-ticket-price', event.ticket_price || 0);
        setFieldValue('edit-default-payment-method', event.default_payment_method);
        setFieldValue('edit-event-image-url', event.event_image_url);
        
        // Set registration type radio button
        const registrationType = event.registration_type || 'signup';
        document.getElementById('edit-event-form').dataset.registrationType = registrationType;
        const registrationRadio = document.querySelector(`#edit-event-form input[name="registration_type"][value="${registrationType}"]`);
        if (registrationRadio) {
            registrationRadio.checked = true;
        }
        
        // Set checkbox
        const memberExclusiveCheckbox = document.getElementById('edit-member-exclusive');
        if (memberExclusiveCheckbox) {
            memberExclusiveCheckbox.checked = event.is_member_exclusive == 1;
        }
        const memberVerificationCheckbox = document.getElementById('edit-memberverification');
        if (memberVerificationCheckbox) {
            memberVerificationCheckbox.checked = event.memberverification == 1 ||
                event.memberverification === true || event.memberverification === '1';
        }
        
        // Show/hide registration deadline if min_attendees is set
        const editDeadlineContainer = document.getElementById('edit-registration-deadline-container');
        if (event.min_attendees && parseInt(event.min_attendees) > 0) {
            editDeadlineContainer.style.display = 'block';
        } else {
            editDeadlineContainer.style.display = 'none';
        }
        
        // Load the module-scoped form-field token for the basic-information update.
        try {
            const fieldData = await EntityFormFields.load('event', eventId, true);
            eventCsrfToken = fieldData.csrf_token || window.entityFormCsrfToken || '';
        } catch (fieldError) {
            console.error('Could not load event security token:', fieldError);
            toast.quickError('Arrangement', 'Kunne ikke hente sikkerhetstoken. Prøv igjen.');
            return;
        }
        
        // Load payment methods with current selection - safe loading
        try {
            let selectedPaymentMethods = [];
            if (event.payment_methods_json) {
                try {
                    selectedPaymentMethods = JSON.parse(event.payment_methods_json);
                } catch (e) {
                    console.error('Error parsing payment_methods_json:', e);
                }
            }
            const defaultMethod = event.default_payment_method || null;
            await loadPaymentMethodsForEdit(selectedPaymentMethods, defaultMethod);
        } catch (paymentError) {
            console.warn('Error loading payment methods:', paymentError);
            // Continue - payment methods are optional for modal to open
        }
        
        // Membership plan and event-price data are loaded independently and
        // saved together only when both sources completed successfully.
        await loadEditMembershipPricing(eventId);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
        modal.show();
        editEventDirty = false;
        editEventModalReady = true;
        
    } catch (error) {
        console.error('Error loading event for edit:', error);
        toast.quickError('Arrangement', 'Feil ved lasting av arrangement');
    }
}

// Handle min_attendees field in edit modal
const editMinAttendeesInput = document.getElementById('edit-min-attendees');
const editDeadlineContainer = document.getElementById('edit-registration-deadline-container');
const editDeadlineInput = document.getElementById('edit-registration-deadline');

if (editMinAttendeesInput && editDeadlineContainer) {
    editMinAttendeesInput.addEventListener('input', function() {
        if (this.value && parseInt(this.value) > 0) {
            editDeadlineContainer.style.display = 'block';
            editDeadlineInput.required = true;
        } else {
            editDeadlineContainer.style.display = 'none';
            editDeadlineInput.required = false;
            editDeadlineInput.value = '';
        }
    });
}

// Save event changes
async function saveEventChanges() {
    if (editEventSaveBusy) return;
    const form = document.getElementById('edit-event-form');
    if (!validateEventDateTimeInputs(form)) {
        toast.quickWarning('Validering', 'Kontroller datoer og klokkeslett. Bruk DD.MM.YYYY og HH:mm.');
        return;
    }
    const formData = new FormData(form);
    
    if (!eventCsrfToken) {
        toast.quickError('Sikkerhet', 'Last arrangementet på nytt før du lagrer.');
        return;
    }
    
    // Validate registration deadline
    const minAttendees = formData.get('min_attendees');
    const registrationDeadline = formData.get('registration_deadline');
    const startDate = formData.get('start_date');
    
    if (minAttendees && parseInt(minAttendees) > 0 && !registrationDeadline) {
        toast.quickWarning('Validering', 'Du må velge en frist for minimum deltakere');
        return;
    }
    
    if (registrationDeadline && startDate) {
        const dateComparison = compareCalendarDates(registrationDeadline, startDate);
        if (dateComparison === null || dateComparison >= 0) {
            toast.quickWarning('Validering', 'Frist for min. deltakere må være før startdato');
            return;
        }
    }
    
    // Collect allowed payment methods from Economy configuration
    const registrationType = EventSettings.registrationType(form);
    let allowedPaymentMethods;
    try { allowedPaymentMethods = EventSettings.paymentMethods(form,'#edit-payment-methods-container input[type="checkbox"]',registrationType); }
    catch(error) { toast.quickWarning('Betaling',error.message); return; }
    
    // Build payment timing methods array: always has 'arrival', plus economy methods if any selected
    const paymentTimingMethods = ['arrival'];
    allowedPaymentMethods.forEach(method => {
        if (method !== 'cash') {
            paymentTimingMethods.push(method); // Add actual method type (card, vipps, etc)
        }
    });
    
    // Get selected default payment method from dropdown
    const editDefaultPaymentMethodSelect = document.getElementById('edit-default-payment-method');
    const defaultPaymentMethod = allowedPaymentMethods.includes(editDefaultPaymentMethodSelect?.value)
        ? editDefaultPaymentMethodSelect.value : allowedPaymentMethods[0] || null;

    const membershipPricingContainer = document.getElementById('edit-membership-pricing-container');
    let membershipPrices = null;
    if (eventHasMembershipAccess()) {
        if (!membershipPricingContainer || !editMembershipPricingLoaded ||
            editMembershipPricingEventId !== String(formData.get('event_id')) ||
            membershipPricingContainer.dataset.membershipState !== 'ready') {
            toast.quickError('Medlemspriser', 'Medlemsprisene er ikke lastet. Last dem på nytt før du lagrer.');
            return;
        }
        membershipPrices = [];
        for (const row of membershipPricingContainer.querySelectorAll('.edit-membership-plan-row')) {
            const enabled = row.querySelector('[data-membership-enabled]');
            if (!enabled?.checked) continue;
            const priceControl = row.querySelector('[data-membership-price]');
            const price = Number(priceControl?.value);
            if (!priceControl?.value || !Number.isFinite(price) || price < 0) {
                toast.quickWarning('Medlemspriser', 'Legg inn en gyldig pris for hver aktiv medlemsplan.');
                priceControl?.focus();
                return;
            }
            membershipPrices.push({
                plan_id:row.querySelector('[data-membership-plan-id]')?.value || '',
                price,
                public_display:row.querySelector('[data-membership-public]')?.checked ? 1 : 0
            });
        }
    }
    
    const data = {
        event_id: formData.get('event_id'),
        title: formData.get('title'),
        description: formData.get('description'),
        registration_type: registrationType,
        start_date: startDate,
        start_time: formData.get('start_time'),
        end_date: formData.get('end_date') || null,
        end_time: formData.get('end_time') || null,
        venue_name: formData.get('venue_name'),
        event_address: formData.get('event_address'),
        event_city: formData.get('event_city'),
        event_postal: formData.get('event_postal'),
        min_attendees: minAttendees || null,
        registration_deadline: registrationDeadline || null,
        max_attendees: formData.get('max_attendees') || null,
        age_limit: formData.get('age_limit') || null,
        ticket_price: formData.get('ticket_price') || 0,
        event_image_url: formData.get('event_image_url') || null,
        default_payment_method: defaultPaymentMethod,
        ...EventSettings.membership(form),
        allowed_payment_methods: allowedPaymentMethods,
        payment_timing_methods: paymentTimingMethods,
        csrf_token: eventCsrfToken
    };
    if (membershipPrices !== null) data.membership_prices = membershipPrices;

    editEventSaveBusy = true;
    form.setAttribute('aria-busy','true');
    form.querySelectorAll('input,select,textarea,button').forEach(control => {
        control.dataset.disabledBeforeEventSave = String(control.disabled);
        control.disabled = true;
    });
    const saveButton = document.querySelector('#editEventModal .modal-footer button[onclick="saveEventChanges()"]');
    if (saveButton) {
        saveButton.dataset.disabledBeforeEventSave = String(saveButton.disabled);
        saveButton.disabled = true;
        saveButton.dataset.originalLabel = saveButton.textContent;
        saveButton.textContent = 'Lagrer…';
    }
    try {
        const response = await fetch('/api/update_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': eventCsrfToken
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            toast.quickSuccess('Arrangement', 'Arrangement oppdatert!');
            editEventDirty = false;
            // The close guard must release after a completed save, not cancel
            // this programmatic hide because the request was previously busy.
            editEventSaveBusy = false;
            bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
            loadEvents();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke oppdatere arrangement');
        }
    } catch (error) {
        console.error('Error updating event:', error);
        toast.quickError('Arrangement', 'Feil ved oppdatering av arrangement');
    } finally {
        editEventSaveBusy = false;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-disabled-before-event-save]').forEach(control => {
            control.disabled = control.dataset.disabledBeforeEventSave === 'true';
            delete control.dataset.disabledBeforeEventSave;
        });
        if (saveButton) {
            saveButton.disabled = saveButton.dataset.disabledBeforeEventSave === 'true';
            saveButton.textContent = saveButton.dataset.originalLabel || 'Lagre endringer';
            delete saveButton.dataset.disabledBeforeEventSave;
            delete saveButton.dataset.originalLabel;
        }
    }
}

function openAddIncomeModal() {
    const eventId = document.getElementById('details-event-id').value;
    document.getElementById('income-event-id').value = eventId;
    document.getElementById('add-income-form').reset();
    document.getElementById('income-event-id').value = eventId;
    
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('viewEventDetailsModal'));
    if (detailsModal) detailsModal.hide();
    
    const incomeModal = new bootstrap.Modal(document.getElementById('addIncomeModal'));
    incomeModal.show();
}

function openAddExpenseModal() {
    const eventId = document.getElementById('details-event-id').value;
    document.getElementById('expense-event-id').value = eventId;
    document.getElementById('add-expense-form').reset();
    document.getElementById('expense-event-id').value = eventId;
    
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('viewEventDetailsModal'));
    if (detailsModal) detailsModal.hide();
    
    const expenseModal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
    expenseModal.show();
}

async function saveIncome() {
    const form = document.getElementById('add-income-form');
    const formData = new FormData(form);
    const dateInput = form.querySelector('input[name="date"]');
    if (!validateDateTimeInput(dateInput, 'Dato')) {
        toast.quickWarning('Validering', 'Dato må være en gyldig dato på formatet DD.MM.YYYY');
        return;
    }
    
    const data = {
        event_id: formData.get('event_id'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        // Finance APIs persist SQL dates; convert the Norwegian display value
        // without passing through Date/string parsing.
        date: norskDateToSql(formData.get('date')),
        notes: formData.get('notes')
    };
    
    if (!data.description || !data.amount || data.amount <= 0) {
        toast.quickWarning('Validering', 'Vennligst fyll ut beskrivelse og gyldig beløp');
        return;
    }
    
    try {
        data.payment_method=await ActivePaymentMethods.choose();
        if (!data.payment_method) return;
        const response = await fetch('/api/add_event_income.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Inntekt', 'Inntekt lagt til!');
            bootstrap.Modal.getInstance(document.getElementById('addIncomeModal')).hide();
            viewEventDetails(data.event_id);
            loadEventsResults();
        } else {
            toast.quickError('Feil', result.message || 'Feil ved lagring');
        }
    } catch (error) {
        console.error('Error saving income:', error);
        toast.quickError('Inntekt', 'Feil ved lagring av inntekt');
    }
}

async function saveExpense() {
    const form = document.getElementById('add-expense-form');
    const formData = new FormData(form);
    const dateInput = form.querySelector('input[name="date"]');
    if (!validateDateTimeInput(dateInput, 'Dato')) {
        toast.quickWarning('Validering', 'Dato må være en gyldig dato på formatet DD.MM.YYYY');
        return;
    }
    
    const data = {
        event_id: formData.get('event_id'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        date: norskDateToSql(formData.get('date')),
        category: formData.get('category'),
        notes: formData.get('notes')
    };
    
    if (!data.description || !data.amount || data.amount <= 0) {
        toast.quickWarning('Validering', 'Vennligst fyll ut beskrivelse og gyldig beløp');
        return;
    }
    
    try {
        const response = await fetch('/api/add_event_expense.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Utgift', 'Utgift lagt til!');
            bootstrap.Modal.getInstance(document.getElementById('addExpenseModal')).hide();
            viewEventDetails(data.event_id);
            loadEventsResults();
        } else {
            toast.quickError('Feil', result.message || 'Feil ved lagring');
        }
    } catch (error) {
        console.error('Error saving expense:', error);
        toast.quickError('Utgift', 'Feil ved lagring av utgift');
    }
}

async function viewEventDetails(eventId, eventTitle = 'Arrangement') {
    document.getElementById('details-event-id').value = eventId;
    document.getElementById('details-event-title').textContent = eventTitle;
    
    try {
        const response = await fetch(`/api/get_event_transactions.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('details-total-income').textContent = result.summary.total_income.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('details-total-expense').textContent = result.summary.total_expense.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('details-net-result').textContent = result.summary.net_result.toLocaleString('nb-NO') + ' NOK';
            
            const transactionsList = document.getElementById('details-transactions-list');
            if (result.transactions && result.transactions.length > 0) {
                // Group transactions by item_name and entry_type, sum amounts
                const grouped = {};
                result.transactions.forEach(t => {
                    const key = `${t.entry_type}|${t.item_name}`;
                    if (!grouped[key]) {
                        grouped[key] = {
                            entry_type: t.entry_type,
                            item_name: t.item_name,
                            total_amount: 0,
                            count: 0
                        };
                    }
                    grouped[key].total_amount += Math.abs(parseFloat(t.total_amount));
                    grouped[key].count += Number(t.quantity) || 1;
                });
                
                // Convert to array and sort (income first, then by amount desc)
                const groupedArray = Object.values(grouped).sort((a, b) => {
                    if (a.entry_type !== b.entry_type) {
                        return a.entry_type === 'income' ? -1 : 1;
                    }
                    return b.total_amount - a.total_amount;
                });
                
                transactionsList.innerHTML = groupedArray.map(g => {
                    const typeClass = g.entry_type === 'income' ? 'text-success' : 'text-danger';
                    const typeIcon = g.entry_type === 'income' ? 'plus-circle' : 'dash-circle';
                    const typeText = g.entry_type === 'income' ? 'Inntekt' : 'Utgift';
                    
                    return `
                        <tr>
                            <td><span class="badge ${g.entry_type === 'income' ? 'bg-success' : 'bg-danger'}"><i class="bi bi-${typeIcon} me-1"></i>${typeText}</span></td>
                            <td>${eventResultsEscape(g.item_name)} <span class="text-muted">(${g.count})</span></td>
                            <td class="${typeClass}"><strong>${g.total_amount.toLocaleString('nb-NO')} NOK</strong></td>
                        </tr>
                    `;
                }).join('');
            } else {
                transactionsList.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">
                            Ingen transaksjoner ennå
                        </td>
                    </tr>
                `;
            }
            
            const detailsModal = new bootstrap.Modal(document.getElementById('viewEventDetailsModal'));
            detailsModal.show();
        }
    } catch (error) {
        console.error('Error loading event details:', error);
        toast.quickError('Arrangement', 'Feil ved lasting av arrangementsdetaljer');
    }
}

async function deleteTransaction(transactionId) {
    if (!confirm('Er du sikker på at du vil slette denne transaksjonen?')) {
        return;
    }
    
    toast.quickWarning('Info', 'Sletting av transaksjoner er ikke implementert ennå');
}

// Load membership plans for create form
let membershipPlansData = [];
async function loadMembershipPlansForCreate() {
    if (!eventHasMembershipAccess()) return;
    const container = document.getElementById('membership-pricing-container');
    if (!container) return;
    
    try {
        const response = await fetch('/api/get_membership_plans.php');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const result = await response.json();
        
        if (result.success && result.plans && result.plans.length > 0) {
            membershipPlansData = result.plans;
            container.innerHTML = '<div class="row">' + result.plans.map(plan => `
                <div class="col-md-6 mb-3">
                    <label class="form-label small">${plan.name}</label>
                    <div class="input-group input-group-sm mb-1">
                        <span class="input-group-text">NOK</span>
                        <input type="number" step="0.01" class="form-control" 
                               name="plan_price_${plan.plan_id}" 
                               data-plan-id="${plan.plan_id}"
                               placeholder="Standard pris">
                        <span class="input-group-text text-muted">${plan.price} kr/år</span>
                    </div>
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" 
                               name="plan_public_${plan.plan_id}" 
                               id="plan_public_${plan.plan_id}"
                               checked>
                        <label class="form-check-label small text-muted" for="plan_public_${plan.plan_id}">
                            Vis offentlig
                        </label>
                    </div>
                </div>
            `).join('') + '</div>';
        } else {
            container.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-info-circle me-2"></i>Ingen medlemsplaner funnet</p>';
        }
    } catch (error) {
        console.error('Error loading membership plans:', error);
        container.innerHTML = '<p class="text-danger small mb-0">Feil ved lasting av medlemsplaner</p>';
    }
}

// Shared activated-method editor for create and edit.
async function loadPaymentMethodsForCreate() {
    return ActivePaymentMethods.eventOptions('payment-methods-container','default-payment-method');
}

// Update default payment method dropdown based on selected checkboxes
function updateDefaultPaymentMethodDropdown(containerId = 'payment-methods-container', dropdownId = 'default-payment-method') {
    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) return;
    
    const currentValue = dropdown.value;
    const checkedMethods = document.querySelectorAll(`#${containerId} input[type="checkbox"]:checked`);
    const iconMap = { 'cash': 'Kontant', 'vipps': 'Vipps', 'stripe': 'Stripe (Kort)', 'paypal': 'PayPal' };
    
    dropdown.innerHTML = '<option value="">Velg standardmetode...</option>';
    
    checkedMethods.forEach(checkbox => {
        const methodType = checkbox.dataset.methodType;
        const methodName = iconMap[methodType] || methodType;
        const option = document.createElement('option');
        option.value = methodType;
        option.textContent = methodName;
        if (methodType === currentValue) {
            option.selected = true;
        }
        dropdown.appendChild(option);
    });
    
    // Auto-select first method if nothing selected and options available
    if (!dropdown.value && dropdown.options.length > 1) {
        dropdown.selectedIndex = 1;
    }
}

// Update payment timing status based on selected methods
function updatePaymentTimingStatus() {
    const statusSpan = document.getElementById('payment-timing-status');
    if (!statusSpan) return;
    
    const checkedMethods = document.querySelectorAll('#payment-methods-container input[type="checkbox"]:checked');
    const hasOnlinePayment = Array.from(checkedMethods).some(cb => cb.dataset.methodType !== 'cash');
    
    if (hasOnlinePayment) {
        statusSpan.innerHTML = '<strong class="text-success">✓ Aktivert</strong> (nettbetaling tilgjengelig)';
    } else if (checkedMethods.length > 0) {
        statusSpan.innerHTML = '<strong class="text-info">Kun oppmøtebetaling</strong>';
    } else {
        statusSpan.innerHTML = '<span class="text-warning">Ingen betalingsmetoder valgt</span>';
    }
}

// Load payment methods for edit form
async function loadPaymentMethodsForEdit(selectedMethods = [], defaultMethod = null) {
    return ActivePaymentMethods.eventOptions('edit-payment-methods-container','edit-default-payment-method',selectedMethods,defaultMethod);
}

// Handle registration type changes - show/hide sections for "Ingen påmelding"
const registrationTypeRadios = document.querySelectorAll('#create-event-form input[name="registration_type"]');
const registrationFieldsSection = document.getElementById('registration-fields-section');

if (registrationTypeRadios.length > 0) {
    function handleRegistrationTypeChange() {
        const selectedType = document.querySelector('#create-event-form input[name="registration_type"]:checked').value;
        EventSettings.syncPayments(document.getElementById('create-event-form'));
        const isNone = selectedType === 'none';
        
        // Show/hide påmeldingsfelt section
        if (registrationFieldsSection) {
            registrationFieldsSection.style.display = isNone ? 'none' : 'block';
        }
        
        // Show/hide participant limits (but NOT ticket price - that's allowed for "Ingen påmelding")
        const minAttendeesInput = document.querySelector('input[name="min_attendees"]');
        const maxAttendeesInput = document.querySelector('input[name="max_attendees"]');
        const regDeadlineContainer = document.getElementById('registration_deadline_container');
        
        // Find the parent row containing both min/max attendees fields
        if (minAttendeesInput) {
            const minRow = minAttendeesInput.closest('.row');
            if (minRow && maxAttendeesInput && minRow.contains(maxAttendeesInput)) {
                minRow.style.display = isNone ? 'none' : '';
            }
        }
        if (regDeadlineContainer) regDeadlineContainer.style.display = 'none';
        
        // Show/hide deltakere line in summary
        const summaryAttendeesLine = document.getElementById('summary-attendees');
        if (summaryAttendeesLine && summaryAttendeesLine.closest('div')) {
            summaryAttendeesLine.closest('div').style.display = isNone ? 'none' : '';
        }
        
        // Update ticket price help text based on registration type
        const ticketPriceHelp = document.getElementById('ticket-price-help');
        if (ticketPriceHelp) {
            if (isNone) {
                ticketPriceHelp.textContent = 'Ved "Ingen påmelding" vises pris med "(betales ved inngang)"';
                ticketPriceHelp.style.display = 'block';
            } else {
                ticketPriceHelp.textContent = '';
                ticketPriceHelp.style.display = 'none';
            }
        }
        
        // Show/hide membership pricing section
        const membershipPricingContainer = document.getElementById('membership-pricing-container');
        if (membershipPricingContainer) {
            const membershipSection = membershipPricingContainer.closest('div').previousElementSibling;
            if (membershipSection && membershipSection.tagName === 'HR') {
                membershipSection.style.display = isNone ? 'none' : 'block';
            }
            const membershipHeader = membershipPricingContainer.previousElementSibling;
            if (membershipHeader && membershipHeader.tagName === 'H6') {
                membershipHeader.style.display = isNone ? 'none' : 'block';
            }
            membershipPricingContainer.style.display = isNone ? 'none' : 'block';
        }
        
        // Show/hide payment methods section
        const paymentSection = document.getElementById('payment-section');
        const paymentMethodsContainer = document.getElementById('payment-methods-container');
        const paymentTimingStatus = document.getElementById('payment-timing-status');
        
        if (paymentSection) {
            const paymentHeader = paymentSection.nextElementSibling;
            if (paymentHeader && paymentHeader.tagName === 'H6') {
                paymentHeader.style.display = isNone ? 'none' : 'block';
            }
            paymentSection.style.display = isNone ? 'none' : 'block';
        }
        if (paymentMethodsContainer) paymentMethodsContainer.style.display = isNone ? 'none' : 'block';
        if (paymentTimingStatus) paymentTimingStatus.closest('p').style.display = isNone ? 'none' : 'block';
        
        // Clear values when "Ingen påmelding" is selected (except ticket price - that's allowed)
        if (isNone) {
            // Don't clear ticketPriceInput - påmeldingsfrie arrangementer kan ha pris
            if (minAttendeesInput) minAttendeesInput.value = '';
            if (maxAttendeesInput) maxAttendeesInput.value = '';
        }
    }
    
    registrationTypeRadios.forEach(radio => {
        radio.addEventListener('change', handleRegistrationTypeChange);
    });
    
    // Run on page load
    handleRegistrationTypeChange();
}

// Handle "Kun medlemmer" checkbox - DO NOT disable ticket price (CREATE FORM)
// Member-exclusive events can still have a regular price for non-members
const memberExclusiveCheckbox = document.getElementById('memberExclusive');
const ticketPriceContainer = document.getElementById('ticket-price-container');
const ticketPriceInput = document.getElementById('ticket_price');

if (memberExclusiveCheckbox && ticketPriceContainer && ticketPriceInput) {
    function handleMemberExclusiveChange() {
        // Keep ticket price field active regardless of member-exclusive status
        // Membership prices are handled separately in event_membership_plans
        ticketPriceInput.disabled = false;
        ticketPriceInput.classList.remove('bg-light', 'text-muted');
    }
    
    memberExclusiveCheckbox.addEventListener('change', handleMemberExclusiveChange);
    
    // Run on page load
    handleMemberExclusiveChange();
}

// Handle "Kun medlemmer" checkbox - DO NOT disable ticket price (EDIT FORM)
// Member-exclusive events can still have a regular price for non-members
const editMemberExclusiveCheckbox = document.getElementById('edit-member-exclusive');
const editTicketPriceContainer = document.getElementById('edit-ticket-price-container');
const editTicketPriceInput = document.getElementById('edit-ticket-price');

if (editMemberExclusiveCheckbox && editTicketPriceContainer && editTicketPriceInput) {
    function handleEditMemberExclusiveChange() {
        // Keep ticket price field active regardless of member-exclusive status
        // Membership prices are handled separately in event_membership_plans
        editTicketPriceInput.disabled = false;
        editTicketPriceInput.classList.remove('bg-light', 'text-muted');
    }
    
    editMemberExclusiveCheckbox.addEventListener('change', handleEditMemberExclusiveChange);
    
    // Also run when edit modal is shown to ensure correct state
    const editEventModal = document.getElementById('editEventModal');
    if (editEventModal) {
        editEventModal.addEventListener('shown.bs.modal', handleEditMemberExclusiveChange);
    }
}

// ========== REGISTRATIONS APPROVAL FUNCTIONALITY ==========

// Registrations loading is now handled by subtab-changed event at bottom of file

async function loadPendingRegistrations() {
    try {
        const response = await fetch('/api/get_pending_registrations.php');
        const result = await response.json();
        
        const tbody = document.getElementById('pending-registrations-list');
        const badges = document.querySelectorAll('[data-event-pending-count]');
        
        if (!result.success) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-4">
                        ${result.message || 'Feil ved lasting av påmeldinger'}
                    </td>
                </tr>
            `;
            return;
        }
        
        // Update badge count
        badges.forEach(badge => {
            badge.textContent = result.registrations?.length || 0;
            badge.style.display = result.registrations?.length ? 'inline-block' : 'none';
        });
        
        if (result.registrations && result.registrations.length > 0) {
            tbody.innerHTML = result.registrations.map(reg => {
                // Show actual entry data from customer registration
                const entry = reg.entry_data || {};
                const fullName = [entry.first_name, entry.last_name].filter(Boolean).join(' ') || `${reg.first_name || ''} ${reg.last_name || ''}`.trim();
                const email = entry.email || reg.email || '';
                const phone = entry.phone || reg.phone || '';
                const address = entry.address || '';
                const city = entry.city || '';
                
                // Build entry data display - show actual values
                let entryDataHtml = `
                    <div class="mb-1">
                        <strong style="font-size: 0.95rem;">${fullName || '-'}</strong>
                    </div>
                `;
                
                if (email) {
                    entryDataHtml += `
                        <div class="text-muted" style="font-size: 0.85rem;">
                            <i class="bi bi-envelope me-1"></i>${email}
                        </div>
                    `;
                }
                if (phone) {
                    entryDataHtml += `
                        <div class="text-muted" style="font-size: 0.85rem;">
                            <i class="bi bi-telephone me-1"></i>${phone}
                        </div>
                    `;
                }
                if (address || city) {
                    const locationParts = [address, city].filter(Boolean).join(', ');
                    entryDataHtml += `
                        <div class="text-muted" style="font-size: 0.85rem;">
                            <i class="bi bi-geo-alt me-1"></i>${locationParts}
                        </div>
                    `;
                }
                
                // Price with membership indicator
                const priceInfo = reg.price_info || {};
                const isMember = priceInfo.is_member;
                const displayPrice = priceInfo.display_price || 0;
                const memberIcon = isMember ? '<i class="bi bi-person-check text-success" aria-label="Medlem"></i>' : '<i class="bi bi-person text-muted" aria-label="Ikke medlem"></i>';
                const priceHtml = `
                    <span class="fw-bold" style="font-size: 1rem;">
                        ${memberIcon} ${displayPrice} kr
                    </span>
                    ${isMember && priceInfo.member_plan ? `<br><small class="text-muted">${priceInfo.member_plan}</small>` : ''}
                `;
                
                return `
                    <tr data-registration-id="${eventImageAttribute(reg.id)}" data-event-id="${eventImageAttribute(reg.event_id)}">
                        <td style="min-width: 250px;">${entryDataHtml}</td>
                        <td class="text-center" style="min-width: 100px;">${priceHtml}</td>
                        <td>
                            <div><strong>${reg.event_title}</strong></div>
                            <small class="text-muted">${formatNorskDato(reg.start_date, reg.start_time, { includeTime: true })}</small>
                        </td>
                        <td class="text-end" style="min-width: 160px;">
                            <button class="btn btn-sm btn-success me-1" onclick="approveRegistrationWithReason('${reg.id}')" title="Godkjenn">
                                <i class="bi bi-check-circle me-1"></i>Godkjenn
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="rejectRegistrationWithReason('${reg.id}')" title="Avvis">
                                <i class="bi bi-x-circle me-1"></i>Avvis
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        Ingen ventende påmeldinger
                    </td>
                </tr>
            `;
        }
        
    } catch (error) {
        console.error('Error loading pending registrations:', error);
        document.getElementById('pending-registrations-list').innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger py-4">
                    Feil ved lasting av påmeldinger
                </td>
            </tr>
        `;
    }
}

async function approveRegistrationWithReason(registrationId) {
    const approvalReason = prompt('Årsak til godkjenning (valgfritt, trykk OK for å fortsette):');
    
    if (approvalReason === null) {
        return; // User cancelled
    }
    
    try {
        const response = await fetch('/api/approve_registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                registration_id: registrationId,
                approval_reason: approvalReason || 'Manuell godkjenning'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Påmelding godkjent', `Innsjekking opprettet. Billettpris: ${result.ticket_price} kr`);
            document.dispatchEvent(new Event('events-registration-decided'));
            // Refresh both views
            setTimeout(() => {
                loadPendingRegistrations();
                loadAllRegistrations();
            }, 300);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke godkjenne påmelding');
        }
    } catch (error) {
        console.error('Error approving registration:', error);
        toast.quickError('Feil', 'Feil ved godkjenning av påmelding');
    }
}

function rejectRegistrationWithReason(registrationId) {
    // Clear previous selections
    document.querySelectorAll('#rejectionReasonModal input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('rejection-registration-id').value = registrationId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('rejectionReasonModal'));
    modal.show();
}

async function submitRejection() {
    const registrationId = document.getElementById('rejection-registration-id').value;
    
    // Collect selected reasons
    const selectedReasons = [];
    document.querySelectorAll('#rejectionReasonModal input[type="checkbox"]:checked').forEach(cb => {
        selectedReasons.push(cb.value);
    });
    
    if (selectedReasons.length === 0) {
        toast.quickError('Mangler årsak', 'Du må velge minst én årsak for avvisning');
        return;
    }
    
    const rejectionReason = selectedReasons.join('; ');
    
    try {
        const response = await fetch('/api/reject_registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                registration_id: registrationId,
                rejection_reason: rejectionReason
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('rejectionReasonModal')).hide();
            
            toast.quickSuccess('Påmelding avvist', 'Påmeldingen er nå avvist.');
            document.dispatchEvent(new Event('events-registration-decided'));
            // Refresh both views
            setTimeout(() => {
                loadPendingRegistrations();
                loadAllRegistrations();
            }, 300);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke avvise påmelding');
        }
    } catch (error) {
        console.error('Error rejecting registration:', error);
        toast.quickError('Feil', 'Feil ved avvisning av påmelding');
    }
}

// Legacy functions for backward compatibility
async function approveRegistration(registrationId) {
    return approveRegistrationWithReason(registrationId);
}

async function rejectRegistration(registrationId) {
    return rejectRegistrationWithReason(registrationId);
}


async function loadAllRegistrations() {
    try {
        const container = document.getElementById('all-registrations-container');
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <span class="text-muted">Laster alle påmeldinger...</span>
            </div>
        `;
        
        const response = await fetch('/api/get_all_registrations.php');
        const result = await response.json();
        
        if (!result.success) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${result.message || 'Feil ved lasting av påmeldinger'}
                </div>
            `;
            return;
        }
        
        if (!result.grouped_by_event || result.grouped_by_event.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Ingen påmeldinger registrert ennå
                </div>
            `;
            return;
        }
        
        // Build HTML for all registrations grouped by event with collapsible accordion
        let html = '<div class="accordion" id="registrationsAccordion">';
        
        result.grouped_by_event.forEach((eventGroup, index) => {
            const dateStr = formatNorskDato(eventGroup.start_date, eventGroup.start_time, { longMonth: true });
            const timeStr = formatNorskTid(eventGroup.start_time);
            
            const collapseId = `collapse-${eventGroup.event_id}`;
            const isFirst = index === 0;
            
            // Status badges for header
            const approvedCount = eventGroup.approved_count || 0;
            const rejectedCount = eventGroup.rejected_count || 0;
            const totalCount = eventGroup.registrations.length;
            
            html += `
                <div class="accordion-item border-0 mb-2 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${isFirst ? '' : 'collapsed'}" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#${collapseId}" 
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.25rem;">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                <div>
                                    <span style="font-weight: 600; font-size: 1rem;">
                                        <i class="bi bi-calendar-event me-2" style="opacity: 0.9;"></i>${eventGroup.event_title}
                                    </span>
                                    <small style="opacity: 0.85; margin-left: 1rem;">${dateStr} kl. ${timeStr}</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge" style="background-color: rgba(255,255,255,0.25); color: white;">
                                        <i class="bi bi-people me-1"></i>${totalCount}
                                    </span>
                                    ${approvedCount > 0 ? `<span class="badge bg-success">${approvedCount} godkjent</span>` : ''}
                                    ${rejectedCount > 0 ? `<span class="badge bg-danger">${rejectedCount} avvist</span>` : ''}
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="${collapseId}" class="accordion-collapse collapse" 
                         data-bs-parent="#registrationsAccordion" data-event-id="${eventGroup.event_id}">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                                        <tr>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Navn</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Kontakt</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Medlemskap</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Billettpris</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Betaling</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Status</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem; text-align: right;">Handling</th>
                                        </tr>
                                    </thead>
                                    <tbody>
            `;
            
            eventGroup.registrations.forEach(reg => {
                const approvalStatusBadge = reg.approval_status === 'approved' 
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Godkjent</span>'
                    : reg.approval_status === 'rejected'
                    ? '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Avvist</span>'
                    : '<span class="badge bg-warning"><i class="bi bi-hourglass-split"></i> Venter</span>';
                
                // Build contact info with both email and phone
                let contactHtml = '';
                if (reg.email) {
                    contactHtml += `<div><i class="bi bi-envelope me-1"></i>${reg.email}</div>`;
                }
                if (reg.phone) {
                    contactHtml += `<div><i class="bi bi-telephone me-1"></i>${reg.phone}</div>`;
                }
                if (!contactHtml) contactHtml = '-';
                
                const membershipInfo = reg.membership_plan_name 
                    ? `<span class="badge bg-success-subtle text-success"><i class="bi bi-star-fill me-1"></i>${reg.membership_plan_name}</span>`
                    : '<span class="text-muted">-</span>';
                
                const ticketPrice = reg.ticket_price != null 
                    ? `<span class="fw-semibold">${parseFloat(reg.ticket_price).toFixed(0)} kr</span>${reg.membership_ticket_price ? ' <small class="text-success">(medlem)</small>' : ''}`
                    : '<span class="text-muted">-</span>';
                
                let paymentStatusHtml = '';
                const paymentMethod = reg.payment_method || 'cash';
                const paymentStatus = reg.payment_status || 'pending';
                const checkedIn = reg.checked_in || 0;
                const paymentAuth = reg.payment_authorization || null;
                const paymentState = reg.payment_state || null;
                
                if (paymentMethod === 'vipps') {
                    let statusBadge = '';
                    let captureBtn = '';
                    const checkinIdVal = reg.checkin_id;
                    const customerNameEscaped = `${reg.first_name || ''} ${reg.last_name || ''}`.trim().replace(/'/g, "\\'");
                    
                    if (paymentState === 'captured' || paymentStatus === 'paid') {
                        statusBadge = '<span class="badge bg-success ms-1">Fanget</span>';
                    } else if (paymentState === 'authorized') {
                        statusBadge = '<span class="badge bg-warning text-dark ms-1">Reservert</span>';
                        if (checkinIdVal) {
                            captureBtn = `<button class="btn btn-sm btn-success ms-1" onclick="captureVippsPayment(${checkinIdVal}, '${customerNameEscaped}')" title="Fang betaling"><i class="bi bi-check-circle"></i> Fang</button>`;
                        }
                    } else if (checkinIdVal) {
                        statusBadge = '<span class="badge bg-secondary ms-1">Ukjent status</span>';
                        captureBtn = `<button class="btn btn-sm btn-outline-primary ms-1" onclick="checkAndCaptureVipps(${checkinIdVal}, '${customerNameEscaped}')" title="Sjekk status"><i class="bi bi-arrow-repeat"></i> Sjekk</button>`;
                    } else {
                        statusBadge = '<span class="badge bg-secondary ms-1">Venter</span>';
                    }
                    let authInfo = '';
                    if (paymentAuth) {
                        authInfo = `<br><small class="text-muted">Ref: ${paymentAuth}</small>`;
                    }
                    paymentStatusHtml = `<span class="badge bg-info text-dark"><i class="bi bi-phone me-1"></i>Vipps</span>${statusBadge}${captureBtn}${authInfo}`;
                } else if (paymentMethod === 'stripe') {
                    let statusBadge = paymentStatus === 'paid' 
                        ? '<span class="badge bg-success ms-1">Betalt</span>'
                        : '<span class="badge bg-warning text-dark ms-1">Venter</span>';
                    let authInfo = paymentAuth ? `<br><small class="text-muted">Ref: ${paymentAuth}</small>` : '';
                    paymentStatusHtml = `<span class="badge bg-primary"><i class="bi bi-credit-card me-1"></i>Stripe</span>${statusBadge}${authInfo}`;
                } else if (paymentMethod === 'paypal') {
                    let statusBadge = paymentStatus === 'paid' 
                        ? '<span class="badge bg-success ms-1">Betalt</span>'
                        : '<span class="badge bg-warning text-dark ms-1">Venter</span>';
                    let authInfo = paymentAuth ? `<br><small class="text-muted">Ref: ${paymentAuth}</small>` : '';
                    paymentStatusHtml = `<span class="badge bg-primary" style="background-color: #003087 !important;"><i class="bi bi-paypal me-1"></i>PayPal</span>${statusBadge}${authInfo}`;
                } else {
                    if (paymentStatus === 'paid' || checkedIn == 1) {
                        paymentStatusHtml = '<span class="badge bg-success"><i class="bi bi-cash me-1"></i>Betalt kontant</span>';
                    } else {
                        paymentStatusHtml = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Må betales</span>';
                    }
                }
                
                const customerName = `${reg.first_name || ''} ${reg.last_name || ''}`.trim();
                
                html += `
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            <strong style="color: #2c3e50; font-size: 0.9rem;">${customerName}</strong>
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            <small style="color: #6c757d;">${contactHtml}</small>
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            ${membershipInfo}
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            ${ticketPrice}
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            ${paymentStatusHtml}
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            ${approvalStatusBadge}
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle; text-align: right;">
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteEventRegistration('${reg.id}', '${customerName.replace(/'/g, "\\'")}')">
                                <i class="bi bi-trash"></i> Slett
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        const controlsHtml = `
            <div class="mb-3 d-flex">
                <span class="ms-auto text-muted small align-self-center">
                    ${result.total_registrations} påmeldinger totalt 
                    (${result.total_approved} godkjent, ${result.total_rejected} avvist)
                </span>
            </div>
        `;
        
        container.innerHTML = controlsHtml + html;
        
    } catch (error) {
        console.error('Error loading all registrations:', error);
        document.getElementById('all-registrations-container').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Feil ved lasting av påmeldinger
            </div>
        `;
    }
}

const vippsCheckCache = new Set();

async function captureVippsPayment(checkinId, customerName) {
    if (!confirm(`Vil du fange Vipps-betalingen for "${customerName}"?\n\nDette vil overføre det reserverte beløpet til din konto.`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/capture_vipps_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ checkin_id: checkinId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Betaling fanget', `${result.amount} kr for ${result.customer_name}`);
            vippsCheckCache.clear();
            loadAllRegistrations();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke fange betalingen');
        }
    } catch (error) {
        console.error('Error capturing Vipps payment:', error);
        toast.quickError('Feil', 'Kunne ikke koble til server');
    }
}

async function checkAndCaptureVipps(checkinId, customerName) {
    try {
        toast.quickInfo('Sjekker...', `Henter status for ${customerName}`);
        
        const response = await fetch('/api/check_single_vipps_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ checkin_id: checkinId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.state === 'AUTHORIZED') {
                toast.quickSuccess('Reservert', `Betalingen er reservert. Klikk "Fang" for å fullføre.`);
            } else if (result.state === 'CAPTURED') {
                toast.quickSuccess('Allerede fanget', 'Betalingen er allerede fullført.');
            } else if (result.state === 'CREATED') {
                toast.quickInfo('Ikke betalt ennå', 'Kunden har ikke fullført betalingen.');
            } else if (result.state === 'EXPIRED' || result.state === 'ABORTED') {
                toast.quickError('Utløpt/Avbrutt', 'Betalingen ble avbrutt eller utløpt.');
            } else {
                toast.quickInfo('Status', `Vipps-status: ${result.state || 'Ukjent'}`);
            }
            vippsCheckCache.clear();
            loadAllRegistrations();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke sjekke status');
        }
    } catch (error) {
        console.error('Error checking Vipps payment:', error);
        toast.quickError('Feil', 'Kunne ikke koble til server');
    }
}

async function checkVippsPayments(eventId) {
    if (vippsCheckCache.has(eventId)) {
        return;
    }
    vippsCheckCache.add(eventId);
    
    try {
        const response = await fetch('/api/check_vipps_payments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId })
        });
        
        const result = await response.json();
        
        if (result.success && result.updated > 0) {
            toast.quickSuccess('Vipps oppdatert', `${result.updated} betalinger bekreftet`);
            loadAllRegistrations();
        }
    } catch (error) {
        console.error('Error checking Vipps payments:', error);
        vippsCheckCache.delete(eventId);
    }
}

document.addEventListener('shown.bs.collapse', function(event) {
    const collapseElement = event.target;
    if (collapseElement.closest('#registrationsAccordion')) {
        const eventId = collapseElement.dataset.eventId;
        if (eventId) {
            checkVippsPayments(eventId);
        }
    }
});

async function deleteEventRegistration(registrationId, customerName) {
    if (!confirm(`Er du sikker på at du vil slette påmeldingen for "${customerName}"?\n\nDette vil fjerne:\n- Påmeldingen\n- Eventuell innsjekking\n- Relaterte data for dette arrangementet`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_event_registration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ registration_id: registrationId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Slettet', `Påmeldingen for ${customerName} er fjernet`);
            loadPendingRegistrations();
            loadAllRegistrations();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke slette påmeldingen');
        }
    } catch (error) {
        console.error('Error deleting registration:', error);
        toast.quickError('Feil', 'Kunne ikke slette påmeldingen');
    }
}

// ============================================
// FULLSCREEN CHECK-IN FUNCTIONALITY
// ============================================

let fullscreenEventId = null;

// Initialize fullscreen check-in
function initFullscreenCheckin() {
    const openBtn = document.getElementById('open-fullscreen-checkin');
    const closeBtn = document.getElementById('close-fullscreen-checkin');
    const overlay = document.getElementById('fullscreen-checkin-overlay');
    
    if (openBtn) {
        openBtn.addEventListener('click', openFullscreenCheckin);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeFullscreenCheckin);
    }
    
    // ESC key to close fullscreen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
            closeFullscreenCheckin();
        }
    });
}

// Open fullscreen check-in overlay
function openFullscreenCheckin() {
    const overlay = document.getElementById('fullscreen-checkin-overlay');
    if (!overlay || !selectedEventId) return;
    
    fullscreenEventId = selectedEventId;
    
    // Update overlay header (uses currentEventTitle/currentEventDate set by openEventDetail)
    document.getElementById('fullscreen-event-title').textContent = currentEventTitle || 'Innsjekking';
    document.getElementById('fullscreen-event-date').textContent = currentEventDate || '';
    
    // Show overlay
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Initialize fullscreen barcode scanner
    initFullscreenBarcodeScanner();
    
    // Sync current data to fullscreen
    syncCheckinToFullscreen();
    
    // Focus scanner input
    setTimeout(() => {
        const scannerInput = document.getElementById('fullscreen-barcode-input');
        if (scannerInput) scannerInput.focus();
    }, 100);
}

// Close fullscreen check-in overlay
function closeFullscreenCheckin() {
    const overlay = document.getElementById('fullscreen-checkin-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Refresh main checkin data
    if (selectedEventId) {
        loadCheckins(selectedEventId);
    }
}

// Initialize fullscreen barcode scanner
function initFullscreenBarcodeScanner() {
    const input = document.getElementById('fullscreen-barcode-input');
    if (!input) return;
    
    input.value = '';
    
    input.addEventListener('input', function() {
        searchFullscreenCheckins(this.value);
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            quickCheckinFromFullscreen(this.value);
        }
    });
}

// Search check-ins in fullscreen mode
function searchFullscreenCheckins(query) {
    const feedback = document.getElementById('fullscreen-barcode-feedback');
    const checkedList = document.getElementById('fullscreen-checked-list');
    const pendingList = document.getElementById('fullscreen-pending-list');
    
    if (!query || query.length < 2) {
        if (feedback) feedback.innerHTML = '';
        return;
    }
    
    const queryLower = query.toLowerCase();
    let matchCount = 0;
    
    // Search in checked-in list
    const checkedRows = checkedList?.querySelectorAll('tr') || [];
    checkedRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(queryLower);
        row.style.display = matches ? '' : 'none';
        if (matches && !row.classList.contains('empty-state')) matchCount++;
    });
    
    // Search in pending list
    const pendingRows = pendingList?.querySelectorAll('tr') || [];
    pendingRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(queryLower);
        row.style.display = matches ? '' : 'none';
        if (matches && !row.classList.contains('empty-state')) matchCount++;
    });
    
    // Show feedback
    if (feedback) {
        if (matchCount === 1) {
            feedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>1 treff - trykk Enter for å sjekke inn</span>';
        } else if (matchCount > 1) {
            feedback.innerHTML = `<span class="text-info"><i class="bi bi-search me-1"></i>${matchCount} treff funnet</span>`;
        } else {
            feedback.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Ingen treff</span>';
        }
    }
}

// Quick check-in from fullscreen on Enter key
async function quickCheckinFromFullscreen(query) {
    if (!query || query.length < 2) return;
    
    const queryLower = query.toLowerCase();
    const pendingList = document.getElementById('fullscreen-pending-list');
    const pendingRows = pendingList?.querySelectorAll('tr') || [];
    
    let matchingRow = null;
    let matchCount = 0;
    
    pendingRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(queryLower) && !row.classList.contains('empty-state')) {
            matchingRow = row;
            matchCount++;
        }
    });
    
    if (matchCount === 1 && matchingRow) {
        const checkinBtn = matchingRow.querySelector('.btn-success');
        if (checkinBtn) {
            checkinBtn.click();
            
            // Clear input and feedback
            const input = document.getElementById('fullscreen-barcode-input');
            const feedback = document.getElementById('fullscreen-barcode-feedback');
            if (input) input.value = '';
            if (feedback) feedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Sjekket inn!</span>';
            
            // Refresh fullscreen after short delay
            setTimeout(() => {
                syncCheckinToFullscreen();
                if (input) input.focus();
            }, 500);
        }
    } else if (matchCount > 1) {
        const feedback = document.getElementById('fullscreen-barcode-feedback');
        if (feedback) {
            feedback.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Flere treff - vennligst presiser søket</span>';
        }
    }
}

// Sync main checkin data to fullscreen overlay
async function syncCheckinToFullscreen() {
    if (!fullscreenEventId) return;
    
    try {
        const response = await fetch(`/api/get_event_checkins.php?event_id=${fullscreenEventId}`);
        const result = await response.json();
        
        if (result.success) {
            // Update fullscreen stats
            document.getElementById('fullscreen-checked-in').textContent = result.summary.checked_in_count;
            document.getElementById('fullscreen-pending-count').textContent = result.summary.not_checked_in_count;
            document.getElementById('fullscreen-noshow').textContent = result.summary.no_show_count || 0;
            document.getElementById('fullscreen-revenue').textContent = result.summary.checkin_revenue.toFixed(0) + ' kr';
            document.getElementById('fullscreen-expected').textContent = result.summary.expected_revenue.toFixed(0) + ' kr';
            document.getElementById('fullscreen-total-attendees').textContent = result.summary.total_attendees;
            
            // Payment method names
            const paymentMethodNames = {
                'cash': 'Kontant', 'card': 'Kort', 'vipps': 'Vipps',
                'invoice': 'Faktura', 'bank_transfer': 'Bank',
                'on_arrival': 'Ved oppmøte', 'free': 'Gratis', 'no_show': 'No-show'
            };
            
            // Render checked-in list
            const checkedList = document.getElementById('fullscreen-checked-list');
            if (result.checked_in && result.checked_in.length > 0) {
                checkedList.innerHTML = result.checked_in.map(c => {
                    const time = c.check_in_time ? (c.check_in_time.substring(11, 16) || '-') : '-';
                    const isNoShow = c.payment_status === 'no_show';
                    const membershipPlanName = c.membership_plan_name || null;
                    const isMember = membershipPlanName !== null;
                    const typeBadge = isMember ? `<span class="ticket-badge member"><i class="bi bi-star-fill"></i>${membershipPlanName}</span>` : '<span class="ticket-badge regular">Ordinær</span>';
                    let paymentBadge = isNoShow ? '<span class="badge bg-dark">No-show</span>' :
                        c.payment_status === 'paid' ? `<span class="badge bg-success">${paymentMethodNames[c.payment_method] || c.payment_method}</span>` :
                        '<span class="badge bg-warning text-dark">Venter</span>';
                    
                    return `<tr class="${isNoShow ? 'table-secondary' : ''}">
                        <td><div class="attendee-name">${c.customer_name || 'Ukjent'}</div>${c.customer_email ? `<div class="attendee-email">${c.customer_email}</div>` : ''}</td>
                        <td>${typeBadge}</td>
                        <td>${isNoShow ? `<s class="text-muted">${c.ticket_price} kr</s>` : `${c.ticket_price} kr`}</td>
                        <td>${paymentBadge}</td>
                        <td>${time}</td>
                    </tr>`;
                }).join('');
            } else {
                checkedList.innerHTML = '<tr><td colspan="5"><div class="checkin-empty-state"><i class="bi bi-inbox"></i><p class="mb-0">Ingen ankommet ennå</p></div></td></tr>';
            }
            
            // Render pending list with dynamic payment buttons
            const pendingList = document.getElementById('fullscreen-pending-list');
            if (result.not_checked_in && result.not_checked_in.length > 0) {
                pendingList.innerHTML = result.not_checked_in.map(c => {
                    const membershipPlanName = c.membership_plan_name || null;
                    const isMember = membershipPlanName !== null;
                    const typeBadge = isMember ? `<span class="ticket-badge member"><i class="bi bi-star-fill"></i>${membershipPlanName}</span>` : '<span class="ticket-badge regular">Ordinær</span>';
                    const isPaid = c.payment_status === 'paid';
                    const paymentStatusBadge = isPaid 
                        ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Betalt</span>'
                        : '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Ikke betalt</span>';
                    const escapedName = (c.customer_name || 'Ukjent').replace(/'/g, "\\'");
                    
                    // Dynamic payment buttons for fullscreen
                    const paymentButtons = getFullscreenPaymentButtons(c.id, c.customer_name || 'Ukjent', isPaid);
                    
                    return `<tr>
                        <td><div class="attendee-name">${c.customer_name || 'Ukjent'}</div>${c.customer_email ? `<div class="attendee-email">${c.customer_email}</div>` : ''}</td>
                        <td>${typeBadge}</td>
                        <td>${c.ticket_price} kr</td>
                        <td>${paymentStatusBadge}</td>
                        <td>${paymentButtons}</td>
                    </tr>`;
                }).join('');
            } else {
                pendingList.innerHTML = '<tr><td colspan="5"><div class="checkin-empty-state"><i class="bi bi-check-circle-fill text-success"></i><p class="mb-0">Alle er innsjekket!</p></div></td></tr>';
            }
        }
    } catch (error) {
        console.error('Error syncing fullscreen checkin:', error);
    }
}

// Generate fullscreen payment buttons
function getFullscreenPaymentButtons(checkinId, customerName, isPaid) {
    if (isPaid) {
        return `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Betalt</span>
                <button class="btn btn-sm btn-outline-success ms-2" onclick="performFullscreenCheckinPrepaid('${checkinId}')">
                    <i class="bi bi-check-circle"></i>
                </button>`;
    }
    
    const escapedName = customerName.replace(/'/g, "\\'");
    let buttons = '<div class="btn-group btn-group-sm" role="group">';
    
    availablePaymentMethods.forEach(pm => {
        const icon = pm.config?.icon || 'wallet2';
        let btnClass = 'btn-outline-secondary';
        
        if (pm.method_type === 'vipps') btnClass = 'btn-outline-warning';
        else if (pm.method_type === 'cash') btnClass = 'btn-outline-success';
        else if (pm.method_type === 'stripe') btnClass = 'btn-outline-primary';
        
        buttons += `<button class="btn ${btnClass}" onclick="performFullscreenCheckinWithMethod('${checkinId}', '${pm.method_type}')" title="${pm.method_name || pm.method_type}">
            <i class="bi bi-${icon}"></i>
        </button>`;
    });
    
    buttons += `</div>
        <button class="btn btn-sm btn-outline-dark ms-1" onclick="markNoShow('${checkinId}', '${escapedName}'); syncCheckinToFullscreen();" title="No-show">
            <i class="bi bi-x-circle"></i>
        </button>`;
    
    return buttons;
}

// Perform check-in from fullscreen for prepaid guests
async function performFullscreenCheckinPrepaid(checkinId) {
    await performCheckinPrepaid(checkinId);
    syncCheckinToFullscreen();
    
    const input = document.getElementById('fullscreen-barcode-input');
    if (input) {
        input.value = '';
        input.focus();
    }
}

// Perform check-in from fullscreen with specific payment method
async function performFullscreenCheckinWithMethod(checkinId, paymentMethod) {
    await performCheckinWithMethod(checkinId, paymentMethod);
    syncCheckinToFullscreen();
    
    // Focus scanner input again
    const input = document.getElementById('fullscreen-barcode-input');
    if (input) {
        input.value = '';
        input.focus();
    }
}

// Perform check-in from fullscreen (legacy)
async function performFullscreenCheckin(checkinId) {
    await performCheckin(checkinId);
    syncCheckinToFullscreen();
    
    // Focus scanner input again
    const input = document.getElementById('fullscreen-barcode-input');
    if (input) {
        input.value = '';
        input.focus();
    }
}

// Initialize fullscreen on page load
document.addEventListener('DOMContentLoaded', initFullscreenCheckin);

// ========== GROUP CHECKOUT FUNCTIONALITY ==========

// Open group checkout modal
function openGroupCheckoutModal() {
    if (selectedForGroup.size < 2) {
        toast.quickWarning('Gruppe', 'Velg minst 2 gjester for gruppesjekk');
        return;
    }
    
    // Get selected guests data
    const selectedGuests = [];
    let totalAmount = 0;
    
    window.pendingCheckins.forEach(checkin => {
        if (selectedForGroup.has(checkin.id)) {
            const isPaid = checkin.payment_status === 'paid';
            selectedGuests.push({
                id: checkin.id,
                name: checkin.customer_name || 'Ukjent',
                price: parseFloat(checkin.ticket_price) || 0,
                isPaid: isPaid
            });
            // Only add unpaid to total
            if (!isPaid) {
                totalAmount += parseFloat(checkin.ticket_price) || 0;
            }
        }
    });
    
    // Populate modal
    const guestList = document.getElementById('group-guest-list');
    guestList.innerHTML = selectedGuests.map(g => `
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span>${g.name}</span>
            <span class="${g.isPaid ? 'text-success' : ''}">${g.isPaid ? '<i class="bi bi-check-circle me-1"></i>Betalt' : g.price + ' kr'}</span>
        </div>
    `).join('');
    
    document.getElementById('group-total-amount').textContent = totalAmount.toFixed(0) + ' kr';
    document.getElementById('group-guest-count').textContent = selectedGuests.length + ' gjester';
    
    // Populate payment method buttons
    const paymentBtns = document.getElementById('group-payment-buttons');
    paymentBtns.innerHTML = availablePaymentMethods.map(pm => {
        const icon = pm.config?.icon || 'wallet2';
        const name = pm.method_name || pm.method_type;
        let btnClass = 'btn-outline-secondary';
        
        if (pm.method_type === 'vipps') btnClass = 'btn-warning';
        else if (pm.method_type === 'cash') btnClass = 'btn-success';
        else if (pm.method_type === 'stripe') btnClass = 'btn-primary';
        
        return `<button class="btn ${btnClass} btn-lg flex-fill" onclick="processGroupCheckout('${pm.method_type}')">
            <i class="bi bi-${icon} me-2"></i>${name}
        </button>`;
    }).join('');
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('groupCheckoutModal'));
    modal.show();
}

// Process group checkout with selected payment method
async function processGroupCheckout(paymentMethod) {
    const checkinIds = Array.from(selectedForGroup);
    
    try {
        // Close modal
        const modalEl = document.getElementById('groupCheckoutModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
        
        // Create group and process payment
        const response = await fetch('/api/create_checkin_group.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: selectedEventId,
                checkin_ids: checkinIds,
                payment_method: paymentMethod
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Gruppesjekk', `${result.checked_in_count} gjester sjekket inn!`);
            selectedForGroup.clear();
            loadCheckins(selectedEventId);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke fullføre gruppesjekk');
        }
    } catch (error) {
        console.error('Error processing group checkout:', error);
        toast.quickError('Feil', 'Kunne ikke fullføre gruppesjekk');
    }
}

</script>

<!-- Group Checkout Modal -->
<div class="modal fade" id="groupCheckoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>Gruppesjekk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="group-guest-count">0 gjester</p>
                <div id="group-guest-list" class="mb-4" style="max-height: 200px; overflow-y: auto;">
                    <!-- Guests will be populated here -->
                </div>
                <div class="bg-light rounded p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total å betale:</span>
                        <span class="fs-4 fw-bold text-primary" id="group-total-amount">0 kr</span>
                    </div>
                    <small class="text-muted">Allerede betalte gjester er ikke inkludert</small>
                </div>
                <div class="d-flex gap-2 flex-wrap" id="group-payment-buttons">
                    <!-- Payment buttons will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionReasonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Avvis påmelding</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Velg årsak(er) for avvisning:</p>
                <input type="hidden" id="rejection-registration-id">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="reject-reason-1" value="Påmeldingen inneholder mangler">
                    <label class="form-check-label" for="reject-reason-1">Påmeldingen inneholder mangler</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="reject-reason-2" value="Flere like påmeldinger registrert">
                    <label class="form-check-label" for="reject-reason-2">Flere like påmeldinger registrert</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="reject-reason-3" value="Påmeldingen kunne ikke verifiseres">
                    <label class="form-check-label" for="reject-reason-3">Påmeldingen kunne ikke verifiseres</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="reject-reason-4" value="Arrangementet har nådd maks kapasitet">
                    <label class="form-check-label" for="reject-reason-4">Arrangementet har nådd maks kapasitet</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-danger" onclick="submitRejection()">
                    <i class="bi bi-x-circle me-1"></i>Avvis påmelding
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ========== EVENT WIZARD FUNCTIONALITY ==========
let wizardCurrentStep = 1;
const wizardTotalSteps = 7;
let wizardMap = null;
let wizardMapMarker = null;
let geocodeTimeout = null;
let wizardGeocodeRevision = 0;
let eventCreationBusy = false;
let eventCreateFieldsEditor = null;
let eventEditFieldsEditor = null;
let eventCsrfToken = '';
const editEventDirtyForm = document.getElementById('edit-event-form');
editEventDirtyForm?.addEventListener('input', () => {
    if (editEventModalReady && !editEventSaveBusy) editEventDirty = true;
});
editEventDirtyForm?.addEventListener('change', () => {
    if (editEventModalReady && !editEventSaveBusy) editEventDirty = true;
});
document.getElementById('editEventModal')?.addEventListener('hide.bs.modal', event => {
    if (editEventSaveBusy) {
        event.preventDefault();
        return;
    }
    if (editEventModalReady && editEventDirty && !window.confirm('Du har ulagrede endringer. Vil du lukke uten å lagre?')) {
        event.preventDefault();
        return;
    }
    editEventDirty = false;
});
document.getElementById('editEventModal')?.addEventListener('hidden.bs.modal', () => {
    editEventModalReady = false;
});
let eventCreationDisabledControls = [];

function setEventCreationBusy(busy) {
    if (eventCreationBusy === busy) return;
    eventCreationBusy = busy;
    const form = document.getElementById('create-event-form');
    if (!form) return;
    form.setAttribute('aria-busy', String(busy));
    const submit = document.getElementById('wizard-submit');
    if (busy) {
        eventCreationDisabledControls = Array.from(form.querySelectorAll('input, select, textarea, button'))
            .map(control => ({control, disabled: control.disabled}));
        eventCreationDisabledControls.forEach(({control}) => { control.disabled = true; });
        if (submit) {
            submit.dataset.idleLabel = submit.innerHTML;
            submit.textContent = 'Lagrer…';
        }
    } else {
        eventCreationDisabledControls.forEach(({control, disabled}) => { control.disabled = disabled; });
        eventCreationDisabledControls = [];
        if (submit && submit.dataset.idleLabel !== undefined) {
            submit.innerHTML = submit.dataset.idleLabel;
            delete submit.dataset.idleLabel;
        }
    }
}

function initWizardMap() {
    const mapContainer = document.getElementById('wizard-map');
    if (!mapContainer || wizardMap) return;
    
    wizardMap = L.map('wizard-map').setView([59.9139, 10.7522], 5);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(wizardMap);
    
    const addressField = document.querySelector('input[name="event_address"]');
    const postalField = document.querySelector('input[name="event_postal"]');
    const cityField = document.querySelector('input[name="event_city"]');
    
    const geocodeHandler = () => {
        clearTimeout(geocodeTimeout);
        geocodeTimeout = setTimeout(() => geocodeWizardAddress(), 800);
    };
    
    if (addressField) addressField.addEventListener('input', geocodeHandler);
    if (postalField) postalField.addEventListener('input', geocodeHandler);
    if (cityField) cityField.addEventListener('input', geocodeHandler);
}

async function geocodeWizardAddress() {
    const address = document.querySelector('input[name="event_address"]')?.value || '';
    const postal = document.querySelector('input[name="event_postal"]')?.value || '';
    const city = document.querySelector('input[name="event_city"]')?.value || '';
    
    const fullAddress = [address, postal, city, 'Norway'].filter(x => x.trim()).join(', ');
    
    if (fullAddress.length < 5) return;
    const requestRevision = ++wizardGeocodeRevision;
    
    try {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1&countrycodes=no`;
        
        const response = await fetch(url);
        const data = await response.json();
        if (requestRevision !== wizardGeocodeRevision) return;
        
        if (data.length > 0 && wizardMap) {
            const lat = parseFloat(data[0].lat);
            const lon = parseFloat(data[0].lon);
            
            if (wizardMapMarker) {
                wizardMap.removeLayer(wizardMapMarker);
            }
            
            wizardMapMarker = L.marker([lat, lon]).addTo(wizardMap);
            wizardMapMarker.bindPopup(`<b>${data[0].display_name}</b>`).openPopup();
            
            wizardMap.setView([lat, lon], 15);
        }
    } catch (error) {
        console.error('Geocoding error:', error);
    }
}

let wizardInitialized = false;

function initEventWizard() {
    // Prevent multiple initializations (which adds duplicate event listeners)
    if (wizardInitialized) {
        // Starting a new creation always opens a clean draft, without a dialog.
        // Never replace the draft belonging to a request that is still saving.
        if (!eventCreationBusy) resetEventWizardForm();
        return;
    }
    wizardInitialized = true;
    
    const prevBtn = document.getElementById('wizard-prev');
    const nextBtn = document.getElementById('wizard-next');
    const submitBtn = document.getElementById('wizard-submit');
    const resetBtn = document.getElementById('wizard-reset');
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const nextStep = getNextValidStep(wizardCurrentStep, 1);
            wizardGoToStep(nextStep);
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            const prevStep = getNextValidStep(wizardCurrentStep, -1);
            wizardGoToStep(prevStep);
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', resetWizard);
    }
    
    // Option card click handler
    document.querySelectorAll('.wizard-option-card input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateWizardBasedOnType();
        });
    });
    
    // Min attendees triggers deadline field
    const minAttendeesField = document.getElementById('min_attendees');
    if (minAttendeesField) {
        minAttendeesField.addEventListener('input', function() {
            const container = document.getElementById('registration_deadline_container');
            if (container) {
                container.style.display = this.value && parseInt(this.value) > 0 ? 'block' : 'none';
            }
        });
    }
    
    // Venue name checkbox toggle
    const hasVenueNameCheckbox = document.getElementById('has_venue_name');
    if (hasVenueNameCheckbox) {
        hasVenueNameCheckbox.addEventListener('change', function() {
            const venueNameField = document.getElementById('venue-name-field');
            if (venueNameField) {
                venueNameField.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    // Contact field validation
    document.querySelectorAll('.contact-field-required').forEach(checkbox => {
        checkbox.addEventListener('change', validateContactFields);
    });
    
    // Load payment methods and membership plans
    resetEventWizardForm();
    loadWizardPaymentMethods();
    loadWizardMembershipPlans();
}

function eventHasMembershipAccess() {
    return document.getElementById('create-event-form')?.dataset.membershipAccess === '1';
}

function getNextValidStep(currentStep, direction) {
    const regType = document.querySelector('#create-event-form input[name="registration_type"]:checked')?.value;
    let nextStep = currentStep + direction;
    
    // For "Uten påmelding" (none) - skip steps 4 (pricing), 5 (medlemstillegg) and 6 (payment)
    // No registration = no price, no membership pricing, no payment methods
    if (regType === 'none') {
        if (nextStep === 4 || nextStep === 5 || nextStep === 6) {
            nextStep = direction > 0 ? 7 : 3;
        }
    }
    // For "Påmelding" (signup) - all steps accessible including step 5 (medlemstillegg)
    // No skipping for this registration type
    // For "Bindende" - skip step 5 AND step 6 (payment already chosen in step 1)
    else if (regType === 'binding') {
        if (nextStep === 5 || nextStep === 6) {
            nextStep = direction > 0 ? 7 : 4;
        }
    }
    if (nextStep === 5 && !eventHasMembershipAccess()) nextStep += direction;
    
    // Clamp to valid range
    if (nextStep < 1) nextStep = 1;
    if (nextStep > wizardTotalSteps) nextStep = wizardTotalSteps;
    
    return nextStep;
}

function wizardGoToStep(step) {
    if (eventCreationBusy) return;
    if (step < 1 || step > wizardTotalSteps) return;
    if (step === 5 && !eventHasMembershipAccess()) return;
    
    // Block navigation to steps that should be skipped based on registration_type
    const regType = document.querySelector('#create-event-form input[name="registration_type"]:checked')?.value;
    if (regType === 'none' && (step === 4 || step === 5 || step === 6)) {
        return; // Block steps 4, 5, 6 for "Uten påmelding"
    }
    if (regType === 'binding' && (step === 5 || step === 6)) {
        return; // Block steps 5, 6 for "Bindende"
    }
    
    // Validate current step before proceeding
    if (step > wizardCurrentStep && !validateWizardStep(wizardCurrentStep)) {
        return;
    }
    
    // Hide current step
    const currentContent = document.getElementById(`wizard-step-${wizardCurrentStep}`);
    if (currentContent) currentContent.style.display = 'none';
    
    // Update step indicators
    document.querySelectorAll('.wizard-step').forEach(stepEl => {
        const stepNum = parseInt(stepEl.dataset.step);
        stepEl.classList.remove('active', 'completed');
        if (stepNum < step) {
            stepEl.classList.add('completed');
        } else if (stepNum === step) {
            stepEl.classList.add('active');
        }
    });
    
    // Show new step
    wizardCurrentStep = step;
    const newContent = document.getElementById(`wizard-step-${step}`);
    if (newContent) newContent.style.display = 'block';
    
    // Update navigation buttons
    const prevBtn = document.getElementById('wizard-prev');
    const nextBtn = document.getElementById('wizard-next');
    const submitBtn = document.getElementById('wizard-submit');
    
    if (prevBtn) prevBtn.style.display = step > 1 ? 'block' : 'none';
    if (nextBtn) nextBtn.style.display = step < wizardTotalSteps ? 'block' : 'none';
    if (submitBtn) submitBtn.style.display = step === wizardTotalSteps ? 'block' : 'none';
    
    // Update summary on last step
    if (step === wizardTotalSteps) {
        updateWizardSummary();
        // Hide registration fields section if no registration
        const regFieldsSection = document.getElementById('registration-fields-section');
        if (regFieldsSection) {
            regFieldsSection.style.display = regType === 'none' ? 'none' : '';
        }
    }
    
    // Initialize map when entering step 3
    if (step === 3) {
        setTimeout(() => {
            initWizardMap();
            if (wizardMap) {
                wizardMap.invalidateSize();
            }
        }, 100);
    }
    
    // Handle step-specific logic
    if (step === 5) {
        updateMembershipAddonsVisibility();
    }
}

function validateWizardStep(step) {
    const form = document.getElementById('create-event-form');
    
    switch(step) {
        case 1:
            const regType = form.querySelector('input[name="registration_type"]:checked');
            if (!regType) {
                alert('Velg en registreringstype');
                return false;
            }
            
            // If binding, require payment method selection
            if (regType.value === 'binding') {
                const bindingPayment = form.querySelector('input[name="binding_payment_method"]:checked');
                if (!bindingPayment) {
                    alert('Velg en betalingsmetode for bindende påmelding');
                    return false;
                }
            }
            return true;
            
        case 2:
            const title = form.querySelector('input[name="title"]');
            const startDate = form.querySelector('input[name="start_date"]');
            const startTime = form.querySelector('input[name="start_time"]');

            if (!validateEventDateTimeInputs(form)) {
                alert('Kontroller datoer og klokkeslett. Bruk DD.MM.YYYY og HH:mm.');
                return false;
            }
            
            if (!title.value.trim()) {
                alert('Fyll inn arrangementsnavn');
                title.focus();
                return false;
            }
            if (!startDate.value) {
                alert('Velg startdato');
                startDate.focus();
                return false;
            }
            if (!startTime.value) {
                alert('Velg starttid');
                startTime.focus();
                return false;
            }
            return true;
            
        case 7:
            // Skip contact field validation for "Uten påmelding"
            const regTypeStep7 = form.querySelector('input[name="registration_type"]:checked')?.value;
            if (regTypeStep7 === 'none') {
                return true;
            }
            return validateContactFields();
            
        default:
            return true;
    }
}

function validateContactFields() {
    const emailField = document.getElementById('field_email');
    const phoneField = document.getElementById('field_phone');
    
    if (!emailField.checked && !phoneField.checked) {
        alert('Du må aktivere minst E-post eller Telefon');
        return false;
    }
    return true;
}

function updateWizardBasedOnType() {
    const regType = document.querySelector('#create-event-form input[name="registration_type"]:checked')?.value;
    
    // Show/hide members-only option based on registration type
    const membersOnlyContainer = document.getElementById('members-only-container');
    if (membersOnlyContainer) {
        membersOnlyContainer.style.display = (regType === 'signup' || regType === 'binding') ? 'block' : 'none';
    }
    
    // Show/hide registration fields section
    const regFieldsSection = document.getElementById('registration-fields-section');
    if (regFieldsSection) {
        regFieldsSection.style.display = regType !== 'none' ? 'block' : 'none';
    }
    
    // Show/hide binding payment selection
    const bindingPaymentContainer = document.getElementById('binding-payment-container');
    if (bindingPaymentContainer) {
        if (regType === 'binding') {
            bindingPaymentContainer.style.display = 'block';
            loadBindingPaymentMethods();
        } else {
            bindingPaymentContainer.style.display = 'none';
        }
    }
}

async function loadBindingPaymentMethods() {
    const bindingContainer=document.getElementById('binding-payment-methods');
    bindingContainer.textContent='Laster aktiverte betalingsmetoder…';
    try {
        const response = await fetch('/api/get_payment_methods.php');
        const result = await response.json();
        
        const container = document.getElementById('binding-payment-methods');
        if (!container) return;
        
        if (result.success && result.payment_methods) {
            const nonCashMethods = result.payment_methods.filter(pm => pm.method_type !== 'cash');
            
            if (nonCashMethods.length === 0) {
                container.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Ingen elektroniske betalingsmetoder konfigurert. Gå til Økonomi-modulen for å sette opp Vipps, Stripe eller PayPal.</div>';
                return;
            }
            
            container.innerHTML = nonCashMethods.map(pm => {
                const icon = (pm.config && pm.config.icon) ? pm.config.icon : 'wallet2';
                const name = ActivePaymentMethods.text(ActivePaymentMethods.label(pm));
                return `
                <div class="form-check mb-2">
                    <input class="form-check-input binding-payment-radio" type="radio" name="binding_payment_method" value="${pm.method_type}" id="binding_pm_${pm.method_type}">
                    <label class="form-check-label" for="binding_pm_${pm.method_type}">
                        <i class="bi bi-${icon} me-1"></i>${name}
                    </label>
                </div>`;
            }).join('');
        } else {
            container.innerHTML = '<div class="alert alert-warning mb-0">Kunne ikke laste betalingsmetoder.</div>';
        }
    } catch (error) {
        console.error('Error loading binding payment methods:', error);
        bindingContainer.textContent='Kunne ikke laste aktiverte betalingsmetoder. Velg arrangementstype på nytt for å prøve igjen.';
    }
}

function updateMembershipAddonsVisibility() {
    const regType = document.querySelector('#create-event-form input[name="registration_type"]:checked')?.value;
    const membersOnlyContainer = document.getElementById('members-only-container');
    
    if (membersOnlyContainer) {
        membersOnlyContainer.style.display = (regType === 'signup' || regType === 'binding') ? 'block' : 'none';
    }
}

function updateWizardSummary() {
    const form = document.getElementById('create-event-form');
    
    // Title
    const title = form.querySelector('input[name="title"]')?.value || '-';
    document.getElementById('summary-title').textContent = title;
    
    // Description
    const desc = form.querySelector('textarea[name="description"]')?.value || 'Ingen beskrivelse';
    document.getElementById('summary-description').textContent = desc.substring(0, 100) + (desc.length > 100 ? '...' : '');
    
    // Type
    const regType = form.querySelector('input[name="registration_type"]:checked')?.value || 'signup';
    const typeLabels = { 'signup': 'Påmelding', 'none': 'Uten påmelding', 'binding': 'Bindende' };
    document.getElementById('summary-type').textContent = typeLabels[regType] || regType;
    
    // Date
    EventFormHelpers.updateSummaryDate(form, document.getElementById('summary-date'), formatNorskDato);
    
    // Location
    const venueName = form.querySelector('input[name="venue_name"]')?.value;
    const city = form.querySelector('input[name="event_city"]')?.value;
    document.getElementById('summary-location').textContent = venueName || city || 'Ikke angitt';
    
    // Price
    const ticketPrice = form.querySelector('input[name="ticket_price"]')?.value;
    document.getElementById('summary-price').textContent = ticketPrice ? `${ticketPrice} kr` : 'Gratis';
    
    // Attendees
    const minAtt = form.querySelector('input[name="min_attendees"]')?.value;
    const maxAtt = form.querySelector('input[name="max_attendees"]')?.value;
    let attendeesText = 'Ingen grense';
    if (minAtt && maxAtt) attendeesText = `${minAtt} - ${maxAtt}`;
    else if (minAtt) attendeesText = `Min. ${minAtt}`;
    else if (maxAtt) attendeesText = `Maks. ${maxAtt}`;
    document.getElementById('summary-attendees').textContent = attendeesText;
    
    // Image preview
    const imgUrl = form.querySelector('input[name="event_image_url"]')?.value;
    const imgContainer = document.getElementById('summary-image');
    if (imgUrl) {
        imgContainer.innerHTML = `<img src="${imgUrl}" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;" onerror="this.parentElement.innerHTML='<i class=\\'bi bi-image text-muted\\' style=\\'font-size: 2rem;\\'></i>'">`;
    } else {
        imgContainer.innerHTML = '<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>';
    }
    
    // Update registration fields visibility
    updateWizardBasedOnType();
}

async function loadWizardPaymentMethods() {
    return loadPaymentMethodsForCreate();
}

async function loadWizardMembershipPlans() {
    if (!eventHasMembershipAccess() || !document.getElementById('membership-pricing-container')) return;
    try {
        const response = await fetch('/api/get_membership_plans.php');
        const result = await response.json();
        
        const container = document.getElementById('membership-pricing-container');
        const plans = result.plans || result.data || [];
        
        if (result.success && plans.length > 0) {
            // CRITICAL: Store plans in global variable for form submission
            membershipPlansData = plans;
            
            container.innerHTML = plans.map(plan => {
                // Use plan_id if available, otherwise use id
                const planId = plan.plan_id || plan.id;
                return `
                <div class="card bg-light mb-2 p-2" data-plan-id="${planId}">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label mb-0 fw-medium">${plan.name}</label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" class="form-control form-control-sm membership-price-input" name="membership_price_${planId}" data-plan-id="${planId}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input membership-public-toggle" type="checkbox" name="membership_public_${planId}" id="membership_public_${planId}" data-plan-id="${planId}" checked>
                                <label class="form-check-label small" for="membership_public_${planId}">
                                    <i class="bi bi-globe text-primary me-1"></i>Vis offentlig
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `}).join('');
        } else {
            membershipPlansData = [];
            container.innerHTML = '<p class="text-muted small">Ingen medlemsplaner funnet.</p>';
        }
    } catch (error) {
        console.error('Error loading membership plans:', error);
        membershipPlansData = [];
        document.getElementById('membership-pricing-container').innerHTML = '<p class="text-muted small">Kunne ikke laste medlemsplaner.</p>';
    }
}

function resetWizard() {
    if (eventCreationBusy) return false;
    if (!confirm('Er du sikker på at du vil nullstille skjemaet?')) return false;
    return resetEventWizardForm();
}

function resetWizardAndGoToList() {
    if (!resetWizard()) return;
    document.querySelector('[href="#events-my"]')?.click();
}

function resetEventWizardForm() {
    if (eventCreationBusy) return false;
    const form = document.getElementById('create-event-form');
    const state = {currentStep: wizardCurrentStep};
    const didReset = EventFormHelpers.resetWizard(document, form, state, () => {
        const minAttendeesInput = document.getElementById('min_attendees');
        const deadlineContainer = document.getElementById('registration_deadline_container');
        const deadlineInput = document.getElementById('registration_deadline');
        const needsDeadline = Number(minAttendeesInput?.value || 0) > 0;
        if (deadlineContainer) deadlineContainer.style.display = needsDeadline ? 'block' : 'none';
        if (deadlineInput) {
            deadlineInput.required = needsDeadline;
            if (!needsDeadline) deadlineInput.value = '';
        }

        const venueNameField = document.getElementById('venue-name-field');
        const hasVenueNameCheckbox = document.getElementById('has_venue_name');
        if (venueNameField) venueNameField.style.display = 'none';
        if (hasVenueNameCheckbox) hasVenueNameCheckbox.checked = false;

        if (geocodeTimeout) {
            clearTimeout(geocodeTimeout);
            geocodeTimeout = null;
        }
        wizardGeocodeRevision++;
        if (wizardMap && wizardMapMarker) {
            wizardMap.removeLayer(wizardMapMarker);
            wizardMapMarker = null;
        }
        if (wizardMap) {
            wizardMap.setView([59.9139, 10.7522], 5);
            wizardMap.invalidateSize();
        }

        updateWizardBasedOnType();
        updateMembershipAddonsVisibility();
        updateWizardSummary();
        if (eventCreateFieldsEditor) {
            eventCreateFieldsEditor.reset();
            eventCreateFieldsEditor.setRegistrationType('signup').catch(error => console.warn('Could not reset form-field defaults:', error));
        }
        // Native reset restores the loaded plans/methods and their defaults.
        // Refetching here could overwrite the next event or erase configuration
        // when the network fails after the previous event was saved successfully.
    });
    wizardCurrentStep = state.currentStep;
    return didReset;
}

function showWizardSuccess() {
    if (!resetEventWizardForm()) return;

    // Return to the event list after success; reopening the wizard starts clean.
    const myEventsTab = document.querySelector('[href="#events-my"]');
    if (myEventsTab) {
        myEventsTab.click();
        setTimeout(() => {
            if (typeof loadEvents === 'function') loadEvents();
        }, 100);
    }
}

async function initEventFormFieldsEditor() {
    const host = document.getElementById('create-event-fields-editor');
    if (!host || !window.EntityFormFields) return;
    const initialRegistrationType = document.querySelector('#create-event-form input[name="registration_type"]:checked')?.value || 'signup';
    eventCreateFieldsEditor = EntityFormFields.mount(host, {entityType: 'event', entityId: null, createMode: true, registrationType: initialRegistrationType});
    document.querySelectorAll('#create-event-form input[name="registration_type"]').forEach(radio => {
        radio.addEventListener('change', () => {
            eventCreateFieldsEditor?.setRegistrationType(radio.value).catch(error => console.warn('Could not load event field defaults:', error));
        });
    });
    try {
        await eventCreateFieldsEditor.refresh();
    } catch (error) {
        console.error('Could not load event form field defaults:', error);
    }
}

async function openEventFormFields(eventId) {
    if (eventEditFieldsEditor && !eventEditFieldsEditor.confirmClose()) return;
    const host = document.getElementById('event-fields-editor');
    const event = (typeof allEvents !== 'undefined' ? allEvents : []).find(item => String(item.id) === String(eventId));
    const title = document.getElementById('eventFieldsModalTitle');
    if (title && event) title.textContent = `Skjemafelt · ${event.title || 'Arrangement'}`;
    if (!host) return;
    if (eventEditFieldsEditor) eventEditFieldsEditor = null;
    eventEditFieldsEditor = EntityFormFields.mount(host, {entityType: 'event', entityId: eventId, registrationType: event?.registration_type || ''});
    try {
        await eventEditFieldsEditor.refresh();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('eventFieldsModal')).show();
    } catch (error) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('eventFieldsModal')).show();
    }
}

// Initialize wizard on DOM ready
document.addEventListener('DOMContentLoaded', initEventWizard);
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('eventFieldsModal')?.addEventListener('hide.bs.modal', event=>{
        if(eventEditFieldsEditor && !eventEditFieldsEditor.confirmClose()) event.preventDefault();
    });
    initEventFormFieldsEditor();
});

// Handle subtab change to load data when entering events tabs
document.addEventListener('subtab-changed', function(event) {
    if (event.detail.module === 'events') {
        if (event.detail.subtab === 'events-dashboard') {
            loadEvents();
        } else if (event.detail.subtab === 'events-my') {
            loadEvents();
            loadPendingRegistrations();
        } else if (event.detail.subtab === 'events-create') {
            initEventWizard();
        } else if (event.detail.subtab === 'events-registrations') {
            loadPendingRegistrations();
            loadAllRegistrations();
        } else if (event.detail.subtab === 'events-results') {
            loadEventsResults();
        }
    }
});
</script>
