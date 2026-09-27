const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const webmail = fs.readFileSync('modules/webmail_content.php', 'utf8');
const recipientApi = fs.readFileSync('api/get_recipients.php', 'utf8');
const queueApi = fs.readFileSync('api/queue_email.php', 'utf8');

assert.match(recipientApi, /mm\.status = 'active'/);
assert.match(recipientApi, /mm\.cancellation_active IS NULL OR mm\.cancellation_active = 0/);
assert.match(recipientApi, /mm\.expiration_date IS NULL OR mm\.expiration_date >= CURDATE\(\)/);
assert.match(recipientApi, /tc\.company_id = mm\.company_id/);

assert.match(webmail, /Velg alle aktive medlemmer/);
assert.match(webmail, /member_recipients: \[\.\.\.wmMemberRecipients\]/);
assert.match(webmail, /wmMemberRecipients\.add\(cb\.value\)/);
assert.match(queueApi, /\$data\['member_recipients'\]/);
assert.match(queueApi, /mm\.status = 'active'/);
assert.match(queueApi, /mm\.cancellation_active IS NULL OR mm\.cancellation_active = 0/);
assert.match(queueApi, /mm\.expiration_date IS NULL OR mm\.expiration_date >= CURDATE\(\)/);
assert.match(queueApi, /FOR UPDATE/);
assert.match(queueApi, /Minst ett valgt medlem er utløpt, kansellert/);

const selectStart = webmail.indexOf('function wmSelectAllRecipients(checked) {');
const selectEnd = webmail.indexOf('function updateSelectedCount()', selectStart);
assert(selectStart >= 0 && selectEnd > selectStart, 'Select-all handler exists');

let selectedCount = '';
const selectAll = {checked: false, indeterminate: false};
const checkboxes = [
  {checked: false, closest() { throw new Error('Select all should ignore search visibility'); }},
  {checked: false, closest() { throw new Error('Select all should ignore search visibility'); }},
];
const context = {
  document: {
    querySelectorAll: selector => selector === '.wm-rec-chk:checked'
      ? checkboxes.filter(item => item.checked)
      : selector === '.wm-rec-chk' ? checkboxes : [],
    getElementById: id => id === 'wm-select-all' ? selectAll : {set textContent(value) { selectedCount = value; }},
  },
};
vm.createContext(context);
const updateStart = selectEnd;
const updateEnd = webmail.indexOf("document.addEventListener('change'", updateStart);
assert(updateEnd > updateStart, 'Selected count updater exists');
vm.runInContext(webmail.slice(selectStart, updateEnd), context);
context.wmSelectAllRecipients(true);
assert.deepEqual(checkboxes.map(item => item.checked), [true, true]);
assert.equal(selectAll.checked, true);
assert.equal(selectAll.indeterminate, false);
assert.equal(selectedCount, '2 valgt');
context.wmSelectAllRecipients(false);
assert.deepEqual(checkboxes.map(item => item.checked), [false, false]);
assert.equal(selectAll.checked, false);
assert.equal(selectAll.indeterminate, false);
checkboxes[0].checked = true;
context.updateSelectedCount();
assert.equal(selectAll.checked, false);
assert.equal(selectAll.indeterminate, true, 'Partial selection is indicated');
assert.equal(selectedCount, '1 valgt');

const staleValidation = queueApi.indexOf('if ($noLongerEligible)');
const queueInsert = queueApi.indexOf('foreach ($validRecipients as $recipient)');
assert(staleValidation >= 0 && queueInsert > staleValidation,
  'Stale member recipients are rejected before any messages are queued');

console.log('Member email recipient tests passed');