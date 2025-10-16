<div class="module-content">
    <div id="events-my" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Dine arrangement</h5>
                <p class="text-muted">Oversikt over alle dine arrangementer</p>
                <div class="table-responsive">
                    <table class="table table-hover" id="events-table">
                        <thead>
                            <tr>
                                <th>Arrangement</th>
                                <th>Dato & Tid</th>
                                <th>Sted</th>
                                <th>Påmeldte</th>
                                <th>Pris</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="events-list">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Laster arrangementer...
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                            <input type="text" class="form-control" name="title" required placeholder="F.eks. Sommerfest 2025">
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
                        <textarea class="form-control" name="description" rows="3" placeholder="Beskriv arrangementet..."></textarea>
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
                            <input type="number" class="form-control" name="age_limit" placeholder="F.eks. 18">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stedsnavn</label>
                            <input type="text" class="form-control" name="venue_name" placeholder="F.eks. Oslo Konserthus">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="event_address" placeholder="Gateadresse">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">By</label>
                            <input type="text" class="form-control" name="event_city" placeholder="F.eks. Oslo">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postnummer</label>
                            <input type="text" class="form-control" name="event_postal" placeholder="F.eks. 0150">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Min. deltakere</label>
                            <input type="number" class="form-control" name="min_attendees" placeholder="Minimum">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Maks. deltakere</label>
                            <input type="number" class="form-control" name="max_attendees" placeholder="Maksimum">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price" placeholder="0">
                        </div>
                    </div>

                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Medlemspris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="member_ticket_price" placeholder="Valgfri medlemsrabatt">
                            <small class="form-text text-muted">La stå tom hvis samme pris som ordinær billett</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="event_image_url" placeholder="https://example.com/bilde.jpg">
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
                                    <select class="form-select" name="ticket_type">
                                        <option value="regular">Ordinær</option>
                                        <option value="member">Medlem</option>
                                        <option value="vip">VIP</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Betalingsmetode</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="cash">Kontant</option>
                                        <option value="card">Kort</option>
                                        <option value="vipps">Vipps</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Notater</label>
                                    <input type="text" class="form-control" name="notes" placeholder="Valgfritt">
                                </div>
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
                let dateTime = `${event.start_date} ${event.start_time}`;
                if (event.end_date || event.end_time) {
                    dateTime += ` - ${event.end_date || event.start_date} ${event.end_time || ''}`;
                }
                
                let priceDisplay = 'Gratis';
                if (event.ticket_price > 0) {
                    priceDisplay = event.ticket_price + ' kr';
                    if (event.member_ticket_price !== null && event.member_ticket_price !== undefined) {
                        priceDisplay += ` <span class="badge bg-success">${event.member_ticket_price} kr (medlem)</span>`;
                    }
                }
                
                return `
                    <tr>
                        <td>
                            <strong>${event.title}</strong>
                            ${event.is_member_exclusive == 1 ? '<span class="badge bg-warning ms-2">Kun medlemmer</span>' : ''}
                        </td>
                        <td>${dateTime}</td>
                        <td>${event.venue_name || event.event_city || '-'}</td>
                        <td>0 / ${event.max_attendees || '∞'}</td>
                        <td>${priceDisplay}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewEvent('${event.id}')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent('${event.id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            eventsList.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Ingen arrangementer funnet
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('events-list').innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger py-4">
                    Feil ved lasting av arrangementer
                </td>
            </tr>
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

// Handle event selection
document.getElementById('checkin-event-selector')?.addEventListener('change', function(e) {
    selectedEventId = e.target.value;
    
    if (selectedEventId) {
        document.getElementById('checkin-stats').style.display = 'block';
        document.getElementById('checkin-form-container').style.display = 'block';
        document.getElementById('checkin-list-container').style.display = 'block';
        loadCheckins(selectedEventId);
    } else {
        document.getElementById('checkin-stats').style.display = 'none';
        document.getElementById('checkin-form-container').style.display = 'none';
        document.getElementById('checkin-list-container').style.display = 'none';
    }
});

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
</script>
