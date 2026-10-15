// Real controller, schemas, ledger, register and UI. Only Vipps and a lost
// cart-read response are simulated. No direct cart deletion between attempts.
const path=require('node:path'),fs=require('node:fs');
const chromiumFixture=require('./chromium_fixture');
if(typeof WebSocket==='undefined'){
  const r=require('node:child_process').spawnSync(process.execPath,['--experimental-websocket',__filename,...process.argv.slice(2)],{stdio:'inherit'});
  process.exit(r.status??1);
}
const [port,temp]=process.argv.slice(2),outputDir=path.join(temp,'vipps-retry-browser');
fs.mkdirSync(outputDir,{recursive:true});
const checks=`(async()=>{
  const $=s=>document.querySelector(s);
  const assert=(v,label)=>{if(!v)throw new Error(label);};
  const wait=async(fn,label)=>{for(let i=0;i<1200;i++){if(fn())return;await new Promise(r=>setTimeout(r,20));}throw new Error(label+': '+$('#sales-cart-error')?.textContent+' / '+$('#sales-payment-status')?.textContent);};
  const read=async(action,query={})=>(await fetch('/api/sales.php?'+new URLSearchParams({action,...query}))).json();
  await wait(()=>!$('#sales-new-product').disabled,'Bootstrap');
  if(!$('#sales-next-customer').hidden){
    $('#sales-next-customer').click();
    await wait(()=>$('#sales-next-customer').hidden,'Start customer');
  }
  const bootstrap=await read('bootstrap'),csrf=bootstrap.csrf_token;
  const product=bootstrap.products.find(p=>p.name==='Vipps retry customized');
  assert(product?.has_fields,'Actual product has dynamic questions');
  const post=async(action,data)=>(await fetch('/api/sales.php?action='+action,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...data,csrf_token:csrf})})).json();
  const stock=async()=>Number((await read('products')).products.find(p=>p.id===product.id).stock_quantity);
  const send=async phone=>{
    await wait(()=>!$('[data-method-key="gateway-a"]').disabled,'Vipps selectable');
    const count=window.apiCalls.filter(c=>c.action==='checkout').length;
    $('[data-method-key="gateway-a"]').click();
    await wait(()=>$('#sales-vipps-phone'),'Phone keypad');
    for(const digit of phone)$('[data-vipps-digit="'+digit+'"]').click();
    assert(window.apiCalls.filter(c=>c.action==='checkout').length===count,'No new payment before explicit Send');
    $('#sales-confirm-vipps').click();
    await wait(()=>window.apiCalls.filter(c=>c.action==='checkout'&&c.result.order).length>count,'Provider checkout reply');
    return window.apiCalls.filter(c=>c.action==='checkout'&&c.result.order).pop().result.order;
  };
  const scenarios=[
    {name:'customer abort',phone:'40000001'},
    {name:'cashier cancel, lost cart read, Vipps retry',phone:'40000002',cancel:true,failRead:true,retry:true,fullscreen:true},
    {name:'provider expiry',phone:'40000003',fullscreen:true},
    {name:'unknown then confirmed abort',phone:'40000004',unknown:true},
    {name:'capture wins cancellation',phone:'40000005',cancel:true,captured:true}
  ];
  for(const scenario of scenarios){
    if(scenario.fullscreen){
      $('#sales-fullscreen').click();
      await wait(()=>$('#sales-admin').classList.contains('sales-fullscreen'),'Fullscreen active');
    }
    const before=await stock();
    $('[data-product="'+product.id+'"]').click();
    await wait(()=>$('#sales-customize'),'Product questions');
    $('#sales-customize').elements.engraving.value=scenario.name;
    $('#sales-customize').elements.notes.value='Behold merknad '+innerWidth;
    $('#sales-customize').elements.quantity.value='2';
    $('#sales-customize-add').click();
    await wait(()=>$('#sales-dialog').hidden&&$('#sales-cart-count').textContent==='2'&&!$('[data-line-qty]').disabled,'Customized lines ready');
    const original=(await read('cart')).cart;
    const originalLine=original.items[0];
    const attempt=await send(scenario.phone);
    await wait(()=>$('#sales-pending-cancel'),'Pending payment modal');
    assert($('#sales-next-customer').hidden&&$('[data-method-key="cash"]').disabled,'Pending is not completed, new tender blocked');
    assert(await stock()===before-2,'Pending reserves exact quantity');
    if(scenario.unknown){
      await wait(()=>window.apiCalls.some(c=>c.action==='payment_status'&&c.result.order?.id===attempt.id&&c.result.order.payment_status==='unknown'),'Unknown lookup');
      assert($('[data-method-key="cash"]').disabled&&$('#sales-checkout').disabled,'Unknown cannot start a second payment');
      assert(await stock()===before-2,'Unknown retains reservation');
    }
    let failRead=false;
    const originalFetch=window.fetch;
    if(scenario.failRead){
      failRead=true;
      window.fetch=async function(url,opts){
        if(failRead&&new URL(url,location.href).searchParams.get('action')==='cart'){
          failRead=false;throw new TypeError('Simulated interrupted cart read');
        }
        return originalFetch(url,opts);
      };
    }
    if(scenario.cancel){
      await wait(()=>!$('#sales-pending-cancel').disabled,'Cancel available');
      $('#sales-pending-cancel').click();
    }
    let paid;
    if(scenario.captured){
      await wait(()=>$('.sales-receipt'),'Captured payment displays receipt instead of restoring cart');
      paid=window.apiCalls.filter(c=>c.action==='cancel_payment'&&c.result.order?.id===attempt.id).pop().result.order;
      assert(paid.payment_status==='paid'&&paid.id===attempt.id,'Capture wins on same order');
    }else{
      if(scenario.failRead){
        await wait(()=>$('#sales-recover-cart')&&!$('#sales-recover-cart').disabled,'Cart read can be retried');
        assert($('[data-method-key="cash"]').disabled&&$('#sales-checkout').disabled,'Cannot pay before cart restoration is confirmed');
        assert($('#sales-dialog').hidden&&$('#sales-cancel-payment').hidden,'Terminal payment modal and cancel button closed');
        const checkoutCount=window.apiCalls.filter(c=>c.action==='checkout').length;
        $('#sales-recover-cart').click();
        await wait(()=>!$('#sales-recover-cart'),'Cart retry completed');
        assert(window.apiCalls.filter(c=>c.action==='checkout').length===checkoutCount,'Retrying cart read never sends payment');
        window.fetch=originalFetch;
      }
      await wait(()=>$('#sales-dialog').hidden&&!$('[data-method-key="cash"]').disabled&&$('#sales-cart-count').textContent==='2','Original trade usable');
      assert($('#sales-payment-status').textContent.includes('Varene er beholdt'),'Message explains goods remain in cart');
      assert($('#sales-next-customer').hidden&&$('#sales-cancel-payment').hidden&&$('#sales-open-payment').hidden,'No sale completion or stale payment actions');
      const restored=(await read('bootstrap')).cart;
      assert(restored.id===original.id&&restored.revision!==original.revision,'Same cart recovered with a fresh revision, including bootstrap');
      assert(JSON.stringify(restored.items)===JSON.stringify(original.items),'IDs, quantities, prices, answers and notes unchanged');
      assert(await stock()===before,'Terminal status releases reservation once');
      const terminal=(await post('payment_status',{order_id:attempt.id})).order;
      assert(terminal.status==='cancelled'&&!terminal.register_document,'Failed attempt remains history, never a fiscal sale receipt');
      assert(await stock()===before,'Repeated terminal notification does not duplicate release');
      assert(!$('[data-line-qty]').disabled&&!$('[data-remove-line]').disabled,'Restored cart is editable');
      if(scenario.retry){
        const again=await send('49999999');
        assert(again.id!==attempt.id,'New explicit attempt gets a new payment reference');
        await wait(()=>$('.sales-receipt'),'Retry captured and receipt shown');
        paid=(await read('order',{order_id:again.id})).order;
      }else{
        $('[data-method-key="cash"]').click();
        $('[data-cash="20000"]').click();
        await wait(()=>!$('#sales-checkout').disabled,'Cash checkout enabled');
        $('#sales-checkout').click();
        await wait(()=>$('#sales-confirm-cash'),'Cash confirmation');
        $('#sales-confirm-cash').click();
        await wait(()=>$('.sales-receipt'),'Cash completes original trade');
        paid=window.apiCalls.filter(c=>c.action==='checkout'&&c.result.order?.payment_method==='cash').pop().result.order;
      }
    }
    assert(paid.payment_status==='paid'&&paid.total_minor===20000&&paid.register_document,'Trade has a paid fiscal document');
    assert(paid.items[0].quantity===2&&paid.items[0].notes===originalLine.notes&&paid.items[0].form_data.engraving===scenario.name,'Paid snapshot preserves customized goods');
    assert(await stock()===before-2,'Only successful trade consumes goods');
    const repeat=(await post('payment_status',{order_id:paid.id})).order;
    assert(repeat.register_document.id===paid.register_document.id,'Repeated confirmation uses same receipt');
    assert(await stock()===before-2,'Repeated confirmation does not consume stock twice');
    $('#sales-dialog [data-close-dialog]').click();
    await wait(()=>!$('#sales-next-customer').hidden,'Next customer offered only after paid trade');
    $('#sales-next-customer').click();
    await wait(()=>$('#sales-next-customer').hidden&&$('#sales-cart-count').textContent==='0','Next customer after completed trade');
    if(scenario.fullscreen){
      assert($('#sales-admin').classList.contains('sales-fullscreen'),'Payment flows stay inside fullscreen');
      $('#sales-fullscreen-exit').click();
      await wait(()=>!$('#sales-admin').classList.contains('sales-fullscreen'),'Exit fullscreen');
    }
    assert(document.documentElement.scrollWidth<=innerWidth+1,'No horizontal overflow after recovery');
  }
  // A payment lock must not become a general "drop scans while busy" lock.
  const plain=bootstrap.products.find(p=>!p.has_fields&&Number(p.stock_quantity)>=4&&['kiosk','both'].includes(p.visibility||'kiosk'));
  assert(plain?.barcode,'A scannable plain product is available');
  const normalFetch=window.fetch;
  let delayedAdd=false;
  window.fetch=async function(url,opts){
    const response=await normalFetch(url,opts);
    if(!delayedAdd&&new URL(url,location.href).searchParams.get('action')==='add'){
      delayedAdd=true;await new Promise(r=>setTimeout(r,300));
    }
    return response;
  };
  const scan=()=>{$('#sales-scanner').value=plain.barcode;$('#sales-scanner').dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true,cancelable:true}));};
  scan();
  await wait(()=>delayedAdd,'First scan awaits its cart reply');
  scan();
  await wait(()=>$('#sales-cart-count').textContent==='2'&&!$('[data-line-qty]').disabled,'Both rapid scans counted');
  window.fetch=normalFetch;
  $('[data-remove-line]').click();
  await wait(()=>$('#sales-cart-count').textContent==='0','Scanned test goods removed explicitly through UI');
  return {passed:true,width:innerWidth,reason:'Customer abort, cashier cancel/cart-read retry, expiry, unknown lock, cash/Vipps continuation, custom fields and capture-race receipts'};
})()`;
chromiumFixture('http://127.0.0.1:'+port+'/sales-e2e.html?company=a',checks,[390,1280],{outputDir}).catch(e=>{console.error(e);process.exitCode=1;});
