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
    const livePrice = document.getElementById('mb-live-price');
    const draftLabel = document.getElementById('mb-editor-title');
    let payload = null;
    let loaded = false;
    let loading = false;
    let activeItem = null;
    let returnFocus = null;
    let previousOverflow = '';
    let overrides = new Map();
    let viewport = 'desktop';
    let demoMode = false;
    let editorPlanId = '';
    const editorDrafts = new Map();
    const rentalMode = document.getElementById('mb-rental-mode');
    const startDate = document.getElementById('mb-start-date');
    const endDate = document.getElementById('mb-end-date');
    const ruleStatus = document.getElementById('mb-rule-status');
    const cents = value => Math.round(number(value) * 100);
    const pricing = () => window.MembershipBenefitPricing;
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
            {id:'m1',type:'membership',name:'Bergsøy Medlem',description:'Medlemskap for danseglede.',price:500,assigned_benefits:[]},
            {id:'r1',type:'rental',name:'Høytun Selskapslokale',description:'Et innbydende lokale med plass til gode øyeblikk.',image_url:'/assets/membership-demo-hall.jpg',rental_rates:{rental_price:1250,weekday_prices_enabled:true,weekday_prices:{monday:1050,tuesday:1050,wednesday:1050,thursday:1050,friday:1400},weekend_price:3200,deposit_amount:1800},assigned_benefits:[]},
            {id:'e1',type:'events',name:'Julebord med julemiddag',description:'En kveld for alle våre medlemmer.',image_url:'/assets/membership-demo-julebord.jpg',price:650,start_date:'2026-12-05',start_time:'19:00',member_exclusive:true,assigned_benefits:[{plan_id:1,benefit_type:'amount_discount',benefit_value:125,price_basis:'unit'}]},
            {id:'e2',type:'events',name:'Romjulsdans',description:'Dans på Herøy Kulturhus.',price:400,start_date:'2026-12-28',assigned_benefits:[]},
            {id:'s1',type:'service',name:'Dansekveld',description:'En sosial kveld med dans og musikk.',price:120,assigned_benefits:[]}
        ],
        warnings:[]
    });
    const normalize = data => {
        if (!data || data.success !== true || !Array.isArray(data.plans) || !Array.isArray(data.products) || !data.company || typeof data.company !== 'object') throw new Error('Katalogen hadde et uventet format.');
        return {
            company:data.company,
            plans:data.plans.map(plan => ({...plan,id:String(plan.id),name:String(plan.plan_name || plan.name || 'Medlemskap'),price:nullableNumber(plan.price)})),
            products:data.products.map((product,index) => ({...product,registration_type:product.registration_type || 'none',id:String(product.id ?? product.source_id ?? index),type:categoryNames[product.type] ? product.type : 'product',name:String(product.name || 'Uten navn'),description:String(product.description || ''),price:nullableNumber(product.price),exclusive:product.member_exclusive === true || product.member_exclusive === 1 || product.member_exclusive === '1' || product.exclusive === true,assigned_benefits:Array.isArray(product.assigned_benefits) ? product.assigned_benefits.map(benefit => ({...benefit,plan_id:String(benefit.plan_id ?? ''),benefit_type:String(benefit.benefit_type || ''),benefit_value:nullableNumber(benefit.benefit_value),price_basis:String(benefit.price_basis || 'unit'),public_display:benefit.public_display === true ? true : benefit.public_display === false ? false : null})).filter(benefit => benefit.plan_id && ['access','fixed_price','percent_discount','amount_discount','included'].includes(benefit.benefit_type)) : []})),
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
            render();
        } catch (error) {
            loaded = false;
            setState('error',`Kunne ikke hente den faktiske tjenestekatalogen. ${error.message || 'Prøv igjen.'}`,`<button type="button" data-mb-retry>Prøv igjen</button><button type="button" data-mb-demo>Åpne tydelig merket demo</button>`);
            mode.textContent = 'Ingen live data';
            state.querySelector('[data-mb-retry]').addEventListener('click',() => loadCatalog(true));
            state.querySelector('[data-mb-demo]').addEventListener('click',() => {
                const demo = demoData();
                demo.products.filter(product => product.type === 'events').forEach(product => { product.registration_type = 'signup'; });
                payload = normalize(demo);
                demoMode = true;
                loaded = true;
                overrides = new Map();
                render();
            });
        } finally { loading = false; }
    }
    const selectedPlan = () => payload?.plans.find(plan => plan.id === planSelect.value) || null;
    const benefitEligible = product => product.type !== 'membership' && product.benefit_eligible !== false
        && (product.type !== 'events' || ['signup','binding'].includes(product.registration_type));
    const savedRule = (product,planId) => product.assigned_benefits.find(rule => rule.plan_id === String(planId)) || null;
    const getRule = (product,planId) => overrides.has(`${product.id}:${planId}`) ? overrides.get(`${product.id}:${planId}`) : savedRule(product,planId);
    const isExclusive = product => product.exclusive;
    const benefitSummary = benefit => {
        const value = benefit.benefit_value;
        if (benefit.benefit_type === 'access') return 'Tilgang';
        if (benefit.benefit_type === 'included') return 'Inkludert';
        if (benefit.benefit_type === 'fixed_price') return value === null ? 'Fast medlemspris' : `Medlemspris ${money(value)}`;
        if (benefit.benefit_type === 'percent_discount') return value === null ? 'Prosentvis rabatt' : `${number(value)} % rabatt${benefit.price_basis === 'booking_total' ? ' på hele leiesummen' : ''}`;
        if (benefit.benefit_type === 'amount_discount') return value === null ? 'Beløpsrabatt' : `${money(value)} i avslag${benefit.price_basis === 'rental_day' ? ' per leiedag' : ''}`;
        return '';
    };
    function renderBenefitIndicators(product) {
        if (!benefitEligible(product)) return '';
        const html = payload.plans.map(item => {
            const rule = getRule(product,item.id);
            const draft = overrides.has(`${product.id}:${item.id}`);
            if (!rule && !draft) return '';
            return `<span class="mb-benefit-indicator${draft ? ' mb-benefit-temporary' : ''}"><strong>${safeText(item.name)}</strong><span class="mb-benefit-summary">${safeText(rule ? benefitSummary(rule) : 'Fjernet i forhåndsvisning')}</span><span class="${draft ? 'mb-benefit-unsaved' : 'mb-benefit-origin'}">${draft ? 'Ikke lagret' : 'Lagret'}</span></span>`;
        }).join('');
        return html ? `<aside class="mb-card-benefits" aria-label="Medlemsfordeler">${html}</aside>` : '<p class="mb-no-benefit">Ingen medlemsfordel satt</p>';
    }
    const effectivePrice = (product,planId) => {
        if (!planId) {
            return isExclusive(product) ? null : product.price;
        }
        if (product.price === null) return null;
        return pricing().applyBenefit(cents(product.price),getRule(product,planId),1).totalCents / 100;
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
        const eligible = benefitEligible(product);
        const plan = selectedPlan();
        const price = product.type === 'rental' ? null : effectivePrice(product,plan?.id);
        const publicPrice = product.price;
        const eventPrice = plan && getRule(product,plan.id);
        const hidden = product.is_visible === false || product.is_public === false;
        const restricted = !!(!plan && isExclusive(product));
        const included = eventPrice?.benefit_type === 'included';
        const visibleMemberPrices = '';
        const priceBlock = product.type === 'rental' ? rentalRateHtml(product) + '<p class="mb-no-benefit">Velg datoer for å beregne leiesum</p>' : price === null ? `<span class="mb-card-price">${restricted ? 'Bare for medlemmer' : 'Pris ikke oppgitt'}</span>${visibleMemberPrices}` :
            included ? '<div class="mb-card-price"><span>Medlemsfordel</span><strong>Inkludert</strong></div>' :
            `<div class="mb-card-price"><span>${plan && (eventPrice || getRule(product,plan.id)) ? 'Medlemspris' : 'Pris'}</span>${plan && publicPrice !== null && price < publicPrice ? `<s>${money(publicPrice)}</s>` : ''}<strong>${money(price)}</strong></div>${visibleMemberPrices}`;
        const date = product.start_date ? `<span>Dato ${safeText(formatDate(product.start_date))}${product.start_time ? ` kl. ${safeText(product.start_time)}` : ''}</span>` : '';
        const place = product.address || product.location || '';
        const details = [date,place ? `<span>${safeText(place)}</span>` : ''].filter(Boolean).join('');
        return `<article class="mb-card${eligible ? '' : ' mb-card-ineligible'}${isExclusive(product) ? ' mb-card-exclusive' : ''}" ${eligible ? `tabindex="0" role="button" aria-label="Prøv fordel for ${safeText(product.name)}" data-mb-item="${safeText(product.id)}"` : 'aria-disabled="true"'}>
            ${isExclusive(product) ? '<span class="mb-exclusive-label">Bare for medlemmer</span>' : ''}
            ${productImage(product)}<div class="mb-card-body"><h5 class="mb-card-title">${safeText(product.name)}</h5><p class="mb-card-description">${safeText(product.description || categoryDescriptions[product.type])}</p>
            ${details ? `<div class="mb-card-meta">${details}</div>` : ''}${priceBlock}${hidden ? '<span class="mb-card-badge mb-hidden-badge">Skjult i offentlig visning</span>' : ''}${renderBenefitIndicators(product)}${eligible ? '<span class="mb-card-action">Prøv medlemsfordel</span>' : ''}</div></article>`;
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
        const company = payload.company;
        const logo = safeUrl(company.img_url || company.logo_url);
        const today = new Intl.DateTimeFormat('sv-SE',{timeZone:'Europe/Oslo',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
        const previewProducts = payload.products.filter(product =>
            !(product.type === 'events' && product.end_date && String(product.end_date).slice(0,10) < today)
            && (product.type === 'membership' || showHidden.checked || (product.is_visible !== false && product.is_public !== false)));
        const featured = previewProducts.find(product => product.type === 'events');
        const featuredHtml = featured ? `<article class="mb-feature mb-feature-inactive" aria-disabled="true">${productImage(featured).replace('mb-card-image','mb-feature-image')}<div class="mb-feature-copy"><span class="mb-feature-kicker">SISTE ARRANGEMENT</span><h4>${safeText(featured.name)}</h4><p>${safeText(featured.start_date ? `${formatDate(featured.start_date)}${featured.start_time ? ` kl. ${safeText(featured.start_time)}` : ''}` : featured.description)}</p><span class="mb-feature-note">Rediger via arrangementskortet nedenfor</span></div></article>` : '';
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
        catalog.style.setProperty('--mb-header-color',headerColor);
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
    function rentalRateHtml(product) {
        const rates = product.rental_rates;
        if (!rates) return '<p class="mb-no-benefit">Leiesatser mangler</p>';
        const lines = [];
        const days = [['monday','Mandag'],['tuesday','Tirsdag'],['wednesday','Onsdag'],['thursday','Torsdag'],['friday','Fredag'],['saturday','Lørdag'],['sunday','Søndag']];
        const enabled = [true,1,'1'].includes(rates.weekday_prices_enabled) || ((rates.weekday_prices_enabled == null || rates.weekday_prices_enabled === '') && !!rates.weekday_prices);
        let dayPrices = rates.weekday_prices || {};
        if (typeof dayPrices === 'string') {
            try { dayPrices = JSON.parse(dayPrices) || {}; } catch (_) { return '<p class="mb-no-benefit">Ukedagsprisene har ugyldig format</p>'; }
        }
        const packageActive = nullableNumber(rates.weekend_price) > 0 && number(rates.saturday_price) <= 0 && number(rates.sunday_price) <= 0;
        if (enabled) {
            days.slice(0,5).forEach(([key,label],index) => {
                const value = nullableNumber(dayPrices[key] ?? dayPrices[index + 1]);
                if (value !== null) lines.push(`<span>${label} <strong>${money(value)}</strong></span>`);
            });
        } else if (nullableNumber(rates.rental_price) !== null) lines.push(`<span>Per dag <strong>${money(rates.rental_price)}</strong></span>`);
        if (packageActive) lines.push(`<span>Helgepakke (lør.–søn.) <strong>${money(rates.weekend_price)}</strong></span>`);
        if (!packageActive) ['saturday','sunday'].forEach((key,index) => {
            const value = number(rates[key + '_price']) > 0 ? rates[key + '_price'] : number(rates.weekend_price) > 0 ? rates.weekend_price : rates.rental_price;
            if (nullableNumber(value) !== null) lines.push(`<span>${index ? 'Søndag' : 'Lørdag'} <strong>${money(value)}</strong></span>`);
        });
        return `<div class="mb-rate-list">${lines.join('') || 'Ingen aktive leiesatser oppgitt'}</div>`;
    }
    function inputRule() {
        if (!editorDrafts.has(`${activeItem.id}:${editorPlanId}`)) return getRule(activeItem,editorPlanId);
        if (activeItem.type === 'rental') {
            if (!valueInput.value.trim()) return null;
            return pricing().parseRentalDiscount(valueInput.value,rentalMode.value);
        }
        const how = mechanism();
        if (how === 'included') return {benefit_type:'included',benefit_value:0,price_basis:'unit'};
        if (!valueInput.value.trim()) return null;
        const value = Number(valueInput.value.trim().replace(',','.'));
        if (!Number.isFinite(value) || value < 0 || (how === 'percentage' && value > 100)) throw new Error('Skriv et gyldig beløp. Prosent må være mellom 0 og 100.');
        return {benefit_type:{own:'fixed_price',percentage:'percent_discount',fixed:'amount_discount'}[how],benefit_value:value,price_basis:'unit'};
    }
    function rememberDraft() {
        if (!activeItem || !editorPlanId) return;
        editorDrafts.set(`${activeItem.id}:${editorPlanId}`,{text:valueInput.value,how:mechanism(),mode:rentalMode.value,dirty:true});
    }
    function renderTabs() {
        editorPlan.innerHTML = payload.plans.map((plan,index) => `<button type="button" id="mb-tab-${index}" role="tab" aria-selected="${plan.id === editorPlanId}" aria-controls="mb-plan-panel" tabindex="${plan.id === editorPlanId ? '0' : '-1'}" data-plan-id="${safeText(plan.id)}">${safeText(plan.name)}</button>`).join('') || '<p>Ingen medlemskapsplaner</p>';
        const index = payload.plans.findIndex(plan => plan.id === editorPlanId);
        document.getElementById('mb-plan-panel').setAttribute('aria-labelledby',`mb-tab-${index}`);
    }
    function loadEditorPlan() {
        renderTabs();
        const rule = getRule(activeItem,editorPlanId);
        const draft = editorDrafts.get(`${activeItem.id}:${editorPlanId}`);
        const how = {fixed_price:'own',percent_discount:'percentage',amount_discount:'fixed',included:'included'}[rule?.benefit_type] || 'own';
        valueInput.value = draft ? draft.text : rule?.benefit_value == null ? '' : String(rule.benefit_value);
        rentalMode.value = draft?.mode || (rule?.benefit_type === 'percent_discount' ? 'percentage' : 'per_day');
        dialog.querySelectorAll('input[name="mb-mechanism"]').forEach(input => { input.checked = input.value === (draft?.how || how); });
        updateEditorPreview();
    };
    function updateEditorPreview() {
        if (!activeItem) return;
        const rental = activeItem.type === 'rental';
        const how = mechanism();
        valueField.hidden = !rental && how === 'included';
        document.getElementById('mb-standard-mechanisms').hidden = rental;
        document.getElementById('mb-rental-mode-field').hidden = !rental;
        document.getElementById('mb-rental-quote').hidden = !rental;
        document.getElementById('mb-source-access').hidden = !isExclusive(activeItem);
        valueField.querySelector('label').textContent = rental ? 'Medlemsavslag' : how === 'percentage' ? 'Rabatt i prosent' : how === 'fixed' ? 'Avslag i kroner' : 'Medlemspris';
        suffix.textContent = rental ? rentalMode.value === 'percentage' ? '% av leiesum' : 'kr/dag' : how === 'percentage' ? '%' : 'kr';
        document.getElementById('mb-input-help').textContent = rental ? 'Skriv f.eks. 50 for 50 kr/dag, eller 50% for rabatt på hele leiesummen.' : '';
        const key = `${activeItem.id}:${editorPlanId}`;
        const draft = editorDrafts.get(key);
        ruleStatus.textContent = draft?.dirty || overrides.has(key) ? 'Ikke lagret · kun forhåndsvisning' : savedRule(activeItem,editorPlanId) ? `Lagret fordel · ${benefitSummary(savedRule(activeItem,editorPlanId))}` : 'Ingen medlemsfordel satt';
        document.getElementById('mb-reset-item').disabled = !overrides.has(key) && !draft;
        document.getElementById('mb-remove-item').disabled = !editorPlanId;
        let rule = null, error = '';
        try { rule = inputRule(); } catch (err) { error = err.message || 'Ugyldig rabatt'; }
        document.getElementById('mb-apply').disabled = !editorPlanId || !rule || !!error;
        valueInput.setCustomValidity(error);
        livePrice.textContent = error || (!rule ? 'Ingen medlemsfordel satt' : benefitSummary(rule));
        if (rental) {
            document.getElementById('mb-rental-rates').innerHTML = rentalRateHtml(activeItem);
            const rates = activeItem.rental_rates || {};
            const packageActive = number(rates.weekend_price) > 0 && number(rates.saturday_price) <= 0 && number(rates.sunday_price) <= 0;
            document.getElementById('mb-rental-period-help').textContent = 'Datoene teller med, også returdagen. ' + (packageActive ? 'Helgepakken (lørdag–søndag) belastes én gang per helg, også når bare deler av helgen leies. ' : 'Leien beregnes med satsen for hver valgt dag. ') + 'Depositum, rengjøring og gebyrer er ikke med.';
            const result = document.getElementById('mb-quote-result');
            if (!startDate.value || !endDate.value) { result.textContent = 'Velg fra- og til-dato for å se normal sum og medlemssum.'; return; }
            if (endDate.value < startDate.value) { result.textContent = 'Til-dato må være samme dag som eller etter fra-dato.'; return; }
            try {
                if (error) throw new Error(error);
                if (!activeItem.rental_rates) throw new Error('Leiesatser mangler. Kan ikke beregne perioden.');
                const quote = window.RentalRequestPricing.calculateRentalQuote({rates:activeItem.rental_rates,startDate:startDate.value,endDate:endDate.value,cleaning:false,adjustment:null});
                if (!Number.isFinite(quote.baseCents) || !Number.isFinite(quote.rentalDays)) throw new Error('Kunne ikke beregne perioden med disse satsene.');
                const applied = pricing().applyBenefit(quote.baseCents,rule,quote.rentalDays);
                result.innerHTML = `<p>${safeText(formatDate(startDate.value))} – ${safeText(formatDate(endDate.value))}</p><dl><div><dt>Leiedager / rabattdager</dt><dd>${quote.rentalDays}</dd></div><div><dt>Normal leiesum</dt><dd>${money(quote.baseCents / 100)}</dd></div><div><dt>Medlemsavslag</dt><dd>−${money(applied.discountCents / 100)}</dd></div><div class="mb-quote-total"><dt>Medlemssum</dt><dd>${money(applied.totalCents / 100)}</dd></div></dl>${!rule ? '<small>Ingen medlemsfordel satt. Medlemssummen er lik normal leiesum.</small>' : ''}`;
            } catch (err) { result.textContent = err.message || 'Kunne ikke beregne perioden.'; }
        } else if (!error && activeItem.price !== null) {
            livePrice.textContent = money(pricing().applyBenefit(cents(activeItem.price),rule,1).totalCents / 100);
            if (!rule) livePrice.textContent += ' · ordinær pris';
        }
    }
    function openEditor(id,card) {
        activeItem = payload.products.find(product => product.id === id);
        if (!activeItem || !benefitEligible(activeItem)) { activeItem = null; return; }
        returnFocus = card;
        editorPlanId = selectedPlan()?.id || payload.plans[0]?.id || '';
        draftLabel.textContent = activeItem.name;
        startDate.value = '';
        endDate.value = '';
        loadEditorPlan();
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
    dialog.querySelectorAll('input[name="mb-mechanism"]').forEach(input => input.addEventListener('change',() => { rememberDraft(); updateEditorPreview(); }));
    valueInput.addEventListener('input',() => {
        if (activeItem?.type === 'rental' && valueInput.value.includes('%')) rentalMode.value = 'percentage';
        rememberDraft();
        updateEditorPreview();
    });
    rentalMode.addEventListener('change',() => {
        valueInput.value = valueInput.value.replace(/%/g,'');
        rememberDraft();
        updateEditorPreview();
    });
    [startDate,endDate].forEach(input => input.addEventListener('change',updateEditorPreview));
    editorPlan.addEventListener('click',event => {
        const tab = event.target.closest('[data-plan-id]');
        if (!tab) return;
        editorPlanId = tab.dataset.planId;
        loadEditorPlan();
        editorPlan.querySelector('[aria-selected="true"]')?.focus();
    });
    editorPlan.addEventListener('keydown',event => {
        const tabs = [...editorPlan.querySelectorAll('[role="tab"]')];
        const index = tabs.indexOf(document.activeElement);
        if (index < 0 || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        tabs[next].click();
    });
    document.getElementById('mb-apply').addEventListener('click',() => {
        if (!activeItem || !editorPlanId || !valueInput.reportValidity()) return;
        let rule;
        try { rule = inputRule(); } catch (_) { return; }
        if (!rule) return;
        overrides.set(`${activeItem.id}:${editorPlanId}`,rule);
        editorDrafts.delete(`${activeItem.id}:${editorPlanId}`);
        render();
        updateEditorPreview();
    });
    document.getElementById('mb-remove-item').addEventListener('click',() => {
        if (!activeItem || !editorPlanId) return;
        overrides.set(`${activeItem.id}:${editorPlanId}`,null);
        editorDrafts.delete(`${activeItem.id}:${editorPlanId}`);
        render();
        loadEditorPlan();
    });
    document.getElementById('mb-reset-item').addEventListener('click',() => {
        if (!activeItem) return;
        overrides.delete(`${activeItem.id}:${editorPlanId}`);
        editorDrafts.delete(`${activeItem.id}:${editorPlanId}`);
        render();
        loadEditorPlan();
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
            editorDrafts.clear();
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
