const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const {spawn,spawnSync}=require('node:child_process'),assert=require('node:assert/strict');
if(typeof WebSocket==='undefined') {
    process.exit(spawnSync(process.execPath,['--experimental-websocket',__filename],{stdio:'inherit'}).status??1);
}
const root=path.join(__dirname,'..'),dir=fs.mkdtempSync(path.join(os.tmpdir(),'rental-workflow-browser-'));
const rendered=spawnSync('php',['-r','include "modules/rental_content.php";'],{cwd:root,encoding:'utf8'});
assert.equal(rendered.status,0,rendered.stderr);
let html=rendered.stdout.replace(/<link[^>]+>/g,'');
html=html.replace(/<script src="\/assets\/([^"]+)"><\/script>/g,(_,name)=>
    '<script>'+fs.readFileSync(path.join(root,'assets',name),'utf8')+'</script>');
const dashboard=fs.readFileSync(path.join(root,'pages/company_dashboard.php'),'utf8');
const subtab=dashboard.slice(dashboard.indexOf('function showSubTabContent('),dashboard.indexOf('function navigateToDittNettsted('));
const stubs=`
window.errors=[];window.calls=[];window.messages=[];window.failSave=false;window.failDecision=false;
window.failAck=false;window.opened=[];window.refreshes=0;
window.addEventListener('error',e=>errors.push(e.message));
window.addEventListener('unhandledrejection',e=>errors.push(String(e.reason)));
window.toast={loading:()=>1,error:(...args)=>messages.push(args),success:(...args)=>messages.push(args)};
window.bootstrap={Modal:{getOrCreateInstance:()=>({show(){},hide(){}})},Tab:{getOrCreateInstance:()=>({show(){}})}};
window.requestFixture={id:'request-a',company_id:'a',rental_object_id:'oa',status:'pending',approved:0,
 customer_name:'John <img src=x onerror="window.injected=1">',display_customer_name:'John',
 object_name:'Sal A',object_category:'venue',object_daily_price:100,rental_fee:200,
 start_date:'2031-05-04',end_date:'2031-05-05',end_time:'18:00:00',cleaning:0,cleaning_fee:20,
 created_at:'2031-01-01',decision_version:'v1',price_adjustment_cents:0};
window.pollFixtures=[
 {id:'p1',module_source:'rental',metadata:{event_type:'rental.request.created',resource_type:'rental_schedule',resource_id:'request-a',customer_name:'John <img src=x>',object_name:'Sal A'}},
 {id:'p2',module_source:'rental',action_type:'rental_booking',items_count:4,metadata:null}
];
window.fetch=async(url,options={})=>{
 const payload=options.body?JSON.parse(options.body):null;calls.push({url:String(url),payload});
 let result={success:true},status=200;
 if(String(url).includes('get_entity_form_fields'))result={success:true,fields:[],presets:[],csrf_token:'fixture'};
 else if(String(url).includes('get_rental_objects'))result={success:true,objects:[]};
 else if(String(url).includes('get_rental_contracts'))result={success:true,contracts:[]};
 else if(String(url).includes('get_rental_schedule'))result={success:true,requests:String(url).includes('approved=0')?[{...requestFixture}]:[],decision_email_enabled:true};
 else if(String(url).includes('rental_unavailable'))result={success:true,periods:[],today:'2031-05-01',objects:[]};
 else if(String(url).includes('get_rental_results'))result={success:true,summary:{},objects:[],recent:[]};
 else if(String(url).includes('update_rental_schedule')){
  if(payload.status&&failDecision){status=409;result={success:false,message:'Forespørselen er endret'};}
  else if(!payload.status&&failSave){status=500;result={success:false,message:'Midlertidig feil'};}
  else {
   requestFixture={...requestFixture,...payload,rental_fee:300,decision_version:'v2'};
   result={success:true,status:payload.status||'pending',email_queued:Boolean(payload.status),rental_fee:300,request:{...requestFixture}};
  }
 } else if(String(url).includes('check_refresh_flag'))result={success:true,polls:pollFixtures,csrf_token:'notifications-fixture'};
 else if(String(url).includes('set_polling_complete')){
  if(failAck){status=500;result={success:false,message:'Kunne ikke kvittere'};}
  else pollFixtures=pollFixtures.filter(p=>p.id!==payload.poll_id);
 }
 return {ok:status===200,status,json:async()=>result,text:async()=>JSON.stringify(result)};
};
${subtab}
`;
const file=path.join(dir,'fixture.html');
fs.writeFileSync(file,'<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><script>'+stubs+'</script>'+
    '<button data-bs-target="#content-rental">Utleie</button><div id="subtabs-rental"></div><div id="content-rental">'+html+'</div>'+
    spawnSync('php',['-r','include "includes/norwegian_datetime_inputs.php";'],{cwd:root,encoding:'utf8'}).stdout+
    '<script>'+fs.readFileSync(path.join(root,'assets/tenant_notifications.js'),'utf8')+'</script>');
