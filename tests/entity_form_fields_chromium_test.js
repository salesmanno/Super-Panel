// Browser-only contract test for the shared module form-field editor and
// Events' walk-in field renderer. All network calls are stubbed in this
// disposable file:// fixture; no application API or database is contacted.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn, spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined') {
    const child = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio: 'inherit'});
    process.exit(child.status ?? 1);
}

const root = path.join(__dirname, '..');
const editorSource = fs.readFileSync(path.join(root, 'assets/entity-form-fields.js'), 'utf8');
const eventSource = fs.readFileSync(path.join(root, 'modules/events_content.php'), 'utf8');
const rentalSource = fs.readFileSync(path.join(root, 'modules/rental_content.php'), 'utf8');
assert.match(eventSource, /form_fields:\s*createFields/);
assert.match(eventSource, /csrf_token:\s*csrfToken/);
assert.match(eventSource, /optional_fields:\s*collectDynamicRegistrationValues\(\)/);
const eventBasicStart = eventSource.indexOf('async function saveEventChanges');
const eventBasicEnd = eventSource.indexOf('function openAddIncomeModal', eventBasicStart);
assert.doesNotMatch(eventSource.slice(eventBasicStart, eventBasicEnd), /field_(email|phone|street|zip|city|postal|age)\s*:/);
assert.doesNotMatch(rentalSource, /customer_fields\s*:/);
assert.match(rentalSource, /if \(!isEdit\) data\.form_fields = rentalCreateFieldsEditor\?\.getFields\(\)/);
assert.match(rentalSource, /data\.csrf_token = csrfToken/);

function extractFunction(source, name, nextMarker) {
    const start = source.indexOf(`async function ${name}(`);
    const plainStart = source.indexOf(`function ${name}(`);
    const index = start >= 0 ? start : plainStart;
    assert.ok(index >= 0, `Missing ${name}`);
    const end = source.indexOf(nextMarker, index);
    assert.ok(end > index, `Missing end marker for ${name}`);
    return source.slice(index, end);
}
const eventLoader = extractFunction(eventSource, 'loadEventRegistrationFields', '// Attendee scanner functionality');
const eventCollector = extractFunction(eventSource, 'collectDynamicRegistrationValues', '\n// Attendee scanner functionality');

