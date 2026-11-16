const fs = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');
const chromiumFixture = require('./chromium_fixture');
if (typeof WebSocket === 'undefined') {
    const {spawnSync} = require('node:child_process');
    const child = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio:'inherit'});
    process.exit(child.status ?? 1);
}

const editorScript = require('node:fs').readFileSync(path.join(__dirname, '../assets/rental_week_editor.js'), 'utf8');
const editorCss = require('node:fs').readFileSync(path.join(__dirname, '../assets/rental_week_editor.css'), 'utf8');
const fixture = `<!doctype html><html lang="nb"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>
${editorCss}
body{margin:0;font:16px sans-serif}#rental-module,#rentalModal{width:100%;padding:12px;box-sizing:border-box;--site-header-bg-color:#2d5598}
.card-body{max-width:1100px;margin:auto}.form-control{box-sizing:border-box;width:100%;min-width:0}.input-group-text{white-space:nowrap}
#rentalModal .rental-edit-base-price .form-control{min-height:44px;font-size:1rem;font-weight:700}
</style></head><body><main id="rental-module"><form id="fixtureForm"><div class="card-body">
<section class="rental-week-editor" data-rental-week-editor data-initial-pricing-model="weekly">
 <h6 class="rental-week-editor-title">Ukens priser</h6>
 <p class="rental-week-editor-copy">Sett dagsprisene. Koble dager hvis perioden skal ha én samlet pris.</p>
 <div class="rental-week-rates-scroll" role="region" aria-label="Ukens dagspriser">
  <div class="rental-week-rates">${['Mandag','Tirsdag','Onsdag','Torsdag','Fredag','Lørdag','Søndag'].map((day,i)=>`<div class="rental-week-rate"><button type="button" class="rental-weekday-pick" data-weekday-pick="${i+1}" aria-pressed="false"><span class="rental-weekday-name">${day}</span><span class="rental-weekday-mark"></span></button><div class="rental-week-rate-price"><input type="number" data-daily-rate="${i+1}" name="daily_prices[${['monday','tuesday','wednesday','thursday','friday','saturday','sunday'][i]}]" min="0" step="0.01" value=""><span>NOK</span></div></div>`).join('')}</div>
 </div>
 <div class="rental-week-group-panel"><div class="rental-week-group-heading"><strong>Samlet pris for dager</strong><span class="small">Pris per forekomst</span></div>
  <p class="rental-weekday-pick-legend">Trykk fra-dag, deretter til-dag. Søndag–mandag kobler de to dagene over ukegrensen.</p>
  <div class="rental-week-price-form" data-weekly-price-form hidden role="group"><span class="rental-week-eyebrow">Prisgruppe</span><h6 data-weekly-price-title></h6><p data-price-pair></p><label for="total">Totalpris</label><div class="rental-week-price-entry"><input id="total" class="form-control form-control-sm" data-weekly-price-input type="number" min="0" max="99999999.99" step="0.01"><span class="input-group-text">NOK</span></div><p data-weekly-price-error class="rental-week-dialog-error" role="alert" hidden></p><div class="rental-week-dialog-actions"><button type="button" data-dialog-cancel>Avbryt</button><button type="button" data-dialog-confirm>Bekreft pris</button></div></div>
  <p class="rental-week-status" data-weekly-status role="status" aria-live="polite"></p><div class="rental-week-group-list" data-weekly-group-list></div><input type="hidden" data-weekly-groups name="price_groups" value="[]">
 </div>
</section></div></form></main>
<section id="rentalModal" aria-label="Rental edit modal fixture"><div class="rental-edit-base-price"><div class="card-body"></div></div></section>
<script>window.escapeBubbled=false;document.addEventListener('keydown',e=>{if(e.key==='Escape')window.escapeBubbled=true;});</script><script>${editorScript}</script></body></html>`;

