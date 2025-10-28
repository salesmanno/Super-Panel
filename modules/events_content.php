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
        <!-- Event Selection Cards -->
        <div id="checkin-event-selection">
            <h5 class="mb-3"><i class="bi bi-calendar-check me-2"></i>Velg arrangement for innsjekking</h5>
            <div id="checkin-events-grid" class="row g-3 mb-4">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Laster arrangementer...</p>
                </div>
            </div>
        </div>

        <!-- Check-in Interface (hidden until event selected) -->
        <div id="checkin-interface" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0" id="checkin-event-title">Arrangementsnavn</h5>
                    <small class="text-muted" id="checkin-event-date">Dato og tid</small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="backToEventSelection()">
                    <i class="bi bi-arrow-left me-1"></i>Tilbake til oversikt
                </button>
            </div>

            <!-- Check-in Statistics -->
            <div class="row mb-4" id="checkin-stats">
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
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Navn *</label>
                                    <input type="text" class="form-control" name="attendee_name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">E-post</label>
                                    <input type="email" class="form-control" name="attendee_email">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" name="attendee_phone">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Billett type</label>
                                    <select class="form-select" name="ticket_type" id="ticket-type-select">
                                        <option value="">Laster...</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3" id="payment-method-container">
                                    <label class="form-label">Betalingsmetode</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="cash">Kontant</option>
                                        <option value="vipps">Vipps</option>
                                        <option value="on_arrival">Oppmøte</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
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

                <!-- Checked-in Attendees List with Search and Filter -->
                <div id="checkin-list-container" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Påmeldte deltakere</h6>
                        <div class="d-flex gap-2">
                            <input type="text" id="attendee-search" class="form-control form-control-sm" placeholder="🔍 Søk navn, epost eller telefon..." style="width: 250px;">
                            <select id="attendee-filter" class="form-select form-select-sm" style="width: 150px;">
                                <option value="all">Alle</option>
                                <option value="paid">Betalt</option>
                                <option value="pending">Ubetalt</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
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
                // Determine if editing or creating
                const isEditing = editingEventId !== null;
                const endpoint = isEditing ? '../api/edit_event.php' : '../api/create_event.php';
                
                if (isEditing) {
                    data.event_id = editingEventId;
                }
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert(isEditing ? '✅ Arrangement oppdatert!' : '✅ Arrangement opprettet!');
                    createEventForm.reset();
                    editingEventId = null;
                    
                    // Reset form title and button
                    document.querySelector('#events-create .card-title').textContent = 'Opprett nytt arrangement';
                    document.querySelector('#create-event-form button[type="submit"]').innerHTML = '<i class="bi bi-check-circle me-1"></i>Opprett arrangement';
                    
                    loadEvents();
                    // Switch to "Dine arrangement" tab
                    document.querySelector('[href="#events-my"]').click();
                } else {
                    alert('❌ Feil: ' + (result.message || 'Kunne ikke lagre arrangement'));
                }
            } catch (error) {
                console.error('Error saving event:', error);
                alert('❌ Feil ved lagring av arrangement');
            }
        });
    }
    
    // Reset editing mode when switching tabs
    document.querySelectorAll('[href^="#events-"]').forEach(tab => {
        tab.addEventListener('click', function() {
            if (!this.getAttribute('href').includes('events-create')) {
                editingEventId = null;
                document.querySelector('#events-create .card-title').textContent = 'Opprett nytt arrangement';
                document.querySelector('#create-event-form button[type="submit"]').innerHTML = '<i class="bi bi-check-circle me-1"></i>Opprett arrangement';
                document.getElementById('create-event-form').reset();
            }
        });
    });
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
                                    <button class="btn btn-sm btn-outline-primary" onclick="editEvent('${event.id}')" title="Rediger">
                                        <i class="bi bi-pencil"></i>
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

let editingEventId = null;

