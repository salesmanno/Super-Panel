<style>
.event-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: white;
    height: 100%;
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
                <p class="text-muted mb-0">Oversikt over alle dine arrangementer</p>
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
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Aldersgrense</label>
                            <input type="number" class="form-control" name="age_limit">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stedsnavn</label>
                            <input type="text" class="form-control" name="venue_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="event_address">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">By</label>
                            <input type="text" class="form-control" name="event_city">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postnummer</label>
                            <input type="text" class="form-control" name="event_postal">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Min. deltakere</label>
                            <input type="number" class="form-control" name="min_attendees">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Maks. deltakere</label>
                            <input type="number" class="form-control" name="max_attendees">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price">
                        </div>
                    </div>

                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Medlemspris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="member_ticket_price">
                            <small class="form-text text-muted">La stå tom hvis samme pris som ordinær billett</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="event_image_url">
                        <small class="form-text text-muted">Valgfri: URL til arrangementsbilde</small>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-ui-checks me-2"></i>Påmeldingsfelt
                        </label>
                        <p class="text-muted small mb-3">Velg hvilke felt som skal vises ved påmelding til arrangementet</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="field_street" id="field_street">
                                    <label class="form-check-label" for="field_street">
                                        🏠 Gateadresse
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="field_zip" id="field_zip">
                                    <label class="form-check-label" for="field_zip">
                                        📮 Postnummer
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="field_city" id="field_city">
                                    <label class="form-check-label" for="field_city">
                                        🏙️ Poststed
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="field_postal" id="field_postal">
                                    <label class="form-check-label" for="field_postal">
                                        📦 Postboks
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Navn og e-post er alltid påkrevd og vises automatisk</small>
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
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Innsjekking</h5>
                <p class="text-muted">Sjekk inn deltakere og administrer oppmøte</p>
                
                <!-- Event Selector -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Velg arrangement</label>
                        <select class="form-select" id="checkin-event-selector">
                            <option value="">Laster arrangementer...</option>
                        </select>
                    </div>
                </div>

                <!-- Check-in Statistics -->
                <div class="row mb-4" id="checkin-stats" style="display: none;">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Totalt påmeldte</h6>
                                <h3 class="mb-0" id="checkin-total-attendees">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Betalt</h6>
                                <h3 class="mb-0" id="checkin-paid-count">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Venter</h6>
                                <h3 class="mb-0" id="checkin-pending-count">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Total inntekt</h6>
                                <h3 class="mb-0" id="checkin-total-revenue">0 kr</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Check-in Form -->
                <div class="card mb-4" id="checkin-form-container" style="display: none;">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Ny innsjekking</h6>
                    </div>
                    <div class="card-body">
                        <form id="checkin-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Navn *</label>
                                    <input type="text" class="form-control" name="attendee_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">E-post *</label>
                                    <input type="email" class="form-control" name="attendee_email" required>
                                </div>
                            </div>
                            
                            <!-- Dynamic Registration Fields -->
                            <div id="dynamic-registration-fields"></div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Billett type</label>
                                    <select class="form-select" name="ticket_type">
                                        <option value="regular">Ordinær</option>
                                        <option value="member">Medlem</option>
                                        <option value="vip">VIP</option>
                                        <option value="staff">Ansatt</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Skal betale?</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_paying" id="isPayingCheckbox" checked>
                                        <label class="form-check-label" for="isPayingCheckbox">Betaler</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3" id="payment-method-container">
                                    <label class="form-label">Betalingsmetode</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="cash">Kontant</option>
                                        <option value="card">Kort</option>
                                        <option value="vipps">Vipps</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Notater</label>
                                    <input type="text" class="form-control" name="notes">
                                </div>
                            </div>
                            <div class="alert alert-info" id="staff-info" style="display: none;">
                                <i class="bi bi-info-circle me-2"></i>
                                <span id="staff-info-text">Deltakeren er registrert som ansatt</span>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Sjekk inn
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Checked-in Attendees List -->
                <div class="table-responsive" id="checkin-list-container" style="display: none;">
                    <h6 class="mb-3">Påmeldte deltakere</h6>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>Kontakt</th>
                                <th>Billett type</th>
                                <th>Pris</th>
                                <th>Betaling</th>
                                <th>Innsjekket</th>
                            </tr>
                        </thead>
                        <tbody id="checkin-attendees-list">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Ingen deltakere ennå
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="events-results" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Arrangement resultat</h5>
                <p class="text-muted">Økonomisk oversikt for arrangementer</p>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Totale arrangementer</h6>
                                <h3 class="mb-0" id="total-events">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Total inntekt</h6>
                                <h3 class="mb-0" id="total-revenue">0 kr</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Totale påmeldinger</h6>
                                <h3 class="mb-0" id="total-attendees">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Kommende</h6>
                                <h3 class="mb-0" id="upcoming-events">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arrangement</th>
                                <th>Dato</th>
                                <th>Påmeldte</th>
                                <th>Inntekt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="events-results-list">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Ingen resultater tilgjengelig
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
document.addEventListener('DOMContentLoaded', function() {
    // Load events when module is active
    if (document.getElementById('events-my')) {
        loadEvents();
    }

    // Create event form submission
    const createEventForm = document.getElementById('create-event-form');
    if (createEventForm) {
        createEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                title: formData.get('title'),
                description: formData.get('description'),
                start_date: formData.get('start_date'),
                start_time: formData.get('start_time'),
                end_date: formData.get('end_date') || null,
                end_time: formData.get('end_time') || null,
                venue_name: formData.get('venue_name'),
                event_address: formData.get('event_address'),
                event_city: formData.get('event_city'),
                event_postal: formData.get('event_postal'),
                min_attendees: formData.get('min_attendees') || null,
                max_attendees: formData.get('max_attendees') || null,
                age_limit: formData.get('age_limit') || null,
                ticket_price: formData.get('ticket_price') || 0,
                member_ticket_price: formData.get('member_ticket_price') || null,
                event_image_url: formData.get('event_image_url') || null,
                is_member_exclusive: formData.get('is_member_exclusive') ? 1 : 0
            };

            try {
                const response = await fetch('../api/create_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Arrangement opprettet!');
                    createEventForm.reset();
                    loadEvents();
                    // Switch to "Dine arrangement" tab
                    document.querySelector('[href="#events-my"]').click();
                } else {
                    alert('❌ Feil: ' + (result.message || 'Kunne ikke opprette arrangement'));
                }
            } catch (error) {
                console.error('Error creating event:', error);
                alert('❌ Feil ved opprettelse av arrangement');
            }
        });
    }
});

