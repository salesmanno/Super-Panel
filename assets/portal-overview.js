/* Production counterpart of the approved portal mockup; no demo identities or grants. */
window.PortalOverview = (() => {
    const el = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    const moduleNames = {rental:'Rental',events:'Events',sales:'Sales',membership:'Medlemskap',economy:'Økonomi',reservation:'Reservasjoner',webmail:'E-post'};
    function tab(module) {
        return [...document.querySelectorAll('.module-tabs [data-bs-target]')]
            .find(button => button.dataset.bsTarget === '#content-' + module);
    }
    async function open(module, poll = {}) {
        const button = tab(module);
        if (!button) throw new Error('Du har ikke tilgang til denne modulen. Last siden på nytt hvis tilgangen er endret.');
        if (module === 'rental' && poll.metadata?.resource_type === 'rental_schedule'
            && poll.metadata?.resource_id && typeof window.openRentalRequestNotification === 'function') {
            return await window.openRentalRequestNotification(poll.metadata.resource_id);
        }
        bootstrap.Tab.getOrCreateInstance(button).show();
        if (module === 'rental') {
            const target = document.querySelector('#subtabs-rental [href="#rental-objects"]');
            target?.click();
            if (window.RentalDashboard) await window.RentalDashboard.openOverview('upcoming');
        } else if (module === 'events') {
            const isRegistration = ['event_registration','ticket_purchase'].includes(poll.action_type);
            document.querySelector(`#subtabs-events [href="#${isRegistration ? 'events-registrations' : 'events-dashboard'}"]`)?.click();
        }
        return true;
    }
    function date(value, withTime = false) {
        const d = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return 'Ukjent tidspunkt';
        return d.toLocaleString('nb-NO', withTime
            ? {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:false}
            : {day:'numeric',month:'short'});
    }
    async function load() {
        const agenda = document.getElementById('po-agenda');
        if (!agenda) return;
        try {
            const response = await fetch('/api/portal_overview.php', {cache:'no-store'});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Kunne ikke hente oversikten.');
            agenda.replaceChildren();
            for (const item of data.agenda) {
                const button = el('button', undefined, 'po-agenda-item');
                button.type = 'button';
                const time = el('time', date(item.date));
                time.dateTime = item.date;
                const body = el('span');
                body.append(el('strong',item.title), el('small', (moduleNames[item.module] || item.module)
                    + (item.time ? ' · ' + item.time.slice(0,5) : '')));
                button.append(time, body, el('span','›'));
                button.onclick = async () => {
                    button.disabled = true;
                    try { await open(item.module); }
                    catch(e) { agenda.append(el('p',e.message,'text-danger small')); }
                    finally { button.disabled = false; }
                };
                agenda.append(button);
            }
            if (!data.agenda.length && !data.errors.length) agenda.append(el('p','Ingen kommende aktiviteter i modulene dine.','po-empty'));
            for (const error of data.errors) agenda.append(el('p',error,'text-danger small'));
            const activity = document.getElementById('po-activity');
            if (activity) {
                while (activity.children.length > 1) activity.lastElementChild.remove();
                const actions = {create:'opprettet',update:'oppdaterte',delete:'slettet',approve:'godkjente',reject:'avslo',send:'sendte'};
                for (const row of data.activity) {
                    const link = el('a');
                    link.href = '/pages/company_audit.php?id=' + encodeURIComponent(row.id);
                    link.append(el('small',date(row.performed_at,true)),
                        document.createTextNode(`${row.actor_name} ${actions[row.action] || row.action} ${row.resource_name || ''}`));
                    activity.append(link);
                }
                if (!data.activity.length) activity.append(el('span','Ingen registrerte endringer.'));
            }
        } catch(e) {
            agenda.replaceChildren(el('p',e.message,'po-empty'));
            const activity = document.getElementById('po-activity');
            if (activity) activity.replaceChildren(el('span','Kunne ikke hente siste aktivitet.'));
        }
    }

    const modal = document.getElementById('company-users-modal');
    const list = document.getElementById('company-users-list');
    const feedback = document.getElementById('company-users-feedback');
    let usersData, loading = false;
    async function request(body) {
        const response = await fetch('/api/company_users.php', body ? {
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({...body,csrf_token:usersData.csrf_token})
        } : {cache:'no-store'});
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Kunne ikke laste firmabrukere.');
        return data;
    }
    async function change(card, payload) {
        if (card.dataset.busy === 'true') return;
        card.dataset.busy = 'true';
        card.querySelectorAll('button,input,select').forEach(node => node.disabled = true);
        feedback.textContent = '';
        const snapshot = usersData;
        try {
            await request(payload);
            if (usersData !== snapshot) return;
            // Mutate local display only after server confirmation; retain the other users' editors.
            const user = usersData.users.find(user => user.id === payload.user_id);
            if (payload.action === 'grant') user.modules = [...new Set([...user.modules,payload.module_id])];
            if (payload.action === 'revoke') user.modules = user.modules.filter(id => id !== payload.module_id);
            card.replaceWith(renderUser(user));
            feedback.className = 'text-success small mb-2';
            feedback.textContent = payload.action === 'password'
                ? 'Nytt passord er lagret. Brukerens andre innlogginger er avsluttet.' : 'Modultilgangen er oppdatert.';
            if (payload.user_id === document.body.dataset.adminId && payload.action !== 'password') {
                feedback.append(document.createTextNode(' Last siden på nytt for å oppdatere dine egne modulfaner.'));
            }
        } catch(e) {
            feedback.className = 'text-danger small mb-2';
            feedback.textContent = e.message;
        } finally {
            card.dataset.busy = 'false';
            card.querySelectorAll('button,input,select').forEach(node => node.disabled = false);
            card.querySelectorAll('input[type="password"]').forEach(node => node.value = '');
        }
    }
    function renderUser(user) {
        const card = el('article',undefined,'po-user-card');
        const title = el('div',undefined,'d-flex justify-content-between gap-2');
        const name = el('div');
        name.append(el('h3',user.name),el('small',user.username + (user.is_active ? '' : ' · Inaktiv')));
        const password = el('button','Nytt passord','btn btn-sm btn-outline-secondary');
        password.type = 'button'; password.setAttribute('aria-expanded','false');
        title.append(name,password); card.append(title);
        const pills = el('div',undefined,'po-user-modules');
        for (const module of usersData.modules.filter(module => user.modules.includes(String(module.id)))) {
            const pill = el('span',module.name,'po-module-pill');
            const remove = el('button','×'); remove.type = 'button';
            remove.setAttribute('aria-label','Fjern ' + module.name + ' fra ' + user.name);
            remove.onclick = () => change(card,{user_id:user.id,action:'revoke',module_id:String(module.id)});
            pill.append(remove); pills.append(pill);
        }
        const available = usersData.modules.filter(module => !user.modules.includes(String(module.id)));
        const add = el('button','+ modul','po-add-module'); add.type = 'button';
        add.disabled = available.length === 0; add.setAttribute('aria-expanded','false');
        add.title = available.length ? 'Legg til en av bedriftens moduler' : 'Alle bedriftens moduler er allerede tildelt';
        pills.append(add); card.append(pills);
        const picker = el('form',undefined,'po-module-picker'); picker.hidden = true;
        const select = el('select',undefined,'form-select form-select-sm');
        select.setAttribute('aria-label','Velg modul for ' + user.name);
        for (const module of available) {
            const option = el('option',module.name); option.value = String(module.id); select.append(option);
        }
        const grant = el('button','Legg til','btn btn-sm btn-primary'); grant.type = 'submit';
        picker.append(select,grant); card.append(picker);
        add.onclick = () => { picker.hidden = !picker.hidden; add.setAttribute('aria-expanded',String(!picker.hidden)); if (!picker.hidden) select.focus(); };
        picker.onsubmit = event => { event.preventDefault(); if (select.value) change(card,{user_id:user.id,action:'grant',module_id:select.value}); };
        const form = el('form',undefined,'po-password-form'); form.hidden = true;
        const input = el('input',undefined,'form-control form-control-sm');
        input.type = 'password'; input.autocomplete = 'new-password'; input.minLength = 12; input.maxLength = 72;
        input.required = true; input.id = 'po-password-' + user.id;
        const label = el('label','Nytt passord (minst 12 tegn). Brukerens andre økter avsluttes.'); label.htmlFor = input.id;
        const save = el('button','Lagre passord','btn btn-sm btn-primary'); save.type = 'submit';
        form.append(label,input,save); card.append(form);
        password.onclick = () => {
            form.hidden = !form.hidden; input.value = '';
            password.setAttribute('aria-expanded',String(!form.hidden));
            if (!form.hidden) input.focus();
        };
        form.onsubmit = event => {
            event.preventDefault();
            if (form.reportValidity()) change(card,{user_id:user.id,action:'password',password:input.value});
        };
        return card;
    }
    async function loadUsers() {
        if (loading) return;
        loading = true; feedback.textContent = '';
        list.replaceChildren(el('p','Henter firmabrukere …','text-muted'));
        try {
            usersData = await request();
            list.replaceChildren(...usersData.users.map(renderUser));
            if (!usersData.users.length) list.append(el('p','Ingen brukere med ansattkobling i bedriften.','text-muted'));
        } catch(e) {
            list.replaceChildren(el('p',e.message,'text-danger'));
            const retry = el('button','Prøv igjen','btn btn-sm btn-outline-primary'); retry.onclick = loadUsers; list.append(retry);
        } finally { loading = false; }
    }
    modal?.addEventListener('show.bs.modal',loadUsers);
    modal?.addEventListener('hidden.bs.modal',() => {
        list.replaceChildren(); usersData = undefined; feedback.textContent = '';
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',load);
    else load();
    return {open,load,tab,moduleNames};
})();
