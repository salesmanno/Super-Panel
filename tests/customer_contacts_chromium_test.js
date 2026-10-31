const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const childProcess = require('node:child_process');

const source = fs.readFileSync(path.join(__dirname, '../modules/membership_content.php'), 'utf8');
const modalStart = source.indexOf('<div class="modal fade" id="customerContactManagerModal"');
const modalEnd = source.indexOf('<div class="modal fade" id="createPlanModal"', modalStart);
assert(modalStart >= 0 && modalEnd > modalStart);
const scriptStart = source.indexOf("let activeContactManagerCustomerId = '';");
const scriptEnd = source.indexOf('</script>', scriptStart);
const code = source.slice(scriptStart, scriptEnd).replace(/<\/script/gi, '<\\/script');
const html = `<!doctype html><html><body>
${source.slice(modalStart, modalEnd)}
<script>
window.bootstrap = {Modal:{getOrCreateInstance:()=>({show(){}})}};
window.loadMembers = async ()=>{};
window.escapeH = value => {const div=document.createElement('div');div.textContent=value||'';return div.innerHTML;};
window.confirm = ()=>true;
let contacts = [{id:'contact-one',contact_type:'email',contact_value:'safe@example.test',is_primary:1,verified_at:null}];
let lastWrite;
window.fetch = async (url, options) => {
  if (!options) return {ok:true,json:async()=>({success:true,contacts})};
  lastWrite = JSON.parse(options.body);
  if(lastWrite.action==='edit') contacts[0].contact_value=lastWrite.contact_value;
  if(lastWrite.action==='add') contacts.push({id:'contact-two',...lastWrite,is_primary:0});
  if(lastWrite.action==='delete') contacts=contacts.filter(row=>row.id!==lastWrite.contact_id);
  return {ok:true,json:async()=>({success:true,message:'Lagret'})};
};
</script>
<script>${code}</script>
<script>
(async()=>{
  const check=(condition,message)=>{if(!condition)throw new Error(message);};
  const waitFor=async predicate=>{for(let n=0;n<100;n++){if(predicate())return;await new Promise(resolve=>setTimeout(resolve,5));}throw new Error('UI update timed out');};
  await openCustomerContactManager('fixture-customer','<img src=x onerror=alert(1)>');
  check(document.getElementById('customerContactManagerTitle').querySelector('img')===null,'customer name rendered as text');
  let row=document.querySelector('[data-contact-row]');
  row.querySelector('[data-contact-action="edit"]').click();
  let input=row.querySelector('[data-contact-input]');
  check(!input.classList.contains('d-none'),'edit opens one inline contact editor');
  input.value='edited@example.test';
  row.querySelector('[data-contact-action="save"]').click();
  await waitFor(()=>document.querySelector('[data-contact-display]')?.textContent==='edited@example.test');
  check(lastWrite.action==='edit'&&lastWrite.customer_id==='fixture-customer','save targets selected customer');
  row=document.querySelector('[data-contact-row]');
  check(row.querySelector('[data-contact-input]').classList.contains('d-none'),'saved editor closes');
  row.querySelector('[data-contact-action="edit"]').click();
  row.querySelector('[data-contact-input]').value='discarded@example.test';
  row.querySelector('[data-contact-action="cancel"]').click();
  check(row.querySelector('[data-contact-input]').value==='edited@example.test','cancel restores original contact');
  const type=document.getElementById('customer-contact-type');
  type.value='phone';type.dispatchEvent(new Event('change'));
  check(document.getElementById('customer-contact-value').type==='tel','phone gets touch phone keyboard');
  document.getElementById('customer-contact-value').value='12345678';
  document.getElementById('customer-contact-add-form').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
  await waitFor(()=>document.querySelectorAll('[data-contact-row]').length===2);
  check(lastWrite.action==='add'&&lastWrite.contact_type==='phone','add contact submits selected type');
  check(!customerContactAttribute('" onclick="bad()').includes('"'),'attribute values escaped');
  document.querySelector('[data-contact-action="delete"]').click();
  await waitFor(()=>document.querySelectorAll('[data-contact-row]').length===1);
  check(lastWrite.action==='delete','delete submitted through manager');
  document.body.dataset.testStatus='passed';
})().catch(error=>{document.body.dataset.testStatus='failed';document.body.dataset.testError=error.message;});
</script></body></html>`;

const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'customer-contacts-chromium-'));
try {
  const fixture = path.join(directory, 'contacts.html');
  fs.writeFileSync(fixture, html);
  const browser = process.env.CHROMIUM_PATH || '/repl/tools/bin/chromium';
  const output = childProcess.execFileSync(browser, [
    '--headless', '--no-sandbox', '--disable-dev-shm-usage', '--no-first-run',
    '--no-default-browser-check', '--virtual-time-budget=8000', '--dump-dom', `file://${fixture}`,
  ], {encoding:'utf8',timeout:30000,maxBuffer:4*1024*1024,stdio:['ignore','pipe','pipe']});
  assert.match(output, /data-test-status="passed"/, output.match(/data-test-error="([^"]+)"/)?.[1] || 'Contact manager DOM checks failed');
  console.log('Customer contact Chromium DOM tests passed');
} finally {
  fs.rmSync(directory, {recursive:true,force:true});
}
