const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');
if (typeof WebSocket === 'undefined') {
  const {spawnSync} = require('node:child_process');
  const child=spawnSync(process.execPath,['--experimental-websocket',__filename],{stdio:'inherit'});
  process.exit(child.status ?? 1);
}

const workspace = path.resolve(__dirname, '..');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'sales-admin-browser-'));
const phpMarkup = execFileSync('php', ['-r', 'include "modules/sales_content.php";'], {cwd:workspace, encoding:'utf8'});
const css = fs.readFileSync(path.join(workspace,'assets/sales-admin.css'),'utf8');
const js = fs.readFileSync(path.join(workspace,'assets/sales-admin.js'),'utf8');
const entityJs = fs.readFileSync(path.join(workspace,'assets/entity-form-fields.js'),'utf8');
const markup = phpMarkup.replace('<link rel="stylesheet" href="/assets/sales-admin.css">', '')
  .replace('<script src="/assets/entity-form-fields.js"></script>', '')
  .replace('<script src="/assets/sales-admin.js" defer></script>', '');
const fixture = path.join(dir,'sales-admin.html');
const stubs = `<script>
const product={id:'prod-1',name:'Leirdyr',description:'Liten figur',sku:'S-1842',barcode:'7031123',selling_price:'175.00',cost_price:'35.00',price_minor:17500,stock_quantity:8,reorder_level:2,is_active:1,is_backorder:0,visibility:'kiosk',has_fields:true,revision:'rev-1',updated_at:'2026-01-01 10:00:00',img_product:''};
let cart={id:'cart-1',revision:'r1',items:[],subtotal_minor:0,tax_minor:0,total_minor:0}, paid=false, paidVipps=false, paymentChecks=0, conflictNextSave=false, methodError='', calls=[];
window.triggerConflict=()=>{conflictNextSave=true};
let receiptEnabled=true,receiptQueueCount=0;
window.setReceiptEnabled=value=>{receiptEnabled=value};
window.setMethodError=value=>{methodError=value};
window.fetch=async (url,opts={})=>{
 if(String(url).includes('get_entity_form_fields.php'))return {ok:true,status:200,json:async()=>({success:true,csrf_token:'field-token',revision:'field-r1',fields:[],presets:[]})};
 if(String(url).includes('save_entity_form_fields.php'))return {ok:true,status:200,json:async()=>({success:true,revision:'field-r2',fields:JSON.parse(opts.body).fields})};
 const u=new URL(url,location.href), action=u.searchParams.get('action'), method=opts.method||'GET';
 calls.push({action,method,body:opts.body?JSON.parse(opts.body):null});
 let result={success:true};
 if(action==='bootstrap'||action==='products') result={...result,csrf_token:'stub-token',tax_registered:1,method_error:methodError,products:[product],methods:[{id:null,method_type:'cash',method_name:'Kontant',available:true},{id:'gw-1',method_type:'vipps',method_name:'Vipps',available:true}],cart,pending_order:null};
 if(action==='cart') result={...result,cart,pending_order:null};
 if(action==='fields') result={...result,fields:[{field_name:'farge',field_label:'Velg farge',field_type:'select',is_required:1,options:['Blå','Rød'],sort_order:0}]};
 if(action==='add'){cart={...cart,revision:'r2',items:[{id:'line-1',product_id:'prod-1',name:'Leirdyr',quantity:1,notes:opts.body?JSON.parse(opts.body).notes:'',form_data:{farge:'Blå'},unit_price_minor:17500,total_minor:17500,subtotal_minor:14000,tax_minor:3500,available:true}],subtotal_minor:14000,tax_minor:3500,total_minor:17500};result={...result,cart};}
 if(action==='quantity'||action==='remove'){result={...result,cart};}
 if(action==='checkout'){
  if(JSON.parse(opts.body).payment_method==='vipps'){result={...result,order:{id:'order-v',order_number:'S-0104',created_at:'2026-01-05T12:01:00Z',status:'pending',payment_method:'vipps',payment_status:'awaiting',total_minor:17500,subtotal_minor:14000,tax_minor:3500,items:[]}};}
  else {paid=true;result={...result,order:{id:'order-1',order_number:'S-0103',created_at:'2026-01-05T12:00:00Z',status:'paid',payment_method:'cash',payment_status:'paid',total_minor:17500,subtotal_minor:14000,tax_minor:3500,received_minor:20000,change_minor:2500,items:[{id:'line-1',product_id:'prod-1',description:'Leirdyr',sku:'S-1842',quantity:1,total_minor:17500,unit_price_minor:17500}]}};cart={id:null,revision:'r3',items:[],subtotal_minor:0,tax_minor:0,total_minor:0};}
 }
 if(action==='payment_status'){paymentChecks++;if(paymentChecks>1)paidVipps=true;result={...result,order:{id:'order-v',order_number:'S-0104',created_at:'2026-01-05T12:01:00Z',status:paymentChecks>1?'paid':'unknown',payment_method:'vipps',payment_status:paymentChecks>1?'paid':'unknown',total_minor:17500,subtotal_minor:14000,tax_minor:3500,items:[]}};}
 if(action==='cancel_payment'){cart={id:'cart-1',revision:'r4',items:[{id:'restored-line',product_id:'prod-1',name:'Leirdyr',quantity:1,notes:'Beholdt merknad',form_data:{farge:'Blå'},unit_price_minor:17500,total_minor:17500,subtotal_minor:14000,tax_minor:3500,available:true}],subtotal_minor:14000,tax_minor:3500,total_minor:17500};result={...result,order:{id:'order-v',order_number:'S-0104',status:'cancelled',payment_method:'vipps',payment_status:'cancelled',total_minor:17500}};}
 if(action==='next_customer'){result={...result,cart};}
 if(action==='orders')result={...result,orders:paid||paidVipps?[{id:'order-1',order_number:'S-0103',created_at:'2026-01-05T12:00:00Z',total_minor:17500,payment_status:'paid',payment_method:'cash'}]:[]};
 if(action==='order')result={...result,order:{id:'order-1',order_number:'S-0103',created_at:'2026-01-05T12:00:00Z',payment_method:'cash',payment_status:'paid',total_minor:17500,subtotal_minor:14000,tax_minor:3500,received_minor:20000,change_minor:2500,items:[{description:'Leirdyr',quantity:1,total_minor:17500}]}};
 if(action==='product_save'){
  const body=JSON.parse(opts.body);
  if(conflictNextSave){conflictNextSave=false;return {ok:false,status:409,json:async()=>({success:false,message:'Revision conflict'})};}
  result={...result,product:{...product,...body,revision:'rev-2'}};
 }
 if(action==='stock')result={...result,product:{...product,stock_quantity:JSON.parse(opts.body).quantity}};
 if(action==='receipt_email'){
  if(!receiptEnabled)return {ok:false,status:403,json:async()=>({success:false,message:'Krever automail_sales_order = 1'})};
  receiptQueueCount++;result={success:true,queued:true,queue_id:'queue-fixture',message:'Kvitteringen er lagt i e-postkøen'};
 }
 if(result.order)Object.assign(result.order,{tenant_admin_id:'admin-fixture',tenant_admin_name:'Testkasserer',receipt_email_enabled:receiptEnabled,receipt_email_reason:null});
 return {ok:true,status:200,json:async()=>result};
};
</script>`;
fs.writeFileSync(fixture,`<!doctype html><html lang="nb"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{box-sizing:border-box}.visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}button,input,select,textarea{font:inherit}button{cursor:pointer}.form-control,.form-select{display:block;width:100%;padding:.45rem .7rem;border:1px solid #cbd9e2;border-radius:8px;min-height:38px}.form-control:focus,.form-select:focus{outline:2px solid #087f8c}.btn{padding:.45rem .7rem;border:1px solid #cbd9e2;border-radius:8px}.btn-primary{color:white;background:#163b5c}.btn-success{color:white;background:#087f8c}.btn-outline-primary{color:#163b5c;background:white}.btn-outline-secondary{color:#53666f;background:white}.btn-outline-danger{color:#a63f3f;background:white}.input-group{display:flex}.input-group .form-control{min-width:0}.input-group-text{padding:.45rem;border:1px solid #cbd9e2;background:#edf2f4}.d-none{display:none!important}.d-block{display:block!important}.text-end{text-align:right}.table{width:100%;border-collapse:collapse}.sales-dialog-open{overflow:hidden}${css}</style></head><body><main class="dashboard-module-theme">${stubs}${markup}<script>${entityJs}</script><script>${js}</script></main></body></html>`);

