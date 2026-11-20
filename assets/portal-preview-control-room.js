/* An intentional pull beyond the top opens the admin layer, never normal scrolling. */
(() => {
    'use strict';
    const panel = document.getElementById('pwp-control-room');
    if (!panel) return;
    const root = document.getElementById('portal-website-preview');
    let wheelDistance = 0, lastWheel = 0, touch = null, opener = null;
    const atTop = () => Math.max(window.scrollY, document.scrollingElement?.scrollTop || 0) <= 1;
    function excluded(target) {
        if (!(target instanceof Element)) return false;
        if (target.closest('input,textarea,select,[contenteditable="true"],dialog,.modal,iframe,#pwp-control-room')) return true;
        for (let node = target; node && node !== document.body && node !== document.documentElement; node = node.parentElement) {
            if (node.scrollHeight > node.clientHeight + 2 && /auto|scroll/.test(getComputedStyle(node).overflowY)) return true;
        }
        return false;
    }
    function setOpen(open, source) {
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', String(!open));
        panel.inert = !open;
        root.querySelectorAll('[data-pwp-admin-open]').forEach(button => button.setAttribute('aria-expanded', String(open)));
        if (open) {
            opener = source || null;
            root.querySelector('.pwp-account')?.removeAttribute('open');
            if (source) panel.querySelector('[data-pwp-admin-close]').focus({preventScroll:true});
        } else if (opener) {
            const details = opener.closest('details');
            (details?.querySelector('summary') || opener).focus({preventScroll:true});
            opener = null;
        }
        wheelDistance = 0;
    }
    document.documentElement.classList.add('pwp-overscroll-guard');
    window.addEventListener('wheel', event => {
        if (event.ctrlKey || event.deltaY >= 0 || !atTop() || excluded(event.target)) { wheelDistance = 0; return; }
        const now = performance.now();
        if (now - lastWheel > 600) wheelDistance = 0;
        lastWheel = now;
        const unit = event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? innerHeight : 1;
        wheelDistance += Math.min(120, -event.deltaY * unit);
        if (wheelDistance >= 64 && !panel.classList.contains('is-open')) setOpen(true);
    }, {passive:true});
    window.addEventListener('touchstart', event => {
        touch = event.touches.length === 1 && atTop() && !excluded(event.target)
            ? {y:event.touches[0].clientY, x:event.touches[0].clientX} : null;
    }, {passive:true});
    window.addEventListener('touchmove', event => {
        if (!touch || event.touches.length !== 1) return;
        const point = event.touches[0];
        if (Math.abs(point.clientX - touch.x) > 45 || point.clientY < touch.y || !atTop()) { touch = null; return; }
        if (point.clientY - touch.y >= 72) { setOpen(true); touch = null; }
    }, {passive:true});
    window.addEventListener('touchend', () => { touch = null; }, {passive:true});
    window.addEventListener('touchcancel', () => { touch = null; }, {passive:true});
    root.addEventListener('click', event => {
        const open = event.target.closest('[data-pwp-admin-open]');
        if (open) setOpen(true, open);
        if (event.target.closest('[data-pwp-admin-close]') || (panel.contains(event.target) && event.target.closest('[data-pwp-page]'))) setOpen(false);
    });
    document.addEventListener('pointerdown', event => {
        if (panel.classList.contains('is-open') && !panel.contains(event.target) && !event.target.closest('[data-pwp-admin-open]')) setOpen(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) {
            setOpen(false); event.stopImmediatePropagation();
        }
    }, true);
})();
