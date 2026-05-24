@props([
    'rows'   => 3,
    'avatar' => false,
])

<style>
@keyframes shimmer {
    0%   { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}
.skeleton-line {
    background: linear-gradient(90deg, var(--hairline) 25%, var(--canvas-2, #FAF8F2) 50%, var(--hairline) 75%);
    background-size: 800px 100%;
    animation: shimmer 1.4s ease-in-out infinite;
    border-radius: var(--r-sm);
}
</style>

<div {{ $attributes->merge(['class' => 'card']) }}>
    @for($i = 0; $i < $rows; $i++)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:{{ $i < $rows - 1 ? '1px solid var(--hairline)' : 'none' }};">
            @if($avatar)
                <div class="skeleton-line" style="width:36px;height:36px;border-radius:999px;flex-shrink:0;"></div>
            @endif
            <div style="flex:1;display:grid;gap:8px;">
                <div class="skeleton-line" style="height:13px;width:{{ 60 + ($i * 13 % 30) }}%;"></div>
                <div class="skeleton-line" style="height:11px;width:{{ 35 + ($i * 7 % 25) }}%;"></div>
            </div>
            <div class="skeleton-line" style="height:24px;width:64px;border-radius:var(--r-pill);flex-shrink:0;"></div>
        </div>
    @endfor
</div>
