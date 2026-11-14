(() => {
    'use strict';
    const root = document.getElementById('membership-benefits');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';
    const state = document.getElementById('mb-state');
    const catalog = document.getElementById('mb-catalog');
    const mode = document.getElementById('mb-mode-label');
    const planSelect = document.getElementById('mb-plan-select');
    const showHidden = document.getElementById('mb-show-hidden');
    const dialog = document.getElementById('mb-dialog');
    const editor = dialog.querySelector('.mb-editor');
    const editorPlan = document.getElementById('mb-editor-plan');
    const valueField = document.getElementById('mb-value-field');
    const valueInput = document.getElementById('mb-value');
    const suffix = document.getElementById('mb-value-suffix');
    const memberOnly = document.getElementById('mb-members-only');
    const livePrice = document.getElementById('mb-live-price');
    const draftLabel = document.getElementById('mb-editor-title');
    let payload = null;
    let loaded = false;
    let loading = false;
    let activeItem = null;
    let returnFocus = null;
    let previousOverflow = '';
    let overrides = new Map();
    let accessOverrides = new Map();
    let viewport = 'desktop';
    let demoMode = false;
    const categoryNames = {membership:'Medlemskap',rental:'Utleie',events:'Arrangementer',product:'Produkter',service:'Tjenester'};
    const categoryDescriptions = {membership:'Medlemskap og fordeler.',rental:'Lokaler og andre utleieobjekter.',events:'Dato, sted og påmelding.',product:'Produkter fra klubben.',service:'Tjenester og tilbud.'};
    const safeText = value => String(value == null ? '' : value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const safeUrl = value => {
        const raw = String(value || '').trim();
        if (!raw) return '';
        try {
            const parsed = new URL(raw, window.location.href);
            return ['http:','https:'].includes(parsed.protocol) ? parsed.href : '';
        } catch (_) { return ''; }
    };
    const safeSize = (value,fallback) => {
        const match = String(value || '').trim().match(/^(\d+(?:\.\d+)?)(px|rem|em|vh|vw|%)?$/);
        if (!match || Number(match[1]) > 10000) return fallback;
        return match[1] + (match[2] || 'px');
    };
    const safeFont = value => /^[a-zA-Z0-9 ,'"_-]{1,150}$/.test(String(value || '')) ? String(value) : 'Georgia, serif';
    const safePosition = (value,fallback) => ['left','center','right'].includes(value) ? value : fallback;
    const number = value => Number.isFinite(Number(value)) ? Number(value) : 0;
    const nullableNumber = value => value === null || value === undefined || value === '' || !Number.isFinite(Number(value)) ? null : Number(value);
    const money = value => new Intl.NumberFormat('nb-NO',{style:'currency',currency:'NOK',maximumFractionDigits:2}).format(Math.max(0,number(value)));
    const setState = (kind, message, actions = '') => {
        state.hidden = false;
        catalog.setAttribute('hidden','');
        catalog.style.display = 'none';
        state.className = 'mb-state mb-state-' + kind;
        state.innerHTML = `<div class="mb-state-icon" aria-hidden="true">${kind === 'loading' ? '<div class="mb-skeleton mb-skeleton-title"></div><div class="mb-skeleton mb-skeleton-row"></div>' : ''}</div><strong>${safeText(message)}</strong>${actions ? `<div class="mb-state-actions">${actions}</div>` : ''}`;
    };
    const demoData = () => ({
        success:true,
        company:{name:'Bergsøy Danseklubb',img_url:'/assets/membership-demo-mark.svg',header_bg_color:'#2d5598',header_text_color:'#ffffff',header_height:'72px',logo_height:'38px',company_header_font_size:'18px',border_radius:'8px',font_family:'Georgia, serif',header_layout:'standard',logo_position:'left',nav_position:'center',contact_phone:'',address_street:'',address_zip:'',address_city:'Fosnavåg'},
        plans:[
            {id:1,name:'Bergsøy Medlem',price:500,image_url:'',description:'Medlemskap for danseglede.'},
            {id:2,name:'Familie',price:850,image_url:'',description:'Fordeler for hele familien.'}
        ],
        products:[
            {id:'m1',source_id:'m1',type:'membership',name:'Bergsøy Medlem',description:'Bli med i Bergsøy Danseklubb og få medlemsfordeler gjennom året.',image_url:'',price:500,exclusive:false,membership_prices:[]},
            {id:'r1',source_id:'r1',type:'rental',name:'Høytun Selskapslokale',description:'Nyt arrangementet i hjertet av Fosnavåg. Et innbydende lokale med god plass til gode øyeblikk.',image_url:'/assets/membership-demo-hall.jpg',price:3900,exclusive:false,membership_prices:[{plan_id:1,price:3300,public_display:true},{plan_id:2,price:3100,public_display:true}]},
            {id:'e1',source_id:'e1',type:'events',name:'Julebord for medlemmer / Med julemiddag',description:'Vi inviterer til julebord for alle våre medlemmer.',image_url:'/assets/membership-demo-julebord.jpg',price:650,start_date:'2026-12-05',start_time:'19:00',exclusive:false,membership_prices:[{plan_id:1,price:500,public_display:true},{plan_id:2,price:450,public_display:true}]},
            {id:'e2',source_id:'e2',type:'events',name:'Romjulsdans',description:'Vi inviterer til Romjulsdans på Herøy Kulturhus.',image_url:'',price:400,start_date:'2026-12-28',start_time:'00:00',exclusive:false,membership_prices:[]},
            {id:'s1',source_id:'s1',type:'service',name:'Dansekveld',description:'En sosial kveld med dans, musikk og godt selskap.',image_url:'',price:120,exclusive:false,membership_prices:[]}
        ],
        warnings:[]
    });
    const normalize = data => {
        if (!data || data.success !== true || !Array.isArray(data.plans) || !Array.isArray(data.products) || !data.company || typeof data.company !== 'object') throw new Error('Katalogen hadde et uventet format.');
        return {
            company:data.company,
            plans:data.plans.map(plan => ({...plan,id:String(plan.id),name:String(plan.name || 'Medlemskap'),price:nullableNumber(plan.price)})),
            products:data.products.map((product,index) => ({...product,id:String(product.id ?? product.source_id ?? index),type:categoryNames[product.type] ? product.type : 'product',name:String(product.name || 'Uten navn'),description:String(product.description || ''),price:nullableNumber(product.price),exclusive:product.exclusive === true,membership_prices:Array.isArray(product.membership_prices) ? product.membership_prices : []})),
            warnings:Array.isArray(data.warnings) ? data.warnings.map(String) : []
        };
    };
    async function loadCatalog(force = false) {
        if (loaded && !force) return;
        if (loading) return;
        loading = true;
        setState('loading','Laster tjenestekatalog …');
        mode.textContent = 'Henter katalog';
        try {
            const response = await fetch('/api/get_membership_benefits_preview.php',{method:'GET',credentials:'same-origin',headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error(`Tjenestekatalogen svarte med ${response.status}.`);
            const data = await response.json();
            payload = normalize(data);
            loaded = true;
            demoMode = false;
            overrides = new Map();
            accessOverrides = new Map();
            render();
        } catch (error) {
            loaded = false;
            setState('error',`Kunne ikke hente den faktiske tjenestekatalogen. ${error.message || 'Prøv igjen.'}`,`<button type="button" data-mb-retry>Prøv igjen</button><button type="button" data-mb-demo>Åpne tydelig merket demo</button>`);
            mode.textContent = 'Ingen live data';
            state.querySelector('[data-mb-retry]').addEventListener('click',() => loadCatalog(true));
            state.querySelector('[data-mb-demo]').addEventListener('click',() => {
                payload = normalize(demoData());
                demoMode = true;
                loaded = true;
                overrides = new Map();
                accessOverrides = new Map();
                render();
            });
        } finally { loading = false; }
    }
    const selectedPlan = () => payload?.plans.find(plan => plan.id === planSelect.value) || null;
    const eventMembershipPrice = (product,planId) => (product.membership_prices || []).find(entry => String(entry.plan_id) === String(planId));
    const getRule = (product,planId) => overrides.get(`${product.id}:${planId}`) || null;
    const isExclusive = product => accessOverrides.get(product.id) ?? product.exclusive;
    const effectivePrice = (product,planId) => {
        if (!planId) {
            return isExclusive(product) ? null : product.price;
        }
        const rule = getRule(product,planId);
        if (rule) {
            if (rule.mechanism === 'included') return 0;
            if (rule.mechanism === 'own') return nullableNumber(rule.value);
            if (product.price === null) return null;
            if (rule.mechanism === 'percentage') return product.price * (1 - Math.min(100,Math.max(0,number(rule.value))) / 100);
            if (rule.mechanism === 'fixed') return Math.max(0,product.price - number(rule.value));
        }
        const existing = eventMembershipPrice(product,planId);
        if (existing && existing.price !== null && existing.price !== '') return nullableNumber(existing.price);
        if (isExclusive(product)) return null;
        return product.price;
    };
    const productImage = product => {
        const url = safeUrl(product.image_url);
        return url ? `<img class="mb-card-image" src="${safeText(url)}" alt="" loading="lazy">` : `<div class="mb-card-image mb-image-placeholder" aria-hidden="true"></div>`;
    };
    const formatDate = value => {
        if (!value) return '';
        const date = new Date(`${String(value).slice(0,10)}T12:00:00`);
        return Number.isNaN(date.getTime()) ? String(value) : new Intl.DateTimeFormat('nb-NO',{day:'numeric',month:'short',year:'numeric'}).format(date);
    };
    function renderCard(product) {
        const plan = selectedPlan();
        const price = effectivePrice(product,plan?.id);
        const publicPrice = product.price;
        const eventPrice = plan && eventMembershipPrice(product,plan.id);
        const membersOnly = !!(plan && isExclusive(product));
        const hidden = product.is_visible === false || product.is_public === false;
        const restricted = !!(!plan && isExclusive(product));
        const included = !!(plan && getRule(product,plan.id)?.mechanism === 'included');
        const visibleMemberPrices = !plan && !restricted ? product.membership_prices
            .filter(entry => entry.public_display === true && nullableNumber(entry.price) !== null)
            .map(entry => {
                const planName = payload.plans.find(item => item.id === String(entry.plan_id))?.name || 'Medlem';
                return `<span class="mb-public-member-price">${safeText(planName)} · medlemspris ${money(entry.price)}</span>`;
            }).join('') : '';
        const priceBlock = price === null ? `<span class="mb-card-price">${restricted ? 'Kun for medlemmer' : 'Pris ikke oppgitt'}</span>${visibleMemberPrices}` :
            included ? '<div class="mb-card-price"><span>Medlemsfordel</span><strong>Inkludert</strong></div>' :
            `<div class="mb-card-price"><span>${plan && (eventPrice || getRule(product,plan.id)) ? 'Medlemspris' : 'Pris'}</span>${plan && publicPrice !== null && price < publicPrice ? `<s>${money(publicPrice)}</s>` : ''}<strong>${money(price)}</strong>${membersOnly ? '<span class="mb-card-badge">Medlemsfordel</span>' : ''}</div>${visibleMemberPrices}`;
        const date = product.start_date ? `<span>Dato ${safeText(formatDate(product.start_date))}${product.start_time ? ` kl. ${safeText(product.start_time)}` : ''}</span>` : '';
        const place = product.address || product.location || '';
        const details = [date,place ? `<span>${safeText(place)}</span>` : ''].filter(Boolean).join('');
        return `<article class="mb-card" tabindex="0" role="button" aria-label="Rediger fordel for ${safeText(product.name)}" data-mb-item="${safeText(product.id)}">
            ${productImage(product)}<div class="mb-card-body"><h5 class="mb-card-title">${safeText(product.name)}</h5><p class="mb-card-description">${safeText(product.description || categoryDescriptions[product.type])}</p>
            ${details ? `<div class="mb-card-meta">${details}</div>` : ''}${priceBlock}${hidden ? '<span class="mb-card-badge mb-hidden-badge">Skjult i offentlig visning</span>' : ''}<span class="mb-card-action">Rediger fordel</span></div></article>`;
    }
    function render() {
        if (!loaded || !payload) return;
        state.hidden = true;
        catalog.removeAttribute('hidden');
        catalog.style.display = '';
        mode.textContent = demoMode ? 'DEMO · eksempeldata' : 'Live katalog · kun preview';
        mode.classList.toggle('mb-demo-mode',demoMode);
        const previousPlan = planSelect.value;
        planSelect.innerHTML = `<option value="">Vanlig besøkende</option>${payload.plans.map(plan => `<option value="${safeText(plan.id)}">${safeText(plan.name)}</option>`).join('')}`;
        if (payload.plans.some(plan => plan.id === previousPlan)) planSelect.value = previousPlan;
        editorPlan.innerHTML = payload.plans.map(plan => `<option value="${safeText(plan.id)}">${safeText(plan.name)}</option>`).join('') || '<option value="">Ingen medlemskapsplaner</option>';
        const company = payload.company;
        const logo = safeUrl(company.img_url || company.logo_url);
        const previewProducts = payload.products.filter(product => showHidden.checked || (product.is_visible !== false && product.is_public !== false));
        const featured = previewProducts.find(product => product.type === 'events');
        const featuredHtml = featured ? `<article class="mb-feature" tabindex="0" role="button" aria-label="Rediger fordel for ${safeText(featured.name)}" data-mb-item="${safeText(featured.id)}">${productImage(featured).replace('mb-card-image','mb-feature-image')}<div class="mb-feature-copy"><span class="mb-feature-kicker">SISTE ARRANGEMENT</span><h4>${safeText(featured.name)}</h4><p>${safeText(featured.start_date ? `${formatDate(featured.start_date)}${featured.start_time ? ` kl. ${safeText(featured.start_time)}` : ''}` : featured.description)}</p><span class="mb-small-link">Se arrangementet</span></div></article>` : '';
        const groups = ['membership','rental','events','product','service'].map(type => {
            const products = previewProducts.filter(item => item.type === type);
            if (!products.length) return '';
            return `<section class="mb-group ${type === 'events' || type === 'product' || type === 'service' ? 'mb-group-wide' : ''}" aria-label="${categoryNames[type]}">
                <div class="mb-group-head"><div><h4>${categoryNames[type]}</h4><p>${categoryDescriptions[type]}</p></div><span class="mb-count">${products.length} ${products.length === 1 ? 'tilbud' : 'tilbud'}</span></div>
                <div class="mb-cards">${products.map(renderCard).join('')}</div></section>`;
        }).join('');
        const headerColor = /^#[0-9a-fA-F]{6}$/.test(company.header_bg_color || '') ? company.header_bg_color : '#2d5598';
        const headerTextColor = /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(company.header_text_color || '') ? company.header_text_color : '#ffffff';
        const headerLayout = ['standard','centered','minimal'].includes(company.header_layout) ? company.header_layout : 'standard';
        const logoPosition = safePosition(company.logo_position,headerLayout === 'centered' ? 'center' : 'left');
        const navPosition = safePosition(company.nav_position,'center');
        const headerHeight = safeSize(company.header_height,'80px');
        const logoHeight = safeSize(company.logo_height,'50px');
        const headerFontSize = safeSize(company.company_header_font_size,'20px');
        const profileFont = safeFont(company.font_family);
        const profileRadius = safeSize(company.border_radius,'16px');
        catalog.innerHTML = `<header class="mb-site-top" data-layout="${headerLayout}" data-logo-position="${logoPosition}" style="background:${headerColor};color:${headerTextColor};min-height:${headerHeight};font-size:${headerFontSize}">${logo ? `<img class="mb-site-logo" src="${safeText(logo)}" alt="" style="height:${logoHeight}">` : '<span class="mb-site-monogram" aria-hidden="true">B</span>'}<span class="mb-site-name">${safeText(company.name || 'Klubben')}</span></header>
            <nav class="mb-site-nav" data-position="${navPosition}" aria-label="Forhåndsvis nettstedmeny"><span>Om oss</span><span>Hjem</span><span>Tjenester</span></nav>
            <div class="mb-catalog-inner"><section class="mb-intro"><div><span class="mb-intro-label">${safeText(company.name || 'KLUBBEN')}</span><h3>Tjenester</h3><p>${previewProducts.length} tilbud fra ${safeText(company.name || 'klubben')}.</p></div>${featuredHtml}</section>
            ${groups ? `<div class="mb-groups">${groups}</div>` : '<div class="mb-catalog-empty"><strong>Ingen tjenester i katalogen ennå.</strong><p>Legg til tilbud i tjenestekatalogen for å se dem her.</p></div>'}
            ${payload.warnings.length ? `<div class="mb-warnings"><strong>Noen detaljer mangler i forhåndsvisningen:</strong><br>${payload.warnings.map(safeText).join('<br>')}</div>` : ''}
            </div>`;
        catalog.style.setProperty('--mb-site-font',profileFont);
        catalog.style.setProperty('--mb-site-radius',profileRadius);
        catalog.classList.toggle('mb-phone-view',viewport === 'phone');
        catalog.querySelectorAll('[data-mb-item]').forEach(card => {
            card.addEventListener('click',() => openEditor(card.dataset.mbItem,card));
            card.addEventListener('keydown',event => {
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openEditor(card.dataset.mbItem,card); }
            });
        });
    }
    const mechanism = () => dialog.querySelector('input[name="mb-mechanism"]:checked')?.value || 'own';
    const draftPrice = () => {
        const product = activeItem;
        if (!product) return null;
        const how = mechanism(), val = nullableNumber(valueInput.value);
        if (how === 'included') return 0;
        if (val === null) return null;
        if (how === 'own') return val;
        if (product.price === null) return null;
        if (how === 'percentage') return product.price * (1 - Math.min(100,Math.max(0,val)) / 100);
        return Math.max(0,product.price - val);
    };
    function updateEditorPreview() {
        const how = mechanism();
        valueField.hidden = how === 'included';
        valueInput.required = how !== 'included';
        valueInput.max = how === 'percentage' ? '100' : '';
        valueField.querySelector('label').textContent = how === 'percentage' ? 'Rabatt i prosent' : how === 'fixed' ? 'Avslag i kroner' : 'Medlemspris';
        document.getElementById('mb-apply').disabled = !editorPlan.value;
        suffix.textContent = how === 'percentage' ? '%' : 'kr';
        const price = draftPrice();
        livePrice.textContent = mechanism() === 'included' ? 'Inkludert' : price === null ? 'Pris ikke oppgitt' : money(price);
    }
    function openEditor(id,card) {
        activeItem = payload.products.find(product => product.id === id);
        if (!activeItem) return;
        returnFocus = card;
        const planId = selectedPlan()?.id || payload.plans[0]?.id || '';
        editorPlan.value = planId;
        const rule = getRule(activeItem,planId);
        const existing = eventMembershipPrice(activeItem,planId);
        let how = rule?.mechanism || (existing ? 'own' : 'own');
        let initialValue = rule?.value;
        if (initialValue === undefined && existing) initialValue = existing.price;
        if (initialValue === undefined && how === 'own') initialValue = activeItem.price;
        if (initialValue === undefined) initialValue = '';
        draftLabel.textContent = activeItem.name;
        dialog.querySelectorAll('input[name="mb-mechanism"]').forEach(input => { input.checked = input.value === how; });
        valueInput.value = initialValue === null ? '' : String(initialValue);
        memberOnly.checked = isExclusive(activeItem);
        updateEditorPreview();
        dialog.hidden = false;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        editor.focus();
    }
    function closeEditor(restoreFocus = true) {
        dialog.hidden = true;
        document.body.style.overflow = previousOverflow;
        activeItem = null;
        if (restoreFocus) {
            const target = returnFocus?.isConnected ? returnFocus : catalog.querySelector(`[data-mb-item="${CSS.escape(returnFocus?.dataset.mbItem || '')}"]`);
            target?.focus();
        }
    }
    dialog.querySelectorAll('[data-mb-close]').forEach(button => button.addEventListener('click',closeEditor));
    dialog.addEventListener('click',event => { if (event.target === dialog) closeEditor(); });
    document.addEventListener('keydown',event => {
        if (dialog.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); closeEditor(); }
        if (event.key === 'Tab') {
            const focusable = [...editor.querySelectorAll('button:not([disabled]),input:not([disabled]),select:not([disabled])')].filter(element => element.offsetParent !== null);
            if (!focusable.length) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    dialog.querySelectorAll('input[name="mb-mechanism"]').forEach(input => input.addEventListener('change',updateEditorPreview));
    valueInput.addEventListener('input',updateEditorPreview);
    memberOnly.addEventListener('change',updateEditorPreview);
    editorPlan.addEventListener('change',() => {
        const current = getRule(activeItem,editorPlan.value);
        const selectedPrice = current?.value ?? eventMembershipPrice(activeItem,editorPlan.value)?.price ?? activeItem.price;
        valueInput.value = selectedPrice === null || selectedPrice === undefined ? '' : String(selectedPrice);
        memberOnly.checked = isExclusive(activeItem);
        dialog.querySelectorAll('input[name="mb-mechanism"]').forEach(input => { input.checked = input.value === (current?.mechanism || (eventMembershipPrice(activeItem,editorPlan.value) ? 'own' : 'own')); });
        updateEditorPreview();
    });
    document.getElementById('mb-apply').addEventListener('click',() => {
        if (!activeItem || !editorPlan.value) return;
        if (mechanism() !== 'included' && !valueInput.reportValidity()) return;
        overrides.set(`${activeItem.id}:${editorPlan.value}`,{mechanism:mechanism(),value:nullableNumber(valueInput.value)});
        accessOverrides.set(activeItem.id, memberOnly.checked);
        render();
        closeEditor();
    });
    document.getElementById('mb-reset-item').addEventListener('click',() => {
        if (!activeItem) return;
        overrides.delete(`${activeItem.id}:${editorPlan.value}`);
        accessOverrides.delete(activeItem.id);
        render();
        closeEditor();
    });
    planSelect.addEventListener('change',render);
    showHidden.addEventListener('change',render);
    document.querySelectorAll('[data-mb-viewport]').forEach(button => button.addEventListener('click',() => {
        viewport = button.dataset.mbViewport === 'phone' ? 'phone' : 'desktop';
        document.querySelectorAll('[data-mb-viewport]').forEach(item => item.setAttribute('aria-pressed',String(item === button)));
        catalog.classList.toggle('mb-phone-view',viewport === 'phone');
    }));
    const maybeLoad = event => {
        if (event?.detail?.module === 'membership' && event.detail.subtab === 'membership-benefits') loadCatalog();
        else {
            overrides.clear();
            accessOverrides.clear();
            showHidden.checked = false;
            if (!dialog.hidden) closeEditor(false);
            if (loaded) render();
        }
    };
    document.addEventListener('subtab-changed',maybeLoad);
    document.addEventListener('click',event => {
        const link = event.target.closest('a[href="#membership-benefits"]');
        if (link) loadCatalog();
    });
    window.addEventListener('hashchange',() => { if (window.location.hash === '#membership-benefits') loadCatalog(); });
    if (window.location.hash === '#membership-benefits') loadCatalog();
})();
