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
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Opprett nytt arrangement</h5>
                <form id="create-event-form">
                    <div class="row">
                        <div class="col-md-<?php echo isset($hasMembershipAccess) && $hasMembershipAccess ? '8' : '12'; ?> mb-3">
                            <label class="form-label">Arrangement navn *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kun for medlemmer?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_member_exclusive" id="memberExclusive">
                                <label class="form-check-label" for="memberExclusive">Kun medlemmer</label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card border-success mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Registreringstype</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Velg hvordan deltakere registrerer seg til arrangementet</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="registration_type" id="reg-signup" value="signup" checked>
                                        <label class="form-check-label" for="reg-signup">
                                            <i class="bi bi-person-plus text-primary me-1"></i> <strong>Påmelding</strong><br>
                                            <small class="text-muted">Deltakere melder seg på</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="registration_type" id="reg-none" value="none">
                                        <label class="form-check-label" for="reg-none">
                                            <i class="bi bi-x-circle text-secondary me-1"></i> <strong>Ingen påmelding</strong><br>
                                            <small class="text-muted">Fritt arrangement uten registrering</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="registration_type" id="reg-binding" value="binding">
                                        <label class="form-check-label" for="reg-binding">
                                            <i class="bi bi-lock text-warning me-1"></i> <strong>Bindende</strong><br>
                                            <small class="text-muted">Krever betaling ved påmelding</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Startdato *</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Starttid *</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttdato</label>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttid</label>
                            <input type="time" class="form-control" name="end_time">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Aldersgrense</label>
                            <input type="number" class="form-control" name="age_limit">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Stedsnavn</label>
                            <input type="text" class="form-control" name="venue_name">
                        </div>
                    </div>

                    <div class="card bg-light mb-4 p-3">
                        <h6 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Lokasjon</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Gateadresse</label>
                                <input type="text" class="form-control address-field" name="event_address" placeholder="f.eks. Hovedgaten 42">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">By</label>
                                <input type="text" class="form-control city-field" name="event_city" placeholder="f.eks. Oslo">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postnummer</label>
                                <input type="text" class="form-control zip-field" name="event_postal" placeholder="f.eks. 0150" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min. deltakere</label>
                            <input type="number" class="form-control" name="min_attendees" id="min_attendees">
                        </div>
                        <div class="col-md-3 mb-3" id="registration_deadline_container" style="display: none;">
                            <label class="form-label">Frist for min. deltakere *</label>
                            <input type="date" class="form-control" name="registration_deadline" id="registration_deadline">
                            <small class="form-text text-muted">Må være før startdato</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Maks. deltakere</label>
                            <input type="number" class="form-control" name="max_attendees">
                        </div>
                    </div>

                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Priser</h6>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3" id="ticket-price-container">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price" id="ticket_price">
                            <small class="form-text text-muted" id="ticket-price-help">Ved "Ingen påmelding" vises pris med "(betales ved inngang)"</small>
                        </div>
                        <div class="col-md-3 mb-3" id="cost-price-container">
                            <label class="form-label">Innkjøpspris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="cost_price" id="cost_price" value="0">
                            <small class="form-text text-muted">Kostnad per billett for resultatberegning</small>
                        </div>
                    </div>
                    
                    <div id="membership-pricing-container">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <small class="text-muted">Laster medlemsplaner...</small>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="memberverification" id="memberverification">
                                <label class="form-check-label" for="memberverification">
                                    <i class="bi bi-shield-check text-success me-1"></i>Autogodkjenning og Autofyll for medlemmer for dette arrangement
                                </label>
                            </div>
                            <small class="form-text text-muted d-block mt-2">Medlemmer blir automatisk godkjent og får autofylt informasjon ved registrering</small>
                        </div>
                    </div>

                    <?php endif; ?>

                    <hr class="my-4" id="payment-section">
                    <h6 class="mb-3"><i class="bi bi-credit-card-2-front me-2"></i>Betalingsmetoder</h6>
                    <div id="payment-methods-container" class="mb-3">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <small class="text-muted">Laster betalingsmetoder...</small>
                        </div>
                    </div>
                    <p class="text-muted small"><strong>Status:</strong> <span id="payment-timing-status">Velg økonomimetoder for å aktivere nettbetaling</span></p>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Bilde-URL</label>
                            <input type="url" class="form-control" name="event_image_url">
                        </div>
                    </div>

                    <div id="registration-fields-section">
                        <hr class="my-4">
                        <h6 class="mb-3"><i class="bi bi-ui-checks me-2"></i>Påmeldingsfelt</h6>
                        <p class="text-muted small mb-3">Fornavn og etternavn er alltid påkrevd. Velg minst én kontaktmetode (e-post eller telefon).</p>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input contact-field-required" type="checkbox" name="field_email" id="field_email" checked>
                                    <label class="form-check-label" for="field_email">
                                        <i class="bi bi-envelope me-1"></i> E-post
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input contact-field-required" type="checkbox" name="field_phone" id="field_phone" checked>
                                    <label class="form-check-label" for="field_phone">
                                        <i class="bi bi-telephone me-1"></i> Telefon
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="field_street" id="field_street">
                                    <label class="form-check-label" for="field_street">
                                        <i class="bi bi-house me-1"></i> Adresse
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="field_age" id="field_age">
                                    <label class="form-check-label" for="field_age">
                                        <i class="bi bi-person-badge me-1"></i> Alder
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Opprett arrangement
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Nullstill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="events-checkin" class="subtab-content" style="display: none;">
        <!-- Event Cards Selector -->
        <div class="mb-4">
            <h5 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Velg arrangement</h5>
            <div class="row g-3" id="checkin-event-cards">
                <div class="col-12 text-center text-muted py-5">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    Laster arrangementer...
                </div>
            </div>
        </div>

        <!-- Event Details & Check-in Panel -->
        <div id="event-details-panel" style="display: none;">
        <div class="row g-3">
            <!-- Left Column: Event Selection & Scanner -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h6 class="mb-0" id="selected-event-title"><i class="bi bi-calendar-event me-2"></i>Valgt arrangement</h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-outline-secondary btn-sm w-100" onclick="document.getElementById('event-details-panel').style.display='none'; document.getElementById('checkin-event-cards').parentElement.style.display='block'">
                            <i class="bi bi-chevron-left me-2"></i>Tilbake til oversikt
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div id="checkin-stats" class="card mb-3" style="display: none;">
                    <div class="card-body p-2">
                        <div class="row g-2 text-center small">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Påmeldte</div>
                                    <div class="fs-5 fw-bold" id="checkin-total-attendees">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Betalt</div>
                                    <div class="fs-5 fw-bold text-success" id="checkin-paid-count">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Venter</div>
                                    <div class="fs-5 fw-bold text-warning" id="checkin-pending-count">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Inntekt</div>
                                    <div class="fs-5 fw-bold text-primary" id="checkin-total-revenue">0 kr</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Guest Registration -->
                <div id="quick-guest-registration" class="card" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registrer gjest</h6>
                    </div>
                    <div class="card-body">
                        <form id="quick-guest-form">
                            <div class="mb-2">
                                <label class="form-label small mb-1">Fornavn *</label>
                                <input type="text" class="form-control form-control-sm" id="guest-fornavn" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Etternavn *</label>
                                <input type="text" class="form-control form-control-sm" id="guest-etternavn" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">E-post</label>
                                <input type="email" class="form-control form-control-sm" id="guest-email">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Antall personer (1-10)</label>
                                <input type="number" class="form-control form-control-sm" id="guest-number-of-people" min="1" max="10" value="1" required>
                                <small class="text-muted">Oppretter en registrering per person</small>
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
                            <div class="mb-2">
                                <label class="form-label small mb-1">Pris (kr)</label>
                                <input type="number" class="form-control form-control-sm" id="guest-price" min="0" step="0.01" required readonly>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Betalingsmetode</label>
                                <select class="form-select form-select-sm" id="guest-payment-method" required>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Status</label>
                                <select class="form-select form-select-sm" id="guest-payment-status" required>
                                    <option value="paid">Betalt</option>
                                    <option value="pending">Venter betaling</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-check-circle me-1"></i>Registrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Attendee List - Two Columns -->
            <div class="col-md-8">
                <ul class="nav nav-tabs mb-3" role="tablist" id="checkin-tabs">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#checkin-confirmed-tab" type="button">
                            <i class="bi bi-check-circle me-2 text-success"></i>Innsjekket
                            <span class="badge bg-success ms-1" id="checkin-confirmed-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#checkin-pending-tab" type="button">
                            <i class="bi bi-clock me-2 text-warning"></i>Ikke innsjekket
                            <span class="badge bg-warning ms-1" id="checkin-pending-count">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="checkin-tabs-content">
                    <!-- Checked In Tab -->
                    <div class="tab-pane fade show active" id="checkin-confirmed-tab" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Innsjekket</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" id="checkin-confirmed-container">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Billetttype</th>
                                                <th>Innsjekket av</th>
                                                <th>Barcode</th>
                                                <th>Pris</th>
                                                <th>Tid</th>
                                            </tr>
                                        </thead>
                                        <tbody id="checkin-confirmed-list">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    Velg arrangement for å se innsjekket
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
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Ikke innsjekket</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" id="checkin-pending-container">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Billetttype</th>
                                                <th>Barcode</th>
                                                <th>Pris</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="checkin-pending-list">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    Velg arrangement for å se ikke innsjekket
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
        <ul class="nav nav-tabs mb-3" role="tablist" id="registrations-nav-tabs">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-reg-tab" data-bs-toggle="tab" data-bs-target="#pending-registrations-panel" type="button" role="tab">
                    <i class="bi bi-hourglass-split me-2"></i>Ventende (Godkjenning)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="all-reg-tab" data-bs-toggle="tab" data-bs-target="#all-registrations-panel" type="button" role="tab">
                    <i class="bi bi-list-ul me-2"></i>Alle påmeldinger
                </button>
            </li>
        </ul>

        <div class="tab-content" id="registrations-tab-content">
            <!-- Pending Registrations Panel -->
            <div class="tab-pane fade show active" id="pending-registrations-panel" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clipboard-check me-2"></i>Eventbestillinger</h5>
                        <p class="text-muted">Godkjenn eller avvis ventende bestillinger</p>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
            </div>

            <!-- All Registrations Panel -->
            <div class="tab-pane fade" id="all-registrations-panel" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-list-ul me-2"></i>Alle påmeldinger per arrangement</h5>
                        <p class="text-muted">Se alle påmeldinger gruppert etter arrangement med godkjenningsinformasjon</p>
                        
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
                                <input type="text" class="form-control address-field" name="event_address" id="edit-event-address" placeholder="f.eks. Hovedgaten 42">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">By</label>
                                <input type="text" class="form-control city-field" name="event_city" id="edit-event-city" placeholder="f.eks. Oslo">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postnummer</label>
                                <input type="text" class="form-control zip-field" name="event_postal" id="edit-event-postal" placeholder="f.eks. 0150" maxlength="4">
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
                        <div class="col-md-3 mb-3" id="edit-ticket-price-container">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price" id="edit-ticket-price">
                        </div>
                        <div class="col-md-3 mb-3" id="edit-cost-price-container">
                            <label class="form-label">Innkjøpspris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="cost_price" id="edit-cost-price" value="0">
                            <small class="form-text text-muted">Kostnad per billett for resultatberegning</small>
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
                                <input class="form-check-input" type="checkbox" name="field_postal" id="edit-field-postal">
                                <label class="form-check-label" for="edit-field-postal">
                                    <i class="bi bi-box text-primary me-1"></i> Postboks
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
            
            // Collect allowed payment methods
            const allowedPaymentMethods = ['cash']; // Cash is always required
            document.querySelectorAll('#payment-methods-container input[type="checkbox"]:checked:not([disabled])').forEach(checkbox => {
                allowedPaymentMethods.push(checkbox.dataset.methodType);
            });
            
            // Build payment timing methods array: always has 'arrival', plus economy methods if any selected
            const paymentTimingMethods = ['arrival'];
            allowedPaymentMethods.forEach(method => {
                if (method !== 'cash') {
                    paymentTimingMethods.push(method); // Add actual method type (card, vipps, etc)
                }
            });
            
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
                cost_price: formData.get('cost_price') || 0,
                event_image_url: formData.get('event_image_url') || null,
                is_member_exclusive: formData.get('is_member_exclusive') ? 1 : 0,
                memberverification: formData.get('memberverification') ? 1 : 0,
                registration_type: formData.get('registration_type') || 'signup',
                payment_timing_methods: paymentTimingMethods,
                membership_pricing: membershipPricing,
                allowed_payment_methods: allowedPaymentMethods,
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
                    // Show all membership prices in a clean list
                    const priceList = event.membership_prices
                        .map(mp => {
                            return `<div class="d-flex justify-content-between align-items-center mb-1">
                                <small>${mp.plan_name}</small>
                                <strong class="text-primary">${mp.price} kr</strong>
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

// Load events into check-in selector as cards
async function loadCheckinEventSelector() {
    const cardsContainer = document.getElementById('checkin-event-cards');
    
    try {
        const response = await fetch('/api/get_events.php');
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        const result = await response.json();
        
        if (result.success && result.events && result.events.length > 0) {
            cardsContainer.innerHTML = result.events.map(event => {
                const dateStr = new Date(event.start_date + ' ' + event.start_time).toLocaleDateString('no-NO', { 
                    day: 'numeric', 
                    month: 'short', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                return `
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 cursor-pointer" style="cursor: pointer;" onclick="openEventDetail('${event.id}', '${event.title}')">
                            <div class="card-body">
                                <h6 class="card-title text-truncate">${event.title}</h6>
                                <small class="text-muted d-block mb-2">
                                    <i class="bi bi-calendar me-1"></i>${dateStr}
                                </small>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded text-center">
                                            <small class="d-block text-muted">Påmeldte</small>
                                            <strong>${event.current_attendees || 0}</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded text-center">
                                            <small class="d-block text-muted">Innsjekket</small>
                                            <strong id="checked-${event.id}">-</strong>
                                        </div>
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
            cardsContainer.innerHTML = '<div class="col-12 text-center text-muted py-4">Ingen arrangementer tilgjengelig</div>';
        }
    } catch (error) {
        console.error('Error loading events:', error);
        cardsContainer.innerHTML = '<div class="col-12 text-center text-danger py-4"><i class="bi bi-exclamation-triangle me-2"></i>Feil ved lasting</div>';
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

function openEventDetail(eventId, eventTitle) {
    selectedEventId = eventId;
    document.getElementById('selected-event-title').innerHTML = '<i class="bi bi-calendar-event me-2"></i>' + eventTitle;
    document.getElementById('checkin-event-cards').parentElement.style.display = 'none';
    document.getElementById('event-details-panel').style.display = 'block';
    document.getElementById('checkin-stats').style.display = 'block';
    document.getElementById('quick-guest-registration').style.display = 'block';
    
    // Load checkins and event details
    loadCheckins(eventId);
    loadEventDetailsForCheckin(eventId);
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
            toast.quickSuccess('Gjest', messageText);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke registrere gjest');
        }
    } catch (error) {
        console.error('Error registering guest:', error);
        toast.quickError('Gjest', 'Kunne ikke registrere gjest');
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
            // Update statistics
            document.getElementById('checkin-total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('checkin-paid-count').textContent = result.summary.paid_count;
            document.getElementById('checkin-pending-count').textContent = result.summary.pending_count;
            document.getElementById('checkin-total-revenue').textContent = result.summary.total_revenue.toFixed(2) + ' kr';
            
            // Update confirmed list (innsjekket)
            const confirmedList = document.getElementById('checkin-confirmed-list');
            const confirmedCount = document.getElementById('checkin-confirmed-count');
            
            if (result.checked_in && result.checked_in.length > 0) {
                confirmedCount.textContent = result.checked_in.length;
                confirmedList.innerHTML = result.checked_in.map(checkin => {
                    const adminName = checkin.admin_first_name && checkin.admin_last_name 
                        ? checkin.admin_first_name + ' ' + checkin.admin_last_name 
                        : 'System';
                    const checkinTime = checkin.check_in_time ? new Date(checkin.check_in_time).toLocaleTimeString('no-NO', {hour: '2-digit', minute: '2-digit'}) : '-';
                    const barcode = checkin.barcode || '-';
                    const ticketType = checkin.ticket_type || 'regular';
                    
                    return `
                        <tr>
                            <td><strong>${ticketType}</strong></td>
                            <td><small>${adminName}</small></td>
                            <td><small class="font-monospace">${barcode}</small></td>
                            <td>${checkin.ticket_price} kr</td>
                            <td><small class="text-muted">${checkinTime}</small></td>
                        </tr>
                    `;
                }).join('');
            } else {
                confirmedCount.textContent = '0';
                confirmedList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Ingen innsjekket ennå
                        </td>
                    </tr>
                `;
            }
            
            // Update pending list (ikke innsjekket - removed since events_checkin no longer stores attendee data)
            const pendingList = document.getElementById('checkin-pending-list');
            const pendingCount = document.getElementById('checkin-pending-count');
            // Note: Pending list is empty as events_checkin stores checked_in=0 for not-yet-checked-in entries            
            if (result.not_checked_in && result.not_checked_in.length > 0) {
                pendingCount.textContent = result.not_checked_in.length;
                pendingList.innerHTML = result.not_checked_in.map(checkin => {
                    const barcode = checkin.barcode || '-';
                    const paymentBadge = checkin.payment_status === 'paid' 
                        ? '<span class="badge bg-success">Betalt</span>' 
                        : '<span class="badge bg-warning">Venter</span>';
                    const ticketType = checkin.ticket_type || 'regular';
                    
                    return `
                        <tr>
                            <td><strong>${ticketType}</strong></td>
                            <td><small class="font-monospace">${barcode}</small></td>
                            <td><small>${checkin.ticket_price} kr</small></td>
                            <td>${paymentBadge}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                pendingCount.textContent = '0';
                pendingList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Alle har sjekket inn! ✓
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading check-ins:', error);
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
        setFieldValue('edit-cost-price', event.cost_price || 0);
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
            await loadPaymentMethodsForEdit(selectedPaymentMethods);
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
    
    // Collect allowed payment methods
    const allowedPaymentMethods = ['cash']; // Cash is always required
    document.querySelectorAll('#edit-payment-methods-container input[type="checkbox"]:checked:not([disabled])').forEach(checkbox => {
        allowedPaymentMethods.push(checkbox.dataset.methodType);
    });
    
    // Build payment timing methods array: always has 'arrival', plus economy methods if any selected
    const paymentTimingMethods = ['arrival'];
    allowedPaymentMethods.forEach(method => {
        if (method !== 'cash') {
            paymentTimingMethods.push(method); // Add actual method type (card, vipps, etc)
        }
    });
    
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
        cost_price: formData.get('cost_price') || 0,
        event_image_url: formData.get('event_image_url') || null,
        default_payment_method: formData.get('default_payment_method') || null,
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
            container.innerHTML = `
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Oppmøtebetaling</strong> er alltid inkludert for å tillate arrangementer uten webbetaling.
                </div>
                <div class="row">` + result.payment_methods.map(method => `
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="payment_method_${method.method_type}" 
                               id="pm_${method.method_type}"
                               data-method-type="${method.method_type}"
                               ${method.method_type === 'cash' ? 'checked disabled' : 'checked'}>
                        <label class="form-check-label ${method.method_type === 'cash' ? 'text-muted' : ''}" for="pm_${method.method_type}">
                            <i class="bi bi-${method.method_type === 'cash' ? 'cash-stack' : method.method_type === 'card' ? 'credit-card' : 'wallet2'} me-1"></i>
                            ${method.method_name}${method.method_type === 'cash' ? ' <small>(alltid påkrevd)</small>' : ''}
                        </label>
                    </div>
                </div>
            `).join('') + '</div>';
            
            // Add event listeners to update payment timing status
            const checkboxes = container.querySelectorAll('input[type="checkbox"]:not([disabled])');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePaymentTimingStatus);
            });
            
            // Initial update
            updatePaymentTimingStatus();
        } else {
            container.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-info-circle me-2"></i>Ingen betalingsmetoder konfigurert</p>';
        }
    } catch (error) {
        console.error('Error loading payment methods:', error);
        container.innerHTML = '<p class="text-danger small mb-0">Feil ved lasting av betalingsmetoder</p>';
    }
}

