// Actual production JS/CSS with disposable API responses, no authentication bypass.
const fs=require('node:fs'),path=require('node:path'),os=require('node:os');
const run=require('./chromium_fixture');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'events-checkin-ui-'));
const logo='data:image/webp;base64,'+fs.readFileSync(path.join(__dirname,'../assets/vipps-payment-logo.webp')).toString('base64');
const source=fs.readFileSync(path.join(__dirname,'../assets/events-checkin.js'),'utf8')
    .replace('/assets/vipps-payment-logo.webp',logo);
const css=fs.readFileSync(path.join(__dirname,'../assets/events-checkin.css'),'utf8');
const people=Array.from({length:8},(_,index)=>({
    id:'p'+index,customer_name:'Deltaker '+index,first_name:'Deltaker',last_name:String(index),
    customer_phone:index===0?'99999999':'',customer_email:'test@example.invalid',
    ticket_price:index===7?0:100,payment_status:index===6?'paid':'pending',payment_method:index===6?'cash':null,
    has_valid_ledger:index===6,checked_in:0,barcode:'BAR'+index
}));
fs.writeFileSync(path.join(dir,'index.html'),`<!doctype html><meta charset="utf-8"><style>
body{margin:0}.modal{display:none;position:fixed;inset:0;z-index:1050;overflow:auto}.modal.show{display:block}
.modal-dialog{margin:1.75rem auto;position:relative}.modal-content{position:relative;height:100%;overflow:auto}button,input{box-sizing:border-box}
${css}</style><button id="open-event">Åpne</button><script>
const testPeople=${JSON.stringify(people)}; let pending=null, rejected=false, calls=[];
window.addEventListener('unhandledrejection',event=>{window.fixtureError=String(event.reason?.stack||event.reason)});
window.bootstrap={Modal:{getOrCreateInstance:root=>({
 show:()=>root.classList.add('show'),
 hide:()=>{root.classList.remove('show');root.dispatchEvent(new Event('hidden.bs.modal'))}
})}};
window.fetch=async(url,options={})=>{
 if(url.includes('get_entity_form_fields'))return{ok:true,json:async()=>({success:true,csrf_token:'fixture-token'})};
 const input=JSON.parse(options.body); calls.push(input);
 let result={success:true};
 if(input.action==='load')result={...result,event:{id:'event',title:'Julebord',start_date:'2020-12-06',start_time:'18:00:00',end_time:'23:00:00',ticket_price:100,editable:true},people:structuredClone(testPeople),methods:{cash:true,vipps:true}};
 if(input.action==='commit')input.participant_ids.forEach(id=>{const p=testPeople.find(p=>p.id===id);p.checked_in=1;p.has_valid_ledger=true});
 if(input.action==='start_vipps'){pending=input;result={...result,reference:'fixture-reference',state:'CREATED',expires_at:Date.now()/1000+80}};
 if(input.action==='cancel_vipps'){pending=null;result.state='ABORTED'}
 if(input.action==='status_vipps'){result.state=rejected?'ABORTED':'COMPLETED';if(!rejected)pending.participant_ids.forEach(id=>{testPeople.find(p=>p.id===id).checked_in=1})}
 return{ok:true,json:async()=>result};
};
${source}
document.getElementById('open-event').onclick=()=>EventCheckinDashboard.open('event');
</script>`);
const checks=`(async()=>{
 const wait=()=>new Promise(r=>setTimeout(r,80)),assert=(ok,msg)=>{if(!ok)throw Error(msg)};
 const click=selector=>{const el=document.querySelector(selector);assert(el,'Missing '+selector);el.click()};
 click('#open-event');for(let i=0;i<100&&!document.querySelector('[data-ec-search]');i++)await wait();
 assert(document.querySelectorAll('.uc-attendee').length===8,'Waiting roster loads actual component: rows='+document.querySelectorAll('.uc-attendee').length+'; '+document.querySelector('[data-ec-message]')?.textContent+'; '+window.fixtureError);
 const search=document.querySelector('[data-ec-search]');
 assert(!search.hasAttribute('name')&&!search.id&&search.autocomplete==='off','Search avoids autofill identifiers');
 click('[data-ec-checkin="p1"]');await wait();
 assert(document.querySelector('[data-ec-vipps]').disabled,'Missing phone disables Vipps');
 click('[data-ec-checkout-close]');click('[data-ec-checkin="p0"]');await wait();
 assert(!document.querySelector('input[type=radio]')&&document.querySelector('[data-ec-cash]'),'Separate payment actions');
 click('[data-ec-more]');await wait();
 for(let n=1;n<=4;n++){click('[data-ec-extra="p'+n+'"]');await wait()}
 assert(document.querySelector('[data-ec-extra="p5"]').disabled,'Five total limit');
 assert(document.querySelector('.uc-payment-amount').textContent.includes('500'),'Combined amount');
 assert(document.querySelectorAll('[data-ec-group-search]')[0].getAttribute('name')===null,'Group search avoids autofill identifiers');
 click('[data-ec-vipps]');await wait();
 assert(testPeople.every(p=>!p.checked_in),'Sending does not check in');
 assert(calls.at(-1).payer_id==='p0'&&calls.at(-1).participant_ids.length===5,'Original payer submitted');
 click('[data-ec-payment-status]');await wait();await wait();
 assert(document.querySelectorAll('.uc-attendee').length===5&&document.querySelector('[data-ec-tab="checked"]').getAttribute('aria-selected')==='true','Confirmed group moves to checked list');
 click('[data-ec-tab="waiting"]');click('[data-ec-checkin="p6"]');await wait();await wait();
 assert(!document.querySelector('#ec-checkout'),'Prepaid checks in directly');
 click('[data-ec-tab="waiting"]');click('[data-ec-checkin="p7"]');await wait();await wait();
 assert(!document.querySelector('#ec-checkout'),'Free checks in directly');
 click('[data-ec-tab="waiting"]');click('[data-ec-checkin="p5"]');await wait();
 assert(document.querySelector('.uc-payment-amount').textContent.includes('100'),'Payment amount visible');
 assert(document.documentElement.scrollWidth<=innerWidth+1,'No horizontal overflow');
 const dialog=document.querySelector('.uc-dialog').getBoundingClientRect();
 assert(dialog.left>=0&&dialog.right<=innerWidth+1,'Payment dialog fits viewport');
 return{passed:true,width:innerWidth,groupSize:5};
})()`;
run(path.join(dir,'index.html'),checks,[390,1280],{outputDir:dir})
    .catch(error=>{console.error(error);process.exitCode=1});
