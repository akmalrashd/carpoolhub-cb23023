{{-- Ambient animated background — a slow, low-opacity hex/triangle motif behind
     every authenticated page, replacing the flat --canvas cream. Colors are the
     app's own warm-yellow palette (never the original demo's purple/pink/teal),
     and the animation is deliberately slow — this sits behind real content, not
     the content itself. Fixed grid size (not viewport-scaled) keeps the DOM/SMIL
     node count constant regardless of screen size; the radial fade at the edges
     was already part of the source design, so a fixed-size grid on very wide
     desktop screens just reads as "faded out sooner", not "cut off". --}}
@php
    $bgCols = 8;
    $bgRows = 8;
    $bgColors = ['var(--ch-yellow)', 'var(--ch-yellow-deep)', 'var(--warning)', 'var(--ch-yellow-soft)'];
    $bgDur = 26; // seconds per full grow/reset cycle — slow on purpose, this sits behind real content
@endphp
<div class="bg-pattern-fixed" aria-hidden="true">
    <div class="bg-pattern-grid">
        @for ($i = 0; $i < $bgCols * $bgRows; $i++)
            @php
                $row = intdiv($i, $bgCols);
                $xPct = ($row % 2 === 1) ? -50 : 0;
                $yPct = -($row * 25);
            @endphp
            <div class="bg-pattern-shape" style="transform: translate({{ $xPct }}%, {{ $yPct }}%);">
                <svg viewBox="0 0 100 115" preserveAspectRatio="xMidYMin slice">
                    @foreach ($bgColors as $ci => $color)
                        <polygon fill="none" stroke="{{ $color }}" stroke-width="3">
                            <animate
                                attributeName="points"
                                repeatCount="indefinite"
                                dur="{{ $bgDur }}s"
                                begin="{{ $ci * ($bgDur / 4) }}s"
                                from="50 57.5, 50 57.5, 50 57.5"
                                to="50 -75, 175 126, -75 126"
                            />
                        </polygon>
                    @endforeach
                </svg>
            </div>
        @endfor
    </div>
    <div class="bg-pattern-overlay"></div>
</div>
