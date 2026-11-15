const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {calculateRentalQuote} = require('../assets/rental_request_pricing.js');
const cases = JSON.parse(execFileSync('php',[__dirname+'/rental_week_pricing_test.php','--json'],{encoding:'utf8'}));
for (const item of cases) {
    const quote = calculateRentalQuote({...item,cleaning:false,adjustment:null});
    assert.equal(quote.baseCents,item.expected.base_cents);
    assert.equal(quote.rentalDays,item.expected.rental_days);
    assert.deepEqual(quote.priceLines,item.expected.lines);
}
const item=cases[0];
for (const patch of [{price_groups:null},{price_groups:[{slot:1,start_day:1,end_day:1,price:0}]},
    {price_groups:[{slot:1,start_day:1,end_day:2,price:10},{slot:2,start_day:2,end_day:3,price:20}]},
    {daily_prices:{}},{pricing_model:'unknown'}]) {
    assert.throws(()=>calculateRentalQuote({...item,rates:{...item.rates,...patch}}));
}
const quote=calculateRentalQuote({...item,cleaning:true,cleaningFee:'250.00',
    adjustment:{type:'discount',amount:'100.00',explanation:'Avtalt'},
    membershipBenefits:[{benefit_type:'percent_discount',benefit_value:'10',price_basis:'booking_total'}]});
assert.equal(quote.totalCents,Math.round(quote.baseCents*.9)+25000-10000);
console.log(`Weekly JS/PHP parity passed for ${cases.length} periods including DST, year boundaries and wrap groups`);
