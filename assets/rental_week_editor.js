(function () {
    'use strict';
    const days = [
        {key:'monday', label:'Mandag'}, {key:'tuesday', label:'Tirsdag'},
        {key:'wednesday', label:'Onsdag'}, {key:'thursday', label:'Torsdag'},
        {key:'friday', label:'Fredag'}, {key:'saturday', label:'Lørdag'},
        {key:'sunday', label:'Søndag'}
    ];
    const dayNames = days.map(day => day.label);
    const amountPattern = /^\d+(?:\.\d{1,2})?$/;
    const amountLimit = 99999999.99;

    function parseJson(raw, fallback) {
        if (raw === null || raw === undefined || raw === '') return fallback;
        if (typeof raw !== 'string') return raw;
        try { return JSON.parse(raw); } catch (_) { return fallback; }
    }
    function daysInGroup(group) {
        const result = [];
        let day = group.start_day;
        for (let count = 0; count < 7; count++) {
            result.push(day);
            if (day === group.end_day) break;
            day = day % 7 + 1;
        }
        return result;
    }
    function groupsState(container) {
        const input = container?.querySelector('[data-weekly-groups]');
        const raw = input?.value ?? '[]';
        const parsed = parseJson(raw, null);
        if (!Array.isArray(parsed)) return {valid:false, groups:[], error:'Lagrede prisgrupper har ugyldig format. Kontroller prisdata før du lagrer.'};
        if (parsed.length > 2) return {valid:false, groups:[], error:'Lagrede priser inneholder mer enn to grupper. Kontroller prisdata før du lagrer.'};
        const slots = new Set(), occupied = new Set(), groups = [];
        for (const item of parsed) {
            const slot = Number(item?.slot), start = Number(item?.start_day), end = Number(item?.end_day);
            const priceText = String(item?.price ?? '');
            if (![1,2].includes(slot) || !Number.isInteger(start) || start < 1 || start > 7
                || !Number.isInteger(end) || end < 1 || end > 7 || !amountPattern.test(priceText)
                || !Number.isFinite(Number(priceText)) || Number(priceText) > amountLimit) {
                return {valid:false, groups:[], error:'En lagret prisgruppe har ugyldig dag, slot eller beløp. Kontroller prisdata før du lagrer.'};
            }
            if (slots.has(slot)) return {valid:false, groups:[], error:'Lagrede prisgrupper har duplisert plassnummer. Kontroller prisdata før du lagrer.'};
            slots.add(slot);
            const group = {slot, start_day:start, end_day:end, price:Number(priceText).toFixed(2)};
            const span = ((end - start + 7) % 7) + 1;
            if (span < 2) return {valid:false, groups:[], error:'En lagret prisgruppe må inneholde minst to ukedager.'};
            for (const day of daysInGroup(group)) {
                if (occupied.has(day)) return {valid:false, groups:[], error:'Lagrede prisgrupper overlapper. Fjern eller korriger gruppene før du lagrer.'};
                occupied.add(day);
            }
            groups.push(group);
        }
        groups.sort((a,b) => a.slot - b.slot);
        return {valid:true, groups, error:''};
    }
    function readGroups(container) {
        const state = groupsState(container);
        if (!state.valid) throw new Error(state.error);
        return state.groups;
    }
    function validateRange(start, end, groups) {
        const span = ((end - start + 7) % 7) + 1;
        if (span < 2) return {valid:false, reason:'En prisgruppe må inneholde minst to ukedager.'};
        const candidate = [];
        let day = start;
        for (let i = 0; i < span; i++) { candidate.push(day); day = day % 7 + 1; }
        if (groups.some(group => candidate.some(value => daysInGroup(group).includes(value)))) {
            return {valid:false, reason:'Perioden overlapper med en eksisterende prisgruppe.'};
        }
        if (groups.length >= 2) return {valid:false, reason:'Du kan legge til maksimalt to prisgrupper.'};
        return {valid:true, days:candidate};
    }
    function renderGroups(container) {
        const host = container.querySelector('[data-weekly-group-list]');
        const input = container.querySelector('[data-weekly-groups]');
        const state = groupsState(container);
        if (!host || !input) return state;
        if (!state.valid) {
            host.innerHTML = `<p class="rental-week-group-error mb-0" role="alert">${state.error}</p>`;
        } else {
            input.value = JSON.stringify(state.groups);
            host.innerHTML = state.groups.length ? state.groups.map(group => {
                const label = `${dayNames[group.start_day - 1]}–${dayNames[group.end_day - 1]}`;
                return `<div class="rental-week-group" data-group-slot="${group.slot}">
                    <span class="rental-week-group-slot">Gruppe ${group.slot}</span>
                    <span class="rental-week-group-period">${label}</span>
                    <strong>${Number(group.price).toLocaleString('nb-NO',{minimumFractionDigits:2,maximumFractionDigits:2})} NOK totalt</strong>
                    <span class="rental-week-group-actions">
                        <button type="button" data-group-edit="${group.slot}" aria-label="Rediger pris for ${label}">Rediger</button>
                        <button type="button" data-group-remove="${group.slot}" aria-label="Fjern ${label}">Fjern</button>
                    </span>
                </div>`;
            }).join('') : '<p class="rental-week-empty mb-0">Ingen prisgrupper lagt til ennå.</p>';
        }
        const dayButtons = container.querySelectorAll('[data-weekday-pick]');
        dayButtons.forEach(button => {
            const value = Number(button.dataset.weekdayPick);
            const used = state.valid && state.groups.some(group => daysInGroup(group).includes(value));
            button.disabled = !state.valid || (used && !container._weeklyPendingPair)
                || (state.groups.length >= 2 && !container._weeklyPendingPair);
            button.classList.toggle('is-in-group', used);
            button.setAttribute('aria-pressed', button.classList.contains('is-pending') || used ? 'true' : 'false');
        });
        return state;
    }
    function closePriceForm(container, message = '') {
        const panel = container.querySelector('[data-weekly-price-form]');
        const pending = container._weeklyPendingPair;
        const editSlot = container._weeklyEditingSlot;
        if (panel) panel.hidden = true;
        if (pending) {
            container._weeklyPendingPair = null;
            container.querySelectorAll('[data-weekday-pick]').forEach(button => button.classList.remove('is-pending'));
        }
        container._weeklyEditingSlot = null;
        if (message) container.querySelector('[data-weekly-status]').textContent = message;
        renderGroups(container);
        const focus = pending
            ? container.querySelector(`[data-weekday-pick="${pending.focusDay}"]`)
            : container.querySelector(`[data-group-edit="${editSlot}"]`);
        focus?.focus();
    }
    function openPriceForm(container, context) {
        const panel = container.querySelector('[data-weekly-price-form]');
        const input = panel?.querySelector('[data-weekly-price-input]');
        if (!panel || !input) return;
        container._weeklyPendingPair = context.kind === 'new' ? context : null;
        container._weeklyEditingSlot = context.kind === 'edit' ? context.slot : null;
        panel.querySelector('[data-weekly-price-title]').textContent = context.kind === 'edit' ? 'Rediger totalpris' : 'Sett totalpris';
        panel.querySelector('[data-price-pair]').textContent = context.kind === 'edit'
            ? `${dayNames[context.start - 1]} til ${dayNames[context.end - 1]}`
            : `${dayNames[context.start - 1]} til ${dayNames[context.end - 1]}`;
        input.value = context.price ?? '';
        const error = panel.querySelector('[data-weekly-price-error]');
        error.hidden = true;
        error.textContent = '';
        panel.hidden = false;
        requestAnimationFrame(() => input.focus());
    }
    function confirmPrice(container) {
        const panel = container.querySelector('[data-weekly-price-form]');
        const input = panel?.querySelector('[data-weekly-price-input]');
        const error = panel?.querySelector('[data-weekly-price-error]');
        const value = String(input?.value ?? '').trim().replace(',', '.');
        if (!amountPattern.test(value) || !Number.isFinite(Number(value)) || Number(value) > amountLimit) {
            error.textContent = 'Skriv inn et beløp fra 0 til 99 999 999,99 med opptil to desimaler.';
            error.hidden = false;
            input?.focus();
            return;
        }
        const state = groupsState(container);
        if (!state.valid) {
            error.textContent = state.error;
            error.hidden = false;
            return;
        }
        const pending = container._weeklyPendingPair;
        const editSlot = container._weeklyEditingSlot;
        if (pending) {
            const valid = validateRange(pending.start, pending.end, state.groups);
            if (!valid.valid) {
                error.textContent = valid.reason;
                error.hidden = false;
                return;
            }
            const slot = state.groups.some(group => group.slot === 1) ? 2 : 1;
            state.groups.push({slot, start_day:pending.start, end_day:pending.end, price:Number(value).toFixed(2)});
        } else if (editSlot) {
            const group = state.groups.find(item => item.slot === editSlot);
            if (!group) {
                error.textContent = 'Prisgruppen ble ikke funnet. Oppdater skjemaet før du lagrer.';
                error.hidden = false;
                return;
            }
            group.price = Number(value).toFixed(2);
        }
        container.querySelector('[data-weekly-groups]').value = JSON.stringify(state.groups);
        closePriceForm(container, pending ? 'Prisgruppe lagret i skjemaet.' : 'Pris oppdatert.');
    }
    function setup(container) {
        if (!container || container.dataset.weekEditorReady === '1') return;
        container.dataset.weekEditorReady = '1';
        container.addEventListener('change', event => {
            if (!event.target.matches('[name="pricing_model"]')) return;
            const weekly = event.target.value === 'weekly';
            container.querySelector('[data-weekly-rate-panel]').hidden = !weekly;
            container.querySelector('[data-weekly-groups-panel]').hidden = !weekly;
            const form = container.closest('form');
            form?.querySelectorAll('[data-legacy-pricing-control]').forEach(node => {
                if (node.dataset.legacyOriginalHidden === undefined) node.dataset.legacyOriginalHidden = node.hidden ? '1' : '0';
                node.hidden = weekly || node.dataset.legacyOriginalHidden === '1';
            });
            form?.querySelectorAll('[name="rental_price"],[name^="weekday_prices["],[name="weekday_prices_enabled"],[name="weekend_price"],[name="saturday_price"],[name="sunday_price"]').forEach(input => {
                input.disabled = weekly;
                if (input.name === 'rental_price') input.required = !weekly && !form.querySelector('[name="weekday_prices_enabled"]')?.checked;
            });
            container.querySelectorAll('[name^="daily_prices["]').forEach(input => { input.disabled = !weekly; input.required = weekly; });
            if (weekly) closePriceForm(container);
        });
        container.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || !event.target.closest('[data-weekly-price-form]')) return;
            event.preventDefault();
            event.stopPropagation();
            closePriceForm(container, 'Prisredigering avbrutt. Ingen endring ble lagret.');
        });
        container.addEventListener('click', event => {
            const cancel = event.target.closest('[data-dialog-cancel]');
            if (cancel) {
                closePriceForm(container, 'Prisredigering avbrutt. Ingen endring ble lagret.');
                return;
            }
            const confirm = event.target.closest('[data-dialog-confirm]');
            if (confirm) { confirmPrice(container); return; }
            const button = event.target.closest('[data-weekday-pick]');
            if (button && !button.disabled) {
                const state = groupsState(container);
                if (!state.valid) {
                    container.querySelector('[data-weekly-status]').textContent = state.error;
                    return;
                }
                const day = Number(button.dataset.weekdayPick);
                if (!container._weeklyPendingPair) {
                    container._weeklyPendingPair = {start:day, focusDay:day};
                    button.classList.add('is-pending');
                    button.setAttribute('aria-pressed', 'true');
                    container.querySelector('[data-weekly-status]').textContent = `${dayNames[day - 1]} valgt. Velg siste dag i perioden.`;
                    return;
                }
                const start = container._weeklyPendingPair.start;
                const result = validateRange(start, day, state.groups);
                if (!result.valid) {
                    container.querySelector('[data-weekly-status]').textContent = result.reason;
                    container._weeklyPendingPair = null;
                    container.querySelectorAll('[data-weekday-pick]').forEach(item => item.classList.remove('is-pending'));
                    renderGroups(container);
                    return;
                }
                container._weeklyPendingPair = {...container._weeklyPendingPair, end:day, focusDay:day};
                container.querySelectorAll('[data-weekday-pick]').forEach(item => item.classList.remove('is-pending'));
                result.days.forEach(value => {
                    const selected = container.querySelector(`[data-weekday-pick="${value}"]`);
                    selected?.classList.add('is-pending');
                    selected?.setAttribute('aria-pressed', 'true');
                });
                openPriceForm(container, {kind:'new', start, end:day, focusDay:day});
                return;
            }
            const edit = event.target.closest('[data-group-edit]');
            if (edit) {
                const state = groupsState(container);
                if (!state.valid) { container.querySelector('[data-weekly-status]').textContent = state.error; return; }
                const group = state.groups.find(item => item.slot === Number(edit.dataset.groupEdit));
                if (group) openPriceForm(container, {kind:'edit', slot:group.slot, start:group.start_day, end:group.end_day, price:group.price});
                return;
            }
            const remove = event.target.closest('[data-group-remove]');
            if (remove) {
                const state = groupsState(container);
                if (!state.valid) { container.querySelector('[data-weekly-status]').textContent = state.error; return; }
                const groups = state.groups.filter(item => item.slot !== Number(remove.dataset.groupRemove));
                groups.forEach((group, index) => { group.slot = index + 1; });
                container.querySelector('[data-weekly-groups]').value = JSON.stringify(groups);
                container.querySelector('[data-weekly-status]').textContent = 'Prisgruppe fjernet.';
                renderGroups(container);
                container.querySelector('[data-weekday-pick]:not(:disabled)')?.focus();
            }
        });
        renderGroups(container);
    }
    window.RentalWeekEditor = {
        setup,
        model: container => container?.querySelector('[name="pricing_model"]:checked')?.value || 'legacy',
        dailyPrices: container => Object.fromEntries(days.map(day => [day.key, container?.querySelector(`[name="daily_prices[${day.key}]"]`)?.value ?? ''])),
        groups: readGroups,
        validateRange,
        days,
        amountLimit
    };
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-rental-week-editor]').forEach(setup));
})();
