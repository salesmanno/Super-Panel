const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('modules/webmail_content.php', 'utf8');
const start = source.indexOf('async function wmSend() {');
const end = source.indexOf('/* ===== TEMPLATE PICKER', start);
assert(start > 0 && end > start);
const elements = {
  'wm-to-input': {value: ''},
  'wm-subject': {value: 'Brev'},
  'wm-compose-alert': {style: {}, innerHTML: ''},
  'wm-template-editor': {style: {display: 'block'}, innerHTML: '<h1>{{subject}}</h1>'},
};
let calls = [], settle, sequence = 0;
const button = {disabled: false};
const context = {
  wmSendInFlight: false, wmSendReplay: {fingerprint: null, key: null},
  wmToList: ['alex@example.invalid'], wmMemberRecipients: new Set(),
  wmActiveTemplateId: 'template-1', wmTemplateEditorDirty: false,
  wmActiveRenderedTemplateBody: '<h1>{{subject}}</h1>',
  crypto: {randomUUID: () => `00000000-0000-4000-8000-${String(++sequence).padStart(12, '0')}`},
  document: {getElementById: id => elements[id], querySelectorAll: () => [button]},
  escapeH: value => value, renderTags() {}, wmClearTemplate() {}, wmAddTag() {},
  fetch: (_, options) => {
    calls.push(JSON.parse(options.body));
    return new Promise((resolve, reject) => { settle = {resolve, reject}; });
  },
};
vm.createContext(context);
vm.runInContext(source.slice(start, end), context);
(async () => {
  const first = context.wmSend();
  const duplicate = context.wmSend();
  assert.equal(calls.length, 1, 'double tap makes only one request');
  assert.equal(button.disabled, true);
  await duplicate;
  const firstKey = calls[0].idempotency_key;
  settle.reject(new Error('Response lost after server commit'));
  await first;
  assert.equal(button.disabled, false, 'failure releases UI');
  const retry = context.wmSend();
  assert.equal(calls[1].idempotency_key, firstKey, 'retry keeps same idempotency key');
  settle.reject(new Error('Network still unavailable'));
  await retry;
  elements['wm-subject'].value = 'Nytt brev';
  const changed = context.wmSend();
  assert.notEqual(calls[2].idempotency_key, firstKey, 'edited content gets a new intent');
  settle.resolve({text: async () => JSON.stringify({success: true, message: 'Lagt i kø'})});
  await changed;
  assert.equal(context.wmToList.length, 0, 'successful send clears recipients');
  assert.equal(context.wmSendReplay.key, null);
  assert.equal(context.wmSendInFlight, false);
  console.log('Webmail send/retry idempotency checks passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