const checks=`(async()=>{
const check=(ok,message)=>{if(!ok)throw new Error(message)};
const delay=()=>new Promise(r=>setTimeout(r,30));
await delay();
check(errors.length===0,'startup errors: '+errors.join(';'));
buildCreateForm('venue');
check(!document.querySelector('#createRentalForm [name="market_price"]'),'venue hides market price');
buildCreateForm('tool');document.querySelector('#createRentalForm [name="market_price"]').value='987';
document.querySelector('#createRentalForm [name="type_data[brand]"]').value='Saved brand';
buildCreateForm('venue',getFormData('createRentalForm'));
check(!document.querySelector('#createRentalForm [name="market_price"]'),'switch to venue removes irrelevant field');
buildCreateForm('tool',getFormData('createRentalForm'));
check(document.querySelector('#createRentalForm [name="market_price"]').value==='987','market price draft survives category switch');
check(document.querySelector('#createRentalForm [name="type_data[brand]"]').value==='Saved brand','category draft restored');
showSubTabContent('rental','rental-requests');
const row=document.querySelector('[data-rental-request-id="request-a"]');
check(row,'real pending request renderer loaded');
check(!RentalRequestWorkflow.hasDrafts(),'shared date enhancer does not mark unchanged rows dirty');
const end=row.querySelector('[data-rental-request-field="end-date"]');
end.value='2031-05-06';end.dispatchEvent(new Event('change',{bubbles:true}));
const before=calls.filter(c=>c.payload).length;
await approveScheduleRequest('request-a');
check(!document.getElementById('rental-decision-dialog')&&calls.filter(c=>c.payload).length===before,'approval does not implicitly save draft');
await declineScheduleRequest('request-a');
check(!document.getElementById('rental-decision-dialog'),'rejection also blocks unsaved draft');
await loadRentalRequests();
check(end.isConnected&&end.value==='2031-05-06','refresh preserves dirty row');
failSave=true;await RentalRequestWorkflow.save('request-a');
check(end.value==='2031-05-06'&&RentalRequestWorkflow.hasDrafts(),'failed save retains draft');
window.confirm=()=>false;RentalRequestWorkflow.discard('request-a');
check(end.value==='2031-05-06','cancelled discard retains draft');
window.confirm=()=>true;RentalRequestWorkflow.discard('request-a');
check(end.value==='2031-05-05'&&!RentalRequestWorkflow.hasDrafts(),'explicit discard restores saved values');
end.value='2031-05-06';end.dispatchEvent(new Event('change',{bubbles:true}));
failSave=false;await RentalRequestWorkflow.save('request-a');
check(!RentalRequestWorkflow.hasDrafts(),'successful explicit save marks draft clean');
const save=calls.filter(c=>c.url.includes('update_rental_schedule')).at(-1).payload;
check(!Object.hasOwn(save,'status')&&!Object.hasOwn(save,'rental_fee')&&save.csrf_token,'save cannot finalize or supply amount');
await approveScheduleRequest('request-a');
let dialog=document.getElementById('rental-decision-dialog');
check(dialog?.open&&dialog.textContent.includes('300,00')&&dialog.textContent.includes('06.05.2031'),'saved server quote in review');
check(dialog.textContent.includes('John <img')&&!dialog.querySelector('img')&&!window.injected,'summary escapes customer text');
check(dialog.textContent.includes('ikke at e-posten allerede er sendt'),'queue is not delivery');
dialog.querySelectorAll('button')[1].click();await delay();
await declineScheduleRequest('request-a');
dialog=document.getElementById('rental-decision-dialog');failDecision=true;
dialog.querySelector('button').click();await delay();
check(dialog.open&&dialog.textContent.includes('Forespørselen er endret')&&row.isConnected,'decision error preserves row and summary');
failDecision=false;
dialog.querySelector('button').click();dialog.querySelector('button').click();await delay();
check(!row.isConnected&&!document.getElementById('rental-decision-dialog'),'confirmed decision removes only target');
RentalRequestWorkflow.inspect(requestFixture);
check(document.getElementById('rental-decision-dialog').textContent.includes('Avslått'),'processed request can be inspected');
document.getElementById('rental-decision-dialog').querySelector('button').click();
const decisions=calls.filter(c=>c.url.includes('update_rental_schedule')&&c.payload.status);
check(decisions.length===2,'double click submits only one successful decision');
check(decisions.at(-1).payload.status==='rejected'&&decisions.at(-1).payload.expected_version==='v2'&&!Object.hasOwn(decisions.at(-1).payload,'end_date'),'decision has saved version, no field edits');
await TenantNotifications.check();
check(document.querySelectorAll('[data-notification-id]').length===2,'multiple notifications');
check(document.querySelector('[data-notification-id="p1"]').textContent.includes('John <img src=x> har sendt en utleieforespørsel om Sal A'),'specific literal notification');
check(!document.querySelector('#tenant-notifications img'),'notification names are text');
check(document.querySelector('[data-notification-id="p2"]').textContent.includes('4 hendelser'),'legacy count supported');
await TenantNotifications.check();
check(document.querySelectorAll('[data-notification-id]').length===2,'no repeated display');
const legacy=document.querySelector('[data-notification-id="p2"]');
failAck=true;legacy.querySelector('button').click();await delay();
check(legacy.isConnected&&legacy.textContent.includes('Kunne ikke kvittere'),'ack failure retains notification');
failAck=false;legacy.querySelector('button').click();await delay();
check(!legacy.isConnected,'explicit acknowledgement');
window.openRentalRequestNotification=async id=>{opened.push(id);return true};
const first=document.querySelector('[data-notification-id="p1"]');
first.querySelector('button').click();await delay();
check(opened[0]==='request-a'&&!first.isConnected,'open follows saved resource');
await TenantNotifications.check();
check(!document.querySelector('[data-notification-id]'),'acknowledged event stays gone');
check(!document.querySelector('#polling-countdown-toast'),'no auto reload countdown');
check(errors.length===0,'browser errors: '+errors.join(';'));
return {passed:true,width:innerWidth};
})()`;
let browser,socket;
async function main(){
 browser=spawn(process.env.CHROMIUM_PATH||'/repl/tools/bin/chromium',['--headless','--no-sandbox','--disable-dev-shm-usage','--remote-debugging-port=0','--user-data-dir='+path.join(dir,'profile'),'about:blank']);
 const url=await new Promise((resolve,reject)=>{let output='';const timeout=setTimeout(()=>reject(new Error('Browser timed out')),15000);
  browser.stderr.on('data',data=>{output+=data;const match=output.match(/DevTools listening on (ws:\/\/[^\s]+)/);if(match){clearTimeout(timeout);resolve(match[1]);}});browser.on('error',reject);});
 socket=new WebSocket(url);await new Promise(r=>socket.addEventListener('open',r,{once:true}));
 let id=0;const pending=new Map();
 socket.addEventListener('message',event=>{const msg=JSON.parse(event.data);const cb=pending.get(msg.id);if(cb){pending.delete(msg.id);msg.error?cb.reject(msg.error):cb.resolve(msg.result);}});
 const send=(method,params={},sessionId)=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});socket.send(JSON.stringify({id,method,params,sessionId}));});
 const {targetId}=await send('Target.createTarget',{url:'about:blank'});
 const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
 for(const width of [390,1280]){
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width===390},sessionId);
  await send('Page.navigate',{url:'file://'+file+'?w='+width},sessionId);await new Promise(r=>setTimeout(r,500));
  const result=await send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
  assert.equal(result.result?.value?.passed,true,JSON.stringify(result));
  console.log('Rental workflow and notifications passed at '+width+'px');
 }
}
main().catch(error=>{console.error(error);process.exitCode=1;}).finally(async()=>{
 if(socket)socket.close();if(browser){const stopped=new Promise(r=>browser.once('exit',r));browser.kill('SIGKILL');await stopped;}
 await new Promise(r=>setTimeout(r,300));
 try {fs.rmSync(dir,{recursive:true,force:true,maxRetries:10,retryDelay:250});}
 catch(error){if(error.code!=='ENOTEMPTY'&&error.code!=='EBUSY')throw error;}
});
