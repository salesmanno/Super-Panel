const assert = require('node:assert/strict');
const fs = require('node:fs');
const EventFormHelpers = require('../assets/event_form_helpers.js');
const eventsContent = fs.readFileSync(require.resolve('../modules/events_content.php'), 'utf8');

assert.match(eventsContent, /<script src="\/assets\/event_form_helpers\.js"><\/script>/);
assert.match(eventsContent, /EventFormHelpers\.attachDateTimeControls\(document\)/);
assert.match(eventsContent, /EventFormHelpers\.handleCreateResult\(result,/);
assert.match(eventsContent, /onSuccess:[\s\S]*?showWizardSuccess\(\)/);
assert.match(eventsContent, /EventFormHelpers\.resetWizard\(document, form, state,/);
assert.match(eventsContent, /EventFormHelpers\.updateSummaryDate\(form, document\.getElementById\('summary-date'\), formatNorskDato\)/);

class FakeEvent {
  constructor(type) {
    this.type = type;
  }
}

class FakeClassList {
  constructor() {
    this.values = new Set();
  }

  add(value) {
    this.values.add(value);
  }

  remove(...values) {
    values.forEach(value => this.values.delete(value));
  }

  contains(value) {
    return this.values.has(value);
  }
}

class FakeElement {
  constructor(ownerDocument, name = '') {
    this.ownerDocument = ownerDocument;
    this.name = name;
    this.type = '';
    this.value = '';
    this.defaultValue = '';
    this.required = false;
    this.disabled = false;
    this.dataset = {};
    this.style = {};
    this.attributes = {};
    this.listeners = {};
    this.children = [];
    this.classList = new FakeClassList();
    this.parentNode = null;
    this.textContent = '';
    this.innerHTML = '';
    this.tabIndex = 0;
    this.customValidity = '';
  }

  setAttribute(name, value) {
    this.attributes[name] = String(value);
  }

  getAttribute(name) {
    return this.attributes[name] || null;
  }

  appendChild(child) {
    if (child.parentNode) child.parentNode.removeChild(child);
    child.parentNode = this;
    this.children.push(child);
    return child;
  }

  insertBefore(child, nextSibling) {
    if (child.parentNode) child.parentNode.removeChild(child);
    const index = this.children.indexOf(nextSibling);
    child.parentNode = this;
    this.children.splice(index === -1 ? this.children.length : index, 0, child);
    return child;
  }

  removeChild(child) {
    const index = this.children.indexOf(child);
    if (index !== -1) this.children.splice(index, 1);
    child.parentNode = null;
  }

  addEventListener(type, callback) {
    this.listeners[type] ||= [];
    this.listeners[type].push(callback);
  }

  dispatchEvent(event) {
    (this.listeners[event.type] || []).forEach(callback => callback(event));
    return true;
  }

  setCustomValidity(message) {
    this.customValidity = message;
  }

  setSelectionRange() {}

  focus() {}

  click() {
    this.clicked = true;
  }

  querySelector(selector) {
    const className = selector.startsWith('.') ? selector.slice(1) : '';
    return this.children.find(child => child.className?.split(' ').includes(className)) || null;
  }
}

class FakeDocument {
  constructor() {
    this.defaultView = {Event: FakeEvent};
    this.elementsById = new Map();
  }

  createElement() {
    return new FakeElement(this);
  }
}

class FakeForm extends FakeElement {
  constructor(document, controls = []) {
    super(document, 'form');
    this.controls = controls;
    controls.forEach(control => this.appendChild(control));
    this.style = {};
  }

  reset() {
    this.controls.forEach(control => {
      control.value = control.defaultValue;
      control.checked = Boolean(control.defaultChecked);
      control.dataset.eventTimePartial = 'false';
      control.customValidity = '';
    });
  }

  querySelectorAll(selector) {
    if (selector === 'input[data-event-time-enhanced="true"]') {
      return this.controls.filter(control => control.dataset.eventTimeEnhanced === 'true');
    }
    if (selector === 'input, textarea, select') return this.controls;
    return this.controls.filter(control => selector.includes(`name="${control.name}"`));
  }

  querySelector(selector) {
    const match = selector.match(/name="([^"]+)"/);
    return match ? this.controls.find(control => control.name === match[1]) || null : null;
  }
}

function createInput(document, name, type = 'text', value = '') {
  const input = new FakeElement(document, name);
  input.type = type;
  input.value = value;
  input.defaultValue = '';
  return input;
}

function createWizardDocument(form) {
  const document = form.ownerDocument;
  const wizardContents = Array.from({length: 7}, () => new FakeElement(document));
  const wizardSteps = Array.from({length: 7}, () => new FakeElement(document));
  const selectors = new Map([
    ['.wizard-content', wizardContents],
    ['.wizard-step', wizardSteps],
    ['.wizard-progress', [new FakeElement(document)]],
    ['.wizard-navigation', [new FakeElement(document)]]
  ]);
  for (const [selector, elements] of selectors) {
    elements.forEach(element => element.classList.add('old-state'));
    document[selector] = elements;
  }

  [
    'wizard-prev',
    'wizard-next',
    'wizard-submit',
    'wizard-success',
    'binding-payment-container'
  ].forEach(id => document.elementsById.set(id, new FakeElement(document)));
  document.elementsById.set('summary-date', new FakeElement(document));

  document.querySelectorAll = selector => selectors.get(selector) || [];
  document.querySelector = selector => (selectors.get(selector) || [])[0] || null;
  document.getElementById = id => document.elementsById.get(id) || null;
  return {document, wizardContents, wizardSteps};
}

async function testDateAndTimeControls() {
  const document = new FakeDocument();
  const deadline = createInput(document, 'registration_deadline');
  const startDate = createInput(document, 'start_date');
  const endDate = createInput(document, 'end_date');
  const startTime = createInput(document, 'start_time', 'time');
  startTime.required = true;
  const endTime = createInput(document, 'end_time', 'time');
  const form = new FakeForm(document, [deadline, startDate, endDate, startTime, endTime]);

  EventFormHelpers.attachDateTimeControls(form);

  startDate.value = '01022028';
  startDate.dispatchEvent(new FakeEvent('input'));
  assert.equal(startDate.value, '01.02.2028', 'Typing eight digits inserts Norwegian date separators');
  assert.equal(EventFormHelpers.toSqlDate(startDate.value), '2028-02-01');
  assert.equal(EventFormHelpers.toNorskDate('2028-02-29'), '29.02.2028', 'Leap-day picker values are formatted without timezone conversion');
  assert.equal(EventFormHelpers.toNorskDate('2027-02-29'), '', 'Invalid native-picker dates are rejected');
  assert.equal(EventFormHelpers.formatDateDigits('31042028'), '31.04.2028', 'Invalid manual dates remain visible for validation');

  const dateWrapper = startDate.parentNode;
  const dateButton = dateWrapper.children.find(child => child.type === 'button');
  const datePicker = dateWrapper.children.find(child => child.type === 'date');
  datePicker.showPicker = () => { datePicker.opened = true; };
  dateButton.dispatchEvent(new FakeEvent('click'));
  assert.equal(datePicker.opened, true, 'Calendar button opens the native picker from a user gesture');
  datePicker.value = '2028-03-09';
  datePicker.dispatchEvent(new FakeEvent('change'));
  assert.equal(startDate.value, '09.03.2028', 'Picker selection writes a Norwegian date into the form field');

  assert.equal(startTime.type, 'hidden', 'Form keeps a canonical hidden time value');
  assert.equal(startTime.dataset.eventTimeRequired, 'true', 'Required start-time rules survive the hidden-field conversion');
  const timeWrapper = startTime.parentNode;
  const hours = timeWrapper.querySelector('.event-time-hours');
  const minutes = timeWrapper.querySelector('.event-time-minutes');
  assert.equal(hours.children.length, 25, 'Time picker offers a blank choice and every hour from 00 to 23');
  assert.equal(minutes.children.length, 61, 'Minute picker offers a blank choice and every minute from 00 to 59');
  assert.equal(hours.children.at(-1).value, '23');
  assert.equal(minutes.children.at(-1).value, '59');
  assert.equal(hours.required, true, 'Required start time requires an hour');
  assert.equal(minutes.required, true, 'Required start time requires minutes');

  hours.value = '23';
  hours.dispatchEvent(new FakeEvent('change'));
  assert.equal(startTime.value, '');
  assert.equal(startTime.dataset.eventTimePartial, 'true', 'Partial time selection is detectable by validation');
  minutes.value = '47';
  minutes.dispatchEvent(new FakeEvent('change'));
  assert.equal(startTime.value, '23:47', 'Selecting hour and minute stores HH:mm');
  assert.equal(startTime.customValidity, '', 'A complete time clears partial-selection validation');

  startTime.value = '08:05';
  EventFormHelpers.syncDateTimeControls(form);
  assert.equal(hours.value, '08', 'Existing edit-form times are reflected in the 24-hour selectors');
  assert.equal(minutes.value, '05');
}

async function testWizardResetAndCreateResults() {
  const document = new FakeDocument();
  const title = createInput(document, 'title');
  const date = createInput(document, 'start_date');
  const time = createInput(document, 'start_time');
  time.dataset.eventTimeEnhanced = 'true';
  const timeHours = new FakeElement(document);
  timeHours.className = 'form-select event-time-hours';
  const timeMinutes = new FakeElement(document);
  timeMinutes.className = 'form-select event-time-minutes';
  const timeWrapper = new FakeElement(document);
  timeWrapper.appendChild(timeHours);
  timeWrapper.appendChild(timeMinutes);
  timeWrapper.appendChild(time);
  const form = new FakeForm(document, [title, date, time]);
  form.style.display = 'none';
  time.parentNode = timeWrapper;
  const wizard = createWizardDocument(form);
  const state = {currentStep: 7};
  let resetCount = 0;
  let failureMessage = '';

  title.value = 'Første arrangement';
  date.value = '02.03.2028';
  time.value = '19:30';
  time.defaultValue = '19:30';
  const summaryDate = wizard.document.getElementById('summary-date');
  summaryDate.textContent = 'Første arrangementdato';
  EventFormHelpers.updateSummaryDate(form, summaryDate, (startDate, startTime) => `${startDate} ${startTime}`);
  const failed = EventFormHelpers.handleCreateResult(
    {success: false, message: 'Feil i forespørselen'},
    {onFailure: message => { failureMessage = message; }}
  );
  assert.equal(failed, false);
  assert.equal(failureMessage, 'Feil i forespørselen');
  assert.equal(title.value, 'Første arrangement', 'Failed save retains the title for correction');
  assert.equal(date.value, '02.03.2028', 'Failed save retains date and time inputs');
  assert.equal(time.value, '19:30');
  assert.equal(summaryDate.textContent, '02.03.2028 19:30', 'Failed save retains the displayed summary');
  assert.equal(state.currentStep, 7, 'Failed save leaves the wizard on its current step');
  assert.equal(resetCount, 0, 'Failed save never resets the wizard');

  function createAgain() {
    return EventFormHelpers.handleCreateResult(
      {success: true},
      {
        onSuccess: () => {
          const didReset = EventFormHelpers.resetWizard(wizard.document, form, state, () => {
            resetCount++;
            EventFormHelpers.updateSummaryDate(form, summaryDate, (startDate, startTime) => `${startDate} ${startTime}`);
          });
          assert.equal(didReset, true);
        }
      }
    );
  }

  assert.equal(createAgain(), true);
  assert.equal(resetCount, 1);
  assert.equal(title.value, '');
  assert.equal(date.value, '');
  assert.equal(time.value, '');
  assert.equal(time.defaultValue, '', 'Reset clears the hidden time default that native form.reset may restore');
  assert.equal(summaryDate.textContent, '', 'Reset removes the previous event date summary');
  assert.equal(state.currentStep, 1);
  assert.equal(form.style.display, 'block');
  assert.deepEqual(wizard.wizardContents.map(element => element.style.display), [
    'block', 'none', 'none', 'none', 'none', 'none', 'none'
  ]);
  assert.deepEqual(wizard.wizardSteps.map(element => [
    element.classList.contains('active'),
    element.classList.contains('completed')
  ]), [
    [true, false], [false, false], [false, false], [false, false],
    [false, false], [false, false], [false, false]
  ]);
  assert.equal(wizard.document.getElementById('wizard-next').style.display, 'block');
  assert.equal(wizard.document.getElementById('wizard-prev').style.display, 'none');
  assert.equal(wizard.document.getElementById('wizard-submit').style.display, 'none');
  assert.equal(wizard.document.getElementById('wizard-success').style.display, 'none');
  assert.equal(wizard.document.getElementById('binding-payment-container').style.display, 'none');
  assert.equal(wizard.document['.wizard-progress'][0].style.display, 'block');
  assert.equal(wizard.document['.wizard-navigation'][0].style.display, 'flex');
  assert.equal(timeHours.value, '');
  assert.equal(timeMinutes.value, '');

  title.value = 'Neste arrangement';
  date.value = '15.06.2028';
  time.value = '09:15';
  time.defaultValue = '09:15';
  assert.equal(createAgain(), true);
  assert.equal(resetCount, 2, 'A second successful creation gets the same complete reset');
  assert.equal(title.value, '');
  assert.equal(date.value, '');
  assert.equal(time.value, '');
  assert.equal(time.defaultValue, '');
  assert.equal(summaryDate.textContent, '');
}

testDateAndTimeControls()
  .then(testWizardResetAndCreateResults)
  .then(() => console.log('event form helper behavior tests passed'))
  .catch(error => {
    console.error(error);
    process.exitCode = 1;
  });