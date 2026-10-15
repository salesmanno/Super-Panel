(function () {
    'use strict';

    const TYPES = ['text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'checkbox'];
    const TYPE_LABELS = {text:'Tekst',email:'E-post',tel:'Telefon',number:'Tall',date:'Dato',textarea:'Lang tekst',select:'Nedtrekksvalg',checkbox:'Avkrysning'};
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[char]));
    const copy = value => JSON.parse(JSON.stringify(value));

    async function load(entityType, entityId, csrfOnly = false) {
        const params = new URLSearchParams({entity_type: entityType});
        if (entityId !== null && entityId !== undefined && entityId !== '') params.set('entity_id', entityId);
        if (csrfOnly) params.set('csrf_only', '1');
        const response = await fetch(`/api/get_entity_form_fields.php?${params.toString()}`, {credentials: 'same-origin'});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke laste skjemafelt');
        if (result.csrf_token) window.entityFormCsrfToken = result.csrf_token;
        return result;
    }

    function normalize(field, index, strictRentalValidation) {
        let options = field.options;
        if (typeof options === 'string') {
            try { options = JSON.parse(options); } catch (_) {
                if (strictRentalValidation === true) throw new Error(`Feltet «${field.field_label || field.field_name || 'uten navn'}» har ugyldig JSON i innstillingen «options». Verdien er ikke endret.`);
                options = [];
            }
        }
        let validationRules = field.validation_rules;
        if (typeof validationRules === 'string') {
            try { validationRules = JSON.parse(validationRules); } catch (_) {
                if (strictRentalValidation === true) throw new Error(`Feltet «${field.field_label || field.field_name || 'uten navn'}» har ugyldig JSON i innstillingen «validation_rules». Verdien er ikke endret.`);
                validationRules = {};
            }
        }
        return {
            ...field,
            field_name: String(field.field_name || `custom_${index + 1}`),
            field_label: String(field.field_label || ''),
            field_type: String(field.field_type || 'text'),
            is_required: !!Number(field.is_required),
            is_visible: field.is_visible === undefined ? true : !!Number(field.is_visible),
            sort_order: Number.isFinite(Number(field.sort_order)) ? Number(field.sort_order) : index,
            default_value: field.default_value ?? null,
            options: Array.isArray(options) ? options : [],
            validation_rules: validationRules && typeof validationRules === 'object' && !Array.isArray(validationRules) ? validationRules : {}
        };
    }

    function mount(host, options) {
        const instance = {};
        host.entityFieldsInstance = instance;
        const current = () => host.entityFieldsInstance === instance;
        let fields = [];
        let savedFields = [];
        let selectedName = null;
        let serverRevision = '';
        let presets = [];
        let busy = false;
        let dirty = false;
        let loading = false;
        let ready = false;
        let refreshRevision = 0;
        let csrfToken = '';
        let registrationType = options.registrationType || '';
        const setStatus = (message, isError) => {
            if (!current()) return;
            const status = host.querySelector('[data-fields-status]');
            if (status) {
                status.textContent = message;
                status.classList.toggle('text-danger', !!isError);
                status.classList.toggle('text-success', !!message && !isError);
            }
        };

        host.innerHTML = `<section class="entity-fields-editor${options.rentalLayout === true ? ' entity-fields-editor-rental' : ''}${options.eventLayout === true ? ' entity-fields-editor-event' : ''}" aria-label="Skjemafelt">
            <div class="entity-fields-head">
                <div><span class="entity-fields-kicker">Deltakerinformasjon</span><h6 class="mb-1">Skjemafelt</h6>
                <p class="small text-muted mb-0">Feltene følger dette ${options.entityType === 'event' ? 'arrangementet' : options.entityType === 'sales_product' ? 'produktet' : 'utleieobjektet'}.</p></div>
                <div class="entity-fields-presets"><label class="visually-hidden" for="${host.id}-preset">Forhåndsoppsett</label>
                    <select class="form-select form-select-sm" id="${host.id}-preset" data-preset disabled><option value="">Forhåndsoppsett</option></select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-apply-preset disabled>Bruk</button>
                </div>
            </div>
            <div data-fields-notice class="alert alert-info py-2 small mb-3" hidden></div>
            <fieldset data-common-fields class="mb-3" hidden><legend class="fs-6">Velg vanlige felt</legend>
                 <p class="small text-muted">Kryss av feltene kunden skal fylle ut. Trykk på feltnavnet for å redigere ett felt.</p>
                <div data-common-list class="d-flex flex-wrap gap-3"></div>
            </fieldset>
            <div data-fields-list aria-live="polite"><div class="entity-fields-loading"><span class="entity-skeleton"></span><span class="entity-skeleton"></span></div></div>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" data-add disabled>Legg til felt</button>
                  <button type="button" class="btn btn-sm btn-primary ms-auto" data-save disabled>${options.createMode ? 'Behold feltendringer' : 'Lagre skjemafelt'}</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-close-field hidden>Lukk feltredigering</button>
                <span class="small ms-auto" data-fields-status role="status" aria-live="polite"></span>
            </div>
        </section>`;

        const confirmClose = () => {
            if (busy || loading) return false;
            if (dirty && !window.confirm('Du har ulagrede feltendringer. Vil du forkaste dem?')) return false;
            if (dirty) fields = copy(savedFields);
            dirty = false;
            selectedName = null;
            render();
            return true;
        };
        const openField = name => {
            if (busy || loading || selectedName === name) return;
            if (selectedName && dirty && !confirmClose()) return;
            if (!fields.some(field => field.field_name === name)) {
                const definition = presets[0]?.fields.find(field => field.field_name === name);
                if (!definition) return;
                // Opening a hidden/absent field is not a visibility toggle.
                fields.push(normalize({...definition,is_visible:false,is_required:false}, fields.length));
                dirty = true;
            }
            selectedName = name;
            render();
            host.querySelector('[data-prop="field_label"]')?.focus();
        };
        host.querySelector('[data-close-field]').addEventListener('click', confirmClose);
        const render = () => {
            const list = host.querySelector('[data-fields-list]');
            if (options.entityType==='event') fields.forEach(field=>{
                if (['first_name','last_name'].includes(field.field_name)) {field.is_visible=true;field.is_required=true;}
            });
            const common = host.querySelector('[data-common-list]');
            common.replaceChildren();
            const definitions = new Map((presets[0]?.fields || []).map(field => [field.field_name,field]));
            const rental = options.entityType === 'rental_object';
            const brokenEmail = rental && fields.find(field => field._invalid_validation_rules_hash);
            if (rental) {
                const notice = host.querySelector('[data-fields-notice]');
                notice.hidden = !brokenEmail;
                notice.textContent = brokenEmail
                    ? 'E-postfeltet har en ugyldig eldre valideringsregel. Trykk på E-post og bekreft erstatning med standard e-postvalidering før du lagrer. Ingen regel endres ved åpning.'
                    : '';
                // Include existing/custom fields in their saved order, followed
                // by standard fields not yet added to this object's setup.
                const ordered = fields.map(field => [field.field_name,definitions.get(field.field_name) || field]);
                for (const entry of definitions) if (!fields.some(field => field.field_name === entry[0])) ordered.push(entry);
                definitions.clear();
                ordered.forEach(([name,field]) => definitions.set(name,field));
            }
            host.querySelector('[data-common-fields]').hidden = !definitions.size || registrationType === 'none';
            for (const [name,definition] of definitions) {
                const field=fields.find(item=>item.field_name===name);
                const label=document.createElement('div');label.className='form-check d-flex gap-2 align-items-center';
                const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.className='form-check-input';
                checkbox.dataset.commonField=name;checkbox.checked=!!field?.is_visible;
                checkbox.disabled=!!field?.is_visible && options.entityType==='event' && ['first_name','last_name'].includes(name);
                checkbox.setAttribute('aria-label', `Bruk ${definition.field_label}`);
                const text=document.createElement('button');text.type='button';
                text.className='btn btn-link p-0 text-start';text.textContent=field?.field_label || definition.field_label;
                text.dataset.editField=name;text.setAttribute('aria-expanded',String(selectedName===name));
                text.addEventListener('click',()=>openField(name));
                checkbox.addEventListener('change',()=>{
                    if (busy) return;
                    let selected=fields.find(item=>item.field_name===name);
                    if (!selected) {selected=normalize(definition,fields.length);fields.push(selected);}
                    selected.is_visible=checkbox.checked;
                    if (!checkbox.checked) selected.is_required=false;
                    dirty=true;render();
                });
                const requiredLabel=document.createElement('label');
                requiredLabel.className='form-check d-inline-flex align-items-center gap-1 ms-2 small text-muted';
                const required=document.createElement('input');
                required.type='checkbox';required.className='form-check-input mt-0';
                required.checked=!!field?.is_required;
                required.disabled=!field?.is_visible || registrationType==='none';
                required.setAttribute('aria-label',`Gjør ${field?.field_label || definition.field_label} påkrevd`);
                required.addEventListener('change',()=>{
                    if (busy) return;
                    let selected=fields.find(item=>item.field_name===name);
                    if (!selected) {selected=normalize(definition,fields.length);fields.push(selected);}
                    if (required.checked) selected.is_visible=true;
                    selected.is_required=required.checked;
                    dirty=true;render();
                });
                requiredLabel.append(required,document.createTextNode('Påkrevd'));
                if (options.entityType === 'rental_object') label.append(checkbox,text,requiredLabel);
                else label.append(checkbox,text);
                if (rental && field) {
                    const order = document.createElement('div');
                    order.className = 'entity-common-order';
                    [-1,1].forEach(direction => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm btn-light';
                        button.dataset.commonMove = String(direction);
                        button.dataset.fieldName = name;
                        button.textContent = direction < 0 ? '↑' : '↓';
                        button.setAttribute('aria-label',`Flytt ${field.field_label} ${direction < 0 ? 'opp' : 'ned'}`);
                        const index = fields.indexOf(field);
                        button.disabled = index + direction < 0 || index + direction >= fields.length;
                        button.addEventListener('click',() => {
                            if (busy || loading) return;
                            const index = fields.indexOf(field), target = index + direction;
                            if (target < 0 || target >= fields.length) return;
                            [fields[index],fields[target]] = [fields[target],fields[index]];
                            fields.forEach((item,position) => item.sort_order = position);
                            dirty = true;
                            render();
                            host.querySelector(`[data-field-name="${name}"][data-common-move="${direction}"]`)?.focus();
                        });
                        order.append(button);
                    });
                    label.append(order);
                }
                common.append(label);
            }
            for(const field of fields) {
                if(definitions.has(field.field_name)) continue;
                const button=document.createElement('button');button.type='button';
                button.className='btn btn-sm btn-outline-secondary';button.textContent=field.field_label || field.field_name;
                button.dataset.editField=field.field_name;
                button.setAttribute('aria-expanded',String(selectedName===field.field_name));
                button.addEventListener('click',()=>openField(field.field_name));common.append(button);
            }
            host.querySelector('[data-common-fields]').hidden = registrationType === 'none';
            host.querySelector('[data-close-field]').hidden = !selectedName;
            if (!fields.length) {
                list.innerHTML = '<div class="entity-fields-empty">Ingen ekstra felt. Du kan legge til felter eller velge et forhåndsoppsett.</div>';
                return;
            }
            list.replaceChildren();
            if (options.eventLayout === true) {
                const orderList = document.createElement('div');
                orderList.className = 'event-fields-order-list';
                const heading = document.createElement('div');
                heading.className = 'event-fields-order-heading';
                heading.textContent = 'Feltrekkefølge';
                orderList.append(heading);
                fields.forEach((field,index) => {
                    const line = document.createElement('div');
                    line.className = 'event-fields-order-row';
                    const open = document.createElement('button');
                    open.type = 'button';
                    open.className = 'event-fields-order-label';
                    open.textContent = field.field_label || field.field_name;
                    open.setAttribute('aria-expanded',String(selectedName === field.field_name));
                    open.disabled = registrationType === 'none' || busy;
                    open.addEventListener('click',() => openField(field.field_name));
                    const visibility = document.createElement('span');
                    visibility.className = 'event-fields-order-state';
                    visibility.textContent = field.is_visible ? (field.is_required ? 'Synlig · påkrevd' : 'Synlig') : 'Skjult';
                    const controls = document.createElement('span');
                    controls.className = 'event-fields-order-controls';
                    [-1,1].forEach(direction => {
                        const move = document.createElement('button');
                        move.type = 'button';
                        move.className = 'btn btn-sm btn-light';
                        move.textContent = direction < 0 ? '↑' : '↓';
                        move.setAttribute('aria-label',`Flytt ${field.field_label || field.field_name} ${direction < 0 ? 'opp' : 'ned'}`);
                        move.disabled = registrationType === 'none' || busy || index + direction < 0 || index + direction >= fields.length;
                        move.addEventListener('click',() => {
                            if (busy || loading || registrationType === 'none') return;
                            const currentIndex = fields.indexOf(field);
                            const target = currentIndex + direction;
                            if (target < 0 || target >= fields.length) return;
                            [fields[currentIndex],fields[target]] = [fields[target],fields[currentIndex]];
                            fields.forEach((item,position) => item.sort_order = position);
                            dirty = true;
                            render();
                            host.querySelector(`.event-fields-order-row:nth-child(${target + 2}) .event-fields-order-label`)?.focus();
                        });
                        controls.append(move);
                    });
                    line.append(open,visibility,controls);
                    orderList.append(line);
                });
                list.append(orderList);
            }
            fields.forEach((field, index) => {
                if (field.field_name !== selectedName) return;
                const row = document.createElement('article');
                row.className = 'entity-field-row';
                const lockedIdentity = options.entityType === 'event' && ['first_name', 'last_name'].includes(field.field_name);
                if (lockedIdentity) { field.is_visible = true; field.is_required = true; }
                const typeOptions = TYPES.map(type => `<option value="${type}" ${field.field_type === type ? 'selected' : ''}>${TYPE_LABELS[type]}</option>`).join('');
                const unknownType = TYPES.includes(field.field_type) ? '' : `<option value="${esc(field.field_type)}" selected>Eksisterende (${esc(field.field_type)})</option>`;
                const rules = field.validation_rules || {};
                const maxLength = rules.max_length ?? rules.maxLength ?? '';
                const minLength = rules.min_length ?? rules.minLength ?? '';
                row.innerHTML = `<div class="entity-field-controls">
                    <div class="entity-field-order"><button type="button" class="btn btn-sm btn-light" data-move="-1" aria-label="Flytt opp" ${index === 0 ? 'disabled' : ''}>↑</button>
                    <button type="button" class="btn btn-sm btn-light" data-move="1" aria-label="Flytt ned" ${index === fields.length - 1 ? 'disabled' : ''}>↓</button></div>
                    <label class="entity-field-label">Etikett<input class="form-control form-control-sm" data-prop="field_label" value="${esc(field.field_label)}"></label>
                    <label class="entity-field-type">Type<select class="form-select form-select-sm" data-prop="field_type">${unknownType}${typeOptions}</select></label>
                    <label class="entity-field-toggle"><input type="checkbox" data-prop="is_visible" ${field.is_visible ? 'checked' : ''} ${lockedIdentity ? 'disabled checked' : ''}> Synlig</label>
                    <label class="entity-field-toggle"><input type="checkbox" data-prop="is_required" ${field.is_required ? 'checked' : ''} ${lockedIdentity ? 'disabled checked' : ''}> Påkrevd</label>
                    <button type="button" class="btn btn-sm btn-outline-danger entity-field-remove" data-remove ${lockedIdentity ? 'disabled' : ''}>Fjern</button>
                </div>
                <details class="mt-2"><summary class="small">Avanserte innstillinger</summary><div class="entity-field-advanced">
                    <label>Standardverdi<input class="form-control form-control-sm" data-prop="default_value" value="${esc(field.default_value)}"></label>
                    <label class="entity-options">Valg (ett per linje)<textarea class="form-control form-control-sm" data-options rows="2">${esc(field.options.join('\n'))}</textarea></label>
                    <label>Min<input class="form-control form-control-sm" data-rule="min" value="${esc(rules.min ?? '')}" inputmode="decimal"></label>
                    <label>Maks<input class="form-control form-control-sm" data-rule="max" value="${esc(rules.max ?? '')}" inputmode="decimal"></label>
                    <label>Min. lengde<input class="form-control form-control-sm" data-rule="min_length" value="${esc(minLength)}" inputmode="numeric"></label>
                    <label>Maks. lengde<input class="form-control form-control-sm" data-rule="max_length" value="${esc(maxLength)}" inputmode="numeric"></label>
                    <label>Mønster<input class="form-control form-control-sm" data-rule="pattern" maxlength="256" value="${esc(rules.pattern ?? '')}" placeholder="f.eks. [A-Z]+"></label>
                </div></details>
                ${rental && field._invalid_validation_rules_hash ? `<div class="alert alert-warning py-2 small mt-2 mb-0">
                    <label class="d-flex align-items-start gap-2"><input type="checkbox" data-repair-email ${field._repair_invalid_validation_rules ? 'checked' : ''}>
                    Erstatt den ugyldige regelen med standard e-postvalidering ved lagring.</label>
                    <span class="d-block mt-1">Dette gjelder bare E-post på dette utleieobjektet. Den gamle regelen beholdes til du bekrefter og lagrer.</span>
                </div>` : ''}`;
                row.querySelector('[data-repair-email]')?.addEventListener('change',event => {
                    if (busy || loading) return;
                    field._repair_invalid_validation_rules = event.currentTarget.checked;
                    if (event.currentTarget.checked) field.validation_rules = {};
                    dirty = true;
                    render();
                });
                row.querySelectorAll('[data-prop]').forEach(control => {
                    control.addEventListener('input', () => {
                        if (busy) return;
                        const key = control.dataset.prop;
                        field[key] = control.type === 'checkbox' ? control.checked : control.value;
                        dirty = true;
                    });
                    control.addEventListener('change', () => {
                        if (busy) return;
                        const key = control.dataset.prop;
                        field[key] = control.type === 'checkbox' ? control.checked : control.value;
                        dirty = true;
                    });
                });
                row.querySelector('[data-options]').addEventListener('input', event => {
                    if (busy) return;
                    field.options = event.currentTarget.value.split('\n').map(item => item.trim()).filter(Boolean);
                    dirty = true;
                });
                row.querySelectorAll('[data-rule]').forEach(control => control.addEventListener('input', () => {
                    if (busy) return;
                    const key = control.dataset.rule;
                    const aliases = key === 'min_length' ? ['minLength'] : key === 'max_length' ? ['maxLength'] : [];
                    aliases.forEach(alias => delete field.validation_rules[alias]);
                    if (control.value === '') delete field.validation_rules[key];
                    else field.validation_rules[key] = ['max_length', 'min_length'].includes(key) ? Number(control.value) : control.value;
                    dirty = true;
                }));
                row.querySelector('[data-remove]').addEventListener('click', () => {
                    fields.splice(index, 1);
                    selectedName=null;
                    fields.forEach((item, order) => item.sort_order = order);
                    dirty = true;
                    render();
                });
                row.querySelectorAll('[data-move]').forEach(button => button.addEventListener('click', () => {
                    let target = index + Number(button.dataset.move);
                    while(options.entityType==='rental_object' && target>=0 && target<fields.length &&
                        definitions.has(fields[target].field_name) && !fields[target].is_visible) target+=Number(button.dataset.move);
                    if (target < 0 || target >= fields.length) return;
                    [fields[index], fields[target]] = [fields[target], fields[index]];
                    fields.forEach((item, order) => item.sort_order = order);
                    dirty = true;
                    render();
                }));
                list.appendChild(row);
            });
        };

        const applyPreset = () => {
            if (busy || loading) return;
            const key = host.querySelector('[data-preset]').value;
            const preset = presets.find(item => String(item.key) === key);
            if (!preset) return;
            if (fields.length && !window.confirm('Dette erstatter dagens feltoppsett. Innsendte svar beholdes. Vil du fortsette?')) return;
            const brokenEmail = fields.find(field => field._invalid_validation_rules_hash);
            fields = (preset.fields || []).map((field,index) => normalize(field,index));
            // Applying a preset must never implicitly repair an existing bad
            // rule or discard the fingerprint required for a scoped repair.
            if (brokenEmail) {
                const email = fields.find(field => field.field_name === brokenEmail.field_name);
                if (email) {
                    email._invalid_validation_rules_hash = brokenEmail._invalid_validation_rules_hash;
                    email._repair_invalid_validation_rules = !!brokenEmail._repair_invalid_validation_rules;
                } else fields.push(brokenEmail);
            }
            selectedName = null;
            fields.forEach((field, index) => field.sort_order = index);
            dirty = true;
            render();
            setStatus(`Forhåndsoppsettet «${preset.label}» er lagt inn.`, false);
        };
        host.querySelector('[data-apply-preset]').addEventListener('click', applyPreset);
        host.querySelector('[data-add]').addEventListener('click', () => {
            if (busy || loading) return;
            if (selectedName && dirty && !confirmClose()) return;
            fields.push(normalize({field_name: `custom_${Date.now()}_${fields.length}`, field_label: 'Nytt felt', field_type: 'text', is_visible: true}, fields.length));
            selectedName=fields[fields.length-1].field_name;
            dirty = true;
            render();
        });
        const presetSelect = host.querySelector('[data-preset]');

        async function refresh() {
            if (!current() || busy || (dirty && !confirmClose())) return false;
            const revision = ++refreshRevision;
            loading = true;
            ready = false;
            host.querySelector('[data-preset]').disabled = true;
            host.querySelector('[data-apply-preset]').disabled = true;
            host.querySelector('[data-add]').disabled = true;
            const saveButton = host.querySelector('[data-save]');
            if (saveButton) saveButton.disabled = true;
            host.querySelector('[data-fields-list]').innerHTML = '<div class="entity-fields-loading"><span class="entity-skeleton"></span><span class="entity-skeleton"></span></div>';
            try {
                const result = await load(options.entityType, options.entityId);
                if (revision !== refreshRevision || !current()) return result;
                csrfToken = result.csrf_token || window.entityFormCsrfToken || '';
                serverRevision = result.revision || '';
                selectedName = null;
                if (!options.createMode && result.registration_type) registrationType = result.registration_type;
                fields = (result.fields || []).map((field,index) => normalize(field,index,!!(options.strictRentalValidation || options.strictEventValidation))).sort((a, b) => a.sort_order - b.sort_order);
                presets = result.presets || [];
                presetSelect.innerHTML = '<option value="">Forhåndsoppsett</option>' + presets.map(item => `<option value="${esc(item.key)}">${esc(item.label)}</option>`).join('');
                const notice = host.querySelector('[data-fields-notice]');
                const disabled = options.entityType === 'event' && registrationType === 'none';
                notice.hidden = !disabled;
                notice.textContent = disabled ? 'Dette arrangementet har ingen påmelding. Skjemafelt kan ikke legges til.' : '';
                host.querySelector('[data-add]').disabled = disabled;
                host.querySelector('[data-apply-preset]').disabled = disabled;
                presetSelect.disabled = disabled || !presets.length;
                dirty = false;
                savedFields = copy(fields);
                render();
                ready = true;
                if (saveButton) saveButton.disabled = options.entityType === 'event' && registrationType === 'none';
                return result;
            } catch (error) {
                if (revision !== refreshRevision || !current()) throw error;
                ready = false;
                host.querySelector('[data-fields-list]').innerHTML = `<div class="entity-fields-error" role="alert">${esc(error.message)} <button class="btn btn-sm btn-link" type="button" data-retry>Prøv igjen</button></div>`;
                host.querySelector('[data-retry]').addEventListener('click', refresh);
                throw error;
            } finally {
                if (revision === refreshRevision) loading = false;
            }
        }

        async function save() {
            if (!current() || busy) return false;
            if (!ready || loading) {
                setStatus('Vent til skjemafeltene er lastet før du lagrer.', true);
                return false;
            }
            if (options.entityType === 'event' && registrationType === 'none' && fields.some(field => field.is_visible)) {
                setStatus('Arrangement uten påmelding kan ikke ha synlige skjemafelt.', true);
                return false;
            }
            if (options.entityType === 'event' && registrationType !== 'none') {
                const visible = new Set(fields.filter(field => field.is_visible).map(field => field.field_name));
                const byName = new Map(fields.map(field => [field.field_name, field]));
                if (!['first_name', 'last_name'].every(name => visible.has(name) && byName.get(name)?.is_required) ||
                    !['email', 'phone'].some(name => visible.has(name))) {
                    setStatus('Fornavn og etternavn må være påkrevde, og minst ett kontaktfelt må være synlig.', true);
                    return false;
                }
            }
            if (options.createMode) {
                savedFields=copy(fields);dirty=false;selectedName=null;render();
                setStatus('Feltendringer beholdt. De lagres når du oppretter arrangementet eller objektet.',false);
                return true;
            }
            busy = true;
            host.setAttribute('aria-busy','true');
            host.querySelectorAll('input,select,textarea,button').forEach(control=>{
                control.dataset.disabledBeforeSave=String(control.disabled);control.disabled=true;
            });
            const button = host.querySelector('[data-save]');
            if (button) { button.disabled = true; button.classList.add('save-busy'); }
            setStatus('Lagrer skjemafelt…', false);
            try {
                const response = await fetch('/api/save_entity_form_fields.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({entity_type: options.entityType, entity_id: options.entityId, fields:copy(fields), expected_revision:serverRevision, csrf_token: csrfToken || window.entityFormCsrfToken})
                });
                const result = await response.json();
                if (!current()) return false;
                if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke lagre skjemafelt');
                fields = (result.fields || fields).map(normalize);
                serverRevision = result.revision || serverRevision;
                savedFields = copy(fields);
                selectedName = null;
                dirty = false;
                render();
                setStatus('Skjemafelt lagret.', false);
                if (typeof options.onSaved === 'function') options.onSaved();
                return true;
            } catch (error) {
                setStatus(error.message || 'Kunne ikke lagre skjemafelt. Utkastet er beholdt.', true);
                return false;
            } finally {
                busy = false;
                if (current()) {
                    host.removeAttribute('aria-busy');
                    host.querySelectorAll('[data-disabled-before-save]').forEach(control=>{
                        control.disabled=control.dataset.disabledBeforeSave==='true';delete control.dataset.disabledBeforeSave;
                    });
                    if (button) { button.disabled = false; button.classList.remove('save-busy'); }
                }
            }
        }

        host.querySelector('[data-save]')?.addEventListener('click', save);
        return {
            refresh,
            save,
            confirmClose,
            isBusy: () => busy,
            isDirty: () => dirty,
            getFields: () => copy(fields),
            getCsrfToken: () => csrfToken || window.entityFormCsrfToken || '',
            isReady: () => ready && !loading && !!(csrfToken || window.entityFormCsrfToken),
            setRegistrationType: async type => {
                if (busy) return;
                const previousType = registrationType;
                registrationType = type;
                const disabled = options.entityType === 'event' && type === 'none';
                const notice = host.querySelector('[data-fields-notice]');
                notice.hidden = !disabled;
                notice.textContent = disabled ? 'Dette arrangementet har ingen påmelding. Skjemafelt kan ikke legges til.' : '';
                if (disabled) {
                    host.querySelector('[data-add]').disabled = true;
                    host.querySelector('[data-apply-preset]').disabled = true;
                    fields = [];
                    render();
                } else if (options.createMode && previousType !== type) {
                    await refresh();
                } else if (!options.createMode) {
                    host.querySelector('[data-add]').disabled = false;
                    host.querySelector('[data-apply-preset]').disabled = !presets.length;
                }
            },
            reset: () => {
                if (busy) return;
                refreshRevision++;
                loading = false;
                fields = [];
                savedFields = [];
                selectedName = null;
                dirty = false;
                ready = false;
                registrationType = '';
                host.querySelector('[data-add]').disabled = true;
                host.querySelector('[data-apply-preset]').disabled = true;
                host.querySelector('[data-preset]').disabled = true;
                const saveButton = host.querySelector('[data-save]');
                if (saveButton) saveButton.disabled = true;
                render();
            }
        };
    }

    window.EntityFormFields = {load, mount};
})();
