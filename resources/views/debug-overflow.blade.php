<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostik lebar</title>
    <style>
        body { margin: 0; font: 13px/1.45 ui-monospace, Menlo, Consolas, monospace; background: #0b1220; color: #e5e7eb; }
        h1 { font-size: 15px; padding: 12px; margin: 0; background: #111827; }
        pre { padding: 12px; margin: 0; white-space: pre-wrap; word-break: break-all; }
        iframe { width: 100%; height: 240px; border: 0; border-top: 2px solid #374151; background: #fff; }
        .hi { color: #fbbf24; }
    </style>
</head>
<body>
    <h1>Diagnostik lebar &mdash; screenshot skrin ini</h1>
    <pre id="out">Mengukur&hellip;</pre>
    <iframe id="frame" src="{{ $target }}"></iframe>

    <script>
        // Runs on the real device, in the real browser. Everything measured so
        // far was Chrome emulation on a desktop, which did not reproduce this.
        var frame = document.getElementById('frame');
        var out = document.getElementById('out');

        frame.addEventListener('load', function () {
            setTimeout(function () {
                var lines = [];
                try {
                    var d = frame.contentDocument;
                    var w = frame.contentWindow;
                    var vw = d.documentElement.clientWidth;

                    lines.push('UA=' + navigator.userAgent.slice(0, 60));
                    lines.push('VIEWPORT=' + vw + '  screen=' + screen.width);
                    lines.push('DOC_SCROLLWIDTH=' + d.documentElement.scrollWidth);
                    lines.push('BODY_SCROLLWIDTH=' + d.body.scrollWidth);
                    lines.push('');

                    var bad = [];
                    d.querySelectorAll('body *').forEach(function (el) {
                        var r = el.getBoundingClientRect();
                        if (r.width > vw + 1 || r.right > vw + 1) { bad.push(el); }
                    });

                    var outer = bad.filter(function (el) {
                        return ! bad.some(function (o) { return o !== el && o.contains(el); });
                    });

                    if (outer.length === 0) {
                        lines.push('TIADA OVERFLOW');
                    }

                    outer.slice(0, 8).forEach(function (el) {
                        var r = el.getBoundingClientRect();
                        var cs = w.getComputedStyle(el);
                        lines.push('>> ' + el.tagName + '.' + (el.className || '').toString().slice(0, 40));
                        lines.push('   rect=' + Math.round(r.left) + '..' + Math.round(r.right)
                            + '  w=' + Math.round(r.width) + '  scrollW=' + el.scrollWidth);
                        lines.push('   pos=' + cs.position + ' display=' + cs.display
                            + ' minW=' + cs.minWidth + ' ws=' + cs.whiteSpace);
                        lines.push('');
                    });
                } catch (e) {
                    lines.push('RALAT: ' + e.message);
                }
                out.textContent = lines.join('\n');
            }, 1500);
        });
    </script>
</body>
</html>
