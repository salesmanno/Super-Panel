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

.event-image {
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-size: cover;
    background-position: center;
    position: relative;
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

<div class="module-content">
    <div id="events-my" class="subtab-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="bi bi-calendar-event me-2"></i>Dine arrangement</h4>
                <p class="text-muted mb-0">Oversikt over alle dine arrangementer 
                    <a href="#" onclick="document.querySelector('[href=\'#events-registrations\']').click(); return false;" style="text-decoration: none;">
                        <span class="badge bg-danger ms-1" id="pending-count-badge" style="display: none;">0 venter godkjenning</span>
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
                        <div class="wizard-step" data-step="5">
                            <div class="step-circle">5</div>
                            <div class="step-label">Medlemskap</div>
                        </div>
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

                <form id="create-event-form">
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
                                    <input type="text" class="form-control form-control-lg" name="title" placeholder="F.eks. Sommerfest 2025" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Beskrivelse</label>
                                    <textarea class="form-control" name="description" rows="3" placeholder="Beskriv arrangementet..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Arrangementsbilde (URL)</label>
                                    <input type="url" class="form-control" name="event_image_url" placeholder="https://...">
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
                                    <input type="date" class="form-control" name="registration_deadline" id="registration_deadline">
                                    <small class="text-muted">Må være før startdato</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Startdato *</label>
                                        <input type="date" class="form-control" name="start_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Starttid *</label>
                                        <input type="time" class="form-control" name="start_time" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sluttdato</label>
                                        <input type="date" class="form-control" name="end_date">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sluttid</label>
                                        <input type="time" class="form-control" name="end_time">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Aldersgrense</label>
                                    <input type="number" class="form-control" name="age_limit" min="0" placeholder="Ingen aldersgrense">
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
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="has_venue" checked>
                                    <label class="form-check-label" for="has_venue">
                                        Arrangementet har et fysisk sted
                                    </label>
                                </div>
                                
                                <div id="venue-fields">
                                    <div class="mb-3">
                                        <label class="form-label">Gateadresse</label>
                                        <input type="text" class="form-control address-field" name="event_address" placeholder="Gatenavn og nummer">
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="has_venue_name">
                                        <label class="form-check-label small" for="has_venue_name">
                                            Legg til eget stedsnavn (f.eks. Kulturhuset, Konsertlokale)
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3" id="venue-name-field" style="display: none;">
                                        <label class="form-label">Stedsnavn</label>
                                        <input type="text" class="form-control" name="venue_name" placeholder="F.eks. Kulturhuset">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Postnummer</label>
                                            <input type="text" class="form-control zip-field" name="event_postal" maxlength="4" placeholder="0000">
                                        </div>
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">By</label>
                                            <input type="text" class="form-control city-field" name="event_city" placeholder="Stedsnavn">
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
                            <p class="text-muted">Sett ordinærpris og eventuell medlemspris</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label class="form-label">Ordinær billettpris (NOK)</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg" name="ticket_price" id="ticket_price" placeholder="0.00">
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
                                
                                <h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Medlemspriser</h6>
                                <div id="membership-pricing-container">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        <small class="text-muted">Laster medlemsplaner...</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Membership Add-ons -->
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
                                        <label class="form-label small mb-1">Fornavn *</label>
                                        <input type="text" class="form-control form-control-sm" id="guest-fornavn" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Etternavn *</label>
                                        <input type="text" class="form-control form-control-sm" id="guest-etternavn" required>
                                    </div>
                                </div>
                                <div class="mb-2 mt-2">
                                    <label class="form-label small mb-1">E-post</label>
                                    <input type="email" class="form-control form-control-sm" id="guest-email">
                                </div>
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
                    <span class="badge bg-warning text-dark ms-2" id="pending-count-badge" style="display: none;">0</span>
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

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Rediger arrangement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-event-form">
                    <input type="hidden" name="event_id" id="edit-event-id">
                    
                    <div class="row">
                        <div class="col-md-<?php echo isset($hasMembershipAccess) && $hasMembershipAccess ? '8' : '12'; ?> mb-3">
                            <label class="form-label">Arrangement navn *</label>
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
                    
                    <div class="card border-secondary mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Registreringstype (kan ikke endres)</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Registreringstypen er låst og kan ikke endres etter opprettelse</p>
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
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Startdato *</label>
                            <input type="date" class="form-control" name="start_date" id="edit-start-date" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Starttid *</label>
                            <input type="time" class="form-control" name="start_time" id="edit-start-time" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttdato</label>
                            <input type="date" class="form-control" name="end_date" id="edit-end-date">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttid</label>
                            <input type="time" class="form-control" name="end_time" id="edit-end-time">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Aldersgrense</label>
                            <input type="number" class="form-control" name="age_limit" id="edit-age-limit">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Stedsnavn</label>
                            <input type="text" class="form-control" name="venue_name" id="edit-venue-name">
                        </div>
                    </div>

                    <div class="card bg-light mb-4 p-3">
                        <h6 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Lokasjon</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Gateadresse</label>
                                <input type="text" class="form-control address-field" name="event_address" id="edit-event-address">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">By</label>
                                <input type="text" class="form-control city-field" name="event_city" id="edit-event-city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postnummer</label>
                                <input type="text" class="form-control zip-field" name="event_postal" id="edit-event-postal" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min. deltakere</label>
                            <input type="number" class="form-control" name="min_attendees" id="edit-min-attendees">
                        </div>
                        <div class="col-md-3 mb-3" id="edit-registration-deadline-container" style="display: none;">
                            <label class="form-label">Frist for min. deltakere *</label>
                            <input type="date" class="form-control" name="registration_deadline" id="edit-registration-deadline">
                            <small class="form-text text-muted">Må være før startdato</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Maks. deltakere</label>
                            <input type="number" class="form-control" name="max_attendees" id="edit-max-attendees">
                        </div>
                    </div>

                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Priser og medlemskap</h6>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3" id="edit-ticket-price-container">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price" id="edit-ticket-price">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="memberverification" id="edit-memberverification">
                                <label class="form-check-label" for="edit-memberverification">
                                    <i class="bi bi-shield-check text-success me-1"></i>Autogodkjenning og Autofyll for medlemmer for dette arrangement
                                </label>
                            </div>
                            <small class="form-text text-muted d-block mt-2">Medlemmer blir automatisk godkjent og får autofylt informasjon ved registrering</small>
                        </div>
                    </div>
                    
                    <div id="edit-membership-pricing-container">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <small class="text-muted">Laster medlemsplaner...</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-credit-card-2-front me-2"></i>Betalingsmetoder</h6>
                    <div id="edit-payment-methods-container" class="mb-3">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <small class="text-muted">Laster betalingsmetoder...</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Standard betalingsmetode</label>
                            <select class="form-select" name="default_payment_method" id="edit-default-payment-method">
                                <option value="">Velg standardmetode...</option>
                            </select>
                            <small class="form-text text-muted">Denne metoden vises som anbefalt ved betaling</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Bilde-URL</label>
                            <input type="url" class="form-control" name="event_image_url" id="edit-event-image-url">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-ui-checks me-2"></i>Påmeldingsfelt</h6>
                    
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Påkrevde felt:</strong> Fornavn og Etternavn er alltid påkrevd.
                    </div>
                    
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Kontaktinformasjon (minst én påkrevd):</strong><br>
                        <small>Velg E-post og/eller Telefonnummer. Minst én må være aktivert for å verifisere medlemskap.</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input edit-contact-field-required" type="checkbox" name="field_email" id="edit-field-email">
                                <label class="form-check-label" for="edit-field-email">
                                    <i class="bi bi-envelope text-primary me-1"></i> E-post
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input edit-contact-field-required" type="checkbox" name="field_phone" id="edit-field-phone">
                                <label class="form-check-label" for="edit-field-phone">
                                    <i class="bi bi-telephone text-primary me-1"></i> Telefon
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="field_street" id="edit-field-street">
                                <label class="form-check-label" for="edit-field-street">
                                    <i class="bi bi-house text-primary me-1"></i> Gateadresse
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="field_zip" id="edit-field-zip">
                                <label class="form-check-label" for="edit-field-zip">
                                    <i class="bi bi-mailbox text-primary me-1"></i> Postnummer
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="field_city" id="edit-field-city">
                                <label class="form-check-label" for="edit-field-city">
                                    <i class="bi bi-geo-alt text-primary me-1"></i> Poststed
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="field_age" id="edit-field-age">
                                <label class="form-check-label" for="edit-field-age">
                                    <i class="bi bi-person-badge text-primary me-1"></i> Alder
                                </label>
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
                        <input type="date" class="form-control" name="date" required>
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
                        <input type="date" class="form-control" name="date" required>
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
                <h6 class="mb-3">Transaksjoner</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Type</th>
                                <th>Beskrivelse</th>
                                <th>Beløp</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="details-transactions-list">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load events when module is active
    if (document.getElementById('events-my')) {
        loadEvents();
        // Load pending registrations count for badge
        loadPendingRegistrations();
    }
    
    // Load membership plans and payment methods for create form
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
            
            const formData = new FormData(this);
            
            // Get registration_type directly from radio buttons (more reliable than FormData)
            const selectedRegType = document.querySelector('input[name="registration_type"]:checked');
            const registrationType = selectedRegType ? selectedRegType.value : 'signup';
            
            // Validate that at least one contact field is selected
            const emailChecked = document.getElementById('field_email').checked;
            const phoneChecked = document.getElementById('field_phone').checked;
            
            if (!emailChecked && !phoneChecked) {
                toast.quickWarning('Validering', 'Du må velge minst ett kontaktfelt (E-post eller Telefonnummer) for å verifisere medlemskap');
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
                const deadlineDate = new Date(registrationDeadline);
                const eventStartDate = new Date(startDate);
                
                if (deadlineDate >= eventStartDate) {
                    toast.quickWarning('Validering', 'Frist for min. deltakere må være før startdato');
                    return;
                }
            }
            
            // Collect membership plan pricing
            const membershipPricing = [];
            membershipPlansData.forEach(plan => {
                const priceInput = document.querySelector(`input[data-plan-id="${plan.plan_id}"]`);
                const publicCheckbox = document.querySelector(`input[name="plan_public_${plan.plan_id}"]`);
                if (priceInput && priceInput.value) {
                    membershipPricing.push({
                        plan_id: plan.plan_id,
                        price: parseFloat(priceInput.value),
                        public_display: publicCheckbox && publicCheckbox.checked ? 1 : 0
                    });
                }
            });
            
            // Collect allowed payment methods from Economy configuration
            const allowedPaymentMethods = [];
            document.querySelectorAll('#payment-methods-container input[type="checkbox"]:checked').forEach(checkbox => {
                allowedPaymentMethods.push(checkbox.dataset.methodType);
            });
            
            // Build payment timing methods array: always has 'arrival', plus economy methods if any selected
            const paymentTimingMethods = ['arrival'];
            allowedPaymentMethods.forEach(method => {
                if (method !== 'cash') {
                    paymentTimingMethods.push(method); // Add actual method type (card, vipps, etc)
                }
            });
            
            // Get selected default payment method
            const defaultPaymentMethodSelect = document.getElementById('default-payment-method');
            const defaultPaymentMethod = defaultPaymentMethodSelect ? defaultPaymentMethodSelect.value : (allowedPaymentMethods[0] || 'cash');
            
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
                ticket_price: formData.get('ticket_price') || 0,
                event_image_url: formData.get('event_image_url') || null,
                is_member_exclusive: formData.get('is_member_exclusive') ? 1 : 0,
                memberverification: formData.get('memberverification') ? 1 : 0,
                registration_type: registrationType,
                payment_timing_methods: paymentTimingMethods,
                membership_pricing: membershipPricing,
                allowed_payment_methods: allowedPaymentMethods,
                default_payment_method: defaultPaymentMethod,
                // Registration fields
                field_email: formData.get('field_email') ? 1 : 0,
                field_phone: formData.get('field_phone') ? 1 : 0,
                field_street: formData.get('field_street') ? 1 : 0,
                field_zip: formData.get('field_zip') ? 1 : 0,
                field_city: formData.get('field_city') ? 1 : 0,
                field_postal: formData.get('field_postal') ? 1 : 0,
                field_age: formData.get('field_age') ? 1 : 0
            };

            try {
                const response = await fetch('/api/create_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    toast.quickSuccess('Arrangement', 'Arrangement opprettet!');
                    createEventForm.reset();
                    // Switch to "Dine arrangement" tab first
                    document.querySelector('[href="#events-my"]').click();
                    // Then load events once
                    setTimeout(() => loadEvents(), 100);
                } else {
                    toast.quickError('Feil', result.message || 'Kunne ikke opprette arrangement');
                }
            } catch (error) {
                console.error('Error creating event:', error);
                toast.quickError('Feil', 'Feil ved opprettelse av arrangement');
            }
        });
    }
});

