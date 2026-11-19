const chromiumFixture = require('./chromium_fixture');
const checks = `(async()=>{
 const assert=(v,m)=>{if(!v)throw Error(m)};
 const tick=()=>new Promise(r=>setTimeout(r,150));
 for(let n=0;n<50&&!document.querySelector('.tc-mode-island');n++)await tick();
 const nav=name=>[...document.querySelectorAll('.tc-site-nav button')].find(b=>b.textContent===name).click();
 nav('Tjenester'); await tick();
 const before=document.querySelector('.tc-public-site').getBoundingClientRect().height;
 document.querySelector('.tc-island-new').click(); await tick();
 assert(document.querySelector('.tc-new-service-choices'),'New must first present service choices');
 const choices=[...document.querySelectorAll('.tc-new-service-choices button')];
 assert(choices.length===4,'All four delegated creation modules should be offered');
 assert(choices.some(b=>/Salgsprodukt/.test(b.textContent)),'Sales creation missing');
 assert(!choices.some(b=>/Økonomi/.test(b.textContent)),'Economy is not a service creation choice');
 choices.find(b=>b.textContent.includes('Arrangement')).click(); await tick();
 assert(document.querySelector('.tc-new-service-panel [name="registration_type"]'),'Missing event registration type');
 assert(document.querySelector('.tc-new-service-panel [name="start_date"]'),'Missing event dates');
 assert(!document.querySelector('.tc-new-service-panel [name="memberverification"], .tc-new-service-panel [name="member_exclusive"]'),'Membership settings belong in benefits, not creation');
 const modal=document.querySelector('.tc-new-service-panel').getBoundingClientRect();
 assert(innerWidth<600 ? modal.width>=innerWidth-24 : modal.width>=900,'Creation modal is too narrow');
 assert(document.querySelector('.tc-public-site').getBoundingClientRect().height===before,'New shifted customer preview');
 document.querySelector('.tc-new-service-panel > button').click();
 nav('Hjem');await tick();
 document.querySelector('.tc-island-new').click();await tick();
 assert(document.querySelector('.tc-floating-inspector[aria-label="Rediger valgt innhold"]'),'Home New did not open editor');
 return {passed:true,width:innerWidth,creationChoices:4};
})()`;
chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',checks,[1280,390])
 .catch(error=>{console.error(error);process.exitCode=1;});
