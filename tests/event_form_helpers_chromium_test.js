const assert = require('node:assert/strict');
const childProcess = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const helperSource = fs.readFileSync(require.resolve('../assets/event_form_helpers.js'), 'utf8')
  .replace(/<\/script/gi, '<\\/script');

const html = `<!doctype html>
<html lang="nb">
<body>
  <div class="wizard-content">
    <form id="create-event-form">
      <input name="title">
      <input type="text" name="start_date">
      <input type="time" name="start_time" required>
    </form>
  </div>
  <div class="wizard-content"></div><div class="wizard-content"></div>
  <div class="wizard-content"></div><div class="wizard-content"></div>
  <div class="wizard-content"></div><div class="wizard-content"></div>
  <div class="wizard-step active"></div><div class="wizard-step"></div>
  <div class="wizard-step"></div><div class="wizard-step"></div>
  <div class="wizard-step"></div><div class="wizard-step"></div>
  <div class="wizard-step"></div>
  <div class="wizard-progress"></div><div class="wizard-navigation"></div>
  <button id="wizard-prev"></button><button id="wizard-next"></button>
  <button id="wizard-submit"></button><div id="wizard-success"></div>
  <div id="binding-payment-container"></div><div id="summary-date"></div>
  <script>${helperSource}</script>
  <script>
    (function () {
      const helpers = window.EventFormHelpers;
      const form = document.getElementById('create-event-form');
      const state = {currentStep: 7};
      const title = form.querySelector('[name="title"]');
      const date = form.querySelector('[name="start_date"]');
      const time = form.querySelector('[name="start_time"]');
      const summary = document.getElementById('summary-date');
      const check = (condition, message) => {
        if (!condition) throw new Error(message);
      };
      const setEvent = (name, eventDate, eventTime) => {
        title.value = name;
        date.value = eventDate;
        const timeWrapper = time.parentNode;
        const hourSelect = timeWrapper.querySelector('.event-time-hours');
        const minuteSelect = timeWrapper.querySelector('.event-time-minutes');
        hourSelect.value = eventTime.slice(0, 2);
        hourSelect.dispatchEvent(new Event('change', {bubbles: true}));
        minuteSelect.value = eventTime.slice(3, 5);
        minuteSelect.dispatchEvent(new Event('change', {bubbles: true}));
        time.defaultValue = eventTime;
      };
      const updateSummary = () => helpers.updateSummaryDate(
        form,
        summary,
        (eventDate, eventTime) => eventTime ? eventDate + ' kl. ' + eventTime : eventDate
      );
      const resetAfterSuccess = () => {
        helpers.resetWizard(document, form, state, updateSummary);
      };

      try {
        helpers.attachDateTimeControls(document);

        setEvent('Første arrangement', '02.03.2028', '19:30');
        summary.textContent = 'Gammel dato';
        updateSummary();
        check(summary.textContent === '02.03.2028 kl. 19:30', 'First event date/time summary is displayed');

        const failed = helpers.handleCreateResult(
          {success: false, message: 'Kunne ikke lagre'},
          {
            onSuccess: () => { throw new Error('Failure must not reset'); },
            onFailure: message => check(message === 'Kunne ikke lagre', 'Failure callback receives the API error')
          }
        );
        check(failed === false, 'Failed save is not reported as successful');
        check(title.value === 'Første arrangement', 'Failed save retains the title');
        check(time.value === '19:30', 'Failed save retains the selected time');
        check(summary.textContent === '02.03.2028 kl. 19:30', 'Failed save retains the summary');

        helpers.handleCreateResult({success: true}, {onSuccess: resetAfterSuccess});
        check(title.value === '' && date.value === '', 'First successful save clears event fields');
        check(time.value === '' && time.defaultValue === '', 'Reset clears both hidden time value and default value');
        check(state.currentStep === 1, 'First successful save returns the wizard to step one');
        check(summary.textContent === '', 'First successful save clears the prior summary date');

        setEvent('Neste arrangement', '15.06.2028', '09:15');
        summary.textContent = 'Første arrangementdato';
        updateSummary();
        helpers.handleCreateResult({success: true}, {onSuccess: resetAfterSuccess});
        check(title.value === '' && date.value === '', 'Second successful save also clears event fields');
        check(time.value === '' && time.defaultValue === '', 'Second reset cannot restore the stale default time');
        check(state.currentStep === 1, 'Second successful save returns the wizard to step one');
        check(summary.textContent === '', 'Second successful save leaves no old date summary');

        date.value = '';
        summary.textContent = 'Gammel dato';
        updateSummary();
        check(summary.textContent === '', 'An empty start date clears an existing summary immediately');
        document.body.dataset.testStatus = 'passed';
      } catch (error) {
        document.body.dataset.testStatus = 'failed';
        document.body.dataset.testError = error && error.stack ? error.stack : String(error);
      }
    })();
  </script>
</body>
</html>`;

async function runWithPlaywright(chromium) {
  const browserPath = process.env.CHROMIUM_PATH || '/repl/tools/bin/chromium';
  const browser = await chromium.launch({
    executablePath: browserPath,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  try {
    const page = await browser.newPage();
    await page.setContent(html);
    const status = await page.locator('body').getAttribute('data-test-status');
    const error = await page.locator('body').getAttribute('data-test-error');
    assert.equal(status, 'passed', error || 'Chromium DOM behavior checks failed');
  } finally {
    await browser.close();
  }
}

async function runWithChromiumCli() {
  const browserPath = process.env.CHROMIUM_PATH || '/repl/tools/bin/chromium';
  if (!fs.existsSync(browserPath)) throw new Error(`Chromium executable not found: ${browserPath}`);
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'event-form-chromium-'));
  const fixturePath = path.join(directory, 'event-form.html');
  fs.writeFileSync(fixturePath, html);
  try {
    const output = childProcess.execFileSync(browserPath, [
      '--headless',
      '--no-sandbox',
      '--disable-dev-shm-usage',
      '--no-first-run',
      '--no-default-browser-check',
      '--dump-dom',
      `file://${fixturePath}`
    ], {encoding: 'utf8', timeout: 30000, maxBuffer: 4 * 1024 * 1024});
    assert.match(output, /data-test-status="passed"/, output.match(/data-test-error="([^"]+)"/)?.[1] || 'Chromium DOM behavior checks failed');
  } finally {
    fs.rmSync(directory, {recursive: true, force: true});
  }
}

async function main() {
  let chromium;
  try {
    ({chromium} = require('playwright'));
  } catch (_) {
    // Use the installed Chromium executable when this workspace has no Playwright package.
  }

  if (chromium) {
    await runWithPlaywright(chromium);
    console.log('event form Playwright DOM behavior tests passed');
  } else {
    await runWithChromiumCli();
    console.log('event form Chromium DOM behavior tests passed (Playwright package unavailable)');
  }
}

main().catch(error => {
  console.error(error);
  process.exitCode = 1;
});