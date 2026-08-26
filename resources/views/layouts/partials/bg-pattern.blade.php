{{-- Ambient animated background — a slow, low-opacity honeycomb motif behind
     every authenticated page, replacing the flat --canvas cream. Colors are the
     app's own warm-yellow palette (never the original demo's purple/pink/teal).
     Hexagons are pointy-top, matching the logo's hex badge (public/assets/branding/icon.png),
     and are true regular hexagons — all 6 sides equal — via the viewBox ratio
     100:115.47 (115.47 = 100 * 2/sqrt(3), the only ratio that makes every side
     equal for this point layout), drawn edge-to-edge so neighbouring cells tile
     like a real honeycomb instead of floating separately.

     Every knob (size, opacity, stroke, blur, animation speed/style) is a CSS
     custom property on .bg-pattern-fixed (see bg-pattern.css) so it can be
     tuned live without editing this file — do that at /dev/bg-playground
     (local env only) before changing the defaults here. The values below
     reproduce today's shipped look exactly; only the hex shape itself changed.

     Fixed grid size (not viewport-scaled) keeps the DOM/animation node count
     constant regardless of screen size; the radial fade at the edges was
     already part of the source design, so a fixed-size grid on very wide
     desktop screens just reads as "faded out sooner", not "cut off". --}}
@php
    $bgCols = 8;
    $bgRows = 8;
    $bgColors = ['var(--ch-yellow)', 'var(--ch-yellow-deep)', 'var(--warning)', 'var(--ch-yellow-soft)'];
    $bgAnim = 'breathe'; // breathe | twinkle | drift | static — see bg-pattern.css
@endphp
<div class="bg-pattern-fixed" data-anim="{{ $bgAnim }}" aria-hidden="true">
    <div class="bg-pattern-grid">
        @for ($i = 0; $i < $bgCols * $bgRows; $i++)
            @php
                $row = intdiv($i, $bgCols);
                $xPct = ($row % 2 === 1) ? -50 : 0;
                $yPct = -($row * 25);
            @endphp
            <div class="bg-pattern-shape" style="transform: translate({{ $xPct }}%, {{ $yPct }}%);">
                <svg viewBox="0 0 100 115.47">
                    {{-- Same points drawn 4x with different stroke colors and a
                         staggered animation-delay (via --hex-delay-frac, a 0..1
                         fraction of the full cycle) — a layered "breathing" halo.
                         Plain CSS animation, not SMIL <animate attributeName="points">,
                         which left these shapes with an empty point list (confirmed
                         via polygon.points.numberOfItems === 0) and nothing painted. --}}
                    @foreach ($bgColors as $ci => $color)
                        <polygon
                            class="bg-pattern-poly"
                            points="50 0, 100 28.87, 100 86.6, 50 115.47, 0 86.6, 0 28.87"
                            style="--hex-color: {{ $color }}; --hex-delay-frac: {{ $ci / count($bgColors) }};"
                        ></polygon>
                    @endforeach
                </svg>
            </div>
        @endfor
    </div>
    <div class="bg-pattern-overlay"></div>
</div>
