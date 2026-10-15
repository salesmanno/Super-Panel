// Real markup, controller, MySQL and ledger. Only Vipps transport is simulated.
const path=require('node:path'),fs=require('node:fs');
const chromiumFixture=require('./chromium_fixture');
if(typeof WebSocket==='undefined'){
  const r=require('node:child_process').spawnSync(process.execPath,['--experimental-websocket',__filename,...process.argv.slice(2)],{stdio:'inherit'});
  process.exit(r.status??1);
}
const [port,temp]=process.argv.slice(2),outputDir=path.join(temp,'vipps-http-browser');
fs.mkdirSync(outputDir,{recursive:true});
const checks=`(async()=>{
  const wait=async(fn,label)=>{for(let i=0;i<1000;i++){if(fn())return;await new Promise(r=>setTimeout(r,20));}throw new Error(label+': '+document.querySelector('#sales-cart-error')?.textContent);};
  const assert=(v,label)=>{if(!v)throw new Error(label);};
  await wait(()=>!document.querySelector('#sales-new-product').disabled,'Bootstrap');
  if(!document.querySelector('#sales-next-customer').hidden){
    document.querySelector('#sales-next-customer').click();
    await wait(()=>document.querySelector('#sales-next-customer').hidden,'Next customer after recovered sale');
  }
  document.querySelector('#sales-items').style.display='block';
  document.querySelector('#sales-new-product').click();
  const form=document.querySelector('#sales-product-form');
  form.elements.name.value='Vipps HTTP '+innerWidth;
  form.elements.selling_price.value='100';
  form.elements.stock_quantity.value='3';
  document.querySelector('#sales-save-product').click();
  await wait(()=>document.querySelector('#sales-dialog').hidden,'Product saved through API');
  const saved=window.apiCalls.filter(c=>c.action==='product_save'&&c.result.success).pop().result.product;
  document.querySelector('[data-product="'+saved.id+'"]').click();
  await wait(()=>document.querySelector('#sales-cart-count').textContent==='1','Product added through API');
  document.querySelector('[data-method-key="gateway-a"]').click();
  await wait(()=>document.querySelector('#sales-vipps-phone'),'Phone modal');
  document.querySelector('#sales-vipps-phone').value='123';
  document.querySelector('#sales-vipps-form').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
  await wait(()=>document.querySelector('#sales-vipps-error')&&!document.querySelector('#sales-vipps-error').hidden,'Invalid phone stays editable');
  const phone=document.querySelector('#sales-vipps-phone');phone.value='49 99 99 99';
  document.querySelector('#sales-vipps-form').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
  await wait(()=>window.apiCalls.some(c=>c.action==='checkout'&&c.result.order?.payment_status==='pending'),'Actual pending order');
  const created=window.apiCalls.filter(c=>c.action==='checkout'&&c.result.order).pop().result.order;
  assert(Date.parse(created.expires_at)>Date.parse(created.server_time),'Server owns persisted deadline');
  await wait(()=>document.querySelector('.sales-receipt'),'Automatic early confirmation without clicking status');
  const checked=window.apiCalls.filter(c=>c.action==='payment_status').pop().result.order;
  assert(checked.payment_status==='paid'&&checked.total_minor===10000,'Authoritative capture returns paid');
  assert(Date.parse(checked.server_time)<Date.parse(checked.expires_at),'Receipt arrives before 80 seconds');
  document.querySelector('#sales-dialog [data-close-dialog]').click();
  assert(!document.querySelector('#sales-next-customer').hidden,'Paid recovered cart allows next customer');
  assert(document.querySelector('#sales-open-payment').hidden,'Paid order is not left pending');
  document.querySelector('#sales-next-customer').click();
  await wait(()=>document.querySelector('#sales-next-customer').hidden,'Next customer succeeds');
  assert(document.querySelector('#sales-cart-count').textContent==='0','Paid cart cleared exactly once');
  return {passed:true,width:innerWidth,reason:'Real API Vipps push payload, phone validation, early polling capture, paid recovery and next customer'};
})()`;
chromiumFixture('http://127.0.0.1:'+port+'/sales-e2e.html?company=a',checks,[390,1280],{outputDir}).catch(e=>{console.error(e);process.exitCode=1;});
