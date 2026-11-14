// Rendered membership benefits UI against a localhost-only stub API; never authenticates or mutates backend data.
const chromiumFixture = require('./chromium_fixture');
const {createFixtureServer} = require('./membership_benefits_fixture_server');

const liveChecks = `(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const tick=()=>new Promise(resolve=>setTimeout(resolve,100));
 try{
  await tick();
  document.getElementById('mb-plan-select').value='';document.getElementById('mb-plan-select').dispatchEvent(new Event('change',{bubbles:true}));
  document.querySelector('[data-mb-viewport="desktop"]').click();
  check(document.querySelector('.mb-site-name')?.textContent==='Bergsøy Danseklubb','Company header uses API profile');
  const profileHeader=document.querySelector('.mb-site-top');
  check(profileHeader.style.minHeight==='72px'&&profileHeader.style.fontSize==='18px','Company header height and type scale come from profile');
  check(document.querySelector('.mb-site-logo')?.style.height==='38px'&&document.getElementById('mb-catalog').style.getPropertyValue('--mb-site-radius')==='8px','Profile logo height and corner radius are applied');
  check(getComputedStyle(document.querySelector('.mb-site-nav')).justifyContent==='flex-end','Profile navigation alignment is applied');
  check(document.querySelector('.mb-site-nav')?.textContent.includes('Tjenester'),'Public navigation is shown');
  const cardDescription=document.querySelector('.mb-card-description');
  check(parseFloat(getComputedStyle(cardDescription).fontSize)>=13,'Catalog descriptions remain readable at 13px or larger: '+getComputedStyle(cardDescription).fontSize);
  const eventGrid=getComputedStyle(document.querySelector('.mb-group[aria-label="Arrangementer"] .mb-cards')).gridTemplateColumns.split(' ').length;
  check(eventGrid===(innerWidth>700?2:1),'Wide event cards use two columns on desktop and one on phones');
  check(document.querySelectorAll('.mb-card').length===7,'Private and expired services omitted by default: '+document.querySelectorAll('.mb-card').length);
  check(getComputedStyle(document.querySelector('.mb-site-logo')).backgroundColor==='rgba(0, 0, 0, 0)','Transparent logo exposes header background');
  const membershipCard=document.querySelector('.mb-group[aria-label="Medlemskap"] .mb-card');
  check(membershipCard?.getAttribute('aria-disabled')==='true' && getComputedStyle(membershipCard).filter.includes('grayscale'),'Membership remains visible but grey and disabled');
  membershipCard.click();
  check(document.getElementById('mb-dialog').hidden,'Membership cannot open benefit assignment');
  const noSignup=[...document.querySelectorAll('.mb-card')].find(card=>card.textContent.includes('Informasjonsarrangement'));
  check(noSignup?.getAttribute('aria-disabled')==='true'&&!noSignup.hasAttribute('data-mb-item'),'Events without registration cannot receive benefits');
  noSignup.click();
  check(document.getElementById('mb-dialog').hidden,'Event without registration does not open editor');
  check(!document.body.textContent.includes('Utløpt arrangement'),'Expired event is removed');
  check(!document.body.textContent.includes('Skjult klubbgenser'),'Invisible product is not part of the public preview');
  check(document.body.textContent.includes('Pris ikke oppgitt'),'Unknown price is not displayed as free');
  check(document.querySelector('.mb-card[data-mb-item="event-1"]').textContent.includes('Bergsøy Medlem · medlemspris 500,00'),'Public event member pricing is represented in the ordinary preview');
  check(!document.querySelector('.mb-card[data-mb-item="event-hidden-price"] .mb-card-price').textContent.includes('415,00'),'Private member price is not displayed as public service price');
  const assigned=document.querySelector('.mb-card[data-mb-item="rental-1"] .mb-card-benefits');
  check(assigned.textContent.includes('Bergsøy Medlem')&&assigned.textContent.includes('15 % rabatt')&&assigned.textContent.includes('Familiemedlem')&&assigned.textContent.includes('Inkludert'),'Saved benefits identify each membership type without opening the editor');
  check(assigned.textContent.includes('Privat visning')&&assigned.textContent.includes('bookingtotal')&&assigned.textContent.includes('ikke offentlig visning'),'Private visibility and price basis are explicit admin annotations');
  check(document.querySelector('.mb-card[data-mb-item="event-hidden-price"] .mb-card-benefits').textContent.includes('415,00'),'Private saved event price is visible in labelled admin overview');
  const plan=document.getElementById('mb-plan-select');plan.value='11';plan.dispatchEvent(new Event('change',{bubbles:true}));
  check(document.querySelector('.mb-card[data-mb-item="rental-1"] .mb-benefit-selected strong').textContent==='Bergsøy Medlem','Selected membership type is highlighted');
  check(document.querySelector('.mb-card[data-mb-item="event-hidden-price"] .mb-card-price strong').textContent.includes('415,00'),'Non-public benefit is still applied for the selected member plan');
  check(document.querySelector('.mb-card[data-mb-item="event-1"]').textContent.includes('500,00'),'Existing public member event price is represented');
  check(document.querySelector('.mb-card[data-mb-item="member-exclusive"]').textContent.includes('Pris ikke oppgitt'),'Exclusive service without member price is unknown, not treated as free');
  const featured=document.querySelector('.mb-feature');
  check(featured.getAttribute('aria-disabled')==='true'&&!featured.hasAttribute('tabindex')&&!featured.hasAttribute('data-mb-item')&&getComputedStyle(featured).filter.includes('grayscale'),'Duplicate featured event is grey and not an interactive control');
  featured.click();featured.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));
  check(document.getElementById('mb-dialog').hidden,'Duplicate featured event cannot open editor');
  const eventCard=document.querySelector('.mb-card[data-mb-item="event-1"]');
  eventCard.focus();eventCard.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));
  check(!document.getElementById('mb-dialog').hidden,'Keyboard activation opens the benefit editor');
  check(document.getElementById('mb-live-price').textContent.includes('500'),'Existing event member price prefilled in editor');
  document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
  check(document.getElementById('mb-dialog').hidden&&document.activeElement===eventCard,'Escape closes editor and returns focus');
  document.querySelector('.mb-card[data-mb-item="event-1"]').click();
  document.querySelector('input[name="mb-mechanism"][value="own"]').click();
  const membersValue=document.getElementById('mb-value');membersValue.value='200';membersValue.dispatchEvent(new Event('input',{bubbles:true}));
  const memberOnly=document.getElementById('mb-members-only');memberOnly.checked=true;memberOnly.dispatchEvent(new Event('change',{bubbles:true}));
  document.getElementById('mb-apply').click();
  check(document.querySelector('.mb-card[data-mb-item="event-1"] .mb-benefit-temporary').textContent.includes('Ikke lagret'),'Temporary benefit indicator is distinct from saved assignment');
  check(document.querySelector('.mb-card[data-mb-item="event-1"]').textContent.includes('200,00'),'Members-only benefit retains its configured member price');
  plan.value='22';plan.dispatchEvent(new Event('change',{bubbles:true}));
  check(document.querySelector('.mb-card[data-mb-item="event-1"] .mb-card-price strong').textContent.includes('380,00'),'Another membership plan retains its independent price');
  plan.value='11';plan.dispatchEvent(new Event('change',{bubbles:true}));
  check(document.querySelector('.mb-card[data-mb-item="event-1"] .mb-card-price strong').textContent.includes('200,00'),'Edited plan keeps only its own preview override');
  plan.value='';plan.dispatchEvent(new Event('change',{bubbles:true}));
  check(document.querySelector('.mb-card[data-mb-item="event-1"] .mb-card-price').textContent.includes('Kun for medlemmer')&&!document.querySelector('.mb-card[data-mb-item="event-1"] .mb-card-price').textContent.includes('200,00'),'Ordinary visitor service price is restricted, separate from admin annotations');
  check(document.querySelector('.mb-card[data-mb-item="member-exclusive"]').textContent.includes('Kun for medlemmer'),'Exclusive item is restricted for ordinary visitors');
  plan.value='11';plan.dispatchEvent(new Event('change',{bubbles:true}));
  document.querySelector('.mb-card[data-mb-item="event-1"]').click();
  document.getElementById('mb-reset-item').click();
  check(document.querySelector('.mb-card[data-mb-item="event-1"]').textContent.includes('500,00'),'Reset restores server-provided event price');
  document.querySelector('.mb-card[data-mb-item="event-1"]').click();
  document.querySelector('input[name="mb-mechanism"][value="percentage"]').click();
  const value=document.getElementById('mb-value');value.value='20';value.dispatchEvent(new Event('input',{bubbles:true}));
  check(document.getElementById('mb-live-price').textContent.includes('520'),'Percentage price updates immediately');
  document.getElementById('mb-apply').click();
  check(document.querySelector('.mb-card[data-mb-item="event-1"]').textContent.includes('520,00'),'Apply updates rendered member price');
  document.querySelector('.mb-card[data-mb-item="event-1"]').click();
  document.querySelector('input[name="mb-mechanism"][value="included"]').click();
  check(document.getElementById('mb-live-price').textContent==='Inkludert','Included mechanism previews as a benefit, not a zero price');
  document.getElementById('mb-apply').click();
  check(document.querySelector('.mb-card[data-mb-item="event-1"] .mb-card-price').textContent.includes('Inkludert')&&!document.querySelector('.mb-card[data-mb-item="event-1"] .mb-card-price').textContent.includes('0,00'),'Included benefit renders as Included');
  document.querySelector('.mb-card[data-mb-item="event-1"]').click();
  document.getElementById('mb-reset-item').click();
  const hiddenToggle=document.getElementById('mb-show-hidden');hiddenToggle.checked=true;hiddenToggle.dispatchEvent(new Event('change',{bubbles:true}));
  check(document.body.textContent.includes('Skjult klubbgenser')&&document.body.textContent.includes('Skjult i offentlig visning'),'Explicit admin preview toggle reveals hidden item with a warning marker');
  hiddenToggle.checked=false;hiddenToggle.dispatchEvent(new Event('change',{bubbles:true}));
  document.querySelector('[data-mb-viewport="phone"]').click();
  check(document.getElementById('mb-catalog').classList.contains('mb-phone-view'),'Phone viewport control switches preview');
  check(document.documentElement.scrollWidth<=innerWidth+1,'No horizontal overflow at this viewport');
  document.querySelector('.mb-card[data-mb-item="event-1"]').click();
  document.dispatchEvent(new CustomEvent('subtab-changed',{detail:{module:'membership',subtab:'membership-plans'}}));
  check(document.getElementById('mb-dialog').hidden&&document.querySelector('.mb-card[data-mb-item="event-1"]').textContent.includes('500,00'),'Leaving the Fordeler subtab closes the editor and clears transient edits');
  plan.value='';plan.dispatchEvent(new Event('change',{bubbles:true}));
  document.querySelector('[data-mb-viewport="desktop"]').click();
  return {passed:true,width:innerWidth};
 }catch(error){return {passed:false,error:error.stack||String(error)}}
})()`;
const errorChecks = `(async()=>{
 const tick=()=>new Promise(resolve=>setTimeout(resolve,100));
 try{
  await tick();
   if(!document.getElementById('mb-state').hidden&&document.getElementById('mb-state').textContent.includes('Kunne ikke hente')){
    if(getComputedStyle(document.getElementById('mb-catalog')).display!=='none')throw new Error('API failure does not show demo content as live: '+document.getElementById('mb-state').hidden+' / '+getComputedStyle(document.getElementById('mb-state')).display+' / '+document.querySelector('.mb-state:not([hidden])+.mb-catalog')+' / '+document.getElementById('mb-catalog').style.display);
   document.querySelector('[data-mb-demo]').click();await tick();
  }
  if(!document.getElementById('mb-mode-label').textContent.includes('DEMO'))throw new Error('Demo is only shown after explicit action and visibly labeled');
  if(document.documentElement.scrollWidth>innerWidth+1)throw new Error('No horizontal overflow in explicit demo');
  return {passed:true,width:innerWidth};
 }catch(error){return {passed:false,error:error.stack||String(error)}}
})()`;

(async()=>{
 const server=createFixtureServer();
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 try{
  await chromiumFixture('http://127.0.0.1:'+server.address().port+'/#membership-benefits',liveChecks,[390,1280]);
  await new Promise(resolve=>server.close(resolve));
  const failedServer=createFixtureServer({failAll:true});
  await new Promise(resolve=>failedServer.listen(0,'127.0.0.1',resolve));
  try { await chromiumFixture('http://127.0.0.1:'+failedServer.address().port+'/#membership-benefits',errorChecks,[390,1280]); }
  finally { await new Promise(resolve=>failedServer.close(resolve)); }
  console.log('Membership benefits browser fixture passed at 390px and 1280px (live stub, API error, explicit demo).');
 }finally{if(server.listening)await new Promise(resolve=>server.close(resolve));}
})().catch(error=>{console.error(error);process.exitCode=1;});
