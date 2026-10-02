const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const pricing = require('../assets/rental_request_pricing.js');

const rates = {
  rental_price: '100.00',
  weekend_price: '150.00',
  saturday_price: '175.00',
  sunday_price: '125.00',
};
const quote = (startDate, endDate, options = {}) => pricing.calculateRentalQuote({
  rates: options.rates || rates,
  startDate,
  endDate,
  cleaningFee: options.cleaningFee,
  cleaning: options.cleaning ?? false,
  adjustment: options.adjustment,
});

assert.equal(quote('2026-04-06', '2026-04-07').baseCents, 20000,
  'Changing a weekday date range recalculates the inclusive daily price');
assert.equal(quote('2026-04-03', '2026-04-05').baseCents, 40000,
  'Period pricing matches the daily, Saturday and Sunday rate rules');
assert.equal(quote('2026-04-04', '2026-04-05', {
  cleaning: true,
  cleaningFee: '75.50',
  adjustment: {type: 'discount', amount: '10.00', explanation: 'Rabatt'},
}).totalCents, 36550, 'Cleaning and discount are calculated from cents');

const none = pricing.normalizeAdjustment('none', '10.00', 'Gammel forklaring');
assert.deepEqual(none, {type: 'none', amountCents: 0, explanation: ''},
  'No adjustment discards stale amount and explanation values');
assert.equal(quote('2026-04-04', '2026-04-05', {
  cleaning: true,
  cleaningFee: '75.50',
  adjustment: none,
}).totalCents, 37550, 'Removing an adjustment retains the base rental and cleaning fee');
assert.equal(quote('2026-04-06', '2026-04-06', {
  rates: {rental_price: '0.00'},
}).totalCents, 0, 'A deliberately configured zero daily price is valid');

assert.throws(() => pricing.calculateRentalQuote({
  rates: {rental_price: null},
  startDate: '2026-04-06',
  endDate: '2026-04-06',
}), /Dagsprisen mangler/, 'Missing daily rate is an explicit error, not a free quote');
assert.throws(() => pricing.calculateRentalQuote({
  rates: {rental_price: '100.999'},
  startDate: '2026-04-06',
  endDate: '2026-04-06',
}), /Dagsprisen er ugyldig/, 'Malformed price precision is rejected');
assert.throws(() => quote('2026-04-31', '2026-05-01'),
  /gyldig start- og sluttdato/, 'Invalid dates do not turn into a zero quote');
assert.throws(() => pricing.normalizeAdjustment('discount', '10.00', ''),
  /forklaring/, 'Discount still requires an explanation');

const content = fs.readFileSync('modules/rental_content.php', 'utf8');
assert.match(content, /addEventListener\('change', \(\) => refreshRentalRequestPrice\(row\)\)/,
  'Both rental date inputs recalculate when changed');
assert.match(content, /handleRentalRequestAdjustmentChange/,
  'Selecting no adjustment clears stale values and refreshes the quote');
assert.match(content, /data-price-error/,
  'Missing rates are displayed as a quote error instead of a zero amount');
const changeStart = content.indexOf('function handleRentalRequestAdjustmentChange(row) {');
const pricingFunctionsStart = content.indexOf('function rentalAdjustmentFromRow(row) {');
const changeEnd = content.indexOf('\nasync function loadRentalRequests()', pricingFunctionsStart);
assert(pricingFunctionsStart >= 0 && changeStart > pricingFunctionsStart && changeEnd > changeStart,
  'Rental request pricing handlers are available');
