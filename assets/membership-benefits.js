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
    const planPanel = document.getElementById('mb-plan-panel');
    const valueField = document.getElementById('mb-value-field');
    const valueInput = document.getElementById('mb-value');
    const suffix = document.getElementById('mb-value-suffix');
    const livePrice = document.getElementById('mb-live-price');
    const draftLabel = document.getElementById('mb-editor-title');
    let payload = null;
    let loaded = false;
    let loading = false;
    let saving = false;
    let exclusiveSaving = false;
    let verificationSaving = false;
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
    const adminOverview = document.getElementById('mb-admin-overview');
    const legacyWarning = document.getElementById('mb-legacy-warning');
    const saveFeedback = document.getElementById('mb-save-feedback');
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
            csrf_token:String(data.csrf_token || ''),
            plans:data.plans.map(plan => ({...plan,id:String(plan.id),name:String(plan.plan_name || plan.name || 'Medlemskap'),price:nullableNumber(plan.price)})),
            products:data.products.map((product,index) => ({...product,registration_type:product.registration_type || 'none',id:String(product.id ?? product.source_id ?? index),type:categoryNames[product.type] ? product.type : 'product',name:String(product.name || 'Uten navn'),description:String(product.description || ''),price:nullableNumber(product.price),exclusive:[product.exclusive,product.is_member_exclusive,product.member_exclusive].some(value => value === true || value === 1 || value === '1'),exclusive_supported:product.exclusive_supported === true,assigned_benefits:Array.isArray(product.assigned_benefits) ? product.assigned_benefits.map(benefit => ({...benefit,plan_id:String(benefit.plan_id ?? ''),benefit_type:String(benefit.benefit_type || ''),benefit_value:nullableNumber(benefit.benefit_value),price_basis:String(benefit.price_basis || 'unit'),public_display:benefit.public_display === true ? true : benefit.public_display === false ? false : null})).filter(benefit => benefit.plan_id && ['access','fixed_price','percent_discount','amount_discount','included'].includes(benefit.benefit_type)) : []})),
            warnings:Array.isArray(data.warnings) ? data.warnings.map(String) : []
        };
    };
    let catalogRequest = null;
    function loadCatalog(force = false) {
        if (catalogRequest) return catalogRequest;
        catalogRequest = fetchCatalog(force).finally(() => { catalogRequest = null; });
        return catalogRequest;
    }
    async function fetchCatalog(force = false) {
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
        && (product.type !== 'events' || (['signup','binding'].includes(product.registration_type)
            && (!product.end_date || String(product.end_date).slice(0,10) >= new Intl.DateTimeFormat('sv-SE',{
                timeZone:'Europe/Oslo',year:'numeric',month:'2-digit',day:'2-digit'
            }).format(new Date()))));
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
    function renderAdminOverview() {
        if (!adminOverview) return;
        const rows = payload.products.filter(benefitEligible).map(product => {
            const entries = payload.plans.map(plan => {
                const key = `${product.id}:${plan.id}`;
                const hasOverride = overrides.has(key);
                const hasDraft = editorDrafts.has(key);
                const rule = hasOverride ? overrides.get(key) : savedRule(product,plan.id);
                if (!rule && !hasDraft && !hasOverride) return '';
                const text = hasDraft ? (savedRule(product,plan.id) ? 'Utkast · lagret regel beholdes inntil du lagrer' : 'Uferdig utkast') : !rule ? 'Fjernet i forhåndsvisning' : benefitSummary(rule);
                const origin = hasDraft || hasOverride ? (savedRule(product,plan.id) ? 'Lagret + utkast' : 'Ikke lagret') : 'Lagret regel';
                return `<span class="mb-admin-rule"><strong>${safeText(plan.name)}</strong><span>${safeText(text)}</span><small>${origin}</small></span>`;
            }).filter(Boolean);
            const assigned = payload.plans.filter(plan => savedRule(product,plan.id) || overrides.has(`${product.id}:${plan.id}`) || editorDrafts.has(`${product.id}:${plan.id}`));
            return `<li><button type="button" class="mb-admin-product" data-mb-admin-item="${safeText(product.id)}"><span class="mb-admin-product-name">${safeText(product.name)}</span><span class="mb-admin-plans">${assigned.length ? assigned.map(plan => safeText(plan.name)).join(' · ') : 'Ingen planfordel satt'}</span></button>${entries.length ? `<div class="mb-admin-rules">${entries.join('')}</div>` : '<p class="mb-admin-no-rule">Ingen lagrede regler eller utkast.</p>'}</li>`;
        }).join('');
        adminOverview.hidden = false;
        adminOverview.innerHTML = `<details><summary>Oversikt over oppsatte fordeler · ${payload.products.filter(product => benefitEligible(product) && product.assigned_benefits.length).length} tjenester</summary>${rows ? `<ul class="mb-admin-list">${rows}</ul>` : '<p class="mb-admin-empty">Ingen kvalifiserte tilbud å vise.</p>'}</details>`;
        adminOverview.querySelectorAll('[data-mb-admin-item]').forEach(button => button.addEventListener('click',() => openEditor(button.dataset.mbAdminItem,button)));
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
        const priceBlock = product.type === 'rental' ? rentalBenefitCardHtml(product,plan) : price === null ? (restricted ? '' : '<span class="mb-card-price">Pris ikke oppgitt</span>') :
            included ? '<div class="mb-card-price"><span>Medlemsfordel</span><strong>Inkludert</strong></div>' :
            `<div class="mb-card-price"><span>${plan && (eventPrice || getRule(product,plan.id)) ? 'Medlemspris' : 'Pris'}</span>${plan && publicPrice !== null && price < publicPrice ? `<s>${money(publicPrice)}</s>` : ''}<strong>${money(price)}</strong></div>`;
        const date = product.start_date ? `<span>Dato ${safeText(formatDate(product.start_date))}${product.start_time ? ` kl. ${safeText(product.start_time)}` : ''}</span>` : '';
        const place = product.address || product.location || '';
        const details = [date,place ? `<span>${safeText(place)}</span>` : ''].filter(Boolean).join('');
        return `<article class="mb-card${eligible ? '' : ' mb-card-ineligible'}${isExclusive(product) ? ' mb-card-exclusive' : ''}" ${eligible ? `tabindex="0" role="button" aria-label="Åpne forhåndsvisning for ${safeText(product.name)}" data-mb-item="${safeText(product.id)}"` : 'aria-disabled="true"'}>
            ${isExclusive(product) ? '<span class="mb-exclusive-label">Kun for medlemmer</span>' : ''}
            ${productImage(product)}<div class="mb-card-body"><h5 class="mb-card-title">${safeText(product.name)}</h5><p class="mb-card-description">${safeText(product.description || categoryDescriptions[product.type])}</p>
            ${details ? `<div class="mb-card-meta">${details}</div>` : ''}${priceBlock}${hidden ? '<span class="mb-card-badge mb-hidden-badge">Skjult i offentlig visning</span>' : ''}</div></article>`;
    }
    function rentalBenefitCardHtml(product,selected) {
        const plans=selected ? [selected] : payload.plans;
        const rows=plans.map(plan=>{
            const rule=getRule(product,plan.id);
            if(!rule) return '';
            const value=nullableNumber(rule.benefit_value);
            const text=rule.benefit_type==='percent_discount' && value!==null ? `${number(value)} % avslag`
                : rule.benefit_type==='amount_discount' && value!==null ? `${money(value)} avslag`
                : rule.benefit_type==='fixed_price' && value!==null ? `Medlemspris ${money(value)}`
                : rule.benefit_type==='included' ? 'Inkludert' : 'Ingen prisrabatt';
            return `<p class="mb-card-price"><span>${safeText(plan.name)}</span><strong>${safeText(text)}</strong></p>`;
        }).join('');
        return rows || '<p class="mb-no-benefit">Ingen medlemsrabatt satt</p>';
    }
    function render() {
        if (!loaded || !payload) return;
        state.hidden = true;
        catalog.removeAttribute('hidden');
        catalog.style.display = '';
        mode.textContent = demoMode ? 'DEMO · ikke lagringsklart' : 'LIVE · lagring ved valg';
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
        catalog.style.setProperty('--mb-catalog-background','#e4f0fc');
        catalog.style.setProperty('--mb-site-radius',profileRadius);
        catalog.classList.toggle('mb-phone-view',viewport === 'phone');
        renderAdminOverview();
        catalog.querySelectorAll('[data-mb-item]').forEach(card => {
            card.addEventListener('click',() => openEditor(card.dataset.mbItem,card));
            card.addEventListener('keydown',event => {
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openEditor(card.dataset.mbItem,card); }
            });
        });
    }
    const mechanism = () => document.getElementById('mb-benefit-type').value || 'own';
    function rentalRateHtml(product) {
        const rates = product.rental_rates;
        if (!rates) return '<p class="mb-no-benefit">Leiesatser mangler</p>';
        const lines = [];
        const days = [['monday','Mandag'],['tuesday','Tirsdag'],['wednesday','Onsdag'],['thursday','Torsdag'],['friday','Fredag'],['saturday','Lørdag'],['sunday','Søndag']];
        if (rates.pricing_model === 'weekly') {
            try {
                const daily = typeof rates.daily_prices === 'string' ? JSON.parse(rates.daily_prices) : rates.daily_prices;
                const groups = typeof rates.price_groups === 'string' ? JSON.parse(rates.price_groups) : rates.price_groups;
                // Validate with the same calculator, even before quote dates are selected.
                window.RentalRequestPricing.calculateRentalQuote({rates,startDate:'2026-11-16',endDate:'2026-11-22',cleaning:false});
                days.forEach(([key,label])=>lines.push(`<span>${label} <strong>${money(daily[key])}</strong></span>`));
                groups.forEach(group=>lines.push(`<span>${safeText(days[group.start_day-1][1])}–${safeText(days[group.end_day-1][1])}
                    <strong>${money(group.price)} samlet</strong></span>`));
                return `<div class="mb-rate-list">${lines.join('')}</div>`;
            } catch (_) { return '<p class="mb-rate-error">Ukeprisoppsettet mangler eller er ugyldig.</p>'; }
        }
        const enabled = [true,1,'1'].includes(rates.weekday_prices_enabled) || ((rates.weekday_prices_enabled == null || rates.weekday_prices_enabled === '') && !!rates.weekday_prices);
        let dayPrices = rates.weekday_prices || {};
        if (typeof dayPrices === 'string') {
            try { dayPrices = JSON.parse(dayPrices) || {}; } catch (_) { return '<p class="mb-no-benefit">Ukedagsprisene har ugyldig format</p>'; }
        }
        const packageActive = nullableNumber(rates.weekend_price) > 0 && number(rates.saturday_price) <= 0 && number(rates.sunday_price) <= 0;
        if (enabled) {
            let weekdayCount = 0;
            days.slice(0,5).forEach(([key,label],index) => {
                const value = nullableNumber(dayPrices[key] ?? dayPrices[index + 1]);
                if (value !== null) { weekdayCount += 1; lines.push(`<span>${label} <strong>${money(value)}</strong></span>`); }
            });
            if (weekdayCount < 5) lines.push(`<span class="mb-rate-error">${weekdayCount ? 'En eller flere ukedagspriser mangler' : 'Aktive ukedagspriser mangler'}</span>`);
        } else if (nullableNumber(rates.rental_price) !== null) lines.push(`<span>Per dag <strong>${money(rates.rental_price)}</strong></span>`);
        if (packageActive) lines.push(`<span>Helgepakke (lør.–søn.) <strong>${money(rates.weekend_price)}</strong></span>`);
        if (!packageActive) ['saturday','sunday'].forEach((key,index) => {
            const explicit = nullableNumber(rates[key + '_price']);
            const value = explicit !== null && explicit > 0 ? explicit : !enabled && number(rates.weekend_price) > 0 ? rates.weekend_price : !enabled ? rates.rental_price : null;
            if (nullableNumber(value) !== null) lines.push(`<span>${index ? 'Søndag' : 'Lørdag'} <strong>${money(value)}</strong></span>`);
        });
        if (enabled && !packageActive && !(number(rates.saturday_price) > 0 && number(rates.sunday_price) > 0)) {
            lines.push('<span class="mb-rate-error">Lørdags- og søndagspris mangler – ingen dagspris brukes som helgefallback</span>');
        }
        return `<div class="mb-rate-list">${lines.length ? lines.join('') : '<span class="mb-rate-error">Leiesatser mangler eller er ugyldige</span>'}</div>`;
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
    function renderPlanList() {
        // Move the shared editor before replacing rows; preserve its controls and listeners.
        editor.insertBefore(planPanel, editor.querySelector('.mb-editor-foot'));
        planPanel.hidden = true;
        document.getElementById('mb-plan-count').textContent = `${payload.plans.length} planer`;
        editorPlan.innerHTML = payload.plans.map((plan,index) => {
            const rule = savedRule(activeItem,plan.id);
            const editing = plan.id === editorPlanId;
            return `<li class="mb-plan-row${editing ? ' is-editing' : ''}" data-plan-row="${safeText(plan.id)}">
                <div class="mb-plan-row-summary">
                    <div class="mb-plan-row-copy"><h4 id="mb-plan-name-${index}">${safeText(plan.name)}</h4>
                    <p class="mb-plan-row-benefit${rule ? ' has-benefit' : ''}">${rule ? `<span class="mb-plan-current-label">Lagret fordel</span>${safeText(benefitSummary(rule))}` : '<span class="mb-plan-current-label is-empty">Ingen fordel satt</span>Ordinær pris gjelder'}</p></div>
                    <div class="mb-plan-row-actions">
                    <button type="button" class="mb-button" data-plan-id="${safeText(plan.id)}" aria-expanded="${editing}"${editing ? ' aria-controls="mb-plan-panel"' : ''}${saving ? ' disabled' : ''}>${editing ? 'Lukk redigering' : rule ? 'Endre fordel' : 'Legg til fordel'}</button>
                    ${rule ? `<button type="button" class="mb-button mb-button-danger" data-remove-plan="${safeText(plan.id)}" aria-label="Fjern fordel for ${safeText(plan.name)}"${saving || demoMode || !payload.csrf_token ? ' disabled' : ''}>Fjern</button>` : ''}
                    </div>
                </div></li>`;
        }).join('') || '<li class="mb-no-benefit">Ingen medlemsplaner tilgjengelig.</li>';
        if (editorPlanId) {
            const row = [...editorPlan.children].find(row => row.dataset.planRow === editorPlanId);
            if (row) {
                row.append(planPanel);
                planPanel.hidden = false;
                planPanel.setAttribute('role','region');
                planPanel.setAttribute('aria-labelledby',row.querySelector('h4').id);
            }
        }
    }
    function discardRow(confirmDiscard = true) {
        if (!activeItem || !editorPlanId) return true;
        const key = `${activeItem.id}:${editorPlanId}`;
        if (confirmDiscard && editorDrafts.has(key) && !window.confirm('Du har ulagrede endringer. Forkast dem?')) return false;
        overrides.delete(key);
        editorDrafts.delete(key);
        return true;
    }
    function collapseRow() {
        const plan = editorPlanId;
        editorPlanId = '';
        renderPlanList();
        editorPlan.querySelector(`[data-plan-id="${CSS.escape(plan)}"]`)?.focus();
    }
    function loadEditorPlan() {
        renderPlanList();
        if (!editorPlanId) { updateEditorPreview(); return; }
        const rule = getRule(activeItem,editorPlanId);
        const draft = editorDrafts.get(`${activeItem.id}:${editorPlanId}`);
        const how = {fixed_price:'own',percent_discount:'percentage',amount_discount:'fixed',included:'included'}[rule?.benefit_type] || 'own';
        const legacyRentalFixedPrice = activeItem.type === 'rental' && rule?.benefit_type === 'fixed_price';
        valueInput.value = draft ? draft.text : legacyRentalFixedPrice || rule?.benefit_value == null ? '' : String(rule.benefit_value);
        document.getElementById('mb-price-details').open = false;
        rentalMode.value = draft?.mode || (rule?.benefit_type === 'percent_discount' ? 'percentage' : 'per_day');
        legacyWarning.hidden = !legacyRentalFixedPrice || !!draft;
        const typeSelect = document.getElementById('mb-benefit-type');
        typeSelect.value = draft?.how || (activeItem.type === 'rental'
            ? rule?.benefit_type === 'percent_discount' ? 'total_percentage' : 'day_discount'
            : how);
        updateEditorPreview();
    };
    function updateEditorPreview() {
        if (!activeItem) return;
        const exclusiveToggle = document.getElementById('mb-member-exclusive');
        const exclusiveState = document.getElementById('mb-exclusive-state');
        const exclusiveStatus = document.getElementById('mb-exclusive-status');
        if (exclusiveToggle && activeItem) {
            exclusiveToggle.disabled = saving || exclusiveSaving || demoMode || !payload.csrf_token || !activeItem.exclusive_supported;
            exclusiveState.textContent = exclusiveSaving ? 'Lagrer …' : activeItem.exclusive
                ? 'Lagret · bare medlemmer kan booke eller melde seg på'
                : 'Lagret · ingen medlemsbegrensning';
            exclusiveState.dataset.active = String(!!activeItem.exclusive);
            if (demoMode) exclusiveStatus.textContent = 'Demo: innstillingen kan ikke lagres.';
            else if (!activeItem.exclusive_supported) exclusiveStatus.textContent = 'Kan ikke endres her: dette tilbudet støtter ikke innstillingen.';
            else if (!payload.csrf_token) exclusiveStatus.textContent = 'Kan ikke lagre: sikkerhetstoken mangler.';
            else if (exclusiveSaving) exclusiveStatus.textContent = 'Lagrer …';
        }
        const verificationToggle = document.getElementById('mb-verification');
        const verificationState = document.getElementById('mb-verification-state');
        if (verificationToggle && activeItem) {
            verificationToggle.disabled = saving || verificationSaving || demoMode || !payload.csrf_token || !activeItem.memberverification_supported;
            verificationState.textContent = verificationSaving ? 'Lagrer …'
                : !activeItem.memberverification_supported ? 'Ikke tilgjengelig for dette tilbudet'
                    : 'Lagret · ' + (activeItem.memberverification ? 'aktivert' : 'deaktivert');
            verificationState.dataset.state = verificationSaving ? 'saving'
                : !activeItem.memberverification_supported ? 'unsupported' : activeItem.memberverification ? 'active' : 'inactive';
        }
        if (!editorPlanId) return;
        const rental = activeItem.type === 'rental';
        const how = mechanism();
        valueField.hidden = !rental && how === 'included';
        if (rental) rentalMode.value = how === 'total_percentage' ? 'percentage' : 'per_day';
        document.getElementById('mb-rental-quote').hidden = !rental;
        document.getElementById('mb-rental-rate-section').hidden = !rental;
        document.getElementById('mb-price-details').hidden = !rental;
        legacyWarning.hidden = !(rental && savedRule(activeItem,editorPlanId)?.benefit_type === 'fixed_price' && !editorDrafts.has(`${activeItem.id}:${editorPlanId}`));
        document.getElementById('mb-source-access').hidden = !isExclusive(activeItem);
        const inputLabel = valueField.querySelector('label');
        const inputHelp = document.getElementById('mb-input-help');
        if (rental) {
            inputLabel.textContent = how === 'total_percentage' ? 'Prosent av leiesummen' : 'Avslag per leiedag';
            suffix.textContent = how === 'total_percentage' ? '%' : 'kr';
            inputHelp.textContent = 'Beregnes fra ordinære leiesatser. De ordinære prisene endres ikke.';
        } else if (how === 'own') {
            inputLabel.textContent = 'Medlemmet betaler';
            suffix.textContent = 'kr';
            inputHelp.textContent = 'Dette er medlemsprisen for den valgte planen.';
        } else if (how === 'percentage') {
            inputLabel.textContent = 'Rabatt';
            suffix.textContent = '%';
            inputHelp.textContent = 'Trekkes fra ordinær pris.';
        } else {
            inputLabel.textContent = 'Avslag';
            suffix.textContent = 'kr';
            inputHelp.textContent = 'Trekkes fra ordinær pris.';
        }
        const key = `${activeItem.id}:${editorPlanId}`;
        const draft = editorDrafts.get(key);
        ruleStatus.textContent = demoMode ? 'DEMO · endringer kan ikke lagres' : draft?.dirty || overrides.has(key) ? 'Ulagret · velg Lagre for å bekrefte' : savedRule(activeItem,editorPlanId) ? `Lagret fordel · ${benefitSummary(savedRule(activeItem,editorPlanId))}` : 'Ingen medlemsfordel satt';
        if (!payload.csrf_token && !demoMode) ruleStatus.textContent = 'Kan ikke lagre: sikkerhetstoken mangler fra katalogen.';
        document.getElementById('mb-reset-item').disabled = saving;
        let rule = null, error = '';
        try { rule = inputRule(); } catch (err) { error = err.message || 'Ugyldig rabatt'; }
        document.getElementById('mb-apply').disabled = saving || demoMode || !payload.csrf_token || !editorPlanId || !rule || !editorDrafts.has(key) || !!error || (rental && rule?.benefit_type === 'fixed_price' && !editorDrafts.has(key));
        valueInput.setCustomValidity(error);
        livePrice.previousElementSibling.textContent = rental ? 'Eksempel på fordel' : 'Medlemspris etter fordel';
        livePrice.textContent = error || (!rule ? 'Ingen medlemsfordel satt' : benefitSummary(rule));
        document.getElementById('mb-rental-rates').innerHTML = rental ? rentalRateHtml(activeItem) : '';
        if (rental) {
            const rates = activeItem.rental_rates || {};
            const packageActive = number(rates.weekend_price) > 0 && number(rates.saturday_price) <= 0 && number(rates.sunday_price) <= 0;
            document.getElementById('mb-rental-period-help').textContent = 'Datoene teller med, også returdagen. ' + (rates.pricing_model === 'weekly'
                ? 'Koblede dager belastes samlet én gang per berørt forekomst. Andre dager bruker enkeltprisen. '
                : packageActive ? 'Helgepakken (lørdag–søndag) belastes én gang per helg, også når bare deler av helgen leies. '
                : 'Leien beregnes med satsen for hver valgt dag. ') + 'Depositum, rengjøring og gebyrer er ikke med.';
            const result = document.getElementById('mb-quote-result');
            if (!startDate.value || !endDate.value) { result.textContent = 'Velg fra- og til-dato for å se normal sum og medlemssum.'; return; }
            if (endDate.value < startDate.value) { result.textContent = 'Til-dato må være samme dag som eller etter fra-dato.'; return; }
            try {
                if (error) throw new Error(error);
                if (!activeItem.rental_rates) throw new Error('Leiesatser mangler. Kan ikke beregne perioden.');
                if (document.getElementById('mb-rental-rates').querySelector('.mb-rate-error')) throw new Error('Aktive leiesatser mangler. Kan ikke beregne perioden.');
                const activeRates = activeItem.rental_rates;
                const weekdayEnabled = [true,1,'1'].includes(activeRates.weekday_prices_enabled)
                    || ((activeRates.weekday_prices_enabled == null || activeRates.weekday_prices_enabled === '') && !!activeRates.weekday_prices);
                const weekendPackage = number(activeRates.weekend_price) > 0 && number(activeRates.saturday_price) <= 0 && number(activeRates.sunday_price) <= 0;
                if (activeRates.pricing_model !== 'weekly' && weekdayEnabled && !weekendPackage && !(number(activeRates.saturday_price) > 0 && number(activeRates.sunday_price) > 0)) {
                    throw new Error('Lørdags- og søndagspris mangler. Dagsprisen brukes ikke som helgefallback.');
                }
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
        saveFeedback.hidden = true;
        saveFeedback.textContent = '';
        editorPlanId = '';
        draftLabel.textContent = activeItem.name;
        startDate.value = '';
        endDate.value = '';
        const verify = document.getElementById('mb-verification');
        verify.checked = !!activeItem.memberverification;
        verify.disabled = !activeItem.memberverification_supported || demoMode || !payload.csrf_token;
        document.getElementById('mb-verification-help').textContent = activeItem.memberverification_supported
            ? 'Gjelder hele tilbudet, uavhengig av medlemsplan og prisfordel.'
            : 'Dette tilbudet støtter ikke Medlemsverifisering. Innstillingen kan ikke endres her.';
        document.getElementById('mb-verification-state').textContent = activeItem.memberverification_supported
            ? `Lagret · ${activeItem.memberverification ? 'aktivert' : 'deaktivert'}`
            : 'Ikke tilgjengelig for dette tilbudet';
        const exclusiveToggle = document.getElementById('mb-member-exclusive');
        exclusiveToggle.checked = !!activeItem.exclusive;
        exclusiveToggle.disabled = demoMode || !payload.csrf_token || !activeItem.exclusive_supported;
        document.getElementById('mb-exclusive-help').textContent = activeItem.exclusive_supported
            ? 'Begrenser hvem som kan booke eller melde seg på. Endrer ikke medlemsprisene.'
            : 'Dette tilbudet støtter ikke denne innstillingen. Den kan ikke endres her.';
        document.getElementById('mb-exclusive-status').textContent = demoMode
            ? 'Demo: innstillingen kan ikke lagres.'
            : !payload.csrf_token ? 'Kan ikke lagre: sikkerhetstoken mangler.'
                : !activeItem.exclusive_supported ? 'Denne tjenesten støtter ikke Kun for medlemmer.'
                    : 'Lagring skjer automatisk når du endrer valget.';
        document.getElementById('mb-exclusive-status').removeAttribute('data-state');
        document.getElementById('mb-exclusive-state').textContent = activeItem.exclusive
            ? 'Lagret · bare medlemmer kan booke eller melde seg på'
            : 'Lagret · ingen medlemsbegrensning';
        document.getElementById('mb-exclusive-state').dataset.active = String(!!activeItem.exclusive);
        const benefitType = document.getElementById('mb-benefit-type');
        benefitType.innerHTML = activeItem.type === 'rental'
            ? '<option value="day_discount">Avslag per leiedag</option><option value="total_percentage">Prosent av leiesummen</option>'
            : '<option value="own">Egen medlemspris</option><option value="percentage">Prosentvis rabatt</option><option value="fixed">Fast avslag i kroner</option><option value="included">Inkludert</option>';
        loadEditorPlan();
        dialog.hidden = false;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        editor.focus();
    }
    let previewEditorClosing = false;
    function closeEditor(restoreFocus = true, confirmDiscard = true) {
        if (!previewEditorClosing && editor.closest('#pwp-dialog')) return window.PortalWorkspace.close();
        if (exclusiveSaving || verificationSaving) return false;
        const dirtyRule = activeItem && editorDrafts.has(`${activeItem.id}:${editorPlanId}`);
        if (confirmDiscard && dirtyRule && !window.confirm('Du har en ulagret prisfordel. Forkast den og lukk?')) return false;
        discardRow(false);
        dialog.hidden = true;
        document.body.style.overflow = previousOverflow;
        activeItem = null;
        editorPlanId = '';
        if (restoreFocus) {
            const target = returnFocus?.isConnected ? returnFocus : catalog.querySelector(`[data-mb-item="${CSS.escape(returnFocus?.dataset.mbItem || '')}"]`);
            target?.focus();
        }
        return true;
    }
    function showSaveFeedback(message,error = false) {
        saveFeedback.textContent = message;
        saveFeedback.hidden = !message;
        saveFeedback.classList.toggle('mb-save-error',error);
    }
    function setSavingState(value) {
        saving = value;
        editor.setAttribute('aria-busy',String(value));
        document.getElementById('mb-apply').textContent = value ? 'Lagrer…' : 'Lagre fordel';
        editor.querySelectorAll('button,input,select').forEach(control => {
            if (value) {
                control.dataset.mbWasDisabled = String(control.disabled);
                control.disabled = true;
            } else if (Object.prototype.hasOwnProperty.call(control.dataset,'mbWasDisabled')) {
                control.disabled = control.dataset.mbWasDisabled === 'true';
                delete control.dataset.mbWasDisabled;
            }
        });
        editorPlan.querySelectorAll('[data-remove-plan]').forEach(button => {
            button.disabled = value || demoMode || !payload.csrf_token;
        });
        editorPlan.querySelectorAll('[data-plan-id]').forEach(button => { button.disabled = value; });
        if (activeItem) updateEditorPreview();
    }
    dialog.querySelectorAll('[data-mb-close]').forEach(button => button.addEventListener('click',() => { if (!saving && !exclusiveSaving && !verificationSaving) closeEditor(); }));
    dialog.addEventListener('click',event => { if (event.target === dialog && !saving && !exclusiveSaving && !verificationSaving) closeEditor(); });
    document.addEventListener('keydown',event => {
        if (dialog.hidden) return;
        if (event.key === 'Escape' && !saving && !exclusiveSaving && !verificationSaving) { event.preventDefault(); closeEditor(); }
        if (event.key === 'Tab') {
            const focusable = [...editor.querySelectorAll('button:not([disabled]),input:not([disabled]),select:not([disabled]),summary')].filter(element => element.offsetParent !== null);
            if (!focusable.length) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    document.getElementById('mb-benefit-type').addEventListener('change',() => {
        showSaveFeedback('');
        valueInput.value = '';
        if (activeItem?.type === 'rental') rentalMode.value = mechanism() === 'total_percentage' ? 'percentage' : 'per_day';
        rememberDraft();
        updateEditorPreview();
    });
    valueInput.addEventListener('input',() => {
        showSaveFeedback('');
        rememberDraft();
        updateEditorPreview();
    });
    document.getElementById('mb-verification').addEventListener('change',async event => {
        const toggle = event.currentTarget;
        if (!activeItem || verificationSaving || demoMode || !payload.csrf_token || !activeItem.memberverification_supported) {
            toggle.checked = !!activeItem?.memberverification;
            return;
        }
        const product = activeItem;
        const previous = !!product.memberverification;
        const requested = toggle.checked;
        const stateLabel = document.getElementById('mb-verification-state');
        product.memberverification = requested;
        verificationSaving = true;
        toggle.disabled = true;
        stateLabel.textContent = 'Lagrer …';
        stateLabel.dataset.state = 'saving';
        showSaveFeedback('');
        try {
            const response = await fetch('/api/save_membership_product_benefit.php',{
                method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json',Accept:'application/json'},
                body:JSON.stringify({action:'memberverification',product_id:product.id,memberverification:requested ? 1 : 0,csrf_token:payload.csrf_token})
            });
            let result;
            try { result = await response.json(); }
            catch (_) { throw new Error('Lagringstjenesten svarte ikke med gyldig JSON.'); }
            if (!response.ok || result?.success !== true || typeof result.memberverification !== 'boolean') {
                throw new Error(result?.message || result?.error || 'Medlemsverifisering kunne ikke lagres.');
            }
            product.memberverification = result.memberverification;
            toggle.checked = result.memberverification;
            stateLabel.textContent = `Lagret · ${result.memberverification ? 'aktivert' : 'deaktivert'}`;
            stateLabel.dataset.state = result.memberverification ? 'active' : 'inactive';
        } catch (error) {
            product.memberverification = previous;
            toggle.checked = previous;
            stateLabel.textContent = `${error.message || 'Medlemsverifisering kunne ikke lagres.'} Valget er tilbakestilt.`;
            stateLabel.dataset.state = 'error';
        } finally {
            verificationSaving = false;
            toggle.disabled = demoMode || !payload.csrf_token || !product.memberverification_supported;
        }
    });
    document.getElementById('mb-member-exclusive').addEventListener('change',async event => {
        const toggle = event.currentTarget;
        if (!activeItem || exclusiveSaving || demoMode || !payload.csrf_token || !activeItem.exclusive_supported) {
            toggle.checked = !!activeItem?.exclusive;
            return;
        }
        const product = activeItem;
        const previous = !!product.exclusive;
        const requested = toggle.checked;
        const status = document.getElementById('mb-exclusive-status');
        const stateLabel = document.getElementById('mb-exclusive-state');
        product.exclusive = requested;
        exclusiveSaving = true;
        status.removeAttribute('data-state');
            stateLabel.textContent = 'Lagrer …';
        status.textContent = 'Lagrer endringen …';
        toggle.disabled = true;
        render();
        try {
            const response = await fetch('/api/save_membership_product_benefit.php',{
                method:'POST',
                credentials:'same-origin',
                headers:{Accept:'application/json','Content-Type':'application/json'},
                body:JSON.stringify({action:'member_exclusive',product_id:product.id,member_exclusive:requested ? 1 : 0,csrf_token:payload.csrf_token})
            });
            let result;
            try { result = await response.json(); }
            catch (_) { throw new Error('Lagringstjenesten svarte ikke med gyldig JSON.'); }
            if (!response.ok || result?.success !== true || typeof result.exclusive !== 'boolean') {
                throw new Error(result?.message || result?.error || `Kunne ikke lagre tilgangsvalget (${response.status}).`);
            }
            product.exclusive = result.exclusive;
            toggle.checked = result.exclusive;
            stateLabel.textContent = result.exclusive ? 'Lagret · bare medlemmer kan booke eller melde seg på' : 'Lagret · ingen medlemsbegrensning';
            status.textContent = 'Lagret.';
            status.dataset.state = 'saved';
            render();
        } catch (error) {
            product.exclusive = previous;
            toggle.checked = previous;
            stateLabel.textContent = `${previous ? 'Lagret · bare medlemmer kan booke eller melde seg på' : 'Lagret · ingen medlemsbegrensning'} · valget er tilbakestilt`;
            status.textContent = `${error.message || 'Kunne ikke lagre valget.'} Endringen er tilbakestilt.`;
            status.dataset.state = 'error';
            render();
        } finally {
            exclusiveSaving = false;
            if (activeItem?.id === product.id) {
                toggle.disabled = demoMode || !payload.csrf_token || !product.exclusive_supported;
            }
        }
    });
    [startDate,endDate].forEach(input => input.addEventListener('change',updateEditorPreview));
    editorPlan.addEventListener('click',event => {
        if (saving) return;
        const remove = event.target.closest('[data-remove-plan]');
        if (remove) {
            if (remove.disabled) return;
            const plan = payload.plans.find(plan => plan.id === remove.dataset.removePlan);
            if (!plan || !window.confirm(`Fjern ${benefitSummary(savedRule(activeItem,plan.id))} for ${plan.name}? Ordinær pris vil gjelde uten denne fordelen.`)) return;
            if (!discardRow()) return;
            editorPlanId = plan.id;
            loadEditorPlan();
            persistBenefit(null);
            return;
        }
        const tab = event.target.closest('[data-plan-id]');
        if (!tab || tab.disabled) return;
        if (tab.dataset.planId === editorPlanId) {
            if (!discardRow()) return;
            collapseRow();
            return;
        }
        if (!discardRow()) return;
        showSaveFeedback('');
        editorPlanId = tab.dataset.planId;
        loadEditorPlan();
        (activeItem.type === 'rental' || mechanism() !== 'included' ? valueInput : document.getElementById('mb-apply')).focus();
    });
    async function persistBenefit(benefit) {
        if (!activeItem || !editorPlanId || saving || demoMode || !payload.csrf_token) return;
        const productId = activeItem.id;
        const planId = editorPlanId;
        const key = `${productId}:${planId}`;
        const product = payload.products.find(item => item.id === productId);
        if (!product) return;
        showSaveFeedback('');
        setSavingState(true);
        try {
            const response = await fetch('/api/save_membership_product_benefit.php',{
                method:'POST',
                credentials:'same-origin',
                headers:{Accept:'application/json','Content-Type':'application/json'},
                body:JSON.stringify({product_id:productId,plan_id:planId,
                    benefit:benefit ? {...benefit,public_display:savedRule(product,planId)?.public_display ?? true} : null,
                    csrf_token:payload.csrf_token})
            });
            let result;
            try { result = await response.json(); }
            catch (_) { throw new Error('Lagringstjenesten svarte ikke med gyldig JSON.'); }
            if (!response.ok || result?.success !== true) throw new Error(result?.message || result?.error || `Kunne ikke lagre fordelen (${response.status}).`);
            const saved = result.benefit ? {
                ...result.benefit,
                plan_id:String(planId),
                benefit_type:String(result.benefit.benefit_type || benefit?.benefit_type || ''),
                benefit_value:nullableNumber(result.benefit.benefit_value),
                price_basis:String(result.benefit.price_basis || benefit?.price_basis || 'unit'),
                public_display:result.benefit.public_display === false ? false : true
            } : null;
            product.assigned_benefits = product.assigned_benefits.filter(rule => rule.plan_id !== String(planId));
            if (saved) product.assigned_benefits.push(saved);
            overrides.delete(key);
            editorDrafts.delete(key);
            render();
            if (activeItem?.id === productId && editorPlanId === planId) collapseRow();
            showSaveFeedback(saved ? 'Fordel lagret.' : 'Fordel fjernet.');
        } catch (error) {
            showSaveFeedback(error.message || 'Kunne ikke lagre fordelen. Prøv igjen.',true);
        } finally {
            setSavingState(false);
        }
    }
    document.getElementById('mb-apply').addEventListener('click',() => {
        if (!activeItem || !editorPlanId || saving || demoMode || !payload.csrf_token || !valueInput.reportValidity()) return;
        let rule;
        try { rule = inputRule(); } catch (error) {
            showSaveFeedback(error.message || 'Kontroller fordelsverdien.',true);
            return;
        }
        if (!rule || !editorDrafts.has(`${activeItem.id}:${editorPlanId}`)) return;
        persistBenefit({...rule,public_display:true});
    });
    document.getElementById('mb-reset-item').addEventListener('click',() => {
        if (!activeItem || saving) return;
        showSaveFeedback('');
        discardRow(false);
        collapseRow();
        render();
    });
    planSelect.addEventListener('change',render);
    showHidden.addEventListener('change',render);
    document.querySelectorAll('[data-mb-viewport]').forEach(button => button.addEventListener('click',() => {
        viewport = button.dataset.mbViewport === 'phone' ? 'phone' : 'desktop';
        document.querySelectorAll('[data-mb-viewport]').forEach(item => item.setAttribute('aria-pressed',String(item === button)));
        catalog.classList.toggle('mb-phone-view',viewport === 'phone');
    }));
    window.MembershipBenefits = {
        closeProduct() {
            if (saving || exclusiveSaving || verificationSaving) return false;
            return dialog.hidden || closeEditor(false) !== false;
        },
        async openProduct(id) {
            await loadCatalog();
            if (!loaded || demoMode || !payload?.products.some(product => String(product.id) === String(id))) {
                throw new Error('Fordelene for denne tjenesten kunne ikke lastes.');
            }
            const item = payload.products.find(product => String(product.id) === String(id));
            if (!benefitEligible(item)) throw new Error('Denne tjenesten kan ikke tildeles medlemsfordeler.');
            // The editor must remain visible while the module itself stays hidden.
            document.body.appendChild(dialog);
            openEditor(item.id, null);
            if (document.body.classList.contains('portal-wpreview') && window.PortalWorkspace) {
                dialog.hidden = true;
                document.body.style.overflow = previousOverflow;
                window.PortalWorkspace.show(editor,`Fordeler · ${item.name}`,{
                    className:'mb-dialog pwp-benefits-surface',
                    beforeClose:()=>{
                        if (saving || exclusiveSaving || verificationSaving) return false;
                        return !editorDrafts.has(`${activeItem?.id}:${editorPlanId}`)
                            || confirm('Du har en ulagret prisfordel. Forkast den og lukk?');
                    },
                    onClose:()=>{
                        previewEditorClosing = true;
                        try { closeEditor(false,false); } finally { previewEditorClosing = false; }
                    }
                });
            }
        }
    };
    const maybeLoad = event => {
        if (event?.detail?.module === 'membership' && event.detail.subtab === 'membership-benefits') loadCatalog();
        else {
            overrides.clear();
            editorDrafts.clear();
            showHidden.checked = false;
            if (!dialog.hidden) closeEditor(false,false);
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