const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'entity-fields-'));
const html = `<!doctype html><html lang="nb"><head><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="file://${path.join(root, 'assets/entity-form-fields.css')}"></head><body>
<div id="event-create"></div><div id="event-edit"></div><div id="rental-create"></div><div id="rental-edit"></div>
<div id="rental-empty"></div><div id="rental-repair"></div><div id="event-none"></div><div id="dynamic-registration-fields"></div>
<form id="quick-guest-form"><label for="guest-fornavn">Fornavn *</label><input id="guest-fornavn"><label for="guest-etternavn">Etternavn *</label><input id="guest-etternavn"><label for="guest-email">E-post</label><input id="guest-email"></form>
<script>
window.testErrors=[]; window.confirmAnswer=false; window.confirmCount=0;
window.confirm=()=>{window.confirmCount++;return window.confirmAnswer};
window.addEventListener('error',e=>testErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>testErrors.push(String(e.reason)));
window.fixtureSaveResults=[]; window.fixtureFieldCalls=[];
window.fetch=async(url,options={})=>{
 const parsed=new URL(String(url),'http://fixture.local');
 if(parsed.pathname.endsWith('/api/get_event_fields.php')) return {ok:true,json:async()=>({success:true,fields:[
   {field_name:'hidden_one',field_label:'Skjult',field_type:'text',is_visible:0},
   {field_name:'first_name',field_label:'Fornavn',field_type:'text',is_visible:1,is_required:1},
   {field_name:'email',field_label:'Arbeids-e-post',field_type:'email',is_visible:1,is_required:1,default_value:'person@example.no'},
   {field_name:'phone',field_label:'Telefon',field_type:'tel',is_visible:1,default_value:'123',validation_rules:{pattern:'[0-9]+'}},
   {field_name:'choice',field_label:'<img src=x onerror=alert(1)>',field_type:'select',is_visible:1,is_required:1,default_value:'B',options:['A','B']},
   {field_name:'accept',field_label:'Godta',field_type:'checkbox',is_visible:1,default_value:'1'},
   {field_name:'when',field_label:'Dato',field_type:'date',is_visible:1,validation_rules:{min:'2028-01-01',max:'2029-01-01'}},
   {field_name:'note',field_label:'Merknad',field_type:'textarea',is_visible:1,validation_rules:{min_length:2,max_length:10,pattern:'[A-Z]+'}}
 ]})};
 if(parsed.pathname.endsWith('/api/get_entity_form_fields.php')){
   const type=parsed.searchParams.get('entity_type'), id=parsed.searchParams.get('entity_id');
   window.fixtureFieldCalls.push({type,id});
   let fields=[],registration_type='signup';
   if(type==='event' && !id) fields=[
     {field_name:'first_name',field_label:'Fornavn',field_type:'text',is_required:1,is_visible:1,sort_order:0},
     {field_name:'last_name',field_label:'Etternavn',field_type:'text',is_required:1,is_visible:1,sort_order:1},
     {field_name:'email',field_label:'E-post',field_type:'email',is_required:1,is_visible:1,sort_order:2}
   ];
   if(type==='event' && id==='event-a') fields=[
     {field_name:'first_name',field_label:'Fornavn',field_type:'text',is_required:1,is_visible:1,sort_order:0},
     {field_name:'last_name',field_label:'Etternavn',field_type:'text',is_required:1,is_visible:1,sort_order:1},
     {field_name:'phone',field_label:'Telefon',field_type:'tel',is_visible:1,sort_order:2}
   ];
   if(type==='event' && id==='event-none') registration_type='none';
   if(type==='rental_object' && id==='rental-a') fields=[{field_name:'city',field_label:'By',field_type:'text',is_visible:1}];
   if(type==='rental_object' && id==='rental-repair') fields=[
     {field_name:'email',field_label:'E-post',field_type:'email',is_visible:1,sort_order:0,validation_rules:[],_invalid_validation_rules_hash:'c'.repeat(64)},
     {field_name:'city',field_label:'By',field_type:'text',is_visible:1,sort_order:1}
   ];
   return {ok:true,json:async()=>({success:true,fields,revision:'a'.repeat(64),registration_type,csrf_token:'csrf-fixture',presets:[{key:'brief',label:'Kort',fields:[
     {field_name:'first_name',field_label:'Fornavn',field_type:'text',is_required:1,is_visible:1,sort_order:0},
     {field_name:'last_name',field_label:'Etternavn',field_type:'text',is_required:1,is_visible:1,sort_order:1},
     {field_name:'email',field_label:'E-post',field_type:'email',is_required:1,is_visible:1,sort_order:2}
   ]}]})};
 }
 if(parsed.pathname.endsWith('/api/save_entity_form_fields.php')){
   const body=JSON.parse(options.body); window.fixtureSaveResults.push(body);
   if(body.entity_id==='event-a' && window.fixtureSaveResults.filter(x=>x.entity_id==='event-a').length===1)
     return {ok:false,json:async()=>({success:false,message:'Prøvefeil'})};
   return {ok:true,json:async()=>({success:true,fields:body.fields})};
 }
 return {ok:true,json:async()=>({success:true})};
};
</script>
<script>${editorSource.replace(/<\/script/gi,'<\\/script')}</script>
<script>window.selectedEventId='walkin-1'; ${eventLoader} ${eventCollector}</script>
</body></html>`;
const file = path.join(directory, 'fixture.html');
fs.writeFileSync(file, html);

