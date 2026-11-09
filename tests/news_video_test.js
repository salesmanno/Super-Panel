const assert = require('node:assert/strict');
const {parse} = require('../assets/news_video.js');
const cases = [
  ['https://youtu.be/abcdefghijk', 'youtube', 'https://www.youtube-nocookie.com/embed/abcdefghijk'],
  ['https://www.youtube.com/watch?v=abcdefghijk', 'youtube'],
  ['https://www.youtube.com/shorts/abcdefghijk', 'youtube'],
  ['https://www.youtube-nocookie.com/embed/abcdefghijk', 'youtube'],
  ['https://vimeo.com/123456789', 'vimeo', 'https://player.vimeo.com/video/123456789'],
  ['https://vimeo.com/123456789/ab1234', 'vimeo', 'https://player.vimeo.com/video/123456789?h=ab1234'],
  ['https://player.vimeo.com/video/123456789?h=ab1234', 'vimeo', 'https://player.vimeo.com/video/123456789?h=ab1234'],
  ['https://cdn.example.test/movie.MP4?token=test', 'file'],
  ['https://cdn.example.test/movie.webm', 'file'],
];
for (const [url, provider, src] of cases) {
  const parsed = parse(url);
  assert.equal(parsed?.provider, provider, url);
  if (src) assert.equal(parsed.src, src, url);
}
for (const url of ['javascript:alert(1)', 'ftp://cdn.example.test/a.mp4', 'https://evil.example/watch?v=abcdefghijk', 'https://youtube.com.evil.test/watch?v=abcdefghijk', 'https://vimeo.com/1234/invalid-hash', 'https://u:p@cdn.example.test/a.mp4', 'https://vimeo.com/123?h[]=x', 'https://youtu.be/short', 'not a url']) {
  assert.equal(parse(url), null, url);
}
console.log('News video parser passed: source detection, unlisted Vimeo, signed file URLs and rejected inputs');