// Update payment timing status based on selected methods
function updatePaymentTimingStatus() {
    const statusSpan = document.getElementById('payment-timing-status');
    if (!statusSpan) return;
    
    const checkedEconomyMethods = document.querySelectorAll('#payment-methods-container input[type="checkbox"]:checked:not([disabled])');
    
    if (checkedEconomyMethods.length > 0) {
        statusSpan.innerHTML = '<strong class="text-success">✓ Aktivert</strong> (nettbetaling tilgjengelig)';
    } else {
        statusSpan.innerHTML = 'Aktiveres når du velger økonomimetoder over';
    }
}

// Load payment methods for edit form
async function loadPaymentMethodsForEdit(selectedMethods = []) {
    const container = document.getElementById('edit-payment-methods-container');
    if (!container) return;
    
    try {
        const response = await fetch('/api/get_payment_methods.php');
        const result = await response.json();
        
        if (result.success && result.payment_methods && result.payment_methods.length > 0) {
            // Ensure cash is always in selected methods
            if (!selectedMethods.includes('cash')) {
                selectedMethods.push('cash');
            }
            
            container.innerHTML = `
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Oppmøtebetaling</strong> er alltid inkludert for å tillate arrangementer uten webbetaling.
                </div>
                <div class="row">` + result.payment_methods.map(method => {
                const isChecked = selectedMethods.includes(method.method_type);
                const isCash = method.method_type === 'cash';
                return `
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="edit_payment_method_${method.method_type}" 
                               id="edit_pm_${method.method_type}"
                               data-method-type="${method.method_type}"
                               ${isChecked ? 'checked' : ''} ${isCash ? 'disabled' : ''}>
                        <label class="form-check-label ${isCash ? 'text-muted' : ''}" for="edit_pm_${method.method_type}">
                            <i class="bi bi-${isCash ? 'cash-stack' : method.method_type === 'card' ? 'credit-card' : 'wallet2'} me-1"></i>
                            ${method.method_name}${isCash ? ' <small>(alltid påkrevd)</small>' : ''}
                        </label>
                    </div>
                </div>
            `;
            }).join('') + '</div>';
        } else {
            container.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-info-circle me-2"></i>Ingen betalingsmetoder konfigurert</p>';
        }
    } catch (error) {
        console.error('Error loading payment methods for edit:', error);
        container.innerHTML = '<p class="text-danger small mb-0">Feil ved lasting av betalingsmetoder</p>';
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
        
        if (minAttendeesInput) minAttendeesInput.closest('.col-md-3').style.display = isNone ? 'none' : 'block';
        if (maxAttendeesInput) maxAttendeesInput.closest('.col-md-3').style.display = isNone ? 'none' : 'block';
        // Keep ticket price visible for all registration types
        if (ticketPriceInput) ticketPriceInput.closest('.col-md-3').style.display = 'block';
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

// Load pending registrations when tab is shown
document.querySelector('[href="#events-registrations"]')?.addEventListener('click', function() {
    setTimeout(() => loadPendingRegistrations(), 100);
});

// Ensure pending registrations load when pending tab is clicked
document.getElementById('pending-reg-tab')?.addEventListener('click', function() {
    setTimeout(() => loadPendingRegistrations(), 100);
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
                // Build dynamic fields display (max 4)
                let dynamicFieldsHtml = '';
                if (reg.dynamic_fields && reg.dynamic_fields.length > 0) {
                    dynamicFieldsHtml = reg.dynamic_fields.map(field => {
                        const value = field.value || '-';
                        return `
                            <div class="d-inline-block me-2 mb-1">
                                <span class="badge bg-light text-dark border" style="font-weight: 400;">
                                    <small class="text-muted">${field.label}:</small> 
                                    <span class="editable-field" data-reg-id="${reg.id}" data-field="${field.field_name}">${value}</span>
                                </span>
                            </div>
                        `;
                    }).join('');
                } else {
                    // Fallback to basic info
                    dynamicFieldsHtml = `
                        <div class="d-inline-block me-2 mb-1">
                            <span class="badge bg-light text-dark border" style="font-weight: 400;">
                                <small class="text-muted">Navn:</small> 
                                <span>${reg.first_name} ${reg.last_name}</span>
                            </span>
                        </div>
                        <div class="d-inline-block me-2 mb-1">
                            <span class="badge bg-light text-dark border" style="font-weight: 400;">
                                <small class="text-muted">E-post:</small> 
                                <span>${reg.email || '-'}</span>
                            </span>
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
                        <td style="min-width: 250px;">${dynamicFieldsHtml}</td>
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

async function rejectRegistrationWithReason(registrationId) {
    const rejectionReason = prompt('Årsak til avvisning (påkrevd):');
    
    if (rejectionReason === null) {
        return; // User cancelled
    }
    
    if (!rejectionReason.trim()) {
        toast.quickError('Mangler årsak', 'Du må oppgi en årsak for avvisning');
        return;
    }
    
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

// Prevent nested tab clicks from bubbling to parent subtab navigation
document.querySelectorAll('#registrations-nav-tabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

// Load pending registrations when tab is initialized
document.addEventListener('shown.bs.tab', function(e) {
    if (e.target.id === 'pending-reg-tab') {
        setTimeout(() => loadPendingRegistrations(), 100);
    } else if (e.target.id === 'all-reg-tab') {
        setTimeout(() => loadAllRegistrations(), 100);
    }
});

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
                    <div id="${collapseId}" class="accordion-collapse collapse ${isFirst ? 'show' : ''}" 
                         data-bs-parent="#registrationsAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                                        <tr>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Navn</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Kontakt</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Medlem</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #495057; font-size: 0.85rem;">Status</th>
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
                
                const contact = reg.email || reg.phone || '-';
                const memberBadge = reg.has_valid_membership 
                    ? '<span class="badge bg-success-subtle text-success"><i class="bi bi-star-fill"></i></span>' 
                    : '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-dash"></i></span>';
                
                html += `
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            <strong style="color: #2c3e50; font-size: 0.9rem;">${reg.first_name} ${reg.last_name}</strong>
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            <small style="color: #6c757d;">${contact}</small>
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle; text-align: center;">
                            ${memberBadge}
                        </td>
                        <td style="padding: 10px 12px; vertical-align: middle;">
                            ${approvalStatusBadge}
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
        
        // Add expand/collapse all buttons
        const controlsHtml = `
            <div class="mb-3 d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="expandAllRegistrations()">
                    <i class="bi bi-arrows-expand me-1"></i>Utvid alle
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="collapseAllRegistrations()">
                    <i class="bi bi-arrows-collapse me-1"></i>Skjul alle
                </button>
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

// Expand/collapse all registrations accordion
function expandAllRegistrations() {
    document.querySelectorAll('#registrationsAccordion .accordion-collapse').forEach(collapse => {
        const bsCollapse = new bootstrap.Collapse(collapse, { toggle: false });
        bsCollapse.show();
    });
}

function collapseAllRegistrations() {
    document.querySelectorAll('#registrationsAccordion .accordion-collapse').forEach(collapse => {
        const bsCollapse = new bootstrap.Collapse(collapse, { toggle: false });
        bsCollapse.hide();
    });
}

</script>
