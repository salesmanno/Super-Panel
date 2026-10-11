(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.RentalRequestPricing = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    function amountToCents(value, label, required, correction = '') {
        if (value === null || value === undefined || String(value).trim() === '') {
            if (!required) return 0;
            throw new Error(`${label} mangler.${correction ? ` ${correction}` : ''}`);
        }

        const normalized = String(value).trim().replace(',', '.');
        if (!/^\d+(?:\.\d{1,2})?$/.test(normalized)) {
            throw new Error(`${label} er ugyldig.${correction ? ` ${correction}` : ''}`);
        }

        const [whole, fraction = ''] = normalized.split('.');
        const cents = (Number(whole) * 100) + Number(fraction.padEnd(2, '0'));
        if (!Number.isSafeInteger(cents)) {
            throw new Error(`${label} er for høyt til å beregne.`);
        }
        return cents;
    }

    function dateAtUtcMidnight(value) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
        if (!match) throw new Error('Velg gyldig start- og sluttdato for å beregne prisen.');

        const date = new Date(0);
        date.setUTCHours(0, 0, 0, 0);
        date.setUTCFullYear(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        if (date.getUTCFullYear() !== Number(match[1])
            || date.getUTCMonth() !== Number(match[2]) - 1
            || date.getUTCDate() !== Number(match[3])) {
            throw new Error('Velg gyldig start- og sluttdato for å beregne prisen.');
        }
        return date;
    }

    function normalizeAdjustment(type, amount, explanation) {
        if (!type || type === 'none') {
            return { type: 'none', amountCents: 0, explanation: '' };
        }
        if (type !== 'discount' && type !== 'surcharge') {
            throw new Error('Ugyldig type prisjustering.');
        }

        const amountCents = amountToCents(amount, 'Beløpet på prisjusteringen', true);
        if (amountCents <= 0) {
            throw new Error('Prisjusteringen må ha et beløp større enn null.');
        }
        const cleanExplanation = String(explanation || '').trim();
        if (!cleanExplanation) {
            throw new Error('Prisjusteringen må ha en forklaring.');
        }
        return { type, amountCents, explanation: cleanExplanation };
    }

    function weekdayPriceCents(value) {
        if (value === null || value === undefined) return null;
        if (typeof value === 'string') {
            try { value = JSON.parse(value); }
            catch (_) { throw new Error('Ukedagsprisene har ugyldig JSON.'); }
            if (value === null) return null;
        }
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        if (!value || Array.isArray(value) || typeof value !== 'object'
            || Object.keys(value).length !== 5 || Object.keys(value).some(key => !days.includes(key))) {
            throw new Error('Ukedagsprisene må inneholde mandag til fredag.');
        }
        const prices = {};
        days.forEach(day => { prices[day] = amountToCents(value[day], 'Ukedagsprisen', true); });
        return prices;
    }

    function calculateRentalQuote({ rates, startDate, endDate, cleaningFee, cleaning, adjustment }) {
        if (!rates || typeof rates !== 'object') {
            throw new Error('Prisene for utleieobjektet mangler. Oppdater prisene før godkjenning.');
        }

        const rateCorrection = 'Oppdater prisene på utleieobjektet.';
        const weekdays = weekdayPriceCents(rates.weekday_prices);
        const daily = amountToCents(rates.rental_price, 'Dagsprisen', weekdays === null, rateCorrection);
        const weekend = amountToCents(rates.weekend_price, 'Helgeprisen', false, rateCorrection);
        const saturday = amountToCents(rates.saturday_price, 'Lørdagsprisen', false, rateCorrection);
        const sunday = amountToCents(rates.sunday_price, 'Søndagsprisen', false, rateCorrection);
        const start = dateAtUtcMidnight(startDate);
        const end = dateAtUtcMidnight(endDate);
        if (end < start) {
            throw new Error('Sluttdato kan ikke være før startdato.');
        }

        let baseCents = 0;
        for (const day = new Date(start); day <= end; day.setUTCDate(day.getUTCDate() + 1)) {
            const weekday = day.getUTCDay();
            if (weekday >= 1 && weekday <= 5 && weekdays !== null) {
                baseCents += weekdays[['monday','tuesday','wednesday','thursday','friday'][weekday - 1]];
                continue;
            }
            if (weekdays !== null && (weekday === 0 || weekday === 6) && weekend <= 0
                && (weekday === 6 ? saturday : sunday) <= 0) {
                amountToCents(rates.rental_price, 'Grunnprisen for helg', true, rateCorrection);
            }
            baseCents += weekday === 6 && saturday > 0 ? saturday
                : weekday === 0 && sunday > 0 ? sunday
                    : (weekday === 0 || weekday === 6) && weekend > 0 ? weekend
                        : daily;
        }

        const cleaningCents = cleaning
            ? amountToCents(cleaningFee, 'Renholdsgebyret', false)
            : 0;
        const normalizedAdjustment = normalizeAdjustment(
            adjustment?.type,
            adjustment?.amount,
            adjustment?.explanation
        );
        const adjustmentCents = normalizedAdjustment.type === 'discount'
            ? -normalizedAdjustment.amountCents
            : normalizedAdjustment.type === 'surcharge' ? normalizedAdjustment.amountCents : 0;

        return {
            baseCents,
            cleaningCents,
            adjustmentCents,
            totalCents: Math.max(0, baseCents + cleaningCents + adjustmentCents),
        };
    }

    return { amountToCents, normalizeAdjustment, calculateRentalQuote, weekdayPriceCents };
});
