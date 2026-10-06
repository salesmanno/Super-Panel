(function () {
    'use strict';

    const TYPES = ['text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'checkbox'];
    const TYPE_LABELS = {text:'Tekst',email:'E-post',tel:'Telefon',number:'Tall',date:'Dato',textarea:'Lang tekst',select:'Nedtrekksvalg',checkbox:'Avkrysning'};
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[char]));
    const copy = value => JSON.parse(JSON.stringify(value));

    async function load(entityType, entityId) {
        const params = new URLSearchParams({entity_type: entityType});
        if (entityId !== null && entityId !== undefined && entityId !== '') params.set('entity_id', entityId);
        const response = await fetch(`/api/get_entity_form_fields.php?${params.toString()}`, {credentials: 'same-origin'});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke laste skjemafelt');
        if (result.csrf_token) window.entityFormCsrfToken = result.csrf_token;
        return result;
    }

    function normalize(field, index) {
        let options = field.options;
        if (typeof options === 'string') {
            try { options = JSON.parse(options); } catch (_) { options = []; }
        }
        let validationRules = field.validation_rules;
        if (typeof validationRules === 'string') {
            try { validationRules = JSON.parse(validationRules); } catch (_) { validationRules = {}; }
        }
        return {
            ...field,
            field_name: String(field.field_name || `custom_${index + 1}`),
            field_label: String(field.field_label || ''),
            field_type: String(field.field_type || 'text'),
            is_required: !!Number(field.is_required),
            is_visible: field.is_visible === undefined ? true : !!Number(field.is_visible),
            sort_order: Number.isFinite(Number(field.sort_order)) ? Number(field.sort_order) : index,
            default_value: field.default_value ?? '',
            options: Array.isArray(options) ? options : [],
            validation_rules: validationRules && typeof validationRules === 'object' && !Array.isArray(validationRules) ? validationRules : {}
        };
    }

    function mount(host, options) {
        let fields = [];
        let presets = [];
        let busy = false;
        let dirty = false;
        let loading = false;
        let ready = false;
        let refreshRevision = 0;
        let csrfToken = '';
        let registrationType = options.registrationType || '';
        const setStatus = (message, isError) => {
            const status = host.querySelector('[data-fields-status]');
            if (status) {
                status.textContent = message;
                status.classList.toggle('text-danger', !!isError);
                status.classList.toggle('text-success', !!message && !isError);
            }
        };

        host.innerHTML = `<section class="entity-fields-editor" aria-label="Skjemafelt">
            <div class="entity-fields-head">
                <div><span class="entity-fields-kicker">Deltakerinformasjon</span><h6 class="mb-1">Skjemafelt</h6>
                <p class="small text-muted mb-0">Feltene følger dette ${options.entityType === 'event' ? 'arrangementet' : 'utleieobjektet'}.</p></div>
                <div class="entity-fields-presets"><label class="visually-hidden" for="${host.id}-preset">Forhåndsoppsett</label>
                    <select class="form-select form-select-sm" id="${host.id}-preset" data-preset disabled><option value="">Forhåndsoppsett</option></select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-apply-preset disabled>Bruk</button>
                </div>
            </div>
            <div data-fields-notice class="alert alert-info py-2 small mb-3" hidden></div>
            <fieldset data-common-fields class="mb-3" hidden><legend class="fs-6">Velg vanlige felt</legend>
                <p class="small text-muted">Kryss av feltene kunden skal fylle ut. Egne felt og detaljer kan tilpasses nedenfor.</p>
                <div data-common-list class="d-flex flex-wrap gap-3"></div>
            </fieldset>
            <div data-fields-list aria-live="polite"><div class="entity-fields-loading"><span class="entity-skeleton"></span><span class="entity-skeleton"></span></div></div>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" data-add disabled>Legg til felt</button>
                 ${options.createMode ? '' : '<button type="button" class="btn btn-sm btn-primary ms-auto" data-save disabled>Lagre skjemafelt</button>'}
                <span class="small ms-auto" data-fields-status role="status" aria-live="polite"></span>
            </div>
        </section>`;

        const render = () => {
            const list = host.querySelector('[data-fields-list]');
            if (options.entityType==='event') fields.forEach(field=>{
                if (['first_name','last_name'].includes(field.field_name)) {field.is_visible=true;field.is_required=true;}
            });
            const common = host.querySelector('[data-common-list]');
            common.replaceChildren();
            const definitions = new Map((presets[0]?.fields || []).map(field => [field.field_name,field]));
            host.querySelector('[data-common-fields]').hidden = !definitions.size || registrationType === 'none';
            for (const [name,definition] of definitions) {
                const field=fields.find(item=>item.field_name===name);
                const label=document.createElement('label');label.className='form-check';
                const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.className='form-check-input';
                checkbox.dataset.commonField=name;checkbox.checked=!!field?.is_visible;
                checkbox.disabled=!!field?.is_visible && options.entityType==='event' && ['first_name','last_name'].includes(name);
                const text=document.createElement('span');text.textContent=definition.field_label;
                checkbox.addEventListener('change',()=>{
                    let selected=fields.find(item=>item.field_name===name);
                    if (!selected) {selected=normalize(definition,fields.length);fields.push(selected);}
                    selected.is_visible=checkbox.checked;
                    if (!checkbox.checked) selected.is_required=false;
                    dirty=true;render();
                });
                label.append(checkbox,text);common.append(label);
            }
            if (!fields.length) {
                list.innerHTML = '<div class="entity-fields-empty">Ingen ekstra felt. Du kan legge til felter eller velge et forhåndsoppsett.</div>';
                return;
            }
            list.replaceChildren();
            fields.forEach((field, index) => {
                if (options.entityType==='rental_object' && definitions.has(field.field_name) && !field.is_visible) return;
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
                </div></details>`;
                row.querySelectorAll('[data-prop]').forEach(control => {
                    control.addEventListener('input', () => {
                        const key = control.dataset.prop;
                        field[key] = control.type === 'checkbox' ? control.checked : control.value;
                        dirty = true;
                    });
                    control.addEventListener('change', () => {
                        const key = control.dataset.prop;
                        field[key] = control.type === 'checkbox' ? control.checked : control.value;
                        dirty = true;
                    });
                });
                row.querySelector('[data-options]').addEventListener('input', event => {
                    field.options = event.currentTarget.value.split('\n').map(item => item.trim()).filter(Boolean);
                    dirty = true;
                });
                row.querySelectorAll('[data-rule]').forEach(control => control.addEventListener('input', () => {
                    const key = control.dataset.rule;
                    const aliases = key === 'min_length' ? ['minLength'] : key === 'max_length' ? ['maxLength'] : [];
                    aliases.forEach(alias => delete field.validation_rules[alias]);
                    if (control.value === '') delete field.validation_rules[key];
                    else field.validation_rules[key] = ['max_length', 'min_length'].includes(key) ? Number(control.value) : control.value;
                    dirty = true;
                }));
                row.querySelector('[data-remove]').addEventListener('click', () => {
                    fields.splice(index, 1);
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
            const key = host.querySelector('[data-preset]').value;
            const preset = presets.find(item => String(item.key) === key);
            if (!preset) return;
            if (fields.length && !window.confirm('Dette erstatter dagens feltoppsett. Innsendte svar beholdes. Vil du fortsette?')) return;
            fields = (preset.fields || []).map(normalize);
            fields.forEach((field, index) => field.sort_order = index);
            dirty = true;
            render();
            setStatus(`Forhåndsoppsettet «${preset.label}» er lagt inn.`, false);
        };
        host.querySelector('[data-apply-preset]').addEventListener('click', applyPreset);
        host.querySelector('[data-add]').addEventListener('click', () => {
            fields.push(normalize({field_name: `custom_${Date.now()}_${fields.length}`, field_label: 'Nytt felt', field_type: 'text', is_visible: true}, fields.length));
            dirty = true;
            render();
        });
        const presetSelect = host.querySelector('[data-preset]');

        async function refresh() {
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
                if (revision !== refreshRevision) return result;
                csrfToken = result.csrf_token || window.entityFormCsrfToken || '';
                if (!options.createMode && result.registration_type) registrationType = result.registration_type;
                fields = (result.fields || []).map(normalize).sort((a, b) => a.sort_order - b.sort_order);
                if (options.entityType === 'event' && registrationType === 'none') fields = [];
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
                render();
                ready = true;
                if (saveButton) saveButton.disabled = false;
                return result;
            } catch (error) {
                if (revision !== refreshRevision) throw error;
                ready = false;
                host.querySelector('[data-fields-list]').innerHTML = `<div class="entity-fields-error" role="alert">${esc(error.message)} <button class="btn btn-sm btn-link" type="button" data-retry>Prøv igjen</button></div>`;
                host.querySelector('[data-retry]').addEventListener('click', refresh);
                throw error;
            } finally {
                if (revision === refreshRevision) loading = false;
            }
        }

        async function save() {
            if (busy) return false;
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
            busy = true;
            const button = host.querySelector('[data-save]');
            if (button) { button.disabled = true; button.classList.add('save-busy'); }
            setStatus('Lagrer skjemafelt…', false);
            try {
                const response = await fetch('/api/save_entity_form_fields.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({entity_type: options.entityType, entity_id: options.entityId, fields, csrf_token: csrfToken || window.entityFormCsrfToken})
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke lagre skjemafelt');
                fields = (result.fields || fields).map(normalize);
                dirty = false;
                render();
                setStatus('Skjemafelt lagret.', false);
                return true;
            } catch (error) {
                setStatus(error.message || 'Kunne ikke lagre skjemafelt. Utkastet er beholdt.', true);
                return false;
            } finally {
                busy = false;
                if (button) { button.disabled = false; button.classList.remove('save-busy'); }
            }
        }

        host.querySelector('[data-save]')?.addEventListener('click', save);
        return {
            refresh,
            save,
            getFields: () => copy(fields),
            getCsrfToken: () => csrfToken || window.entityFormCsrfToken || '',
            isReady: () => ready && !loading && !!(csrfToken || window.entityFormCsrfToken),
            setRegistrationType: async type => {
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
                refreshRevision++;
                loading = false;
                fields = [];
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
