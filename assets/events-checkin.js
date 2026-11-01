/* Approved unified event check-in. All payment decisions are server verified. */
(() => {
    'use strict';
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const money = value => new Intl.NumberFormat('nb-NO', {style:'currency', currency:'NOK'}).format(Number(value || 0));
    const icon = name => `<i class="bi bi-${name}" aria-hidden="true"></i>`;
    let root, modal, data, eventId, token, revision = 0, busy = false, timer, polling = false;
    let active = 'waiting', query = '', target = null, extras = [], picker = false, groupQuery = '', reference = null, expiry = 0;
    let returnFocus, dialogFocus, requestPhase = '';
    const current = () => document.getElementById('ec-checkout');
    const byId = id => data?.people.find(person => String(person.id) === String(id));
    const paid = person => Number(person.ticket_price) <= 0 || !!person.has_valid_ledger;
    const selected = () => [target, ...extras.map(byId)].filter(Boolean);
    const total = () => selected().reduce((sum, person) => sum + Number(person.ticket_price), 0);
    const mutable = () => !!data?.event.editable;
    function message(text, error = false) {
        const box = root.querySelector('[data-ec-message]');
        box.textContent = text; box.className = `uc-message${error ? ' is-error' : ''}`;
        box.hidden = !text;
    }
    async function api(action, payload = {}) {
        const response = await fetch('/api/event_checkin.php', {
            method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({csrf_token:token, action, event_id:eventId, ...payload})
        });
        let result;
        try { result = await response.json(); } catch { throw Error('Serveren svarte ikke som forventet. Kontroller status før du prøver igjen.'); }
        if (!response.ok || !result.success) throw Error(result.message || 'Handlingen kunne ikke fullføres.');
        return result;
    }
    function ensureRoot() {
        if (root) return;
        root = document.createElement('div');
        root.id = 'eventCheckinSurface';
        root.className = 'modal fade ec-modal';
        root.tabIndex = -1;
        root.setAttribute('aria-labelledby','ec-title');
        root.innerHTML = `<div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content uc-shell">
            <div class="uc-main"><div class="uc-topbar"><button type="button" data-ec-close class="uc-back">${icon('arrow-left')} Tilbake til arrangementer</button>
            <button type="button" data-ec-reload class="uc-icon-button" aria-label="Oppdater deltakerlisten">${icon('arrow-clockwise')}</button></div>
            <div data-ec-message role="status" aria-live="polite" class="uc-message" hidden></div>
            <div data-ec-content></div></div><div data-ec-overlay></div></div></div>`;
        document.body.append(root);
        modal = bootstrap.Modal.getOrCreateInstance(root, {backdrop:'static', keyboard:false});
        root.addEventListener('hide.bs.modal',event=>{if(busy)event.preventDefault();});
        root.addEventListener('hidden.bs.modal', () => {
            clearInterval(timer); revision++; target = null;
            root.querySelector('[data-ec-overlay]').innerHTML = '';
            if (document.fullscreenElement === root) document.exitFullscreen().catch(()=>{});
            returnFocus?.focus();
        });
        root.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); event.stopImmediatePropagation(); current() ? closeCheckout() : modal.hide(); }
            if (event.key === 'Tab' && current()) {
                const fields = [...current().querySelectorAll('button:not(:disabled),input:not(:disabled),select:not(:disabled)')].filter(e=>e.getClientRects().length);
                if (!fields.length) return;
                if (event.shiftKey && document.activeElement === fields[0]) { event.preventDefault(); fields.at(-1).focus(); }
                else if (!event.shiftKey && document.activeElement === fields.at(-1)) { event.preventDefault(); fields[0].focus(); }
            }
            if (event.key === 'Enter' && event.target.matches('[data-ec-search]')) {
                event.preventDefault();
                const person = data?.people.find(p=>String(p.barcode) === event.target.value.trim() && p.barcode);
                if (person) checkin(person);
            }
        });
        root.addEventListener('input', event => {
            if (event.target.matches('[data-ec-search]')) { query = event.target.value; renderRows(); }
            if (event.target.matches('[data-ec-group-search]')) { groupQuery = event.target.value; renderCandidates(); }
        });
        root.addEventListener('click', async event => {
            const button = event.target.closest('button'); if (!button || button.disabled) return;
            try {
                if (button.matches('[data-ec-close]')) modal.hide();
                if (button.matches('[data-ec-reload]')) await reload();
                if (button.matches('[data-ec-tab]')) { active=button.dataset.ecTab; query=''; render(); }
                if (button.matches('[data-ec-focus]')) {
                    if (document.fullscreenElement) await document.exitFullscreen();
                    else if (root.requestFullscreen) await root.requestFullscreen();
                    else root.classList.toggle('ec-focus');
                }
                if (button.matches('[data-ec-checkin]')) await checkin(byId(button.dataset.ecCheckin));
                if (button.matches('[data-ec-add]')) {
                    const form=root.querySelector('[data-ec-manual]'); form.hidden=!form.hidden;
                    if (!form.hidden) form.querySelector('input').focus();
                }
                if (button.matches('[data-ec-checkout-close]')) closeCheckout();
                if (button.matches('[data-ec-more]')) { picker=true; renderCheckout(); current().querySelector('[data-ec-group-search]').focus(); }
                if (button.matches('[data-ec-extra]')) {
                    const id=button.dataset.ecExtra;
                    extras=extras.includes(id)?extras.filter(value=>value!==id):extras.length<4?[...extras,id]:extras;
                    renderCheckout(); current().querySelector('[data-ec-group-search]')?.focus();
                }
                if (button.matches('[data-ec-cash]')) await commit('cash',true);
                if (button.matches('[data-ec-previous]')) await commit('previous',true);
                if (button.matches('[data-ec-vipps]')) await startVipps();
                if (button.matches('[data-ec-payment-status]')) await poll(false);
                if (button.matches('[data-ec-cancel-payment]')) await poll(true);
            } catch (error) { checkoutMessage(error.message); }
        });
        root.addEventListener('submit', async event => {
            if (!event.target.matches('[data-ec-manual]')) return;
            event.preventDefault();
            if (busy || !event.target.reportValidity()) return;
            setBusy(true);
            try {
                const fields=Object.fromEntries(new FormData(event.target));
                const response=await fetch('/api/create_manual_event_participant.php', {
                    method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({...fields,event_id:eventId,checked_in:false,csrf_token:token})
                });
                const result=await response.json();
                if (!response.ok || !result.success) throw Error(result.message||'Deltakeren kunne ikke legges til.');
                active='waiting'; query=''; await reload(); message(mutable()?'Deltakeren er lagt til og kan nå sjekkes inn.':'Deltakeren er lagt til. Innsjekking åpner på arrangementsdatoen.');
            } catch(error) { message(error.message,true); } finally { setBusy(false); }
        });
    }
    function setBusy(value) {
        busy=value;
        root?.querySelectorAll('[data-ec-payment-action]').forEach(button=>{
            button.disabled=value || button.dataset.unavailable === '1';
        });
    }
    async function reload() {
        const version=revision;
        const result=await api('load');
        if (version!==revision) return;
        data=result; render();
    }
    function render() {
        const event=data.event;
        const count=data.people.filter(person=>Number(person.checked_in)===1).length;
        const all=data.people.length;
        const date=event.start_date?new Intl.DateTimeFormat('nb-NO',{dateStyle:'long'}).format(new Date(String(event.start_date).slice(0,10)+'T12:00:00')):'Dato mangler';
        root.querySelector('[data-ec-content]').innerHTML = `
            <header class="uc-event-header"><span class="uc-event-mark">${icon('calendar-event')}</span><div class="uc-heading-copy">
            <div class="uc-heading-kicker">ARRANGEMENT</div><h1 id="ec-title">${esc(event.title)}</h1>
            <div class="uc-header-meta"><span>${icon('calendar3')} ${esc(date)}</span><span>${icon('clock')} ${esc(String(event.start_time||'').slice(0,5))}${event.end_time?'–'+esc(String(event.end_time).slice(0,5)):''}</span>${event.venue_name?`<span>${icon('geo-alt')} ${esc(event.venue_name)}</span>`:''}</div></div>
            <button type="button" data-ec-focus class="uc-focus-button" aria-label="Veksle fullskjerm">${icon('arrows-fullscreen')}<span>Fokusmodus</span></button></header>
            ${!mutable()?`<div class="uc-readonly-notice">${icon('shield-check')} Du kan legge til deltakere nå. Innsjekking åpner på arrangementsdatoen.</div>`:''}
            <section class="uc-overview" aria-label="Oppmøte"><div class="uc-progress-block"><div class="uc-progress-top"><span>Oppmøte</span><strong>${count}<small> / ${all}</small></strong></div>
            <div class="uc-progress-track" role="progressbar" aria-label="Innsjekket" aria-valuemin="0" aria-valuemax="${Math.max(all,1)}" aria-valuenow="${count}"><span style="width:${all?count/all*100:0}%"></span></div>
            <small>${all?Math.round(count/all*100):0}% innsjekket · ${all-count} venter</small></div>
            <div class="uc-overview-stat">${icon('check-circle')}<span><strong>${count}</strong><small>Innsjekket</small></span></div>
            <div class="uc-overview-stat">${icon('people')}<span><strong>${all-count}</strong><small>Gjenstår</small></span></div></section>
            <section class="uc-roster"><div class="uc-roster-heading"><div><div class="uc-heading-kicker">DELTAKEROVERSIKT</div><h2>Deltakere <small>${all}</small></h2></div>
            <button type="button" class="uc-add-button" data-ec-add>${icon('plus-lg')} Legg til deltaker</button></div>
            <form data-ec-manual class="uc-manual" hidden><strong>Ny deltaker</strong><div class="uc-manual-grid">
            <label>Fornavn<input name="attendee_first_name" required maxlength="255" autocomplete="off"></label>
            <label>Etternavn<input name="attendee_last_name" required maxlength="255" autocomplete="off"></label>
            <label>E-post <small>valgfritt</small><input name="attendee_email" type="email" autocomplete="off"></label>
            <label>Telefon <small>valgfritt</small><input name="attendee_phone" type="tel" autocomplete="off"></label></div>
            <div class="uc-manual-footer"><span>Ordinær billett: ${money(event.ticket_price)}. Betaling bekreftes ved innsjekking.</span><button type="submit" class="uc-primary-button">Lagre deltaker</button></div></form>
            <div class="uc-roster-tabs" role="tablist" aria-label="Oppmøtestatus">${[['waiting','Ikke innsjekket',all-count],['checked','Innsjekket',count]].map(([id,label,n])=>`<button type="button" role="tab" data-ec-tab="${id}" aria-selected="${active===id}" aria-controls="ec-roster-panel" class="uc-roster-tab ${active===id?'is-active':''}">${label} <small>${n}</small></button>`).join('')}</div>
            <label class="uc-search">${icon('search')}<input type="search" data-ec-search autocomplete="off" autocorrect="off" spellcheck="false" aria-label="Søk blant deltakere" placeholder="Søk navn, e-post, telefon eller strekkode" value="${esc(query)}"></label>
            <div class="uc-list-meta"><span data-ec-count></span><span>${icon('qr-code-scan')} Søk eller skann kode</span></div>
            <div id="ec-roster-panel" role="tabpanel"><div class="uc-attendee-list" data-ec-rows role="list"></div></div></section>`;
        renderRows();
    }
    function renderRows() {
        const q=query.trim().toLocaleLowerCase('nb-NO');
        const rows=data.people.filter(person=>(Number(person.checked_in)===1)===(active==='checked'))
            .filter(person=>[person.customer_name,person.customer_email,person.customer_phone,person.barcode].join(' ').toLocaleLowerCase('nb-NO').includes(q))
            .sort((a,b)=>a.customer_name.localeCompare(b.customer_name,'nb'));
        root.querySelector('[data-ec-count]').textContent=`${rows.length} i denne listen`;
        root.querySelector('[data-ec-rows]').innerHTML=rows.length?rows.map(person=>`
            <article class="uc-attendee" role="listitem"><span class="uc-avatar">${esc((person.first_name||'?')[0]+(person.last_name||'')[0])}</span>
            <div class="uc-person"><strong>${esc(person.customer_name)}</strong><small>${esc(person.customer_email||person.customer_phone||'Ingen kontaktopplysninger')}</small>
            <span class="uc-payment-status ${paid(person)?'is-paid':'is-unpaid'}">${Number(person.ticket_price)<=0?'Gratis':paid(person)?'Betalt':'Ubetalt'}${person.pending_reference?' · Venter på Vipps':''}</span></div>
            <div class="uc-attendee-action">${Number(person.checked_in)===1?`<span class="uc-checked-status">${icon('check-circle')} Innsjekket<small>${esc(String(person.check_in_time||'').slice(11,16))}</small></span>`:
            `<button type="button" data-ec-checkin="${esc(person.id)}" class="uc-checkin-button" ${mutable()?'':'disabled'}>${icon('check2')} Sjekk inn</button>`}</div>
            <span class="uc-price">${money(person.ticket_price)}</span></article>`).join(''):
            `<div class="uc-empty">${icon('people')}<strong>${q?'Ingen treff':'Ingen deltakere i denne listen'}</strong><span>${q?'Prøv et annet søk.':'Deltakerne vises her når de er registrert eller sjekket inn.'}</span></div>`;
    }
    async function checkin(person) {
        if (!person || busy || !mutable()) return;
        if (Number(person.checked_in)===1) { message(`${person.customer_name} er allerede sjekket inn.`); return; }
        if (paid(person) && !person.pending_reference) {
            setBusy(true);
            try {
                await api('commit',{participant_ids:[person.id],payer_id:person.id,method:'',confirmed:false});
                active='checked'; query=''; await reload(); message(`${person.customer_name} er sjekket inn.`);
            } finally { setBusy(false); }
            return;
        }
        const pending=person.pending_payment;
        dialogFocus=document.activeElement; target=pending?byId(pending.payer_id)||person:person;
        extras=pending?(pending.participant_ids||[]).filter(id=>id!==target.id):[];
        picker=false; groupQuery='';
        reference=person.pending_reference||null; expiry=0; requestPhase=reference?'pending':'';
        if(pending) expiry=pending.expires_at||0;
        renderCheckout(); current().querySelector('button')?.focus();
        if(reference) { await poll(false); beginPoll(); }
    }
    function checkoutMessage(text) {
        if (current()) { const el=current().querySelector('[data-ec-checkout-message]'); el.textContent=text; el.hidden=!text; }
        else message(text,true);
    }
    function closeCheckout() {
        if (busy) return;
        clearInterval(timer); target=null;
        root.querySelector('[data-ec-overlay]').innerHTML='';
        dialogFocus?.focus();
    }
    function candidates() {
        const q=groupQuery.trim().toLocaleLowerCase('nb-NO');
        return data.people.filter(p=>p.id!==target.id && Number(p.checked_in)!==1 && !paid(p)
            && p.payment_status!=='paid' && !p.pending_reference)
            .filter(p=>[p.customer_name,p.customer_phone].join(' ').toLocaleLowerCase('nb-NO').includes(q));
    }
    function renderCandidates() {
        const el=current()?.querySelector('[data-ec-candidates]'); if(!el) return;
        el.innerHTML=candidates().map(p=>`<button type="button" data-ec-extra="${esc(p.id)}" aria-pressed="${extras.includes(p.id)}" ${(extras.length>=4&&!extras.includes(p.id))?'disabled':''}>
            <span>${icon(extras.includes(p.id)?'check2':'plus')} ${esc(p.customer_name)}</span><small>${money(p.ticket_price)}</small></button>`).join('')||'<div class="uc-group-empty">Ingen ubetalte deltakere samsvarer med søket.</div>';
    }
    function renderCheckout() {
        if (!target) return;
        const previous=target.payment_status==='paid' && !target.has_valid_ledger && !reference;
        const pending=!!reference;
        root.querySelector('[data-ec-overlay]').innerHTML=`<div class="uc-modal-backdrop"><section id="ec-checkout" class="uc-dialog" role="dialog" aria-modal="true" aria-labelledby="ec-checkout-title">
            <div class="uc-dialog-head"><span class="uc-dialog-icon">${icon('credit-card')}</span><button type="button" class="uc-icon-button" data-ec-checkout-close aria-label="Lukk betalingsdialog">${icon('x-lg')}</button></div>
            <div class="uc-dialog-content"><div class="uc-heading-kicker">INNSJEKKING</div><h2 id="ec-checkout-title">${previous?'Bekreft tidligere betaling':'Ubetalt — velg betalingsmåte'}</h2>
            <p><strong>${esc(target.customer_name)}</strong> ${previous?'står som betalt. Bekreft den registrerte betalingsmåten; det sendes ingen ny belastning.':'sjekkes inn når betalingen er bekreftet.'}</p>
            <div class="uc-payment-amount"><span>${previous?'Tidligere betalt':'Betalingsbeløp'}${selected().length>1?` · ${selected().length} personer`:''}</span><strong>${money(total())}</strong></div>
            ${!previous&&!pending?`<div class="uc-group"><div class="uc-group-chips">${selected().map((p,i)=>`<span>${esc(p.customer_name)}${i===0?' · betaler':` <button type="button" data-ec-extra="${esc(p.id)}" aria-label="Fjern ${esc(p.customer_name)}">${icon('x')}</button>`}</span>`).join('')}</div>
            ${picker?`<div class="uc-group-picker"><label class="uc-group-search">${icon('search')}<input type="search" data-ec-group-search autocomplete="off" spellcheck="false" aria-label="Søk blant ubetalte deltakere" placeholder="Søk blant ubetalte deltakere" value="${esc(groupQuery)}"><small>${selected().length}/5</small></label><div class="uc-group-results" data-ec-candidates role="group" aria-label="Ubetalte deltakere"></div></div>`:
            `<button type="button" class="uc-group-toggle" data-ec-more>${icon('person-plus')} Legg til flere til innsjekk <small>maks 5</small></button>`}</div>`:''}
            <div class="uc-checkout-error" data-ec-checkout-message role="status" aria-live="polite" hidden></div>
            ${previous?`<div class="uc-simulation-note">Ingen ny belastning. Registrert betalingsmåte: <strong>${esc(target.payment_method||'mangler')}</strong>.</div><button type="button" class="uc-primary-button" data-ec-previous data-ec-payment-action data-unavailable="${target.payment_method?'0':'1'}" ${target.payment_method?'':'disabled'}>Bekreft tidligere betaling og sjekk inn</button>`:
            pending?`<div class="uc-vipps-request"><strong>${requestPhase==='unknown'?'Betalingsstatus er uavklart':'Venter på Vipps-bekreftelse'}</strong><span>Betaler: ${esc(target.customer_name)}</span><span>${esc(target.customer_phone||'')}</span><small data-ec-countdown></small></div>
            <p>Du kan lukke vinduet og åpne deltakeren igjen. Ingen sjekkes inn før Vipps har bekreftet betalingen.</p>
            <div class="uc-dialog-actions"><button type="button" class="uc-secondary-button" data-ec-cancel-payment data-ec-payment-action>Avbryt forespørsel</button><button type="button" class="uc-primary-button" data-ec-payment-status data-ec-payment-action>Kontroller status</button></div>`:
            `<div class="uc-payment-actions"><button type="button" class="uc-cash-button" data-ec-cash data-ec-payment-action data-unavailable="${data.methods.cash?'0':'1'}" ${data.methods.cash?'':'disabled'}>${icon('cash-stack')}<span><strong>Kontant mottatt</strong><small>Bekreft kontant og sjekk inn</small></span></button>
            <button type="button" class="uc-vipps-button" data-ec-vipps data-ec-payment-action data-unavailable="${data.methods.vipps&&target.customer_phone?'0':'1'}" ${data.methods.vipps&&target.customer_phone?'':'disabled'} aria-label="Send Vipps!"><img src="/assets/vipps-payment-logo.webp" alt="Vipps"><span><small>Be om betaling på telefon</small></span></button></div>
            ${!data.methods.cash&&!data.methods.vipps?'<p class="uc-checkout-error">Ingen betalingsmåte er aktivert for arrangementet.</p>':''}
            <p class="uc-helper">${!target.customer_phone?'Vipps krever et registrert mobilnummer på betaleren.':data.methods.vipps?`Vipps sendes til ${esc(target.customer_phone)}. Innsjekking venter på bekreftet betaling.`:'Vipps er ikke aktivert for dette arrangementet.'}</p>`}
            </div></section></div>`;
        renderCandidates(); updateCountdown(); setBusy(busy);
    }
    async function commit(method,confirmed) {
        if (busy || !target) return;
        setBusy(true);
        try {
            await api('commit',{participant_ids:selected().map(p=>p.id),payer_id:target.id,method,confirmed});
            const name=target.customer_name, count=selected().length;
            setBusy(false); closeCheckout(); active='checked'; query=''; await reload();
            message(count>1?`${count} deltakere er sjekket inn. ${name} er registrert som betaler.`:`${name} er sjekket inn.`);
        } finally { setBusy(false); }
    }
    async function startVipps() {
        if (busy || !target || reference) return;
        const scope=revision, person=target;
        setBusy(true);
        try {
            const result=await api('start_vipps',{participant_ids:selected().map(p=>p.id),payer_id:target.id});
            if(scope!==revision||target!==person)return;
            reference=result.reference; expiry=result.expires_at||0; requestPhase=result.state==='UNKNOWN'?'unknown':'pending';
            await applyPayment(result); if(target) { renderCheckout(); beginPoll(); }
        } catch(error) {
            if(scope!==revision||target!==person)return;
            // Refresh persisted attempts before allowing another charge after a timeout.
            await reload();
            const refreshed=byId(person.id);
            if(scope!==revision||target!==person)return;
            if(refreshed?.pending_reference) {
                target=byId(refreshed.pending_payment?.payer_id)||refreshed;
                extras=(refreshed.pending_payment?.participant_ids||[]).filter(id=>id!==target.id);
                reference=refreshed.pending_reference; expiry=refreshed.pending_payment?.expires_at||0;
                requestPhase='unknown'; renderCheckout(); beginPoll();
            }
            throw error;
        } finally { setBusy(false); }
    }
    function beginPoll() {
        clearInterval(timer);
        let ticks=0;
        timer=setInterval(()=>{ updateCountdown(); if(++ticks%3===0) poll(false).catch(()=>{}); },1000);
    }
    function updateCountdown() {
        const el=current()?.querySelector('[data-ec-countdown]');
        if(el) el.textContent=expiry?Math.max(0,Math.ceil(expiry-Date.now()/1000))+' sekunder igjen':'Kontrollerer betalingsstatus …';
    }
    async function poll(cancel) {
        if (!reference || polling || busy) return;
        const scope=revision, expectedReference=reference, person=target;
        polling=true;
        if(cancel) setBusy(true);
        try {
            const result=await api(cancel?'cancel_vipps':'status_vipps',{reference});
            if(scope!==revision||expectedReference!==reference||target!==person)return;
            expiry=result.expires_at||expiry;
            await applyPayment(result);
        } catch(error) {
            if(scope===revision&&expectedReference===reference&&target===person) {
                requestPhase='unknown'; checkoutMessage(error.message);
            }
        } finally { polling=false; if(cancel) setBusy(false); }
    }
    async function applyPayment(result) {
        if (result.state==='COMPLETED') {
            setBusy(false); closeCheckout(); active='checked'; query=''; await reload(); message('Vipps-betalingen er bekreftet. Deltakerne er sjekket inn.');
        } else if (['ABORTED','EXPIRED','TERMINATED','CANCELLED','REJECTED'].includes(result.state)) {
            clearInterval(timer); reference=null; requestPhase=''; await reload(); renderCheckout();
            checkoutMessage('Betalingen ble ikke gjennomført. Ingen innsjekking ble registrert. Du kan velge betalingsmåte på nytt.');
        }
    }
    window.EventCheckinDashboard = {
        async open(id) {
            ensureRoot(); revision++; returnFocus=document.activeElement; eventId=String(id);
            data=null; target=null; active='waiting'; query=''; reference=null; busy=false; clearInterval(timer);
            root.querySelector('[data-ec-overlay]').innerHTML='';
            root.querySelector('[data-ec-content]').innerHTML='<div class="uc-empty" role="status">Henter deltakere …</div>';
            message(''); modal.show();
            const version=revision;
            try {
                const response=await fetch(`/api/get_entity_form_fields.php?entity_type=event&entity_id=${encodeURIComponent(eventId)}&csrf_only=1`,{credentials:'same-origin'});
                const result=await response.json();
                if(version!==revision) return;
                if(!response.ok || !result.success || !result.csrf_token) throw Error(result.message||'Tilgangen til arrangementet kunne ikke kontrolleres.');
                token=result.csrf_token; await reload();
            } catch(error) {
                message(error.message,true);
                root.querySelector('[data-ec-content]').innerHTML='<div class="uc-empty">Oversikten kunne ikke lastes. Bruk oppdateringsknappen for å prøve igjen.</div>';
            }
        }
    };
})();
