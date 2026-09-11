/* Single source of truth for the "no profile photo" avatar fallback in JS —
   the twin of app/Support/Avatar.php. Keep PALETTE in the exact same order
   as the PHP one: color(id) is a plain `id % length` lookup, so the same
   user id has to land on the same array index in both places.

   Loaded globally (see layouts/app.blade.php <head>) so every page's own
   JS — trips-index.js, trips-requests.js, payments-index.js,
   explore-index.js, admin pages, etc. — can call this instead of each
   re-implementing its own initial/colour logic. */
(function () {
    'use strict';

    var PALETTE = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];

    function color(id) {
        var n = Math.abs(parseInt(id, 10)) || 0;
        return PALETTE[n % PALETTE.length];
    }

    function initial(name) {
        var trimmed = String(name || '').trim();
        return trimmed ? trimmed.charAt(0).toUpperCase() : '?';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Builds the inner HTML for an avatar element: a real photo when one is
     * given, otherwise a single initial on its deterministic colour. Caller
     * owns the outer element's sizing/shape class — this only fills it in.
     */
    function innerHtml(options) {
        options = options || {};
        var photoUrl = options.photoUrl;
        var name = options.name;
        var id = options.id;

        if (photoUrl) {
            return '<img src="' + escapeHtml(photoUrl) + '" alt="' + escapeHtml(name || '') + '">';
        }

        return escapeHtml(initial(name));
    }

    /** Inline style string for the coloured-fallback state (id-based). */
    function bgStyle(id) {
        return 'background:' + color(id) + ';color:#fff;';
    }

    window.CarpoolAvatar = {
        PALETTE: PALETTE,
        color: color,
        initial: initial,
        innerHtml: innerHtml,
        bgStyle: bgStyle,
    };
})();
