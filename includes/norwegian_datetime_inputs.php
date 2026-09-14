<script>
(function () {
    'use strict';

    const datePattern = /^(\d{2})\.(\d{2})\.(\d{4})$/;
    const sqlDatePattern = /^(\d{4})-(\d{2})-(\d{2})$/;
    const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;

    function isValidDate(year, month, day) {
        const candidate = new Date(year, month - 1, day);
        return candidate.getFullYear() === year
            && candidate.getMonth() === month - 1
            && candidate.getDate() === day;
    }

    function norskToSqlDate(value) {
        const match = String(value || '').trim().match(datePattern);
        if (!match) return null;
        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3]);
        if (!isValidDate(year, month, day)) return null;
        return `${match[3]}-${match[2]}-${match[1]}`;
    }

    function sqlToNorskDate(value) {
        const match = String(value || '').trim().match(sqlDatePattern);
        if (!match) return '';
        const year = Number(match[1]);
        const month = Number(match[2]);
        const day = Number(match[3]);
        if (!isValidDate(year, month, day)) return '';
        return `${match[3]}.${match[2]}.${match[1]}`;
    }

    function createProxy(original, placeholder, maxLength) {
        const proxy = document.createElement('input');
        proxy.type = 'text';
        proxy.className = original.className;
        proxy.placeholder = placeholder;
        proxy.inputMode = 'numeric';
        proxy.autocomplete = 'off';
        proxy.maxLength = maxLength;
        proxy.disabled = original.disabled;
        proxy.readOnly = original.readOnly;
        proxy.required = original.required;
        proxy.style.cssText = original.style.cssText;
        proxy.dataset.norwegianDateTimeProxy = '1';

        for (const attribute of ['aria-label', 'aria-describedby', 'title']) {
            if (original.hasAttribute(attribute)) {
                proxy.setAttribute(attribute, original.getAttribute(attribute));
            }
        }

        original.required = false;
        original.className = '';
        original.style.cssText = 'position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important;margin:0!important;padding:0!important;border:0!important;';
        original.tabIndex = -1;
        original.setAttribute('aria-hidden', 'true');
        original.dataset.norwegianDateTimeEnhanced = '1';
        original.insertAdjacentElement('beforebegin', proxy);
        return proxy;
    }

    function bindOriginalValue(original, syncProxy) {
        const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        if (!descriptor || !descriptor.get || !descriptor.set) {
            return (value) => {
                original.value = value == null ? '' : value;
            };
        }

        Object.defineProperty(original, 'value', {
            configurable: true,
            get() {
                return descriptor.get.call(this);
            },
            set(value) {
                descriptor.set.call(this, value == null ? '' : value);
                syncProxy();
            }
        });
        return (value) => {
            descriptor.set.call(original, value == null ? '' : value);
        };
    }

    function enhanceDate(original) {
        const proxy = createProxy(original, 'DD.MM.YYYY', 10);

        const syncProxy = () => {
            proxy.value = sqlToNorskDate(original.value);
        };
        const setOriginalSilently = bindOriginalValue(original, syncProxy);
        syncProxy();

        const syncOriginal = (showError) => {
            const value = proxy.value.trim();
            let valid = false;
            if (value === '') {
                original.value = '';
                proxy.setCustomValidity(proxy.required ? 'Dato er påkrevd' : '');
                valid = !proxy.required;
            } else {
                const sqlDate = norskToSqlDate(value);
                proxy.setCustomValidity(sqlDate ? '' : 'Bruk en gyldig dato i formatet DD.MM.YYYY');
                if (sqlDate) {
                    original.value = sqlDate;
                    valid = true;
                } else {
                    setOriginalSilently('');
                }
            }
            if (valid) original.dispatchEvent(new Event('input', {bubbles: true}));
            if (showError) proxy.reportValidity();
        };

        proxy.addEventListener('input', () => syncOriginal(false));
        proxy.addEventListener('blur', () => syncOriginal(true));
        proxy._validateNorwegianDateTime = syncOriginal;
        proxy.addEventListener('change', () => {
            syncOriginal(true);
            original.dispatchEvent(new Event('change', {bubbles: true}));
        });
        proxy.form?.addEventListener('submit', (event) => {
            syncOriginal(false);
            if (!proxy.checkValidity()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                proxy.reportValidity();
            }
        }, true);
        proxy.form?.addEventListener('reset', () => setTimeout(syncProxy, 0));
    }

    function enhanceTime(original) {
        const proxy = createProxy(original, 'HH:mm', 5);

        const syncProxy = () => {
            const value = String(original.value || '');
            proxy.value = timePattern.test(value.substring(0, 5)) ? value.substring(0, 5) : '';
        };
        const setOriginalSilently = bindOriginalValue(original, syncProxy);
        syncProxy();

        const syncOriginal = (showError) => {
            const value = proxy.value.trim();
            const valid = value === '' ? !proxy.required : timePattern.test(value);
            proxy.setCustomValidity(valid ? '' : (value === '' ? 'Klokkeslett er påkrevd' : 'Bruk 24-timers klokke i formatet HH:mm'));
            if (valid) {
                original.value = value;
                original.dispatchEvent(new Event('input', {bubbles: true}));
            } else {
                setOriginalSilently('');
            }
            if (showError) proxy.reportValidity();
        };

        proxy.addEventListener('input', () => syncOriginal(false));
        proxy.addEventListener('blur', () => syncOriginal(true));
        proxy._validateNorwegianDateTime = syncOriginal;
        proxy.addEventListener('change', () => {
            syncOriginal(true);
            original.dispatchEvent(new Event('change', {bubbles: true}));
        });
        proxy.form?.addEventListener('submit', (event) => {
            syncOriginal(false);
            if (!proxy.checkValidity()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                proxy.reportValidity();
            }
        }, true);
        proxy.form?.addEventListener('reset', () => setTimeout(syncProxy, 0));
    }

    function enhanceDateTime(original) {
        const proxy = createProxy(original, 'DD.MM.YYYY HH:mm', 16);
        const dateTimePattern = /^(\d{2}\.\d{2}\.\d{4})\s([0-2]\d:[0-5]\d)$/;

        const syncProxy = () => {
            const value = String(original.value || '').trim();
            const match = value.match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/);
            proxy.value = match ? `${sqlToNorskDate(match[1])} ${match[2]}` : '';
        };
        const setOriginalSilently = bindOriginalValue(original, syncProxy);
        syncProxy();

        const syncOriginal = (showError) => {
            const value = proxy.value.trim();
            let valid = false;
            if (value === '') {
                original.value = '';
                proxy.setCustomValidity(proxy.required ? 'Dato og klokkeslett er påkrevd' : '');
                valid = !proxy.required;
            } else {
                const match = value.match(dateTimePattern);
                const sqlDate = match ? norskToSqlDate(match[1]) : null;
                valid = Boolean(match && sqlDate && timePattern.test(match[2]));
                proxy.setCustomValidity(valid ? '' : 'Bruk formatet DD.MM.YYYY HH:mm med 24-timers klokke');
                if (valid) {
                    original.value = `${sqlDate}T${match[2]}`;
                } else {
                    setOriginalSilently('');
                }
            }
            if (valid) original.dispatchEvent(new Event('input', {bubbles: true}));
            if (showError) proxy.reportValidity();
        };

        proxy.addEventListener('input', () => syncOriginal(false));
        proxy.addEventListener('blur', () => syncOriginal(true));
        proxy._validateNorwegianDateTime = syncOriginal;
        proxy.addEventListener('change', () => {
            syncOriginal(true);
            original.dispatchEvent(new Event('change', {bubbles: true}));
        });
        proxy.form?.addEventListener('submit', (event) => {
            syncOriginal(false);
            if (!proxy.checkValidity()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                proxy.reportValidity();
            }
        }, true);
        proxy.form?.addEventListener('reset', () => setTimeout(syncProxy, 0));
    }

    function validateProxies(scope, showError = true) {
        const proxies = scope.querySelectorAll('[data-norwegian-date-time-proxy="1"]');
        for (const proxy of proxies) {
            if (proxy.disabled) continue;
            proxy._validateNorwegianDateTime?.(false);
            if (!proxy.checkValidity()) {
                if (showError) proxy.reportValidity();
                return false;
            }
        }
        return true;
    }

    function enhance(root) {
        const scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('input[type="date"]:not([data-norwegian-date-time-enhanced])').forEach(enhanceDate);
        scope.querySelectorAll('input[type="time"]:not([data-norwegian-date-time-enhanced])').forEach(enhanceTime);
        scope.querySelectorAll('input[type="datetime-local"]:not([data-norwegian-date-time-enhanced])').forEach(enhanceDateTime);
    }

    function start() {
        enhance(document);
        window.validateNorwegianDateTimeInputs = (scope = document) => validateProxies(scope, true);

        // Several existing modals save through type="button" and inline handlers
        // instead of native form submission. Validate the active modal/form in the
        // capture phase so invalid text cannot reach those save handlers.
        document.addEventListener('click', (event) => {
            const button = event.target.closest('button[type="button"], input[type="button"]');
            if (!button) return;
            if (button.hasAttribute('data-bs-dismiss') || button.hasAttribute('data-bs-toggle')) return;
            const action = button.getAttribute('onclick') || '';
            if (!/(save|submit|create|add|update|register|confirm|process|renew|reschedule|set)/i.test(action)) return;
            const scope = button.closest('.modal') || button.closest('form');
            if (!scope || validateProxies(scope, false)) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            validateProxies(scope, true);
        }, true);

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;
                    if (node.matches?.('input[type="date"], input[type="time"], input[type="datetime-local"]')) {
                        enhance(node.parentElement || document);
                    } else {
                        enhance(node);
                    }
                });
            }
        });
        observer.observe(document.body, {childList: true, subtree: true});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>