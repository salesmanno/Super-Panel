// Real PHP controller, isolated MySQL, real triggers; no production requests.
const fs=require('node:fs'),path=require('node:path'),chromiumFixture=require('./chromium_fixture');
const [port,temp]=process.argv.slice(2),outputDir=path.join(temp,'compact-dashboard');
fs.mkdirSync(outputDir,{recursive:true});
const checks=`(async()=>{
 const $=s=>document.querySelector(s), calls=window.apiCalls;
 const assert=(ok,label)=>{if(!ok)throw new Error(label);};
 const wait=async(fn,label)=>{for(let n=0;n<500;n++){if(fn())return;await new Promise(r=>setTimeout(r,20));}throw new Error(label+' '+$('#sales-dialog-body')?.textContent+' '+$('#sales-cart-error')?.textContent);};
 const call=async(action,data)=>{
   const res=await fetch('/api/sales.php?action='+action,data?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...data,csrf_token:'fixture-token'})}:undefined);
   const json=await res.json();return {status:res.status,...json};
 };
 await wait(()=>calls.some(c=>c.action==='bootstrap')&&$('#sales-register-gate')&&!$('#sales-register-gate').hidden,'VAT1 start gate visible');
 assert([...document.querySelectorAll('[data-product]')].every(b=>b.disabled),'VAT1 products disabled before open');
 assert($('#sales-scanner').disabled,'Scanner disabled before open');
 const cart=await call('cart'),product=(await call('products')).products.find(p=>p.name==='MVA-vare');
 const direct=await call('add',{product_id:product.id,quantity:1,revision:cart.cart.revision,mutation_id:crypto.randomUUID()});
 assert(!direct.success,'Direct API cannot bypass start');
 $('#sales-quick-reports').click();
 await wait(()=>!$('#sales-dialog').hidden && ($('#sales-report-register')||$('#sales-open-register-setup')),'Reports always show a visible dialog');
 $('#sales-dialog [data-close-dialog]').click();
 $('#sales-quick-settlement').click();
 await wait(()=>$('#sales-open-register-setup'),'Closed register settlement explains setup');
 $('#sales-open-register-setup').click();
 await wait(()=>$('#sales-register-create'),'Register setup loaded');
 const stateCall=calls.filter(c=>c.action==='register_state').at(-1),state=stateCall.result;
 const selected=state.selected_register_id||state.registers.find(r=>r.status==='active').id;
 if(!state.selected_register_id){
   document.querySelector('[data-register-select="'+selected+'"]').click();
   await wait(()=>$('#sales-register-open-form'),'Selected register open form');
 }
 await wait(()=>$('#sales-register-open-form')&&!$('#sales-register-open-form button').disabled,'Opening ready');
 const form=$('#sales-register-open-form');form.elements.opening_float.value='0.00';form.requestSubmit();
 await wait(()=>!$('#sales-register-gate').hidden===false && $('#sales-dialog-body').textContent.includes('Periode'),'Opened register removes gate');
 $('#sales-dialog [data-close-dialog]').click();
 await wait(()=>document.querySelector('[data-product="'+product.id+'"]')&&!document.querySelector('[data-product="'+product.id+'"]').disabled,'Products unlocked');
 assert(getComputedStyle($('#sales-search')).boxShadow==='none','Flat search has no inset shadow');
 assert(getComputedStyle($('#sales-scanner')).boxShadow==='none','Flat scanner has no inset shadow');
 const catalog=$('.sales-catalog').getBoundingClientRect();
 assert(catalog.height>=230,'Catalogue remains substantial with one product');
 const layout=()=>{
   assert(document.documentElement.scrollWidth<=innerWidth+1,'No horizontal overflow');
   assert($('#sales-quick-reports').getBoundingClientRect().width>=44,'Report touch action');
   assert($('#sales-quick-settlement').getBoundingClientRect().width>=44,'Settlement touch action');
 };
 layout();$('#sales-fullscreen').click();
 await wait(()=>$('#sales-admin').classList.contains('sales-fullscreen'),'Fullscreen');
 layout();assert($('#sales-admin').contains($('#sales-quick-reports')),'Reports remain inside fullscreen root');
 $('#sales-fullscreen-exit').click();await wait(()=>!$('#sales-admin').classList.contains('sales-fullscreen'),'Fullscreen restored');
 document.querySelector('[data-product="'+product.id+'"]').click();
 await wait(()=>document.querySelector('[data-line-qty]')&&!document.querySelector('[data-line-qty]').disabled,'Cart updated');
 $('#sales-quick-settlement').click();
 await wait(()=>$('#sales-register-report-form'),'Settlement input');
 $('#sales-register-report-form').elements.counted_cash.value='0.00';$('#sales-register-report-submit').click();
 await wait(()=>$('#sales-register-action-error')&&!$('#sales-register-action-error').hidden,'Open cart blocks Z visibly');
 $('#sales-dialog [data-close-dialog]').click();
 document.querySelector('[data-remove-line]').click();
 await wait(()=>!document.querySelector('[data-line-qty]'),'Cart emptied');
 $('#sales-quick-reports').click();
 await wait(()=>$('#sales-create-report')&&!$('#sales-create-report').disabled,'X available');
 $('#sales-create-report').click();const xStart=calls.length;$('#sales-register-report-submit').click();
 await wait(()=>$('#sales-register-print')&&calls.slice(xStart).some(c=>c.action==='register_report'&&c.result.success)
   &&calls.slice(xStart).some(c=>c.action==='register_state'),'X document and refreshed state');
 await new Promise(requestAnimationFrame);
 $('#sales-dialog [data-close-dialog]').click();
 await wait(()=>!$('#sales-quick-settlement').disabled,'Ready for Z');
 $('#sales-quick-settlement').click();
 await wait(()=>$('#sales-register-report-form'),'Z form');
 $('#sales-register-report-form').elements.counted_cash.value='0.00';$('#sales-register-report-submit').click();
 await wait(()=>calls.filter(c=>c.action==='register_report'&&c.result.success&&c.result.document.document_type==='z_report').length>0,'Z saved');
 await wait(()=>!$('#sales-register-gate').hidden,'After Z start gate returns');
 $('#sales-dialog [data-close-dialog]').click();
 $('#sales-quick-reports').click();
 await wait(()=>document.querySelector('[data-open-register-document]'),'Closed period report history');
 assert($('#sales-create-report').disabled,'Closed period cannot create another report');
 document.querySelector('[data-open-register-document]').click();
 await wait(()=>$('#sales-register-print'),'Historical report actually rendered');
 $('#sales-dialog [data-close-dialog]').click();
 layout();
 return {passed:true,width:innerWidth,reason:'VAT1 gate/direct API, opening, fullscreen, flat fields, open-cart Z block, X, Z, closed report history'};
})()`;
chromiumFixture('http://127.0.0.1:'+port+'/sales-e2e.html?company=b',checks,[320,390,768,1280],{outputDir})
 .catch(e=>{console.error(e);process.exitCode=1;});
