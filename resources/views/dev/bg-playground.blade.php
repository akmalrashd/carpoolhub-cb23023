<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BG Playground — CarpoolHub Dev</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    {{-- Same shared shell tokens/components every real page uses, so the sample
         content below (.card/.btn/.badge/.input) looks exactly like production —
         this is a legibility test, not just an animation preview. --}}
    <link rel="stylesheet" href="{{ asset('css/shell.css') }}?v={{ filemtime(public_path('css/shell.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/bg-pattern.css') }}?v={{ filemtime(public_path('css/bg-pattern.css')) }}">
    <style>
        body { min-height: 100vh; }

        .bgp-page {
            max-width: 880px;
            margin: 0 auto;
            padding: 32px 24px 120px;
        }
        @media (min-width: 1024px) {
            .bgp-page { margin: 0 380px 0 0; max-width: none; padding: 40px 48px 140px; }
        }

        .bgp-intro { margin-bottom: 28px; }
        .bgp-intro p { max-width: 60ch; }

        .bgp-hero {
            padding: 28px 4px 40px;
        }
        .bgp-hero h2 { margin: 10px 0 8px; }
        .bgp-hero p { max-width: 52ch; margin: 0; }

        .bgp-stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .bgp-section { margin-bottom: 28px; }

        .bgp-trip-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .bgp-trip-head .avatar { width: 42px; height: 42px; font-size: 15px; }
        .bgp-trip-route { display: flex; align-items: center; gap: 8px; margin: 10px 0 16px; color: var(--ink-2); font-size: 14px; }
        .bgp-btn-row, .bgp-badge-row { display: flex; flex-wrap: wrap; gap: 10px; }

        .bgp-field-group { margin-bottom: 18px; }
        .bgp-form-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .bgp-form-row .field-label { margin-bottom: 6px; }

        /* ── Control panel ── */
        .bgp-panel {
            background: var(--surface);
            border-left: 1px solid var(--hairline);
            padding: 20px 20px 32px;
            font-family: var(--font-ui);
        }
        @media (min-width: 1024px) {
            .bgp-panel {
                position: fixed;
                top: 0; right: 0; bottom: 0;
                width: 380px;
                overflow-y: auto;
                box-shadow: var(--shadow-3);
            }
        }
        .bgp-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
        .bgp-panel-head h3 { margin: 0; }
        .bgp-panel-sub { margin: 2px 0 18px; }

        .bgp-group { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--hairline); }
        .bgp-group:last-of-type { border-bottom: none; }
        .bgp-group-title { display: block; margin-bottom: 10px; }

        .bgp-seg { display: flex; gap: 6px; flex-wrap: wrap; }
        .bgp-seg button {
            flex: 1 1 auto;
            border: 1px solid var(--hairline-strong);
            background: var(--surface-2);
            color: var(--ink-2);
            border-radius: var(--r-sm);
            padding: 7px 10px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font-ui);
            white-space: nowrap;
        }
        .bgp-seg button.is-active { background: var(--ch-yellow); border-color: var(--ch-yellow); color: var(--ch-yellow-ink); }

        .bgp-field { display: block; margin-bottom: 14px; }
        .bgp-field:last-child { margin-bottom: 0; }
        .bgp-field-label { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--ink-2); margin-bottom: 6px; }
        .bgp-field-label b { font-family: var(--font-mono); font-weight: 600; color: var(--ch-yellow-ink); }
        .bgp-field.is-disabled { opacity: 0.4; }
        .bgp-field.is-disabled input { pointer-events: none; }

        input[type="range"] { width: 100%; accent-color: var(--ch-yellow-deep); }

        .bgp-actions { display: flex; gap: 8px; margin-bottom: 14px; }
        .bgp-actions .btn { flex: 1; }

        .bgp-readout {
            font-family: var(--font-mono);
            font-size: 11px;
            line-height: 1.6;
            background: var(--ink);
            color: #FDE68A;
            border-radius: var(--r-sm);
            padding: 12px 14px;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 220px;
            overflow-y: auto;
        }
        .bgp-hint { font-size: 11.5px; color: var(--muted); margin-top: 10px; line-height: 1.5; }
    </style>
</head>
<body>

@include('layouts.partials.bg-pattern')

