(function () {
    'use strict';

    const endpoint = '/api/image_upload.php';
    const acceptedFormats = ['image/jpeg', 'image/png', 'image/webp'];
    const maxFileBytes = 10 * 1024 * 1024;
    const controls = new WeakMap();
    const records = new Set();
    const configCache = new Map();
    const inputSelector = [
        'input[name="img_product"]',
        'input.image-url-input',
        'input[name="event_image_url"]',
        'input[name="plan_image_url"]',
        '#announcement-image-url',
        '#service-img-url',
        'input[name="logo_url"]',
        'input[name="general_logo_url"]',
        'input[name="general_favicon_url"]',
        'input[name="general_hero_image_url"]'
    ].join(',');
    const moduleFor = input => {
        if (input.matches('[name="img_product"]')) return 'sales';
        if (input.matches('.image-url-input')) return 'rental';
        if (input.matches('[name="event_image_url"]')) return 'events';
        if (input.matches('[name="plan_image_url"]')) return 'membership';
        if (input.matches('#announcement-image-url')) return 'announcements';
        if (input.matches('#service-img-url')) return 'reservation';
        if (input.matches('[name="logo_url"]') && input.closest('#company-settings, [data-company-settings]')) return 'company';
        if (input.matches('[name="general_logo_url"],[name="general_favicon_url"],[name="general_hero_image_url"]')) return 'website';
        if (input.matches('[name="logo_url"]')) return 'company';
        return '';
    };
    const purposeFor = input => {
        const name = `${input.name || ''} ${input.id || ''}`.toLowerCase();
        if (name.includes('favicon')) return 'favicon';
        if (name.includes('logo')) return 'logo';
        return 'image';
    };
    const announceChange = input => {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const maxLengthFor = input => {
        if (input.maxLength > 0) return input.maxLength;
        if (input.id === 'announcement-image-url') return 255;
        if (input.name === 'img_product') return 500;
        return Infinity;
    };
    function attach(input) {
        if (!(input instanceof HTMLInputElement) || controls.has(input)) return;
        const module = moduleFor(input);
        if (!module) return;
        const originalIdAttribute = input.getAttribute('id');
        const generatedUrlId = input.id ? '' : `shared-image-url-${Math.random().toString(36).slice(2, 10)}`;
        if (generatedUrlId) {
            let uniqueId = generatedUrlId;
            while (document.getElementById(uniqueId)) uniqueId = `shared-image-url-${Math.random().toString(36).slice(2, 10)}`;
            input.id = uniqueId;
        }

        const container = document.createElement('div');
        container.className = 'shared-image-upload';
        container.setAttribute('data-shared-image-upload', '');
        const pickButton = document.createElement('button');
        pickButton.type = 'button';
        pickButton.className = 'shared-image-upload__pick';
        const pickerId = `shared-image-file-${Math.random().toString(36).slice(2, 10)}`;
        pickButton.textContent = 'Last opp bildefil';
        pickButton.setAttribute('aria-label', 'Velg bilde fra enheten');

        const modeButton = document.createElement('button');
        modeButton.type = 'button';
        modeButton.className = 'shared-image-upload__mode';
        modeButton.setAttribute('aria-controls', input.id);
        modeButton.setAttribute('aria-expanded', 'false');
        const modeText = document.createElement('em');
        modeButton.append(modeText);

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.id = pickerId;
        fileInput.className = 'shared-image-upload__file';
        fileInput.accept = acceptedFormats.join(',');
        fileInput.setAttribute('aria-label', 'Velg bilde for opplasting');

        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'shared-image-upload__clear';
        clearButton.textContent = 'Fjern bilde';
        clearButton.hidden = !input.value.trim();

        const hint = document.createElement('span');
        hint.className = 'shared-image-upload__hint';
        hint.textContent = 'JPEG, PNG eller WebP · maks 10 MB. Bildet sendes til offentlig ImgBB-lagring.';

        const preview = document.createElement('img');
        preview.className = 'shared-image-upload__preview';
        preview.alt = '';
        preview.setAttribute('aria-hidden', 'true');

        const status = document.createElement('span');
        status.className = 'shared-image-upload__status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');

        const progress = document.createElement('span');
        progress.className = 'shared-image-upload__progress';
        progress.hidden = true;
        const progressBar = document.createElement('span');
        progress.append(progressBar);
        container.append(pickButton, modeButton, fileInput, clearButton, hint, preview, status, progress);
        const rentalRow = input.matches('.image-url-input') ? input.closest('.image-row') : null;
        const enclosingLabel = input.closest('label');
        if (rentalRow) rentalRow.insertAdjacentElement('afterend', container);
        else if (enclosingLabel && enclosingLabel.contains(input)) enclosingLabel.insertAdjacentElement('afterend', container);
        else input.insertAdjacentElement('afterend', container);

        const hiddenLabel = !input.matches('.image-url-input') && enclosingLabel &&
            enclosingLabel.contains(input) &&
            enclosingLabel.querySelectorAll('input, select, textarea, button').length === 1
            ? enclosingLabel : null;
        const record = {
            input, module, container, fileInput, pickButton, clearButton, status, preview, progress, progressBar,
            modeButton, modeText, hint, hiddenLabel, urlMode: false,
            generatedUrlId, originalIdAttribute,
            hadUrlClass: input.classList.contains('shared-image-url'),
            hadHiddenUrlClass: input.classList.contains('shared-image-url-hidden'),
            hadLabelClass: Boolean(hiddenLabel?.classList.contains('shared-image-url-label')),
            hadHiddenLabelClass: Boolean(hiddenLabel?.classList.contains('shared-image-url-hidden')),
            state: 'idle', generation: 0, valueAtStart: '', request: null, lastValue: input.value,
            owner: input.closest('form, .modal, .tab-pane') || input.parentElement
        };
        controls.set(input, record);
        records.add(record);
        input.dataset.sharedImageUpload = 'ready';
        input.classList.add('shared-image-url');
        if (hiddenLabel) hiddenLabel.classList.add('shared-image-url-label');
        renderMode(record);
        input.addEventListener('input', () => {
            syncValue(record);
            if (record.state === 'error' || record.state === 'success') setState(record, 'idle', '');
            refreshPreview(record);
            clearButton.textContent = 'Fjern bilde';
            clearButton.hidden = !input.value.trim();
        });
        input.addEventListener('change', () => {
            syncValue(record);
            refreshPreview(record);
        });
        input.addEventListener('invalid', () => {
            if (!record.urlMode) {
                record.urlMode = true;
                renderMode(record);
            }
        });
        pickButton.addEventListener('click', () => {
            if (!pickButton.disabled) fileInput.click();
        });
        modeButton.addEventListener('click', () => {
            invalidate(record, '');
            if (record.state === 'error') setState(record, 'idle', '');
            record.urlMode = !record.urlMode;
            renderMode(record);
            refreshPreview(record);
            if (record.urlMode) input.focus();
            else modeButton.focus();
        });
        clearButton.addEventListener('click', () => {
            invalidate(record, '');
            fileInput.value = '';
            input.value = '';
            record.lastValue = '';
            announceChange(input);
            setState(record, 'idle', '');
            refreshPreview(record);
            clearButton.hidden = true;
            if (record.urlMode) input.focus();
            else record.pickButton.focus();
        });
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (file) upload(record, file);
        });
        const form = input.closest('form');
        form?.addEventListener('reset', () => {
            invalidate(record, '');
            window.setTimeout(() => {
                record.urlMode = false;
                renderMode(record);
                refreshRecord(record);
            }, 0);
        }, true);
        refreshPreview(record);
    }

    function controlAnchor(record) {
        const rentalRow = record.input.matches('.image-url-input') ? record.input.closest('.image-row') : null;
        const enclosingLabel = record.input.closest('label');
        return rentalRow || (enclosingLabel && enclosingLabel.contains(record.input) ? enclosingLabel : record.input);
    }

    function positionControl(record) {
        const input = record.input;
        if (!input.isConnected) return;
        const anchor = controlAnchor(record);
        const owner = input.closest('form, .modal, .tab-pane') || input.parentElement;
        if (owner !== record.owner) {
            invalidate(record, '');
            record.owner = owner;
        }
        if (record.container.previousElementSibling !== anchor || record.container.parentElement !== anchor.parentElement) {
            anchor.insertAdjacentElement('afterend', record.container);
        }
    }

    function dispose(record) {
        invalidate(record, '');
        record.container.remove();
        if (!record.hadUrlClass) record.input.classList.remove('shared-image-url');
        if (!record.hadHiddenUrlClass) record.input.classList.remove('shared-image-url-hidden');
        if (record.hiddenLabel && !record.hadLabelClass) record.hiddenLabel.classList.remove('shared-image-url-label');
        if (record.hiddenLabel && !record.hadHiddenLabelClass) record.hiddenLabel.classList.remove('shared-image-url-hidden');
        if (record.generatedUrlId) {
            if (record.originalIdAttribute === null) record.input.removeAttribute('id');
            else record.input.setAttribute('id', record.originalIdAttribute);
        }
        records.delete(record);
        controls.delete(record.input);
        delete record.input.dataset.sharedImageUpload;
    }

    function renderMode(record) {
        const urlMode = record.urlMode;
        record.container.classList.toggle('is-url-mode', urlMode);
        record.input.classList.toggle('shared-image-url-hidden', !urlMode);
        record.hiddenLabel?.classList.toggle('shared-image-url-hidden', !urlMode);
        record.pickButton.hidden = urlMode;
        record.hint.hidden = urlMode;
        record.fileInput.setAttribute('aria-hidden', String(urlMode));
        if (urlMode) record.fileInput.setAttribute('tabindex', '-1');
        else record.fileInput.removeAttribute('tabindex');
        record.modeText.textContent = urlMode ? 'eller last opp bildefil' : 'eller bruk bildelenke';
        record.modeButton.setAttribute('aria-expanded', String(urlMode));
        record.modeButton.setAttribute('aria-label', urlMode ? 'Bytt til opplasting av bildefil' : 'Bytt til å skrive inn bildelenke');
    }

    function cleanAfterDomMove(candidates) {
        if (!candidates.size) return;
        window.setTimeout(() => {
            candidates.forEach(record => {
                if (!record.input.isConnected) dispose(record);
                else positionControl(record);
            });
        }, 0);
    }

    function invalidate(record, message) {
        record.generation++;
        const request = record.request;
        record.request = null;
        if (request && request.readyState !== XMLHttpRequest.DONE) request.abort();
        setState(record, 'idle', message);
    }

    function syncValue(record) {
        const value = record.input.value;
        if (value === record.lastValue) return;
        if (record.state === 'pending') invalidate(record, '');
        else if (record.state === 'error' || record.state === 'success') setState(record, 'idle', '');
        record.lastValue = value;
    }

    function refreshRecord(record) {
        if (!record.input.isConnected) return;
        syncValue(record);
        refreshPreview(record);
    }

    function refreshIn(scope) {
        const inputs = scope instanceof HTMLInputElement && scope.matches(inputSelector)
            ? [scope]
            : [...(scope.querySelectorAll?.(inputSelector) || [])];
        inputs.forEach(input => {
            const record = controls.get(input);
            if (record) refreshRecord(record);
        });
    }

    function invalidateIn(scope) {
        scope.querySelectorAll?.(inputSelector).forEach(input => {
            const record = controls.get(input);
            if (record) invalidate(record, '');
        });
    }

    function setState(record, state, message) {
        record.state = state;
        record.container.classList.toggle('is-uploading', state === 'pending');
        record.container.classList.toggle('is-error', state === 'error');
        record.container.classList.toggle('is-success', state === 'success');
        record.status.textContent = message || '';
        const busy = state === 'pending';
        record.fileInput.disabled = busy;
        record.pickButton.disabled = busy;
        record.clearButton.disabled = busy;
        record.clearButton.textContent = state === 'error' && !record.input.value.trim() ? 'Fjern feil' : 'Fjern bilde';
        record.clearButton.hidden = busy || (!record.input.value.trim() && state !== 'error');
        record.progress.hidden = !busy;
        if (!busy) record.progressBar.style.width = '0%';
    }

    function refreshPreview(record) {
        const value = record.input.value.trim();
        if (!value) {
            record.preview.removeAttribute('src');
            record.preview.classList.remove('is-visible');
            record.clearButton.hidden = true;
            return;
        }
        record.clearButton.hidden = record.state === 'pending';
        try {
            const parsed = new URL(value, location.origin);
            if (!['http:', 'https:'].includes(parsed.protocol)) throw new Error('invalid');
            record.preview.onerror = () => record.preview.classList.remove('is-visible');
            record.preview.onload = () => record.preview.classList.add('is-visible');
            if (record.preview.src !== parsed.href) record.preview.src = parsed.href;
            if (record.preview.complete && record.preview.naturalWidth > 0) record.preview.classList.add('is-visible');
        } catch (_) {
            record.preview.removeAttribute('src');
            record.preview.classList.remove('is-visible');
        }
    }

    async function getConfig(module) {
        const cacheKey = module;
        if (!configCache.has(cacheKey)) {
            configCache.set(cacheKey, fetch(`${endpoint}?module=${encodeURIComponent(module)}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }).then(async response => {
                if (response.status === 401 || response.status === 403) {
                    configCache.delete(cacheKey);
                    let message = 'Tilgangen er endret. Oppdater siden eller logg inn på nytt før du prøver igjen.';
                    try {
                        const denied = await response.json();
                        message = denied.message || message;
                    } catch (_) {}
                    throw new Error(message);
                }
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Opplasting er ikke tilgjengelig.');
                if (!result.configured) throw new Error('ImgBB er ikke konfigurert for denne portalen. Be administrator legge inn serverens ImgBB-nøkkel.');
                return result;
            }).catch(error => {
                configCache.delete(cacheKey);
                throw error;
            }));
        }
        return configCache.get(cacheKey);
    }

    function stillCurrent(record, generation, originalOwner, originalValue, companyId) {
        if (generation !== record.generation || !record.input.isConnected) return false;
        if (record.owner !== originalOwner || !originalOwner?.isConnected) return false;
        if ((record.input.closest('form, .modal, .tab-pane') || record.input.parentElement) !== originalOwner) return false;
        if (record.input.value !== originalValue) return false;
        if (record.input.closest('.modal') && !record.input.closest('.modal').classList.contains('show')) return false;
        const activeCompanyId = document.body?.dataset.companyId;
        return String(companyId) === String(record.companyId) &&
            (!activeCompanyId || String(activeCompanyId) === String(companyId));
    }

    async function upload(record, file) {
        const generation = ++record.generation;
        const originalOwner = record.owner;
        const originalValue = record.input.value;
        record.valueAtStart = originalValue;
        setState(record, 'pending', 'Klargjør opplasting …');
        record.clearButton.hidden = true;

        try {
            if (!acceptedFormats.includes(file.type)) throw new Error('Velg en JPEG-, PNG- eller WebP-bildefil.');
            if (file.size > maxFileBytes) throw new Error('Bildet er større enn 10 MB. Velg en mindre fil.');
            if (file.size <= 0) throw new Error('Den valgte bildefilen er tom.');

            const config = await getConfig(record.module);
            if (generation !== record.generation || !record.input.isConnected) return;
            record.companyId = config.company_id;
            if (!stillCurrent(record, generation, originalOwner, originalValue, config.company_id)) {
                setState(record, 'idle', 'Opplastingen ble avbrutt fordi feltet eller firmaet ble endret.');
                return;
            }
            if (!config.formats?.includes(file.type)) throw new Error('Dette bildefilformatet støttes ikke av serveren.');
            setState(record, 'pending', 'Laster opp bildet …');
            await postImage(record, file, config, generation, originalOwner, originalValue);
        } catch (error) {
            if (generation !== record.generation || !record.input.isConnected) return;
            setState(record, 'error', error.message || 'Bildet kunne ikke lastes opp. Prøv igjen eller bruk en bildeadresse.');
        } finally {
            if (generation === record.generation) record.fileInput.value = '';
        }
    }

    function postImage(record, file, config, generation, originalOwner, originalValue) {
        return new Promise((resolve, reject) => {
            const body = new FormData();
            body.append('image', file, file.name);
            body.append('module', record.module);
            body.append('csrf_token', config.csrf_token);
            body.append('company_id', config.company_id);
            body.append('purpose', purposeFor(record.input));

            const request = new XMLHttpRequest();
            record.request = request;
            request.open('POST', endpoint, true);
            request.withCredentials = true;
            request.timeout = 120000;
            request.setRequestHeader('Accept', 'application/json');
            request.upload.onprogress = event => {
                if (!event.lengthComputable || generation !== record.generation) return;
                const percent = Math.min(96, Math.round(event.loaded / event.total * 100));
                record.progressBar.style.width = `${percent}%`;
                record.status.textContent = `Laster opp bildet … ${percent}%`;
            };
            const rejectRequest = error => {
                if (record.request === request) record.request = null;
                reject(error);
            };
            request.onerror = () => rejectRequest(new Error('Nettverksfeil under opplasting. Kontroller tilkoblingen og prøv igjen.'));
            request.ontimeout = () => rejectRequest(new Error('Opplastingen tok for lang tid og ble avbrutt. Kontroller tilkoblingen og prøv igjen.'));
            request.onabort = () => rejectRequest(new Error('Opplastingen ble avbrutt.'));
            request.onload = () => {
                if (record.request === request) record.request = null;
                if (generation !== record.generation) {
                    resolve();
                    return;
                }
                let result;
                try { result = JSON.parse(request.responseText); }
                catch (_) { reject(new Error('Serveren svarte med et ugyldig svar. Prøv igjen senere.')); return; }
                if (request.status < 200 || request.status >= 300 || !result.success) {
                    if (request.status === 401 || request.status === 403) configCache.delete(record.module);
                    reject(new Error(result.message || 'Opplastingen ble avvist av serveren.'));
                    return;
                }
                if (typeof result.url !== 'string' || !/^https:\/\/i\.ibb\.co\//i.test(result.url)) {
                    reject(new Error('Serveren returnerte ikke en gyldig ImgBB-bildeadresse.'));
                    return;
                }
                if (result.url.length > maxLengthFor(record.input)) {
                    reject(new Error(`Bildeadressen er for lang for dette feltet (${result.url.length} tegn; maks ${maxLengthFor(record.input)}). Den er ikke satt inn.`));
                    return;
                }
                if (!stillCurrent(record, generation, originalOwner, originalValue, config.company_id)) {
                    setState(record, 'idle', 'Bildet ble lastet opp, men feltet ble lukket eller endret. Åpne feltet på nytt før du velger bilde.');
                    resolve();
                    return;
                }
                record.input.value = result.url;
                record.lastValue = result.url;
                announceChange(record.input);
                record.progressBar.style.width = '100%';
                setState(record, 'success', 'Bildet er lastet opp, og bildeadressen er lagt inn.');
                refreshPreview(record);
                record.clearButton.hidden = false;
                resolve();
            };
            request.send(body);
        });
    }

    function getPendingControls(scope) {
        const inputs = [...document.querySelectorAll(inputSelector)];
        return inputs.map(input => controls.get(input)).filter(record => {
            if (!record || !['pending', 'error'].includes(record.state)) return false;
            if (!scope || scope === document) return true;
            return scope.contains(record.input);
        });
    }

    function saveAction(element) {
        if (!(element instanceof HTMLElement)) return false;
        if (element.matches('[data-shared-image-upload]')) return false;
        const idAndName = `${element.id || ''} ${element.getAttribute('name') || ''}`.toLowerCase();
        const text = (element.textContent || '').trim().toLowerCase();
        const handler = element.getAttribute('onclick') || '';
        // Rental's create button builds a summary snapshot. Treat it as save-relevant
        // before the preview-label exclusions so it cannot capture a stale image URL.
        if (/\bshowCreateSummary\s*\(/i.test(handler)) return true;
        if (/\b(?:cancel|avbryt|close|lukk|back|tilbake|preview|forhåndsvis|delete|slett|remove|fjern)\b/i.test(`${idAndName} ${text} ${handler}`)) return false;
        const saveHandler = /\b(?:save|lagre|update|oppdater|publish|publiser|submit|bekreft)[A-Za-z0-9_$]*\s*\(/i.test(handler) ||
            /\bcreateWebsiteSettings\s*\(/i.test(handler);
        const saveIdentity = /\b(?:save|lagre|update|oppdater|publish|publiser|submit|bekreft)\b/.test(idAndName);
        const saveLabel = /\b(?:lagre|publiser|bekreft|submit)\b/.test(text);
        return saveHandler || saveIdentity || saveLabel;
    }

    function scopeFor(element) {
        return element.closest('form') || element.closest('.modal') || element.closest('.subtab-content') ||
            element.closest('.tab-pane, [role="tabpanel"], .module-content, #sales-admin') || null;
    }

    function blockSave(event) {
        const submit = event.type === 'submit';
        const button = submit ? event.submitter : event.target.closest?.('button, input[type="submit"], input[type="image"]');
        if (!submit && !saveAction(button)) return;
        const scope = submit ? event.target : scopeFor(button);
        if (!scope) return;
        const blocked = getPendingControls(scope);
        if (!blocked.length) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const first = blocked[0];
        first.container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        first.pickButton.focus({ preventScroll: true });
        first.status.textContent = blocked.some(record => record.state === 'pending')
            ? 'Vent til bildeopplastingen er ferdig før du lagrer.'
            : 'En bildeopplasting feilet. Prøv på nytt, fjern feilen eller skriv inn en bildeadresse manuelt før du lagrer.';
    }

    function scan(root = document) {
        if (root instanceof Element && root.matches(inputSelector)) attach(root);
        root.querySelectorAll?.(inputSelector).forEach(attach);
    }

    function init() {
        scan();
        document.addEventListener('click', blockSave, true);
        document.addEventListener('submit', blockSave, true);
        document.addEventListener('hide.bs.modal', event => {
            invalidateIn(event.target);
        }, true);
        document.addEventListener('shown.bs.modal', event => refreshIn(event.target), true);
        document.addEventListener('shown.bs.tab', () => refreshIn(document), true);
        const observer = new MutationObserver(records => {
            const detachedControls = new Set();
            for (const mutation of records) {
                for (const node of mutation.addedNodes) {
                    if (node instanceof Element) scan(node);
                }
                for (const node of mutation.removedNodes) {
                    if (!(node instanceof Element)) continue;
                    if (node.matches(inputSelector)) {
                        const record = controls.get(node);
                        if (record) detachedControls.add(record);
                    }
                    node.querySelectorAll(inputSelector).forEach(input => {
                        const record = controls.get(input);
                        if (record) detachedControls.add(record);
                    });
                }
                if (mutation.type === 'attributes' && mutation.target instanceof Element &&
                    mutation.target.matches('#sales-dialog') && mutation.attributeName === 'hidden') {
                    if (mutation.target.hidden) invalidateIn(mutation.target);
                    else refreshIn(mutation.target);
                }
            }
            cleanAfterDomMove(detachedControls);
        });
        observer.observe(document.documentElement, {
            childList: true, subtree: true, attributes: true, attributeFilter: ['hidden']
        });

        window.SharedImageUpload = Object.freeze({
            scan,
            refresh: refreshIn,
            getPendingControls,
            canSave(scope = document) { return getPendingControls(scope).length === 0; },
            selectors: inputSelector
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
}());
