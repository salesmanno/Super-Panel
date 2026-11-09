(function (root) {
  'use strict';
  function parse(value) {
    try {
      const url = new URL(String(value || '').trim());
      if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return null;
      const host = url.hostname.toLowerCase().replace(/^www\./, '');
      const path = url.pathname.split('/').filter(Boolean);
      if (['youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com'].includes(host)) {
        const id = host === 'youtu.be' ? path[0] : url.searchParams.get('v') || (['embed', 'shorts'].includes(path[0]) ? path[1] : '');
        return /^[A-Za-z0-9_-]{11}$/.test(id || '') ? { provider: 'youtube', id, src: `https://www.youtube-nocookie.com/embed/${id}` } : null;
      }
      if (['vimeo.com', 'player.vimeo.com'].includes(host)) {
        if (Array.from(url.searchParams.keys()).some(key => key.startsWith('h['))) return null;
        const match = url.pathname.match(/^\/(?:video\/)?(\d+)(?:\/([A-Za-z0-9]+))?\/?$/);
        if (!match) return null;
        const hash = url.searchParams.get('h') || match[2] || '';
        if (hash && !/^[A-Za-z0-9]+$/.test(hash)) return null;
        return { provider: 'vimeo', id: match[1], src: `https://player.vimeo.com/video/${match[1]}${hash ? '?h=' + hash : ''}` };
      }
      if (/\.(mp4|webm)$/i.test(url.pathname)) return { provider: 'file', src: url.href };
      return null;
    } catch (_) { return null; }
  }
  root.NewsVideo = { parse };
  if (typeof module !== 'undefined' && module.exports) module.exports = root.NewsVideo;
})(typeof window !== 'undefined' ? window : globalThis);