<div class="bgp-page">
    <div class="bgp-intro">
        <span class="badge badge-warning"><span class="dot"></span> Dev only — local env</span>
        <h1 class="h1" style="margin:12px 0 6px;">Background Playground</h1>
        <p class="t-md text-muted">Tune the honeycomb background live with the panel on the right, using real page content (cards, buttons, text) so you can judge contrast and distraction — not just the pattern by itself. Nothing here saves automatically; when you land on something you like, copy the values at the bottom of the panel and send them over so it can be applied to every page.</p>
    </div>

    <div class="bgp-hero">
        <span class="t-eyebrow">Text directly on the pattern</span>
        <h2 class="h2">This is how a heading reads with nothing behind it</h2>
        <p class="t-lg text-muted">No card, no surface — just ink on the ambient background, same as the auth pages. If this is hard to read, the pattern is too strong here regardless of how good it looks on its own.</p>
    </div>

    <div class="bgp-section">
        <div class="bgp-stat-row">
            <div class="card card-pad">
                <span class="t-eyebrow">Active Trips</span>
                <div class="h2" style="margin-top:4px;">12</div>
            </div>
            <div class="card card-pad">
                <span class="t-eyebrow">Pending</span>
                <div class="h2" style="margin-top:4px;">RM 340</div>
            </div>
            <div class="card card-pad">
                <span class="t-eyebrow">Connections</span>
                <div class="h2" style="margin-top:4px;">8</div>
            </div>
        </div>
    </div>

    <div class="bgp-section">
        <div class="card card-pad-lg">
            <div class="bgp-trip-head">
                <span class="avatar">AK</span>
                <div>
                    <strong class="h4" style="display:block;">Akmal drives to UTM</strong>
                    <span class="t-xs text-muted">Today, 8:00 AM · 3 seats left</span>
                </div>
            </div>
            <div class="bgp-trip-route">
                <i class="fa-solid fa-location-dot" style="color:var(--ch-yellow-deep);"></i>
                <span>Taman Universiti → Skudai</span>
            </div>
            <div class="bgp-btn-row">
                <button type="button" class="btn btn-primary">Request Seat</button>
                <button type="button" class="btn btn-ghost">View Details</button>
            </div>
        </div>
    </div>

    <div class="bgp-section">
        <div class="bgp-field-group">
            <span class="t-eyebrow">Form legibility</span>
        </div>
        <div class="bgp-form-row">
            <div>
                <span class="field-label">Pickup point</span>
                <input class="input" type="text" placeholder="e.g. Taman Universiti" style="width:220px;">
            </div>
            <button type="button" class="btn btn-dark">Search</button>
        </div>
    </div>

    <div class="bgp-section">
        <div class="bgp-badge-row">
            <span class="badge badge-success"><span class="dot"></span> Confirmed</span>
            <span class="badge badge-warning"><span class="dot"></span> Pending</span>
            <span class="badge badge-danger"><span class="dot"></span> Cancelled</span>
            <span class="badge badge-info"><span class="dot"></span> Info</span>
            <span class="badge badge-yellow"><span class="dot"></span> Featured</span>
            <span class="badge badge-dark"><span class="dot"></span> Admin</span>
        </div>
    </div>

    <div class="bgp-section">
        <p class="t-md text-muted">Longer body copy to check reading comfort over an extended stretch of the pattern — the kind of paragraph you'd find on a details or terms page. If the eye keeps snagging on the hexagons instead of the words, intensity or blur probably needs to come down before contrast or animation speed does.</p>
    </div>
</div>

