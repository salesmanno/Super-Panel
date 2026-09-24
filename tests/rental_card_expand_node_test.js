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
const input = () => ({value: '', disabled: true, reportValidity: () => true});
const status = () => ({textContent: '', classList: {add() {}, remove() {}}});
const button = () => ({disabled: true});
const panel = {
  hidden: true,
  isConnected: true,
  html: '',
  set innerHTML(value) {
    this.html = value;
    this.form = {
      input: input(),
      status: status(),
      button: button(),
      isConnected: true,
      querySelector(selector) {
        if (selector === 'input') return this.input;
        if (selector === '[data-code-status]') return this.status;
        if (selector === 'button[type="submit"]') return this.button;
        return null;
      },
    };
  },
  get innerHTML() { return this.html; },
  replaceChildren() { this.html = ''; this.form.isConnected = false; },
  querySelector(selector) { return this.form.querySelector(selector); },
};
const attributes = {};
const classes = new Set();
const hint = {innerHTML: ''};
const stateBadge = {innerHTML: ''};
const switchLabel = {textContent: 'Synlig'};
const card = {
  classList: {toggle(name, active) { if (active) classes.add(name); else classes.delete(name); }},
  setAttribute(name, value) { attributes[name] = value; },
  querySelector(selector) {
    if (selector === '.rental-expand-hint') return hint;
    if (selector === '.rental-card-status') return stateBadge;
    if (selector === '.form-switch .form-check-label') return switchLabel;
    return null;
  },
};
const wrapper = {querySelector: () => card};
const elements = {
  'rental-card-venue-1': wrapper,
  'rental-expanded-venue-1': panel,
};
const context = {
  allRentalObjects: [object],
  rentalSchemas: {venue: {label: 'Lokale'}},
  document: {getElementById: id => elements[id]},
  escHtml: value => String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;'),
  escAttr: value => String(value),
  validRentalImageUrl: () => false,
  fetch: async (url, options) => {
    if (options?.method === 'POST') {
      const data = JSON.parse(options.body);
      assert.equal(data.id, object.id);
      if (Object.hasOwn(data, 'is_available')) {
        availabilityCalls++;
        return {ok: true, text: async () => JSON.stringify({success: true})};
      }
      updateCalls++;
      assert.deepEqual(data.type_data, {access_code: 'NEW456'});
      savedCode = data.type_data.access_code;
      return {ok: true, json: async () => ({success: true})};
    }
    getCalls++;
    assert.match(url, /get_rental_objects\.php\?id=venue-1$/);
    return {ok: true, json: async () => ({success: true, object: {
      category: 'venue', type_data: {access_code: savedCode},
    }})};
  },
  toast: {quickSuccess() {}, quickError() {}},
  rentalFilter: '',
};
vm.createContext(context);
vm.runInContext(source.slice(begin, end), context);
const availabilityBegin = source.indexOf('async function toggleAvailability(');
const availabilityEnd = source.indexOf('async function editRentalObject(', availabilityBegin);
vm.runInContext(source.slice(availabilityBegin, availabilityEnd), context);
const tick = () => new Promise(resolve => setImmediate(resolve));

(async () => {
  const html = context.renderObjectCard(object);
  assert.match(html, /aria-expanded="false"/);
  assert.match(html, /handleRentalObjectCardClick/);
  assert.match(html, /event\.stopPropagation\(\); toggleContractForm/);
  assert.match(html, /event\.stopPropagation\(\); viewDetails/);
  assert.match(html, /event\.stopPropagation\(\); deleteRentalObject/);
  assert.doesNotMatch(html, /value="OLD123"/);

  context.handleRentalObjectCardClick({target: {closest: () => true}}, object.id);
  assert.equal(panel.hidden, true, 'Own buttons and switch do not expand');
  context.handleRentalObjectCardClick({target: {closest: () => null}}, object.id);
  await tick();
  assert.equal(panel.hidden, false);
  assert.equal(attributes['aria-expanded'], 'true');
  assert(classes.has('is-expanded'));
  assert.equal(panel.form.input.value, 'OLD123');
  assert.equal(panel.form.button.disabled, false);
  await context.toggleAvailability(object.id, false);
  assert.equal(availabilityCalls, 1);
  assert.equal(panel.hidden, false, 'Switch does not close the expanded card');
  assert.equal(panel.form.input.value, 'OLD123', 'Switch does not discard unsaved code');
  assert.equal(switchLabel.textContent, 'Skjult');
  assert(classes.has('is-muted'));

  const oldForm = panel.form;
  oldForm.input.value = 'NEW456';
  await context.saveRentalCardAccessCode({
    preventDefault() {}, stopPropagation() {}, currentTarget: oldForm,
  }, object.id);
  assert.equal(updateCalls, 1);
  assert.equal(savedCode, 'NEW456');
  assert.equal(oldForm.status.textContent, 'Koden er lagret.');

  context.handleRentalObjectCardKeydown({
    target: card, currentTarget: card, key: 'Enter', preventDefault() {},
  }, object.id);
  assert.equal(panel.hidden, true);
  assert.equal(attributes['aria-expanded'], 'false');
  assert.equal(panel.innerHTML, '', 'Closing removes the code field from the DOM');

  context.handleRentalObjectCardKeydown({
    target: card, currentTarget: card, key: ' ', preventDefault() {},
  }, object.id);
  await tick();
  assert.equal(panel.form.input.value, 'NEW456', 'Reopening reads the persisted value');
  assert.equal(getCalls, 2);
  context.handleRentalObjectCardKeydown({
    target: panel.form.input, currentTarget: card, key: ' ', preventDefault() {
      throw new Error('Typing in the input must not toggle the card');
    },
  }, object.id);
  assert.equal(panel.hidden, false);
  console.log('Rental card expansion tests passed');
})().catch(error => { console.error(error); process.exitCode = 1; });