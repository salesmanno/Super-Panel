// Actual module markup + assets + HTTP controller + isolated MySQL.
// No product, cart, checkout or validation responses are mocked.
const fs=require('node:fs'),path=require('node:path');
const chromiumFixture=require('./chromium_fixture');
if(typeof WebSocket==='undefined'){
  const result=require('node:child_process').spawnSync(process.execPath,['--experimental-websocket',__filename,...process.argv.slice(2)],{stdio:'inherit'});
  process.exit(result.status??1);
}
const [port,temp]=process.argv.slice(2);
async function run(company){
  const outputDir=path.join(temp,'browser-'+company);fs.mkdirSync(outputDir,{recursive:true});
  const checks=`(async()=>{
    const registered=${company==='b'};
    const wait=async(fn,label)=>{for(let n=0;n<200;n++){const result=fn();if(result)return result;await new Promise(r=>setTimeout(r,15));}throw new Error(label+': '+document.querySelector('#sales-product-error')?.textContent+' '+document.querySelector('#sales-cart-error')?.textContent);};
    const assert=(value,label)=>{if(!value)throw new Error(label);};
    const calls=window.apiCalls;
    await wait(()=>calls.some(c=>c.action==='bootstrap'),'Bootstrap');
    await wait(()=>!document.querySelector('#sales-new-product').disabled,'Product creation ready');
    const boot=calls.find(c=>c.action==='bootstrap').result;
    assert(Number(boot.tax_registered)===(registered?1:0),'Company VAT status comes from actual API');
    assert(document.querySelector('[data-method-key="cash"]').getAttribute('aria-pressed')==='true','Cash is default');
    assert(!document.querySelector('select#sales-method'),'No legacy payment dropdown');
    if(!registered) {
      const notes=document.querySelector('#sales-method-note').textContent;
      const unavailable=boot.methods.filter(m=>!m.available);
      assert(unavailable.length>=5&&unavailable.every(m=>notes.includes(m.method_name)),'Incomplete and unsupported gateways are explained');
      assert(document.querySelector('[data-method-key="gateway-a"]'),'Actual database credentials enable Vipps without environment payment keys');
      assert(!JSON.stringify(boot).includes('fixture-db-secret'),'Payment credentials never appear in bootstrap response');
      assert(!document.querySelector('[data-method-key="gateway-b"],[data-method-key="gateway-inactive"]'),'No foreign/inactive gateway');
    }
    const variants=registered?['standard','custom','exempt']:['exempt'];
    for(const variant of variants) {
      document.querySelector('#sales-items').style.display='block';
      document.querySelector('#sales-new-product').click();
      let form=document.querySelector('#sales-product-form');
      const label=form.elements.selling_price.closest('label').textContent;
      assert(registered?label.includes('Salgspris inkl. MVA'):label.includes('Salgspris')&&!label.includes('MVA'),'Correct sale-price label on creation');
      assert(registered?!!form.elements.tax_class:!form.elements.tax_class,'VAT controls match company');
      if(registered){
        assert(form.elements.item_taxable.checked&&form.elements.tax_rate.value==='25','New product defaults to taxable 25 percent');
        assert(!form.elements.tax_class.querySelector('option[value="zero"]'),'No unsupported zero class');
        if(variant!=='standard'){form.elements.tax_class.value=variant;form.elements.tax_class.dispatchEvent(new Event('change',{bubbles:true}));}
        if(variant==='custom')assert(form.elements.tax_rate.value==='0'&&form.elements.item_taxable.checked,'Explicit zero rate stays distinct from exemption');
      }
      form.elements.name.value='E2E '+${JSON.stringify(company)}+' '+variant+' '+innerWidth;
      form.elements.selling_price.value='125.00';form.elements.stock_quantity.value='2';
      for(const field of ['cost_price','discount_percent','reorder_level','low_stock_threeshold'])form.elements[field].value='';
      let before=calls.length;document.querySelector('#sales-save-product').click();
      let saved=await wait(()=>calls.slice(before).find(c=>c.action==='product_save'),'Actual product POST');
      assert(saved.status===200&&saved.result.success,'Actual API accepts minimal form: '+JSON.stringify(saved.result));
      assert(saved.payload.cost_price==='0'&&saved.payload.discount_percent==='0','Blank optional numeric payload is explicit zero');
      let product=saved.result.product;
      assert(product.tax_class===(registered?variant:'exempt'),'Stored VAT class');
      assert(Number(product.tax_rate)===(registered&&variant==='standard'?25:0),'Stored VAT rate');
      const stock=Number(product.stock_quantity),barcode=product.barcode;
      await wait(()=>document.querySelector('#sales-dialog').hidden,'Create dialog closes');
      // Round-trip existing values and an intentional clearing of optional data.
      document.querySelector('[data-edit-product="'+product.id+'"]').click();form=document.querySelector('#sales-product-form');
      const editLabel=form.elements.selling_price.closest('label').textContent;
      assert(registered?editLabel.includes('inkl. MVA'):!editLabel.includes('MVA'),'Correct price label on editing');
      form.elements.cost_price.value='20.50';form.elements.reorder_level.value='3';
      before=calls.length;document.querySelector('#sales-save-product').click();
      saved=await wait(()=>calls.slice(before).find(c=>c.action==='product_save'),'Edit POST');
      assert(saved.result.success&&Number(saved.result.product.cost_price)===20.5,'Edit persists explicitly entered cost');
      await wait(()=>document.querySelector('#sales-dialog').hidden,'Edit closes');
      document.querySelector('[data-edit-product="'+product.id+'"]').click();form=document.querySelector('#sales-product-form');
      form.elements.cost_price.value='';form.elements.reorder_level.value='';
      before=calls.length;document.querySelector('#sales-save-product').click();
      saved=await wait(()=>calls.slice(before).find(c=>c.action==='product_save'),'Clear optional fields');
      assert(saved.result.success&&Number(saved.result.product.cost_price)===0&&Number(saved.result.product.reorder_level)===0,'Intentional clearing persists zero');
      assert(saved.result.product.barcode===barcode&&Number(saved.result.product.stock_quantity)===stock,'Editing does not alter barcode or stock');
      await wait(()=>document.querySelector('#sales-dialog').hidden,'Clear dialog closes');
      before=calls.length;document.querySelector('[data-product="'+product.id+'"]').click();
      const added=await wait(()=>calls.slice(before).find(c=>c.action==='add'),'Add actual product to cart');
      assert(added.result.success,'Cart accepts created product');
      const tax=registered&&variant==='standard'?2500:0;
      assert(added.result.cart.total_minor===12500&&added.result.cart.tax_minor===tax,'Actual cart extracts VAT from gross price');
      document.querySelector('[data-cash="20000"]').click();
      await wait(()=>!document.querySelector('#sales-checkout').disabled,'Actual cash checkout enabled after cart UI finishes updating');
      document.querySelector('#sales-checkout').click();before=calls.length;document.querySelector('#sales-confirm-cash').click();
      const paid=await wait(()=>calls.slice(before).find(c=>c.action==='checkout'),'Actual checkout');
      assert(paid.result.success,'Checkout accepts actual UI payload: '+JSON.stringify(paid.result));
      const order=paid.result.order;
      assert(order.payment_status==='paid'&&order.total_minor===12500&&order.subtotal_minor===12500-tax&&order.tax_minor===tax,'Paid immutable receipt reconciles gross/net/VAT');
      assert(order.items.length===1&&order.items[0].tax_minor===tax,'Receipt line preserves VAT');
      await wait(()=>document.querySelector('.sales-receipt'),'Receipt rendered');
      assert(document.documentElement.scrollWidth<=innerWidth+1,'No horizontal overflow on receipt');
      document.querySelector('#sales-dialog [data-close-dialog]').click();
      before=calls.length;document.querySelector('#sales-next-customer').click();
      await wait(()=>calls.slice(before).find(c=>c.action==='next_customer'),'Next customer acknowledges receipt');
      await wait(()=>document.querySelector('#sales-next-customer').hidden,'Next customer UI settled');
    }
    return {passed:true,width:innerWidth,company:${JSON.stringify(company)},reason:'Actual form/API creation, editing, numeric clearing and cash checkout with verified VAT'};
  })()`;
  await chromiumFixture('http://127.0.0.1:'+port+'/sales-e2e.html?company='+company,checks,[390,1280],{outputDir});
}
(async()=>{await run('a');await run('b');})().catch(error=>{console.error(error);process.exitCode=1});
