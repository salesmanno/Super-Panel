const chromiumFixture = require('./chromium_fixture');

const checks = `(async () => {
  const check = (condition, message) => { if (!condition) throw new Error(message); };
  const settle = () => new Promise(resolve => setTimeout(resolve, 120));
  for (let attempt = 0; attempt < 60 && !document.querySelector('.tc-mode-island'); attempt++) {
    await settle();
  }
  const dock = () => document.querySelector('.tc-mode-island').getBoundingClientRect();
  const geometry = () => JSON.stringify([...document.querySelectorAll('.tc-public-site header, .tc-public-site img, .tc-home-hero, .tc-story-wrap, .tc-public-service, .tc-site-footer')].map(el => {
    const r = el.getBoundingClientRect();
    return [Math.round(r.x), Math.round(r.y + scrollY), Math.round(r.width), Math.round(r.height)];
  }));
  const site = document.querySelector('.tc-public-site');
  await Promise.all([...site.querySelectorAll('img')].map(img => img.decode().catch(() => {})));
  let modes = 0;
  for (const pageName of ['Hjem', 'Tjenester']) {
    [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === pageName).click();
    await settle();
    await Promise.all([...site.querySelectorAll('img')].map(img => img.decode().catch(() => {})));
    window.scrollTo(0, 160);
    await settle();
    const baseline = geometry();
    const y = scrollY;
    const position = dock().top;
    for (const button of document.querySelectorAll('.tc-mode-island button[aria-pressed]')) {
      button.focus({preventScroll:true});
      button.click();
      await settle();
      check(scrollY === y, pageName + ': mode changed scroll position');
      check(geometry() === baseline, pageName + ': ' + button.textContent + ' changed preview geometry: ' + JSON.stringify({before:JSON.parse(baseline),after:JSON.parse(geometry())}));
      check(Math.abs(dock().top - position) < 1, 'Mode moved the dock');
      check(!site.querySelector('.tc-card-hide, .tc-edit-marker, .tc-image-edit-hint'), 'Admin buttons appeared inside preview');
      modes++;
    }
    window.scrollTo(0, 0);
    await settle();
    check(Math.abs(dock().top - position) < 1, 'Scroll moved the dock');
    check(Math.abs(dock().left + dock().width / 2 - document.documentElement.clientWidth / 2) < 1, 'Dock not centered');
    check(innerHeight - dock().bottom >= 23, 'Dock not positioned near bottom');
  }
  return {passed:true, modes, width:innerWidth};
})()`;

chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy', checks, [1280, 390])
  .catch(error => { console.error(error); process.exitCode = 1; });