<aside class="bgp-panel">
    <div class="bgp-panel-head">
        <h3 class="h3">Controls</h3>
    </div>
    <p class="bgp-panel-sub t-xs text-muted">Adjusts the same partial every real page includes — this is exactly what shipping it would look like.</p>

    <div class="bgp-group">
        <span class="bgp-group-title t-eyebrow">Animation style</span>
        <div class="bgp-seg" data-seg="anim">
            <button type="button" data-value="breathe" class="is-active">Breathe</button>
            <button type="button" data-value="twinkle">Twinkle</button>
            <button type="button" data-value="drift">Drift</button>
            <button type="button" data-value="static">Static</button>
        </div>
    </div>

    <div class="bgp-group">
        <span class="bgp-group-title t-eyebrow">Palette</span>
        <div class="bgp-seg" data-seg="palette">
            <button type="button" data-value="warm" class="is-active">Warm mix</button>
            <button type="button" data-value="gold">Gold only</button>
            <button type="button" data-value="ink">Ink outline</button>
        </div>
    </div>

    <div class="bgp-group">
        <span class="bgp-group-title t-eyebrow">Shape &amp; intensity</span>

        <label class="bgp-field">
            <span class="bgp-field-label">Hex size <b data-out="hexW">110px</b></span>
            <input type="range" data-bind="hexW" data-unit="px" min="60" max="200" step="2" value="110">
        </label>

        <label class="bgp-field">
            <span class="bgp-field-label">Intensity (grid opacity) <b data-out="intensity">0.18</b></span>
            <input type="range" data-bind="intensity" data-unit="" min="0.03" max="0.5" step="0.01" value="0.18">
        </label>

        <label class="bgp-field">
            <span class="bgp-field-label">Stroke width <b data-out="stroke">3px</b></span>
            <input type="range" data-bind="stroke" data-unit="px" min="1" max="8" step="0.5" value="3">
        </label>

        <label class="bgp-field">
            <span class="bgp-field-label">Soft blur (glow) <b data-out="blur">0px</b></span>
            <input type="range" data-bind="blur" data-unit="px" min="0" max="6" step="0.5" value="0">
        </label>
    </div>

    <div class="bgp-group">
        <span class="bgp-group-title t-eyebrow">Motion</span>

        <label class="bgp-field">
            <span class="bgp-field-label">Cycle length <b data-out="duration">26s</b></span>
            <input type="range" data-bind="duration" data-unit="s" min="4" max="60" step="1" value="26">
        </label>

        <label class="bgp-field">
            <span class="bgp-field-label">Stagger spread <b data-out="spread">1</b></span>
            <input type="range" data-bind="spread" data-unit="" min="0" max="1" step="0.05" value="1">
        </label>

        <label class="bgp-field">
            <span class="bgp-field-label">Dim opacity <b data-out="opMin">0.35</b></span>
            <input type="range" data-bind="opMin" data-unit="" min="0.1" max="0.9" step="0.01" value="0.35">
        </label>

        <label class="bgp-field">
            <span class="bgp-field-label">Bright opacity <b data-out="opMax">1</b></span>
            <input type="range" data-bind="opMax" data-unit="" min="0.1" max="1" step="0.01" value="1">
        </label>

        <label class="bgp-field" data-breathe-only>
            <span class="bgp-field-label">Shrink scale (Breathe only) <b data-out="scaleMin">0.82</b></span>
            <input type="range" data-bind="scaleMin" data-unit="" min="0.5" max="1" step="0.01" value="0.82">
        </label>

        <label class="bgp-field" data-breathe-only>
            <span class="bgp-field-label">Grow scale (Breathe only) <b data-out="scaleMax">1.06</b></span>
            <input type="range" data-bind="scaleMax" data-unit="" min="1" max="1.3" step="0.01" value="1.06">
        </label>
    </div>

    <div class="bgp-group">
        <div class="bgp-actions">
            <button type="button" class="btn btn-ghost btn-sm" id="bgpReset">Reset</button>
            <button type="button" class="btn btn-primary btn-sm" id="bgpCopy">Copy values</button>
        </div>
        <pre class="bgp-readout" id="bgpReadout"></pre>
        <p class="bgp-hint">Grid density (rows/columns) is fixed server-side for performance — hex size doubles as the density control, since bigger hexagons mean fewer fit on screen. Reduced-motion visitors never see any of this; the pattern is hidden entirely for them.</p>
    </div>
</aside>

