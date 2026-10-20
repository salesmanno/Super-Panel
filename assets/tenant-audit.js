(() => {
    'use strict';

    const API_URL = new URL('../api/get_audit_logs.php', window.location.href);
    const form = document.getElementById('filter-form');
    const results = document.getElementById('results-content');
    const resultCount = document.getElementById('result-count');
    const resetButton = document.getElementById('reset-filters');
    const refreshButton = document.getElementById('refresh-button');
    const dialog = document.getElementById('audit-dialog');
    const dialogTitle = document.getElementById('dialog-title');
    const dialogContent = document.getElementById('dialog-content');
    const dialogClose = document.getElementById('dialog-close');
    let currentPage = 1;
    let lastFocus = null;

    const text = (value, fallback = '—') => {
        if (value === null || value === undefined || value === '') return fallback;
        if (typeof value === 'object') return safeJson(value);
        return String(value);
    };
    const auditActionLabels = {
        'email.insert': 'E-post lagt i kø',
        'email.queued': 'E-post lagt i kø',
        'email.processing_started': 'E-postbehandling startet',
        'email.smtp_accepted': 'SMTP aksepterte e-posten',
        'email.smtp_failed': 'SMTP-forsøk feilet',
        'email.update': 'E-postkø oppdatert',
        'email.delete': 'E-post fjernet fra kø'
    };
    const auditRoleLabels = {
        admin: 'Administrator',
        owner: 'Eier',
        super_admin: 'Superadministrator',
        staff: 'Ansatt',
        employee: 'Ansatt'
    };
    const auditActorTypeLabels = {
        tenant_admin: 'Brukerkonto',
        system: 'Systemprosess',
        api: 'API'
    };
    const auditModuleLabels = { email: 'E-post' };
    const auditResourceLabels = { email_queue: 'E-postkø' };
    const auditFieldLabels = {
        id: 'Kø-ID',
        template_id: 'Mal-ID',
        module_source: 'Kildemodul',
        source_id: 'Kilde-ID',
        status: 'Status',
        priority: 'Prioritet',
        attempts: 'Forsøk',
        attempt_number: 'Forsøksnummer',
        max_attempts: 'Maksimalt antall forsøk',
        scheduled_at: 'Planlagt tidspunkt',
        next_retry_at: 'Neste forsøk',
        sent_at: 'SMTP-akseptert tidspunkt'
    };
    const auditLinkLabels = {
        queue_id: 'Kø-ID',
        template_id: 'Mal-ID',
        source_id: 'Kilde-ID',
        source_module: 'Kildemodul',
        correlation_id: 'Sporingsreferanse',
        initiator_audit_id: 'Opprinnelig handling',
        template_body_sha256: 'Hash av malinnhold',
        template_subject_sha256: 'Hash av maleemne',
        message_body_sha256: 'Hash av kølagt innhold',
        message_subject_sha256: 'Hash av kølagt emne'
    };
    const auditActionLabel = value => auditActionLabels[String(value)] || text(value);
    const auditModuleLabel = value => auditModuleLabels[String(value)] || text(value);
    const auditResourceLabel = value => auditResourceLabels[String(value)] || text(value);
    const auditFieldLabel = value => auditFieldLabels[String(value)] || String(value);
    const auditActorRoleLabel = value => {
        const role = String(value || '').trim();
        if (!role || role === 'Ikke registrert') return '';
        return auditRoleLabels[role.toLowerCase()] || role;
    };
    const auditActorName = log => {
        const name = text(log.actor_name, 'Ukjent administrator');
        if (log.actor_type === 'system' || String(log.actor_role || '').toLowerCase() === 'system') return name;
        const role = auditActorRoleLabel(log.actor_role);
        return [role, name].filter(Boolean).join(' ');
    };
    const auditActorTypeLabel = value => auditActorTypeLabels[String(value)] || text(value, 'Type ikke registrert');
    const safeJson = value => {
        try {
            const encoded = JSON.stringify(value, null, 2);
            return encoded === undefined ? '—' : encoded;
        } catch (error) {
            return '[Verdien kunne ikke vises]';
        }
    };
    const node = (tag, className, content) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (content !== undefined) element.textContent = content;
        return element;
    };
    const formatDate = value => {
        if (typeof value !== 'string') return { date: text(value), time: '' };
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?/);
        if (!match || !validCalendarDate(Number(match[1]), Number(match[2]), Number(match[3]))) return { date: value, time: '' };
        return { date: `${match[3]}.${match[2]}.${match[1]}`, time: `${match[4]}:${match[5]}` };
    };
    function validCalendarDate(year, month, day) {
        if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day) || year < 1 || month < 1 || month > 12) return false;
        const leap = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
        const daysByMonth = [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        return day >= 1 && day <= daysByMonth[month - 1];
    }
    function canonicalDate(value) {
        if (value === '') return '';
        const match = typeof value === 'string' ? value.match(/^(\d{2})\.(\d{2})\.(\d{4})$/) : null;
        if (!match) return null;
        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3]);
        if (!validCalendarDate(year, month, day)) return null;
        return `${match[3]}-${match[2]}-${match[1]}`;
    }
    function formatSqlDetailValue(key, value) {
        if (typeof value !== 'string') return safeJson(value);
        const keyName = String(key).toLowerCase();
        const isDateField = /date|_at$|timestamp|datetime/.test(keyName);
        const isTimeField = /time/.test(keyName);
        const sqlDateTime = value.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
        if (isDateField && sqlDateTime) {
            const [, year, month, day, hour, minute, second] = sqlDateTime;
            if (validCalendarDate(Number(year), Number(month), Number(day)) && Number(hour) <= 23 && Number(minute) <= 59 && (second === undefined || Number(second) <= 59)) {
                return `${day}.${month}.${year} ${hour}:${minute}`;
            }
        }
        const sqlDate = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (isDateField && sqlDate) {
            const [, year, month, day] = sqlDate;
            if (validCalendarDate(Number(year), Number(month), Number(day))) return `${day}.${month}.${year}`;
        }
        const sqlTime = value.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);
        if (isTimeField && sqlTime) {
            const [, hour, minute, second] = sqlTime;
            if (Number(hour) <= 23 && Number(minute) <= 59 && (second === undefined || Number(second) <= 59)) return `${hour}:${minute}`;
        }
        return safeJson(value);
    }
    const formatOutcome = value => ({
        success: 'Fullført',
        noop: 'Ingen endring',
        denied: 'Avvist',
        failed: 'Feilet',
        rolled_back: 'Rullet tilbake',
        smtp_accepted: 'E-post godtatt',
        legacy: 'Eldre hendelse'
    }[String(value || 'legacy')] || text(value));
    const addCellLabel = (cell, label) => cell.setAttribute('data-label', label);

    async function request(params) {
        const url = new URL(API_URL);
        for (const [key, value] of Object.entries(params)) {
            if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, value);
        }
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        });
        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('Serveren returnerte et svar som ikke kunne leses.');
        }
        if (!response.ok || !payload || payload.success !== true) {
            throw new Error(payload && typeof payload.message === 'string' ? payload.message : 'Aktivitetsloggen kunne ikke hentes.');
        }
        return payload;
    }

    function currentFilters() {
        const data = new FormData(form);
        return {
            from: String(data.get('from') || ''),
            to: String(data.get('to') || ''),
            admin_id: String(data.get('admin_id') || ''),
            module: String(data.get('module') || ''),
            action: String(data.get('action') || '')
        };
    }
    function apiFilters() {
        const filters = currentFilters();
        return {
            ...filters,
            from: canonicalDate(filters.from) || '',
            to: canonicalDate(filters.to) || ''
        };
    }

    function fillSelect(selectId, values, defaultText) {
        const select = document.getElementById(selectId);
        const retained = select.value;
        while (select.options.length > 1) select.remove(1);
        if (Array.isArray(values)) {
            values.forEach(value => {
                const option = document.createElement('option');
                option.value = String(value && typeof value === 'object' ? value.id : value);
                option.textContent = selectId === 'filter-admin'
                    ? (value && typeof value === 'object' ? String(value.name) : `Administrator #${String(value)}`)
                    : (selectId === 'filter-action'
                        ? auditActionLabel(value)
                        : selectId === 'filter-module' ? auditModuleLabel(value) : String(value));
                select.appendChild(option);
            });
        }
        select.options[0].textContent = defaultText;
        select.value = [...select.options].some(option => option.value === retained) ? retained : '';
    }

    function showLoading() {
        results.setAttribute('aria-busy', 'true');
        resultCount.textContent = 'Henter …';
        const state = node('div', 'loading-state');
        state.setAttribute('aria-label', 'Henter aktivitetslogg');
        for (let row = 0; row < 4; row += 1) {
            const line = node('div', 'skeleton-row');
            for (let col = 0; col < 5; col += 1) line.appendChild(node('span', 'skeleton-line'));
            state.appendChild(line);
        }
        results.replaceChildren(state);
    }

    function showError(message) {
        results.setAttribute('aria-busy', 'false');
        resultCount.textContent = 'Kunne ikke lastes';
        const state = node('div', 'error-state');
        state.append(node('div', 'empty-mark', '!'), node('h3', '', 'Aktivitetsloggen er utilgjengelig'));
        state.appendChild(node('p', '', message));
        const retry = node('button', 'btn btn-outline-secondary mt-3', 'Prøv igjen');
        retry.type = 'button';
        retry.addEventListener('click', () => loadLogs(currentPage));
        state.appendChild(retry);
        results.replaceChildren(state);
    }

    function makeOutcome(value) {
        const outcome = node('span', `outcome outcome-${String(value || 'legacy').replace(/[^a-z0-9_-]/gi, '')}`, formatOutcome(value));
        return outcome;
    }

    function makeRow(log) {
        const row = document.createElement('tr');
        row.className = 'audit-row';
        const timestamp = formatDate(log.performed_at);

        const dateCell = document.createElement('td');
        addCellLabel(dateCell, 'Tidspunkt');
        dateCell.appendChild(node('span', 'date-main', timestamp.date));
        if (timestamp.time) dateCell.appendChild(node('span', 'date-time', timestamp.time));

        const actorCell = document.createElement('td');
        addCellLabel(actorCell, 'Utført av');
        actorCell.appendChild(node('span', 'actor-name', `${auditActorName(log)} utførte`));
        actorCell.appendChild(node('span', 'actor-meta', auditActorTypeLabel(log.actor_type)));

        const actionCell = document.createElement('td');
        addCellLabel(actionCell, 'Hendelse');
        actionCell.appendChild(node('span', 'action-name', auditActionLabel(log.action)));
        actionCell.appendChild(node('span', 'resource-meta', `${auditResourceLabel(log.resource_type || 'Ressurs')} · ${text(log.resource_name || log.resource_id, 'Uten navn')}`));

        const moduleCell = document.createElement('td');
        addCellLabel(moduleCell, 'Område');
        moduleCell.appendChild(node('span', 'module-tag', auditModuleLabel(log.module)));

        const outcomeCell = document.createElement('td');
        addCellLabel(outcomeCell, 'Resultat');
        outcomeCell.appendChild(makeOutcome(log.outcome));

        const detailsCell = document.createElement('td');
        addCellLabel(detailsCell, 'Detaljer');
        const button = node('button', 'btn btn-outline-primary details-button', 'Vis detaljer');
        button.type = 'button';
        button.setAttribute('aria-label', `Vis detaljer for ${auditActionLabel(log.action || 'hendelse')} – ${text(log.resource_name || log.resource_id, 'ressurs')}`);
        button.addEventListener('click', () => openDetails(log.id, button));
        detailsCell.appendChild(button);
        row.append(dateCell, actorCell, actionCell, moduleCell, outcomeCell, detailsCell);
        return row;
    }

    function renderPagination(pagination) {
        const bar = node('div', 'pagination-bar');
        const start = pagination.total === 0 ? 0 : (pagination.page - 1) * pagination.limit + 1;
        const end = Math.min(pagination.page * pagination.limit, pagination.total);
        bar.appendChild(node('span', '', `Viser ${start}–${end} av ${pagination.total}`));
        const controls = node('div', 'pagination-controls');
        const previous = node('button', 'btn btn-outline-secondary', 'Forrige');
        previous.type = 'button';
        previous.disabled = pagination.page <= 1;
        previous.addEventListener('click', () => loadLogs(pagination.page - 1));
        const indicator = node('span', 'page-indicator', `Side ${pagination.page} av ${pagination.pages}`);
        const next = node('button', 'btn btn-outline-secondary', 'Neste');
        next.type = 'button';
        next.disabled = pagination.page >= pagination.pages;
        next.addEventListener('click', () => loadLogs(pagination.page + 1));
        controls.append(previous, indicator, next);
        bar.appendChild(controls);
        return bar;
    }

    function renderLogs(payload) {
        const logs = Array.isArray(payload.logs) ? payload.logs : [];
        const pagination = payload.pagination || { page: 1, total: logs.length, pages: 1, limit: 50 };
        currentPage = Number(pagination.page) || 1;
        results.setAttribute('aria-busy', 'false');
        resultCount.textContent = `${pagination.total || 0} totalt`;
        const content = document.createElement('div');

        if (logs.length === 0) {
            const empty = node('div', 'empty-state');
            empty.append(node('div', 'empty-mark', '—'), node('h3', '', 'Ingen hendelser å vise'));
            const hasFilters = Object.values(currentFilters()).some(Boolean);
            empty.appendChild(node('p', '', hasFilters ? 'Ingen treff med disse filtrene. Prøv å utvide søket.' : 'Det er ennå ikke registrert endringer i aktivitetsloggen.'));
            if (hasFilters) {
                const clear = node('button', 'btn btn-outline-secondary mt-3', 'Nullstill filtre');
                clear.type = 'button';
                clear.addEventListener('click', resetFilters);
                empty.appendChild(clear);
            }
            content.appendChild(empty);
        } else {
            const wrap = node('div', 'table-wrap');
            const table = node('table', 'table audit-table');
            table.setAttribute('aria-label', 'Bedriftens aktivitetslogg');
            const head = document.createElement('thead');
            const headerRow = document.createElement('tr');
            ['Tidspunkt', 'Utført av', 'Hendelse', 'Område', 'Resultat', ''].forEach(label => headerRow.appendChild(node('th', '', label)));
            head.appendChild(headerRow);
            const body = document.createElement('tbody');
            logs.forEach(log => body.appendChild(makeRow(log)));
            table.append(head, body);
            wrap.appendChild(table);
            content.appendChild(wrap);
        }
        content.appendChild(renderPagination(pagination));
        results.replaceChildren(content);
        updateDateValidation();
    }

    function updateDateValidation() {
        const from = document.getElementById('filter-from');
        const to = document.getElementById('filter-to');
        const fromCanonical = canonicalDate(from.value);
        const toCanonical = canonicalDate(to.value);
        from.setCustomValidity(from.value && fromCanonical === null ? 'Skriv en gyldig dato som DD.MM.YYYY.' : '');
        to.setCustomValidity(to.value && toCanonical === null ? 'Skriv en gyldig dato som DD.MM.YYYY.' : '');
        if (fromCanonical && toCanonical && toCanonical < fromCanonical) {
            to.setCustomValidity('Sluttdato kan ikke være før startdato.');
        }
    }

    async function loadLogs(page = 1) {
        updateDateValidation();
        if (!form.reportValidity()) return;
        showLoading();
        try {
            const payload = await request({ ...apiFilters(), page: Math.max(1, Number(page) || 1) });
            const filters = payload.filters || {};
            fillSelect('filter-admin', filters.admins || filters.admin_ids, 'Alle administratorer');
            fillSelect('filter-module', filters.modules, 'Alle områder');
            fillSelect('filter-action', filters.actions, 'Alle hendelser');
            renderLogs(payload);
        } catch (error) {
            showError(error instanceof Error ? error.message : 'Uventet feil ved henting av aktivitetsloggen.');
        }
    }

    function resetFilters() {
        form.reset();
        currentPage = 1;
        loadLogs(1);
    }

    function appendDetailItem(grid, label, value) {
        const item = node('div', 'detail-item');
        item.append(node('span', 'detail-label', label), node('span', 'detail-value', text(value)));
        grid.appendChild(item);
    }

    function renderFieldDifferences(parent, before, after, redactedSet) {
        const beforeObject = before && typeof before === 'object' && !Array.isArray(before) ? before : {};
        const afterObject = after && typeof after === 'object' && !Array.isArray(after) ? after : {};
        try {
            const keys = [...new Set([...Object.keys(beforeObject), ...Object.keys(afterObject)])]
                .filter(key => !redactedSet.has(key))
                .sort();
            const changed = keys.filter(key => safeJson(beforeObject[key]) !== safeJson(afterObject[key]));
            if (!changed.length) {
                parent.appendChild(node('p', 'resource-meta', 'Ingen feltforskjeller er registrert.'));
                return;
            }
            const columns = node('div', 'change-columns');
            [['Før', beforeObject], ['Etter', afterObject]].forEach(([label, values]) => {
                const box = node('section', 'change-box');
                box.appendChild(node('h3', '', label));
                const list = node('ul', 'change-list');
                changed.forEach(key => {
                    const item = document.createElement('li');
                    const shownValue = Object.prototype.hasOwnProperty.call(values, key) ? formatSqlDetailValue(key, values[key]) : '—';
                    item.append(node('span', 'change-key', auditFieldLabel(key)), node('span', 'change-value', shownValue));
                    list.appendChild(item);
                });
                box.appendChild(list);
                columns.appendChild(box);
            });
            parent.appendChild(columns);
        } catch (error) {
            const pre = node('pre', 'json-fallback', safeJson({ before, after }));
            pre.setAttribute('aria-label', 'Endringer vist som sikker JSON-tekst');
            parent.appendChild(pre);
        }
    }

    function renderRedactedFields(parent, redactedFields) {
        if (!Array.isArray(redactedFields)) return;
        const names = [...new Set(redactedFields.filter(field => typeof field === 'string' && field.length > 0))];
        if (!names.length) return;
        const panel = node('section', 'redacted-panel');
        panel.appendChild(node('h3', '', 'Verdier som ikke ble logget'));
        const list = node('ul', 'change-list');
        names.forEach(field => {
            const item = document.createElement('li');
            item.append(node('span', 'change-key', field), node('span', 'change-value', 'Verdi ikke logget'));
            list.appendChild(item);
        });
        panel.appendChild(list);
        parent.appendChild(panel);
    }

    function renderLinks(parent, links) {
        if (!links || typeof links !== 'object' || Array.isArray(links)) return;
        const keys = Object.keys(links);
        if (!keys.length) return;
        parent.appendChild(node('h3', 'section-title', 'Koblingsreferanser'));
        const linkList = node('div', 'link-list');
        keys.sort().forEach(key => {
            if (key === 'initiator_audit_id') {
                const sourceId = links[key];
                if (sourceId === null || sourceId === undefined || sourceId === '') return;
                const button = node('button', 'btn btn-outline-primary source-action', 'Se opprinnelig handling');
                button.type = 'button';
                button.addEventListener('click', () => openDetails(sourceId, button));
                linkList.appendChild(button);
                return;
            }
            const chip = node('span', 'link-chip');
            chip.append(node('strong', '', `${auditLinkLabels[key] || key}:`), document.createTextNode(text(links[key])));
            linkList.appendChild(chip);
        });
        if (linkList.childNodes.length) parent.appendChild(linkList);
    }

    function renderDetail(log) {
        dialogTitle.textContent = `${auditActionLabel(log.action || 'Hendelse')} · ${text(log.resource_name || auditResourceLabel(log.resource_type), 'Ressurs')}`;
        dialogContent.replaceChildren();
        const grid = node('div', 'detail-grid');
        const stamp = formatDate(log.performed_at);
        appendDetailItem(grid, 'Tidspunkt', [stamp.date, stamp.time].filter(Boolean).join(' · '));
        appendDetailItem(grid, 'Utført av', auditActorName(log));
        appendDetailItem(grid, 'Rolle', auditActorRoleLabel(log.actor_role) || 'Ikke registrert');
        appendDetailItem(grid, 'Kontotype', auditActorTypeLabel(log.actor_type));
        appendDetailItem(grid, 'Handling', auditActionLabel(log.action));
        appendDetailItem(grid, 'Område', auditModuleLabel(log.module));
        appendDetailItem(grid, 'Ressurs', auditResourceLabel(log.resource_type));
        appendDetailItem(grid, 'Resultat', formatOutcome(log.outcome));
        appendDetailItem(grid, 'Ressurs-ID', log.resource_id);
        appendDetailItem(grid, 'Bedrift', log.company_name);
        appendDetailItem(grid, 'Hendelses-ID', log.id);
        dialogContent.appendChild(grid);

        dialogContent.appendChild(node('h3', 'section-title', 'Endringer'));
        const redacted = Array.isArray(log.redacted_fields) ? log.redacted_fields : [];
        const redactedSet = new Set(redacted.filter(field => typeof field === 'string'));
        renderFieldDifferences(dialogContent, log.before, log.after, redactedSet);
        renderRedactedFields(dialogContent, redacted);
        renderLinks(dialogContent, log.links);
    }

    async function openDetails(id, trigger) {
        if (!dialog.open) lastFocus = trigger;
        dialogTitle.textContent = 'Laster hendelse';
        dialogContent.replaceChildren(node('div', 'loading-state', 'Henter sikre hendelsesdetaljer …'));
        if (!dialog.open) dialog.showModal();
        try {
            const payload = await request({ id: String(id) });
            if (!payload.log || typeof payload.log !== 'object') throw new Error('Hendelsesdetaljene mangler i svaret.');
            renderDetail(payload.log);
            dialogClose.focus();
        } catch (error) {
            dialogContent.replaceChildren(node('div', 'dialog-error', error instanceof Error ? error.message : 'Hendelsesdetaljene kunne ikke hentes.'));
            dialogClose.focus();
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        updateDateValidation();
        if (!form.reportValidity()) return;
        currentPage = 1;
        loadLogs(1);
    });
    resetButton.addEventListener('click', resetFilters);
    refreshButton.addEventListener('click', () => loadLogs(currentPage));
    document.getElementById('filter-from').addEventListener('change', updateDateValidation);
    document.getElementById('filter-to').addEventListener('change', updateDateValidation);
    dialogClose.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
    });

    loadLogs(1);
})();