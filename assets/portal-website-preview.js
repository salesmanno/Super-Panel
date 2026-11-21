(() => {
    'use strict';
    const root = document.getElementById('portal-website-preview');
    if (!root) return;
    const data = JSON.parse(document.getElementById('pwp-data').textContent);
    let company = data.company || {};
    const modules = new Set(data.modules.map(module => module.module_key));
    if (modules.has('reservation')) modules.add('reservations');
    const content = document.getElementById('pwp-content');
    // Notifications must remain visible even while the legacy workspace is hidden.
    const toastContainer = document.getElementById('toastContainer');
    if (toastContainer) document.body.appendChild(toastContainer);
    const menu = document.getElementById('pwp-create-menu');
    const newButton = document.getElementById('pwp-new');
    let page = 'home', mode = 'read';
    let islandPage = 0;
    function renderIsland() {
        const island = root.querySelector('.pwp-island');
        const actions = [...island.querySelectorAll('[data-pwp-mode], #pwp-new')].filter(button =>
            page === 'services' || ['read', 'edit'].includes(button.dataset.pwpMode) || button === newButton);
        const pageCount = Math.max(1, Math.ceil(actions.length / 3));
        islandPage = ((islandPage % pageCount) + pageCount) % pageCount;
        island.querySelectorAll('[data-pwp-mode], #pwp-new').forEach(button => {
            const index = actions.indexOf(button);
            button.hidden = page === 'about' || index < islandPage * 3 || index >= islandPage * 3 + 3;
        });
        island.querySelectorAll('[data-pwp-island-step]').forEach(button => {
            button.disabled = pageCount <= 1;
        });
    }
    // Reserve only the actual fixed footer height; the island has no layout footprint.
    const footer = root.querySelector('.pwp-footer');
    function measureFooter() {
        root.style.setProperty('--pwp-footer-height', `${footer?.getBoundingClientRect().height || 0}px`);
    }
    if (footer && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(measureFooter).observe(footer);
    }
    const workspace = window.PortalWorkspace;
    let activeSetting = 'company';
    let clearMemberScope = () => {};
    function showCreationSurface(element) {
        if (!element) return;
        workspace.show(element, {announcements:'Opprett ny sak', rental:'Opprett utleieobjekt', events:'Opprett arrangement'}[element.dataset.pwpKind] || 'Opprett',
            {className:element.id.startsWith('news-')?'news-control-room':''});
    }
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char =>
        ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char]));
    const text = value => {
        const doc = new DOMParser().parseFromString(String(value || ''), 'text/html');
        return doc.body.textContent || '';
    };
    const url = value => {
        const raw = String(value || '').trim();
        if (!raw) return '';
        try { const parsed = new URL(raw, location.origin); return ['https:', 'http:'].includes(parsed.protocol) ? parsed.href : ''; }
        catch { return ''; }
    };
    const image = (src, title = '') => url(src) ? `<img src="${esc(url(src))}" alt="${esc(title)}" loading="lazy">` : '';
    const labels = {membership: 'Medlemskap', rental: 'Utleie', events: 'Arrangementer', sales: 'Salgsvarer', reservations: 'Timebestilling'};
    const choices = {membership: 'Medlemskapsplan', rental: 'Utleieobjekt', events: 'Arrangement', sales: 'Salgsprodukt', reservations: 'Bestillingstjeneste'};
    const say = message => { document.getElementById('pwp-errors').textContent = message; };
    const canOpen = key => key === 'announcements' || modules.has(key);
    function openWorkspace(key) {
        if (!canOpen(key)) { say('Du har ikke tilgang til denne modulen.'); return false; }
        if (!workspace.activate(key)) { say('Modulen er ikke tilgjengelig i denne portalen.'); return false; }
        clearMemberScope();
        return true;
    }
    function openPlanMembers(item) {
        if (!openWorkspace('membership')) return;
        const filter = document.getElementById('member-plan-filter');
        const membersLink = document.querySelector('[href="#membership-members"]');
        if (!filter || !membersLink) return;
        const heading = document.querySelector('#membership-members .card-title');
        const original = {value:filter.value, disabled:filter.disabled, title:heading?.textContent};
        const applyScope = () => {
            if (![...filter.options].some(option => option.value === item.source_id)) {
                filter.add(new Option(item.name, item.source_id));
            }
            filter.value = item.source_id;
            filter.disabled = true;
            filter.dispatchEvent(new Event('change', {bubbles:true}));
        };
        // Loading the legacy plan options must not reset this card's plan scope.
        const observer = new MutationObserver(applyScope);
        applyScope();
        observer.observe(filter, {childList:true});
        if (heading) heading.textContent = `Medlemmer ${item.name}`;
        clearMemberScope = () => {
            observer.disconnect();
            filter.disabled = original.disabled;
            filter.value = original.value;
            filter.dispatchEvent(new Event('change', {bubbles:true}));
            if (heading) heading.textContent = original.title;
            clearMemberScope = () => {};
        };
        membersLink.click();
        workspace.show(document.getElementById('membership-members'), `Medlemmer ${item.name}`);
    }
    async function create(key) {
        menu.hidden = true;
        newButton.setAttribute('aria-expanded', 'false');
        if (!openWorkspace(key)) return;
        // Reuse the real forms and their event handlers, not shortened copies.
        const triggers = {
            announcements: '#news-create-tab',
            events: '#subtabs-events a[href="#events-create"]',
            membership: '[data-bs-target="#createPlanModal"]',
            sales: '#sales-new-product',
            reservations: '[data-bs-target="#createServiceModal"]',
        };
        if (key === 'announcements' && window.PortalNews) {
            if (!window.PortalNews.openNew()) return;
        } else if (key === 'rental' && typeof window.openRentalCreate === 'function') {
            window.openRentalCreate();
        } else if (key === 'reservations' && typeof window.openCreateServiceModal === 'function') {
            window.openCreateServiceModal();
        } else {
            // Product controls stay disabled until the real module has loaded.
            let trigger;
            for (let attempt = 0; attempt < 80; attempt++) {
                trigger = document.querySelector(triggers[key] || ':not(*)');
                if (trigger && !trigger.disabled) break;
                await new Promise(resolve => setTimeout(resolve, 60));
            }
            if (trigger?.disabled) trigger = null;
            if (trigger) trigger.click();
            else document.querySelector('#pwp-workspace-bar span').textContent = 'Opprettelsesskjemaet ble ikke funnet. Åpne det fra modulen.';
        }
        const inlineForms = {announcements:'#news-create-panel', rental:'#rental-create', events:'#events-create'};
        if (inlineForms[key]) {
            const surface = document.querySelector(inlineForms[key]);
            if (surface) surface.dataset.pwpKind = key;
            showCreationSurface(surface);
        }
    }
    async function editSalesProduct(id) {
        const ready = () => window.SalesOnline?.snapshot().ready;
        if (!ready()) {
            say('Henter salgsvarer …');
            document.dispatchEvent(new CustomEvent('sales:online-load'));
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => { cleanup(); reject(new Error('Varelisten kunne ikke lastes. Prøv igjen.')); }, 8000);
                const cleanup = () => { clearTimeout(timeout); document.removeEventListener('sales:products-updated', updated); };
                const updated = () => {
                    const state = window.SalesOnline?.snapshot();
                    if (state?.error) { cleanup(); reject(new Error(state.error)); }
                    else if (state?.ready) { cleanup(); resolve(); }
                };
                document.addEventListener('sales:products-updated', updated);
                updated();
            });
        }
        if (!window.SalesOnline.snapshot().products.some(product => String(product.id) === String(id))) throw new Error('Varen er ikke tilgjengelig i bedriftens vareliste.');
        say('');
        window.SalesOnline.edit(id);
    }
    async function openServiceFields(item) {
        const type = {events:'event',rental:'rental_object',sales:'sales_product',membership:'membership_plan'}[item.module];
        const host = document.createElement('div');
        host.id = 'pwp-service-fields';
        if (!type || !window.EntityFormFields?.mount) {
            host.textContent = !type ? 'Denne tjenestetypen har ikke et separat skjemafeltoppsett i denne modulen.' : 'Skjemafeltredigeringen kunne ikke lastes. Last siden på nytt.';
            workspace.showTemporary(host, `Skjemafelt · ${item.name}`);
            return;
        }
        let editor;
        if (!workspace.showTemporary(host, `Skjemafelt · ${item.name}`, {beforeClose:()=>editor?.confirmClose() ?? true})) return;
        editor = window.EntityFormFields.mount(host, {
            entityType:type, entityId:item.source_id, eventLayout:true,
            onSaved:()=>workspace.close(true)
        });
        await editor.refresh();
    }
    function openProductSummary(item) {
        const article = document.createElement('article');
        article.innerHTML = `${image(item.image_url,item.name)}<h2>${esc(item.name)}</h2><p>${esc(text(item.description))}</p>${item.price != null ? `<strong>${Number(item.price).toLocaleString('nb-NO',{style:'currency',currency:'NOK'})}</strong>`:''}`;
        workspace.showTemporary(article, `Se på · ${item.name}`);
    }
    let serviceView = null, serviceNavigating = false;
    const serviceActionLabels = {read:'Se på',edit:'Rediger',fields:'Skjemafelt',benefits:'Fordeler',payments:'Betalingsmetoder'};
    function serviceActions(item) {
        const actions = ['read','edit'];
        if (['events','rental','membership','sales'].includes(item.module) && window.EntityFormFields?.mount) actions.push('fields');
        if (modules.has('membership') && window.MembershipBenefits?.openProduct
            && ['events','rental','sales'].includes(item.module) && item.benefit_eligible !== false) actions.push('benefits');
        if (['events','rental','membership'].includes(item.module)) actions.push('payments');
        return actions;
    }
    function serviceFormSnapshot(surface) {
        return JSON.stringify([...surface.querySelectorAll('input,select,textarea')]
            .filter(field => !field.closest('.pwp-service-navigation') && (field.name || field.id)
                && field.type !== 'file' && !field.hasAttribute('data-norwegian-date-time-proxy'))
            .map(field => [field.name || field.id,field.type === 'checkbox' || field.type === 'radio' ? field.checked
                : field.multiple ? [...field.selectedOptions].map(option=>option.value) : field.value]));
    }
    async function closeServiceView() {
        const view = serviceView, surface = view?.surface;
        if (!surface) return true;
        if ((surface.id === 'pwp-dialog' && !surface.open)
            || (surface.matches('.modal') && !surface.classList.contains('show'))
            || (['mb-dialog','sales-dialog'].includes(surface.id) && surface.hidden)) return true;
        // Event/rental already own their dirty guards; do not prompt twice.
        if (view.action === 'edit' && !['editEventModal','rentalModal'].includes(surface.dataset.pwpSourceModal || surface.id)
            && view.interacted && serviceFormSnapshot(surface) !== view.baseline
            && !confirm('Du har ulagrede endringer. Forkast dem og bytt visning?')) return false;
        if (surface.id === 'pwp-dialog') return workspace.close();
        if (surface.id === 'mb-dialog') return window.MembershipBenefits.closeProduct();
        if (surface.id === 'sales-dialog') return window.SalesOnline.close();
        if (surface.matches('.modal')) {
            const instance = window.bootstrap?.Modal.getInstance(surface);
            if (!instance) return !surface.classList.contains('show');
            return new Promise(resolve => {
                let timer;
                const finish = ok => {
                    clearTimeout(timer); surface.removeEventListener('hidden.bs.modal', hidden);
                    surface.removeEventListener('hide.bs.modal', hiding); resolve(ok);
                };
                const hidden = () => finish(true);
                const hiding = event => queueMicrotask(()=>{if(event.defaultPrevented) finish(false);});
                surface.addEventListener('hidden.bs.modal',hidden);
                surface.addEventListener('hide.bs.modal',hiding);
                timer = setTimeout(()=>finish(!surface.classList.contains('show')),1000);
                instance.hide();
            });
        }
        return false;
    }
    function mountServiceNavigation(view) {
        if (serviceView !== view) return;
        const surface = document.getElementById('pwp-dialog')?.open ? document.getElementById('pwp-dialog')
            : [...document.querySelectorAll('.pwp-module-modal.show')].at(-1)
                || ['mb-dialog','sales-dialog'].map(id=>document.getElementById(id)).find(element=>element && !element.hidden);
        if (!surface) { serviceView = null; return; }
        const host = surface.querySelector('#pwp-dialog-body,.modal-body,#sales-dialog-body,.mb-editor') || surface;
        surface.querySelector('.pwp-service-navigation')?.remove();
        const nav = document.createElement('nav');
        nav.className = 'pwp-service-navigation';
        nav.setAttribute('aria-label',`Visninger for ${view.item.name}`);
        const actions = serviceActions(view.item);
        nav.innerHTML = `<button type="button" data-service-step="-1" aria-label="Forrige visning">‹</button>
            <label><span>Samme tjeneste</span><select aria-label="Velg visning for samme tjeneste">${actions.map(action=>
                `<option value="${action}"${action===view.action?' selected':''}>${serviceActionLabels[action]}</option>`).join('')}</select></label>
            <button type="button" data-service-step="1" aria-label="Neste visning">›</button>`;
        host.prepend(nav);
        view.surface = surface; view.baseline = serviceFormSnapshot(surface); view.interacted = false;
        const interaction = event => { if (!nav.contains(event.target)) view.interacted = true; };
        surface.addEventListener('input',interaction); surface.addEventListener('change',interaction);
        view.cleanup = () => {
            nav.remove(); surface.removeEventListener('input',interaction); surface.removeEventListener('change',interaction);
        };
        const select = nav.querySelector('select');
        const navigate = action => openService(view.item,action).catch(error=>say(error.message));
        select.addEventListener('change',()=>{const next=select.value;select.value=view.action;navigate(next);});
        nav.addEventListener('click',event=>{
            const button=event.target.closest('[data-service-step]');
            if (!button) return;
            const index=actions.indexOf(view.action);
            navigate(actions[(index+Number(button.dataset.serviceStep)+actions.length)%actions.length]);
        });
    }
    async function openService(item, action) {
        if (serviceNavigating) return;
        if (!item || !canOpen(item.module)) { say('Du har ikke tilgang til denne tjenesten.'); return; }
        serviceNavigating = true;
        const previous = serviceView;
        previous?.surface?.querySelectorAll('.pwp-service-navigation button,.pwp-service-navigation select')
            .forEach(control=>{control.disabled=true;});
        try {
            if (!await closeServiceView()) return;
            previous?.cleanup?.();
            const view = {item,action};
            serviceView = view;
            try {
                const loading = document.createElement('p');
                loading.dataset.pwpServiceLoading = '';
                loading.textContent = `Henter ${serviceActionLabels[action]?.toLowerCase() || 'visning'} for ${item.name} …`;
                workspace.showTemporary(loading,`${serviceActionLabels[action] || 'Tjeneste'} · ${item.name}`,{transient:true});
                await openServiceSurface(item,action);
                mountServiceNavigation(view);
            } catch (error) {
                if (document.querySelector('#pwp-dialog-body [data-pwp-service-loading]')) workspace.close(true);
                serviceView = null;
                throw error;
            }
        } finally {
            serviceNavigating = false;
            serviceView?.surface?.querySelectorAll('.pwp-service-navigation button,.pwp-service-navigation select')
                .forEach(control=>{control.disabled=false;});
        }
    }
    async function openServiceSurface(item, action) {
        if (!item || !canOpen(item.module)) { say('Du har ikke tilgang til denne tjenesten.'); return; }
        if (action === 'payments') { await openServicePayments(item); return; }
        if (action === 'fields') { await openServiceFields(item); return; }
        if (action === 'benefits') {
            if (!modules.has('membership') || !window.MembershipBenefits) throw new Error('Medlemsfordeler er ikke tilgjengelig.');
            await window.MembershipBenefits.openProduct(item.id);
            return;
        }
        if (item.module === 'membership' && action === 'read') { openPlanMembers(item); return; }
        if (!openWorkspace(item.module)) return;
        if (action === 'edit') {
            switch (item.module) {
                case 'rental':
                    if (window.loadRentalObjects) await window.loadRentalObjects();
                    if (!window.editRentalObject) break;
                    await window.editRentalObject(item.source_id); return;
                case 'events':
                    if (!window.editEvent) break;
                    await window.editEvent(item.source_id); return;
                case 'membership':
                    if (!window.editMembershipPlan) break;
                    await window.editMembershipPlan(item.source_id); return;
                case 'sales': await editSalesProduct(item.source_id); return;
                case 'reservations':
                    if (window.loadServices) await window.loadServices();
                    if (!window.editService) break;
                    await window.editService(item.source_id); return;
            }
            throw new Error('Redigeringen for denne tjenestetypen kunne ikke åpnes.');
        }
        if (action === 'read') {
            if (item.module === 'events') {
                if (window.loadEvents) await window.loadEvents();
                if (!window.EventsDashboard?.openParticipants) throw new Error('Deltakerlisten kunne ikke åpnes.');
                await window.EventsDashboard.openParticipants(item.source_id);
            } else if (item.module === 'rental') {
                if (window.loadRentalObjects) await window.loadRentalObjects();
                if (!window.viewDetails) throw new Error('Utleieobjektet kunne ikke åpnes.');
                window.viewDetails(item.source_id);
            } else openProductSummary(item);
            return;
        }
        throw new Error('Ukjent handling. Velg Se på, Rediger, Fordeler eller Skjemafelt.');
    }
    async function openServicePayments(item) {
        const host = document.createElement('form');
        host.className = 'pwp-payment-settings';
        const supported = ['events', 'membership', 'rental'].includes(item.module);
        if (!supported) {
            host.textContent = 'Betalingsmetoder kan velges for arrangementer, medlemskap og utleie.';
            workspace.showTemporary(host, `Betalingsmetoder · ${item.name}`);
            return;
        }
        let pending = false, baseline = '', state;
        const selection = () => [...host.querySelectorAll('input[name="method"]:checked')].map(input => input.value);
        const signature = () => JSON.stringify(selection().sort());
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        status.textContent = 'Henter betalingsmetoder …';
        host.append(status);
        if (!workspace.showTemporary(host, `Betalingsmetoder · ${item.name}`, {
            beforeClose: () => !pending && (!state || baseline === signature() || confirm('Du har ulagrede betalingsvalg. Lukk uten å lagre?'))
        })) return;
        const request = async (options = {}) => {
            const query = new URLSearchParams({module: item.module, id: item.source_id});
            const response = await fetch(`/api/portal_service_payments.php?${query}`, {cache:'no-store', ...options});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Betalingsmetodene kunne ikke behandles.');
            return result;
        };
        const load = async () => {
            try {
                state = await request();
                if (!host.isConnected) return;
                host.replaceChildren();
                const introduction = document.createElement('p');
                introduction.textContent = state.registration_type === 'none'
                    ? 'Arrangementet er uten påmelding og bruker ingen betalingsmetoder.'
                    : 'Velg betalingsmetodene som skal tilbys for denne tjenesten. Flere kan velges.';
                host.append(introduction);
                if (state.registration_type === 'binding') {
                    const hint = document.createElement('p');
                    hint.textContent = 'Bindende påmelding: kontant er ikke tillatt.';
                    host.append(hint);
                }
                const list = document.createElement('fieldset');
                const legend = document.createElement('legend');
                legend.textContent = 'Betalingsmetoder';
                list.append(legend);
                const options = [...state.methods];
                state.selected.filter(value => !options.some(option => option.value === value))
                    .forEach(value => options.push({value, label:`${value} – ikke tilgjengelig lenger. Fjern valget før lagring.`, unavailable:true}));
                for (const option of options) {
                    const label = document.createElement('label');
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox'; checkbox.name = 'method'; checkbox.value = option.value;
                    checkbox.checked = state.selected.includes(option.value);
                    if (option.unavailable) checkbox.addEventListener('change', () => { checkbox.disabled = !checkbox.checked; });
                    label.append(checkbox, document.createTextNode(option.label));
                    list.append(label);
                }
                if (!options.length) {
                    const empty = document.createElement('p');
                    empty.textContent = state.registration_type === 'none' ? 'Ingen betalingsvalg er nødvendige.'
                        : 'Ingen aktive og konfigurerte betalingsmetoder. Aktiver dem i Økonomi → Betalingsløsninger først.';
                    list.append(empty);
                }
                const save = document.createElement('button');
                save.type = 'submit'; save.className = 'btn btn-primary'; save.textContent = 'Lagre betalingsmetoder';
                host.append(list, status, save);
                status.textContent = '';
                baseline = signature();
            } catch (error) {
                status.textContent = error.message;
                if (!host.querySelector('[data-payment-retry]')) {
                    const retry = document.createElement('button');
                    retry.type = 'button'; retry.dataset.paymentRetry = ''; retry.textContent = 'Prøv igjen';
                    retry.addEventListener('click', () => { retry.remove(); load(); });
                    host.append(retry);
                }
            }
        };
        host.addEventListener('submit', async event => {
            event.preventDefault();
            if (pending || !state) return;
            const chosen = selection();
            pending = true;
            host.querySelectorAll('input,button').forEach(control => { control.disabled = true; });
            status.textContent = 'Lagrer …';
            try {
                const result = await request({method:'POST', headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({module:item.module, id:item.source_id, methods:chosen,
                        version:state.version, csrf_token:data.payment_csrf})});
                state = result;
                baseline = signature();
                status.textContent = result.changed ? 'Betalingsmetodene er lagret.' : 'Ingen endringer å lagre.';
            } catch (error) {
                status.textContent = error.message;
            } finally {
                pending = false;
                host.querySelectorAll('input,button').forEach(control => { control.disabled = false; });
            }
        });
        await load();
    }
    function settingChoices() {
        return [
            ...(data.is_supervisor ? [
                ['company','Bedriften'], ['staff','Ansatte'], ['access','Firmabrukere og modultilgang'], ['about','Om oss-tekst']
            ] : []),
            ...(data.is_supervisor || modules.has('ditt_nettsted') ? [['website','Webside']] : []),
            ...(modules.has('email') || modules.has('webmail') ? [['email','E-post']] : []),
            ...(modules.has('economy') ? [['economy','Økonomi']] : []),
            ...[...modules].filter(key => ['sales','events','rental','membership','reservations'].includes(key))
                .map(key => [key, labels[key] || 'Timebestilling']),
            ['announcements','Nyhetsadministrasjon']
        ];
    }
    function selectSetting(key) {
        const choices = settingChoices();
        if (!choices.some(([value]) => value === key)) return;
        if (!workspace.canLeaveSettings()) return;
        activeSetting = key;
        root.querySelector('.pwp-settings-tools')?.removeAttribute('open');
        root.querySelectorAll('[data-pwp-setting]').forEach(button => button.setAttribute('aria-selected', String(button.dataset.pwpSetting === key)));
        const select = document.getElementById('pwp-settings-select');
        if (select) select.value = key;
        const host = document.getElementById('pwp-settings-main');
        workspace.clearAbout();
        host.replaceChildren();
        if (['company','staff','access','about','website','email','economy'].includes(key)) workspace.settings(key, host);
        else workspace.moduleSurface(key, choices.find(([value]) => value === key)[1], host);
    }
    function render() {
        workspace.clearAbout();
        root.dataset.page = page;
        document.querySelectorAll('[data-pwp-page]').forEach(button => {
            if (button.dataset.pwpPage === page) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        renderIsland();
        const island = root.querySelector('.pwp-island');
        island.classList.toggle('is-faded', page === 'about');
        island.inert = page === 'about';
        if (page === 'home') {
            const [hero, ...rest] = data.news;
            content.innerHTML = hero ? `<article class="pwp-hero" data-news-id="${esc(hero.id)}" tabindex="0" role="button"><div><h1>${esc(hero.title)}</h1><p>${esc(text(hero.content).slice(0, 240))}</p></div>${image(hero.image_url, hero.title)}</article>
                <div class="pwp-grid">${rest.map(story => `<button type="button" class="pwp-card" data-news-id="${esc(story.id)}">${image(story.image_url, story.title)}<span class="pwp-card-body"><h3>${esc(story.title)}</h3><p>${esc(text(story.content).slice(0, 140))}</p></span></button>`).join('')}</div>`
                : '<h1>Nyheter</h1><p>Ingen publiserte saker akkurat nå. Bruk «Ny» for å opprette en sak.</p>';
        } else if (page === 'services') {
            content.innerHTML = '<h1>Tjenester</h1>' + Object.entries(labels).filter(([key]) => modules.has(key)).map(([key, label]) => {
                const products = data.products.filter(item => item.module === key && item.is_visible && item.is_public);
                if (['sales','reservations'].includes(key) && products.length === 0) return '';
                return `<section><h2>${esc(label)}</h2>${products.length ? `<div class="pwp-grid">${products.map(item => `<button type="button" class="pwp-card" data-service-id="${esc(item.id)}">${image(item.image_url, item.name)}<span class="pwp-card-body"><h3>${esc(item.name)}</h3><p>${esc(text(item.description).slice(0, 150))}</p>${item.price !== null ? `<strong>${Number(item.price).toLocaleString('nb-NO', {style:'currency',currency:'NOK'})}</strong>` : ''}</span></button>`).join('')}</div>` : '<p>Ingen offentlige tilbud. Du kan opprette et tilbud med «Ny».</p>'}</section>`;
            }).join('');
        } else {
            const choices = settingChoices();
            const primaryOrder = ['company','staff','email','economy','website'];
            const primary = choices.filter(([key]) => primaryOrder.includes(key))
                .sort(([a],[b]) => primaryOrder.indexOf(a) - primaryOrder.indexOf(b));
            const tools = choices.filter(([key]) => !primary.some(([value]) => value === key));
            const descriptions = {company:'Bedriftsprofil',staff:'Medarbeidere',email:'Utsending og maler',economy:'Betaling og oversikt',website:'Utseende og nettside'};
            content.innerHTML = `<section class="pwp-about"><header><div><span class="pwp-about-kicker">ADMINISTRASJON / INNSTILLINGER</span><h1>Om oss</h1><p>Bedriftens interne arbeidsflate. Dette er ikke innhold som vises til kundene.</p></div></header>
                <div class="pwp-settings-layout"><nav class="pwp-settings-nav" aria-label="Bedriftsinnstillinger">${primary.map(([key,label],index)=>`<button type="button" data-pwp-setting="${esc(key)}" aria-selected="false"><span class="pwp-setting-number">${String(index+1).padStart(2,'0')}</span><span class="pwp-setting-label"><b>${esc(label)}</b><small>${esc(descriptions[key])}</small></span><span class="pwp-setting-arrow" aria-hidden="true">→</span></button>`).join('')}
                ${tools.length ? `<details class="pwp-settings-tools"><summary>Flere verktøy <span aria-hidden="true">⌄</span></summary><div>${tools.map(([key,label])=>`<button type="button" data-pwp-setting="${esc(key)}" aria-selected="false">${esc(label)}</button>`).join('')}</div></details>`:''}</nav>
                <div id="pwp-settings-main" class="pwp-settings-main"></div></div></section>`;
            selectSetting(choices.some(([key]) => key === activeSetting) ? activeSetting : choices[0][0]);
        }
        applyWebsiteAppearance(company);
    }
    function applyWebsiteAppearance(theme) {
        const color = (value,fallback) => /^#[\da-f]{3,8}$/i.test(value || '') ? value : fallback;
        const size = (value,fallback) => /^\d+(\.\d+)?(px|rem|em|%|vh|vw)$/i.test(value || '') ? value : fallback;
        const positions = {left:'flex-start',center:'center',right:'flex-end'};
        for (const [property,value] of Object.entries({
            '--pwp-header':color(theme.header_bg_color,'#176b69'), '--pwp-ink':color(theme.header_text_color,'#ffffff'),
            '--pwp-height':size(theme.header_height,'80px'),'--pwp-logo-height':size(theme.logo_height,'50px'),
            '--pwp-title-size':size(theme.company_header_font_size,'20px'),'--pwp-radius':size(theme.border_radius,'16px'),
            '--pwp-font': /^[a-zA-Z0-9 ,'"_-]{1,150}$/.test(theme.font_family || '') ? theme.font_family : 'Inter, sans-serif',
            '--pwp-body':color(theme.body_bg_color,'#e0f0fc'),'--pwp-primary':color(theme.primary_color,'#315c91'),
            '--pwp-logo-position':positions[theme.logo_position] || 'center'
        })) root.style.setProperty(property,value);
        root.dataset.headerMode = theme.header_mode === 'dynamic' ? 'dynamic' : 'static';
        root.dataset.footerMode = theme.footer_mode === 'dynamic' ? 'dynamic' : 'static';
        root.querySelector('.pwp-site-nav').style.justifyContent = positions[theme.nav_position] || 'center';
        document.getElementById('pwp-company-name').textContent = theme.name || '';
        const controlCompany = document.getElementById('pwp-control-company');
        if (controlCompany) controlCompany.textContent = theme.name || '';
        document.getElementById('pwp-footer-company').textContent = theme.name || '';
        document.getElementById('pwp-contact').textContent = [theme.contact_phone,theme.address_street,theme.address_zip,theme.address_city].filter(Boolean).join(' · ');
        measureFooter();
        const logo = document.getElementById('pwp-logo');
        logo.hidden = !url(theme.img_url);
        if (!logo.hidden) logo.src = url(theme.img_url);
        else logo.removeAttribute('src');
        root.querySelectorAll('.pwp-hero').forEach(hero => { hero.hidden = String(theme.hero_enabled ?? '1') === '0'; });
    }
    applyWebsiteAppearance(company);
    document.addEventListener('portal:website-preview', event => {
        Object.assign(company,event.detail); applyWebsiteAppearance(company);
    });
    document.addEventListener('portal:website-saved', event => {
        Object.assign(company,event.detail); data.company = company;
        document.getElementById('pwp-data').textContent = JSON.stringify(data);
    });
    say(data.errors.join(' '));
    root.addEventListener('click', event => {
        const pageButton = event.target.closest('[data-pwp-page]');
        if (pageButton) {
            if (!workspace.canLeaveSettings()) return;
            root.querySelector('.pwp-account')?.removeAttribute('open');
            if (!workspace.close()) return;
            page = pageButton.dataset.pwpPage;
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
            if (page !== 'services' && ['benefits','fields','payments'].includes(mode)) mode = 'read';
            islandPage = 0;
            root.querySelectorAll('[data-pwp-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pwpMode === mode)));
            render(); applyWebsiteAppearance(company); return;
        }
        const islandArrow = event.target.closest('[data-pwp-island-step]');
        if (islandArrow) {
            islandPage += Number(islandArrow.dataset.pwpIslandStep);
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
            renderIsland();
            return;
        }
        const modeButton = event.target.closest('[data-pwp-mode]');
        if (modeButton) {
            mode = modeButton.dataset.pwpMode;
            root.querySelectorAll('[data-pwp-mode]').forEach(button => button.setAttribute('aria-pressed', String(button === modeButton)));
            return; // Do not rerender or change the preview on mode selection.
        }
        const settingButton = event.target.closest('[data-pwp-setting]');
        if (settingButton) { selectSetting(settingButton.dataset.pwpSetting); return; }
        const story = event.target.closest('[data-news-id]');
        if (story && openWorkspace('announcements')) {
            const id = story.dataset.newsId;
            if (mode === 'read') {
                const item = data.news.find(news => String(news.id) === id);
                const article = document.createElement('article');
                article.innerHTML = `<h2>${esc(item?.title)}</h2>${image(item?.image_url,item?.title)}<p style="white-space:pre-line">${esc(text(item?.content))}</p>`;
                workspace.showTemporary(article, item?.title || 'Nyhet');
            } else {
                if (!window.PortalNews) { say('Nyhetsredigeringen er ikke tilgjengelig. Last siden på nytt.'); return; }
                window.PortalNews.edit(id).then(opened => {
                    if (!opened) say('Saken kunne ikke åpnes, eller åpningen ble avbrutt.');
                }).catch(error => say(error.message));
            }
            return;
        }
        const service = event.target.closest('[data-service-id]');
        if (service) {
            const item = data.products.find(product => product.id === service.dataset.serviceId);
            openService(item, mode).catch(error=>say(error.message));
        }
    });
    root.addEventListener('keydown', event => {
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('article[data-news-id]')) {
            event.preventDefault(); event.target.click();
        }
    });
    newButton.addEventListener('click', () => {
        if (page === 'home') { create('announcements'); return; }
        menu.hidden = !menu.hidden;
        newButton.setAttribute('aria-expanded', String(!menu.hidden));
        if (menu.hidden) return;
        menu.innerHTML = Object.entries(choices).filter(([key]) => modules.has(key))
            .map(([key, title]) => `<button type="button" data-create="${key}">${esc(title)} <span aria-hidden="true">＋</span></button>`).join('') || '<p>Ingen tjenestemoduler tilgjengelig.</p>';
    });
    menu.addEventListener('click', event => {
        const button = event.target.closest('[data-create]');
        if (button) create(button.dataset.create);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
            const account = root.querySelector('.pwp-account[open]');
            if (account) { account.removeAttribute('open'); account.querySelector('summary').focus({preventScroll:true}); }
        }
    });
    document.addEventListener('click', event => {
        const account = root.querySelector('.pwp-account[open]');
        if (account && !account.contains(event.target)) account.removeAttribute('open');
        if (!menu.contains(event.target) && !newButton.contains(event.target)) {
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
        }
    });
    let refreshSerial = 0;
    document.addEventListener('portal:news-saved', () => workspace.close(true));
    document.addEventListener('hidden.bs.modal', event => {
        if (event.target.classList.contains('pwp-module-modal')) document.dispatchEvent(new CustomEvent('portal:surface-closed'));
    });
    document.addEventListener('portal:news-editor-open', () => {
        workspace.show(document.getElementById('news-create-panel'), 'Rediger sak',
            {className:'news-control-room',beforeClose:()=>window.PortalNews.canClose()});
    });
    document.addEventListener('portal:surface-closed', async () => {
        clearMemberScope();
        const serial = ++refreshSerial;
        try {
            const response = await fetch('/api/get_portal_website_preview.php', {credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}});
            const next = await response.json();
            if (!response.ok || !next.success) throw new Error(next.message || 'Kunne ikke oppdatere forhåndsvisningen.');
            if (serial !== refreshSerial) return;
            Object.assign(data, next);
            company = data.company || {};
            document.getElementById('pwp-data').textContent = JSON.stringify(data);
            applyWebsiteAppearance(company);
            modules.clear(); next.modules.forEach(module => modules.add(module.module_key));
            if (modules.has('reservation')) modules.add('reservations');
            if (!document.getElementById('pwp-dialog')?.open) { render(); say(next.errors.join(' ')); }
        } catch (error) {
            if (serial === refreshSerial) say(error.message || 'Kunne ikke oppdatere forhåndsvisningen. Last siden på nytt.');
        }
    });
    render();
})();
