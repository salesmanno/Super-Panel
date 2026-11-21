const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const browser=require('./chromium_fixture');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'portal-service-navigation-'));
const fixture=path.join(dir,'index.html');
const data={payment_csrf:'token',company:{name:'Firma',footer_mode:'dynamic'},news:[],errors:[],
 modules:['events','rental','membership','sales'].map(module_key=>({module_key})),
 products:['events','rental','membership','sales','events'].map((module,index)=>({
  id:'product-'+index,source_id:'source-'+index,module,name:'Tjeneste '+index,is_visible:true,is_public:true,price:500
 }))};
const island=fs.readFileSync('includes/portal_website_preview.php','utf8')
 .match(/<div class="pwp-island"[\s\S]*?<\/div>/)[0].replace(/<\?php[\s\S]*?\?>/g,'');
fs.writeFileSync(fixture,`<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<style>html,body{margin:0}*{box-sizing:border-box}.modal{display:none}.modal.show{display:block;position:fixed;inset:10px;z-index:1055;background:white;overflow:auto}#portal-legacy{display:none}
${fs.readFileSync('assets/portal-website-preview.css','utf8')}</style>
<body class="portal-wpreview"><section id="portal-website-preview">
<header class="pwp-brand"><img id="pwp-logo"><strong id="pwp-company-name"></strong></header>
<nav class="pwp-site-nav"><button data-pwp-page="home">Hjem</button><button data-pwp-page="services">Tjenester</button></nav>
<div id="pwp-errors"></div><div id="pwp-content" class="pwp-content"></div>
<footer class="pwp-footer"><strong id="pwp-footer-company"></strong><span id="pwp-contact"></span></footer>
${island}<div id="pwp-create-menu" hidden></div><script id="pwp-data" type="application/json">${JSON.stringify(data)}</script></section>
<div id="portal-legacy">${['events','rental','membership','sales'].map(key=>`<button data-bs-target="#content-${key}"></button><div id="content-${key}"></div>`).join('')}
${['editEventModal','rentalModal','editPlanModal','participantsModal'].map(id=>`<div class="modal" id="${id}"><div class="modal-dialog"><div class="modal-content"><h2>${id}</h2><div class="modal-body"><form><input id="${id}-name" name="name" value="Original"></form></div></div></div></div>`).join('')}
<section id="membership-members"><h2 class="card-title">Medlemmer</h2><select id="member-plan-filter"></select></section><a href="#membership-members"></a></div>
<dialog id="pwp-dialog"><div class="pwp-dialog-shell"><header class="pwp-dialog-head"><h2 id="pwp-dialog-title"></h2><button id="pwp-dialog-close" aria-label="Lukk">×</button></header><div id="pwp-dialog-body"></div></div></dialog>
<div id="mb-dialog" hidden><div class="mb-editor"><h2>Fordeler</h2></div></div>
<div id="sales-dialog" hidden><div id="sales-dialog-body"><form><input name="product" value="Original"></form></div></div>
<script>
window.calls=[];window.discard=false;window.busyPayment=false;window.confirmations=0;window.fieldsDirty=false;
window.confirm=()=>{confirmations++;return discard};
const instances=new Map();
class Modal{
 constructor(el){this.el=el;instances.set(el,this)}
 show(){const event=new Event('show.bs.modal',{bubbles:true,cancelable:true});this.el.dispatchEvent(event);if(event.defaultPrevented)return;this.el.classList.add('show');this.el.dispatchEvent(new Event('shown.bs.modal',{bubbles:true}))}
 hide(){const event=new Event('hide.bs.modal',{bubbles:true,cancelable:true});this.el.dispatchEvent(event);if(event.defaultPrevented)return;
 this.el.classList.remove('show');setTimeout(()=>this.el.dispatchEvent(new Event('hidden.bs.modal',{bubbles:true})),15)}
 static getInstance(el){return instances.get(el)}
 static getOrCreateInstance(el){return this.getInstance(el)||new Modal(el)}
}
window.bootstrap={Modal,Tab:{getOrCreateInstance(){return{show(){}}}}};
${process.env.BOOTSTRAP_TEST_BUNDLE ? `const fixtureTab=window.bootstrap.Tab;${fs.readFileSync(process.env.BOOTSTRAP_TEST_BUNDLE,'utf8')}\n;window.bootstrap.Tab=fixtureTab;` : ''}
for(const id of ['editEventModal','rentalModal']){
 document.getElementById(id).addEventListener('hide.bs.modal',event=>{
  if(document.getElementById(id+'-name').value!=='Original'&&!confirm('Ulagret'))event.preventDefault()
 });
}
window.loadEvents=async()=>{};window.loadRentalObjects=async()=>{};
window.editEvent=async id=>{calls.push(['edit','events',id]);bootstrap.Modal.getOrCreateInstance(document.getElementById('editEventModal')).show()};
window.editRentalObject=async id=>{calls.push(['edit','rental',id]);bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).show()};
window.editMembershipPlan=async id=>{calls.push(['edit','membership',id]);bootstrap.Modal.getOrCreateInstance(document.getElementById('editPlanModal')).show()};
window.EventsDashboard={openParticipants:async id=>{calls.push(['read','events',id]);bootstrap.Modal.getOrCreateInstance(document.getElementById('participantsModal')).show()}};
window.viewDetails=id=>{calls.push(['read','rental',id]);bootstrap.Modal.getOrCreateInstance(document.getElementById('participantsModal')).show()};
window.EntityFormFields={mount(host,options){calls.push(['fields',options.entityType,options.entityId]);host.innerHTML='<p>Eksisterende felt for '+options.entityId+'</p>';
 return{refresh:async()=>{},confirmClose:()=>!fieldsDirty||confirm('Ulagrede felt')}}};
window.MembershipBenefits={openProduct:async id=>{calls.push(['benefits',id]);PortalWorkspace.show(document.querySelector('#mb-dialog .mb-editor'),'Fordeler')},closeProduct(){return PortalWorkspace.close()}};
window.SalesOnline={snapshot:()=>({ready:true,products:[{id:'source-3'}]}),edit:id=>{calls.push(['edit','sales',id]);PortalWorkspace.show(document.getElementById('sales-dialog'),'Rediger produkt')},
 close(){document.getElementById('sales-dialog').hidden=true;return true}};
window.fetch=async(url,options={})=>{
 if(url.includes('get_portal_website_preview'))return{ok:true,json:async()=>({success:true,...JSON.parse(document.getElementById('pwp-data').textContent)})};
 const query=new URL(url,location.href);calls.push(['payments',query.searchParams.get('module'),query.searchParams.get('id')]);
 if(options.method==='POST'){await new Promise(r=>setTimeout(r,180));return{ok:true,json:async()=>({success:true,version:'saved'})}}
 return{ok:true,json:async()=>({success:true,name:'Valgt tjeneste',selected:['vipps'],methods:[{value:'vipps',label:'Vipps'},{value:'stripe',label:'Kort'}],version:'a'})};
};
</script>
${['portal-preview-workspace','portal-website-preview'].map(file=>`<script>${fs.readFileSync('assets/'+file+'.js','utf8')}</script>`).join('')}`);
const checks=`(async()=>{
const assert=(v,m)=>{if(!v)throw Error(m)};
const click=s=>{const el=document.querySelector(s);assert(el,'Missing '+s);el.click()};
const tick=(ms=70)=>new Promise(r=>setTimeout(r,ms));
const shownNav=()=>[...document.querySelectorAll('.pwp-service-navigation')].find(el=>el.getBoundingClientRect().height>0);
let frame;
const checkFrame=()=>{const box=document.getElementById('pwp-dialog').getBoundingClientRect();assert(document.getElementById('pwp-dialog').open,'Common frame closed');if(frame)assert(Math.abs(box.top-frame.top)<1&&Math.abs(box.left-frame.left)<1&&Math.abs(box.width-frame.width)<1&&Math.abs(box.height-frame.height)<1,'Interface switch moved or resized modal');else frame=box;assert(!document.querySelector('.modal-backdrop'),'Nested Bootstrap backdrop')};
const go=async action=>{const el=shownNav()?.querySelector('select');assert(el,'No service navigation');el.value=action;el.dispatchEvent(new Event('change',{bubbles:true}));await tick();checkFrame()};
click('[data-pwp-page="services"]');click('[data-pwp-mode="edit"]');click('[data-service-id="product-0"]');await tick();
assert(document.getElementById('pwp-dialog').dataset.pwpSourceModal==='editEventModal','Event edit not embedded in common frame: '+document.getElementById('pwp-errors').textContent+' / '+JSON.stringify(calls)+' / '+document.getElementById('editEventModal').className);checkFrame();
assert(shownNav().querySelector('select').value==='edit','Wrong initial action');
const eventName=document.getElementById('editEventModal-name');eventName.value='Endret';eventName.dispatchEvent(new Event('input',{bubbles:true}));
await go('fields');assert(document.getElementById('editEventModal').classList.contains('show'),'Dirty cancel closed event');
assert(shownNav().querySelector('select').value==='edit','Cancelled selection not restored');
window.discard=true;await go('fields');
assert(!document.getElementById('editEventModal').classList.contains('show'),'Previous Bootstrap modal remained open');
assert(document.getElementById('pwp-dialog-body').textContent.includes('source-0'),'Fields lost selected event');
window.fieldsDirty=true;window.discard=false;await go('payments');
assert(shownNav().querySelector('select').value==='fields','Dirty field guard bypassed');
window.fieldsDirty=false;await go('payments');
assert(calls.some(x=>x.join(':')==='payments:events:source-0'),'Payment editor used wrong event');
assert(document.querySelector('input[value="vipps"]').checked,'Existing payment choice missing');
document.querySelector('input[value="stripe"]').click();await go('read');
assert(shownNav().querySelector('select').value==='payments','Dirty payment guard bypassed');
document.querySelector('.pwp-payment-settings').requestSubmit();
await go('read');
assert(shownNav().querySelector('select').value==='payments','Navigation interrupted payment-method save');
await tick(160);
window.discard=true;await go('read');
assert(document.getElementById('pwp-dialog').dataset.pwpSourceModal==='participantsModal','Read did not open participant view in common frame');
assert(calls.some(x=>x.join(':')==='read:events:source-0'),'Participants lost selected event');
shownNav().querySelector('[data-service-step="-1"]').click();await tick();checkFrame();
assert(shownNav().querySelector('select').value==='payments','Previous did not wrap');
click('#pwp-dialog-close');await tick();
click('[data-service-id="product-4"]');await tick();await go('payments');
assert(calls.some(x=>x.join(':')==='payments:events:source-4'),'New card kept previous source');
await go('benefits');assert(document.querySelector('#pwp-dialog .mb-editor'),'Benefits not in common frame');
assert(calls.some(x=>x.join(':')==='benefits:product-4'),'Benefits used source instead of output product');
await go('fields');assert(!document.querySelector('#pwp-dialog .mb-editor'),'Benefits remained embedded');
click('#pwp-dialog-close');await tick();
for(const index of [1,2]){
 click('[data-service-id="product-'+index+'"]');await tick();
 assert(shownNav(),'Missing navigation for service '+index);
 if(index===2){
  const name=document.getElementById('editPlanModal-name');name.value='Endret';name.dispatchEvent(new Event('input',{bubbles:true}));
  window.discard=false;await go('payments');assert(shownNav().querySelector('select').value==='edit','Membership lost unsaved edit');window.discard=true;
 }
 await go('payments');assert(calls.some(x=>x.join(':')==='payments:'+['','rental','membership'][index]+':source-'+index),'Payment scope mismatch');
 const nav=shownNav();const rect=nav.getBoundingClientRect();assert(rect.width<=innerWidth,'Navigation overflow');
 assert(![...nav.querySelector('select').options].some(o=>o.value==='benefits')||index!==2,'Membership offered benefit on itself');
 click('#pwp-dialog-close');await tick();
}
click('[data-service-id="product-3"]');await tick();assert(document.querySelector('#pwp-dialog #sales-dialog'),'Sales editor missing from common frame');
assert(shownNav(),'Sales navigation missing');
await go('fields');assert(document.getElementById('sales-dialog').hidden,'Sales editor not closed');
assert(calls.some(x=>x.join(':')==='fields:sales_product:source-3'),'Sales source lost');
assert(document.documentElement.scrollWidth<=innerWidth,'Page overflow');
click('#pwp-dialog-close');await tick();click('[data-service-id="product-2"]');await tick();
bootstrap.Modal.getInstance(document.getElementById('editPlanModal')).hide();await tick();
assert(!document.getElementById('pwp-dialog').open,'Existing save/close API did not close common frame');
assert(!document.querySelector('#pwp-dialog #editPlanModal'),'Original modal not restored after close');
click('[data-service-id="product-2"]');await tick();await go('fields');
return{passed:true,width:innerWidth,checks:'same-source navigation, four service types, existing selections, modal cleanup, native and generic dirty guards, carousel'};
})()`;
browser(fixture,checks,[1280,390,320]).catch(error=>{console.error(error);process.exitCode=1});