async function editEvent(eventId) {
    try {
        // Fetch event details
        const response = await fetch('../api/get_events.php');
        const result = await response.json();
        
        if (result.success) {
            const event = result.events.find(e => e.id == eventId);
            if (!event) {
                alert('❌ Fant ikke arrangement');
                return;
            }
            
            // Store editing ID
            editingEventId = eventId;
            
            // Pre-fill form
            const form = document.getElementById('create-event-form');
            form.querySelector('[name="title"]').value = event.title || '';
            form.querySelector('[name="description"]').value = event.description || '';
            form.querySelector('[name="start_date"]').value = event.start_date || '';
            form.querySelector('[name="start_time"]').value = event.start_time || '';
            form.querySelector('[name="end_date"]').value = event.end_date || '';
            form.querySelector('[name="end_time"]').value = event.end_time || '';
            form.querySelector('[name="venue_name"]').value = event.venue_name || '';
            form.querySelector('[name="event_address"]').value = event.event_address || '';
            form.querySelector('[name="event_city"]').value = event.event_city || '';
            form.querySelector('[name="event_postal"]').value = event.event_postal || '';
            form.querySelector('[name="min_attendees"]').value = event.min_attendees || '';
            form.querySelector('[name="max_attendees"]').value = event.max_attendees || '';
            form.querySelector('[name="age_limit"]').value = event.age_limit || '';
            form.querySelector('[name="ticket_price"]').value = event.ticket_price || '0';
            form.querySelector('[name="member_ticket_price"]').value = event.member_ticket_price || '';
            form.querySelector('[name="event_image_url"]').value = event.event_image_url || '';
            
            const memberExclusiveCheckbox = form.querySelector('[name="is_member_exclusive"]');
            if (memberExclusiveCheckbox) {
                memberExclusiveCheckbox.checked = event.is_member_exclusive == 1;
            }
            
            // Update form title and button
            document.querySelector('#events-create .card-title').textContent = 'Rediger arrangement';
            document.querySelector('#create-event-form button[type="submit"]').innerHTML = '<i class="bi bi-check-circle me-1"></i>Oppdater arrangement';
            
            // Switch to create/edit tab
            document.querySelector('[href="#events-create"]').click();
        }
    } catch (error) {
        console.error('Error loading event for editing:', error);
        alert('❌ Feil ved lasting av arrangement');
    }
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
let selectedEventData = null;
let allAttendees = []; // Store all attendees for filtering

// Load events into visual card selector
async function loadCheckinEventSelector() {
    try {
        const response = await fetch('../api/get_events.php');
        const result = await response.json();
        
        const grid = document.getElementById('checkin-events-grid');
        if (result.success && result.events && result.events.length > 0) {
            grid.innerHTML = result.events.map(event => {
                const startDate = new Date(event.start_date + ' ' + event.start_time);
                const dateStr = startDate.toLocaleDateString('no-NO', { day: 'numeric', month: 'short', year: 'numeric' });
                const timeStr = event.start_time ? event.start_time.substring(0, 5) : '';
                const imageUrl = event.event_image_url || '';
                const capacity = event.max_attendees ? `${event.current_attendees || 0}/${event.max_attendees}` : 'Ubegrenset';
                
                return `
                    <div class="col-md-4">
                        <div class="card h-100 hover-shadow" style="cursor: pointer;" onclick="selectEventForCheckin('${event.id}', '${event.title.replace(/'/g, "\\'")}', '${dateStr} ${timeStr}')">
                            ${imageUrl ? `
                                <div style="height: 150px; background: url('${imageUrl}') center/cover; border-radius: 8px 8px 0 0;"></div>
                            ` : `
                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 150px; border-radius: 8px 8px 0 0;">
                                    <i class="bi bi-calendar-event" style="font-size: 3rem; color: #ccc;"></i>
                                </div>
                            `}
                            <div class="card-body">
                                <h6 class="card-title mb-2">${event.title}</h6>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-calendar3 me-1"></i>${dateStr} ${timeStr}
                                </p>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-geo-alt me-1"></i>${event.venue_name || event.event_city || 'Ikke angitt'}
                                </p>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-people me-1"></i>${capacity} plasser
                                </p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Klikk for innsjekking</small>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-2">Ingen arrangementer tilgjengelig for innsjekking</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('checkin-events-grid').innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">Feil ved lasting av arrangementer</p>
            </div>
        `;
    }
}

async function selectEventForCheckin(eventId, eventTitle, eventDate) {
    selectedEventId = eventId;
    document.getElementById('checkin-event-title').textContent = eventTitle;
    document.getElementById('checkin-event-date').textContent = eventDate;
    
    // Fetch full event data to get pricing
    try {
        const response = await fetch('../api/get_events.php');
        const result = await response.json();
        
        if (result.success) {
            selectedEventData = result.events.find(e => e.id == eventId);
            
            // Update ticket type dropdown with actual prices
            updateTicketTypeOptions();
        }
    } catch (error) {
        console.error('Error loading event data:', error);
    }
    
    // Hide event selection, show check-in interface
    document.getElementById('checkin-event-selection').style.display = 'none';
    document.getElementById('checkin-interface').style.display = 'block';
    
    // Load check-ins for this event
    loadCheckins(eventId);
}

function updateTicketTypeOptions() {
    if (!selectedEventData) return;
    
    const ticketTypeSelect = document.getElementById('ticket-type-select');
    if (!ticketTypeSelect) return;
    
    let options = [];
    
    // Always add regular ticket if there's a price
    if (selectedEventData.ticket_price > 0) {
        options.push({
            value: 'regular',
            label: `Ordinær (${parseFloat(selectedEventData.ticket_price).toFixed(2)} kr)`,
            price: selectedEventData.ticket_price
        });
    } else {
        options.push({
            value: 'regular',
            label: 'Ordinær (Gratis)',
            price: 0
        });
    }
    
    // Add member ticket if different from regular
    if (selectedEventData.member_ticket_price !== null && 
        selectedEventData.member_ticket_price !== undefined && 
        selectedEventData.member_ticket_price != selectedEventData.ticket_price) {
        options.push({
            value: 'member',
            label: `Medlem (${parseFloat(selectedEventData.member_ticket_price).toFixed(2)} kr)`,
            price: selectedEventData.member_ticket_price
        });
    }
    
    // Always add VIP and Staff options
    options.push({
        value: 'vip',
        label: 'VIP (Egen pris)',
        price: selectedEventData.ticket_price
    });
    
    options.push({
        value: 'staff',
        label: 'Ansatt (Gratis)',
        price: 0
    });
    
    // Build dropdown
    ticketTypeSelect.innerHTML = options.map(opt => 
        `<option value="${opt.value}" data-price="${opt.price}">${opt.label}</option>`
    ).join('');
    
    // Remove old event listeners to prevent duplicates
    const newSelect = ticketTypeSelect.cloneNode(true);
    ticketTypeSelect.parentNode.replaceChild(newSelect, ticketTypeSelect);
    
    // Add change event to update payment method based on ticket type
    newSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
        const paymentSelect = document.querySelector('[name="payment_method"]');
        
        // If staff or free event, default to "Gratis" or hide payment method
        if (this.value === 'staff' || price === 0) {
            // Free - payment method not needed but keep visible for consistency
            if (paymentSelect) {
                paymentSelect.value = 'on_arrival'; // Default to "Oppmøte" for free
            }
        }
    });
}

function backToEventSelection() {
    selectedEventId = null;
    allAttendees = [];
    document.getElementById('checkin-event-selection').style.display = 'block';
    document.getElementById('checkin-interface').style.display = 'none';
    
    // Clear search and filter
    const searchInput = document.getElementById('attendee-search');
    const filterSelect = document.getElementById('attendee-filter');
    if (searchInput) searchInput.value = '';
    if (filterSelect) filterSelect.value = 'all';
}

// Payment method is always visible now (no toggle needed)

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

// Search and filter attendees
document.getElementById('attendee-search')?.addEventListener('input', function(e) {
    filterAttendees();
});

document.getElementById('attendee-filter')?.addEventListener('change', function(e) {
    filterAttendees();
});

function filterAttendees() {
    const searchTerm = document.getElementById('attendee-search')?.value.toLowerCase() || '';
    const filterValue = document.getElementById('attendee-filter')?.value || 'all';
    
    let filtered = allAttendees;
    
    // Apply payment status filter
    if (filterValue === 'paid') {
        filtered = filtered.filter(a => a.payment_status === 'paid');
    } else if (filterValue === 'pending') {
        filtered = filtered.filter(a => a.payment_status === 'pending');
    }
    
    // Apply search filter
    if (searchTerm) {
        filtered = filtered.filter(a => {
            const name = (a.attendee_name || '').toLowerCase();
            const email = (a.attendee_email || '').toLowerCase();
            const phone = (a.attendee_phone || '').toLowerCase();
            return name.includes(searchTerm) || email.includes(searchTerm) || phone.includes(searchTerm);
        });
    }
    
    // Render filtered list
    renderAttendeeList(filtered);
}

function renderAttendeeList(attendees) {
    const tbody = document.getElementById('checkin-attendees-list');
    if (!attendees || attendees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Ingen deltakere funnet</td></tr>';
        return;
    }
    
    tbody.innerHTML = attendees.map(attendee => {
        const checkedInDate = new Date(attendee.created_at);
        const checkedInStr = checkedInDate.toLocaleString('no-NO');
        const paymentBadge = attendee.payment_status === 'paid' 
            ? '<span class="badge bg-success">Betalt</span>' 
            : '<span class="badge bg-warning text-dark">Venter</span>';
        
        return `
            <tr>
                <td><strong>${attendee.attendee_name}</strong></td>
                <td>
                    ${attendee.attendee_email ? `<div><small>${attendee.attendee_email}</small></div>` : ''}
                    ${attendee.attendee_phone ? `<div><small>${attendee.attendee_phone}</small></div>` : ''}
                </td>
                <td><span class="badge bg-secondary">${attendee.ticket_type || 'Ordinær'}</span></td>
                <td><strong>${parseFloat(attendee.amount_paid || 0).toFixed(2)} kr</strong></td>
                <td>${paymentBadge}</td>
                <td><small class="text-muted">${checkedInStr}</small></td>
            </tr>
        `;
    }).join('');
}

// Check-in form submission
document.getElementById('checkin-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!selectedEventId) {
        alert('Velg et arrangement først');
        return;
    }
    
    const formData = new FormData(this);
    const data = {
        event_id: selectedEventId,
        attendee_name: formData.get('attendee_name'),
        attendee_email: formData.get('attendee_email'),
        attendee_phone: formData.get('attendee_phone'),
        ticket_type: formData.get('ticket_type'),
        payment_method: formData.get('payment_method'),
        notes: formData.get('notes')
    };
    
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
            
            // Store all attendees for filtering
            allAttendees = result.checkins || [];
            
            // Render initial list
            renderAttendeeList(allAttendees);
            
            // Show containers
            document.getElementById('checkin-form-container').style.display = 'block';
            document.getElementById('checkin-list-container').style.display = 'block';
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
