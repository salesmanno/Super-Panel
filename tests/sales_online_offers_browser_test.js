// Real PHP markup and production scripts; isolated network responses, no app auth bypass.
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'sales-online-offers-'));
const markup = execFileSync('php', ['-r', 'include "modules/sales_content.php";'], {encoding:'utf8'})
  .replace(/<script\b[^>]*src=[^>]*><\/script>/g, '').replace(/<link\b[^>]*>/g, '');
const stubs = `
let saves=0, failPreview=false;
let products=[
 {id:'online',name:'Klubbskjorte',description:'Myk bomull',selling_price:'250.00',price_minor:22500,is_active:1,visibility:'online',stock_quantity:9,revision:'r1'},
 {id:'kiosk',name:'Kaffe',selling_price:'25.00',price_minor:2500,is_active:1,visibility:'kiosk',revision:'r2'},
 {id:'inactive',name:'Gammel vare',selling_price:'70.00',price_minor:7000,is_active:0,visibility:'both',revision:'r3'}
];
window.fetch=async(url,options={})=>{
 const action=new URL(url,'https://fixture.invalid').searchParams.get('action');
 const result=data=>({ok:true,status:200,json:async()=>({success:true,...data})});
 if(action==='online_preview') {
   if(failPreview)return {ok:false,status:503,json:async()=>({success:false,message:'Kunne ikke hente firmaprofil'})};
   return result({company:{name:'Danseklubben',header_bg_color:'#2d5598',header_text_color:'#ffffff',header_height:'88px',logo_height:'42px',font_family:'Georgia, serif'},warnings:[]});
 }
 if(action==='bootstrap')return result({products,methods:[],tax_registered:0,csrf_token:'fixture',cart:{items:[],total_minor:0}});
 if(action==='orders')return result({orders:[]});
 if(action==='register_state')return result({register_state:{registers:[],cashier_name:'Test'}});
 if(action==='product_save'){
   const data=JSON.parse(options.body);saves++;
   if(data.expected_revision!=='r1')throw new Error('Expected revision missing');
   products=products.map(p=>p.id===data.id?{...p,...data,revision:'r4',price_minor:Number(data.selling_price)*100}:p);
   return result({product:products.find(p=>p.id===data.id)});
 }
 throw new Error('Unexpected fixture request '+action);
};
window.alert=message=>{throw new Error(message)};
`;
const checks = `(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const wait=()=>new Promise(resolve=>setTimeout(resolve,80));
 const root=document.querySelector('#sales-online-offers');
 document.querySelectorAll('.subtab-content').forEach(el=>el.style.display='none');
 root.style.display='block';
 document.dispatchEvent(new CustomEvent('subtab-changed',{detail:{module:'sales',subtab:'sales-online-offers'}}));
 await wait();
 check(root.querySelectorAll('[data-soo-product]').length===1,'Only active online products shown');
 check(root.textContent.includes('Danseklubben'),'Real company name rendered');
 check(getComputedStyle(root.querySelector('.soo-site-header')).minHeight==='88px','Company header height honored');
 check(root.querySelector('[data-soo-product]').textContent.includes('225'),'Effective price shown');
 root.querySelector('#soo-show-hidden').click();
 check(root.querySelectorAll('[data-soo-product]').length===3,'Kiosk and inactive accessible explicitly');
 root.querySelector('[data-soo-product="online"]').click();
 const form=document.querySelector('#sales-product-form');
 check(form && !document.querySelector('#sales-dialog').hidden,'Card opens existing product editor');
 check(form.elements.name.value==='Klubbskjorte','Editor populated with selected product');
 form.elements.name.value='Ny klubbskjorte';
 // Existing online payment guard deliberately retained; switch to kiosk to test save without gateway.
 form.elements.visibility.value='kiosk';
 form.elements.visibility.dispatchEvent(new Event('change',{bubbles:true}));
 document.querySelector('#sales-save-product').click();
 await wait();
 check(saves===1,'Save uses existing product_save endpoint');
 check(document.querySelector('#sales-dialog').hidden,'Successful save closes editor');
 check(root.textContent.includes('Ny klubbskjorte'),'Simulator reflects saved changes');
 root.querySelector('#soo-create').click();
 check(document.querySelector('#sales-product-form').elements.name.value==='','Ny vare opens existing create form');
 document.querySelector('[data-close-dialog]').click();
 root.querySelector('[data-soo-viewport="phone"]').click();
 check(root.querySelector('.soo-phone-view'),'Phone preview selectable');
 check(document.documentElement.scrollWidth<=innerWidth,'No document overflow at '+innerWidth);
 failPreview=true;
 document.dispatchEvent(new CustomEvent('subtab-changed',{detail:{module:'sales',subtab:'sales-online-offers'}}));
 await wait();
 check(root.querySelector('#soo-state').textContent.includes('Kunne ikke hente firmaprofil'),'Failure shown instead of silent demo data');
 failPreview=false;
 root.querySelector('[data-soo-retry]').click();await wait();
 check(!root.querySelector('#soo-preview').hidden,'Retry restores live preview');
 root.querySelector('[data-soo-viewport="desktop"]').click();
 return {passed:true,width:innerWidth};
})()`;
(async()=>{
 const css=['sales-admin.css','sales-online-offers.css'].map(file=>fs.readFileSync('assets/'+file,'utf8')).join('\n');
 const js=['sales-admin.js','sales-online-offers.js'].map(file=>fs.readFileSync('assets/'+file,'utf8')).join('\n');
 const fixture=path.join(dir,'fixture.html');
 fs.writeFileSync(fixture,`<!doctype html><html lang="nb"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0} [hidden]{display:none!important} ${css}</style><body>${markup}<script>${stubs}</script><script>${js}</script></body></html>`);
 await chromiumFixture(fixture,checks,[390,1280],{outputDir:dir});
 console.log('Sales online offers tests passed; fixture screenshots:',dir);
})().catch(error=>{console.error(error);process.exitCode=1});