const checks = `(async()=>{
 const check=(condition,message)=>{if(!condition)throw new Error(message)};
 const pause=()=>new Promise(resolve=>setTimeout(resolve,80));
 const mount=window.EntityFormFields.mount;
 const eventCreate=mount(document.getElementById('event-create'),{entityType:'event',createMode:true,registrationType:'signup'});
 const initialLoad=eventCreate.refresh();
 check(!eventCreate.isReady(),'creation cannot save while presets/CSRF are loading');
 await initialLoad;
 check(eventCreate.isReady(),'event create waits until defaults/token arrive');
 check(eventCreate.getFields().length===3,'event defaults loaded');
 const preset=document.querySelector('#event-create [data-preset]');
 check(!document.querySelector('#event-create .entity-field-row'),'only overview is shown initially');
 document.querySelector('#event-create [data-edit-field="first_name"]').click();
 check(document.querySelectorAll('#event-create .entity-field-row').length===1,'locked name still opens one field');
 document.querySelector('#event-create [data-fields-list] input[data-prop="field_label"]').value='Tilpasset navn';
 document.querySelector('#event-create [data-fields-list] input[data-prop="field_label"]').dispatchEvent(new Event('input',{bubbles:true}));
 preset.value='brief'; window.confirmAnswer=false;
 document.querySelector('#event-create [data-apply-preset]').click();
 check(eventCreate.getFields()[0].field_label==='Tilpasset navn'&&confirmCount===1,'preset asks before replacing a draft');
 window.confirmAnswer=true; document.querySelector('#event-create [data-apply-preset]').click();
 check(eventCreate.getFields()[0].field_label==='Fornavn','confirmed preset replaces draft');
 await eventCreate.setRegistrationType('none');
 check(eventCreate.getFields().length===0,'no-registration creation forces an empty field array');
 await eventCreate.setRegistrationType('signup');
 check(eventCreate.getFields().length===3,'switching back restores defaults');
 document.querySelector('#event-create [data-add]').click();
 check(eventCreate.getFields().length===4,'custom field can be added');
 await eventCreate.save();
 check(!document.querySelector('#event-create .entity-field-row')&&fixtureSaveResults.length===0,'create-mode field save closes locally without server persistence');
 eventCreate.reset();
 check(!eventCreate.isReady(),'reset invalidates prior token/default state');
 await eventCreate.setRegistrationType('signup');
 check(eventCreate.isReady()&&eventCreate.getFields().length===3,'reset reloads clean defaults');

 const eventEdit=mount(document.getElementById('event-edit'),{entityType:'event',entityId:'event-a'});
 await eventEdit.refresh();
 document.querySelector('#event-edit [data-edit-field="phone"]').click();
 const eventLabel=document.querySelector('#event-edit [data-fields-list] input[data-prop="field_label"]');
 eventLabel.value='Ny telefon';eventLabel.dispatchEvent(new Event('input',{bubbles:true}));
 document.querySelector('#event-edit [data-save]').click();
 check([...document.querySelectorAll('#event-edit input,#event-edit select,#event-edit textarea,#event-edit button')].every(control=>control.disabled),'all field controls are disabled while saving');
 document.querySelector('#event-edit [data-save]').click();
 await pause();
 check(window.fixtureSaveResults.filter(x=>x.entity_id==='event-a').length===1,'concurrent save clicks are suppressed');
 check(eventEdit.getFields()[2].field_label==='Ny telefon','failed save retains the draft');
 check(document.querySelectorAll('#event-edit .entity-field-row').length===1,'failed save leaves the same row open');
 check(document.querySelector('#event-edit [data-fields-status]').textContent==='Prøvefeil','save failure reported in editor: '+document.querySelector('#event-edit [data-fields-status]').textContent+' payloads='+JSON.stringify(window.fixtureSaveResults));
 document.querySelector('#event-edit [data-save]').click();await pause();
 check(window.fixtureSaveResults.filter(x=>x.entity_id==='event-a').length===2,'save can be retried');
 check(window.fixtureSaveResults.at(-1).entity_type==='event'&&window.fixtureSaveResults.at(-1).csrf_token==='csrf-fixture','save is scoped to this event and carries csrf');
 check(!document.querySelector('#event-edit .entity-field-row'),'successful event save closes the selected row');
 check(window.fixtureSaveResults.at(-1).fields.length===3,'saving a single visible row retains all sibling definitions');
 check(window.fixtureSaveResults.at(-1).expected_revision==='a'.repeat(64),'full snapshots carry a server precondition');

 const rentalEdit=mount(document.getElementById('rental-edit'),{entityType:'rental_object',entityId:'rental-a'});
 await rentalEdit.refresh();
 check(rentalEdit.getFields()[0].field_name==='city','rental fields isolated from event draft');
 document.querySelector('#rental-edit [data-save]').click();await pause();
 check(window.fixtureSaveResults.at(-1).entity_type==='rental_object'&&window.fixtureSaveResults.at(-1).entity_id==='rental-a','rental save targets its object');
 const emptyRental=mount(document.getElementById('rental-empty'),{entityType:'rental_object',entityId:'rental-empty'});
 await emptyRental.refresh();
 check(emptyRental.getFields().length===0&&document.querySelector('#rental-empty [data-fields-list]').textContent.includes('Ingen ekstra felt'),'empty rental setup renders intentionally');
 const emailQuick=document.querySelector('#rental-empty [data-common-field="email"]');
 check(emailQuick&&!emailQuick.checked,'Rental offers an unchecked standard contact field without manual configuration');
 emailQuick.click();
 check(emptyRental.getFields()[0].field_name==='email'&&emptyRental.getFields()[0].field_type==='email','Quick selection uses the canonical shared email field');
 check(!document.querySelector('#rental-empty .entity-field-row'),'visibility toggle does not open the editor');
 document.querySelector('#rental-empty [data-edit-field="email"]').click();
 check(document.querySelector('#rental-empty details')&&!document.querySelector('#rental-empty details').open,'Advanced rental settings start collapsed');
 document.querySelector('#rental-empty [data-save]').click();await pause();
 check(window.fixtureSaveResults.at(-1).fields[0].field_name==='email','Quick selection saves through the same entity-scoped field endpoint');
 check(!document.querySelector('#rental-empty .entity-field-row'),'successful rental save closes the selected row');
 const repairEditor=mount(document.getElementById('rental-repair'),{entityType:'rental_object',entityId:'rental-repair'});
 await repairEditor.refresh();
 check(!document.querySelector('#rental-repair [data-fields-notice]').hidden,'broken email has explicit repair notice');
 check(!repairEditor.getFields()[0]._repair_invalid_validation_rules,'opening does not confirm repair');
 document.querySelector('#rental-repair [data-preset]').value='brief';
 document.querySelector('#rental-repair [data-apply-preset]').click();
 check(repairEditor.getFields().find(f=>f.field_name==='email')._invalid_validation_rules_hash==='c'.repeat(64),'presets retain exact repair fingerprint');
 const orderBefore=repairEditor.getFields().map(f=>f.field_name);
 const move=document.querySelector('#rental-repair [data-field-name="email"][data-common-move="-1"]');
 check(move&&!move.disabled,'field ordering available without opening details');
 move.click();
 const reordered=repairEditor.getFields();
 check(reordered.map(f=>f.field_name).join()!==orderBefore.join()&&reordered.every((f,i)=>f.sort_order===i),'visible order controls update stored sort_order');
 check([...document.querySelectorAll('#rental-repair [data-edit-field]')].map(b=>b.dataset.editField).slice(0,reordered.length).join()===reordered.map(f=>f.field_name).join(),'checkbox overview follows draft field order');
 document.querySelector('#rental-repair [data-edit-field="email"]').click();
 const repairChoice=document.querySelector('#rental-repair [data-repair-email]');
 check(repairChoice&&!repairChoice.checked,'repair is explicit and off by default');
 repairChoice.click();
 check(repairEditor.getFields().find(f=>f.field_name==='email')._repair_invalid_validation_rules,'checkbox authorizes only the marked email row');
 document.querySelector('#rental-repair [data-save]').click();await pause();
 const repairPayload=fixtureSaveResults.at(-1);
 check(repairPayload.entity_id==='rental-repair'&&repairPayload.fields.find(f=>f.field_name==='email')._invalid_validation_rules_hash==='c'.repeat(64),'repair save stays bound to object and rule fingerprint');
 check(repairPayload.fields.every((f,i)=>f.sort_order===i),'save sends selected order');
 check(repairPayload.fields.every(f=>f.default_value===null),'unchanged absent default values stay null');
 document.querySelector('#rental-empty [data-common-field="email"]').click();
 check(!emptyRental.getFields()[0].is_visible&&!document.querySelector('#rental-empty [data-fields-list] .entity-field-row'),'Unchecking a standard rental field hides it while retaining its definition');
 const noneEvent=mount(document.getElementById('event-none'),{entityType:'event',entityId:'event-none'});
 await noneEvent.refresh();
 check(noneEvent.getFields().length===0&&!document.querySelector('#event-none [data-add]').disabled===false,'no-registration event starts empty');
 check(document.querySelector('#event-none [data-add]').disabled,'no-registration event cannot add fields');

 await loadEventRegistrationFields('walkin-1');
 const rendered=document.querySelectorAll('#dynamic-registration-fields [data-dynamic-field-name]');
 check(rendered.length===5,'hidden fields and duplicate built-ins omitted');
 check(document.getElementById('guest-email').required&&document.getElementById('guest-email').value==='person@example.no','duplicate email definition synchronizes the attendee control');
 check(!document.getElementById('guest-dynamic-hidden_one-0'),'hidden definition has no control');
 const select=document.querySelector('#dynamic-registration-fields select');
 check(select.value==='B'&&select.options.length===3,'select options/default rendered safely');
 check(!document.querySelector('#dynamic-registration-fields img'),'untrusted labels rendered as text');
 const date=document.querySelector('#dynamic-registration-fields input[type=date]');
 check(date.min==='2028-01-01'&&date.max==='2029-01-01','date validation bounds applied');
 const textArea=document.querySelector('#dynamic-registration-fields textarea');
 check(textArea.minLength===2&&textArea.maxLength===10,'textarea length rules applied');
 check(document.querySelector('#dynamic-registration-fields input[type=tel]').pattern==='[0-9]+','safe tel pattern applied');
 const checkbox=document.querySelector('#dynamic-registration-fields input[type=checkbox]');
 check(checkbox.checked,'checkbox default applied');
 checkbox.checked=false;
 const values=collectDynamicRegistrationValues();
 check(values.accept===0&&values.phone==='123'&&values.choice==='B'&&!Object.hasOwn(values,'email'),'optional dictionary preserves values without duplicating attendee email');
 check(window.testErrors.length===0,'no browser errors: '+window.testErrors.join('; '));
 return {passed:true,width:visualViewport.width};
})()`;

