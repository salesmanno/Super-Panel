/* Module-local decisions. No decision implicitly saves an editable draft. */
window.RentalRequestWorkflow = (() => {
    const baselines = new Map();
    const savedValues = new Map();
    const rowFor = id => document.querySelector(`[data-rental-request-id="${CSS.escape(String(id))}"]`);
    const inputs = row => [...row.querySelectorAll('input, select')]
        .filter(el => !el.hasAttribute('data-norwegian-date-time-proxy'));
    const snapshot = row => JSON.stringify(inputs(row).map(el => [el.dataset.rentalRequestField || el.name || el.id || el.className,
        el.type === 'checkbox' ? el.checked : el.value]));
    const dirty = row => baselines.has(row.dataset.rentalRequestId) &&
        baselines.get(row.dataset.rentalRequestId) !== snapshot(row);
    const message = (title, text) => toast.error(null, title, text);
    function capture(row) {
        baselines.set(row.dataset.rentalRequestId, snapshot(row));
        savedValues.set(row.dataset.rentalRequestId, inputs(row).map(el => el.type==='checkbox' ? el.checked : el.value));
    }
    function discard(id) {
        const row = rowFor(id);
        if (!row || row.dataset.busy==='true' || !dirty(row)) return;
        if (!window.confirm('Forkaste ulagrede endringer i denne forespørselen? De lagrede opplysningene beholdes.')) return;
        const values=savedValues.get(String(id));
        inputs(row).forEach((el,index)=>{
            if (el.type==='checkbox') el.checked=values[index];
            else el.value=values[index];
            el.dispatchEvent(new Event('change',{bubbles:true}));
        });
        handleRentalRequestAdjustmentChange(row);
    }
    async function post(id, payload) {
        const ctx = rentalRequestContext(id);
        const response = await fetch('/api/update_rental_schedule.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id, csrf_token:ctx.csrfToken,
                expected_version:ctx.request.decision_version, ...payload})
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke lagre. Prøv igjen.');
        return result;
    }
    async function save(id) {
        const row = rowFor(id);
        if (!row || row.dataset.busy === 'true') return;
        let loading;
        try {
            const fields = getRentalRequestInlinePayload(id);
            // The API, not the client estimate, owns the saved amount.
            delete fields.rental_fee;
            setRentalRequestRowBusy(id, true);
            loading = toast.loading('Lagrer endringer…');
            const result = await post(id, fields);
            rentalRequestAcceptSaved(id, result);
            capture(row);
            const total = row.querySelector('.rental-request-total');
            if (total && Number.isFinite(Number(result.rental_fee))) {
                total.textContent = 'Totalt: ' + Number(result.rental_fee).toLocaleString('nb-NO',
                    {minimumFractionDigits:2,maximumFractionDigits:2}) + ' NOK';
            }
            toast.success(loading, 'Endringene er lagret', 'Forespørselen er fortsatt ventende. Ingen beslutnings-e-post er sendt.');
        } catch (error) {
            toast.error(loading, 'Kunne ikke lagre', error.message);
        } finally {
            setRentalRequestRowBusy(id, false);
        }
    }
    function review(id, status) {
        const row = rowFor(id);
        if (!row || row.dataset.busy === 'true' || document.getElementById('rental-decision-dialog')) return;
        if (dirty(row)) {
            message('Ulagrede endringer', 'Lagre endringene før du godkjenner eller avslår. Ingen beslutning er gjort.');
            row.querySelector('.rental-request-save')?.focus();
            return;
        }
        const ctx = rentalRequestContext(id);
        const request = ctx.request;
        if (!request?.decision_version) {
            message('Opplysninger mangler','Last inn forespørselen før du fortsetter.');
            return;
        }
        const dialog = document.createElement('dialog');
        dialog.id = 'rental-decision-dialog';
        dialog.className = 'border rounded p-3';
        dialog.style.width = 'min(92vw, 560px)';
        dialog.setAttribute('aria-labelledby','rental-decision-title');
        const title = document.createElement('h5');
        title.id = 'rental-decision-title';
        title.textContent = status === 'approved' ? 'Godkjenn forespørsel' : 'Avslå forespørsel';
        dialog.append(title);
        const details = document.createElement('dl');
        const date = value => /^\d{4}-\d{2}-\d{2}$/.test(value || '') ? value.split('-').reverse().join('.') : value || '–';
        for (const [label,value] of [
            ['Kunde',request.customer_name || request.display_customer_name || 'Navn ikke oppgitt'],
            ['Objekt',request.object_name],
            ['Fra',date(request.start_date)+' kl. 08:00'],
            ['Til',date(request.end_date)+' kl. '+String(request.end_time || '').slice(0,5)],
            ['Lagret leiebeløp',Number(request.rental_fee).toLocaleString('nb-NO',{minimumFractionDigits:2,maximumFractionDigits:2})+' NOK'],
            ['Beslutning',status === 'approved' ? 'Godkjent' : 'Avslått'],
        ]) {
            const term = document.createElement('dt'), def = document.createElement('dd');
            term.textContent = label; def.textContent = value || '–';
            details.append(term,def);
        }
        dialog.append(details);
        const email = document.createElement('p');
        email.textContent = ctx.emailEnabled
            ? 'Kundebeskjeden legges i e-postkø når beslutningen er lagret. Dette betyr ikke at e-posten allerede er sendt.'
            : 'Automatisk kundebeskjed er deaktivert. Ingen beslutnings-e-post legges i kø.';
        dialog.append(email);
        const errorText = document.createElement('p');
        errorText.className = 'text-danger';
        errorText.setAttribute('role','alert');
        dialog.append(errorText);
        const confirm = document.createElement('button'), cancel = document.createElement('button');
        confirm.type = cancel.type = 'button';
        confirm.className = 'btn '+(status === 'approved' ? 'btn-success' : 'btn-danger')+' me-2';
        confirm.textContent = status === 'approved' ? 'Bekreft godkjenning' : 'Bekreft avslag';
        cancel.className = 'btn btn-outline-secondary'; cancel.textContent = 'Tilbake';
        cancel.onclick = () => dialog.close();
        let submitting = false;
        dialog.addEventListener('cancel',event => { if (submitting) event.preventDefault(); });
        dialog.addEventListener('close',() => dialog.remove());
        confirm.onclick = async () => {
            if (submitting) return;
            // A programmatic edit while the summary is open must not be lost.
            if (dirty(row)) { errorText.textContent = 'Forespørselen har ulagrede endringer. Gå tilbake og lagre dem først.'; return; }
            submitting = true; confirm.disabled = cancel.disabled = true;
            setRentalRequestRowBusy(id,true);
            try {
                const result = await post(id,{status});
                baselines.delete(String(id));
                savedValues.delete(String(id));
                row.remove();
                dialog.close();
                if (!document.querySelector('[data-rental-request-id]')) {
                    document.getElementById('rentalRequestsContainer').textContent = 'Ingen ventende forespørsler';
                }
                toast.success(null,status === 'approved' ? 'Godkjent' : 'Avslått',
                    result.email_queued ? 'Beslutningen er lagret. Kundebeskjeden er lagt i e-postkø, ikke bekreftet sendt.'
                        : 'Beslutningen er lagret. Ingen kundebeskjed er lagt i kø.');
                if (status === 'approved') loadApprovedSchedule();
            } catch (error) {
                errorText.textContent = error.message;
            } finally {
                submitting = false; confirm.disabled = cancel.disabled = false;
                setRentalRequestRowBusy(id,false);
            }
        };
        dialog.append(confirm,cancel);
        document.body.append(dialog);
        dialog.showModal();
        cancel.focus();
    }
    function inspect(request) {
        if (document.getElementById('rental-decision-dialog')) return;
        const dialog=document.createElement('dialog');
        dialog.id='rental-decision-dialog';dialog.className='border rounded p-3';
        dialog.style.width='min(92vw, 560px)';
        const title=document.createElement('h5');title.textContent='Utleieforespørsel';
        title.id='rental-decision-title';dialog.setAttribute('aria-labelledby',title.id);
        dialog.append(title);
        const date=value=>/^\d{4}-\d{2}-\d{2}$/.test(value||'')?value.split('-').reverse().join('.'):value||'–';
        const statuses={pending:'Venter på behandling',approved:'Godkjent',rejected:'Avslått',cancelled:'Kansellert',completed:'Fullført'};
        const details=document.createElement('dl');
        for(const [label,value] of [
            ['Kunde',request.customer_name||request.display_customer_name||'Navn ikke oppgitt'],
            ['Objekt',request.object_name],
            ['Fra',date(request.start_date)+' kl. 08:00'],
            ['Til',date(request.end_date)+' kl. '+String(request.end_time||'').slice(0,5)],
            ['Lagret leiebeløp',Number(request.rental_fee).toLocaleString('nb-NO',{minimumFractionDigits:2,maximumFractionDigits:2})+' NOK'],
            ['Status',statuses[request.status]||request.status||'Ukjent'],
        ]) {
            const dt=document.createElement('dt'),dd=document.createElement('dd');
            dt.textContent=label;dd.textContent=value||'–';details.append(dt,dd);
        }
        dialog.append(details);
        const close=document.createElement('button');close.type='button';
        close.className='btn btn-outline-secondary';close.textContent='Lukk';
        close.onclick=()=>dialog.close();dialog.append(close);
        dialog.addEventListener('close',()=>dialog.remove());
        document.body.append(dialog);dialog.showModal();close.focus();
    }
    return {capture, save, review, inspect, discard, hasDrafts:() =>
        [...document.querySelectorAll('[data-rental-request-id]')].some(dirty)};
})();
