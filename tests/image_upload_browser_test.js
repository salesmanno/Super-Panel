const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
if (typeof WebSocket === 'undefined') {
    const child = require('node:child_process').spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio:'inherit'});
    process.exit(child.status ?? 1);
}
const chromiumFixture = require('./chromium_fixture');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'image-upload-browser-'));
const script = fs.readFileSync(path.join(__dirname, '../assets/image-upload.js'), 'utf8');
const css = fs.readFileSync(path.join(__dirname, '../assets/image-upload.css'), 'utf8');
const fixture = path.join(dir, 'fixture.html');
const fixtures = [
    ['sales', '<label>Bildeadresse<input name="img_product"></label>'],
    ['rental', '<div class="image-row" style="display:flex;flex-wrap:wrap"><input type="radio" name="primary" checked><input class="image-url-input"><input class="image-caption-input" value="Behold bildetekst"><button type="button" onclick="this.closest(\'.image-row\').remove()">Fjern rad</button></div>'],
    ['events', '<input name="event_image_url">'],
    ['membership', '<input name="plan_image_url">'],
    ['announcements', '<input id="announcement-image-url">'],
    ['reservation', '<input id="service-img-url">'],
    ['company', '<input name="logo_url">'],
    ['website', '<input name="general_logo_url"><input name="general_favicon_url"><input name="general_hero_image_url"><input name="general_hero_button_url" type="url">'],
];
const mock = `
window.requests=[];window.configRequests=[];window.saves=0;window.badConfig=false;
window.fetch=async url=>{configRequests.push(url);return {ok:true,json:async()=>({success:true,configured:!badConfig,company_id:document.body.dataset.companyId,csrf_token:'test-token',max_bytes:10485760,formats:['image/png','image/jpeg','image/webp']})};};
class FakeXHR {
 static DONE=4;
 constructor(){this.upload={};this.readyState=0;}
 open(){this.readyState=1;} setRequestHeader(){}
 send(body){this.body=body;requests.push(this);}
 abort(){this.readyState=4;this.onabort?.();}
 respond(result,status=200){this.status=status;this.responseText=JSON.stringify(result);this.readyState=4;this.onload?.();}
}
window.XMLHttpRequest=FakeXHR;
`;
fs.writeFileSync(fixture, `<!doctype html><html lang="nb"><meta name="viewport" content="width=device-width,initial-scale=1"><style>
body{font:15px system-ui;background:#edf2f5;color:#223b4c;margin:0;padding:18px}main{max-width:900px;margin:auto}form,.modal{background:white;border:1px solid #d5e1e5;border-radius:10px;margin:12px 0;padding:14px}input:not([type=file]):not([type=radio]){box-sizing:border-box;max-width:100%;display:block;border:1px solid #c6d2d8;border-radius:5px;padding:9px;width:100%}button{padding:8px;margin:3px}label{display:block}${css}</style>
<body data-company-id="company-a"><main><h1>Bilder i skjemaene</h1><p>Samme bildeopplasting i opprettelse og redigering.</p>
${fixtures.map(([module, fields])=>`<form id="${module}-form"><h2>${module}</h2>${fields}<button type="submit">Lagre</button></form>`).join('')}
<div class="modal show" id="edit-modal"><h2>Rediger arrangement</h2><input name="event_image_url"><button type="button" id="modal-save" onclick="saves++">Lagre</button></div>
<form id="unrelated-form"><button type="submit">Lagre annet</button></form>
</main><script>${mock}</script><script>${script}</script><script>document.addEventListener('submit',e=>{e.preventDefault();saves++});</script></body></html>`);
const checks = `(async()=>{
 const check=(ok,m)=>{if(!ok)throw Error(m)};
 const tick=()=>new Promise(r=>setTimeout(r,30));
 const select=async(scope,type='image/png',bytes=10)=>{
   const input=document.querySelector(scope+' .shared-image-upload__file');
   const transfer=new DataTransfer();transfer.items.add(new File([new Uint8Array(bytes)],'photo.png',{type}));
   input.files=transfer.files;input.dispatchEvent(new Event('change',{bubbles:true}));await tick();return input;
 };
 const good={success:true,url:'https://i.ibb.co/ExampleHash/image.png',width:320,height:200};
 check(document.querySelectorAll('.shared-image-upload').length===11,'every module and modal enhanced');
 check(!document.querySelector('[name=general_hero_button_url]').dataset.sharedImageUpload,'ordinary URL not enhanced');
 check(!document.querySelector('label .shared-image-upload'),'no nested labels');
 check(!document.querySelector('[capture]'),'mobile photo library available');
 check(configRequests.length===0,'no configuration requests on render');
 await select('#sales-form');check(requests.length===1,'upload started');
 check(requests[0].body.get('module')==='sales'&&requests[0].body.get('company_id')==='company-a','correct module/company');
 check(!SharedImageUpload.canSave(document.querySelector('#sales-form')),'pending upload blocks save');
 let count=saves;document.querySelector('#sales-form button[type=submit]').click();check(saves===count,'form save blocked while pending');
 document.querySelector('#unrelated-form button').click();check(saves===count+1,'unrelated form unaffected');
 requests[0].respond(good);await tick();
 check(document.querySelector('[name=img_product]').value===good.url,'existing URL populated');
 check(SharedImageUpload.canSave(document.querySelector('#sales-form')),'save enabled after success');
 document.querySelector('#sales-form button[type=submit]').click();check(saves===count+2,'ordinary save works');
 await select('#events-form','text/plain');check(!SharedImageUpload.canSave(document.querySelector('#events-form')),'bad file reports error');
 const eventUrl=document.querySelector('#events-form input[name=event_image_url]');
 eventUrl.value='https://example.org/manual.jpg';eventUrl.dispatchEvent(new Event('input',{bubbles:true}));
 check(SharedImageUpload.canSave(document.querySelector('#events-form')),'manual URL clears upload error');
 await select('#events-form');let r=requests.at(-1);r.respond({success:false,message:'Testfeil'},502);await tick();
 check(!SharedImageUpload.canSave(document.querySelector('#events-form')),'failed upload blocks save');
 document.querySelector('#events-form .shared-image-upload__clear').click();
 check(SharedImageUpload.canSave(document.querySelector('#events-form')),'explicit discard clears error');
 await select('#edit-modal');r=requests.at(-1);count=saves;
 document.querySelector('#modal-save').click();check(saves===count,'AJAX onclick modal save blocked');
 const modal=document.querySelector('#edit-modal');
 modal.dispatchEvent(new Event('hide.bs.modal',{bubbles:true}));modal.classList.remove('show');await tick();
 check(r.readyState===4,'modal closure aborts request');
 modal.classList.add('show');modal.dispatchEvent(new Event('shown.bs.modal',{bubbles:true}));
 r.respond(good);await tick();check(modal.querySelector('[name=event_image_url]').value==='','late response cannot populate reopened editor');
 await select('#membership-form');r=requests.at(-1);document.querySelector('#membership-form').reset();await tick();
 r.respond(good);await tick();check(document.querySelector('[name=plan_image_url]').value==='','reset prevents stale completion');
 await select('#company-form');r=requests.at(-1);check(r.body.get('purpose')==='logo','logo processing requested');
 r.respond(good);await tick();
 const favicon=document.querySelector('[name=general_favicon_url]');
 const picker=favicon.nextElementSibling.querySelector('input[type=file]');
 const transfer=new DataTransfer();transfer.items.add(new File(['png'],'test.png',{type:'image/png'}));picker.files=transfer.files;picker.dispatchEvent(new Event('change',{bubbles:true}));await tick();
 r=requests.at(-1);check(r.body.get('purpose')==='favicon','favicon processing requested');
 r.respond(good);await tick();
 await select('#rental-form');r=requests.at(-1);
 check(document.querySelector('#rental-form .image-caption-input').value==='Behold bildetekst'&&document.querySelector('#rental-form input[type=radio]').checked,'rental gallery metadata preserved');
 document.querySelector('#rental-form .image-row button').click();await tick();
 check(!document.querySelector('#rental-form .shared-image-upload'),'no orphan upload after row removed');
 r.respond(good);await tick();
 const newInput=document.createElement('input');newInput.className='image-url-input';document.querySelector('#rental-form').append(newInput);await tick();
 check(newInput.dataset.sharedImageUpload==='ready','dynamic gallery row enhanced');
 await select('#reservation-form');r=requests.at(-1);document.body.dataset.companyId='company-b';r.respond(good);await tick();
 check(document.querySelector('#service-img-url').value==='','different company never receives URL');
 document.body.dataset.companyId='company-a';
 await select('#announcements-form');r=requests.at(-1);r.ontimeout();await tick();
 check(document.querySelector('#announcements-form .shared-image-upload__status').textContent.length>0&&!SharedImageUpload.canSave(document.querySelector('#announcements-form')),'timeout actionable and blocking');
 document.querySelector('#announcements-form .shared-image-upload__clear').click();
 check(document.documentElement.scrollWidth<=innerWidth,'no horizontal overflow');
 return {passed:true,width:innerWidth,controls:document.querySelectorAll('.shared-image-upload').length};
})()`;
chromiumFixture(fixture, checks, [390, 1280], {outputDir:dir}).catch(error=>{console.error(error);process.exitCode=1});
