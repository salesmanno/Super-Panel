const chromiumFixture = require('./chromium_fixture');

const checks = `(async () => {
  const wait = () => new Promise(resolve => setTimeout(resolve, 120));
  const check = (value, message) => { if (!value) throw new Error(message); };
  for (let n = 0; n < 60 && !document.querySelector('.tc-site-nav'); n++) await wait();
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Om oss').click();
  await wait();
  await wait();
  const island = document.querySelector('.tc-mode-island');
  check(getComputedStyle(island).opacity === '0' && island.inert, 'Island must fade out and be inactive on Om oss');
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
  const website = document.querySelector('.tc-website-settings-grid');
  const headerMode = website.querySelector('[name="header_mode"]');
  const footerMode = website.querySelector('[name="footer_mode"]');
  check(headerMode && footerMode, 'Website must offer header and footer modes');
  const setMode = async (field, value) => {
    field.value = value;
    field.dispatchEvent(new Event('change', {bubbles:true}));
    await wait();
  };
  check(headerMode.value === 'static' && footerMode.value === 'static', 'Modes must initially be static');
  await setMode(headerMode, 'dynamic');
  await setMode(footerMode, 'dynamic');
  check(getComputedStyle(document.querySelector('.tc-site-header')).position === 'static', 'Dynamic header must scroll with the page');
  check(getComputedStyle(document.querySelector('.tc-site-nav')).position === 'static', 'Dynamic header navigation must scroll too');
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Hjem').click();
  await wait();
  check(getComputedStyle(document.querySelector('.tc-site-footer')).position !== 'fixed', 'Dynamic footer must remain in document flow');
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Om oss').click();
  await wait();
  document.querySelectorAll('.tc-settings-nav [role=tab]')[4].click();
  await wait();
  check(document.querySelector('.tc-website-settings-grid [name="header_mode"]').value === 'dynamic', 'Modes must survive page navigation');
  await setMode(document.querySelector('.tc-website-settings-grid [name="header_mode"]'), 'static');
  await setMode(document.querySelector('.tc-website-settings-grid [name="footer_mode"]'), 'static');
  check(getComputedStyle(document.querySelector('.tc-site-header')).position === 'sticky', 'Static header must stay visible');
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Hjem').click();
  await wait();
  check(getComputedStyle(document.querySelector('.tc-site-footer')).position === 'fixed', 'Static footer must stay visible');
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Om oss').click();
  await wait();
  document.querySelectorAll('.tc-settings-nav [role=tab]')[0].click();
  await wait();
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Hjem').click();
  await wait();
  await wait();
  check(getComputedStyle(island).opacity === '1' && !island.inert, 'Island must return on Hjem');
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Om oss').click();
  await wait();
  await wait();
  document.querySelectorAll('.tc-settings-nav [role=tab]')[4].click();
  await wait();
  document.querySelector('.tc-website-settings-grid .tc-position-settings').scrollIntoView({block:'center'});
  return {passed: true, width: innerWidth, tabs: tabs.length, navHeight: document.querySelector('.tc-settings-nav').offsetHeight};
})()`;

chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',
  checks, [320, 390, 600, 760, 1280])
  .catch(error => { console.error(error); process.exitCode = 1; });