const checks = `(async()=>{
 await new Promise(resolve=>setTimeout(resolve,80));
 const root=document.querySelector('#sales-admin');
 const overflow=document.documentElement.scrollWidth>innerWidth+1;
 const offenders=[...document.querySelectorAll('body *')].map(e=>({tag:e.tagName,id:e.id,cls:String(e.className).slice(0,80),right:Math.round(e.getBoundingClientRect().right),left:Math.round(e.getBoundingClientRect().left),width:Math.round(e.getBoundingClientRect().width),anc:[e.parentElement,e.parentElement?.parentElement,e.parentElement?.parentElement?.parentElement].map(p=>p?{cls:String(p.className).slice(0,55),left:Math.round(p.getBoundingClientRect().left),right:Math.round(p.getBoundingClientRect().right),width:Math.round(p.getBoundingClientRect().width)}:null)})).filter(e=>e.right>innerWidth+2).slice(0,8);
 const card=document.querySelector('[data-product="prod-1"]');
 if(!root||!card||overflow) return {passed:false,reason:'render/overflow',width:innerWidth,scroll:document.documentElement.scrollWidth,offenders};
 card.click(); await new Promise(resolve=>setTimeout(resolve,5));
 const select=document.querySelector('#sales-dialog select[name="farge"]');
 if(!select)return {passed:false,reason:'custom fields missing'};
 select.value='Blå'; document.querySelector('#sales-customize-add').click();
 await new Promise(resolve=>setTimeout(resolve,50));
 if(!document.querySelector('#sales-cart-lines').textContent.includes('Leirdyr'))return {passed:false,reason:'add mutation'};
  const tender=document.querySelector('#sales-tendered'),checkout=document.querySelector('#sales-checkout');
  tender.value='174.99';tender.dispatchEvent(new Event('input',{bubbles:true}));
  const belowBlocked=checkout.disabled;
  tender.value='175.00';tender.dispatchEvent(new Event('input',{bubbles:true}));
  const exactAllowed=!checkout.disabled&&document.querySelector('#sales-change').textContent.includes('0,00');
  tender.value='250.00';tender.dispatchEvent(new Event('input',{bubbles:true}));
  const typedChange=document.querySelector('#sales-change').textContent.includes('75,00');
  document.querySelector('[data-cash="10000"]').click();
  const denominationBelowBlocked=checkout.disabled&&tender.value==='100.00';
  document.querySelector('[data-cash="20000"]').click();
  const denominationChange=!checkout.disabled&&tender.value==='200.00'&&document.querySelector('#sales-change').textContent.includes('25,00');
 document.querySelector('#sales-checkout').click();
 await new Promise(resolve=>setTimeout(resolve,5));
 document.querySelector('#sales-confirm-cash').click();
 await new Promise(resolve=>setTimeout(resolve,80));
   const receiptText=(document.querySelector('.sales-receipt')?.textContent||'').replace(/\\s/g,'');
  const receiptCash=receiptText.includes('200,00kr')&&receiptText.includes('25,00kr');
  if(!document.querySelector('.sales-receipt')||document.querySelector('#sales-cart-count').textContent!=='0'||!receiptCash)return {passed:false,reason:'cash receipt metadata',width:innerWidth,receipt:receiptText,receiptCash};
  if(!document.querySelector('.sales-receipt').textContent.includes('Registrert av: Testkasserer'))return {passed:false,reason:'receipt actor label',width:innerWidth};
  document.querySelector('#sales-email-receipt').click();
  const mailForm=document.querySelector('#sales-receipt-email-form');
  mailForm.elements.sales_receipt_recipient.value='receipt@example.invalid';
  window.setReceiptEnabled(false);mailForm.querySelector('button').click();await new Promise(resolve=>setTimeout(resolve,30));
  if(!mailForm.textContent.includes('automail_sales_order'))return {passed:false,reason:'email switch enforced after dialog opened',width:innerWidth};
  window.setReceiptEnabled(true);mailForm.querySelector('button').click();await new Promise(resolve=>setTimeout(resolve,30));
  const mailCalls=calls.filter(c=>c.action==='receipt_email');
  if(receiptQueueCount!==1||!mailForm.textContent.includes('lagt i e-postkøen')||mailCalls.length!==2||mailCalls[0].body.mutation_id!==mailCalls[1].body.mutation_id)return {passed:false,reason:'receipt queue/retry identity',width:innerWidth};
  if(document.documentElement.scrollWidth>innerWidth)return {passed:false,reason:'receipt email horizontal overflow',width:innerWidth};
 document.querySelector('[data-close-dialog]').click();
 card.click(); await new Promise(resolve=>setTimeout(resolve,5));
 document.querySelector('#sales-dialog select[name="farge"]').value='Blå';
 document.querySelector('#sales-customize-add').click(); await new Promise(resolve=>setTimeout(resolve,50));
 document.querySelector('#sales-method').value='vipps';document.querySelector('#sales-method').dispatchEvent(new Event('change',{bubbles:true}));
 document.querySelector('#sales-phone').value='41234567';document.querySelector('#sales-phone').dispatchEvent(new Event('input',{bubbles:true}));
 document.querySelector('#sales-checkout').click();await new Promise(resolve=>setTimeout(resolve,5));
 document.querySelector('#sales-confirm-vipps').click();await new Promise(resolve=>setTimeout(resolve,50));
 const status=document.querySelector('#sales-check-status');if(!status)return {passed:false,reason:'Vipps pending status',width:innerWidth};
  document.querySelector('#sales-cancel-payment').click();await new Promise(resolve=>setTimeout(resolve,40));
  const restored=document.querySelector('#sales-cart-lines').textContent.includes('Beholdt merknad')&&document.querySelector('#sales-cart-lines').textContent.includes('Blå');
  const nextBlocked=document.querySelector('#sales-next-customer').hidden;
  document.querySelector('#sales-checkout').click();await new Promise(resolve=>setTimeout(resolve,5));
  document.querySelector('#sales-confirm-vipps').click();await new Promise(resolve=>setTimeout(resolve,50));
 document.querySelector('#sales-check-status').click();await new Promise(resolve=>setTimeout(resolve,30));
 const unknown=document.querySelector('#sales-payment-status').textContent.includes('Ukjent');
 document.querySelector('#sales-check-status').click();await new Promise(resolve=>setTimeout(resolve,80));
 if(!document.querySelector('.sales-receipt'))return {passed:false,reason:'Vipps resolution',width:innerWidth};
 document.querySelector('[data-close-dialog]').click();
 document.querySelector('#sales-refresh-orders').click();await new Promise(resolve=>setTimeout(resolve,30));
 const ledger=document.querySelector('#sales-orders').textContent.includes('S-0103');
 document.querySelector('[data-order="order-1"]').click();await new Promise(resolve=>setTimeout(resolve,40));
 if(!document.querySelector('.sales-receipt'))return {passed:false,reason:'ledger detail',width:innerWidth};
 document.querySelector('[data-close-dialog]').click();
 document.querySelector('#sales-items').style.display='block';document.querySelector('#sales-new-product').click();
 const productForm=document.querySelector('#sales-product-form');productForm.elements.name.value='Testvare';productForm.elements.selling_price.value='44.50';
 document.querySelector('#sales-save-product').click();await new Promise(resolve=>setTimeout(resolve,50));
 const row=document.querySelector('[data-edit-product="prod-1"]');if(!row)return {passed:false,reason:'product save',width:innerWidth};
  row.click();await new Promise(resolve=>setTimeout(resolve,5));
  const editForm=document.querySelector('#sales-product-form');editForm.elements.name.value='Konfliktutkast';window.triggerConflict();
  document.querySelector('#sales-save-product').click();await new Promise(resolve=>setTimeout(resolve,40));
  const draftKept=document.querySelector('#sales-product-form')?.elements.name.value==='Konfliktutkast'&&document.querySelector('#sales-product-error').textContent.includes('Utkastet');
  const revisionSent=calls.some(c=>c.action==='product_save'&&c.body?.id==='prod-1'&&typeof c.body.expected_revision==='string'&&c.body.expected_revision.length>0);
  document.querySelector('[data-close-dialog]').click();
 document.querySelector('[data-stock-product="prod-1"]').click();
 const stockForm=document.querySelector('#sales-stock-form');stockForm.elements.quantity.value='11';stockForm.elements.reason.value='Testopptelling';
 document.querySelector('#sales-save-stock').click();await new Promise(resolve=>setTimeout(resolve,50));
 document.querySelector('[data-edit-product="prod-1"]').click();document.querySelector('#sales-open-fields').click();await new Promise(resolve=>setTimeout(resolve,40));
 const addField=document.querySelector('#sales-fields-host [data-add]');if(!addField)return {passed:false,reason:'field editor',width:innerWidth};
  addField.click();window.confirm=()=>false;document.querySelector('#sales-dialog [data-close-dialog]').click();
  const dirtyGuardStayedOpen=!document.querySelector('#sales-dialog').hidden;
  document.querySelector('#sales-fields-host [data-save]').click();await new Promise(resolve=>setTimeout(resolve,50));
  const editorClosed= document.querySelector('#sales-fields-host').hidden&&!document.querySelector('#sales-dialog').hidden;
  document.querySelector('#sales-dialog [data-close-dialog]').click();
  window.setMethodError('Vipps setup unavailable');document.querySelector('#sales-refresh-products').click();await new Promise(resolve=>setTimeout(resolve,30));
  document.querySelector('#sales-new-product').click();
  const onlineBlocked=document.querySelector('#sales-product-form [name="visibility"] option[value="online"]').disabled;
  document.querySelector('#sales-dialog [data-close-dialog]').click();
  return {passed:belowBlocked&&exactAllowed&&typedChange&&denominationBelowBlocked&&denominationChange&&restored&&nextBlocked&&unknown&&ledger&&draftKept&&revisionSent&&dirtyGuardStayedOpen&&editorClosed&&onlineBlocked&&document.querySelector('#sales-dialog').hidden&&document.documentElement.scrollWidth<=innerWidth+1,reason:'cash denominations/tendered/cancel restore/revision conflict/field dirty guard',width:innerWidth};
})()`;

chromiumFixture(fixture,checks,[320,390,768,1024,1280]).finally(()=>setTimeout(()=>{try{fs.rmSync(dir,{recursive:true,force:true})}catch(_){}},350));
