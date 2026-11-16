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

    const weekDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    function weekMoney(value) {
        if (!['string','number'].includes(typeof value)
            || !/^\d{1,8}(?:\.\d{1,2})?$/.test(String(value).trim().replace(',','.'))) {
            throw new Error('Prisen må være 0–99 999 999,99 kr, med høyst to desimaler.');
        }
        return amountToCents(value, 'Dags- eller koblingsprisen', true);
    }
    function weekJson(value, label) {
        if (typeof value === 'string') {
            try { value = JSON.parse(value); } catch (_) { throw new Error(`${label} har ugyldig JSON.`); }
        }
        if (!value || typeof value !== 'object') throw new Error(`${label} mangler eller er ugyldig.`);
        return value;
    }
    function calculateWeeklyBase(rates, start, end) {
        const daily = weekJson(rates.daily_prices, 'Dagsprisene');
        if (Array.isArray(daily) || Object.keys(daily).length !== 7
            || Object.keys(daily).some(key => !weekDays.includes(key))) {
            throw new Error('Fyll ut enkeltpris for alle dager fra mandag til søndag.');
        }
        const groups = weekJson(rates.price_groups, 'Dagskoblingene');
        if (!Array.isArray(groups) || groups.length > 2) throw new Error('Det kan være høyst to dagskoblinger per objekt.');
        const occupied = new Set(), slots = new Set();
        const normalized = groups.map(group => {
            if (!group || typeof group !== 'object') throw new Error('Ugyldig dagskobling.');
            for (const [key,max] of [['slot',2],['start_day',7],['end_day',7]]) {
                if (!['string','number'].includes(typeof group[key]) || !/^[1-7]$/.test(String(group[key]))
                    || Number(group[key]) > max) throw new Error('Ugyldig fra-dag, til-dag eller koblingsnummer.');
            }
            const slot = Number(group.slot), from = Number(group.start_day), to = Number(group.end_day);
            if (slots.has(slot) || from === to) throw new Error('Koblinger må ha unikt nummer og dekke minst to dager.');
            if (from > to && !(from === 7 && to === 1)) {
                throw new Error('Dagskoblingen har feil dagrekkefølge. Søndag–mandag er tillatt; øvrige koblinger følger ukerekkefølgen.');
            }
            slots.add(slot);
            const length = (to - from + 7) % 7 + 1;
            for (let i=0;i<length;i++) {
                const day = (from-1+i)%7+1;
                if (occupied.has(day)) throw new Error('Dagskoblingene kan ikke overlappe.');
                occupied.add(day);
            }
            return {slot,from,to,length,cents:weekMoney(group.price)};
        }).sort((a,b)=>a.slot-b.slot);
        const prices = weekDays.map((key,index) => {
            const value = daily[key];
            if (occupied.has(index+1) && (value === null || value === undefined
                || (typeof value === 'string' && value.trim() === ''))) return null;
            return weekMoney(value);
        });
        if ((end-start)/86400000 >= 3660) throw new Error('Velg en gyldig leieperiode på høyst 3660 dager.');
        let baseCents = 0;
        const seen = new Set(), lines = [];
        for (const date = new Date(start); date <= end; date.setUTCDate(date.getUTCDate()+1)) {
            const weekday = date.getUTCDay() || 7;
            const group = normalized.find(g => (weekday-g.from+7)%7 < g.length);
            if (group) {
                const anchor = new Date(date);
                anchor.setUTCDate(anchor.getUTCDate()-(weekday-group.from+7)%7);
                const anchorDate = anchor.toISOString().slice(0,10), key = `${group.slot}:${anchorDate}`;
                if (!seen.has(key)) {
                    baseCents += group.cents;
                    lines.push({type:'group',slot:group.slot,anchor_date:anchorDate,
                        start_day:group.from,end_day:group.to,amount_cents:group.cents});
                    seen.add(key);
                }
            } else {
                const cents = prices[weekday-1];
                baseCents += cents;
                lines.push({type:'day',date:date.toISOString().slice(0,10),amount_cents:cents});
            }
        }
        return {baseCents,lines};
    }

    function calculateRentalQuote({ rates, startDate, endDate, cleaningFee, cleaning, adjustment, membershipBenefits = [] }) {
        if (!rates || typeof rates !== 'object') {
            throw new Error('Prisene for utleieobjektet mangler. Oppdater prisene før godkjenning.');
        }

        const model = rates.pricing_model ?? 'legacy';
        if (!['legacy','weekly'].includes(model)) throw new Error('Ukjent prismodell for utleieobjektet.');
        const start = dateAtUtcMidnight(startDate);
        const end = dateAtUtcMidnight(endDate);
        if (end < start) throw new Error('Sluttdato kan ikke være før startdato.');
        let baseCents = 0, priceLines = [];
        if (model === 'weekly') {
            const quote = calculateWeeklyBase(rates,start,end);
            baseCents = quote.baseCents; priceLines = quote.lines;
        } else {
        const rateCorrection = 'Oppdater prisene på utleieobjektet.';
        const flag = rates.weekday_prices_enabled;
        const weekdayData = rates.weekday_prices === 'null' ? null : rates.weekday_prices;
        let weekdayPricingEnabled;
        if (flag === null || flag === undefined || flag === '') {
            weekdayPricingEnabled = weekdayData !== null && weekdayData !== undefined
                && weekdayData !== '';
        } else if (String(flag) === '0' || flag === false) {
            weekdayPricingEnabled = false;
        } else if (String(flag) === '1' || flag === true) {
            weekdayPricingEnabled = true;
        } else {
            throw new Error('Valget for ukedagspriser er ugyldig.');
        }
        const weekdays = weekdayPricingEnabled ? weekdayPriceCents(weekdayData) : null;
        if (weekdayPricingEnabled && weekdays === null) {
            throw new Error('Ukedagspriser er slått på, men mangler. Oppdater prisene på utleieobjektet.');
        }
        const daily = amountToCents(rates.rental_price, 'Dagsprisen', weekdays === null, rateCorrection);
        const weekend = amountToCents(rates.weekend_price, 'Helgeprisen', false, rateCorrection);
        const saturday = amountToCents(rates.saturday_price, 'Lørdagsprisen', false, rateCorrection);
        const sunday = amountToCents(rates.sunday_price, 'Søndagsprisen', false, rateCorrection);
        const chargedWeekends = new Set();
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
            if ((weekday === 0 || weekday === 6) && weekend > 0 && saturday <= 0 && sunday <= 0) {
                const saturdayDate = new Date(day);
                if (weekday === 0) saturdayDate.setUTCDate(saturdayDate.getUTCDate()-1);
                const key = saturdayDate.toISOString().slice(0,10);
                if (!chargedWeekends.has(key)) baseCents += weekend;
                chargedWeekends.add(key);
                continue;
            }
            baseCents += weekday === 6 && saturday > 0 ? saturday
                : weekday === 0 && sunday > 0 ? sunday
                    : (weekday === 0 || weekday === 6) && weekend > 0 ? weekend
                        : daily;
        }
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

        const rentalDays = Math.round((end-start)/86400000)+1;
        let memberBaseCents = baseCents;
        let membershipBenefit = null;
        if (!Array.isArray(membershipBenefits)) throw new Error('Ugyldige medlemsfordeler.');
        for (const rule of membershipBenefits) {
            const basis = rule.price_basis || 'unit';
            const type = rule.benefit_type;
            if (!['unit','booking_total','rental_day'].includes(basis)
                || (basis === 'rental_day' && type !== 'amount_discount')) throw new Error('Ugyldig prisgrunnlag for medlemsfordelen.');
            let candidate = baseCents;
            if (type === 'included') candidate = 0;
            else if (type !== 'access') {
                const cents = amountToCents(rule.benefit_value, 'Medlemsfordelen', true);
                if (cents > 100000000000) throw new Error('Medlemsfordelen er for stor.');
                if (type === 'amount_discount') candidate = Math.max(0, baseCents - cents * (basis === 'rental_day' ? rentalDays : 1));
                else if (type === 'percent_discount') {
                    if (cents > 10000) throw new Error('Medlemsrabatten kan ikke overstige 100 prosent.');
                    candidate = Math.round(baseCents * (10000 - cents) / 10000);
                } else if (type === 'fixed_price') candidate = Math.min(baseCents, cents);
                else throw new Error('Ukjent medlemsfordel.');
            }
            if (candidate < memberBaseCents) { memberBaseCents = candidate; membershipBenefit = rule; }
        }
        return {
            baseCents,
            priceLines,
            rentalDays,
            memberBaseCents,
            membershipDiscountCents: baseCents - memberBaseCents,
            membershipBenefit,
            cleaningCents,
            adjustmentCents,
            totalCents: Math.max(0, memberBaseCents + cleaningCents + adjustmentCents),
        };
    }

    return { amountToCents, normalizeAdjustment, calculateRentalQuote, weekdayPriceCents };
});
