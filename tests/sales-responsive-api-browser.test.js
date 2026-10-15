// Actual Sales renderer and controller; products are created in the disposable DB.
const fs=require('node:fs'),path=require('node:path');
const chromiumFixture=require('./chromium_fixture');
const [port,temp]=process.argv.slice(2),outputDir=path.join(temp,'responsive-browser');
fs.mkdirSync(outputDir,{recursive:true});
const checks=`(async()=>{
  const $=s=>document.querySelector(s),assert=(ok,label)=>{if(!ok)throw new Error(label);};
  const wait=async(fn,label)=>{for(let i=0;i<500;i++){if(fn())return;await new Promise(r=>setTimeout(r,20));}throw new Error(label);};
  await wait(()=>!$('#sales-new-product').disabled,'Bootstrap ready');
  const prefix='Skjermtest '+innerWidth;
  let boot=window.apiCalls.find(c=>c.action==='bootstrap').result;
  if(!window.apiCalls.some(c=>c.action==='product_save'&&c.payload?.name?.startsWith(prefix))){
    const products=[
      {name:prefix+' Kaffe',selling_price:'25.00',img_product:'/product-wide.svg'},
      {name:prefix+' EkstraordinærtLangtNorskProduktnavnMedFlereSammensatteOrdSomIkkeSkalKlippes',selling_price:'12345678.90',img_product:'/not-an-image.png'},
      {name:prefix+' Uten bilde',selling_price:'999.99',img_product:''}
    ];
    for(const product of products) {
      const response=await fetch('/api/sales.php?action=product_save',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({...product,stock_quantity:5,csrf_token:boot.csrf_token})});
      const saved=await response.json();assert(saved.success,'Fixture product saved: '+JSON.stringify(saved));
    }
  }
  $('#sales-refresh-products').click();
  await wait(()=>document.querySelector('.sales-product-meta strong')&&Array.from(document.querySelectorAll('.sales-product-meta strong')).some(e=>e.textContent.startsWith(prefix)),'Real renderer refreshed');
  $('#sales-search').value=prefix;$('#sales-search').dispatchEvent(new Event('input',{bubbles:true}));
  await wait(()=>document.querySelectorAll('.sales-product-tile').length===3,'Three responsive product cases');
  await wait(()=>document.querySelector('.sales-product-image img[src$="product-wide.svg"]')?.naturalWidth>0
    &&document.querySelector('.sales-product-image svg'),'Image and fallback actually rendered');
  await wait(()=>document.querySelector('[data-method-key="gateway-a"] .sales-vipps-logo')?.naturalWidth>0,
    'Actual local Vipps logo loaded');
  assert(!document.querySelector('[data-method-key="gateway-a-2"] .sales-vipps-logo'),
    'Other gateway names are not relabeled as Vipps');
  const check=mode=>{
    assert(document.documentElement.scrollWidth<=innerWidth+1,mode+' document has no horizontal overflow');
    const logo=document.querySelector('[data-method-key="gateway-a"] .sales-vipps-logo');
    const logoBox=logo.getBoundingClientRect(),buttonBox=logo.closest('button').getBoundingClientRect();
    assert(logoBox.width>0&&logoBox.height>0&&logoBox.height<=25,mode+' Vipps logo is visible and compact');
    assert(Math.abs(logoBox.width/logoBox.height-logo.naturalWidth/logo.naturalHeight)<.02,
      mode+' Vipps logo is not stretched');
    assert(logoBox.left>=buttonBox.left&&logoBox.right<=buttonBox.right,mode+' logo stays inside method button');
    const tiles=Array.from(document.querySelectorAll('.sales-product-tile'));
    const rows=new Map();
    for(const tile of tiles) {
      const box=tile.getBoundingClientRect(),image=tile.querySelector('.sales-product-image').getBoundingClientRect();
      assert(box.width>=44&&box.height>=44,mode+' touch target >=44px');
      assert(Math.abs(image.width-image.height)<=1,mode+' square image '+image.width+'x'+image.height);
      if(innerWidth<768)assert(image.width<=104.5,mode+' compact mobile image');
      for(const part of tile.querySelectorAll('.sales-product-meta strong,.sales-product-price')) {
        assert(part.scrollWidth<=part.clientWidth+1,mode+' long name/price fits card');
        assert(part.scrollHeight<=part.clientHeight+1,mode+' text not clipped');
      }
      const row=Math.round(box.top),last=rows.get(row);
      if(last)assert(box.left>=last.right-1,mode+' product cards do not overlap');
      rows.set(row,box);
    }
    const top=tiles[0].getBoundingClientRect().top;
    assert(tiles.some((tile,i)=>i>0&&Math.abs(tile.getBoundingClientRect().top-top)<1),mode+' more than one column');
    assert($('.sales-product-image img[src$="product-wide.svg"]')?.naturalWidth>0,mode+' real image rendered');
    assert(document.querySelector('.sales-product-image svg'),mode+' broken image has neutral fallback');
  };
  check('Normal');
  $('#sales-fullscreen').click();
  await wait(()=>$('#sales-admin').classList.contains('sales-fullscreen'),'Fullscreen entered');
  await new Promise(r=>setTimeout(r,100));check('Fullscreen');
  $('#sales-fullscreen-exit').click();
  await wait(()=>!$('#sales-admin').classList.contains('sales-fullscreen'),'Fullscreen exited');
  check('Restored');
  return {passed:true,width:innerWidth,reason:'Square compact images, real/missing image, long names/prices, touch targets, normal/fullscreen layout'};
})()`;
chromiumFixture('http://127.0.0.1:'+port+'/sales-e2e.html',checks,[320,390,768,1280],{outputDir})
  .catch(e=>{console.error(e);process.exitCode=1;});