<script>
(function () {
    var root = document.querySelector('.bg-pattern-fixed');
    if (!root) return;

    var polys = Array.prototype.slice.call(document.querySelectorAll('.bg-pattern-poly'));

    var VAR_MAP = {
        hexW: ['--hex-w', 'px'],
        intensity: ['--hex-intensity', ''],
        stroke: ['--hex-stroke', 'px'],
        blur: ['--hex-blur', 'px'],
        duration: ['--hex-duration', 's'],
        spread: ['--hex-spread', ''],
        opMin: ['--hex-op-min', ''],
        opMax: ['--hex-op-max', ''],
        scaleMin: ['--hex-scale-min', ''],
        scaleMax: ['--hex-scale-max', '']
    };

    var PALETTES = {
        warm: ['var(--ch-yellow)', 'var(--ch-yellow-deep)', 'var(--warning)', 'var(--ch-yellow-soft)'],
        gold: ['var(--ch-yellow)', 'var(--ch-yellow)', 'var(--ch-yellow)', 'var(--ch-yellow)'],
        ink: ['var(--ink-3)', 'var(--ink-3)', 'var(--ink-3)', 'var(--ink-3)']
    };

    var DEFAULTS = {
        anim: 'breathe',
        palette: 'warm',
        hexW: 110,
        intensity: 0.18,
        stroke: 3,
        blur: 0,
        duration: 26,
        spread: 1,
        opMin: 0.35,
        opMax: 1,
        scaleMin: 0.82,
        scaleMax: 1.06
    };

    var state = Object.assign({}, DEFAULTS);

    var readoutEl = document.getElementById('bgpReadout');
    var bindInputs = Array.prototype.slice.call(document.querySelectorAll('[data-bind]'));
    var segGroups = Array.prototype.slice.call(document.querySelectorAll('[data-seg]'));
    var breatheOnlyFields = Array.prototype.slice.call(document.querySelectorAll('[data-breathe-only]'));

    function applyVar(key) {
        var m = VAR_MAP[key];
        root.style.setProperty(m[0], state[key] + m[1]);
    }

    function applyPalette(name) {
        var colors = PALETTES[name] || PALETTES.warm;
        polys.forEach(function (el, idx) {
            el.style.setProperty('--hex-color', colors[idx % colors.length]);
        });
    }

    function updateBreatheAvailability() {
        breatheOnlyFields.forEach(function (field) {
            field.classList.toggle('is-disabled', state.anim !== 'breathe');
        });
    }

    function updateReadout() {
        var lines = [
            '--hex-w: ' + state.hexW + 'px;',
            '--hex-intensity: ' + state.intensity + ';',
            '--hex-stroke: ' + state.stroke + 'px;',
            '--hex-blur: ' + state.blur + 'px;',
            '--hex-duration: ' + state.duration + 's;',
            '--hex-spread: ' + state.spread + ';',
            '--hex-op-min: ' + state.opMin + ';',
            '--hex-op-max: ' + state.opMax + ';',
            '--hex-scale-min: ' + state.scaleMin + ';',
            '--hex-scale-max: ' + state.scaleMax + ';',
            '',
            'data-anim: ' + state.anim,
            'palette: ' + state.palette
        ];
        readoutEl.textContent = lines.join('\n');
    }

    function syncInputsFromState() {
        bindInputs.forEach(function (input) {
            var key = input.dataset.bind;
            input.value = state[key];
            updateOut(key);
        });
        segGroups.forEach(function (group) {
            var seg = group.dataset.seg;
            Array.prototype.slice.call(group.querySelectorAll('button')).forEach(function (btn) {
                btn.classList.toggle('is-active', btn.dataset.value === state[seg]);
            });
        });
    }

    function updateOut(key) {
        var out = document.querySelector('[data-out="' + key + '"]');
        if (!out) return;
        var input = document.querySelector('[data-bind="' + key + '"]');
        var unit = input ? input.dataset.unit : '';
        out.textContent = state[key] + unit;
    }

    function applyAllFresh() {
        root.dataset.anim = state.anim;
        Object.keys(VAR_MAP).forEach(applyVar);
        applyPalette(state.palette);
        updateBreatheAvailability();
        updateReadout();
    }

    bindInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            var key = input.dataset.bind;
            state[key] = parseFloat(input.value);
            applyVar(key);
            updateOut(key);
            updateReadout();
        });
    });

    segGroups.forEach(function (group) {
        var seg = group.dataset.seg;
        Array.prototype.slice.call(group.querySelectorAll('button')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                state[seg] = btn.dataset.value;
                Array.prototype.slice.call(group.querySelectorAll('button')).forEach(function (b) {
                    b.classList.toggle('is-active', b === btn);
                });
                if (seg === 'anim') {
                    root.dataset.anim = state.anim;
                    updateBreatheAvailability();
                } else if (seg === 'palette') {
                    applyPalette(state.palette);
                }
                updateReadout();
            });
        });
    });

    document.getElementById('bgpReset').addEventListener('click', function () {
        state = Object.assign({}, DEFAULTS);
        syncInputsFromState();
        applyAllFresh();
    });

    var copyBtn = document.getElementById('bgpCopy');
    copyBtn.addEventListener('click', function () {
        var text = readoutEl.textContent;
        if (!navigator.clipboard) return;
        navigator.clipboard.writeText(text).then(function () {
            var original = copyBtn.textContent;
            copyBtn.textContent = 'Copied!';
            setTimeout(function () { copyBtn.textContent = original; }, 1500);
        });
    });

    syncInputsFromState();
    applyAllFresh();
})();
</script>
</body>
</html>