let browser, socket;
async function main(){
 browser=spawn(process.env.CHROMIUM_PATH||'/repl/tools/bin/chromium',['--headless','--no-sandbox','--disable-dev-shm-usage','--remote-debugging-port=0','--user-data-dir='+path.join(directory,'profile'),'about:blank']);
 const debuggerUrl=await new Promise((resolve,reject)=>{
   let output='';const timeout=setTimeout(()=>reject(new Error('Chromium debugger timed out')),15000);
   browser.stderr.on('data',data=>{output+=data;const match=output.match(/DevTools listening on (ws:\/\/[^\s]+)/);if(match){clearTimeout(timeout);resolve(match[1])}});
   browser.on('error',reject);
 });
 socket=new WebSocket(debuggerUrl);await new Promise(resolve=>socket.addEventListener('open',resolve,{once:true}));
 let id=0;const pending=new Map();
 socket.addEventListener('message',event=>{const message=JSON.parse(event.data);const cb=pending.get(message.id);if(cb){pending.delete(message.id);message.error?cb.reject(message.error):cb.resolve(message.result)}});
 const send=(method,params={},sessionId)=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});socket.send(JSON.stringify({id,method,params,sessionId}))});
 const {targetId}=await send('Target.createTarget',{url:'about:blank'});
 const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
 for(const width of [390,1280]){
   await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width===390},sessionId);
   await send('Page.navigate',{url:'file://'+file+'?w='+width},sessionId);await new Promise(resolve=>setTimeout(resolve,250));
   const result=await send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
   assert.equal(result.result?.value?.passed,true,JSON.stringify(result));
   assert.ok(result.result.value.width<=width&&result.result.value.width>=width-20,'Viewport is close to configured width: '+result.result.value.width);
   console.log('Entity form editor and Events dynamic fields passed at '+width+'px');
 }
}
main().catch(error=>{console.error(error);process.exitCode=1}).finally(async()=>{
 if(socket)socket.close();
 if(browser){const stopped=new Promise(resolve=>browser.once('exit',resolve));browser.kill('SIGKILL');await stopped}
 await new Promise(resolve=>setTimeout(resolve,300));
 try{fs.rmSync(directory,{recursive:true,force:true,maxRetries:10,retryDelay:250})}
 catch(error){if(error.code!=='ENOTEMPTY'&&error.code!=='EBUSY')throw error}
});
