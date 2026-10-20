/* Existing tenant-scoped polling and acknowledgement, presented as one newest-first stack. */
window.TenantNotifications = (() => {
    let polls = [], csrf = '', busy = false, acting = false, timer, revision = 0;
    const actions = {
        new_booking:'Ny booking',payment_received:'Betaling mottatt',cancellation:'Kansellering',
        new_reservation:'Ny reservasjon',ticket_purchase:'Nytt billettkjøp',new_member:'Nytt medlem',
        rental_booking:'Ny utleieforespørsel',event_registration:'Ny påmelding'
    };
    function describe(poll) {
        const meta = poll.metadata || {};
        if (poll.module_source === 'rental' && meta.event_type === 'rental.request.created') {
            return (meta.customer_name || 'En kunde') + ' har sendt en utleieforespørsel'
                + (meta.object_name ? ' om ' + meta.object_name : '') + '.';
        }
        const isEventReservation = poll.module_source === 'events' && poll.action_type === 'new_reservation';
        return (actions[poll.action_type] || 'Ny oppdatering')
            + (meta.customer_name ? ' — ' + meta.customer_name : '')
            + (meta.event_title ? ' — ' + meta.event_title : '')
            + (!isEventReservation && Number(poll.items_count) > 1
                ? ' (' + Number(poll.items_count) + ' hendelser)' : '');
    }
    function host() { return document.getElementById('tenant-notifications'); }
    function node(tag,text,className) {
        const n = document.createElement(tag);
        if (text !== undefined) n.textContent = text;
        if (className) n.className = className;
        return n;
    }
    function renderMessage(message, retry = false, isError = false) {
        const container = host();
        if (!container) return;
        container.replaceChildren();
        container.dataset.count = String(polls.length);
        container.setAttribute('aria-busy', 'false');
        const count = document.getElementById('po-notice-count');
        if (count) count.textContent = polls.length ? String(polls.length) : '';
        const state = node('p',message,'po-empty');
        if (isError) state.setAttribute('role','alert');
        container.append(state);
        if (retry) {
            const button = node('button','Prøv igjen','btn btn-sm btn-outline-primary po-retry-notifications');
            button.type = 'button';
            button.onclick = () => check();
            container.append(button);
        }
    }
    function render() {
        const container = host();
        if (!container) return;
        container.replaceChildren();
        container.dataset.count = String(polls.length);
        container.setAttribute('aria-busy', 'false');
        const count = document.getElementById('po-notice-count');
        if (count) count.textContent = String(polls.length);
        const poll = polls[0];
        if (!poll) { renderMessage('Ingen nye varsler. Du er oppdatert.'); return; }
        const card = node('article',undefined,'po-notice-card');
        card.dataset.notificationId = poll.id;
        const open = node('button',undefined,'po-notice-open'); open.type = 'button';
        const module = window.PortalOverview.moduleNames[poll.module_source] || poll.module_source;
        const stamp = new Date(String(poll.created_at).replace(' ','T'));
        const date = Number.isNaN(stamp.getTime()) ? '' : stamp.toLocaleString('nb-NO',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false});
        open.append(node('small',module + (date ? ' · ' + date : '')),
            node('strong',describe(poll)),node('span','Åpne i ' + module + ' →' + (polls.length > 1 ? ' · ' + (polls.length - 1) + ' varsler til' : '')));
        const dismiss = node('button','×','po-notice-dismiss'); dismiss.type = 'button';
        dismiss.setAttribute('aria-label','Lukk varsel: ' + describe(poll));
        const error = node('p','','po-error'); error.setAttribute('role','alert');
        async function run(dismissed) {
            if (acting) return;
            acting = true; revision++; open.disabled = dismiss.disabled = true; error.textContent = '';
            try {
                if (!dismissed && await window.PortalOverview.open(poll.module_source,poll) === false) return;
                const response = await fetch('/api/set_polling_complete.php',{
                    method:'POST',headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({poll_id:poll.id,csrf_token:csrf,dismissed})
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke lukke varselet.');
                polls = polls.filter(item => item.id !== poll.id);
                render();
                host()?.querySelector('button')?.focus();
            } catch(e) { error.textContent = e.message; }
            finally { acting = false; open.disabled = dismiss.disabled = false; }
        }
        open.onclick = () => run(false);
        dismiss.onclick = () => run(true);
        card.append(open,dismiss,error); container.append(card);
    }
    async function check() {
        if (busy || acting || !host()) return;
        busy = true;
        const version = revision;
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), window.PORTAL_NOTICE_TIMEOUT_MS || 8000);
        try {
            const response = await fetch('/api/check_refresh_flag.php',{cache:'no-store',signal:controller.signal});
            let result;
            try { result = await response.json(); }
            catch { throw new Error('Varseltjenesten svarte med et ugyldig svar.'); }
            if (response.status === 401) throw new Error('Innloggingen er utløpt. Last siden på nytt.');
            if (response.status === 403) throw new Error(result.message || 'Du har ikke tilgang til disse varslene.');
            if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke hente varsler.');
            if (version !== revision || acting) return;
            csrf = result.csrf_token;
            // Server scopes the rows; also require a current, real module tab before displaying them.
            polls = (result.polls || (result.poll ? [result.poll] : []))
                .filter(poll => window.PortalOverview.tab(poll.module_source))
                .sort((a,b) => String(b.created_at).localeCompare(String(a.created_at)) || String(b.id).localeCompare(String(a.id)));
            if (!acting) render();
        } catch(e) {
            // Don't silently convert an unavailable API into an empty / "all caught up" state.
            if (!polls.length) renderMessage(e.name === 'AbortError'
                ? 'Det tar for lang tid å hente varsler. Kontroller forbindelsen og prøv igjen.'
                : (e.message || 'Kunne ikke hente varsler. Kontroller forbindelsen og prøv igjen.'), true, true);
            console.error('Varsler:',e.message);
        } finally { window.clearTimeout(timeout); busy = false; }
    }
    function start() { check(); if (!timer) timer = setInterval(check,10000); }
    return {start,check,describe,render};
})();
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',TenantNotifications.start);
else TenantNotifications.start();
