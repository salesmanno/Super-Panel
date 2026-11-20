/* The preview owns the presentation. Original forms retain their handlers and APIs. */
(() => {
    'use strict';
    if (!document.body.classList.contains('portal-wpreview')) return;
    const dialog = document.getElementById('pwp-dialog');
    if (!dialog) return;
    const body = document.getElementById('pwp-dialog-body');
    let mounted = null, aboutMounted = null, opener = null, beforeClose = null, afterClose = null;
    let settingRevision = 0;
    const restore = record => {
        if (!record) return;
        record.element.className = record.className;
        record.element.style.cssText = record.style;
        record.element.hidden = record.hidden;
        record.marker.replaceWith(record.element);
    };
    function mount(element, host) {
        const marker = document.createComment('preview surface home');
        const record = {element, marker, className:element.className, style:element.style.cssText, hidden:element.hidden};
        element.before(marker);
        host.appendChild(element);
        element.hidden = false;
        element.style.display = 'block';
        element.classList.add('pwp-mounted-surface', 'show', 'active');
        return record;
    }
    function close(force = false) {
        if (!dialog.open && !mounted) return true;
        if (!force && beforeClose && !beforeClose()) return false;
        beforeClose = null;
        restore(mounted); mounted = null;
        const cleanup = afterClose; afterClose = null;
        cleanup?.();
        dialog.close();
        body.replaceChildren();
        document.body.classList.remove('pwp-dialog-open');
        opener?.focus({preventScroll:true});
        document.dispatchEvent(new CustomEvent('portal:surface-closed'));
        return true;
    }
    function show(element, title, options = {}) {
        if (!element || !close()) return false;
        opener = document.activeElement;
        beforeClose = options.beforeClose || null;
        afterClose = options.onClose || null;
        document.getElementById('pwp-dialog-title').textContent = title;
        const wrapper = document.createElement('div');
        wrapper.className = options.className || '';
        body.appendChild(wrapper);
        mounted = mount(element, wrapper);
        dialog.show(); // Non-blocking: the customer-side body remains usable.
        document.body.classList.add('pwp-dialog-open');
        document.getElementById('pwp-dialog-close').focus({preventScroll:true});
        window.dispatchEvent(new Event('resize'));
        return true;
    }
    function showTemporary(element, title, options = {}) {
        const home = document.createElement('div');
        home.hidden = true; document.body.appendChild(home); home.appendChild(element);
        const opened = show(element, title, {...options,onClose:()=>{options.onClose?.();home.remove();}});
        if (!opened) home.remove();
        return opened;
    }
    function clearAbout() {
        settingRevision++;
        restore(aboutMounted); aboutMounted = null;
    }
    function adoptModals() {
        // Bootstrap portals must not be hidden by the legacy root or a mounted tab.
        document.querySelectorAll('#portal-legacy .modal').forEach(modal => {
            modal.classList.add('pwp-module-modal');
            document.body.appendChild(modal);
        });
    }
    function moduleButton(key) {
        const aliases = {reservations:'reservation', email:document.querySelector('[data-bs-target="#content-email"]')?'email':'webmail'};
        return document.querySelector(`[data-bs-target="#content-${key}"]`)
            || document.querySelector(`[data-bs-target="#content-${aliases[key] || key}"]`);
    }
    function activate(key) {
        const button = moduleButton(key);
        if (!button) return null;
        bootstrap.Tab.getOrCreateInstance(button).show();
        adoptModals();
        return document.querySelector(button.dataset.bsTarget);
    }
    function tabsFor(key) {
        const slug = key === 'reservations' ? 'reservation' : key === 'webmail' ? 'email' : key;
        return [...document.querySelectorAll(`#subtabs-${slug} .nav-link`)]
            .filter(link => !link.closest('[hidden]') && getComputedStyle(link).display !== 'none');
    }
    function moduleSurface(key, title, host) {
        const element = activate(key);
        if (!element) return false;
        if (host) {
            restore(aboutMounted);
            aboutMounted = mount(element, host);
        } else if (!show(element, title)) return false;
        const links = tabsFor(key);
        if (links.length) {
            const nav = document.createElement('nav');
            nav.className = 'pwp-surface-tabs';
            nav.setAttribute('aria-label', title);
            links.forEach(link => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = link.textContent.trim();
                button.addEventListener('click', () => {
                    link.click();
                    nav.querySelectorAll('button').forEach(other => other.setAttribute('aria-pressed', String(other === button)));
                });
                nav.appendChild(button);
            });
            (host || body).prepend(nav);
            nav.querySelector('button')?.click();
        }
        return true;
    }
    async function settings(key, host) {
        clearAbout();
        const revision = settingRevision;
        host.replaceChildren();
        if (key === 'access') {
            const description = document.createElement('p');
            description.textContent = 'Administrer bedriftens ansattknyttede brukere og delegerte modultilganger.';
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-primary';
            button.textContent = 'Administrer firmabrukere';
            button.addEventListener('click', () => {
                const modal = document.getElementById('company-users-modal');
                if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
            });
            host.append(description, button);
            return;
        }
        if (key === 'email' || key === 'economy') {
            if (!moduleSurface(key, key === 'email' ? 'E-post' : 'Økonomi', host)) host.textContent = 'Modulen er ikke tilgjengelig.';
            return;
        }
        const loading = document.createElement('p');
        loading.textContent = 'Henter bedriftsinnstillinger …';
        host.appendChild(loading);
        try {
            const response = await fetch('/api/load_ditt_nettsted_content.php', {credentials:'same-origin',cache:'no-store'});
            if (revision !== settingRevision) return;
            if (response.status === 403) {
                loading.textContent = 'Lås opp bedriftsinnstillingene med administratorpassordet. Vanlig tilgangskontroll gjelder.';
                const passwordForm = document.getElementById('dittNettstedPasswordForm');
                if (passwordForm) aboutMounted = mount(passwordForm, host);
                return;
            }
            if (!response.ok) throw new Error('Kunne ikke hente bedriftsinnstillinger. Prøv igjen.');
            const html = await response.text();
            if (revision !== settingRevision) return;
            const originalHost = document.getElementById('firmaModalTabContent');
            originalHost.innerHTML = html;
            const target = {company:'firma-firmadata', staff:'firma-staff', website:'firma-webside', access:'firma-modules', about:'firma-about'}[key];
            const pane = document.getElementById(target);
            if (!pane) throw new Error('Denne innstillingen er ikke tilgjengelig.');
            host.replaceChildren();
            aboutMounted = mount(pane, host);
            // Preserve the same explicit unlock before rendering any protected fields.
            const passwordForm = document.getElementById('dittNettstedPasswordForm');
            if (passwordForm) passwordForm.style.display = 'block';
            document.dispatchEvent(new CustomEvent('portal:settings-mounted', {detail:{key}}));
        } catch (error) {
            if (revision !== settingRevision) return;
            host.textContent = error.message;
            const retry = document.createElement('button');
            retry.type = 'button'; retry.textContent = 'Prøv igjen';
            retry.addEventListener('click', () => settings(key, host));
            host.appendChild(retry);
        }
    }
    document.getElementById('pwp-dialog-close').addEventListener('click', () => close());
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && dialog.open && !document.querySelector('.modal.show')) {
            event.preventDefault(); close();
        }
    });
    adoptModals();
    // Modules can load modals lazily. Keep them outside hidden navigation.
    new MutationObserver(adoptModals).observe(document.getElementById('portal-legacy'), {childList:true,subtree:true});
    window.PortalWorkspace = {show, showTemporary, close, activate, moduleSurface, settings, clearAbout, adoptModals};
})();
