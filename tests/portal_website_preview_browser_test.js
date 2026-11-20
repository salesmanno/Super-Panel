const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');
const account = spawnSync('php',['-r','$adminName="Test Administrator";include "includes/portal_preview_account.php";'],{encoding:'utf8'});
if (account.status) throw new Error(account.stderr);
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'portal-website-preview-'));
const fixture = path.join(dir, 'index.html');
const data = {
  company: {name:'Testbedrift',header_bg_color:'#244d83',header_text_color:'#ffffff',header_height:'100px'},
  is_supervisor:true,
  modules: ['events','rental','membership','sales','reservations','email','economy'].map(module_key=>({module_key,name:module_key})),
  news: [{id:'news-1',title:'Nyhet',content:'Eksempel',image_url:''}],
  products:[
    {id:'offer-member',module:'membership',source_id:'plan-member',name:'Bergsøy Medlem',description:'',image_url:'',price:250,is_visible:true,is_public:true},
    {id:'offer-event',module:'events',source_id:'event-one',name:'Arrangement',price:100,is_visible:true,is_public:true},
    {id:'offer-rental',module:'rental',source_id:'rental-one',name:'Utleie',price:100,is_visible:true,is_public:true}
    ,{id:'offer-sales',module:'sales',source_id:'sale-one',name:'Salgsprodukt',price:100,is_visible:true,is_public:true}
  ], errors:[]
};
fs.writeFileSync(fixture, `<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<style>${fs.readFileSync('assets/portal-website-preview.css','utf8')}${fs.readFileSync('assets/entity-form-fields.css','utf8')}</style>
<body class="portal-wpreview">
<section id="portal-website-preview"><header class="pwp-brand"><div class="pwp-brand-identity"><img id="pwp-logo" hidden><strong id="pwp-company-name"></strong></div>${account.stdout}</header>
<nav class="pwp-site-nav"><button data-pwp-page="home">Hjem</button><button data-pwp-page="services">Tjenester</button><button data-pwp-page="about">Om oss</button></nav>
<div id="pwp-errors"></div><div id="pwp-content" style="min-height:1200px"></div><footer><span id="pwp-footer-company"></span><span id="pwp-contact"></span></footer>
<div class="pwp-island"><button data-pwp-mode="read">Se på</button><button data-pwp-mode="edit">Rediger</button><button data-pwp-mode="benefits">Fordeler</button><button data-pwp-mode="fields">Skjemafelt</button><button id="pwp-new">＋ Ny</button></div>
<div id="pwp-create-menu" class="pwp-create-menu" hidden></div></section>
<div id="pwp-workspace-bar" hidden><button id="pwp-close-workspace">Tilbake</button><span></span></div>
<dialog id="pwp-dialog"><div class="pwp-dialog-shell"><header class="pwp-dialog-head"><h2 id="pwp-dialog-title"></h2><button id="pwp-dialog-close">Lukk</button></header><div id="pwp-dialog-body"></div></div></dialog>
<div id="portal-legacy"><div id="toastContainer"></div>
${['announcements',...data.modules.map(m=>m.module_key)].map(key=>`<button data-bs-target="#content-${key}">${key}</button>`).join('')}
${['announcements',...data.modules.map(m=>m.module_key)].map(key=>`<section id="content-${key}" class="tab-pane"><input name="original-${key}" value="Kept"></section>`).join('')}
<button id="news-create-tab">Ny sak</button><div id="subtabs-events"><a href="#events-create">Arrangement</a></div>
<button data-bs-target="#createPlanModal">Medlemsplan</button><button id="sales-new-product">Produkt</button>
<a href="#membership-members">Medlemmer</a><section id="membership-members"><h5 class="card-title">Medlemsoversikt</h5>
<select id="member-plan-filter"><option value="">Alle</option><option value="plan-member">Bergsøy Medlem</option><option value="plan-board">Bergsøy Medlem Styre</option></select><div id="fixture-members"></div></section>
<form id="legacy-original-form"><input name="important-legacy-field" value="Preserved"></form></div>
<div id="firmaModalTabContent"></div>
<div id="rentalModal" class="modal"><div class="modal-dialog"><div class="modal-content">Utleieredigering</div></div></div>
<script id="pwp-data" type="application/json">${JSON.stringify(data)}</script>
<script>${fs.readFileSync('assets/entity-form-fields.js','utf8')}</script>
<script>window.actions=[]; window.bootstrap={Tab:{getOrCreateInstance(button){return{show(){actions.push(button.dataset.bsTarget)}}}}};
window.openRentalCreate=()=>actions.push('rental-create'); window.openCreateServiceModal=()=>actions.push('reservations-create');
for(const id of ['news-create-panel','rental-create','events-create']){
const panel=document.createElement('section');panel.id=id;panel.innerHTML='<input name="creation-test-field" value="Kept">';document.getElementById('portal-legacy').appendChild(panel);
}
window.unlocked=true;window.saved=0;
window.fetch=async url=>url.includes('get_entity_form_fields')
  ?{ok:true,json:async()=>({success:true,csrf_token:'fixture-token',revision:'a'.repeat(64),presets:[{id:'standard',label:'Kontakt',fields:[{field_name:'email',field_label:'E-post',field_type:'email',is_visible:1}]}],fields:[{field_name:'email',field_label:'Eksisterende felt for '+new URL(url,'http://fixture').searchParams.get('entity_id'),field_type:'email',is_visible:1,is_required:0,sort_order:0,options:[],validation_rules:{}}]})}
  :url.includes('load_ditt_nettsted_content')
  ?{ok:unlocked,status:unlocked?200:403,text:async()=>'<section id="firma-firmadata" class="tab-pane"><form onsubmit="event.preventDefault();saved++"><input id="fixture-company-name" value="Firmanavn"><button>Lagre</button></form></section><section id="firma-webside" class="tab-pane">Websidefelt</section><section id="firma-staff" class="tab-pane">Ansattefelt</section><section id="firma-about" class="tab-pane">Om oss-tekst</section>'}
  :{ok:true,json:async()=>({success:true,...JSON.parse(document.getElementById('pwp-data').textContent)})};
window.editRentalObject=id=>{actions.push('edit-rental:'+id);document.getElementById('rentalModal').classList.add('show')};
window.editEvent=id=>actions.push('edit-event:'+id);
window.EventsDashboard={openParticipants:id=>actions.push('participants:'+id)};
window.loadRentalObjects=async()=>{actions.push('load-rental')};
window.loadEvents=async()=>{actions.push('load-events')};
window.viewDetails=id=>actions.push('read-rental:'+id);
window.MembershipBenefits={openProduct:async id=>actions.push('benefits:'+id)};
const actualFieldMount=window.EntityFormFields.mount;
window.EntityFormFields.mount=(host,options)=>{
 actions.push('fields:'+options.entityType+':'+options.entityId);
 const editor=actualFieldMount(host,options);
 return {...editor,confirmClose:()=>window.confirmFields!==false&&editor.confirmClose()};
};
window.salesReady=false;
window.SalesOnline={snapshot:()=>({ready:salesReady,products:[{id:'sale-one'}]}),edit:id=>actions.push('edit-sales:'+id)};
document.addEventListener('sales:online-load',()=>setTimeout(()=>{salesReady=true;document.dispatchEvent(new CustomEvent('sales:products-updated'))},10));
window.PortalNews={canClose:()=>true,openNew:()=>true,edit:async id=>{actions.push('edit-news:'+id);document.dispatchEvent(new CustomEvent('portal:news-editor-open'));return true}};
document.getElementById('member-plan-filter').addEventListener('change',event=>{
document.getElementById('fixture-members').textContent=event.target.value==='plan-member'?'Medlem ordinær':'Medlem ordinær, Medlem Styre';
});
document.querySelectorAll('#news-create-tab,#subtabs-events a,[data-bs-target="#createPlanModal"],#sales-new-product').forEach(b=>b.onclick=e=>{e.preventDefault();actions.push(b.id||b.getAttribute('href')||b.dataset.bsTarget)});
</script><script>${fs.readFileSync('assets/portal-preview-workspace.js','utf8')}</script><script>${fs.readFileSync('assets/portal-website-preview.js','utf8')}</script>`);
const checks = `(async()=>{
 const assert=(v,m)=>{if(!v)throw Error(m)};
 const click=s=>document.querySelector(s).click();
 const tick=()=>new Promise(r=>setTimeout(r,30));
 assert(getComputedStyle(document.querySelector('#portal-legacy')).display==='none','Legacy must be hidden for preview');
 assert(document.querySelector('#pwp-logo').hidden,'Empty image URL must not load the page as an image');
 assert(document.querySelector('#toastContainer').parentElement===document.body,'Notifications must not be hidden');
 click('#pwp-new');
 assert(document.querySelector('#pwp-dialog').open,'News creation should open a modal surface');
 assert(document.querySelector('#pwp-dialog-body #news-create-panel'),'Existing news form must be the dialog content');
 click('#pwp-dialog-close');await tick();
 click('[data-pwp-mode="edit"]');click('[data-news-id="news-1"]');await tick();
 assert(actions.includes('edit-news:news-1'),'News edit must open the exact story');
 assert(document.querySelector('#pwp-dialog-body #news-create-panel'),'News editing must be hosted inside preview dialog');
 assert(getComputedStyle(document.querySelector('#portal-legacy')).display==='none','News editing exposed legacy');
 click('#pwp-dialog-close');await tick();
 click('[data-pwp-page="services"]');
 click('[data-pwp-mode="read"]');
 click('[data-service-id="offer-member"]');
 assert(document.getElementById('member-plan-filter').value==='plan-member','Membership card must select its exact plan');
 assert(document.getElementById('member-plan-filter').disabled,'Scoped card list must not mix plans');
 assert(!document.getElementById('fixture-members').textContent.includes('Styre'),'Different plan leaked into member list');
 document.getElementById('member-plan-filter').innerHTML='<option value="">Alle</option><option value="plan-member">Bergsøy Medlem</option>';
 await tick();
 assert(document.getElementById('member-plan-filter').value==='plan-member','Async option loading reset the selected plan');
 click('#pwp-dialog-close');await tick();
 assert(!document.getElementById('member-plan-filter').disabled,'Legacy filter should work normally after leaving scoped list');
 window.scrollTo(0,120); const y=scrollY;
 const before=document.querySelector('#pwp-content').getBoundingClientRect().top;
 click('[data-pwp-mode="edit"]'); assert(scrollY===y,'Mode changed scroll');
 assert(document.querySelector('#pwp-content').getBoundingClientRect().top===before,'Mode shifted content');
 const expected={membership:'#createPlanModal',events:'#events-create',rental:'rental-create',sales:'sales-new-product',reservations:'reservations-create'};
 for(const [key,action] of Object.entries(expected)){
   click('#pwp-new'); await tick();
   assert(document.querySelectorAll('[data-create]').length===5,'Missing authorised service choices');
   click('[data-create="'+key+'"]'); await tick(); assert(actions.includes(action),'Wrong creation action: '+key);
   if(['events','rental'].includes(key)){
      assert(document.getElementById('pwp-dialog').open,'Creation must use a larger modal: '+key);
      const box=document.getElementById('pwp-dialog').getBoundingClientRect();
      assert(box.top>=0&&box.top<60&&box.bottom<=innerHeight,'Dialog must remain inside the viewport');
     assert(innerWidth<600?box.width>=document.documentElement.clientWidth-20:box.width>=1000,'Creation modal too small: '+key);
   }
    assert(getComputedStyle(document.querySelector('#portal-legacy')).display==='none','Full legacy module must stay hidden');
   assert(document.querySelector('[name="important-legacy-field"]').value==='Preserved','Original form was replaced');
    click('#pwp-dialog-close');await tick();
 }
  click('[data-pwp-mode="read"]');click('[data-service-id="offer-event"]');await tick();
  assert(actions.includes('participants:event-one'),'Read mode must open the real participant/check-in window');
  click('[data-pwp-mode="edit"]');click('[data-service-id="offer-event"]');
  assert(actions.includes('edit-event:event-one'),'Event edit must target the source event');
  click('[data-service-id="offer-rental"]');await tick();
  assert(actions.includes('edit-rental:rental-one'),'Rental edit must target the source rental');
  assert(getComputedStyle(document.querySelector('#portal-legacy')).display==='none','Service editing exposed legacy');
  document.getElementById('rentalModal').classList.remove('show');
  click('[data-service-id="offer-sales"]');await tick();
  assert(actions.includes('edit-sales:sale-one'),'Sales edit must wait for the real product list');
  for(const [id,type,source] of [['offer-event','event','event-one'],['offer-rental','rental_object','rental-one'],['offer-member','membership_plan','plan-member'],['offer-sales','sales_product','sale-one']]){
    click('[data-pwp-mode="fields"]');click('[data-service-id="'+id+'"]');await tick();
    assert(document.getElementById('pwp-dialog').open,'Fields mode must open its own modal');
    assert(actions.includes('fields:'+type+':'+source),'Fields must be scoped by entity type AND source ID');
    assert(document.getElementById('pwp-dialog-body').textContent.includes(source),'Wrong existing fields shown');
    window.confirmFields=false;click('#pwp-dialog-close');assert(document.getElementById('pwp-dialog').open,'Dirty field editor was closed');
    window.confirmFields=true;click('#pwp-dialog-close');await tick();
  }
  click('[data-pwp-mode="benefits"]');click('[data-service-id="offer-rental"]');await tick();
  assert(actions.includes('benefits:offer-rental'),'Benefits must open the specific output product, not the catalog');
  assert(!document.getElementById('pwp-dialog').open,'Benefits fell back to general module display');
  click('[data-pwp-mode="read"]');click('[data-service-id="offer-rental"]');await tick();
  assert(actions.includes('read-rental:rental-one'),'Read rental must show that exact object');
  assert(document.querySelector('.pwp-header-logout')&&!document.querySelector('.pwp-site-nav a[href*="logout"]'),'Logout is not exclusively in the header');
  const island=document.querySelector('.pwp-island').getBoundingClientRect();
  assert(island.left>=0&&island.right<=innerWidth,'Action island overflows on mobile');
  assert(island.height<52,'Action island no longer matches compact single-row mockup');
  click('.pwp-account summary');
  assert(document.querySelector('.pwp-account').open,'User menu did not open');
  assert(getComputedStyle(document.querySelector('.pwp-header-logout')).textDecorationLine==='none','Logout should be a menu action, not an underlined bare link');
  document.querySelector('.pwp-account summary').dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
  assert(!document.querySelector('.pwp-account').open,'Escape did not close account menu');
  click('[data-pwp-page="about"]');await tick();
  assert(document.querySelector('.pwp-settings-layout'),'About must render the integrated settings workspace');
  assert(document.querySelector('.pwp-island').inert,'Island should not receive focus on About');
  assert(!document.querySelector('#pwp-settings-main input[type="password"]'),'Company settings must not ask for another password');
  const settingsNav=document.querySelector('.pwp-settings-nav').getBoundingClientRect();
  assert(settingsNav.width>innerWidth*.5&&settingsNav.height<125,'About should use compact horizontal/wrapped tabs rather than tall sidebar');
  assert(document.documentElement.scrollWidth<=document.documentElement.clientWidth,'About tabs must not create horizontal page scrolling');
  assert(document.querySelector('#pwp-settings-main #companyDataForm')||document.querySelector('#pwp-settings-main #fixture-company-name'),'Unlocked company form must render inline');
  document.querySelector('#pwp-settings-main form').requestSubmit();
  assert(saved===1,'Mounted company form lost its real save handler');
  window.unlocked=false;click('[data-pwp-setting="website"]');await tick();
  assert(document.querySelector('#pwp-settings-main').textContent.includes('ikke tilgang'),'Removing the password must not bypass access denial');
  assert(!document.querySelector('#pwp-settings-main input[type="password"]'),'Denied access must not resurrect password prompt');
  window.unlocked=true;
  click('[data-pwp-setting="website"]');await tick();
  assert(document.querySelector('#pwp-settings-main #firma-webside'),'Website settings not integrated');
  assert(!JSON.parse(document.getElementById('pwp-data').textContent).modules.some(module=>module.module_key==='ditt_nettsted'),'Supervisor test must not rely on website module assignment');
  click('[data-pwp-setting="email"]');await tick();
  assert(document.querySelector('#pwp-settings-main #content-email'),'Email must stay inside About');
  click('[data-pwp-setting="economy"]');await tick();
  assert(document.querySelector('#pwp-settings-main #content-economy'),'Economy must stay inside About');
  click('[data-pwp-page="services"]');
  assert(document.getElementById('content-economy').closest('#portal-legacy'),'Leaving About must restore original module DOM');
  assert(document.querySelectorAll('#content-economy').length===1,'Module was duplicated');
 assert(!document.querySelector('button[data-create="economy"]'),'Economy is not a service type');
 // A forged choice may not open a module absent from delegated permissions.
 const length=actions.length; document.querySelector('#pwp-create-menu').innerHTML='<button data-create="forbidden">X</button>';
 document.querySelector('[data-create="forbidden"]').click();
 assert(actions.length===length,'Unpermitted module opened');
 assert(document.querySelector('#pwp-errors').textContent.includes('ikke tilgang'),'Missing access error');
 const accessData=JSON.parse(document.getElementById('pwp-data').textContent);
 accessData.is_supervisor=false;
 document.getElementById('pwp-data').textContent=JSON.stringify(accessData);
 document.dispatchEvent(new CustomEvent('portal:surface-closed'));await tick();
 click('[data-pwp-page="about"]');await tick();
 assert(!document.querySelector('[data-pwp-setting="website"]'),'Non-supervisor without website module must not see Website');
 accessData.modules.push({module_key:'ditt_nettsted',name:'Webside'});
 document.getElementById('pwp-data').textContent=JSON.stringify(accessData);
 document.dispatchEvent(new CustomEvent('portal:surface-closed'));await tick();
 assert(document.querySelector('[data-pwp-setting="website"]'),'Delegated website access must remain available for non-supervisors');
 click('[data-pwp-setting="website"]');await tick();
 assert(document.querySelector('#pwp-settings-main #firma-webside'),'Delegated website settings no longer load');
 // Empty sales/appointment sections must not appear merely because modules are enabled.
 click('[data-pwp-page="services"]');
 const offerData=JSON.parse(document.getElementById('pwp-data').textContent);
 offerData.products=offerData.products.filter(item=>!['sales','reservations'].includes(item.module));
 const salesOffer={id:'web-sale',module:'sales',source_id:'sale-two',name:'Nettvare',price:50,is_visible:true,is_public:false};
 const bookingOffer={id:'web-booking',module:'reservations',source_id:'service-two',name:'Bestillingstime',price:100,is_visible:false,is_public:true};
 offerData.products.push(salesOffer,bookingOffer);
 const refreshOffers=async()=>{document.getElementById('pwp-data').textContent=JSON.stringify(offerData);document.dispatchEvent(new CustomEvent('portal:surface-closed'));await tick();};
 const hasSection=label=>[...document.querySelectorAll('#pwp-content section > h2')].some(h=>h.textContent===label);
 await refreshOffers();
 assert(!hasSection('Salgsvarer')&&!hasSection('Timebestilling'),'Empty/private/hidden offers must not leave empty sections');
 assert(hasSection('Medlemskap'),'Other service sections were unintentionally removed');
 click('#pwp-new');
 assert(document.querySelector('[data-create="sales"]')&&document.querySelector('[data-create="reservations"]'),'Hiding empty sections must preserve permitted creation');
 click('#pwp-new');
 salesOffer.is_public=true;bookingOffer.is_visible=true;await refreshOffers();
 assert(hasSection('Salgsvarer')&&hasSection('Timebestilling'),'Public offers must restore their sections');
 offerData.products=offerData.products.filter(item=>!['sales','reservations'].includes(item.module));
 await refreshOffers();
 assert(!hasSection('Salgsvarer')&&!hasSection('Timebestilling'),'Removing the last offer must hide its section again');
 return {passed:true,width:innerWidth,creationFlows:6,originalFormsPreserved:true};
})()`;
chromiumFixture(fixture, checks, [1280,390]).catch(error=>{console.error(error);process.exitCode=1;});
