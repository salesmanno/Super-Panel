const assert = require('node:assert/strict');
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
body{margin:0;font:16px sans-serif}#rental-module{width:100%;padding:12px;box-sizing:border-box}
.card-body{max-width:1100px;margin:auto}.input-group{display:flex;min-width:0}.input-group input{min-width:0;flex:1}
.form-control{box-sizing:border-box;width:100%;min-width:0}.input-group-text{white-space:nowrap}
</style></head><body><main id="rental-module"><form id="fixtureForm"><div class="card-body">
<section class="rental-week-editor" data-rental-week-editor data-initial-pricing-model="weekly">
<h6 class="rental-week-editor-title">Prisoppsett</h6>
<div class="rental-model-choices" role="radiogroup"><label class="rental-model-choice"><input type="radio" name="pricing_model" value="legacy"> Eldre</label><label class="rental-model-choice"><input type="radio" name="pricing_model" value="weekly" checked> Ukespriser</label></div>
<div data-weekly-rate-panel><div class="rental-week-rates">${['Mandag','Tirsdag','Onsdag','Torsdag','Fredag','Lørdag','Søndag'].map((day,i)=>`<div class="rental-week-rate"><div class="rental-week-rate-heading"><label>${day}</label><button type="button" class="rental-weekday-pick" data-weekday-pick="${i+1}" aria-pressed="false"><span></span></button></div><div class="input-group"><input name="daily_prices[${['monday','tuesday','wednesday','thursday','friday','saturday','sunday'][i]}]" value="100.00"><span class="input-group-text">NOK</span></div></div>`).join('')}</div></div>
<div data-weekly-groups-panel><div class="rental-week-group-panel">
<p>Pick days.</p><p class="rental-week-status" data-weekly-status role="status" aria-live="polite"></p><div class="rental-week-group-list" data-weekly-group-list></div>
<div class="rental-week-price-form" data-weekly-price-form hidden role="group"><h6 data-weekly-price-title></h6><p data-price-pair></p><label for="total">Total</label><input id="total" data-weekly-price-input type="number" min="0" max="99999999.99" step="0.01"><p data-weekly-price-error role="alert" hidden></p><button type="button" data-dialog-cancel>Avbryt</button><button type="button" data-dialog-confirm>Bekreft pris</button></div>
<input type="hidden" data-weekly-groups name="price_groups" value="[]"></div></div>
</section><div data-legacy-pricing-control><input name="rental_price" value="75"></div></div></form><div id="fakeRentalModal">Modal remains open</div></main>
<script>window.escapeBubbled=false;document.addEventListener('keydown',e=>{if(e.key==='Escape')window.escapeBubbled=true;});</script><script>${editorScript}</script></body></html>`;

const checks = `(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const wait=()=>new Promise(resolve=>setTimeout(resolve,30));
 const editor=document.querySelector('[data-rental-week-editor]');
 const pick=n=>editor.querySelector('[data-weekday-pick="'+n+'"]');
 const panel=editor.querySelector('[data-weekly-price-form]');
 const input=editor.querySelector('[data-weekly-price-input]');
 const confirm=editor.querySelector('[data-dialog-confirm]');
 const cancel=editor.querySelector('[data-dialog-cancel]');
 const groups=()=>JSON.parse(editor.querySelector('[data-weekly-groups]').value);
 await wait();
 check(!document.querySelector('.rental-week-price-dialog'),'No global overlay/modal is used');
 check(pick(1).parentElement.classList.contains('rental-week-rate-heading'),'Day checkbox sits beside its daily rate');
 pick(1).click();pick(3).click();
 await wait();
 check(!panel.hidden,'Selecting a day pair reveals inline price field');
 check(panel.closest('.rental-week-editor')===editor,'Price field stays within tinted editor');
 check(document.activeElement===input,'Inline editor receives focus');
 input.value='0';confirm.click();
 check(groups().length===1&&groups()[0].price==='0.00','Zero group price is accepted and stored as decimal string');
 check(!document.querySelector('.rental-week-price-dialog'),'No overlay was appended to body');
 check(pick(1).disabled&&pick(3).disabled,'Occupied days are unavailable for another group');
 editor.querySelector('[data-group-edit="1"]').click();
 input.value='100000000';confirm.click();
 check(!editor.querySelector('[data-weekly-price-error]').hidden&&groups()[0].price==='0.00','Backend amount ceiling is enforced before accepting a group price');
 input.value='123.45';confirm.click();
 check(groups()[0].price==='123.45','Existing price group is editable');
 pick(4).click();pick(6).click();
 check(!panel.hidden,'Second pair opens inline field');
 cancel.click();
 check(panel.hidden&&groups().length===1,'Avbryt cancels without adding a group');
 check(document.activeElement===pick(6),'Cancel button restores focus to the day control');
 pick(4).click();pick(6).click();
 input.value='999';input.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
 check(panel.hidden&&groups().length===1,'Escape cancels without adding a group');
 check(document.activeElement===pick(6),'Cancel restores focus to the day control');
 check(!window.escapeBubbled,'Escape does not bubble to or dismiss the rental modal');
 pick(7).click();pick(4).click();
 check(panel.hidden&&groups().length===1&&editor.querySelector('[data-weekly-status]').textContent.includes('overlapper'),'Wrapping range that crosses an existing group is rejected');
 pick(5).click();pick(7).click();input.value='900.00';confirm.click();
 check(groups().length===2&&groups()[1].price==='900.00','A second non-overlapping group can be created');
 check([...editor.querySelectorAll('[data-weekday-pick]')].every(button=>button.disabled),'Adding more than two groups is blocked');
 const legacy=editor.querySelector('[name="pricing_model"][value="legacy"]');
 legacy.checked=true;legacy.dispatchEvent(new Event('change',{bubbles:true}));
 check(!document.querySelector('[data-legacy-pricing-control]').hidden,'Legacy rates are restored when selected');
 check(editor.querySelector('[name="daily_prices[monday]"]').disabled,'Weekly rates are inactive in legacy mode');
 const weekly=editor.querySelector('[name="pricing_model"][value="weekly"]');
 weekly.checked=true;weekly.dispatchEvent(new Event('change',{bubbles:true}));
 check(editor.querySelector('[name="daily_prices[monday]"]').required&&!editor.querySelector('[name="daily_prices[monday]"]').disabled,'Weekly rates reactivate');
 editor.querySelector('[data-group-remove="1"]').click();
 check(groups().length===1&&groups()[0].slot===1,'Removing a group keeps remaining slot canonical');
 const corrupt=editor.cloneNode(true);corrupt.dataset.weekEditorReady='';corrupt.querySelector('[data-weekly-groups]').value=JSON.stringify([
  {slot:1,start_day:1,end_day:3,price:'100.00'},{slot:2,start_day:7,end_day:4,price:'200.00'}
 ]);
 document.querySelector('#fixtureForm').append(corrupt);window.RentalWeekEditor.setup(corrupt);
 check(corrupt.querySelector('[data-weekly-group-list]').textContent.includes('overlapper'),'Invalid stored groups show a visible validation error');
 let invalidError='';try{window.RentalWeekEditor.groups(corrupt)}catch(error){invalidError=error.message}
 check(invalidError.includes('overlapper'),'Invalid existing groups are not silently dropped and block save payload');
 check(document.documentElement.scrollWidth<=innerWidth,'Document has no horizontal overflow at '+innerWidth);
 check(editor.scrollWidth<=editor.clientWidth,'Editor has no horizontal overflow at '+innerWidth);
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
        await new Promise(resolve=>setTimeout(resolve,500));
        if (!process.env.KEEP_RENTAL_UI_FIXTURE) await fs.rm(directory,{recursive:true,force:true});
    }
})().catch(error=>{console.error(error);process.exitCode=1;});
