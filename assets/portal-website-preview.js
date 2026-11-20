(() => {
    'use strict';
    const root = document.getElementById('portal-website-preview');
    if (!root) return;
    const data = JSON.parse(document.getElementById('pwp-data').textContent);
    const company = data.company || {};
    const modules = new Set(data.modules.map(module => module.module_key));
    if (modules.has('reservation')) modules.add('reservations');
    const content = document.getElementById('pwp-content');
    // Notifications must remain visible even while the legacy workspace is hidden.
    const toastContainer = document.getElementById('toastContainer');
    if (toastContainer) document.body.appendChild(toastContainer);
    const menu = document.getElementById('pwp-create-menu');
    const newButton = document.getElementById('pwp-new');
    let page = 'home', mode = 'read';
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
    async function openService(item, action) {
        if (!item || !canOpen(item.module)) { say('Du har ikke tilgang til denne tjenesten.'); return; }
        if (!workspace.close()) return;
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
        document.querySelectorAll('[data-pwp-page]').forEach(button => {
            if (button.dataset.pwpPage === page) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        root.querySelector('[data-pwp-mode="benefits"]')?.toggleAttribute('hidden', page !== 'services');
        root.querySelector('[data-pwp-mode="fields"]')?.toggleAttribute('hidden', page !== 'services');
        newButton.hidden = page === 'about';
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
                return `<section><h2>${esc(label)}</h2>${products.length ? `<div class="pwp-grid">${products.map(item => `<button type="button" class="pwp-card" data-service-id="${esc(item.id)}">${image(item.image_url, item.name)}<span class="pwp-card-body"><h3>${esc(item.name)}</h3><p>${esc(text(item.description).slice(0, 150))}</p>${item.price !== null ? `<strong>${Number(item.price).toLocaleString('nb-NO', {style:'currency',currency:'NOK'})}</strong>` : ''}</span></button>`).join('')}</div>` : '<p>Ingen offentlige tilbud. Du kan opprette et tilbud med «Ny».</p>'}</section>`;
            }).join('');
        } else {
            const choices = settingChoices();
            const primary = choices.filter(([key]) => ['company','staff','email','economy','website'].includes(key));
            const tools = choices.filter(([key]) => !primary.some(([value]) => value === key));
            content.innerHTML = `<section class="pwp-about"><header><h1>Om oss</h1><p>Bedriften, nettsiden og administrasjonen samlet. Dette er din interne arbeidsflate.</p></header>
                <div class="pwp-settings-layout"><nav class="pwp-settings-nav" aria-label="Bedriftsinnstillinger">${primary.map(([key,label])=>`<button type="button" data-pwp-setting="${esc(key)}" aria-selected="false">${esc(label)}</button>`).join('')}
                ${tools.length ? `<details class="pwp-settings-tools"><summary>Flere verktøy <span aria-hidden="true">⌄</span></summary><div>${tools.map(([key,label])=>`<button type="button" data-pwp-setting="${esc(key)}" aria-selected="false">${esc(label)}</button>`).join('')}</div></details>`:''}</nav>
                <div id="pwp-settings-main" class="pwp-settings-main"></div></div></section>`;
            selectSetting(choices.some(([key]) => key === activeSetting) ? activeSetting : choices[0][0]);
        }
    }
    for (const [property, value] of Object.entries({
        '--pwp-header': company.header_bg_color, '--pwp-ink': company.header_text_color || '#ffffff',
        '--pwp-height': company.header_height, '--pwp-logo-height': company.logo_height,
        '--pwp-title-size': company.company_header_font_size, '--pwp-radius': company.border_radius,
        '--pwp-font': company.font_family,
        '--pwp-logo-position': {left:'flex-start',center:'center',right:'flex-end'}[company.logo_position],
    })) if (value) root.style.setProperty(property, value);
    document.getElementById('pwp-company-name').textContent = company.name || '';
    document.getElementById('pwp-footer-company').textContent = company.name || '';
    document.getElementById('pwp-contact').textContent = [company.contact_phone, company.address_street, company.address_zip, company.address_city].filter(Boolean).join(' · ');
    const logo = document.getElementById('pwp-logo');
    if (url(company.img_url)) { logo.src = url(company.img_url); logo.hidden = false; }
    say(data.errors.join(' '));
    root.addEventListener('click', event => {
        const pageButton = event.target.closest('[data-pwp-page]');
        if (pageButton) {
            root.querySelector('.pwp-account')?.removeAttribute('open');
            page = pageButton.dataset.pwpPage;
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
            if (page !== 'services' && ['benefits','fields'].includes(mode)) mode = 'read';
            root.querySelectorAll('[data-pwp-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pwpMode === mode)));
            render(); return;
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
            modules.clear(); next.modules.forEach(module => modules.add(module.module_key));
            if (modules.has('reservation')) modules.add('reservations');
            if (!document.getElementById('pwp-dialog')?.open) { render(); say(next.errors.join(' ')); }
        } catch (error) {
            if (serial === refreshSerial) say(error.message || 'Kunne ikke oppdatere forhåndsvisningen. Last siden på nytt.');
        }
    });
    render();
})();