const controls = {
  type: {value: 'none'},
  amount: {value: '10.00', disabled: false},
  explanation: {value: 'Gammel forklaring', disabled: false},
  startDate: {value: '2026-04-06'},
  endDate: {value: '2026-04-07'},
  cleaning: {checked: false},
  base: {textContent: ''},
  cleaningSummary: {textContent: ''},
  total: {textContent: ''},
  adjustmentSummary: {textContent: ''},
  priceError: {textContent: '', hidden: true},
  approve: {disabled: false},
};
const row = {
  dataset: {
    busy: 'false',
    dailyPrice: '100.00',
    weekendPrice: '150.00',
    saturdayPrice: '175.00',
    sundayPrice: '125.00',
    cleaningFee: '75.50',
  },
  querySelector(selector) {
    if (selector === '.rental-request-adjustment-type') return controls.type;
    if (selector === '.rental-request-adjustment-amount') return controls.amount;
    if (selector === '.rental-request-adjustment-explanation') return controls.explanation;
    if (selector === '[data-rental-request-field="start-date"]') return controls.startDate;
    if (selector === '[data-rental-request-field="end-date"]') return controls.endDate;
    if (selector === '.rental-request-cleaning') return controls.cleaning;
    if (selector === '.rental-request-base-fee') return controls.base;
    if (selector === '.rental-request-cleaning-fee') return controls.cleaningSummary;
    if (selector === '.rental-request-total') return controls.total;
    if (selector === '.rental-request-adjustment-summary') return controls.adjustmentSummary;
    if (selector === '.rental-request-price-error') return controls.priceError;
    if (selector === '.rental-request-approve') return controls.approve;
    return null;
  },
};
const handlerContext = {RentalRequestPricing: pricing};
vm.createContext(handlerContext);
vm.runInContext(content.slice(pricingFunctionsStart, changeEnd), handlerContext);
assert.equal(handlerContext.refreshRentalRequestPrice(row).totalCents, 20000,
  'The request row initially quotes its two inclusive weekdays');
controls.startDate.value = '2026-04-03';
controls.endDate.value = '2026-04-05';
assert.equal(handlerContext.refreshRentalRequestPrice(row).baseCents, 40000,
  'Changing the dates recalculates the request row with weekend rates');
controls.cleaning.checked = true;
assert.equal(handlerContext.refreshRentalRequestPrice(row).totalCents, 47550,
  'The refreshed quote retains selected cleaning');
controls.type.value = 'discount';
controls.amount.value = '10.00';
controls.explanation.value = 'Rabatt';
assert.equal(handlerContext.rentalAdjustmentFromRow(row).amountCents, 1000,
  'Discount amount is read from the request row');
const discountedQuote = handlerContext.refreshRentalRequestPrice(row);
assert.ok(discountedQuote, row.dataset.priceError);
assert.equal(discountedQuote.totalCents, 46550,
  'A valid discount adjusts the current base and cleaning quote');
vm.runInContext(content.slice(changeStart, changeEnd), handlerContext);
controls.type.value = 'none';
handlerContext.handleRentalRequestAdjustmentChange(row);
assert.equal(controls.amount.value, '', 'Switching to no adjustment clears the previous amount');
assert.equal(controls.explanation.value, '', 'Switching to no adjustment clears the previous explanation');
assert.equal(controls.amount.disabled, true, 'Amount is disabled when no adjustment is selected');
assert.equal(controls.explanation.disabled, true, 'Explanation is disabled when no adjustment is selected');
assert.equal(row.dataset.rentalTotal, '475.5',
  'Removing an adjustment preserves base rent and the cleaning fee');
controls.type.value = 'discount';
handlerContext.handleRentalRequestAdjustmentChange(row);
assert.equal(controls.amount.disabled, false, 'Amount is enabled for a discount');
assert.equal(controls.explanation.disabled, false, 'Explanation is enabled for a discount');
controls.type.value = 'none';
handlerContext.handleRentalRequestAdjustmentChange(row);
row.dataset.dailyPrice = '';
assert.equal(handlerContext.refreshRentalRequestPrice(row), null,
  'A missing rate prevents a valid quote');
assert.match(row.dataset.priceError, /Dagsprisen mangler/,
  'A missing rate produces an explicit price error');
assert.equal(controls.priceError.hidden, false, 'The price error is shown in the request row');
assert.equal(controls.approve.disabled, true, 'Approval is disabled when its price cannot be calculated');
row.dataset.dailyPrice = '0.00';
row.dataset.weekendPrice = '0.00';
row.dataset.saturdayPrice = '0.00';
row.dataset.sundayPrice = '0.00';
controls.type.value = 'none';
controls.cleaning.checked = false;
handlerContext.handleRentalRequestAdjustmentChange(row);
assert.equal(row.dataset.rentalTotal, '0',
  'A deliberate zero-price object remains a valid quote');
assert.equal(controls.approve.disabled, false, 'A configured zero price is not treated as missing');

console.log('rental request pricing tests passed');
