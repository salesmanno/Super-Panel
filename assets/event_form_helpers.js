(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.EventFormHelpers = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function dateParts(value) {
        const match = String(value || '').match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
        if (!match) return null;

        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3]);
        const leapYear = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
        const daysInMonth = month === 2
            ? (leapYear ? 29 : 28)
            : ([4, 6, 9, 11].includes(month) ? 30 : 31);

        return month >= 1 && month <= 12 && day >= 1 && day <= daysInMonth
            ? {day, month, year}
            : null;
    }

    function toSqlDate(value) {
        const parts = dateParts(value);
        return parts
            ? `${parts.year}-${String(parts.month).padStart(2, '0')}-${String(parts.day).padStart(2, '0')}`
            : '';
    }

    function toNorskDate(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d)?$/);
        if (!match) return '';
        const norsk = `${match[3]}.${match[2]}.${match[1]}`;
        return dateParts(norsk) ? norsk : '';
    }

    function formatDateDigits(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        if (digits.length <= 2) return digits;
        if (digits.length <= 4) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
        return `${digits.slice(0, 2)}.${digits.slice(2, 4)}.${digits.slice(4)}`;
    }

    function dispatchInputEvents(input) {
        const EventConstructor = input.ownerDocument?.defaultView?.Event ||
            (typeof Event !== 'undefined' ? Event : null);
        if (!EventConstructor) return;
        input.dispatchEvent(new EventConstructor('input', {bubbles: true}));
        input.dispatchEvent(new EventConstructor('change', {bubbles: true}));
    }

    function enhanceDateInput(input) {
        if (!input || input.dataset.eventDateEnhanced === 'true') return;
        input.dataset.eventDateEnhanced = 'true';
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]{2}\\.[0-9]{2}\\.[0-9]{4}');
        input.setAttribute('placeholder', 'DD.MM.YYYY');
        input.setAttribute('maxlength', '10');
        input.setAttribute('autocomplete', 'off');
        input.lang = 'nb-NO';

        input.addEventListener('input', function () {
            const formatted = formatDateDigits(input.value);
            if (formatted === input.value) return;
            input.value = formatted;
            if (typeof input.setSelectionRange === 'function') {
                input.setSelectionRange(formatted.length, formatted.length);
            }
        });

        const document = input.ownerDocument;
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group event-date-picker';
        wrapper.style.position = 'relative';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary';
        button.setAttribute('aria-label', `Velg ${input.getAttribute('aria-label') || input.name.replace(/_/g, ' ')}`);
        button.innerHTML = '<i class="bi bi-calendar3" aria-hidden="true"></i><span class="visually-hidden">Velg dato</span>';

        const picker = document.createElement('input');
        picker.type = 'date';
        // The dashboard's generic date enhancer must not replace this native
        // picker with another text proxy and remove its touch target.
        picker.dataset.norwegianDateTimeEnhanced = '1';
        picker.lang = 'nb-NO';
        picker.className = 'event-native-date-picker';
        picker.tabIndex = -1;
        picker.setAttribute('aria-hidden', 'true');
        picker.style.position = 'absolute';
        picker.style.width = '2.5rem';
        picker.style.height = '100%';
        picker.style.opacity = '0';
        picker.style.pointerEvents = 'auto';
        picker.style.right = '0';
        picker.style.bottom = '0';
        picker.style.zIndex = '2';

        button.addEventListener('click', function () {
            picker.value = toSqlDate(input.value);
            if (input.disabled) return;
            try {
                if (typeof picker.showPicker === 'function') picker.showPicker();
                else picker.click();
            } catch (error) {
                picker.focus();
                picker.click();
            }
        });

        picker.addEventListener('change', function () {
            const norskDate = toNorskDate(picker.value);
            if (!norskDate) return;
            input.value = norskDate;
            dispatchInputEvents(input);
        });
        picker.value = toSqlDate(input.value);
        input.addEventListener('input', () => { picker.value = toSqlDate(input.value); });

        wrapper.appendChild(button);
        wrapper.appendChild(picker);
    }

    function timeParts(value) {
        const match = String(value || '').match(/^(\d{2}):(\d{2})$/);
        if (!match) return null;
        const hours = Number(match[1]);
        const minutes = Number(match[2]);
        return hours <= 23 && minutes <= 59 ? {hours, minutes} : null;
    }

    function addSelectOptions(select, placeholder, max) {
        const blank = select.ownerDocument.createElement('option');
        blank.value = '';
        blank.textContent = placeholder;
        select.appendChild(blank);
        for (let value = 0; value <= max; value++) {
            const option = select.ownerDocument.createElement('option');
            option.value = String(value).padStart(2, '0');
            option.textContent = option.value;
            select.appendChild(option);
        }
    }

    function enhanceTimeInput(input) {
        if (!input || input.dataset.eventTimeEnhanced === 'true') return;
        input.dataset.eventTimeEnhanced = 'true';

        const document = input.ownerDocument;
        const wasRequired = input.required;
        const initialValue = input.value;
        const label = input.getAttribute('aria-label') || input.name.replace(/_/g, ' ');
        input.dataset.eventTimeRequired = wasRequired ? 'true' : 'false';
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group event-time-picker';
        wrapper.style.maxWidth = '15rem';
        input.parentNode.insertBefore(wrapper, input);

        const hours = document.createElement('select');
        hours.className = 'form-select event-time-hours';
        hours.setAttribute('aria-label', `Timer for ${label} (24-timers klokke)`);
        hours.dataset.eventTimePart = 'hours';
        hours.required = wasRequired;
        addSelectOptions(hours, 'TT', 23);

        const separator = document.createElement('span');
        separator.className = 'input-group-text';
        separator.textContent = ':';
        separator.setAttribute('aria-hidden', 'true');

        const minutes = document.createElement('select');
        minutes.className = 'form-select event-time-minutes';
        minutes.setAttribute('aria-label', `Minutter for ${label}`);
        minutes.dataset.eventTimePart = 'minutes';
        minutes.required = wasRequired;
        addSelectOptions(minutes, 'MM', 59);

        input.type = 'hidden';
        input.required = false;
        input.setAttribute('aria-hidden', 'true');
        input.tabIndex = -1;
        wrapper.appendChild(hours);
        wrapper.appendChild(separator);
        wrapper.appendChild(minutes);
        wrapper.appendChild(input);

        const text = document.createElement('input');
        text.type = 'text';
        text.className = 'form-control event-time-text';
        text.setAttribute('aria-label', `${label} skrevet som HH:mm`);
        text.setAttribute('inputmode', 'numeric');
        text.setAttribute('placeholder', 'HH:mm');
        text.setAttribute('autocomplete', 'off');
        text.setAttribute('maxlength', '5');
        text.hidden = true;
        text.disabled = true;
        wrapper.appendChild(text);

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'btn btn-link btn-sm event-time-toggle';
        toggle.style.width = '100%';
        toggle.style.textAlign = 'left';
        toggle.textContent = 'Skriv klokkeslett';
        toggle.setAttribute('aria-expanded', 'false');
        wrapper.appendChild(toggle);

        function setMode(useText) {
            const parts = timeParts(input.value);
            hours.value = parts ? String(parts.hours).padStart(2, '0') : '';
            minutes.value = parts ? String(parts.minutes).padStart(2, '0') : '';
            text.value = input.value;
            text.hidden = !useText;
            text.disabled = !useText;
            text.required = useText && wasRequired;
            hours.hidden = minutes.hidden = separator.hidden = useText;
            hours.disabled = minutes.disabled = useText;
            input.dataset.eventTimeMode = useText ? 'text' : 'picker';
            toggle.textContent = useText ? 'Velg timer og minutter' : 'Skriv klokkeslett';
            toggle.setAttribute('aria-expanded', String(useText));
            if (useText) {
                updateTypedTime();
            } else {
                text.setCustomValidity('');
                updateStoredTime();
            }
        }
        input.eventTimeSetMode = setMode;
        toggle.addEventListener('click', () => {
            const useText = input.dataset.eventTimeMode !== 'text';
            setMode(useText);
            if (useText) text.focus();
            else hours.focus();
        });

        function updateTypedTime() {
            const value = text.value;
            const message = value && !timeParts(value)
                ? 'Skriv et gyldig klokkeslett som HH:mm (00:00–23:59)' : '';
            text.setCustomValidity(message);
            input.setCustomValidity(message);
            input.dataset.eventTimePartial = message ? 'true' : 'false';
            input.value = value;
            dispatchInputEvents(input);
        }
        text.addEventListener('input', () => {
            if (/^\d{4}$/.test(text.value)) {
                text.value = text.value.slice(0, 2) + ':' + text.value.slice(2);
            }
            updateTypedTime();
        });
        text.addEventListener('blur', () => {
            // Compact numeric input needs no punctuation on a phone keyboard.
            const compact = text.value.match(/^(\d)(\d{2})$/);
            const separated = text.value.match(/^(\d{1,2}):(\d{1,2})$/);
            const parts = compact || separated;
            if (parts) text.value = parts[1].padStart(2, '0') + ':' + parts[2].padStart(2, '0');
            updateTypedTime();
        });

        function updateStoredTime() {
            const hasPartialValue = Boolean(hours.value) !== Boolean(minutes.value);
            const isComplete = Boolean(hours.value && minutes.value);
            input.dataset.eventTimePartial = hasPartialValue ? 'true' : 'false';
            input.setCustomValidity(hasPartialValue ? `Velg både timer og minutter for ${label.toLowerCase()}` : '');
            input.value = isComplete ? `${hours.value}:${minutes.value}` : '';
            const message = hasPartialValue ? `Velg både timer og minutter for ${label.toLowerCase()}` : '';
            hours.setCustomValidity(message);
            minutes.setCustomValidity(message);
            text.value = input.value;
            dispatchInputEvents(input);
        }

        hours.addEventListener('change', updateStoredTime);
        minutes.addEventListener('change', updateStoredTime);

        const parts = timeParts(initialValue);
        if (parts) {
            hours.value = String(parts.hours).padStart(2, '0');
            minutes.value = String(parts.minutes).padStart(2, '0');
        }
    }

    function attachDateTimeControls(scope) {
        if (!scope || typeof scope.querySelectorAll !== 'function') return;
        scope.querySelectorAll(
            'input[name="registration_deadline"], input[name="start_date"], input[name="end_date"], ' +
            'input[name="start_time"], input[name="end_time"]'
        ).forEach(input => {
            if (input.name.endsWith('_time')) enhanceTimeInput(input);
            else enhanceDateInput(input);
        });
    }

    function syncDateTimeControls(scope, resetMode = false) {
        if (!scope || typeof scope.querySelectorAll !== 'function') return;
        scope.querySelectorAll('input[data-event-date-enhanced="true"]').forEach(input => {
            const picker = input.parentNode.querySelector('.event-native-date-picker');
            if (picker) picker.value = toSqlDate(input.value);
        });
        scope.querySelectorAll('input[data-event-time-enhanced="true"]').forEach(input => {
            const wrapper = input.parentNode;
            const hours = wrapper.querySelector('.event-time-hours');
            const minutes = wrapper.querySelector('.event-time-minutes');
            const parts = timeParts(input.value);
            if (parts) {
                hours.value = String(parts.hours).padStart(2, '0');
                minutes.value = String(parts.minutes).padStart(2, '0');
            } else {
                hours.value = '';
                minutes.value = '';
            }
            input.dataset.eventTimePartial = 'false';
            input.setCustomValidity('');
            hours.setCustomValidity('');
            minutes.setCustomValidity('');
            const text = wrapper.querySelector('.event-time-text');
            if (text) {
                text.value = input.value;
                text.setCustomValidity('');
            }
            if (resetMode && typeof input.eventTimeSetMode === 'function') input.eventTimeSetMode(false);
        });
    }

    function updateSummaryDate(form, summaryElement, formatter) {
        if (!summaryElement) return;
        const startDate = form?.querySelector('input[name="start_date"]')?.value || '';
        const startTime = form?.querySelector('input[name="start_time"]')?.value || '';
        summaryElement.textContent = startDate && typeof formatter === 'function'
            ? formatter(startDate, startTime, {
                weekday: true,
                longMonth: true,
                includeTime: Boolean(startTime)
            })
            : '';
    }

    function resetWizard(document, form, state, onReset) {
        if (!form) return false;
        form.reset();
        form.querySelectorAll('input[data-event-time-enhanced="true"]').forEach(input => {
            input.value = '';
            input.defaultValue = '';
            input.dataset.eventTimePartial = 'false';
        });
        form.querySelectorAll('input, textarea, select').forEach(control => {
            if (typeof control.setCustomValidity === 'function') control.setCustomValidity('');
        });
        syncDateTimeControls(form, true);

        if (state) state.currentStep = 1;
        document.querySelectorAll('.wizard-content').forEach((element, index) => {
            element.style.display = index === 0 ? 'block' : 'none';
        });
        document.querySelectorAll('.wizard-step').forEach((element, index) => {
            element.classList.remove('active', 'completed');
            if (index === 0) element.classList.add('active');
        });

        const setDisplay = (id, display) => {
            const element = document.getElementById(id);
            if (element) element.style.display = display;
        };
        setDisplay('wizard-prev', 'none');
        setDisplay('wizard-next', 'block');
        setDisplay('wizard-submit', 'none');
        form.style.display = 'block';
        setDisplay('wizard-success', 'none');
        setDisplay('binding-payment-container', 'none');
        const summaryDate = document.getElementById('summary-date');
        if (summaryDate) summaryDate.textContent = '';

        const progress = document.querySelector('.wizard-progress');
        const navigation = document.querySelector('.wizard-navigation');
        if (progress) progress.style.display = 'block';
        if (navigation) navigation.style.display = 'flex';

        if (typeof onReset === 'function') onReset();
        return true;
    }

    function handleCreateResult(result, callbacks) {
        if (result && result.success === true) {
            if (callbacks && typeof callbacks.onSuccess === 'function') callbacks.onSuccess(result);
            return true;
        }
        if (callbacks && typeof callbacks.onFailure === 'function') {
            callbacks.onFailure(result && result.message ? result.message : 'Kunne ikke opprette arrangement');
        }
        return false;
    }

    return {
        attachDateTimeControls,
        dateParts,
        formatDateDigits,
        handleCreateResult,
        resetWizard,
        syncDateTimeControls,
        toNorskDate,
        toSqlDate,
        updateSummaryDate
    };
});