// Prevent multiple simultaneous calls
let loadEventsRunning = false;

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
            eventsList.innerHTML = `<div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> ${result.message || 'Kunne ikke laste arrangementer'}
            </div>`;
            return;
        }
        
        if (result.success && result.events && result.events.length > 0) {
            // DEDUPLICATE: Remove duplicate events by ID
            const uniqueEvents = Array.from(new Map(result.events.map(e => [e.id, e])).values());
            
            console.log('Rendering', uniqueEvents.length, 'unique events');
            
            eventsList.innerHTML = uniqueEvents.map(event => {
                const startDate = new Date(event.start_date + ' ' + event.start_time);
                const dateStr = startDate.toLocaleDateString('no-NO', { day: 'numeric', month: 'short', year: 'numeric' });
                const timeStr = event.start_time ? event.start_time.substring(0, 5) : '';
                
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
                
                const imageHtml = event.event_image_url 
                    ? `<div class="event-image" style="background-image: url('${event.event_image_url}')"></div>`
                    : `<div class="event-image-placeholder"><i class="bi bi-calendar-event"></i></div>`;
                
                const memberBadge = event.is_member_exclusive == 1 
                    ? '<span class="badge bg-warning event-badge-overlay">Kun medlemmer</span>' 
                    : '';
                
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
                        <div class="event-card">
                            ${imageHtml}
                            ${memberBadge}
                            <div class="event-body" onclick="toggleEventResults('${event.id}')" style="cursor: pointer;">
                                <h5 class="event-title">${event.title}</h5>
                                <div class="event-meta">
                                    ${metaItems}
                                </div>
                            </div>
                            <div class="event-footer">
                                <div>${priceDisplay}</div>
                                <div class="event-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); viewEvent('${event.id}')" title="Vis detaljer">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" data-event='${JSON.stringify(event).replace(/'/g, "&apos;")}' onclick="event.stopPropagation(); editEvent('${event.id}')" title="Rediger">
                                        <i class="bi bi-pencil"></i>
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

function performDeleteEvent(eventId, deleteMode) {
    const deleteTypeLabel = deleteMode === 'soft' ? 'gjort usynlig' : 'slettet permanent';
    
    fetch('/api/delete_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            event_id: eventId,
            delete_mode: deleteMode
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
async function loadPaymentMethods() {
    try {
        const response = await fetch('/api/get_payment_methods.php');
        const result = await response.json();
        if (result.success) {
            availablePaymentMethods = result.payment_methods || [];
        }
    } catch (error) {
        console.error('Error loading payment methods:', error);
        availablePaymentMethods = [{ method_type: 'cash', method_name: 'Kontant', config: { icon: 'cash-stack' } }];
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
    let buttons = '<div class="btn-group" role="group">';
    
    availablePaymentMethods.forEach(pm => {
        const icon = pm.config?.icon || 'wallet2';
        const name = pm.method_name || pm.method_type;
        let btnClass = 'btn-outline-secondary';
        
        if (pm.method_type === 'vipps') btnClass = 'btn-outline-warning';
        else if (pm.method_type === 'cash') btnClass = 'btn-outline-success';
        else if (pm.method_type === 'stripe') btnClass = 'btn-outline-primary';
        
        buttons += `<button class="btn btn-sm ${btnClass}" onclick="performCheckinWithMethod('${checkinId}', '${pm.method_type}')" title="${name}">
            <i class="bi bi-${icon}"></i>
        </button>`;
    });
    
    buttons += `</div>
        <button class="btn btn-sm btn-outline-dark ms-1" onclick="markNoShow('${checkinId}', '${escapedName}')" title="No-show">
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
                const eventDate = new Date(event.start_date + ' ' + event.start_time);
                const dateStr = eventDate.toLocaleDateString('no-NO', { 
                    day: 'numeric', 
                    month: 'short', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const escapedTitle = event.title.replace(/'/g, "\\'").replace(/"/g, '\\"');
                const escapedDateStr = dateStr.replace(/'/g, "\\'");
                
                const hasImage = event.event_image_url && event.event_image_url.trim() !== '';
                const imageStyle = hasImage 
                    ? `background-image: url('${event.event_image_url}'); background-size: cover; background-position: center;`
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
            document.getElementById(`checked-${eventId}`).textContent = result.summary.checked_in_count || 0;
        }
    } catch (error) {
        console.error('Error loading check-in count:', error);
    }
}

let currentEventTitle = '';
let currentEventDate = '';

function openEventDetail(eventId, eventTitle, eventDate) {
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
    loadCheckins(eventId);
    loadEventDetailsForCheckin(eventId);
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
            const paymentMethods = result.event.payment_methods_json ? JSON.parse(result.event.payment_methods_json) : ['cash'];
            const methodNames = { 'cash': 'Kontant', 'card': 'Kort', 'vipps': 'Vipps', 'invoice': 'Faktura', 'bank_transfer': 'Bankoverføring' };
            paymentMethods.forEach(method => {
                const option = document.createElement('option');
                option.value = method;
                option.textContent = methodNames[method] || method;
                if (method === result.event.default_payment_method) option.selected = true;
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
    
    const formData = {
        event_id: selectedEventId,
        attendee_first_name: document.getElementById('guest-fornavn').value.trim(),
        attendee_last_name: document.getElementById('guest-etternavn').value.trim(),
        attendee_email: document.getElementById('guest-email').value.trim() || null,
        membership_plan_id: document.getElementById('guest-membership-plan').value || null,
        payment_method: document.getElementById('guest-payment-method').value,
        payment_status: document.getElementById('guest-payment-status').value,
        number_of_people: numberOfPeople
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
    try {
        const response = await fetch(`/api/get_event_fields.php?event_id=${eventId}`);
        const result = await response.json();
        
        const container = document.getElementById('dynamic-registration-fields');
        container.innerHTML = '';
        
        if (result.success && result.fields && result.fields.length > 0) {
            const row = document.createElement('div');
            row.className = 'row';
            
            result.fields.forEach(field => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-6 mb-3';
                
                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = field.field_label + (field.is_required ? ' *' : '');
                
                let input;
                if (field.field_type === 'textarea') {
                    input = document.createElement('textarea');
                    input.rows = 2;
                } else {
                    input = document.createElement('input');
                    input.type = field.field_type || 'text';
                }
                
                input.className = 'form-control';
                input.name = 'field_' + field.field_name;
                if (field.is_required) {
                    input.required = true;
                }
                
                colDiv.appendChild(label);
                colDiv.appendChild(input);
                row.appendChild(colDiv);
            });
            
            container.appendChild(row);
        }
    } catch (error) {
        console.error('Error loading event fields:', error);
    }
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
        payment_method: isPaying ? formData.get('payment_method') : 'free',
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
                        ? new Date(checkin.check_in_time).toLocaleTimeString('no-NO', {hour: '2-digit', minute: '2-digit'}) 
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
                                <div class="attendee-name">${customerName}</div>
                                ${customerEmail ? `<div class="attendee-email">${customerEmail}</div>` : ''}
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
                               data-name="${customerName.replace(/"/g, '&quot;')}"
                               onchange="toggleGroupSelection('${checkin.id}', ${isPaid})"
                               ${selectedForGroup.has(checkin.id) ? 'checked' : ''}>`;
                    
                    return `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    ${groupCheckbox}
                                    <div>
                                        <div class="attendee-name">${customerName}</div>
                                        ${customerEmail ? `<div class="attendee-email">${customerEmail}</div>` : ''}
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
    const paymentMethod = document.getElementById('guest-payment-method')?.value || 'cash';
    
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

// Load events results with cards layout
async function loadEventsResults() {
    console.log('loadEventsResults() called');
    const resultsList = document.getElementById('events-results-list');
    resultsList.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Laster resultater...</div>';
    
    try {
        const response = await fetch('/api/get_events_results.php');
        console.log('API response status:', response.status);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        console.log('API result:', result);
        
        if (!result.success) throw new Error(result.message || 'Ukjent feil');
        
        // Show/update summary
        const summaryDiv = document.getElementById('events-results-summary');
        if (summaryDiv && result.summary) {
            summaryDiv.style.display = 'block';
            document.getElementById('summary-total-events').textContent = result.summary.total_events;
            document.getElementById('summary-total-revenue').textContent = parseFloat(result.summary.total_revenue).toFixed(0) + ' kr';
            document.getElementById('summary-total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('summary-upcoming-events').textContent = result.summary.upcoming_events;
            
            // New stats
            const mostProf = result.summary.most_profitable || {title: 'N/A', profit: 0};
            const mostExp = result.summary.most_expensive || {title: 'N/A', price: 0};
            const mostAtt = result.summary.most_attended || {title: 'N/A', attendees: 0};
            
            document.getElementById('summary-most-profitable').textContent = mostProf.title ? mostProf.title.substring(0, 15) : '-';
            document.getElementById('summary-most-profitable-value').textContent = parseFloat(mostProf.profit || 0).toFixed(0) + ' kr';
            
            document.getElementById('summary-most-expensive').textContent = mostExp.title ? mostExp.title.substring(0, 15) : '-';
            document.getElementById('summary-most-expensive-value').textContent = parseFloat(mostExp.price || 0).toFixed(0) + ' kr';
            
            document.getElementById('summary-most-attended').textContent = mostAtt.title ? mostAtt.title.substring(0, 15) : '-';
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
                                    const startDate = new Date(event.start_date).toLocaleDateString('no-NO', { day: 'numeric', month: 'short', year: 'numeric' });
                                    const netColor = event.net_result >= 0 ? 'text-success' : 'text-danger';
                                    
                                    return `
                                        <tr>
                                            <td>
                                                <strong>${event.title}</strong>
                                            </td>
                                            <td>
                                                ${startDate}
                                                ${event.start_time ? `<br><small class="text-muted">${event.start_time}</small>` : ''}
                                            </td>
                                            <td>
                                                <span class="badge ${statusBadge}">${event.status}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">${event.attendee_count || 0}</span>
                                            </td>
                                            <td class="text-end text-success">
                                                <strong>${parseFloat(event.income || 0).toFixed(0)} kr</strong>
                                            </td>
                                            <td class="text-end text-danger">
                                                ${parseFloat(event.expense || 0).toFixed(0)} kr
                                            </td>
                                            <td class="text-end fw-bold ${netColor}">
                                                ${parseFloat(event.net_result || 0).toFixed(0)} kr
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
        document.getElementById('events-results-list').innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Feil ved lasting av resultater
                </div>
            </div>
        `;
    }
}

// Load results when results tab is shown
const resultsTabLink = document.querySelector('[href="#events-results"]');
if (resultsTabLink) {
    resultsTabLink.addEventListener('shown.bs.tab', function() {
        loadEventsResults();
    });
    // Also try click event as fallback
    resultsTabLink.addEventListener('click', function(e) {
        e.preventDefault();
        setTimeout(() => loadEventsResults(), 100);
    });
}

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

// Load event finances from unified_ledger
async function loadEventFinances(eventId) {
    try {
        const response = await fetch(`/api/get_event_finances.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            const incomeList = document.getElementById(`event-income-${eventId}`);
            const expenseList = document.getElementById(`event-expense-${eventId}`);
            
            // Display income
            if (result.income && result.income.length > 0) {
                incomeList.innerHTML = result.income.map(item => {
                    const date = new Date(item.transaction_date).toLocaleDateString('no-NO');
                    return `
                        <div class="event-finance-item">
                            <div>
                                <span>${item.item_name}</span>
                                <small class="text-muted d-block">${date} - ${item.payment_method || 'Kontant'}</small>
                            </div>
                            <strong class="text-success">+${parseFloat(item.selling_price).toFixed(2)} NOK</strong>
                        </div>
                    `;
                }).join('');
            } else {
                incomeList.innerHTML = '<small class="text-muted">Ingen inntekter registrert</small>';
            }
            
            // Display expenses
            if (result.expenses && result.expenses.length > 0) {
                expenseList.innerHTML = result.expenses.map(item => {
                    const date = new Date(item.transaction_date).toLocaleDateString('no-NO');
                    return `
                        <div class="event-finance-item">
                            <div>
                                <span>${item.item_name}</span>
                                <small class="text-muted d-block">${date} - ${item.payment_method || 'Kontant'}</small>
                            </div>
                            <strong class="text-danger">-${Math.abs(parseFloat(item.selling_price)).toFixed(2)} NOK</strong>
                        </div>
                    `;
                }).join('');
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
        const response = await fetch('/api/add_event_finance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: eventId,
                type: 'income',
                item_name: description,
                amount: amount,
                payment_method: 'cash'
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
        const response = await fetch('/api/add_event_finance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: eventId,
                type: 'expense',
                item_name: description,
                amount: amount,
                payment_method: 'cash'
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
async function editEvent(eventId) {
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
        setFieldValue('edit-start-date', event.start_date);
        setFieldValue('edit-start-time', event.start_time);
        setFieldValue('edit-end-date', event.end_date);
        setFieldValue('edit-end-time', event.end_time);
        setFieldValue('edit-age-limit', event.age_limit);
        setFieldValue('edit-venue-name', event.venue_name);
        setFieldValue('edit-event-address', event.event_address);
        setFieldValue('edit-event-city', event.event_city);
        setFieldValue('edit-event-postal', event.event_postal);
        setFieldValue('edit-min-attendees', event.min_attendees);
        setFieldValue('edit-registration-deadline', event.registration_deadline);
        setFieldValue('edit-max-attendees', event.max_attendees);
        setFieldValue('edit-ticket-price', event.ticket_price || 0);
        setFieldValue('edit-default-payment-method', event.default_payment_method);
        setFieldValue('edit-event-image-url', event.event_image_url);
        
        // Set registration type radio button
        const registrationType = event.registration_type || 'signup';
        const registrationRadio = document.querySelector(`input[name="registration_type"][value="${registrationType}"]`);
        if (registrationRadio) {
            registrationRadio.checked = true;
        }
        
        // Set checkbox
        const memberExclusiveCheckbox = document.getElementById('edit-member-exclusive');
        if (memberExclusiveCheckbox) {
            memberExclusiveCheckbox.checked = event.is_member_exclusive == 1;
        }
        
        // Show/hide registration deadline if min_attendees is set
        const editDeadlineContainer = document.getElementById('edit-registration-deadline-container');
        if (event.min_attendees && parseInt(event.min_attendees) > 0) {
            editDeadlineContainer.style.display = 'block';
        } else {
            editDeadlineContainer.style.display = 'none';
        }
        
        // Fetch and set registration fields - safe parsing
        try {
            const fieldsResponse = await fetch(`/api/get_event_registration_fields.php?event_id=${eventId}`);
            
            if (fieldsResponse.ok) {
                const fieldsResult = await fieldsResponse.json();
                
                // Reset all optional field checkboxes
                document.getElementById('edit-field-email').checked = false;
                document.getElementById('edit-field-phone').checked = false;
                document.getElementById('edit-field-street').checked = false;
                document.getElementById('edit-field-zip').checked = false;
                document.getElementById('edit-field-city').checked = false;
                document.getElementById('edit-field-postal').checked = false;
                document.getElementById('edit-field-age').checked = false;
                document.getElementById('edit-memberverification').checked = false;
                
                if (fieldsResult.success && fieldsResult.fields && fieldsResult.fields.length > 0) {
                    fieldsResult.fields.forEach(field => {
                        const checkbox = document.getElementById(`edit-field-${field.field_name}`);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                    console.log('Loaded registration fields:', fieldsResult.fields);
                } else {
                    console.log('No registration fields returned for this event');
                }
                
                // Load memberverification from events_eventdata directly
                if (event.memberverification) {
                    document.getElementById('edit-memberverification').checked = true;
                }
            } else {
                console.warn(`HTTP ${fieldsResponse.status}: Kunne ikke hente registreringsfelt - fortsetter likevel`);
            }
        } catch (fieldError) {
            console.warn('Error loading registration fields:', fieldError);
            // Continue - registration fields are optional for modal to open
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
        
        // Load membership pricing for edit modal
        try {
            const pricingResponse = await fetch(`/api/get_event_membership_pricing.php?event_id=${eventId}`);
            if (pricingResponse.ok) {
                const pricingResult = await pricingResponse.json();
                const container = document.getElementById('edit-membership-pricing-container');
                container.innerHTML = '';
                
                if (pricingResult.success && pricingResult.pricing && pricingResult.pricing.length > 0) {
                    pricingResult.pricing.forEach((pricing, index) => {
                        const priceDiv = document.createElement('div');
                        const isPublic = pricing.public_display == 1 || pricing.public_display === true;
                        priceDiv.className = `card mb-2 border-1 ${!isPublic ? 'opacity-50 bg-light' : ''}`;
                        const publicBadge = isPublic ? '<span class="badge bg-success ms-2">Offentlig</span>' : '<span class="badge bg-warning ms-2">Privat (skjult)</span>';
                        
                        priceDiv.innerHTML = `
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <strong>${pricing.plan_name}</strong>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-tag me-1"></i>Pris: <strong>${pricing.ticket_price} kr</strong>
                                        </div>
                                    </div>
                                    <div class="col-auto text-end">
                                        <input type="hidden" class="edit-membership-plan-id" value="${pricing.plan_id}">
                                        <input type="hidden" class="edit-membership-price" value="${pricing.ticket_price}">
                                        <div>
                                            <span class="badge bg-primary">${pricing.ticket_price} kr</span>
                                            ${publicBadge}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        container.appendChild(priceDiv);
                    });
                } else {
                    container.innerHTML = '<div class="alert alert-info small mb-0"><i class="bi bi-info-circle me-2"></i>Ingen medlemskapspriser konfigurert for dette arrangementet</div>';
                }
            }
        } catch (pricingError) {
            console.warn('Error loading membership pricing:', pricingError);
            // Continue - membership pricing is optional
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
        modal.show();
        
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
    const form = document.getElementById('edit-event-form');
    const formData = new FormData(form);
    
    // Validate that at least one contact field is selected
    const emailChecked = document.getElementById('edit-field-email').checked;
    const phoneChecked = document.getElementById('edit-field-phone').checked;
    
    if (!emailChecked && !phoneChecked) {
        toast.quickWarning('Validering', 'Du må velge minst ett kontaktfelt (E-post eller Telefonnummer) for å verifisere medlemskap');
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
        const deadlineDate = new Date(registrationDeadline);
        const eventStartDate = new Date(startDate);
        
        if (deadlineDate >= eventStartDate) {
            toast.quickWarning('Validering', 'Frist for min. deltakere må være før startdato');
            return;
        }
    }
    
    // Collect allowed payment methods from Economy configuration
    const allowedPaymentMethods = [];
    document.querySelectorAll('#edit-payment-methods-container input[type="checkbox"]:checked').forEach(checkbox => {
        allowedPaymentMethods.push(checkbox.dataset.methodType);
    });
    
    // Build payment timing methods array: always has 'arrival', plus economy methods if any selected
    const paymentTimingMethods = ['arrival'];
    allowedPaymentMethods.forEach(method => {
        if (method !== 'cash') {
            paymentTimingMethods.push(method); // Add actual method type (card, vipps, etc)
        }
    });
    
    // Get selected default payment method from dropdown
    const editDefaultPaymentMethodSelect = document.getElementById('edit-default-payment-method');
    const defaultPaymentMethod = editDefaultPaymentMethodSelect ? editDefaultPaymentMethodSelect.value : (allowedPaymentMethods[0] || 'cash');
    
    const data = {
        event_id: formData.get('event_id'),
        title: formData.get('title'),
        description: formData.get('description'),
        registration_type: formData.get('registration_type') || 'signup',
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
        is_member_exclusive: formData.get('is_member_exclusive') ? 1 : 0,
        memberverification: formData.get('memberverification') ? 1 : 0,
        allowed_payment_methods: allowedPaymentMethods,
        payment_timing_methods: paymentTimingMethods,
        // Registration fields
        field_phone: formData.get('field_phone') ? 1 : 0,
        field_email: formData.get('field_email') ? 1 : 0,
        field_street: formData.get('field_street') ? 1 : 0,
        field_zip: formData.get('field_zip') ? 1 : 0,
        field_city: formData.get('field_city') ? 1 : 0,
        field_postal: formData.get('field_postal') ? 1 : 0,
        field_age: formData.get('field_age') ? 1 : 0
    };

    try {
        const response = await fetch('/api/update_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            toast.quickSuccess('Arrangement', 'Arrangement oppdatert!');
            bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
            loadEvents();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke oppdatere arrangement');
        }
    } catch (error) {
        console.error('Error updating event:', error);
        toast.quickError('Arrangement', 'Feil ved oppdatering av arrangement');
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
    
    const data = {
        event_id: formData.get('event_id'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        date: formData.get('date'),
        notes: formData.get('notes')
    };
    
    if (!data.description || !data.amount || data.amount <= 0) {
        toast.quickWarning('Validering', 'Vennligst fyll ut beskrivelse og gyldig beløp');
        return;
    }
    
    try {
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
    
    const data = {
        event_id: formData.get('event_id'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        date: formData.get('date'),
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
                transactionsList.innerHTML = result.transactions.map(t => {
                    const date = new Date(t.transaction_date).toLocaleDateString('nb-NO');
                    const typeClass = t.entry_type === 'income' ? 'text-success' : 'text-danger';
                    const typeIcon = t.entry_type === 'income' ? 'plus-circle' : 'dash-circle';
                    const typeText = t.entry_type === 'income' ? 'Inntekt' : 'Utgift';
                    const amount = Math.abs(parseFloat(t.total_amount));
                    
                    return `
                        <tr>
                            <td><small>${date}</small></td>
                            <td><span class="badge ${t.entry_type === 'income' ? 'bg-success' : 'bg-danger'}"><i class="bi bi-${typeIcon} me-1"></i>${typeText}</span></td>
                            <td>${t.item_name}</td>
                            <td class="${typeClass}"><strong>${amount.toLocaleString('nb-NO')} NOK</strong></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction('${t.id}')" title="Slett">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                transactionsList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
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

// Load payment methods for create form
let paymentMethodsData = [];
async function loadPaymentMethodsForCreate() {
    const container = document.getElementById('payment-methods-container');
    if (!container) return;
    
    try {
        const response = await fetch('/api/get_payment_methods.php');
        const result = await response.json();
        
        if (result.success && result.payment_methods && result.payment_methods.length > 0) {
            paymentMethodsData = result.payment_methods;
            const iconMap = { 'cash': 'cash-stack', 'vipps': 'phone', 'stripe': 'credit-card', 'paypal': 'paypal' };
            container.innerHTML = `
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Velg betalingsmetoder som er tilgjengelige for dette arrangementet. Konfigurasjon gjøres i <strong>Økonomi → Betalingsløsninger</strong>.
                </div>
                <div class="row">` + result.payment_methods.map(method => {
                    const icon = iconMap[method.method_type] || 'wallet2';
                    const demoLabel = method.is_demo ? ' <small class="text-warning">(Demo)</small>' : '';
                    return `
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="payment_method_${method.method_type}" 
                               id="pm_${method.method_type}"
                               data-method-type="${method.method_type}"
                               checked>
                        <label class="form-check-label" for="pm_${method.method_type}">
                            <i class="bi bi-${icon} me-1"></i>
                            ${method.method_name}${demoLabel}
                        </label>
                    </div>
                </div>
                `;
            }).join('') + '</div>';
            
            // Add event listeners to update payment timing status and default dropdown
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    updatePaymentTimingStatus();
                    updateDefaultPaymentMethodDropdown();
                });
            });
            
            // Initial update
            updatePaymentTimingStatus();
            updateDefaultPaymentMethodDropdown();
        } else if (!result.success && result.error_type === 'connection_error') {
            container.innerHTML = `<div class="alert alert-warning small mb-0">
                <i class="bi bi-wifi-off me-2"></i>Kunne ikke hente betalingsmetoder (tilkoblingsfeil). 
                <button type="button" class="btn btn-sm btn-outline-warning ms-2" onclick="loadPaymentMethodsForCreate()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Prøv igjen
                </button>
            </div>`;
        } else if (result.success && (!result.payment_methods || result.payment_methods.length === 0)) {
            container.innerHTML = '<p class="text-warning small mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Ingen betalingsmetoder konfigurert i Økonomi → Betalingsløsninger</p>';
        } else {
            container.innerHTML = '<p class="text-warning small mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Ingen betalingsmetoder konfigurert i Økonomi → Betalingsløsninger</p>';
        }
    } catch (error) {
        console.error('Error loading payment methods:', error);
        container.innerHTML = `<div class="alert alert-danger small mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>Feil ved lasting av betalingsmetoder. 
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="loadPaymentMethodsForCreate()">
                <i class="bi bi-arrow-clockwise me-1"></i>Prøv igjen
            </button>
        </div>`;
    }
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
    const container = document.getElementById('edit-payment-methods-container');
    if (!container) return;
    
    try {
        const response = await fetch('/api/get_payment_methods.php');
        const result = await response.json();
        
        if (result.success && result.payment_methods && result.payment_methods.length > 0) {
            const iconMap = { 'cash': 'cash-stack', 'vipps': 'phone', 'stripe': 'credit-card', 'paypal': 'paypal' };
            
            container.innerHTML = `
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Velg betalingsmetoder som er tilgjengelige for dette arrangementet. Konfigurasjon gjøres i <strong>Økonomi → Betalingsløsninger</strong>.
                </div>
                <div class="row">` + result.payment_methods.map(method => {
                const isChecked = selectedMethods.includes(method.method_type);
                const icon = iconMap[method.method_type] || 'wallet2';
                const demoLabel = method.is_demo ? ' <small class="text-warning">(Demo)</small>' : '';
                return `
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="edit_payment_method_${method.method_type}" 
                               id="edit_pm_${method.method_type}"
                               data-method-type="${method.method_type}"
                               ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label" for="edit_pm_${method.method_type}">
                            <i class="bi bi-${icon} me-1"></i>
                            ${method.method_name}${demoLabel}
                        </label>
                    </div>
                </div>
            `;
            }).join('') + '</div>';
            
            // Add event listeners to update dropdown when checkboxes change
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    updateDefaultPaymentMethodDropdown('edit-payment-methods-container', 'edit-default-payment-method');
                });
            });
            
            // Update dropdown and set default value
            updateDefaultPaymentMethodDropdown('edit-payment-methods-container', 'edit-default-payment-method');
            if (defaultMethod) {
                const dropdown = document.getElementById('edit-default-payment-method');
                if (dropdown) dropdown.value = defaultMethod;
            }
        } else if (!result.success && result.error_type === 'connection_error') {
            container.innerHTML = `<div class="alert alert-warning small mb-0">
                <i class="bi bi-wifi-off me-2"></i>Kunne ikke hente betalingsmetoder (tilkoblingsfeil). 
                <button type="button" class="btn btn-sm btn-outline-warning ms-2" onclick="loadPaymentMethodsForEdit(${JSON.stringify(selectedMethods)}, '${defaultMethod || ''}')">
                    <i class="bi bi-arrow-clockwise me-1"></i>Prøv igjen
                </button>
            </div>`;
        } else {
            container.innerHTML = '<p class="text-warning small mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Ingen betalingsmetoder konfigurert i Økonomi → Betalingsløsninger</p>';
        }
    } catch (error) {
        console.error('Error loading payment methods for edit:', error);
        container.innerHTML = `<div class="alert alert-danger small mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>Feil ved lasting av betalingsmetoder. 
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="loadPaymentMethodsForEdit(${JSON.stringify(selectedMethods)}, '${defaultMethod || ''}')">
                <i class="bi bi-arrow-clockwise me-1"></i>Prøv igjen
            </button>
        </div>`;
    }
}

// Handle registration type changes - show/hide sections for "Ingen påmelding"
const registrationTypeRadios = document.querySelectorAll('input[name="registration_type"]');
const registrationFieldsSection = document.getElementById('registration-fields-section');

if (registrationTypeRadios.length > 0) {
    function handleRegistrationTypeChange() {
        const selectedType = document.querySelector('input[name="registration_type"]:checked').value;
        const isNone = selectedType === 'none';
        
        // Show/hide påmeldingsfelt section
        if (registrationFieldsSection) {
            registrationFieldsSection.style.display = isNone ? 'none' : 'block';
        }
        
        // Show/hide participant limits (but NOT ticket price - that's allowed for "Ingen påmelding")
        const minAttendeesInput = document.querySelector('input[name="min_attendees"]');
        const maxAttendeesInput = document.querySelector('input[name="max_attendees"]');
        const ticketPriceInput = document.querySelector('input[name="ticket_price"]');
        const regDeadlineContainer = document.getElementById('registration_deadline_container');
        
        if (minAttendeesInput && minAttendeesInput.closest('.col-md-3')) minAttendeesInput.closest('.col-md-3').style.display = isNone ? 'none' : 'block';
        if (maxAttendeesInput && maxAttendeesInput.closest('.col-md-3')) maxAttendeesInput.closest('.col-md-3').style.display = isNone ? 'none' : 'block';
        // Keep ticket price visible for all registration types
        if (ticketPriceInput && ticketPriceInput.closest('.col-md-3')) ticketPriceInput.closest('.col-md-3').style.display = 'block';
        if (regDeadlineContainer) regDeadlineContainer.style.display = 'none';
        
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

// Load both registration lists when tab is shown
document.querySelector('[href="#events-registrations"]')?.addEventListener('click', function() {
    setTimeout(() => {
        loadPendingRegistrations();
        loadAllRegistrations();
    }, 100);
});

async function loadPendingRegistrations() {
    try {
        const response = await fetch('/api/get_pending_registrations.php');
        const result = await response.json();
        
        const tbody = document.getElementById('pending-registrations-list');
        const badge = document.getElementById('pending-count-badge');
        
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
        if (badge && result.registrations && result.registrations.length > 0) {
            badge.textContent = result.registrations.length;
            badge.style.display = 'inline-block';
        } else if (badge) {
            badge.style.display = 'none';
        }
        
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
                const memberIcon = isMember ? '✅' : '❌';
                const priceHtml = `
                    <span class="fw-bold" style="font-size: 1rem;">
                        ${memberIcon} ${displayPrice} kr
                    </span>
                    ${isMember && priceInfo.member_plan ? `<br><small class="text-muted">${priceInfo.member_plan}</small>` : ''}
                `;
                
                return `
                    <tr data-registration-id="${reg.id}">
                        <td style="min-width: 250px;">${entryDataHtml}</td>
                        <td class="text-center" style="min-width: 100px;">${priceHtml}</td>
                        <td>
                            <div><strong>${reg.event_title}</strong></div>
                            <small class="text-muted">${reg.start_date} ${reg.start_time || ''}</small>
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
            const eventDate = new Date(eventGroup.start_date + ' ' + eventGroup.start_time);
            const dateStr = eventDate.toLocaleDateString('no-NO', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric'
            });
            const timeStr = eventDate.toLocaleTimeString('no-NO', { 
                hour: '2-digit', 
                minute: '2-digit'
            });
            
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
                         data-bs-parent="#registrationsAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                                        <tr>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Navn</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Kontakt</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Medlemskap</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Billettpris</th>
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
                    const time = c.check_in_time ? new Date(c.check_in_time).toLocaleTimeString('no-NO', {hour:'2-digit', minute:'2-digit'}) : '-';
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
    
    try {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1&countrycodes=no`;
        
        const response = await fetch(url);
        const data = await response.json();
        
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

function initEventWizard() {
    const prevBtn = document.getElementById('wizard-prev');
    const nextBtn = document.getElementById('wizard-next');
    const submitBtn = document.getElementById('wizard-submit');
    const resetBtn = document.getElementById('wizard-reset');
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => wizardGoToStep(wizardCurrentStep + 1));
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => wizardGoToStep(wizardCurrentStep - 1));
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
    
    // Venue checkbox toggle
    const hasVenueCheckbox = document.getElementById('has_venue');
    if (hasVenueCheckbox) {
        hasVenueCheckbox.addEventListener('change', function() {
            const venueFields = document.getElementById('venue-fields');
            if (venueFields) {
                venueFields.style.display = this.checked ? 'block' : 'none';
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
    loadWizardPaymentMethods();
    loadWizardMembershipPlans();
}

function wizardGoToStep(step) {
    if (step < 1 || step > wizardTotalSteps) return;
    
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
            return true;
            
        case 2:
            const title = form.querySelector('input[name="title"]');
            const startDate = form.querySelector('input[name="start_date"]');
            const startTime = form.querySelector('input[name="start_time"]');
            
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
    const regType = document.querySelector('input[name="registration_type"]:checked')?.value;
    
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
}

function updateMembershipAddonsVisibility() {
    const regType = document.querySelector('input[name="registration_type"]:checked')?.value;
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
    const startDate = form.querySelector('input[name="start_date"]')?.value;
    const startTime = form.querySelector('input[name="start_time"]')?.value;
    if (startDate) {
        const dateObj = new Date(startDate + 'T' + (startTime || '00:00'));
        document.getElementById('summary-date').textContent = dateObj.toLocaleDateString('no-NO', { 
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' 
        }) + (startTime ? ` kl. ${startTime}` : '');
    }
    
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
    try {
        const response = await fetch('/api/get_payment_methods.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const container = document.getElementById('payment-methods-container');
            const defaultSelect = document.getElementById('default-payment-method');
            
            if (result.data.length === 0) {
                container.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Ingen betalingsmetoder konfigurert. Gå til Økonomi-modulen for å sette opp betalingsløsninger.</div>';
                return;
            }
            
            container.innerHTML = result.data.map(pm => `
                <div class="form-check form-check-inline mb-2">
                    <input class="form-check-input payment-method-checkbox" type="checkbox" name="payment_methods[]" value="${pm.method_type}" id="pm_${pm.method_type}" checked>
                    <label class="form-check-label" for="pm_${pm.method_type}">
                        <i class="bi bi-${pm.config?.icon || 'wallet2'} me-1"></i>${pm.method_name || pm.method_type}
                    </label>
                </div>
            `).join('');
            
            defaultSelect.innerHTML = '<option value="">Velg standardmetode...</option>' + 
                result.data.map(pm => `<option value="${pm.method_type}">${pm.method_name || pm.method_type}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading payment methods:', error);
    }
}

async function loadWizardMembershipPlans() {
    try {
        const response = await fetch('/api/get_membership_plans.php');
        const result = await response.json();
        
        const container = document.getElementById('membership-pricing-container');
        const plans = result.plans || result.data || [];
        
        if (result.success && plans.length > 0) {
            container.innerHTML = plans.map(plan => `
                <div class="card bg-light mb-2 p-2" data-plan-id="${plan.id}">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label mb-0 fw-medium">${plan.name}</label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" class="form-control form-control-sm membership-price-input" name="membership_price_${plan.id}" data-plan-id="${plan.id}" placeholder="Medlemspris (kr)">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input membership-public-toggle" type="checkbox" name="membership_public_${plan.id}" id="membership_public_${plan.id}" data-plan-id="${plan.id}" checked>
                                <label class="form-check-label small" for="membership_public_${plan.id}">
                                    <i class="bi bi-globe text-primary me-1"></i>Vis offentlig
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-muted small">Ingen medlemsplaner funnet.</p>';
        }
    } catch (error) {
        console.error('Error loading membership plans:', error);
        document.getElementById('membership-pricing-container').innerHTML = '<p class="text-muted small">Kunne ikke laste medlemsplaner.</p>';
    }
}

function resetWizard() {
    if (!confirm('Er du sikker på at du vil nullstille skjemaet?')) return;
    
    const form = document.getElementById('create-event-form');
    form.reset();
    
    // Reset to step 1
    wizardCurrentStep = 1;
    
    // Hide all steps except first
    document.querySelectorAll('.wizard-content').forEach((el, index) => {
        el.style.display = index === 0 ? 'block' : 'none';
    });
    
    // Reset step indicators
    document.querySelectorAll('.wizard-step').forEach((el, index) => {
        el.classList.remove('active', 'completed');
        if (index === 0) el.classList.add('active');
    });
    
    // Reset navigation buttons
    document.getElementById('wizard-prev').style.display = 'none';
    document.getElementById('wizard-next').style.display = 'block';
    document.getElementById('wizard-submit').style.display = 'none';
    
    // Show form, hide success
    document.getElementById('create-event-form').style.display = 'block';
    document.getElementById('wizard-success').style.display = 'none';
    document.querySelector('.wizard-progress').style.display = 'block';
    document.querySelector('.wizard-navigation').style.display = 'flex';
    
    // Reload dynamic content
    loadWizardPaymentMethods();
    loadWizardMembershipPlans();
}

function resetWizardAndGoToList() {
    resetWizard();
    document.querySelector('[href="#events-my"]')?.click();
}

function showWizardSuccess() {
    document.getElementById('create-event-form').style.display = 'none';
    document.querySelector('.wizard-progress').style.display = 'none';
    document.querySelector('.wizard-navigation').style.display = 'none';
    document.getElementById('wizard-success').style.display = 'block';
}

// Initialize wizard on DOM ready
document.addEventListener('DOMContentLoaded', initEventWizard);

// Handle subtab change to reset wizard when entering create tab
document.addEventListener('subtab-changed', function(event) {
    if (event.detail.subtab === 'events-create') {
        // Optionally reset wizard when entering the tab
        // resetWizard();
        initEventWizard();
    }
});
</script>
