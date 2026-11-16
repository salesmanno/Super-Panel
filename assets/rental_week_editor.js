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
        if (group.start_day === 7 && group.end_day === 1) return [7,1];
        const result = [];
        for (let day = group.start_day; day <= group.end_day; day++) result.push(day);
        return result;
    }
    function groupsState(container) {
        const input = container?.querySelector('[data-weekly-groups]');
        const parsed = parseJson(input?.value ?? '[]', null);
        if (!Array.isArray(parsed)) return {valid:false, groups:[], error:'Lagrede prisgrupper har ugyldig format. Kontroller prisdata før du lagrer.'};
        if (parsed.length > 2) return {valid:false, groups:[], error:'Lagrede priser inneholder mer enn to grupper. Kontroller prisdata før du lagrer.'};
        const slots = new Set(), occupied = new Set(), groups = [];
        for (const item of parsed) {
            const slot = Number(item?.slot), start = Number(item?.start_day), end = Number(item?.end_day);
            const priceText = String(item?.price ?? '');
            if (![1,2].includes(slot) || !Number.isInteger(start) || start < 1 || start > 7
                || !Number.isInteger(end) || end < 1 || end > 7
                || (start >= end && !(start === 7 && end === 1))
                || !amountPattern.test(priceText) || !Number.isFinite(Number(priceText)) || Number(priceText) > amountLimit) {
                return {valid:false, groups:[], error:'En lagret prisgruppe må ha stigende dager, minst to dager og et gyldig beløp. Kontroller prisdata før du lagrer.'};
            }
            if (slots.has(slot)) return {valid:false, groups:[], error:'Lagrede prisgrupper har duplisert plassnummer. Kontroller prisdata før du lagrer.'};
            slots.add(slot);
            const group = {slot, start_day:start, end_day:end, price:Number(priceText).toFixed(2)};
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
        start = Number(start); end = Number(end);
        if (!Number.isInteger(start) || !Number.isInteger(end) || start < 1 || start > 7 || end < 1 || end > 7 || start === end) {
            return {valid:false, reason:'En prisgruppe må inneholde minst to sammenhengende dager.'};
        }
        const first = Math.min(start, end), last = Math.max(start, end);
        const wrapsWeek = start === 7 && end === 1;
        const normalizedStart = wrapsWeek ? 7 : first;
        const normalizedEnd = wrapsWeek ? 1 : last;
        const candidate = wrapsWeek ? [7,1] : [];
        if (!wrapsWeek) for (let day = first; day <= last; day++) candidate.push(day);
        if (groups.some(group => candidate.some(value => daysInGroup(group).includes(value)))) {
            return {valid:false, reason:'Perioden overlapper med en eksisterende prisgruppe.'};
        }
        if (groups.length >= 2) return {valid:false, reason:'Du kan legge til maksimalt to prisgrupper.'};
        return {valid:true, start:normalizedStart, end:normalizedEnd, days:candidate};
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
                return `<div class="rental-week-group rental-week-group-${group.slot}" data-group-slot="${group.slot}">
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
        host.querySelectorAll('[data-group-edit],[data-group-remove]').forEach(button => {
            button.disabled = !!container._weeklyPendingPair || !!container._weeklyEditingSlot;
        });
        const pendingDays = new Set();
        if (container._weeklyPendingPair) {
            if (container._weeklyPendingPair.end) {
                daysInGroup({start_day:container._weeklyPendingPair.start,end_day:container._weeklyPendingPair.end})
                    .forEach(day => pendingDays.add(day));
            } else pendingDays.add(container._weeklyPendingPair.start);
        }
        const groupedDays = new Set(state.valid ? state.groups.flatMap(daysInGroup) : []);
        container.querySelectorAll('[data-weekday-pick]').forEach(button => {
            const day = Number(button.dataset.weekdayPick);
            const group = state.valid ? state.groups.find(item => daysInGroup(item).includes(day)) : null;
            const selected = pendingDays.has(day);
            const range = selected ? container._weeklyPendingPair : group;
            const position = !range ? '' : day === range.start_day || day === range.start
                ? 'start' : day === range.end_day || day === range.end ? 'end' : 'middle';
            button.dataset.groupPosition = position;
            let marker = button.querySelector('.rental-weekday-range');
            if (!marker) {
                marker = document.createElement('span');
                marker.className = 'rental-weekday-range';
                marker.setAttribute('aria-hidden', 'true');
                button.prepend(marker);
            }
            marker.textContent = position === 'start' ? '<' : position === 'end' ? '>' : position ? '-' : '';
            marker.hidden = !position;
            button.classList.toggle('is-pending', selected);
            button.classList.toggle('is-in-group', !!group);
            button.classList.toggle('is-group-two', group?.slot === 2);
            button.setAttribute('aria-pressed', selected || !!group ? 'true' : 'false');
            button.disabled = !state.valid || !!container._weeklyEditingSlot
                || !!container._weeklyPendingPair?.end || groupedDays.has(day) || state.groups.length >= 2;
            const price = container.querySelector(`[data-daily-rate="${day}"]`);
            if (price) {
                price.classList.toggle('is-coupled', selected || !!group);
                price.required = !group && !selected;
                price.removeAttribute('aria-readonly');
            }
        });
        return state;
    }
    function closePriceForm(container, message = '') {
        const panel = container.querySelector('[data-weekly-price-form]');
        const pending = container._weeklyPendingPair;
        const editSlot = container._weeklyEditingSlot;
        if (panel) panel.hidden = true;
        container._weeklyPendingPair = null;
        container._weeklyEditingSlot = null;
        if (message) container.querySelector('[data-weekly-status]').textContent = message;
        renderGroups(container);
        const focus = pending
            ? container.querySelector(`[data-weekday-pick="${pending.focusDay}"]`)
            : container.querySelector(`[data-group-edit="${editSlot}"]`);
        if (focus && !focus.disabled) focus.focus();
        else {
            const group = pending && groupsState(container).groups.find(item => daysInGroup(item).includes(pending.focusDay));
            const target = group
                ? container.querySelector(`[data-group-edit="${group.slot}"]`)
                : container.querySelector('[data-weekday-pick]:not(:disabled)');
            target?.focus();
        }
    }
    function openPriceForm(container, context) {
        const panel = container.querySelector('[data-weekly-price-form]');
        const input = panel?.querySelector('[data-weekly-price-input]');
        if (!panel || !input) return;
        container._weeklyPendingPair = context.kind === 'new' ? context : null;
        container._weeklyEditingSlot = context.kind === 'edit' ? context.slot : null;
        panel.querySelector('[data-weekly-price-title]').textContent = context.kind === 'edit' ? 'Rediger totalpris' : 'Sett totalpris';
        panel.querySelector('[data-price-pair]').textContent = `${dayNames[context.start - 1]}–${dayNames[context.end - 1]}`;
        input.value = context.price ?? '';
        const error = panel.querySelector('[data-weekly-price-error]');
        error.hidden = true;
        error.textContent = '';
        panel.hidden = false;
        renderGroups(container);
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
            state.groups.push({slot, start_day:valid.start, end_day:valid.end, price:Number(value).toFixed(2)});
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
        container.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || !event.target.closest('[data-weekly-price-form]')) return;
            event.preventDefault();
            event.stopPropagation();
            closePriceForm(container, 'Prisredigering avbrutt. Ingen endring ble lagret.');
        });
        container.addEventListener('click', event => {
            if (event.target.closest('[data-dialog-cancel]')) {
                closePriceForm(container, 'Prisredigering avbrutt. Ingen endring ble lagret.');
                return;
            }
            if (event.target.closest('[data-dialog-confirm]')) { confirmPrice(container); return; }
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
                    container.querySelector('[data-weekly-status]').textContent = `${dayNames[day - 1]} valgt. Velg siste dag i perioden.`;
                    renderGroups(container);
                    return;
                }
                const first = container._weeklyPendingPair.start;
                if (day === first) {
                    closePriceForm(container, 'Valget er nullstilt. Velg to ulike dager.');
                    return;
                }
                const range = validateRange(first, day, state.groups);
                if (!range.valid) {
                    container.querySelector('[data-weekly-status]').textContent = range.reason;
                    return;
                }
                container._weeklyPendingPair = {...container._weeklyPendingPair, start:range.start, end:range.end, focusDay:day};
                container.querySelector('[data-weekly-status]').textContent = `${dayNames[range.start - 1]}–${dayNames[range.end - 1]} valgt. Legg inn én totalpris for perioden.`;
                renderGroups(container);
                openPriceForm(container, {kind:'new', start:range.start, end:range.end, focusDay:day});
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
                container.querySelector('[data-weekly-status]').textContent = 'Prisgruppe fjernet. Dagsprisene er tilbake i bruk.';
                renderGroups(container);
                container.querySelector('[data-weekday-pick]:not(:disabled)')?.focus();
            }
        });
        renderGroups(container);
    }
    window.RentalWeekEditor = {
        setup,
        refresh: renderGroups,
        model: () => 'weekly',
        dailyPrices: container => Object.fromEntries(days.map(day => [day.key, container?.querySelector(`[name="daily_prices[${day.key}]"]`)?.value ?? ''])),
        groups: readGroups,
        validateRange,
        days,
        amountLimit
    };
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-rental-week-editor]').forEach(setup));
})();
