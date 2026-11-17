(() => {
    'use strict';

    const root = document.getElementById('sales-online-offers');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';

    const stateEl = root.querySelector('#soo-state');
    const previewEl = root.querySelector('#soo-preview');
    const liveMark = root.querySelector('#soo-live-mark');
    const warningEl = root.querySelector('#soo-company-warning');
    const showHidden = root.querySelector('#soo-show-hidden');
    const viewportButtons = [...root.querySelectorAll('[data-soo-viewport]')];
    let company = null;
    let companyReady = false;
    let products = [];
    let dataReady = false;
    let loadInFlight = false;
    let viewport = 'desktop';
    let requestSerial = 0;

    const esc = value => String(value == null ? '' : value).replace(/[&<>"']/g, char =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const safeUrl = value => {
        const raw = String(value || '').trim();
        if (!raw) return '';
        try {
            const parsed = new URL(raw, window.location.href);
            return ['http:', 'https:'].includes(parsed.protocol) ? parsed.href : '';
        } catch (_) { return ''; }
    };
    const safeSize = (value, fallback) => {
        const match = String(value || '').trim().match(/^(\d+(?:\.\d+)?)(px|rem|em|vh|vw|%)?$/);
        if (!match || Number(match[1]) > 10000) return fallback;
        return match[1] + (match[2] || 'px');
    };
    const safeFont = value => /^[a-zA-Z0-9 ,'"_-]{1,150}$/.test(String(value || '')) ? String(value) : 'Georgia, serif';
    const validColor = (value, fallback) => /^#[0-9a-fA-F]{6}$/.test(String(value || '')) ? value : fallback;
    const bool = value => value === true || value === 1 || value === '1';
    const money = value => new Intl.NumberFormat('nb-NO', {
        style: 'currency', currency: 'NOK', maximumFractionDigits: 2
    }).format(Math.max(0, Number(value) || 0));
    const effectivePrice = product => {
        const minor = product.price_minor == null || product.price_minor === '' ? NaN : Number(product.price_minor);
        if (Number.isFinite(minor) && minor >= 0) return minor / 100;
        return Math.max(0, Number(product.selling_price) || 0);
    };

    function setState(kind, message, actions = '') {
        previewEl.hidden = true;
        stateEl.hidden = false;
        stateEl.className = `soo-state soo-state-${kind}`;
        stateEl.innerHTML = `${kind === 'loading' ? '<div class="soo-skeleton soo-skeleton-head"></div><div class="soo-skeleton soo-skeleton-card"></div>' : `<span class="soo-state-symbol" aria-hidden="true">${kind === 'error' ? '!' : '·'}</span>`}<strong>${esc(message)}</strong>${actions ? `<div class="soo-state-actions">${actions}</div>` : ''}`;
        stateEl.querySelector('[data-soo-retry]')?.addEventListener('click', () => load(true));
        liveMark.textContent = kind === 'error' ? 'Ingen live data' : kind === 'empty' ? 'Tom varevisning' : 'Henter forhåndsvisning';
    }

    function getSnapshot() {
        const bridge = window.SalesOnline;
        if (!bridge || typeof bridge.snapshot !== 'function') return {products: [], ready: false, error: ''};
        try {
            const snapshot = bridge.snapshot();
            return {
                products: Array.isArray(snapshot?.products) ? snapshot.products : [],
                ready: snapshot?.ready === true,
                loading: snapshot?.loading === true,
                error: String(snapshot?.error || '')
            };
        } catch (error) {
            return {products: [], ready: false, loading: false, error: error?.message || 'Produktdata kunne ikke leses.'};
        }
    }

    function refreshProducts() {
        const snapshot = getSnapshot();
        root.querySelector('#soo-create').disabled = !snapshot.ready;
        products = snapshot.products;
        dataReady = snapshot.ready;
        if (!companyReady) return;
        render();
        if (snapshot.error && !dataReady) {
            setState('error', snapshot.error, '<button type="button" data-soo-retry>Prøv igjen</button>');
        } else if (companyReady && !dataReady && !snapshot.loading) {
            setState('error', 'Produktlisten er ikke tilgjengelig ennå.', '<button type="button" data-soo-retry>Prøv igjen</button>');
        }
    }

    function productCard(product) {
        const image = safeUrl(product.img_product);
        const active = bool(product.is_active);
        const visibility = String(product.visibility || 'kiosk');
        const publicVisibility = ['online', 'both'].includes(visibility);
        const stock = product.stock_quantity === null || product.stock_quantity === undefined || product.stock_quantity === ''
            ? '' : `${Math.max(0, Number(product.stock_quantity) || 0)} på lager`;
        const price = effectivePrice(product);
        const list = Math.max(0, Number(product.selling_price) || 0);
        const discounted = Number.isFinite(Number(product.price_minor)) && price < list;
        const privateLabel = visibility === 'kiosk' ? 'Kasse og kiosk' : visibility === 'both' ? 'Nett og kiosk' : 'Kun nett';
        const inactiveLabel = active ? '' : '<span class="soo-card-state">Inaktiv · vises ikke offentlig</span>';
        const visibilityLabel = publicVisibility ? (visibility === 'both' ? 'Nett og kiosk' : 'Nett') : 'Kiosk · ikke offentlig';
        return `<button class="soo-product${active && publicVisibility ? '' : ' is-nonpublic'}" type="button" data-soo-product="${esc(product.id)}" aria-label="Rediger ${esc(product.name || 'vare')}">
            <span class="soo-product-image">${image ? `<img src="${esc(image)}" alt="" loading="lazy">` : '<span class="soo-image-placeholder" aria-hidden="true"><span></span></span>'}
                <span class="soo-image-tag">${esc(privateLabel)}</span></span>
            <span class="soo-product-body">
                <span class="soo-product-eyebrow">${esc(visibilityLabel)}</span>
                <strong class="soo-product-name">${esc(product.name || 'Uten navn')}</strong>
                ${product.description ? `<span class="soo-product-description">${esc(product.description)}</span>` : ''}
                <span class="soo-product-price">${discounted ? `<s>${esc(money(list))}</s>` : ''}<strong>${esc(money(price))}</strong></span>
                ${stock ? `<span class="soo-product-stock">${esc(stock)}</span>` : ''}
                ${inactiveLabel}
            </span>
        </button>`;
    }

    function render() {
        if (!dataReady) {
            setState('loading', 'Laster varer …');
            return;
        }
        const primary = products.filter(product => bool(product.is_active) && ['online', 'both'].includes(String(product.visibility || 'kiosk')));
        const visible = showHidden.checked
            ? products
            : primary;
        if (!products.length) {
            setState('empty', 'Ingen varer registrert ennå.', '<span>Opprett en vare for å starte varetilbudet.</span>');
            liveMark.textContent = 'Ingen varer';
            return;
        }
        stateEl.hidden = true;
        previewEl.hidden = false;
        liveMark.textContent = `${primary.length} aktive nettvarer`;
        const companyData = company || {};
        const logo = safeUrl(companyData.img_url || companyData.logo_url);
        const headerColor = validColor(companyData.header_bg_color, '#2d5598');
        const textColor = /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(companyData.header_text_color || '')
            ? companyData.header_text_color : '#ffffff';
        const layout = ['standard', 'centered', 'minimal'].includes(companyData.header_layout) ? companyData.header_layout : 'standard';
        const logoPosition = ['left', 'center', 'right'].includes(companyData.logo_position) ? companyData.logo_position : (layout === 'centered' ? 'center' : 'left');
        const navPosition = ['left', 'center', 'right'].includes(companyData.nav_position) ? companyData.nav_position : 'center';
        const name = companyData.name || 'Klubben';
        const headerHeight = safeSize(companyData.header_height, '72px');
        const logoHeight = safeSize(companyData.logo_height, '38px');
        const headerFontSize = safeSize(companyData.company_header_font_size, '18px');
        previewEl.style.setProperty('--soo-site-font', safeFont(companyData.font_family));
        previewEl.style.setProperty('--soo-site-radius', safeSize(companyData.border_radius, '8px'));
        previewEl.style.setProperty('--soo-brand', headerColor);
        previewEl.innerHTML = `<header class="soo-site-header" data-layout="${layout}" data-logo-position="${logoPosition}" style="background:${headerColor};color:${textColor};min-height:${headerHeight};font-size:${headerFontSize}">
                ${logo ? `<img src="${esc(logo)}" class="soo-site-logo" alt="" style="height:${logoHeight}">` : ''}
                <span class="soo-site-name">${esc(name)}</span>
                <span class="soo-site-admin-tag">ADMINISTRATOR · FORHÅNDSVISNING</span>
            </header>
            <nav class="soo-site-nav" data-position="${navPosition}" aria-label="Simulert nettmeny"><span>Hjem</span><span>Om klubben</span><span class="is-current">Varetilbud</span></nav>
            <div class="soo-catalog-inner">
                <div class="soo-catalog-intro"><div><span class="soo-section-label">${esc(name)}</span><h3>Varetilbud</h3><p>Varer fra klubben, presentert i den merkede tjenesten.</p></div><span class="soo-admin-stamp">Administrativ visning</span></div>
                ${visible.length ? `<div class="soo-product-grid">${visible.map(productCard).join('')}</div>` : `<div class="soo-filter-empty"><strong>Ingen nettvarer å vise.</strong><span>${showHidden.checked ? 'Ingen produkter er tilgjengelige.' : 'Vis kioskvarer og inaktive varer for å redigere øvrige produkter.'}</span></div>`}
                <footer class="soo-catalog-foot"><span>Forhåndsvisning for administrator</span><span>${visible.length} ${visible.length === 1 ? 'vare' : 'varer'}</span></footer>
            </div>`;
        previewEl.classList.toggle('soo-phone-view', viewport === 'phone');
        previewEl.querySelectorAll('[data-soo-product]').forEach(card => card.addEventListener('click', () => {
            const id = card.dataset.sooProduct;
            if (typeof window.SalesOnline?.edit === 'function') window.SalesOnline.edit(id);
        }));
    }

    async function load(force = false) {
        if (loadInFlight) return;
        if (dataReady && !force) {
            document.dispatchEvent(new CustomEvent('sales:online-load'));
            refreshProducts();
            return;
        }
        loadInFlight = true;
        const serial = ++requestSerial;
        setState('loading', 'Henter klubbens varetilbud …');
        warningEl.hidden = true;
        try {
            const response = await fetch('/api/sales.php?action=online_preview', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {Accept: 'application/json'}
            });
            let payload;
            try { payload = await response.json(); } catch (_) { throw new Error('Serveren svarte med et ugyldig svar.'); }
            if (!response.ok || payload?.success !== true || !payload.company || typeof payload.company !== 'object') {
                throw new Error(payload?.message || `Kunne ikke hente forhåndsvisningen${response.ok ? '.' : ` (${response.status}).`}`);
            }
            if (serial !== requestSerial) return;
            company = payload.company;
            companyReady = true;
            const warnings = Array.isArray(payload.warnings) ? payload.warnings.map(String) : [];
            if (warnings.length) {
                warningEl.hidden = false;
                warningEl.innerHTML = `<strong>Merk:</strong> ${warnings.map(esc).join('<br>')}`;
            }
            document.dispatchEvent(new CustomEvent('sales:online-load'));
            refreshProducts();
        } catch (error) {
            if (serial !== requestSerial) return;
            setState('error', error?.message || 'Forhåndsvisningen kunne ikke lastes.', '<button type="button" data-soo-retry>Prøv igjen</button>');
        } finally {
            loadInFlight = false;
        }
    }

    function isVisible() {
        if (root.style.display === 'none' || root.hidden) return false;
        return root.getClientRects().length > 0 || root.offsetParent !== null;
    }
    function onOpen() {
        if (!isVisible()) return;
        document.dispatchEvent(new CustomEvent('sales:online-load'));
        refreshProducts();
        load(true);
    }

    root.querySelector('#soo-create').addEventListener('click', () => {
        if (typeof window.SalesOnline?.create === 'function') window.SalesOnline.create();
    });
    showHidden.addEventListener('change', render);
    viewportButtons.forEach(button => button.addEventListener('click', () => {
        viewport = button.dataset.sooViewport;
        viewportButtons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        render();
    }));
    document.addEventListener('sales:products-updated', refreshProducts);
    document.addEventListener('click', event => {
        if (event.target.closest?.('a[href="#sales-online-offers"]')) setTimeout(onOpen, 0);
    });
    document.addEventListener('moduleSubtabChanged', onOpen);
    document.addEventListener('subtab-changed', event => {
        const detail = event.detail || {};
        if (detail.module === 'sales' && detail.subtab === 'sales-online-offers') onOpen();
    });
    window.addEventListener('hashchange', onOpen);
    if (isVisible()) onOpen();
    else setState('loading', 'Åpne Varetilbud nett for å laste forhåndsvisningen.');
})();
