(function () {
    const normalize = value => ({kontant:'cash',card:'stripe',kort:'stripe',invoice:'faktura',bank_transfer:'bank'})[String(value).toLowerCase()] || String(value).toLowerCase();
    const text = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    window.ActivePaymentMethods = {
        normalize, text,
        label(method) {
            return (method.method_name || method.method_type) + (method.is_demo ? ' (Testmodus)' : '') +
                (method.is_configured === false ? ' (Mangler konfigurasjon)' : '');
        },
        async load(eventId = '') {
            const response = await fetch('/api/get_payment_methods.php' + (eventId ? '?event_id='+encodeURIComponent(eventId) : ''), {cache:'no-store'});
            const result = await response.json();
            if (!response.ok || !result.success || !Array.isArray(result.payment_methods)) throw new Error('Kunne ikke laste aktiverte betalingsmetoder. Prøv igjen.');
            return result.payment_methods;
        },
        async choose() {
            const methods=await this.load();
            if (!methods.length) throw new Error('Ingen betalingsmetoder er aktivert i Økonomi.');
            const answer=window.prompt('Velg aktivert betalingsmetode:\n'+methods.map((m,i)=>(i+1)+': '+this.label(m)).join('\n'));
            if (answer === null) return null;
            const index=Number(answer)-1;
            if (!Number.isInteger(index) || !methods[index]) throw new Error('Velg en av de aktiverte betalingsmetodene.');
            return methods[index].method_type;
        },
        async eventOptions(containerId, selectId, selected = null, defaultMethod = null) {
            const container = document.getElementById(containerId), select = document.getElementById(selectId);
            if (!container || !select) return;
            const revision = String(Number(container.dataset.loadRevision || 0)+1);
            container.dataset.loadRevision=revision; container.dataset.paymentState='loading';
            container.textContent='Laster aktiverte betalingsmetoder…';
            select.replaceChildren(new Option('Laster…','')); select.disabled=true;
            try {
                let methods = await this.load();
                if (container.dataset.loadRevision !== revision) return;
                const form=container.closest('form');
                if (EventSettings.registrationType(form)==='binding') methods=methods.filter(m=>m.method_type!=='cash');
                const previous = (selected || []).map(normalize);
                const all = methods.map(m=>({...m, checked: selected === null || previous.includes(m.method_type)}));
                for (const type of previous) {
                    if (!methods.some(m=>m.method_type===type)) all.push({method_type:type,method_name:type+' (Deaktivert)',checked:true,unavailable:true});
                }
                container.replaceChildren();
                const hint=document.createElement('p');
                hint.className='small text-muted';
                hint.textContent=methods.length ? 'Betalingsmetoder aktivert i Økonomi. Velg metodene for dette arrangementet.' : 'Ingen tilgjengelige betalingsmetoder er aktivert i Økonomi.';
                container.append(hint);
                for (const method of all) {
                    const row=document.createElement('div');row.className='form-check mb-2';
                    const input=document.createElement('input');
                    input.type='checkbox';input.className='form-check-input payment-method-checkbox';
                    input.dataset.methodType=method.method_type;input.value=method.method_type;
                    input.id=containerId+'-'+method.method_type;input.checked=method.checked;input.disabled=!!method.unavailable;
                    input.dataset.unavailable=method.unavailable ? '1' : '0';
                    const label=document.createElement('label');label.className='form-check-label';label.htmlFor=input.id;
                    label.textContent=this.label(method);
                    row.append(input,label);
                    if (method.unavailable) {
                        const remove=document.createElement('button');remove.type='button';remove.className='btn btn-sm btn-link';
                        remove.textContent='Fjern fra oppsettet';
                        remove.onclick=()=>{input.checked=false;remove.disabled=true;update();};
                        row.append(remove);
                    }
                    container.append(row);
                    input.addEventListener('change',()=>update());
                }
                const update=()=>{
                    const wanted=select.value || normalize(defaultMethod || '');
                    select.replaceChildren(new Option('Velg standardmetode…',''));
                    for (const method of all) {
                        if (!document.getElementById(containerId+'-'+method.method_type).checked) continue;
                        const option=new Option(this.label(method),method.method_type);
                        option.disabled=!!method.unavailable;select.append(option);
                    }
                    if ([...select.options].some(o=>o.value===wanted)) select.value=wanted;
                    select.disabled=false;
                };
                update();
                container.dataset.paymentState='ready';
                EventSettings.syncPayments(form);
            } catch (error) {
                if (container.dataset.loadRevision !== revision) return;
                container.dataset.paymentState='error';
                container.textContent=error.message;
                select.replaceChildren(new Option('Kunne ikke laste betalingsmetoder',''));
                const retry=document.createElement('button');retry.type='button';retry.className='btn btn-sm btn-link';
                retry.textContent='Prøv igjen';retry.onclick=()=>this.eventOptions(containerId,selectId,selected,defaultMethod);
                container.append(retry);
            }
        }
    };
})();
