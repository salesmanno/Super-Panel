(function () {
    'use strict';
    const root = document.getElementById('events-module');
    if (!root) return;
    const hasMembershipAccess = root.dataset.membershipAccess === '1';

    const state = {
        events: [],
        loaded: false,
        failed: false,
        editor: null,
        editorId: null,
        editorTrigger: null,
        scrollY: 0,
        listScroll: 0,
        deferredRender: false,
        appliedSearch: '',
        appliedStatus: 'active',
        modalSections: [],
        modalKind: null,
        modalMarker: null,
        transitioningToDetails: false,
        childModalId: null,
        modalControls: null,
        registrationObserver: null
    };
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
    }[char]));
    const asBool = value => value === true || value === 1 || value === '1';
    const money = value => `${new Intl.NumberFormat('nb-NO', {maximumFractionDigits:2}).format(Number(value) || 0)} kr`;
    const noRegistration = event => event.registration_type === 'none';

    function displayDate(event) {
        if (typeof window.formatNorskDato === 'function') {
            return window.formatNorskDato(event.start_date, event.start_time, {includeTime:true});
        }
        const date = String(event.start_date || '').slice(0,10).split('-');
        return date.length === 3 ? `${date[2]}.${date[1]}.${date[0]}${event.start_time ? ` · ${String(event.start_time).slice(0,5)}` : ''}` : 'Dato ikke angitt';
    }

    function eventSortTime(event) {
        return `${String(event.start_date || '').slice(0,10)}T${String(event.start_time || '00:00').slice(0,5)}`;
    }

    function osloNow() {
        const parts = new Intl.DateTimeFormat('en-CA',{timeZone:'Europe/Oslo',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date());
        const part = type => parts.find(item => item.type === type)?.value || '';
        return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`;
    }

    function isUpcoming(event) {
        return !asBool(event.is_past) && asBool(event.is_active) &&
            eventSortTime(event) >= osloNow();
    }

    function statusEvent(event) {
        if (!asBool(event.is_active)) return 'hidden';
        return asBool(event.is_past) ? 'past' : 'active';
    }

    function matchesStatus(event,selected) {
        if (selected === 'all') return true;
        if (selected === 'hidden') return !asBool(event.is_active);
        if (selected === 'past') return asBool(event.is_past);
        return asBool(event.is_active) && !asBool(event.is_past);
    }

    function renderSummary() {
        const target = document.getElementById('eventsDashboardSummary');
        if (!target) return;
        if (!state.loaded) {
            if (state.failed) {
                target.innerHTML = `<div class="events-summary-strip is-error">
                    <div class="events-summary-next"><span class="events-summary-mark"><i class="bi bi-exclamation-circle"></i></span><div>
                        <small>ARRANGEMENTSOVERSIKT</small><strong>Data ikke tilgjengelig</strong><span>Oppsummering kan ikke beregnes før arrangementene er lastet.</span></div></div>
                    <div class="events-summary-metric"><small>KOMMENDE</small><strong>—</strong><span>Ikke lastet</span></div>
                    <div class="events-summary-metric"><small>VENTER GODKJENNING</small><strong>—</strong><span>Ikke lastet</span></div>
                </div>`;
                return;
            }
            target.innerHTML = `<div class="events-summary-strip is-loading" aria-label="Laster oversikt"><span></span><span></span><span></span></div>`;
            return;
        }
        const upcomingEvents = state.events.filter(isUpcoming).sort((a,b) => eventSortTime(a).localeCompare(eventSortTime(b)));
        const upcoming = upcomingEvents[0];
        const countsKnown = state.events.every(event => asBool(event.counts_loaded));
        const pending = countsKnown
            ? state.events.reduce((sum,event) => sum + (Number(event.pending_registrations) || 0),0)
            : null;
        target.innerHTML = `<div class="events-summary-strip">
            <div class="events-summary-next"><span class="events-summary-mark"><i class="bi bi-calendar2-week"></i></span>
                <div><small>NESTE AKTIVE ARRANGEMENT</small><strong>${upcoming ? esc(upcoming.title) : 'Ingen kommende arrangementer'}</strong>
                <span>${upcoming ? esc(displayDate(upcoming)) : 'Opprett et arrangement for å komme i gang'}</span></div></div>
            <button type="button" class="events-summary-metric" data-open-overview="upcoming" aria-label="Vis kommende arrangementer">
                <small>KOMMENDE</small><strong>${upcomingEvents.length}</strong><span>arrangement${upcomingEvents.length === 1 ? '' : 'er'}<i class="bi bi-arrow-right-short"></i></span>
            </button>
            <button type="button" class="events-summary-metric" data-open-overview="requests" aria-label="Vis ventende påmeldinger">
                <small>VENTER GODKJENNING</small><strong>${pending === null ? '—' : pending}</strong><span>${pending === null ? 'Tall ikke lastet' : 'påmeldinger'}<i class="bi bi-arrow-right-short"></i></span>
            </button>
        </div>`;
    }

    function detailCell(label, value) {
        return `<div class="events-detail-cell"><small>${esc(label)}</small><strong>${esc(value || 'Ikke angitt')}</strong></div>`;
    }

    function renderEntry(event) {
        const id = String(event.id);
        const place = [event.venue_name, event.event_city].filter(Boolean).join(' · ') || 'Sted ikke angitt';
        const stateClass = statusEvent(event);
        const stateText = stateClass === 'past' ? 'Avsluttet' : stateClass === 'hidden' ? 'Skjult' : 'Synlig';
        const image = typeof window.safeEventImageUrl === 'function' ? window.safeEventImageUrl(event.event_image_url) : '';
        const imageMarkup = image
            ? `<img src="${esc(image)}" alt="" loading="lazy" data-card-image>`
            : '<i class="bi bi-calendar2-event" aria-hidden="true"></i>';
        const registrationText = noRegistration(event) ? 'Uten påmelding' : (event.registration_type === 'binding' ? 'Bindende' : 'Påmelding');
        const countsLoaded = asBool(event.counts_loaded);
        const attendees = countsLoaded ? Number(event.current_attendees) : null;
        const pending = countsLoaded ? Number(event.pending_registrations) : null;
        const ticketKnown = event.ticket_price !== null && event.ticket_price !== undefined && String(event.ticket_price) !== '';
        const regularPrice = ticketKnown ? (Number(event.ticket_price) === 0 ? 'Gratis' : money(event.ticket_price)) : 'Pris ikke tilgjengelig';
        const memberPrices = hasMembershipAccess && Array.isArray(event.membership_prices) ? event.membership_prices : [];
        const memberPriceLines = hasMembershipAccess
            ? (asBool(event.membership_prices_loaded)
                ? memberPrices.length
                    ? memberPrices.map(price => `<span class="events-member-price"><b>${esc(price.plan_name || 'Medlemsplan')}</b><strong>${price.price === null || price.price === undefined || String(price.price) === '' ? 'Pris ikke tilgjengelig' : money(price.price)}</strong>${asBool(price.public_display) ? '' : '<em>Skjult offentlig</em>'}</span>`).join('')
                    : '<span class="events-member-price-note">Ingen medlemspriser satt opp</span>'
                : '<span class="events-member-price-note is-unavailable">Medlemspriser ikke lastet</span>')
            : '';
        const counts = countsLoaded
            ? `${attendees} påmeldt · ${pending} venter`
            : 'Påmeldings- og deltakertall ikke lastet';
        const capacityText = countsLoaded
            ? (event.max_attendees ? `${attendees} / ${event.max_attendees} plasser` : `${attendees} påmeldt · ubegrenset`)
            : 'Kapasitet ikke tilgjengelig';
        const memberRequired = hasMembershipAccess && asBool(event.is_member_exclusive);
        const membershipDetails = hasMembershipAccess
            ? `<div class="events-member-prices"><small>Medlemspriser</small>${memberPriceLines}</div>`
            : '';
        const endDate = event.end_date ? displayDate({start_date:event.end_date,start_time:event.end_time}) : 'Ingen sluttdato';
        const registrationDeadline = event.registration_deadline
            ? (() => {
                const raw = String(event.registration_deadline);
                const date = raw.slice(0,10).split('-').reverse().join('.');
                const time = raw.slice(11,16);
                return `${date}${time ? ` kl. ${time}` : ''}`;
            })()
            : 'Ingen påmeldingsfrist';
        const membershipRequirement = memberRequired
            ? (asBool(event.memberverification) ? 'Kun medlemmer · medlemskap verifiseres automatisk' : 'Kun medlemmer')
            : 'Åpent for alle';
        const details = `<div class="events-event-details" id="events-detail-${esc(id)}" hidden>
            <h3 class="events-details-title">${esc(event.title || 'Arrangement')}</h3>
            <div class="events-details-copy">${esc(event.description || 'Ingen beskrivelse er lagt til.')}</div>
            <div class="events-details-grid">
                ${detailCell('Tidspunkt',displayDate(event))}
                ${detailCell('Sted',[event.venue_name,event.event_address,event.event_postal,event.event_city].filter(Boolean).join(', '))}
                ${detailCell('Påmelding',registrationText)}
                ${detailCell('Billettpris',regularPrice)}
                ${detailCell('Deltakere',counts)}
                ${detailCell('Kapasitet',capacityText)}
                ${detailCell('Slutt',endDate)}
                ${detailCell('Påmeldingsfrist',registrationDeadline)}
                ${hasMembershipAccess ? detailCell('Medlemskrav',membershipRequirement) : ''}
            </div>
            ${membershipDetails}
        </div>`;
        return `<article class="events-event-entry" id="events-event-${esc(id)}">
            <div class="events-event-card${stateClass === 'hidden' ? ' is-inactive' : ''}${stateClass === 'past' ? ' is-past' : ''}" data-event-card="${esc(id)}" tabindex="0" role="group" aria-expanded="false" aria-controls="events-detail-${esc(id)}" aria-label="${esc(event.title || 'Arrangement')}. Trykk Enter for å vise detaljer.">
                <div class="events-event-cover">${imageMarkup}</div>
                <div class="events-event-main">
                    <div class="events-event-top">
                        <button type="button" class="events-event-open" data-event-details="${esc(id)}" aria-expanded="false" aria-controls="events-detail-${esc(id)}">${esc(event.title || 'Arrangement uten navn')}</button>
                        <div class="dropdown events-event-actions">
                            <button type="button" class="events-event-menu-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Handlinger for ${esc(event.title || 'arrangement')}"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button type="button" class="dropdown-item" data-event-action="edit" data-event-id="${esc(id)}"><i class="bi bi-pencil me-2"></i>Grunnopplysninger</button></li>
                                <li><button type="button" class="dropdown-item" data-event-action="fields" data-event-id="${esc(id)}"><i class="bi bi-ui-checks me-2"></i>Skjemafelt</button></li>
                                <li><button type="button" class="dropdown-item" data-event-action="visibility" data-event-id="${esc(id)}" data-active="${asBool(event.is_active) ? '1' : '0'}"><i class="bi bi-eye${asBool(event.is_active) ? '-slash' : ''} me-2"></i>${asBool(event.is_active) ? 'Skjul' : 'Vis'}</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="dropdown-item text-danger" data-event-action="delete" data-event-id="${esc(id)}"><i class="bi bi-trash me-2"></i>Slett</button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="events-event-meta">
                        <span><i class="bi bi-calendar3"></i>${esc(displayDate(event))}</span>
                        <span><i class="bi bi-geo-alt"></i>${esc(place)}</span>
                    </div>
                    <div class="events-event-facts">
                        <span><i class="bi bi-tag"></i><strong>${esc(regularPrice)}</strong>${memberRequired ? '<small>ordinær</small>' : ''}</span>
                        <span><i class="bi bi-person-check"></i>${esc(counts)}</span>
                        <span><i class="bi bi-people"></i>${esc(capacityText)}</span>
                    </div>
                    <div class="events-event-tags">
                        <span class="events-event-tag ${stateClass === 'active' ? 'is-live' : stateClass === 'past' ? 'is-past' : 'is-hidden'}">${stateClass === 'active' ? '<i class="bi bi-broadcast"></i>' : stateClass === 'past' ? '<i class="bi bi-clock-history"></i>' : '<i class="bi bi-eye-slash"></i>'}${stateText}</span>
                        <span class="events-event-tag"><i class="bi bi-people"></i>${esc(registrationText)}</span>
                    </div>
                    ${membershipDetails}
                </div>
                ${details}
                <div class="events-inline-fields" id="events-inline-fields-${esc(id)}" hidden></div>
            </div>
        </article>`;
    }

    function renderCreateTile() {
        return `<button type="button" class="events-create-tile" data-event-create>
            <span class="events-create-mark"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
            <span><strong>Opprett arrangement</strong><small>Start veiviseren for et nytt arrangement</small></span>
        </button>`;
    }

    function renderList() {
        const host = document.getElementById('eventsDashboardList');
        const status = document.getElementById('eventsDashboardNotice');
        if (!host) return;
        if (!state.loaded) {
            host.innerHTML = `<div class="events-event-list"><div class="events-dashboard-skeleton" aria-label="Laster arrangementer"><span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span></div>${renderCreateTile()}</div>`;
            renderSummary();
            if (status) status.textContent = 'Henter arrangementene dine…';
            return;
        }
        renderSummary();
        const query = state.appliedSearch.trim().toLocaleLowerCase('nb-NO');
        const selected = document.getElementById('eventsDashboardStatus')?.value || state.appliedStatus;
        state.appliedStatus = selected;
        const filtered = state.events.filter(item => {
            const text = `${item.title || ''} ${item.description || ''} ${item.venue_name || ''} ${item.event_city || ''}`.toLocaleLowerCase('nb-NO');
            return (!query || text.includes(query)) && matchesStatus(item,selected);
        });
        if (status) {
            status.innerHTML = state.failed
                ? 'Oppdatering feilet. Viser sist lastede data; <button type="button" class="btn btn-sm btn-link p-0 align-baseline" data-events-retry>prøv igjen</button>.'
                : `${filtered.length} av ${state.events.length} arrangement${state.events.length === 1 ? '' : 'er'}`;
        }
        if (!filtered.length) {
            const noneExist = state.events.length === 0;
            host.innerHTML = `<div class="events-dashboard-empty"><i class="bi bi-calendar2-event"></i>
                <strong>${noneExist ? 'Ingen arrangementer ennå' : 'Ingen treff i dette utvalget'}</strong>
                <span>${noneExist ? 'Opprett det første arrangementet for å legge planen.' : 'Endre søket eller velg en annen visning.'}</span></div>
                <div class="events-event-list">${renderCreateTile()}</div>`;
            return;
        }
        host.innerHTML = `<div class="events-event-list">${filtered.map(renderEntry).join('')}${renderCreateTile()}</div>`;
        host.querySelectorAll('[data-card-image]').forEach(image => image.addEventListener('error', () => image.remove(), {once:true}));
    }

    function sourceUpdated(event) {
        const detail = event.detail || {};
        if (detail.success) {
            state.events = Array.isArray(detail.events) ? detail.events : [];
            state.loaded = true;
            state.failed = false;
            if (state.editorId) state.deferredRender = true;
            else renderList();
        } else {
            state.failed = true;
            const status = document.getElementById('eventsDashboardNotice');
            if (!state.loaded) {
                const host = document.getElementById('eventsDashboardList');
                if (host) host.innerHTML = `<div class="events-dashboard-error" role="alert"><span>${esc(detail.message || 'Arrangementene kunne ikke lastes.')}</span><button type="button" class="btn btn-sm btn-outline-secondary" data-events-retry>Prøv igjen</button></div><div class="events-event-list">${renderCreateTile()}</div>`;
                if (status) status.textContent = 'Ingen tall vises når datakilden ikke er tilgjengelig.';
                renderSummary();
            } else if (state.editorId) {
                state.deferredRender = true;
                if (status) status.innerHTML = 'Oppdatering feilet. Sist lastede data og feltutkastet beholdes; <button type="button" class="btn btn-sm btn-link p-0 align-baseline" data-events-retry>prøv igjen</button>.';
            } else renderList();
        }
    }

    function scrollEntryToTop(entry) {
        if (!entry) return;
        const host = document.getElementById('eventsDashboardList');
        if (!host) return;
        host.scrollTo({top:host.scrollTop + entry.getBoundingClientRect().top - host.getBoundingClientRect().top,behavior:reducedMotion.matches ? 'auto' : 'smooth'});
    }

    async function closeFieldEditor(force) {
        if (!state.editorId) return true;
        if (!force && state.editor && !state.editor.confirmClose()) return false;
        const id = state.editorId;
        const panel = document.getElementById(`events-inline-fields-${CSS.escape(id)}`);
        if (panel) { panel.replaceChildren(); panel.hidden = true; }
        state.editor = null;
        state.editorId = null;
        state.editorTrigger?.setAttribute('aria-expanded','false');
        const trigger = state.editorTrigger;
        state.editorTrigger = null;
        if (state.deferredRender) {
            state.deferredRender = false;
            renderList();
        }
        document.getElementById('eventsDashboardList')?.scrollTo({top:state.listScroll,behavior:reducedMotion.matches ? 'auto' : 'smooth'});
        window.scrollTo({top:state.scrollY,behavior:reducedMotion.matches ? 'auto' : 'smooth'});
        trigger?.focus({preventScroll:true});
        return true;
    }

    async function openFieldEditor(id, trigger) {
        if (state.editorId && state.editorId === String(id)) return closeFieldEditor(false);
        if (state.editorId && !(await closeFieldEditor(false))) return false;
        const panel = document.getElementById(`events-inline-fields-${CSS.escape(String(id))}`);
        const entry = document.getElementById(`events-event-${CSS.escape(String(id))}`);
        const item = state.events.find(event => String(event.id) === String(id));
        if (!panel || !entry || !item) return false;
        state.scrollY = window.scrollY;
        state.listScroll = document.getElementById('eventsDashboardList')?.scrollTop || 0;
        state.editorId = String(id);
        state.editorTrigger = trigger || null;
        if (trigger) trigger.setAttribute('aria-expanded','true');
        panel.hidden = false;
        const host = document.createElement('div');
        host.id = `event-fields-inline-${String(id).replace(/[^a-zA-Z0-9_-]/g,'')}`;
        panel.append(host);
        const editor = window.EntityFormFields.mount(host,{
            entityType:'event',
            entityId:id,
            registrationType:item.registration_type || '',
            eventLayout:true,
            strictEventValidation:true,
            onSaved:() => closeFieldEditor(true)
        });
        state.editor = editor;
        scrollEntryToTop(entry);
        try { await editor.refresh(); } catch (_) { /* Retain editor's actionable retry state and draft. */ }
        if (state.editorId !== String(id) || !panel.isConnected) return false;
        const actions = host.querySelector('.entity-fields-editor > .d-flex');
        if (actions && !actions.querySelector('[data-event-inline-close]')) {
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-sm btn-outline-secondary events-inline-close';
            close.dataset.eventInlineClose = 'true';
            close.textContent = 'Lukk';
            close.addEventListener('click',() => closeFieldEditor(false));
            actions.prepend(close);
        }
        host.querySelector('[data-common-field], [data-edit-field], [data-preset]')?.focus({preventScroll:true});
        scrollEntryToTop(entry);
        return true;
    }

    async function beforeNavigation() {
        return !state.editorId || closeFieldEditor(false);
    }

    function confirmEditorForAction() {
        if (!state.editorId) return true;
        if (!state.editor || !state.editor.confirmClose()) return false;
        closeFieldEditor(true);
        return true;
    }

    function toggleEventDetails(id) {
        const panel = document.getElementById(`events-detail-${CSS.escape(String(id))}`);
        const card = document.querySelector(`[data-event-card="${CSS.escape(String(id))}"]`);
        if (!panel || !card) return;
        panel.hidden = !panel.hidden;
        card.setAttribute('aria-expanded',String(!panel.hidden));
        card.querySelector('[data-event-details]')?.setAttribute('aria-expanded',String(!panel.hidden));
    }

    async function setVisibility(button) {
        button.disabled = true;
        try {
            const eventId = button.dataset.eventId;
            const active = button.dataset.active === '1' ? 0 : 1;
            let token = window.entityFormCsrfToken || '';
            if (!token && window.EntityFormFields?.load) {
                const formFields = await window.EntityFormFields.load('event', eventId, true);
                token = formFields.csrf_token || window.entityFormCsrfToken || '';
            }
            if (!token) throw new Error('Kunne ikke hente sikkerhetstoken. Last arrangementet på nytt.');
            const response = await fetch('/api/set_event_visibility.php',{
                method:'POST',
                credentials:'same-origin',
                headers:{'Content-Type':'application/json','X-CSRF-Token':token},
                body:JSON.stringify({event_id:eventId,is_active:active,csrf_token:token})
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke endre synlighet.');
            if (typeof window.toast?.quickSuccess === 'function') window.toast.quickSuccess('Arrangement',active ? 'Arrangementet er synlig.' : 'Arrangementet er skjult.');
            const item = state.events.find(event => String(event.id) === String(eventId));
            if (item) item.is_active = active;
            renderList();
            if (typeof window.loadEvents === 'function') window.loadEvents();
        } catch (error) {
            if (typeof window.toast?.quickError === 'function') window.toast.quickError('Arrangement',error.message || 'Kunne ikke endre synlighet.');
            button.disabled = false;
        }
    }

    function renderUpcomingCard(event) {
        const id = String(event.id);
        const panelId = `events-upcoming-detail-${id.replace(/[^a-zA-Z0-9_-]/g,'')}`;
        const capacity = asBool(event.counts_loaded)
            ? (event.max_attendees ? `${Number(event.current_attendees) || 0} av ${event.max_attendees} plasser` : `${Number(event.current_attendees) || 0} påmeldt`)
            : 'Kapasitet ikke lastet';
        return `<article class="events-upcoming-entry">
            <button type="button" class="events-upcoming-card" data-upcoming-select="${esc(id)}" aria-expanded="false" aria-controls="${esc(panelId)}">
                <span class="events-upcoming-date"><strong>${esc(displayDate(event))}</strong><small>${esc(event.venue_name || event.event_city || 'Sted ikke angitt')}</small></span>
                <span class="events-upcoming-title">${esc(event.title || 'Arrangement')}</span><i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="events-upcoming-detail" id="${esc(panelId)}" hidden>
                <p>${esc(event.description || 'Ingen beskrivelse er lagt til.')}</p>
                <div class="events-upcoming-facts">
                    <span><small>Påmeldinger</small><strong>${asBool(event.counts_loaded) ? `${Number(event.current_attendees) || 0} påmeldt · ${Number(event.pending_registrations) || 0} venter` : 'Tall ikke lastet'}</strong></span>
                    <span><small>Kapasitet</small><strong>${esc(capacity)}</strong></span>
                    <span><small>Sted</small><strong>${esc([event.venue_name,event.event_address,event.event_city].filter(Boolean).join(', ') || 'Ikke angitt')}</strong></span>
                </div>
            </div>
        </article>`;
    }

    function applyRegistrationFilter() {
        const select = state.modalControls?.querySelector('#eventsRegistrationsEventFilter');
        if (!select) return;
        const selected = select.value;
        document.querySelectorAll('#pending-registrations-list tr[data-registration-id]').forEach(row => {
            row.hidden = !!selected && row.dataset.eventId !== selected;
        });
        document.querySelectorAll('#all-registrations-container [data-event-id]').forEach(group => {
            const card = group.closest('.accordion-item') || group;
            card.hidden = !!selected && group.dataset.eventId !== selected;
        });
    }

    function attachRegistrationFilter() {
        const control = document.createElement('div');
        control.className = 'events-registration-filter';
        control.innerHTML = `<label for="eventsRegistrationsEventFilter">Filtrer på arrangement</label>
            <select id="eventsRegistrationsEventFilter" class="form-select form-select-sm">
                <option value="">Alle arrangementer</option>
                ${state.events.map(event => `<option value="${esc(event.id)}">${esc(event.title || 'Arrangement')} · ${esc(displayDate(event))}</option>`).join('')}
            </select>`;
        state.modalControls = control;
        const body = document.getElementById('eventsOverviewModalBody');
        body?.prepend(control);
        control.querySelector('select')?.addEventListener('change',applyRegistrationFilter);
        state.registrationObserver = new MutationObserver(applyRegistrationFilter);
        ['pending-registrations-list','all-registrations-container'].forEach(id => {
            const target = document.getElementById(id);
            if (target) state.registrationObserver.observe(target,{childList:true,subtree:true});
        });
        applyRegistrationFilter();
    }

    function openOverview(kind, shouldRefresh) {
        const modal = document.getElementById('eventsOverviewModal');
        const body = document.getElementById('eventsOverviewModalBody');
        if (!modal || !body) return;
        if (!confirmEditorForAction()) return;
        if (kind === 'upcoming') {
            if (state.modalKind) restoreOverview();
            state.modalKind = 'upcoming';
            const items = state.events.filter(isUpcoming).sort((a,b) => eventSortTime(a).localeCompare(eventSortTime(b)));
            const availability = state.failed ? '<p class="events-dashboard-error" role="status">Oppdatering feilet. Viser sist lastede arrangementer.</p>' : '';
            body.innerHTML = `${availability}<p class="events-overview-intro">${items.length ? `${items.length} kommende arrangement${items.length === 1 ? '' : 'er'} sortert etter starttid.` : 'Ingen aktive arrangementer har en fremtidig starttid.'}</p>
                <div class="events-upcoming-list">${items.map(renderUpcomingCard).join('') || '<div class="events-dashboard-empty"><i class="bi bi-calendar2-week"></i><strong>Ingen kommende arrangementer</strong><span>Opprett et arrangement eller se en annen visning.</span></div>'}</div>`;
            const title = document.getElementById('eventsOverviewModalTitle');
            if (title) title.textContent = 'Kommende arrangementer';
            bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }
        const ids = kind === 'requests' ? ['events-registrations'] : ['events-checkin'];
        const sections = ids.map(id => document.getElementById(id)).filter(Boolean);
        if (!sections.length) return;
        if (state.modalKind && state.modalKind !== kind) restoreOverview();
        if (!state.modalKind) {
            state.modalKind = kind;
            state.modalSections = sections.map(section => {
                const marker = document.createComment(`events ${kind} home`);
                section.parentNode.insertBefore(marker,section);
                return {section,marker};
            });
        }
        sections.forEach(section => { body.append(section);section.style.display = 'block'; });
        const title = document.getElementById('eventsOverviewModalTitle');
        if (title) title.textContent = kind === 'requests' ? 'Eventbestillinger' : 'Innsjekking';
        if (kind === 'requests' && !state.modalControls) attachRegistrationFilter();
        bootstrap.Modal.getOrCreateInstance(modal).show();
        if (shouldRefresh) {
            if (kind === 'requests') { window.loadPendingRegistrations?.();window.loadAllRegistrations?.(); }
            else window.loadCheckinEventSelector?.();
        }
    }

    function restoreOverview() {
        state.registrationObserver?.disconnect();
        state.registrationObserver = null;
        const selectedTab = document.querySelector('#subtabs-events .nav-link.active')?.hash.slice(1) || '';
        for (const {section,marker} of state.modalSections) {
            marker.parentNode?.insertBefore(section,marker);
            marker.remove();
            section.style.display = selectedTab === section.id ? 'block' : 'none';
        }
        state.modalSections = [];
        state.modalControls?.remove();
        state.modalControls = null;
        state.modalKind = null;
        const body = document.getElementById('eventsOverviewModalBody');
        if (body) body.replaceChildren();
    }

    document.addEventListener('events-dashboard-source',sourceUpdated);
    document.addEventListener('events-registration-decided', () => window.loadEvents?.());
    document.addEventListener('DOMContentLoaded',() => {
        const search = document.getElementById('eventsDashboardSearch');
        search?.addEventListener('input',async event => {
            const type = event.inputType || '';
            const deliberate = /^(delete|history)/.test(type) || ['insertFromPaste','insertFromDrop'].includes(type) ||
                (['insertText','insertCompositionText'].includes(type) && event.data !== null && event.data !== undefined);
            if (!deliberate && search.value !== '') { search.value = state.appliedSearch;return; }
            const query = search.value;
            if (await beforeNavigation()) { state.appliedSearch = query;renderList(); }
            else search.value = state.appliedSearch;
        });
        search?.addEventListener('focus',() => { search.value = state.appliedSearch; });
        search?.addEventListener('change',() => { search.value = state.appliedSearch; });
        document.getElementById('eventsDashboardStatus')?.addEventListener('change',async event => {
            const target = event.currentTarget;
            if (await beforeNavigation()) { state.appliedStatus = target.value;renderList(); }
            else target.value = state.appliedStatus;
        });
        root.addEventListener('click',event => {
            const overviewButton = event.target.closest('[data-open-overview]');
            if (overviewButton) {
                openOverview(overviewButton.dataset.openOverview,true);
                return;
            }
            if (event.target.closest('[data-event-create]')) {
                document.querySelector('#subtabs-events a[href="#events-create"]')?.click();
                return;
            }
            const details = event.target.closest('[data-event-details]');
            if (details) {
                toggleEventDetails(details.dataset.eventDetails);
                return;
            }
            const action = event.target.closest('[data-event-action]');
            if (action) {
                event.preventDefault();
                const id = action.dataset.eventId;
                if (action.dataset.eventAction === 'edit') {
                    if (!confirmEditorForAction()) return;
                    window.editEvent?.(id);
                }
                else if (action.dataset.eventAction === 'fields') openFieldEditor(id,action);
                else if (['visibility','delete'].includes(action.dataset.eventAction)) {
                    if (!confirmEditorForAction()) return;
                    if (action.dataset.eventAction === 'visibility') setVisibility(action);
                    else window.deleteEvent?.(id);
                }
                return;
            }
            if (event.target.closest('[data-events-retry]')) window.loadEvents?.();
            const card = event.target.closest('[data-event-card]');
            if (card && !event.target.closest('button,input,select,textarea,a,.events-event-details,.events-inline-fields,[role="menu"]')) {
                toggleEventDetails(card.dataset.eventCard);
            }
        });
        root.addEventListener('keydown',event => {
            const card = event.target.closest('[data-event-card]');
            if (!card || event.target !== card) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleEventDetails(card.dataset.eventCard);
            }
        });
        document.getElementById('eventsOverviewModalBody')?.addEventListener('click',event => {
            const selected = event.target.closest('[data-upcoming-select]');
            if (selected) {
                const detail = document.getElementById(selected.getAttribute('aria-controls'));
                if (detail) {
                    detail.hidden = !detail.hidden;
                    selected.setAttribute('aria-expanded',String(!detail.hidden));
                }
            }
        });
        document.querySelectorAll('#subtabs-events a').forEach(link => link.addEventListener('click',event => {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!confirmEditorForAction()) return;
            const targetId = link.hash.slice(1);
            if (state.modalKind) {
                restoreOverview();
                bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal'))?.hide();
            }
            document.querySelectorAll('#subtabs-events .nav-link').forEach(item => item.classList.toggle('active',item === link));
            window.localStorage.setItem('salesman_active_subtab_events',targetId);
            if (typeof window.showSubTabContent === 'function') window.showSubTabContent('events',targetId);
            else {
                root.querySelectorAll('.subtab-content').forEach(panel => { panel.style.display = panel.id === targetId ? 'block' : 'none'; });
                document.dispatchEvent(new CustomEvent('subtab-changed',{detail:{module:'events',subtab:targetId}}));
            }
        },true));
        document.querySelectorAll('.module-tabs .nav-link').forEach(link => link.addEventListener('click',event => {
            if (!state.editorId && !state.modalKind) return;
            if (!confirmEditorForAction()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            if (state.modalKind) {
                restoreOverview();
                bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal'))?.hide();
            }
        },true));
        const overview = document.getElementById('eventsOverviewModal');
        overview?.addEventListener('hidden.bs.modal',() => {
            if (!state.transitioningToDetails) restoreOverview();
        });
        const details = document.getElementById('viewEventDetailsModal');
        const rejection = document.getElementById('rejectionReasonModal');
        const beginChildTransition = (child,event) => {
            if (!state.modalKind || !overview?.classList.contains('show')) return;
            event.preventDefault();
            state.transitioningToDetails = true;
            state.childModalId = child.id;
            overview.addEventListener('hidden.bs.modal',function reopenChild() {
                overview.removeEventListener('hidden.bs.modal',reopenChild);
                if (state.childModalId === child.id) bootstrap.Modal.getOrCreateInstance(child).show();
            },{once:true});
            bootstrap.Modal.getOrCreateInstance(overview).hide();
        };
        const finishChildTransition = child => {
            if (state.childModalId !== child.id) return;
            state.transitioningToDetails = false;
            state.childModalId = null;
            if (state.modalKind) openOverview(state.modalKind,false);
        };
        rejection?.addEventListener('show.bs.modal',event => beginChildTransition(rejection,event));
        rejection?.addEventListener('hidden.bs.modal',() => finishChildTransition(rejection));
        details?.addEventListener('show.bs.modal',event => beginChildTransition(details,event));
        details?.addEventListener('hidden.bs.modal',() => finishChildTransition(details));
        window.addEventListener('beforeunload',event => {
            if (!state.editorId || !(state.editor?.isDirty() || state.editor?.isBusy())) return;
            event.preventDefault();
            event.returnValue = '';
        });
        root.addEventListener('click',event => {
            if (state.editorId && event.target.closest('.entity-fields-editor-event') &&
                !event.target.closest('[data-event-inline-close],[data-retry]')) state.fieldDirty = true;
        });
        renderList();
    });

    document.addEventListener('subtab-changed',event => {
        if (event.detail?.module !== 'events') return;
        if (state.modalKind && event.detail.subtab !== 'events-dashboard') {
            bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal'))?.hide();
        }
        if (event.detail.subtab === 'events-dashboard') {
            window.loadEvents?.();
        }
    });

    window.EventsDashboard = {
        renderList,
        openOverview,
        openFieldEditor,
        closeFieldEditor,
        beforeNavigation,
        setSource:(success,events,message) => sourceUpdated({detail:{success,events,message}})
    };
})();
