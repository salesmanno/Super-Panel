const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('modules/rental_content.php', 'utf8');
const begin = source.indexOf('function renderObjectCard(obj) {');
const end = source.indexOf('function buildCreateForm(', begin);
assert(begin >= 0 && end > begin, 'Card functions must be present');

const object = {
  id: 'venue-1',
  object_name: 'Grendehuset',
  category: 'venue',
  is_available: 1,
  rental_price: 500,
  type_data: {access_code: 'OLD123'},
};
let savedCode = 'OLD123';
let getCalls = 0;
let updateCalls = 0;
let availabilityCalls = 0;
let failCodeSave = false;
const attributes = {};
const classes = new Set();
const codeValue = {textContent: savedCode};
const display = {
  hidden: false,
  querySelector(selector) { return selector === 'code' ? codeValue : null; },
};
const input = {
  value: '',
  disabled: false,
  reportValidity: () => true,
  focus() {},
  select() {},
};
const status = {
  textContent: '',
  classList: {add(name) { classes.add(name); }, remove(name) { classes.delete(name); }},
};
const accessButton = {
  disabled: false,
  dataset: {rentalAccessMode: 'display'},
  title: '',
  setAttribute(name, value) { attributes[name] = value; },
  focus() {},
};
const form = {
  hidden: true,
  isConnected: true,
  querySelector(selector) {
    return selector === '[data-rental-access-input]' ? input : null;
  },
};
const stateBadge = {innerHTML: ''};
const visibilityAction = {dataset: {}, innerHTML: ''};
const objectCard = {
  classList: {toggle(name, active) { if (active) classes.add(name); else classes.delete(name); }},
  querySelector(selector) {
    if (selector === '.rental-card-status') return stateBadge;
    return null;
  },
};
const wrapper = {
  isConnected: true,
  querySelector(selector) {
    if (selector === '.rental-object-card') return objectCard;
    if (selector === '.rental-access-control') return accessButton;
    if (selector === '[data-rental-access-value]') return display;
    if (selector === '.rental-access-edit-form') return form;
    if (selector === '[data-rental-access-status]') return status;
    return null;
  },
};
const context = {
  allRentalObjects: [object],
  rentalSchemas: {venue: {label: 'Lokale'}},
  document: {
    getElementById: id => id === 'rental-card-venue-1' ? wrapper : null,
    querySelector(selector) {
      if (selector.includes('[data-rental-visibility=')) return visibilityAction;
      if (selector.includes('[data-rental-access-value] code')) return codeValue;
      return null;
    },
  },
  CSS: {escape: value => String(value)},
  escHtml: value => String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;'),
  escAttr: value => String(value),
  validRentalImageUrl: () => false,
  toast: {quickSuccess() {}, quickError() {}},
  rentalFilter: '',
  Event,
  CustomEvent,
  ensureRentalFormToken: async () => 'fixture-csrf',
  fetch: async (url, options) => {
    assert.match(url, /update_rental_object\.php$/);
    const data = JSON.parse(options.body);
    assert.equal(data.id, object.id);
    if (Object.hasOwn(data, 'is_available')) {
      availabilityCalls++;
      object.is_available = data.is_available;
      return {ok: true, text: async () => JSON.stringify({success: true})};
    }
    updateCalls++;
    assert.deepEqual(data.type_data, {access_code: input.value.trim()});
    if (failCodeSave) return {ok: false, json: async () => ({success: false, message: 'Lagring feilet'})};
    savedCode = data.type_data.access_code;
    return {ok: true, json: async () => ({success: true})};
  },
};
vm.createContext(context);
const weekdayBegin = source.indexOf('const rentalWeekdays = [');
const weekdayEnd = source.indexOf('function rentalWeekdayMode(', weekdayBegin);
assert(weekdayBegin >= 0 && weekdayEnd > weekdayBegin, 'Real weekday display helpers must be present');
vm.runInContext(source.slice(weekdayBegin, weekdayEnd), context);
vm.runInContext(source.slice(begin, end), context);
const availabilityBegin = source.indexOf('async function toggleAvailability(');
const availabilityEnd = source.indexOf('async function editRentalObject(', availabilityBegin);
vm.runInContext(source.slice(availabilityBegin, availabilityEnd), context);

(async () => {
  const html = context.renderObjectCard(object);
  assert.match(html, /aria-expanded="false"/);
  assert.match(html, /data-rental-fields="venue-1"/);
  assert.match(html, /data-rental-edit-fields="venue-1"/);
  assert.match(html, /editRentalObject/);
  assert.match(html, /toggleAvailability/);
  assert.match(html, /toggleRentalAccessCode/);
  assert.match(html, /data-rental-access-input/);
  assert.match(html, /deleteRentalObject/);
  assert.doesNotMatch(html, /Lagre kode/);

  context.toggleRentalAccessCode(object.id);
  assert.equal(form.hidden, false);
  assert.equal(display.hidden, true);
  assert.equal(attributes['aria-expanded'], 'true');
  assert.equal(input.value, 'OLD123');
  assert.equal(updateCalls, 0, 'opening code editing does not mutate the object');
  input.value = 'DRAFT2';
  await context.toggleAvailability(object.id, false);
  assert.equal(availabilityCalls, 1);
  assert.equal(form.hidden, false, 'availability changes do not close code editing');
  assert.equal(input.value, 'DRAFT2', 'availability changes do not discard the code draft');
  assert.match(stateBadge.innerHTML, /Skjult/);
  assert(classes.has('is-muted'));

  input.value = '0000';
  await context.toggleRentalAccessCode(object.id);
  assert.equal(updateCalls, 0, 'invalid code is rejected before writing');
  assert.match(status.textContent, /0000/);

  input.value = 'A732';
  failCodeSave = true;
  await context.toggleRentalAccessCode(object.id);
  assert.equal(updateCalls, 1);
  assert.equal(form.hidden, false, 'failed save keeps code editing open');
  assert.equal(input.value, 'A732', 'failed save retains the user draft');
  assert.equal(codeValue.textContent, 'OLD123', 'failed save does not change the displayed code');
  assert.equal(status.textContent, 'Lagring feilet');

  failCodeSave = false;
  input.value = 'NEW456';
  await context.toggleRentalAccessCode(object.id);
  assert.equal(updateCalls, 2);
  assert.equal(savedCode, 'NEW456');
  assert.equal(object.type_data.access_code, 'NEW456');
  assert.equal(form.hidden, true, 'successful save returns to card display');
  assert.equal(display.hidden, false);
  assert.equal(codeValue.textContent, 'NEW456');
  assert.equal(attributes['aria-expanded'], 'false');
  assert.equal(status.textContent, 'Koden er lagret.');
  assert.equal(getCalls, 0, 'inline editing uses the code already shown on the card');
  await context.toggleAvailability(object.id, true);
  assert.equal(availabilityCalls, 2);
  console.log('Rental card menu and access-code tests passed');
})().catch(error => { console.error(error); process.exitCode = 1; });