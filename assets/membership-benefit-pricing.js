(function(root,factory){const api=factory();if(typeof module==='object'&&module.exports)module.exports=api;else root.MembershipBenefitPricing=api;})(typeof window!=='undefined'?window:globalThis,function(){
    'use strict';
    function numeric(value) {
        const text=String(value??'').trim().replace(',','.');
        if(!/^\d+(?:\.\d{1,2})?$/.test(text))throw new Error('Oppgi et positivt beløp med maks to desimaler.');
        const number=Number(text);
        if(!Number.isFinite(number)||number>1000000000)throw new Error('Beløpet er for stort.');
        return number;
    }
    function parseRentalDiscount(text,mode='per_day'){
        text=String(text??'').trim();
        const percent=text.endsWith('%')||mode==='percentage';
        if(text.endsWith('%'))text=text.slice(0,-1).trim();
        const value=numeric(text);
        if(percent&&value>100)throw new Error('Prosent må være mellom 0 og 100.');
        return {benefit_type:percent?'percent_discount':'amount_discount',benefit_value:value,price_basis:percent?'booking_total':'rental_day'};
    }
    function applyBenefit(baseCents,rule,days=1){
        if(!Number.isSafeInteger(baseCents)||baseCents<0||!Number.isSafeInteger(days)||days<1)throw new Error('Ugyldig prisgrunnlag.');
        let totalCents=baseCents;
        if(rule){
            const type=rule.benefit_type;
            if(type==='included')totalCents=0;
            else if(type!=='access'){
                const value=numeric(rule.benefit_value);
                if(type==='percent_discount'){
                    if(value>100)throw new Error('Prosent må være mellom 0 og 100.');
                    totalCents=Math.round(baseCents*(10000-Math.round(value*100))/10000);
                }else if(type==='amount_discount')totalCents=Math.max(0,baseCents-Math.round(value*100)*(rule.price_basis==='rental_day'?days:1));
                else if(type==='fixed_price')totalCents=Math.round(value*100)*(rule.price_basis==='rental_day'?days:1);
                else throw new Error('Ukjent fordelstype.');
            }
        }
        return {baseCents,discountCents:baseCents-totalCents,totalCents};
    }
    return {parseRentalDiscount,applyBenefit};
});
