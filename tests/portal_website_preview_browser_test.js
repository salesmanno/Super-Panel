const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const chromiumFixture = require('./chromium_fixture');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'portal-website-preview-'));
const fixture = path.join(dir, 'index.html');
const data = {
  company: {name:'Testbedrift',header_bg_color:'#244d83',header_text_color:'#ffffff',header_height:'100px'},
  modules: ['events','rental','membership','sales','reservations'].map(module_key=>({module_key,name:module_key})),
  news: [{id:'news-1',title:'Nyhet',content:'Eksempel',image_url:''}],
  products:[{id:'offer-member',module:'membership',source_id:'plan-member',name:'Bergsøy Medlem',description:'',image_url:'',price:250,is_visible:true,is_public:true}], errors:[]
};
fs.writeFileSync(fixture, `<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<style>${fs.readFileSync('assets/portal-website-preview.css','utf8')}</style>
<body class="portal-wpreview">
<section id="portal-website-preview"><header class="pwp-brand"><img id="pwp-logo" hidden><strong id="pwp-company-name"></strong></header>
<nav class="pwp-site-nav"><button data-pwp-page="home">Hjem</button><button data-pwp-page="services">Tjenester</button></nav>
<div id="pwp-errors"></div><div id="pwp-content" style="min-height:1200px"></div><footer><span id="pwp-footer-company"></span><span id="pwp-contact"></span></footer>
<div class="pwp-island"><button data-pwp-mode="read">Se på</button><button data-pwp-mode="edit">Rediger</button><button data-pwp-mode="benefits">Fordeler</button><button id="pwp-new">＋ Ny</button></div>
<div id="pwp-create-menu" class="pwp-create-menu" hidden></div></section>
<div id="pwp-workspace-bar" hidden><button id="pwp-close-workspace">Tilbake</button><span></span></div>
<div id="portal-legacy"><div id="toastContainer"></div>
${['announcements',...data.modules.map(m=>m.module_key)].map(key=>`<button data-bs-target="#content-${key}">${key}</button>`).join('')}
<button id="news-create-tab">Ny sak</button><div id="subtabs-events"><a href="#events-create">Arrangement</a></div>
<button data-bs-target="#createPlanModal">Medlemsplan</button><button id="sales-new-product">Produkt</button>
<a href="#membership-members">Medlemmer</a><section id="membership-members"><h5 class="card-title">Medlemsoversikt</h5>
<select id="member-plan-filter"><option value="">Alle</option><option value="plan-member">Bergsøy Medlem</option><option value="plan-board">Bergsøy Medlem Styre</option></select><div id="fixture-members"></div></section>
<form id="legacy-original-form"><input name="important-legacy-field" value="Preserved"></form></div>
<script id="pwp-data" type="application/json">${JSON.stringify(data)}</script>
<script>window.actions=[]; window.bootstrap={Tab:{getOrCreateInstance(button){return{show(){actions.push(button.dataset.bsTarget)}}}}};
window.openRentalCreate=()=>actions.push('rental-create'); window.openCreateServiceModal=()=>actions.push('reservations-create');
for(const id of ['news-create-panel','rental-create','events-create']){
const panel=document.createElement('section');panel.id=id;panel.innerHTML='<input name="creation-test-field" value="Kept">';document.getElementById('portal-legacy').appendChild(panel);
}
window.fetch=async()=>({ok:true,json:async()=>({success:true,...JSON.parse(document.getElementById('pwp-data').textContent)})});
document.getElementById('member-plan-filter').addEventListener('change',event=>{
document.getElementById('fixture-members').textContent=event.target.value==='plan-member'?'Medlem ordinær':'Medlem ordinær, Medlem Styre';
});
document.querySelectorAll('#news-create-tab,#subtabs-events a,[data-bs-target="#createPlanModal"],#sales-new-product').forEach(b=>b.onclick=e=>{e.preventDefault();actions.push(b.id||b.getAttribute('href')||b.dataset.bsTarget)});
</script><script>${fs.readFileSync('assets/portal-website-preview.js','utf8')}</script>`);
const checks = `(async()=>{
 const assert=(v,m)=>{if(!v)throw Error(m)};
 const click=s=>document.querySelector(s).click();
 const tick=()=>new Promise(r=>setTimeout(r,30));
 assert(getComputedStyle(document.querySelector('#portal-legacy')).display==='none','Legacy must be hidden for preview');
 assert(document.querySelector('#pwp-logo').hidden,'Empty image URL must not load the page as an image');
 assert(document.querySelector('#toastContainer').parentElement===document.body,'Notifications must not be hidden');
 click('#pwp-new'); assert(actions.includes('news-create-tab'),'Home New must open real news form');
 assert(document.body.classList.contains('pwp-creation-open'),'News creation should open a modal surface');
 assert(document.querySelector('#news-create-panel[role="dialog"]'),'Existing news form must be the dialog content');
 click('#pwp-close-workspace');
 click('[data-pwp-page="services"]');
 click('[data-service-id="offer-member"]');
 assert(document.getElementById('member-plan-filter').value==='plan-member','Membership card must select its exact plan');
 assert(document.getElementById('member-plan-filter').disabled,'Scoped card list must not mix plans');
 assert(!document.getElementById('fixture-members').textContent.includes('Styre'),'Different plan leaked into member list');
 document.getElementById('member-plan-filter').innerHTML='<option value="">Alle</option><option value="plan-member">Bergsøy Medlem</option>';
 await tick();
 assert(document.getElementById('member-plan-filter').value==='plan-member','Async option loading reset the selected plan');
 click('#pwp-close-workspace');
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
     assert(document.body.classList.contains('pwp-creation-open'),'Creation must use a larger modal: '+key);
     const box=document.getElementById('portal-legacy').getBoundingClientRect();
     assert(innerWidth<600?box.width>=document.documentElement.clientWidth-20:box.width>=1000,'Creation modal too small: '+key);
   }
   assert(getComputedStyle(document.querySelector('#portal-legacy')).display!=='none','Real module must be shown');
   assert(document.querySelector('[name="important-legacy-field"]').value==='Preserved','Original form was replaced');
   click('#pwp-close-workspace');
 }
 assert(!document.querySelector('button[data-create="economy"]'),'Economy is not a service type');
 // A forged choice may not open a module absent from delegated permissions.
 const length=actions.length; document.querySelector('#pwp-create-menu').innerHTML='<button data-create="forbidden">X</button>';
 document.querySelector('[data-create="forbidden"]').click();
 assert(actions.length===length,'Unpermitted module opened');
 assert(document.querySelector('#pwp-errors').textContent.includes('ikke tilgang'),'Missing access error');
 return {passed:true,width:innerWidth,creationFlows:6,originalFormsPreserved:true};
})()`;
chromiumFixture(fixture, checks, [1280,390]).catch(error=>{console.error(error);process.exitCode=1;});
