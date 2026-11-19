(() => {
    'use strict';
    const root = document.getElementById('portal-website-preview');
    if (!root) return;
    const data = JSON.parse(document.getElementById('pwp-data').textContent);
    const company = data.company || {};
    const modules = new Set(data.modules.map(module => module.module_key));
    const content = document.getElementById('pwp-content');
    // Notifications must remain visible even while the legacy workspace is hidden.
    const toastContainer = document.getElementById('toastContainer');
    if (toastContainer) document.body.appendChild(toastContainer);
    const menu = document.getElementById('pwp-create-menu');
    const newButton = document.getElementById('pwp-new');
    let page = 'home', mode = 'read', previousScroll = 0;
    let clearMemberScope = () => {};
    function clearCreationSurface() {
        document.body.classList.remove('pwp-creation-open');
        document.querySelectorAll('[data-pwp-create-path], [data-pwp-create-surface]').forEach(element => {
            if (element.hasAttribute('data-pwp-create-surface')) {
                element.removeAttribute('role');
                element.removeAttribute('aria-modal');
            }
            element.removeAttribute('data-pwp-create-path');
            element.removeAttribute('data-pwp-create-surface');
        });
    }
    function showCreationSurface(element) {
        if (!element) return;
        element.setAttribute('data-pwp-create-surface', '');
        element.setAttribute('role', 'dialog');
        element.setAttribute('aria-modal', 'false');
        for (let parent = element.parentElement; parent; parent = parent.parentElement) {
            parent.setAttribute('data-pwp-create-path', '');
            if (parent.id === 'portal-legacy') break;
        }
        document.body.classList.add('pwp-creation-open');
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
        const button = document.querySelector(`[data-bs-target="#content-${key}"]`);
        if (!button) { say('Modulen er ikke tilgjengelig i denne portalen.'); return false; }
        clearMemberScope();
        clearCreationSurface();
        previousScroll = scrollY;
        document.body.classList.add('pwp-workspace-open');
        document.getElementById('pwp-workspace-bar').hidden = false;
        bootstrap.Tab.getOrCreateInstance(button).show();
        window.scrollTo(0, 0);
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
        if (key === 'rental' && typeof window.openRentalCreate === 'function') {
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
        if (inlineForms[key]) showCreationSurface(document.querySelector(inlineForms[key]));
    }
    function render() {
        document.querySelectorAll('[data-pwp-page]').forEach(button => {
            if (button.dataset.pwpPage === page) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        root.querySelector('[data-pwp-mode="benefits"]')?.toggleAttribute('hidden', page !== 'services');
        newButton.hidden = page === 'about';
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
            content.innerHTML = '<h1>Administrasjon</h1><div class="pwp-grid">' +
                data.modules.map(module => `<button type="button" class="pwp-card" data-open-module="${esc(module.module_key)}"><span class="pwp-card-body"><h3>${esc(module.name)}</h3><p>Åpne eksisterende administrasjon</p></span></button>`).join('') +
                '<button type="button" class="pwp-card" data-open-module="announcements"><span class="pwp-card-body"><h3>Nyhetsadministrasjon</h3></span></button></div>';
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
            page = pageButton.dataset.pwpPage;
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
            if (page !== 'services' && mode === 'benefits') mode = 'read';
            root.querySelectorAll('[data-pwp-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pwpMode === mode)));
            render(); return;
        }
        const modeButton = event.target.closest('[data-pwp-mode]');
        if (modeButton) {
            mode = modeButton.dataset.pwpMode;
            root.querySelectorAll('[data-pwp-mode]').forEach(button => button.setAttribute('aria-pressed', String(button === modeButton)));
            return; // Do not rerender or change the preview on mode selection.
        }
        const moduleButton = event.target.closest('[data-open-module]');
        if (moduleButton) { openWorkspace(moduleButton.dataset.openModule); return; }
        const story = event.target.closest('[data-news-id]');
        if (story && openWorkspace('announcements')) {
            const id = story.dataset.newsId;
            const card = [...document.querySelectorAll('#news-home-preview [data-edit-story], #news-edit-cards [data-edit-story]')].find(item => item.dataset.editStory === id);
            card?.click(); return;
        }
        const service = event.target.closest('[data-service-id]');
        if (service) {
            const item = data.products.find(product => product.id === service.dataset.serviceId);
            if (mode === 'benefits' && modules.has('membership')) {
                if (openWorkspace('membership')) document.querySelector('[href="#membership-benefits"]')?.click();
            } else if (item?.module === 'membership' && mode === 'read') {
                openPlanMembers(item);
            } else if (item) openWorkspace(item.module);
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
        if (event.key === 'Escape') { menu.hidden = true; newButton.setAttribute('aria-expanded', 'false'); }
    });
    document.addEventListener('click', event => {
        if (!menu.contains(event.target) && !newButton.contains(event.target)) {
            menu.hidden = true; newButton.setAttribute('aria-expanded', 'false');
        }
    });
    let refreshSerial = 0;
    document.getElementById('pwp-close-workspace').addEventListener('click', async () => {
        if (document.querySelector('.modal.show')) return;
        clearMemberScope();
        clearCreationSurface();
        // The same DOM is kept mounted, so unsaved drafts are never discarded.
        document.body.classList.remove('pwp-workspace-open');
        document.getElementById('pwp-workspace-bar').hidden = true;
        window.scrollTo(0, previousScroll);
        const serial = ++refreshSerial;
        try {
            const response = await fetch('/api/get_portal_website_preview.php', {credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}});
            const next = await response.json();
            if (!response.ok || !next.success) throw new Error(next.message || 'Kunne ikke oppdatere forhåndsvisningen.');
            if (serial !== refreshSerial) return;
            Object.assign(data, next);
            modules.clear(); next.modules.forEach(module => modules.add(module.module_key));
            if (!document.body.classList.contains('pwp-workspace-open')) { render(); say(next.errors.join(' ')); }
        } catch (error) {
            if (serial === refreshSerial) say(error.message || 'Kunne ikke oppdatere forhåndsvisningen. Last siden på nytt.');
        }
    });
    render();
})();
