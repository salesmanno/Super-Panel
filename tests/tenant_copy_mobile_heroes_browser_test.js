const chromiumFixture = require('./chromium_fixture');

const checks = `(async () => {
  const check = (condition, message) => { if (!condition) throw new Error(message); };
  const settle = () => new Promise(resolve => setTimeout(resolve, 120));
  for (let n = 0; n < 60 && !document.querySelector('.tc-site-nav'); n++) await settle();
  const open = async name => {
    [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === name).click();
    await settle();
    await Promise.all([...document.querySelectorAll('.tc-public-site img')].map(img => img.decode().catch(() => {})));
  };
  const mobile = innerWidth <= 767;
  await open('Hjem');
  const home = document.querySelector('.tc-home-hero');
  const homeRect = home.getBoundingClientRect();
  const homeImage = home.querySelector('img');
  const homeImageRect = homeImage.getBoundingClientRect();
  const copy = home.querySelector('.tc-home-hero-copy').getBoundingClientRect();
  if (mobile) {
    check(homeImageRect.bottom <= copy.top, 'Home image must precede the text on mobile');
    check(getComputedStyle(homeImage).objectFit === 'contain', 'Home crops the source image');
    check(getComputedStyle(home.querySelector('h1')).textAlign === 'center', 'Home title must be centered');
  } else {
    check(homeImageRect.left > copy.left, 'Desktop home no longer has its image on the right');
  }
  await open('Tjenester');
  const intro = document.querySelector('.tc-service-intro');
  const serviceRect = intro.getBoundingClientRect();
  const card = intro.querySelector('.tc-latest-event');
  const image = card.querySelector('img');
  const imageRect = image.getBoundingClientRect();
  const serviceCopy = card.querySelector('span').getBoundingClientRect();
  const heading = intro.querySelector('h1').getBoundingClientRect();
  check(imageRect.right <= serviceCopy.left + 1, 'Featured event must retain side-by-side image and text');
  if (mobile) {
    check(heading.bottom < card.getBoundingClientRect().top, 'Services heading must precede featured event');
    check(getComputedStyle(image).objectFit === 'contain', 'Featured poster is cropped');
    check(Math.abs(homeRect.height - serviceRect.height) <= 2, 'Top areas have different mobile heights: ' + homeRect.height + '/' + serviceRect.height);
  }
  check(document.documentElement.scrollWidth <= innerWidth, 'Page overflows horizontally');
  return {passed:true,width:innerWidth,homeHeight:Math.round(homeRect.height),servicesHeight:Math.round(serviceRect.height),mobile};
})()`;

chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',
  checks, [320, 390, 440, 600, 767, 1280])
  .catch(error => { console.error(error); process.exitCode = 1; });
