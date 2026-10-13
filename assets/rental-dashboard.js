(function () {
    'use strict';
    const root = document.getElementById('rental-module');
    if (!root) return;

    const dashboard = {
        sourceReady: {objects:false, requests:false, contracts:false, schedule:false},
        sourceFailed: {objects:false, requests:false, contracts:false, schedule:false},
        editor: null,
        openId: null,
        trigger: null,
        deferredObjectRender: false,
        scrollY: 0,
        listScroll: 0,
        activeContractsCount: null,
        appliedSearch: '',
        appliedCategory: '',
        modalSection: null,
        modalMarker: null,
        activeOverview: null,
        transitioningToDetails: false
    };
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    function scrollObjectToTop(entry) {
        const list = document.getElementById('rentalObjectsContainer');
        if (!list || !entry) return;
        list.scrollTo({
            top:list.scrollTop + entry.getBoundingClientRect().top - list.getBoundingClientRect().top,
            behavior:reducedMotion.matches ? 'auto' : 'smooth'
        });
    }

    function renderMetrics() {
        const values = {
            objects: document.getElementById('rentalMetricObjects'),
            requests: document.getElementById('rentalMetricRequests'),
            contracts: document.getElementById('rentalMetricContracts'),
            upcoming: document.getElementById('rentalMetricUpcoming')
        };
        const pending = typeof rentalPendingRequests !== 'undefined' && rentalPendingRequests instanceof Map ? rentalPendingRequests.size : null;
        const upcoming = typeof allApprovedRentals !== 'undefined' && Array.isArray(allApprovedRentals)
            ? allApprovedRentals.filter(item => String(item.end_date || '') >= (window.getRentalOsloToday?.() || new Date().toISOString().slice(0,10))).length
            : null;
        const objectCount = typeof allRentalObjects !== 'undefined' && Array.isArray(allRentalObjects) ? allRentalObjects.length : null;
        const assign = (key, value, source) => {
            const node = values[key];
            if (!node) return;
            node.textContent = dashboard.sourceFailed[source] ? '—' : (dashboard.sourceReady[source] && value !== null ? String(value) : '…');
            node.setAttribute('aria-label', dashboard.sourceFailed[source] ? 'Kunne ikke laste tall' : String(value ?? 'Laster'));
        };
        assign('objects', objectCount, 'objects');
        assign('requests', pending, 'requests');
        assign('contracts', dashboard.activeContractsCount, 'contracts');
        assign('upcoming', upcoming, 'schedule');
    }

    function sourceUpdated(event) {
        const {source, success, status = 'all', activeCount} = event.detail || {};
        if (!Object.hasOwn(dashboard.sourceReady, source)) return;
        if (source === 'contracts') {
            // A completed/draft-only result cannot represent all active contracts.
            if (!['all','active'].includes(status)) return;
            if (success) dashboard.activeContractsCount = activeCount;
        }
        dashboard.sourceReady[source] = !!success;
        dashboard.sourceFailed[source] = !success;
        renderMetrics();
    }

    function renderObjects(objects) {
        const host = document.getElementById('rentalObjectsContainer');
        if (!host) return;
        if (!objects.length) {
            const hasInventory = Array.isArray(allRentalObjects) && allRentalObjects.length > 0;
            host.innerHTML = `<div class="rental-object-list"><div class="rental-empty-state"><span class="rental-empty-icon"><i class="bi bi-house"></i></span><strong>${hasInventory ? 'Ingen objekter i dette utvalget' : 'Start med ditt første utleieobjekt'}</strong><span>${hasInventory ? 'Prøv et annet søk eller en annen kategori.' : 'Legg inn lokale, utstyr eller kjøretøy i oversikten.'}</span></div>${window.renderRentalCreateTile?.() || ''}</div>`;
            return;
        }
        host.innerHTML = `<div class="rental-object-list">${objects.map(item => window.renderObjectCard(item)).join('')}${window.renderRentalCreateTile?.() || ''}</div>`;
    }

    function applyObjectFilters() {
        // A browser/password manager may fill the input without an editing event.
        // Only a query accepted from a deliberate edit may filter the inventory.
        const search = document.getElementById('rentalObjectSearch');
        if (search) search.value = dashboard.appliedSearch;
        const query = dashboard.appliedSearch.trim().toLocaleLowerCase('nb-NO');
        const category = document.getElementById('categoryFilter')?.value || '';
        dashboard.appliedCategory = category;
        const objects = allRentalObjects.filter(item => {
            const schema = rentalSchemas?.[item.category];
            const haystack = `${item.object_name || ''} ${item.category || ''} ${schema?.label || ''}`.toLocaleLowerCase('nb-NO');
            return (!category || item.category === category) && (!query || haystack.includes(query));
        });
        renderObjects(objects);
    }

    async function closeFieldPanel(force) {
        if (!dashboard.openId) return true;
        if (!force && dashboard.editor && !dashboard.editor.confirmClose()) return false;
        const id = dashboard.openId;
        const panel = document.getElementById(`rental-inline-${String(id)}`);
        panel?.replaceChildren();
        if (panel) panel.hidden = true;
        root.querySelector(`[data-rental-fields="${CSS.escape(String(id))}"]`)?.setAttribute('aria-expanded','false');
        dashboard.editor = null;
        dashboard.openId = null;
        if (dashboard.deferredObjectRender) {
            dashboard.deferredObjectRender = false;
            applyObjectFilters();
        }
        document.getElementById('rentalObjectsContainer')?.scrollTo({top:dashboard.listScroll,behavior:reducedMotion.matches ? 'auto' : 'smooth'});
        window.scrollTo({top:dashboard.scrollY, behavior:reducedMotion.matches ? 'auto' : 'smooth'});
        root.querySelector(`[data-rental-fields="${CSS.escape(String(id))}"]`)?.focus({preventScroll:true});
        dashboard.trigger = null;
        return true;
    }

    async function openFieldPanel(id, button) {
        if (dashboard.openId && String(dashboard.openId) === String(id)) {
            await closeFieldPanel(false);
            button?.focus();
            return;
        }
        if (dashboard.openId && !(await closeFieldPanel(false))) return;
        const panel = document.getElementById(`rental-inline-${String(id)}`);
        const entry = document.getElementById(`rental-card-${String(id)}`);
        if (!panel || !entry) return;
        dashboard.scrollY = window.scrollY;
        dashboard.listScroll = document.getElementById('rentalObjectsContainer')?.scrollTop || 0;
        dashboard.openId = String(id);
        dashboard.trigger = button || null;
        panel.hidden = false;
        button?.setAttribute('aria-expanded','true');
        scrollObjectToTop(entry);
        const label = allRentalObjects.find(item => String(item.id) === String(id))?.object_name || 'Utleieobjekt';
        const host = document.createElement('div');
        host.id = `rental-fields-editor-inline-${String(id).replace(/[^a-zA-Z0-9_-]/g,'')}`;
        host.className = 'rental-fields-host';
        panel.append(host);
        const editor = window.EntityFormFields.mount(host, {
            entityType:'rental_object',
            entityId:id,
            strictRentalValidation:true,
            rentalLayout:true,
            onSaved:() => closeFieldPanel(true)
        });
        dashboard.editor = editor;
        try {
            await editor.refresh();
        } catch (_) {
            // The editor renders the actionable API/field validation error and keeps its retry control.
        }
        if (!panel.isConnected || dashboard.openId !== String(id)) return;
        const heading = host.querySelector('.entity-fields-head > div');
        if (heading) {
            const title = document.createElement('span');
            title.className = 'rental-inline-object-label';
            title.textContent = label;
            heading.append(title);
        }
        const actions = host.querySelector('.entity-fields-editor > .d-flex');
        if (actions && !actions.querySelector('[data-inline-close]')) {
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-sm btn-outline-secondary rental-inline-close';
            close.dataset.inlineClose = 'true';
            close.textContent = 'Avbryt';
            close.addEventListener('click', () => closeFieldPanel(false));
            actions.prepend(close);
        }
        host.querySelector('[data-common-field], [data-prop="field_label"], [data-preset]')?.focus({preventScroll:true});
        scrollObjectToTop(entry);
    }

    async function beforeFilter() {
        if (!dashboard.openId) return true;
        const allowed = await closeFieldPanel(false);
        if (!allowed) {
            const search = document.getElementById('rentalObjectSearch');
            const category = document.getElementById('categoryFilter');
            if (search) search.value = dashboard.appliedSearch;
            if (category) category.value = dashboard.appliedCategory;
        }
        return allowed;
    }

    function openOverview(kind, refresh = true) {
        const modal = document.getElementById('rentalOverviewModal');
        const body = document.getElementById('rentalOverviewModalBody');
        const section = document.getElementById(kind === 'requests' ? 'rental-requests' : 'rental-upcoming');
        if (!modal || !body || !section) return;
        if (dashboard.modalSection && dashboard.modalSection !== section) restoreOverviewContent();
        if (!dashboard.modalSection) {
            const marker = document.createComment('rental overview home');
            section.parentNode.insertBefore(marker, section);
            dashboard.modalMarker = marker;
            dashboard.modalSection = section;
        }
        body.append(section);
        section.style.display = 'block';
        const title = document.getElementById('rentalOverviewModalTitle');
        if (title) title.textContent = kind === 'requests' ? 'Forespørsler' : 'Kommende utleie';
        dashboard.activeOverview = kind;
        bootstrap.Modal.getOrCreateInstance(modal).show();
        if (refresh) {
            if (kind === 'requests') window.loadRentalRequests?.();
            else window.loadApprovedSchedule?.();
        }
    }

    function restoreOverviewContent() {
        const section = dashboard.modalSection;
        const marker = dashboard.modalMarker;
        if (!section || !marker) return;
        marker.parentNode?.insertBefore(section, marker);
        marker.remove();
        section.style.display = 'none';
        dashboard.modalSection = null;
        dashboard.modalMarker = null;
    }

    document.addEventListener('rental-dashboard-source', sourceUpdated);
    document.addEventListener('rental-dashboard-update', renderMetrics);
    document.addEventListener('DOMContentLoaded', () => {
        const search = document.getElementById('rentalObjectSearch');
        search?.addEventListener('input', async event => {
            const inputType = event.inputType || '';
            const explicitEdit = /^(delete|history)/.test(inputType)
                || ['insertFromPaste', 'insertFromDrop'].includes(inputType)
                || (['insertText', 'insertCompositionText'].includes(inputType) && event.data !== null && event.data !== undefined);
            if (!explicitEdit && search.value !== '') {
                search.value = dashboard.appliedSearch;
                return;
            }
            const query = search.value;
            if (await beforeFilter()) {
                dashboard.appliedSearch = query;
                applyObjectFilters();
            }
        });
        search?.addEventListener('change', () => { search.value = dashboard.appliedSearch; });
        search?.addEventListener('focus', () => { search.value = dashboard.appliedSearch; });
        document.getElementById('rentalObjectsContainer')?.addEventListener('click', event => {
            const button = event.target.closest('[data-rental-fields]');
            if (button) {
                event.preventDefault();
                event.stopPropagation();
                openFieldPanel(button.dataset.rentalFields, button);
            }
        });
        const objectList = document.getElementById('rentalObjectsContainer');
        objectList?.addEventListener('shown.bs.dropdown', event => {
            const dropdown = event.target.closest?.('.rental-card-menu');
            const menu = dropdown?.querySelector('.dropdown-menu');
            if (menu && menu.parentElement !== document.body) {
                menu.classList.add('rental-card-menu-floating');
                document.body.append(menu);
            }
        });
        objectList?.addEventListener('hide.bs.dropdown', event => {
            const dropdown = event.target.closest?.('.rental-card-menu');
            const objectId = dropdown?.dataset.rentalObjectId;
            const menu = objectId && document.querySelector(`.dropdown-menu[data-rental-menu-owner="${CSS.escape(objectId)}"]`);
            if (dropdown && menu && menu.parentElement === document.body) {
                menu.classList.remove('rental-card-menu-floating');
                dropdown.append(menu);
            }
        });
        document.querySelectorAll('#subtabs-rental a[href="#rental-requests"], #subtabs-rental a[href="#rental-upcoming"]').forEach(link => {
            link.addEventListener('click', event => {
                const createView = document.getElementById('rental-create');
                if (createView?.style.display === 'block' && !window.closeRentalCreate?.()) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
                event.preventDefault();
                event.stopImmediatePropagation();
                const kind = link.hash === '#rental-requests' ? 'requests' : 'upcoming';
                document.querySelectorAll('#subtabs-rental .nav-link').forEach(item => item.classList.remove('active'));
                document.querySelector('#subtabs-rental a[href="#rental-objects"]')?.classList.add('active');
                window.localStorage.setItem('salesman_active_subtab_rental', 'rental-objects');
                document.querySelectorAll('#content-rental .subtab-content').forEach(item => item.style.display = item.id === 'rental-objects' ? 'block' : 'none');
                openOverview(kind);
            }, true);
        });
        document.querySelectorAll('#subtabs-rental a').forEach(link => {
            link.addEventListener('click', event => {
                const createView = document.getElementById('rental-create');
                if (!createView || createView.style.display !== 'block' || link.hash === '#rental-create') return;
                if (!window.closeRentalCreate?.()) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }, true);
        });
        root.querySelectorAll('[data-rental-open]').forEach(button => button.addEventListener('click', () => openOverview(button.dataset.rentalOpen)));
        const overview = document.getElementById('rentalOverviewModal');
        overview?.addEventListener('hidden.bs.modal', () => {
            restoreOverviewContent();
            if (!dashboard.transitioningToDetails) dashboard.activeOverview = null;
        });
        const details = document.getElementById('detailsModal');
        details?.addEventListener('show.bs.modal', event => {
            if (!dashboard.activeOverview || !overview?.classList.contains('show')) return;
            event.preventDefault();
            dashboard.transitioningToDetails = true;
            overview.addEventListener('hidden.bs.modal', function reopenDetails() {
                overview.removeEventListener('hidden.bs.modal', reopenDetails);
                bootstrap.Modal.getOrCreateInstance(details).show();
            }, {once:true});
            bootstrap.Modal.getOrCreateInstance(overview).hide();
        });
        details?.addEventListener('hidden.bs.modal', () => {
            if (!dashboard.transitioningToDetails) return;
            dashboard.transitioningToDetails = false;
            if (dashboard.activeOverview) openOverview(dashboard.activeOverview, false);
        });
        renderMetrics();
    });

    window.RentalDashboard = {
        renderObjects,
        openOverview,
        openFieldPanel:(id, trigger) => openFieldPanel(
            id,
            trigger || root.querySelector(`[data-rental-fields="${CSS.escape(String(id))}"]`)
        ),
        closeFieldPanel,
        beforeFilter,
        refreshObjects:applyObjectFilters,
        isFieldPanelOpen:() => !!dashboard.openId,
        deferObjectRender:() => { dashboard.deferredObjectRender = true; }
    };
})();
