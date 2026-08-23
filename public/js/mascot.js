/**
 * CarpoolHub AI mascot controller.
 * Drives any [data-mascot] element (see resources/views/components/mascot.blade.php)
 * through the animated states defined in public/css/mascot.css via a
 * `data-state` attribute — persistent states (idle, sleep) with setState(),
 * timed flourishes (wink, alert, notify, burst, ...) with play().
 */
const Mascot = (() => {
    const tracked = new WeakSet();

    // Same path data as resources/views/components/mascot.blade.php — kept
    // in sync manually since this copy exists so JS-rendered UI (dynamic
    // innerHTML, e.g. saved-routes' AI fare advisor) can produce the same
    // markup the Blade component does, via html() below.
    const SVG_BODY = 'M104 0.31C104 3.67 103.14 7.17 101.96 10.4C100.77 13.62 98.57 16.68 96.89 19.67C95.22 22.66 93.54 25.5 91.92 28.32C90.3 31.14 88.74 33.87 87.17 36.59C85.61 39.3 84.08 41.96 82.55 44.64C81.01 47.31 79.49 49.95 77.95 52.64C76.41 55.32 74.87 57.99 73.29 60.74C71.71 63.49 70.13 66.25 68.48 69.11C66.83 71.98 65.26 75.02 63.41 77.94C61.56 80.85 59.8 84.2 57.38 86.59C54.97 88.99 52.04 91 48.93 92.29C45.83 93.57 42.17 93.96 38.75 94.3C35.32 94.63 31.73 94.3 28.38 94.3C25.02 94.3 21.8 94.3 18.61 94.3C15.41 94.3 12.31 94.3 9.21 94.3C6.11 94.3 3.07 94.3 0 94.3C-3.07 94.3 -6.11 94.3 -9.22 94.3C-12.32 94.3 -15.42 94.3 -18.61 94.3C-21.8 94.3 -25.02 94.3 -28.38 94.3C-31.74 94.3 -35.33 94.63 -38.75 94.3C-42.18 93.96 -45.83 93.57 -48.94 92.29C-52.04 91 -54.97 88.99 -57.39 86.59C-59.8 84.2 -61.56 80.85 -63.41 77.94C-65.26 75.02 -66.84 71.98 -68.48 69.11C-70.13 66.25 -71.72 63.49 -73.3 60.74C-74.87 57.99 -76.41 55.32 -77.95 52.64C-79.49 49.95 -81.01 47.31 -82.55 44.64C-84.09 41.96 -85.61 39.3 -87.18 36.59C-88.74 33.87 -90.3 31.14 -91.92 28.32C-93.54 25.5 -95.22 22.66 -96.9 19.67C-98.57 16.68 -100.77 13.62 -101.96 10.4C-103.14 7.17 -104 3.67 -104 0.31C-104 -3.06 -103.14 -6.55 -101.96 -9.78C-100.77 -13.01 -98.57 -16.07 -96.9 -19.06C-95.22 -22.04 -93.54 -24.89 -91.92 -27.71C-90.3 -30.53 -88.74 -33.25 -87.18 -35.97C-85.61 -38.69 -84.09 -41.35 -82.55 -44.02C-81.01 -46.7 -79.49 -49.34 -77.95 -52.02C-76.41 -54.71 -74.87 -57.38 -73.3 -60.13C-71.72 -62.87 -70.13 -65.63 -68.48 -68.5C-66.84 -71.36 -65.26 -74.41 -63.41 -77.32C-61.56 -80.24 -59.8 -83.59 -57.39 -85.98C-54.97 -88.37 -52.04 -90.39 -48.94 -91.67C-45.83 -92.95 -42.18 -93.35 -38.75 -93.68C-35.33 -94.02 -31.74 -93.68 -28.38 -93.68C-25.02 -93.68 -21.8 -93.68 -18.61 -93.68C-15.42 -93.68 -12.32 -93.68 -9.22 -93.68C-6.11 -93.68 -3.07 -93.68 0 -93.68C3.07 -93.68 6.11 -93.68 9.21 -93.68C12.31 -93.68 15.41 -93.68 18.61 -93.68C21.8 -93.68 25.02 -93.68 28.38 -93.68C31.73 -93.68 35.32 -94.02 38.75 -93.68C42.17 -93.35 45.83 -92.95 48.93 -91.67C52.04 -90.39 54.97 -88.37 57.38 -85.98C59.8 -83.59 61.56 -80.24 63.41 -77.32C65.26 -74.41 66.83 -71.36 68.48 -68.5C70.13 -65.63 71.71 -62.87 73.29 -60.13C74.87 -57.38 76.41 -54.71 77.95 -52.02C79.49 -49.34 81.01 -46.7 82.55 -44.02C84.08 -41.35 85.61 -38.69 87.17 -35.97C88.74 -33.25 90.3 -30.53 91.92 -27.71C93.54 -24.89 95.22 -22.04 96.89 -19.06C98.57 -16.07 100.77 -13.01 101.96 -9.78C103.14 -6.55 104 -3.06 104 0.31Z';
    const SVG_EYE = 'M-9.3 -11.3A9.3 9.3 0 0 1 0 -20.6L0 -20.6A9.3 9.3 0 0 1 9.3 -11.3L9.3 11.3A9.3 9.3 0 0 1 0 20.6L0 20.6A9.3 9.3 0 0 1 -9.3 11.3Z';

    function html({ size = 24, variant = 'yellow', state = 'idle', id = '', className = '' } = {}) {
        const idAttr = id ? ` id="${id}"` : '';
        return `<span${idAttr} class="mascot mascot--${variant} ${className}" data-mascot data-state="${state}" style="--mascot-size:${size}px">`
            + `<svg class="mascot-svg" viewBox="-125 -125 250 250" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">`
            + `<path class="mascot-body" d="${SVG_BODY}" />`
            + `<g class="mascot-eyes">`
            + `<path class="mascot-eye mascot-eye-l" d="${SVG_EYE}" />`
            + `<path class="mascot-eye mascot-eye-r" d="${SVG_EYE}" />`
            + `</g>`
            + `<g class="mascot-dots">`
            + `<circle class="mascot-dot mascot-dot-1" cx="-32" cy="2" r="9" />`
            + `<circle class="mascot-dot mascot-dot-2" cx="0" cy="2" r="9" />`
            + `<circle class="mascot-dot mascot-dot-3" cx="32" cy="2" r="9" />`
            + `</g>`
            + `<g class="mascot-bang">`
            + `<rect class="mascot-bang-bar" x="-6.5" y="-42" width="13" height="46" rx="6.5" />`
            + `<circle class="mascot-bang-dot" cx="0" cy="20" r="7.5" />`
            + `</g>`
            + `<circle class="mascot-notif-dot" cx="66" cy="-58" r="13" />`
            + `</svg></span>`;
    }

    function resolve(node) {
        if (!node) return null;
        return typeof node === 'string' ? document.getElementById(node) : node;
    }

    function setState(node, state) {
        const el = resolve(node);
        if (!el) return;
        clearTimeout(el._mascotRevertTimer);
        el.dataset.state = state;
    }

    function play(node, state, opts = {}) {
        const el = resolve(node);
        if (!el) return;
        const { revertTo = 'idle', duration = 1400 } = opts;
        clearTimeout(el._mascotRevertTimer);
        el.dataset.state = state;
        el._mascotRevertTimer = setTimeout(() => {
            el.dataset.state = revertTo;
        }, duration);
    }

    function scheduleBlink(el) {
        const delay = 2600 + Math.random() * 3200;
        el._mascotBlinkTimer = setTimeout(() => {
            if (el.dataset.state === 'idle') {
                el.classList.add('is-blinking');
                setTimeout(() => el.classList.remove('is-blinking'), 160);
            }
            scheduleBlink(el);
        }, delay);
    }

    function init(el) {
        if (tracked.has(el)) return;
        tracked.add(el);
        if (!el.dataset.state) el.dataset.state = 'idle';
        scheduleBlink(el);

        // Small hover flourish so a mascot sitting inside a button/link reads
        // as alive even before anything real happens (idle breathing alone
        // is too subtle to notice at header-icon sizes).
        const trigger = el.closest('button, a') || el;
        trigger.addEventListener('mouseenter', () => {
            if (el.dataset.state === 'idle') play(el, 'wide', { duration: 550 });
        });
    }

    function initAll(root = document) {
        root.querySelectorAll('[data-mascot]').forEach(init);
    }

    document.addEventListener('DOMContentLoaded', () => initAll());

    return { setState, play, initAll, html };
})();

window.Mascot = Mascot;
