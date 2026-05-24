@props([
    'step'  => 1,
    'total' => 4,
])

<div {{ $attributes->merge(['class' => '']) }} style="display:flex;align-items:center;gap:8px;">
    @for($i = 1; $i <= $total; $i++)
        <div style="
            width: {{ $i === $step ? '28px' : '8px' }};
            height: 8px;
            border-radius: var(--r-pill);
            background: {{ $i === $step ? 'var(--ch-yellow)' : ($i < $step ? 'var(--ch-yellow-line)' : 'var(--hairline-strong)') }};
            transition: width .2s ease, background .2s ease;
        "></div>
    @endfor
    <span style="font-size:12px;color:var(--muted);margin-left:4px;">{{ $step }} / {{ $total }}</span>
</div>
