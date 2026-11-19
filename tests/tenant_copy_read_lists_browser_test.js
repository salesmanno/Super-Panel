const chromiumFixture = require('./chromium_fixture');
const checks = `(async()=>{
 const assert=(value,message)=>{if(!value)throw Error(message)};
 const tick=()=>new Promise(resolve=>setTimeout(resolve,120));
 for(let n=0;n<50&&!document.querySelector('.tc-mode-island');n++)await tick();
 [...document.querySelectorAll('.tc-site-nav button')].find(button=>button.textContent==='Tjenester').click();await tick();
 const service = title=>[...document.querySelectorAll('button.tc-public-service')].find(button=>button.textContent.toLowerCase().includes(title.toLowerCase()));
 service('Bergsøy Medlem').click();await tick();
 let panel=document.querySelector('.tc-reader-data-panel');
 assert(panel.querySelector('h2').textContent==='Medlemmer Bergsøy Medlem','Membership heading incorrect');
 assert(panel.querySelectorAll('.tc-read-data-row').length===2,'Wrong number of members for selected plan');
 assert(!panel.textContent.includes('Bergsøy Medlem Styre'),'Other membership plan included');
 assert(!panel.textContent.includes('membership_plans'),'Database table exposed');
 panel.querySelector('button').click();
 service('ROMJULSDANS').click();await tick();
 panel=document.querySelector('.tc-reader-data-panel');
 assert(panel.querySelector('h2').textContent.toLowerCase()==='påmeldte romjulsdans','Participant heading incorrect');
 assert(!/events_eventdata|events_checkin|→/.test(panel.textContent),'Database references exposed');
 assert(panel.querySelectorAll('.tc-read-data-row').length===2,'Participants missing');
 return {passed:true,width:innerWidth,planScopedMembers:2,eventParticipants:2};
})()`;
chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',checks,[1280,390])
 .catch(error=>{console.error(error);process.exitCode=1;});
