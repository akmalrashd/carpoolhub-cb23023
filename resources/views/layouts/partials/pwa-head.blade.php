{{--
    PWA wiring and native-app feel. Included by every full HTML document
    (layouts/app, auth/login, auth/register, admin/users/index).

    Before this existed, public/manifest.json and public/sw.js were never
    referenced by any view - the files shipped but nothing loaded them, so the
    app was not installable and had no offline behaviour at all.

    Must be included AFTER a page's own <style> block: the standalone-mode
    rules below target .mobile-header, and on equal specificity the later
    declaration wins.
--}}

<link rel="manifest" href="{{ asset('manifest.json') }}">

<meta name="theme-color" content="#FACC15">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="CarpoolHub">
{{-- Stops iOS turning phone-shaped digits (plates, fares) into call links. --}}
<meta name="format-detection" content="telephone=no">

<style>
    /* Rotating the device must not resize text. */
    html {
        -webkit-text-size-adjust: 100%;
        text-size-adjust: 100%;
    }

    /* Kills the rubber-band overscroll and pull-to-refresh, which are the two
       things that most give away a web page pretending to be an app. */
    html, body {
        overscroll-behavior: none;
    }

    /* No grey flash when tapping. */
    * {
        -webkit-tap-highlight-color: transparent;
    }

    /* The header dropdowns are `position: absolute; right: 0` against their own
       toggle, which sits mid-header, and they are laid out even while closed.
       Measured on a 489px viewport the bento panel sat at left=336 right=676 -
       187px past the edge - so it, not the page content, was what made every
       page scroll sideways and clip its cards.

       These rules live here rather than in the page's own <style> because this
       partial is included after it closes. The base .bento-menu-dropdown rule
       appears ~800 lines below the mobile media query, so an override placed up
       there loses on source order for every property the base rule also sets -
       which is exactly what happened: only `left` took effect, and the panel
       flipped from clipping on the left to clipping on the right.

       Pinned to the viewport, a fixed panel also stops contributing to the
       document's scroll width at all. */
    @media (max-width: 1023px) {
        .notification-dropdown,
        .bento-menu-dropdown {
            position: fixed;
            top: calc(var(--mobile-header-h) + 6px);
            left: 8px;
            right: 8px;
            width: auto;
            max-width: none;
            margin-top: 0;
            max-height: calc(100vh - var(--mobile-header-h) - 24px);
            overflow-y: auto;
        }
    }

    /* `manipulation` removes the double-tap-to-zoom delay while still allowing
       pan and pinch. Deliberately NOT `none`: that would be inherited by the
       Leaflet maps and break their gestures. Leaflet sets its own value on
       .leaflet-container and drives zoom from JS, so maps are unaffected. */
    body {
        touch-action: manipulation;
    }

    /* Long-pressing chrome should not pop a text selection handle. Content the
       user may legitimately want to copy stays selectable. */
    body {
        -webkit-user-select: none;
        user-select: none;
    }

    input, textarea, select, [contenteditable="true"],
    p, td, pre, code, .selectable {
        -webkit-user-select: text;
        user-select: text;
    }

    /* Momentum scrolling inside panes on iOS. */
    body, .main-content, .notification-dropdown, .bento-menu-dropdown {
        -webkit-overflow-scrolling: touch;
    }

    /* NOTE: deliberately no viewport-fit=cover, and no safe-area padding here.
       Adding cover switched env(safe-area-inset-bottom) from 0 to 34px on an
       iPhone, and the bottom nav already sizes itself with
       calc(83px + env(safe-area-inset-bottom)) - so the bar silently grew by a
       third. Without cover, iOS lays the viewport out inside the safe area
       already and the insets stay 0, which is what the design expects. */
</style>

<script>
    (function () {
        'use strict';

        /* ---- Service worker -------------------------------------------- */
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' })
                    .catch(function (error) {
                        console.warn('Service worker registration failed:', error);
                    });
            });
        }

        /* ---- Pinch zoom -------------------------------------------------
           The viewport meta already stops pinch zoom on Android. iOS Safari
           has ignored user-scalable=no since iOS 10 and only respects these
           gesture events, so they have to be cancelled by hand.

           Touches that start inside a map are left alone - pinching to zoom a
           route is the one place the gesture is wanted.                    */
        function insideMap(target) {
            return target instanceof Element && target.closest('.leaflet-container') !== null;
        }

        ['gesturestart', 'gesturechange', 'gestureend'].forEach(function (name) {
            document.addEventListener(name, function (event) {
                if (! insideMap(event.target)) {
                    event.preventDefault();
                }
            }, { passive: false });
        });

        /* Belt and braces for iOS: a two-finger touchmove outside a map is a
           pinch attempt, since nothing in this UI needs multi-touch. */
        document.addEventListener('touchmove', function (event) {
            if (event.touches.length > 1 && ! insideMap(event.target)) {
                event.preventDefault();
            }
        }, { passive: false });

        /* ---- Desktop zoom shortcuts are intentionally left alone --------
           Ctrl+scroll and Ctrl+/- are accessibility features on desktop and
           there is no touch surface to protect there. */
    })();
</script>