const checks = `(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const wait=()=>new Promise(resolve=>setTimeout(resolve,30));
 const editor=document.querySelector('[data-rental-week-editor]');
 const pick=n=>editor.querySelector('[data-weekday-pick="'+n+'"]');
 const rate=n=>editor.querySelector('[data-daily-rate="'+n+'"]');
 const panel=editor.querySelector('[data-weekly-price-form]');
 const input=editor.querySelector('[data-weekly-price-input]');
 const confirm=editor.querySelector('[data-dialog-confirm]');
 const cancel=editor.querySelector('[data-dialog-cancel]');
 const groups=()=>JSON.parse(editor.querySelector('[data-weekly-groups]').value);
 await wait();
  const modalHost=document.querySelector('#rentalModal .card-body');
  const modalEditor=editor.cloneNode(true);
  modalEditor.dataset.weekEditorReady='';
  modalHost.append(modalEditor);
  window.RentalWeekEditor.setup(modalEditor);
  await wait();
  const modalPick=n=>modalEditor.querySelector('[data-weekday-pick="'+n+'"]');
  const modalRate=n=>modalEditor.querySelector('[data-daily-rate="'+n+'"]');
  const pill=modalPick(1),mark=pill.querySelector('.rental-weekday-mark');
  const pillStyle=getComputedStyle(pill),markStyle=getComputedStyle(mark);
  const idlePillBackground=pillStyle.backgroundColor;
  const pillRect=pill.getBoundingClientRect(),markRect=mark.getBoundingClientRect();
  check(pillStyle.borderRadius.split(' ').every(part=>parseFloat(part)>=20),'Sibling modal day button renders as a fully rounded pill');
  check(parseFloat(markStyle.width)>=16&&parseFloat(markStyle.height)>=16&&markStyle.display!=='none'&&markStyle.visibility==='visible','Modal checkbox remains visibly square at a usable size');
  check(Math.abs((markRect.left+markRect.width/2)-pillRect.right)<=3
   &&Math.abs((markRect.top+markRect.height/2)-pillRect.bottom)<=3,'Checkbox center interrupts the lower-right pill outline');
  check(pillStyle.backgroundColor!=='rgba(0, 0, 0, 0)'&&markStyle.boxShadow!=='none','Checkbox backing visibly interrupts the pill border');
  modalPick(1).click();
  check(modalPick(1).getAttribute('aria-pressed')==='true'
   &&getComputedStyle(modalPick(1)).backgroundColor!==idlePillBackground,'Selected day retains pressed state and uses the header accent surface');
  check(parseFloat(getComputedStyle(modalRate(1)).opacity)<1,'Sibling modal daily rate is subdued when a day is selected');
  modalPick(1).click();
  const modalXs=[...modalEditor.querySelectorAll('[data-weekday-pick]')].map(button=>button.getBoundingClientRect().left);
  check(modalXs.every((value,index)=>index===0||value>modalXs[index-1]),'Sibling modal days remain in a horizontal weekday strip');
  const modalInputStyle=getComputedStyle(modalRate(1));
  check(parseFloat(modalInputStyle.minHeight)<=30&&parseFloat(modalInputStyle.height)<=31
   &&parseFloat(modalInputStyle.fontSize)<14,'Compact week price field overrides base-price form-control styling in the modal');
  const modalStrip=modalEditor.querySelector('.rental-week-rates-scroll');
  if(innerWidth<500)check(modalStrip.scrollWidth>modalStrip.clientWidth,'Sibling modal strip scrolls internally on mobile without stacking');
  else check(modalStrip.scrollWidth<=modalStrip.clientWidth+1,'Sibling modal strip fits on wide screens ('+modalStrip.scrollWidth+'/'+modalStrip.clientWidth+', editor '+modalEditor.clientWidth+')');
 [...editor.querySelectorAll('[data-daily-rate]')].forEach((field,index)=>field.value=String(105.25+index));
 const originalRates=[...editor.querySelectorAll('[data-daily-rate]')].map(field=>field.value);
 check(!editor.querySelector('[name="pricing_model"]')&&!document.querySelector('[data-legacy-pricing-control]'),'No legacy model or price markup remains');
 check(pick(1).querySelector('.rental-weekday-name')&&pick(1).querySelector('.rental-weekday-mark'),'Day name and check mark share the outlined pill');
 check(!rate(1).disabled&&!rate(1).readOnly,'Daily fields stay editable, including when visually subdued');
 pick(7).click();
 check(pick(7).classList.contains('is-pending')&&rate(7).classList.contains('is-coupled'),'First selected day and its daily input are visibly marked');
 check(!rate(7).disabled&&!rate(7).readOnly,'Pending daily price remains editable');
 pick(7).click();
 check(!pick(7).classList.contains('is-pending')&&!rate(7).classList.contains('is-coupled')&&panel.hidden,'Clicking the same day resets an incomplete selection');
 pick(4).click();pick(5).click();input.value='345';
 input.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
 check(panel.hidden&&groups().length===0&&!window.escapeBubbled,'Escape cancels inline price entry without dismissing a parent modal');
 pick(7).click();pick(6).click();
 await wait();
 check(!panel.hidden,'Selecting two days opens inline total price field');
 check(groups().length===0,'Group is not saved until a combined price is confirmed');
 check([6,7].every(n=>pick(n).classList.contains('is-pending')&&rate(n).classList.contains('is-coupled')),'Reversed clicks normalize to Sat–Sun and highlight the inclusive range');
 check(!rate(6).disabled&&!rate(6).readOnly,'Grouped daily rates remain editable');
 confirm.click();
 check(!editor.querySelector('[data-weekly-price-error]').hidden&&groups().length===0,'Combined price is required before confirming a group');
 input.value='640.75';confirm.click();
 check(groups().length===1&&groups()[0].start_day===6&&groups()[0].end_day===7&&groups()[0].price==='640.75','Reverse day selection stores an ascending non-wrapping range');
 check(document.activeElement===editor.querySelector('[data-group-edit="1"]'),'Confirming combined price moves keyboard focus to its saved summary');
 check([6,7].every(n=>pick(n).classList.contains('is-in-group')&&rate(n).classList.contains('is-coupled')),'Saved group days stay highlighted and subdued');
 check(pick(6).querySelector('.rental-weekday-range').textContent==='<'&&pick(7).querySelector('.rental-weekday-range').textContent==='>','Saved weekend has start and end symbols');
 check(!rate(6).required&&!rate(7).required,'Saved groups do not require redundant daily prices');
 check(!rate(6).disabled&&!rate(6).readOnly,'Saved group daily rates remain editable');
 editor.querySelector('[data-group-edit="1"]').click();input.value='800';cancel.click();
 check(groups()[0].price==='640.75'&&panel.hidden,'Cancel editing leaves saved price untouched');
 check(rate(6).classList.contains('is-coupled'),'Canceling group edit restores saved group styling');
 editor.querySelector('[data-group-edit="1"]').click();input.value='100000000';confirm.click();
 check(!editor.querySelector('[data-weekly-price-error]').hidden&&groups()[0].price==='640.75','Amount ceiling is enforced without overwriting the group');
 input.value='700.25';confirm.click();
 check(groups()[0].price==='700.25','Existing group price can be edited');
 pick(1).click();pick(3).click();
 check([1,2,3].every(n=>pick(n).classList.contains('is-pending')),'Intermediate days highlight continuously before price confirmation');
 check([1,2,3].map(n=>pick(n).querySelector('.rental-weekday-range').textContent).join('')==='<->','Pending interval shows start, middle and end markers');
 input.value='350';cancel.click();
 check(groups().length===1&&panel.hidden&&![1,2,3].some(n=>rate(n).classList.contains('is-coupled')),'Canceling a pending group restores daily price styling');
 check(!rate(2).disabled&&!rate(2).readOnly,'Cancelled range rates remain editable');
 pick(1).click();pick(3).click();input.value='350';confirm.click();
 check(groups().length===2&&groups()[1].start_day===1&&groups()[1].end_day===3,'A second disjoint group can be confirmed');
 check([...editor.querySelectorAll('[data-weekday-pick]')].every(button=>button.disabled),'Maximum of two groups disables additional day selection');
 const range=window.RentalWeekEditor.validateRange(7,6,[]);
 check(range.valid&&range.start===6&&range.end===7&&range.days.join(',')==='6,7','Range validator normalizes endpoints ascending and never wraps');
 check(!window.RentalWeekEditor.validateRange(4,4,[]).valid,'A single-day group is forbidden');
 const sundayMonday=window.RentalWeekEditor.validateRange(7,1,[]);
 check(sundayMonday.valid&&sundayMonday.start===7&&sundayMonday.end===1&&sundayMonday.days.join(',')==='7,1','Sunday first then Monday is the two-day boundary wrap');
 const mondaySunday=window.RentalWeekEditor.validateRange(1,7,[]);
 check(mondaySunday.valid&&mondaySunday.start===1&&mondaySunday.end===7&&mondaySunday.days.length===7,'Monday first then Sunday remains the full ascending week');
 editor.querySelector('[data-group-remove="1"]').click();
 check(groups().length===1&&groups()[0].slot===1,'Removing a group reindexes remaining groups and retains the other');
 editor.querySelector('[data-group-remove="1"]').click();
 check(groups().length===0&&[1,2,3].every(n=>!rate(n).classList.contains('is-coupled')),'Removing the last group restores daily price styling');
 check([...editor.querySelectorAll('[data-daily-rate]')].every((field,index)=>field.value===originalRates[index]),'Adding and removing groups preserves every daily price value');
 pick(7).click();pick(1).click();
 check([1,7].every(n=>pick(n).classList.contains('is-pending'))&&[2,3,4,5,6].every(n=>!pick(n).classList.contains('is-pending')),'Sunday–Monday wrap highlight marks only its two adjacent endpoints');
 input.value='125';confirm.click();
 check(groups()[0].start_day===7&&groups()[0].end_day===1&&editor.querySelector('[data-group-slot="1"]').textContent.includes('Søndag–Mandag'),'Sunday–Monday stores and displays the canonical wrap range');
 editor.querySelector('[data-group-remove="1"]').click();
 const corrupt=editor.cloneNode(true);corrupt.dataset.weekEditorReady='';
 corrupt.querySelector('[data-weekly-groups]').value=JSON.stringify([{slot:1,start_day:7,end_day:6,price:'200'}]);
 document.querySelector('#fixtureForm').append(corrupt);window.RentalWeekEditor.setup(corrupt);
 check(corrupt.querySelector('[data-weekly-group-list]').textContent.includes('stigende'),'Loaded reversed ranges visibly fail validation');
 let invalidError='';try{window.RentalWeekEditor.groups(corrupt)}catch(error){invalidError=error.message}
 check(invalidError.includes('stigende'),'Loaded reversed ranges cannot enter save payload');
 const strip=editor.querySelector('.rental-week-rates-scroll');
 const days=[...editor.querySelectorAll('[data-weekday-pick]')].map(button=>button.getBoundingClientRect().left);
 check(days.every((value,index)=>index===0||value>days[index-1]),'Weekday geometry remains in Monday–Sunday order at '+innerWidth);
 const inputWidth=rate(1).getBoundingClientRect().width;
 check(inputWidth>=48&&inputWidth<=90,'Daily price field stays compact and fits several digits ('+inputWidth+'px)');
 check(document.documentElement.scrollWidth<=innerWidth,'Document has no horizontal overflow at '+innerWidth);
 check(editor.scrollWidth<=editor.clientWidth,'Editor contains its horizontal strip at '+innerWidth);
 if(innerWidth<500)check(strip.scrollWidth>strip.clientWidth,'Mobile weekday strip scrolls internally rather than stacking');
 else check(strip.scrollWidth<=strip.clientWidth+1,'Wide weekday strip fits without internal overflow');
 corrupt.remove();
 strip.scrollLeft=0;
 pick(6).click();pick(7).click();input.value='3000.00';confirm.click();
 pick(2).click();pick(4).click();input.value='825.50';confirm.click();
 check(panel.hidden&&groups().length===2,'Confirmed groups leave a compact summary rather than an open price form');
 check(editor.querySelector('[data-group-slot="2"]').classList.contains('rental-week-group-2')
  &&pick(3).classList.contains('is-group-two'),'Second group has a distinct outline in both strip and summary');
 return {passed:true,width:innerWidth};
})()`;

(async()=>{
    const directory=await fs.mkdtemp(path.join(os.tmpdir(),'rental-week-editor-'));
    try {
        const file=path.join(directory,'fixture.html');
        await fs.writeFile(file,fixture);
        await chromiumFixture(file,checks,[390,800,1280],{outputDir:directory});
        console.log('Rental week editor Chromium interactions passed at 390px, 800px and 1280px.');
    } finally {
        await new Promise(resolve=>setTimeout(resolve,300));
        if (!process.env.KEEP_RENTAL_UI_FIXTURE) await fs.rm(directory,{recursive:true,force:true});
    }
})().catch(error=>{console.error(error);process.exitCode=1;});
