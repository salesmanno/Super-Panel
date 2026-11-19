const chromiumFixture = require('./chromium_fixture');

const checks = `(async () => {
  const wait = () => new Promise(resolve => setTimeout(resolve, 120));
  const check = (value, message) => { if (!value) throw new Error(message); };
  for (let n = 0; n < 60 && !document.querySelector('.tc-site-nav'); n++) await wait();
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Om oss').click();
  await wait();
  const nav = document.querySelector('.tc-settings-nav');
  const tabs = [...nav.querySelectorAll('[role=tab]')];
  check(tabs.length === 5, 'All five areas must remain available');
  check(nav.scrollWidth <= nav.clientWidth + 1, 'Tab navigation scrolls horizontally');
  if (innerWidth <= 760) {
    check(nav.getBoundingClientRect().height <= 110, 'Mobile tab navigation is too tall');
    for (const tab of tabs) {
      const rect = tab.getBoundingClientRect();
      check(rect.left >= 0 && rect.right <= innerWidth, 'Tab is outside mobile viewport');
      check(rect.height >= 44, 'Touch target is too small');
      check(getComputedStyle(tab.querySelector('small')).display === 'none', 'Verbose tab descriptions remain visible');
    }
  }
  for (const tab of tabs) {
    tab.click();
    await wait();
    check(tab.getAttribute('aria-selected') === 'true', 'Selected state was not updated');
    check(document.querySelector('.tc-settings-main-head h2').textContent === tab.querySelector('b').textContent, 'Wrong settings area opened');
  }
  tabs[0].click();
  await wait();
  return {passed: true, width: innerWidth, tabs: tabs.length, navHeight: nav.offsetHeight};
})()`;

chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',
  checks, [320, 390, 600, 760, 1280])
  .catch(error => { console.error(error); process.exitCode = 1; });
