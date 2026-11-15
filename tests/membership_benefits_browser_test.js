const browser=require('./chromium_fixture');
const {createFixtureServer}=require('./membership_benefits_fixture_server');
const checks=`(async()=>{
 const check=(v,m)=>{if(!v)throw Error(m)};
 const change=(id,value)=>{const n=document.getElementById(id);n.value=value;n.dispatchEvent(new Event('input',{bubbles:true}));n.dispatchEvent(new Event('change',{bubbles:true}));};
 const wait=async(fn,label)=>{for(let i=0;i<100;i++){if(fn())return;await new Promise(r=>setTimeout(r,25));}throw Error(label||'Timeout');};
 const feedback=()=>document.getElementById('mb-save-feedback');
 const row=id=>document.querySelector('[data-plan-row="'+id+'"]');
 const edit=id=>row(id).querySelector('[data-plan-id]').click();
 const remove=id=>row(id).querySelector('[data-remove-plan]').click();
 const save=async()=>{document.getElementById('mb-apply').click();await wait(()=>!document.querySelector('.is-editing')&&!feedback().hidden,'Save did not finish: '+feedback().textContent);};
 const close=()=>document.querySelector('#mb-dialog [data-mb-close]').click();
 let confirmation=true, confirmCalls=0, writes=0, failWrite=false;
 window.confirm=()=>{confirmCalls++;return confirmation;};
 const originalFetch=window.fetch;
 window.fetch=async(...args)=>{
   if(String(args[0]).includes('/api/save_membership_product_benefit.php')){
     writes++;
     if(failWrite)return new Response(JSON.stringify({success:false,message:'Test: lagring feilet'}),{status:500,headers:{'Content-Type':'application/json'}});
   }
   return originalFetch(...args);
 };
 try{
 await wait(()=>document.querySelector('.mb-site-name'),'Catalog load');
 check(document.querySelector('.mb-site-name').textContent.includes('Bergsøy'),'Profile loaded');
 check(getComputedStyle(document.querySelector('.mb-site-logo')).backgroundColor==='rgba(0, 0, 0, 0)','Transparent logo');
 check(!document.querySelector('.mb-benefit-caption'),'No bulky admin header');
 check(!document.querySelector('#mb-admin-overview details').open,'Secondary overview collapsed');
 check(!document.querySelector('[data-mb-item="expired-event"]'),'Expired events excluded');
 check(!document.querySelector('[data-mb-item="no-signup"]'),'Events without signup not editable');
 check(!document.querySelector('[data-mb-item="membership-1"]'),'Memberships not editable');
 for(const id of ['rental-1','event-1']){
   const card=document.querySelector('.mb-card[data-mb-item="'+id+'"]');
   check(card.classList.contains('mb-card-exclusive'),'Member-only outline on '+id);
   check(!card.querySelector('.mb-card-body').textContent.includes('Kun for medlemmer'),'No duplicate exclusivity text');
   check(!card.querySelector('.mb-card-benefits,.mb-benefit-indicator'),'Admin benefits outside card');
 }
 document.querySelector('.mb-feature').click();
 check(document.getElementById('mb-dialog').hidden,'Featured duplicate disabled');
 document.querySelector('.mb-card[data-mb-item="rental-1"]').click();
 check(!document.getElementById('mb-dialog').hidden,'Rental editor opens');
 check(document.querySelectorAll('.mb-plan-row').length===2,'All plans in list');
 check(!document.querySelector('#mb-editor-plan [role=tab]'),'No membership plan tabs');
 check(document.getElementById('mb-plan-panel').hidden,'All editing initially collapsed');
 check(row('11').textContent.includes('15 %')&&row('22').textContent.includes('Inkludert'),'Saved modifications visible immediately');
 check(row('11').querySelector('[data-remove-plan]')&&row('22').querySelector('[data-remove-plan]'),'Direct removal on every saved row');
 check(writes===0,'Opening list never writes');
 const verify=document.getElementById('mb-verification');
 check(!verify.checked&&!verify.disabled,'Global verification available');
 verify.click(); edit('11');
 check(verify.checked,'Verification independent of row');
 document.getElementById('mb-save-verification').click();
 await wait(()=>feedback().textContent.includes('Medlemsverifisering lagret'),'Verification save');
 check(!document.getElementById('mb-members-only'),'No plan-specific member-only switch');
 check(document.getElementById('mb-value').value==='15','Saved percentage prefilled');
 check(!document.getElementById('mb-price-details').open,'Tariff/quote details collapsed');
 check(document.querySelectorAll('.is-editing').length===1,'Only one editor open');
 document.getElementById('mb-price-details').open=true;
 change('mb-start-date','2026-11-14');change('mb-end-date','2026-11-15');
 check(document.getElementById('mb-quote-result').textContent.match(/3[\\s\\u00a0]000/),'Weekend package quote');
 change('mb-value','50%');
 confirmation=false;
 edit('22');
 check(document.getElementById('mb-value').value==='50%'&&row('11').classList.contains('is-editing'),'Declined discard keeps input');
 close();
 check(!document.getElementById('mb-dialog').hidden&&document.getElementById('mb-value').value==='50%','Declined close retains draft');
 confirmation=true;
 await save();
 check(row('11').textContent.includes('50 %'),'Saved row summary updated');
 check(row('22').textContent.includes('Inkludert'),'Other plan unchanged');
 check(!row('11').querySelector('[data-plan-id]').disabled,'Row enabled after save');
 check(document.querySelector('.mb-card[data-mb-item="rental-1"]').textContent.includes('50 % avslag'),'Preview updated after confirmed save');
 edit('11'); change('mb-value','60');
 document.getElementById('mb-reset-item').click();
 check(document.getElementById('mb-plan-panel').hidden&&row('11').textContent.includes('50 %'),'Cancel closes without changing stored rule');
 edit('11'); change('mb-value','101');
 check(document.getElementById('mb-apply').disabled,'Invalid percentage cannot save');
 document.getElementById('mb-reset-item').click();
 confirmation=false;
 const beforeRemoval=writes;
 remove('11');
 check(writes===beforeRemoval&&row('11').textContent.includes('50 %'),'Removal requires confirmation');
 confirmation=true; failWrite=true;
 remove('11');
 await wait(()=>feedback().classList.contains('mb-save-error'),'Failed removal reported');
 check(row('11').textContent.includes('50 %'),'Failed removal preserves saved summary');
 check(document.querySelector('.mb-card[data-mb-item="rental-1"]').textContent.includes('50 % avslag'),'Failed removal preserves preview');
 failWrite=false;
 remove('11');
 await wait(()=>row('11').textContent.includes('Ingen fordel')&&!document.querySelector('.is-editing'),'Removal finished');
 check(!row('11').querySelector('[data-remove-plan]'),'Removed rule no longer has removal button');
 check(row('22').textContent.includes('Inkludert'),'Other rule remains after removal');
 edit('11');
 check(document.getElementById('mb-value').value==='','Empty plan has no invented price');
 change('mb-value','50');
 failWrite=true;
 document.getElementById('mb-apply').click();
 await wait(()=>feedback().classList.contains('mb-save-error'),'Save error reported');
 check(document.getElementById('mb-value').value==='50'&&row('11').textContent.includes('Ingen fordel'),'Failed save retains draft but not fake saved rule');
 failWrite=false;
 await save();
 check(row('11').textContent.includes('per leiedag'),'Daily amount saved with correct basis');
 check(document.querySelector('.mb-card[data-mb-item="rental-1"]').textContent.includes('50,00'),'Daily amount preview');
 close();
 document.querySelector('.mb-card[data-mb-item="event-1"]').click();
 check(row('11').textContent.includes('500,00'),'Event saved price visible without expanding');
 check(row('22').textContent.includes('Ingen fordel'),'No legacy fallback');
 edit('11');
 check(document.getElementById('mb-value').value==='500','Event fixed price prefilled');
 document.querySelector('input[name="mb-mechanism"][value="included"]').click();
 await save();
 check(row('11').textContent.includes('Inkludert'),'Included benefit saved');
 edit('22');
 document.querySelector('input[name="mb-mechanism"][value="own"]').click();
 change('mb-value','0');
 await save();
 check(row('22').textContent.includes('0,00'),'Zero member price supported');
 edit('22'); change('mb-value','99');
 edit('11');
 check(row('11').classList.contains('is-editing')&&document.getElementById('mb-value').value!=='99','Confirmed switch discards only draft');
 document.getElementById('mb-reset-item').click();
 check(row('22').textContent.includes('0,00'),'Switch did not save discarded draft');
 check(document.documentElement.scrollWidth<=innerWidth+1,'No viewport overflow');
 check(document.querySelector('.mb-editor').scrollWidth<=document.querySelector('.mb-editor').clientWidth+1,'No horizontal modal overflow');
 // Focus loop remains usable even when price details are collapsed.
 const dialog=document.getElementById('mb-dialog');
 check(dialog.querySelectorAll('button:not([disabled])').length>0,'Focusable actions available');
 document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
 check(dialog.hidden,'Escape closes clean editor');
 document.querySelector('.mb-card[data-mb-item="rental-1"]').click();
 check(verify.checked,'Verification restored on reopen');
 check(row('11').textContent.includes('per leiedag')&&row('22').textContent.includes('Inkludert'),'Reopened list retains saved rules');
 check(confirmCalls>=5,'Destructive/draft confirmations exercised');
 return {passed:true,width:innerWidth,writes};
 }catch(e){return {passed:false,error:e.stack}}
})()`;
(async()=>{
 for(const width of [390,1280]){
   const s=createFixtureServer();
   await new Promise(r=>s.listen(0,'127.0.0.1',r));
   try{await browser('http://127.0.0.1:'+s.address().port+'/#membership-benefits',checks,[width]);}
   finally{await new Promise(r=>s.close(r));}
 }
})().catch(e=>{console.error(e);process.exitCode=1});