async function loadEvents() {
    try {
        const response = await fetch('../api/get_events.php');
        const result = await response.json();

        const eventsList = document.getElementById('events-list');
        if (result.success && result.events && result.events.length > 0) {
            eventsList.innerHTML = result.events.map(event => {
                const startDate = new Date(event.start_date + ' ' + event.start_time);
                const dateStr = startDate.toLocaleDateString('no-NO', { day: 'numeric', month: 'short', year: 'numeric' });
                const timeStr = event.start_time ? event.start_time.substring(0, 5) : '';
                
                let priceDisplay = '<span class="event-price event-price-free">Gratis</span>';
                if (event.ticket_price > 0) {
                    priceDisplay = `<span class="event-price">${event.ticket_price} kr</span>`;
                    if (event.member_ticket_price !== null && event.member_ticket_price !== undefined && event.member_ticket_price > 0) {
                        priceDisplay += `<br><small class="text-muted">${event.member_ticket_price} kr (medlem)</small>`;
                    }
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
                
                return `
                    <div class="col-md-4">
                        <div class="event-card">
                            ${imageHtml}
                            ${memberBadge}
                            <div class="event-body">
                                <h5 class="event-title">${event.title}</h5>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="bi bi-calendar3"></i>
                                        <span>${dateStr} ${timeStr}</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span>${location}</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="bi bi-people"></i>
                                        <span>${capacity} plasser</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="bi bi-person-circle"></i>
                                        <span>Opprettet av ${createdBy}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="event-footer">
                                <div>${priceDisplay}</div>
                                <div class="event-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewEvent('${event.id}')" title="Vis detaljer">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent('${event.id}')" title="Slett">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
    }
}

function viewEvent(eventId) {
    alert('Visning av arrangement: ' + eventId);
}

function deleteEvent(eventId) {
    if (confirm('Er du sikker på at du vil slette dette arrangementet?')) {
        fetch('../api/delete_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ event_id: eventId })
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert('✅ Arrangement slettet');
                loadEvents();
            } else {
                alert('❌ Feil: ' + (result.message || 'Kunne ikke slette'));
            }
        })
        .catch(error => {
            console.error('Error deleting event:', error);
            alert('❌ Feil ved sletting');
        });
    }
}

// ========== CHECK-IN FUNCTIONALITY ==========

let selectedEventId = null;

// Load events into check-in selector
async function loadCheckinEventSelector() {
    try {
        const response = await fetch('../api/get_events.php');
        const result = await response.json();
        
        const selector = document.getElementById('checkin-event-selector');
        if (result.success && result.events && result.events.length > 0) {
            selector.innerHTML = '<option value="">-- Velg arrangement --</option>' + 
                result.events.map(event => `
                    <option value="${event.id}">
                        ${event.title} - ${event.start_date} ${event.start_time}
                    </option>
                `).join('');
        } else {
            selector.innerHTML = '<option value="">Ingen arrangementer tilgjengelig</option>';
        }
    } catch (error) {
        console.error('Error loading events:', error);
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

// Handle event selection
document.getElementById('checkin-event-selector')?.addEventListener('change', async function(e) {
    selectedEventId = e.target.value;
    
    if (selectedEventId) {
        document.getElementById('checkin-stats').style.display = 'block';
        document.getElementById('checkin-form-container').style.display = 'block';
        document.getElementById('checkin-list-container').style.display = 'block';
        
        await loadEventRegistrationFields(selectedEventId);
        loadCheckins(selectedEventId);
    } else {
        document.getElementById('checkin-stats').style.display = 'none';
        document.getElementById('checkin-form-container').style.display = 'none';
        document.getElementById('checkin-list-container').style.display = 'none';
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

// Check-in form submission
document.getElementById('checkin-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!selectedEventId) {
        alert('Velg et arrangement først');
        return;
    }
    
    const formData = new FormData(this);
    const isPaying = document.getElementById('isPayingCheckbox').checked;
    const data = {
        event_id: selectedEventId,
        attendee_name: formData.get('attendee_name'),
        attendee_email: formData.get('attendee_email'),
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
        const response = await fetch('../api/checkin_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Deltaker sjekket inn!');
            this.reset();
            loadCheckins(selectedEventId);
        } else {
            alert('❌ Feil: ' + (result.message || 'Kunne ikke sjekke inn'));
        }
    } catch (error) {
        console.error('Error checking in:', error);
        alert('❌ Feil ved innsjekking');
    }
});

// Load check-ins for selected event
async function loadCheckins(eventId) {
    try {
        const response = await fetch(`../api/get_event_checkins.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            // Update statistics
            document.getElementById('checkin-total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('checkin-paid-count').textContent = result.summary.paid_count;
            document.getElementById('checkin-pending-count').textContent = result.summary.pending_count;
            document.getElementById('checkin-total-revenue').textContent = result.summary.total_revenue.toFixed(2) + ' kr';
            
            // Update attendee list
            const list = document.getElementById('checkin-attendees-list');
            if (result.checkins && result.checkins.length > 0) {
                list.innerHTML = result.checkins.map(checkin => {
                    const name = checkin.attendee_name || (checkin.first_name + ' ' + checkin.last_name);
                    const contact = checkin.attendee_email || checkin.attendee_phone || '-';
                    const checkinTime = checkin.check_in_time ? new Date(checkin.check_in_time).toLocaleString('no-NO') : '-';
                    
                    const paymentBadge = checkin.payment_status === 'paid' 
                        ? '<span class="badge bg-success">Betalt</span>' 
                        : '<span class="badge bg-warning">Venter</span>';
                    
                    return `
                        <tr>
                            <td><strong>${name}</strong></td>
                            <td>${contact}</td>
                            <td>${checkin.ticket_type}</td>
                            <td>${checkin.ticket_price} kr</td>
                            <td>${paymentBadge}</td>
                            <td><small>${checkinTime}</small></td>
                        </tr>
                    `;
                }).join('');
            } else {
                list.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Ingen deltakere ennå
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
document.querySelector('[href="#events-checkin"]')?.addEventListener('click', function() {
    loadCheckinEventSelector();
});

// Load events results
async function loadEventsResults() {
    try {
        const response = await fetch('/api/get_events_results.php');
        const result = await response.json();
        
        if (result.success) {
            // Oppdater sammendragskort
            document.getElementById('total-events').textContent = result.summary.total_events;
            document.getElementById('total-revenue').textContent = result.summary.total_revenue + ' kr';
            document.getElementById('total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('upcoming-events').textContent = result.summary.upcoming_events;
            
            // Oppdater arrangement liste
            const resultsList = document.getElementById('events-results-list');
            
            if (result.events && result.events.length > 0) {
                resultsList.innerHTML = result.events.map(event => {
                    const statusBadge = event.status === 'Kommende' 
                        ? '<span class="badge bg-primary">Kommende</span>'
                        : event.status === 'Pågår'
                        ? '<span class="badge bg-success">Pågår</span>'
                        : '<span class="badge bg-secondary">Avsluttet</span>';
                    
                    return `
                        <tr>
                            <td><strong>${event.title}</strong></td>
                            <td>${event.start_date} ${event.start_time}</td>
                            <td>${event.attendee_count}</td>
                            <td>${parseFloat(event.event_revenue).toFixed(2)} kr</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                resultsList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Ingen resultater tilgjengelig
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading events results:', error);
    }
}

// Load results when results tab is shown
document.querySelector('[href="#events-results"]')?.addEventListener('shown.bs.tab', function() {
    loadEventsResults();
});
</script>
